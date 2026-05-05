# E-mailové notifikace

Helpdesk odesílá odchozí e-mailové notifikace přes standardní Laravel mail konfiguraci. Umí také načítat odpovědi k existujícím ticketům z lokálního Postfix Maildiru. Aplikace nepoužívá IMAP a nezakládá nové tickety z e-mailu.

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

## Příchozí odpovědi z lokálního Maildiru

Příchozí odpovědi se zpracovávají z lokálního Postfix Maildiru. Aplikace nepoužívá IMAP. Převádí pouze validní odpovědi na notifikace existujících ticketů na veřejné komentáře u existujících ticketů.

Očekávaný tok:

- Laravel odešle ticket notifikaci přes nastavený mail transport, často lokální Postfix.
- Notifikace obsahuje tokenizovanou adresu `Reply-To`, například `helpdesk-replies+TOKEN@helpdesk.example.org`.
- Uživatel odpoví na e-mail.
- MTA/Postfix doručí odpověď do lokálního Maildiru.
- Laravel scheduler spustí `helpdesk:fetch-inbound-mail`.
- Command přečte zprávy z `Maildir/new` a `Maildir/cur`.
- Validní odpovědi se uloží jako veřejné komentáře k ticketu.
- Zpracované zprávy se přesunou do `Processed`.
- Chybné, ignorované, duplicitní nebo neoprávněné zprávy se přesunou do `Failed`.

### Routování reply domény na helpdesk server

Aplikace generuje reply adresy ve tvaru:

```text
helpdesk-replies+TOKEN@helpdesk.example.org
```

Doména `helpdesk.example.org` musí být routována na SMTP server běžící na helpdesk serveru. Lokální Postfix na helpdesk serveru potom zprávu uloží do nakonfigurovaného Maildiru. Bez tohoto mail routingu se odpovědi uživatelů na helpdesk server nikdy nedostanou a v Maildiru se neobjeví žádné soubory.

Běžné možnosti routování:

- Interní DNS/MX záznam pro `helpdesk.example.org` směřující na helpdesk SMTP server.
- Transport nebo routing pravidlo na centrálním SMTP relay.
- Pravidlo v poštovním systému nebo bezpečnostní gateway.
- Vendor-specific routing, například GroupWise/GWIA `route.cfg`.

GroupWise/GWIA podporuje `route.cfg` pro routování vybraných SMTP destinací na konkrétní hosty. Soubor se umisťuje do adresáře `domain/wpgate/gwia`.

Příklad:

```text
helpdesk.example.org [192.0.2.10]
```

IP adresa se zapisuje do hranatých závorek. Po změně `route.cfg` restartujte GWIA. Přesná cesta a název služby závisí na instalaci GroupWise.

Po nastavení routování otestujte spojení z mail relay nebo GWIA serveru:

```bash
nc -v helpdesk.example.org 25
telnet helpdesk.example.org 25
```

Úspěšné doručení má vytvořit soubor v `/var/lib/helpdesk-mail/Maildir/new`.

### Doporučená struktura Maildiru

Použijte tuto strukturu:

```text
/var/lib/helpdesk-mail/
├── Maildir/
│   ├── cur/
│   ├── new/
│   └── tmp/
├── Processed/
└── Failed/
```

Samotný `Maildir` obsahuje pouze standardní adresáře `cur`, `new` a `tmp`. `Processed` a `Failed` jsou pracovní složky aplikace vedle `Maildiru`, ne uvnitř něj. Nové zprávy doručené Postfixem se obvykle objeví v `Maildir/new`; aplikace čte také `Maildir/cur`.

### Vytvoření lokálního mail uživatele

Vytvořte samostatného lokálního uživatele pro doručování do Maildiru:

```bash
sudo useradd -r -m -d /var/lib/helpdesk-mail -s /usr/sbin/nologin helpdesk-mail

sudo -u helpdesk-mail mkdir -p /var/lib/helpdesk-mail/Maildir/{cur,new,tmp}
sudo -u helpdesk-mail mkdir -p /var/lib/helpdesk-mail/Processed
sudo -u helpdesk-mail mkdir -p /var/lib/helpdesk-mail/Failed

sudo chown -R helpdesk-mail:helpdesk-mail /var/lib/helpdesk-mail
```

