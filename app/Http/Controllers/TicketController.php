<?php

namespace App\Http\Controllers;

use Carbon\CarbonInterface;
use App\Http\Requests\UpdateTicketPinRequest;
use App\Http\Requests\UpdateTicketCategoryRequest;
use App\Http\Requests\UpdateTicketAssigneeRequest;
use App\Http\Requests\UpdateTicketPriorityRequest;
use App\Http\Requests\UpdateTicketRequesterRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Http\Requests\UpdateTicketVisibilityRequest;
use App\Models\Announcement;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use App\Policies\TicketPolicy;
use App\Support\LocaleManager;
use App\Support\ResolvesHelpdeskUser;
use App\Services\TicketHistoryService;
use App\Services\TicketAttachmentService;
use App\Services\TicketNotificationService;
use App\Services\TicketWatcherService;
use App\Services\TicketWorkflowAutomationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    use ResolvesHelpdeskUser;

    private const INDEX_FILTERS_SESSION_KEY = 'tickets.index.filters';
    private const DEFAULT_INDEX_SORT = 'updated_at';
    private const DEFAULT_INDEX_DIRECTION = 'desc';
    private const INDEX_SORT_COLUMNS = [
        'number',
        'subject',
        'status',
        'priority',
        'updated_at',
    ];
    private const INDEX_SORT_DIRECTIONS = [
        'asc',
        'desc',
    ];

    public function index(Request $request): View
    {
        $actor = $this->currentHelpdeskUser();
        $filters = $this->resolveTicketIndexFilters($request, $actor);
        $canUseInlineListEditing = $actor instanceof User
            && ($actor->isAdmin() || $actor->isSolver());
        $canViewArchivedTickets = $actor instanceof User
            && $actor->isAdmin()
            && Ticket::supportsArchiving();

        $tickets = $this->applyTicketSorting(
            $this->applyTicketFilters($this->ticketIndexQuery($actor, $filters['archive']), $filters, $actor),
            $filters,
        )
            ->paginate(15)
            ->appends($this->nonDefaultTicketIndexQuery($filters));

        $tickets->getCollection()->transform(function (Ticket $ticket) use ($actor, $canUseInlineListEditing) {
            $ticket->setAttribute(
                'can_inline_status_update',
                $canUseInlineListEditing && $this->ticketPolicy()->updateStatus($actor, $ticket),
            );
            $ticket->setAttribute(
                'can_inline_priority_update',
                $canUseInlineListEditing && $this->ticketPolicy()->updatePriority($actor, $ticket),
            );

            return $ticket;
        });

        $pinnedTickets = collect();

        if (Ticket::supportsPinning() && $filters['archive'] !== 'archived') {
            $pinnedTickets = $this->applyTicketFilters($this->ticketIndexQuery($actor), $filters, $actor)
                ->where('is_pinned', true)
                ->orderByDesc('pinned_at')
                ->orderByDesc('updated_at')
                ->get();
        }

        $activeAnnouncements = Announcement::query()
            ->active()
            ->publicVisible()
            ->orderByDesc('starts_at')
            ->orderByDesc('created_at')
            ->select(['id', 'title', 'body', 'starts_at', 'ends_at']);

        if (Announcement::hasTypeColumn()) {
            $activeAnnouncements->addSelect('type');
        }

        return view('tickets.index', [
            'activeAnnouncements' => $activeAnnouncements->get(),
            'pinnedTickets' => $pinnedTickets,
            'pinningEnabled' => Ticket::supportsPinning(),
            'tickets' => $tickets,
            'filters' => $filters,
            'statuses' => TicketStatus::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']),
            'priorities' => TicketPriority::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']),
            'categories' => TicketCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'visibilityOptions' => Ticket::visibilityOptions(),
            'hasActiveFilters' => collect($this->filterOnlyTicketState($filters))->contains(fn ($value) => $value !== ''),
            'canCreateTickets' => $this->ticketPolicy()->create($actor),
            'canViewArchivedTickets' => $canViewArchivedTickets,
        ]);
    }

    public function create(): View
    {
        abort_unless($this->ticketPolicy()->create($this->currentHelpdeskUser()), 403);

        return view('tickets.create', $this->ticketFormViewData(null));
    }

    public function edit(Ticket $ticket): View
    {
        $this->authorizeTicketAbility('update', $ticket);

        return view('tickets.edit', [
            'ticket' => $ticket,
            ...$this->ticketFormViewData($ticket),
        ]);
    }

    public function show(Ticket $ticket): View
    {
        $this->ensureTicketCanBeViewed($ticket);

        $actor = $this->currentHelpdeskUser();
        $canViewInternalNotes = $this->ticketPolicy()->viewInternalNotes($actor, $ticket);

        $ticket->load([
            'status:id,name,color,slug,is_closed',
            'priority:id,name,color,slug',
            'category:id,name',
            'requester:id,name,display_name,username',
            'assignee:id,name,display_name,username',
            'archivedBy:id,name,display_name,username',
            'directAttachments' => fn ($query) => $query
                ->with('uploader:id,name,display_name,username')
                ->orderBy('created_at'),
            'watchers' => fn ($query) => $query->orderBy('users.name'),
            'history' => fn ($query) => $query
                ->with('user:id,name,display_name,username')
                ->latest('id'),
        ]);

        if ($canViewInternalNotes) {
            $ticket->load([
                'internalComments' => fn ($query) => $query
                    ->with('user:id,name,display_name,username')
                    ->orderBy('created_at'),
            ]);
        } else {
            $ticket->setRelation('internalComments', collect());
        }

        if (TicketComment::supportsThreading()) {
            $ticket->load([
                'publicRootComments' => fn ($query) => $query
                    ->with([
                        'user:id,name,display_name,username',
                        'attachments' => fn ($attachmentQuery) => $attachmentQuery
                            ->with('uploader:id,name,display_name,username')
                            ->orderBy('created_at'),
                        'publicReplies' => fn ($replyQuery) => $replyQuery
                            ->with([
                                'user:id,name,display_name,username',
                                'attachments' => fn ($attachmentQuery) => $attachmentQuery
                                    ->with('uploader:id,name,display_name,username')
                                    ->orderBy('created_at'),
                            ])
                            ->orderBy('created_at'),
                    ])
                    ->orderBy('created_at'),
            ]);

            $publicCommentThreads = $ticket->publicRootComments;
        } else {
            $ticket->load([
                'publicComments' => fn ($query) => $query
                    ->with([
                        'user:id,name,display_name,username',
                        'attachments' => fn ($attachmentQuery) => $attachmentQuery
                            ->with('uploader:id,name,display_name,username')
                            ->orderBy('created_at'),
                    ])
                    ->orderBy('created_at'),
            ]);

            $publicCommentThreads = $ticket->publicComments->map(function (TicketComment $comment) {
                $comment->setRelation('publicReplies', collect());

                return $comment;
            });
        }

        $originalSnapshotEntry = $this->originalSnapshotEntry($ticket);
        $originalVersionSnapshot = $this->extractOriginalVersionSnapshot($originalSnapshotEntry?->new_value ?? []);
        $currentOriginalVersionSnapshot = $this->extractOriginalVersionSnapshot($this->captureTicketSnapshot($ticket));
        $canUpdateStatus = $this->ticketPolicy()->updateStatus($actor, $ticket);
        $canUpdatePriority = $this->ticketPolicy()->updatePriority($actor, $ticket);
        $canUpdateVisibility = $this->ticketPolicy()->updateVisibility($actor, $ticket);
        $canUpdateRequester = $this->ticketPolicy()->updateRequester($actor, $ticket);
        $canUpdateAssignee = $this->ticketPolicy()->updateAssignee($actor, $ticket);
        $canUpdateCategory = $this->ticketPolicy()->updateCategory($actor, $ticket);
        $canUpdatePin = $this->ticketPolicy()->updatePin($actor, $ticket);
        $canWatchTicket = $this->ticketPolicy()->watch($actor, $ticket);
        $canCommentPublic = $this->ticketPolicy()->commentPublic($actor, $ticket);
        $canCreateInternalNote = $this->ticketPolicy()->commentInternal($actor, $ticket);
        $canDeleteAttachments = $this->ticketPolicy()->deleteAttachment($actor, $ticket);
        $canEditTicket = $this->ticketPolicy()->update($actor, $ticket);
        $canConfirmResolution = $this->ticketPolicy()->confirmResolution($actor, $ticket);
        $canReportProblemPersists = $this->ticketPolicy()->reportProblemPersists($actor, $ticket);
        $canArchiveTicket = $this->ticketPolicy()->archive($actor, $ticket);
        $canRestoreTicket = $this->ticketPolicy()->restore($actor, $ticket);
        $people = User::query()->orderBy('name')->get(['id', 'name', 'display_name', 'username']);
        $assignableSolvers = User::query()
            ->assignableSolvers()
            ->orderBy('name')
            ->get(['id', 'name', 'display_name', 'username']);

        return view('tickets.show', [
            'ticket' => $ticket,
            'statuses' => TicketStatus::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']),
            'requesters' => $people,
            'assignees' => $assignableSolvers,
            'priorities' => TicketPriority::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']),
            'categories' => TicketCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'pinningEnabled' => Ticket::supportsPinning(),
            'visibilityOptions' => Ticket::visibilityOptions(),
            'watcherActionEnabled' => $canWatchTicket,
            'isWatchingTicket' => $actor instanceof User
                ? $ticket->watchers->contains('id', $actor->id)
                : false,
            'commentThreadingEnabled' => TicketComment::supportsThreading(),
            'publicCommentThreads' => $publicCommentThreads,
            'originalSnapshot' => $originalVersionSnapshot,
            'originalSnapshotSource' => $originalSnapshotEntry?->meta['source'] ?? null,
            'hasOriginalVersionChanges' => $originalSnapshotEntry instanceof TicketHistory
                && $this->snapshotHasDifferences($originalVersionSnapshot, $currentOriginalVersionSnapshot),
            'canEditTicket' => $canEditTicket,
            'canUpdateStatus' => $canUpdateStatus,
            'canUpdatePriority' => $canUpdatePriority,
            'canUpdateVisibility' => $canUpdateVisibility,
            'canUpdateRequester' => $canUpdateRequester,
            'canUpdateAssignee' => $canUpdateAssignee,
            'canUpdateCategory' => $canUpdateCategory,
            'canUpdatePin' => $canUpdatePin,
            'canCommentPublic' => $canCommentPublic,
            'canViewInternalNotes' => $canViewInternalNotes,
            'canCreateInternalNote' => $canCreateInternalNote,
            'canDeleteAttachments' => $canDeleteAttachments,
            'canConfirmResolution' => $canConfirmResolution,
            'canReportProblemPersists' => $canReportProblemPersists,
            'canArchiveTicket' => $canArchiveTicket,
            'canRestoreTicket' => $canRestoreTicket,
        ]);
    }

    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        $this->authorizeTicketAbility('updateStatus', $ticket);

        $selectedStatusId = $request->integer('status_id');
        $selectedStatus = TicketStatus::query()->find($selectedStatusId);

        return $this->applyTicketUpdateWithHistory(
            $ticket,
            $selectedStatus instanceof TicketStatus
                ? $this->workflowAutomationService()->attributesForStatusTransition($ticket, $selectedStatus)
                : ['ticket_status_id' => $selectedStatusId],
            __('tickets.flash.status_updated'),
            'status_update',
            $request,
            'tickets.flash.status_updated',
        );
    }

    public function updateRequester(UpdateTicketRequesterRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('updateRequester', $ticket);

        return $this->applyTicketUpdateWithHistory($ticket, [
            'requester_id' => $request->integer('requester_id'),
        ], __('tickets.flash.requester_updated'), 'requester_update');
    }

    public function updateAssignee(UpdateTicketAssigneeRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('updateAssignee', $ticket);

        $assigneeId = $request->filled('assignee_id')
            ? (int) $request->input('assignee_id')
            : null;

        return $this->applyTicketUpdateWithHistory($ticket, [
            ...$this->workflowAutomationService()->attributesForAssigneeUpdate($ticket, $assigneeId),
        ], __('tickets.flash.assignee_updated'), 'assignee_update');
    }

    public function updatePriority(UpdateTicketPriorityRequest $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        $this->authorizeTicketAbility('updatePriority', $ticket);

        return $this->applyTicketUpdateWithHistory(
            $ticket,
            $this->workflowAutomationService()->attributesForPriorityUpdate($ticket, $request->integer('priority_id')),
            __('tickets.flash.priority_updated'),
            'priority_update',
            $request,
            'tickets.flash.priority_updated',
        );
    }

    public function updateCategory(UpdateTicketCategoryRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('updateCategory', $ticket);

        return $this->applyTicketUpdateWithHistory($ticket, [
            'ticket_category_id' => $request->integer('category_id'),
        ], __('tickets.flash.category_updated'), 'category_update');
    }

    public function updateVisibility(UpdateTicketVisibilityRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('updateVisibility', $ticket);

        return $this->applyTicketUpdateWithHistory($ticket, [
            'visibility' => $request->string('visibility')->toString(),
        ], __('tickets.flash.visibility_updated'), 'visibility_update');
    }

    public function updatePin(UpdateTicketPinRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('updatePin', $ticket);

        if (! Ticket::supportsPinning()) {
            throw ValidationException::withMessages([
                'pinned' => __('tickets.validation.pinning_unavailable'),
            ])->errorBag('ticketPin');
        }

        $isPinned = $request->boolean('pinned');

        return $this->applyTicketUpdateWithHistory($ticket, [
            'is_pinned' => $isPinned,
            'pinned_at' => $isPinned ? now() : null,
        ], $isPinned
            ? __('tickets.flash.ticket_pinned')
            : __('tickets.flash.ticket_unpinned'), 'pin_update');
    }

    public function archive(Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('archive', $ticket);

        return $this->applyTicketUpdateWithHistory($ticket, [
            'archived_at' => now(),
            'archived_by_user_id' => $this->currentHelpdeskUser()?->id,
        ], __('tickets.flash.ticket_archived'), 'ticket_archive');
    }

    public function restore(Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('restore', $ticket);

        return $this->applyTicketUpdateWithHistory($ticket, [
            'archived_at' => null,
            'archived_by_user_id' => null,
        ], __('tickets.flash.ticket_restored'), 'ticket_restore');
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->currentHelpdeskUser();
        abort_unless($this->ticketPolicy()->create($actor), 403);

        $validated = $this->validateTicketInput(
            $request,
            allowVisibility: false,
            allowSensitiveVisibility: true,
        );

        $initialStatus = $this->resolveInitialStatus();
        $attributes = [
            ...$this->buildEditableTicketAttributes($validated),
            'visibility' => $request->boolean('is_sensitive')
                ? Ticket::VISIBILITY_INTERNAL
                : Ticket::VISIBILITY_PUBLIC,
            'requester_id' => $this->resolveRequester()->id,
            'assignee_id' => null,
            'ticket_status_id' => $initialStatus->id,
        ];

        $ticket = Ticket::query()->create($attributes);

        $ticket->update([
            'ticket_number' => $this->generateTicketNumber($ticket),
        ]);

        $ticket->refresh();
        $ticket->load($this->ticketSnapshotRelations());
        $this->ensureOriginalSnapshot($ticket, 'create');
        $this->ticketAttachmentService()->storeMany(
            $ticket,
            $request->file('attachments', []),
            $actor,
            visibility: 'public',
        );
        $this->ticketWatcherService()->syncAutomaticParticipants($ticket);
        $this->ticketNotificationService()->notify($ticket, 'created', $actor, excludeActor: false);

        return redirect()
            ->route('tickets.index')
            ->with('status', 'Ticket byl úspěšně vytvořen.');
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('update', $ticket);

        $canManageVisibility = $this->ticketPolicy()->updateVisibility($this->currentHelpdeskUser(), $ticket);
        $canManageExpectedResolution = $this->ticketPolicy()->updateExpectedResolution($this->currentHelpdeskUser(), $ticket)
            && Ticket::supportsExpectedResolution();
        $validated = $this->validateTicketInput(
            $request,
            allowVisibility: $canManageVisibility,
            allowExpectedResolution: $canManageExpectedResolution,
        );

        return $this->applyTicketUpdateWithHistory(
            $ticket,
            $this->workflowAutomationService()->attributesForTicketUpdate(
                $ticket,
                $this->currentHelpdeskUser(),
                $this->buildEditableTicketAttributes($validated),
            ),
            'Ticket byl úspěšně upraven.',
            'ticket_update',
        );
    }

    public function confirmResolution(Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('confirmResolution', $ticket);

        $closedStatus = $this->resolveStatusByIdentifiers(
            ['closed'],
            'workflow',
            __('tickets.validation.workflow_closed_status_missing'),
        );

        return $this->applyTicketUpdateWithHistory(
            $ticket,
            $this->workflowAutomationService()->attributesForStatusTransition($ticket, $closedStatus),
            __('tickets.flash.resolution_confirmed'),
            'requester_confirm_resolution',
        );
    }

    public function reportProblemPersists(Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('reportProblemPersists', $ticket);

        $reopenedStatus = $this->resolveStatusByIdentifiers(
            $ticket->assignee_id !== null
                ? ['in_progress', 'assigned']
                : ['new'],
            'workflow',
            __('tickets.validation.workflow_assigned_status_missing'),
        );

        return $this->applyTicketUpdateWithHistory(
            $ticket,
            $this->workflowAutomationService()->attributesForStatusTransition($ticket, $reopenedStatus),
            __('tickets.flash.problem_persists'),
            'requester_report_problem_persists',
        );
    }

    private function resolveRequester(): User
    {
        return $this->requireHelpdeskUser(
            'Pro vytvoření ticketu zatím musí v databázi existovat alespoň jeden uživatel.',
            'requester',
        );
    }

    private function resolveInitialStatus(): TicketStatus
    {
        $status = $this->findStatusBySlugOrCode('new');

        if ($status instanceof TicketStatus) {
            return $status;
        }

        throw ValidationException::withMessages([
            'status' => __('tickets.validation.initial_status_missing'),
        ]);
    }

    private function findStatusBySlugOrCode(string $identifier): ?TicketStatus
    {
        return TicketStatus::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('slug', $identifier);

                if (Schema::hasColumn('ticket_statuses', 'code')) {
                    $query->orWhere('code', $identifier);
                }
            })
            ->first();
    }

    private function generateTicketNumber(Ticket $ticket): string
    {
        $createdAt = $ticket->created_at ?? now();
        $year = (int) $createdAt->format('Y');
        $sequence = Ticket::query()
            ->whereYear('created_at', $year)
            ->where($ticket->getQualifiedKeyName(), '<=', $ticket->getKey())
            ->count();

        return sprintf('%d-%03d', $year, $sequence);
    }

    private function applyTicketUpdateWithHistory(
        Ticket $ticket,
        array $attributes,
        string $successMessage,
        string $action,
        ?Request $request = null,
        ?string $jsonSuccessMessageKey = null,
    ): RedirectResponse|JsonResponse {
        $actor = $this->currentHelpdeskUser();
        $beforeNotificationSnapshot = $this->ticketNotificationSnapshot($ticket);
        $attributes = $this->withExpectedResolutionNotificationReset($ticket, $attributes);
        $ticket = $this->ticketHistoryService()->applyUpdateWithHistory(
            $ticket,
            $attributes,
            $action,
            $actor,
        );
        $this->ticketWatcherService()->syncAutomaticParticipants($ticket);
        $this->sendTicketUpdateNotification($ticket, $action, $actor, $beforeNotificationSnapshot);

        if ($request instanceof Request && $request->expectsJson()) {
            $responseLocale = app(LocaleManager::class)->resolveForRequest($request);

            return response()->json([
                'message' => $jsonSuccessMessageKey !== null
                    ? __($jsonSuccessMessageKey, [], $responseLocale)
                    : $successMessage,
                'ticket' => $this->inlineTicketResponsePayload($ticket, $responseLocale),
            ]);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', $successMessage);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withExpectedResolutionNotificationReset(Ticket $ticket, array $attributes): array
    {
        if (! array_key_exists('expected_resolution_at', $attributes)) {
            return $attributes;
        }

        $newExpectedResolutionAt = $attributes['expected_resolution_at'];
        $newValue = $newExpectedResolutionAt instanceof \DateTimeInterface
            ? $newExpectedResolutionAt->format('c')
            : (is_string($newExpectedResolutionAt) ? $newExpectedResolutionAt : null);
        $oldValue = $ticket->expected_resolution_at?->format('c');

        if ($oldValue === $newValue) {
            return $attributes;
        }

        return array_replace($attributes, [
            'expected_resolution_due_soon_notified_at' => null,
            'expected_resolution_overdue_notified_at' => null,
        ]);
    }

    private function inlineTicketResponsePayload(Ticket $ticket, ?string $locale = null): array
    {
        $ticket->load([
            'status:id,name,slug',
            'priority:id,name,slug',
        ]);

        return [
            'id' => $ticket->id,
            'status' => [
                'id' => $ticket->ticket_status_id,
                'name' => $ticket->status?->translatedName($locale) ?? __('tickets.common.not_available', [], $locale),
                'badge_class' => $ticket->status?->badgeToneClass() ?? 'badge-tone-slate',
            ],
            'priority' => [
                'id' => $ticket->ticket_priority_id,
                'name' => $ticket->priority?->translatedName($locale) ?? __('tickets.common.not_available', [], $locale),
                'badge_class' => $ticket->priority?->badgeToneClass() ?? 'badge-tone-slate',
            ],
            'updated_at' => $ticket->updated_at?->toIso8601String(),
            'updated_at_display' => $this->formatListUpdatedAt($ticket->updated_at, $locale),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ticketNotificationSnapshot(Ticket $ticket): array
    {
        return [
            'assignee_id' => $ticket->assignee_id !== null ? (int) $ticket->assignee_id : null,
            'ticket_status_id' => $ticket->ticket_status_id !== null ? (int) $ticket->ticket_status_id : null,
            'expected_resolution_at' => $ticket->expected_resolution_at?->toIso8601String(),
            'expected_resolution_source' => Ticket::supportsExpectedResolutionSource()
                ? $ticket->expected_resolution_source
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     */
    private function sendTicketUpdateNotification(Ticket $ticket, string $action, ?User $actor, array $before): void
    {
        $ticket->load([
            'status:id,name,slug',
            'assignee:id,name,display_name,username,email,preferred_locale',
        ]);

        $event = match ($action) {
            'assignee_update' => (int) ($before['assignee_id'] ?? 0) !== (int) ($ticket->assignee_id ?? 0)
                ? 'assignee_changed'
                : null,
            'status_update' => (int) ($before['ticket_status_id'] ?? 0) !== (int) ($ticket->ticket_status_id ?? 0)
                ? $this->ticketStatusNotificationEvent($ticket)
                : null,
            'requester_confirm_resolution' => 'closed',
            'requester_report_problem_persists' => 'problem_persists',
            default => null,
        };

        if ($event === null && (int) ($before['ticket_status_id'] ?? 0) !== (int) ($ticket->ticket_status_id ?? 0)) {
            $event = $this->ticketStatusNotificationEvent($ticket);
        }

        if ($event === null
            && $action === 'ticket_update'
            && ($before['expected_resolution_at'] ?? null) !== $ticket->expected_resolution_at?->toIso8601String()
        ) {
            $event = 'expected_resolution_changed';
        }

        if ($event === null) {
            return;
        }

        $this->ticketNotificationService()->notify($ticket, $event, $actor, [
            'assignee' => $ticket->assignee?->displayName() ?? __('tickets.common.unassigned'),
            'close_reason' => $this->closeReasonForNotification($ticket, $action, $actor),
            'old_expected_resolution_at' => $before['expected_resolution_at'] ?? null,
        ]);
    }

    private function closeReasonForNotification(Ticket $ticket, string $action, ?User $actor): ?string
    {
        if ($action !== 'requester_confirm_resolution') {
            return null;
        }

        if ($actor instanceof User && (int) $actor->id === (int) $ticket->requester_id) {
            return 'requester_confirmed';
        }

        return 'manual';
    }

    private function ticketStatusNotificationEvent(Ticket $ticket): string
    {
        return match ($ticket->status?->slug) {
            'resolved' => 'resolved',
            'closed' => 'closed',
            default => 'status_changed',
        };
    }

    private function resolveStatusByIdentifiers(
        array $identifiers,
        string $field,
        string $message,
        ?string $errorBag = null,
    ): TicketStatus {
        foreach ($identifiers as $identifier) {
            $status = $this->findStatusBySlugOrCode($identifier);

            if ($status instanceof TicketStatus) {
                return $status;
            }
        }

        $exception = ValidationException::withMessages([
            $field => $message,
        ]);

        if ($errorBag !== null) {
            $exception->errorBag($errorBag);
        }

        throw $exception;
    }

    private function ticketFormViewData(?Ticket $ticket): array
    {
        $actor = $this->currentHelpdeskUser();

        return [
            'priorities' => TicketPriority::query()->orderBy('sort_order')->orderBy('name')->get(),
            'categories' => TicketCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'visibilityOptions' => Ticket::visibilityOptions(),
            'canManageVisibility' => $ticket instanceof Ticket
                ? $this->ticketPolicy()->updateVisibility($actor, $ticket)
                : false,
            'canManageExpectedResolution' => $ticket instanceof Ticket
                ? $this->ticketPolicy()->updateExpectedResolution($actor, $ticket) && Ticket::supportsExpectedResolution()
                : false,
            'expectedResolutionEnabled' => Ticket::supportsExpectedResolution(),
        ];
    }

    private function ticketIndexQuery(?User $watcherUser = null, string $archiveFilter = ''): Builder
    {
        $query = Ticket::query()
            ->visibleTo($watcherUser)
            ->select('tickets.*')
            ->with([
                'status:id,name,color,slug',
                'priority:id,name,color,slug',
                'requester:id,name,display_name,username',
                'assignee:id,name,display_name,username',
            ])
            ->withCount('publicComments');

        if ($watcherUser instanceof User) {
            $query->withExists([
                'watchers as is_watched_by_current_user' => fn (Builder $query) => $query->whereKey($watcherUser->id),
            ]);
        } else {
            $query->selectRaw('0 as is_watched_by_current_user');
        }

        if (Ticket::supportsArchiving()) {
            if ($watcherUser instanceof User && $watcherUser->isAdmin() && $archiveFilter === 'archived') {
                $query->whereNotNull('archived_at');
            } else {
                $query->whereNull('archived_at');
            }
        }

        return $query;
    }

    private function applyTicketFilters(Builder $query, array $filters, ?User $watcherUser = null): Builder
    {
        return $query
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = '%'.addcslashes($filters['search'], '\\%_').'%';

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('subject', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            })
            ->when($filters['status'] !== '', function (Builder $query) use ($filters): void {
                if (ctype_digit($filters['status'])) {
                    $query->where('ticket_status_id', (int) $filters['status']);

                    return;
                }

                $query->whereHas('status', fn (Builder $query) => $this->whereStatusIdentifiers($query, [$filters['status']]));
            })
            ->when($filters['priority'] !== '', function (Builder $query) use ($filters): void {
                $query->where('ticket_priority_id', (int) $filters['priority']);
            })
            ->when($filters['category'] !== '', function (Builder $query) use ($filters): void {
                $query->where('ticket_category_id', (int) $filters['category']);
            })
            ->when($filters['relation'] !== '', function (Builder $query) use ($filters, $watcherUser): void {
                $this->applyTicketRelationFilter($query, $filters['relation'], $watcherUser);
            })
            ->when($filters['scope'] === 'open', function (Builder $query): void {
                $query->whereDoesntHave('status', fn (Builder $query) => $this->whereStatusIdentifiers($query, ['closed', 'cancelled']));
            })
            ->when($filters['scope'] === 'finished', function (Builder $query): void {
                $query->whereHas('status', fn (Builder $query) => $this->whereStatusIdentifiers($query, ['closed', 'cancelled']));
            })
            ->when($filters['due'] === 'overdue_or_soon', function (Builder $query): void {
                if (! Ticket::supportsExpectedResolution()) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query
                    ->whereNotNull('expected_resolution_at')
                    ->where('expected_resolution_at', '<=', Carbon::now()->addDays(3));
            })
            ->when($filters['due'] === 'missing_expected_resolution', function (Builder $query): void {
                if (! Ticket::supportsExpectedResolution()) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereNull('expected_resolution_at');
            })
            ->when($filters['watched'] === '1', function (Builder $query) use ($watcherUser): void {
                if (! $watcherUser instanceof User) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereHas('watchers', fn (Builder $query) => $query->whereKey($watcherUser->id));
            });
    }

    private function applyTicketRelationFilter(Builder $query, string $relation, ?User $actor): void
    {
        if (! $actor instanceof User) {
            $query->whereRaw('1 = 0');

            return;
        }

        match ($relation) {
            'requester' => $query->where('requester_id', $actor->id),
            'assigned' => $query->where('assignee_id', $actor->id),
            'watched' => $query->whereHas('watchers', fn (Builder $query) => $query->whereKey($actor->id)),
            'unassigned' => $query->whereNull('assignee_id'),
            default => null,
        };
    }

    private function whereStatusIdentifiers(Builder $query, array $identifiers): void
    {
        $query->whereIn('slug', $identifiers);

        if (Schema::hasColumn('ticket_statuses', 'code')) {
            $query->orWhereIn('code', $identifiers);
        }
    }

    private function applyTicketSorting(Builder $query, array $filters): Builder
    {
        $sort = $filters['sort'] ?? self::DEFAULT_INDEX_SORT;
        $direction = $filters['direction'] ?? self::DEFAULT_INDEX_DIRECTION;

        if (! in_array($sort, self::INDEX_SORT_COLUMNS, true)) {
            $sort = self::DEFAULT_INDEX_SORT;
        }

        if (! in_array($direction, self::INDEX_SORT_DIRECTIONS, true)) {
            $direction = self::DEFAULT_INDEX_DIRECTION;
        }

        if ($sort === 'status') {
            return $query
                ->leftJoin('ticket_statuses as sort_statuses', 'sort_statuses.id', '=', 'tickets.ticket_status_id')
                ->orderBy('sort_statuses.sort_order', $direction)
                ->orderBy('sort_statuses.name', $direction)
                ->orderBy('tickets.updated_at', 'desc')
                ->orderBy('tickets.id', 'desc');
        }

        if ($sort === 'priority') {
            return $query
                ->leftJoin('ticket_priorities as sort_priorities', 'sort_priorities.id', '=', 'tickets.ticket_priority_id')
                ->orderBy('sort_priorities.sort_order', $direction)
                ->orderBy('sort_priorities.name', $direction)
                ->orderBy('tickets.updated_at', 'desc')
                ->orderBy('tickets.id', 'desc');
        }

        $column = match ($sort) {
            'number' => 'tickets.ticket_number',
            'subject' => 'tickets.subject',
            default => 'tickets.updated_at',
        };

        return $query
            ->orderBy($column, $direction)
            ->orderBy('tickets.id', 'desc');
    }

    private function resolveTicketIndexFilters(Request $request, ?User $actor): array
    {
        if ($request->boolean('reset')) {
            $filters = $this->removeUnauthorizedArchiveFilter($this->defaultTicketFilters(), $actor);
            $request->session()->put(self::INDEX_FILTERS_SESSION_KEY, $filters);

            return $filters;
        }

        $filterKeys = array_keys($this->defaultTicketFilters());
        $hasFilterQuery = false;

        foreach ($filterKeys as $filterKey) {
            if ($request->query->has($filterKey)) {
                $hasFilterQuery = true;
                break;
            }
        }

        if ($hasFilterQuery) {
            $filters = $this->normalizeTicketFilters($request->only($filterKeys));
            $filters = $this->removeUnauthorizedArchiveFilter($filters, $actor);
            $request->session()->put(self::INDEX_FILTERS_SESSION_KEY, $filters);

            return $filters;
        }

        return $this->removeUnauthorizedArchiveFilter($this->normalizeTicketFilters(
            (array) $request->session()->get(self::INDEX_FILTERS_SESSION_KEY, $this->defaultTicketFilters()),
        ), $actor);
    }

    private function defaultTicketFilters(): array
    {
        return [
            'search' => '',
            'status' => '',
            'priority' => '',
            'category' => '',
            'relation' => '',
            'scope' => '',
            'due' => '',
            'watched' => '',
            'archive' => '',
            'sort' => self::DEFAULT_INDEX_SORT,
            'direction' => self::DEFAULT_INDEX_DIRECTION,
        ];
    }

    private function normalizeTicketFilters(array $filters): array
    {
        $sort = (string) ($filters['sort'] ?? self::DEFAULT_INDEX_SORT);
        $direction = (string) ($filters['direction'] ?? self::DEFAULT_INDEX_DIRECTION);
        $scope = (string) ($filters['scope'] ?? '');
        $relation = (string) ($filters['relation'] ?? '');
        $due = (string) ($filters['due'] ?? '');
        $archive = (string) ($filters['archive'] ?? '');

        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'status' => $this->normalizeStatusFilter((string) ($filters['status'] ?? '')),
            'priority' => (string) ($filters['priority'] ?? ''),
            'category' => (string) ($filters['category'] ?? ''),
            'relation' => in_array($relation, ['requester', 'assigned', 'watched', 'unassigned'], true) ? $relation : '',
            'scope' => in_array($scope, ['open', 'finished'], true) ? $scope : '',
            'due' => in_array($due, ['overdue_or_soon', 'missing_expected_resolution'], true) ? $due : '',
            'watched' => (string) ($filters['watched'] ?? '') === '1' ? '1' : '',
            'archive' => $archive === 'archived' ? 'archived' : '',
            'sort' => in_array($sort, self::INDEX_SORT_COLUMNS, true) ? $sort : self::DEFAULT_INDEX_SORT,
            'direction' => in_array($direction, self::INDEX_SORT_DIRECTIONS, true) ? $direction : self::DEFAULT_INDEX_DIRECTION,
        ];
    }

    private function normalizeStatusFilter(string $status): string
    {
        if ($status === '') {
            return '';
        }

        if (ctype_digit($status)) {
            return $status;
        }

        $query = TicketStatus::query()->where('slug', $status);

        if (Schema::hasColumn('ticket_statuses', 'code')) {
            $query->orWhere('code', $status);
        }

        $statusModel = $query->first(['id']);

        return $statusModel instanceof TicketStatus ? (string) $statusModel->id : '';
    }

    private function removeUnauthorizedArchiveFilter(array $filters, ?User $actor): array
    {
        if (! $actor instanceof User || ! $actor->isAdmin() || ! Ticket::supportsArchiving()) {
            $filters['archive'] = '';
        }

        return $filters;
    }

    private function nonDefaultTicketIndexQuery(array $filters): array
    {
        $query = array_filter($this->filterOnlyTicketState($filters), fn (string $value) => $value !== '');

        if (($filters['sort'] ?? self::DEFAULT_INDEX_SORT) !== self::DEFAULT_INDEX_SORT) {
            $query['sort'] = $filters['sort'];
        }

        if (($filters['direction'] ?? self::DEFAULT_INDEX_DIRECTION) !== self::DEFAULT_INDEX_DIRECTION) {
            $query['direction'] = $filters['direction'];
        }

        return $query;
    }

    private function filterOnlyTicketState(array $filters): array
    {
        return [
            'search' => (string) ($filters['search'] ?? ''),
            'status' => (string) ($filters['status'] ?? ''),
            'priority' => (string) ($filters['priority'] ?? ''),
            'category' => (string) ($filters['category'] ?? ''),
            'relation' => (string) ($filters['relation'] ?? ''),
            'scope' => (string) ($filters['scope'] ?? ''),
            'due' => (string) ($filters['due'] ?? ''),
            'watched' => (string) ($filters['watched'] ?? ''),
            'archive' => (string) ($filters['archive'] ?? ''),
        ];
    }

    private function formatListUpdatedAt(?CarbonInterface $value, ?string $locale = null): string
    {
        if (! $value instanceof CarbonInterface) {
            return __('tickets.common.not_available', [], $locale);
        }

        return $value
            ->locale($locale ?? app()->getLocale())
            ->translatedFormat(__('tickets.formats.list_updated_at', [], $locale));
    }

    private function ticketSnapshotRelations(): array
    {
        return [
            'status:id,name',
            'priority:id,name',
            'category:id,name',
            'requester:id,name,display_name,username',
            'assignee:id,name,display_name,username',
            'archivedBy:id,name,display_name,username',
        ];
    }

    private function validateTicketInput(
        Request $request,
        bool $allowVisibility,
        bool $allowSensitiveVisibility = false,
        bool $allowExpectedResolution = false,
    ): array
    {
        $rules = [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority_id' => ['required', 'integer', 'exists:ticket_priorities,id'],
            'category_id' => ['required', 'integer', 'exists:ticket_categories,id'],
        ];

        if ($allowVisibility) {
            $rules['visibility'] = ['nullable', 'in:public,internal,private'];
        }

        if ($allowSensitiveVisibility) {
            $rules['is_sensitive'] = ['nullable', 'boolean'];
        }

        if ($allowExpectedResolution) {
            $rules['expected_resolution_at'] = ['nullable', 'date'];
        }

        if ($allowSensitiveVisibility) {
            $rules = [
                ...$rules,
                ...$this->ticketAttachmentService()->validationRules(),
            ];
        }

        return $request->validate($rules);
    }

    private function buildEditableTicketAttributes(array $validated): array
    {
        $attributes = [
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'ticket_priority_id' => $validated['priority_id'],
            'ticket_category_id' => $validated['category_id'],
        ];

        if (array_key_exists('visibility', $validated) && $validated['visibility'] !== null) {
            $attributes['visibility'] = $validated['visibility'];
        }

        if (array_key_exists('expected_resolution_at', $validated)) {
            $attributes['expected_resolution_at'] = $validated['expected_resolution_at'] !== null
                ? Carbon::parse($validated['expected_resolution_at'])
                : null;
            $attributes['expected_resolution_source'] = $validated['expected_resolution_at'] !== null
                ? TicketWorkflowAutomationService::EXPECTED_RESOLUTION_SOURCE_MANUAL
                : null;
        }

        return $attributes;
    }

    private function ensureOriginalSnapshot(Ticket $ticket, string $source): void
    {
        if ($this->originalSnapshotEntry($ticket) instanceof TicketHistory) {
            return;
        }

        $ticket->loadMissing($this->ticketSnapshotRelations());

        $ticket->history()->create([
            'user_id' => $this->resolveHistoryActorId(),
            'event' => $source === 'create'
                ? TicketHistory::EVENT_CREATED
                : TicketHistory::EVENT_ORIGINAL_SNAPSHOT_BACKFILLED,
            'field' => TicketHistory::FIELD_SNAPSHOT,
            'old_value' => null,
            'new_value' => $this->captureTicketSnapshot($ticket),
            'meta' => [
                'source' => $source,
            ],
        ]);
    }

    private function originalSnapshotEntry(Ticket $ticket): ?TicketHistory
    {
        return $ticket->history()
            ->where('field', TicketHistory::FIELD_SNAPSHOT)
            ->whereIn('event', [
                TicketHistory::EVENT_CREATED,
                TicketHistory::EVENT_ORIGINAL_SNAPSHOT_BACKFILLED,
            ])
            ->oldest('id')
            ->first();
    }

    private function recordSnapshotUpdate(
        Ticket $ticket,
        array $oldSnapshot,
        array $newSnapshot,
        string $action,
    ): void {
        if (! $this->snapshotHasDifferences($oldSnapshot, $newSnapshot)) {
            return;
        }

        $ticket->history()->create([
            'user_id' => $this->resolveHistoryActorId(),
            'event' => TicketHistory::EVENT_UPDATED,
            'field' => TicketHistory::FIELD_SNAPSHOT,
            'old_value' => $oldSnapshot,
            'new_value' => $newSnapshot,
            'meta' => [
                'action' => $action,
                'changed_fields' => $this->snapshotChangedFields($oldSnapshot, $newSnapshot),
            ],
        ]);
    }

    private function captureTicketSnapshot(Ticket $ticket): array
    {
        $ticket->loadMissing($this->ticketSnapshotRelations());

        return [
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'visibility' => $ticket->visibility,
            'status' => [
                'id' => $ticket->ticket_status_id,
                'name' => $ticket->status?->name,
            ],
            'priority' => [
                'id' => $ticket->ticket_priority_id,
                'name' => $ticket->priority?->name,
            ],
            'category' => [
                'id' => $ticket->ticket_category_id,
                'name' => $ticket->category?->name,
            ],
            'requester' => [
                'id' => $ticket->requester_id,
                'name' => $ticket->requester?->displayName(),
            ],
            'assignee' => $ticket->assignee_id
                ? [
                    'id' => $ticket->assignee_id,
                    'name' => $ticket->assignee?->displayName(),
                ]
                : null,
            'pinned' => Ticket::supportsPinning()
                ? [
                    'is_pinned' => (bool) $ticket->is_pinned,
                    'pinned_at' => $ticket->pinned_at?->toIso8601String(),
                ]
                : null,
            'expected_resolution_at' => $ticket->expected_resolution_at?->toIso8601String(),
            'expected_resolution_source' => Ticket::supportsExpectedResolutionSource()
                ? $ticket->expected_resolution_source
                : null,
            'closed_at' => $ticket->closed_at?->toIso8601String(),
            'archived_at' => Ticket::supportsArchiving()
                ? $ticket->archived_at?->toIso8601String()
                : null,
            'archived_by' => Ticket::supportsArchiving() && $ticket->archived_by_user_id
                ? [
                    'id' => $ticket->archived_by_user_id,
                    'name' => $ticket->archivedBy?->displayName(),
                ]
                : null,
            'created_at' => $ticket->created_at?->toIso8601String(),
        ];
    }

    private function snapshotHasDifferences(array $oldSnapshot, array $newSnapshot): bool
    {
        return $this->snapshotChangedFields($oldSnapshot, $newSnapshot) !== [];
    }

    private function snapshotChangedFields(array $oldSnapshot, array $newSnapshot): array
    {
        $changedFields = [];

        foreach (array_unique([...array_keys($oldSnapshot), ...array_keys($newSnapshot)]) as $field) {
            if (($oldSnapshot[$field] ?? null) !== ($newSnapshot[$field] ?? null)) {
                $changedFields[] = $field;
            }
        }

        return $changedFields;
    }

    private function extractOriginalVersionSnapshot(array $snapshot): array
    {
        return [
            'subject' => $snapshot['subject'] ?? null,
            'description' => $snapshot['description'] ?? null,
        ];
    }

    private function resolveHistoryActorId(): ?int
    {
        return $this->currentHelpdeskUser()?->id;
    }

    private function authorizeTicketAbility(string $ability, Ticket $ticket): void
    {
        abort_unless(
            $this->ticketPolicy()->{$ability}($this->currentHelpdeskUser(), $ticket),
            403,
        );
    }

    private function ticketPolicy(): TicketPolicy
    {
        return app(TicketPolicy::class);
    }

    private function ensureTicketCanBeViewed(Ticket $ticket): void
    {
        $this->authorizeTicketAbility('view', $ticket);
    }

    private function workflowAutomationService(): TicketWorkflowAutomationService
    {
        return app(TicketWorkflowAutomationService::class);
    }

    private function ticketHistoryService(): TicketHistoryService
    {
        return app(TicketHistoryService::class);
    }

    private function ticketNotificationService(): TicketNotificationService
    {
        return app(TicketNotificationService::class);
    }

    private function ticketAttachmentService(): TicketAttachmentService
    {
        return app(TicketAttachmentService::class);
    }

    private function ticketWatcherService(): TicketWatcherService
    {
        return app(TicketWatcherService::class);
    }
}
