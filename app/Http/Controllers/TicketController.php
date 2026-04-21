<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTicketPinRequest;
use App\Http\Requests\UpdateTicketAssigneeRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Models\Announcement;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketHistory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
            'priority' => (string) $request->query('priority', ''),
            'category' => (string) $request->query('category', ''),
            'assignee' => (string) $request->query('assignee', ''),
        ];

        $tickets = $this->applyTicketFilters($this->ticketIndexQuery(), $filters)
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        $pinnedTickets = collect();

        if (Ticket::supportsPinning()) {
            $pinnedTickets = $this->applyTicketFilters($this->ticketIndexQuery(), $filters)
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
            'statuses' => TicketStatus::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'priorities' => TicketPriority::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'categories' => TicketCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'assignees' => User::query()->orderBy('name')->get(['id', 'name']),
            'hasActiveFilters' => collect($filters)->contains(fn ($value) => $value !== ''),
        ]);
    }

    public function create(): View
    {
        return view('tickets.create', $this->ticketFormViewData());
    }

    public function edit(Ticket $ticket): View
    {
        return view('tickets.edit', [
            'ticket' => $ticket,
            ...$this->ticketFormViewData(),
        ]);
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load([
            'status:id,name,color',
            'priority:id,name,color',
            'category:id,name',
            'requester:id,name',
            'assignee:id,name',
            'internalComments' => fn ($query) => $query
                ->with('user:id,name')
                ->orderBy('created_at'),
            'publicComments' => fn ($query) => $query
                ->with('user:id,name')
                ->orderBy('created_at'),
        ]);

        $originalSnapshotEntry = $this->originalSnapshotEntry($ticket);
        $originalVersionSnapshot = $this->extractOriginalVersionSnapshot($originalSnapshotEntry?->new_value ?? []);
        $currentOriginalVersionSnapshot = $this->extractOriginalVersionSnapshot($this->captureTicketSnapshot($ticket));

        return view('tickets.show', [
            'ticket' => $ticket,
            'statuses' => TicketStatus::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'assignees' => User::query()->orderBy('name')->get(['id', 'name']),
            'pinningEnabled' => Ticket::supportsPinning(),
            'originalSnapshot' => $originalVersionSnapshot,
            'originalSnapshotSource' => $originalSnapshotEntry?->meta['source'] ?? null,
            'hasOriginalVersionChanges' => $originalSnapshotEntry instanceof TicketHistory
                && $this->snapshotHasDifferences($originalVersionSnapshot, $currentOriginalVersionSnapshot),
        ]);
    }

    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket): RedirectResponse
    {
        return $this->applyTicketUpdateWithHistory($ticket, [
            'ticket_status_id' => $request->integer('status_id'),
        ], 'Stav ticketu byl úspěšně změněn.', 'status_update');
    }

    public function updateAssignee(UpdateTicketAssigneeRequest $request, Ticket $ticket): RedirectResponse
    {
        return $this->applyTicketUpdateWithHistory($ticket, [
            'assignee_id' => $request->filled('assignee_id')
                ? (int) $request->input('assignee_id')
                : null,
        ], 'Řešitel ticketu byl úspěšně změněn.', 'assignee_update');
    }

    public function updatePin(UpdateTicketPinRequest $request, Ticket $ticket): RedirectResponse
    {
        if (! Ticket::supportsPinning()) {
            throw ValidationException::withMessages([
                'pinned' => 'Připnutí ticketu zatím není v databázi dostupné. Spusťte migrace aplikace.',
            ])->errorBag('ticketPin');
        }

        $isPinned = $request->boolean('pinned');

        return $this->applyTicketUpdateWithHistory($ticket, [
            'is_pinned' => $isPinned,
            'pinned_at' => $isPinned ? now() : null,
        ], $isPinned
            ? 'Ticket byl úspěšně připnut.'
            : 'Ticket byl úspěšně odepnut.', 'pin_update');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTicketInput($request);

        $initialStatus = $this->resolveInitialStatus();
        $attributes = [
            ...$this->buildEditableTicketAttributes($validated, $request->boolean('pinned')),
            'visibility' => 'public',
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
        $validated = $this->validateTicketInput($request);

        return $this->applyTicketUpdateWithHistory($ticket, $this->buildEditableTicketAttributes(
            $validated,
            $request->boolean('pinned'),
        ), 'Ticket byl úspěšně upraven.', 'ticket_update');
    }

    private function resolveRequester(): User
    {
        $authenticatedUser = auth()->user();

        if ($authenticatedUser instanceof User) {
            return $authenticatedUser;
        }

        // Temporary fallback until authentication is integrated.
        $fallbackUser = User::query()->orderBy('id')->first();

        if ($fallbackUser instanceof User) {
            return $fallbackUser;
        }

        throw ValidationException::withMessages([
            'requester' => 'Pro vytvoření ticketu zatím musí v databázi existovat alespoň jeden uživatel.',
        ]);
    }

    private function resolveInitialStatus(): TicketStatus
    {
        $status = TicketStatus::query()
            ->where(function ($query): void {
                $query->where('slug', 'new');

                if (Schema::hasColumn('ticket_statuses', 'code')) {
                    $query->orWhere('code', 'new');
                }
            })
            ->first();

        if ($status instanceof TicketStatus) {
            return $status;
        }

        throw ValidationException::withMessages([
            'status' => 'Nelze vytvořit ticket, protože v systému chybí výchozí stav "new". Kontaktujte administrátora.',
        ]);
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
    ): RedirectResponse {
        $ticket->loadMissing($this->ticketSnapshotRelations());

        $oldSnapshot = $this->captureTicketSnapshot($ticket);

        $this->ensureOriginalSnapshot($ticket, 'backfill_before_update');

        $ticket->update($attributes);

        $ticket->refresh();
        $ticket->load($this->ticketSnapshotRelations());

        $newSnapshot = $this->captureTicketSnapshot($ticket);

        $this->recordSnapshotUpdate($ticket, $oldSnapshot, $newSnapshot, $action);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', $successMessage);
    }

    private function ticketFormViewData(): array
    {
        return [
            'priorities' => TicketPriority::query()->orderBy('sort_order')->orderBy('name')->get(),
            'categories' => TicketCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'pinningEnabled' => Ticket::supportsPinning(),
        ];
    }

    private function ticketIndexQuery(): Builder
    {
        return Ticket::query()
            ->with([
                'status:id,name,color',
                'priority:id,name,color',
                'requester:id,name',
                'assignee:id,name',
            ])
            ->withCount('publicComments');
    }

    private function applyTicketFilters(Builder $query, array $filters): Builder
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
            ->when($filters['assignee'] !== '', function (Builder $query) use ($filters): void {
                $query->where('assignee_id', (int) $filters['assignee']);
            });
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

    private function validateTicketInput(Request $request): array
    {
        return $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority_id' => ['required', 'integer', 'exists:ticket_priorities,id'],
            'category_id' => ['required', 'integer', 'exists:ticket_categories,id'],
            'pinned' => ['nullable', 'boolean'],
        ]);
    }

    private function buildEditableTicketAttributes(array $validated, bool $shouldPin): array
    {
        return [
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'ticket_priority_id' => $validated['priority_id'],
            'ticket_category_id' => $validated['category_id'],
            ...$this->buildPinningAttributes($shouldPin),
        ];
    }

    private function buildPinningAttributes(bool $shouldPin): array
    {
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
        $authenticatedUser = auth()->user();

        if ($authenticatedUser instanceof User) {
            return $authenticatedUser->id;
        }

        return User::query()->orderBy('id')->value('id');
    }
}
