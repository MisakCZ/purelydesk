@extends('layouts.admin')

@section('title', $ticket->ticket_number ? 'Ticket '.$ticket->ticket_number : 'Detail ticketu')

@php
    $errorBags = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $commentErrors = $errorBags->getBag('comment');
    $internalNoteErrors = $errorBags->getBag('internalNote');
    $assigneeErrors = $errorBags->getBag('ticketAssignee');
    $pinErrors = $errorBags->getBag('ticketPin');
    $statusErrors = $errorBags->getBag('ticketStatus');
@endphp

@push('styles')
    <style>
        .ticket-detail {
            display: grid;
            gap: 1.5rem;
        }

        .page-head-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .ticket-hero {
            display: grid;
            gap: 0.8rem;
            padding: 1.5rem;
            border: 1px solid #d9e0e7;
            border-radius: 1rem;
            background: linear-gradient(180deg, #fbfdff 0%, #f7fafc 100%);
        }

        .ticket-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .ticket-number {
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: #0f766e;
            text-transform: uppercase;
        }

        .ticket-subject {
            margin: 0;
            font-size: 1.75rem;
            line-height: 1.2;
            color: #13202b;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .detail-card {
            padding: 1rem 1.1rem;
            border: 1px solid #e5ebf1;
            border-radius: 1rem;
            background: #fff;
        }

        .detail-card.full {
            grid-column: 1 / -1;
        }

        .detail-label {
            display: block;
            margin-bottom: 0.4rem;
            color: #5b6b79;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .detail-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.4rem;
        }

        .detail-label-row .detail-label {
            margin-bottom: 0;
        }

        .detail-value {
            color: #13202b;
            font-size: 1rem;
            line-height: 1.6;
        }

        .detail-empty {
            color: #64748b;
        }

        .detail-flag {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.7rem;
            border-radius: 999px;
            background: #fff4d6;
            color: #8a5a00;
            font-size: 0.88rem;
            font-weight: 700;
        }

        .original-version-panel {
            display: grid;
            gap: 1rem;
            padding: 1.25rem;
            border: 1px solid #ead9b5;
            border-radius: 1rem;
            background: linear-gradient(180deg, #fffdfa 0%, #fff7e8 100%);
        }

        .original-version-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .original-version-copy h3 {
            margin: 0;
            font-size: 1.05rem;
            color: #13202b;
        }

        .original-version-copy p {
            margin: 0.35rem 0 0;
            color: #6b5a2e;
            line-height: 1.6;
        }

        .original-version-panel .detail-card {
            border-color: #ead9b5;
            background: rgba(255, 255, 255, 0.88);
        }

        .original-version-note {
            margin: 0;
            padding: 0.8rem 0.95rem;
            border: 1px solid #ead9b5;
            border-radius: 0.9rem;
            background: rgba(255, 252, 244, 0.95);
            color: #6b5a2e;
            line-height: 1.6;
        }

        .original-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            min-height: 2.1rem;
            padding: 0.4rem 0.75rem;
            border: 1px solid #ead9b5;
            border-radius: 999px;
            background: #fff8e6;
            color: #8a5a00;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .original-indicator:hover {
            background: #fff1c9;
        }

        .original-indicator svg {
            width: 0.95rem;
            height: 0.95rem;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            background: #eef2f6;
            color: #334155;
            white-space: nowrap;
        }

        .badge-dot {
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 999px;
            background: currentColor;
            opacity: 0.7;
        }

        .alert {
            padding: 0.9rem 1rem;
            border-radius: 0.9rem;
            border: 1px solid #b7e4dd;
            background: #ecfdf8;
            color: #0f513f;
        }

        .comment-section {
            display: grid;
            gap: 1rem;
        }

        .editable-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .editable-value {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .inline-form {
            display: grid;
            gap: 0.75rem;
            margin-top: 0.9rem;
        }

        .inline-form-row {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .inline-form .button {
            min-height: 3rem;
        }

        .inline-form-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .inline-help {
            margin: 0;
            color: #64748b;
            font-size: 0.92rem;
        }

        .button-compact {
            min-height: 2.5rem;
            padding: 0.55rem 0.8rem;
            border-radius: 0.75rem;
        }

        .icon-button {
            width: 2.35rem;
            min-width: 2.35rem;
            min-height: 2.35rem;
            padding: 0;
            border: 1px solid #d9e0e7;
            border-radius: 999px;
            background: #f8fafc;
            color: #5b6b79;
        }

        .icon-button:hover {
            background: #eef2f6;
            color: #0f766e;
        }

        .icon-button svg {
            width: 1rem;
            height: 1rem;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .comment-empty {
            padding: 1.25rem;
            border: 1px dashed #cfd8e3;
            border-radius: 1rem;
            background: #fbfdff;
            color: #64748b;
        }

        .internal-note-card,
        .internal-note-form {
            border-color: #e1d7c4;
            background: #fcfaf6;
        }

        .comment-list {
            display: grid;
            gap: 0.9rem;
        }

        .comment-card {
            padding: 1rem 1.1rem;
            border: 1px solid #e5ebf1;
            border-radius: 1rem;
            background: #fff;
        }

        .comment-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.65rem;
        }

        .comment-author {
            font-weight: 700;
            color: #13202b;
        }

        .comment-time {
            color: #64748b;
            font-size: 0.92rem;
            white-space: nowrap;
        }

        .comment-body {
            color: #334155;
            line-height: 1.7;
        }

        .comment-form {
            display: grid;
            gap: 1rem;
            padding: 1rem 1.1rem;
            border: 1px solid #e5ebf1;
            border-radius: 1rem;
            background: #fff;
        }

        .comment-form-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .comment-form-head h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #13202b;
        }

        .comment-form-head p {
            margin: 0.35rem 0 0;
            color: #64748b;
        }

        .textarea {
            width: 100%;
            min-height: 9rem;
            padding: 0.8rem 0.95rem;
            border: 1px solid #cfd8e3;
            border-radius: 0.9rem;
            background: #fff;
            color: #13202b;
            font: inherit;
            resize: vertical;
        }

        .textarea:focus {
            outline: 2px solid rgba(15, 118, 110, 0.16);
            border-color: #0f766e;
        }

        .field-error-list,
        .field-error {
            color: #b42318;
        }

        .field-error-list {
            margin: 0;
            padding: 0.9rem 1rem;
            list-style: none;
            border: 1px solid #f3c8c3;
            border-radius: 0.9rem;
            background: #fff5f4;
        }

        .field-error-list li + li {
            margin-top: 0.35rem;
        }

        .field-error {
            font-size: 0.9rem;
        }

        .field {
            display: grid;
            gap: 0.45rem;
        }

        .label {
            font-size: 0.95rem;
            font-weight: 600;
            color: #13202b;
        }

        .select,
        .textarea {
            width: 100%;
            padding: 0.8rem 0.95rem;
            border: 1px solid #cfd8e3;
            border-radius: 0.9rem;
            background: #fff;
            color: #13202b;
            font: inherit;
        }

        .select:focus,
        .textarea:focus {
            outline: 2px solid rgba(15, 118, 110, 0.16);
            border-color: #0f766e;
        }

        @media (max-width: 720px) {
            .ticket-hero {
                padding: 1rem;
            }

            .ticket-subject {
                font-size: 1.4rem;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .detail-card.full {
                grid-column: auto;
            }

            .comment-head {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.35rem;
            }

            .editable-summary,
            .inline-form-row {
                flex-direction: column;
                align-items: stretch;
            }

            .original-version-head,
            .detail-label-row {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-head">
            <div class="page-head-bar">
                <div>
                    <h2>Detail ticketu</h2>
                    <p>První detailní přehled helpdesk požadavku.</p>
                </div>

            <div class="page-head-actions">
                <a class="button button-secondary" href="{{ route('tickets.edit', $ticket) }}">Upravit ticket</a>
                <a class="button button-secondary" href="{{ route('tickets.index') }}">Zpět na seznam</a>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="ticket-detail">
            @if (session('status'))
                <div class="alert" role="status">{{ session('status') }}</div>
            @endif

            @if ($hasOriginalVersionChanges && $originalSnapshot)
                <section
                    id="original-version-box"
                    class="original-version-panel"
                    data-editor-panel
                    hidden
                >
                    <div class="original-version-head">
                        <div class="original-version-copy">
                            <h3>Původní verze ticketu</h3>
                            <p>Neměnný otisk ticketu uložený do historie před pozdějšími úpravami.</p>
                        </div>

                        <button class="button button-secondary button-compact" type="button" data-editor-cancel="original-version-box">
                            Zavřít
                        </button>
                    </div>

                    @if ($originalSnapshotSource !== 'create')
                        <p class="original-version-note">
                            U staršího ticketu byl původní snapshot zachycen až při první následné změně po zavedení historie.
                        </p>
                    @endif

                    <section class="detail-grid">
                        <article class="detail-card full">
                            <span class="detail-label">Subject</span>
                            <div class="detail-value">{{ $originalSnapshot['subject'] ?? '—' }}</div>
                        </article>

                        <article class="detail-card full">
                            <span class="detail-label">Description</span>
                            <div class="detail-value">
                                {!! nl2br(e($originalSnapshot['description'] ?? '—')) !!}
                            </div>
                        </article>
                    </section>
                </section>
            @endif

            <section class="ticket-hero">
                <div class="ticket-number">{{ $ticket->ticket_number ?? 'Bez čísla ticketu' }}</div>
                <h3 class="ticket-subject">{{ $ticket->subject }}</h3>
                <div class="ticket-meta">
                    <span class="badge">
                        <span class="badge-dot"></span>
                        {{ $ticket->status?->name ?? '—' }}
                    </span>
                    <span class="badge">
                        <span class="badge-dot"></span>
                        {{ $ticket->priority?->name ?? '—' }}
                    </span>
                </div>
            </section>

            <section class="detail-grid">
                <article class="detail-card full">
                    <div class="detail-label-row">
                        <span class="detail-label">Description</span>

                        @if ($hasOriginalVersionChanges && $originalSnapshot)
                            <button
                                class="button original-indicator"
                                type="button"
                                data-editor-toggle="original-version-box"
                                aria-controls="original-version-box"
                                aria-expanded="false"
                                title="Zobrazit původní verzi ticketu"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 3v5h5"/>
                                    <path d="M3.05 13a9 9 0 1 0 2.13-5.7L3 8"/>
                                    <path d="M12 7v5l3 2"/>
                                </svg>
                                Upraveno
                            </button>
                        @endif
                    </div>

                    <div class="detail-value">
                        {!! nl2br(e($ticket->description ?? '—')) !!}
                    </div>
                </article>

                <article class="detail-card">
                    <span class="detail-label">Status</span>
                    <div class="editable-summary">
                        <div class="detail-value editable-value">
                            <span class="badge">
                                <span class="badge-dot"></span>
                                {{ $ticket->status?->name ?? '—' }}
                            </span>
                        </div>

                        <button
                            class="button icon-button"
                            type="button"
                            data-editor-toggle="status-editor"
                            aria-controls="status-editor"
                            aria-expanded="false"
                            title="Upravit stav"
                        >
                            <span class="sr-only">Upravit stav</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
                            </svg>
                        </button>
                    </div>

                    <form
                        id="status-editor"
                        class="inline-form"
                        data-editor-panel
                        method="post"
                        action="{{ route('tickets.status.update', $ticket) }}"
                        hidden
                    >
                        @csrf
                        @method('patch')

                        @if ($statusErrors->any())
                            <ul class="field-error-list">
                                @foreach ($statusErrors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="field">
                            <label class="label" for="status_id">Změnit stav</label>
                            <div class="inline-form-row">
                                <select class="select" id="status_id" name="status_id" required>
                                    @foreach ($statuses as $status)
                                        <option
                                            value="{{ $status->id }}"
                                            @selected((string) old('status_id', $ticket->ticket_status_id) === (string) $status->id)
                                        >
                                            {{ $status->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($statusErrors->has('status_id'))
                                <div class="field-error">{{ $statusErrors->first('status_id') }}</div>
                            @endif
                        </div>

                        <div class="inline-form-actions">
                            <button class="button button-primary button-compact" type="submit">Uložit stav</button>
                            <button class="button button-secondary button-compact" type="button" data-editor-cancel="status-editor">Zrušit</button>
                        </div>

                        <p class="inline-help">Interní administrativní akce připravená pro pozdější doplnění oprávnění.</p>
                    </form>
                </article>

                <article class="detail-card">
                    <span class="detail-label">Priority</span>
                    <div class="detail-value">{{ $ticket->priority?->name ?? '—' }}</div>
                </article>

                <article class="detail-card">
                    <span class="detail-label">Category</span>
                    <div class="detail-value">{{ $ticket->category?->name ?? '—' }}</div>
                </article>

                <article class="detail-card">
                    <span class="detail-label">Requester</span>
                    <div class="detail-value">{{ $ticket->requester?->name ?? '—' }}</div>
                </article>

                <article class="detail-card">
                    <span class="detail-label">Assignee</span>
                    <div class="editable-summary">
                        <div class="detail-value editable-value">
                            @if ($ticket->assignee)
                                {{ $ticket->assignee->name }}
                            @else
                                <span class="detail-empty">Nepřiřazeno</span>
                            @endif
                        </div>

                        <button
                            class="button icon-button"
                            type="button"
                            data-editor-toggle="assignee-editor"
                            aria-controls="assignee-editor"
                            aria-expanded="false"
                            title="Upravit řešitele"
                        >
                            <span class="sr-only">Upravit řešitele</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
                            </svg>
                        </button>
                    </div>

                    <form
                        id="assignee-editor"
                        class="inline-form"
                        data-editor-panel
                        method="post"
                        action="{{ route('tickets.assignee.update', $ticket) }}"
                        hidden
                    >
                        @csrf
                        @method('patch')

                        @if ($assigneeErrors->any())
                            <ul class="field-error-list">
                                @foreach ($assigneeErrors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="field">
                            <label class="label" for="assignee_id">Přiřadit řešitele</label>
                            <div class="inline-form-row">
                                <select class="select" id="assignee_id" name="assignee_id">
                                    <option value="">Nepřiřazeno</option>
                                    @foreach ($assignees as $assignee)
                                        <option
                                            value="{{ $assignee->id }}"
                                            @selected((string) old('assignee_id', $ticket->assignee_id) === (string) $assignee->id)
                                        >
                                            {{ $assignee->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($assigneeErrors->has('assignee_id'))
                                <div class="field-error">{{ $assigneeErrors->first('assignee_id') }}</div>
                            @endif
                        </div>

                        <div class="inline-form-actions">
                            <button class="button button-primary button-compact" type="submit">Uložit řešitele</button>
                            <button class="button button-secondary button-compact" type="button" data-editor-cancel="assignee-editor">Zrušit</button>
                        </div>

                        <p class="inline-help">Prázdná hodnota znamená, že ticket zůstane nepřiřazený.</p>
                    </form>
                </article>

                <article class="detail-card">
                    <span class="detail-label">Created at</span>
                    <div class="detail-value">{{ $ticket->created_at?->format('d.m.Y H:i') ?? '—' }}</div>
                </article>

                <article class="detail-card">
                    <span class="detail-label">Updated at</span>
                    <div class="detail-value">{{ $ticket->updated_at?->format('d.m.Y H:i') ?? '—' }}</div>
                </article>

                <article class="detail-card">
                    <span class="detail-label">Připnutí</span>
                    @if ($pinningEnabled)
                        <div class="detail-value">
                            @if ($ticket->is_pinned)
                                <span class="detail-flag">Připnuto</span>
                            @else
                                <span class="detail-empty">Nepřipnuto</span>
                            @endif
                        </div>

                        <form class="inline-form" method="post" action="{{ route('tickets.pin.update', $ticket) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="pinned" value="{{ $ticket->is_pinned ? '0' : '1' }}">

                            @if ($pinErrors->any())
                                <ul class="field-error-list">
                                    @foreach ($pinErrors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            @if ($pinErrors->has('pinned'))
                                <div class="field-error">{{ $pinErrors->first('pinned') }}</div>
                            @endif

                            <div class="inline-form-actions">
                                <button class="button button-primary button-compact" type="submit">
                                    {{ $ticket->is_pinned ? 'Odepnout ticket' : 'Připnout ticket' }}
                                </button>
                            </div>

                            <p class="inline-help">Interní administrativní akce připravená pro pozdější doplnění oprávnění.</p>
                        </form>
                    @else
                        <div class="detail-value detail-empty">Připnutí bude dostupné po spuštění databázové migrace.</div>
                    @endif
                </article>
            </section>

            <section class="comment-section">
                <div class="page-head" id="comments">
                    <div class="page-head-bar">
                        <div>
                            <h2>Komentáře</h2>
                            <p>Veřejná komunikace k tomuto ticketu v chronologickém pořadí.</p>
                        </div>

                        <button
                            class="button icon-button"
                            type="button"
                            data-editor-toggle="comment-editor"
                            aria-controls="comment-editor"
                            aria-expanded="false"
                            title="Přidat veřejný komentář"
                        >
                            <span class="sr-only">Přidat veřejný komentář</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
                            </svg>
                        </button>
                    </div>

                    @if ($commentErrors->any())
                        <div class="field-error">Formulář veřejného komentáře obsahuje chyby. Otevřete editor tlačítkem s tužkou.</div>
                    @endif
                </div>

                @if ($ticket->publicComments->isEmpty())
                    <div class="comment-empty">Zatím tu nejsou žádné veřejné komentáře.</div>
                @else
                    <div class="comment-list">
                        @foreach ($ticket->publicComments as $comment)
                            <article class="comment-card">
                                <div class="comment-head">
                                    <div class="comment-author">{{ $comment->user?->name ?? 'Neznámý uživatel' }}</div>
                                    <div class="comment-time">{{ $comment->created_at?->format('d.m.Y H:i') ?? '—' }}</div>
                                </div>
                                <div class="comment-body">
                                    {!! nl2br(e($comment->body)) !!}
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif

                <form
                    id="comment-editor"
                    class="comment-form"
                    data-editor-panel
                    method="post"
                    action="{{ route('tickets.comments.store', $ticket) }}"
                    hidden
                >
                    @csrf

                    <div class="comment-form-head">
                        <h3>Přidat veřejný komentář</h3>
                        <p>Komentář bude uložen jako veřejný a zobrazí se v historii ticketu.</p>
                    </div>

                    @if ($commentErrors->any())
                        <ul class="field-error-list">
                            @foreach ($commentErrors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <div>
                        <textarea class="textarea" name="body" required>{{ old('body') }}</textarea>
                        @if ($commentErrors->has('body'))
                            <div class="field-error">{{ $commentErrors->first('body') }}</div>
                        @endif
                    </div>

                    <div class="comment-form-actions">
                        <button class="button button-primary" type="submit">Přidat komentář</button>
                        <button class="button button-secondary" type="button" data-editor-cancel="comment-editor">Zrušit</button>
                    </div>
                </form>
            </section>

            <section class="comment-section">
                <div class="page-head" id="internal-notes">
                    <div class="page-head-bar">
                        <div>
                            <h2>Interní poznámky</h2>
                            <p>Oddělený interní blok pro administrativní poznámky k ticketu.</p>
                        </div>

                        <button
                            class="button icon-button"
                            type="button"
                            data-editor-toggle="internal-note-editor"
                            aria-controls="internal-note-editor"
                            aria-expanded="false"
                            title="Přidat interní poznámku"
                        >
                            <span class="sr-only">Přidat interní poznámku</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>
                            </svg>
                        </button>
                    </div>

                    @if ($internalNoteErrors->any())
                        <div class="field-error">Formulář interní poznámky obsahuje chyby. Otevřete editor tlačítkem s tužkou.</div>
                    @endif
                </div>

                @if ($ticket->internalComments->isEmpty())
                    <div class="comment-empty">Zatím tu nejsou žádné interní poznámky.</div>
                @else
                    <div class="comment-list">
                        @foreach ($ticket->internalComments as $note)
                            <article class="comment-card internal-note-card">
                                <div class="comment-head">
                                    <div class="comment-author">{{ $note->user?->name ?? 'Neznámý uživatel' }}</div>
                                    <div class="comment-time">{{ $note->created_at?->format('d.m.Y H:i') ?? '—' }}</div>
                                </div>
                                <div class="comment-body">
                                    {!! nl2br(e($note->body)) !!}
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif

                <form
                    id="internal-note-editor"
                    class="comment-form internal-note-form"
                    data-editor-panel
                    method="post"
                    action="{{ route('tickets.internal-notes.store', $ticket) }}"
                    hidden
                >
                    @csrf

                    <div class="comment-form-head">
                        <h3>Přidat interní poznámku</h3>
                        <p>Interní administrativní akce připravená pro pozdější doplnění oprávnění.</p>
                    </div>

                    @if ($internalNoteErrors->any())
                        <ul class="field-error-list">
                            @foreach ($internalNoteErrors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <div>
                        <textarea class="textarea" name="note_body" required>{{ old('note_body') }}</textarea>
                        @if ($internalNoteErrors->has('note_body'))
                            <div class="field-error">{{ $internalNoteErrors->first('note_body') }}</div>
                        @endif
                    </div>

                    <div class="comment-form-actions">
                        <button class="button button-primary" type="submit">Uložit interní poznámku</button>
                        <button class="button button-secondary" type="button" data-editor-cancel="internal-note-editor">Zrušit</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const setExpanded = (panelId, expanded) => {
                document.querySelectorAll(`[data-editor-toggle="${panelId}"]`).forEach((button) => {
                    button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                });
            };

            document.querySelectorAll('[data-editor-toggle]').forEach((button) => {
                button.addEventListener('click', () => {
                    const panelId = button.getAttribute('data-editor-toggle');
                    const panel = document.getElementById(panelId);

                    if (!panel) {
                        return;
                    }

                    panel.hidden = false;
                    setExpanded(panelId, true);

                    const focusTarget = panel.querySelector('select, textarea, input');

                    if (focusTarget) {
                        focusTarget.focus();
                    }
                });
            });

            document.querySelectorAll('[data-editor-cancel]').forEach((button) => {
                button.addEventListener('click', () => {
                    const panelId = button.getAttribute('data-editor-cancel');
                    const panel = document.getElementById(panelId);

                    if (!panel) {
                        return;
                    }

                    panel.hidden = true;
                    setExpanded(panelId, false);
                });
            });

            document.querySelectorAll('[data-editor-panel]').forEach((panel) => {
                setExpanded(panel.id, !panel.hidden);
            });
        });
    </script>
@endpush
