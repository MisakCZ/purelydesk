<?php

namespace App\Services;

use App\Models\IncomingEmail;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Support\Str;

class InboundEmailReplyService
{
    public function __construct(
        private readonly InboundAttachmentRejectedNotifier $attachmentRejectedNotifier,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $attachments
     */
    public function processReply(
        Ticket $ticket,
        User $sender,
        string $senderEmail,
        string $messageId,
        string $cleanBody,
        array $attachments = [],
    ): TicketComment {
        $incomingEmail = IncomingEmail::query()->firstOrCreate(
            ['message_id' => $messageId],
            [
                'ticket_id' => $ticket->id,
                'sender_user_id' => $sender->id,
                'sender_email' => $senderEmail,
            ],
        );

        if ($incomingEmail->ticket_comment_id !== null) {
            return $incomingEmail->comment()->firstOrFail();
        }

        $hasRejectedAttachments = ! config('helpdesk.inbound.import_attachments')
            && $this->hasRealAttachments($attachments);

        $comment = TicketComment::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $sender->id,
            'visibility' => 'public',
            'body' => $this->commentBody($cleanBody, $sender, $hasRejectedAttachments),
        ]);

        $incomingEmail->forceFill([
            'ticket_id' => $ticket->id,
            'ticket_comment_id' => $comment->id,
            'sender_user_id' => $sender->id,
            'sender_email' => $senderEmail,
        ])->save();

        if ($hasRejectedAttachments) {
            $this->sendAttachmentRejectedNotice($incomingEmail, $ticket, $sender, $senderEmail);
        }

        return $comment;
    }

    /**
     * @param  array<int, array<string, mixed>>  $attachments
     */
    private function hasRealAttachments(array $attachments): bool
    {
        foreach ($attachments as $attachment) {
            if ($this->isRealAttachment($attachment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $attachment
     */
    private function isRealAttachment(array $attachment): bool
    {
        $filename = trim((string) ($attachment['filename'] ?? $attachment['name'] ?? ''));
        $mimeType = Str::lower((string) ($attachment['mime_type'] ?? $attachment['mime'] ?? ''));
        $disposition = Str::lower((string) ($attachment['disposition'] ?? ''));
        $contentId = trim((string) ($attachment['content_id'] ?? $attachment['contentId'] ?? ''));
        $size = (int) ($attachment['size'] ?? 0);
        $isInline = (bool) ($attachment['inline'] ?? false)
            || $disposition === 'inline'
            || $contentId !== '';

        if ($isInline && Str::startsWith($mimeType, 'image/')) {
            if ($filename === '' || $contentId !== '' || $size <= 20 * 1024) {
                return false;
            }
        }

        if ($disposition === 'attachment') {
            return true;
        }

        if ($filename !== '' && ! $isInline) {
            return true;
        }

        return ! $isInline && ! Str::startsWith($mimeType, 'image/');
    }

    private function commentBody(string $cleanBody, User $sender, bool $hasRejectedAttachments): string
    {
        $body = trim($cleanBody);

        if (! $hasRejectedAttachments) {
            return $body;
        }

        $locale = $sender->preferred_locale ?: app()->getLocale();

        return trim($body."\n\n".__('notifications.inbound.attachments_ignored.comment_note', [], $locale));
    }

    private function sendAttachmentRejectedNotice(
        IncomingEmail $incomingEmail,
        Ticket $ticket,
        User $sender,
        string $senderEmail,
    ): void {
        if (! config('helpdesk.inbound.notify_rejected_attachments')) {
            return;
        }

        if ($incomingEmail->attachment_notice_sent_at !== null) {
            return;
        }

        $locale = $sender->preferred_locale ?: app()->getLocale();

        $this->attachmentRejectedNotifier->send($ticket, $senderEmail, $locale);

        $incomingEmail->forceFill([
            'attachment_notice_sent_at' => now(),
        ])->save();
    }
}
