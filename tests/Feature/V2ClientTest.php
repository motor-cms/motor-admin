<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Client;

pest()->group('V2Client')->use(RefreshDatabase::class);

describe('V2 Client API', function () {

    it('includes api_version v2 in response meta', function () {
        $response = $this->asAdmin()->getJson('/api/v2/clients');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('can get all clients', function () {
        assertV2CrudIndex('/api/v2/clients', 1, ['id', 'name', 'slug']);
    });

    it('can get a specific client', function () {
        assertV2CrudShow(
            '/api/v2/clients/'.Client::whereName('Default')->first()->id,
            ['id', 'name', 'slug']
        );
    });

    it('can create a client', function () {
        assertV2CrudCreate('/api/v2/clients', [
            'name' => 'V2 Test Client',
            'slug' => 'v2-test-client',
        ], Client::class);
    });

    it('validates required fields on create', function () {
        $countBefore = Client::count();
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/clients', [])
            ->assertStatus(422);
        expect(Client::count() - $countBefore)->toBe(0);
    });

    it('can update a client', function () {
        assertV2CrudUpdate(
            '/api/v2/clients/'.Client::whereName('Default')->first()->id,
            ['name' => 'V2 Updated Client', 'slug' => 'v2-updated'],
            'name',
            'V2 Updated Client'
        );
    });

    it('can delete a client with 204 No Content', function () {
        assertV2CrudDelete(
            '/api/v2/clients/'.Client::whereName('Default')->first()->id,
            Client::class
        );
    });

    it('denies access to basic users', function () {
        assertV2PermissionsDenied('/api/v2/clients', Client::first()->id);
    });
});