Konkrétní názvy uživatele a skupiny jsou věcí nasazení. Nepoužívejte osobní uživatelský účet.

### Oprávnění pro PHP a scheduler

Uživatel, pod kterým běží Laravel scheduler, musí umět číst Maildir a přesouvat soubory do `Processed` a `Failed`. Obvykle jde o PHP-FPM nebo web server uživatele. Časté názvy jsou `www-data`, `nginx`, `apache` nebo distribuční varianta. Na SLES/openSUSE to může být `wwwrun`.

Příklad pro Debian/Ubuntu-like systémy:

```bash
sudo setfacl -R -m u:www-data:rwx /var/lib/helpdesk-mail
sudo setfacl -R -d -m u:www-data:rwx /var/lib/helpdesk-mail
```

Příklad pro SLES/openSUSE:

```bash
sudo setfacl -R -m u:wwwrun:rwx /var/lib/helpdesk-mail
sudo setfacl -R -d -m u:wwwrun:rwx /var/lib/helpdesk-mail
```

Název uživatele upravte podle účtu, pod kterým běží `php artisan schedule:run`.

### Postfix Maildir delivery

Zapněte doručování do Maildiru a plus addressing:

```bash
sudo postconf -e 'home_mailbox = Maildir/'
sudo postconf -e 'recipient_delimiter = +'
```

`recipient_delimiter = +` je nutný nebo silně doporučený pro reply tokeny. Aplikace generuje reply adresy ve tvaru `base+token@domain`.

### Varianta s lokálním aliasem

Pro jednoduchý lokální alias přidejte do `/etc/aliases`:

```text
helpdesk-replies: helpdesk-mail
```

Potom obnovte aliasy a reloadujte Postfix:

```bash
sudo newaliases
sudo systemctl reload postfix
```

### Varianta s virtual alias doménou

Pro samostatnou reply doménu použijte virtual alias domain. Příklad používá `texthash`, protože některé distribuce nemají podporu Postfix hash map nainstalovanou defaultně:

```bash
sudo postconf -e 'virtual_alias_domains = helpdesk.example.org'
sudo postconf -e 'virtual_alias_maps = texthash:/etc/postfix/virtual'
```

Příklad `/etc/postfix/virtual`:

```text
@helpdesk.example.org helpdesk-mail@localhost
```

Ověření a reload:

```bash
sudo postfix check
sudo systemctl reload postfix
```

Pokud chcete použít `hash:/etc/postfix/virtual`, může být potřeba balík typu `postfix-hash` nebo jeho obdoba pro danou distribuci a následně `postmap /etc/postfix/virtual`.

Adresu mapujte na lokální cíl, například `helpdesk-mail@localhost`. Mapování jen na `helpdesk-mail` se na některých systémech může přepsat přes `myorigin` a zpráva může odejít zpět ven přes externí relay.

### Bezpečnost Postfixu

Postfix nesmí být open relay. Pokud helpdesk server přijímá poštu pouze z jiného důvěryhodného relay serveru nebo gateway, omezte port 25 firewallem jen na důvěryhodné interní mail relay nebo gateway IP adresy. Server má přijímat pouze doménu nebo adresu určenou pro inbound odpovědi.

Obecný základ:

```bash
sudo postconf -e 'mynetworks = 127.0.0.0/8 [::1]/128'
sudo postconf -e 'smtpd_relay_restrictions = permit_mynetworks, reject_unauth_destination'
```

Konkrétní nastavení závisí na topologii pošty. Po změnách kontrolujte Postfix logy.

### Laravel konfigurace

Příchozí odpovědi jsou volitelné a defaultně vypnuté:

```env
HELPDESK_INBOUND_MAIL_ENABLED=false
HELPDESK_INBOUND_MAIL_DRIVER=maildir
HELPDESK_INBOUND_REPLY_ADDRESS=helpdesk-replies@helpdesk.example.org
HELPDESK_INBOUND_USE_PLUS_ADDRESSING=true
HELPDESK_INBOUND_MAILDIR_PATH=/var/lib/helpdesk-mail/Maildir
HELPDESK_INBOUND_MAILDIR_PROCESSED_PATH=/var/lib/helpdesk-mail/Processed
HELPDESK_INBOUND_MAILDIR_FAILED_PATH=/var/lib/helpdesk-mail/Failed
HELPDESK_INBOUND_MAILDIR_MAX_MESSAGES=50
HELPDESK_INBOUND_IMPORT_ATTACHMENTS=false
HELPDESK_INBOUND_NOTIFY_REJECTED_ATTACHMENTS=true
```

