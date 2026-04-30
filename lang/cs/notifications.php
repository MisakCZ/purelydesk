<?php

return [
    'ticket' => [
        'subject' => '[Helpdesk] :number: :event',
        'greeting' => 'Dobrý den,',
        'action' => 'Zobrazit detail ticketu',
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
            'resolved' => 'Ticket byl označen jako vyřešený.',
            'problem_persists' => 'Zadavatel označil, že problém stále trvá.',
            'closed' => 'Ticket byl uzavřen.',
            'expected_resolution_changed' => 'Předpokládané vyřešení bylo změněno na: :expected_resolution_at.',
        ],
    ],
];
