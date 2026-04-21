@extends('layouts.admin')

@section('title', 'Tickety')

@push('styles')
    <style>
        .table-wrap {
            overflow-x: auto;
        }

        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
        }

        .ticket-table th,
        .ticket-table td {
            padding: 0.9rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e5ebf1;
            vertical-align: middle;
        }

        .ticket-table th {
            color: #5b6b79;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            background: #f8fafc;
        }

        .ticket-table tbody tr:hover {
            background: #f8fbfc;
        }

        .ticket-number {
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
        }

        .subject {
            min-width: 18rem;
        }

        .subject strong {
            display: block;
            color: #13202b;
        }

        .subject span,
        .muted {
            color: #5b6b79;
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

        .ticket-link {
            color: inherit;
            text-decoration: none;
        }

        .ticket-link:hover {
            color: #0f766e;
            text-decoration: underline;
        }

        .empty-state {
            padding: 3.5rem 1.5rem;
            text-align: center;
            background: linear-gradient(180deg, #fbfdff 0%, #f7fafc 100%);
            border: 1px dashed #cfd8e3;
            border-radius: 1rem;
        }

        .empty-state h3 {
            margin: 0;
            font-size: 1.2rem;
            color: #13202b;
        }

        .empty-state p {
            max-width: 34rem;
            margin: 0.75rem auto 0;
            color: #5b6b79;
            line-height: 1.6;
        }

        .alert {
            margin-bottom: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 0.9rem;
            border: 1px solid #b7e4dd;
            background: #ecfdf8;
            color: #0f513f;
        }

        @media (max-width: 720px) {
            .empty-state {
                padding: 2.5rem 1rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-head">
        <div class="page-head-bar">
            <div>
                <h2>Seznam ticketů</h2>
                <p>Přehled aktuálních helpdesk požadavků v systému.</p>
            </div>

            <a class="button button-primary" href="{{ route('tickets.create') }}">Nový ticket</a>
        </div>
    </div>

    <div class="page-body">
        @if (session('status'))
            <div class="alert" role="status">{{ session('status') }}</div>
        @endif

        @if ($tickets->isEmpty())
            <section class="empty-state" aria-label="Prázdný seznam ticketů">
                <h3>Zatím nejsou evidované žádné tickety</h3>
                <p>Jakmile budou v systému vytvořené první požadavky, zobrazí se zde jejich seznam včetně stavu, priority a přiřazení.</p>
            </section>
        @else
            <div class="table-wrap">
                <table class="ticket-table">
                    <thead>
                        <tr>
                            <th scope="col">Ticket number</th>
                            <th scope="col">Subject</th>
                            <th scope="col">Status</th>
                            <th scope="col">Priority</th>
                            <th scope="col">Requester</th>
                            <th scope="col">Assignee</th>
                            <th scope="col">Updated at</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr>
                                <td class="ticket-number">
                                    <a class="ticket-link" href="{{ route('tickets.show', $ticket) }}">
                                        {{ $ticket->ticket_number ?? '—' }}
                                    </a>
                                </td>
                                <td class="subject">
                                    <strong>
                                        <a class="ticket-link" href="{{ route('tickets.show', $ticket) }}">
                                            {{ $ticket->subject }}
                                        </a>
                                    </strong>
                                    <span>{{ $ticket->visibility === 'restricted' ? 'Restricted' : 'Public' }}</span>
                                </td>
                                <td>
                                    <span class="badge">
                                        <span class="badge-dot"></span>
                                        {{ $ticket->status?->name ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge">
                                        <span class="badge-dot"></span>
                                        {{ $ticket->priority?->name ?? '—' }}
                                    </span>
                                </td>
                                <td>{{ $ticket->requester?->name ?? '—' }}</td>
                                <td>{{ $ticket->assignee?->name ?? '—' }}</td>
                                <td class="muted">{{ $ticket->updated_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
