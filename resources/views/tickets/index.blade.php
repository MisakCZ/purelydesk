@extends('layouts.admin')

@section('title', __('tickets.index.page_title'))

@push('styles')
    <style>
        .filter-card {
            margin-bottom: 1rem;
            padding: 0.85rem 0.9rem;
            border: 1px solid #dbe3eb;
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
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
            display: block;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: minmax(11rem, 1.25fr) repeat(auto-fit, minmax(7.6rem, 0.82fr));
            gap: 0.55rem;
            align-items: end;
        }

        .filter-field {
            display: grid;
            gap: 0.35rem;
            padding: 0.55rem 0.65rem 0.65rem;
            border: 1px solid #edf2f7;
            border-radius: 0.9rem;
            background: #f8fafc;
            min-width: 0;
        }

        .filter-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            min-width: 0;
        }

        .filter-label {
            font-size: 0.73rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #64748b;
            min-width: 0;
        }

        .filter-clear {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.15rem;
            height: 1.15rem;
            border-radius: 0.4rem;
            background: transparent;
            color: #b42318;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1;
            flex: 0 0 auto;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .filter-clear:hover {
            background: #fff1f1;
            color: #991b1b;
        }

        .filter-control {
            position: relative;
        }

        .filter-control-select::after {
            content: "";
            position: absolute;
            right: 0.82rem;
            top: 50%;
            width: 0.52rem;
            height: 0.52rem;
            border-right: 1.6px solid #94a3b8;
            border-bottom: 1.6px solid #94a3b8;
            transform: translateY(-65%) rotate(45deg);
            pointer-events: none;
        }

        .filter-search-icon {
            position: absolute;
            left: 0.82rem;
            top: 50%;
            transform: translateY(-50%);
            width: 0.9rem;
            height: 0.9rem;
            color: #94a3b8;
            pointer-events: none;
        }

        .filter-input,
        .filter-select {
            width: 100%;
            min-height: 2.45rem;
            padding: 0.52rem 0.75rem;
            border: 1px solid #d7e0ea;
            border-radius: 0.78rem;
            background: #fff;
            color: #0f172a;
            font: inherit;
            font-size: 0.92rem;
            font-weight: 500;
            line-height: 1.35;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }

        .filter-input {
            padding-left: 2.15rem;
        }

        .filter-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 2rem;
        }

        .filter-input:focus,
        .filter-select:focus {
            outline: 2px solid rgba(15, 118, 110, 0.16);
            border-color: #0f766e;
            background: #fff;
        }

        .filter-input::placeholder {
            color: #94a3b8;
            font-weight: 400;
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

        .sort-link {
            display: inline-flex;
            align-items: center;
            gap: 0.28rem;
            color: inherit;
            text-decoration: none;
            white-space: nowrap;
        }

        .sort-link:hover,
        .sort-link.active {
            color: #0f766e;
        }

        .sort-indicator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 0.8rem;
            color: #94a3b8;
            font-size: 0.68rem;
            line-height: 1;
        }

        .sort-link.active .sort-indicator {
            color: currentColor;
        }

        .ticket-table tbody tr:hover {
            background: #f8fbff;
        }

        .ticket-row-updated {
            background: #f0fdf4 !important;
        }

        .ticket-col-number {
            width: 6.6rem;
        }

        .ticket-col-status {
            width: 17.7rem;
        }

        .ticket-col-priority {
            width: 8.85rem;
        }

        .ticket-col-updated {
            width: 8.65rem;
            white-space: nowrap;
        }

        .ticket-table th.ticket-col-updated,
        .ticket-table td.ticket-col-updated {
            padding-right: 1.25rem;
        }

        .ticket-number {
            color: #7b8794;
            font-size: 0.79rem;
            font-weight: 400;
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
            padding-left: 1.15rem !important;
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
            gap: 0.2rem 0.45rem;
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

        .subject-meta-separator {
            color: #94a3b8;
            line-height: 1.4;
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

        .badge-caret {
            font-size: 0.72rem;
            line-height: 1;
            opacity: 0.7;
            transition: transform 0.15s ease;
        }

        .list-inline-menu {
            position: relative;
            display: block;
        }

        .list-inline-menu[open] {
            z-index: 20;
        }

        .list-inline-menu[open] .badge-caret {
            transform: rotate(180deg);
        }

        .list-inline-menu > summary {
            list-style: none;
        }

        .list-inline-menu > summary::-webkit-details-marker {
            display: none;
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

        .list-inline-dropdown {
            position: fixed;
            min-width: 14.5rem;
            max-width: min(18rem, calc(100vw - 2rem));
            padding: 0.35rem;
            border: 1px solid #d9e0e7;
            border-radius: 0.95rem;
            background: #fff;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
            z-index: 260;
            font-size: 0.88rem;
        }

        .list-inline-options {
            display: grid;
            gap: 0.2rem;
            max-height: 15rem;
            overflow: auto;
        }

        .list-inline-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            min-height: 2.18rem;
            padding: 0.44rem 0.68rem;
            border: 0;
            border-radius: 0.7rem;
            background: transparent;
            color: #334155;
            cursor: pointer;
            font: inherit;
            font-size: 0.88rem;
            text-align: left;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .list-inline-option:hover {
            background: #f8fafc;
            color: #13202b;
        }

        .list-inline-option.active {
            background: #eef6f5;
            color: #13202b;
            font-weight: 700;
        }

        .list-inline-option:disabled {
            cursor: default;
        }

        .list-inline-option-check {
            color: #0f766e;
            font-size: 0.74rem;
            line-height: 1;
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

        .inline-feedback.is-error {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
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

            .ticket-col-status {
                width: 17.1rem;
            }

            .ticket-col-priority {
                width: 8.55rem;
            }

            .ticket-col-updated {
                width: 8.35rem;
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
                'genericError' => __('tickets.index.inline.generic_error'),
                'saved' => __('tickets.index.inline.saved'),
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

            const feedback = document.getElementById('ticket-inline-feedback');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const requestLocale = document.documentElement.lang
                ? document.documentElement.lang.split('-')[0].toLowerCase()
                : null;

            let feedbackTimeout = null;
            const menuViewportGap = 12;
            const menuOffset = 8;

            const hideFeedback = () => {
                if (! feedback) {
                    return;
                }

                feedback.hidden = true;
                feedback.classList.remove('is-error');
                feedback.textContent = '';
            };

            const showFeedback = (message, type = 'success') => {
                if (! feedback || ! message) {
                    return;
                }

                feedback.textContent = message;
                feedback.hidden = false;
                feedback.classList.toggle('is-error', type === 'error');

                if (feedbackTimeout) {
                    window.clearTimeout(feedbackTimeout);
                }

                feedbackTimeout = window.setTimeout(() => {
                    hideFeedback();
                }, 2600);
            };

            const syncMenuState = (menu) => {
                const trigger = menu.querySelector('summary');

                if (! trigger) {
                    return;
                }

                trigger.setAttribute('aria-expanded', menu.open ? 'true' : 'false');
            };

            const resetMenuPosition = (menu) => {
                if (! menu) {
                    return;
                }

                const dropdown = menu.querySelector('.list-inline-dropdown');

                if (! dropdown) {
                    return;
                }

                dropdown.style.top = '';
                dropdown.style.left = '';
                dropdown.style.minWidth = '';
                menu.classList.remove('is-dropup');
            };

            const positionMenu = (menu) => {
                if (! menu?.open) {
                    return;
                }

                const trigger = menu.querySelector('summary');
                const dropdown = menu.querySelector('.list-inline-dropdown');

                if (! trigger || ! dropdown) {
                    return;
                }

                const triggerRect = trigger.getBoundingClientRect();

                dropdown.style.minWidth = `${Math.max(Math.ceil(triggerRect.width), 232)}px`;
                dropdown.style.left = `${Math.max(menuViewportGap, Math.round(triggerRect.left))}px`;
                dropdown.style.top = `${Math.max(menuViewportGap, Math.round(triggerRect.bottom + menuOffset))}px`;

                const dropdownRect = dropdown.getBoundingClientRect();
                const maxLeft = Math.max(menuViewportGap, window.innerWidth - dropdownRect.width - menuViewportGap);
                const hasEnoughSpaceBelow = triggerRect.bottom + menuOffset + dropdownRect.height <= window.innerHeight - menuViewportGap;
                const hasEnoughSpaceAbove = triggerRect.top - menuOffset - dropdownRect.height >= menuViewportGap;

                let left = Math.min(Math.max(menuViewportGap, triggerRect.left), maxLeft);
                let top = triggerRect.bottom + menuOffset;
                let openUpwards = false;

                if (! hasEnoughSpaceBelow && hasEnoughSpaceAbove) {
                    top = triggerRect.top - dropdownRect.height - menuOffset;
                    openUpwards = true;
                }

                const maxTop = Math.max(menuViewportGap, window.innerHeight - dropdownRect.height - menuViewportGap);
                top = Math.min(Math.max(menuViewportGap, top), maxTop);

                dropdown.style.left = `${Math.round(left)}px`;
                dropdown.style.top = `${Math.round(top)}px`;
                menu.classList.toggle('is-dropup', openUpwards);
            };

            const closeMenu = (menu) => {
                if (! menu) {
                    return;
                }

                menu.open = false;
                resetMenuPosition(menu);
                syncMenuState(menu);
            };

            const positionOpenMenus = () => {
                document.querySelectorAll('[data-ticket-inline-menu][open]').forEach((menu) => {
                    positionMenu(menu);
                });
            };

            document.querySelectorAll('[data-ticket-inline-menu]').forEach((menu) => {
                syncMenuState(menu);

                menu.addEventListener('toggle', () => {
                    syncMenuState(menu);

                    if (! menu.open) {
                        resetMenuPosition(menu);
                        return;
                    }

                    document.querySelectorAll('[data-ticket-inline-menu][open]').forEach((otherMenu) => {
                        if (otherMenu !== menu) {
                            closeMenu(otherMenu);
                        }
                    });

                    window.requestAnimationFrame(() => {
                        positionMenu(menu);
                    });
                });
            });

            window.addEventListener('resize', positionOpenMenus);
            window.addEventListener('scroll', positionOpenMenus, true);

            document.addEventListener('click', (event) => {
                document.querySelectorAll('[data-ticket-inline-menu][open]').forEach((menu) => {
                    if (! menu.contains(event.target)) {
                        closeMenu(menu);
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                document.querySelectorAll('[data-ticket-inline-menu][open]').forEach((menu) => {
                    closeMenu(menu);
                });
            });

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

                    document.querySelectorAll(`[data-ticket-id="${ticketId}"][data-ticket-field="status"] [data-ticket-current-value]`).forEach((node) => {
                        node.setAttribute('data-ticket-current-value', ticketData.status.id);
                    });
                }

                if (ticketData.priority) {
                    document.querySelectorAll(`[data-ticket-id="${ticketId}"][data-ticket-field="priority"]`).forEach((node) => {
                        replaceBadgeToneClass(node, ticketData.priority.badge_class);
                    });

                    document.querySelectorAll(`[data-ticket-id="${ticketId}"][data-ticket-field="priority"] [data-ticket-field-value]`).forEach((node) => {
                        node.textContent = ticketData.priority.name;
                    });

                    document.querySelectorAll(`[data-ticket-id="${ticketId}"][data-ticket-field="priority"] [data-ticket-current-value]`).forEach((node) => {
                        node.setAttribute('data-ticket-current-value', ticketData.priority.id);
                    });
                }

                if (ticketData.updated_at_display) {
                    document.querySelectorAll(`[data-ticket-id="${ticketId}"][data-ticket-updated-at]`).forEach((node) => {
                        node.textContent = ticketData.updated_at_display;
                    });
                }
            };

            const updateOptionStates = (ticketId, field, selectedValue) => {
                document.querySelectorAll(`[data-ticket-id="${ticketId}"][data-ticket-field="${field}"][data-ticket-option-value]`).forEach((button) => {
                    const isActive = String(button.dataset.ticketOptionValue) === String(selectedValue);
                    const check = button.querySelector('[data-ticket-option-check]');

                    button.disabled = isActive;
                    button.classList.toggle('active', isActive);

                    if (check) {
                        check.hidden = ! isActive;
                    }
                });
            };

            document.querySelectorAll('[data-ticket-inline-option-form]').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const submitter = event.submitter;

                    if (! submitter) {
                        return;
                    }

                    const payload = new FormData(form, submitter);

                    if (requestLocale && ! payload.has('_locale')) {
                        payload.append('_locale', requestLocale);
                    }

                    form.querySelectorAll('button').forEach((button) => {
                        button.disabled = true;
                    });

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                ...(requestLocale ? { 'X-Helpdesk-Locale': requestLocale } : {}),
                                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                            },
                            body: payload,
                            credentials: 'same-origin',
                        });

                        const responseData = await response.json().catch(() => null);

                        if (! response.ok) {
                            const errorMessage = Object.values(responseData?.errors ?? {}).flat().join(' ') || responseData?.message || translations.genericError;
                            showFeedback(errorMessage, 'error');

                            return;
                        }

                        const ticketId = form.dataset.ticketId;
                        const field = form.dataset.ticketField;
                        const selectedValue = submitter.dataset.ticketOptionValue;
                        updateTicketDisplays(ticketId, responseData.ticket ?? {});
                        updateOptionStates(ticketId, field, selectedValue);
                        showFeedback(responseData.message || translations.saved);

                        const row = document.querySelector(`tr[data-ticket-row="${ticketId}"]`);

                        if (row) {
                            row.classList.add('ticket-row-updated');

                            window.setTimeout(() => {
                                row.classList.remove('ticket-row-updated');
                            }, 1600);
                        }

                        closeMenu(form.closest('[data-ticket-inline-menu]'));
                    } catch (error) {
                        showFeedback(translations.genericError, 'error');
                    } finally {
                        const ticketId = form.dataset.ticketId;
                        const field = form.dataset.ticketField;
                        const activeValueNode = document.querySelector(`[data-ticket-id="${ticketId}"][data-ticket-field="${field}"][data-ticket-current-value]`);
                        const currentValue = activeValueNode
                            ? activeValueNode.getAttribute('data-ticket-current-value')
                            : null;

                        form.querySelectorAll('button').forEach((button) => {
                            const shouldStayDisabled = currentValue !== null
                                && String(button.dataset.ticketOptionValue) === String(currentValue);
                            button.disabled = shouldStayDisabled;
                        });
                    }
                });
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
        $sortQuery = static function (string $column) use ($filters): array {
            $query = array_filter($filters, fn ($value) => $value !== '');
            $isActiveColumn = ($filters['sort'] ?? 'updated_at') === $column;

            $query['sort'] = $column;
            $query['direction'] = $isActiveColumn && ($filters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc';

            unset($query['page']);

            return $query;
        };
        $sortIndicator = static function (string $column) use ($filters): string {
            if (($filters['sort'] ?? 'updated_at') !== $column) {
                return '↕';
            }

            return ($filters['direction'] ?? 'desc') === 'asc' ? '↑' : '↓';
        };
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
                <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                <input type="hidden" name="direction" value="{{ $filters['direction'] }}">

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
                        <div class="filter-control">
                            <span class="filter-search-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="7"></circle>
                                    <path d="m20 20-3.5-3.5"></path>
                                </svg>
                            </span>
                            <input
                                class="filter-input"
                                id="search_input"
                                type="search"
                                value="{{ $filters['search'] }}"
                                placeholder="{{ __('tickets.index.filters.search_placeholder') }}"
                                data-filter-search-input
                            >
                        </div>
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
                        <div class="filter-control filter-control-select">
                            <select class="filter-select" id="status" name="status" data-filter-auto-submit>
                                <option value="">{{ __('tickets.index.filters.all') }}</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->id }}" @selected($filters['status'] === (string) $status->id)>{{ $status->translatedName() }}</option>
                                @endforeach
                            </select>
                        </div>
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
                        <div class="filter-control filter-control-select">
                            <select class="filter-select" id="priority" name="priority" data-filter-auto-submit>
                                <option value="">{{ __('tickets.index.filters.all') }}</option>
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority->id }}" @selected($filters['priority'] === (string) $priority->id)>{{ $priority->translatedName() }}</option>
                                @endforeach
                            </select>
                        </div>
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
                        <div class="filter-control filter-control-select">
                            <select class="filter-select" id="category" name="category" data-filter-auto-submit>
                                <option value="">{{ __('tickets.index.filters.all') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($filters['category'] === (string) $category->id)>{{ $category->translatedName() }}</option>
                                @endforeach
                            </select>
                        </div>
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
                        <div class="filter-control filter-control-select">
                            <select class="filter-select" id="watched" name="watched" data-filter-auto-submit>
                                <option value="">{{ __('tickets.index.filters.all_tickets') }}</option>
                                <option value="1" @selected($filters['watched'] === '1')>{{ __('tickets.index.filters.watched_only') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-field">
                        <div class="filter-head">
                            <label class="filter-label" for="scope">{{ __('tickets.index.filters.scope') }}</label>
                            @if ($filters['scope'] !== '')
                                <a
                                    class="filter-clear"
                                    href="{{ route('tickets.index', $clearFilterQuery('scope')) }}"
                                    aria-label="{{ __('tickets.index.filters.clear_scope') }}"
                                    title="{{ __('tickets.index.filters.clear_scope') }}"
                                >
                                    &times;
                                </a>
                            @endif
                        </div>
                        <div class="filter-control filter-control-select">
                            <select class="filter-select" id="scope" name="scope" data-filter-auto-submit>
                                <option value="">{{ __('tickets.index.filters.scope_all') }}</option>
                                <option value="open" @selected($filters['scope'] === 'open')>{{ __('tickets.index.filters.scope_open') }}</option>
                                <option value="finished" @selected($filters['scope'] === 'finished')>{{ __('tickets.index.filters.scope_finished') }}</option>
                            </select>
                        </div>
                    </div>

                    @if ($canViewArchivedTickets)
                        <div class="filter-field">
                            <div class="filter-head">
                                <label class="filter-label" for="archive">{{ __('tickets.index.filters.archive') }}</label>
                                @if ($filters['archive'] !== '')
                                    <a
                                        class="filter-clear"
                                        href="{{ route('tickets.index', $clearFilterQuery('archive')) }}"
                                        aria-label="{{ __('tickets.index.filters.clear_archive') }}"
                                        title="{{ __('tickets.index.filters.clear_archive') }}"
                                    >
                                        &times;
                                    </a>
                                @endif
                            </div>
                            <div class="filter-control filter-control-select">
                                <select class="filter-select" id="archive" name="archive" data-filter-auto-submit>
                                    <option value="">{{ __('tickets.index.filters.archive_active') }}</option>
                                    <option value="archived" @selected($filters['archive'] === 'archived')>{{ __('tickets.index.filters.archive_archived') }}</option>
                                </select>
                            </div>
                        </div>
                    @endif
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
                                <div>{{ __('tickets.index.meta.requester', ['name' => $ticket->requester?->displayName() ?? __('tickets.common.not_available')]) }}</div>
                                <div>
                                    {{ $ticket->assignee
                                        ? __('tickets.index.meta.assignee', ['name' => $ticket->assignee->displayName()])
                                        : __('tickets.index.meta.assignee_unassigned') }}
                                </div>
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
                            <th class="ticket-col-number" scope="col">
                                <a class="sort-link{{ $filters['sort'] === 'number' ? ' active' : '' }}" href="{{ route('tickets.index', $sortQuery('number')) }}" aria-label="{{ __('tickets.index.sort.toggle', ['column' => __('tickets.index.table.ticket_number')]) }}">
                                    <span>{{ __('tickets.index.table.ticket_number') }}</span>
                                    <span class="sort-indicator" aria-hidden="true">{{ $sortIndicator('number') }}</span>
                                </a>
                            </th>
                            <th scope="col">
                                <a class="sort-link{{ $filters['sort'] === 'subject' ? ' active' : '' }}" href="{{ route('tickets.index', $sortQuery('subject')) }}" aria-label="{{ __('tickets.index.sort.toggle', ['column' => __('tickets.index.table.subject')]) }}">
                                    <span>{{ __('tickets.index.table.subject') }}</span>
                                    <span class="sort-indicator" aria-hidden="true">{{ $sortIndicator('subject') }}</span>
                                </a>
                            </th>
                            <th class="ticket-col-status" scope="col">
                                <a class="sort-link{{ $filters['sort'] === 'status' ? ' active' : '' }}" href="{{ route('tickets.index', $sortQuery('status')) }}" aria-label="{{ __('tickets.index.sort.toggle', ['column' => __('tickets.index.table.status')]) }}">
                                    <span>{{ __('tickets.index.table.status') }}</span>
                                    <span class="sort-indicator" aria-hidden="true">{{ $sortIndicator('status') }}</span>
                                </a>
                            </th>
                            <th class="ticket-col-priority" scope="col">
                                <a class="sort-link{{ $filters['sort'] === 'priority' ? ' active' : '' }}" href="{{ route('tickets.index', $sortQuery('priority')) }}" aria-label="{{ __('tickets.index.sort.toggle', ['column' => __('tickets.index.table.priority')]) }}">
                                    <span>{{ __('tickets.index.table.priority') }}</span>
                                    <span class="sort-indicator" aria-hidden="true">{{ $sortIndicator('priority') }}</span>
                                </a>
                            </th>
                            <th class="ticket-col-updated" scope="col">
                                <a class="sort-link{{ $filters['sort'] === 'updated_at' ? ' active' : '' }}" href="{{ route('tickets.index', $sortQuery('updated_at')) }}" aria-label="{{ __('tickets.index.sort.toggle', ['column' => __('tickets.index.table.updated_at')]) }}">
                                    <span>{{ __('tickets.index.table.updated_at') }}</span>
                                    <span class="sort-indicator" aria-hidden="true">{{ $sortIndicator('updated_at') }}</span>
                                </a>
                            </th>
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
                                        <span class="subject-meta-separator" aria-hidden="true">&middot;</span>
                                        <span class="subject-meta-item">
                                            {{ $ticket->assignee
                                                ? __('tickets.index.meta.assignee', ['name' => $ticket->assignee->displayName()])
                                                : __('tickets.index.meta.assignee_unassigned') }}
                                        </span>
                                        <span class="subject-meta-separator" aria-hidden="true">&middot;</span>
                                        <span class="subject-meta-item">
                                            {{ trans_choice('tickets.index.meta.comments', $ticket->public_comments_count, ['count' => $ticket->public_comments_count]) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="ticket-col-status">
                                    @if ($ticket->can_inline_status_update)
                                        <details class="list-inline-menu" data-ticket-inline-menu>
                                            <summary
                                                class="badge badge-button list-inline-trigger {{ $ticket->status?->badgeToneClass() ?? 'badge-tone-slate' }}"
                                                data-ticket-id="{{ $ticket->id }}"
                                                data-ticket-field="status"
                                                aria-expanded="false"
                                            >
                                                <span class="badge-dot"></span>
                                                <span class="badge-label" data-ticket-field-value>{{ $ticket->status?->translatedName() ?? __('tickets.common.not_available') }}</span>
                                                <span class="badge-caret" aria-hidden="true">▾</span>
                                                <span class="sr-only" data-ticket-current-value="{{ $ticket->ticket_status_id }}"></span>
                                            </summary>

                                            <div class="list-inline-dropdown">
                                                <form
                                                    class="list-inline-option-form"
                                                    method="post"
                                                    action="{{ route('tickets.status.update', $ticket) }}"
                                                    data-ticket-inline-option-form
                                                    data-ticket-id="{{ $ticket->id }}"
                                                    data-ticket-field="status"
                                                >
                                                    @csrf
                                                    @method('patch')
                                                    <input type="hidden" name="_locale" value="{{ $locale }}">

                                                    <div class="list-inline-options">
                                                        @foreach ($statuses as $status)
                                                            @php($isCurrentStatus = (string) $ticket->ticket_status_id === (string) $status->id)
                                                            <button
                                                                class="list-inline-option{{ $isCurrentStatus ? ' active' : '' }}"
                                                                type="submit"
                                                                name="status_id"
                                                                value="{{ $status->id }}"
                                                                data-ticket-option-value="{{ $status->id }}"
                                                                @disabled($isCurrentStatus)
                                                            >
                                                                <span>{{ $status->translatedName() }}</span>
                                                                <span class="list-inline-option-check" data-ticket-option-check @if (! $isCurrentStatus) hidden @endif>✓</span>
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </form>
                                            </div>
                                        </details>
                                    @else
                                        <span class="badge {{ $ticket->status?->badgeToneClass() ?? 'badge-tone-slate' }}" data-ticket-id="{{ $ticket->id }}" data-ticket-field="status">
                                            <span class="badge-dot"></span>
                                            <span class="badge-label" data-ticket-field-value>{{ $ticket->status?->translatedName() ?? __('tickets.common.not_available') }}</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="ticket-col-priority">
                                    @if ($ticket->can_inline_priority_update)
                                        <details class="list-inline-menu" data-ticket-inline-menu>
                                            <summary
                                                class="badge badge-button list-inline-trigger {{ $ticket->priority?->badgeToneClass() ?? 'badge-tone-slate' }}"
                                                data-ticket-id="{{ $ticket->id }}"
                                                data-ticket-field="priority"
                                                aria-expanded="false"
                                            >
                                                <span class="badge-dot"></span>
                                                <span class="badge-label" data-ticket-field-value>{{ $ticket->priority?->translatedName() ?? __('tickets.common.not_available') }}</span>
                                                <span class="badge-caret" aria-hidden="true">▾</span>
                                                <span class="sr-only" data-ticket-current-value="{{ $ticket->ticket_priority_id }}"></span>
                                            </summary>

                                            <div class="list-inline-dropdown">
                                                <form
                                                    class="list-inline-option-form"
                                                    method="post"
                                                    action="{{ route('tickets.priority.update', $ticket) }}"
                                                    data-ticket-inline-option-form
                                                    data-ticket-id="{{ $ticket->id }}"
                                                    data-ticket-field="priority"
                                                >
                                                    @csrf
                                                    @method('patch')
                                                    <input type="hidden" name="_locale" value="{{ $locale }}">

                                                    <div class="list-inline-options">
                                                        @foreach ($priorities as $priority)
                                                            @php($isCurrentPriority = (string) $ticket->ticket_priority_id === (string) $priority->id)
                                                            <button
                                                                class="list-inline-option{{ $isCurrentPriority ? ' active' : '' }}"
                                                                type="submit"
                                                                name="priority_id"
                                                                value="{{ $priority->id }}"
                                                                data-ticket-option-value="{{ $priority->id }}"
                                                                @disabled($isCurrentPriority)
                                                            >
                                                                <span>{{ $priority->translatedName() }}</span>
                                                                <span class="list-inline-option-check" data-ticket-option-check @if (! $isCurrentPriority) hidden @endif>✓</span>
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </form>
                                            </div>
                                        </details>
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
