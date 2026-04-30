<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Policies\TicketPolicy;
use App\Support\ResolvesHelpdeskUser;
use App\Services\TicketAttachmentService;
use App\Services\TicketHistoryService;
use App\Services\TicketNotificationService;
use App\Services\TicketWorkflowAutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TicketCommentController extends Controller
{
    use ResolvesHelpdeskUser;

    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('commentPublic', $ticket);
        $actor = $this->currentHelpdeskUser();

        $validated = $request->validateWithBag('comment', [
            'body' => ['required', 'string'],
            'parent_id' => ['nullable', 'integer'],
            ...$this->ticketAttachmentService()->validationRules(),
        ]);

        $parentComment = $this->resolveReplyParent($ticket, $validated['parent_id'] ?? null);

        $comment = $this->createComment(
            $ticket,
            $validated['body'],
            'public',
            'comment',
            'body',
            $parentComment?->id,
        );

        $this->ticketAttachmentService()->storeMany(
            $ticket,
            $request->file('attachments', []),
            $actor,
            $comment,
            'public',
        );

        $workflowAttributes = $this->workflowAutomationService()->attributesForRequesterActivity($ticket, $actor);

        if ($workflowAttributes !== []) {
            $ticket = $this->ticketHistoryService()->applyUpdateWithHistory(
                $ticket,
                $workflowAttributes,
                'requester_public_comment',
                $actor,
            );
        } else {
            $ticket->refresh();
        }

        $this->ticketNotificationService()->notify($ticket, 'public_comment', $actor, [
            'comment_body' => $comment->body,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', $parentComment instanceof TicketComment ? 'Odpověď byla přidána.' : 'Komentář byl přidán.');
    }

    public function storeInternal(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('commentInternal', $ticket);

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
    ): TicketComment
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

        $comment = TicketComment::query()->create($attributes);

        // Keep explicit activity tracking in sync with the touched ticket timestamp.
        $ticket->timestamps = false;
        $ticket->forceFill([
            'last_activity_at' => now(),
        ])->saveQuietly();
        $ticket->timestamps = true;

        return $comment;
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
        return $this->requireHelpdeskUser(
            'Poznámku zatím nelze uložit, protože v databázi neexistuje žádný uživatel.',
            $errorKey,
            $errorBag,
        );
    }

    private function authorizeTicketAbility(string $ability, Ticket $ticket): void
    {
        abort_unless(
            app(TicketPolicy::class)->{$ability}($this->currentHelpdeskUser(), $ticket),
            403,
        );
    }

    private function workflowAutomationService(): TicketWorkflowAutomationService
    {
        return app(TicketWorkflowAutomationService::class);
    }

    private function ticketHistoryService(): TicketHistoryService
    {
        return app(TicketHistoryService::class);
    }

    private function ticketNotificationService(): TicketNotificationService
    {
        return app(TicketNotificationService::class);
    }

    private function ticketAttachmentService(): TicketAttachmentService
    {
        return app(TicketAttachmentService::class);
    }
}
