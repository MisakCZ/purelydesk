@extends('layouts.admin')

@section('title', 'Tickety')

@push('styles')
    <style>
        .filter-card {
            margin-bottom: 1rem;
            padding: 1rem;
            border: 1px solid #e5ebf1;
            border-radius: 1rem;
            background: #fff;
        }

        .announcement-stack {
            display: grid;
            gap: 0.85rem;
            margin-bottom: 1rem;
        }

        .announcement-card {
            padding: 1rem 1.1rem;
            border: 1px solid #d9e0e7;
            border-left-width: 0.45rem;
            border-radius: 1rem;
            background: #fff;
        }

        .announcement-card[data-type="info"] {
            border-left-color: #2563eb;
            background: #f8fbff;
        }

        .announcement-card[data-type="warning"] {
            border-left-color: #d97706;
            background: #fffaf0;
        }

        .announcement-card[data-type="outage"] {
            border-left-color: #dc2626;
            background: #fff6f6;
        }

        .announcement-card[data-type="maintenance"] {
            border-left-color: #7c3aed;
            background: #f9f7ff;
        }

        .announcement-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 0.65rem;
        }

        .announcement-title {
            margin: 0;
            font-size: 1.02rem;
            color: #13202b;
        }

        .announcement-body {
            margin: 0;
            color: #334155;
            line-height: 1.6;
            white-space: pre-line;
        }

        .announcement-meta {
            margin-top: 0.75rem;
            color: #5b6b79;
            font-size: 0.9rem;
        }

        .pinned-section {
            margin-bottom: 1rem;
            padding: 1rem;
            border: 1px solid #ead9b5;
            border-radius: 1rem;
            background: linear-gradient(180deg, #fffdfa 0%, #fff7e8 100%);
        }

        .pinned-section-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .pinned-section-head h3 {
            margin: 0;
            font-size: 1.05rem;
            color: #13202b;
        }

        .pinned-section-head p {
            margin: 0.35rem 0 0;
            color: #6b5a2e;
        }

        .pinned-grid {
            display: grid;
            gap: 0.9rem;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }

        .pinned-ticket {
            padding: 1rem 1.05rem;
            border: 1px solid #ead9b5;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.88);
            box-shadow: 0 10px 24px rgba(148, 123, 55, 0.08);
        }

        .pinned-ticket-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .pinned-ticket-number {
            color: #8a5a00;
            font-size: 0.84rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .pinned-ticket-subject {
            margin: 0.35rem 0 0;
            font-size: 1rem;
            line-height: 1.45;
            color: #13202b;
        }

        .pinned-ticket-meta {
            display: grid;
            gap: 0.45rem;
            margin-top: 0.9rem;
            color: #5b6b79;
            font-size: 0.92rem;
        }

        .pinned-ticket-badges {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.9rem;
        }

        .badge-pinned {
            background: #fce7b2;
            color: #8a5a00;
        }

        .filter-form {
            display: grid;
            gap: 0.85rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: minmax(14rem, 1.35fr) repeat(4, minmax(10rem, 0.9fr));
            gap: 0.85rem;
            align-items: end;
        }

        .filter-field {
            display: grid;
            gap: 0.45rem;
        }

        .filter-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.65rem;
        }

        .filter-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #13202b;
        }

        .filter-clear {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.35rem;
            height: 1.35rem;
            border-radius: 999px;
            color: #b42318;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1;
        }

        .filter-clear:hover {
            background: #fff1f1;
            color: #991b1b;
        }

        .filter-input,
        .filter-select {
            width: 100%;
            min-height: 2.9rem;
            padding: 0.8rem 0.95rem;
            border: 1px solid #cfd8e3;
            border-radius: 0.9rem;
            background: #fff;
            color: #13202b;
            font: inherit;
        }

        .filter-input:focus,
        .filter-select:focus {
            outline: 2px solid rgba(15, 118, 110, 0.16);
            border-color: #0f766e;
        }

        .badge-watching {
            background: #dff5f2;
            color: #0f766e;
        }

        .watch-state {
            color: #5b6b79;
        }

        .watch-state.watching {
            color: #0f766e;
            font-weight: 600;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 980px;
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

        .pagination-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .pagination-meta {
            color: #5b6b79;
            font-size: 0.95rem;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.6rem;
            min-height: 2.6rem;
            padding: 0.55rem 0.8rem;
            border: 1px solid #d9e0e7;
            border-radius: 0.8rem;
            background: #fff;
            color: #13202b;
            text-decoration: none;
            font-weight: 600;
        }

        .page-link:hover {
            background: #f8fafc;
        }

        .page-link.active {
            border-color: #0f766e;
            background: #dff5f2;
            color: #0f766e;
        }

        .page-link.disabled {
            color: #94a3b8;
            background: #f8fafc;
            pointer-events: none;
        }

        @media (max-width: 720px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .pinned-section-head {
                flex-direction: column;
            }

            .empty-state {
                padding: 2.5rem 1rem;
            }

            .pagination-wrap {
                align-items: stretch;
            }

            .announcement-head {
                flex-direction: column;
            }
        }

        @media (max-width: 1180px) and (min-width: 721px) {
            .filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterForm = document.querySelector('[data-ticket-filters]');

            if (! filterForm) {
                return;
            }

            const searchInput = filterForm.querySelector('[data-filter-search-input]');
            const searchHidden = filterForm.querySelector('[data-filter-search-hidden]');

            filterForm.querySelectorAll('[data-filter-auto-submit]').forEach((field) => {
                field.addEventListener('change', () => {
                    filterForm.requestSubmit();
                });
            });

            if (! searchInput || ! searchHidden) {
                return;
            }

            searchInput.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                searchHidden.value = searchInput.value.trim();
                filterForm.requestSubmit();
            });
        });
    </script>
