<?php

namespace App\Http\Controllers;

use Carbon\CarbonInterface;
use App\Http\Requests\UpdateTicketPinRequest;
use App\Http\Requests\UpdateTicketCategoryRequest;
use App\Http\Requests\UpdateTicketAssigneeRequest;
use App\Http\Requests\UpdateTicketPriorityRequest;
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
use App\Support\ResolvesHelpdeskUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    use ResolvesHelpdeskUser;

    private const INDEX_FILTERS_SESSION_KEY = 'tickets.index.filters';

    public function index(Request $request): View
    {
        $filters = $this->resolveTicketIndexFilters($request);
        $actor = $this->currentHelpdeskUser();
        $canUseInlineListEditing = $actor instanceof User
            && ($actor->isAdmin() || $actor->isSolver());

        $tickets = $this->applyTicketFilters($this->ticketIndexQuery($actor), $filters, $actor)
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->appends($this->nonEmptyTicketFilters($filters));

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

        if (Ticket::supportsPinning()) {
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
            'hasActiveFilters' => collect($filters)->contains(fn ($value) => $value !== ''),
            'canCreateTickets' => $this->ticketPolicy()->create($actor),
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
            'requester:id,name',
            'assignee:id,name',
            'watchers' => fn ($query) => $query->orderBy('users.name'),
            'history' => fn ($query) => $query
                ->with('user:id,name')
                ->latest('id'),
        ]);

        if ($canViewInternalNotes) {
            $ticket->load([
                'internalComments' => fn ($query) => $query
                    ->with('user:id,name')
                    ->orderBy('created_at'),
            ]);
        } else {
            $ticket->setRelation('internalComments', collect());
        }

        if (TicketComment::supportsThreading()) {
            $ticket->load([
                'publicRootComments' => fn ($query) => $query
                    ->with([
                        'user:id,name',
                        'publicReplies' => fn ($replyQuery) => $replyQuery
                            ->with('user:id,name')
                            ->orderBy('created_at'),
                    ])
                    ->orderBy('created_at'),
            ]);

            $publicCommentThreads = $ticket->publicRootComments;
        } else {
            $ticket->load([
                'publicComments' => fn ($query) => $query
                    ->with('user:id,name')
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
        $canUpdateAssignee = $this->ticketPolicy()->updateAssignee($actor, $ticket);
        $canUpdateCategory = $this->ticketPolicy()->updateCategory($actor, $ticket);
        $canUpdatePin = $this->ticketPolicy()->updatePin($actor, $ticket);
        $canWatchTicket = $this->ticketPolicy()->watch($actor, $ticket);
        $canCommentPublic = $this->ticketPolicy()->commentPublic($actor, $ticket);
        $canCreateInternalNote = $this->ticketPolicy()->commentInternal($actor, $ticket);
        $canEditTicket = $this->ticketPolicy()->update($actor, $ticket);
        $canConfirmResolution = $this->ticketPolicy()->confirmResolution($actor, $ticket);
        $canReportProblemPersists = $this->ticketPolicy()->reportProblemPersists($actor, $ticket);

        return view('tickets.show', [
            'ticket' => $ticket,
            'statuses' => TicketStatus::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']),
            'assignees' => User::query()->orderBy('name')->get(['id', 'name']),
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
            'canUpdateAssignee' => $canUpdateAssignee,
            'canUpdateCategory' => $canUpdateCategory,
            'canUpdatePin' => $canUpdatePin,
            'canCommentPublic' => $canCommentPublic,
            'canViewInternalNotes' => $canViewInternalNotes,
            'canCreateInternalNote' => $canCreateInternalNote,
            'canConfirmResolution' => $canConfirmResolution,
            'canReportProblemPersists' => $canReportProblemPersists,
        ]);
    }

    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        $this->authorizeTicketAbility('updateStatus', $ticket);

        $selectedStatusId = $request->integer('status_id');
        $selectedStatus = TicketStatus::query()->find($selectedStatusId);

        return $this->applyTicketUpdateWithHistory($ticket, [
            'ticket_status_id' => $selectedStatusId,
            'closed_at' => $selectedStatus?->is_closed
                ? ($ticket->closed_at ?? now())
                : null,
        ], __('tickets.flash.status_updated'), 'status_update', $request);
    }

    public function updateAssignee(UpdateTicketAssigneeRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('updateAssignee', $ticket);

        return $this->applyTicketUpdateWithHistory($ticket, [
            'assignee_id' => $request->filled('assignee_id')
                ? (int) $request->input('assignee_id')
                : null,
        ], __('tickets.flash.assignee_updated'), 'assignee_update');
    }

    public function updatePriority(UpdateTicketPriorityRequest $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        $this->authorizeTicketAbility('updatePriority', $ticket);

        return $this->applyTicketUpdateWithHistory($ticket, [
            'ticket_priority_id' => $request->integer('priority_id'),
        ], __('tickets.flash.priority_updated'), 'priority_update', $request);
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

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->currentHelpdeskUser();
        abort_unless($this->ticketPolicy()->create($actor), 403);

        $canManagePin = $this->ticketPolicy()->managePinnedState($actor);
        $validated = $this->validateTicketInput(
            $request,
            allowVisibility: false,
            allowPin: $canManagePin,
        );

        $initialStatus = $this->resolveInitialStatus();
        $attributes = [
            ...$this->buildEditableTicketAttributes(
                $validated,
                shouldPin: $canManagePin && $request->boolean('pinned'),
                allowPin: $canManagePin,
            ),
            'visibility' => Ticket::VISIBILITY_PUBLIC,
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

        return redirect()
            ->route('tickets.index')
            ->with('status', 'Ticket byl úspěšně vytvořen.');
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('update', $ticket);

        $canManageVisibility = $this->ticketPolicy()->updateVisibility($this->currentHelpdeskUser(), $ticket);
        $canManagePin = $this->ticketPolicy()->updatePin($this->currentHelpdeskUser(), $ticket);
        $validated = $this->validateTicketInput(
            $request,
            allowVisibility: $canManageVisibility,
            allowPin: $canManagePin,
        );

        return $this->applyTicketUpdateWithHistory($ticket, $this->buildEditableTicketAttributes(
            $validated,
            shouldPin: $canManagePin && $request->boolean('pinned'),
            allowPin: $canManagePin,
        ), 'Ticket byl úspěšně upraven.', 'ticket_update');
    }

    public function confirmResolution(Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('confirmResolution', $ticket);

        $closedStatus = $this->resolveStatusByIdentifiers(
            ['closed'],
            'workflow',
            'V systému chybí stav "closed". Ticket zatím nelze potvrdit jako vyřešený.',
        );

        return $this->applyTicketUpdateWithHistory($ticket, [
            'ticket_status_id' => $closedStatus->id,
            'closed_at' => $ticket->closed_at ?? now(),
        ], 'Ticket byl potvrzen jako vyřešený a uzavřen.', 'requester_confirm_resolution');
    }

    public function reportProblemPersists(Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('reportProblemPersists', $ticket);

        $reopenedStatus = $this->resolveStatusByIdentifiers(
            ['in_progress', 'new'],
            'workflow',
            'V systému chybí stav "in_progress" i náhradní stav "new". Ticket zatím nelze znovu otevřít.',
        );

        return $this->applyTicketUpdateWithHistory($ticket, [
            'ticket_status_id' => $reopenedStatus->id,
            'closed_at' => null,
        ], 'Ticket byl znovu otevřen a vrácen k řešení.', 'requester_report_problem_persists');
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
        return sprintf('T-%s-%05d', now()->format('Ymd'), $ticket->id);
    }

    private function applyTicketUpdateWithHistory(
        Ticket $ticket,
        array $attributes,
        string $successMessage,
        string $action,
        ?Request $request = null,
    ): RedirectResponse|JsonResponse {
        $ticket->loadMissing($this->ticketSnapshotRelations());

        $oldSnapshot = $this->captureTicketSnapshot($ticket);

        $this->ensureOriginalSnapshot($ticket, 'backfill_before_update');

        $ticket->update($attributes);

        $ticket->refresh();
        $ticket->load($this->ticketSnapshotRelations());

        $newSnapshot = $this->captureTicketSnapshot($ticket);

        $this->recordSnapshotUpdate($ticket, $oldSnapshot, $newSnapshot, $action);

        if ($request instanceof Request && $request->expectsJson()) {
            return response()->json([
                'message' => $successMessage,
                'ticket' => $this->inlineTicketResponsePayload($ticket),
            ]);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', $successMessage);
    }

    private function inlineTicketResponsePayload(Ticket $ticket): array
    {
        $ticket->loadMissing([
            'status:id,name,slug',
            'priority:id,name,slug',
        ]);

        return [
            'id' => $ticket->id,
            'status' => [
                'id' => $ticket->ticket_status_id,
                'name' => $ticket->status?->translatedName() ?? __('tickets.common.not_available'),
                'badge_class' => $ticket->status?->badgeToneClass() ?? 'badge-tone-slate',
            ],
            'priority' => [
                'id' => $ticket->ticket_priority_id,
                'name' => $ticket->priority?->translatedName() ?? __('tickets.common.not_available'),
                'badge_class' => $ticket->priority?->badgeToneClass() ?? 'badge-tone-slate',
            ],
            'updated_at' => $ticket->updated_at?->toIso8601String(),
            'updated_at_display' => $this->formatListUpdatedAt($ticket->updated_at),
        ];
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
            'pinningEnabled' => Ticket::supportsPinning(),
            'canManageVisibility' => $ticket instanceof Ticket
                ? $this->ticketPolicy()->updateVisibility($actor, $ticket)
                : false,
            'canManagePin' => $ticket instanceof Ticket
                ? $this->ticketPolicy()->updatePin($actor, $ticket)
                : $this->ticketPolicy()->managePinnedState($actor),
        ];
    }

    private function ticketIndexQuery(?User $watcherUser = null): Builder
    {
        $query = Ticket::query()
            ->visibleTo($watcherUser)
            ->select('tickets.*')
            ->with([
                'status:id,name,color,slug',
                'priority:id,name,color,slug',
                'requester:id,name',
                'assignee:id,name',
            ])
            ->withCount('publicComments');

        if ($watcherUser instanceof User) {
            $query->withExists([
                'watchers as is_watched_by_current_user' => fn (Builder $query) => $query->whereKey($watcherUser->id),
            ]);
        } else {
            $query->selectRaw('0 as is_watched_by_current_user');
        }

        return $query;
    }

    private function applyTicketFilters(Builder $query, array $filters, ?User $watcherUser = null): Builder
    {
        return $query
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where('subject', 'like', '%'.addcslashes($filters['search'], '\\%_').'%');
            })
            ->when($filters['status'] !== '', function (Builder $query) use ($filters): void {
                $query->where('ticket_status_id', (int) $filters['status']);
            })
            ->when($filters['priority'] !== '', function (Builder $query) use ($filters): void {
                $query->where('ticket_priority_id', (int) $filters['priority']);
            })
            ->when($filters['category'] !== '', function (Builder $query) use ($filters): void {
                $query->where('ticket_category_id', (int) $filters['category']);
            })
            ->when($filters['watched'] === '1', function (Builder $query) use ($watcherUser): void {
                if (! $watcherUser instanceof User) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereHas('watchers', fn (Builder $query) => $query->whereKey($watcherUser->id));
            });
    }

    private function resolveTicketIndexFilters(Request $request): array
    {
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
            $request->session()->put(self::INDEX_FILTERS_SESSION_KEY, $filters);

            return $filters;
        }

        return $this->normalizeTicketFilters(
            (array) $request->session()->get(self::INDEX_FILTERS_SESSION_KEY, $this->defaultTicketFilters()),
        );
    }

    private function defaultTicketFilters(): array
    {
        return [
            'search' => '',
            'status' => '',
            'priority' => '',
            'category' => '',
            'watched' => '',
        ];
    }

    private function normalizeTicketFilters(array $filters): array
    {
        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'status' => (string) ($filters['status'] ?? ''),
            'priority' => (string) ($filters['priority'] ?? ''),
            'category' => (string) ($filters['category'] ?? ''),
            'watched' => (string) ($filters['watched'] ?? '') === '1' ? '1' : '',
        ];
    }

    private function nonEmptyTicketFilters(array $filters): array
    {
        return array_filter($filters, fn (string $value) => $value !== '');
    }

    private function formatListUpdatedAt(?CarbonInterface $value): string
    {
        if (! $value instanceof CarbonInterface) {
            return __('tickets.common.not_available');
        }

        return $value
            ->locale(app()->getLocale())
            ->translatedFormat(__('tickets.formats.list_updated_at'));
    }

    private function ticketSnapshotRelations(): array
    {
        return [
            'status:id,name',
            'priority:id,name',
            'category:id,name',
            'requester:id,name',
            'assignee:id,name',
        ];
    }

    private function validateTicketInput(
        Request $request,
        bool $allowVisibility,
        bool $allowPin,
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

        if ($allowPin) {
            $rules['pinned'] = ['nullable', 'boolean'];
        }

        return $request->validate($rules);
    }

    private function buildEditableTicketAttributes(
        array $validated,
        bool $shouldPin,
        bool $allowPin,
    ): array
    {
        $attributes = [
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'ticket_priority_id' => $validated['priority_id'],
            'ticket_category_id' => $validated['category_id'],
            ...$this->buildPinningAttributes($shouldPin, $allowPin),
        ];

        if (array_key_exists('visibility', $validated) && $validated['visibility'] !== null) {
            $attributes['visibility'] = $validated['visibility'];
        }

        return $attributes;
    }

    private function buildPinningAttributes(bool $shouldPin, bool $allowPin): array
    {
        if (! $allowPin) {
            return [];
        }

        if ($shouldPin && ! Ticket::supportsPinning()) {
            throw ValidationException::withMessages([
                'pinned' => 'Připnutí ticketu zatím není v databázi dostupné. Spusťte migrace aplikace.',
            ]);
        }

        if (! Ticket::supportsPinning()) {
            return [];
        }

        return [
            'is_pinned' => $shouldPin,
            'pinned_at' => $shouldPin ? now() : null,
        ];
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
                'name' => $ticket->requester?->name,
            ],
            'assignee' => $ticket->assignee_id
                ? [
                    'id' => $ticket->assignee_id,
                    'name' => $ticket->assignee?->name,
                ]
                : null,
            'pinned' => Ticket::supportsPinning()
                ? [
                    'is_pinned' => (bool) $ticket->is_pinned,
                    'pinned_at' => $ticket->pinned_at?->toIso8601String(),
                ]
                : null,
            'closed_at' => $ticket->closed_at?->toIso8601String(),
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
}
