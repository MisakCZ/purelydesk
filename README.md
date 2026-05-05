# Helpdesk

[Česká verze](README.cs.md)

Helpdesk is a Laravel-based internal helpdesk application for organizations that need a simple ticket workflow integrated with LDAP authentication. It is designed as a general open-source friendly application, not as software tied to a specific company, network, or directory structure.

The application uses local user profiles synchronized from LDAP, while authentication and role assignment are driven by an external LDAP-compatible directory.

## Features

- LDAP login with local user profile synchronization.
- Role model with `user`, `solver`, and `admin`.
- Role mapping from LDAP groups.
- Ticket visibility levels: `public`, `internal`, and `private`.
- Sensitive request option during ticket creation.
- Ticket comments and separate internal notes.
- Resolved-ticket workflow with requester confirmation and automatic closing.
- Attachments for tickets and public comments.
- Protected attachment preview and download through Laravel controllers.
- Image attachment lightbox gallery.
- Outgoing e-mail notifications and inbound replies through local Maildir polling.
- Czech and English UI localization.
- Conventional Laravel Blade UI without a SPA frontend.

## Technology Stack

- Laravel
- PHP 8.3+
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

## Configuration Overview

The most important configuration areas are:

- `DB_*` for MariaDB/MySQL connection settings.
- `LDAP_*` for LDAP login and role mapping.
- `MAIL_*`, `HELPDESK_MAIL_NOTIFICATIONS`, and `HELPDESK_INBOUND_*` for outgoing notifications and optional inbound reply processing.
- `HELPDESK_RESOLVED_AUTO_CLOSE_DAYS` for automatic closing of resolved tickets.
- `HELPDESK_ATTACHMENT_*` for attachment limits and storage path.
- `APP_LOCALE` and `APP_FALLBACK_LOCALE` for the default UI language.

Do not commit real secrets, LDAP bind passwords, production `APP_KEY` values, or environment-specific server names to Git.

## License

This project is distributed as a Laravel application. Use the license file in the repository if one is added by the project owner.