Token se nedává do `HELPDESK_INBOUND_REPLY_ADDRESS`. Aplikace token generuje automaticky přes plus addressing. `HELPDESK_INBOUND_MAIL_ENABLED` nechte `false`, dokud není serverové doručování do Maildiru otestované.

Po změně `.env` vyčistěte Laravel cache konfigurace:

```bash
php artisan config:clear
php artisan optimize:clear
```

Ruční spuštění polleru:

```bash
php artisan helpdesk:fetch-inbound-mail
```

S vypnutým inboundem má command skončit úspěšně a nic nezpracovat. Po zapnutí inbound mailu čte maximálně `HELPDESK_INBOUND_MAILDIR_MAX_MESSAGES` zpráv za jeden běh.

V produkci spouštějte Laravel scheduler každou minutu. Scheduler spouští `helpdesk:fetch-inbound-mail` každých pět minut. Cron a systemd timer jsou popsané v dokumentu [Nasazení](deployment.md).

### Reply tokeny a autorizace

Odchozí ticket notifikace obsahují tokenizovanou adresu `Reply-To`, pokud je zapnutý plus addressing:

```text
helpdesk-replies+<token>@helpdesk.example.org
```

Token je náhodný a je navázaný na konkrétní ticket a konkrétního příjemce. Při příjmu odpovědi je token hlavní způsob identifikace ticketu i očekávaného odesílatele. Subject zároveň obsahuje stabilní značku ticketu, například:

```text
[Helpdesk #2026-001]
```

Značka v subjectu slouží jen jako fallback pro dohledání kandidátního ticketu. Sama o sobě nestačí k autorizaci komentáře. Odesílatel musí existovat v lokální tabulce `users`, odpovídat adrese z hlavičky `From` a projít aktuální policy kontrolou ticketu.

Pokud je `HELPDESK_INBOUND_USE_PLUS_ADDRESSING=false`, odchozí notifikace používají základní reply adresu bez tokenu v adrese. V tomto režimu může subject fallback fungovat jen pro odpovědi zadavatele nebo řešitele a stále se ověřují oprávnění.

Tělo notifikace obsahuje lokalizovaný reply marker:

```text
Odpovězte nad tento řádek.
```

Inbound parser se snaží uložit pouze text nad tímto markerem a neimportovat citovanou historii konverzace.

### Přílohy v příchozích e-mailech

Přílohy z příchozích e-mailů se v první verzi neimportují:

```env
HELPDESK_INBOUND_IMPORT_ATTACHMENTS=false
HELPDESK_INBOUND_NOTIFY_REJECTED_ATTACHMENTS=true
```

Pokud validní odpověď obsahuje skutečné přílohy, text odpovědi se přesto uloží jako veřejný komentář. Ke komentáři se přidá lokalizovaná poznámka, že přílohy nebyly importovány a mají se nahrát přímo v detailu ticketu. Pokud je zapnuto upozornění, odesílatel dostane krátký e-mail o odmítnutí příloh. Toto upozornění se posílá pouze odesílateli, používá hlavičky pro potlačení automatických odpovědí a nepoužívá tokenizovanou reply adresu, aby nevznikaly mail loop.

Inline loga v podpisech, tracking pixely a nepojmenované inline obrázky se ignorují, pokud je lze rozumně rozlišit.

### Omezení první verze

- Inbound e-mail vytváří pouze veřejné komentáře k existujícím ticketům.
- Nezakládá nové tickety e-mailem.
- Nemění status, prioritu, řešitele ani viditelnost.
- Nepotvrzuje ani neodmítá resolved workflow e-mailem.
- Neimportuje inbound přílohy.
- Pokud inbound zpráva obsahuje skutečné přílohy, systém přidá poznámku do komentáře a může upozornit odesílatele.
- Přílohy je nutné nahrávat přes webové UI.

### Test doručení do Maildiru

Pošlete testovací zprávy na reply adresu:

```bash
echo "Test inbound reply" | mail -s "Inbound test" helpdesk-replies@helpdesk.example.org
echo "Test plus token" | mail -s "Inbound plus test" helpdesk-replies+abc123@helpdesk.example.org
```

