@extends('layouts.admin')

@section('title', __('tickets.create.page_title'))

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
@endphp

@push('styles')
    <style>
        .ticket-form-panel {
            display: grid;
            gap: 0.95rem;
            padding: 1rem 1.05rem;
            border: 1px solid #e5ebf1;
            border-radius: 1rem;
            background: #fff;
        }

        .ticket-form-panel .form-layout {
            gap: 0.95rem;
        }

        .ticket-form-panel .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem 1rem;
        }

        .ticket-form-panel .field {
            gap: 0.38rem;
        }

        .ticket-form-panel .label {
            font-size: 0.73rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #64748b;
        }

        .ticket-form-panel .input,
        .ticket-form-panel .select,
        .ticket-form-panel .textarea {
            min-height: 2.65rem;
            padding: 0.72rem 0.88rem;
            border-radius: 0.85rem;
            color: #0f172a;
            font-size: 0.92rem;
            font-weight: 500;
            line-height: 1.35;
        }

        .ticket-form-panel .textarea {
            min-height: 8.75rem;
        }

        .ticket-form-panel .input::placeholder,
        .ticket-form-panel .textarea::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .ticket-form-panel .hint {
            font-size: 0.84rem;
            line-height: 1.45;
        }

        .ticket-form-panel .checkbox-title {
            display: block;
            margin-bottom: 0.15rem;
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .ticket-form-panel .checkbox-field .hint {
            display: block;
            font-weight: 500;
        }

        .ticket-form-panel .actions {
            gap: 0.65rem;
            padding-top: 0.15rem;
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
        <section class="ticket-form-panel">
            <form class="form-layout" method="post" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
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
