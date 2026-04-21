@extends('layouts.admin')

@section('title', $ticket->ticket_number ? 'Upravit '.$ticket->ticket_number : 'Upravit ticket')

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
@endphp

@push('styles')
    <style>
        .form-layout {
            display: grid;
            gap: 1.25rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .field {
            display: grid;
            gap: 0.45rem;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        .label {
            font-size: 0.95rem;
            font-weight: 600;
            color: #13202b;
        }

        .input,
        .select,
        .textarea {
            width: 100%;
            border: 1px solid #cfd8e3;
            border-radius: 0.9rem;
            background: #fff;
            color: #13202b;
            font: inherit;
            padding: 0.8rem 0.95rem;
        }

        .textarea {
            min-height: 11rem;
            resize: vertical;
        }

        .input:focus,
        .select:focus,
        .textarea:focus {
            outline: 2px solid rgba(15, 118, 110, 0.16);
            border-color: #0f766e;
        }

        .hint {
            color: #5b6b79;
            font-size: 0.9rem;
        }

        .error-list,
        .field-error {
            color: #b42318;
        }

        .error-list {
            margin: 0 0 0.5rem;
            padding: 0.9rem 1rem;
            list-style: none;
            border: 1px solid #f3c8c3;
            border-radius: 0.9rem;
            background: #fff5f4;
        }

        .error-list li + li {
            margin-top: 0.35rem;
        }

        .field-error {
            font-size: 0.9rem;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .checkbox-field {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.85rem 1rem;
            border: 1px solid #e5ebf1;
            border-radius: 0.9rem;
            background: #fcfaf6;
            color: #13202b;
            font-weight: 600;
        }

        .checkbox-field input {
            width: 1rem;
            height: 1rem;
            margin: 0;
        }

        @media (max-width: 900px) {
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
                <h2>Upravit ticket</h2>
                <p>Základní editace ticketu včetně připnutí.</p>
            </div>

            <a class="button button-secondary" href="{{ route('tickets.show', $ticket) }}">Zpět na detail</a>
        </div>
    </div>

    <div class="page-body">
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
                <button class="button button-primary" type="submit">Uložit změny</button>
                <a class="button button-secondary" href="{{ route('tickets.show', $ticket) }}">Zrušit</a>
            </div>
        </form>
    </div>
@endsection
