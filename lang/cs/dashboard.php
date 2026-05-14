<?php

return [
    'page_title' => 'Dashboard',
    'heading' => 'Dashboard',
    'subheading' => 'Kompaktní přehled vaší helpdesk pracovní fronty.',
    'actions' => [
        'new_ticket' => 'Nový ticket',
        'all_tickets' => 'Úplný seznam ticketů',
        'open_list' => 'Otevřít seznam',
        'open_full_list' => 'Otevřít úplný seznam',
        'view_all' => 'Zobrazit vše',
        'new_ticket_hint' => 'Před založením nového požadavku zkontrolujte, jestli se už neřeší:',
        'check_open_tickets' => 'otevřené tickety',
        'new_ticket_tooltip' => 'Před zadáním obecného ticketu se prosím podívejte, zda už není zadaný jiným uživatelem.',
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
        'new_unassigned_tickets' => 'Nové',
        'my_assigned_tickets' => 'Moje',
        'due_today' => 'Termín dnes',
        'due_soon' => 'Termín brzy',
        'waiting_for_user' => 'Čeká se',
        'without_expected_resolution' => 'Bez termínu',
        'due_soon_or_overdue' => 'Po termínu / blíží se termín',
    ],
    'summary_notes' => [
        'new_unassigned_tickets' => 'Bez řešitele',
        'my_assigned_tickets' => 'Vaše aktivní fronta',
        'due_today' => 'K vyřešení dnes',
        'due_soon' => 'Do 24 hodin',
        'waiting_for_user' => 'Doplnění uživatele',
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
        'without_expected_resolution' => [
            'heading' => 'Bez očekávaného termínu',
            'subheading' => 'Vaše přiřazené otevřené tickety bez očekávaného termínu vyřešení.',
            'empty' => 'Všechny vaše přiřazené otevřené tickety mají očekávaný termín vyřešení.',
        ],
    ],
    'current' => [
        'heading' => 'Aktuální tickety',
        'subheading' => 'Zobrazeno posledních :count položek. Úplný výpis je v seznamu.',
        'empty' => 'Nejsou tu žádné aktuální tickety pro pracovní přehled.',
        'columns' => [
            'id' => 'ID',
            'subject' => 'Předmět',
            'requester' => 'Zadavatel',
            'priority' => 'Priorita',
            'status' => 'Stav',
            'deadline' => 'Termín',
            'updated_at' => 'Aktualizováno',
        ],
    ],
    'sla' => [
        'heading' => 'SLA / Termíny',
        'none' => 'Žádný relevantní ticket',
        'overdue' => [
            'label' => 'Po termínu',
            'ticket' => 'Nejstarší: :ticket',
            'note' => 'Vyžaduje pozornost',
        ],
        'due_soon' => [
            'label' => 'Blíží se termín (≤ 24 h)',
            'ticket' => 'Nejbližší: :ticket',
            'note' => 'Zbývá méně než 24 h',
        ],
        'due_today' => [
            'label' => 'Dnes k vyřízení',
            'ticket' => 'Nejbližší: :ticket',
            'note' => 'Do konce dne',
        ],
        'resolved' => [
            'label' => 'Vyřešené',
            'ticket' => 'Poslední: :ticket',
            'note_solver' => 'Vaše vyřešené tickety',
            'note_requester' => 'Vaše vyřešené požadavky',
        ],
    ],
    'diagnostics' => [
        'heading' => 'Kontrola termínů',
        'missing_expected_resolution' => '{1} 1 přiřazený otevřený ticket nemá očekávaný termín vyřešení.|[2,4] :count přiřazené otevřené tickety nemají očekávaný termín vyřešení.|[5,*] :count přiřazených otevřených ticketů nemá očekávaný termín vyřešení.',
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
