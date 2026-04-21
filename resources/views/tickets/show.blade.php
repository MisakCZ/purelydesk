@extends('layouts.admin')

@section('title', $ticket->ticket_number ? 'Ticket '.$ticket->ticket_number : 'Detail ticketu')

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
                    <div class="detail-value">{{ $ticket->status?->name ?? '—' }}</div>
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
        </div>
    </div>
@endsection
