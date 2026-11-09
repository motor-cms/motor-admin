<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Motor\Admin\Services\AI\Builder\MediaSearch;
use Motor\Admin\Services\AI\Builder\ScoutMediaSearch;
use Motor\Admin\Services\AI\Builder\SectionSchema;

pest()->group('AIBuilder')->use(RefreshDatabase::class);

function contentManifest(): array
{
    return json_decode(file_get_contents(__DIR__.'/../../Fixtures/ai/manifest-content.json'), true);
}

function slide(string $headline, string $url, string $query): array
{
    return [
        'slot:content.0.text' => 'Tech News',
        'slot:content.1.text' => $headline,
        'slot:content.2.text' => 'Was sich diese Woche in der IT getan hat, kurz für Sie zusammengefasst.',
        'slot:links.0.label'  => 'Weiterlesen',
        'slot:links.0.url'    => $url,
        'slot:image.0'        => ['query' => $query, 'alt' => 'Person arbeitet am Laptop mit Code auf dem Bildschirm'],
    ];
}

function contentOutput(): array
{
    return [
        'sections' => [
            [
                'block'  => 'std-carousel',
                'fields' => (object) [],
                'lists'  => ['components:slide' => [
                    slide('Neue Chips für KI im Büro', '/news', 'Laptop Code'),
                    slide('Glasfaser im Saarland', 'https://erfunden.example/glasfaser', 'Laptop Code'),
                    slide('Sicher im Homeoffice', '', 'Bergsee Sonnenuntergang'),
                ]],
                'media' => [],
            ],
            [
                'block'  => 'std-page-hero',
                'fields' => ['title' => 'Technik, die Ihnen Arbeit abnimmt'],
                'lists'  => (object) [],
                'media'  => [['slot' => 'media', 'query' => 'Team Besprechung', 'alt' => 'Team bespricht ein Projekt am Tisch']],
            ],
        ],
        'placeholders' => [],
    ];
}

/** Mediathek mit zwei Treffern für „Laptop Code“ und einem für „Team Besprechung“ */
function fakeMediaLibrary(): object
{
    $search = new class implements MediaSearch
    {
        public array $calls = [];

        public function images(int $clientId, string $query, int $limit = 5): array
        {
            $this->calls[] = [$clientId, $query];

            return match ($query) {
                'Laptop Code'      => [['id' => 11, 'alt_text' => 'Laptop'], ['id' => 12, 'alt_text' => 'Code']],
                'Team Besprechung' => [['id' => 21, 'alt_text' => 'Team']],
                default            => [],
            };
        }
    };
    app()->instance(MediaSearch::class, $search);

    return $search;
}

function contentBody($client, array $overrides = []): array
{
    return array_merge([
        'client_id'        => $client->id,
        'prompt'           => 'erstelle slider mit bildern und button mit tech news',
        'catalog_manifest' => contentManifest(),
        'max_sections'     => 2,
        'content'          => true,
        'context'          => ['link_targets' => [['title' => 'News', 'url' => '/news'], ['title' => 'Kontakt', 'url' => '/kontakt']]],
    ], $overrides);
}

beforeEach(function () {
    config(['ai.mistral.api_key' => 'test-key', 'ai.mistral.models.builder' => 'mistral-medium-2604']);
    Sleep::fake();
});

