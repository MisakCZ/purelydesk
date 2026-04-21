<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class TicketWatcherController extends Controller
{
    public function store(Ticket $ticket): RedirectResponse
    {
        $user = $this->resolveWatcher();

        $ticket->watchers()->syncWithoutDetaching([$user->id]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket jste začali sledovat.');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $user = $this->resolveWatcher();

        $ticket->watchers()->detach($user->id);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Sledování ticketu bylo zrušeno.');
    }

    private function resolveWatcher(): User
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
            'watcher' => 'Sledování ticketu zatím nelze změnit, protože v databázi neexistuje žádný uživatel.',
        ])->errorBag('ticketWatcher');
    }
}
