<?php

use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\Role;
use Motor\Admin\Models\User;

describe('Role', function () {
    it('can get all Roles')
        ->asAdmin()
        ->get('/api/roles')
        ->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) => $json->has(
            'data',
            3,
            fn(AssertableJson $data) =>
            $data
                ->has('id')
                ->has('name')
                ->has('guard_name')
                ->has('permissions')
                ->etc()
        )->etc());
    it('can create a Role', function () {
        $rolecount = Role::count();
        $this->asAdmin()
            ->post('/api/roles', [
                'name' => 'test',
                'guard_name' => 'web',
                'permissions' => [
                    Permission::first()->id
                ]
            ])->assertStatus(201);
        expect(Role::count() - $rolecount)->toBe(1);
    });
    it("can't create a Role with invalid permissions", function () {
        $rolecount = Role::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/roles', [
                'name' => 'test2',
                'guard_name' => 'web',
                'permissions' => [0]
            ])->assertStatus(422);
        expect(Role::count() - $rolecount)->toBe(0);
    });
    /*it("can't create a Role with permissions belonging to the wrong guard", function () {
        $rolecount = Role::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/roles', [
                'name' => 'test4',
                'guard_name' => 'api',
                'permissions' => [Permission::whereGuardName('web')->first()->id]
            ])->assertStatus(422);
        expect(Role::count() - $rolecount)->toBe(0);
    });*/
    it("can't create an empty Role", function () {
        $rolecount = Role::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/roles', [])->assertStatus(422);
        expect(Role::count() - $rolecount)->toBe(0);
    });
    it(
        'can get a specific Role',
        fn() =>
        $this->asAdmin()->get('/api/roles/' . Role::whereName('Editor')->first()->id)
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json->has(
                'data',
                fn(AssertableJson $data) =>
                $data
                    ->has('id')
                    ->has('name')
                    ->has('guard_name')
                    ->has('permissions')
            )->etc())
    );
    it('can update roles', fn() => $this->asAdmin()
        ->put('/api/roles/' . Role::whereName('Editor')->first()->id, [
            'client_id' => Client::first()->id,
            'name' => 'changed',
            'guard_name' => 'web',
        ])->assertStatus(200)
        ->assertJson(fn(AssertableJson $json) => $json->has('data', fn(AssertableJson $data) =>
        $data->where('name', 'changed')->etc())->etc()));
    it('can delete roles', function () {
        $rolecount = Role::count();
        $this->asAdmin()->delete('/api/roles/' . Role::whereName('changed')->first()->id)
            ->assertStatus(200);
        expect($rolecount - Role::count())->toBe(1);
    });
    it(
        "can't do anything without permissions",
        function () {
            $this->asBasic()->getJson('/api/roles')->assertStatus(403);
            $this->asBasic()->getJson('/api/roles/' . Role::first()->id)->assertStatus(403);
            $this->asBasic()->post('/api/roles', [])->assertStatus(403);
            $this->asBasic()->put('/api/roles/' . Role::first()->id, [])->assertStatus(403);
            $this->asBasic()->delete('/api/roles/' . Role::first()->id)->assertStatus(403);
        }
    );
});
