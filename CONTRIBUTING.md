# Contributing to PurelyDesk

## Development Principles

PurelyDesk aims to stay simple, conventional, and easy to deploy as a Laravel application.

Preferred approach:

- Laravel conventions;
- Blade templates, no SPA frontend;
- MariaDB/MySQL compatibility;
- standard LDAP-compatible authentication without vendor lock-in;
- clear policies and authorization checks;
- public comments separated from internal notes;
- strict ticket visibility rules for public, internal, and private tickets;
- bilingual documentation in English and Czech where applicable;
- minimal dependencies.

Do not add new Composer or npm packages unless the need is clear and justified.

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install --ignore-scripts --no-audit --no-fund
npm run build
```

## Local Demo Login

For local evaluation without LDAP, configure:

```env
APP_ENV=local
LDAP_ENABLED=false
HELPDESK_DEMO_LOGIN_ENABLED=true
```

Create demo users:

```bash
php artisan db:seed --class=DemoUserSeeder
```

Demo login is only for local/testing environments and must not be used in production.

## Running Tests

```bash
php artisan test
npm run build
git diff --check
```

For changed PHP files, run:

```bash
php -l path/to/changed-file.php
```

## Coding Guidelines

- Keep controllers thin where reasonable.
- Use policies for authorization.
- Use services for workflow, authentication, and mail logic when helpful.
- Avoid hard-coding organization-specific infrastructure.
- Keep migrations reversible where possible.
- Keep public documentation anonymized.
- Preserve public/internal/private ticket visibility rules.
- Keep public comments and internal notes separate.

## Pull Request Checklist

Before opening a pull request:

- tests pass;
- frontend build passes;
- `git diff --check` passes;
- no secrets or environment-specific values are committed;
- authorization and ticket visibility rules are preserved;
- English and Czech documentation is updated when user-facing behavior changes;
- no new package is added without clear justification.

## Documentation Rules

Public documentation must not contain:

- internal domains;
- internal IP addresses;
- hostnames;
- LDAP DNs;
- passwords;
- tokens;
- organization-specific infrastructure values.

Use placeholders such as `example.org`, `helpdesk.example.org`, and `192.0.2.10`.
