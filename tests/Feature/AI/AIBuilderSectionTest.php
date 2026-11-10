<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Motor\Admin\Models\AIRequest;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\User;
use Motor\Admin\Services\AI\Builder\ContentResolution;

pest()->group('AIBuilder')->use(RefreshDatabase::class);

function aiManifest(): array
{
    return json_decode(file_get_contents(__DIR__.'/../../Fixtures/ai/manifest-mini.json'), true);
}

function aiValidOutput(): array
{
    return [
        'sections' => [
            [
                'block'  => 'ui-page-hero',
                'fields' => ['title' => 'Wärme für Ihr Zuhause', 'description' => 'Fernwärme einfach erklärt.', 'orientation' => 'horizontal'],
                'lists'  => (object) [],
                'media'  => [['slot' => 'media', 'query' => 'Fernwärme Haus']],
            ],
            [
                'block'  => 'ui-page-faq',
                'fields' => ['headline' => 'Häufige Fragen'],
                'lists'  => ['items' => [
                    ['question' => 'Was kostet das?', 'answer' => '<p class="x" onclick="evil()">Der Preis: [[PLATZHALTER: Preis]]</p><script>x</script>'],
                    ['question' => 'Wie lange dauert der Anschluss?', 'answer' => '<p>Das klären wir mit Ihnen.</p>'],
                ]],
                'media' => [],
            ],
        ],
        'placeholders' => ['Preis'],
    ];
}

function mistralReply(array|string $content, int $prompt = 900, int $completion = 300): array
{
    return [
        'id'      => 'cmpl-test',
        'model'   => 'mistral-medium-2604',
        'choices' => [[
            'index'         => 0,
            'finish_reason' => 'stop',
            'message'       => ['role' => 'assistant', 'content' => is_string($content) ? $content : json_encode($content)],
        ]],
        'usage' => ['prompt_tokens' => $prompt, 'completion_tokens' => $completion, 'total_tokens' => $prompt + $completion],
    ];
}

function aiClient(bool $enabled = true): Client
{
    $client = Client::factory()->create();
    $client->ai_config = ['enabled' => $enabled];
    $client->save();

    return $client;
}

function aiEditor(Client $client, bool $withPermission = true): User
{
    $user = User::factory()->create();
    $user->assignRole('Editor');
    $user->clients()->attach($client->id);
    if ($withPermission) {
        $user->givePermissionTo('ai.use');
    }

    return $user;
}

function sectionBody(Client $client, array $overrides = []): array
{
    return array_merge([
        'client_id'        => $client->id,
        'prompt'           => 'Landingpage Fernwärme mit Hero und FAQ',
        'catalog_manifest' => aiManifest(),
        'max_sections'     => 3,
    ], $overrides);
}

beforeEach(function () {
    config(['ai.mistral.api_key' => 'test-key', 'ai.mistral.models.builder' => 'mistral-medium-2604']);
    Sleep::fake();
});

