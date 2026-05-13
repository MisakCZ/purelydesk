# Installation

This guide describes a clean development or test installation after cloning the project from GitHub. It does not assume any organization-specific infrastructure.

## Requirements

- PHP 8.3 or newer, matching `composer.json`.
- Composer.
- MariaDB or MySQL.
- A web server for browser testing, for example Nginx or Apache.
- PHP extensions commonly required by Laravel, including:
  - `ldap`
  - `mbstring`
  - `openssl`
  - `pdo_mysql`
  - `fileinfo`
  - `ctype`
  - `json`
  - `tokenizer`
  - `xml`

## Clone the Repository

```bash
git clone https://github.com/example/helpdesk.git
cd helpdesk
```

Replace the repository URL with the real GitHub URL of your fork or project.

## Install PHP Dependencies

```bash
composer install
```

For production builds use `composer install --no-dev --optimize-autoloader`.

## Create the Environment File

```bash
cp .env.example .env
```

Edit `.env` and configure at least:

- `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `LDAP_*` settings if LDAP login should be active
- `MAIL_*` settings if outgoing notifications should be active

## Generate the Application Key

```bash
php artisan key:generate
```

Never reuse a production `APP_KEY` between installations and never commit it to Git.

## Configure the Database

Create an empty MariaDB/MySQL database and a database user with privileges for that database.

Example values in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=helpdesk
DB_USERNAME=helpdesk
DB_PASSWORD=secret
```

## Run Migrations

```bash
php artisan migrate
```

## Seed Initial Data

```bash
php artisan db:seed
```

This step is required for normal use of the application. Seeders create the basic roles, ticket statuses, priorities, and categories required by the helpdesk workflow. Without these records, forms can show empty role, status, priority, or category lists and ticket creation may not be usable.

The base seeders are written to be idempotent, so they are safe to run again after cleaning or rebuilding a development database:

```bash
php artisan db:seed
```

If you need to restore only selected lookup tables, you can also run the individual seeders:

```bash
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=TicketStatusSeeder
php artisan db:seed --class=TicketPrioritySeeder
php artisan db:seed --class=TicketCategorySeeder
```

## Optional Local Demo Login Without LDAP

Production authentication is LDAP-based. For quick local evaluation after cloning the repository, you can enable an explicit local demo login. It works only when all of these conditions are true:

- `APP_ENV=local` or `APP_ENV=testing`
- `LDAP_ENABLED=false`
- `HELPDESK_DEMO_LOGIN_ENABLED=true`

The demo login is rejected outside local/testing environments, even if it is accidentally enabled in `.env`. Do not use it in production.

Example local `.env` values:

```env
APP_ENV=local
LDAP_ENABLED=false
HELPDESK_DEMO_LOGIN_ENABLED=true
```

Create the demo accounts with:

```bash
php artisan db:seed --class=DemoUserSeeder
```

The seeder creates these active local demo users:

- `admin@example.org` / `password`
- `solver@example.org` / `password`
- `user@example.org` / `password`

The demo users are created with `auth_source=local-demo` and assigned the matching `admin`, `solver`, or `user` role. The seeder also ensures the basic roles, ticket statuses, priorities, and categories exist. It is idempotent and does not delete existing users.

## Run Tests

```bash
php artisan test
```

Tests should pass before you start changing the application.

## Local Development Server

For simple local testing you can use Laravel's built-in server:

```bash
php artisan serve
```

For LDAP and mail integration testing, configure `.env` to point to test services, not production services.
