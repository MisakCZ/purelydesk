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
            'parent_id' => ['nullable', 'integer'],
        ]);

        $parentComment = $this->resolveReplyParent($ticket, $validated['parent_id'] ?? null);

        $this->createComment(
            $ticket,
            $validated['body'],
            'public',
            'comment',
            'body',
            $parentComment?->id,
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', $parentComment instanceof TicketComment ? 'Odpověď byla přidána.' : 'Komentář byl přidán.');
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

    private function createComment(
        Ticket $ticket,
        string $body,
        string $visibility,
        string $errorBag,
        string $errorKey = 'body',
        ?int $parentId = null,
    ): void
    {
        $attributes = [
            'ticket_id' => $ticket->id,
            'user_id' => $this->resolveAuthor($errorBag, $errorKey)->id,
            'visibility' => $visibility,
            'body' => $body,
        ];

        if (TicketComment::supportsThreading()) {
            $attributes['parent_id'] = $parentId;
        }

        TicketComment::query()->create($attributes);
    }

    private function resolveReplyParent(Ticket $ticket, mixed $parentId): ?TicketComment
    {
        if ($parentId === null || $parentId === '') {
            return null;
        }

        if (! TicketComment::supportsThreading()) {
            throw ValidationException::withMessages([
                'parent_id' => 'Odpovědi na komentáře budou dostupné po spuštění databázové migrace aplikace.',
            ])->errorBag('comment');
        }

        $parentComment = TicketComment::query()
            ->whereKey((int) $parentId)
            ->where('ticket_id', $ticket->id)
            ->publicVisible()
            ->rootComments()
            ->first();

        if ($parentComment instanceof TicketComment) {
            return $parentComment;
        }

        throw ValidationException::withMessages([
            'parent_id' => 'Odpověď lze přidat jen k existujícímu veřejnému hlavnímu komentáři tohoto ticketu.',
        ])->errorBag('comment');
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
