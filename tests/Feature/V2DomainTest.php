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

    it('exposes is_preview_domain on the v2 resource', function () {
        $client = Client::factory()->create(['slug' => 'preview-resource-client']);
        $domain = Domain::factory()->create(['client_id' => $client->id, 'is_preview_domain' => true]);

        $this->asAdmin()
            ->getJson('/api/v2/domains/'.$domain->id)
            ->assertStatus(200)
            ->assertJsonPath('data.is_preview_domain', true);
    });

    it('can create a domain with is_preview_domain set', function () {
        $client = Client::factory()->create(['slug' => 'preview-create-client']);

        $response = $this->asAdmin()
            ->postJson('/api/v2/domains', [
                'client_id' => $client->id,
                'is_active' => true,
                'is_preview_domain' => true,
                'name' => 'preview-create-domain',
                'protocol' => 'https',
                'host' => 'preview-create.localhost',
                'port' => 443,
                'path' => '/',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_preview_domain', true);
    });

    it('can update a domain to set is_preview_domain', function () {
        $client = Client::factory()->create(['slug' => 'preview-update-client']);
        $domain = Domain::factory()->create(['client_id' => $client->id, 'is_preview_domain' => false]);

        $response = $this->asAdmin()
            ->putJson('/api/v2/domains/'.$domain->id, [
                'client_id' => $domain->client_id,
                'is_active' => true,
                'is_preview_domain' => true,
                'name' => $domain->name,
                'protocol' => $domain->protocol,
                'host' => $domain->host,
                'port' => $domain->port,
                'path' => $domain->path,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_preview_domain', true);

        expect($domain->fresh()->is_preview_domain)->toBeTrue();
    });

    it('silently reassigns is_preview_domain when a sibling claims it via the API', function () {
        $client = Client::factory()->create(['slug' => 'preview-reassign-client']);
        $first  = Domain::factory()->create(['client_id' => $client->id, 'is_preview_domain' => true]);
        $second = Domain::factory()->create(['client_id' => $client->id, 'is_preview_domain' => false]);

        $this->asAdmin()
            ->putJson('/api/v2/domains/'.$second->id, [
                'client_id' => $second->client_id,
                'is_active' => true,
                'is_preview_domain' => true,
                'name' => $second->name,
                'protocol' => $second->protocol,
                'host' => $second->host,
                'port' => $second->port,
                'path' => $second->path,
            ])
            ->assertStatus(200);

        expect($first->fresh()->is_preview_domain)->toBeFalse()
            ->and($second->fresh()->is_preview_domain)->toBeTrue();
    });

    it('does not reassign across clients when setting is_preview_domain via the API', function () {
        $clientA = Client::factory()->create(['slug' => 'preview-iso-client-a']);
        $clientB = Client::factory()->create(['slug' => 'preview-iso-client-b']);
        $aDomain = Domain::factory()->create(['client_id' => $clientA->id, 'is_preview_domain' => true]);
        $bDomain = Domain::factory()->create(['client_id' => $clientB->id, 'is_preview_domain' => false]);

        $this->asAdmin()
            ->putJson('/api/v2/domains/'.$bDomain->id, [
                'client_id' => $bDomain->client_id,
                'is_active' => true,
                'is_preview_domain' => true,
                'name' => $bDomain->name,
                'protocol' => $bDomain->protocol,
                'host' => $bDomain->host,
                'port' => $bDomain->port,
                'path' => $bDomain->path,
            ])
            ->assertStatus(200);

        expect($aDomain->fresh()->is_preview_domain)->toBeTrue()
            ->and($bDomain->fresh()->is_preview_domain)->toBeTrue();
    });
});