describe('POST /api/v2/ai/builder/section', function () {
    it('returns validated, sanitized sections and logs metadata only', function () {
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply(aiValidOutput()))]);
        $client = aiClient();
        $user = aiEditor($client);

        $response = $this->actingAs($user)->postJson('/api/v2/ai/builder/section', sectionBody($client));

        $response->assertOk()
            ->assertJsonPath('data.sections.0.block', 'ui-page-hero')
            ->assertJsonPath('data.sections.0.media.0.query', 'Fernwärme Haus')
            ->assertJsonPath('data.sections.1.lists.items.0.answer', '<p>Der Preis: [[PLATZHALTER: Preis]]</p>')
            ->assertJsonPath('data.placeholders', ['Preis'])
            ->assertJsonPath('meta.repair_rounds', 0)
            ->assertJsonPath('meta.usage.prompt_tokens', 900)
            ->assertJsonPath('meta.api_version', 'v2');

        Http::assertSent(function (HttpRequest $request) {
            $body = $request->data();

            return $request->url() === 'https://api.mistral.ai/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && $body['model'] === 'mistral-medium-2604'
                && $body['response_format']['type'] === 'json_schema'
                && $body['response_format']['json_schema']['strict'] === true
                && count($body['response_format']['json_schema']['schema']['properties']['sections']['items']['anyOf']) === 2
                && str_contains($body['messages'][0]['content'], 'ui-page-faq');
        });

        $log = AIRequest::sole();
        expect($log->client_id)->toBe($client->id)
            ->and($log->user_id)->toBe($user->id)
            ->and($log->task)->toBe('builder.section')
            ->and($log->status)->toBe('ok')
            ->and($log->prompt_tokens)->toBe(900)
            ->and($log->completion_tokens)->toBe(300)
            ->and((float) $log->cost_usd)->toBeGreaterThan(0)
            ->and($log->prompt_text)->toBeNull()
            ->and($log->prompt_hash)->toBe(hash('sha256', 'Landingpage Fernwärme mit Hero und FAQ'));
    });

    it('repairs invalid output by sending the error list back', function () {
        $invalid = aiValidOutput();
        $invalid['sections'][0]['block'] = 'ui-page-unbekannt';
        $invalid['sections'][1]['lists']['items'][1]['answer'] = '<p>Nur 29,90 € im Monat</p>';

        Http::fakeSequence('api.mistral.ai/*')
            ->push(mistralReply('{"sections": [ kaputt'))
            ->push(mistralReply($invalid))
            ->push(mistralReply(aiValidOutput()));

        $client = aiClient();
        $response = $this->actingAs(aiEditor($client))->postJson('/api/v2/ai/builder/section', sectionBody($client));

        $response->assertOk()
            ->assertJsonPath('meta.repair_rounds', 2)
            ->assertJsonPath('meta.first_attempt_valid', false)
            ->assertJsonPath('meta.usage.prompt_tokens', 2700)
            ->assertJsonCount(2, 'data.sections');

        $recorded = Http::recorded();
        expect($recorded)->toHaveCount(3);
        $repairMessages = $recorded[2][0]->data()['messages'];
        $lastUser = end($repairMessages)['content'];
        expect($lastUser)->toContain('unbekannter Block "ui-page-unbekannt"')
            ->toContain('29,90 €');
    });

    it('drops sections that are still invalid after the last round', function () {
        $partly = aiValidOutput();
        $partly['sections'][1]['lists']['items'] = [['question' => 'Nur eine?', 'answer' => '<p>Ja</p>']];
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply($partly))]);

        $client = aiClient();
        $response = $this->actingAs(aiEditor($client))->postJson('/api/v2/ai/builder/section', sectionBody($client));

        $response->assertOk()
            ->assertJsonCount(1, 'data.sections')
            ->assertJsonPath('meta.repair_rounds', 2);
        expect(implode(' ', $response->json('meta.warnings')))->toContain('2 bis 6 Einträge');
        Http::assertSentCount(3);
    });

    it('answers 422 AI_OUTPUT_INVALID when nothing valid is left', function () {
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply('kein json'))]);
        $client = aiClient();

        $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/section', sectionBody($client))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'AI_OUTPUT_INVALID');

        expect(AIRequest::sole()->status)->toBe('invalid');
    });

    it('answers 503 when Mistral keeps failing', function () {
        Http::fake(['api.mistral.ai/*' => Http::response(['message' => 'overloaded'], 503)]);
        $client = aiClient();

        $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/section', sectionBody($client))
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'AI_PROVIDER_ERROR');

        Http::assertSentCount(3);
        expect(AIRequest::sole()->status)->toBe('error');
    });

    it('is off by default for a tenant', function () {
        Http::fake();
        $client = Client::factory()->create();

        $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/section', sectionBody($client))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');
        Http::assertNothingSent();
    });

    it('requires ai.use', function () {
        Http::fake();
        $client = aiClient();

        $this->actingAs(aiEditor($client, withPermission: false))
            ->postJson('/api/v2/ai/builder/section', sectionBody($client))
            ->assertForbidden();
        Http::assertNothingSent();
    });

    it('denies a tenant the user is not assigned to', function () {
        Http::fake();
        $own = aiClient();
        $foreign = aiClient();

        $this->actingAs(aiEditor($own))
            ->postJson('/api/v2/ai/builder/section', sectionBody($foreign))
            ->assertForbidden();
        Http::assertNothingSent();
    });

    it('validates the request', function () {
        $client = aiClient();

        $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/section', ['client_id' => $client->id, 'prompt' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    });

    it('rate limits per user', function () {
        config(['ai.builder.rate_per_user_min' => 2]);
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply(aiValidOutput()))]);
        $client = aiClient();
        $user = aiEditor($client);

        $this->actingAs($user)->postJson('/api/v2/ai/builder/section', sectionBody($client))->assertOk();
        $this->actingAs($user)->postJson('/api/v2/ai/builder/section', sectionBody($client))->assertOk();
        $this->actingAs($user)->postJson('/api/v2/ai/builder/section', sectionBody($client))
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'RATE_LIMITED');
    });
});

