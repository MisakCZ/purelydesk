<?php

return [
    'failed' => 'These credentials are not valid.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'ldap_unavailable' => 'The sign-in service is currently unavailable. Please try again later.',
    'ldap_not_authorized' => 'Your account is not authorized to access the helpdesk.',
    'login' => [
        'page_title' => 'Sign in',
        'title' => 'Sign in to helpdesk',
        'subtitle' => 'Use your standard LDAP credentials.',
        'username' => 'Username',
        'password' => 'Password',
        'submit' => 'Sign in',
        'demo' => [
            'title' => 'Demo login is enabled for local development.',
            'description' => 'Use one of these local demo accounts:',
            'accounts' => [
                'admin@example.org / password',
                'solver@example.org / password',
                'user@example.org / password',
            ],
        ],
    ],
];
