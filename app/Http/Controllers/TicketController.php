<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTicketAssigneeRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Models\Announcement;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
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

        $tickets = Ticket::query()
            ->with([
                'status:id,name,color',
                'priority:id,name,color',
                'requester:id,name',
                'assignee:id,name',
            ])
            ->withCount('publicComments')
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where('subject', 'like', '%'.addcslashes($filters['search'], '\\%_').'%');
            })
            ->when($filters['status'] !== '', function ($query) use ($filters): void {
                $query->where('ticket_status_id', (int) $filters['status']);
            })
            ->when($filters['priority'] !== '', function ($query) use ($filters): void {
                $query->where('ticket_priority_id', (int) $filters['priority']);
            })
            ->when($filters['category'] !== '', function ($query) use ($filters): void {
                $query->where('ticket_category_id', (int) $filters['category']);
            })
            ->when($filters['assignee'] !== '', function ($query) use ($filters): void {
                $query->where('assignee_id', (int) $filters['assignee']);
            })
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

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
        return view('tickets.create', [
            'priorities' => TicketPriority::query()->orderBy('sort_order')->orderBy('name')->get(),
            'categories' => TicketCategory::query()->where('is_active', true)->orderBy('name')->get(),
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

        return view('tickets.show', [
            'ticket' => $ticket,
            'statuses' => TicketStatus::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'assignees' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->update([
            'ticket_status_id' => $request->integer('status_id'),
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Stav ticketu byl úspěšně změněn.');
    }

    public function updateAssignee(UpdateTicketAssigneeRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->update([
            'assignee_id' => $request->filled('assignee_id')
                ? (int) $request->input('assignee_id')
                : null,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Řešitel ticketu byl úspěšně změněn.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority_id' => ['required', 'integer', 'exists:ticket_priorities,id'],
            'category_id' => ['required', 'integer', 'exists:ticket_categories,id'],
        ]);

        $initialStatus = $this->resolveInitialStatus();

        $ticket = Ticket::query()->create([
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'visibility' => 'public',
            'requester_id' => $this->resolveRequester()->id,
            'assignee_id' => null,
            'ticket_status_id' => $initialStatus->id,
            'ticket_priority_id' => $validated['priority_id'],
            'ticket_category_id' => $validated['category_id'],
        ]);

        $ticket->update([
            'ticket_number' => $this->generateTicketNumber($ticket),
        ]);

        return redirect()
            ->route('tickets.index')
            ->with('status', 'Ticket byl úspěšně vytvořen.');
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
}
