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
        @if (($unreadSummary['count'] ?? 0) > 0)
            <span class="badge badge-tone-blue" title="{{ trans_choice('activities.ticket_notice', $unreadSummary['count'], ['count' => $unreadSummary['count']]) }}">
                <span class="badge-dot"></span>
                {{ trans_choice('activities.badge.new_count', $unreadSummary['count'], ['count' => $unreadSummary['count']]) }}
            </span>
        @endif
    </div>

    <div class="dashboard-ticket-requester">
        {{ __('tickets.index.meta.requester', ['name' => $ticket->requester?->displayName() ?? __('tickets.common.not_available')]) }}
        <span aria-hidden="true">&middot;</span>
        {{ __('tickets.index.meta.assignee', ['name' => $ticket->assignee?->displayName() ?? __('tickets.index.meta.assignee_unassigned')]) }}
        @can('viewInternalNotes', $ticket)
            @if (($ticket->internal_comments_count ?? 0) > 0)
                <span aria-hidden="true">&middot;</span>
                <span class="dashboard-ticket-internal-note">
                    <span class="dashboard-ticket-internal-note-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 4.5h9.5L18 7v12.5H6z"></path>
                            <path d="M15 4.5V8h3"></path>
                            <path d="M9 12h6"></path>
                            <path d="M9 15.5h4"></path>
                        </svg>
                    </span>
                    {{ __('tickets.index.meta.internal_note') }}
                </span>
            @endif
        @endcan
    </div>

    <div class="dashboard-ticket-meta">
        {{ __('dashboard.ticket.updated_at', ['date' => $updatedAt]) }}
        @if ($expectedResolutionAt)
            <span aria-hidden="true">&middot;</span>
            {{ __('dashboard.ticket.expected_resolution_at', ['date' => $expectedResolutionAt]) }}
        @endif
    </div>
</article>
