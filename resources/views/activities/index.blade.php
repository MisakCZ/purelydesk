@extends('layouts.admin')

@section('title', __('activities.page_title').' - '.config('app.name', 'Helpdesk'))

@push('styles')
    <style>
        .activity-page {
            display: grid;
            gap: 1rem;
        }

        .activity-overview {
            background: var(--panel, var(--color-surface));
        }

        .activity-head-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .activity-list {
            display: grid;
            gap: 1rem;
        }

        .activity-group {
            display: grid;
            gap: 0.62rem;
        }

        .activity-group-title {
            display: inline-flex;
            align-items: center;
            gap: 0.42rem;
            width: fit-content;
            margin: 0;
            padding: 0.38rem 0.62rem;
            border: 1px solid color-mix(in srgb, var(--color-border) 76%, transparent);
            border-radius: 999px;
            background: color-mix(in srgb, var(--color-primary-soft) 42%, var(--color-surface));
            color: var(--color-primary);
            font-size: 0.78rem;
            font-weight: 850;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .activity-group-title svg,
        .activity-card-icon svg,
        .activity-empty-icon svg,
        .activity-meta-item svg,
        .activity-open-link svg {
            width: 1em;
            height: 1em;
            flex: 0 0 auto;
        }

        .activity-card {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 0.78rem;
            position: relative;
            overflow: hidden;
            padding: 1rem 1.08rem;
            border: 1px solid color-mix(in srgb, var(--color-border) 82%, transparent);
            border-radius: 1.05rem;
            background:
                linear-gradient(135deg, color-mix(in srgb, var(--color-primary-soft) 22%, transparent), transparent 46%),
                var(--color-surface);
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.055);
        }

        .activity-card::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 0.22rem;
            background: linear-gradient(180deg, var(--color-primary), color-mix(in srgb, var(--color-success, #15803d) 65%, var(--color-primary)));
            opacity: 0.68;
        }

        .activity-card:hover {
            border-color: color-mix(in srgb, var(--color-primary) 30%, var(--color-border));
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.075);
        }

        .activity-card-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.15rem;
            height: 2.15rem;
            margin-top: 0.02rem;
            border-radius: 0.85rem;
            background: color-mix(in srgb, var(--color-primary-soft) 76%, var(--color-surface));
            color: var(--color-primary);
        }

        .activity-card-body {
            display: grid;
            gap: 0.62rem;
            min-width: 0;
        }

        .activity-card-top {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            gap: 0.75rem;
        }

        .activity-ticket {
            display: inline-flex;
            align-items: baseline;
            gap: 0.5rem;
            min-width: 0;
            color: var(--color-text);
            text-decoration: none;
        }

        .activity-ticket:hover {
            color: var(--color-primary);
            text-decoration: underline;
        }

        .activity-ticket-number {
            color: var(--color-primary);
            font-weight: 850;
            white-space: nowrap;
        }

        .activity-ticket-subject {
            font-weight: 800;
            line-height: 1.35;
            overflow-wrap: break-word;
            word-break: normal;
        }

        .activity-summary {
            margin: 0;
            max-width: 58rem;
            color: var(--color-muted);
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .activity-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
            padding-top: 0.56rem;
            border-top: 1px solid color-mix(in srgb, var(--color-border) 72%, transparent);
        }

        .activity-card-meta,
        .activity-meta-item {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .activity-card-meta {
            gap: 0.34rem 0.62rem;
            color: var(--color-muted);
            font-size: 0.82rem;
            font-weight: 650;
        }

        .activity-meta-item {
            gap: 0.28rem;
        }

        .activity-open-link {
            display: inline-flex;
            align-items: center;
            gap: 0.36rem;
            min-height: 2rem;
            padding: 0.34rem 0.62rem;
            border: 1px solid color-mix(in srgb, var(--color-primary) 24%, var(--color-border));
            border-radius: 999px;
            background: color-mix(in srgb, var(--color-surface) 90%, var(--color-primary-soft));
            color: var(--color-primary);
            font-size: 0.82rem;
            font-weight: 780;
            text-decoration: none;
        }

        .activity-open-link:hover {
            background: var(--color-primary-soft);
            text-decoration: none;
        }

        .activity-empty {
            display: grid;
            justify-items: center;
            gap: 0.55rem;
            padding: 2.3rem 1.5rem;
            border: 1px dashed color-mix(in srgb, var(--color-border) 82%, transparent);
            border-radius: 1.15rem;
            background:
                radial-gradient(circle at top, color-mix(in srgb, var(--color-success, #15803d) 13%, transparent), transparent 42%),
                color-mix(in srgb, var(--color-surface-muted) 72%, transparent);
            text-align: center;
        }

        .activity-empty-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 1.05rem;
            background: color-mix(in srgb, var(--color-success, #15803d) 13%, var(--color-surface));
            color: var(--color-success, #15803d);
        }

        .activity-empty h3 {
            margin: 0;
        }

        .activity-empty p {
            margin: 0.35rem 0 0;
            color: var(--color-muted);
        }

        @media (max-width: 720px) {
            .activity-card,
            .activity-card-top {
                grid-template-columns: 1fr;
            }

            .activity-card-icon {
                display: none;
            }

            .activity-card-footer {
                align-items: flex-start;
            }

            .activity-open-link {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $locale = app()->getLocale();
        $dateTimeFormat = __('tickets.formats.datetime');
        $groupedActivities = $activities->groupBy(function ($activity) {
            if ($activity->created_at?->isToday()) {
                return 'today';
            }

            if ($activity->created_at?->isYesterday()) {
                return 'yesterday';
            }

            return 'older';
        });
    @endphp

    <div class="activity-page">
        @if (session('status'))
            <div class="alert" role="status">{{ session('status') }}</div>
        @endif

        <section class="page-card activity-overview">
            <div class="page-head">
                <div class="page-head-bar">
                    <div>
                        <h2>{{ __('activities.heading') }}</h2>
                        <p>{{ __('activities.subheading') }}</p>
                    </div>

                    @if ($activities->isNotEmpty())
                        <div class="activity-head-actions">
                            <form method="post" action="{{ route('activities.mark-all-read') }}">
                                @csrf
                                <button class="button button-secondary ticket-detail-action" type="submit">
                                    <span class="ticket-detail-action-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M5 12.5l4.2 4.2L19 6.8"></path>
                                            <path d="M4 6h16v12H4z"></path>
                                        </svg>
                                    </span>
                                    <span>{{ __('activities.actions.mark_all_read') }}</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <div class="page-body">
                @if ($activities->isEmpty())
                    <div class="activity-empty">
                        <span class="activity-empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 7.5h16v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-10z"></path>
                                <path d="m4.8 8.2 6.1 5a1.75 1.75 0 0 0 2.2 0l6.1-5"></path>
                                <path d="m8.4 15.4 1.5 1.5 4.1-4.3"></path>
                            </svg>
                        </span>
                        <h3>{{ __('activities.empty.heading') }}</h3>
                        <p>{{ __('activities.empty.body') }}</p>
                    </div>
                @else
                    <div class="activity-list">
                        @foreach (['today', 'yesterday', 'older'] as $group)
                            @if (($groupedActivities[$group] ?? collect())->isNotEmpty())
                                <section class="activity-group" aria-labelledby="activity-group-{{ $group }}">
                                    <h3 class="activity-group-title" id="activity-group-{{ $group }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M8 2.8v3"></path>
                                            <path d="M16 2.8v3"></path>
                                            <path d="M4.5 9.2h15"></path>
                                            <path d="M6.5 4.3h11a2 2 0 0 1 2 2v11.2a2 2 0 0 1-2 2h-11a2 2 0 0 1-2-2V6.3a2 2 0 0 1 2-2z"></path>
                                        </svg>
                                        <span>{{ __('activities.groups.'.$group) }}</span>
                                    </h3>

                                    @foreach ($groupedActivities[$group] as $activity)
                                        @php
                                            $ticket = $activity->ticket;
                                            $anchor = '';

                                            if ($activity->subject_type === \App\Models\TicketComment::class) {
                                                $anchor = $activity->type === \App\Models\TicketActivity::TYPE_INTERNAL_NOTE
                                                    ? '#internal-note-'.$activity->subject_id
                                                    : '#comment-'.$activity->subject_id;
                                            } elseif ($activity->subject_type === \App\Models\TicketHistory::class) {
                                                $anchor = '#history-entry-'.$activity->subject_id;
                                            }
                                        @endphp

                                        <article class="activity-card">
                                            <span class="activity-card-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M5 6.5h14"></path>
                                                    <path d="M5 12h10"></path>
                                                    <path d="M5 17.5h7"></path>
                                                    <path d="M17 14.5l2 2 3.2-4"></path>
                                                </svg>
                                            </span>

                                            <div class="activity-card-body">
                                                <div class="activity-card-top">
                                                    <a class="activity-ticket" href="{{ route('tickets.show', $ticket).$anchor }}">
                                                        <span class="activity-ticket-number">{{ $ticket->ticket_number ?? __('tickets.common.no_number') }}</span>
                                                        <span class="activity-ticket-subject">{{ $ticket->subject }}</span>
                                                    </a>
                                                    <span class="badge badge-tone-blue">
                                                        <span class="badge-dot"></span>
                                                        {{ __('activities.types.'.$activity->type) }}
                                                    </span>
                                                </div>

                                                <p class="activity-summary">{{ __($activity->summary_key ?? 'activities.summaries.'.$activity->type) }}</p>

                                                <div class="activity-card-footer">
                                                    <div class="activity-card-meta">
                                                        <span class="activity-meta-item">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="M20 21a8 8 0 0 0-16 0"></path>
                                                                <path d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"></path>
                                                            </svg>
                                                            <span>{{ __('activities.labels.actor') }}: {{ $activity->actor?->displayName() ?? __('activities.labels.system') }}</span>
                                                        </span>
                                                        <span aria-hidden="true">&middot;</span>
                                                        <span class="activity-meta-item">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <circle cx="12" cy="12" r="8.5"></circle>
                                                                <path d="M12 7.5V12l3 2"></path>
                                                            </svg>
                                                            <span>{{ $activity->created_at?->locale($locale)->translatedFormat($dateTimeFormat) ?? __('tickets.common.not_available') }}</span>
                                                        </span>
                                                    </div>

                                                    <a class="activity-open-link" href="{{ route('tickets.show', $ticket).$anchor }}">
                                                        <span>{{ __('activities.actions.open_ticket') }}</span>
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M7 17 17 7"></path>
                                                            <path d="M9 7h8v8"></path>
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </section>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
