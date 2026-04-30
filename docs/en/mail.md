# Mail Notifications

The helpdesk sends outgoing e-mail notifications through Laravel's standard mail configuration. Incoming e-mail, replies by e-mail, and ticket creation by e-mail are not part of the first implementation.

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

## Permission Filtering

Notification recipients are filtered through current ticket visibility rules:

- Public tickets can notify authorized requester, assignee, watchers, and configured solver queue recipients.
- Internal tickets do not notify unrelated regular users.
- Private tickets do not notify watchers who do not otherwise have access.

This prevents a watcher record from leaking information about private tickets.

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
