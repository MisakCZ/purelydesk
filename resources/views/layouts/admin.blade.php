<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('app.name', 'Helpdesk'))</title>
        <style>
            :root {
                color-scheme: light;
                --bg: #f4f6f8;
                --panel: #ffffff;
                --panel-muted: #f8fafc;
                --line: #d9e0e7;
                --text: #13202b;
                --muted: #5b6b79;
                --accent: #0f766e;
                --accent-soft: #dff5f2;
                --shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            }

            * {
                box-sizing: border-box;
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

            [hidden] {
                display: none !important;
            }

            body {
                margin: 0;
                min-height: 100vh;
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                background:
                    radial-gradient(circle at top left, rgba(15, 118, 110, 0.10), transparent 28%),
                    linear-gradient(180deg, #f8fbfc 0%, var(--bg) 100%);
                color: var(--text);
            }

            a {
                color: inherit;
            }

            .shell {
                width: min(1200px, calc(100% - 2rem));
                margin: 0 auto;
                padding: 2rem 0 3rem;
            }

            .topbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: 1.5rem;
            }

            .brand {
                display: flex;
                align-items: center;
                gap: 0.9rem;
            }

            .brand-mark {
                width: 2.75rem;
                height: 2.75rem;
                border-radius: 0.9rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                background: linear-gradient(135deg, #0f766e, #155e75);
                color: #fff;
                box-shadow: var(--shadow);
            }

            .brand-copy h1 {
                margin: 0;
                font-size: 1.1rem;
            }

            .brand-copy p {
                margin: 0.2rem 0 0;
                color: var(--muted);
                font-size: 0.95rem;
            }

            .nav {
                display: flex;
                gap: 0.75rem;
                flex-wrap: wrap;
                align-items: center;
            }

            .nav-link {
                display: inline-flex;
                align-items: center;
                min-height: 2.5rem;
                padding: 0.55rem 0.9rem;
                border-radius: 999px;
                text-decoration: none;
                color: var(--muted);
                background: rgba(255, 255, 255, 0.7);
                border: 1px solid transparent;
            }

            .nav-link.active {
                color: var(--accent);
                background: var(--accent-soft);
                border-color: rgba(15, 118, 110, 0.12);
            }

            .attachment-queue {
                display: grid;
                gap: 0.4rem;
                margin-top: 0.45rem;
            }

            .attachment-queue-empty,
            .attachment-queue-error {
                font-size: 0.8rem;
                font-weight: 600;
                line-height: 1.35;
            }

            .attachment-queue-empty {
                color: #64748b;
            }

            .attachment-queue-error {
                color: #b91c1c;
            }

            .attachment-queue-list {
                display: flex;
                flex-wrap: wrap;
                gap: 0.45rem;
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .attachment-queue-item {
                display: inline-flex;
                align-items: center;
                max-width: 100%;
                gap: 0.45rem;
                padding: 0.35rem 0.45rem;
                border: 1px solid #dbe4ee;
                border-radius: 0.7rem;
                background: #f8fafc;
                color: #334155;
                font-size: 0.8rem;
                font-weight: 650;
            }

            .attachment-queue-name {
                max-width: 15rem;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .attachment-queue-size {
                color: #64748b;
                font-size: 0.74rem;
                font-weight: 600;
                white-space: nowrap;
            }

            .attachment-queue-remove {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 1.35rem;
                height: 1.35rem;
                padding: 0;
                border: 0;
                border-radius: 999px;
                background: #fee2e2;
                color: #991b1b;
                cursor: pointer;
                font-size: 0.9rem;
                font-weight: 900;
                line-height: 1;
            }

            .attachment-lightbox {
                position: fixed;
                inset: 0;
                z-index: 1000;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.25rem;
                background: rgba(15, 23, 42, 0.76);
            }

            .attachment-lightbox[hidden] {
                display: none !important;
            }

            .attachment-lightbox-dialog {
                position: relative;
                display: grid;
                gap: 0.65rem;
                max-width: min(96vw, 1180px);
                max-height: 94vh;
            }

            .attachment-lightbox-image {
                max-width: min(96vw, 1180px);
                max-height: calc(94vh - 4rem);
                border-radius: 0.8rem;
                background: #fff;
                object-fit: contain;
                box-shadow: 0 24px 70px rgba(0, 0, 0, 0.35);
            }

            .attachment-lightbox-caption {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                color: #e2e8f0;
                font-size: 0.86rem;
                font-weight: 650;
            }

            .attachment-lightbox-download {
                color: #bfdbfe;
                text-decoration: none;
            }

            .attachment-lightbox-close {
                position: absolute;
                top: -0.85rem;
                right: -0.85rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2rem;
                height: 2rem;
                border: 0;
                border-radius: 999px;
                background: #fff;
                color: #0f172a;
                cursor: pointer;
                font-size: 1.3rem;
                font-weight: 800;
                line-height: 1;
            }

            .attachment-lightbox-nav {
                position: absolute;
                top: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2.65rem;
                height: 2.65rem;
                border: 0;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.92);
                color: #0f172a;
                cursor: pointer;
                font-size: 1.8rem;
                font-weight: 800;
                line-height: 1;
                transform: translateY(-50%);
                box-shadow: 0 16px 34px rgba(0, 0, 0, 0.22);
            }

            .attachment-lightbox-nav:disabled {
                cursor: default;
                opacity: 0.35;
            }

            .attachment-lightbox-prev {
                left: -1.1rem;
            }

            .attachment-lightbox-next {
                right: -1.1rem;
            }

            .attachment-lightbox-position {
                color: #cbd5e1;
                font-size: 0.82rem;
                font-weight: 750;
                white-space: nowrap;
            }

            @media (max-width: 700px) {
                .attachment-lightbox {
                    padding: 0.8rem;
                }

                .attachment-lightbox-nav {
                    width: 2.25rem;
                    height: 2.25rem;
                    font-size: 1.45rem;
                }

                .attachment-lightbox-prev {
                    left: 0.35rem;
                }

                .attachment-lightbox-next {
                    right: 0.35rem;
                }

                .attachment-lightbox-close {
                    top: 0.35rem;
                    right: 0.35rem;
                }
            }

            .locale-switcher {
                margin-left: 0.55rem;
                padding-left: 0.75rem;
                border-left: 1px solid rgba(217, 224, 231, 0.95);
                position: relative;
            }

            .locale-switcher[open] {
                z-index: 20;
            }

            .locale-toggle {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                min-height: 2.2rem;
                padding: 0;
                border: 0;
                background: transparent;
                color: #7b8794;
                cursor: pointer;
                font: inherit;
                font-size: 0.76rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                transition: color 0.15s ease;
                list-style: none;
            }

            .locale-toggle::-webkit-details-marker {
                display: none;
            }

            .locale-toggle:hover {
                color: var(--text);
            }

            .locale-current {
                color: #13202b;
                line-height: 1;
            }

            .locale-chevron {
                color: #94a3b8;
                font-size: 0.7rem;
                line-height: 1;
                transition: transform 0.15s ease;
            }

            .locale-switcher[open] .locale-chevron {
                transform: rotate(180deg);
            }

            .locale-menu {
                position: absolute;
                top: calc(100% + 0.45rem);
                right: 0;
                min-width: 8.75rem;
                padding: 0.35rem;
                border: 1px solid rgba(217, 224, 231, 0.95);
                border-radius: 0.9rem;
                background: #fff;
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.12);
                font-size: 0.86rem;
            }

            .locale-form {
                margin: 0;
            }

            .locale-option {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                min-height: 2.15rem;
                padding: 0.44rem 0.62rem;
                border: 0;
                border-radius: 0.65rem;
                background: transparent;
                color: #475569;
                cursor: pointer;
                font: inherit;
                font-size: 0.86rem;
                text-align: left;
                transition: background-color 0.15s ease, color 0.15s ease;
            }

            .locale-option:hover {
                background: #f8fafc;
                color: #13202b;
            }

            .locale-option.active {
                background: #eef6f5;
                color: #13202b;
                font-weight: 700;
            }

            .locale-option-label {
                font-size: 0.86rem;
                line-height: 1.3;
            }

            .locale-option-check {
                color: var(--accent);
                font-size: 0.76rem;
                line-height: 1;
            }

            .session-user {
                margin-left: 0.35rem;
                color: var(--muted);
                font-size: 0.82rem;
                white-space: nowrap;
            }

            .logout-form {
                margin: 0;
            }

            .logout-button {
                min-height: 2.2rem;
                padding: 0.35rem 0.65rem;
                border: 1px solid rgba(217, 224, 231, 0.95);
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.72);
                color: var(--muted);
                cursor: pointer;
                font: inherit;
                font-size: 0.82rem;
                font-weight: 600;
            }

            .logout-button:hover {
                color: var(--text);
                background: #fff;
            }

            .page-card {
                background: var(--panel);
                border: 1px solid rgba(217, 224, 231, 0.9);
                border-radius: 1.25rem;
                box-shadow: var(--shadow);
                overflow: hidden;
            }

            .page-head {
                padding: 1.5rem 1.5rem 1rem;
                border-bottom: 1px solid var(--line);
                background:
                    linear-gradient(135deg, rgba(15, 118, 110, 0.06), transparent 45%),
                    var(--panel);
            }

            .page-head-bar {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
            }

            .page-head h2 {
                margin: 0;
                font-size: 1.35rem;
            }

            .page-head p {
                margin: 0.4rem 0 0;
                color: var(--muted);
            }

            .page-body {
                padding: 1.5rem;
            }

            .button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 2.75rem;
                padding: 0.65rem 1rem;
                border: 0;
                border-radius: 0.9rem;
                text-decoration: none;
                font-weight: 600;
                cursor: pointer;
                transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
            }

            .button-primary {
                background: var(--accent);
                color: #fff;
            }

            .button-primary:hover {
                background: #0b625b;
            }

            .button-secondary {
                background: #f8fafc;
                color: var(--text);
                border: 1px solid var(--line);
            }

            .button-secondary:hover {
                background: #eef2f6;
            }

            .panel {
                padding: 1.25rem;
                border: 1px solid var(--line);
                border-radius: 1rem;
                background: var(--panel);
            }

            .panel-head {
                display: grid;
                gap: 0.35rem;
                margin-bottom: 1rem;
            }

            .panel-head h3 {
                margin: 0;
                font-size: 1.05rem;
                color: var(--text);
            }

            .panel-head p {
                margin: 0;
                color: var(--muted);
                line-height: 1.6;
            }

            .form-layout,
            .announcement-form {
                display: grid;
                gap: 1rem;
            }

            .field,
            .form-field {
                display: grid;
                gap: 0.45rem;
            }

            .field-full {
                grid-column: 1 / -1;
            }

            .label,
            .form-label {
                font-size: 0.92rem;
                font-weight: 600;
                color: var(--text);
            }

            .input,
            .select,
            .textarea,
            .form-input,
            .form-select,
            .form-textarea {
                width: 100%;
                min-height: 2.9rem;
                padding: 0.8rem 0.95rem;
                border: 1px solid #cfd8e3;
                border-radius: 0.9rem;
                background: #fff;
                color: var(--text);
                font: inherit;
            }

            .textarea,
            .form-textarea {
                min-height: 9.5rem;
                resize: vertical;
            }

            .input:focus,
            .select:focus,
            .textarea:focus,
            .form-input:focus,
            .form-select:focus,
            .form-textarea:focus {
                outline: 2px solid rgba(15, 118, 110, 0.16);
                border-color: var(--accent);
            }

            .hint {
                color: var(--muted);
                font-size: 0.9rem;
                line-height: 1.55;
            }

            .error-list,
            .field-error-list {
                margin: 0;
                padding: 0.9rem 1rem;
                list-style: none;
                border: 1px solid #f3c8c3;
                border-radius: 0.9rem;
                background: #fff5f4;
                color: #b42318;
            }

            .error-list li + li,
            .field-error-list li + li {
                margin-top: 0.35rem;
            }

            .field-error {
                color: #b42318;
                font-size: 0.9rem;
            }

            .actions,
            .form-actions {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                flex-wrap: wrap;
            }

            .checkbox-field {
                display: flex;
                align-items: center;
                gap: 0.7rem;
                padding: 0.85rem 1rem;
                border: 1px solid #e5ebf1;
                border-radius: 0.9rem;
                background: #fcfaf6;
                color: var(--text);
                font-weight: 600;
            }

            .checkbox-field input {
                width: 1rem;
                height: 1rem;
                margin: 0;
            }

            .alert {
                margin-bottom: 1rem;
                padding: 0.9rem 1rem;
                border-radius: 0.9rem;
                border: 1px solid #b7e4dd;
                background: #ecfdf8;
                color: #0f513f;
            }

            .empty-state {
                padding: 2.25rem 1rem;
                text-align: center;
                border: 1px dashed #cfd8e3;
                border-radius: 1rem;
                background: linear-gradient(180deg, #fbfdff 0%, #f7fafc 100%);
            }

            .empty-state h3 {
                margin: 0;
                color: var(--text);
                font-size: 1.08rem;
            }

            .empty-state p {
                max-width: 32rem;
                margin: 0.75rem auto 0;
                color: var(--muted);
                line-height: 1.65;
            }

            .ticket-number {
                color: #7b8794;
                font-size: 0.79rem;
                font-weight: 400;
                line-height: 1.35;
                white-space: nowrap;
            }

            .subject-title {
                display: block;
                color: var(--text);
                line-height: 1.35;
                font-size: 1rem;
                font-weight: 700;
                overflow-wrap: anywhere;
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

            @media (max-width: 720px) {
                .shell {
                    width: min(100% - 1rem, 100%);
                    padding-top: 1rem;
                }

                .topbar {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .page-head,
                .page-body {
                    padding: 1rem;
                }

                .page-head-bar {
                    flex-direction: column;
                    align-items: stretch;
                }

                .locale-switcher {
                    margin-left: 0;
                    padding-left: 0;
                    border-left: 0;
                }

                .locale-menu {
                    right: auto;
                    left: 0;
                }
            }
        </style>
        @stack('styles')
    </head>
    <body>
        <div class="shell">
            <header class="topbar">
                <div class="brand">
                    <div class="brand-mark">HD</div>
                    <div class="brand-copy">
                        <h1>{{ config('app.name', 'Helpdesk') }}</h1>
                        <p>{{ __('layout.brand.subtitle') }}</p>
                    </div>
                </div>

                <nav class="nav" aria-label="{{ __('layout.nav.main') }}">
                    @php($activeLocale = $currentLocale ?? app()->getLocale())
                    <a class="nav-link {{ request()->routeIs('tickets.index') ? 'active' : '' }}" href="{{ route('tickets.index') }}">
                        {{ __('layout.nav.tickets') }}
                    </a>
                    @if ($canManageAnnouncements ?? false)
                        <a class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}" href="{{ route('announcements.index') }}">
                            {{ __('layout.nav.announcements') }}
                        </a>
                    @endif

                    <details class="locale-switcher">
                        <summary
                            class="locale-toggle"
                            aria-label="{{ __('layout.nav.current', ['locale' => strtoupper($activeLocale)]) }}"
                            title="{{ __('layout.nav.switch') }}"
                        >
                            <span class="locale-current">{{ strtoupper($activeLocale) }}</span>
                            <span class="locale-chevron" aria-hidden="true">▾</span>
                        </summary>

                        <div class="locale-menu" role="menu" aria-label="{{ __('layout.nav.language') }}">
                            @foreach (($supportedLocales ?? config('helpdesk.supported_locales', [])) as $supportedLocale)
                                <form class="locale-form" method="post" action="{{ route('locale.update') }}">
                                    @csrf
                                    <input type="hidden" name="locale" value="{{ $supportedLocale }}">
                                    <button
                                        class="locale-option {{ $activeLocale === $supportedLocale ? 'active' : '' }}"
                                        type="submit"
                                        role="menuitem"
                                        title="{{ __('layout.nav.switch') }}: {{ __('layout.nav.locales.'.$supportedLocale) }}"
                                        aria-pressed="{{ $activeLocale === $supportedLocale ? 'true' : 'false' }}"
                                    >
                                        <span class="locale-option-label">{{ __('layout.nav.locales.'.$supportedLocale) }}</span>
                                        @if ($activeLocale === $supportedLocale)
                                            <span class="locale-option-check" aria-hidden="true">✓</span>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </details>

                    @auth
                        <span class="session-user">{{ $currentUser?->loginName() }}</span>
                        <form class="logout-form" method="post" action="{{ route('logout') }}">
                            @csrf
                            <button class="logout-button" type="submit">{{ __('layout.nav.logout') }}</button>
                        </form>
                    @endauth
                </nav>
            </header>

            <main class="page-card">
                @yield('content')
            </main>
        </div>

        <div
            class="attachment-lightbox"
            data-attachment-lightbox-modal
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('tickets.attachments.lightbox_label') }}"
            hidden
        >
            <div class="attachment-lightbox-dialog">
                <button
                    class="attachment-lightbox-close"
                    type="button"
                    data-attachment-lightbox-close
                    aria-label="{{ __('tickets.attachments.lightbox_close') }}"
                >×</button>
                <button
                    class="attachment-lightbox-nav attachment-lightbox-prev"
                    type="button"
                    data-attachment-lightbox-prev
                    aria-label="{{ __('tickets.attachments.lightbox_previous') }}"
                >‹</button>
                <img class="attachment-lightbox-image" data-attachment-lightbox-image alt="">
                <button
                    class="attachment-lightbox-nav attachment-lightbox-next"
                    type="button"
                    data-attachment-lightbox-next
                    aria-label="{{ __('tickets.attachments.lightbox_next') }}"
                >›</button>
                <div class="attachment-lightbox-caption">
                    <span data-attachment-lightbox-title></span>
                    <span class="attachment-lightbox-position" data-attachment-lightbox-position></span>
                    <a class="attachment-lightbox-download" data-attachment-lightbox-download href="#">
                        {{ __('tickets.attachments.download') }}
                    </a>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const formatFileSize = (bytes) => {
                    if (bytes < 1024) {
                        return `${bytes} B`;
                    }

                    if (bytes < 1024 * 1024) {
                        return `${(bytes / 1024).toFixed(1).replace('.', ',')} KB`;
                    }

                    return `${(bytes / 1024 / 1024).toFixed(1).replace('.', ',')} MB`;
                };

                document.querySelectorAll('[data-attachment-input]').forEach((input) => {
                    const queue = input.parentElement?.querySelector('[data-attachment-queue]');

                    if (! queue || typeof DataTransfer === 'undefined') {
                        return;
                    }

                    const files = [];
                    const maxFiles = Number(input.dataset.maxFiles || 10);
                    const maxSize = Number(input.dataset.maxSize || 0);
                    const allowedExtensions = (input.dataset.allowedExtensions || '')
                        .split(',')
                        .map((extension) => extension.trim().toLowerCase())
                        .filter(Boolean);

                    const fileKey = (file) => `${file.name}:${file.size}:${file.lastModified}`;
                    const isAllowedType = (file) => {
                        if (allowedExtensions.length === 0) {
                            return true;
                        }

                        const extension = file.name.includes('.')
                            ? file.name.split('.').pop().toLowerCase()
                            : '';

                        return allowedExtensions.includes(extension);
                    };
                    const syncInput = () => {
                        const transfer = new DataTransfer();
                        files.forEach((file) => transfer.items.add(file));
                        input.files = transfer.files;
                    };
                    const render = (message = '') => {
                        queue.innerHTML = '';

                        if (message !== '') {
                            const error = document.createElement('div');
                            error.className = 'attachment-queue-error';
                            error.textContent = message;
                            queue.appendChild(error);
                        }

                        if (files.length === 0) {
                            const empty = document.createElement('div');
                            empty.className = 'attachment-queue-empty';
                            empty.textContent = input.dataset.labelEmpty || '';
                            queue.appendChild(empty);

                            return;
                        }

                        const list = document.createElement('ul');
                        list.className = 'attachment-queue-list';

                        files.forEach((file, index) => {
                            const item = document.createElement('li');
                            item.className = 'attachment-queue-item';

                            const name = document.createElement('span');
                            name.className = 'attachment-queue-name';
                            name.textContent = file.name;

                            const size = document.createElement('span');
                            size.className = 'attachment-queue-size';
                            size.textContent = formatFileSize(file.size);

                            const remove = document.createElement('button');
                            remove.className = 'attachment-queue-remove';
                            remove.type = 'button';
                            remove.title = input.dataset.labelRemove || '';
                            remove.setAttribute('aria-label', input.dataset.labelRemove || '');
                            remove.textContent = '×';
                            remove.addEventListener('click', () => {
                                files.splice(index, 1);
                                syncInput();
                                render();
                            });

                            item.append(name, size, remove);
                            list.appendChild(item);
                        });

                        queue.appendChild(list);
                    };

                    input.addEventListener('change', () => {
                        let message = '';
                        const knownFiles = new Set(files.map(fileKey));

                        Array.from(input.files).forEach((file) => {
                            if (files.length >= maxFiles) {
                                message = input.dataset.labelTooMany || '';
                                return;
                            }

                            if (maxSize > 0 && file.size > maxSize) {
                                message = input.dataset.labelTooLarge || '';
                                return;
                            }

                            if (! isAllowedType(file)) {
                                message = input.dataset.labelType || '';
                                return;
                            }

                            const key = fileKey(file);

                            if (! knownFiles.has(key)) {
                                files.push(file);
                                knownFiles.add(key);
                            }
                        });

                        syncInput();
                        render(message);
                    });

                    render();
                });

                const lightbox = document.querySelector('[data-attachment-lightbox-modal]');

                if (lightbox) {
                    const image = lightbox.querySelector('[data-attachment-lightbox-image]');
                    const title = lightbox.querySelector('[data-attachment-lightbox-title]');
                    const download = lightbox.querySelector('[data-attachment-lightbox-download]');
                    const position = lightbox.querySelector('[data-attachment-lightbox-position]');
                    const previousButton = lightbox.querySelector('[data-attachment-lightbox-prev]');
                    const nextButton = lightbox.querySelector('[data-attachment-lightbox-next]');
                    const triggers = Array.from(document.querySelectorAll('[data-attachment-lightbox]'));
                    let currentIndex = 0;
                    let lastFocusedTrigger = null;

                    const showImage = (index) => {
                        if (triggers.length === 0) {
                            return;
                        }

                        currentIndex = (index + triggers.length) % triggers.length;

                        const trigger = triggers[currentIndex];
                        const previewUrl = trigger.dataset.previewUrl;
                        const downloadUrl = trigger.dataset.downloadUrl || trigger.href;
                        const attachmentTitle = trigger.dataset.title || '';

                        image.src = previewUrl;
                        image.alt = attachmentTitle;
                        title.textContent = attachmentTitle;
                        download.href = downloadUrl;
                        position.textContent = `${currentIndex + 1} / ${triggers.length}`;

                        const hasMultipleImages = triggers.length > 1;
                        previousButton.hidden = ! hasMultipleImages;
                        nextButton.hidden = ! hasMultipleImages;
                        previousButton.disabled = ! hasMultipleImages;
                        nextButton.disabled = ! hasMultipleImages;
                    };

                    const close = () => {
                        lightbox.hidden = true;
                        image.removeAttribute('src');
                        image.removeAttribute('alt');

                        if (lastFocusedTrigger instanceof HTMLElement) {
                            lastFocusedTrigger.focus();
                        }
                    };

                    const open = (index, trigger) => {
                        lastFocusedTrigger = trigger;
                        showImage(index);
                        lightbox.hidden = false;
                        lightbox.querySelector('[data-attachment-lightbox-close]')?.focus();
                    };

                    const previous = () => showImage(currentIndex - 1);
                    const next = () => showImage(currentIndex + 1);

                    triggers.forEach((trigger, index) => {
                        trigger.addEventListener('click', (event) => {
                            event.preventDefault();
                            open(index, trigger);
                        });
                    });

                    lightbox.addEventListener('click', (event) => {
                        if (event.target === lightbox) {
                            close();
                        }
                    });

                    lightbox.querySelector('[data-attachment-lightbox-close]')?.addEventListener('click', close);
                    previousButton?.addEventListener('click', previous);
                    nextButton?.addEventListener('click', next);

                    document.addEventListener('keydown', (event) => {
                        if (lightbox.hidden) {
                            return;
                        }

                        if (event.key === 'Escape') {
                            close();
                        }

                        if (event.key === 'ArrowLeft' && triggers.length > 1) {
                            event.preventDefault();
                            previous();
                        }

                        if (event.key === 'ArrowRight' && triggers.length > 1) {
                            event.preventDefault();
                            next();
                        }
                    });
                }
            });
        </script>
        @stack('scripts')
    </body>
</html>
