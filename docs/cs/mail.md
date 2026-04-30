# E-mailové notifikace

Helpdesk odesílá odchozí e-mailové notifikace přes standardní Laravel mail konfiguraci. Příjem e-mailů, odpovědi e-mailem ani zakládání ticketů e-mailem nejsou součástí první implementace.

## Zapnutí notifikací

```env
HELPDESK_MAIL_NOTIFICATIONS=true
```

Pokud je hodnota `false`, aplikace neposílá ticket notifikační e-maily a má dál normálně fungovat.

## Lokální Postfix relay

Běžné produkční nastavení je odesílání pošty na lokální Postfix na `127.0.0.1:25`:

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

Postfix potom zajišťuje doručení pošty do skutečného cíle.

## Přímý SMTP relay

Pokud používáte vzdálený SMTP relay, nastavte Laravel přímo:

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

Použijte hodnoty dodané vaší poštovní platformou.

## Nastavení příjemců nových ticketů

```env
HELPDESK_NOTIFY_SOLVERS_ON_NEW_TICKETS=true
HELPDESK_NOTIFY_ADMINS_ON_NEW_TICKETS=false
```

Solveři mohou být defaultně notifikováni o nových veřejných a interních ticketech. Admini defaultně nejsou notifikováni o každém novém ticketu, i když vidí všechny tickety. Oprávnění ticket vidět není totéž jako notifikační preference.

Pokud je `HELPDESK_NOTIFY_ADMINS_ON_NEW_TICKETS=true`, admini mohou být přidáni do notifikací nově založených ticketů, ale příjemci se stále deduplikují a filtrují přes oprávnění ticketu.

## Filtrování podle oprávnění

Příjemci notifikací jsou filtrováni přes aktuální pravidla viditelnosti ticketů:

- Veřejné tickety mohou notifikovat oprávněného zadavatele, řešitele, sledující a nakonfigurovanou frontu solverů.
- Interní tickety nenotifikují nesouvisející běžné uživatele.
- Privátní tickety nenotifikují sledující, kteří k nim jinak nemají přístup.

Tím se zabrání úniku informací o privátních ticketech přes watcher záznam.

## Test z Laravelu

Pro jednoduchý aplikační test použijte Laravel Tinker:

```bash
php artisan tinker
```

```php
Mail::raw('Helpdesk mail test', fn ($message) => $message->to('user@example.org')->subject('Helpdesk test'));
```

Použijte testovací adresu příjemce, ne osobní schránku skutečného uživatele, pokud to není záměr.

## Volitelný serverový test

Pokud je nainstalovaný `mail` nebo `mailx`, můžete otestovat lokální poštovní stack mimo Laravel:

```bash
echo "Helpdesk mail test" | mail -s "Helpdesk test" user@example.org
```

Tento test ověřuje serverovou poštovní cestu, ne aplikační logiku notifikací.
