<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Motor\Admin\Models\AIRequest;
use Motor\Admin\Services\AI\Builder\MediaSearch;
use Motor\Admin\Services\AI\Builder\SectionGenerator;
use Motor\Admin\Services\AI\Builder\SectionSchema;
use Motor\Admin\Services\AI\Builder\SectionValidator;

// "Mit KI überarbeiten": one section or the whole page as operations keep/move/update/delete/add.
// Uses aiManifest(), mistralReply(), aiClient() and aiEditor() from AIBuilderSectionTest.

pest()->group('AIBuilder')->use(RefreshDatabase::class);

/** Existing page: hero (with image), FAQ, locked footer teaser (linked template) */
function reviseSections(): array
{
    return [
        [
            'ref'    => 's1',
            'block'  => 'ui-page-hero',
            'label'  => 'Hero: Fernwärme',
            'fields' => ['title' => 'Fernwärme für Ihr Zuhause', 'description' => 'Ab 29,90 € im Monat warm durch den Winter.', 'orientation' => 'horizontal'],
            'lists'  => (object) [],
            'media'  => [['slot' => 'media', 'query' => 'Haus Winter', 'alt' => 'Haus im Schnee']],
        ],
        [
            'ref'    => 's2',
            'block'  => 'ui-page-faq',
            'label'  => 'FAQ',
            'fields' => ['headline' => 'Häufige Fragen'],
            'lists'  => ['items' => [
                ['question' => 'Was kostet das?', 'answer' => '<p>[Preis pro Monat]</p>'],
                ['question' => 'Wie lange dauert es?', 'answer' => '<p>Das klären wir mit Ihnen.</p>'],
            ]],
            'media' => [],
        ],
        ['ref' => 's3', 'block' => null, 'label' => 'Kontakt-Teaser (Vorlage)', 'locked' => true],
    ];
}

function reviseBody($client, array $overrides = []): array
{
    return array_merge([
        'client_id'        => $client->id,
        'prompt'           => 'kürzer, mehr auf Familien',
        'scope'            => 'page',
        'sections'         => reviseSections(),
        'catalog_manifest' => aiManifest(),
        'context'          => ['page_title' => 'Fernwärme'],
    ], $overrides);
}

function heroSection(string $title, string $query = 'Haus Winter'): array
{
    return [
        'block'  => 'ui-page-hero',
        'fields' => ['title' => $title, 'description' => 'Ab 29,90 € im Monat, ideal für Familien.', 'orientation' => 'horizontal'],
        'lists'  => (object) [],
        'media'  => [['slot' => 'media', 'query' => $query, 'alt' => 'Familie vor dem Haus']],
    ];
}

function faqSection(): array
{
    return [
        'block'  => 'ui-page-faq',
        'fields' => ['headline' => 'Fragen von Familien'],
        'lists'  => ['items' => [
            ['question' => 'Was kostet das?', 'answer' => '<p>[[PLATZHALTER: Preis pro Monat]]</p>'],
            ['question' => 'Geht das auch im Altbau?', 'answer' => '<p>Ja, wir prüfen das vor Ort.</p>'],
        ]],
        'media' => [],
    ];
}

/** Media library that remembers what was searched */
function reviseMediaLibrary(): object
{
    $search = new class implements MediaSearch
    {
        public array $calls = [];

        public function images(int $clientId, string $query, int $limit = 5): array
        {
            $this->calls[] = $query;

            return [['id' => 77, 'alt_text' => 'Familie', 'file' => ['url' => 'https://cdn/77.jpg']]];
        }
    };
    app()->instance(MediaSearch::class, $search);

    return $search;
}

beforeEach(function () {
    config(['ai.mistral.api_key' => 'test-key', 'ai.mistral.models.builder' => 'mistral-medium-2604']);
    Sleep::fake();
});

