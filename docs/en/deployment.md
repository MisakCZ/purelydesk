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

You can run it manually for testing:

```bash
php artisan helpdesk:close-resolved-tickets
```

The grace period for resolved tickets is configured with:

```env
HELPDESK_RESOLVED_AUTO_CLOSE_DAYS=5
```

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
