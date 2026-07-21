<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Policies\TicketPolicy;
use App\Support\ResolvesHelpdeskUser;
use App\Services\TicketAttachmentService;
use App\Services\TicketActivityService;
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

        $activity = $this->ticketActivityService()->recordPublicComment($ticket, $comment, $actor);

        $workflowAttributes = $this->workflowAutomationService()->attributesForRequesterActivity($ticket, $actor);

        if ($workflowAttributes !== []) {
            $ticket = $this->ticketHistoryService()->applyUpdateWithHistory(
                $ticket,
                $workflowAttributes,
                'requester_public_comment',
                $actor,
            );
            $history = $ticket->history()
                ->latest('id')
                ->first();

            if ($history !== null && ($history->meta['action'] ?? null) === 'requester_public_comment') {
                $this->ticketActivityService()->recordTicketUpdate($ticket, $history, 'requester_public_comment', $actor);
            }
        } else {
            $ticket->refresh();
        }

        $this->ticketNotificationService()->notify($ticket, 'public_comment', $actor, [
            'comment_body' => $comment->body,
            'is_reply' => $parentComment instanceof TicketComment,
            'additional_recipients' => $this->additionalReplyRecipients($parentComment),
            'ticket_activity_id' => $activity->id,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', $parentComment instanceof TicketComment
                ? __('tickets.show.comments.reply_stored')
                : __('tickets.show.comments.comment_stored'));
    }

    public function storeInternal(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketAbility('commentInternal', $ticket);

        $validated = $request->validateWithBag('internalNote', [
            'note_body' => ['required', 'string'],
        ]);

        $actor = $this->currentHelpdeskUser();
        $comment = $this->createComment($ticket, $validated['note_body'], 'internal', 'internalNote', 'note_body');

        $this->ticketActivityService()->recordInternalNote($ticket, $comment, $actor);

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
                'parent_id' => __('tickets.show.comments.reply_unavailable'),
            ])->errorBag('comment');
        }

        $parentComment = TicketComment::query()
            ->with([
                'parent:id,parent_id',
                'user:id,name,display_name,username,email,preferred_locale,is_active',
            ])
            ->whereKey((int) $parentId)
            ->where('ticket_id', $ticket->id)
            ->publicVisible()
            ->first();

        if ($parentComment instanceof TicketComment && $this->commentDepth($parentComment) < 2) {
            return $parentComment;
        }

        throw ValidationException::withMessages([
            'parent_id' => __('tickets.show.comments.reply_parent_invalid'),
        ])->errorBag('comment');
    }

    private function commentDepth(TicketComment $comment): int
    {
        $depth = 0;
        $current = $comment;

        while ($current->parent_id !== null) {
            $depth++;
            $current = $current->parent;

            if (! $current instanceof TicketComment) {
                break;
            }

            if ($depth >= 2) {
                break;
            }
        }

        return $depth;
    }

    /**
     * @return array<int, User>
     */
    private function additionalReplyRecipients(?TicketComment $parentComment): array
    {
        if (! $parentComment instanceof TicketComment) {
            return [];
        }

        return $parentComment->user instanceof User ? [$parentComment->user] : [];
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

    private function ticketActivityService(): TicketActivityService
    {
        return app(TicketActivityService::class);
    }

    private function ticketAttachmentService(): TicketAttachmentService
    {
        return app(TicketAttachmentService::class);
    }
}