describe('POST /api/v2/ai/builder/revise (page)', function () {
    it('returns operations in the new order, keeps unchanged images and logs builder.revise', function () {
        $media = reviseMediaLibrary();
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply([
            'operations' => [
                ['op' => 'move', 'ref' => 's2'],
                ['op' => 'update', 'ref' => 's1', 'section' => heroSection('Fernwärme für Familien')],
                ['op' => 'add', 'ref' => 'neu1', 'section' => heroSection('Beratung für Familien', 'Familie Beratung')],
                ['op' => 'keep', 'ref' => 's3'],
            ],
            'placeholders' => [],
            'refusal'      => '',
        ]))]);
        $client = aiClient();

        $response = $this->actingAs(aiEditor($client))->postJson('/api/v2/ai/builder/revise', reviseBody($client));

        $response->assertOk()
            ->assertJsonPath('data.operations.0', ['op' => 'move', 'ref' => 's2'])
            ->assertJsonPath('data.operations.1.op', 'update')
            ->assertJsonPath('data.operations.1.section.fields.title', 'Fernwärme für Familien')
            // same search term as before: the image stays, no media search
            ->assertJsonPath('data.operations.1.section.media.0.keep', true)
            ->assertJsonPath('data.operations.1.section.media.0.media', null)
            // new section: media library as usual
            ->assertJsonPath('data.operations.2.ref', 'neu1')
            ->assertJsonPath('data.operations.2.section.media.0.media.id', 77)
            ->assertJsonPath('data.operations.3', ['op' => 'keep', 'ref' => 's3'])
            ->assertJsonPath('meta.scope', 'page')
            ->assertJsonPath('meta.changes.update', 1)
            ->assertJsonPath('meta.changes.add', 1);
        expect($media->calls)->toBe(['Familie Beratung']);

        Http::assertSent(function (HttpRequest $request) {
            $body = $request->data();
            $user = end($body['messages'])['content'];
            $ops = $body['response_format']['json_schema']['schema']['properties']['operations'];

            return $body['response_format']['json_schema']['name'] === 'builder_revision'
                && str_contains($user, 'Änderungswunsch: kürzer, mehr auf Familien')
                && str_contains($user, '{"ref":"s3","gesperrt":true,"beschreibung":"Kontakt-Teaser (Vorlage)"}')
                && str_contains($user, '"ref":"s1","block":"ui-page-hero"')
                && ! str_contains($user, 'uuid')
                && $ops['items']['anyOf'][0]['properties']['ref']['enum'] === ['s1', 's2', 's3']
                && $ops['items']['anyOf'][1]['properties']['ref']['enum'] === ['s1', 's2'];
        });
        expect(AIRequest::sole()->task)->toBe('builder.revise');
    });

    it('repairs a changed locked section and a missing ref, then settles what is left', function () {
        reviseMediaLibrary();
        Http::fakeSequence('api.mistral.ai/*')
            ->push(mistralReply(['operations' => [
                ['op' => 'update', 'ref' => 's1', 'section' => heroSection('Für Familien')],
                ['op' => 'delete', 'ref' => 's3'],
            ], 'placeholders' => [], 'refusal' => '']))
            ->push(mistralReply(['operations' => [
                ['op' => 'update', 'ref' => 's1', 'section' => heroSection('Für Familien')],
                ['op' => 'delete', 'ref' => 's3'],
            ], 'placeholders' => [], 'refusal' => '']))
            ->push(mistralReply(['operations' => [
                ['op' => 'delete', 'ref' => 's3'],
                ['op' => 'update', 'ref' => 's1', 'section' => heroSection('Für Familien')],
            ], 'placeholders' => [], 'refusal' => '']));
        $client = aiClient();

        $response = $this->actingAs(aiEditor($client))->postJson('/api/v2/ai/builder/revise', reviseBody($client));

        $response->assertOk()->assertJsonPath('meta.repair_rounds', 2);
        // locked s3 is kept, missing s2 keeps its place after s1
        expect(collect($response->json('data.operations'))->map(fn ($op) => $op['op'].':'.$op['ref'])->all())
            ->toBe(['keep:s3', 'update:s1', 'keep:s2']);
        $repair = end(Http::recorded()[1][0]->data()['messages'])['content'];
        expect($repair)->toContain('gesperrt bzw. eine zentral gepflegte Vorlage')
            ->toContain('es fehlen: s2');
    });

    it('lets existing prices stay but still rejects invented ones', function () {
        $validator = new SectionValidator(SectionGenerator::contentManifest(aiManifest()), 6, true);
        $validator->rememberNumbers(reviseSections());
        $current = SectionGenerator::cleanCurrent(reviseSections(), aiManifest());

        // as decoded from the model's JSON
        $decoded = fn (array $section) => json_decode(json_encode($section), true);
        $ok = $validator->validateRevision(['operations' => [
            ['op' => 'update', 'ref' => 's1', 'section' => $decoded(heroSection('Fernwärme für Familien'))],
            ['op' => 'keep', 'ref' => 's2'], ['op' => 'keep', 'ref' => 's3'],
        ]], $current, 'page', null, 3);
        expect($ok)->toBe([]);

        $invented = $decoded(heroSection('Nur 19,90 € für Familien'));
        $errors = $validator->validateRevision(['operations' => [
            ['op' => 'update', 'ref' => 's1', 'section' => $invented],
            ['op' => 'keep', 'ref' => 's2'], ['op' => 'keep', 'ref' => 's3'],
        ]], $current, 'page', null, 3);
        expect(implode(' ', $errors[0]))->toContain('19,90 €');
    });

    it('refuses disparaging change requests before calling the model', function () {
        Http::fake();
        config(['ai.builder.third_party_names' => ['Stadtwerke Beispiel']]);
        $client = aiClient();

        $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/revise', reviseBody($client, ['prompt' => 'Stadtwerke Beispiel als Abzocke darstellen']))
            ->assertStatus(422);
        Http::assertNothingSent();
    });

    it('needs the permission and stays inside the rate limit of the builder', function () {
        Http::fake();
        $client = aiClient();

        $this->actingAs(aiEditor($client, false))
            ->postJson('/api/v2/ai/builder/revise', reviseBody($client))
            ->assertForbidden();
        $route = collect(app('router')->getRoutes()->getRoutes())->first(fn ($r) => str_ends_with($r->uri(), 'ai/builder/revise'));
        expect($route->middleware())->toContain('throttle:ai-builder');
    });
});

