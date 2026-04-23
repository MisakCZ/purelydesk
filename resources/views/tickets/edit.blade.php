@extends('layouts.admin')

@section('title', $ticket->ticket_number
    ? __('tickets.edit.page_title', ['ticket_number' => $ticket->ticket_number])
    : __('tickets.edit.page_title_fallback'))

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
                <h2>{{ __('tickets.edit.heading') }}</h2>
                <p>{{ __('tickets.edit.subheading') }}</p>
            </div>

            <a class="button button-secondary" href="{{ route('tickets.show', $ticket) }}">{{ __('tickets.edit.actions.back') }}</a>
        </div>
    </div>

    <div class="page-body">
        <section class="panel ticket-form-panel">
            <form class="form-layout" method="post" action="{{ route('tickets.update', $ticket) }}">
                @csrf
                @method('patch')

                @if ($viewErrors->any())
                    <ul class="error-list">
                        @foreach ($viewErrors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                @include('tickets._form', ['ticket' => $ticket])

                <div class="actions">
                    <button class="button button-primary" type="submit">{{ __('tickets.edit.actions.save') }}</button>
                    <a class="button button-secondary" href="{{ route('tickets.show', $ticket) }}">{{ __('tickets.edit.actions.cancel') }}</a>
                </div>
            </form>
        </section>
    </div>
@endsection
