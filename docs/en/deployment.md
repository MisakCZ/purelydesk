# Deployment

This document describes a generic production deployment. The example uses Nginx, PHP-FPM, and MariaDB, but the application can also run behind Apache or another web server that supports Laravel's public document root.

## Production Environment

Recommended production settings:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://helpdesk.example.org
```

Always use HTTPS in production. TLS termination may happen directly in Nginx or at a trusted reverse proxy in front of the application.

## Directory Layout

Example deployment path:

```text
/var/www/helpdesk
```

The web server document root must point to:

```text
/var/www/helpdesk/public
```

Do not expose the project root, `.env`, `storage`, or vendor files directly through the web server.

## File Permissions

The PHP-FPM user must be able to write to:

```bash
storage
bootstrap/cache
```

Example:

```bash
cd /var/www/helpdesk
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

Adapt the user and group to your distribution.

## Nginx Example

```nginx
server {
    listen 80;
    server_name helpdesk.example.org;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name helpdesk.example.org;

    root /var/www/helpdesk/public;
    index index.php;

    client_max_body_size 25m;

    ssl_certificate /etc/ssl/certs/helpdesk.example.org.crt;
    ssl_certificate_key /etc/ssl/private/helpdesk.example.org.key;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Adjust `fastcgi_pass` to match your PHP-FPM socket or TCP listener.

## PHP Upload Limits

Attachment upload limits must be aligned across the application, PHP, and Nginx.

Example PHP settings:

```ini
upload_max_filesize = 20M
post_max_size = 25M
max_file_uploads = 10
```

Example Nginx setting:

```nginx
client_max_body_size 25m;
```

Application settings:

```env
HELPDESK_ATTACHMENT_MAX_SIZE_MB=20
HELPDESK_ATTACHMENT_MAX_FILES=10
```

`post_max_size` and `client_max_body_size` should be larger than one file because a request can contain multiple attachments and form fields.

## Branding and UI Color Schemes

The application header can show a deployed logo instead of the default `HD` monogram. Configure the public logo path in `.env`:

```env
HELPDESK_BRAND_LOGO_PATH=/helpdesk-logo.svg
HELPDESK_BRAND_FALLBACK_TEXT=HD
HELPDESK_BRAND_LOGO_MODE=wide
```

The path should point to a public asset served by the application or web server. A simple deployment option is to place the installation-specific logo directly in `public/helpdesk-logo.svg` and use `/helpdesk-logo.svg` as the path. Do not commit real internal logos or organization-specific branding assets to the public repository.

`HELPDESK_BRAND_LOGO_MODE=mark` treats the logo as a small icon in the original monogram area and keeps the application name next to it. `HELPDESK_BRAND_LOGO_MODE=wide` treats the logo as a wider horizontal brand mark and hides the duplicate visible application name to keep the header compact. If the logo path is empty, or if the image fails to load, the header falls back to `HELPDESK_BRAND_FALLBACK_TEXT`.

Users can switch the UI color scheme from the application header. The available schemes are Default, Dark, Pastel, and Contrast. The selected scheme is stored only in the browser's `localStorage`; it is not stored in the database and does not affect other users.

## Deployment Commands

After changing `.env` or another configuration file, always clear Laravel's cached configuration. Otherwise the application may continue using old values:

```bash
php artisan config:clear
php artisan optimize:clear
```

Run the same commands after updating code when cached framework files may contain stale configuration, routes, views, or services.

After deploying new code with migrations:

```bash
php artisan migrate --force
```

For production dependency installation:

```bash
composer install --no-dev --optimize-autoloader
```

## Scheduler

Laravel's scheduler should run regularly in production. Add a cron entry for the PHP user that runs the application:

```cron
* * * * * cd /var/www/helpdesk && php artisan schedule:run >> /dev/null 2>&1
```

Use the same operating system user that normally runs PHP-FPM or otherwise has equivalent permissions for the application. This user must be able to read the project files and write to `storage` and `bootstrap/cache`. On many systems this user is named `www-data`, `apache`, `nginx`, or a distribution-specific equivalent.

Example:

```bash
sudo crontab -u www-data -e
```

Replace `www-data` with the actual web/PHP user on your server and adjust `/var/www/helpdesk` to your deployment path.

If your system does not use cron, you can run Laravel's scheduler with a systemd timer instead.

Example service unit:

```ini
# /etc/systemd/system/helpdesk-scheduler.service
[Unit]
Description=Run Helpdesk Laravel scheduler

