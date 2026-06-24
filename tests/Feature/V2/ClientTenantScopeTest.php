<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\User;
use Motor\Core\Scopes\ClientScope;

uses(RefreshDatabase::class);

beforeEach(fn () => app()->forgetInstance(ClientScope::RESOLVER_KEY));
afterEach(fn () => app()->forgetInstance(ClientScope::RESOLVER_KEY));

// ZRMDEV-240 part 1: /api/v2/clients must only return the Mandanten the
// logged-in user is assigned to via the users_client pivot. A tenant must not
// learn who else uses the system. The Client model carries a global ClientScope
// keyed on its own primary key; the V2 ScopeRequestsToClient middleware binds
// the resolver on the real request.

function makeClientReader(): User
{
    $user = User::factory()->create();
    $user->assignRole('Editor');
    $user->givePermissionTo('clients.read');

    return $user;
}

describe('ZRMDEV-240 /api/v2/clients tenant scope', function () {
    it('returns only the clients the user is assigned to', function () {
        $own = Client::factory()->create(['name' => 'Own Client']);
        $foreign = Client::factory()->create(['name' => 'Foreign Client']);

        $user = makeClientReader();
        $user->clients()->attach($own->id);

        $response = $this->actingAs($user)->getJson('/api/v2/clients');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        expect($ids)->toContain($own->id)
            ->not->toContain($foreign->id);
    });

    it('hides the seeded default client when the user is not assigned to it', function () {
        $own = Client::factory()->create();

        $user = makeClientReader();
        $user->clients()->attach($own->id);

        $response = $this->actingAs($user)->getJson('/api/v2/clients');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        expect($ids)->toEqual([$own->id]);
    });

    it('returns nothing for a user with no client assignments', function () {
        Client::factory()->create();

        $user = makeClientReader();

        $response = $this->actingAs($user)->getJson('/api/v2/clients');

        $response->assertOk();
        expect($response->json('data'))->toBeEmpty();
    });

    it('lets SuperAdmin see every client', function () {
        $a = Client::factory()->create();
        $b = Client::factory()->create();

        $response = $this->asAdmin()->getJson('/api/v2/clients');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        expect($ids)->toContain($a->id)->toContain($b->id);
    });

    it('404s when fetching a foreign client by id', function () {
        $own = Client::factory()->create();
        $foreign = Client::factory()->create();

        $user = makeClientReader();
        $user->clients()->attach($own->id);

        $this->actingAs($user)
            ->getJson('/api/v2/clients/'.$foreign->id)
            ->assertNotFound();
    });
});
