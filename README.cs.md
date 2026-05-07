# Helpdesk

[English version](README.md)

Helpdesk je interní helpdesk aplikace postavená na Laravelu pro organizace, které potřebují jednoduchý ticket workflow integrovaný s LDAP autentizací. Aplikace je navržena jako obecný open-source friendly projekt, ne jako software svázaný s konkrétní firmou, sítí nebo adresářovou strukturou.

Aplikace používá lokální uživatelské profily synchronizované z LDAPu, zatímco přihlášení a přiřazení rolí vychází z externího LDAP kompatibilního adresáře.

## Funkce

- LDAP přihlášení se synchronizací lokálního uživatelského profilu.
- Role `user`, `solver` a `admin`.
- Mapování rolí z LDAP skupin.
- Úrovně viditelnosti ticketů: `public`, `internal` a `private`.
- Volba citlivého požadavku při založení ticketu.
- Komentáře k ticketům a oddělené interní poznámky.
- Workflow vyřešených ticketů s potvrzením zadavatelem a automatickým uzavřením.
- Přílohy k ticketům a veřejným komentářům.
- Chráněný náhled a stahování příloh přes Laravel controllery.
- Lightbox galerie pro obrázkové přílohy.
- Odchozí e-mailové notifikace a příchozí odpovědi přes lokální Maildir polling.
- Česká a anglická lokalizace UI.
- Konvenční Laravel Blade UI bez SPA frontendu.

## Technologický stack

- Laravel
- PHP 8.3+
- MariaDB nebo MySQL
- LDAP kompatibilní adresářový server
- Nasazení kompatibilní s Nginx nebo Apache
- PHP-FPM pro dokumentovaný produkční scénář

## Dokumentace

- [Instalace](docs/cs/installation.md)
- [Nasazení](docs/cs/deployment.md)
- [LDAP konfigurace](docs/cs/ldap.md)
- [E-mailové notifikace](docs/cs/mail.md)
- [Přílohy](docs/cs/attachments.md)
- [Bezpečnost](docs/cs/security.md)

## Rychlý start

```bash
git clone https://github.com/example/helpdesk.git
cd helpdesk
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan test
```

Po stažení upravte `.env` pro databázi, LDAP server, poštu a URL aplikace. Podrobnosti jsou v odkazované dokumentaci výše.

## Přehled konfigurace

Nejdůležitější oblasti konfigurace jsou:

- `DB_*` pro připojení k MariaDB/MySQL.
- `LDAP_*` pro LDAP přihlášení a mapování rolí.
- `MAIL_*`, `HELPDESK_MAIL_NOTIFICATIONS` a `HELPDESK_INBOUND_*` pro odchozí notifikace a volitelné zpracování příchozích odpovědí.
- `HELPDESK_BRAND_LOGO_PATH`, `HELPDESK_BRAND_FALLBACK_TEXT` a `HELPDESK_BRAND_LOGO_MODE` pro volitelný branding hlavičky. Skutečná interní loga necommitujte.
- `HELPDESK_RESOLVED_AUTO_CLOSE_DAYS` pro automatické uzavírání vyřešených ticketů.
- `HELPDESK_ATTACHMENT_*` pro limity příloh a storage cestu.
- `APP_LOCALE` a `APP_FALLBACK_LOCALE` pro výchozí jazyk UI.

Do Gitu neukládejte skutečná tajemství, LDAP bind hesla, produkční hodnoty `APP_KEY` ani názvy serverů konkrétního prostředí.

## Licence

Projekt je distribuován jako Laravel aplikace. Použijte licenční soubor v repozitáři, pokud ho vlastník projektu doplní.