describe('Inhalte vorschlagen', function () {
    it('fills texts, resolves images from the media library and keeps only known links', function () {
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply(contentOutput()))]);
        $search = fakeMediaLibrary();
        $client = aiClient();

        $response = $this->actingAs(aiEditor($client))->postJson('/api/v2/ai/builder/section', contentBody($client));

        $response->assertOk()
            ->assertJsonPath('meta.content', true)
            ->assertJsonPath('meta.media_found', 3)
            ->assertJsonPath('meta.media_missing', 1);

        $data = $response->json('data');
        $items = $data['sections'][0]['lists']['components:slide'];
        // Bester unbenutzter Treffer je Bild, kein Bild doppelt
        expect($items[0]['slot:content.1.text'])->toBe('Neue Chips für KI im Büro')
            ->and($items[0]['slot:image.0']['media']['id'])->toBe(11)
            ->and($items[1]['slot:image.0']['media']['id'])->toBe(12)
            ->and($items[0]['slot:image.0']['alt'])->toBe('Person arbeitet am Laptop mit Code auf dem Bildschirm')
            // nichts gefunden: Platzhalter bleibt, Suchbegriff geht mit
            ->and($items[2]['slot:image.0'])->toBe(['query' => 'Bergsee Sonnenuntergang', 'alt' => 'Person arbeitet am Laptop mit Code auf dem Bildschirm', 'media' => null])
            // Links: bekannte Seite bleibt, erfundene Adresse und leerer Button werden „#“
            ->and($items[0]['slot:links.0.url'])->toBe('/news')
            ->and($items[1]['slot:links.0.url'])->toBe('#')
            ->and($items[2]['slot:links.0.url'])->toBe('#')
            ->and($data['sections'][1]['media'][0])->toMatchArray(['slot' => 'media', 'query' => 'Team Besprechung', 'alt' => 'Team bespricht ein Projekt am Tisch'])
            ->and($data['sections'][1]['media'][0]['media']['id'])->toBe(21)
            ->and($data['placeholders'])->toContain('Linkziel prüfen')
            ->and($response->json('meta.warnings'))->toContain('Link "https://erfunden.example/glasfaser" (slot:links.0.url) ist keine vorhandene Seite und wurde durch "#" ersetzt.')
            ->and($search->calls[0][0])->toBe($client->id);

        Http::assertSent(function (HttpRequest $request) {
            $body = json_decode(json_encode($request->data()), true);
            $schema = $body['response_format']['json_schema']['schema']['properties']['sections']['items']['anyOf'];
            $slide = $schema[0]['properties']['lists']['properties']['components:slide']['items']['properties'];
            $heroMedia = $schema[1]['properties']['media']['items'];

            return str_contains($body['messages'][0]['content'], 'Inhalte vorschlagen')
                && str_contains($body['messages'][0]['content'], 'Rolle overline')
                && str_contains($body['messages'][1]['content'], 'News → /news')
                && $slide['slot:image.0']['required'] === ['query', 'alt']
                && $heroMedia['required'] === ['slot', 'query', 'alt'];
        });
    });

    it('stays structure-only with placeholders when content is off', function () {
        $output = contentOutput();
        unset($output['sections'][1]['media'][0]['alt']);
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply($output))]);
        $search = fakeMediaLibrary();
        $client = aiClient();

        $response = $this->actingAs(aiEditor($client))->postJson('/api/v2/ai/builder/section', contentBody($client, ['content' => false]));

        $response->assertOk()
            ->assertJsonPath('meta.content', false)
            ->assertJsonPath('data.sections.1.media.0', ['slot' => 'media', 'query' => 'Team Besprechung']);
        expect($response->json('data.sections.0.lists')['components:slide'][1]['slot:links.0.url'])->toBe('https://erfunden.example/glasfaser');
        expect($search->calls)->toBe([]);

        Http::assertSent(fn (HttpRequest $request) => ! str_contains($request->data()['messages'][0]['content'], 'Inhalte vorschlagen')
            && ! str_contains($request->data()['messages'][1]['content'], 'Vorhandene Seiten'));
    });

    it('sends an image without query/alt object back for repair', function () {
        $bad = contentOutput();
        $bad['sections'][0]['lists']['components:slide'][0]['slot:image.0'] = 'Laptop';
        Http::fakeSequence('api.mistral.ai/*')
            ->push(mistralReply($bad))
            ->push(mistralReply(contentOutput()));
        fakeMediaLibrary();
        $client = aiClient();

        $this->actingAs(aiEditor($client))->postJson('/api/v2/ai/builder/section', contentBody($client))
            ->assertOk()
            ->assertJsonPath('meta.repair_rounds', 1);

        Http::assertSentCount(2);
        Http::assertSent(fn (HttpRequest $request) => count($request->data()['messages']) === 4
            && str_contains($request->data()['messages'][3]['content'], 'Bild braucht ein Objekt'));
    });

    it('accepts image fields and roles in the manifest, rejects unknown types', function () {
        $client = aiClient();
        $manifest = contentManifest();
        $manifest['blocks'][1]['fields'][0]['type'] = 'video';

        $this->actingAs(aiEditor($client))->postJson('/api/v2/ai/builder/section', contentBody($client, ['catalog_manifest' => $manifest]))
            ->assertStatus(422);
    });

    it('builds the image schema as a strict object', function () {
        expect(SectionSchema::fieldSchema(['key' => 'slot:image.0', 'type' => 'image']))->toMatchArray([
            'type'     => 'object',
            'required' => ['query', 'alt'],
        ]);
    });
});

it('takes only images whose texts carry the search words', function () {
    $words = ScoutMediaSearch::words('KI Roboter Hand');
    expect($words)->toBe(['roboter', 'hand'])
        ->and(ScoutMediaSearch::required($words))->toBe(1)
        // „anhand“ ist kein Wortanfang: das Stromkosten-Foto aus der Eval fällt raus
        ->and(ScoutMediaSearch::relevance($words, 'Eine Frau berechnet Stromkosten anhand des Arbeitspreises'))->toBe(0)
        ->and(ScoutMediaSearch::relevance(ScoutMediaSearch::words('Solarpanel Windrad'), 'Solarpanels auf dem Dach'))->toBe(1);

    $webdesign = ScoutMediaSearch::words('Webdesign Arbeitsplatz Laptop');
    expect(ScoutMediaSearch::required($webdesign))->toBe(2)
        ->and(ScoutMediaSearch::relevance($webdesign, 'Kabel und Glasfasern auf einem Laptop'))->toBe(1)
        ->and(ScoutMediaSearch::relevance(ScoutMediaSearch::words('Glasfaser'), 'glasfaser-kabel.jpg'))->toBe(1)
        // Laptop allein reicht nicht, das Hauptmotiv (erstes Wort) schon
        ->and(ScoutMediaSearch::relevant($webdesign, 1, false))->toBeFalse()
        ->and(ScoutMediaSearch::relevant(ScoutMediaSearch::words('Wärmepumpe Außenbereich Haus'), 1, true))->toBeTrue();
});
