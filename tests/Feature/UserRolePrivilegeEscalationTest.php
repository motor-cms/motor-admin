<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Role;
use Motor\Admin\Models\User;

pest()->group('User', 'ZRMDEV-237', 'security')->use(RefreshDatabase::class);

// ZRMDEV-237: roles and permissions are a privilege-escalation surface.
// UserPolicy::update permits a user to update their own account, and the
// role/permission sync ran unconditionally — so any authenticated user could
// PATCH their own record with roles: [SuperAdmin] and take over the system,
// then revoke anyone else's SuperAdmin. Only SuperAdmins may change
// roles/permissions now.

function superAdminRoleId(): int
{
    return Role::whereName('SuperAdmin')->first()->id;
}

function nonAdminUser(): User
{
    // Seeded "Authenticated" user — should only be able to log in (no roles).
    return User::whereEmail('auth@motor-cms.com')->first();
}

describe('ZRMDEV-237 role/permission privilege escalation', function () {
    it('does not let a non-SuperAdmin grant themselves SuperAdmin via the V2 self-update', function () {
        $user = nonAdminUser();
        expect($user->hasRole('SuperAdmin'))->toBeFalse();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch('/api/v2/users/'.$user->id, [
                'email' => $user->email,
                'name'  => 'Hacker',
                'roles' => [superAdminRoleId()],
            ]);

        expect($user->fresh()->hasRole('SuperAdmin'))->toBeFalse();
    });

    it('does not let a non-SuperAdmin grant themselves SuperAdmin via the unversioned self-update', function () {
        $user = nonAdminUser();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch('/api/users/'.$user->id, [
                'email' => $user->email,
                'name'  => $user->name,
                'roles' => [superAdminRoleId()],
            ]);

        expect($user->fresh()->hasRole('SuperAdmin'))->toBeFalse();
    });

    it('still applies allowed field updates while ignoring the role payload', function () {
        $user = nonAdminUser();

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch('/api/v2/users/'.$user->id, [
                'email' => $user->email,
                'name'  => 'Renamed Self',
                'roles' => [superAdminRoleId()],
            ])->assertStatus(200);

        $fresh = $user->fresh();
        expect($fresh->name)->toBe('Renamed Self')
            ->and($fresh->hasRole('SuperAdmin'))->toBeFalse();
    });

    it('does not let a non-SuperAdmin with users.write change another user roles', function () {
        $attacker = User::factory()->create();
        $attacker->givePermissionTo('users.write');
        expect($attacker->hasRole('SuperAdmin'))->toBeFalse();

        $victim = nonAdminUser();

        $this->actingAs($attacker)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch('/api/v2/users/'.$victim->id, [
                'email' => $victim->email,
                'name'  => $victim->name,
                'roles' => [superAdminRoleId()],
            ]);

        expect($victim->fresh()->hasRole('SuperAdmin'))->toBeFalse();
    });

    it('does not let a non-SuperAdmin revoke an existing SuperAdmin', function () {
        $attacker = User::factory()->create();
        $attacker->givePermissionTo('users.write');

        $admin = User::whereEmail('admin@motor-cms.com')->first();
        expect($admin->hasRole('SuperAdmin'))->toBeTrue();

        // Attempt to overwrite the admin's SuperAdmin role with a lesser one.
        $this->actingAs($attacker)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch('/api/v2/users/'.$admin->id, [
                'email' => $admin->email,
                'name'  => $admin->name,
                'roles' => [Role::whereName('Editor')->first()->id],
            ]);

        expect($admin->fresh()->hasRole('SuperAdmin'))->toBeTrue();
    });

    it('still lets a SuperAdmin assign roles', function () {
        $victim = nonAdminUser();
        expect($victim->hasRole('Editor'))->toBeFalse();

        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->patch('/api/v2/users/'.$victim->id, [
                'email' => $victim->email,
                'name'  => $victim->name,
                'roles' => [Role::whereName('Editor')->first()->id],
            ])->assertStatus(200);

        expect($victim->fresh()->hasRole('Editor'))->toBeTrue();
    });
});
