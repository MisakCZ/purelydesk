# Mail Notifications

PurelyDesk sends outgoing e-mail notifications through Laravel's standard mail configuration.

Inbound reply processing through a local Maildir is documented as an experimental feature under development. It is intended for advanced deployments where the administrator can route a dedicated reply address or reply domain to a local MTA/Postfix Maildir. It should be tested end to end before production use.

## Enable Notifications

```env
HELPDESK_MAIL_NOTIFICATIONS=true
```

If this value is `false`, the application does not send ticket notification e-mails and should continue working normally.

## Local Postfix Relay

A common production setup is to send mail to a local Postfix instance on `127.0.0.1:25`:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=25
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=helpdesk@example.org
MAIL_FROM_NAME="${APP_NAME}"
```

Postfix is then responsible for routing mail to the real destination.

## Direct SMTP Relay

If you use a remote SMTP relay, configure Laravel directly:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.org
MAIL_PORT=587
MAIL_USERNAME=helpdesk@example.org
MAIL_PASSWORD=secret
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=helpdesk@example.org
MAIL_FROM_NAME="${APP_NAME}"
```

Use values provided by your mail platform.

## New Ticket Recipient Settings

```env
HELPDESK_NOTIFY_SOLVERS_ON_NEW_TICKETS=true
HELPDESK_NOTIFY_ADMINS_ON_NEW_TICKETS=false
```

Solvers can be notified about new public and internal tickets by default. Admins are not notified about every new ticket by default, even though admins can see all tickets. Visibility permission is not the same as notification preference.

If `HELPDESK_NOTIFY_ADMINS_ON_NEW_TICKETS=true`, admins may be added to created-ticket notifications, but recipients are still deduplicated and filtered through ticket permissions.

## Ticket Event Recipient Model

Ticket notification recipients depend on the event type:

- New ticket: the requester is notified as confirmation. Solver queue users are notified only when `HELPDESK_NOTIFY_SOLVERS_ON_NEW_TICKETS=true`. Admins are notified only when `HELPDESK_NOTIFY_ADMINS_ON_NEW_TICKETS=true`. Existing assignee or watcher records are not used as a general reason for a created-ticket notification.
- Public comment: requester, current assignee, and watchers are notified. The comment author is excluded.
- Assignee change: the requester and the new assignee are notified. If the actor assigns the ticket to themselves, no assignee-change e-mail is sent to that actor.
- Manual expected resolution change: only the requester is notified. If the requester is also the actor, no e-mail is sent to that actor.
- Expected resolution due-soon and overdue reminders: only the current assignee is notified. Requesters, admins, and watchers are not recipients of these reminder e-mails.
- Status changes, resolved, closed, problem persists, and automatic close: requester, assignee, and watchers are considered. The actor is excluded when there is one; automatic close has no actor.
- Internal notes do not send outbound ticket notifications.

All recipient lists are deduplicated and filtered through the current ticket visibility policy before mail is sent.

## Permission Filtering

Notification recipients are filtered through current ticket visibility rules:

- Public tickets can notify authorized requester, assignee, watchers, and configured solver queue recipients.
- Internal tickets do not notify unrelated regular users.
- Private tickets do not notify watchers who do not otherwise have access.

This prevents a watcher record from leaking information about private tickets.

## Experimental Inbound Replies from Local Maildir

Inbound replies are processed from a local Postfix Maildir. The application does not use IMAP. This feature is experimental and currently only converts valid replies to existing ticket notifications into public comments on existing tickets.

The expected flow is:

- Laravel sends ticket notifications through the configured mail transport, commonly local Postfix.
- Notifications contain a tokenized `Reply-To` address, for example `helpdesk-replies+TOKEN@helpdesk.example.org`.
- The user replies to the notification e-mail.
- The MTA/Postfix delivers the reply into a local Maildir.
- Laravel's scheduler runs `helpdesk:fetch-inbound-mail`.
- The command reads messages from `Maildir/new` and `Maildir/cur`.
- Valid replies are saved as public ticket comments.
- Processed messages are moved to `Processed`.
- Invalid, unauthorized, duplicate, ignored, or failed messages are moved to `Failed`.

### Routing the Reply Domain to the Helpdesk Server

The application generates reply addresses in this form:

```text
helpdesk-replies+TOKEN@helpdesk.example.org
```

The domain `helpdesk.example.org` must be routed to the SMTP server running on the helpdesk server. Local Postfix on the helpdesk server then stores the message into the configured Maildir. Without this mail routing, user replies never reach the helpdesk server and no files will appear in Maildir.

Common routing options:

- Internal DNS/MX record for `helpdesk.example.org` pointing to the helpdesk SMTP server.
- Transport or routing rule on a central SMTP relay.
- Rule in the organization's mail system or security gateway.
- Vendor-specific routing, for example GroupWise/GWIA `route.cfg`.

