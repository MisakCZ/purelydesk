<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TicketCommentController extends Controller
{
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validateWithBag('comment', [
            'body' => ['required', 'string'],
        ]);

        TicketComment::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->resolveAuthor()->id,
            'visibility' => 'public',
            'body' => $validated['body'],
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Komentář byl přidán.');
    }

    private function resolveAuthor(): User
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
            'comment' => 'Komentář zatím nelze uložit, protože v databázi neexistuje žádný uživatel.',
        ]);
    }
}
