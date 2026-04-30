# Přílohy

Helpdesk podporuje přílohy u ticketů a veřejných komentářů. Přílohy nejsou vkládány inline přímo do textu; ukládají se jako samostatné soubory navázané na ticket nebo komentář.

## Storage model

Přílohy se ukládají přes Laravel Storage na neveřejný disk/cestu. Nejsou poskytovány přes přímou veřejnou URL.

U každé přílohy se ukládají zejména:

- ticket
- volitelný komentář ticketu
- uživatel, který přílohu nahrál
- původní název souboru
- storage cesta
- MIME typ
- velikost souboru
- časové údaje

## Chráněný náhled a stažení

Požadavky na náhled a stažení jdou přes Laravel controller routy. Před vrácením souboru aplikace ověří, že aktuální uživatel smí vidět související ticket.

To je nezbytné pro interní a privátní tickety. Přímá storage URL se nesmí vystavovat.

## Fronta příloh v UI

UI podporuje výběr příloh ve více krocích před odesláním formuláře. Nově vybrané soubory se přidají do existující fronty a lze je před odesláním odebrat.

Jde pouze o pohodlí v prohlížeči. Autoritativní zůstává serverová validace.

## Lightbox galerie obrázků

Obrázkové přílohy se zobrazují jako malé náhledy. Kliknutí na náhled otevře jednoduchou chráněnou lightbox galerii používající controller preview URL.

Galerie umožňuje přecházet mezi viditelnými obrázkovými přílohami na detailu ticketu. Neobrázkové přílohy zůstávají běžnými odkazy ke stažení.

## Konfigurace

```env
HELPDESK_ATTACHMENT_MAX_SIZE_MB=20
HELPDESK_ATTACHMENT_MAX_FILES=10
HELPDESK_ATTACHMENT_DISK=local
HELPDESK_ATTACHMENT_PATH=ticket-attachments
```

`HELPDESK_ATTACHMENT_DISK=local` používá výchozí privátní lokální disk Laravelu.

## Sladění limitů aplikace, PHP a web serveru

Aplikační limit sám o sobě nestačí. PHP i web server musí povolit stejnou nebo větší velikost požadavku.

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

Pokud jsou limity PHP nebo Nginxu nižší než aplikační limit, upload může selhat ještě před spuštěním Laravel validace.

## Povolené a blokované typy souborů

Povolené přípony a MIME typy se nastavují v `config/helpdesk.php` a lze je upravit přes environment proměnné.

Nepovolujte spustitelné typy souborů jako:

- `exe`
- `bat`
- `cmd`
- `ps1`
- `msi`
- `sh`

Allow-list držte konzervativní a přidávejte jen typy souborů, které vaše organizace potřebuje.

## Zálohy

Zálohujte neveřejnou storage cestu obsahující přílohy. Záloha databáze bez storage příloh je neúplná, protože záznamy ticketů by odkazovaly na soubory, které už neexistují.