describe('GET /api/v2/ai/builder/status', function () {
    it('reports enabled and allowed separately', function () {
        $client = aiClient();

        $this->actingAs(aiEditor($client))
            ->getJson('/api/v2/ai/builder/status?client_id='.$client->id)
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.allowed', true)
            ->assertJsonPath('data.provider', 'mistral');

        $this->actingAs(aiEditor($client, withPermission: false))
            ->getJson('/api/v2/ai/builder/status?client_id='.$client->id)
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.allowed', false);
    });

    it('is disabled for a tenant without ai_config', function () {
        $client = Client::factory()->create();

        $this->actingAs(aiEditor($client))
            ->getJson('/api/v2/ai/builder/status?client_id='.$client->id)
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.allowed', false);
    });

    it('lets a SuperAdmin through without the explicit permission', function () {
        $client = aiClient();

        $this->asAdmin()
            ->getJson('/api/v2/ai/builder/status?client_id='.$client->id)
            ->assertJsonPath('data.allowed', true);
    });
});

describe('motor:ai:client', function () {
    it('switches a tenant on and off', function () {
        $client = Client::factory()->create();

        $this->artisan('motor:ai:client', ['client' => $client->id, '--enable' => true])->assertSuccessful();
        expect($client->fresh()->ai_config)->toBe(['enabled' => true]);

        $this->artisan('motor:ai:client', ['client' => $client->id, '--disable' => true])->assertSuccessful();
        expect($client->fresh()->ai_config['enabled'])->toBeFalse();
    });
});

