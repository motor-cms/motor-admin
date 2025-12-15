<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\PermissionGroup;

pest()->group('V2Permission')->use(RefreshDatabase::class);

describe('V2 Permission API', function () {

    it('includes api_version v2 in response meta', function () {
        $response = $this->asAdmin()->getJson('/api/v2/permissions');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('can get all permissions', function () {
        assertV2CrudIndex('/api/v2/permissions', 25, ['id', 'name', 'guard_name']);
    });

    it('can get all permissions of a group', function () {
        $response = $this->asAdmin()
            ->getJson('/api/v2/permissions-items/'.PermissionGroup::whereName('users')->first()->id);

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJsonCount(3, 'data');
    });

    it('can get a specific permission', function () {
        assertV2CrudShow(
            '/api/v2/permissions/'.Permission::whereName('users.read')->first()->id,
            ['id', 'name', 'guard_name']
        );
    });

    it('can create a permission', function () {
        assertV2CrudCreate('/api/v2/permissions', [
            'permission_group_id' => PermissionGroup::whereName('users')->first()->id,
            'name' => 'v2.test.permission',
            'guard_name' => 'web',
        ], Permission::class);
    });

    it('validates required fields on create', function () {
        $countBefore = Permission::count();
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/permissions', [])
            ->assertStatus(422);
        expect(Permission::count() - $countBefore)->toBe(0);
    });

    it("can't create a permission with invalid group", function () {
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/permissions', [
                'permission_group_id' => 0,
                'name' => 'invalid.group.permission',
                'guard_name' => 'web',
            ])
            ->assertStatus(422);
    });

    it('can update a permission', function () {
        assertV2CrudUpdate(
            '/api/v2/permissions/'.Permission::whereName('users.read')->first()->id,
            ['name' => 'users.v2.updated', 'guard_name' => 'web'],
            'name',
            'users.v2.updated'
        );
    });

    it('can delete a permission with 204 No Content', function () {
        assertV2CrudDelete(
            '/api/v2/permissions/'.Permission::whereName('users.read')->first()->id,
            Permission::class
        );
    });
});
