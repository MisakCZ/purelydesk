@extends('layouts.admin')

@section('title', 'Oznámení')

@push('styles')
    <style>
        .announcements-layout {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: minmax(0, 1.1fr) minmax(18rem, 0.9fr);
        }

        .panel {
            padding: 1.25rem;
            border: 1px solid #e5ebf1;
            border-radius: 1rem;
            background: #fff;
        }

        .panel h3 {
            margin: 0;
            font-size: 1.05rem;
            color: #13202b;
        }

        .panel p {
            margin: 0.5rem 0 0;
            color: #5b6b79;
            line-height: 1.6;
        }

        .announcement-list {
            display: grid;
            gap: 1rem;
        }

        .announcement-item {
            padding: 1rem;
            border: 1px solid #d9e0e7;
            border-left-width: 0.45rem;
            border-radius: 1rem;
            background: #fbfdff;
        }

        .announcement-item[data-type="info"] {
            border-left-color: #2563eb;
        }

        .announcement-item[data-type="warning"] {
            border-left-color: #d97706;
        }

        .announcement-item[data-type="outage"] {
            border-left-color: #dc2626;
        }

        .announcement-item[data-type="maintenance"] {
            border-left-color: #7c3aed;
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

        .announcement-item p {
            margin: 0.6rem 0 0;
            color: #334155;
            line-height: 1.6;
            white-space: pre-line;
        }

        .announcement-detail {
            margin-top: 0.75rem;
            color: #5b6b79;
            font-size: 0.92rem;
        }

        .announcement-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.65rem;
            margin-top: 1rem;
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
            display: grid;
            gap: 1rem;
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
            min-height: 2.35rem;
            padding: 0.5rem 0.8rem;
            border-radius: 0.8rem;
        }

        .form-field {
            display: grid;
            gap: 0.45rem;
        }

        .form-label {
            font-size: 0.92rem;
            font-weight: 600;
            color: #13202b;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            min-height: 2.9rem;
            padding: 0.8rem 0.95rem;
            border: 1px solid #cfd8e3;
            border-radius: 0.9rem;
            background: #fff;
            color: #13202b;
            font: inherit;
        }

        .form-textarea {
            min-height: 9rem;
            resize: vertical;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: 2px solid rgba(15, 118, 110, 0.16);
            border-color: #0f766e;
        }

        .form-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .checkbox-field {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            color: #13202b;
            font-weight: 600;
        }

        .field-error {
            color: #b91c1c;
            font-size: 0.9rem;
        }

        .empty-state {
            padding: 2.5rem 1rem;
            text-align: center;
            border: 1px dashed #cfd8e3;
            border-radius: 1rem;
            background: linear-gradient(180deg, #fbfdff 0%, #f7fafc 100%);
        }

        .empty-state h3 {
            margin: 0;
            color: #13202b;
            font-size: 1.1rem;
        }

        .empty-state p {
            max-width: 30rem;
            margin: 0.75rem auto 0;
        }

        .alert {
            margin-bottom: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 0.9rem;
            border: 1px solid #b7e4dd;
            background: #ecfdf8;
            color: #0f513f;
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
                <h2>Provozní oznámení</h2>
                <p>Jednoduchá interní správa oznámení zobrazovaných nad seznamem ticketů.</p>
            </div>

            <a class="button button-secondary" href="{{ route('tickets.index') }}">Zpět na tickety</a>
        </div>
    </div>

    <div class="page-body">
        @if (session('status'))
            <div class="alert" role="status">{{ session('status') }}</div>
        @endif

        <div class="announcements-layout">
            <section class="panel" aria-label="Seznam oznámení">
                <h3>Existující oznámení</h3>
                <p>Na seznamu ticketů se zobrazují jen aktivní veřejná oznámení podle `is_active`, `starts_at` a `ends_at`.</p>

                @if ($announcements->isEmpty())
                    <div class="empty-state" style="margin-top: 1rem;">
                        <h3>Zatím nejsou založená žádná oznámení</h3>
                        <p>Po vytvoření prvního oznámení se objeví tady i nad seznamem ticketů, pokud bude právě aktivní.</p>
                    </div>
                @else
                    <div class="announcement-list" style="margin-top: 1rem;">
                        @foreach ($announcements as $announcement)
                            <article class="announcement-item" data-type="{{ $announcement->type }}">
                                <div class="announcement-row">
                                    <div>
                                        <h4>{{ $announcement->title }}</h4>
                                        <div class="announcement-detail">
                                            {{ $announcementTypes[$announcement->type] ?? ucfirst($announcement->type) }}
                                            @if ($announcement->author)
                                                · Autor: {{ $announcement->author->name }}
                                            @endif
                                        </div>
                                    </div>

                                    <span class="announcement-status {{ $announcement->isCurrentlyActive() ? 'is-active' : 'is-inactive' }}">
                                        {{ $announcement->isCurrentlyActive() ? 'Aktivní' : 'Neaktivní' }}
                                    </span>
                                </div>

                                <p>{{ $announcement->body }}</p>

                                <div class="announcement-detail">
                                    Visibility: {{ $announcement->visibility }}
                                    @if ($announcement->starts_at)
                                        · Od: {{ $announcement->starts_at->format('d.m.Y H:i') }}
                                    @endif
                                    @if ($announcement->ends_at)
                                        · Do: {{ $announcement->ends_at->format('d.m.Y H:i') }}
                                    @endif
                                    · Vytvořeno: {{ $announcement->created_at?->format('d.m.Y H:i') ?? '—' }}
                                </div>

                                <div class="announcement-actions">
                                    <a class="button button-secondary button-small" href="{{ route('announcements.edit', $announcement) }}">Upravit</a>

                                    <form method="post" action="{{ route('announcements.destroy', $announcement) }}">
                                        @csrf
                                        @method('delete')

                                        <button class="button button-danger button-small" type="submit">Smazat</button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="panel" aria-label="Nové oznámení">
                <h3>Nové oznámení</h3>
                <p>Zatím bez plného řízení oprávnění. Nové oznámení se ukládá jako veřejné a aktivní podle zadaných dat.</p>

                <form class="announcement-form" method="post" action="{{ route('announcements.store') }}" style="margin-top: 1rem;">
                    @csrf
                    @include('announcements._form', ['announcement' => null, 'announcementTypes' => $announcementTypes])

                    <button class="button button-primary" type="submit">Uložit oznámení</button>
                </form>
            </section>
        </div>
    </div>
@endsection
