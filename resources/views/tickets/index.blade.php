@extends('layouts.admin')

@section('title', __('tickets.index.page_title'))

@push('styles')
    <style>
        .filter-card {
            margin-bottom: 1rem;
            padding: 0.95rem;
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
            gap: 0.75rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: minmax(12rem, 1.2fr) repeat(4, minmax(9rem, 0.88fr));
            gap: 0.75rem;
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
            min-height: 2.75rem;
            padding: 0.72rem 0.9rem;
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

        .table-wrap {
            overflow: visible;
        }

        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .ticket-table th,
        .ticket-table td {
            padding: 0.8rem 0.85rem;
            text-align: left;
            border-bottom: 1px solid #e5ebf1;
            vertical-align: top;
        }

        .ticket-table th {
            color: #5b6b79;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            background: #f8fafc;
        }

        .ticket-table tbody tr:hover {
            background: #f8fbff;
        }

        .ticket-row-updated {
            background: #f0fdf4 !important;
        }

        .ticket-col-number {
            width: 7.4rem;
        }

        .ticket-col-status,
        .ticket-col-priority {
            width: 11rem;
        }

        .ticket-col-updated {
            width: 7.35rem;
            white-space: nowrap;
        }

        .ticket-number {
            color: #64748b;
            font-size: 0.82rem;
            font-weight: 500;
            line-height: 1.35;
            white-space: nowrap;
        }

        .ticket-updated-value {
            color: #64748b;
            font-size: 0.82rem;
            font-weight: 500;
            line-height: 1.35;
            white-space: nowrap;
        }

        .ticket-number .ticket-link {
            display: inline-flex;
            align-items: center;
            min-height: 2rem;
        }

        .subject {
            min-width: 0;
            padding-left: 1.45rem !important;
        }

        .subject-title {
            display: block;
            color: #13202b;
            line-height: 1.35;
            font-size: 1rem;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .subject-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem 0.75rem;
            margin-top: 0.45rem;
            font-size: 0.84rem;
            color: #5b6b79;
        }

        .subject-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            line-height: 1.4;
        }

        .subject-meta-pill {
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            background: #f5f0df;
            color: #8a5a00;
            font-weight: 700;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-size: 0.84rem;
            font-weight: 600;
            background: #eef2f6;
            color: #334155;
            white-space: nowrap;
            max-width: 100%;
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

        .badge-dot {
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 999px;
            background: currentColor;
            opacity: 0.7;
            flex: 0 0 auto;
        }

        .badge-button {
            border: 0;
            cursor: pointer;
        }

        .list-inline-trigger {
            width: 100%;
            justify-content: flex-start;
            text-align: left;
            transition: box-shadow 0.15s ease, filter 0.15s ease, transform 0.15s ease;
        }

        .list-inline-trigger:hover {
            filter: brightness(0.98);
            box-shadow: inset 0 0 0 1px rgba(15, 118, 110, 0.18);
            transform: translateY(-1px);
        }

        .list-inline-trigger[aria-expanded="true"] {
            filter: brightness(0.97);
            box-shadow: inset 0 0 0 1px rgba(15, 118, 110, 0.18);
        }

        .badge-label {
            display: inline-block;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ticket-link {
            color: inherit;
            text-decoration: none;
        }

        .ticket-link:hover {
            color: #0f766e;
            text-decoration: underline;
        }

        .muted {
            color: #5b6b79;
        }

        .alert {
            margin-bottom: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 0.9rem;
            border: 1px solid #b7e4dd;
            background: #ecfdf8;
            color: #0f513f;
        }

        .inline-feedback {
            margin-top: -0.2rem;
        }

        .list-inline-editor {
            position: fixed;
            z-index: 40;
            width: min(21rem, calc(100vw - 1.5rem));
            padding: 1rem;
            border: 1px solid #d9e0e7;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
        }

        .list-inline-editor.is-saving {
            opacity: 0.8;
        }

        .list-inline-editor-form {
            display: grid;
            gap: 0.8rem;
        }

        .list-inline-editor-head {
            display: grid;
            gap: 0.25rem;
        }

        .list-inline-editor-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #13202b;
        }

        .list-inline-editor-ticket {
            color: #5b6b79;
            font-size: 0.88rem;
            line-height: 1.45;
        }

        .list-inline-editor-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.65rem;
        }

        .button-compact {
            min-height: 2.35rem;
            padding: 0.5rem 0.8rem;
            border-radius: 0.8rem;
            font-size: 0.9rem;
        }

        .field-error {
            color: #b42318;
            font-size: 0.9rem;
            line-height: 1.5;
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

        @media (max-width: 1180px) and (min-width: 721px) {
            .filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .ticket-col-status,
            .ticket-col-priority {
                width: 10rem;
            }
        }

        @media (max-width: 860px) {
            .table-wrap {
                overflow-x: auto;
            }

            .ticket-table {
                min-width: 760px;
            }
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
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const translations = {{ \Illuminate\Support\Js::from([
                'inlineStatusTitle' => __('tickets.index.inline.status_title'),
                'inlinePriorityTitle' => __('tickets.index.inline.priority_title'),
                'saveStatus' => __('tickets.index.inline.save_status'),
                'savePriority' => __('tickets.index.inline.save_priority'),
                'save' => __('tickets.index.inline.save'),
                'cancel' => __('tickets.index.inline.cancel'),
                'genericError' => __('tickets.index.inline.generic_error'),
                'saved' => __('tickets.index.inline.saved'),
                'noNumber' => __('tickets.common.no_number'),
            ]) }};
            const filterForm = document.querySelector('[data-ticket-filters]');

            if (filterForm) {
                const searchInput = filterForm.querySelector('[data-filter-search-input]');
                const searchHidden = filterForm.querySelector('[data-filter-search-hidden]');

                filterForm.querySelectorAll('[data-filter-auto-submit]').forEach((field) => {
                    field.addEventListener('change', () => {
                        filterForm.requestSubmit();
                    });
                });

                if (searchInput && searchHidden) {
                    searchInput.addEventListener('keydown', (event) => {
                        if (event.key !== 'Enter') {
                            return;
                        }

                        event.preventDefault();
                        searchHidden.value = searchInput.value.trim();
                        filterForm.requestSubmit();
                    });
                }
            }

            const editor = document.getElementById('ticket-inline-editor');
            const feedback = document.getElementById('ticket-inline-feedback');

            if (! editor) {
                return;
            }

            const form = editor.querySelector('[data-inline-form]');
            const select = editor.querySelector('[data-inline-select]');
            const title = editor.querySelector('[data-inline-title]');
            const ticketLabel = editor.querySelector('[data-inline-ticket]');
            const errorBox = editor.querySelector('[data-inline-error]');
            const submitButton = editor.querySelector('[data-inline-submit]');
            const cancelButton = editor.querySelector('[data-inline-cancel]');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const optionNodes = {
                status: document.getElementById('ticket-inline-options-status'),
                priority: document.getElementById('ticket-inline-options-priority'),
            };
            const options = Object.fromEntries(Object.entries(optionNodes).map(([key, node]) => [
                key,
                node ? JSON.parse(node.textContent) : [],
            ]));

            let activeTrigger = null;
            let feedbackTimeout = null;

            const hideFeedback = () => {
                if (! feedback) {
                    return;
                }

                feedback.hidden = true;
                feedback.textContent = '';
            };

            const showFeedback = (message) => {
                if (! feedback || ! message) {
                    return;
                }

                feedback.textContent = message;
                feedback.hidden = false;

                if (feedbackTimeout) {
                    window.clearTimeout(feedbackTimeout);
                }

                feedbackTimeout = window.setTimeout(() => {
                    hideFeedback();
                }, 2600);
            };

            const setSavingState = (isSaving) => {
                editor.classList.toggle('is-saving', isSaving);
                submitButton.disabled = isSaving;
                cancelButton.disabled = isSaving;
                select.disabled = isSaving;
            };

            const setTriggerExpanded = (trigger, expanded) => {
                if (! trigger) {
                    return;
                }

                trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            };

            const closeEditor = () => {
                setTriggerExpanded(activeTrigger, false);
                activeTrigger = null;
                form.dataset.mode = '';
                form.dataset.submitUrl = '';
                form.dataset.ticketId = '';
                select.name = '';
                select.innerHTML = '';
                errorBox.hidden = true;
                errorBox.textContent = '';
                editor.hidden = true;
            };

            const positionEditor = (trigger) => {
                if (! trigger) {
                    return;
                }

                const rect = trigger.getBoundingClientRect();
                const width = editor.offsetWidth;
                const height = editor.offsetHeight;
                const padding = 12;
                let top = rect.bottom + 8;
                let left = rect.left;

                if (left + width > window.innerWidth - padding) {
                    left = window.innerWidth - width - padding;
                }

                if (left < padding) {
                    left = padding;
                }

                if (top + height > window.innerHeight - padding) {
                    top = Math.max(padding, rect.top - height - 8);
                }

                editor.style.top = `${top}px`;
                editor.style.left = `${left}px`;
            };

            const populateOptions = (mode, currentValue) => {
                select.innerHTML = '';

                (options[mode] ?? []).forEach((option) => {
                    const optionNode = document.createElement('option');
                    optionNode.value = option.id;
                    optionNode.textContent = option.name;

                    if (String(option.id) === String(currentValue)) {
                        optionNode.selected = true;
                    }

                    select.appendChild(optionNode);
                });
            };

            const updateTicketDisplays = (ticketId, ticketData) => {
                const replaceBadgeToneClass = (node, badgeClass) => {
                    if (! node || ! badgeClass) {
                        return;
                    }

                    Array.from(node.classList)
                        .filter((className) => className.startsWith('badge-tone-'))
                        .forEach((className) => node.classList.remove(className));

                    node.classList.add(badgeClass);
                };

                if (ticketData.status) {
                    document.querySelectorAll(`[data-ticket-id="${ticketId}"][data-ticket-field="status"]`).forEach((node) => {
                        replaceBadgeToneClass(node, ticketData.status.badge_class);
                    });

                    document.querySelectorAll(`[data-ticket-id="${ticketId}"][data-ticket-field="status"] [data-ticket-field-value]`).forEach((node) => {
                        node.textContent = ticketData.status.name;
                    });
                }

                if (ticketData.priority) {
                    document.querySelectorAll(`[data-ticket-id="${ticketId}"][data-ticket-field="priority"]`).forEach((node) => {
                        replaceBadgeToneClass(node, ticketData.priority.badge_class);
                    });

                    document.querySelectorAll(`[data-ticket-id="${ticketId}"][data-ticket-field="priority"] [data-ticket-field-value]`).forEach((node) => {
                        node.textContent = ticketData.priority.name;
                    });
                }

                if (ticketData.updated_at_display) {
                    document.querySelectorAll(`[data-ticket-id="${ticketId}"][data-ticket-updated-at]`).forEach((node) => {
                        node.textContent = ticketData.updated_at_display;
                    });
                }
            };

            const openEditor = (trigger) => {
                const mode = trigger.dataset.inlineMode;

                if (! mode || ! options[mode]) {
                    return;
                }

                if (activeTrigger === trigger && ! editor.hidden) {
                    closeEditor();

                    return;
                }

                setTriggerExpanded(activeTrigger, false);
                activeTrigger = trigger;
                setTriggerExpanded(activeTrigger, true);

                form.dataset.mode = mode;
                form.dataset.submitUrl = trigger.dataset.submitUrl;
                form.dataset.ticketId = trigger.dataset.ticketId;
                title.textContent = mode === 'status' ? translations.inlineStatusTitle : translations.inlinePriorityTitle;
                ticketLabel.textContent = `${trigger.dataset.ticketNumber || translations.noNumber} · ${trigger.dataset.ticketSubject || ''}`;
                select.name = mode === 'status' ? 'status_id' : 'priority_id';
                submitButton.textContent = mode === 'status' ? translations.saveStatus : translations.savePriority;
                populateOptions(mode, trigger.dataset.currentValue);
                errorBox.hidden = true;
                errorBox.textContent = '';
                editor.hidden = false;

                requestAnimationFrame(() => {
                    positionEditor(trigger);
                    select.focus();
                });
            };

            document.querySelectorAll('[data-ticket-inline-trigger]').forEach((trigger) => {
                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    openEditor(trigger);
                });
            });

            cancelButton.addEventListener('click', () => {
                closeEditor();
            });

            document.addEventListener('click', (event) => {
                if (editor.hidden) {
                    return;
                }

                if (editor.contains(event.target) || (activeTrigger && activeTrigger.contains(event.target))) {
                    return;
                }

                closeEditor();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && ! editor.hidden) {
                    closeEditor();
                }
            });

            window.addEventListener('resize', () => {
                if (! editor.hidden) {
                    positionEditor(activeTrigger);
                }
            });

            window.addEventListener('scroll', () => {
                if (! editor.hidden) {
                    positionEditor(activeTrigger);
                }
            }, true);

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (! activeTrigger || ! form.dataset.submitUrl || ! select.name) {
                    return;
                }

                const payload = new FormData();
                payload.append('_method', 'PATCH');
                payload.append(select.name, select.value);

                setSavingState(true);
                errorBox.hidden = true;
                errorBox.textContent = '';

                try {
                    const response = await fetch(form.dataset.submitUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                        },
                        body: payload,
                        credentials: 'same-origin',
                    });

                    const responseData = await response.json().catch(() => null);

                    if (! response.ok) {
                        const errorMessage = Object.values(responseData?.errors ?? {}).flat().join(' ') || responseData?.message || translations.genericError;
                        errorBox.textContent = errorMessage;
                        errorBox.hidden = false;

                        return;
                    }

                    updateTicketDisplays(form.dataset.ticketId, responseData.ticket ?? {});
                    activeTrigger.dataset.currentValue = String(select.value);
                    showFeedback(responseData.message || translations.saved);

                    const row = document.querySelector(`tr[data-ticket-row="${form.dataset.ticketId}"]`);

                    if (row) {
                        row.classList.add('ticket-row-updated');

                        window.setTimeout(() => {
                            row.classList.remove('ticket-row-updated');
                        }, 1600);
                    }

                    closeEditor();
                } catch (error) {
                    errorBox.textContent = translations.genericError;
                    errorBox.hidden = false;
                } finally {
                    setSavingState(false);
                }
            });
        });
    </script>
