<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Domain;

pest()
    ->group('DomainPreviewFlag')
    ->use(RefreshDatabase::class);

describe('Domain is_preview_domain silent reassignment', function () {

    it('unflags the previous preview domain when a sibling is set as preview', function () {
        $client = Client::factory()->create(['slug' => 'preview-flag-client-a']);

        $first  = Domain::factory()->create(['client_id' => $client->id, 'is_preview_domain' => true]);
        $second = Domain::factory()->create(['client_id' => $client->id, 'is_preview_domain' => false]);

        $second->update(['is_preview_domain' => true]);

        expect($first->fresh()->is_preview_domain)->toBeFalse()
            ->and($second->fresh()->is_preview_domain)->toBeTrue();
    });

    it('does not touch domains belonging to a different client', function () {
        $clientA = Client::factory()->create(['slug' => 'preview-flag-client-iso-a']);
        $clientB = Client::factory()->create(['slug' => 'preview-flag-client-iso-b']);

        $aDomain = Domain::factory()->create(['client_id' => $clientA->id, 'is_preview_domain' => true]);
        $bDomain = Domain::factory()->create(['client_id' => $clientB->id, 'is_preview_domain' => false]);

        $bDomain->update(['is_preview_domain' => true]);

        expect($aDomain->fresh()->is_preview_domain)->toBeTrue()
            ->and($bDomain->fresh()->is_preview_domain)->toBeTrue();
    });

    it('is a no-op when is_preview_domain is set to false', function () {
        $client = Client::factory()->create(['slug' => 'preview-flag-client-noop-false']);

        $first  = Domain::factory()->create(['client_id' => $client->id, 'is_preview_domain' => true]);
        $second = Domain::factory()->create(['client_id' => $client->id, 'is_preview_domain' => false]);

        $second->update(['is_preview_domain' => false]);

        expect($first->fresh()->is_preview_domain)->toBeTrue()
            ->and($second->fresh()->is_preview_domain)->toBeFalse();
    });

    it('is a no-op when is_preview_domain is not in the dirty attributes', function () {
        $client = Client::factory()->create(['slug' => 'preview-flag-client-noop-dirty']);

        $first  = Domain::factory()->create(['client_id' => $client->id, 'is_preview_domain' => true]);
        $second = Domain::factory()->create(['client_id' => $client->id, 'is_preview_domain' => false]);

        $second->update(['name' => 'just renamed, flag untouched']);

        expect($first->fresh()->is_preview_domain)->toBeTrue()
            ->and($second->fresh()->is_preview_domain)->toBeFalse();
    });

    it('allows setting the flag on the very first domain of a client (no siblings to unflag)', function () {
        $client = Client::factory()->create(['slug' => 'preview-flag-client-first']);

        $only = Domain::factory()->create(['client_id' => $client->id, 'is_preview_domain' => true]);

        expect($only->fresh()->is_preview_domain)->toBeTrue();
    });
});