@endpush

@section('content')
    @php
        $clearFilterQuery = static function (string $filterKey) use ($filters): array {
            $query = array_filter($filters, fn ($value) => $value !== '');
            $query[$filterKey] = '';

            return $query;
        };
    @endphp

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

        @if ($activeAnnouncements->isNotEmpty())
            <section class="announcement-stack" aria-label="Provozní oznámení">
                @foreach ($activeAnnouncements as $announcement)
                    <article class="announcement-card" data-type="{{ $announcement->type }}">
                        <div class="announcement-head">
                            <div>
                                <p class="announcement-title">{{ $announcement->title }}</p>
                            </div>

                            <span class="badge">
                                <span class="badge-dot"></span>
                                {{ \App\Models\Announcement::typeOptions()[$announcement->type] ?? ucfirst($announcement->type) }}
                            </span>
                        </div>

                        <p class="announcement-body">{{ $announcement->body }}</p>

                        @if ($announcement->starts_at || $announcement->ends_at)
                            <div class="announcement-meta">
                                Aktivní
                                @if ($announcement->starts_at)
                                    od {{ $announcement->starts_at->format('d.m.Y H:i') }}
                                @endif
                                @if ($announcement->ends_at)
                                    do {{ $announcement->ends_at->format('d.m.Y H:i') }}
                                @endif
                            </div>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif

        <section class="filter-card" aria-label="Filtrování ticketů">
            <form class="filter-form" method="get" action="{{ route('tickets.index') }}" data-ticket-filters>
                <input type="hidden" name="search" value="{{ $filters['search'] }}" data-filter-search-hidden>

                <div class="filter-grid">
                    <div class="filter-field">
                        <div class="filter-head">
                            <label class="filter-label" for="search_input">Hledání v subjectu</label>
                            @if ($filters['search'] !== '')
                                <a
                                    class="filter-clear"
                                    href="{{ route('tickets.index', $clearFilterQuery('search')) }}"
                                    aria-label="Zrušit hledání v subjectu"
                                    title="Zrušit hledání v subjectu"
                                >
                                    &times;
                                </a>
                            @endif
                        </div>
                        <input
                            class="filter-input"
                            id="search_input"
                            type="search"
                            value="{{ $filters['search'] }}"
                            placeholder="Např. tiskárna, VPN"
                            data-filter-search-input
                        >
                    </div>

                    <div class="filter-field">
                        <div class="filter-head">
                            <label class="filter-label" for="status">Status</label>
                            @if ($filters['status'] !== '')
                                <a
                                    class="filter-clear"
                                    href="{{ route('tickets.index', $clearFilterQuery('status')) }}"
                                    aria-label="Zrušit filtr statusu"
                                    title="Zrušit filtr statusu"
                                >
                                    &times;
                                </a>
                            @endif
                        </div>
                        <select class="filter-select" id="status" name="status" data-filter-auto-submit>
                            <option value="">Všechny</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}" @selected($filters['status'] === (string) $status->id)>{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-field">
                        <div class="filter-head">
                            <label class="filter-label" for="priority">Priority</label>
                            @if ($filters['priority'] !== '')
                                <a
                                    class="filter-clear"
                                    href="{{ route('tickets.index', $clearFilterQuery('priority')) }}"
                                    aria-label="Zrušit filtr priority"
                                    title="Zrušit filtr priority"
                                >
                                    &times;
                                </a>
                            @endif
                        </div>
                        <select class="filter-select" id="priority" name="priority" data-filter-auto-submit>
                            <option value="">Všechny</option>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->id }}" @selected($filters['priority'] === (string) $priority->id)>{{ $priority->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-field">
                        <div class="filter-head">
                            <label class="filter-label" for="category">Category</label>
                            @if ($filters['category'] !== '')
                                <a
                                    class="filter-clear"
                                    href="{{ route('tickets.index', $clearFilterQuery('category')) }}"
                                    aria-label="Zrušit filtr kategorie"
                                    title="Zrušit filtr kategorie"
                                >
                                    &times;
                                </a>
                            @endif
                        </div>
                        <select class="filter-select" id="category" name="category" data-filter-auto-submit>
                            <option value="">Všechny</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($filters['category'] === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-field">
                        <div class="filter-head">
                            <label class="filter-label" for="watched">Sledování</label>
                            @if ($filters['watched'] !== '')
                                <a
                                    class="filter-clear"
                                    href="{{ route('tickets.index', $clearFilterQuery('watched')) }}"
                                    aria-label="Zrušit filtr sledovaných ticketů"
                                    title="Zrušit filtr sledovaných ticketů"
                                >
                                    &times;
                                </a>
                            @endif
                        </div>
                        <select class="filter-select" id="watched" name="watched" data-filter-auto-submit>
                            <option value="">Všechny tickety</option>
                            <option value="1" @selected($filters['watched'] === '1')>Jen moje sledované</option>
                        </select>
                    </div>
                </div>
            </form>
        </section>

        @if ($pinningEnabled && $pinnedTickets->isNotEmpty())
            <section class="pinned-section" aria-label="Připnuté tickety">
                <div class="pinned-section-head">
                    <div>
                        <h3>Připnuté tickety</h3>
                        <p>Rychlý přehled ticketů označených pro zvýšenou pozornost.</p>
                    </div>

                    <span class="badge badge-pinned">
                        <span class="badge-dot"></span>
                        {{ $pinnedTickets->count() }}× připnuto
                    </span>
                </div>

                <div class="pinned-grid">
                    @foreach ($pinnedTickets as $ticket)
                        <article class="pinned-ticket">
                            <div class="pinned-ticket-head">
                                <div>
                                    <div class="pinned-ticket-number">{{ $ticket->ticket_number ?? 'Bez čísla' }}</div>
                                    <h3 class="pinned-ticket-subject">
                                        <a class="ticket-link" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->subject }}</a>
                                    </h3>
                                </div>

                                <span class="badge badge-pinned">Připnuto</span>
                            </div>

                            <div class="pinned-ticket-badges">
                                <span class="badge">
                                    <span class="badge-dot"></span>
                                    {{ $ticket->status?->name ?? '—' }}
                                </span>
                                <span class="badge">
                                    <span class="badge-dot"></span>
                                    {{ $ticket->priority?->name ?? '—' }}
                                </span>
                                @if ($ticket->is_watched_by_current_user)
                                    <span class="badge badge-watching">Sleduji</span>
                                @endif
                            </div>

                            <div class="pinned-ticket-meta">
                                <div>Requester: {{ $ticket->requester?->name ?? '—' }}</div>
                                <div>Assignee: {{ $ticket->assignee?->name ?? '—' }}</div>
                                <div class="{{ $ticket->is_watched_by_current_user ? 'watch-state watching' : 'watch-state' }}">
                                    {{ $ticket->is_watched_by_current_user ? 'Sledujete' : 'Nesledujete' }}
                                </div>
                                <div>Updated: {{ $ticket->updated_at?->format('d.m.Y H:i') ?? '—' }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($tickets->isEmpty())
            <section class="empty-state" aria-label="Prázdný seznam ticketů">
                @if ($hasActiveFilters)
                    <h3>Žádné tickety neodpovídají zadaným filtrům</h3>
                    <p>Upravte některý z aktivních filtrů nebo zrušte konkrétní omezení červeným křížkem.</p>
                @else
                    <h3>Zatím nejsou evidované žádné tickety</h3>
                    <p>Jakmile budou v systému vytvořené první požadavky, zobrazí se zde jejich seznam včetně stavu, priority a přiřazení.</p>
                @endif
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
                            <th scope="col">Comments</th>
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
                                    <span>
                                        {{ $ticket->visibility === 'restricted' ? 'Restricted' : 'Public' }}
                                        @if ($pinningEnabled && $ticket->is_pinned)
                                            · Připnuto
                                        @endif
                                        ·
                                        <span class="{{ $ticket->is_watched_by_current_user ? 'watch-state watching' : 'watch-state' }}">
                                            {{ $ticket->is_watched_by_current_user ? 'Sleduji' : 'Nesleduji' }}
                                        </span>
                                    </span>
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
                                <td>{{ $ticket->public_comments_count }}</td>
                                <td class="muted">{{ $ticket->updated_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($tickets->hasPages())
                <div class="pagination-wrap">
                    <div class="pagination-meta">
                        Zobrazeno {{ $tickets->firstItem() }}-{{ $tickets->lastItem() }} z {{ $tickets->total() }} ticketů
                    </div>

                    <nav class="pagination" aria-label="Stránkování ticketů">
                        @if ($tickets->onFirstPage())
                            <span class="page-link disabled">Předchozí</span>
                        @else
                            <a class="page-link" href="{{ $tickets->previousPageUrl() }}">Předchozí</a>
                        @endif

                        @foreach ($tickets->getUrlRange(max(1, $tickets->currentPage() - 2), min($tickets->lastPage(), $tickets->currentPage() + 2)) as $page => $url)
                            @if ($page === $tickets->currentPage())
                                <span class="page-link active">{{ $page }}</span>
                            @else
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($tickets->hasMorePages())
                            <a class="page-link" href="{{ $tickets->nextPageUrl() }}">Další</a>
                        @else
                            <span class="page-link disabled">Další</span>
                        @endif
                    </nav>
                </div>
            @endif
        @endif
    </div>
@endsection
