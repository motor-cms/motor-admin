<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Motor\Admin\Models\AIRequest;
use Motor\Admin\Models\AISystemPrompt;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\User;
use Motor\Admin\Services\AI\AIProviderException;
use Motor\Admin\Services\AI\Builder\SectionSchema;
use Motor\Admin\Services\AI\Builder\SectionValidator;
use Motor\Admin\Services\AI\MistralClient;

pest()->group('AIBuilder')->use(RefreshDatabase::class);

function miniManifest(): array
{
    return json_decode(file_get_contents(__DIR__.'/../../Fixtures/ai/manifest-mini.json'), true);
}

beforeEach(function () {
    config(['ai.mistral.api_key' => 'test-key']);
    Sleep::fake();
});

describe('SectionSchema', function () {
    it('builds one strict variant per block', function () {
        $schema = SectionSchema::forManifest(miniManifest(), 4);
        $variants = $schema['properties']['sections']['items']['anyOf'];

        expect($schema['properties']['sections']['maxItems'])->toBe(4)
            ->and($variants)->toHaveCount(2)
            ->and($variants[0]['properties']['block']['enum'])->toBe(['ui-page-hero'])
            ->and($variants[0]['properties']['fields']['required'])->toBe(['title', 'description', 'orientation'])
            ->and((array) $variants[0]['properties']['fields']['properties'])->toMatchArray([
                'title'       => ['type' => 'string', 'maxLength' => 60],
                'orientation' => ['type' => 'string', 'enum' => ['horizontal', 'vertical']],
            ])
            ->and($variants[0]['properties']['media']['items']['properties']['slot']['enum'])->toBe(['media'])
            ->and(((array) $variants[1]['properties']['lists']['properties'])['items'])->toMatchArray(['minItems' => 2, 'maxItems' => 6]);
    });

    it('encodes empty property maps as JSON objects', function () {
        $json = json_encode(SectionSchema::forManifest(miniManifest(), 1));

        expect($json)->toContain('"lists":{"type":"object","additionalProperties":false,"required":[],"properties":{}}');
    });
});

describe('SectionValidator', function () {
    it('accepts a valid section', function () {
        $validator = new SectionValidator(miniManifest());

        expect($validator->validate(['sections' => [[
            'block'  => 'ui-page-hero',
            'fields' => ['title' => 'Hallo', 'description' => '', 'orientation' => 'vertical'],
            'lists'  => [],
            'media'  => [['slot' => 'media', 'query' => 'Team']],
        ]], 'placeholders' => []]))->toBe([]);
    });

    it('reports catalog, length, list, enum and invented-number errors', function () {
        $validator = new SectionValidator(miniManifest(), 2);
        $errors = $validator->validate(['sections' => [
            ['block' => 'nope', 'fields' => [], 'lists' => [], 'media' => []],
            ['block' => 'ui-page-hero', 'fields' => ['title' => str_repeat('x', 61), 'orientation' => 'diagonal', 'extra' => 'y'], 'lists' => [], 'media' => [['slot' => 'bg', 'query' => 'x']]],
            ['block' => 'ui-page-faq', 'fields' => ['headline' => 'Spare 20 %'], 'lists' => ['items' => [['question' => 'Q', 'answer' => '<p>ab 12,5 ct/kWh</p>']]], 'media' => []],
        ]]);
        $all = implode("\n", array_merge(...array_values($errors)));

        expect($errors)->toHaveKeys([-1, 0, 1, 2])
            ->and($all)->toContain('Höchstens 2 Abschnitte')
            ->toContain('unbekannter Block "nope"')
            ->toContain('höchstens 60 Zeichen')
            ->toContain('erlaubt sind nur horizontal, vertical')
            ->toContain('unbekanntes Feld "extra"')
            ->toContain('unbekannter Medien-Slot "bg"')
            ->toContain('braucht 2 bis 6 Einträge, hat 1')
            ->toContain('"20 %"')
            ->toContain('"12,5 ct"');
    });

    it('allows numbers inside placeholders and warns about Du-Form', function () {
        $validator = new SectionValidator(miniManifest());
        $errors = $validator->validate(['sections' => [[
            'block' => 'ui-page-hero', 'fields' => ['title' => 'Nur [[PLATZHALTER: Preis in €]] für dich'], 'lists' => [], 'media' => [],
        ]]]);

        expect($errors)->toBe([])
            ->and($validator->warnings)->toHaveCount(1);
    });
});

