<?php

use Motor\Admin\Models\AISystemPrompt;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\ConfigVariable;
use Motor\Admin\Models\Domain;
use Motor\Admin\Models\EmailTemplate;
use Motor\Admin\Models\Language;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\PermissionGroup;
use Motor\Admin\Models\Role;
use Motor\Admin\Models\User;

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
            User::class => [
                'filterableAttributes' => ['client_id'],
                'sortableAttributes'   => ['users.id', 'client.name', 'client_id', 'created_at', 'updated_at', 'id', 'name', 'email'],
                'rankingRules'         => ['sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],

            ],
            Role::class => [
                'filterableAttributes' => ['guard_name'],
                'sortableAttributes'   => ['roles.id', 'created_at', 'updated_at', 'id', 'name', 'guard_name'],
                'rankingRules'         => ['sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            PermissionGroup::class => [
                'filterableAttributes' => [],
                'sortableAttributes'   => ['permission_groups.id', 'created_at', 'updated_at', 'id', 'name', 'sort_position'],
                'rankingRules'         => ['sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            Permission::class => [
                'filterableAttributes' => ['permission_group_id', 'guard_name'],
                'sortableAttributes'   => ['permissions.id', 'created_at', 'updated_at', 'id', 'name', 'guard_name'],
                'rankingRules'         => ['sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            Language::class => [
                'filterableAttributes' => [],
                'sortableAttributes'   => ['languages.id', 'is_active', 'created_at', 'updated_at', 'id', 'native_name', 'english_name', 'iso_639_1'],
                'rankingRules'         => ['sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            EmailTemplate::class => [
                'filterableAttributes' => ['email_templates.client_id', 'client_id', 'language_id'],
                'sortableAttributes'   => ['email_templates.id', 'is_active', 'language.english_name', 'client.name', 'email_templates.updated_at', 'email_templates.created_at', 'created_at', 'updated_at', 'id', 'name', 'slug'],
                'rankingRules'         => ['sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            ConfigVariable::class => [
                'filterableAttributes' => ['package', 'group', 'is_invisible'],
                'sortableAttributes'   => ['config_variables.id', 'value', 'is_active', 'created_at', 'updated_at', 'id', 'name', 'package', 'group'],
                'rankingRules'         => ['sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            Client::class => [
                'filterableAttributes' => ['is_active'],
                'sortableAttributes'   => ['language.english_name', 'clients.id', 'value', 'is_active', 'slug', 'contact_name', 'created_at', 'updated_at', 'id', 'name'],
                'rankingRules'         => ['sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            Domain::class => [
                'filterableAttributes' => ['domains.client_id', 'client_id', 'is_active'],
                'sortableAttributes'   => ['domains.id', 'client.name', 'is_active', 'host', 'created_at', 'updated_at', 'id', 'name'],
                'rankingRules'         => ['sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],
            AISystemPrompt::class => [
                'filterableAttributes' => ['client_id'],
                'sortableAttributes'   => ['client.name', 'created_at', 'updated_at', 'id', 'name'],
                'rankingRules'         => ['sort', 'words', 'typo', 'proximity', 'attribute', 'exactness'],
            ],        ],
    ],
];
