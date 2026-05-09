<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\DashboardAnnouncement;
use Motor\Admin\Models\User;
use Motor\Core\Scopes\ClientScope;

uses(RefreshDatabase::class);

beforeEach(fn () => app()->forgetInstance(ClientScope::RESOLVER_KEY));
afterEach(fn () => app()->forgetInstance(ClientScope::RESOLVER_KEY));

// Phase 9 follow-up: prove the V2 ScopeRequestsToClient middleware actually
// fires on motor-admin's V2 route group. DashboardAnnouncement is the only
// tenanted (BelongsToClient) resource on this package's V2 surface, so the
// list endpoint is the cleanest probe. Bare actingAs() leaves resolver
// binding entirely up to the middleware -- if the wiring is wrong, the
// foreign-tenant row leaks into the index response.
it('binds the tenant resolver on motor-admin V2 routes', function () {
    $clientA = Client::factory()->create();
    $clientB = Client::factory()->create();

    $user = User::factory()->create();
    $user->assignRole('Editor');
    $user->givePermissionTo(['dashboard-announcements.read']);
    $user->clients()->attach($clientA->id);

    $own = DashboardAnnouncement::withoutGlobalScopes()->create([
        'client_id' => $clientA->id,
        'title'     => 'mw-own',
        'type'      => 'info',
        'audience'  => 'client',
        'is_active' => true,
        'created_by' => $user->id,
    ]);

    $foreign = DashboardAnnouncement::withoutGlobalScopes()->create([
        'client_id' => $clientB->id,
        'title'     => 'mw-foreign',
        'type'      => 'info',
        'audience'  => 'client',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/v2/dashboard/announcements');

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->all();
    expect($titles)->toContain('mw-own')
        ->and($titles)->not->toContain('mw-foreign');
});
