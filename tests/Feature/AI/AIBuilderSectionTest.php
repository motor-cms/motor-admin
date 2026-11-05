<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Motor\Admin\Models\AIRequest;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\User;

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
        $user->givePermissionTo('builder-pages.ai');
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
            ->assertJsonPath('data.sections.1.lists.items.0.answer', '<p>Der Preis: [[PLATZHALTER: Preis]]</p>x')
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

    it('requires builder-pages.ai', function () {
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
