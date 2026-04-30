<section class="dashboard-section">
    <div class="dashboard-section-head">
        <div>
            <h3>{{ __('dashboard.sections.'.$key.'.heading') }}</h3>
            <p>{{ __('dashboard.sections.'.$key.'.subheading') }}</p>
        </div>
        @if ($href)
            <a class="dashboard-section-link" href="{{ $href }}">{{ __('dashboard.actions.open_list') }}</a>
        @endif
    </div>

    @if ($tickets->isEmpty())
        <div class="dashboard-empty">{{ __('dashboard.sections.'.$key.'.empty') }}</div>
    @else
        <div class="dashboard-ticket-list">
            @foreach ($tickets as $ticket)
                @include('dashboard.partials.ticket', [
                    'ticket' => $ticket,
                    'locale' => $locale,
                    'dateTimeFormat' => $dateTimeFormat,
                ])
            @endforeach
        </div>
    @endif
</section>
