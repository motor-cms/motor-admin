<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Domain;

pest()
    ->group('DomainCanonicalFlag')
    ->use(RefreshDatabase::class);

describe('Domain is_canonical silent reassignment', function () {

    it('unflags the previous canonical domain when a sibling is set as canonical', function () {
        $client = Client::factory()->create(['slug' => 'canonical-flag-client-a']);

        $first  = Domain::factory()->create(['client_id' => $client->id, 'is_canonical' => true]);
        $second = Domain::factory()->create(['client_id' => $client->id, 'is_canonical' => false]);

        $second->update(['is_canonical' => true]);

        expect($first->fresh()->is_canonical)->toBeFalse()
            ->and($second->fresh()->is_canonical)->toBeTrue();
    });

    it('does not touch domains belonging to a different client', function () {
        $clientA = Client::factory()->create(['slug' => 'canonical-flag-iso-a']);
        $clientB = Client::factory()->create(['slug' => 'canonical-flag-iso-b']);

        $aDomain = Domain::factory()->create(['client_id' => $clientA->id, 'is_canonical' => true]);
        $bDomain = Domain::factory()->create(['client_id' => $clientB->id, 'is_canonical' => false]);

        $bDomain->update(['is_canonical' => true]);

        expect($aDomain->fresh()->is_canonical)->toBeTrue()
            ->and($bDomain->fresh()->is_canonical)->toBeTrue();
    });

    it('is a no-op when is_canonical is set to false', function () {
        $client = Client::factory()->create(['slug' => 'canonical-flag-noop-false']);

        $first  = Domain::factory()->create(['client_id' => $client->id, 'is_canonical' => true]);
        $second = Domain::factory()->create(['client_id' => $client->id, 'is_canonical' => false]);

        $second->update(['is_canonical' => false]);

        expect($first->fresh()->is_canonical)->toBeTrue()
            ->and($second->fresh()->is_canonical)->toBeFalse();
    });

    it('is a no-op when is_canonical is not in the dirty attributes', function () {
        $client = Client::factory()->create(['slug' => 'canonical-flag-noop-dirty']);

        $first  = Domain::factory()->create(['client_id' => $client->id, 'is_canonical' => true]);
        $second = Domain::factory()->create(['client_id' => $client->id, 'is_canonical' => false]);

        $second->update(['name' => 'just renamed, flag untouched']);

        expect($first->fresh()->is_canonical)->toBeTrue()
            ->and($second->fresh()->is_canonical)->toBeFalse();
    });

    it('allows setting the flag on the very first domain of a client (no siblings to unflag)', function () {
        $client = Client::factory()->create(['slug' => 'canonical-flag-first']);

        $only = Domain::factory()->create(['client_id' => $client->id, 'is_canonical' => true]);

        expect($only->fresh()->is_canonical)->toBeTrue();
    });

    it('does not interfere with the is_preview_domain auto-flip', function () {
        $client = Client::factory()->create(['slug' => 'canonical-flag-preview-coexist']);

        $first = Domain::factory()->create([
            'client_id'         => $client->id,
            'is_preview_domain' => true,
            'is_canonical'      => false,
        ]);
        $second = Domain::factory()->create([
            'client_id'         => $client->id,
            'is_preview_domain' => false,
            'is_canonical'      => true,
        ]);

        // Flipping the canonical flag on $first should not touch $second's preview flag.
        $first->update(['is_canonical' => true]);

        expect($first->fresh()->is_canonical)->toBeTrue()
            ->and($first->fresh()->is_preview_domain)->toBeTrue()
            ->and($second->fresh()->is_canonical)->toBeFalse()
            ->and($second->fresh()->is_preview_domain)->toBeFalse();
    });
});

describe('Domain::canonicalFor', function () {

    it('returns the flagged canonical domain for a client', function () {
        $client = Client::factory()->create(['slug' => 'canonical-for-flagged']);

        Domain::factory()->create(['client_id' => $client->id, 'is_canonical' => false]);
        $canonical = Domain::factory()->create(['client_id' => $client->id, 'is_canonical' => true]);
        Domain::factory()->create(['client_id' => $client->id, 'is_canonical' => false]);

        expect(Domain::canonicalFor($client->id)->id)->toBe($canonical->id);
    });

    it('falls back to the first active domain by id when no flag is set', function () {
        $client = Client::factory()->create(['slug' => 'canonical-for-fallback']);

        $first  = Domain::factory()->create(['client_id' => $client->id, 'is_canonical' => false]);
        Domain::factory()->create(['client_id' => $client->id, 'is_canonical' => false]);
        Domain::factory()->create(['client_id' => $client->id, 'is_canonical' => false]);

        expect(Domain::canonicalFor($client->id)->id)->toBe($first->id);
    });

    it('skips inactive domains even when they carry the canonical flag', function () {
        $client = Client::factory()->create(['slug' => 'canonical-for-inactive']);

        Domain::factory()->create([
            'client_id'    => $client->id,
            'is_active'    => false,
            'is_canonical' => true,
        ]);
        $active = Domain::factory()->create([
            'client_id'    => $client->id,
            'is_active'    => true,
            'is_canonical' => false,
        ]);

        expect(Domain::canonicalFor($client->id)->id)->toBe($active->id);
    });

    it('returns null when the client has no active domains', function () {
        $client = Client::factory()->create(['slug' => 'canonical-for-empty']);

        Domain::factory()->create(['client_id' => $client->id, 'is_active' => false]);

        expect(Domain::canonicalFor($client->id))->toBeNull();
    });
});