Zkontrolujte Maildir a Postfix log:

```bash
find /var/lib/helpdesk-mail/Maildir/new -type f -ls
sudo journalctl -u postfix -f
```

Očekávaný výsledek:

- Zpráva se objeví v `Maildir/new`.
- V Postfix logu je lokální doručení, typicky `relay=local`.
- Zpráva se neposílá přes externí `relayhost`.

### SELinux, AppArmor a MAC politiky

Pokud Postfix hlásí chybu typu:

```text
maildir delivery failed: Permission denied
```

a ruční test zápisu funguje:

```bash
sudo -u helpdesk-mail touch /var/lib/helpdesk-mail/Maildir/tmp/test-write
```

pak problém nemusí být v běžných Unix oprávněních. Zkontrolujte SELinux, AppArmor nebo jinou mandatory access control politiku.

SELinux diagnostika:

```bash
getenforce
ls -Zd /var/lib/helpdesk-mail /var/lib/helpdesk-mail/Maildir /var/lib/helpdesk-mail/Maildir/tmp
```

Dočasný SELinux test:

```bash
sudo chcon -R -t mail_spool_t /var/lib/helpdesk-mail
```

Trvalejší SELinux konfigurace, pokud je dostupný `semanage`:

```bash
sudo semanage fcontext -a -t mail_spool_t '/var/lib/helpdesk-mail(/.*)?'
sudo restorecon -Rv /var/lib/helpdesk-mail
```

`chcon` je vhodný pro test, ale může být přepsán relabelingem. `semanage fcontext` plus `restorecon` je trvalejší řešení. SELinux typy a balíčky se liší podle distribuce. U AppArmor je potřeba upravit profil MTA nebo local delivery agenta.

### Troubleshooting

- Lokální příkaz `mail` funguje, ale skutečné odpovědi uživatelů se v Maildiru neobjevují: chybějící část je obvykle externí mail routing na helpdesk server.
- Zkontrolujte logy mail gateway, centrálního relay nebo GWIA.
- Zkontrolujte Postfix journal na helpdesk serveru.
- Zkontrolujte firewall mezi mail gateway a helpdesk serverem.
- Zkontrolujte `route.cfg`, transport mapu nebo DNS MX záznamy pro reply doménu.
- Pošta odchází ven místo lokálního doručení: zkontrolujte `virtual_alias_domains`, `virtual_alias_maps` a lokální cíl aliasu, například `helpdesk-mail@localhost`.
- Postfix hlásí `unsupported dictionary type: hash`: použijte `texthash` nebo nainstalujte distribuční balík s podporou Postfix hash map.
- Maildir delivery končí `Permission denied`: zkontrolujte Unix oprávnění, otestujte `touch` jako mailbox uživatel a potom zkontrolujte SELinux/AppArmor/MAC politiku.
- E-mail se objeví v Maildiru, ale Laravel ho nezpracuje: zkontrolujte `HELPDESK_INBOUND_MAIL_ENABLED`, práva web/PHP uživatele, spusťte `php artisan helpdesk:fetch-inbound-mail` ručně, zkontrolujte `incoming_emails` a adresáře `Processed` / `Failed`.
- Odpověď nevytvoří komentář: ověřte `Reply-To` token, ujistěte se, že `From` odpovídá `users.email`, a potvrďte, že uživatel má oprávnění `view` a `commentPublic` k ticketu.

## Notifikace resolved workflow

Workflow vyřešených ticketů používá stejný notifikační mechanismus a filtrování podle oprávnění:

- Když solver nebo admin označí ticket jako vyřešený, zadavatel dostane notifikaci a může v detailu potvrdit vyřešení nebo oznámit, že problém trvá.
- Když zadavatel potvrdí vyřešení, ticket se uzavře a obvyklí příjemci ticketu mohou dostat notifikaci podle svého přístupu.
- Když zadavatel oznámí, že problém trvá, ticket se vrátí do aktivního stavu a přiřazený řešitel / sledující mohou dostat notifikaci podle svého přístupu.
- Když `helpdesk:close-resolved-tickets` uzavře ticket automaticky, zadavatel, řešitel a oprávnění sledující mohou dostat standardní notifikaci o uzavření ticketu.

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