[Service]
Type=oneshot
User=www-data
Group=www-data
WorkingDirectory=/var/www/helpdesk
ExecStart=/usr/bin/php artisan schedule:run
```

Example timer unit:

```ini
# /etc/systemd/system/helpdesk-scheduler.timer
[Unit]
Description=Run Helpdesk Laravel scheduler every minute

[Timer]
OnBootSec=1min
OnUnitActiveSec=1min
Unit=helpdesk-scheduler.service

[Install]
WantedBy=timers.target
```

Enable it with:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now helpdesk-scheduler.timer
sudo systemctl list-timers helpdesk-scheduler.timer
```

Replace `www-data`, the group, `/var/www/helpdesk`, and `/usr/bin/php` according to your server.

The application schedules `helpdesk:close-resolved-tickets` hourly. This command closes resolved tickets after their `auto_close_at` deadline, writes a history record, and sends the standard ticket notification if mail notifications are enabled.

The application also schedules `helpdesk:fetch-inbound-mail` every five minutes. Inbound reply processing is optional and experimental; the command does nothing unless `HELPDESK_INBOUND_MAIL_ENABLED=true`. When enabled, it reads the configured local Maildir and moves handled messages to the configured `Processed` or `Failed` directories. Use it only after Maildir delivery, reply-domain routing to the helpdesk SMTP server, Postfix aliases, ACLs for the PHP user, and SELinux/AppArmor behavior have been tested end to end. Details are described in [Mail notifications](mail.md).

You can run it manually for testing:

```bash
php artisan helpdesk:close-resolved-tickets
php artisan helpdesk:fetch-inbound-mail
```

The grace period for resolved tickets is configured with:

```env
HELPDESK_RESOLVED_AUTO_CLOSE_DAYS=5
```

## Expected Resolution Deadlines

Tickets can store an expected resolution date in `expected_resolution_at`. The requester does not enter this date when creating a ticket, because it is an operational estimate owned by the helpdesk team.

When a ticket is assigned to a solver and no expected resolution date exists, the application sets one automatically from the ticket priority. The first version uses calendar days:

```env
HELPDESK_EXPECTED_RESOLUTION_LOW_DAYS=10
HELPDESK_EXPECTED_RESOLUTION_NORMAL_DAYS=5
HELPDESK_EXPECTED_RESOLUTION_HIGH_DAYS=2
HELPDESK_EXPECTED_RESOLUTION_CRITICAL_DAYS=1
HELPDESK_EXPECTED_RESOLUTION_DEADLINE_NOTIFICATIONS=true
HELPDESK_EXPECTED_RESOLUTION_DUE_SOON_HOURS=24
HELPDESK_EXPECTED_RESOLUTION_OVERDUE_REPEAT_HOURS=24
```

The application tracks whether the deadline was set automatically or manually. Automatically set deadlines are recalculated when the priority changes. Manually set deadlines are not overwritten by priority changes. Changing the assignee does not extend or recalculate an existing deadline; the new assignee inherits the current value.

Solver and admin users can set or change the expected resolution date manually from the ticket edit form. Regular users can see the date on the ticket detail when it exists, but cannot submit or change it. Dashboard and ticket list filters can highlight assigned open tickets without a deadline, overdue tickets, and tickets due soon.

The scheduler runs `helpdesk:notify-expected-resolution-deadlines` hourly. It sends due-soon and overdue reminder e-mails only to the current assignee. The requester is not reminded about overdue tickets by this command, but the requester is notified when a solver/admin manually changes the expected resolution date. If a deadline is postponed, the solver should add a clear reason, preferably as a public comment when the requester should see it.

## Backups

Back up at least:

- MariaDB/MySQL database.
- Non-public Laravel storage containing ticket attachments.
- The production `.env` file stored securely outside the Git repository.

Test restore procedures regularly.

## Logs and Maintenance

Laravel logs are written under `storage/logs`. Configure log rotation with your operating system's logrotate or equivalent tool so logs do not fill the filesystem.

Monitor:

- PHP-FPM logs.
- Web server access and error logs.
- Laravel logs.
- Database backups and storage usage.
