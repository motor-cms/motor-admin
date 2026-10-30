<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\Role;
use Motor\Admin\Models\User;

pest()->group('Role', 'Permission', 'ZRMDEV-237', 'security')->use(RefreshDatabase::class);

// ZRMDEV-237, second path: the June fix stopped users from assigning roles to
// themselves, but roles.write still let a user add any permission to a role
// they hold (or rename SuperAdmin away and their own role to SuperAdmin), and
// permissions.write let them rename a held permission into a powerful one.
// Creating, changing and deleting roles and permissions is SuperAdmin-only.

function userWithRole(string $roleName, array $permissions): User
{
    $role = Role::create(['name' => $roleName, 'guard_name' => 'web']);
    $role->givePermissionTo($permissions);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

describe('ZRMDEV-237 role/permission write escalation', function () {
    it('does not let a non-SuperAdmin with roles.write add permissions to their own role (V2)', function () {
        $user = userWithRole('Role Manager', ['roles.read', 'roles.write']);
        $role = $user->roles->first();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch('/api/v2/roles/'.$role->id, [
                'name'        => $role->name,
                'guard_name'  => 'web',
                'permissions' => Permission::pluck('id')->all(),
            ])->assertStatus(403);

        expect($role->fresh()->hasPermissionTo('users.delete'))->toBeFalse();
    });

    it('does not let a non-SuperAdmin with roles.write add permissions to their own role (unversioned)', function () {
        $user = userWithRole('Role Manager', ['roles.read', 'roles.write']);
        $role = $user->roles->first();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch('/api/roles/'.$role->id, [
                'name'        => $role->name,
                'guard_name'  => 'web',
                'permissions' => Permission::pluck('id')->all(),
            ])->assertStatus(403);

        expect($role->fresh()->hasPermissionTo('users.delete'))->toBeFalse();
    });

    it('does not let a non-SuperAdmin with roles.write rename the SuperAdmin role', function () {
        $user = userWithRole('Role Manager', ['roles.read', 'roles.write']);
        $superAdmin = Role::whereName('SuperAdmin')->first();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch('/api/v2/roles/'.$superAdmin->id, [
                'name'       => 'Retired',
                'guard_name' => 'web',
            ])->assertStatus(403);

        expect($superAdmin->fresh()->name)->toBe('SuperAdmin');
    });

    it('does not let a non-SuperAdmin with roles.write create a role', function () {
        $user = userWithRole('Role Manager', ['roles.read', 'roles.write']);
        $countBefore = Role::count();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/roles', [
                'name'        => 'Shadow Admin',
                'guard_name'  => 'web',
                'permissions' => Permission::pluck('id')->all(),
            ])->assertStatus(403);

        expect(Role::count())->toBe($countBefore);
    });

    it('does not let a non-SuperAdmin with roles.delete delete the SuperAdmin role', function () {
        $user = userWithRole('Role Manager', ['roles.read', 'roles.delete']);
        $superAdmin = Role::whereName('SuperAdmin')->first();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->delete('/api/v2/roles/'.$superAdmin->id)
            ->assertStatus(403);

        expect(Role::whereName('SuperAdmin')->exists())->toBeTrue();
    });

    it('does not let a non-SuperAdmin with permissions.write rename a permission they hold', function () {
        $user = userWithRole('Permission Manager', ['permissions.read', 'permissions.write']);
        $held = Permission::whereName('permissions.read')->first();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch('/api/v2/permissions/'.$held->id, [
                'name'                => 'users.delete-everything',
                'guard_name'          => 'web',
                'permission_group_id' => $held->permission_group_id,
            ])->assertStatus(403);

        expect($held->fresh()->name)->toBe('permissions.read');
    });

    it('does not let a non-SuperAdmin with permissions.write create a permission', function () {
        $user = userWithRole('Permission Manager', ['permissions.read', 'permissions.write']);
        $countBefore = Permission::count();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/permissions', [
                'name'                => 'shadow.write',
                'guard_name'          => 'web',
                'permission_group_id' => Permission::first()->permission_group_id,
            ])->assertStatus(403);

        expect(Permission::count())->toBe($countBefore);
    });

    it('still lets a non-SuperAdmin with roles.read list roles', function () {
        $user = userWithRole('Role Reader', ['roles.read']);

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/api/v2/roles')
            ->assertStatus(200);
    });

    it('still lets a SuperAdmin change the permissions of a role', function () {
        $role = Role::whereName('Editor')->first();
        $permission = Permission::whereName('users.delete')->first();
        expect($role->hasPermissionTo($permission))->toBeFalse();

        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->patch('/api/v2/roles/'.$role->id, [
                'name'        => $role->name,
                'guard_name'  => 'web',
                'permissions' => [$permission->id],
            ])->assertStatus(200);

        expect($role->fresh()->hasPermissionTo('users.delete'))->toBeTrue();
    });
});
