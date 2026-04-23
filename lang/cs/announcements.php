<?php

return [
    'formats' => [
        'datetime' => 'j. n. Y H:i',
    ],
    'index' => [
        'page_title' => 'Oznámení',
        'heading' => 'Provozní oznámení',
        'subheading' => 'Jednoduchá interní správa oznámení zobrazovaných nad seznamem ticketů.',
        'actions' => [
            'back_to_tickets' => 'Zpět na tickety',
            'edit' => 'Upravit',
            'delete' => 'Smazat',
            'save' => 'Uložit oznámení',
        ],
        'sections' => [
            'existing_heading' => 'Existující oznámení',
            'existing_subheading' => 'Nad seznamem ticketů se zobrazují jen oznámení, která jsou právě aktivní a spadají do zvoleného období.',
            'create_heading' => 'Nové oznámení',
            'create_subheading' => 'Zatím bez plného řízení oprávnění. Nové oznámení se ukládá jako veřejné a aktivní podle zadaných dat.',
        ],
        'empty' => [
            'heading' => 'Zatím nejsou založená žádná oznámení',
            'body' => 'Po vytvoření prvního oznámení se objeví tady i nad seznamem ticketů, pokud bude právě aktivní.',
        ],
        'meta' => [
            'author' => 'Autor: :name',
            'visibility' => 'Viditelnost: :value',
            'starts_at' => 'Od: :date',
            'ends_at' => 'Do: :date',
            'created_at' => 'Vytvořeno: :date',
        ],
        'state' => [
            'active' => 'Aktivní',
            'inactive' => 'Neaktivní',
        ],
    ],
    'edit' => [
        'page_title' => 'Upravit oznámení',
        'heading' => 'Upravit oznámení',
        'subheading' => 'Správa obsahu, aktivity a časového okna provozního oznámení.',
        'panel_subheading' => 'Upravte obsah, aktivitu a časové okno oznámení.',
        'panel_label' => 'Editace oznámení',
        'actions' => [
            'back' => 'Zpět na oznámení',
            'save' => 'Uložit změny',
            'cancel' => 'Zrušit',
        ],
    ],
    'form' => [
        'title' => 'Nadpis',
        'type' => 'Typ',
        'body' => 'Text',
        'starts_at' => 'Aktivní od',
        'ends_at' => 'Aktivní do',
        'is_active' => 'Oznámení je aktivní',
    ],
    'flash' => [
        'created' => 'Oznámení bylo úspěšně vytvořeno.',
        'updated' => 'Oznámení bylo úspěšně upraveno.',
        'deleted' => 'Oznámení bylo úspěšně smazáno.',
    ],
    'validation' => [
        'author_missing' => 'Oznámení zatím nelze uložit, protože v databázi neexistuje žádný uživatel.',
    ],
    'values' => [
        'visibility' => [
            'public' => 'Veřejné',
            'internal' => 'Interní',
            'private' => 'Privátní',
        ],
    ],
    'types' => [
        'info' => 'Info',
        'warning' => 'Varování',
        'outage' => 'Výpadek',
        'maintenance' => 'Údržba',
    ],
];
