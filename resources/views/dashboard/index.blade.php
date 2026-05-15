@extends('layouts.admin')

@section('title', __('dashboard.page_title'))

@php
    $locale = app()->getLocale();
    $dateTimeFormat = __('tickets.formats.list_updated_at');
    $deadlineFormat = __('tickets.formats.datetime');
@endphp

@push('styles')
    <style>
        .dashboard-shell {
            display: grid;
            gap: 1.45rem;
        }

        .dashboard-shell {
            --dashboard-card-bg: linear-gradient(145deg, color-mix(in srgb, var(--color-surface, #fff) 98%, transparent), color-mix(in srgb, var(--color-surface-muted, #f8fafc) 88%, transparent));
            --dashboard-card-shadow: 0 18px 42px rgba(15, 23, 42, 0.07);
            --dashboard-blue: #2563eb;
            --dashboard-blue-soft: color-mix(in srgb, #2563eb 14%, var(--color-surface, #fff));
            --dashboard-green: #15803d;
            --dashboard-green-soft: color-mix(in srgb, #15803d 14%, var(--color-surface, #fff));
            --dashboard-amber: #c2410c;
            --dashboard-amber-soft: color-mix(in srgb, #c2410c 15%, var(--color-surface, #fff));
            --dashboard-violet: #7c3aed;
            --dashboard-violet-soft: color-mix(in srgb, #7c3aed 14%, var(--color-surface, #fff));
            --dashboard-red: #dc2626;
            --dashboard-red-soft: color-mix(in srgb, #dc2626 13%, var(--color-surface, #fff));
        }

        :root[data-theme="dark"] .dashboard-shell {
            --dashboard-card-bg: linear-gradient(145deg, color-mix(in srgb, var(--color-surface, #172033) 94%, #ffffff 4%), color-mix(in srgb, var(--color-surface-muted, #111827) 92%, #ffffff 2%));
            --dashboard-card-shadow: 0 20px 42px rgba(0, 0, 0, 0.28);
            --dashboard-blue: #93c5fd;
            --dashboard-blue-soft: color-mix(in srgb, #2563eb 26%, var(--color-surface, #172033));
            --dashboard-green: #86efac;
            --dashboard-green-soft: color-mix(in srgb, #16a34a 26%, var(--color-surface, #172033));
            --dashboard-amber: #fdba74;
            --dashboard-amber-soft: color-mix(in srgb, #f97316 24%, var(--color-surface, #172033));
            --dashboard-violet: #c4b5fd;
            --dashboard-violet-soft: color-mix(in srgb, #7c3aed 28%, var(--color-surface, #172033));
            --dashboard-red: #fca5a5;
            --dashboard-red-soft: color-mix(in srgb, #dc2626 26%, var(--color-surface, #172033));
        }

        .dashboard-announcements {
            display: grid;
            gap: 0.65rem;
        }

        .dashboard-announcements-head,
        .dashboard-card-head,
        .dashboard-admin-head,
        .dashboard-section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.9rem;
        }

        .dashboard-announcements-title,
        .dashboard-card-title,
        .dashboard-admin-head h3,
        .dashboard-section-head h3 {
            margin: 0;
            color: var(--color-text, #13202b);
            font-size: 1.02rem;
            font-weight: 780;
            line-height: 1.32;
        }

        .dashboard-card-subtitle,
        .dashboard-admin-head p,
        .dashboard-section-head p {
            margin: 0.35rem 0 0;
            color: var(--color-muted, #64748b);
            font-size: 0.83rem;
            font-weight: 560;
            line-height: 1.5;
        }

        .dashboard-announcement-list {
            display: grid;
            gap: 0.58rem;
        }

        .dashboard-announcement {
            display: grid;
            gap: 0.42rem;
            padding: 0.85rem 0.95rem;
            border: 1px solid var(--color-border, #e5ebf1);
            border-radius: 1.05rem;
            background: var(--color-surface, #fff);
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.045);
        }

        .dashboard-announcement[data-type="info"] {
            border-color: #bfdbfe;
            background: linear-gradient(135deg, rgba(239, 246, 255, 0.96), rgba(255, 255, 255, 0.9));
        }

        .dashboard-announcement[data-type="warning"] {
            border-color: #fde68a;
            background: linear-gradient(135deg, rgba(255, 251, 235, 0.98), rgba(255, 255, 255, 0.9));
        }

        .dashboard-announcement[data-type="outage"] {
            border-color: #fecaca;
            background: linear-gradient(135deg, rgba(254, 242, 242, 0.98), rgba(255, 255, 255, 0.9));
        }

        .dashboard-announcement[data-type="maintenance"] {
            border-color: #c7d2fe;
            background: linear-gradient(135deg, rgba(238, 242, 255, 0.98), rgba(255, 255, 255, 0.9));
        }

        .dashboard-announcement-head,
        .dashboard-announcement-foot {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.7rem;
        }

        .dashboard-announcement-title {
            margin: 0;
            color: var(--color-text, #13202b);
            font-size: 0.9rem;
            font-weight: 780;
            line-height: 1.35;
        }

        .dashboard-announcement-body,
        .dashboard-announcement-meta {
            margin: 0;
            color: var(--color-muted, #475569);
            font-size: 0.79rem;
            line-height: 1.5;
        }

        .dashboard-announcement-body {
            white-space: pre-line;
        }

        .dashboard-announcement-body a,
        .dashboard-link,
        .dashboard-section-link {
            color: var(--color-primary, #0f766e);
            font-weight: 760;
            text-decoration: none;
        }

        .dashboard-announcement .badge {
            flex: 0 0 auto;
            padding: 0.26rem 0.52rem;
            font-size: 0.74rem;
            line-height: 1.2;
        }

        .dashboard-create-row {
            display: flex;
            justify-content: flex-end;
        }

        .page-card:has(.dashboard-create-action) {
            overflow: visible;
        }

        .page-head,
        .page-head-bar {
            position: relative;
            z-index: 120;
        }

        .dashboard-create-action {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.62rem;
            min-height: 2.85rem;
            padding: 0.54rem 0.78rem 0.54rem 0.62rem;
            border: 1px solid color-mix(in srgb, var(--dashboard-green, #15803d) 26%, var(--color-border, #bbf7d0));
            border-radius: 999px;
            background: linear-gradient(145deg, var(--dashboard-green-soft, #e8f8ee), color-mix(in srgb, var(--color-surface, #fff) 94%, transparent));
            color: var(--color-primary, #0f766e);
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.055);
            font-size: 0.9rem;
            font-weight: 800;
            line-height: 1.2;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            z-index: 130;
        }

        .dashboard-create-action:hover,
        .dashboard-create-action:focus-visible {
            border-color: color-mix(in srgb, var(--dashboard-green, #15803d) 42%, var(--color-border, #bbf7d0));
            color: var(--dashboard-green, #15803d);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.09);
            transform: translateY(-1px);
        }

        .dashboard-create-icon {
            display: grid;
            place-items: center;
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            background: color-mix(in srgb, var(--dashboard-green, #15803d) 18%, var(--color-surface, #fff));
            color: var(--dashboard-green, #15803d);
            flex: 0 0 auto;
        }

        .dashboard-create-icon svg {
            width: 1.08rem;
            height: 1.08rem;
        }

        .dashboard-create-tooltip {
            position: absolute;
            right: 0;
            bottom: calc(100% + 0.65rem);
            width: min(20rem, calc(100vw - 2rem));
            padding: 0.72rem 0.82rem;
            border: 1px solid color-mix(in srgb, var(--dashboard-amber, #c2410c) 24%, var(--color-border, #fde68a));
            border-radius: 0.85rem;
            background: color-mix(in srgb, var(--dashboard-amber-soft, #fff4db) 62%, var(--color-surface, #fff));
            color: var(--color-text, #13202b);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.12);
            font-size: 0.78rem;
            font-weight: 650;
            line-height: 1.45;
            opacity: 0;
            pointer-events: none;
            transform: translateY(0.35rem);
            transition: opacity 0.15s ease, transform 0.15s ease;
            z-index: 320;
        }

        .dashboard-create-tooltip::after {
            content: "";
            position: absolute;
            right: 1.25rem;
            bottom: -0.38rem;
            width: 0.7rem;
            height: 0.7rem;
            border-right: 1px solid color-mix(in srgb, var(--dashboard-amber, #c2410c) 24%, var(--color-border, #fde68a));
            border-bottom: 1px solid color-mix(in srgb, var(--dashboard-amber, #c2410c) 24%, var(--color-border, #fde68a));
            background: inherit;
            transform: rotate(45deg);
        }

        .dashboard-create-action:hover .dashboard-create-tooltip,
        .dashboard-create-action:focus-visible .dashboard-create-tooltip {
            opacity: 1;
            transform: translateY(0);
        }

        .dashboard-metrics {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 1rem;
        }

        .dashboard-metric-card {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: center;
            gap: 0.95rem;
            min-height: 7.1rem;
            padding: 1.05rem;
            border: 1px solid rgba(203, 213, 225, 0.8);
            border-radius: 1.05rem;
            background: var(--dashboard-card-bg);
            box-shadow: var(--dashboard-card-shadow);
            color: inherit;
            text-decoration: none;
        }

        .dashboard-metric-card:hover {
            border-color: rgba(148, 163, 184, 0.9);
            transform: translateY(-1px);
        }

        .dashboard-metric-icon {
            display: grid;
            place-items: center;
            width: 3.45rem;
            height: 3.45rem;
            border-radius: 0.82rem;
            color: currentColor;
        }

        .dashboard-icon-svg {
            width: 2.1rem;
            height: 2.1rem;
            overflow: visible;
            stroke: currentColor;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .dashboard-icon-svg .dashboard-icon-accent {
            fill: currentColor;
            stroke: none;
        }

        .dashboard-metric-content {
            display: grid;
            gap: 0.12rem;
            min-width: 0;
        }

        .dashboard-metric-count {
            color: var(--color-text, #0f172a);
            font-size: clamp(1.8rem, 2.7vw, 2.45rem);
            font-weight: 840;
            line-height: 1;
            letter-spacing: -0.04em;
        }

        .dashboard-metric-title {
            color: var(--color-text, #1f2937);
            font-size: 0.92rem;
            font-weight: 730;
            line-height: 1.25;
        }

        .dashboard-metric-note {
            color: var(--color-muted, #64748b);
            font-size: 0.74rem;
            font-weight: 620;
            line-height: 1.35;
        }

        .dashboard-tone-blue .dashboard-metric-icon,
        .dashboard-sla-card.dashboard-tone-blue .dashboard-sla-icon {
            background: var(--dashboard-blue-soft);
            color: var(--dashboard-blue);
        }

        .dashboard-tone-green .dashboard-metric-icon,
        .dashboard-sla-card.dashboard-tone-green .dashboard-sla-icon {
            background: var(--dashboard-green-soft);
            color: var(--dashboard-green);
        }

        .dashboard-tone-amber .dashboard-metric-icon,
        .dashboard-sla-card.dashboard-tone-amber .dashboard-sla-icon {
            background: var(--dashboard-amber-soft);
            color: var(--dashboard-amber);
        }

        .dashboard-tone-violet .dashboard-metric-icon {
            background: var(--dashboard-violet-soft);
            color: var(--dashboard-violet);
        }

        .dashboard-tone-red .dashboard-metric-icon,
        .dashboard-sla-card.dashboard-tone-red .dashboard-sla-icon {
            background: var(--dashboard-red-soft);
            color: var(--dashboard-red);
        }

        .dashboard-work-grid {
            display: grid;
            grid-template-columns: minmax(0, 2.2fr) minmax(20rem, 0.9fr);
            gap: 1.25rem;
            align-items: flex-start;
        }

        .dashboard-card,
        .dashboard-section,
        .dashboard-admin {
            border: 1px solid rgba(203, 213, 225, 0.85);
            border-radius: 1.1rem;
            background: var(--color-surface, #fff);
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.06);
        }

        .dashboard-card {
            overflow: hidden;
        }

        .dashboard-current-card {
            align-self: start;
        }

        .dashboard-card-head {
            padding: 1.15rem 1.25rem 0.9rem;
        }

        .dashboard-current-list {
            display: grid;
            gap: 0;
            margin: 0 1.15rem 1.15rem;
            border: 1px solid var(--color-border, #e2e8f0);
            border-radius: 0.9rem;
            background: var(--color-surface, #fff);
            overflow: hidden;
        }

        .dashboard-current-item {
            display: grid;
            gap: 0.48rem;
            padding: 0.86rem 0.95rem;
            border-top: 1px solid var(--color-border, #e2e8f0);
        }

        .dashboard-current-item:first-child {
            border-top: 0;
        }

        .dashboard-current-main {
            display: flex;
            align-items: baseline;
            gap: 0.55rem;
            min-width: 0;
        }

        .dashboard-current-main .ticket-number {
            flex: 0 0 auto;
            color: var(--color-primary, #0f766e);
            font-size: 0.82rem;
            font-weight: 800;
            text-decoration: none;
        }

        .dashboard-current-subject {
            display: -webkit-box;
            min-width: 0;
            overflow: hidden;
            color: var(--color-primary, #0f766e);
            font-size: 0.95rem;
            font-weight: 760;
            line-height: 1.32;
            text-decoration: none;
            white-space: normal;
            overflow-wrap: normal;
            word-break: normal;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .dashboard-current-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.36rem 0.5rem;
            color: var(--color-muted, #64748b);
            font-size: 0.76rem;
            font-weight: 620;
            line-height: 1.35;
        }

        .dashboard-current-meta-item {
            display: inline-flex;
            align-items: center;
            min-width: 0;
            gap: 0.25rem;
        }

        .dashboard-current-meta-item:not(:last-child)::after {
            content: "·";
            margin-left: 0.5rem;
            color: color-mix(in srgb, var(--color-muted, #64748b) 60%, transparent);
        }

        .dashboard-current-meta-label {
            color: var(--color-muted, #64748b);
            font-weight: 720;
        }

        .dashboard-current-meta .badge {
            width: max-content;
            padding: 0.28rem 0.55rem;
            font-size: 0.74rem;
            line-height: 1.2;
            white-space: nowrap;
        }

        .dashboard-empty {
            margin: 0 1.15rem 1.15rem;
            padding: 0.75rem 0.85rem;
            border: 1px dashed #cfd8e3;
            border-radius: 0.85rem;
            background: color-mix(in srgb, var(--color-surface-muted, #fbfdff) 74%, var(--color-surface, #fff));
            color: var(--color-muted, #64748b);
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .dashboard-sla {
            display: grid;
            gap: 0.85rem;
            padding: 1.15rem;
        }

        .dashboard-sla-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.8rem;
        }

        .dashboard-sla-title {
            margin: 0;
            color: var(--color-text, #13202b);
            font-size: 1.02rem;
            font-weight: 800;
        }

        .dashboard-sla-list {
            display: grid;
            gap: 0.72rem;
        }

        .dashboard-sla-card {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 0.8rem;
            align-items: center;
            min-height: 6.75rem;
            padding: 0.92rem;
            border: 1px solid rgba(203, 213, 225, 0.8);
            border-radius: 0.95rem;
        }

        .dashboard-sla-card.dashboard-tone-red {
            border-color: color-mix(in srgb, var(--dashboard-red) 32%, var(--color-border, #fecaca));
            background: linear-gradient(145deg, var(--dashboard-red-soft), color-mix(in srgb, var(--color-surface, #fff) 92%, transparent));
        }

        .dashboard-sla-card.dashboard-tone-amber {
            border-color: color-mix(in srgb, var(--dashboard-amber) 30%, var(--color-border, #fed7aa));
            background: linear-gradient(145deg, var(--dashboard-amber-soft), color-mix(in srgb, var(--color-surface, #fff) 92%, transparent));
        }

        .dashboard-sla-card.dashboard-tone-blue {
            border-color: color-mix(in srgb, var(--dashboard-blue) 30%, var(--color-border, #bfdbfe));
            background: linear-gradient(145deg, var(--dashboard-blue-soft), color-mix(in srgb, var(--color-surface, #fff) 92%, transparent));
        }

        .dashboard-sla-card.dashboard-tone-green {
            border-color: color-mix(in srgb, var(--dashboard-green) 30%, var(--color-border, #bbf7d0));
            background: linear-gradient(145deg, var(--dashboard-green-soft), color-mix(in srgb, var(--color-surface, #fff) 92%, transparent));
        }

        .dashboard-sla-icon {
            display: grid;
            place-items: center;
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 0.78rem;
            color: currentColor;
        }

        .dashboard-sla-count {
            margin: 0;
            color: var(--color-text, #0f172a);
            font-size: 1.95rem;
            font-weight: 840;
            line-height: 1;
            letter-spacing: -0.04em;
        }

        .dashboard-sla-label {
            margin: 0.2rem 0 0;
            color: var(--color-text, #111827);
            font-size: 0.93rem;
            font-weight: 790;
        }

        .dashboard-sla-detail,
        .dashboard-sla-note {
            margin: 0.22rem 0 0;
            color: var(--color-muted, #64748b);
            font-size: 0.78rem;
            font-weight: 620;
            line-height: 1.35;
        }

        .dashboard-tone-red .dashboard-sla-note {
            color: var(--dashboard-red);
        }

        .dashboard-tone-amber .dashboard-sla-note {
            color: var(--dashboard-amber);
        }

        .dashboard-tone-blue .dashboard-sla-note {
            color: var(--dashboard-blue);
        }

        .dashboard-tone-green .dashboard-sla-note {
            color: var(--dashboard-green);
        }

        .dashboard-section,
        .dashboard-admin {
            display: grid;
            gap: 0.65rem;
            padding: 0.95rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .dashboard-ticket-list {
            display: grid;
            gap: 0.45rem;
        }

        .dashboard-ticket {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.45rem 0.65rem;
            align-items: center;
            padding: 0.55rem 0.65rem;
            border: 1px solid var(--color-border, #edf2f7);
            border-radius: 0.78rem;
            background: color-mix(in srgb, var(--color-surface-muted, #f8fafc) 72%, var(--color-surface, #fff));
        }

        .dashboard-ticket-main {
            display: grid;
            gap: 0.12rem;
        }

        .dashboard-ticket-badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.32rem;
        }

        .dashboard-ticket .badge {
            flex: 0 0 auto;
            width: auto;
            max-width: 10rem;
            padding: 0.28rem 0.55rem;
            font-size: 0.78rem;
            line-height: 1.25;
        }

        .dashboard-ticket-meta,
        .dashboard-ticket-requester {
            grid-column: 1 / -1;
            color: var(--color-muted, #64748b);
            font-size: 0.74rem;
            font-weight: 620;
            line-height: 1.35;
        }

        .dashboard-admin {
            margin-top: 1rem;
        }

        .dashboard-admin-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        @media (max-width: 1180px) {
            .dashboard-metrics {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .dashboard-work-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 920px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-current-main {
                align-items: flex-start;
                flex-direction: column;
                gap: 0.18rem;
            }
        }

        @media (max-width: 640px) {
            .dashboard-shell {
                gap: 1rem;
            }

            .dashboard-metrics {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .dashboard-create-row {
                justify-content: stretch;
            }

            .dashboard-create-action {
                justify-content: center;
                width: 100%;
            }

            .dashboard-create-tooltip {
                right: 50%;
                transform: translate(50%, 0.35rem);
            }

            .dashboard-create-tooltip::after {
                right: calc(50% - 0.35rem);
            }

            .dashboard-create-action:hover .dashboard-create-tooltip,
            .dashboard-create-action:focus-visible .dashboard-create-tooltip {
                transform: translate(50%, 0);
            }

            .dashboard-metric-card {
                min-height: auto;
                padding: 0.9rem;
            }

            .dashboard-announcements-head,
            .dashboard-announcement-head,
            .dashboard-announcement-foot,
            .dashboard-card-head,
            .dashboard-sla-head,
            .dashboard-section-head,
            .dashboard-admin-head {
                flex-direction: column;
                gap: 0.45rem;
            }

            .dashboard-card-head,
            .dashboard-sla {
                padding: 0.9rem;
            }

            .dashboard-table-wrap,
            .dashboard-empty {
                margin-right: 0.9rem;
                margin-left: 0.9rem;
            }

            .dashboard-sla-card {
                min-height: auto;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-head">
        <div class="page-head-bar">
            <div>
                <h2>{{ __('dashboard.heading') }}</h2>
                <p>{{ __('dashboard.subheading') }}</p>
            </div>

            @can('create', \App\Models\Ticket::class)
                <a
                    class="dashboard-create-action"
                    href="{{ route('tickets.create') }}"
                    aria-describedby="dashboard-create-ticket-tip"
                >
                    <span class="dashboard-create-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M7 4h7l3 3v13H7z"></path>
                            <path d="M14 4v4h4"></path>
                            <path d="M12 10.5v5"></path>
                            <path d="M9.5 13h5"></path>
                        </svg>
                    </span>
                    <span>{{ __('dashboard.actions.new_ticket') }}</span>
                    <span id="dashboard-create-ticket-tip" class="dashboard-create-tooltip" role="tooltip">
                        {{ __('dashboard.actions.new_ticket_tooltip') }}
                    </span>
                </a>
            @endcan
        </div>
    </div>

    <div class="page-body">
        <div class="dashboard-shell">
            @if ($dashboard['announcements']['items']->isNotEmpty())
                <section class="dashboard-announcements" aria-label="{{ __('dashboard.announcements.label') }}">
                    <div class="dashboard-announcements-head">
                        <h3 class="dashboard-announcements-title">{{ __('dashboard.announcements.heading') }}</h3>

                        @if ($dashboard['announcements']['hasMore'])
                            <a class="dashboard-link" href="{{ route('announcements.active') }}">{{ __('dashboard.announcements.view_all') }}</a>
                        @endif
                    </div>

                    <div class="dashboard-announcement-list">
                        @foreach ($dashboard['announcements']['items'] as $announcement)
                            @php
                                $announcementBadgeTone = match ($announcement->type) {
                                    \App\Models\Announcement::TYPE_OUTAGE => 'badge-tone-red',
                                    \App\Models\Announcement::TYPE_WARNING => 'badge-tone-amber',
                                    \App\Models\Announcement::TYPE_MAINTENANCE => 'badge-tone-violet',
                                    default => 'badge-tone-blue',
                                };
                            @endphp
                            <article class="dashboard-announcement" data-type="{{ $announcement->type }}">
                                <div class="dashboard-announcement-head">
                                    <p class="dashboard-announcement-title">{{ $announcement->title }}</p>
                                    <span class="badge {{ $announcementBadgeTone }}">
                                        {{ \App\Models\Announcement::translatedTypeLabel($announcement->type) }}
                                    </span>
                                </div>

                                <div class="dashboard-announcement-body">{!! $announcement->bodyHtml() !!}</div>

                                <div class="dashboard-announcement-foot">
                                    @if ($announcement->starts_at || $announcement->ends_at)
                                        <p class="dashboard-announcement-meta">
                                            {{ __('dashboard.announcements.validity') }}
                                            @if ($announcement->starts_at)
                                                {{ __('dashboard.announcements.from') }} {{ $announcement->starts_at->locale($locale)->translatedFormat($dateTimeFormat) }}
                                            @endif
                                            @if ($announcement->ends_at)
                                                {{ __('dashboard.announcements.to') }} {{ $announcement->ends_at->locale($locale)->translatedFormat($dateTimeFormat) }}
                                            @endif
                                        </p>
                                    @endif

                                    <a class="dashboard-link" href="{{ route('announcements.active') }}">{{ __('dashboard.announcements.open') }}</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($dashboard['isSolverDashboard'])
                <div class="dashboard-metrics" aria-label="{{ __('dashboard.summary.label') }}">
                    @foreach ([
                        'new_unassigned_tickets' => ['tone' => 'blue', 'icon' => 'inbox', 'href' => route('tickets.index', ['status' => 'new', 'relation' => 'unassigned'])],
                        'my_assigned_tickets' => ['tone' => 'green', 'icon' => 'user', 'href' => route('tickets.index', ['scope' => 'open', 'relation' => 'assigned'])],
                        'due_today' => ['tone' => 'red', 'icon' => 'calendar', 'href' => route('tickets.index', ['scope' => 'open', 'due' => 'overdue_or_soon'])],
                        'due_soon' => ['tone' => 'amber', 'icon' => 'clock', 'href' => route('tickets.index', ['scope' => 'open', 'due' => 'overdue_or_soon'])],
                        'waiting_for_user' => ['tone' => 'violet', 'icon' => 'users', 'href' => route('tickets.index', ['status' => 'waiting_user'])],
                    ] as $summaryKey => $summary)
                        <a class="dashboard-metric-card dashboard-tone-{{ $summary['tone'] }}" href="{{ $summary['href'] }}">
                            <span class="dashboard-metric-icon" aria-hidden="true">
                                @include('dashboard.partials.icon', ['name' => $summary['icon']])
                            </span>
                            <span class="dashboard-metric-content">
                                <span class="dashboard-metric-count">{{ $dashboard['solverCounts'][$summaryKey] ?? 0 }}</span>
                                <span class="dashboard-metric-title">{{ __('dashboard.summary.'.$summaryKey) }}</span>
                                <span class="dashboard-metric-note">{{ __('dashboard.summary_notes.'.$summaryKey) }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>

                <div class="dashboard-work-grid">
                    <section class="dashboard-card dashboard-current-card" aria-label="{{ __('dashboard.current.heading') }}">
                        <div class="dashboard-card-head">
                            <div>
                                <h3 class="dashboard-card-title">{{ __('dashboard.current.heading') }}</h3>
                                <p class="dashboard-card-subtitle">{{ __('dashboard.current.subheading', ['count' => 5]) }}</p>
                            </div>
                            <a class="dashboard-link" href="{{ route('tickets.index', ['reset' => 1]) }}">{{ __('dashboard.actions.open_full_list') }}</a>
                        </div>

                        @if ($dashboard['solverSections']['current_tickets']->isEmpty())
                            <div class="dashboard-empty">{{ __('dashboard.current.empty') }}</div>
                        @else
                            <div class="dashboard-current-list">
                                @foreach ($dashboard['solverSections']['current_tickets'] as $ticket)
                                    <article class="dashboard-current-item">
                                        <div class="dashboard-current-main">
                                            <a class="ticket-number" href="{{ route('tickets.show', $ticket) }}">
                                                {{ $ticket->ticket_number ?? __('tickets.common.no_number') }}
                                            </a>
                                            <a class="dashboard-current-subject" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->subject }}</a>
                                        </div>

                                        <div class="dashboard-current-meta">
                                            <span class="dashboard-current-meta-item">
                                                <span class="dashboard-current-meta-label">{{ __('dashboard.current.columns.requester') }}:</span>
                                                {{ $ticket->requester?->displayName() ?? __('tickets.common.not_available') }}
                                            </span>
                                            <span class="dashboard-current-meta-item">
                                                <span class="dashboard-current-meta-label">{{ __('dashboard.current.columns.priority') }}:</span>
                                                <span class="badge {{ $ticket->priority?->badgeToneClass() ?? 'badge-tone-slate' }}">
                                                    <span class="badge-dot"></span>
                                                    {{ $ticket->priority?->translatedName() ?? __('tickets.common.not_available') }}
                                                </span>
                                            </span>
                                            <span class="dashboard-current-meta-item">
                                                <span class="dashboard-current-meta-label">{{ __('dashboard.current.columns.status') }}:</span>
                                                <span class="badge {{ $ticket->status?->badgeToneClass() ?? 'badge-tone-slate' }}">
                                                    <span class="badge-dot"></span>
                                                    {{ $ticket->status?->translatedName() ?? __('tickets.common.not_available') }}
                                                </span>
                                            </span>
                                            <span class="dashboard-current-meta-item">
                                                <span class="dashboard-current-meta-label">{{ __('dashboard.current.columns.deadline') }}:</span>
                                                {{ $ticket->expected_resolution_at?->locale($locale)->translatedFormat($deadlineFormat) ?? __('tickets.common.not_available') }}
                                            </span>
                                            <span class="dashboard-current-meta-item">
                                                <span class="dashboard-current-meta-label">{{ __('dashboard.current.columns.updated_at') }}:</span>
                                                {{ $ticket->updated_at?->locale($locale)->translatedFormat($dateTimeFormat) ?? __('tickets.common.not_available') }}
                                            </span>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    @include('dashboard.partials.sla', [
                        'items' => $dashboard['solverSlaDeadlines'],
                        'context' => 'solver',
                        'href' => route('tickets.index', ['relation' => 'assigned']),
                    ])
                </div>

                @if (($dashboard['solverCounts']['without_expected_resolution'] ?? 0) > 0)
                    <section class="dashboard-admin">
                        <div class="dashboard-admin-head">
                            <div>
                                <h3>{{ __('dashboard.diagnostics.heading') }}</h3>
                                <p>{{ trans_choice('dashboard.diagnostics.missing_expected_resolution', $dashboard['solverCounts']['without_expected_resolution'], ['count' => $dashboard['solverCounts']['without_expected_resolution']]) }}</p>
                            </div>
                            <a class="dashboard-link" href="{{ route('tickets.index', ['scope' => 'open', 'relation' => 'assigned', 'due' => 'missing_expected_resolution']) }}">{{ __('dashboard.actions.open_list') }}</a>
                        </div>
                    </section>
                @endif

                <section class="dashboard-admin">
                    <div class="dashboard-admin-head">
                        <div>
                            <h3>{{ __('dashboard.personal.heading') }}</h3>
                            <p>{{ trans_choice('dashboard.personal.open_requested', $dashboard['personalRequesterCount'], ['count' => $dashboard['personalRequesterCount']]) }}</p>
                        </div>
                    </div>
                    <div class="dashboard-admin-links">
                        <a class="dashboard-create-action dashboard-requested-action" href="{{ route('tickets.index', ['scope' => 'open', 'relation' => 'requester']) }}">
                            <span class="dashboard-create-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 5h10a2 2 0 0 1 2 2v12H5V7a2 2 0 0 1 2-2z"></path>
                                    <path d="M9 9h6"></path>
                                    <path d="M9 13h4"></path>
                                    <path d="M8 19v-2a3 3 0 0 1 3-3h2a3 3 0 0 1 3 3v2"></path>
                                </svg>
                            </span>
                            <span>{{ __('dashboard.personal.open_link') }}</span>
                        </a>
                    </div>
                </section>
            @elseif (! $dashboard['isAdminDashboard'])
                <div class="dashboard-work-grid">
                    <div class="dashboard-grid">
                        @include('dashboard.partials.section', [
                            'key' => 'my_open_tickets',
                            'tickets' => $dashboard['userSections']['my_open_tickets'],
                            'href' => route('tickets.index', ['scope' => 'open', 'relation' => 'requester']),
                            'locale' => $locale,
                            'dateTimeFormat' => $dateTimeFormat,
                        ])
                        @include('dashboard.partials.section', [
                            'key' => 'waiting_for_confirmation',
                            'tickets' => $dashboard['userSections']['waiting_for_confirmation'],
                            'href' => route('tickets.index', ['status' => 'resolved', 'relation' => 'requester']),
                            'locale' => $locale,
                            'dateTimeFormat' => $dateTimeFormat,
                        ])
                    </div>

                    @include('dashboard.partials.sla', [
                        'items' => $dashboard['requesterSlaDeadlines'],
                        'context' => 'requester',
                        'href' => route('tickets.index', ['relation' => 'requester']),
                    ])
                </div>
            @endif

            @if ($dashboard['adminLinks'] !== [])
                <section class="dashboard-admin">
                    <div class="dashboard-admin-head">
                        <div>
                            <h3>{{ __('dashboard.admin.heading') }}</h3>
                            <p>{{ __('dashboard.admin.subheading') }}</p>
                        </div>
                    </div>
                    <div class="dashboard-admin-links">
                        @foreach ($dashboard['adminLinks'] as $link)
                            <a class="button button-secondary button-compact app-action" href="{{ $link['url'] }}">
                                <span class="app-action-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 5h10v10"></path>
                                        <path d="M19 5L8 16"></path>
                                        <path d="M5 9v10h10"></path>
                                    </svg>
                                </span>
                                <span>{{ $link['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection
