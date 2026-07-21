<?php

namespace App\Notifications;

use App\Models\TicketNotificationBatch;
use App\Models\TicketNotificationBatchItem;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\TicketReplyTokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TicketNotificationBatchNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly TicketNotificationBatch $batch,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable instanceof User && $notifiable->preferred_locale
            ? $notifiable->preferred_locale
            : app()->getLocale();
        $ticket = $this->batch->ticket;
        $number = $ticket->ticket_number ?? __('tickets.common.no_number', [], $locale);
        $items = $this->batch->items
            ->sortBy(fn (TicketNotificationBatchItem $item) => sprintf('%s-%020d', $item->created_at?->format('Y-m-d H:i:s.u'), $item->id))
            ->values();
        $waitingForUser = $this->waitingForUser();

        $message = (new MailMessage)
            ->subject(__('notifications.ticket_batch.subjects.'.($waitingForUser ? 'waiting_user' : 'summary'), [
                'number' => $number,
                'count' => $items->count(),
            ], $locale))
            ->greeting(__('notifications.ticket.greeting', [], $locale))
            ->line($this->replyInstruction($locale))
            ->line(__('notifications.ticket.lines.number', ['number' => $number], $locale))
            ->line(__('notifications.ticket.lines.subject', ['subject' => $ticket->subject], $locale))
            ->line(__('notifications.ticket_batch.intro', ['count' => $items->count()], $locale));

        foreach ($items as $item) {
            $message->line($this->itemLine($item, $locale));

            if ($item->event === 'public_comment') {
                $message
                    ->line(__('notifications.ticket_batch.comment_label', [], $locale))
                    ->line($this->commentBody($item, $locale));
            }
        }

        $message->line(__('notifications.ticket_batch.current_status', [
            'status' => $ticket->status?->translatedName($locale) ?? __('tickets.common.not_available', [], $locale),
        ], $locale));

        if ($waitingForUser) {
            $message->line(__('notifications.ticket_batch.waiting_user_notice', [], $locale));
        }

        if ($this->inboundMailEnabled()) {
            $message->replyTo($this->replyToAddress($notifiable));
        }

        return $message
            ->action(__('notifications.ticket.action', [], $locale), route('tickets.show', $ticket))
            ->line(__('notifications.ticket.lines.footer', [], $locale));
    }

    private function itemLine(TicketNotificationBatchItem $item, string $locale): string
    {
        $context = $item->context ?? [];
        $actor = $item->actor?->notificationName()
            ?? (is_string($context['actor_name'] ?? null) ? $context['actor_name'] : null)
            ?? __('notifications.ticket.system_actor', [], $locale);
        $time = $item->created_at?->locale($locale)->translatedFormat(__('notifications.ticket_batch.time_format', [], $locale)) ?? '';
        $event = __('notifications.ticket_batch.events.'.$item->event, [
            'status' => TicketStatus::translatedNameForSlug(
                is_string($context['status_slug'] ?? null) ? $context['status_slug'] : null,
                is_string($context['status_name'] ?? null) ? $context['status_name'] : null,
                $locale,
            ),
            'assignee' => is_string($context['assignee'] ?? null) && $context['assignee'] !== ''
                ? $context['assignee']
                : __('tickets.common.unassigned', [], $locale),
        ], $locale);

        return __('notifications.ticket_batch.item', [
            'time' => $time,
            'actor' => $actor,
            'event' => $event,
        ], $locale);
    }

    private function commentBody(TicketNotificationBatchItem $item, string $locale): string
    {
        $body = trim((string) (($item->context ?? [])['comment_body'] ?? ''));

        if ($body === '') {
            return __('tickets.common.not_available', [], $locale);
        }

        return Str::limit($body, 1200, __('notifications.ticket_batch.truncated_suffix', [], $locale));
    }

    private function waitingForUser(): bool
    {
        return $this->batch->ticket->hasStatusSlug('waiting_user');
    }

    private function replyToAddress(object $notifiable): string
    {
        $replyAddress = (string) config('helpdesk.inbound.reply_address');

        if (! $notifiable instanceof User) {
            return $replyAddress;
        }

        return app(TicketReplyTokenService::class)->replyAddressFor($this->batch->ticket, $notifiable);
    }

    private function replyInstruction(string $locale): string
    {
        return $this->inboundMailEnabled()
            ? __('notifications.ticket.reply_marker', [], $locale)
            : __('notifications.ticket.no_reply_instruction', [], $locale);
    }

    private function inboundMailEnabled(): bool
    {
        return (bool) config('helpdesk.inbound.mail_enabled');
    }
}
