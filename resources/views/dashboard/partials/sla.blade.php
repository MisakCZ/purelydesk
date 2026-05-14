@php
    $items = $items ?? [];
    $context = $context ?? 'solver';
    $href = $href ?? route('tickets.index');
@endphp

<aside class="dashboard-card dashboard-sla" aria-label="{{ __('dashboard.sla.heading') }}">
    <div class="dashboard-sla-head">
        <h3 class="dashboard-sla-title">{{ __('dashboard.sla.heading') }}</h3>
        <a class="dashboard-link" href="{{ $href }}">{{ __('dashboard.actions.view_all') }}</a>
    </div>

    <div class="dashboard-sla-list">
        @foreach ([
            'overdue' => ['tone' => 'red', 'icon' => 'clock'],
            'due_soon' => ['tone' => 'amber', 'icon' => 'clock'],
            'due_today' => ['tone' => 'blue', 'icon' => 'calendar'],
            'resolved' => ['tone' => 'green', 'icon' => 'calendar'],
        ] as $slaKey => $sla)
            @php
                $slaItem = $items[$slaKey] ?? ['count' => 0, 'ticket' => null];
                $slaTicket = $slaItem['ticket'];
                $noteKey = $slaKey === 'resolved'
                    ? 'dashboard.sla.resolved.note_'.$context
                    : 'dashboard.sla.'.$slaKey.'.note';
            @endphp

            <article class="dashboard-sla-card dashboard-tone-{{ $sla['tone'] }}" data-sla-key="{{ $slaKey }}" data-sla-count="{{ $slaItem['count'] }}">
                <span class="dashboard-sla-icon" aria-hidden="true">
                    @include('dashboard.partials.icon', ['name' => $sla['icon']])
                </span>
                <div>
                    <p class="dashboard-sla-count">{{ $slaItem['count'] }}</p>
                    <p class="dashboard-sla-label">{{ __('dashboard.sla.'.$slaKey.'.label') }}</p>
                    <p class="dashboard-sla-detail">
                        @if ($slaTicket)
                            {{ __('dashboard.sla.'.$slaKey.'.ticket', ['ticket' => $slaTicket->ticket_number ?? __('tickets.common.no_number')]) }}
                        @else
                            {{ __('dashboard.sla.none') }}
                        @endif
                    </p>
                    <p class="dashboard-sla-note">
                        @if ($slaItem['count'] > 0)
                            {{ __($noteKey) }}
                        @else
                            {{ __('dashboard.sla.none') }}
                        @endif
                    </p>
                </div>
            </article>
        @endforeach
    </div>
</aside>
