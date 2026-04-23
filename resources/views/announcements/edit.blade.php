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

            <a class="button button-secondary" href="{{ route('announcements.index') }}">{{ __('announcements.edit.actions.back') }}</a>
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
                    <button class="button button-primary" type="submit">{{ __('announcements.edit.actions.save') }}</button>
                    <a class="button button-secondary" href="{{ route('announcements.index') }}">{{ __('announcements.edit.actions.cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
@endsection
