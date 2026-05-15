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

        .ticket-form-action {
            display: inline-flex;
            align-items: center;
            gap: 0.62rem;
            min-height: 2.85rem;
            padding: 0.54rem 0.78rem 0.54rem 0.62rem;
            border: 1px solid color-mix(in srgb, var(--ticket-green, #15803d) 26%, var(--color-border, #bbf7d0));
            border-radius: 999px;
            background: linear-gradient(145deg, var(--ticket-green-soft, #e8f8ee), color-mix(in srgb, var(--color-surface, #fff) 94%, transparent));
            color: var(--color-primary, #0f766e);
            font-size: 0.9rem;
            font-weight: 800;
            line-height: 1.2;
            text-decoration: none;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.055);
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .ticket-form-action:hover,
        .ticket-form-action:focus-visible {
            border-color: color-mix(in srgb, var(--ticket-green, #15803d) 42%, var(--color-border, #bbf7d0));
            color: var(--ticket-green, #15803d);
            transform: translateY(-1px);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.09);
        }

        .ticket-form-action-icon {
            display: grid;
            place-items: center;
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            background: color-mix(in srgb, var(--ticket-green, #15803d) 18%, var(--color-surface, #fff));
            color: var(--ticket-green, #15803d);
            flex: 0 0 auto;
        }

        .ticket-form-action-icon svg {
            width: 1.08rem;
            height: 1.08rem;
        }

        @media (max-width: 900px) {
            .ticket-form-panel .form-grid {
                grid-template-columns: 1fr;
            }

            .ticket-form-panel .actions {
                align-items: stretch;
            }

            .ticket-form-action {
                width: 100%;
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

            <a class="button button-secondary ticket-form-action" href="{{ route('tickets.index') }}">
                <span class="ticket-form-action-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 6l-6 6 6 6"></path>
                        <path d="M20 12H9"></path>
                        <path d="M9 5H5v14h4"></path>
                    </svg>
                </span>
                <span>{{ __('tickets.create.actions.back') }}</span>
            </a>
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
                    <button class="button button-primary ticket-form-action" type="submit">
                        <span class="ticket-form-action-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12.5l4.2 4.2L19 6.8"></path>
                                <path d="M19 13v5a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h7"></path>
                            </svg>
                        </span>
                        <span>{{ __('tickets.create.actions.save') }}</span>
                    </button>
                    <a class="button button-secondary ticket-form-action" href="{{ route('tickets.index') }}">
                        <span class="ticket-form-action-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 6l12 12"></path>
                                <path d="M18 6L6 18"></path>
                            </svg>
                        </span>
                        <span>{{ __('tickets.create.actions.cancel') }}</span>
                    </a>
                </div>
            </form>
        </section>
    </div>
@endsection
