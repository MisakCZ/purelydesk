# Security

This document summarizes baseline security recommendations for operating the helpdesk.

## Production Debug Settings

Use:

```env
APP_ENV=production
APP_DEBUG=false
```

Never run production with debug mode enabled. Debug output can expose paths, environment values, SQL errors, and other sensitive data.

## HTTPS

Use HTTPS in production. Login credentials, session cookies, ticket contents, comments, and attachment downloads must not travel over plain HTTP.

Set secure cookie options according to your deployment and reverse proxy setup.

## LDAP Security

Use LDAPS or StartTLS in production:

```env
LDAP_ENCRYPTION=ldaps
```

or:

```env
LDAP_ENCRYPTION=starttls
```

Use a read-only LDAP bind account. Do not use a privileged admin account for LDAP search.

The operating system must trust the certificate authority used by the LDAP server certificate.

## Secrets

Do not commit real secrets to Git:

- production `APP_KEY`
- database passwords
- LDAP bind password
- SMTP password
- private server names or internal hostnames

The `.env` file must not be versioned. Keep `.env.example` generic and safe.

## Attachments

Attachments are stored outside the public webroot and served through controller actions. Download and preview actions must check ticket permissions before returning a file.

Do not expose attachment storage as a public directory.

## Ticket Visibility Rules

The application supports:

- `public`: visible to authenticated users according to policy.
- `internal`: visible to requester, solvers, and admins.
- `private`: visible to requester, assignee, and admins.

Watcher records never automatically grant access to private tickets.

Admins can see all tickets, but admin visibility does not automatically mean admins receive every notification. Notification recipient selection is handled separately.

## Backups

Back up:

- database
- non-public attachment storage
- production environment configuration stored outside Git

Encrypt backups if they contain ticket content, personal data, or attachments.

## Dependency Updates

Keep Composer dependencies updated:

```bash
composer outdated
composer update
php artisan test
```

Review Laravel security releases and apply patches promptly.
