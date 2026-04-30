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

After changing configuration or updating code, run:

```bash
php artisan config:clear
php artisan optimize:clear
```

After deploying new code with migrations:

```bash
php artisan migrate --force
```

For production dependency installation:

```bash
composer install --no-dev --optimize-autoloader
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
