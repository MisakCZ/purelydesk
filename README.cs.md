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
- Očekávané termíny vyřešení s výchozími hodnotami podle priority, připomínkami řešiteli a notifikací zadavatele při ruční změně termínu.
- Přílohy k ticketům a veřejným komentářům.
- Chráněný náhled a stahování příloh přes Laravel controllery.
- Lightbox galerie pro obrázkové přílohy.
- Odchozí e-mailové notifikace.
- Příchozí e-mailové odpovědi přes lokální Maildir polling jsou experimentální funkcionalita ve vývoji.
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

## Aktuální stav příchozích e-mailů

Zpracování příchozích e-mailů je aktuálně dokumentované jako experimentální funkcionalita ve vývoji. Zamýšlená první verze zpracovává odpovědi na existující ticket notifikace a ukládá platné odpovědi jako veřejné komentáře.

Nezakládá nové tickety, nemění stav, prioritu, řešitele, kategorii ani viditelnost ticketu, nepotvrzuje resolved workflow a neimportuje přílohy z e-mailu.

Produkční použití závisí na správném routingu e-mailů do lokálního MTA/Postfix Maildiru a musí být ověřeno end-to-end v cílovém prostředí.

## Lokální demo přihlášení bez LDAPu

PurelyDesk běžně ověřuje uživatele přes LDAP. Pro rychlé lokální vyzkoušení lze zapnout lokální demo přihlášení. Tento režim je určený pouze pro lokální vývoj a mimo prostředí `local` a `testing` je automaticky zakázaný.

```env
APP_ENV=local
LDAP_ENABLED=false
HELPDESK_DEMO_LOGIN_ENABLED=true
```

Demo uživatele vytvoříte příkazem:

```bash
php artisan db:seed --class=DemoUserSeeder
```

Tím se pro lokální vyzkoušení zároveň doplní základní role, stavy, priority a kategorie.

Demo účty:

- `admin@example.org` / `password`
- `solver@example.org` / `password`
- `user@example.org` / `password`

Demo přihlášení nikdy nepoužívejte v produkci. Produkční autentizace má používat LDAP.

## Přehled konfigurace

Nejdůležitější oblasti konfigurace jsou:

- `DB_*` pro připojení k MariaDB/MySQL.
- `LDAP_*` pro LDAP přihlášení a mapování rolí.
- `HELPDESK_DEMO_LOGIN_ENABLED` pro explicitní lokální demo přihlášení bez LDAPu.
- `MAIL_*` a `HELPDESK_MAIL_NOTIFICATIONS` pro odchozí notifikace.
- `HELPDESK_INBOUND_*` pro volitelné experimentální zpracování příchozích odpovědí přes Maildir.
- `HELPDESK_BRAND_LOGO_PATH`, `HELPDESK_BRAND_FALLBACK_TEXT` a `HELPDESK_BRAND_LOGO_MODE` pro volitelný branding hlavičky. Skutečná interní loga necommitujte.
- `HELPDESK_RESOLVED_AUTO_CLOSE_DAYS` pro automatické uzavírání vyřešených ticketů.
- `HELPDESK_EXPECTED_RESOLUTION_*` pro výchozí očekávané termíny podle priority a připomínky řešiteli při blížícím se nebo překročeném termínu.
- `HELPDESK_ATTACHMENT_*` pro limity příloh a storage cestu.
- `APP_LOCALE` a `APP_FALLBACK_LOCALE` pro výchozí jazyk UI.

Do Gitu neukládejte skutečná tajemství, LDAP bind hesla, produkční hodnoty `APP_KEY` ani názvy serverů konkrétního prostředí.

## Autor

Autorem a správcem projektu je Michal Hradecký, misak.cz.

Aplikace byla vyvíjena za pomoci OpenAI Codexu.

## Licence

Projekt je zveřejněn pod licencí MIT. Plné znění licence najdete v souboru [LICENSE](LICENSE).