describe('MistralClient', function () {
    it('passes tools through and returns tool calls', function () {
        Http::fake(['api.mistral.ai/*' => Http::response([
            'model'   => 'mistral-small-2603',
            'choices' => [['finish_reason' => 'tool_calls', 'message' => ['content' => '', 'tool_calls' => [
                ['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'list_blocks', 'arguments' => '{}']],
            ]]]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
        ])]);

        $result = app(MistralClient::class)->chat([
            'model'       => 'mistral-small-2603',
            'messages'    => [['role' => 'user', 'content' => 'Welche Blöcke?']],
            'tools'       => [['type' => 'function', 'function' => ['name' => 'list_blocks', 'parameters' => ['type' => 'object', 'properties' => (object) []]]]],
            'tool_choice' => 'any',
        ]);

        expect($result['tool_calls'][0]['function']['name'])->toBe('list_blocks')
            ->and($result['finish_reason'])->toBe('tool_calls');
        Http::assertSent(fn (HttpRequest $r) => $r->data()['tool_choice'] === 'any' && ! isset($r->data()['reasoning_effort']));
    });

    it('drops thinking chunks of reasoning models and sets their effort', function () {
        Http::fake(['api.mistral.ai/*' => Http::response([
            'model'   => 'mistral-large-4',
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => [
                ['type' => 'thinking', 'thinking' => [['type' => 'text', 'text' => 'hmm']]],
                ['type' => 'text', 'text' => '{"ok":true}'],
            ]]]],
            'usage' => [],
        ])]);

        $result = app(MistralClient::class)->chat(['model' => 'mistral-large-4', 'messages' => []]);

        expect($result['text'])->toBe('{"ok":true}');
        Http::assertSent(fn (HttpRequest $r) => $r->data()['reasoning_effort'] === 'low');
    });

    it('retries 429 and then gives up without leaking the key', function () {
        Http::fake(['api.mistral.ai/*' => Http::response(['message' => 'Requests rate limit exceeded'], 429)]);

        try {
            app(MistralClient::class)->chat(['model' => 'mistral-small-2603', 'messages' => []]);
            $this->fail('expected exception');
        } catch (AIProviderException $e) {
            expect($e->providerStatus)->toBe(429)
                ->and($e->getMessage())->toContain('429')
                ->not->toContain('test-key');
        }
        Http::assertSentCount(3);
    });

    it('refuses to call without a key', function () {
        config(['ai.mistral.api_key' => null]);
        Http::fake();

        expect(fn () => app(MistralClient::class)->chat(['model' => 'x', 'messages' => []]))
            ->toThrow(AIProviderException::class);
        Http::assertNothingSent();
    });
});

describe('ai_help with Mistral', function () {
    function helpSetup(bool $enabled): array
    {
        $client = Client::factory()->create();
        $client->ai_config = ['enabled' => $enabled];
        $client->save();
        $prompt = AISystemPrompt::create(['client_id' => $client->id, 'name' => 'Ton', 'prompt' => 'Sie-Form, sachlich.']);
        $user = User::factory()->create();
        $user->assignRole('Editor');
        $user->clients()->attach($client->id);
        $user->givePermissionTo('builder-pages.ai');

        return [$client, $prompt, $user];
    }

    it('answers via Mistral and logs metadata', function () {
        config(['ai.provider' => 'mistral', 'ai.model' => null]);
        Http::fake(['api.mistral.ai/*' => Http::response([
            'model'   => 'mistral-small-2603',
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => 'Kürzer: Hallo.']]],
            'usage'   => ['prompt_tokens' => 20, 'completion_tokens' => 4],
        ])]);
        [$client, $prompt, $user] = helpSetup(true);

        $this->actingAs($user)
            ->postJson('/api/ai_help', ['prompt' => 'Hallo und herzlich willkommen', 'system_prompt' => $prompt->id])
            ->assertOk()
            ->assertJsonPath('data.message', 'Kürzer: Hallo.');

        Http::assertSent(fn (HttpRequest $r) => $r->data()['model'] === 'mistral-small-2603'
            && $r->data()['messages'][0]['content'] === 'Sie-Form, sachlich.');
        expect(AIRequest::sole())->task->toBe('help')->client_id->toBe($client->id)->prompt_tokens->toBe(20);
    });

    it('is closed while the tenant switch is off', function () {
        config(['ai.provider' => 'mistral']);
        Http::fake();
        [, $prompt, $user] = helpSetup(false);

        $this->actingAs($user)
            ->postJson('/api/ai_help', ['prompt' => 'Hallo', 'system_prompt' => $prompt->id])
            ->assertForbidden();
        Http::assertNothingSent();
    });
});
