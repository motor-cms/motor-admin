<?php

use Illuminate\Testing\Fluent\AssertableJson;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\PermissionGroup;

describe('Permission', function () {
    // Groups
    it('can create a PermissionGroup', function () {
        $permission_group_count = PermissionGroup::count();
        $this->asAdmin()
            ->post('/api/permission_groups', [
                'name'          => 'test',
                'sort_position' => '0',
            ])->assertStatus(201);
        expect(PermissionGroup::count() - $permission_group_count)->toBe(1);
    });
    it("can't create an empty PermissionGroup", function () {
        $permission_group_count = PermissionGroup::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/permission_groups', [])->assertStatus(422);
        expect(PermissionGroup::count() - $permission_group_count)->toBe(0);
    });
    it('can get all PermissionGroups')
        ->asAdmin()
        ->get('/api/permission_groups')
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->has(
            'data',
            25,
            fn (AssertableJson $data) => $data
                ->has('id')
                ->has('name')
                ->has('sort_position')
                ->etc()
        )->etc());
    it(
        'can get a specific PermissionGroup',
        fn () => $this->asAdmin()->get('/api/permission_groups/'.PermissionGroup::whereName('users')->first()->id)
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has(
                'data',
                fn (AssertableJson $data) => $data
                    ->has('id')
                    ->has('name')
                    ->has('sort_position')
                    ->has('permissions', 3, fn (AssertableJson $permissions) => $permissions
                        ->has('id')
                        ->has('name')
                        ->has('guard_name')
                        ->etc())
            )->etc())
    );
    it('can update permission_groups', fn () => $this->asAdmin()
        ->put('/api/permission_groups/'.PermissionGroup::whereName('users')->first()->id, [
            'name'          => 'changed',
            'sort_position' => '0',
        ])->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->has('data', fn (AssertableJson $data) => $data->where('name', 'changed')->etc())->etc()));
    it('can delete permission_groups', function () {
        $permission_group_count = PermissionGroup::count();
        $this->asAdmin()->delete('/api/permission_groups/'.PermissionGroup::whereName('users')->first()->id)
            ->assertStatus(200);
        expect($permission_group_count - PermissionGroup::count())->toBe(1);
    });

    // Permissions
    it('can create a Permission', function () {
        $permissioncount = Permission::count();
        $this->asAdmin()
            ->post('/api/permissions', [
                'permission_group_id' => PermissionGroup::whereName('users')->first()->id,
                'name'                => 'test',
                'guard_name'          => 'web',
            ])->assertStatus(201);
        expect(Permission::count() - $permissioncount)->toBe(1);
    });
    it("can't create a Permission with invalid group", function () {
        $permissioncount = Permission::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/permissions', [
                'permission_group_id' => 0,
                'name'                => 'test',
                'guard_name'          => 'web',
            ])->assertStatus(422);
        expect(Permission::count() - $permissioncount)->toBe(0);
    });
    it("can't create an empty Permission", function () {
        $permissioncount = Permission::count();
        $this->asAdmin()->withJsonHeaders()
            ->post('/api/permissions', [])->assertStatus(422);
        expect(Permission::count() - $permissioncount)->toBe(0);
    });
    it('can get all Permissions')
        ->asAdmin()
        ->get('/api/permissions')
        ->assertStatus(200)
        ->assertJson(
            fn (AssertableJson $json) => $json->has(
                'data',
                25,
                fn (AssertableJson $data) => $data
                    ->has('id')
                    ->has('name')
                    ->has('guard_name')
                    ->etc()
            )->etc()
        );
    it(
        'can get all Permissions of a group',
        fn () => $this->asAdmin()
            ->get('/api/permissions_items/'.PermissionGroup::whereName('users')->first()->id)
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has(
                'data',
                3,
                fn (AssertableJson $data) => $data
                    ->has('id')
                    ->has('name')
                    ->has('guard_name')
                    ->etc()
            )->etc())
    );
    it(
        'can get a specific Permission',
        fn () => $this->asAdmin()->get('/api/permissions/'.Permission::whereName('users.read')->first()->id)
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has(
                'data',
                fn (AssertableJson $data) => $data
                    ->has('id')
                    ->has('name')
                    ->has('guard_name')
            )->etc())
    );
    it('can update permissions', fn () => $this->asAdmin()
        ->put('/api/permissions/'.Permission::whereName('users.read')->first()->id, [
            'guard_name' => 'web',
            'name'       => 'users.changed',
        ])->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json->has('data', fn (AssertableJson $data) => $data->where('name', 'users.changed')->etc())->etc()));
    it('can delete permissions', function () {
        $permissioncount = Permission::count();
        $this->asAdmin()->delete('/api/permissions/'.Permission::whereName('users.read')->first()->id)
            ->assertStatus(200);
        expect($permissioncount - Permission::count())->toBe(1);
    });
});
