<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\Role;

pest()->group('Role')->use(RefreshDatabase::class);

describe('Role', function () {
    it('can create a Role', function () {
        $rolecount = Role::count();
        $this->asAdmin()
            ->post('/api/roles', [
                'name'        => 'test',
                'guard_name'  => 'web',
                'permissions' => [
                    Permission::first()->id,
                ],
            ])->assertStatus(201);
        expect(Role::count() - $rolecount)->toBe(1);
    });
    it("can't create a Role with invalid permissions", function () {
        $rolecount = Role::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/roles', [
                'name'        => 'test',
                'guard_name'  => 'web',
                'permissions' => [0],
            ])->assertStatus(422);
        expect(Role::count() - $rolecount)->toBe(0);
    });
    it("can't create an empty Role", function () {
        $rolecount = Role::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/roles', [])->assertStatus(422);
        expect(Role::count() - $rolecount)->toBe(0);
    });
    it('can get all Roles')
        ->asAdmin()
        ->get('/api/roles')
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->has(
            'data',
            3,
            fn (AssertableJson $data) => $data
                ->has('id')
                ->has('name')
                ->has('guard_name')
                ->has('permissions')
                ->etc()
        )->etc());
    it(
        'can get a specific Role',
        fn () => $this->asAdmin()->get('/api/roles/'.Role::whereName('Editor')->first()->id)
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has(
                'data',
                fn (AssertableJson $data) => $data
                    ->has('id')
                    ->has('name')
                    ->has('guard_name')
                    ->has('permissions')
            )->etc())
    );
    it('can update roles', fn () => $this->asAdmin()
        ->put('/api/roles/'.Role::whereName('Editor')->first()->id, [
            'client_id'  => Client::first()->id,
            'name'       => 'changed',
            'guard_name' => 'web',
        ])->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->has('data', fn (AssertableJson $data) => $data->where('name', 'changed')->etc())->etc()));
    it('can delete roles', function () {
        $rolecount = Role::count();
        $this->asAdmin()->delete('/api/roles/'.Role::whereName('Editor')->first()->id)
            ->assertStatus(200);
        expect($rolecount - Role::count())->toBe(1);
    });
});
