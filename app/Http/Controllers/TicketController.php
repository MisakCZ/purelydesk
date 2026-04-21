<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function index(): View
    {
        $tickets = Ticket::query()
            ->with([
                'status:id,name,color',
                'priority:id,name,color',
                'requester:id,name',
                'assignee:id,name',
            ])
            ->orderByDesc('updated_at')
            ->get();

        return view('tickets.index', [
            'tickets' => $tickets,
        ]);
    }

    public function create(): View
    {
        return view('tickets.create', [
            'statuses' => TicketStatus::query()->orderBy('sort_order')->orderBy('name')->get(),
            'priorities' => TicketPriority::query()->orderBy('sort_order')->orderBy('name')->get(),
            'categories' => TicketCategory::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status_id' => ['required', 'integer', 'exists:ticket_statuses,id'],
            'priority_id' => ['required', 'integer', 'exists:ticket_priorities,id'],
            'category_id' => ['required', 'integer', 'exists:ticket_categories,id'],
        ]);

        $ticket = Ticket::query()->create([
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'visibility' => 'public',
            'requester_id' => $this->resolveRequester()->id,
            'assignee_id' => null,
            'ticket_status_id' => $validated['status_id'],
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

    private function generateTicketNumber(Ticket $ticket): string
    {
        return sprintf('T-%s-%05d', now()->format('Ymd'), $ticket->id);
    }
}
