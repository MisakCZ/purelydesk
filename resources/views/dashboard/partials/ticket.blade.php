@php
    $updatedAt = $ticket->updated_at
        ? $ticket->updated_at->locale($locale)->translatedFormat($dateTimeFormat)
        : __('tickets.common.not_available');
    $expectedResolutionAt = $ticket->expected_resolution_at
        ? $ticket->expected_resolution_at->locale($locale)->translatedFormat(__('tickets.formats.datetime'))
        : null;
@endphp

<article class="dashboard-ticket">
    <div class="dashboard-ticket-main">
        <a class="ticket-number" href="{{ route('tickets.show', $ticket) }}">
            {{ $ticket->ticket_number ?? __('tickets.common.no_number') }}
        </a>
        <a class="subject-title" href="{{ route('tickets.show', $ticket) }}">
            {{ $ticket->subject }}
        </a>
    </div>

    <div class="dashboard-ticket-badges">
        <span class="badge {{ $ticket->status?->badgeToneClass() ?? 'badge-tone-slate' }}">
            <span class="badge-dot"></span>
            {{ $ticket->status?->translatedName() ?? __('tickets.common.not_available') }}
        </span>
        <span class="badge {{ $ticket->priority?->badgeToneClass() ?? 'badge-tone-slate' }}">
            <span class="badge-dot"></span>
            {{ $ticket->priority?->translatedName() ?? __('tickets.common.not_available') }}
        </span>
    </div>

    <div class="dashboard-ticket-meta">
        {{ __('dashboard.ticket.updated_at', ['date' => $updatedAt]) }}
        @if ($expectedResolutionAt)
            <span aria-hidden="true">&middot;</span>
            {{ __('dashboard.ticket.expected_resolution_at', ['date' => $expectedResolutionAt]) }}
        @endif
    </div>
</article>
