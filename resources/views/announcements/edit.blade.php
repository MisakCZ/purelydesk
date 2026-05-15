@extends('layouts.admin')

@section('title', __('announcements.edit.page_title'))

@push('styles')
    <style>
        .form-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        @media (max-width: 720px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-head">
        <div class="page-head-bar">
            <div>
                <h2>{{ __('announcements.edit.heading') }}</h2>
                <p>{{ __('announcements.edit.subheading') }}</p>
            </div>

            <a class="button button-secondary app-action" href="{{ route('announcements.index') }}">
                <span class="app-action-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 6l-6 6 6 6"></path>
                        <path d="M20 12H9"></path>
                        <path d="M9 5H5v14h4"></path>
                    </svg>
                </span>
                <span>{{ __('announcements.edit.actions.back') }}</span>
            </a>
        </div>
    </div>

    <div class="page-body">
        <section class="panel" aria-label="{{ __('announcements.edit.panel_label') }}">
            <div class="panel-head">
                <h3>{{ $announcement->title }}</h3>
                <p>{{ __('announcements.edit.panel_subheading') }}</p>
            </div>

            <form class="announcement-form" method="post" action="{{ route('announcements.update', $announcement) }}">
                @csrf
                @method('patch')

                @include('announcements._form', ['announcement' => $announcement, 'announcementTypes' => $announcementTypes])

                <div class="form-actions">
                    <button class="button button-primary app-action" type="submit">
                        <span class="app-action-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12.5l4.2 4.2L19 6.8"></path>
                                <path d="M19 13v5a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h7"></path>
                            </svg>
                        </span>
                        <span>{{ __('announcements.edit.actions.save') }}</span>
                    </button>
                    <a class="button button-secondary app-action" href="{{ route('announcements.index') }}">
                        <span class="app-action-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 6l12 12"></path>
                                <path d="M18 6L6 18"></path>
                            </svg>
                        </span>
                        <span>{{ __('announcements.edit.actions.cancel') }}</span>
                    </a>
                </div>
            </form>
        </section>
    </div>
@endsection
