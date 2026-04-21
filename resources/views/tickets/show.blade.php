@extends('layouts.admin')

@section('title', $ticket->ticket_number ? 'Ticket '.$ticket->ticket_number : 'Detail ticketu')

@php
    $errorBags = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $commentErrors = $errorBags->getBag('comment');
    $assigneeErrors = $errorBags->getBag('ticketAssignee');
    $statusErrors = $errorBags->getBag('ticketStatus');
@endphp

@push('styles')
    <style>
        .ticket-detail {
            display: grid;
            gap: 1.5rem;
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

        .detail-value {
            color: #13202b;
            font-size: 1rem;
            line-height: 1.6;
        }

        .detail-empty {
            color: #64748b;
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

        .inline-help {
            margin: 0;
            color: #64748b;
            font-size: 0.92rem;
        }

        .comment-empty {
            padding: 1.25rem;
            border: 1px dashed #cfd8e3;
            border-radius: 1rem;
            background: #fbfdff;
            color: #64748b;
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

            .inline-form-row {
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

            <a class="button button-secondary" href="{{ route('tickets.index') }}">Zpět na seznam</a>
        </div>
    </div>

    <div class="page-body">
        <div class="ticket-detail">
            @if (session('status'))
                <div class="alert" role="status">{{ session('status') }}</div>
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
                    <span class="detail-label">Description</span>
                    <div class="detail-value">
                        {!! nl2br(e($ticket->description ?? '—')) !!}
                    </div>
                </article>

                <article class="detail-card">
                    <span class="detail-label">Status</span>
                    <div class="detail-value">
                        <span class="badge">
                            <span class="badge-dot"></span>
                            {{ $ticket->status?->name ?? '—' }}
                        </span>
                    </div>

                    <form class="inline-form" method="post" action="{{ route('tickets.status.update', $ticket) }}">
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
                                <button class="button button-primary" type="submit">Uložit stav</button>
                            </div>
                            @if ($statusErrors->has('status_id'))
                                <div class="field-error">{{ $statusErrors->first('status_id') }}</div>
                            @endif
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
                    <div class="detail-value">
                        @if ($ticket->assignee)
                            {{ $ticket->assignee->name }}
                        @else
                            <span class="detail-empty">Nepřiřazeno</span>
                        @endif
                    </div>

                    <form class="inline-form" method="post" action="{{ route('tickets.assignee.update', $ticket) }}">
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
                                <button class="button button-primary" type="submit">Uložit řešitele</button>
                            </div>
                            @if ($assigneeErrors->has('assignee_id'))
                                <div class="field-error">{{ $assigneeErrors->first('assignee_id') }}</div>
                            @endif
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
            </section>

            <section class="comment-section">
                <div class="page-head" id="comments">
                    <div>
                        <h2>Komentáře</h2>
                        <p>Veřejná komunikace k tomuto ticketu v chronologickém pořadí.</p>
                    </div>
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

                <form class="comment-form" method="post" action="{{ route('tickets.comments.store', $ticket) }}">
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

                    <div>
                        <button class="button button-primary" type="submit">Přidat komentář</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