GroupWise/GWIA supports `route.cfg` for routing selected SMTP destinations to specific hosts. The file is placed in the `domain/wpgate/gwia` directory.

Example:

```text
helpdesk.example.org [192.0.2.10]
```

The IP address is written in square brackets. Restart GWIA after changing `route.cfg`. The exact path and service name depend on the GroupWise installation.

After configuring routing, test from the mail relay or GWIA server:

```bash
nc -v helpdesk.example.org 25
telnet helpdesk.example.org 25
```

Successful delivery should create a file in `/var/lib/helpdesk-mail/Maildir/new`.

### Recommended Maildir Layout

Use this layout:

```text
/var/lib/helpdesk-mail/
├── Maildir/
│   ├── cur/
│   ├── new/
│   └── tmp/
├── Processed/
└── Failed/
```

`Maildir` itself contains only the standard `cur`, `new`, and `tmp` directories. `Processed` and `Failed` are application working directories next to `Maildir`, not inside it. New messages delivered by Postfix normally appear in `Maildir/new`; the application also reads `Maildir/cur`.

### Create a Local Mail User

Create a dedicated local user for Maildir delivery:

```bash
sudo useradd -r -m -d /var/lib/helpdesk-mail -s /usr/sbin/nologin helpdesk-mail

sudo -u helpdesk-mail mkdir -p /var/lib/helpdesk-mail/Maildir/{cur,new,tmp}
sudo -u helpdesk-mail mkdir -p /var/lib/helpdesk-mail/Processed
sudo -u helpdesk-mail mkdir -p /var/lib/helpdesk-mail/Failed

sudo chown -R helpdesk-mail:helpdesk-mail /var/lib/helpdesk-mail
```

The exact user and group names are deployment choices. Do not use a personal user account.

### Permissions for PHP and Scheduler

The user running Laravel's scheduler must be able to read the Maildir and move files into `Processed` and `Failed`. This is usually the PHP-FPM or web server user. Common names are `www-data`, `nginx`, `apache`, or distribution-specific names. On SLES/openSUSE it may be `wwwrun`.

Example for Debian/Ubuntu-style systems:

```bash
sudo setfacl -R -m u:www-data:rwx /var/lib/helpdesk-mail
sudo setfacl -R -d -m u:www-data:rwx /var/lib/helpdesk-mail
```

Example for SLES/openSUSE:

```bash
sudo setfacl -R -m u:wwwrun:rwx /var/lib/helpdesk-mail
sudo setfacl -R -d -m u:wwwrun:rwx /var/lib/helpdesk-mail
```

Adapt the user name to the account that runs `php artisan schedule:run`.

### Postfix Maildir Delivery

Enable Maildir delivery and plus addressing:

```bash
sudo postconf -e 'home_mailbox = Maildir/'
sudo postconf -e 'recipient_delimiter = +'
```

`recipient_delimiter = +` is required or strongly recommended for reply tokens. The application generates reply addresses in the form `base+token@domain`.

### Local Alias Option

For a simple local alias, add this to `/etc/aliases`:

```text
helpdesk-replies: helpdesk-mail
```

Then reload aliases and Postfix:

```bash
sudo newaliases
sudo systemctl reload postfix
```

### Virtual Alias Domain Option

For a dedicated reply domain, use a virtual alias domain. This example uses `texthash` because some distributions do not install Postfix hash map support by default:

```bash
sudo postconf -e 'virtual_alias_domains = helpdesk.example.org'
sudo postconf -e 'virtual_alias_maps = texthash:/etc/postfix/virtual'
```

Example `/etc/postfix/virtual`:

```text
@helpdesk.example.org helpdesk-mail@localhost
```

Validate and reload:

```bash
sudo postfix check
sudo systemctl reload postfix
```

If you prefer `hash:/etc/postfix/virtual`, it may require a package such as `postfix-hash` or the equivalent for your distribution, followed by `postmap /etc/postfix/virtual`.

Map the address to a local target such as `helpdesk-mail@localhost`. Mapping only to `helpdesk-mail` can be rewritten through `myorigin` on some systems and may send the message back out through an external relay.

### Postfix Security

Postfix must not be an open relay. If the helpdesk server receives mail only from another trusted relay or gateway, restrict port 25 at the firewall to trusted internal mail relay or gateway IP addresses. The server should accept only the domain or address used for inbound replies.

Generic baseline:

```bash
sudo postconf -e 'mynetworks = 127.0.0.0/8 [::1]/128'
sudo postconf -e 'smtpd_relay_restrictions = permit_mynetworks, reject_unauth_destination'
```

Exact settings depend on your mail topology. Review Postfix logs after changes.

### Laravel Configuration

Inbound replies are optional, experimental, and disabled by default:

