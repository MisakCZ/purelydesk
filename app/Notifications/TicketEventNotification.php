<?php

namespace App\Notifications;

use App\Models\Ticket;
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

        return (new MailMessage)
            ->locale($locale)
            ->subject(__('notifications.ticket.subject', [
                'number' => $number,
                'event' => mb_strtolower($eventLabel),
            ], $locale))
            ->greeting(__('notifications.ticket.greeting', [], $locale))
            ->line(__('notifications.ticket.lines.event', ['event' => $eventLabel], $locale))
            ->line(__('notifications.ticket.lines.number', ['number' => $number], $locale))
            ->line(__('notifications.ticket.lines.subject', ['subject' => $this->ticket->subject], $locale))
            ->line($this->description($locale))
            ->action(__('notifications.ticket.action', [], $locale), route('tickets.show', $this->ticket))
            ->line(__('notifications.ticket.lines.footer', [], $locale));
    }

    private function description(string $locale): string
    {
        return __("notifications.ticket.descriptions.{$this->event}", [
            'status' => $this->ticket->status?->translatedName($locale) ?? __('tickets.common.not_available', [], $locale),
            'assignee' => (string) ($this->context['assignee'] ?? __('tickets.common.unassigned', [], $locale)),
            'expected_resolution_at' => $this->ticket->expected_resolution_at?->locale($locale)->translatedFormat(__('tickets.formats.datetime', [], $locale))
                ?? __('tickets.common.not_available', [], $locale),
        ], $locale);
    }
}
