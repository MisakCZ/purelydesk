@extends('layouts.admin')

@section('title', __('announcements.active.page_title'))

@php
    $locale = app()->getLocale();
    $dateTimeFormat = __('announcements.formats.datetime');
@endphp

@push('styles')
    <style>
        .active-announcements {
            display: grid;
            gap: 0.65rem;
        }

        .active-announcement {
            display: grid;
            gap: 0.42rem;
            padding: 0.82rem 0.92rem;
            border: 1px solid #e5ebf1;
            border-radius: 0.95rem;
            background: #fff;
        }

        .active-announcement[data-type="info"] {
            border-color: #bfdbfe;
            background: #eff6ff;
        }

        .active-announcement[data-type="warning"] {
            border-color: #fde68a;
            background: #fffbeb;
        }

        .active-announcement[data-type="outage"] {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .active-announcement[data-type="maintenance"] {
            border-color: #c7d2fe;
            background: #eef2ff;
        }

        .active-announcement-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.7rem;
        }

        .active-announcement h3 {
            margin: 0;
            color: #13202b;
            font-size: 0.98rem;
            line-height: 1.35;
        }

        .active-announcement p,
        .active-announcement-body {
            margin: 0;
            color: #475569;
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .active-announcement-body {
            white-space: pre-line;
        }

        .active-announcement-body a {
            color: #0f766e;
            font-weight: 700;
        }

        .active-announcement-meta {
            color: #64748b;
            font-size: 0.76rem;
            font-weight: 650;
        }
    </style>
@endpush

@section('content')
    <div class="page-head">
        <div class="page-head-bar">
            <div>
                <h2>{{ __('announcements.active.heading') }}</h2>
                <p>{{ __('announcements.active.subheading') }}</p>
            </div>

            <a class="button button-secondary" href="{{ route('dashboard') }}">{{ __('announcements.active.actions.back_to_dashboard') }}</a>
        </div>
    </div>

    <div class="page-body">
        @if ($announcements->isEmpty())
            <section class="empty-state">
                <h3>{{ __('announcements.active.empty.heading') }}</h3>
                <p>{{ __('announcements.active.empty.body') }}</p>
            </section>
        @else
            <section class="active-announcements" aria-label="{{ __('announcements.active.label') }}">
                @foreach ($announcements as $announcement)
                    @php
                        $announcementBadgeTone = match ($announcement->type) {
                            \App\Models\Announcement::TYPE_OUTAGE => 'badge-tone-red',
                            \App\Models\Announcement::TYPE_WARNING => 'badge-tone-amber',
                            \App\Models\Announcement::TYPE_MAINTENANCE => 'badge-tone-violet',
                            default => 'badge-tone-blue',
                        };
                    @endphp
                    <article class="active-announcement" data-type="{{ $announcement->type }}">
                        <div class="active-announcement-head">
                            <h3>{{ $announcement->title }}</h3>
                            <span class="badge {{ $announcementBadgeTone }}">
                                {{ \App\Models\Announcement::translatedTypeLabel($announcement->type) }}
                            </span>
                        </div>

                        <div class="active-announcement-body">{!! $announcement->bodyHtml() !!}</div>

                        @if ($announcement->starts_at || $announcement->ends_at)
                            <p class="active-announcement-meta">
                                {{ __('announcements.active.meta.validity') }}
                                @if ($announcement->starts_at)
                                    {{ __('announcements.active.meta.from') }} {{ $announcement->starts_at->locale($locale)->translatedFormat($dateTimeFormat) }}
                                @endif
                                @if ($announcement->ends_at)
                                    {{ __('announcements.active.meta.to') }} {{ $announcement->ends_at->locale($locale)->translatedFormat($dateTimeFormat) }}
                                @endif
                            </p>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif
    </div>
@endsection
