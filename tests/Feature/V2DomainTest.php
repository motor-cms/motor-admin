<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Domain;

pest()->group('V2Domain')->use(RefreshDatabase::class);

describe('V2 Domain API', function () {

    it('includes api_version v2 in response meta', function () {
        $response = $this->asAdmin()->getJson('/api/v2/domains');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('can get all domains', function () {
        assertV2CrudIndex('/api/v2/domains', 1, ['id', 'name', 'client']);
    });

    it('can get a specific domain', function () {
        assertV2CrudShow(
            '/api/v2/domains/'.Domain::whereName('localhost')->first()->id,
            ['id', 'name', 'host', 'protocol', 'port', 'path']
        );
    });

    it('can create a domain', function () {
        assertV2CrudCreate('/api/v2/domains', [
            'client_id' => Client::first()->id,
            'is_active' => true,
            'name' => 'v2-test-domain',
            'protocol' => 'https',
            'host' => 'v2.localhost',
            'port' => 443,
            'path' => '/',
        ], Domain::class);
    });

    it('validates required fields on create', function () {
        $countBefore = Domain::count();
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/domains', [])
            ->assertStatus(422);
        expect(Domain::count() - $countBefore)->toBe(0);
    });

    it("can't create a domain with invalid client", function () {
        $this->asAdmin()
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v2/domains', [
                'client_id' => 0,
                'is_active' => true,
                'name' => 'invalid-client',
                'protocol' => 'https',
                'host' => 'invalid.localhost',
                'port' => 443,
                'path' => '/',
            ])
            ->assertStatus(422);
    });

    it('can update a domain', function () {
        assertV2CrudUpdate(
            '/api/v2/domains/'.Domain::whereName('localhost')->first()->id,
            [
                'client_id' => Client::first()->id,
                'is_active' => true,
                'name' => 'v2-updated-domain',
                'protocol' => 'https',
                'host' => 'localhost',
                'port' => 443,
                'path' => '/',
            ],
            'name',
            'v2-updated-domain'
        );
    });

    it('can delete a domain with 204 No Content', function () {
        assertV2CrudDelete(
            '/api/v2/domains/'.Domain::whereName('localhost')->first()->id,
            Domain::class
        );
    });

    it('denies access to basic users', function () {
        assertV2PermissionsDenied('/api/v2/domains', Domain::first()->id);
    });

    it('can filter domains by client_id', function () {
        $client = Client::first();
        $otherClient = Client::factory()->create(['slug' => 'other-client']);

        $matchingDomain = Domain::factory()->create(['client_id' => $client->id, 'is_active' => true]);
        $otherDomain = Domain::factory()->create(['client_id' => $otherClient->id, 'is_active' => true]);

        $response = $this->asAdmin()
            ->getJson('/api/v2/domains?client_id='.$client->id);

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');

        $returnedIds = collect($response->json('data'))->pluck('id')->all();
        expect($returnedIds)->toContain($matchingDomain->id);
        expect($returnedIds)->not->toContain($otherDomain->id);
    });

    it('can filter domains by is_active', function () {
        $client = Client::first();

        $activeDomain = Domain::factory()->create(['client_id' => $client->id, 'is_active' => true]);
        $inactiveDomain = Domain::factory()->create(['client_id' => $client->id, 'is_active' => false]);

        $response = $this->asAdmin()
            ->getJson('/api/v2/domains?is_active=1');

        $response->assertStatus(200)
            ->assertJsonPath('meta.api_version', 'v2');

        $returnedIds = collect($response->json('data'))->pluck('id')->all();
        expect($returnedIds)->toContain($activeDomain->id);
        expect($returnedIds)->not->toContain($inactiveDomain->id);
    });
});
