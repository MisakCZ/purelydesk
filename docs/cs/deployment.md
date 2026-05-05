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

Po změně `.env` nebo jiného konfiguračního souboru vždy vyčistěte Laravel cache konfigurace. Jinak může aplikace dál používat staré hodnoty:

```bash
php artisan config:clear
php artisan optimize:clear
```

Stejné příkazy spusťte také po aktualizaci kódu, pokud mohou být ve framework cache zastaralé konfigurace, routy, view nebo služby.

Po nasazení nového kódu s migracemi:

```bash
php artisan migrate --force
```

Pro produkční instalaci závislostí:

```bash
composer install --no-dev --optimize-autoloader
```

## Scheduler

V produkci má pravidelně běžet Laravel scheduler. Přidejte cron záznam pro uživatele, pod kterým běží aplikace:

```cron
* * * * * cd /var/www/helpdesk && php artisan schedule:run >> /dev/null 2>&1
```

Použijte stejného systémového uživatele, pod kterým běží PHP-FPM, případně uživatele se stejnými právy k aplikaci. Tento uživatel musí umět číst soubory projektu a zapisovat do `storage` a `bootstrap/cache`. Na různých systémech se může jmenovat například `www-data`, `apache`, `nginx` nebo jinak podle distribuce.

Příklad:

```bash
sudo crontab -u www-data -e
```

`www-data` nahraďte skutečným web/PHP uživatelem na serveru a cestu `/var/www/helpdesk` upravte podle skutečného umístění aplikace.

Pokud systém nepoužívá cron, můžete Laravel scheduler spouštět také přes systemd timer.

Příklad service unity:

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

Příklad timer unity:

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

Zapnutí:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now helpdesk-scheduler.timer
sudo systemctl list-timers helpdesk-scheduler.timer
```

`www-data`, skupinu, `/var/www/helpdesk` a `/usr/bin/php` nahraďte hodnotami odpovídajícími vašemu serveru.

Aplikace plánuje příkaz `helpdesk:close-resolved-tickets` každou hodinu. Příkaz uzavírá vyřešené tickety po dosažení `auto_close_at`, zapíše historii a odešle standardní ticket notifikaci, pokud jsou e-mailové notifikace zapnuté.

Aplikace také plánuje příkaz `helpdesk:fetch-inbound-mail` každých pět minut. Příkaz nic neudělá, pokud není nastaveno `HELPDESK_INBOUND_MAIL_ENABLED=true`. Po zapnutí čte nastavený lokální Maildir a přesouvá zpracované zprávy do nakonfigurovaných adresářů `Processed` nebo `Failed`. Doručování do Maildiru, routování inbound reply domény na helpdesk SMTP server, Postfix aliasy, ACL pro PHP uživatele a troubleshooting SELinux/AppArmor jsou popsané v dokumentu [E-mailové notifikace](mail.md).

Pro test je lze spustit ručně:

```bash
php artisan helpdesk:close-resolved-tickets
php artisan helpdesk:fetch-inbound-mail
```

Lhůta pro vyřešené tickety se nastavuje pomocí:

```env
HELPDESK_RESOLVED_AUTO_CLOSE_DAYS=5
```

## Předpokládané termíny vyřešení

Ticket může mít uložené očekávané datum vyřešení v poli `expected_resolution_at`. Zadavatel ho při zakládání ticketu nevyplňuje, protože jde o provozní odhad helpdesk týmu.

Když je ticket přiřazen řešiteli a nemá nastavený očekávaný termín vyřešení, aplikace ho automaticky doplní podle priority ticketu. První verze používá kalendářní dny:

```env
HELPDESK_EXPECTED_RESOLUTION_LOW_DAYS=10
HELPDESK_EXPECTED_RESOLUTION_NORMAL_DAYS=5
HELPDESK_EXPECTED_RESOLUTION_HIGH_DAYS=2
HELPDESK_EXPECTED_RESOLUTION_CRITICAL_DAYS=1
```

Aplikace rozlišuje, zda byl termín nastaven automaticky nebo ručně. Automaticky nastavené termíny se při změně priority přepočítají. Ručně nastavené termíny se změnou priority nepřepisují. Změna řešitele existující termín neprodlužuje ani nepřepočítává; nový řešitel přebírá aktuální hodnotu.

Solver a admin mohou očekávaný termín vyřešení ručně nastavit nebo změnit ve formuláři úpravy ticketu. Běžný uživatel termín na detailu ticketu vidí, pokud existuje, ale nemůže ho odeslat ani změnit. Dashboard a filtry seznamu ticketů umí zvýraznit přiřazené otevřené tickety bez termínu, tickety po termínu a tickety s blížícím se termínem.

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
