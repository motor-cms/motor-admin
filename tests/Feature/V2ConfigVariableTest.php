<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\ConfigVariable;

pest()->group('V2ConfigVariable')->use(RefreshDatabase::class);

describe('V2 ConfigVariable API', function () {

    it('includes api_version v2 in response meta', function () {
        $response = $this->asAdmin()->getJson('/api/v2/config-variables');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('can get all config variables', function () {
        assertV2CrudIndex('/api/v2/config-variables', 1, ['id', 'name', 'package', 'group', 'value']);
    });

    it('can get a specific config variable', function () {
        assertV2CrudShow(
            '/api/v2/config-variables/'.ConfigVariable::whereName('name')->first()->id,
            ['id', 'name', 'package', 'group', 'value']
        );
    });

    it('can create a config variable', function () {
        assertV2CrudCreate('/api/v2/config-variables', [
            'name' => 'v2_test_var',
            'package' => 'v2-package',
            'group' => 'v2-group',
            'value' => 'v2-value',
            'is_invisible' => false,
        ], ConfigVariable::class);
    });

    it('validates required fields on create', function () {
        $countBefore = ConfigVariable::count();
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/config-variables', [])
            ->assertStatus(422);
        expect(ConfigVariable::count() - $countBefore)->toBe(0);
    });

    it('can update a config variable', function () {
        assertV2CrudUpdate(
            '/api/v2/config-variables/'.ConfigVariable::whereName('name')->first()->id,
            [
                'name' => 'v2_updated_var',
                'package' => 'test-package',
                'group' => 'test-group',
                'value' => 'test-value',
            ],
            'name',
            'v2_updated_var'
        );
    });

    it('can delete a config variable with 204 No Content', function () {
        assertV2CrudDelete(
            '/api/v2/config-variables/'.ConfigVariable::whereName('name')->first()->id,
            ConfigVariable::class
        );
    });

    it('denies access to basic users', function () {
        assertV2PermissionsDenied('/api/v2/config-variables', ConfigVariable::first()->id);
    });
});
