# Helpdesk

[![CI](https://github.com/MisakCZ/purelydesk/actions/workflows/ci.yml/badge.svg)](https://github.com/MisakCZ/purelydesk/actions/workflows/ci.yml)

[Česká verze](README.cs.md)

Helpdesk is a Laravel-based internal helpdesk application for organizations that need a simple ticket workflow integrated with LDAP authentication. It is designed as a general open-source friendly application, not as software tied to a specific company, network, or directory structure.

The application uses local user profiles synchronized from LDAP, while authentication and role assignment are driven by an external LDAP-compatible directory.

Continuous integration runs Composer validation, Laravel tests, frontend build, and whitespace checks.

## Features

- LDAP login with local user profile synchronization.
- Role model with `user`, `solver`, and `admin`.
- Role mapping from LDAP groups.
- Ticket visibility levels: `public`, `internal`, and `private`.
- Sensitive request option during ticket creation.
- Ticket comments and separate internal notes.
- Resolved-ticket workflow with requester confirmation and automatic closing.
- Expected resolution deadlines with priority-based defaults, assignee reminders, and requester notifications for manual deadline changes.
- Attachments for tickets and public comments.
- Protected attachment preview and download through Laravel controllers.
- Image attachment lightbox gallery.
- Outgoing e-mail notifications.
- Inbound reply processing is under development and documented as an experimental Maildir-based feature.
- Czech and English UI localization.
- Conventional Laravel Blade UI without a SPA frontend.

## Technology Stack

- Laravel
- PHP 8.4+
- MariaDB or MySQL
- LDAP-compatible directory server
- Nginx or Apache compatible deployment
- PHP-FPM for the documented production deployment scenario

## Documentation

- [Installation](docs/en/installation.md)
- [Deployment](docs/en/deployment.md)
- [LDAP configuration](docs/en/ldap.md)
- [Mail notifications](docs/en/mail.md)
- [Attachments](docs/en/attachments.md)
- [Security](docs/en/security.md)

## Project Information

- [Security policy](SECURITY.md)
- [Contributing](CONTRIBUTING.md)
- [Changelog](CHANGELOG.md)

## Quick Start

```bash
git clone https://github.com/example/helpdesk.git
cd helpdesk
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan test
```

After cloning, update `.env` for your database, LDAP server, mail settings, and application URL. See the documentation links above for details.

## Current Inbound E-mail Status

Inbound e-mail processing is currently documented as an experimental feature. The intended first version processes replies to existing ticket notifications and stores valid replies as public comments.

It does not create new tickets, change ticket status, priority, assignee, category, or visibility, confirm resolved tickets, or import inbound attachments.

Production use depends on correct mail routing to a local MTA/Postfix Maildir and must be tested end to end in the target environment.

## Local Demo Login Without LDAP

PurelyDesk normally authenticates users through LDAP. For quick local evaluation, you can enable the local demo login. This mode is intended only for local development and is automatically disabled outside `local` and `testing` environments.

```env
APP_ENV=local
LDAP_ENABLED=false
HELPDESK_DEMO_LOGIN_ENABLED=true
```

Create the demo users with:

```bash
php artisan db:seed --class=DemoUserSeeder
```

This also ensures the basic roles, statuses, priorities, and categories exist for local evaluation.

Demo accounts:

- `admin@example.org` / `password`
- `solver@example.org` / `password`
- `user@example.org` / `password`

Never use the demo login in production. Production authentication should use LDAP.

## Configuration Overview

The most important configuration areas are:

- `DB_*` for MariaDB/MySQL connection settings.
- `LDAP_*` for LDAP login and role mapping.
- `HELPDESK_DEMO_LOGIN_ENABLED` for explicit local-only demo login without LDAP.
- `MAIL_*` and `HELPDESK_MAIL_NOTIFICATIONS` for outgoing notifications.
- `HELPDESK_INBOUND_*` for optional experimental inbound reply processing through Maildir.
- `HELPDESK_BRAND_LOGO_PATH`, `HELPDESK_BRAND_FALLBACK_TEXT`, and `HELPDESK_BRAND_LOGO_MODE` for optional deployed header branding. Do not commit real internal logos.
- `HELPDESK_RESOLVED_AUTO_CLOSE_DAYS` for automatic closing of resolved tickets.
- `HELPDESK_EXPECTED_RESOLUTION_*` for priority-based expected resolution defaults and due-soon / overdue assignee reminders.
- `HELPDESK_ATTACHMENT_*` for attachment limits and storage path.
- `APP_LOCALE` and `APP_FALLBACK_LOCALE` for the default UI language.

Do not commit real secrets, LDAP bind passwords, production `APP_KEY` values, or environment-specific server names to Git.

## Author

Created and maintained by Michal Hradecký, misak.cz.

Development of this application was assisted by OpenAI Codex.

## License

This project is released under the MIT License. See [LICENSE](LICENSE) for the full license text.
