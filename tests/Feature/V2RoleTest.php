<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\Role;

pest()->group('V2Role')->use(RefreshDatabase::class);

describe('V2 Role API', function () {

    it('includes api_version v2 in response meta', function () {
        $response = $this->asAdmin()->getJson('/api/v2/roles');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('can get all roles', function () {
        // Note: permissions not eager-loaded in V2 RoleService
        assertV2CrudIndex('/api/v2/roles', 3, ['id', 'name', 'guard_name']);
    });

    it('can get a specific role', function () {
        // Note: permissions not eager-loaded in V2 RoleService (uses whenLoaded)
        assertV2CrudShow(
            '/api/v2/roles/'.Role::whereName('Editor')->first()->id,
            ['id', 'name', 'guard_name']
        );
    });

    it('can create a role', function () {
        assertV2CrudCreate('/api/v2/roles', [
            'name' => 'V2 Test Role',
            'guard_name' => 'web',
            'permissions' => [Permission::first()->id],
        ], Role::class);
    });

    it('validates required fields on create', function () {
        $countBefore = Role::count();
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/roles', [])
            ->assertStatus(422);
        expect(Role::count() - $countBefore)->toBe(0);
    });

    it("can't create a role with invalid permissions", function () {
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/roles', [
                'name' => 'Invalid Permissions Role',
                'guard_name' => 'web',
                'permissions' => [0],
            ])
            ->assertStatus(422);
    });

    it('can update a role', function () {
        assertV2CrudUpdate(
            '/api/v2/roles/'.Role::whereName('Editor')->first()->id,
            [
                'name' => 'V2 Updated Role',
                'guard_name' => 'web',
            ],
            'name',
            'V2 Updated Role'
        );
    });

    it('can delete a role with 204 No Content', function () {
        assertV2CrudDelete(
            '/api/v2/roles/'.Role::whereName('Editor')->first()->id,
            Role::class
        );
    });
});
