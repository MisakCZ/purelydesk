<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Policies\TicketPolicy;
use App\Support\ResolvesHelpdeskUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TicketCommentController extends Controller
{
    use ResolvesHelpdeskUser;

    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->ensureTicketCanBeViewed($ticket);

        $validated = $request->validateWithBag('comment', [
            'body' => ['required', 'string'],
        ]);

        $this->createComment($ticket, $validated['body'], 'public', 'comment');

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Komentář byl přidán.');
    }

    public function storeInternal(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->ensureTicketCanBeViewed($ticket);

        // TODO: Restrict this to internal admin users when auth/policies are integrated.
        $validated = $request->validateWithBag('internalNote', [
            'note_body' => ['required', 'string'],
        ]);

        $this->createComment($ticket, $validated['note_body'], 'internal', 'internalNote', 'note_body');

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Interní poznámka byla uložena.');
    }

    private function createComment(Ticket $ticket, string $body, string $visibility, string $errorBag, string $errorKey = 'body'): void
    {
        TicketComment::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->resolveAuthor($errorBag, $errorKey)->id,
            'visibility' => $visibility,
            'body' => $body,
        ]);
    }

    private function resolveAuthor(string $errorBag, string $errorKey): User
    {
        $fallbackUser = $this->currentHelpdeskUser();

        if ($fallbackUser instanceof User) {
            return $fallbackUser;
        }

        throw ValidationException::withMessages([
            $errorKey => 'Poznámku zatím nelze uložit, protože v databázi neexistuje žádný uživatel.',
        ])->errorBag($errorBag);
    }

    private function ensureTicketCanBeViewed(Ticket $ticket): void
    {
        abort_unless(
            app(TicketPolicy::class)->view($this->currentHelpdeskUser(), $ticket),
            403,
        );
    }
}