@endpush

@section('content')
    @php
        $locale = app()->getLocale();
        $dateTimeFormat = __('tickets.formats.datetime');
        $listUpdatedAtFormat = __('tickets.formats.list_updated_at');
        $clearFilterQuery = static function (string $filterKey) use ($filters): array {
            $query = array_filter($filters, fn ($value) => $value !== '');
            $query[$filterKey] = '';

            return $query;
        };
        $inlineStatusOptions = $statuses->map(fn ($status) => [
            'id' => $status->id,
            'name' => $status->translatedName(),
        ])->values();
        $inlinePriorityOptions = $priorities->map(fn ($priority) => [
            'id' => $priority->id,
            'name' => $priority->translatedName(),
        ])->values();
    @endphp

    <div class="page-head">
        <div class="page-head-bar">
            <div>
                <h2>{{ __('tickets.index.heading') }}</h2>
                <p>{{ __('tickets.index.subheading') }}</p>
            </div>

            @if ($canCreateTickets)
                <a class="button button-primary" href="{{ route('tickets.create') }}">{{ __('tickets.index.actions.create') }}</a>
            @endif
        </div>
    </div>

    <div class="page-body">
        @if (session('status'))
            <div class="alert" role="status">{{ session('status') }}</div>
        @endif

        <div id="ticket-inline-feedback" class="alert inline-feedback" role="status" aria-live="polite" hidden></div>

        @if ($activeAnnouncements->isNotEmpty())
            <section class="announcement-stack" aria-label="{{ __('tickets.index.announcements.label') }}">
                @foreach ($activeAnnouncements as $announcement)
                    <article class="announcement-card" data-type="{{ $announcement->type }}">
                        <div class="announcement-head">
                            <div>
                                <p class="announcement-title">{{ $announcement->title }}</p>
                            </div>

                            <span class="badge">
                                <span class="badge-dot"></span>
                                {{ \App\Models\Announcement::translatedTypeLabel($announcement->type) }}
                            </span>
                        </div>

                        <p class="announcement-body">{{ $announcement->body }}</p>

                        @if ($announcement->starts_at || $announcement->ends_at)
                            <div class="announcement-meta">
                                {{ __('tickets.index.announcements.active') }}
                                @if ($announcement->starts_at)
                                    {{ __('tickets.index.announcements.from') }} {{ $announcement->starts_at->locale($locale)->translatedFormat($dateTimeFormat) }}
                                @endif
                                @if ($announcement->ends_at)
                                    {{ __('tickets.index.announcements.to') }} {{ $announcement->ends_at->locale($locale)->translatedFormat($dateTimeFormat) }}
                                @endif
                            </div>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif

        <section class="filter-card" aria-label="{{ __('tickets.index.filters.section') }}">
            <form class="filter-form" method="get" action="{{ route('tickets.index') }}" data-ticket-filters>
                <input type="hidden" name="search" value="{{ $filters['search'] }}" data-filter-search-hidden>

                <div class="filter-grid">
                    <div class="filter-field">
                        <div class="filter-head">
                            <label class="filter-label" for="search_input">{{ __('tickets.index.filters.search') }}</label>
                            @if ($filters['search'] !== '')
                                <a
                                    class="filter-clear"
                                    href="{{ route('tickets.index', $clearFilterQuery('search')) }}"
                                    aria-label="{{ __('tickets.index.filters.clear_search') }}"
                                    title="{{ __('tickets.index.filters.clear_search') }}"
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
                            placeholder="{{ __('tickets.index.filters.search_placeholder') }}"
                            data-filter-search-input
                        >
                    </div>

                    <div class="filter-field">
                        <div class="filter-head">
                            <label class="filter-label" for="status">{{ __('tickets.index.filters.status') }}</label>
                            @if ($filters['status'] !== '')
                                <a
                                    class="filter-clear"
                                    href="{{ route('tickets.index', $clearFilterQuery('status')) }}"
                                    aria-label="{{ __('tickets.index.filters.clear_status') }}"
                                    title="{{ __('tickets.index.filters.clear_status') }}"
                                >
                                    &times;
                                </a>
                            @endif
                        </div>
                        <select class="filter-select" id="status" name="status" data-filter-auto-submit>
                            <option value="">{{ __('tickets.index.filters.all') }}</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}" @selected($filters['status'] === (string) $status->id)>{{ $status->translatedName() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-field">
                        <div class="filter-head">
                            <label class="filter-label" for="priority">{{ __('tickets.index.filters.priority') }}</label>
                            @if ($filters['priority'] !== '')
                                <a
                                    class="filter-clear"
                                    href="{{ route('tickets.index', $clearFilterQuery('priority')) }}"
                                    aria-label="{{ __('tickets.index.filters.clear_priority') }}"
                                    title="{{ __('tickets.index.filters.clear_priority') }}"
                                >
                                    &times;
                                </a>
                            @endif
                        </div>
                        <select class="filter-select" id="priority" name="priority" data-filter-auto-submit>
                            <option value="">{{ __('tickets.index.filters.all') }}</option>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->id }}" @selected($filters['priority'] === (string) $priority->id)>{{ $priority->translatedName() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-field">
                        <div class="filter-head">
                            <label class="filter-label" for="category">{{ __('tickets.index.filters.category') }}</label>
                            @if ($filters['category'] !== '')
                                <a
                                    class="filter-clear"
                                    href="{{ route('tickets.index', $clearFilterQuery('category')) }}"
                                    aria-label="{{ __('tickets.index.filters.clear_category') }}"
                                    title="{{ __('tickets.index.filters.clear_category') }}"
                                >
                                    &times;
                                </a>
                            @endif
                        </div>
                        <select class="filter-select" id="category" name="category" data-filter-auto-submit>
                            <option value="">{{ __('tickets.index.filters.all') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected($filters['category'] === (string) $category->id)>{{ $category->translatedName() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-field">
                        <div class="filter-head">
                            <label class="filter-label" for="watched">{{ __('tickets.index.filters.watching') }}</label>
                            @if ($filters['watched'] !== '')
                                <a
                                    class="filter-clear"
                                    href="{{ route('tickets.index', $clearFilterQuery('watched')) }}"
                                    aria-label="{{ __('tickets.index.filters.clear_watching') }}"
                                    title="{{ __('tickets.index.filters.clear_watching') }}"
                                >
                                    &times;
                                </a>
                            @endif
                        </div>
                        <select class="filter-select" id="watched" name="watched" data-filter-auto-submit>
                            <option value="">{{ __('tickets.index.filters.all_tickets') }}</option>
                            <option value="1" @selected($filters['watched'] === '1')>{{ __('tickets.index.filters.watched_only') }}</option>
                        </select>
                    </div>
                </div>
            </form>
        </section>

        @if ($pinningEnabled && $pinnedTickets->isNotEmpty())
            <section class="pinned-section" aria-label="{{ __('tickets.index.pinned.label') }}">
                <div class="pinned-section-head">
                    <div>
                        <h3>{{ __('tickets.index.pinned.heading') }}</h3>
                        <p>{{ __('tickets.index.pinned.subheading') }}</p>
                    </div>

                    <span class="badge badge-pinned">
                        <span class="badge-dot"></span>
                        {{ __('tickets.index.pinned.count', ['count' => $pinnedTickets->count()]) }}
                    </span>
                </div>

                <div class="pinned-grid">
                    @foreach ($pinnedTickets as $ticket)
                        <article class="pinned-ticket">
                            <div class="pinned-ticket-head">
                                <div>
                                    <div class="pinned-ticket-number">{{ $ticket->ticket_number ?? __('tickets.common.no_number') }}</div>
                                    <h3 class="pinned-ticket-subject">
                                        <a class="ticket-link" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->subject }}</a>
                                    </h3>
                                </div>

                                <span class="badge badge-pinned">{{ __('tickets.index.pinned.badge') }}</span>
                            </div>

                            <div class="pinned-ticket-badges">
                                <span class="badge {{ $ticket->status?->badgeToneClass() ?? 'badge-tone-slate' }}" data-ticket-id="{{ $ticket->id }}" data-ticket-field="status">
                                    <span class="badge-dot"></span>
                                    <span data-ticket-field-value>{{ $ticket->status?->translatedName() ?? __('tickets.common.not_available') }}</span>
                                </span>
                                <span class="badge {{ $ticket->priority?->badgeToneClass() ?? 'badge-tone-slate' }}" data-ticket-id="{{ $ticket->id }}" data-ticket-field="priority">
                                    <span class="badge-dot"></span>
                                    <span data-ticket-field-value>{{ $ticket->priority?->translatedName() ?? __('tickets.common.not_available') }}</span>
                                </span>
                            </div>

                            <div class="pinned-ticket-meta">
                                <div>{{ $ticket->translatedVisibilityLabel() }}</div>
                                <div>{{ __('tickets.index.meta.requester', ['name' => $ticket->requester?->name ?? __('tickets.common.not_available')]) }}</div>
                                <div>{{ __('tickets.index.meta.assignee', ['name' => $ticket->assignee?->name ?? __('tickets.common.unassigned')]) }}</div>
                                <div>{{ __('tickets.index.meta.updated', ['date' => $ticket->updated_at?->locale($locale)->translatedFormat($listUpdatedAtFormat) ?? __('tickets.common.not_available')]) }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($tickets->isEmpty())
            <section class="empty-state" aria-label="{{ __('tickets.index.heading') }}">
                @if ($hasActiveFilters)
                    <h3>{{ __('tickets.index.empty.filtered_heading') }}</h3>
                    <p>{{ __('tickets.index.empty.filtered_body') }}</p>
                @else
                    <h3>{{ __('tickets.index.empty.heading') }}</h3>
                    <p>{{ __('tickets.index.empty.body') }}</p>
                @endif
            </section>
        @else
            <div class="table-wrap">
                <table class="ticket-table">
                    <thead>
                        <tr>
                            <th class="ticket-col-number" scope="col">{{ __('tickets.index.table.ticket_number') }}</th>
                            <th scope="col">{{ __('tickets.index.table.subject') }}</th>
                            <th class="ticket-col-status" scope="col">{{ __('tickets.index.table.status') }}</th>
                            <th class="ticket-col-priority" scope="col">{{ __('tickets.index.table.priority') }}</th>
                            <th class="ticket-col-updated" scope="col">{{ __('tickets.index.table.updated_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr data-ticket-row="{{ $ticket->id }}">
                                <td class="ticket-number ticket-col-number">
                                    <a class="ticket-link" href="{{ route('tickets.show', $ticket) }}">
                                        {{ $ticket->ticket_number ?? __('tickets.common.not_available') }}
                                    </a>
                                </td>
                                <td class="subject">
                                    <a class="ticket-link subject-title" href="{{ route('tickets.show', $ticket) }}">
                                        {{ $ticket->subject }}
                                    </a>

                                    <div class="subject-meta">
                                        <span class="subject-meta-item">
                                            {{ $ticket->translatedVisibilityLabel() }}
                                        </span>
                                        <span class="subject-meta-item">
                                            {{ __('tickets.index.meta.assignee', ['name' => $ticket->assignee?->name ?? __('tickets.common.unassigned')]) }}
                                        </span>
                                        <span class="subject-meta-item">
                                            {{ trans_choice('tickets.index.meta.comments', $ticket->public_comments_count, ['count' => $ticket->public_comments_count]) }}
                                        </span>
                                        @if ($pinningEnabled && $ticket->is_pinned)
                                            <span class="subject-meta-item subject-meta-pill">{{ __('tickets.index.meta.pinned') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="ticket-col-status">
                                    @if ($ticket->can_inline_status_update)
                                        <button
                                            class="badge badge-button list-inline-trigger {{ $ticket->status?->badgeToneClass() ?? 'badge-tone-slate' }}"
                                            type="button"
                                            data-ticket-inline-trigger
                                            data-ticket-id="{{ $ticket->id }}"
                                            data-ticket-field="status"
                                            data-inline-mode="status"
                                            data-submit-url="{{ route('tickets.status.update', $ticket) }}"
                                            data-current-value="{{ $ticket->ticket_status_id }}"
                                            data-ticket-number="{{ $ticket->ticket_number ?? __('tickets.common.not_available') }}"
                                            data-ticket-subject="{{ $ticket->subject }}"
                                            aria-expanded="false"
                                        >
                                            <span class="badge-dot"></span>
                                            <span class="badge-label" data-ticket-field-value>{{ $ticket->status?->translatedName() ?? __('tickets.common.not_available') }}</span>
                                        </button>
                                    @else
                                        <span class="badge {{ $ticket->status?->badgeToneClass() ?? 'badge-tone-slate' }}" data-ticket-id="{{ $ticket->id }}" data-ticket-field="status">
                                            <span class="badge-dot"></span>
                                            <span class="badge-label" data-ticket-field-value>{{ $ticket->status?->translatedName() ?? __('tickets.common.not_available') }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="ticket-col-priority">
                                    @if ($ticket->can_inline_priority_update)
                                        <button
                                            class="badge badge-button list-inline-trigger {{ $ticket->priority?->badgeToneClass() ?? 'badge-tone-slate' }}"
                                            type="button"
                                            data-ticket-inline-trigger
                                            data-ticket-id="{{ $ticket->id }}"
                                            data-ticket-field="priority"
                                            data-inline-mode="priority"
                                            data-submit-url="{{ route('tickets.priority.update', $ticket) }}"
                                            data-current-value="{{ $ticket->ticket_priority_id }}"
                                            data-ticket-number="{{ $ticket->ticket_number ?? __('tickets.common.not_available') }}"
                                            data-ticket-subject="{{ $ticket->subject }}"
                                            aria-expanded="false"
                                        >
                                            <span class="badge-dot"></span>
                                            <span class="badge-label" data-ticket-field-value>{{ $ticket->priority?->translatedName() ?? __('tickets.common.not_available') }}</span>
                                        </button>
                                    @else
                                        <span class="badge {{ $ticket->priority?->badgeToneClass() ?? 'badge-tone-slate' }}" data-ticket-id="{{ $ticket->id }}" data-ticket-field="priority">
                                            <span class="badge-dot"></span>
                                            <span class="badge-label" data-ticket-field-value>{{ $ticket->priority?->translatedName() ?? __('tickets.common.not_available') }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="ticket-col-updated muted">
                                    <span class="ticket-updated-value" data-ticket-id="{{ $ticket->id }}" data-ticket-updated-at>{{ $ticket->updated_at?->locale($locale)->translatedFormat($listUpdatedAtFormat) ?? __('tickets.common.not_available') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div id="ticket-inline-editor" class="list-inline-editor" hidden>
                <form class="list-inline-editor-form" data-inline-form>
                    <div class="list-inline-editor-head">
                        <div class="list-inline-editor-title" data-inline-title></div>
                        <div class="list-inline-editor-ticket" data-inline-ticket></div>
                    </div>

                    <select class="list-inline-select" data-inline-select></select>
                    <div class="field-error" data-inline-error hidden></div>

                    <div class="list-inline-editor-actions">
                        <button class="button button-primary button-compact" type="submit" data-inline-submit>{{ __('tickets.index.inline.save') }}</button>
                        <button class="button button-secondary button-compact" type="button" data-inline-cancel>{{ __('tickets.index.inline.cancel') }}</button>
                    </div>
                </form>
            </div>

            <script id="ticket-inline-options-status" type="application/json">@json($inlineStatusOptions)</script>
            <script id="ticket-inline-options-priority" type="application/json">@json($inlinePriorityOptions)</script>

            @if ($tickets->hasPages())
                <div class="pagination-wrap">
                    <div class="pagination-meta">
                        {{ __('tickets.index.pagination.meta', ['from' => $tickets->firstItem(), 'to' => $tickets->lastItem(), 'total' => $tickets->total()]) }}
                    </div>

                    <nav class="pagination" aria-label="{{ __('tickets.index.pagination.label') }}">
                        @if ($tickets->onFirstPage())
                            <span class="page-link disabled">{{ __('tickets.index.pagination.previous') }}</span>
                        @else
                            <a class="page-link" href="{{ $tickets->previousPageUrl() }}">{{ __('tickets.index.pagination.previous') }}</a>
                        @endif

                        @foreach ($tickets->getUrlRange(max(1, $tickets->currentPage() - 2), min($tickets->lastPage(), $tickets->currentPage() + 2)) as $page => $url)
                            @if ($page === $tickets->currentPage())
                                <span class="page-link active">{{ $page }}</span>
                            @else
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($tickets->hasMorePages())
                            <a class="page-link" href="{{ $tickets->nextPageUrl() }}">{{ __('tickets.index.pagination.next') }}</a>
                        @else
                            <span class="page-link disabled">{{ __('tickets.index.pagination.next') }}</span>
                        @endif
                    </nav>
                </div>
            @endif
        @endif
    </div>
@endsection
