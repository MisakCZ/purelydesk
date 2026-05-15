<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('app.name', 'Helpdesk'))</title>
        <script>
            (() => {
                const themes = ['default', 'dark', 'pastel', 'contrast'];
                let storedTheme = null;

                try {
                    storedTheme = window.localStorage?.getItem('helpdesk.theme');
                } catch (error) {
                    storedTheme = null;
                }

                document.documentElement.dataset.theme = themes.includes(storedTheme) ? storedTheme : 'default';
            })();
        </script>
        <style>
            :root {
                color-scheme: light;
                --color-bg: #f4f6f8;
                --color-surface: #ffffff;
                --color-surface-muted: #f8fafc;
                --color-text: #13202b;
                --color-muted: #5b6b79;
                --color-border: #d9e0e7;
                --color-primary: #0f766e;
                --color-primary-hover: #0b625b;
                --color-primary-soft: #dff5f2;
                --color-danger: #b42318;
                --color-danger-soft: #fff5f4;
                --color-warning: #b45309;
                --color-warning-soft: #fff7e8;
                --color-success: #15803d;
                --color-success-soft: #ecfdf8;
                --color-field-bg: #ffffff;
                --color-hover: #eef2f6;
                --color-menu-bg: #ffffff;
                --color-brand-gradient-start: #0f766e;
                --color-brand-gradient-end: #155e75;
                --bg: var(--color-bg);
                --panel: var(--color-surface);
                --panel-muted: var(--color-surface-muted);
                --line: var(--color-border);
                --text: var(--color-text);
                --muted: var(--color-muted);
                --accent: var(--color-primary);
                --accent-soft: var(--color-primary-soft);
                --shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            }

            :root[data-theme="dark"] {
                color-scheme: dark;
                --color-bg: #0f172a;
                --color-surface: #172033;
                --color-surface-muted: #111827;
                --color-text: #e5e7eb;
                --color-muted: #a7b0be;
                --color-border: #334155;
                --color-primary: #5eead4;
                --color-primary-hover: #99f6e4;
                --color-primary-soft: rgba(45, 212, 191, 0.16);
                --color-danger: #fca5a5;
                --color-danger-soft: rgba(127, 29, 29, 0.28);
                --color-warning: #fbbf24;
                --color-warning-soft: rgba(146, 64, 14, 0.24);
                --color-success: #86efac;
                --color-success-soft: rgba(20, 83, 45, 0.26);
                --color-field-bg: #0f172a;
                --color-hover: #223047;
                --color-menu-bg: #172033;
                --color-brand-gradient-start: #14b8a6;
                --color-brand-gradient-end: #2563eb;
                --shadow: 0 22px 48px rgba(0, 0, 0, 0.32);
            }

            :root[data-theme="pastel"] {
                color-scheme: light;
                --color-bg: #f7f2ea;
                --color-surface: #fffaf3;
                --color-surface-muted: #f8efe3;
                --color-text: #2d2a26;
                --color-muted: #76695a;
                --color-border: #eadfce;
                --color-primary: #4f8f86;
                --color-primary-hover: #3f756f;
                --color-primary-soft: #dcefeb;
                --color-danger: #b85f62;
                --color-danger-soft: #fdecec;
                --color-warning: #b77b2a;
                --color-warning-soft: #fff0d6;
                --color-success: #5f9468;
                --color-success-soft: #e7f3e7;
                --color-field-bg: #fffdf8;
                --color-hover: #f2eadf;
                --color-menu-bg: #fffaf3;
                --color-brand-gradient-start: #4f8f86;
                --color-brand-gradient-end: #cc8f6a;
                --shadow: 0 20px 42px rgba(105, 85, 62, 0.12);
            }

            :root[data-theme="contrast"] {
                color-scheme: light;
                --color-bg: #e8edf3;
                --color-surface: #ffffff;
                --color-surface-muted: #eef3f8;
                --color-text: #0b1220;
                --color-muted: #354052;
                --color-border: #9aa8b8;
                --color-primary: #0f4c81;
                --color-primary-hover: #0b3d68;
                --color-primary-soft: #d9eafa;
                --color-danger: #9f1239;
                --color-danger-soft: #ffe4e6;
                --color-warning: #92400e;
                --color-warning-soft: #fef3c7;
                --color-success: #166534;
                --color-success-soft: #dcfce7;
                --color-field-bg: #ffffff;
                --color-hover: #dbe5ee;
                --color-menu-bg: #ffffff;
                --color-brand-gradient-start: #0f4c81;
                --color-brand-gradient-end: #111827;
                --shadow: 0 20px 45px rgba(11, 18, 32, 0.14);
            }

            * {
                box-sizing: border-box;
            }

            html {
                scrollbar-gutter: stable;
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
                    linear-gradient(180deg, var(--panel-muted) 0%, var(--bg) 100%);
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
                display: grid;
                grid-template-columns: minmax(12.5rem, 0.68fr) auto max-content;
                align-items: center;
                column-gap: 0.82rem;
                margin-bottom: 1.7rem;
                padding: 0.88rem 0.95rem;
                border: 1px solid color-mix(in srgb, var(--line) 42%, transparent);
                border-radius: 1.85rem;
                background: color-mix(in srgb, var(--panel) 94%, rgba(255, 255, 255, 0.84));
                box-shadow:
                    0 18px 42px rgba(15, 23, 42, 0.07),
                    inset 0 1px 0 color-mix(in srgb, #fff 72%, transparent);
                position: relative;
            }

            .brand {
                display: flex;
                align-items: center;
                gap: 0.72rem;
                min-width: 0;
                max-width: min(100%, 22rem);
                color: inherit;
                text-decoration: none;
            }

            .brand--wide {
                align-items: center;
                display: flex;
                gap: 0.36rem;
                min-width: 0;
                max-width: min(100%, 24rem);
            }

            .brand-media {
                display: inline-flex;
                align-items: center;
                min-width: 0;
                flex: 0 0 auto;
            }

            .brand-mark {
                width: 2.72rem;
                height: 2.72rem;
                border-radius: 0.92rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                background: linear-gradient(135deg, var(--color-brand-gradient-start), var(--color-brand-gradient-end));
                color: #fff;
                box-shadow: 0 12px 26px color-mix(in srgb, var(--accent) 18%, transparent);
            }

            .brand-logo {
                display: block;
                width: auto;
                height: auto;
                object-fit: contain;
                flex: 0 1 auto;
            }

            .brand-logo-mark {
                max-width: 2.72rem;
                max-height: 2.72rem;
            }

            .brand-logo-wide {
                min-width: min(100%, 11rem);
                max-width: 15.5rem;
                max-height: 2.72rem;
            }

            .brand-copy {
                flex: 0 0 auto;
                min-width: max-content;
                padding-left: 0.72rem;
                border-left: 1px solid color-mix(in srgb, var(--line) 52%, transparent);
            }

            .brand-copy h1 {
                margin: 0;
                overflow: visible;
                font-size: 1.08rem;
                font-weight: 760;
                line-height: 1.1;
                text-overflow: clip;
                white-space: nowrap;
            }

            .brand-copy p {
                margin: 0.2rem 0 0;
                color: var(--muted);
                font-size: 0.82rem;
                font-weight: 600;
                line-height: 1.25;
                white-space: nowrap;
            }

            .brand--wide .brand-copy p {
                margin-top: 0;
                font-size: 0.82rem;
                line-height: 1.25;
            }

            .brand--wide .brand-copy {
                min-width: 0;
            }

            .nav {
                display: flex;
                justify-content: center;
                gap: 0.16rem;
                flex-wrap: nowrap;
                align-items: center;
                min-width: max-content;
                justify-self: end;
                padding: 0;
                border: 1px solid color-mix(in srgb, var(--line) 36%, transparent);
                border-radius: 999px;
                background: color-mix(in srgb, var(--panel) 82%, var(--panel-muted));
                box-shadow:
                    0 8px 20px rgba(15, 23, 42, 0.045),
                    inset 0 1px 0 color-mix(in srgb, #fff 72%, transparent);
            }

            .nav-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 0.26rem;
                min-width: max-content;
                padding-left: 0.82rem;
                border-left: 1px solid color-mix(in srgb, var(--line) 48%, transparent);
            }

            .mobile-nav {
                display: none;
                position: relative;
                margin-left: auto;
            }

            .mobile-nav-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2.6rem;
                height: 2.6rem;
                border: 1px solid var(--line);
                border-radius: 0.85rem;
                background: var(--panel);
                color: var(--text);
                cursor: pointer;
                list-style: none;
                box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
            }

            .mobile-nav-toggle::-webkit-details-marker {
                display: none;
            }

            .mobile-nav-lines {
                display: grid;
                gap: 0.25rem;
                width: 1.05rem;
            }

            .mobile-nav-lines span {
                display: block;
                height: 2px;
                border-radius: 999px;
                background: currentColor;
            }

            .mobile-nav-panel {
                position: absolute;
                top: calc(100% + 0.55rem);
                right: 0;
                z-index: 80;
                display: grid;
                gap: 0.55rem;
                width: min(20rem, calc(100vw - 1rem));
                padding: 0.65rem;
                border: 1px solid var(--line);
                border-radius: 1rem;
                background: var(--color-menu-bg);
                box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
            }

            .mobile-nav-section {
                display: grid;
                gap: 0.35rem;
                padding-top: 0.55rem;
                border-top: 1px solid var(--line);
            }

            .mobile-nav-section:first-child {
                padding-top: 0;
                border-top: 0;
            }

            .mobile-nav-link,
            .mobile-nav-account {
                display: flex;
                align-items: center;
                justify-content: space-between;
                min-height: 2.45rem;
                padding: 0.55rem 0.7rem;
                border-radius: 0.75rem;
                color: var(--muted);
                text-decoration: none;
                font-size: 0.92rem;
                font-weight: 650;
            }

            .mobile-nav-link.active {
                color: var(--accent);
                background: var(--accent-soft);
            }

            .mobile-nav-link:hover {
                color: var(--text);
                background: var(--color-hover);
            }

            .mobile-nav-caption {
                padding: 0 0.15rem;
                color: var(--muted);
                font-size: 0.72rem;
                font-weight: 800;
                letter-spacing: 0.05em;
                text-transform: uppercase;
            }

            .mobile-nav .locale-form,
            .mobile-nav .logout-form {
                width: 100%;
            }

            .mobile-nav .logout-button {
                width: 100%;
                border-radius: 0.75rem;
                text-align: left;
            }

            .nav-link {
                display: inline-flex;
                align-items: center;
                gap: 0.34rem;
                min-height: 2.48rem;
                padding: 0.56rem 0.76rem;
                border-radius: 999px;
                text-decoration: none;
                color: var(--muted);
                background: transparent;
                border: 0;
                font-size: 0.89rem;
                font-weight: 700;
                white-space: nowrap;
                transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
            }

            .nav-link.active {
                color: var(--accent);
                background: linear-gradient(135deg, color-mix(in srgb, var(--accent-soft) 92%, var(--panel)), color-mix(in srgb, var(--accent-soft) 64%, var(--panel)));
                box-shadow:
                    inset 0 1px 0 color-mix(in srgb, #fff 72%, transparent),
                    0 8px 18px color-mix(in srgb, var(--accent) 7%, transparent);
            }

            .nav-link:hover {
                color: var(--text);
                background: color-mix(in srgb, var(--panel-muted) 72%, transparent);
            }

            .nav-link-icon,
            .control-icon,
            .logout-icon,
            .user-avatar {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
            }

            .nav-link-icon svg,
            .control-icon svg,
            .logout-icon svg {
                width: 0.94rem;
                height: 0.94rem;
            }

            .nav-link-icon,
            .control-icon,
            .logout-icon {
                opacity: 0.58;
            }

            .nav-link.active .nav-link-icon,
            .locale-toggle:hover .control-icon,
            .theme-toggle:hover .control-icon,
            .logout-button:hover .logout-icon {
                opacity: 0.86;
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
                color: var(--muted);
            }

            .attachment-queue-error {
                color: var(--color-danger);
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
                border: 1px solid var(--line);
                border-radius: 0.7rem;
                background: var(--panel-muted);
                color: var(--text);
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
                color: var(--muted);
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
                background: var(--color-danger-soft);
                color: var(--color-danger);
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

            .locale-switcher,
            .theme-switcher {
                position: relative;
            }

            .locale-switcher,
            .theme-switcher,
            .session-user,
            .logout-form {
                position: relative;
            }

            .theme-switcher::before,
            .session-user::before,
            .logout-form::before {
                content: "";
                position: absolute;
                top: 50%;
                left: -0.15rem;
                width: 1px;
                height: 1.45rem;
                background: color-mix(in srgb, var(--line) 42%, transparent);
                transform: translateY(-50%);
            }

            .locale-switcher[open],
            .theme-switcher[open] {
                z-index: 620;
            }

            .locale-toggle,
            .theme-toggle {
                display: inline-flex;
                align-items: center;
                gap: 0.32rem;
                min-height: 2.32rem;
                padding: 0.38rem 0.58rem;
                border: 1px solid transparent;
                border-radius: 999px;
                background: transparent;
                color: var(--muted);
                cursor: pointer;
                font: inherit;
                font-size: 0.76rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                transition: color 0.15s ease;
                list-style: none;
            }

            .locale-toggle::-webkit-details-marker,
            .theme-toggle::-webkit-details-marker {
                display: none;
            }

            .locale-toggle:hover,
            .theme-toggle:hover {
                color: var(--text);
                background: color-mix(in srgb, var(--panel-muted) 68%, transparent);
            }

            .locale-current,
            .theme-current {
                color: var(--text);
                line-height: 1;
            }

            .locale-chevron,
            .theme-chevron {
                color: #94a3b8;
                font-size: 0.7rem;
                line-height: 1;
                transition: transform 0.15s ease;
            }

            .locale-switcher[open] .locale-chevron,
            .theme-switcher[open] .theme-chevron {
                transform: rotate(180deg);
            }

            .locale-menu,
            .theme-menu {
                position: absolute;
                top: calc(100% + 0.45rem);
                right: 0;
                min-width: 8.75rem;
                padding: 0.35rem;
                border: 1px solid var(--line);
                border-radius: 0.9rem;
                background: var(--color-menu-bg);
                box-shadow: 0 16px 32px rgba(15, 23, 42, 0.12);
                font-size: 0.86rem;
                z-index: 640;
            }

            .locale-form {
                margin: 0;
            }

            .locale-option,
            .theme-option {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                min-height: 2.15rem;
                padding: 0.44rem 0.62rem;
                border: 0;
                border-radius: 0.65rem;
                background: transparent;
                color: var(--muted);
                cursor: pointer;
                font: inherit;
                font-size: 0.86rem;
                text-align: left;
                transition: background-color 0.15s ease, color 0.15s ease;
            }

            .locale-option:hover,
            .theme-option:hover {
                background: var(--color-hover);
                color: var(--text);
            }

            .locale-option.active,
            .theme-option.active {
                background: var(--accent-soft);
                color: var(--text);
                font-weight: 700;
            }

            .locale-option-label,
            .theme-option-label {
                font-size: 0.86rem;
                line-height: 1.3;
            }

            .locale-option-check,
            .theme-option-check {
                color: var(--accent);
                font-size: 0.76rem;
                line-height: 1;
            }

            .session-user {
                display: inline-flex;
                align-items: center;
                gap: 0.36rem;
                min-height: 2.32rem;
                padding: 0.26rem 0.58rem 0.26rem 0.3rem;
                border: 1px solid transparent;
                border-radius: 999px;
                background: transparent;
                color: var(--muted);
                font-size: 0.82rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .user-avatar {
                width: 1.72rem;
                height: 1.72rem;
                border-radius: 999px;
                background: linear-gradient(135deg, var(--color-brand-gradient-start), var(--color-brand-gradient-end));
                color: #fff;
                font-size: 0.72rem;
                font-weight: 850;
                line-height: 1;
            }

            .logout-form {
                margin: 0;
            }

            .logout-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.34rem;
                min-height: 2.32rem;
                padding: 0.38rem 0.62rem;
                border: 1px solid color-mix(in srgb, var(--line) 72%, transparent);
                border-radius: 999px;
                background: color-mix(in srgb, var(--panel) 72%, transparent);
                color: var(--muted);
                cursor: pointer;
                font: inherit;
                font-size: 0.82rem;
                font-weight: 600;
            }

            .logout-button:hover {
                color: var(--text);
                background: color-mix(in srgb, var(--panel-muted) 72%, transparent);
            }

            @media (max-width: 1100px) {
                .topbar {
                    grid-template-columns: minmax(0, 1fr) auto;
                }

                .brand {
                    max-width: 100%;
                }

                .nav,
                .nav-actions {
                    display: none;
                }

                .mobile-nav {
                    display: block;
                }
            }

            .page-card {
                background: var(--panel);
                border: 1px solid rgba(217, 224, 231, 0.9);
                border-radius: 1.25rem;
                box-shadow: var(--shadow);
                overflow: hidden;
            }

            .site-footer {
                margin-top: 1rem;
                color: var(--muted);
                font-size: 0.82rem;
                line-height: 1.5;
                text-align: center;
            }

            .site-footer a {
                color: var(--accent);
                font-weight: 650;
                text-decoration: none;
            }

            .site-footer a:hover {
                text-decoration: underline;
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
                background: var(--color-primary-hover);
            }

            .button-secondary {
                background: var(--panel-muted);
                color: var(--text);
                border: 1px solid var(--line);
            }

            .button-secondary:hover {
                background: var(--color-hover);
            }

            .app-action {
                display: inline-flex;
                align-items: center;
                gap: 0.62rem;
                min-height: 2.85rem;
                padding: 0.54rem 0.78rem 0.54rem 0.62rem;
                border: 1px solid color-mix(in srgb, var(--color-success, #15803d) 26%, var(--color-border, #bbf7d0));
                border-radius: 999px;
                background: linear-gradient(145deg, color-mix(in srgb, var(--color-success, #15803d) 12%, var(--color-surface, #fff)), color-mix(in srgb, var(--color-surface, #fff) 94%, transparent));
                color: var(--color-primary, #0f766e);
                font-size: 0.9rem;
                font-weight: 800;
                line-height: 1.2;
                text-decoration: none;
                box-shadow: 0 14px 32px rgba(15, 23, 42, 0.055);
                transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            }

            .app-action:hover,
            .app-action:focus-visible {
                border-color: color-mix(in srgb, var(--color-success, #15803d) 42%, var(--color-border, #bbf7d0));
                background: linear-gradient(145deg, color-mix(in srgb, var(--color-success, #15803d) 12%, var(--color-surface, #fff)), color-mix(in srgb, var(--color-surface, #fff) 94%, transparent));
                color: var(--color-success, #15803d);
                transform: translateY(-1px);
                box-shadow: 0 18px 38px rgba(15, 23, 42, 0.09);
            }

            .app-action-icon {
                display: grid;
                place-items: center;
                width: 2rem;
                height: 2rem;
                border-radius: 999px;
                background: color-mix(in srgb, var(--color-success, #15803d) 18%, var(--color-surface, #fff));
                color: var(--color-success, #15803d);
                flex: 0 0 auto;
            }

            .app-action-icon svg {
                width: 1.08rem;
                height: 1.08rem;
            }

            .app-action.button-compact,
            .app-action.button-small {
                min-height: 2.55rem;
                padding: 0.42rem 0.72rem 0.42rem 0.52rem;
                border-radius: 999px;
            }

            .app-action.button-compact .app-action-icon,
            .app-action.button-small .app-action-icon {
                width: 1.7rem;
                height: 1.7rem;
            }

            .app-action.button-compact .app-action-icon svg,
            .app-action.button-small .app-action-icon svg {
                width: 0.94rem;
                height: 0.94rem;
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
                border: 1px solid var(--line);
                border-radius: 0.9rem;
                background: var(--color-field-bg);
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
                border: 1px solid color-mix(in srgb, var(--color-danger) 35%, var(--line));
                border-radius: 0.9rem;
                background: var(--color-danger-soft);
                color: #b42318;
            }

            .error-list li + li,
            .field-error-list li + li {
                margin-top: 0.35rem;
            }

            .field-error {
                color: var(--color-danger);
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
                border: 1px solid var(--line);
                border-radius: 0.9rem;
                background: var(--panel-muted);
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
                border: 1px solid color-mix(in srgb, var(--color-success) 35%, var(--line));
                background: var(--color-success-soft);
                color: var(--color-success);
            }

            .empty-state {
                padding: 2.25rem 1rem;
                text-align: center;
                border: 1px dashed var(--line);
                border-radius: 1rem;
                background: linear-gradient(180deg, var(--panel) 0%, var(--panel-muted) 100%);
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
                color: var(--muted);
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
                background: var(--color-hover);
                color: var(--text);
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

            :root:not([data-theme="default"]) .filter-card,
            :root:not([data-theme="default"]) .announcement-card,
            :root:not([data-theme="default"]) .pinned-section,
            :root:not([data-theme="default"]) .pinned-ticket,
            :root:not([data-theme="default"]) .dashboard-summary-card,
            :root:not([data-theme="default"]) .dashboard-announcement,
            :root:not([data-theme="default"]) .dashboard-section,
            :root:not([data-theme="default"]) .dashboard-ticket,
            :root:not([data-theme="default"]) .ticket-row,
            :root:not([data-theme="default"]) .ticket-card,
            :root:not([data-theme="default"]) .ticket-mobile-card,
            :root:not([data-theme="default"]) .ticket-panel,
            :root:not([data-theme="default"]) .comment-card,
            :root:not([data-theme="default"]) .note-card,
            :root:not([data-theme="default"]) .history-card,
            :root:not([data-theme="default"]) .attachment-card,
            :root:not([data-theme="default"]) .login-card {
                border-color: var(--line) !important;
                background: var(--panel) !important;
                color: var(--text) !important;
            }

            :root:not([data-theme="default"]) .announcement-title,
            :root:not([data-theme="default"]) .announcement-body,
            :root:not([data-theme="default"]) .dashboard-announcements-title,
            :root:not([data-theme="default"]) .dashboard-summary-count,
            :root:not([data-theme="default"]) .comment-author,
            :root:not([data-theme="default"]) .comment-body,
            :root:not([data-theme="default"]) .ticket-detail-title {
                color: var(--text) !important;
            }

            :root:not([data-theme="default"]) .announcement-meta,
            :root:not([data-theme="default"]) .dashboard-summary-label,
            :root:not([data-theme="default"]) .dashboard-action-note,
            :root:not([data-theme="default"]) .comment-time,
            :root:not([data-theme="default"]) .ticket-meta,
            :root:not([data-theme="default"]) .ticket-secondary,
            :root:not([data-theme="default"]) .attachment-meta {
                color: var(--muted) !important;
            }

            :root:not([data-theme="default"]) input,
            :root:not([data-theme="default"]) select,
            :root:not([data-theme="default"]) textarea {
                border-color: var(--line);
                background: var(--color-field-bg);
                color: var(--text);
            }

            :root[data-theme="dark"] .badge,
            :root[data-theme="dark"] .badge-tone-slate {
                background: #243145;
                color: #dbe4ee;
            }

            @media (max-width: 768px) {
                .shell {
                    width: min(100% - 1rem, 100%);
                    padding-top: 1rem;
                }

                .topbar {
                    align-items: center;
                    gap: 0.65rem;
                    margin-bottom: 0.8rem;
                    padding: 0.58rem;
                    border-radius: 1.05rem;
                }

                .brand {
                    max-width: 100%;
                    flex: 1 1 auto;
                }

                .brand--wide {
                    max-width: 100%;
                }

                .brand-mark,
                .brand-logo-mark {
                    max-width: 2.25rem;
                    max-height: 2.25rem;
                }

                .brand-logo-wide {
                    max-width: 11.25rem;
                    max-height: 2.25rem;
                }

                .brand-copy h1 {
                    font-size: 1rem;
                }

                .brand-copy p {
                    display: none;
                }

                .brand--wide .brand-copy p {
                    display: none;
                }

                .brand--wide .brand-copy {
                    padding-left: 0.5rem;
                }

                .nav {
                    display: none;
                }

                .mobile-nav {
                    flex: 0 0 auto;
                }

                .page-head,
                .page-body {
                    padding: 1rem;
                }

                .page-head-bar {
                    flex-direction: column;
                    align-items: stretch;
                }

                .locale-switcher,
                .theme-switcher {
                    margin-left: 0;
                    padding-left: 0;
                    border-left: 0;
                }

                .locale-menu,
                .theme-menu {
                    right: auto;
                    left: 0;
                }
            }
        </style>
        @stack('styles')
    </head>
    <body>
        @php
            $brandLogoPath = trim((string) config('helpdesk.brand.logo_path', ''));
            $brandFallbackText = trim((string) config('helpdesk.brand.fallback_text', 'HD')) ?: 'HD';
            $configuredBrandLogoMode = trim((string) config('helpdesk.brand.logo_mode', 'mark'));
            $brandLogoMode = in_array($configuredBrandLogoMode, ['mark', 'wide'], true) ? $configuredBrandLogoMode : 'mark';
            $footerCopyrightHtml = trim((string) config('helpdesk.footer.copyright_html', ''));
            $availableThemes = ['default', 'dark', 'pastel', 'contrast'];
            $userInitial = mb_strtoupper(mb_substr($currentUser?->loginName() ?? '?', 0, 1));
        @endphp
        <div class="shell">
            <header class="topbar">
                <a class="brand {{ $brandLogoPath !== '' && $brandLogoMode === 'wide' ? 'brand--wide' : '' }}" href="{{ route('dashboard') }}" aria-label="{{ config('app.name', 'Helpdesk') }}">
                    <div class="brand-media">
                        @if ($brandLogoPath !== '')
                            <img
                                class="brand-logo {{ $brandLogoMode === 'wide' ? 'brand-logo-wide' : 'brand-logo-mark' }}"
                                src="{{ $brandLogoPath }}"
                                alt="{{ config('app.name', 'Helpdesk') }}"
                                onerror="this.hidden=true;this.nextElementSibling.hidden=false;"
                            >
                            <div class="brand-mark" hidden>{{ $brandFallbackText }}</div>
                        @else
                            <div class="brand-mark">{{ $brandFallbackText }}</div>
                        @endif
                    </div>
                    <div class="brand-copy">
                        <h1>{{ config('app.name', 'Helpdesk') }}</h1>
                        <p>{{ __('layout.brand.subtitle') }}</p>
                    </div>
                </a>

                <nav class="nav" aria-label="{{ __('layout.nav.main') }}">
                    @php($activeLocale = $currentLocale ?? app()->getLocale())
                    @auth
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <span class="nav-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="4" y="4" width="6.5" height="6.5" rx="1.4"></rect>
                                    <rect x="13.5" y="4" width="6.5" height="6.5" rx="1.4"></rect>
                                    <rect x="4" y="13.5" width="6.5" height="6.5" rx="1.4"></rect>
                                    <rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.4"></rect>
                                </svg>
                            </span>
                            <span>{{ __('layout.nav.dashboard') }}</span>
                        </a>
                        <a class="nav-link {{ request()->routeIs('tickets.index') ? 'active' : '' }}" href="{{ route('tickets.index') }}">
                            <span class="nav-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 4h7l3 3v13H7z"></path>
                                    <path d="M14 4v4h4"></path>
                                    <path d="M9.5 12h5"></path>
                                    <path d="M9.5 16h4"></path>
                                </svg>
                            </span>
                            <span>{{ __('layout.nav.tickets') }}</span>
                        </a>
                    @endauth
                    @if ($canManageAnnouncements ?? false)
                        <a class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}" href="{{ route('announcements.index') }}">
                            <span class="nav-link-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 9a6 6 0 1 0-12 0c0 7-2.5 7-2.5 7h17S18 16 18 9"></path>
                                    <path d="M10 20a2.4 2.4 0 0 0 4 0"></path>
                                </svg>
                            </span>
                            <span>{{ __('layout.nav.announcements') }}</span>
                        </a>
                    @endif
                </nav>

                <div class="nav-actions">
                    <details class="locale-switcher">
                        <summary
                            class="locale-toggle"
                            aria-label="{{ __('layout.nav.current', ['locale' => strtoupper($activeLocale)]) }}"
                            title="{{ __('layout.nav.switch') }}"
                        >
                            <span class="control-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M3.6 9h16.8"></path>
                                    <path d="M3.6 15h16.8"></path>
                                    <path d="M12 3a14 14 0 0 1 0 18"></path>
                                    <path d="M12 3a14 14 0 0 0 0 18"></path>
                                </svg>
                            </span>
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

                    <details class="theme-switcher" data-theme-switcher>
                        <summary
                            class="theme-toggle"
                            aria-label="{{ __('layout.nav.theme_current', ['theme' => __('layout.nav.themes.default')]) }}"
                            title="{{ __('layout.nav.theme_switch') }}"
                            data-theme-current-label="{{ __('layout.nav.theme_current', ['theme' => '__THEME__']) }}"
                        >
                            <span class="control-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3 4.8 6v5.4c0 4.4 3 7.6 7.2 9.1 4.2-1.5 7.2-4.7 7.2-9.1V6z"></path>
                                </svg>
                            </span>
                            <span class="theme-current" data-theme-current>{{ __('layout.nav.themes.default') }}</span>
                            <span class="theme-chevron" aria-hidden="true">▾</span>
                        </summary>

                        <div class="theme-menu" role="menu" aria-label="{{ __('layout.nav.theme') }}">
                            @foreach ($availableThemes as $theme)
                                <button
                                    class="theme-option"
                                    type="button"
                                    role="menuitemradio"
                                    aria-checked="false"
                                    data-theme-option="{{ $theme }}"
                                    data-theme-label="{{ __('layout.nav.themes.'.$theme) }}"
                                >
                                    <span class="theme-option-label">{{ __('layout.nav.themes.'.$theme) }}</span>
                                    <span class="theme-option-check" aria-hidden="true" hidden>✓</span>
                                </button>
                            @endforeach
                        </div>
                    </details>

                    @auth
                        <span class="session-user">
                            <span class="user-avatar" aria-hidden="true">{{ $userInitial }}</span>
                            <span>{{ $currentUser?->loginName() }}</span>
                        </span>
                        <form class="logout-form" method="post" action="{{ route('logout') }}">
                            @csrf
                            <button class="logout-button" type="submit">
                                <span class="logout-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10 17l5-5-5-5"></path>
                                        <path d="M15 12H3"></path>
                                        <path d="M13 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"></path>
                                    </svg>
                                </span>
                                <span>{{ __('layout.nav.logout') }}</span>
                            </button>
                        </form>
                    @endauth
                </div>

                <details class="mobile-nav">
                    <summary class="mobile-nav-toggle" aria-label="{{ __('layout.nav.mobile_menu') }}">
                        <span class="mobile-nav-lines" aria-hidden="true">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </summary>

                    <div class="mobile-nav-panel">
                        <div class="mobile-nav-section">
                            @auth
                                <a class="mobile-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                    {{ __('layout.nav.dashboard') }}
                                </a>
                                <a class="mobile-nav-link {{ request()->routeIs('tickets.index') ? 'active' : '' }}" href="{{ route('tickets.index') }}">
                                    {{ __('layout.nav.tickets') }}
                                </a>
                            @endauth
                            <a class="mobile-nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}" href="{{ route('announcements.active') }}">
                                {{ __('layout.nav.announcements') }}
                            </a>
                        </div>

                        <div class="mobile-nav-section" aria-label="{{ __('layout.nav.language') }}">
                            <div class="mobile-nav-caption">{{ __('layout.nav.language') }}</div>
                            @foreach (($supportedLocales ?? config('helpdesk.supported_locales', [])) as $supportedLocale)
                                <form class="locale-form" method="post" action="{{ route('locale.update') }}">
                                    @csrf
                                    <input type="hidden" name="locale" value="{{ $supportedLocale }}">
                                    <button
                                        class="locale-option {{ $activeLocale === $supportedLocale ? 'active' : '' }}"
                                        type="submit"
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

                        <div class="mobile-nav-section" data-theme-switcher aria-label="{{ __('layout.nav.theme') }}">
                            <div class="mobile-nav-caption">{{ __('layout.nav.theme') }}</div>
                            @foreach ($availableThemes as $theme)
                                <button
                                    class="theme-option"
                                    type="button"
                                    role="menuitemradio"
                                    aria-checked="false"
                                    data-theme-option="{{ $theme }}"
                                    data-theme-label="{{ __('layout.nav.themes.'.$theme) }}"
                                >
                                    <span class="theme-option-label">{{ __('layout.nav.themes.'.$theme) }}</span>
                                    <span class="theme-option-check" aria-hidden="true" hidden>✓</span>
                                </button>
                            @endforeach
                        </div>

                        @auth
                            <div class="mobile-nav-section">
                                <span class="mobile-nav-account">{{ $currentUser?->loginName() }}</span>
                                <form class="logout-form" method="post" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="logout-button" type="submit">{{ __('layout.nav.logout') }}</button>
                                </form>
                            </div>
                        @endauth
                    </div>
                </details>
            </header>

            <main class="page-card">
                @yield('content')
            </main>

            @if ($footerCopyrightHtml !== '')
                <footer class="site-footer">
                    {!! $footerCopyrightHtml !!}
                </footer>
            @endif
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
                const themeSwitchers = Array.from(document.querySelectorAll('[data-theme-switcher]'));

                if (themeSwitchers.length > 0) {
                    const themes = ['default', 'dark', 'pastel', 'contrast'];
                    const normalizeTheme = (theme) => themes.includes(theme) ? theme : 'default';
                    const storedTheme = () => {
                        try {
                            return window.localStorage?.getItem('helpdesk.theme');
                        } catch (error) {
                            return null;
                        }
                    };
                    const storeTheme = (theme) => {
                        try {
                            window.localStorage?.setItem('helpdesk.theme', theme);
                        } catch (error) {
                            // Theme switching remains usable even when localStorage is blocked.
                        }
                    };
                    const applyTheme = (theme) => {
                        const activeTheme = normalizeTheme(theme);

                        document.documentElement.dataset.theme = activeTheme;
                        storeTheme(activeTheme);

                        themeSwitchers.forEach((themeSwitcher) => {
                            const current = themeSwitcher.querySelector('[data-theme-current]');
                            const summary = themeSwitcher.querySelector('.theme-toggle');
                            const summaryLabel = summary?.dataset.themeCurrentLabel || '';
                            const options = Array.from(themeSwitcher.querySelectorAll('[data-theme-option]'));
                            const activeOption = options.find((option) => option.dataset.themeOption === activeTheme);
                            const label = activeOption?.dataset.themeLabel || activeTheme;

                            if (current) {
                                current.textContent = label;
                            }

                            if (summary && summaryLabel !== '') {
                                summary.setAttribute('aria-label', summaryLabel.replace('__THEME__', label));
                            }

                            options.forEach((option) => {
                                const isActive = option.dataset.themeOption === activeTheme;
                                option.classList.toggle('active', isActive);
                                option.setAttribute('aria-checked', isActive ? 'true' : 'false');
                                option.querySelector('.theme-option-check').hidden = ! isActive;
                            });
                        });
                    };

                    themeSwitchers.forEach((themeSwitcher) => {
                        themeSwitcher.querySelectorAll('[data-theme-option]').forEach((option) => {
                            option.addEventListener('click', () => {
                                applyTheme(option.dataset.themeOption);
                                themeSwitcher.removeAttribute('open');
                                themeSwitcher.closest('.mobile-nav')?.removeAttribute('open');
                            });
                        });
                    });

                    applyTheme(document.documentElement.dataset.theme || storedTheme());
                }

                const mobileNav = document.querySelector('.mobile-nav');

                if (mobileNav) {
                    document.addEventListener('click', (event) => {
                        if (mobileNav.open && ! mobileNav.contains(event.target)) {
                            mobileNav.removeAttribute('open');
                        }
                    });

                    document.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape') {
                            mobileNav.removeAttribute('open');
                        }
                    });
                }

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
