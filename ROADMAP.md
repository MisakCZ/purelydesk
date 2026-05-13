# Roadmap

PurelyDesk is an early public version of a Laravel-based helpdesk application. This roadmap is a planning document, not a promise of delivery dates.

Priorities may change based on real deployments, security review, and community feedback.

## Current Focus

- Documentation cleanup for public release.
- Safe public configuration examples.
- Screenshots and onboarding materials.
- Stable CI for Composer validation, Laravel tests, frontend build, and whitespace checks.
- Security and contribution guidelines.
- Keeping the first release simple, understandable, and deployable.

## Near-Term Improvements

- Screenshots and visual overview of the application.
- Better first-run and local evaluation instructions.
- More complete demo data for local testing.
- Notification matrix documentation: event, recipients, conditions, and configuration.
- Additional tests around outbound e-mail notifications.
- More explicit documentation for roles, permissions, and ticket visibility.
- Review of public/internal/private visibility edge cases.
- Better admin-facing overview pages for operational status.
- Improved documentation for production deployment.

## E-mail Roadmap

Outgoing e-mail notifications are part of the normal feature set.

Inbound Maildir reply processing is experimental and under development. The first intended inbound version only adds public comments to existing tickets.

Inbound e-mail processing does not:

- create new tickets;
- change status, priority, assignee, category, or visibility;
- confirm or reject resolved-ticket workflow;
- import inbound attachments.

Attachments must be uploaded through the web UI.

Production inbound use requires end-to-end validation of mail routing, local MTA/Postfix, Maildir permissions, and security gateway behavior.

Future ideas:

- optional additional inbound drivers, for example IMAP, only if justified;
- better diagnostics for inbound processing;
- safer admin-facing status checks for inbound mail setup;
- more detailed rejection and error reporting.

## Possible Future Features

- Configurable notification templates.
- Daily or periodic solver summaries to reduce notification noise.
- Dashboard metrics for solvers and admins.
- Optional SLA/business-day calendar support.
- More flexible ticket categories and department assignment rules.
- Admin UI for selected safe settings.
- Export and reporting features.
- Better audit views for ticket history.
- Docker Compose or container-based local development setup, if it can stay simple.
- Optional public API, only after core authorization rules are stable.

## Not Planned for the First Public Version

- Full e-mail-based ticket creation.
- Importing inbound e-mail attachments.
- Editing raw `.env` values through the web UI.
- Hard-coded support for one specific LDAP vendor only.
- SPA frontend rewrite.
- Heavy enterprise ITSM feature set.
- Vendor-specific SSO integration as a default requirement.
- Automatic server/Postfix/Nginx configuration from the application.
- Replacing proper server administration with application-level magic.

## Contribution Areas

Community contributions are especially useful in these areas:

- documentation improvements;
- translations;
- tests;
- accessibility and UI polish;
- deployment examples;
- bug reports with reproduction steps;
- security reports through the private security process described in [SECURITY.md](SECURITY.md).

## Project Principles

- Simple conventional Laravel.
- Blade UI, no SPA frontend.
- MariaDB/MySQL compatibility.
- Standard LDAP-compatible authentication.
- No new packages without clear need.
- Strict authorization and visibility checks.
- Public comments and internal notes remain separate.
- Public documentation must stay free of internal infrastructure values.
