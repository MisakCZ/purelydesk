@extends('layouts.admin')

@section('title', __('tickets.create.page_title'))

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
@endphp

@push('styles')
    <style>
        .ticket-form-panel {
            display: grid;
            gap: 1.1rem;
        }

        .ticket-form-panel .form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (max-width: 900px) {
            .ticket-form-panel .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-head">
        <div class="page-head-bar">
            <div>
                <h2>{{ __('tickets.create.heading') }}</h2>
                <p>{{ __('tickets.create.subheading') }}</p>
            </div>

            <a class="button button-secondary" href="{{ route('tickets.index') }}">{{ __('tickets.create.actions.back') }}</a>
        </div>
    </div>

    <div class="page-body">
        <section class="panel ticket-form-panel">
            <form class="form-layout" method="post" action="{{ route('tickets.store') }}">
                @csrf

                @if ($viewErrors->any())
                    <ul class="error-list">
                        @foreach ($viewErrors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                @include('tickets._form', ['ticket' => null])

                <div class="actions">
                    <button class="button button-primary" type="submit">{{ __('tickets.create.actions.save') }}</button>
                    <a class="button button-secondary" href="{{ route('tickets.index') }}">{{ __('tickets.create.actions.cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
@endsection
