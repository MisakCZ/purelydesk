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
        $this->authorizeTicketAbility('watch', $ticket);

        $user = $this->resolveWatcher();

        $ticket->watchers()->syncWithoutDetaching([$user->id]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', __('tickets.flash.watching_started'));
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('watch', $ticket);

        $user = $this->resolveWatcher();

        $ticket->watchers()->detach($user->id);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', __('tickets.flash.watching_stopped'));
    }

    private function resolveWatcher(): User
    {
        return $this->requireHelpdeskUser(
            __('tickets.validation.watcher_missing'),
            'watcher',
            'ticketWatcher',
        );
    }

    private function authorizeTicketAbility(string $ability, Ticket $ticket): void
    {
        abort_unless(
            app(TicketPolicy::class)->{$ability}($this->currentHelpdeskUser(), $ticket),
            403,
        );
    }
}
