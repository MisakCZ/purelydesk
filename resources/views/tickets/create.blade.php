@extends('layouts.admin')

@section('title', 'Nový ticket')

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
                <h2>Nový ticket</h2>
                <p>Vytvoření prvního helpdesk požadavku přes interní administraci.</p>
            </div>

            <a class="button button-secondary" href="{{ route('tickets.index') }}">Zpět na seznam</a>
        </div>
    </div>

    <div class="page-body">
        <form class="form-layout" method="post" action="{{ route('tickets.store') }}">
            @csrf

            @if ($viewErrors->any())
                <ul class="error-list">
                    @foreach ($viewErrors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="form-grid">
                <div class="field field-full">
                    <label class="label" for="subject">Předmět</label>
                    <input
                        class="input"
                        id="subject"
                        name="subject"
                        type="text"
                        value="{{ old('subject') }}"
                        maxlength="255"
                        required
                    >
                    <div class="hint">Stručný název ticketu pro seznam a orientaci.</div>
                    @if ($viewErrors->has('subject'))
                        <div class="field-error">{{ $viewErrors->first('subject') }}</div>
                    @endif
                </div>

                <div class="field">
                    <label class="label" for="status_id">Stav</label>
                    <select class="select" id="status_id" name="status_id" required>
                        <option value="">Vyberte stav</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}" @selected((string) old('status_id') === (string) $status->id)>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                    @if ($viewErrors->has('status_id'))
                        <div class="field-error">{{ $viewErrors->first('status_id') }}</div>
                    @endif
                </div>

                <div class="field">
                    <label class="label" for="priority_id">Priorita</label>
                    <select class="select" id="priority_id" name="priority_id" required>
                        <option value="">Vyberte prioritu</option>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->id }}" @selected((string) old('priority_id') === (string) $priority->id)>
                                {{ $priority->name }}
                            </option>
                        @endforeach
                    </select>
                    @if ($viewErrors->has('priority_id'))
                        <div class="field-error">{{ $viewErrors->first('priority_id') }}</div>
                    @endif
                </div>

                <div class="field">
                    <label class="label" for="category_id">Kategorie</label>
                    <select class="select" id="category_id" name="category_id" required>
                        <option value="">Vyberte kategorii</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @if ($viewErrors->has('category_id'))
                        <div class="field-error">{{ $viewErrors->first('category_id') }}</div>
                    @endif
                </div>

                <div class="field field-full">
                    <label class="label" for="description">Popis</label>
                    <textarea class="textarea" id="description" name="description" required>{{ old('description') }}</textarea>
                    <div class="hint">Detailnější popis problému nebo požadavku.</div>
                    @if ($viewErrors->has('description'))
                        <div class="field-error">{{ $viewErrors->first('description') }}</div>
                    @endif
                </div>
            </div>

            <div class="actions">
                <button class="button button-primary" type="submit">Uložit ticket</button>
                <a class="button button-secondary" href="{{ route('tickets.index') }}">Zrušit</a>
            </div>
        </form>
    </div>
@endsection
