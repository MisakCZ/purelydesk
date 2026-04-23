@extends('layouts.admin')

@section('title', $ticket->ticket_number ? __('tickets.show.page_title', ['ticket_number' => $ticket->ticket_number]) : __('tickets.show.page_heading'))

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
    $hasHeroQuickActions = $canUpdateStatus
        || $canUpdatePriority
        || $canUpdateVisibility
        || $canUpdateAssignee
        || $canUpdateCategory
        || $canUpdatePin
        || $watcherActionEnabled;
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

@php
    $locale = app()->getLocale();
    $dateTimeFormat = __('tickets.formats.datetime');
@endphp

@push('styles')
    <style>
        .ticket-detail {
            display: grid;
            gap: 1.5rem;
        }

        .section-comments-public {
            order: 20;
        }

        .section-comments-internal {
            order: 21;
        }

        .section-content-meta {
            order: 30;
        }

        .section-history {
            order: 40;
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

        .metadata-actions {
            margin-top: 0.2rem;
            padding-top: 0.95rem;
            border-top: 1px solid #e2e8f0;
        }

        .metadata-action-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem;
            margin-top: 0.5rem;
        }

        .metadata-action-form {
            display: inline-flex;
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

        .badge-tone-slate {
            background: #eef2f6;
            color: #475569;
        }

        .badge-tone-blue {
            background: #e6efff;
            color: #1d4ed8;
        }

        .badge-tone-amber {
            background: #fff4db;
            color: #b45309;
        }

        .badge-tone-violet {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .badge-tone-cyan {
            background: #e6fffb;
            color: #0f766e;
        }

        .badge-tone-green {
            background: #e8f8ee;
            color: #15803d;
        }

        .badge-tone-neutral {
            background: #e5e7eb;
            color: #111827;
        }

        .badge-tone-red {
            background: #ffe7e7;
            color: #b91c1c;
        }

        .badge-button {
            border: 0;
            cursor: pointer;
            font: inherit;
        }

        .badge-button:hover {
            filter: brightness(0.98);
            box-shadow: inset 0 0 0 1px rgba(15, 118, 110, 0.18);
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

        .badge-switcher {
            position: relative;
        }

        .badge-switcher[open] {
            z-index: 20;
        }

        .badge-switcher[open] .badge-caret {
            transform: rotate(180deg);
        }

        .badge-menu-toggle {
            list-style: none;
        }

        .badge-menu-toggle::-webkit-details-marker {
            display: none;
        }

        .badge-caret {
            font-size: 0.72rem;
            line-height: 1;
            opacity: 0.7;
            transition: transform 0.15s ease;
        }

        .badge-menu {
            position: absolute;
            top: calc(100% + 0.45rem);
            left: 0;
            min-width: 15rem;
            max-width: min(22rem, calc(100vw - 2rem));
            padding: 0.4rem;
            border: 1px solid #d9e0e7;
            border-radius: 0.95rem;
            background: #fff;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
            display: grid;
            gap: 0.45rem;
        }

        .badge-menu-form,
        .badge-menu-options {
            display: grid;
            gap: 0.2rem;
        }

        .badge-menu-options {
            max-height: 17rem;
            overflow: auto;
        }

        .badge-menu-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            min-height: 2.45rem;
            padding: 0.5rem 0.75rem;
            border: 0;
            border-radius: 0.75rem;
            background: transparent;
            color: #334155;
            cursor: pointer;
            font: inherit;
            text-align: left;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .badge-menu-option:hover {
            background: #f8fafc;
            color: #13202b;
        }

        .badge-menu-option.active {
            background: #eef6f5;
            color: #13202b;
            font-weight: 700;
        }

        .badge-menu-option:disabled {
            cursor: default;
        }

        .badge-menu-check {
            color: #0f766e;
            font-size: 0.82rem;
            line-height: 1;
        }

        .badge-menu-note {
            padding: 0.55rem 0.75rem;
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .badge-menu-meta {
            display: grid;
            gap: 0.6rem;
            padding: 0.2rem 0.1rem 0.05rem;
            border-top: 1px solid #edf2f7;
        }

        .badge-menu-meta .watcher-list {
            margin-top: 0;
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
                    <h2>{{ __('tickets.show.page_heading') }}</h2>
                    <p>{{ __('tickets.show.page_subheading') }}</p>
                </div>

            <div class="page-head-actions">
                @if ($canEditTicket)
                    <a class="button button-secondary" href="{{ route('tickets.edit', $ticket) }}">{{ __('tickets.show.actions.edit') }}</a>
                @endif
                <a class="button button-secondary" href="{{ route('tickets.index') }}">{{ __('tickets.show.actions.back') }}</a>
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
                            <h3>{{ __('tickets.show.original_version.heading') }}</h3>
                            <p>{{ __('tickets.show.original_version.subheading') }}</p>
                        </div>

                        <button class="button button-secondary button-compact" type="button" data-editor-cancel="original-version-box">
                            {{ __('tickets.show.original_version.close') }}
                        </button>
                    </div>

                    @if ($originalSnapshotSource !== 'create')
                        <p class="original-version-note">
                            {{ __('tickets.show.original_version.legacy_note') }}
                        </p>
                    @endif

                    <section class="detail-grid">
                        <article class="detail-card full">
                            <span class="detail-label">{{ __('tickets.show.original_version.subject') }}</span>
                            <div class="detail-value">{{ $originalSnapshot['subject'] ?? __('tickets.common.not_available') }}</div>
                        </article>

                        <article class="detail-card full">
                            <span class="detail-label">{{ __('tickets.show.original_version.description') }}</span>
                            <div class="detail-value">
                                {!! nl2br(e($originalSnapshot['description'] ?? __('tickets.common.not_available'))) !!}
                            </div>
                        </article>
                    </section>
                </section>
            @endif

            <section class="ticket-hero">
                <div class="ticket-number">{{ $ticket->ticket_number ?? __('tickets.common.no_ticket_number') }}</div>
                <h3 class="ticket-subject">{{ $ticket->subject }}</h3>
                <div class="hero-meta-actions">
                    <div class="ticket-meta">
                        @if ($canUpdateStatus)
                            <details class="badge-switcher" @if ($statusErrors->any()) open @endif>
                                <summary
                                    class="badge badge-button badge-menu-toggle {{ $ticket->status?->badgeToneClass() ?? 'badge-tone-slate' }}"
                                    aria-expanded="{{ $statusErrors->any() ? 'true' : 'false' }}"
                                    title="{{ __('tickets.show.hero.status') }}"
                                >
                                    <span class="badge-dot"></span>
                                    {{ __('tickets.show.hero.status') }}: {{ $ticket->status?->translatedName() ?? __('tickets.common.not_available') }}
                                    <span class="badge-caret" aria-hidden="true">▾</span>
                                </summary>

                                <div class="badge-menu">
                                    @if ($statusErrors->any())
                                        <ul class="field-error-list">
                                            @foreach ($statusErrors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    <form class="badge-menu-form" method="post" action="{{ route('tickets.status.update', $ticket) }}">
                                        @csrf
                                        @method('patch')
                                        <input type="hidden" name="_locale" value="{{ $locale }}">

                                        <div class="badge-menu-options">
                                            @foreach ($statuses as $status)
                                                @php($isCurrentStatus = (string) $ticket->ticket_status_id === (string) $status->id)
                                                <button
                                                    class="badge-menu-option{{ $isCurrentStatus ? ' active' : '' }}"
                                                    type="submit"
                                                    name="status_id"
                                                    value="{{ $status->id }}"
                                                    @disabled($isCurrentStatus)
                                                >
                                                    <span>{{ $status->translatedName() }}</span>
                                                    <span class="badge-menu-check" @if (! $isCurrentStatus) hidden @endif>✓</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </form>
                                </div>
                            </details>
                        @else
                            <span class="badge {{ $ticket->status?->badgeToneClass() ?? 'badge-tone-slate' }}">
                                <span class="badge-dot"></span>
                                {{ __('tickets.show.hero.status') }}: {{ $ticket->status?->translatedName() ?? __('tickets.common.not_available') }}
                            </span>
                        @endif

                        @if ($canUpdatePriority)
                            <details class="badge-switcher" @if ($priorityErrors->any()) open @endif>
                                <summary
                                    class="badge badge-button badge-menu-toggle {{ $ticket->priority?->badgeToneClass() ?? 'badge-tone-slate' }}"
                                    aria-expanded="{{ $priorityErrors->any() ? 'true' : 'false' }}"
                                    title="{{ __('tickets.show.hero.priority') }}"
                                >
                                    <span class="badge-dot"></span>
                                    {{ __('tickets.show.hero.priority') }}: {{ $ticket->priority?->translatedName() ?? __('tickets.common.not_available') }}
                                    <span class="badge-caret" aria-hidden="true">▾</span>
                                </summary>

                                <div class="badge-menu">
                                    @if ($priorityErrors->any())
                                        <ul class="field-error-list">
                                            @foreach ($priorityErrors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    <form class="badge-menu-form" method="post" action="{{ route('tickets.priority.update', $ticket) }}">
                                        @csrf
                                        @method('patch')
                                        <input type="hidden" name="_locale" value="{{ $locale }}">

                                        <div class="badge-menu-options">
                                            @foreach ($priorities as $priority)
                                                @php($isCurrentPriority = (string) $ticket->ticket_priority_id === (string) $priority->id)
                                                <button
                                                    class="badge-menu-option{{ $isCurrentPriority ? ' active' : '' }}"
                                                    type="submit"
                                                    name="priority_id"
                                                    value="{{ $priority->id }}"
                                                    @disabled($isCurrentPriority)
                                                >
                                                    <span>{{ $priority->translatedName() }}</span>
                                                    <span class="badge-menu-check" @if (! $isCurrentPriority) hidden @endif>✓</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </form>
                                </div>
                            </details>
                        @else
                            <span class="badge {{ $ticket->priority?->badgeToneClass() ?? 'badge-tone-slate' }}">
                                <span class="badge-dot"></span>
                                {{ __('tickets.show.hero.priority') }}: {{ $ticket->priority?->translatedName() ?? __('tickets.common.not_available') }}
                            </span>
                        @endif

                        @if ($canUpdateVisibility)
                            <details class="badge-switcher" @if ($visibilityErrors->any()) open @endif>
                                <summary
                                    class="badge badge-button badge-menu-toggle"
                                    aria-expanded="{{ $visibilityErrors->any() ? 'true' : 'false' }}"
                                    title="{{ __('tickets.show.hero.visibility') }}"
                                >
                                    <span class="badge-dot"></span>
                                    {{ __('tickets.show.hero.visibility') }}: {{ $ticket->translatedVisibilityLabel() }}
                                    <span class="badge-caret" aria-hidden="true">▾</span>
                                </summary>

                                <div class="badge-menu">
                                    @if ($visibilityErrors->any())
                                        <ul class="field-error-list">
                                            @foreach ($visibilityErrors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    <form class="badge-menu-form" method="post" action="{{ route('tickets.visibility.update', $ticket) }}">
                                        @csrf
                                        @method('patch')
                                        <input type="hidden" name="_locale" value="{{ $locale }}">

                                        <div class="badge-menu-options">
                                            @foreach ($visibilityOptions as $value => $label)
                                                @php($isCurrentVisibility = (string) $ticket->normalizedVisibility() === (string) $value)
                                                <button
                                                    class="badge-menu-option{{ $isCurrentVisibility ? ' active' : '' }}"
                                                    type="submit"
                                                    name="visibility"
                                                    value="{{ $value }}"
                                                    @disabled($isCurrentVisibility)
                                                >
                                                    <span>{{ $label }}</span>
                                                    <span class="badge-menu-check" @if (! $isCurrentVisibility) hidden @endif>✓</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </form>
                                </div>
                            </details>
                        @else
                            <span class="badge">
                                <span class="badge-dot"></span>
                                {{ __('tickets.show.hero.visibility') }}: {{ $ticket->translatedVisibilityLabel() }}
                            </span>
                        @endif

                        @if ($canUpdateAssignee)
                            <details class="badge-switcher" @if ($assigneeErrors->any()) open @endif>
                                <summary
                                    class="badge badge-button badge-menu-toggle"
                                    aria-expanded="{{ $assigneeErrors->any() ? 'true' : 'false' }}"
                                    title="{{ __('tickets.show.hero.assignee') }}"
                                >
                                    <span class="badge-dot"></span>
                                    {{ __('tickets.show.hero.assignee') }}: {{ $ticket->assignee?->name ?? __('tickets.common.unassigned') }}
                                    <span class="badge-caret" aria-hidden="true">▾</span>
                                </summary>

                                <div class="badge-menu">
                                    @if ($assigneeErrors->any())
                                        <ul class="field-error-list">
                                            @foreach ($assigneeErrors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    <form class="badge-menu-form" method="post" action="{{ route('tickets.assignee.update', $ticket) }}">
                                        @csrf
                                        @method('patch')
                                        <input type="hidden" name="_locale" value="{{ $locale }}">

                                        <div class="badge-menu-options">
                                            @php($isUnassigned = $ticket->assignee_id === null)
                                            <button
                                                class="badge-menu-option{{ $isUnassigned ? ' active' : '' }}"
                                                type="submit"
                                                name="assignee_id"
                                                value=""
                                                @disabled($isUnassigned)
                                            >
                                                <span>{{ __('tickets.common.unassigned') }}</span>
                                                <span class="badge-menu-check" @if (! $isUnassigned) hidden @endif>✓</span>
                                            </button>

                                            @foreach ($assignees as $assignee)
                                                @php($isCurrentAssignee = (string) $ticket->assignee_id === (string) $assignee->id)
                                                <button
                                                    class="badge-menu-option{{ $isCurrentAssignee ? ' active' : '' }}"
                                                    type="submit"
                                                    name="assignee_id"
                                                    value="{{ $assignee->id }}"
                                                    @disabled($isCurrentAssignee)
                                                >
                                                    <span>{{ $assignee->name }}</span>
                                                    <span class="badge-menu-check" @if (! $isCurrentAssignee) hidden @endif>✓</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </form>
                                </div>
                            </details>
                        @else
                            <span class="badge">
                                <span class="badge-dot"></span>
                                {{ __('tickets.show.hero.assignee') }}: {{ $ticket->assignee?->name ?? __('tickets.common.unassigned') }}
                            </span>
                        @endif

                        @if ($canUpdateCategory)
                            <details class="badge-switcher" @if ($categoryErrors->any()) open @endif>
                                <summary
                                    class="badge badge-button badge-menu-toggle"
                                    aria-expanded="{{ $categoryErrors->any() ? 'true' : 'false' }}"
                                    title="{{ __('tickets.show.hero.category') }}"
                                >
                                    <span class="badge-dot"></span>
                                    {{ __('tickets.show.hero.category') }}: {{ $ticket->category?->translatedName() ?? __('tickets.common.not_available') }}
                                    <span class="badge-caret" aria-hidden="true">▾</span>
                                </summary>

                                <div class="badge-menu">
                                    @if ($categoryErrors->any())
                                        <ul class="field-error-list">
                                            @foreach ($categoryErrors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    <form class="badge-menu-form" method="post" action="{{ route('tickets.category.update', $ticket) }}">
                                        @csrf
                                        @method('patch')
                                        <input type="hidden" name="_locale" value="{{ $locale }}">

                                        <div class="badge-menu-options">
                                            @foreach ($categories as $category)
                                                @php($isCurrentCategory = (string) $ticket->ticket_category_id === (string) $category->id)
                                                <button
                                                    class="badge-menu-option{{ $isCurrentCategory ? ' active' : '' }}"
                                                    type="submit"
                                                    name="category_id"
                                                    value="{{ $category->id }}"
                                                    @disabled($isCurrentCategory)
                                                >
                                                    <span>{{ $category->translatedName() }}</span>
                                                    <span class="badge-menu-check" @if (! $isCurrentCategory) hidden @endif>✓</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </form>
                                </div>
                            </details>
                        @else
                            <span class="badge">
                                <span class="badge-dot"></span>
                                {{ __('tickets.show.hero.category') }}: {{ $ticket->category?->translatedName() ?? __('tickets.common.not_available') }}
                            </span>
                        @endif

                        @if ($canUpdatePin)
                            <details class="badge-switcher" @if ($pinErrors->any()) open @endif>
                                <summary
                                    class="badge badge-button badge-menu-toggle{{ $pinningEnabled && $ticket->is_pinned ? ' badge-watching' : '' }}"
                                    aria-expanded="{{ $pinErrors->any() ? 'true' : 'false' }}"
                                    title="{{ __('tickets.show.hero.pinning') }}"
                                >
                                    <span class="badge-dot"></span>
                                    {{ __('tickets.show.hero.pinning') }}:
                                    @if (! $pinningEnabled)
                                        {{ __('tickets.common.unavailable') }}
                                    @else
                                        {{ $ticket->is_pinned ? __('tickets.common.yes') : __('tickets.common.no') }}
                                    @endif
                                    <span class="badge-caret" aria-hidden="true">▾</span>
                                </summary>

                                <div class="badge-menu">
                                    @if ($pinningEnabled)
                                        @if ($pinErrors->any())
                                            <ul class="field-error-list">
                                                @foreach ($pinErrors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        <form class="badge-menu-form" method="post" action="{{ route('tickets.pin.update', $ticket) }}">
                                            @csrf
                                            @method('patch')
                                            <input type="hidden" name="_locale" value="{{ $locale }}">

                                            <button class="badge-menu-option" type="submit" name="pinned" value="{{ $ticket->is_pinned ? '0' : '1' }}">
                                                <span>{{ $ticket->is_pinned ? __('tickets.show.forms.unpin_ticket') : __('tickets.show.forms.pin_ticket') }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <div class="badge-menu-note">{{ __('tickets.show.forms.pinning_unavailable') }}</div>
                                    @endif
                                </div>
                            </details>
                        @else
                            <span class="badge{{ $pinningEnabled && $ticket->is_pinned ? ' badge-watching' : '' }}">
                                <span class="badge-dot"></span>
                                {{ __('tickets.show.hero.pinning') }}:
                                @if (! $pinningEnabled)
                                    {{ __('tickets.common.unavailable') }}
                                @else
                                    {{ $ticket->is_pinned ? __('tickets.common.yes') : __('tickets.common.no') }}
                                @endif
                            </span>
                        @endif

                        @if ($watcherActionEnabled)
                            <details class="badge-switcher" @if ($watcherErrors->any()) open @endif>
                                <summary
                                    class="badge badge-button badge-menu-toggle{{ $isWatchingTicket ? ' badge-watching' : '' }}"
                                    aria-expanded="{{ $watcherErrors->any() ? 'true' : 'false' }}"
                                    title="{{ __('tickets.show.hero.edit_title.watching') }}"
                                >
                                    <span class="badge-dot"></span>
                                    {{ __('tickets.show.hero.watching') }}: {{ $isWatchingTicket ? __('tickets.common.yes') : __('tickets.common.no') }}
                                    <span class="badge-caret" aria-hidden="true">▾</span>
                                </summary>

                                <div class="badge-menu">
                                    @if ($watcherErrors->any())
                                        <ul class="field-error-list">
                                            @foreach ($watcherErrors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if ($isWatchingTicket)
                                        <form class="badge-menu-form" method="post" action="{{ route('tickets.watchers.destroy', $ticket) }}">
                                            @csrf
                                            @method('delete')
                                            <input type="hidden" name="_locale" value="{{ $locale }}">

                                            <button class="badge-menu-option" type="submit">
                                                <span>{{ __('tickets.show.forms.watch_stop') }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <form class="badge-menu-form" method="post" action="{{ route('tickets.watchers.store', $ticket) }}">
                                            @csrf
                                            <input type="hidden" name="_locale" value="{{ $locale }}">

                                            <button class="badge-menu-option" type="submit">
                                                <span>{{ __('tickets.show.forms.watch_start') }}</span>
                                            </button>
                                        </form>
                                    @endif

                                    <div class="badge-menu-meta">
                                        <div class="badge-menu-note">{{ __('tickets.show.hero.watchers_count', ['count' => $ticket->watchers->count()]) }}</div>
                                        @if ($ticket->watchers->isEmpty())
                                            <div class="watcher-empty">{{ __('tickets.show.hero.watchers_empty') }}</div>
                                        @else
                                            <div class="watcher-list" aria-label="{{ __('tickets.show.hero.watchers_label') }}">
                                                @foreach ($ticket->watchers as $watcher)
                                                    <span class="watcher-pill">{{ $watcher->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </details>
                        @else
                            <span class="badge{{ $isWatchingTicket ? ' badge-watching' : '' }}">
                                <span class="badge-dot"></span>
                                {{ __('tickets.show.hero.watching') }}: {{ $isWatchingTicket ? __('tickets.common.yes') : __('tickets.common.no') }}
                            </span>
                        @endif
                    </div>

                    @if ($hasHeroQuickActions)
                        <p class="hero-meta-help">{{ __('tickets.show.hero.help') }}</p>
                    @endif

                    @if ($heroAdminHasErrors)
                        <div class="hero-admin-errors">{{ __('tickets.show.hero.errors') }}</div>
                    @endif
                </div>
            </section>

            <section class="section-panel section-content-meta">
                <div class="section-panel-head">
                    <div>
                        <h2>{{ __('tickets.show.content.heading') }}</h2>
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
                                    {{ __('tickets.show.original_version.edited') }}
                                </button>
                            @endif
                        </div>

                        <div class="detail-value content-description">
                            {!! nl2br(e($ticket->description ?? __('tickets.common.not_available'))) !!}
                        </div>
                    </article>

                    <aside class="metadata-list" aria-label="{{ __('tickets.show.metadata.label') }}">
                        <div class="metadata-item">
                            <span class="metadata-label">{{ __('tickets.show.metadata.requester') }}</span>
                            <div class="metadata-value">{{ $ticket->requester?->name ?? __('tickets.common.not_available') }}</div>
                        </div>

                        <div class="metadata-item">
                            <span class="metadata-label">{{ __('tickets.show.metadata.created_at') }}</span>
                            <div class="metadata-value">{{ $ticket->created_at?->locale($locale)->translatedFormat($dateTimeFormat) ?? __('tickets.common.not_available') }}</div>
                        </div>

                        <div class="metadata-item">
                            <span class="metadata-label">{{ __('tickets.show.metadata.updated_at') }}</span>
                            <div class="metadata-value">{{ $ticket->updated_at?->locale($locale)->translatedFormat($dateTimeFormat) ?? __('tickets.common.not_available') }}</div>
                        </div>

                        @if ($canConfirmResolution || $canReportProblemPersists)
                            <div class="metadata-item metadata-actions">
                                <span class="metadata-label">{{ __('tickets.show.metadata.resolution') }}</span>
                                <div class="metadata-value">{{ __('tickets.show.metadata.resolution_help') }}</div>
                                <div class="metadata-action-list">
                                    @if ($canConfirmResolution)
                                        <form class="metadata-action-form" method="post" action="{{ route('tickets.confirm-resolution', $ticket) }}">
                                            @csrf
                                            @method('patch')
                                            <button class="button button-primary button-compact" type="submit">{{ __('tickets.show.metadata.confirm_resolved') }}</button>
                                        </form>
                                    @endif

                                    @if ($canReportProblemPersists)
                                        <form class="metadata-action-form" method="post" action="{{ route('tickets.report-problem-persists', $ticket) }}">
                                            @csrf
                                            @method('patch')
                                            <button class="button button-secondary button-compact" type="submit">{{ __('tickets.show.metadata.problem_persists') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </aside>
                </div>
            </section>

            <section class="comment-section section-comments-public">
                <div class="page-head" id="comments">
                    <div class="page-head-bar">
                        <div>
                            <h2>Komentáře</h2>
                            <p>Veřejná komunikace k tomuto ticketu v chronologickém pořadí.</p>
                        </div>

                        @if ($canCommentPublic)
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
                        @endif
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

                                @if ($commentThreadingEnabled && $canCommentPublic)
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

                @if ($canCommentPublic)
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
                @endif
            </section>

            @if ($canViewInternalNotes)
                <section class="comment-section section-comments-internal">
                    <div class="page-head" id="internal-notes">
                        <div class="page-head-bar">
                            <div>
                                <h2>Interní poznámky</h2>
                                <p>Oddělený interní blok pro solvery a administraci.</p>
                            </div>

                            @if ($canCreateInternalNote)
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
                            @endif
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

                    @if ($canCreateInternalNote)
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
                                <p>Poznámka zůstane oddělená od veřejné komunikace ticketu.</p>
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
                    @endif
                </section>
            @endif

            <section class="section-panel section-history">
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
            const syncBadgeSwitcher = (switcher) => {
                const summary = switcher.querySelector('summary');

                if (! summary) {
                    return;
                }

                summary.setAttribute('aria-expanded', switcher.open ? 'true' : 'false');
            };

            document.querySelectorAll('.badge-switcher').forEach((switcher) => {
                syncBadgeSwitcher(switcher);

                switcher.addEventListener('toggle', () => {
                    syncBadgeSwitcher(switcher);

                    if (! switcher.open) {
                        return;
                    }

                    document.querySelectorAll('.badge-switcher[open]').forEach((otherSwitcher) => {
                        if (otherSwitcher !== switcher) {
                            otherSwitcher.open = false;
                            syncBadgeSwitcher(otherSwitcher);
                        }
                    });
                });
            });

            document.addEventListener('click', (event) => {
                document.querySelectorAll('.badge-switcher[open]').forEach((switcher) => {
                    if (! switcher.contains(event.target)) {
                        switcher.open = false;
                        syncBadgeSwitcher(switcher);
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                document.querySelectorAll('.badge-switcher[open]').forEach((switcher) => {
                    switcher.open = false;
                    syncBadgeSwitcher(switcher);
                });
            });

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
