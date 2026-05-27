<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\DashboardAnnouncement;
use Motor\Admin\Models\User;
use Motor\Core\Scopes\ClientScope;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->forgetInstance(ClientScope::RESOLVER_KEY);
});

afterEach(function () {
    app()->forgetInstance(ClientScope::RESOLVER_KEY);
});

function makeAnnouncement(int $clientId, string $title = 'msg'): DashboardAnnouncement
{
    return DashboardAnnouncement::create([
        'title' => $title,
        'body' => 'b',
        'type' => 'info',
        'audience' => 'self',
        'is_active' => true,
        'client_id' => $clientId,
    ]);
}

describe('DashboardAnnouncement tenant scope', function () {

    it('hides foreign-client announcements from a single-client user', function () {
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('Editor');
        $user->clients()->attach($clientA->id);

        makeAnnouncement($clientA->id, 'mine');
        makeAnnouncement($clientB->id, 'foreign');

        actingAsClientScopedUser($user);

        expect(DashboardAnnouncement::pluck('title')->all())->toBe(['mine']);
        expect(DashboardAnnouncement::find($clientB->id))->toBeNull();
    });

    it('lets SuperAdmin see all announcements regardless of client', function () {
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();
        makeAnnouncement($clientA->id);
        makeAnnouncement($clientB->id);

        $admin = User::factory()->create();
        $admin->assignRole('SuperAdmin');
        actingAsClientScopedUser($admin);

        expect(DashboardAnnouncement::count())->toBe(2);
    });

    it('shows nothing to a user with no clients', function () {
        $clientA = Client::factory()->create();
        makeAnnouncement($clientA->id);

        $user = User::factory()->create();
        $user->assignRole('Editor');
        actingAsClientScopedUser($user);

        expect(DashboardAnnouncement::count())->toBe(0);
    });

    it('auto-fills client_id when a single-client user creates without it', function () {
        $client = Client::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('Editor');
        $user->clients()->attach($client->id);

        actingAsClientScopedUser($user);

        $announcement = DashboardAnnouncement::create([
            'title' => 'auto',
            'body' => 'b',
            'type' => 'info',
            'audience' => 'self',
            'is_active' => true,
        ]);

        expect($announcement->client_id)->toBe($client->id);
    });
});
