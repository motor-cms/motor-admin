<?php

return [
    'items' => [
        0   => [
            'slug'        => 'dashboard',
            'icon'        => 'home',
            'route'       => 'admin.dashboard',
            'roles'       => ['SuperAdmin'],
            'permissions' => ['dashboard.read'],
        ],
        900 => [
            'slug'        => 'administration',
            'icon'        => 'cogs',
            'route'       => null,
            'roles'       => ['SuperAdmin'],
            'permissions' => ['administration.read'],
            'items'       => [
                100 => [
                    'slug'        => 'users',
                    'icon'        => 'fa fa-user',
                    'route'       => 'admin.motor-admin.users',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['users.read'],
                ],
                110 => [
                    'slug'        => 'languages',
                    'icon'        => 'fa fa-globe',
                    'route'       => 'admin.motor-admin.languages',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['languages.read'],
                ],
                120 => [
                    'slug'        => 'clients',
                    'icon'        => 'fa fa-plus',
                    'route'       => 'admin.motor-admin.clients',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['clients.read'],
                ],
                130 => [
                    'slug'        => 'email_templates',
                    'icon'        => 'fa fa-plus',
                    'route'       => 'admin.motor-admin.email-templates',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['email_templates.read'],
                ],
                140 => [
                    'slug'        => 'roles',
                    'icon'        => 'fa fa-plus',
                    'route'       => 'admin.motor-admin.roles',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['roles.read'],
                ],
                150 => [
                    'slug'        => 'permissions',
                    'icon'        => 'fa fa-plus',
                    'route'       => 'admin.permissions.index',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['permissions.read'],
                ],
                160 => [
                    'slug'        => 'category_trees',
                    'icon'        => 'fa fa-plus',
                    'route'       => 'admin.motor-admin.category-trees',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['category_trees.read'],
                    'aliases'     => ['admin.categories'],
                ],
                170 => [
                    'slug'        => 'config_variables',
                    'icon'        => 'fa fa-plus',
                    'route'       => 'admin.motor-admin.config-variables',
                    'roles'       => ['SuperAdmin'],
                    'permissions' => ['config_variables.read'],
                ],
            ],
        ],
    ],
];
