# Instalace

Tento návod popisuje čistou vývojovou nebo testovací instalaci po stažení projektu z GitHubu. Nepředpokládá žádnou infrastrukturu konkrétní organizace.

## Požadavky

- PHP 8.3 nebo novější podle `composer.json`.
- Composer.
- MariaDB nebo MySQL.
- Web server pro testování v prohlížeči, například Nginx nebo Apache.
- PHP rozšíření běžně vyžadovaná Laravelem, včetně:
  - `ldap`
  - `mbstring`
  - `openssl`
  - `pdo_mysql`
  - `fileinfo`
  - `ctype`
  - `json`
  - `tokenizer`
  - `xml`

## Stažení repozitáře

```bash
git clone https://github.com/example/helpdesk.git
cd helpdesk
```

URL repozitáře nahraďte skutečnou GitHub URL svého forku nebo projektu.

## Instalace PHP závislostí

```bash
composer install
```

Pro produkční build použijte `composer install --no-dev --optimize-autoloader`.

## Vytvoření environment souboru

```bash
cp .env.example .env
```

Upravte `.env` a nastavte alespoň:

- `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `LDAP_*`, pokud má být aktivní LDAP přihlášení
- `MAIL_*`, pokud mají být aktivní odchozí notifikace

## Vygenerování aplikačního klíče

```bash
php artisan key:generate
```

Produkční `APP_KEY` nikdy nepoužívejte opakovaně mezi instalacemi a nikdy ho neukládejte do Gitu.

## Nastavení databáze

Vytvořte prázdnou MariaDB/MySQL databázi a databázového uživatele s oprávněním k této databázi.

Příklad hodnot v `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=helpdesk
DB_USERNAME=helpdesk
DB_PASSWORD=secret
```

## Spuštění migrací

```bash
php artisan migrate
```

## Naplnění základních dat

```bash
php artisan db:seed
```

Seedery vytvoří základní role, stavy ticketů, priority a kategorie vyžadované helpdesk workflow.

## Spuštění testů

```bash
php artisan test
```

Testy by měly projít předtím, než začnete aplikaci upravovat.

## Lokální vývojový server

Pro jednoduché lokální testování můžete použít vestavěný Laravel server:

```bash
php artisan serve
```

Pro testování LDAP a e-mailové integrace nastavte `.env` na testovací služby, ne na produkční služby.
