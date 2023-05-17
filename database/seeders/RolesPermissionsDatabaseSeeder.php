<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\Role;

class RolesPermissionsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $publishPermission = Permission::where('name', 'builder_pages.publisher')->first();
        $adminRole = Role::where('name', 'SuperAdmin')->first();
        $editorRole = Role::where('name', 'Editor')->first();

        $adminRole->givePermissionTo($publishPermission);
        $editorRole->givePermissionTo($publishPermission);
    }
}
