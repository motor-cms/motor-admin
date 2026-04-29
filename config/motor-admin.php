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
];
