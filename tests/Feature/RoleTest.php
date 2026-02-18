<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\Role;

pest()->group('Role')->use(RefreshDatabase::class);

describe('Role', function () {
    it('can create a Role', fn () => assertCrudCreate(
        '/api/roles',
        [
            'name' => 'test',
            'guard_name' => 'web',
            'permissions' => [Permission::first()->id],
        ],
        Role::class
    ));

    it("can't create a Role with invalid permissions", fn () => assertCrudValidation(
        '/api/roles',
        [
            'name' => 'test',
            'guard_name' => 'web',
            'permissions' => [0],
        ],
        Role::class
    ));

    it("can't create an empty Role", fn () => assertCrudValidation(
        '/api/roles',
        [],
        Role::class
    ));

    it('can get all Roles', fn () => assertCrudIndex(
        '/api/roles',
        3,
        ['id', 'name', 'guard_name', 'permissions']
    ));

    it('can get a specific Role', fn () => assertCrudShow(
        '/api/roles/'.Role::whereName('Editor')->first()->id,
        ['id', 'name', 'guard_name', 'permissions']
    ));

    it('can update roles', fn () => assertCrudUpdate(
        '/api/roles/'.Role::whereName('Editor')->first()->id,
        [
            'client_id' => Client::first()->id,
            'name' => 'changed',
            'guard_name' => 'web',
        ],
        'name',
        'changed'
    ));

    it('can delete roles', fn () => assertCrudDelete(
        '/api/roles/'.Role::whereName('Editor')->first()->id,
        Role::class
    ));

    it('can filter roles by guard_name', function () {
        // Create a role with a different guard_name
        Role::factory()->create([
            'name' => 'ApiRole',
            'guard_name' => 'api',
        ]);

        // All roles (3 seeded + 1 api) = 4
        $this->asAdmin()
            ->get('/api/roles')
            ->assertStatus(200)
            ->assertJsonCount(4, 'data');

        // Filter by web guard — should only get the 3 seeded roles
        $response = $this->asAdmin()
            ->get('/api/roles?guard_name=web')
            ->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(3);
        foreach ($data as $role) {
            expect($role['guard_name'])->toBe('web');
        }

        // Filter by api guard — should only get the 1 api role
        $response = $this->asAdmin()
            ->get('/api/roles?guard_name=api')
            ->assertStatus(200);

        $data = $response->json('data');
        expect(count($data))->toBe(1);
        expect($data[0]['guard_name'])->toBe('api');
        expect($data[0]['name'])->toBe('ApiRole');
    });
});
