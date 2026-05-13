# Security Policy

## Supported Versions

Currently, security updates are intended for the main branch and the latest public release.

PurelyDesk is an early public version. This security policy may be refined as the project and release process mature.

## Reporting a Vulnerability

Please do not report security vulnerabilities through public GitHub issues.

Contact the maintainer privately using the contact information available on the maintainer's GitHub profile or project website.

Please include:

- affected version or commit;
- clear description of the issue;
- reproduction steps;
- potential impact;
- suggested mitigation, if known.

## Security Notes

PurelyDesk handles ticket content, user identities, comments, internal notes, and attachments. Deployments should protect:

- production `APP_KEY`;
- database credentials;
- LDAP bind credentials;
- mail credentials;
- private attachment storage;
- environment-specific server names and configuration.

Attachments are intended to be stored outside the public webroot and served only through authorized controller actions.

Ticket visibility rules must preserve the separation between public, internal, and private tickets.

Inbound Maildir reply processing is experimental and should be tested end to end before production use.

## Public Disclosure

Please allow reasonable time for investigation and remediation before public disclosure.
