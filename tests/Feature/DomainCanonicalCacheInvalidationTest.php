<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\Domain;
use Motor\Admin\Models\Language;
use Motor\Builder\Jobs\RebuildPageCacheJob;
use Motor\Builder\Models\BuilderPage;

pest()
    ->group('DomainCanonicalCacheInvalidation')
    ->use(RefreshDatabase::class);

function makePublishedPagesForClient(int $clientId, int $count): array
{
    $pages = [];
    for ($i = 0; $i < $count; $i++) {
        $pages[] = BuilderPage::factory()->create([
            'client_id' => $clientId,
            'language_id' => Language::first()->id,
            'is_published' => true,
            'global_css' => '',
        ]);
    }

    return $pages;
}

describe('Domain canonical changes invalidate page cache', function () {

    it('dispatches a rebuild job per published page when is_canonical is flipped on', function () {
        Queue::fake();

        $client = Client::factory()->create();
        $pages = makePublishedPagesForClient($client->id, 3);

        $domain = Domain::factory()->create([
            'client_id' => $client->id,
            'is_canonical' => false,
        ]);

        Queue::assertNotPushed(RebuildPageCacheJob::class);

        $domain->update(['is_canonical' => true]);

        Queue::assertPushed(RebuildPageCacheJob::class, 3);
        foreach ($pages as $page) {
            Queue::assertPushed(RebuildPageCacheJob::class, fn ($job) => $job->recordId === $page->id);
        }
    });

    it('does NOT dispatch extra rebuild jobs from the auto-flipped sibling Domain', function () {
        Queue::fake();

        $client = Client::factory()->create();
        $pages = makePublishedPagesForClient($client->id, 2);

        // First Domain becomes canonical — fires 2 rebuild jobs.
        $first = Domain::factory()->create([
            'client_id' => $client->id,
            'is_canonical' => true,
        ]);

        Queue::assertPushed(RebuildPageCacheJob::class, 2);

        // Second Domain is created with is_canonical=true. The `saving` hook
        // auto-flips `first` to false via a bulk Query Builder update (which
        // bypasses model events) — only `$second`'s saved event fires the
        // rebuild dispatch, so we expect exactly +2 jobs (total: 4).
        Domain::factory()->create([
            'client_id' => $client->id,
            'is_canonical' => true,
        ]);

        Queue::assertPushed(RebuildPageCacheJob::class, 4);
    });

    it('does not dispatch for unrelated field changes on a non-canonical domain', function () {
        Queue::fake();

        $client = Client::factory()->create();
        makePublishedPagesForClient($client->id, 2);

        $domain = Domain::factory()->create([
            'client_id' => $client->id,
            'is_canonical' => false,
        ]);

        $domain->update(['name' => 'just a rename', 'host' => 'newhost.test']);

        Queue::assertNotPushed(RebuildPageCacheJob::class);
    });

    it('dispatches when canonical domain changes host/port/protocol', function () {
        Queue::fake();

        $client = Client::factory()->create();
        makePublishedPagesForClient($client->id, 2);

        $domain = Domain::factory()->create([
            'client_id' => $client->id,
            'is_canonical' => true,
            'host' => 'old.test',
        ]);
        // Creation fires once for the initial flag set.
        Queue::assertPushed(RebuildPageCacheJob::class, 2);

        $domain->update(['host' => 'new.test']);

        // +2 jobs because host changed on a canonical domain.
        Queue::assertPushed(RebuildPageCacheJob::class, 4);
    });

    it('does not dispatch for inactive domain flag flips with no other clients impact', function () {
        Queue::fake();

        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();
        makePublishedPagesForClient($clientA->id, 2);
        makePublishedPagesForClient($clientB->id, 3);

        $domainA = Domain::factory()->create([
            'client_id' => $clientA->id,
            'is_canonical' => false,
        ]);

        $domainA->update(['is_canonical' => true]);

        // Only client A's pages get rebuilds; client B's pages do not.
        Queue::assertPushed(RebuildPageCacheJob::class, 2);
    });
});