```env
HELPDESK_INBOUND_MAIL_ENABLED=false
HELPDESK_INBOUND_MAIL_DRIVER=maildir
HELPDESK_INBOUND_REPLY_ADDRESS=helpdesk-replies@helpdesk.example.org
HELPDESK_INBOUND_USE_PLUS_ADDRESSING=true
HELPDESK_INBOUND_MAILDIR_PATH=/var/lib/helpdesk-mail/Maildir
HELPDESK_INBOUND_MAILDIR_PROCESSED_PATH=/var/lib/helpdesk-mail/Processed
HELPDESK_INBOUND_MAILDIR_FAILED_PATH=/var/lib/helpdesk-mail/Failed
HELPDESK_INBOUND_MAILDIR_MAX_MESSAGES=50
HELPDESK_INBOUND_IMPORT_ATTACHMENTS=false
HELPDESK_INBOUND_NOTIFY_REJECTED_ATTACHMENTS=true
```

Do not put the token into `HELPDESK_INBOUND_REPLY_ADDRESS`. The application generates the token automatically through plus addressing. Keep `HELPDESK_INBOUND_MAIL_ENABLED=false` unless you are explicitly testing Maildir delivery and reply routing.

After changing `.env`, clear Laravel's cached configuration:

```bash
php artisan config:clear
php artisan optimize:clear
```

Run the poller manually:

```bash
php artisan helpdesk:fetch-inbound-mail
```

With inbound disabled, the command should exit successfully and process nothing. After enabling inbound mail, it reads up to `HELPDESK_INBOUND_MAILDIR_MAX_MESSAGES` messages per run.

In production, run Laravel's scheduler every minute. The scheduler runs `helpdesk:fetch-inbound-mail` every five minutes. See [Deployment](deployment.md) for cron and systemd timer examples.

### Reply Tokens and Authorization

Outgoing ticket notifications include a tokenized `Reply-To` address only when inbound mail is enabled and plus addressing is enabled:

```text
helpdesk-replies+<token>@helpdesk.example.org
```

The token is random and bound to a specific ticket and recipient user. When a reply is received, the token is the primary way to identify both the ticket and the expected sender. The subject also contains a stable ticket marker such as:

```text
[Helpdesk #2026-001]
```

The subject marker is only a fallback for locating a candidate ticket. It is not enough by itself to authorize a comment. The sender must exist in the local `users` table, match the e-mail address in `From`, and pass the current ticket policy checks.

If `HELPDESK_INBOUND_USE_PLUS_ADDRESSING=false`, outgoing notifications use the base reply address without a token in the address. In that mode, subject fallback can work only for requester or assignee replies and still requires permission checks.

If `HELPDESK_INBOUND_MAIL_ENABLED=false`, outbound ticket notifications do not include the reply marker and do not use a tokenized `Reply-To` address. Users should add comments through the web ticket detail link instead. Enable inbound replies only after Maildir delivery and mail routing have been tested end to end.

When inbound mail is enabled, notification bodies include a localized reply marker:

```text
Reply above this line.
```

The inbound parser stores only the text above this marker where possible and avoids importing the quoted historical thread.

### Inbound Attachments

Inbound e-mail attachments are not imported in the first version:

```env
HELPDESK_INBOUND_IMPORT_ATTACHMENTS=false
HELPDESK_INBOUND_NOTIFY_REJECTED_ATTACHMENTS=true
```

If a valid reply contains real attachments, the text reply is still saved as a public comment. A localized note is appended to the comment explaining that attachments were not imported and should be uploaded directly in the ticket detail. If enabled, the sender receives a short attachment rejection notice. That notice is sent only to the sender, uses auto-response suppression headers, and does not use the tokenized reply address to avoid mail loops.

Inline signature logos, tracking pixels, and unnamed inline images are ignored where they can be reasonably detected.

### Limitations of Experimental Inbound Replies

- Inbound e-mail can only add public comments to existing tickets.
- It does not create new tickets.
- It does not change status, priority, assignee, category, or visibility.
- It does not confirm or reject resolved-ticket workflow.
- It does not import inbound attachments.
- Attachments must be uploaded through the web UI.
- Mail routing, gateway delivery, Postfix configuration, filesystem permissions, and SELinux/AppArmor policies must be validated by the deployment administrator.

### Test Maildir Delivery

Send test messages to the reply address:

```bash
echo "Test inbound reply" | mail -s "Inbound test" helpdesk-replies@helpdesk.example.org
echo "Test plus token" | mail -s "Inbound plus test" helpdesk-replies+abc123@helpdesk.example.org
```

Check the Maildir and Postfix logs:

```bash
find /var/lib/helpdesk-mail/Maildir/new -type f -ls
sudo journalctl -u postfix -f
```

Expected result:

- The message appears in `Maildir/new`.
- The Postfix log shows local delivery, typically `relay=local`.
- The message is not sent through an external `relayhost`.

