<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\PermissionGroup;

pest()->group('V2PermissionGroup')->use(RefreshDatabase::class);

describe('V2 PermissionGroup API', function () {

    it('includes api_version v2 in response meta', function () {
        $response = $this->asAdmin()->getJson('/api/v2/permission-groups');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('can get all permission groups', function () {
        assertV2CrudIndex('/api/v2/permission-groups', 25, ['id', 'name', 'sort_position']);
    });

    it('can get a specific permission group', function () {
        assertV2CrudShow(
            '/api/v2/permission-groups/'.PermissionGroup::whereName('users')->first()->id,
            ['id', 'name', 'sort_position']
        );
    });

    it('can create a permission group', function () {
        assertV2CrudCreate('/api/v2/permission-groups', [
            'name' => 'v2-test-group',
            'sort_position' => 99,
        ], PermissionGroup::class);
    });

    it('validates required fields on create', function () {
        $countBefore = PermissionGroup::count();
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/permission-groups', [])
            ->assertStatus(422);
        expect(PermissionGroup::count() - $countBefore)->toBe(0);
    });

    it('can update a permission group', function () {
        assertV2CrudUpdate(
            '/api/v2/permission-groups/'.PermissionGroup::whereName('users')->first()->id,
            ['name' => 'v2-updated-group', 'sort_position' => 0],
            'name',
            'v2-updated-group'
        );
    });

    it('can delete a permission group with 204 No Content', function () {
        assertV2CrudDelete(
            '/api/v2/permission-groups/'.PermissionGroup::whereName('users')->first()->id,
            PermissionGroup::class
        );
    });

    it('includes permissions and permission_names in show response', function () {
        $group = PermissionGroup::whereName('users')->first();

        $response = $this->asAdmin()
            ->getJson('/api/v2/permission-groups/'.$group->id);

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJson(fn (\Illuminate\Testing\Fluent\AssertableJson $json) => $json->has(
                'data',
                fn (\Illuminate\Testing\Fluent\AssertableJson $data) => $data
                    ->has('permissions')
                    ->has('permission_names')
                    ->etc()
            )->etc());
    });

    it('denies access to basic users', function () {
        assertV2PermissionsDenied('/api/v2/permission-groups', PermissionGroup::first()->id);
    });
});
