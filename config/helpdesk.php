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
];
