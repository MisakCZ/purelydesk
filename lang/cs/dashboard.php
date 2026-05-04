<?php

return [
    'page_title' => 'Dashboard',
    'heading' => 'Dashboard',
    'subheading' => 'Kompaktní přehled vaší helpdesk pracovní fronty.',
    'actions' => [
        'new_ticket' => 'Nový ticket',
        'all_tickets' => 'Úplný seznam ticketů',
        'open_list' => 'Otevřít seznam',
        'new_ticket_hint' => 'Před založením nového požadavku zkontrolujte, jestli se už neřeší:',
        'check_open_tickets' => 'otevřené tickety',
    ],
    'announcements' => [
        'label' => 'Aktivní provozní oznámení',
        'heading' => 'Provozní oznámení',
        'view_all' => 'Zobrazit všechna oznámení',
        'open' => 'Otevřít oznámení',
        'validity' => 'Platnost',
        'from' => 'od',
        'to' => 'do',
    ],
    'summary' => [
        'label' => 'Souhrn pracovní fronty',
        'new_unassigned_tickets' => 'Nové nepřiřazené',
        'my_assigned_tickets' => 'Moje přiřazené',
        'waiting_for_user' => 'Čeká na uživatele',
        'due_soon_or_overdue' => 'Po termínu / blíží se termín',
    ],
    'pinned' => [
        'label' => 'Připnuté tickety',
        'heading' => 'Důležité otevřené tickety',
        'subheading' => 'Připnuté tickety, které jsou pro vás viditelné. Pomáhají ověřit, jestli se problém už neřeší.',
    ],
    'personal' => [
        'heading' => 'Moje zadané tickety',
        'open_requested' => '{0} Nemáte žádné otevřené zadané tickety.|{1} Máte 1 otevřený zadaný ticket.|[2,4] Máte :count otevřené zadané tickety.|[5,*] Máte :count otevřených zadaných ticketů.',
        'open_link' => 'Otevřít moje zadané',
    ],
    'sections' => [
        'limited_preview' => 'Zobrazeno posledních :count položek. Úplný výpis je v seznamu.',
        'my_open_tickets' => [
            'heading' => 'Moje otevřené tickety',
            'subheading' => 'Tickety, které jste zadali a nejsou uzavřené ani zrušené.',
            'empty' => 'Nemáte žádné otevřené tickety.',
        ],
        'waiting_for_confirmation' => [
            'heading' => 'Čeká na moje potvrzení',
            'subheading' => 'Vaše vyřešené tickety čekající na potvrzení.',
            'empty' => 'Žádné tickety nečekají na vaše potvrzení.',
        ],
        'new_unassigned_tickets' => [
            'heading' => 'Nové nepřiřazené tickety',
            'subheading' => 'Viditelné nové tickety, které ještě nejsou přiřazené řešiteli.',
            'empty' => 'Nejsou tu žádné nové nepřiřazené tickety.',
        ],
        'my_assigned_tickets' => [
            'heading' => 'Moje přiřazené tickety',
            'subheading' => 'Otevřené tickety aktuálně přiřazené vám.',
            'empty' => 'Nemáte žádné přiřazené otevřené tickety.',
        ],
        'waiting_for_user' => [
            'heading' => 'Čeká na uživatele',
            'subheading' => 'Tickety čekající na doplnění od zadavatele.',
            'empty' => 'Žádné viditelné tickety nečekají na uživatele.',
        ],
        'resolved_waiting_confirmation' => [
            'heading' => 'Vyřešené čekající na potvrzení',
            'subheading' => 'Vyřešené tickety, které vidíte.',
            'empty' => 'Žádné vyřešené tickety nečekají na potvrzení.',
        ],
        'due_soon_or_overdue' => [
            'heading' => 'Po termínu / blíží se termín',
            'subheading' => 'Otevřené tickety s očekávaným vyřešením během tří dnů nebo už po termínu.',
            'empty' => 'Žádné viditelné tickety nejsou po termínu ani před blízkým termínem.',
        ],
    ],
    'admin' => [
        'heading' => 'Administrace',
        'subheading' => 'Rychlé odkazy pro administrativní práci.',
        'links' => [
            'tickets' => 'Seznam ticketů',
            'archive' => 'Archivované tickety',
            'announcements' => 'Oznámení',
        ],
    ],
    'ticket' => [
        'updated_at' => 'Aktualizováno :date',
        'expected_resolution_at' => 'Předpokládané vyřešení: :date',
    ],
];