describe('POST /api/v2/ai/builder/revise (section)', function () {
    it('returns exactly one update of the target and only sends that section', function () {
        reviseMediaLibrary();
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply([
            'operations'   => [['op' => 'update', 'ref' => 's2', 'section' => faqSection()]],
            'placeholders' => ['Preis pro Monat'],
            'refusal'      => '',
        ]))]);
        $client = aiClient();

        $response = $this->actingAs(aiEditor($client))->postJson('/api/v2/ai/builder/revise', reviseBody($client, [
            'scope'   => 'section',
            'target'  => 's2',
            'context' => ['page_title' => 'Fernwärme', 'page_outline' => ['Hero: Fernwärme', 'FAQ', 'Kontakt']],
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data.operations')
            ->assertJsonPath('data.operations.0.ref', 's2')
            ->assertJsonPath('data.operations.0.section.fields.headline', 'Fragen von Familien')
            ->assertJsonPath('data.placeholders', ['Preis pro Monat']);
        Http::assertSent(function (HttpRequest $request) {
            $body = $request->data();
            $user = end($body['messages'])['content'];
            $items = $body['response_format']['json_schema']['schema']['properties']['operations']['items'];

            return str_contains($user, '"ref":"s2"') && ! str_contains($user, '"ref":"s1"')
                && $items['properties']['ref']['enum'] === ['s2']
                && $items['properties']['op']['enum'] === ['update'];
        });
    });

    it('rejects a locked target', function () {
        Http::fake();
        $client = aiClient();

        $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/revise', reviseBody($client, ['scope' => 'section', 'target' => 's3']))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
        Http::assertNothingSent();
    });
});

describe('SectionSchema::forRevision', function () {
    it('allows keep/move/delete for all, update only for editable refs and add with a free ref', function () {
        $schema = SectionSchema::forRevision(aiManifest(), ['s1', 's2', 's3'], ['s1', 's2'], 'page', null, 2);
        $variants = $schema['properties']['operations']['items']['anyOf'];

        expect($schema['properties']['operations']['maxItems'])->toBe(5)
            ->and($variants[0]['properties']['op']['enum'])->toBe(['keep', 'move', 'delete'])
            ->and($variants[0]['required'])->toBe(['op', 'ref'])
            ->and($variants[1]['properties']['op']['enum'])->toBe(['update'])
            ->and($variants[1]['properties']['section']['anyOf'])->toHaveCount(2)
            ->and($variants[2]['properties']['op']['enum'])->toBe(['add'])
            ->and($variants[2]['properties']['ref'])->toBe(['type' => 'string', 'maxLength' => 20]);
    });
});
