<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Helpdesk Administrative Mode
    |--------------------------------------------------------------------------
    |
    | Temporary application-wide administrative mode. When enabled, restricted
    | tickets remain visible in the helpdesk UI even before full roles and
    | policies are integrated.
    |
    */
    'admin_mode' => env('HELPDESK_ADMIN_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Supported UI Locales
    |--------------------------------------------------------------------------
    |
    | The helpdesk UI is currently prepared for Czech and English translations.
    | The locale switch itself can be added later without another UI refactor.
    |
    */
    'supported_locales' => ['cs', 'en'],
];
