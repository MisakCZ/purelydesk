@extends('layouts.admin')

@section('title', $ticket->ticket_number ? 'Ticket '.$ticket->ticket_number : 'Detail ticketu')

@php
    $errorBags = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $commentErrors = $errorBags->getBag('comment');
    $internalNoteErrors = $errorBags->getBag('internalNote');
    $assigneeErrors = $errorBags->getBag('ticketAssignee');
    $categoryErrors = $errorBags->getBag('ticketCategory');
    $pinErrors = $errorBags->getBag('ticketPin');
    $priorityErrors = $errorBags->getBag('ticketPriority');
    $statusErrors = $errorBags->getBag('ticketStatus');
    $visibilityErrors = $errorBags->getBag('ticketVisibility');
    $watcherErrors = $errorBags->getBag('ticketWatcher');
    $replyParentId = (string) old('parent_id', '');
    $heroAdminHasErrors = $statusErrors->any()
        || $priorityErrors->any()
        || $visibilityErrors->any()
        || $assigneeErrors->any()
        || $categoryErrors->any()
        || $pinErrors->any()
        || $watcherErrors->any();
    $historyFieldLabels = [
        'ticket_number' => 'Ticket number',
        'subject' => 'Subject',
        'description' => 'Description',
        'visibility' => 'Visibility',
        'status' => 'Status',
        'priority' => 'Priority',
        'category' => 'Category',
        'requester' => 'Requester',
        'assignee' => 'Assignee',
        'pinned' => 'Připnutí',
        'closed_at' => 'Uzavření',
        'created_at' => 'Created at',
    ];
    $describeHistoryEntry = function ($entry) use ($historyFieldLabels) {
        $event = (string) $entry->event;
        $changedFields = collect($entry->meta['changed_fields'] ?? [])
            ->map(fn ($field) => $historyFieldLabels[$field] ?? ucfirst(str_replace('_', ' ', (string) $field)))
            ->filter()
            ->values();

        return match ($event) {
            \App\Models\TicketHistory::EVENT_CREATED => 'Původní snapshot ticketu byl uložen při vytvoření.',
            \App\Models\TicketHistory::EVENT_ORIGINAL_SNAPSHOT_BACKFILLED => 'Původní snapshot ticketu byl doplněn dodatečně k existujícímu ticketu.',
            \App\Models\TicketHistory::EVENT_UPDATED => $changedFields->isNotEmpty()
                ? 'Upraveno: '.$changedFields->implode(', ').'.'
                : 'Byla zaznamenána změna ticketu.',
            default => 'Byla zaznamenána změna ticketu.',
        };
    };
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

        .hero-meta-actions {
            display: grid;
            gap: 0.85rem;
        }

        .hero-meta-help {
            margin: 0;
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .hero-admin-errors {
            color: #b42318;
            font-size: 0.92rem;
            line-height: 1.5;
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

        .section-panel {
            display: grid;
            gap: 1.1rem;
            padding: 1.25rem;
            border: 1px solid #e5ebf1;
            border-radius: 1rem;
            background: #fff;
        }

        .section-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .section-panel-head h2 {
            margin: 0;
            font-size: 1.15rem;
            color: #13202b;
        }

        .section-panel-head p {
            margin: 0.35rem 0 0;
            color: #64748b;
            line-height: 1.6;
        }

        .content-meta-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(16rem, 0.95fr);
            gap: 1rem 1.25rem;
            align-items: start;
        }

        .content-block {
            display: grid;
            gap: 0.8rem;
        }

        .content-description {
            min-height: 100%;
            padding: 1rem 1.05rem;
            border: 1px solid #edf2f7;
            border-radius: 0.95rem;
            background: #fbfdff;
        }

        .metadata-list {
            display: grid;
            gap: 0.85rem;
            padding: 1rem 1.05rem;
            border: 1px solid #edf2f7;
            border-radius: 0.95rem;
            background: #f8fafc;
        }

        .metadata-item {
            display: grid;
            gap: 0.25rem;
        }

        .metadata-label {
            color: #5b6b79;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .metadata-value {
            color: #13202b;
            line-height: 1.5;
        }

        .management-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .management-item {
            display: grid;
            gap: 0.75rem;
            align-content: start;
            padding: 1rem 1.05rem;
            border: 1px solid #edf2f7;
            border-radius: 0.95rem;
            background: #fbfdff;
        }

        .management-item.wide {
            grid-column: 1 / -1;
        }

        .management-note {
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.5;
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

        .badge-button {
            border: 0;
            cursor: pointer;
            font: inherit;
        }

        .badge-button:hover {
            background: #e2edf6;
            color: #0f766e;
        }

        .badge-button:focus-visible {
            outline: 2px solid rgba(15, 118, 110, 0.2);
            outline-offset: 2px;
        }

        .badge-watching {
            background: #dff5f2;
            color: #0f766e;
        }

        .badge-button.badge-watching:hover {
            background: #c9efe9;
            color: #0b5e57;
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

        .hero-admin-panels {
            display: grid;
            gap: 0.85rem;
        }

        .hero-admin-panel {
            margin-top: 0;
            padding: 1rem 1.05rem;
            border: 1px solid #d9e0e7;
            border-radius: 0.95rem;
            background: rgba(255, 255, 255, 0.92);
        }

        .hero-admin-panel .comment-form-head h3 {
            font-size: 1rem;
        }

        .history-panel {
            display: grid;
            gap: 1rem;
        }

        .watcher-list {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0;
        }

        .watcher-pill {
            display: inline-flex;
            align-items: center;
            min-height: 1.95rem;
            padding: 0.35rem 0.75rem;
            border: 1px solid #d9e0e7;
            border-radius: 999px;
            background: #f8fafc;
            color: #334155;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .watcher-empty {
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.5;
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

        .comment-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.8rem;
        }

        .comment-link {
            padding: 0;
            border: 0;
            background: transparent;
            color: #0f766e;
            font: inherit;
            font-size: 0.92rem;
            font-weight: 600;
            cursor: pointer;
        }

        .comment-link:hover {
            color: #0b5e57;
            text-decoration: underline;
        }

        .comment-children {
            display: grid;
            gap: 0.75rem;
            margin-top: 0.95rem;
            margin-left: 1.35rem;
            padding-left: 1rem;
            border-left: 2px solid #d9e7e4;
        }

        .comment-card.reply-card {
            padding: 0.9rem 1rem;
            background: #f8fbfc;
        }

        .comment-form {
            display: grid;
            gap: 1rem;
            padding: 1rem 1.1rem;
            border: 1px solid #e5ebf1;
            border-radius: 1rem;
            background: #fff;
        }

        .comment-form.reply-form {
            margin-top: 0.9rem;
            padding: 0.95rem 1rem;
            background: #f8fbfc;
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

             .content-meta-layout,
             .management-grid {
                grid-template-columns: 1fr;
            }

            .detail-card.full {
                grid-column: auto;
            }

            .management-item.wide {
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
            .detail-label-row,
            .section-panel-head {
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
                <div class="hero-meta-actions">
                    <div class="ticket-meta">
                        <button
                            class="badge badge-button"
                            type="button"
                            data-editor-toggle="hero-status-editor"
                            aria-controls="hero-status-editor"
                            aria-expanded="false"
                            title="Upravit stav"
                        >
                            <span class="badge-dot"></span>
                            Status: {{ $ticket->status?->name ?? '—' }}
                        </button>

                        <button
                            class="badge badge-button"
                            type="button"
                            data-editor-toggle="hero-priority-editor"
                            aria-controls="hero-priority-editor"
                            aria-expanded="false"
                            title="Upravit prioritu"
                        >
                            <span class="badge-dot"></span>
                            Priorita: {{ $ticket->priority?->name ?? '—' }}
                        </button>

                        <button
                            class="badge badge-button"
                            type="button"
                            data-editor-toggle="hero-visibility-editor"
                            aria-controls="hero-visibility-editor"
                            aria-expanded="false"
                            title="Upravit viditelnost"
                        >
                            <span class="badge-dot"></span>
                            Viditelnost: {{ $visibilityOptions[$ticket->visibility] ?? ucfirst((string) $ticket->visibility) }}
                        </button>

                        <button
                            class="badge badge-button"
                            type="button"
                            data-editor-toggle="hero-assignee-editor"
                            aria-controls="hero-assignee-editor"
                            aria-expanded="false"
                            title="Upravit řešitele"
                        >
                            <span class="badge-dot"></span>
                            Řešitel: {{ $ticket->assignee?->name ?? 'Nepřiřazeno' }}
                        </button>

                        <button
                            class="badge badge-button"
                            type="button"
                            data-editor-toggle="hero-category-editor"
                            aria-controls="hero-category-editor"
                            aria-expanded="false"
                            title="Upravit kategorii"
                        >
                            <span class="badge-dot"></span>
                            Kategorie: {{ $ticket->category?->name ?? '—' }}
                        </button>

                        <button
                            class="badge badge-button{{ $pinningEnabled && $ticket->is_pinned ? ' badge-watching' : '' }}"
                            type="button"
                            data-editor-toggle="hero-pin-editor"
                            aria-controls="hero-pin-editor"
                            aria-expanded="false"
                            title="Upravit připnutí"
                        >
                            <span class="badge-dot"></span>
                            Připnutí:
                            @if (! $pinningEnabled)
                                Nedostupné
                            @else
                                {{ $ticket->is_pinned ? 'Ano' : 'Ne' }}
                            @endif
                        </button>

                        <button
                            class="badge badge-button{{ $isWatchingTicket ? ' badge-watching' : '' }}"
                            type="button"
                            data-editor-toggle="hero-watcher-editor"
                            aria-controls="hero-watcher-editor"
                            aria-expanded="false"
                            title="Upravit sledování"
                        >
                            <span class="badge-dot"></span>
                            Sledování: {{ $isWatchingTicket ? 'Ano' : 'Ne' }}
                        </button>
                    </div>

                    <p class="hero-meta-help">Kliknutím na badge rychle upravíte provozní nastavení ticketu.</p>

                    @if ($heroAdminHasErrors)
                        <div class="hero-admin-errors">Některý z formulářů správy ticketu obsahuje chyby. Otevřete příslušný badge.</div>
                    @endif

                    <div class="hero-admin-panels">
                        <form
                            id="hero-status-editor"
                            class="inline-form hero-admin-panel"
                            data-editor-panel
                            method="post"
                            action="{{ route('tickets.status.update', $ticket) }}"
                            hidden
                        >
                            @csrf
                            @method('patch')

                            <div class="comment-form-head">
                                <h3>Změnit stav ticketu</h3>
                            </div>

                            @if ($statusErrors->any())
                                <ul class="field-error-list">
                                    @foreach ($statusErrors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="field">
                                <label class="label" for="hero_status_id">Stav</label>
                                <div class="inline-form-row">
                                    <select class="select" id="hero_status_id" name="status_id" required>
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
                                <button class="button button-secondary button-compact" type="button" data-editor-cancel="hero-status-editor">Zrušit</button>
                            </div>
                        </form>

                        <form
                            id="hero-priority-editor"
                            class="inline-form hero-admin-panel"
                            data-editor-panel
                            method="post"
                            action="{{ route('tickets.priority.update', $ticket) }}"
                            hidden
                        >
                            @csrf
                            @method('patch')

                            <div class="comment-form-head">
                                <h3>Změnit prioritu ticketu</h3>
                            </div>

                            @if ($priorityErrors->any())
                                <ul class="field-error-list">
                                    @foreach ($priorityErrors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="field">
                                <label class="label" for="hero_priority_id">Priorita</label>
                                <div class="inline-form-row">
                                    <select class="select" id="hero_priority_id" name="priority_id" required>
                                        @foreach ($priorities as $priority)
                                            <option
                                                value="{{ $priority->id }}"
                                                @selected((string) old('priority_id', $ticket->ticket_priority_id) === (string) $priority->id)
                                            >
                                                {{ $priority->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @if ($priorityErrors->has('priority_id'))
                                    <div class="field-error">{{ $priorityErrors->first('priority_id') }}</div>
                                @endif
                            </div>

                            <div class="inline-form-actions">
                                <button class="button button-primary button-compact" type="submit">Uložit prioritu</button>
                                <button class="button button-secondary button-compact" type="button" data-editor-cancel="hero-priority-editor">Zrušit</button>
                            </div>
                        </form>

                        <form
                            id="hero-visibility-editor"
                            class="inline-form hero-admin-panel"
                            data-editor-panel
                            method="post"
                            action="{{ route('tickets.visibility.update', $ticket) }}"
                            hidden
                        >
                            @csrf
                            @method('patch')

                            <div class="comment-form-head">
                                <h3>Změnit viditelnost ticketu</h3>
                            </div>

                            @if ($visibilityErrors->any())
                                <ul class="field-error-list">
                                    @foreach ($visibilityErrors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="field">
                                <label class="label" for="hero_visibility">Viditelnost</label>
                                <div class="inline-form-row">
                                    <select class="select" id="hero_visibility" name="visibility" required>
                                        @foreach ($visibilityOptions as $value => $label)
                                            <option
                                                value="{{ $value }}"
                                                @selected((string) old('visibility', $ticket->visibility) === (string) $value)
                                            >
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @if ($visibilityErrors->has('visibility'))
                                    <div class="field-error">{{ $visibilityErrors->first('visibility') }}</div>
                                @endif
                            </div>

                            <div class="inline-form-actions">
                                <button class="button button-primary button-compact" type="submit">Uložit viditelnost</button>
                                <button class="button button-secondary button-compact" type="button" data-editor-cancel="hero-visibility-editor">Zrušit</button>
                            </div>
                        </form>

                        <form
                            id="hero-assignee-editor"
                            class="inline-form hero-admin-panel"
                            data-editor-panel
                            method="post"
                            action="{{ route('tickets.assignee.update', $ticket) }}"
                            hidden
                        >
                            @csrf
                            @method('patch')

                            <div class="comment-form-head">
                                <h3>Změnit řešitele ticketu</h3>
                            </div>

                            @if ($assigneeErrors->any())
                                <ul class="field-error-list">
                                    @foreach ($assigneeErrors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="field">
                                <label class="label" for="hero_assignee_id">Řešitel</label>
                                <div class="inline-form-row">
                                    <select class="select" id="hero_assignee_id" name="assignee_id">
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
                                <button class="button button-secondary button-compact" type="button" data-editor-cancel="hero-assignee-editor">Zrušit</button>
                            </div>
                        </form>

                        <form
                            id="hero-category-editor"
                            class="inline-form hero-admin-panel"
                            data-editor-panel
                            method="post"
                            action="{{ route('tickets.category.update', $ticket) }}"
                            hidden
                        >
                            @csrf
                            @method('patch')

                            <div class="comment-form-head">
                                <h3>Změnit kategorii ticketu</h3>
                            </div>

                            @if ($categoryErrors->any())
                                <ul class="field-error-list">
                                    @foreach ($categoryErrors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="field">
                                <label class="label" for="hero_category_id">Kategorie</label>
                                <div class="inline-form-row">
                                    <select class="select" id="hero_category_id" name="category_id" required>
                                        @foreach ($categories as $category)
                                            <option
                                                value="{{ $category->id }}"
                                                @selected((string) old('category_id', $ticket->ticket_category_id) === (string) $category->id)
                                            >
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @if ($categoryErrors->has('category_id'))
                                    <div class="field-error">{{ $categoryErrors->first('category_id') }}</div>
                                @endif
                            </div>

                            <div class="inline-form-actions">
                                <button class="button button-primary button-compact" type="submit">Uložit kategorii</button>
                                <button class="button button-secondary button-compact" type="button" data-editor-cancel="hero-category-editor">Zrušit</button>
                            </div>
                        </form>

                        @if ($pinningEnabled)
                            <form
                                id="hero-pin-editor"
                                class="inline-form hero-admin-panel"
                                data-editor-panel
                                method="post"
                                action="{{ route('tickets.pin.update', $ticket) }}"
                                hidden
                            >
                                @csrf
                                @method('patch')
                                <input type="hidden" name="pinned" value="{{ $ticket->is_pinned ? '0' : '1' }}">

                                <div class="comment-form-head">
                                    <h3>Změnit připnutí ticketu</h3>
                                </div>

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
                                    <button class="button button-secondary button-compact" type="button" data-editor-cancel="hero-pin-editor">Zrušit</button>
                                </div>
                            </form>
                        @else
                            <div
                                id="hero-pin-editor"
                                class="inline-form hero-admin-panel"
                                data-editor-panel
                                hidden
                            >
                                <div class="comment-form-head">
                                    <h3>Připnutí ticketu</h3>
                                    <p>Připnutí bude dostupné po spuštění databázové migrace.</p>
                                </div>

                                <div class="inline-form-actions">
                                    <button class="button button-secondary button-compact" type="button" data-editor-cancel="hero-pin-editor">Zavřít</button>
                                </div>
                            </div>
                        @endif

                        @if ($isWatchingTicket)
                            <form
                                id="hero-watcher-editor"
                                class="inline-form hero-admin-panel"
                                data-editor-panel
                                method="post"
                                action="{{ route('tickets.watchers.destroy', $ticket) }}"
                                hidden
                            >
                                @csrf
                                @method('delete')

                                <div class="comment-form-head">
                                    <h3>Sledování ticketu</h3>
                                    <p>Počet sledujících: {{ $ticket->watchers->count() }}</p>
                                </div>

                                @if ($watcherErrors->any())
                                    <ul class="field-error-list">
                                        @foreach ($watcherErrors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($watcherActionEnabled)
                                    <div class="inline-form-actions">
                                        <button class="button button-secondary button-compact" type="submit">Přestat sledovat</button>
                                        <button class="button button-secondary button-compact" type="button" data-editor-cancel="hero-watcher-editor">Zrušit</button>
                                    </div>
                                @else
                                    <p class="inline-help">Sledování zatím nelze použít, protože v databázi neexistuje žádný uživatel.</p>
                                @endif

                                @if ($ticket->watchers->isEmpty())
                                    <div class="watcher-empty">Zatím ticket nikdo nesleduje.</div>
                                @else
                                    <div class="watcher-list" aria-label="Seznam sledujících">
                                        @foreach ($ticket->watchers as $watcher)
                                            <span class="watcher-pill">{{ $watcher->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </form>
                        @else
                            <form
                                id="hero-watcher-editor"
                                class="inline-form hero-admin-panel"
                                data-editor-panel
                                method="post"
                                action="{{ route('tickets.watchers.store', $ticket) }}"
                                hidden
                            >
                                @csrf

                                <div class="comment-form-head">
                                    <h3>Sledování ticketu</h3>
                                    <p>Počet sledujících: {{ $ticket->watchers->count() }}</p>
                                </div>

                                @if ($watcherErrors->any())
                                    <ul class="field-error-list">
                                        @foreach ($watcherErrors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($watcherActionEnabled)
                                    <div class="inline-form-actions">
                                        <button class="button button-primary button-compact" type="submit">Začít sledovat</button>
                                        <button class="button button-secondary button-compact" type="button" data-editor-cancel="hero-watcher-editor">Zrušit</button>
                                    </div>
                                @else
                                    <p class="inline-help">Sledování zatím nelze použít, protože v databázi neexistuje žádný uživatel.</p>
                                @endif

                                @if ($ticket->watchers->isEmpty())
                                    <div class="watcher-empty">Zatím ticket nikdo nesleduje.</div>
                                @else
                                    <div class="watcher-list" aria-label="Seznam sledujících">
                                        @foreach ($ticket->watchers as $watcher)
                                            <span class="watcher-pill">{{ $watcher->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </form>
                        @endif
                    </div>
                </div>
            </section>

            <section class="section-panel">
                <div class="section-panel-head">
                    <div>
                        <h2>Obsah a metadata</h2>
                        <p>Popis požadavku a základní informace o založení a poslední úpravě ticketu.</p>
                    </div>
                </div>

                <div class="content-meta-layout">
                    <article class="content-block">
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

                        <div class="detail-value content-description">
                            {!! nl2br(e($ticket->description ?? '—')) !!}
                        </div>
                    </article>

                    <aside class="metadata-list" aria-label="Metadata ticketu">
                        <div class="metadata-item">
                            <span class="metadata-label">Requester</span>
                            <div class="metadata-value">{{ $ticket->requester?->name ?? '—' }}</div>
                        </div>

                        <div class="metadata-item">
                            <span class="metadata-label">Created at</span>
                            <div class="metadata-value">{{ $ticket->created_at?->format('d.m.Y H:i') ?? '—' }}</div>
                        </div>

                        <div class="metadata-item">
                            <span class="metadata-label">Updated at</span>
                            <div class="metadata-value">{{ $ticket->updated_at?->format('d.m.Y H:i') ?? '—' }}</div>
                        </div>
                    </aside>
                </div>
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
                        @if ($replyParentId !== '')
                            <div class="field-error">
                                @if ($commentThreadingEnabled)
                                    Formulář odpovědi obsahuje chyby. Otevřete ho odkazem Odpovědět u příslušného komentáře.
                                @else
                                    Odpověď na komentář bude dostupná po spuštění databázové migrace aplikace.
                                @endif
                            </div>
                        @else
                            <div class="field-error">Formulář veřejného komentáře obsahuje chyby. Otevřete editor tlačítkem s tužkou.</div>
                        @endif
                    @endif
                </div>

                @if ($publicCommentThreads->isEmpty())
                    <div class="comment-empty">Zatím tu nejsou žádné veřejné komentáře.</div>
                @else
                    <div class="comment-list">
                        @foreach ($publicCommentThreads as $comment)
                            <article class="comment-card">
                                <div class="comment-head">
                                    <div class="comment-author">{{ $comment->user?->name ?? 'Neznámý uživatel' }}</div>
                                    <div class="comment-time">{{ $comment->created_at?->format('d.m.Y H:i') ?? '—' }}</div>
                                </div>
                                <div class="comment-body">
                                    {!! nl2br(e($comment->body)) !!}
                                </div>

                                @if ($commentThreadingEnabled)
                                    <div class="comment-actions">
                                        <button
                                            class="comment-link"
                                            type="button"
                                            data-editor-toggle="reply-editor-{{ $comment->id }}"
                                            aria-controls="reply-editor-{{ $comment->id }}"
                                            aria-expanded="false"
                                        >
                                            Odpovědět
                                        </button>
                                    </div>

                                    <form
                                        id="reply-editor-{{ $comment->id }}"
                                        class="comment-form reply-form"
                                        data-editor-panel
                                        method="post"
                                        action="{{ route('tickets.comments.store', $ticket) }}"
                                        hidden
                                    >
                                        @csrf
                                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">

                                        <div class="comment-form-head">
                                            <h3>Odpovědět na komentář</h3>
                                            <p>Odpověď se uloží jako veřejný komentář pod tímto vláknem.</p>
                                        </div>

                                        @if ($replyParentId === (string) $comment->id && $commentErrors->any())
                                            <ul class="field-error-list">
                                                @foreach ($commentErrors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        <div>
                                            <textarea class="textarea" name="body" required>{{ $replyParentId === (string) $comment->id ? old('body') : '' }}</textarea>
                                            @if ($replyParentId === (string) $comment->id && $commentErrors->has('body'))
                                                <div class="field-error">{{ $commentErrors->first('body') }}</div>
                                            @endif
                                            @if ($replyParentId === (string) $comment->id && $commentErrors->has('parent_id'))
                                                <div class="field-error">{{ $commentErrors->first('parent_id') }}</div>
                                            @endif
                                        </div>

                                        <div class="comment-form-actions">
                                            <button class="button button-primary" type="submit">Odeslat odpověď</button>
                                            <button class="button button-secondary" type="button" data-editor-cancel="reply-editor-{{ $comment->id }}">Zrušit</button>
                                        </div>
                                    </form>
                                @endif

                                @if ($comment->publicReplies->isNotEmpty())
                                    <div class="comment-children" aria-label="Odpovědi na komentář">
                                        @foreach ($comment->publicReplies as $reply)
                                            <article class="comment-card reply-card">
                                                <div class="comment-head">
                                                    <div class="comment-author">{{ $reply->user?->name ?? 'Neznámý uživatel' }}</div>
                                                    <div class="comment-time">{{ $reply->created_at?->format('d.m.Y H:i') ?? '—' }}</div>
                                                </div>
                                                <div class="comment-body">
                                                    {!! nl2br(e($reply->body)) !!}
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                @endif
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
                        <textarea class="textarea" name="body" required>{{ $replyParentId === '' ? old('body') : '' }}</textarea>
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

            <section class="section-panel">
                <div class="section-panel-head" id="history">
                    <div>
                        <h2>Historie změn</h2>
                        <p>Auditní záznamy změn ticketu uložené v systému.</p>
                    </div>

                    <button
                        class="button button-secondary button-compact"
                        type="button"
                        data-editor-toggle="history-panel"
                        aria-controls="history-panel"
                        aria-expanded="false"
                    >
                        Zobrazit historii změn
                    </button>
                </div>

                <div id="history-panel" class="history-panel" data-editor-panel hidden>
                    @if ($ticket->history->isEmpty())
                        <div class="comment-empty">Zatím tu nejsou žádné záznamy historie změn.</div>
                    @else
                        <div class="comment-list">
                            @foreach ($ticket->history as $historyEntry)
                                <article class="comment-card">
                                    <div class="comment-head">
                                        <div class="comment-author">{{ $historyEntry->user?->name ?? 'Systém' }}</div>
                                        <div class="comment-time">{{ $historyEntry->created_at?->format('d.m.Y H:i') ?? '—' }}</div>
                                    </div>
                                    <div class="comment-body">
                                        {{ $describeHistoryEntry($historyEntry) }}
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const openPanel = (panel) => {
                panel.hidden = false;
                setExpanded(panel.id, true);

                const focusTarget = panel.querySelector('select, textarea, input');

                if (focusTarget) {
                    focusTarget.focus();
                }
            };

            const closePanel = (panel) => {
                panel.hidden = true;
                setExpanded(panel.id, false);
            };

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

                    if (panel.hidden) {
                        openPanel(panel);
                    } else {
                        closePanel(panel);
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

                    closePanel(panel);
                });
            });

            document.querySelectorAll('[data-editor-panel]').forEach((panel) => {
                setExpanded(panel.id, !panel.hidden);
            });
        });
    </script>
@endpush
