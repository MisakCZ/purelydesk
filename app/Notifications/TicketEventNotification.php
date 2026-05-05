<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketReplyTokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketEventNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $event,
        public readonly array $context = [],
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
        $locale = $notifiable->preferred_locale ?: app()->getLocale();
        $number = $this->ticket->ticket_number ?? __('tickets.common.no_number', [], $locale);
        $eventLabel = __("notifications.ticket.events.{$this->event}", [], $locale);

        $message = (new MailMessage)
            ->subject(__('notifications.ticket.subject', [
                'number' => $number,
                'event' => mb_strtolower($eventLabel),
            ], $locale))
            ->greeting(__('notifications.ticket.greeting', [], $locale))
            ->line($this->replyInstruction($locale))
            ->line(__('notifications.ticket.lines.event', ['event' => $eventLabel], $locale))
            ->line(__('notifications.ticket.lines.number', ['number' => $number], $locale))
            ->line(__('notifications.ticket.lines.subject', ['subject' => $this->ticket->subject], $locale))
            ->line($this->description($locale))
            ->line(__('notifications.ticket.lines.ticket_description', [], $locale))
            ->line($this->ticket->description ?: __('tickets.common.not_available', [], $locale));

        if ($this->inboundMailEnabled()) {
            $message->replyTo($this->replyToAddress($notifiable));
        }

        if ($this->event === 'public_comment') {
            $message
                ->line(__('notifications.ticket.lines.comment_body', [], $locale))
                ->line((string) ($this->context['comment_body'] ?? __('tickets.common.not_available', [], $locale)));
        }

        return $message
            ->action(__('notifications.ticket.action', [], $locale), route('tickets.show', $this->ticket))
            ->line(__('notifications.ticket.lines.footer', [], $locale));
    }

    private function replyToAddress(object $notifiable): string
    {
        $replyAddress = (string) config('helpdesk.inbound.reply_address');

        if (! $notifiable instanceof User) {
            return $replyAddress;
        }

        return app(TicketReplyTokenService::class)->replyAddressFor($this->ticket, $notifiable);
    }

    private function replyInstruction(string $locale): string
    {
        if (! $this->inboundMailEnabled()) {
            return __('notifications.ticket.no_reply_instruction', [], $locale);
        }

        return __('notifications.ticket.reply_marker', [], $locale);
    }

    private function inboundMailEnabled(): bool
    {
        return (bool) config('helpdesk.inbound.mail_enabled');
    }

    private function description(string $locale): string
    {
        if ($this->event === 'closed') {
            return $this->closedDescription($locale);
        }

        return __("notifications.ticket.descriptions.{$this->event}", [
            'status' => $this->ticket->status?->translatedName($locale) ?? __('tickets.common.not_available', [], $locale),
            'assignee' => (string) ($this->context['assignee'] ?? __('tickets.common.unassigned', [], $locale)),
            'expected_resolution_at' => $this->ticket->expected_resolution_at?->locale($locale)->translatedFormat(__('tickets.formats.datetime', [], $locale))
                ?? __('tickets.common.not_available', [], $locale),
            'old_expected_resolution_at' => $this->formatContextDate('old_expected_resolution_at', $locale),
        ], $locale);
    }

    private function closedDescription(string $locale): string
    {
        $reason = (string) ($this->context['close_reason'] ?? 'manual');
        $key = match ($reason) {
            'requester_confirmed' => 'closed_by_requester',
            'automatic' => 'closed_automatically',
            default => 'closed',
        };

        return __("notifications.ticket.descriptions.{$key}", [
            'days' => (int) ($this->context['auto_close_days'] ?? config('helpdesk.workflow.resolved_auto_close_days', 5)),
        ], $locale);
    }

    private function formatContextDate(string $key, string $locale): string
    {
        $value = $this->context[$key] ?? null;

        if (! is_string($value) || $value === '') {
            return __('tickets.common.not_available', [], $locale);
        }

        return \Illuminate\Support\Carbon::parse($value)
            ->locale($locale)
            ->translatedFormat(__('tickets.formats.datetime', [], $locale));
    }
}
