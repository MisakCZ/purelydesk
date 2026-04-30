@extends('layouts.admin')

@section('title', __('dashboard.page_title'))

@php
    $locale = app()->getLocale();
    $dateTimeFormat = __('tickets.formats.list_updated_at');
@endphp

@push('styles')
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.8rem;
        }

        .dashboard-grid-main {
            align-items: start;
        }

        .dashboard-grid-secondary {
            margin-top: 0.8rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .dashboard-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.7rem;
            margin-bottom: 0.8rem;
        }

        .dashboard-summary-card {
            display: grid;
            gap: 0.18rem;
            padding: 0.75rem 0.85rem;
            border: 1px solid #e5ebf1;
            border-radius: 0.9rem;
            background: #fff;
            color: inherit;
            text-decoration: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        }

        .dashboard-summary-card:hover {
            border-color: #b8d7d2;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
            transform: translateY(-1px);
        }

        .dashboard-summary-count {
            color: #13202b;
            font-size: 1.35rem;
            font-weight: 760;
            line-height: 1.1;
        }

        .dashboard-summary-label {
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .dashboard-hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
        }

        .dashboard-section,
        .dashboard-admin {
            display: grid;
            gap: 0.65rem;
            padding: 0.85rem;
            border: 1px solid #e5ebf1;
            border-radius: 0.95rem;
            background: #fff;
        }

        .dashboard-section-head,
        .dashboard-admin-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.65rem;
        }

        .dashboard-section-head h3,
        .dashboard-admin-head h3 {
            margin: 0;
            color: #13202b;
            font-size: 0.96rem;
            line-height: 1.35;
        }

        .dashboard-section-head p,
        .dashboard-admin-head p {
            margin: 0.2rem 0 0;
            color: #64748b;
            font-size: 0.8rem;
            line-height: 1.45;
        }

        .dashboard-section-link {
            flex: 0 0 auto;
            color: #0f766e;
            font-size: 0.74rem;
            font-weight: 750;
            text-decoration: none;
        }

        .dashboard-ticket-list {
            display: grid;
            gap: 0.42rem;
        }

        .dashboard-ticket {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.45rem 0.65rem;
            align-items: center;
            padding: 0.52rem 0.62rem;
            border: 1px solid #edf2f7;
            border-radius: 0.78rem;
            background: #f8fafc;
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

        .dashboard-ticket-meta {
            grid-column: 1 / -1;
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 600;
        }

        .dashboard-empty {
            padding: 0.5rem 0.62rem;
            border: 1px dashed #cfd8e3;
            border-radius: 0.75rem;
            background: #fbfdff;
            color: #64748b;
            font-size: 0.8rem;
            line-height: 1.45;
        }

        .dashboard-admin {
            margin-top: 1rem;
        }

        .dashboard-admin-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        @media (max-width: 920px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-summary,
            .dashboard-grid-secondary {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 560px) {
            .dashboard-summary,
            .dashboard-grid-secondary {
                grid-template-columns: 1fr;
            }

            .dashboard-ticket {
                grid-template-columns: 1fr;
            }

            .dashboard-ticket-badges {
                justify-content: flex-start;
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

            <div class="dashboard-hero-actions">
                <a class="button button-primary" href="{{ route('tickets.create') }}">{{ __('dashboard.actions.new_ticket') }}</a>
                <a class="button button-secondary" href="{{ route('tickets.index') }}">{{ __('dashboard.actions.all_tickets') }}</a>
            </div>
        </div>
    </div>

    <div class="page-body">
        @if ($dashboard['isSolverDashboard'])
            <div class="dashboard-summary" aria-label="{{ __('dashboard.summary.label') }}">
                @foreach (['new_unassigned_tickets', 'my_assigned_tickets', 'waiting_for_user', 'due_soon_or_overdue'] as $summaryKey)
                    @if ($summaryKey !== 'due_soon_or_overdue' || $dashboard['showExpectedResolutionSection'])
                        <a class="dashboard-summary-card" href="{{ match ($summaryKey) {
                            'new_unassigned_tickets' => route('tickets.index', ['status' => 'new', 'relation' => 'unassigned']),
                            'my_assigned_tickets' => route('tickets.index', ['scope' => 'open', 'relation' => 'assigned']),
                            'waiting_for_user' => route('tickets.index', ['status' => 'waiting_user']),
                            default => route('tickets.index', ['scope' => 'open', 'due' => 'overdue_or_soon']),
                        } }}">
                            <span class="dashboard-summary-count">{{ $dashboard['solverCounts'][$summaryKey] ?? 0 }}</span>
                            <span class="dashboard-summary-label">{{ __('dashboard.summary.'.$summaryKey) }}</span>
                        </a>
                    @endif
                @endforeach
            </div>

            <div class="dashboard-grid dashboard-grid-main">
                @include('dashboard.partials.section', [
                    'key' => 'new_unassigned_tickets',
                    'tickets' => $dashboard['solverSections']['new_unassigned_tickets'],
                    'href' => route('tickets.index', ['status' => 'new', 'relation' => 'unassigned']),
                    'locale' => $locale,
                    'dateTimeFormat' => $dateTimeFormat,
                ])
                @include('dashboard.partials.section', [
                    'key' => 'my_assigned_tickets',
                    'tickets' => $dashboard['solverSections']['my_assigned_tickets'],
                    'href' => route('tickets.index', ['scope' => 'open', 'relation' => 'assigned']),
                    'locale' => $locale,
                    'dateTimeFormat' => $dateTimeFormat,
                ])
            </div>

            <div class="dashboard-grid dashboard-grid-secondary">
                @include('dashboard.partials.section', [
                    'key' => 'waiting_for_user',
                    'tickets' => $dashboard['solverSections']['waiting_for_user'],
                    'href' => route('tickets.index', ['status' => 'waiting_user']),
                    'locale' => $locale,
                    'dateTimeFormat' => $dateTimeFormat,
                ])
                @include('dashboard.partials.section', [
                    'key' => 'resolved_waiting_confirmation',
                    'tickets' => $dashboard['solverSections']['resolved_waiting_confirmation'],
                    'href' => route('tickets.index', ['status' => 'resolved']),
                    'locale' => $locale,
                    'dateTimeFormat' => $dateTimeFormat,
                ])
                @if ($dashboard['showExpectedResolutionSection'])
                    @include('dashboard.partials.section', [
                        'key' => 'due_soon_or_overdue',
                        'tickets' => $dashboard['solverSections']['due_soon_or_overdue'],
                        'href' => route('tickets.index', ['scope' => 'open', 'due' => 'overdue_or_soon']),
                        'locale' => $locale,
                        'dateTimeFormat' => $dateTimeFormat,
                    ])
                @endif
            </div>

            <section class="dashboard-admin">
                <div class="dashboard-admin-head">
                    <div>
                        <h3>{{ __('dashboard.personal.heading') }}</h3>
                        <p>{{ trans_choice('dashboard.personal.open_requested', $dashboard['personalRequesterCount'], ['count' => $dashboard['personalRequesterCount']]) }}</p>
                    </div>
                </div>
                <div class="dashboard-admin-links">
                    <a class="button button-secondary button-compact" href="{{ route('tickets.index', ['scope' => 'open', 'relation' => 'requester']) }}">{{ __('dashboard.personal.open_link') }}</a>
                </div>
            </section>
        @elseif (! $dashboard['isAdminDashboard'])
            <div class="dashboard-grid dashboard-grid-main">
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
                        <a class="button button-secondary button-compact" href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
