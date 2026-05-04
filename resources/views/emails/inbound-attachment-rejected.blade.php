<p>{{ __('notifications.inbound.attachments_ignored.greeting', [], $locale) }}</p>

<p>
    {{ __('notifications.inbound.attachments_ignored.body', [
        'number' => $ticket->ticket_number ?? __('tickets.common.no_number', [], $locale),
    ], $locale) }}
</p>

<p>
    <a href="{{ $ticketUrl }}">{{ __('notifications.inbound.attachments_ignored.action', [], $locale) }}</a>
</p>

<p>{{ __('notifications.ticket.lines.footer', [], $locale) }}</p>
