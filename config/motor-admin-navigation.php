<?php

return [
    'items' => [
        0   => [
            'slug'        => 'dashboard',
            'icon'        => 'home',
            'route'       => 'admin.dashboard',
            'roles'       => ['SuperAdmin'],
            'permissions' => ['dashboard.read'],
            'name'        => 'motor-admin.global.dashboard',
        ],
        900 => [
            'slug'        => 'administration',
            'icon'        => 'cogs',
            'route'       => null,
            'roles'       => ['SuperAdmin'],
            'permissions' => ['administration.read'],
            'name'        => 'motor-admin.global.administration',
            'items'       => [
                100 => [
                    'slug'        => 'users',
                    'icon'        => 'fa fa-user',
                    'route'       => 'admin.motor-admin.users',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['users.read'],
                    'name'        => 'motor-admin.users.users',
                ],
                110 => [
                    'slug'        => 'languages',
                    'icon'        => 'fa fa-globe',
                    'route'       => 'admin.motor-admin.languages',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['languages.read'],
                    'name'        => 'motor-admin.languages.languages',
                ],
                120 => [
                    'slug'        => 'clients',
                    'icon'        => 'fa fa-plus',
                    'route'       => 'admin.motor-admin.clients',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['clients.read'],
                    'name'        => 'motor-admin.clients.clients',
                ],
                130 => [
                    'slug'        => 'email_templates',
                    'icon'        => 'fa fa-plus',
                    'route'       => 'admin.motor-admin.email-templates',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['email_templates.read'],
                    'name'        => 'motor-admin.email_templates.email_templates',
                ],
                140 => [
                    'slug'        => 'roles',
                    'icon'        => 'fa fa-plus',
                    'route'       => 'admin.motor-admin.roles',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['roles.read'],
                    'name'        => 'motor-admin.roles.roles',
                ],
                150 => [
                    'slug'        => 'permissions',
                    'icon'        => 'fa fa-plus',
                    'route'       => 'admin.permissions.index',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['permissions.read'],
                    'name'        => 'motor-admin.permissions.permissions',
                ],
                160 => [
                    'slug'        => 'category_trees',
                    'icon'        => 'fa fa-plus',
                    'route'       => 'admin.motor-admin.category-trees',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['category_trees.read'],
                    'aliases'     => ['admin.categories'],
                    'name'        => 'motor-admin.category_trees.category_trees',
                ],
                170 => [
                    'slug'        => 'config_variables',
                    'icon'        => 'fa fa-plus',
                    'route'       => 'admin.motor-admin.config-variables',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['config_variables.read'],
                    'name'        => 'motor-admin.config_variables.config_variables',
                ],
            ],
        ],
    ],
];
