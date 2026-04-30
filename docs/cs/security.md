# Bezpečnost

Tento dokument shrnuje základní bezpečnostní doporučení pro provoz helpdesku.

## Produkční debug nastavení

Používejte:

```env
APP_ENV=production
APP_DEBUG=false
```

V produkci nikdy nespouštějte aplikaci se zapnutým debug režimem. Debug výstupy mohou odhalit cesty, environment hodnoty, SQL chyby a další citlivá data.

## HTTPS

V produkci používejte HTTPS. Přihlašovací údaje, session cookies, obsah ticketů, komentáře a stahované přílohy nesmí cestovat přes nešifrované HTTP.

Nastavení secure cookie přizpůsobte nasazení a případné reverzní proxy.

## LDAP bezpečnost

V produkci používejte LDAPS nebo StartTLS:

```env
LDAP_ENCRYPTION=ldaps
```

nebo:

```env
LDAP_ENCRYPTION=starttls
```

Používejte read-only LDAP bind účet. Pro LDAP vyhledávání nepoužívejte administrátorský účet.

Operační systém musí důvěřovat certifikační autoritě použité pro certifikát LDAP serveru.

## Tajné hodnoty

Do Gitu neukládejte skutečná tajemství:

- produkční `APP_KEY`
- databázová hesla
- LDAP bind heslo
- SMTP heslo
- privátní názvy serverů nebo interní hostname

Soubor `.env` nesmí být verzovaný. `.env.example` musí zůstat obecný a bezpečný.

## Přílohy

Přílohy jsou uložené mimo public webroot a poskytují se přes controller akce. Akce pro stažení a náhled musí před vrácením souboru kontrolovat oprávnění k ticketu.

Storage příloh nevystavujte jako veřejný adresář.

## Pravidla viditelnosti ticketů

Aplikace podporuje:

- `public`: viditelné pro přihlášené uživatele podle policy.
- `internal`: viditelné pro zadavatele, solvery a adminy.
- `private`: viditelné pro zadavatele, přiřazeného řešitele a adminy.

Watcher záznamy nikdy automaticky nepřidělují přístup k privátním ticketům.

Admini vidí všechny tickety, ale viditelnost pro admina automaticky neznamená, že admin dostává každou notifikaci. Výběr příjemců notifikací je řešen samostatně.

## Zálohy

Zálohujte:

- databázi
- neveřejné storage příloh
- produkční environment konfiguraci uloženou mimo Git

Zálohy šifrujte, pokud obsahují obsah ticketů, osobní údaje nebo přílohy.

## Aktualizace závislostí

Udržujte Composer závislosti aktuální:

```bash
composer outdated
composer update
php artisan test
```

Sledujte bezpečnostní vydání Laravelu a opravy aplikujte včas.
