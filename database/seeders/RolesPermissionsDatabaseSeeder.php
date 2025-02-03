<?php

namespace Motor\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\Role;

class RolesPermissionsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $writerRole = Role::where('name', 'Writer')->first();
        $editorRole = Role::where('name', 'Editor')->first();

        $writerPermissions = [
            'dashboard.read',
            'builder.read',
            'builder_pages.read',
            'builder_pages.write',
            'builder_pages.delete',
            'builder_components.read',
            'builder_components.write',
            'builder_components.delete',
        ];

        $editorPermissions = [
            'builder_pages.publisher',
        ];

        array_push($editorPermissions, ...$writerPermissions);

        $this->givePermissionsToRole($writerPermissions, $writerRole);
        $this->givePermissionsToRole($editorPermissions, $editorRole);
    }

    private function givePermissionsToRole(array $permissions, Role $role): void
    {
        foreach ($permissions as $permission) {
            $role->givePermissionTo(
                Permission::where('name', $permission)->first()
            );
        }
    }
}
