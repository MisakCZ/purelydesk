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
            }

            .locale-form {
                margin: 0;
            }

            .locale-option {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                min-height: 2.3rem;
                padding: 0.48rem 0.65rem;
                border: 0;
                border-radius: 0.65rem;
                background: transparent;
                color: #475569;
                cursor: pointer;
                font: inherit;
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
                line-height: 1.3;
            }

            .locale-option-check {
                color: var(--accent);
                font-size: 0.82rem;
                line-height: 1;
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
                </nav>
            </header>

            <main class="page-card">
                @yield('content')
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
