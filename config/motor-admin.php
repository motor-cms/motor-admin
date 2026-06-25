<?php

use Motor\Admin\Models\Client;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Models\Language;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\PermissionGroup;
use Motor\Admin\Models\Role;
use Motor\Admin\Models\User;

return [
    'models' => [
        'client'           => Client::class,
        'language'         => Language::class,
        'user'             => User::class,
        'role'             => Role::class,
        'permission'       => Permission::class,
        'permission_group' => PermissionGroup::class,
        'email_template'   => EmailTemplate::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected email template slugs
    |--------------------------------------------------------------------------
    |
    | Email templates whose slug is listed here cannot be deleted via the API.
    | These are "system" templates that external systems look up by slug (e.g.
    | the ekpro-api sending the Mastercard confirmation mail); deleting them
    | causes 404s and silent mail failures on the website (ZRMDEV-233).
    |
    | The defaults below cover the generic motor-backend-error-template and the
    | energis "Mastercard" confirmation mail (masterdata_confirmation_email),
    | which the ekpro-api sends after processing an order. Further deployment-
    | specific slugs are added via the comma-separated env var
    | MOTOR_ADMIN_PROTECTED_EMAIL_TEMPLATE_SLUGS and merged in, so no code
    | change is needed to protect additional templates.
    |
    */
    'protected_email_template_slugs' => array_values(array_unique(array_filter(array_merge(
        [
            'motor-backend-error-template',
            'masterdata_confirmation_email',
        ],
        array_map('trim', explode(',', (string) env('MOTOR_ADMIN_PROTECTED_EMAIL_TEMPLATE_SLUGS', ''))),
    )))),
];
