<?php

return [
    'ticket' => [
        'subject' => '[Helpdesk #:number] :event',
        'greeting' => 'Dobrý den,',
        'action' => 'Zobrazit detail ticketu',
        'reply_marker' => 'Odpovězte nad tento řádek.',
        'events' => [
            'created' => 'Nový ticket',
            'public_comment' => 'Nový komentář',
            'assignee_changed' => 'Změna řešitele',
            'status_changed' => 'Změna statusu',
            'resolved' => 'Ticket vyřešen',
            'problem_persists' => 'Problém trvá',
            'closed' => 'Ticket uzavřen',
            'expected_resolution_changed' => 'Změna předpokládaného vyřešení',
        ],
        'lines' => [
            'event' => 'Událost: :event',
            'number' => 'Číslo ticketu: :number',
            'subject' => 'Předmět: :subject',
            'ticket_description' => 'Popis ticketu:',
            'comment_body' => 'Obsah komentáře:',
            'footer' => 'Tato zpráva byla odeslána automaticky z helpdesku.',
        ],
        'descriptions' => [
            'created' => 'V helpdesku byl vytvořen nový ticket.',
            'public_comment' => 'K ticketu byl přidán nový komentář.',
            'assignee_changed' => 'Řešitel ticketu byl změněn na: :assignee.',
            'status_changed' => 'Status ticketu byl změněn na: :status.',
            'resolved' => 'Ticket byl označen jako vyřešený. V detailu můžete potvrdit vyřešení, nebo oznámit, že problém trvá.',
            'problem_persists' => 'Zadavatel označil, že problém stále trvá.',
            'closed' => 'Ticket byl uzavřen.',
            'expected_resolution_changed' => 'Předpokládané vyřešení bylo změněno z :old_expected_resolution_at na :expected_resolution_at.',
        ],
    ],
    'inbound' => [
        'console' => [
            'finished' => 'Inbound e-maily zpracovány: :processed zpracováno, :ignored ignorováno, :failed selhalo.',
        ],
        'attachments_ignored' => [
            'comment_note' => '[Poznámka helpdesku: E-mail obsahoval přílohy, ale přílohy z e-mailových odpovědí zatím nejsou automaticky ukládány. Nahrajte je prosím přímo v detailu ticketu.]',
            'subject' => '[Helpdesk] :number: přílohy nebyly přidány',
            'greeting' => 'Dobrý den,',
            'body' => 'Vaše textová odpověď k ticketu :number byla přijata a uložena jako komentář. Přílohy z e-mailu ale zatím neumíme automaticky přidat, nahrajte je prosím přímo v detailu ticketu.',
            'action' => 'Otevřít detail ticketu',
        ],
    ],
];