### SELinux, AppArmor, and MAC Policies

If Postfix logs an error such as:

```text
maildir delivery failed: Permission denied
```

and a manual write test works:

```bash
sudo -u helpdesk-mail touch /var/lib/helpdesk-mail/Maildir/tmp/test-write
```

then the problem may not be regular Unix permissions. Check SELinux, AppArmor, or another mandatory access control policy.

SELinux diagnostics:

```bash
getenforce
ls -Zd /var/lib/helpdesk-mail /var/lib/helpdesk-mail/Maildir /var/lib/helpdesk-mail/Maildir/tmp
```

Temporary SELinux test:

```bash
sudo chcon -R -t mail_spool_t /var/lib/helpdesk-mail
```

More persistent SELinux configuration, if `semanage` is available:

```bash
sudo semanage fcontext -a -t mail_spool_t '/var/lib/helpdesk-mail(/.*)?'
sudo restorecon -Rv /var/lib/helpdesk-mail
```

`chcon` is useful for testing but may be overwritten by relabeling. `semanage fcontext` plus `restorecon` is the more persistent approach. SELinux types and packages vary by distribution. With AppArmor, adjust the MTA or local delivery agent profile instead.

### Troubleshooting

- Local `mail` command works, but real user replies do not appear in Maildir: the missing part is usually external mail routing to the helpdesk server.
- Check mail gateway, central relay, or GWIA logs.
- Check the Postfix journal on the helpdesk server.
- Check the firewall between the mail gateway and helpdesk server.
- Check `route.cfg`, transport map, or DNS MX records for the reply domain.
- Mail leaves the server instead of being delivered locally: check `virtual_alias_domains`, `virtual_alias_maps`, and make sure the alias target is local, for example `helpdesk-mail@localhost`.
- Postfix reports `unsupported dictionary type: hash`: use `texthash` or install the distribution package that provides Postfix hash map support.
- Maildir delivery fails with `Permission denied`: check Unix permissions, test `touch` as the mailbox user, then check SELinux/AppArmor/MAC policy.
- Mail appears in Maildir but Laravel does not process it: check `HELPDESK_INBOUND_MAIL_ENABLED`, permissions for the web/PHP user, run `php artisan helpdesk:fetch-inbound-mail` manually, inspect `incoming_emails`, and check `Processed` / `Failed`.
- A reply does not create a comment: verify the `Reply-To` token, make sure `From` matches `users.email`, and confirm the user has `view` and `commentPublic` permission for the ticket.

## Resolved Workflow Notifications

The resolved-ticket workflow uses the same notification pipeline and permission filtering:

- When a solver or admin marks a ticket as resolved, the requester is notified and can open the ticket detail to confirm the resolution or report that the problem still persists.
- When the requester confirms the resolution, the ticket is closed and the usual ticket recipients can be notified according to their access.
- When the requester reports that the problem persists, the ticket returns to an active state and the assignee/watchers can be notified according to their access.
- When `helpdesk:close-resolved-tickets` closes a ticket automatically, requester, assignee, and authorized watchers can receive the standard closed-ticket notification.

## Expected Resolution Deadline Reminders

If expected resolution deadline notifications are enabled, the scheduler can notify the current assignee when a ticket deadline is approaching or already overdue:

- A due-soon reminder is sent once when `expected_resolution_at` is within the configured window, 24 hours by default.
- An overdue reminder is sent after `expected_resolution_at` has passed.
- Overdue reminders repeat at most once per configured interval, 24 hours by default, while the ticket is still active and overdue.
- Requesters, admins, and watchers are not recipients of these reminders.
- The requester is notified separately when a solver or admin manually changes the expected resolution date.
- The assignee should add a clear reason when postponing a deadline, preferably as a public comment if the requester should see it.

Relevant environment variables:

```env
HELPDESK_EXPECTED_RESOLUTION_DEADLINE_NOTIFICATIONS=true
HELPDESK_EXPECTED_RESOLUTION_DUE_SOON_HOURS=24
HELPDESK_EXPECTED_RESOLUTION_OVERDUE_REPEAT_HOURS=24
```

These reminders require the Laravel scheduler. The scheduled command is `helpdesk:notify-expected-resolution-deadlines`.

## Test from Laravel

Use Laravel Tinker for a simple application-level test:

```bash
php artisan tinker
```

```php
Mail::raw('Helpdesk mail test', fn ($message) => $message->to('user@example.org')->subject('Helpdesk test'));
```

Use a test recipient address, not a real user's personal mailbox unless intended.

## Optional Server Test

If `mail` or `mailx` is installed, you can test the local mail stack outside Laravel:

```bash
echo "Helpdesk mail test" | mail -s "Helpdesk test" user@example.org
```

This validates the server mail path, not the application notification logic.