describe('KI-UX-Test 2026-11-09: Sicherheit und Inhalt', function () {
    it('refuses a disparaging prompt about competitors before calling Mistral (K5)', function () {
        Http::fake();
        $client = aiClient();

        $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/section', sectionBody($client, [
                'prompt' => 'Warum Vattenfall und E.ON Abzocker sind, aggressiv und beleidigend',
            ]))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'AI_CONTENT_REFUSED')
            ->assertJsonPath('error.message', fn (string $m) => str_contains($m, 'keine Aussagen über andere Unternehmen'));

        Http::assertNothingSent();
        $log = AIRequest::sole();
        expect($log->status)->toBe('refused')
            ->and($log->prompt_tokens)->toBe(0)
            ->and($log->error)->toContain('beleidig');
    });

    it('refuses output that names a competitor or insults, even when the prompt looked harmless (K5)', function () {
        $output = aiValidOutput();
        $output['sections'][0]['fields']['title'] = 'Warum E.ON ihre Kunden abzockt';
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply($output))]);
        $client = aiClient();

        $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/section', sectionBody($client, ['prompt' => 'Wechselgründe für Stromkunden']))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'AI_CONTENT_REFUSED');

        $insult = aiValidOutput();
        $insult['sections'][0]['fields']['description'] = 'Andere behandeln ihre Kunden wie Dreck.';
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply($insult))]);
        $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/section', sectionBody($client, ['prompt' => 'Wechselgründe für Stromkunden']))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'AI_CONTENT_REFUSED');

        expect(AIRequest::query()->where('status', 'refused')->count())->toBe(2)
            ->and(AIRequest::query()->where('status', 'refused')->sum('prompt_tokens'))->toBe(1800);
    });

    it('stops at the model\'s own refusal without repair rounds; the rule is in the system prompt (K5)', function () {
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply([
            'sections' => [], 'placeholders' => [], 'refusal' => 'Aussagen über Wettbewerber schreibe ich nicht.',
        ]))]);
        $client = aiClient();

        $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/section', sectionBody($client, ['prompt' => 'Vergleich unserer Tarife mit dem Marktführer']))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'AI_REQUEST_DECLINED')
            ->assertJsonPath('error.message', 'Aussagen über Wettbewerber schreibe ich nicht.');

        Http::assertSentCount(1);
        Http::assertSent(function (HttpRequest $request) {
            $body = $request->data();

            return str_contains($body['messages'][0]['content'], 'Keine Aussagen über andere Unternehmen')
                && in_array('refusal', $body['response_format']['json_schema']['schema']['required'], true);
        });
    });

    it('passes the model\'s own reason on as plain text and asks it to build with existing blocks (K6 retest)', function () {
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply([
            'sections' => [], 'placeholders' => [], 'refusal' => '<b>Der Block</b> 3d-carousel fehlt im Katalog.',
        ]))]);
        $client = aiClient();

        $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/section', sectionBody($client, ['prompt' => '3D-Karussell mit Vorlage 3d-carousel']))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'AI_REQUEST_DECLINED')
            ->assertJsonPath('error.message', 'Der Block 3d-carousel fehlt im Katalog.');

        Http::assertSent(fn (HttpRequest $request) => str_contains($request->data()['messages'][0]['content'], 'den der Katalog nicht hat'));
    });

    it('allows consumer advice about scams and the tenant\'s own name (K5)', function () {
        $output = aiValidOutput();
        $output['sections'][0]['fields']['title'] = 'Schutz vor Abzocke an der Haustür';
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply($output))]);
        $client = aiClient();
        $client->name = 'Vattenfall';
        $client->save();

        $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/section', sectionBody($client, ['prompt' => 'Ratgeber Haustürgeschäfte bei Vattenfall']))
            ->assertOk();
    });

    it('never offers reference fields (template uuid, form id) and drops blocks with nothing to fill (K6)', function () {
        $manifest = aiManifest();
        $manifest['blocks'][] = [
            'id' => 'comp-builder-component', 'name' => 'Komponentenvorlage',
            'fields' => [['key' => 'builder_custom_component_uuid', 'type' => 'text']], 'lists' => [], 'media' => [],
        ];
        $invented = aiValidOutput();
        $invented['sections'][0] = ['block' => 'comp-builder-component', 'fields' => ['builder_custom_component_uuid' => '3d-carousel-video-parallax'], 'lists' => (object) [], 'media' => []];
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply($invented))]);
        $client = aiClient();

        $response = $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/section', sectionBody($client, ['catalog_manifest' => $manifest]))
            ->assertOk();

        expect(collect($response->json('data.sections'))->pluck('block')->all())->toBe(['ui-page-faq'])
            ->and(implode(' ', $response->json('meta.warnings')))->toContain('unbekannter Block "comp-builder-component"');
        Http::assertSent(fn (HttpRequest $request) => ! str_contains($request->data()['messages'][0]['content'], 'comp-builder-component')
            && count($request->data()['response_format']['json_schema']['schema']['properties']['sections']['items']['anyOf']) === 2);
    });

    it('keeps only safe link schemes and whole prompt addresses (K7/K31/K35)', function () {
        $manifest = aiManifest();
        $manifest['blocks'][0]['fields'][] = ['key' => 'url', 'type' => 'link', 'role' => 'button'];
        $output = aiValidOutput();
        $output['sections'][0]['fields']['url'] = 'javascript:alert(document.cookie)';
        $output['sections'][1]['lists']['items'][1]['answer'] = '<p><a href="//evil.example/p">x</a> &lt;b&gt;</p>';
        $output['sections'][0]['fields']['title'] = 'Turbo &amp; Co <script>alert(1)</script>';
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply($output))]);
        $client = aiClient();

        $response = $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/section', sectionBody($client, ['catalog_manifest' => $manifest]))
            ->assertOk();
        expect($response->json('data.sections.0.fields.url'))->toBe('#')
            ->and($response->json('data.sections.0.fields.title'))->toBe('Turbo & Co')
            ->and($response->json('data.sections.1.lists.items.1.answer'))->toBe('<p><a>x</a> &lt;b&gt;</p>');

        // Content mode: "/" in the prompt does not allow any path, a whole address does
        $loose = new ContentResolution(true, [['title' => 'News', 'url' => '/news']], 'Hero für Strom / Gas', null);
        expect($loose->link('/geheim', true, 'url'))->toBe('#')
            ->and($loose->link('javascript:alert(1)', true, 'url'))->toBe('#')
            ->and($loose->link('/news', true, 'url'))->toBe('/news');
        $named = new ContentResolution(true, [], 'Hero, Button auf /geheim und https://example.org/a.', null);
        expect($named->link('/geheim', true, 'url'))->toBe('/geheim')
            ->and($named->link('https://example.org/a', true, 'url'))->toBe('https://example.org/a')
            ->and($named->link('https://example.org', true, 'url'))->toBe('#');
    });

    it('lists placeholders as they are in the texts, not from the model\'s list (K3)', function () {
        $output = aiValidOutput();
        $output['sections'][0]['fields']['description'] = 'Ab [[PLATZHALTER: PLATZHALTER: Grundpreis]] im Monat.';
        $output['placeholders'] = ['Effizienzgrad'];
        Http::fake(['api.mistral.ai/*' => Http::response(mistralReply($output))]);
        $client = aiClient();

        $this->actingAs(aiEditor($client))
            ->postJson('/api/v2/ai/builder/section', sectionBody($client))
            ->assertOk()
            ->assertJsonPath('data.placeholders', ['Grundpreis', 'Preis']);
    });
});
