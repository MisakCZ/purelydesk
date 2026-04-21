<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use App\Support\ResolvesHelpdeskUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class TicketWatcherController extends Controller
{
    use ResolvesHelpdeskUser;

    public function store(Ticket $ticket): RedirectResponse
    {
        $this->ensureTicketCanBeViewed($ticket);

        $user = $this->resolveWatcher();

        $ticket->watchers()->syncWithoutDetaching([$user->id]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket jste začali sledovat.');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->ensureTicketCanBeViewed($ticket);

        $user = $this->resolveWatcher();

        $ticket->watchers()->detach($user->id);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Sledování ticketu bylo zrušeno.');
    }

    private function resolveWatcher(): User
    {
        $fallbackUser = $this->currentHelpdeskUser();

        if ($fallbackUser instanceof User) {
            return $fallbackUser;
        }

        throw ValidationException::withMessages([
            'watcher' => 'Sledování ticketu zatím nelze změnit, protože v databázi neexistuje žádný uživatel.',
        ])->errorBag('ticketWatcher');
    }

    private function ensureTicketCanBeViewed(Ticket $ticket): void
    {
        abort_unless(
            app(TicketPolicy::class)->view($this->currentHelpdeskUser(), $ticket),
            403,
        );
    }
}
