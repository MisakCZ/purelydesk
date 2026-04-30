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

    /*
    |--------------------------------------------------------------------------
    | Helpdesk Authentication
    |--------------------------------------------------------------------------
    |
    | The application normally uses Laravel session auth backed by LDAP login.
    | The fallback user is intended only for explicit local development before
    | a login session exists.
    |
    */
    'auth' => [
        'allow_temporary_user_fallback' => env('HELPDESK_ALLOW_TEMPORARY_USER_FALLBACK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Helpdesk Notifications
    |--------------------------------------------------------------------------
    |
    | Outgoing helpdesk mail notifications use Laravel's standard mail
    | configuration. In production this can point to local Postfix via
    | MAIL_MAILER=smtp, MAIL_HOST=127.0.0.1 and MAIL_PORT=25.
    |
    */
    'notifications' => [
        'mail' => [
            'enabled' => env('HELPDESK_MAIL_NOTIFICATIONS', false),
            'notify_solvers_on_new_tickets' => env('HELPDESK_NOTIFY_SOLVERS_ON_NEW_TICKETS', true),
            'notify_admins_on_new_tickets' => env('HELPDESK_NOTIFY_ADMINS_ON_NEW_TICKETS', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ticket Attachments
    |--------------------------------------------------------------------------
    |
    | Attachments are stored on a non-public Laravel disk and served only
    | through authorized controller actions.
    |
    */
    'attachments' => [
        'disk' => env('HELPDESK_ATTACHMENT_DISK', 'local'),
        'path' => env('HELPDESK_ATTACHMENT_PATH', 'ticket-attachments'),
        'max_size_mb' => (int) env('HELPDESK_ATTACHMENT_MAX_SIZE_MB', 20),
        'max_files' => (int) env('HELPDESK_ATTACHMENT_MAX_FILES', 10),
        'allowed_extensions' => array_filter(array_map('trim', explode(',', env(
            'HELPDESK_ATTACHMENT_ALLOWED_EXTENSIONS',
            'jpg,jpeg,png,gif,webp,pdf,txt,csv,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp,rtf,zip',
        )))),
        'allowed_mime_types' => array_filter(array_map('trim', explode(',', env(
            'HELPDESK_ATTACHMENT_ALLOWED_MIME_TYPES',
            'image/jpeg,image/png,image/gif,image/webp,application/pdf,text/plain,text/csv,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.oasis.opendocument.text,application/vnd.oasis.opendocument.spreadsheet,application/vnd.oasis.opendocument.presentation,application/rtf,application/zip,application/x-zip-compressed',
        )))),
    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP Authentication
    |--------------------------------------------------------------------------
    |
    | Generic LDAP settings. eDirectory can be configured here through filters
    | and attribute names without coupling application code to one LDAP server.
    |
    */
    'ldap' => [
        'enabled' => env('LDAP_ENABLED', false),
        'host' => env('LDAP_HOST'),
        'port' => (int) env('LDAP_PORT', 389),
        'encryption' => env('LDAP_ENCRYPTION', 'none'),
        'base_dn' => env('LDAP_BASE_DN'),
        'bind_dn' => env('LDAP_BIND_DN'),
        'bind_password' => env('LDAP_BIND_PASSWORD'),
        'user_filter' => env('LDAP_USER_FILTER', '(&(objectClass=person)(uid={username}))'),
        'username_attribute' => env('LDAP_USERNAME_ATTRIBUTE', 'uid'),
        'email_attribute' => env('LDAP_EMAIL_ATTRIBUTE', 'mail'),
        'display_name_attribute' => env('LDAP_DISPLAY_NAME_ATTRIBUTE', 'cn'),
        'display_name_attributes' => env('LDAP_DISPLAY_NAME_ATTRIBUTES', 'displayName,fullName,cn'),
        'unique_id_attribute' => env('LDAP_UNIQUE_ID_ATTRIBUTE', 'guid'),
        'department_attribute' => env('LDAP_DEPARTMENT_ATTRIBUTE', 'department'),
        'user_group_attributes' => env('LDAP_USER_GROUP_ATTRIBUTES', 'memberOf,groupMembership'),
        'groups_enabled' => env('LDAP_GROUPS_ENABLED', false),
        'group_base_dn' => env('LDAP_GROUP_BASE_DN'),
        'group_filter' => env('LDAP_GROUP_FILTER', '(objectClass=groupOfNames)'),
        'group_member_attribute' => env('LDAP_GROUP_MEMBER_ATTRIBUTE', 'member'),
        'role_user_groups' => env('LDAP_ROLE_USER_GROUPS', ''),
        'role_solver_groups' => env('LDAP_ROLE_SOLVER_GROUPS', ''),
        'role_admin_groups' => env('LDAP_ROLE_ADMIN_GROUPS', ''),
        'allow_default_user_role' => env('LDAP_ALLOW_DEFAULT_USER_ROLE', true),
        'network_timeout' => (int) env('LDAP_NETWORK_TIMEOUT', 5),
    ],
];
