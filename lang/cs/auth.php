<?php

return [
    'failed' => 'Zadané přihlašovací údaje nejsou platné.',
    'password' => 'Zadané heslo není správné.',
    'throttle' => 'Příliš mnoho pokusů o přihlášení. Zkuste to znovu za :seconds sekund.',
    'ldap_unavailable' => 'Přihlašovací služba je momentálně nedostupná. Zkuste to prosím později.',
    'ldap_not_authorized' => 'Váš účet nemá oprávnění pro přístup do helpdesku.',
    'login' => [
        'page_title' => 'Přihlášení',
        'title' => 'Přihlášení do helpdesku',
        'subtitle' => 'Použijte své běžné LDAP přihlašovací údaje.',
        'username' => 'Uživatelské jméno',
        'password' => 'Heslo',
        'submit' => 'Přihlásit',
        'demo' => [
            'title' => 'Demo přihlášení je zapnuté pro lokální vývoj.',
            'description' => 'Použijte jeden z těchto lokálních demo účtů:',
            'accounts' => [
                'admin@example.org / password',
                'solver@example.org / password',
                'user@example.org / password',
            ],
        ],
    ],
];
