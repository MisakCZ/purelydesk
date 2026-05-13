# Changelog

All notable changes to PurelyDesk will be documented in this file.

The format is inspired by Keep a Changelog.

## Unreleased

### Added

- Placeholder for upcoming changes.

## 0.1.0 - Initial public release

### Added

- Laravel-based ticket workflow.
- LDAP authentication with local user profile synchronization.
- Role model with user, solver, and admin roles.
- Ticket visibility levels: public, internal, and private.
- Ticket comments and separate internal notes.
- Attachments for tickets and public comments.
- Protected attachment preview and download.
- Resolved-ticket workflow with requester confirmation and automatic closing.
- Expected resolution deadline workflow.
- Outgoing e-mail notifications.
- Experimental Maildir-based inbound reply processing.
- Local demo login for local/testing environments.
- Czech and English UI localization.
- GitHub Actions CI.

### Notes

- Inbound e-mail processing is experimental and limited to public comments on existing tickets.
- Inbound e-mail does not create new tickets, change workflow state, or import attachments.
