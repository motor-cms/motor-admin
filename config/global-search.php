<?php

return [
    'modules' => [
        'user' => [
            'index' => 'motor_admin_users_index',
            'model' => \Motor\Admin\Models\User::class,
            'title_field' => 'name',
            'excerpt_field' => 'email',
            'meta_fields' => [],
            'default_filter' => null,
        ],
        'domain' => [
            'index' => 'motor_admin_domains_index',
            'model' => \Motor\Admin\Models\Domain::class,
            'title_field' => 'name',
            'excerpt_field' => 'host',
            'meta_fields' => ['protocol', 'port', 'is_active'],
            'default_filter' => null,
        ],
        'email_template' => [
            'index' => 'motor_admin_email_templates_index',
            'model' => \Motor\Admin\Models\EmailTemplate::class,
            'title_field' => 'name',
            'excerpt_field' => 'subject',
            'meta_fields' => [],
            'default_filter' => null,
        ],
        'language' => [
            'index' => 'motor_admin_languages_index',
            'model' => \Motor\Admin\Models\Language::class,
            'title_field' => 'native_name',
            'excerpt_field' => 'english_name',
            'meta_fields' => ['iso_639_1'],
            'default_filter' => null,
        ],
    ],
];
