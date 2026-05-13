# Instalace

Tento návod popisuje čistou vývojovou nebo testovací instalaci po stažení projektu z GitHubu. Nepředpokládá žádnou infrastrukturu konkrétní organizace.

## Požadavky

- PHP 8.4 nebo novější podle `composer.json`.
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

Zpracování příchozích odpovědí je volitelné a experimentální. Ponechte `HELPDESK_INBOUND_MAIL_ENABLED=false`, pokud výslovně netestujete doručení do Maildiru a routing reply adresy/domény.

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

Tento krok je povinný pro běžné používání aplikace. Seedery vytvoří základní role, stavy ticketů, priority a kategorie vyžadované helpdesk workflow. Bez těchto záznamů mohou být ve formulářích prázdné seznamy rolí, stavů, priorit nebo kategorií a zakládání ticketů nemusí být použitelné.

Základní seedery jsou napsané idempotentně, takže je bezpečné spustit je znovu po vyčištění nebo obnovení vývojové databáze:

```bash
php artisan db:seed
```

Pokud potřebujete obnovit jen vybrané číselníky, můžete spustit také jednotlivé seedery:

```bash
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=TicketStatusSeeder
php artisan db:seed --class=TicketPrioritySeeder
php artisan db:seed --class=TicketCategorySeeder
```

## Volitelné lokální demo přihlášení bez LDAPu

Produkční autentizace je založená na LDAPu. Pro rychlé lokální vyzkoušení po stažení repozitáře lze zapnout explicitní lokální demo přihlášení. Funguje pouze při splnění všech těchto podmínek:

- `APP_ENV=local` nebo `APP_ENV=testing`
- `LDAP_ENABLED=false`
- `HELPDESK_DEMO_LOGIN_ENABLED=true`

Demo přihlášení je mimo prostředí local/testing odmítnuté, i kdyby bylo omylem zapnuté v `.env`. Nepoužívejte ho v produkci.

Příklad lokálních hodnot v `.env`:

```env
APP_ENV=local
LDAP_ENABLED=false
HELPDESK_DEMO_LOGIN_ENABLED=true
```

Demo účty vytvoříte příkazem:

```bash
php artisan db:seed --class=DemoUserSeeder
```

Seeder vytvoří tyto aktivní lokální demo uživatele:

- `admin@example.org` / `password`
- `solver@example.org` / `password`
- `user@example.org` / `password`

Demo uživatelé jsou vytvořeni s `auth_source=local-demo` a dostanou odpovídající roli `admin`, `solver` nebo `user`. Seeder zároveň zajistí existenci základních rolí, stavů ticketů, priorit a kategorií. Je idempotentní a nemaže existující uživatele.

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
