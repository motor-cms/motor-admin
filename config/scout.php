<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Meilisearch settings. Meilisearch is an open
    | source search engine with minimal configuration. Below, you can state
    | the host and key information for your own Meilisearch installation.
    |
    | See: https://docs.meilisearch.com/guides/advanced_guides/configuration.html
    |
    */

    'meilisearch' => [
        'index-settings' => [
            \Motor\Admin\Models\User::class            => [
                'filterableAttributes' => ['client_id'],
                'sortableAttributes'   => ['users.id', 'client_id', 'created_at', 'updated_at', 'id', 'name', 'email'],
            ],
            \Motor\Admin\Models\Role::class            => [
                'filterableAttributes' => [],
                'sortableAttributes'   => ['roles.id', 'created_at', 'updated_at', 'id', 'name', 'guard_name'],
            ],
            \Motor\Admin\Models\PermissionGroup::class => [
                'filterableAttributes' => [],
                'sortableAttributes'   => ['permission_groups.id', 'created_at', 'updated_at', 'id', 'name', 'sort_position'],
            ],
            \Motor\Admin\Models\Permission::class      => [
                'filterableAttributes' => ['permission_group_id', 'guard_name'],
                'sortableAttributes'   => ['permissions.id', 'created_at', 'updated_at', 'id', 'name', 'guard_name'],
            ],
            \Motor\Admin\Models\Language::class        => [
                'filterableAttributes' => [],
                'sortableAttributes'   => ['languages.id', 'created_at', 'updated_at', 'id', 'native_name', 'english_name'],
            ],
            \Motor\Admin\Models\EmailTemplate::class   => [
                'filterableAttributes' => ['email_templates.client_id', 'client_id'],
                'sortableAttributes'   => ['email_templates.id', 'created_at', 'updated_at', 'id', 'name', 'slug'],
            ],
            \Motor\Admin\Models\ConfigVariable::class   => [
                'filterableAttributes' => ['package', 'group'],
                'sortableAttributes'   => ['config_variables.id', 'created_at', 'updated_at', 'id', 'name', 'package', 'group'],
            ],
            \Motor\Admin\Models\Client::class   => [
                'filterableAttributes' => [],
                'sortableAttributes'   => ['clients.id', 'created_at', 'updated_at', 'id', 'name'],
            ],
            \Motor\Admin\Models\Domain::class   => [
                'filterableAttributes' => [],
                'sortableAttributes'   => ['domains.id', 'created_at', 'updated_at', 'id', 'name'],
            ],
        ],
    ],
];
