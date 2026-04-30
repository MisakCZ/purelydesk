# Nasazení

Tento dokument popisuje obecné produkční nasazení. Příklad používá Nginx, PHP-FPM a MariaDB, ale aplikace může běžet také za Apache nebo jiným web serverem, který podporuje veřejný document root Laravelu.

## Produkční prostředí

Doporučené produkční nastavení:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://helpdesk.example.org
```

V produkci vždy používejte HTTPS. TLS může být ukončeno přímo v Nginxu nebo na důvěryhodné reverzní proxy před aplikací.

## Struktura adresářů

Příklad cesty nasazení:

```text
/var/www/helpdesk
```

Document root web serveru musí ukazovat na:

```text
/var/www/helpdesk/public
```

Přes web server přímo nevystavujte kořen projektu, `.env`, `storage` ani vendor soubory.

## Oprávnění souborů

Uživatel PHP-FPM musí mít právo zapisovat do:

```bash
storage
bootstrap/cache
```

Příklad:

```bash
cd /var/www/helpdesk
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

Uživatele a skupinu přizpůsobte své distribuci.

## Příklad Nginx konfigurace

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

`fastcgi_pass` upravte podle svého PHP-FPM socketu nebo TCP listeneru.

## PHP limity uploadu

Limity uploadu příloh musí být sladěné v aplikaci, PHP i Nginxu.

Příklad PHP nastavení:

```ini
upload_max_filesize = 20M
post_max_size = 25M
max_file_uploads = 10
```

Příklad Nginx nastavení:

```nginx
client_max_body_size 25m;
```

Aplikační nastavení:

```env
HELPDESK_ATTACHMENT_MAX_SIZE_MB=20
HELPDESK_ATTACHMENT_MAX_FILES=10
```

`post_max_size` a `client_max_body_size` mají být větší než velikost jednoho souboru, protože požadavek může obsahovat více příloh a formulářová pole.

## Příkazy při nasazení

Po změně konfigurace nebo aktualizaci kódu spusťte:

```bash
php artisan config:clear
php artisan optimize:clear
```

Po nasazení nového kódu s migracemi:

```bash
php artisan migrate --force
```

Pro produkční instalaci závislostí:

```bash
composer install --no-dev --optimize-autoloader
```

## Zálohy

Zálohujte minimálně:

- MariaDB/MySQL databázi.
- Neveřejné Laravel storage obsahující přílohy ticketů.
- Produkční `.env` soubor uložený bezpečně mimo Git repozitář.

Postupy obnovy pravidelně testujte.

## Logy a údržba

Laravel logy jsou ukládány do `storage/logs`. Nastavte rotaci logů pomocí systémového logrotate nebo obdobného nástroje, aby logy nezaplnily souborový systém.

Sledujte:

- PHP-FPM logy.
- Access a error logy web serveru.
- Laravel logy.
- Zálohy databáze a využití storage.
