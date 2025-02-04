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
                'sortableAttributes'   => ['users.id', 'client.name', 'client_id', 'created_at', 'updated_at', 'id', 'name', 'email'],
                'rankingRules' => [ 'sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],

            ],
            \Motor\Admin\Models\Role::class            => [
                'filterableAttributes' => [],
                'sortableAttributes'   => ['roles.id', 'created_at', 'updated_at', 'id', 'name', 'guard_name'],
                'rankingRules' => [ 'sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
           ],
            \Motor\Admin\Models\PermissionGroup::class => [
                'filterableAttributes' => [],
                'sortableAttributes'   => ['permission_groups.id', 'created_at', 'updated_at', 'id', 'name', 'sort_position'],
                'rankingRules' => [ 'sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            \Motor\Admin\Models\Permission::class      => [
                'filterableAttributes' => ['permission_group_id', 'guard_name'],
                'sortableAttributes'   => ['permissions.id', 'created_at', 'updated_at', 'id', 'name', 'guard_name'],
                'rankingRules' => [ 'sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            \Motor\Admin\Models\Language::class        => [
                'filterableAttributes' => [],
                'sortableAttributes'   => ['languages.id', 'created_at', 'updated_at', 'id', 'native_name', 'english_name', 'iso_639_1'],
                'rankingRules' => [ 'sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            \Motor\Admin\Models\EmailTemplate::class   => [
                'filterableAttributes' => ['email_templates.client_id', 'client_id'],
                'sortableAttributes'   => ['email_templates.id', 'language.english_name', 'client.name', 'created_at', 'updated_at', 'id', 'name', 'slug'],
                'rankingRules' => [ 'sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            \Motor\Admin\Models\ConfigVariable::class   => [
                'filterableAttributes' => ['package', 'group'],
                'sortableAttributes'   => ['config_variables.id', 'value', 'created_at', 'updated_at', 'id', 'name', 'package', 'group'],
                'rankingRules' => [ 'sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            \Motor\Admin\Models\Client::class   => [
                'filterableAttributes' => [],
                'sortableAttributes'   => ['clients.id', 'value', 'slug', 'contact_name', 'created_at', 'updated_at', 'id', 'name'],
                'rankingRules' => [ 'sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            \Motor\Admin\Models\Domain::class   => [
                'filterableAttributes' => [],
                'sortableAttributes'   => ['domains.id', 'client.name', 'is_active', 'host', 'created_at', 'updated_at', 'id', 'name'],
                'rankingRules' => [ 'sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
        ],
    ],
];
