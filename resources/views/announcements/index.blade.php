@extends('layouts.admin')

@section('title', __('announcements.index.page_title'))

@php
    $locale = app()->getLocale();
    $dateTimeFormat = __('announcements.formats.datetime');
@endphp

@push('styles')
    <style>
        .announcements-layout {
            display: grid;
            gap: 1.15rem;
            grid-template-columns: minmax(0, 1.1fr) minmax(18rem, 0.9fr);
        }

        .announcements-layout .panel {
            padding: 1rem 1.05rem;
        }

        .announcements-layout .panel-head {
            margin-bottom: 0.85rem;
        }

        .announcements-layout .panel-head p {
            font-size: 0.94rem;
        }

        .announcement-list {
            display: grid;
            gap: 0.85rem;
        }

        .announcement-item {
            padding: 1rem 1.1rem;
            border: 1px solid #d9e0e7;
            border-left-width: 0.45rem;
            border-radius: 1rem;
            background: #fff;
        }

        .announcement-item[data-type="info"] {
            border-left-color: #2563eb;
            background: #f8fbff;
        }

        .announcement-item[data-type="warning"] {
            border-left-color: #d97706;
            background: #fffaf0;
        }

        .announcement-item[data-type="outage"] {
            border-left-color: #dc2626;
            background: #fff6f6;
        }

        .announcement-item[data-type="maintenance"] {
            border-left-color: #7c3aed;
            background: #f9f7ff;
        }

        .announcement-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .announcement-item h4 {
            margin: 0;
            font-size: 1rem;
            color: #13202b;
        }

        .announcement-item p,
        .announcement-body {
            margin: 0.5rem 0 0;
            color: #334155;
            line-height: 1.6;
            white-space: pre-line;
        }

        .announcement-body a {
            color: #0f766e;
            font-weight: 700;
        }

        .announcement-detail {
            margin-top: 0.65rem;
            color: #5b6b79;
            font-size: 0.9rem;
            line-height: 1.55;
        }

        .announcement-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.65rem;
            margin-top: 0.85rem;
            flex-wrap: wrap;
        }

        .announcement-status {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .announcement-status.is-active {
            background: #dcfce7;
            color: #166534;
        }

        .announcement-status.is-inactive {
            background: #f1f5f9;
            color: #475569;
        }

        .announcement-form {
            margin-top: 1rem;
        }

        .button-danger {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .button-danger:hover {
            background: #fee2e2;
        }

        .button-small {
            min-height: 2.45rem;
            padding: 0.52rem 0.82rem;
            border-radius: 0.8rem;
        }

        .form-grid {
            display: grid;
            gap: 0.85rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .announcements-layout .form-input,
        .announcements-layout .form-select,
        .announcements-layout .form-textarea {
            min-height: 2.75rem;
            padding: 0.72rem 0.9rem;
        }

        .announcements-layout .form-textarea {
            min-height: 7.5rem;
        }

        .announcements-layout input[type="datetime-local"].form-input {
            min-height: 2.65rem;
            padding-top: 0.66rem;
            padding-bottom: 0.66rem;
        }

        .announcements-layout .checkbox-field {
            padding: 0.78rem 0.92rem;
        }

        .announcements-layout .empty-state {
            padding: 1.35rem 0.9rem;
        }

        .announcements-layout .empty-state p {
            max-width: 26rem;
        }

        @media (max-width: 920px) {
            .announcements-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .announcement-row {
                flex-direction: column;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-head">
        <div class="page-head-bar">
            <div>
                <h2>{{ __('announcements.index.heading') }}</h2>
                <p>{{ __('announcements.index.subheading') }}</p>
            </div>

            <a class="button button-secondary" href="{{ route('tickets.index') }}">{{ __('announcements.index.actions.back_to_tickets') }}</a>
        </div>
    </div>

    <div class="page-body">
        @if (session('status'))
            <div class="alert" role="status">{{ session('status') }}</div>
        @endif

        <div class="announcements-layout">
            <section class="panel" aria-label="{{ __('announcements.index.sections.existing_heading') }}">
                <div class="panel-head">
                    <h3>{{ __('announcements.index.sections.existing_heading') }}</h3>
                    <p>{{ __('announcements.index.sections.existing_subheading') }}</p>
                </div>

                @if ($announcements->isEmpty())
                    <div class="empty-state">
                        <h3>{{ __('announcements.index.empty.heading') }}</h3>
                        <p>{{ __('announcements.index.empty.body') }}</p>
                    </div>
                @else
                    <div class="announcement-list">
                        @foreach ($announcements as $announcement)
                            <article class="announcement-item" data-type="{{ $announcement->type }}">
                                <div class="announcement-row">
                                    <div>
                                        <h4>{{ $announcement->title }}</h4>
                                        <div class="announcement-detail">
                                            {{ $announcementTypes[$announcement->type] ?? ucfirst($announcement->type) }}
                                            @if ($announcement->author)
                                                · {{ __('announcements.index.meta.author', ['name' => $announcement->author->name]) }}
                                            @endif
                                        </div>
                                    </div>

                                    <span class="announcement-status {{ $announcement->isCurrentlyActive() ? 'is-active' : 'is-inactive' }}">
                                        {{ $announcement->isCurrentlyActive() ? __('announcements.index.state.active') : __('announcements.index.state.inactive') }}
                                    </span>
                                </div>

                                <div class="announcement-body">{!! $announcement->bodyHtml() !!}</div>

                                <div class="announcement-detail">
                                    {{ __('announcements.index.meta.visibility', ['value' => \App\Models\Announcement::translatedVisibilityLabel($announcement->visibility)]) }}
                                    @if ($announcement->starts_at)
                                        · {{ __('announcements.index.meta.starts_at', ['date' => $announcement->starts_at->locale($locale)->translatedFormat($dateTimeFormat)]) }}
                                    @endif
                                    @if ($announcement->ends_at)
                                        · {{ __('announcements.index.meta.ends_at', ['date' => $announcement->ends_at->locale($locale)->translatedFormat($dateTimeFormat)]) }}
                                    @endif
                                    · {{ __('announcements.index.meta.created_at', ['date' => $announcement->created_at?->locale($locale)->translatedFormat($dateTimeFormat) ?? __('tickets.common.not_available')]) }}
                                </div>

                                <div class="announcement-actions">
                                    <a class="button button-secondary button-small" href="{{ route('announcements.edit', $announcement) }}">{{ __('announcements.index.actions.edit') }}</a>

                                    <form method="post" action="{{ route('announcements.destroy', $announcement) }}">
                                        @csrf
                                        @method('delete')

                                        <button class="button button-danger button-small" type="submit">{{ __('announcements.index.actions.delete') }}</button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="panel" aria-label="{{ __('announcements.index.sections.create_heading') }}">
                <div class="panel-head">
                    <h3>{{ __('announcements.index.sections.create_heading') }}</h3>
                    <p>{{ __('announcements.index.sections.create_subheading') }}</p>
                </div>

                <form class="announcement-form" method="post" action="{{ route('announcements.store') }}">
                    @csrf
                    @include('announcements._form', ['announcement' => null, 'announcementTypes' => $announcementTypes])

                    <button class="button button-primary" type="submit">{{ __('announcements.index.actions.save') }}</button>
                </form>
            </section>
        </div>
    </div>
@endsection
