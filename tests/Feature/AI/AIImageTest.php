<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Motor\Admin\Models\AIRequest;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\User;
use Motor\Admin\Services\AI\AIProviderException;
use Motor\Admin\Services\AI\Images\AiImageMarker;
use Motor\Admin\Services\AI\Images\MistralImageGenerator;
use Motor\Admin\Services\AI\Images\Stock\PexelsProvider;
use Motor\Admin\Services\AI\Images\Stock\PixabayProvider;
use Motor\Admin\Services\AI\Images\Stock\StockImage;
use Motor\Admin\Services\AI\Images\Stock\StockProviders;
use Motor\Media\Models\File;

pest()->group('AIBuilder')->use(RefreshDatabase::class);

function tinyJpeg(): string
{
    $img = imagecreatetruecolor(8, 6);
    imagefill($img, 0, 0, imagecolorallocate($img, 200, 120, 40));
    ob_start();
    imagejpeg($img);

    return (string) ob_get_clean();
}

function tinyPng(): string
{
    $img = imagecreatetruecolor(8, 6);
    ob_start();
    imagepng($img);

    return (string) ob_get_clean();
}

/** JPEG with an APP11 JUMBF segment labelled c2pa right after SOI, like Mistral's output */
function c2paJpeg(): string
{
    $jpeg = tinyJpeg();
    $payload = "JP\x00\x11\x00\x00\x00\x01\x00\x00\x00\x20jumbjumdc2pa urn:c2pa:test trainedAlgorithmicMedia";

    return "\xFF\xD8\xFF\xEB".pack('n', strlen($payload) + 2).$payload.substr($jpeg, 2);
}

function imageClient(bool $enabled = true, array $extra = []): Client
{
    $client = Client::factory()->create();
    $client->ai_config = array_merge(['enabled' => $enabled], $extra);
    $client->save();

    return $client;
}

function imageEditor(Client $client, bool $withPermission = true): User
{
    $user = User::factory()->create();
    $user->assignRole('Editor');
    $user->clients()->attach($client->id);
    if ($withPermission) {
        $user->givePermissionTo('builder-pages.ai');
    }

    return $user;
}

/** Live answer shape (11/2026): signed URL in the tool.execution entry, message text only */
function conversationWithUrl(string $url = 'https://mistralaiblackforestprod.blob.core.windows.net/images/pruna/x/image.jpg?sig=abc'): array
{
    return [
        'object'          => 'conversation.response',
        'conversation_id' => 'conv_test',
        'outputs'         => [
            [
                'object'    => 'entry',
                'type'      => 'tool.execution',
                'model'     => 'mistral-medium-2604',
                'id'        => 'tool_exec_1',
                'name'      => 'image_generation',
                'arguments' => json_encode(['prompt' => 'A modern heat pump in front of a house, evening light']),
                'info'      => ['result' => json_encode(['url' => $url])],
            ],
            ['object' => 'entry', 'type' => 'message.output', 'role' => 'assistant', 'content' => 'OK'],
        ],
        'usage' => ['prompt_tokens' => 200, 'completion_tokens' => 70, 'total_tokens' => 555, 'connector_tokens' => 285, 'connectors' => ['image_generation' => 1]],
    ];
}

function imageBody(Client $client, array $overrides = []): array
{
    return array_merge([
        'client_id'       => $client->id,
        'prompt'          => 'Fotorealistisch: moderne Wärmepumpe vor einem Einfamilienhaus, Abendlicht',
        'alt_text'        => 'Wärmepumpe vor einem Einfamilienhaus',
        'query'           => 'Wärmepumpe Haus',
        'photorealistic'  => true,
        'caption_visible' => true,
    ], $overrides);
}

beforeEach(function () {
    config(['ai.mistral.api_key' => 'test-key', 'ai.images.model' => 'mistral-medium-2604', 'ai.images.price_per_image_usd' => 0.1]);
    Storage::fake('media');
    Sleep::fake();
});

describe('AiImageMarker', function () {
    it('adds an XMP packet with DigitalSourceType to a JPEG without C2PA, without re-encoding', function () {
        $jpeg = tinyJpeg();
        $out = AiImageMarker::mark($jpeg, 'image/jpeg', 'Mistral AI image_generation', 'KI-generiert');

        expect($out['marking'])->toBe('xmp')
            ->and($out['bytes'])->toContain('http://ns.adobe.com/xap/1.0/')
            ->toContain('Iptc4xmpExt:DigitalSourceType="'.AiImageMarker::DIGITAL_SOURCE_TYPE.'"')
            ->and(AiImageMarker::isMarked($out['bytes']))->toBeTrue()
            ->and(getimagesizefromstring($out['bytes'])[0])->toBe(8)
            // image data untouched: the original scan follows the inserted segment byte for byte
            ->and(str_ends_with($out['bytes'], substr($jpeg, 20)))->toBeTrue();
    });

    it('leaves a C2PA-signed JPEG byte for byte (the signature covers the layout)', function () {
        $jpeg = c2paJpeg();
        $out = AiImageMarker::mark($jpeg, 'image/jpeg', 'x');

        expect($out['marking'])->toBe('c2pa')->and($out['bytes'])->toBe($jpeg);
    });

    it('adds iTXt XMP and a tEXt note to a PNG', function () {
        $out = AiImageMarker::mark(tinyPng(), 'image/png', 'x', 'AI generated');

        expect($out['marking'])->toBe('xmp')
            ->and($out['bytes'])->toContain('iTXtXML:com.adobe.xmp')->toContain('tEXtDescription')
            ->and(imagecreatefromstring($out['bytes']))->not->toBeFalse();
    });

    it('does not touch other formats', function () {
        expect(AiImageMarker::mark('RIFF....WEBP', 'image/webp', 'x')['marking'])->toBe('none');
    });
});

describe('POST /api/v2/ai/builder/image', function () {
    it('generates an image, stores it in the tenant media library with origin and logs the cost', function () {
        Http::fake([
            'api.mistral.ai/v1/conversations' => Http::response(conversationWithUrl()),
            '*.blob.core.windows.net/*'       => Http::response(tinyJpeg(), 200, ['Content-Type' => 'application/octet-stream']),
        ]);
        $client = imageClient();
        $user = imageEditor($client);

        $response = $this->actingAs($user)->postJson('/api/v2/ai/builder/image', imageBody($client));

        $response->assertOk()
            ->assertJsonPath('data.ai_labeling', 'generated')
            ->assertJsonPath('data.alt_text', 'Wärmepumpe vor einem Einfamilienhaus')
            ->assertJsonPath('data.file.origin.source_type', 'ai')
            ->assertJsonPath('data.file.origin.provider', 'mistral')
            ->assertJsonPath('data.file.origin.ai_prompt', imageBody($client)['prompt'])
            ->assertJsonPath('data.file.origin.ai_prompt_revised', 'A modern heat pump in front of a house, evening light')
            ->assertJsonPath('data.file.origin.marking', 'xmp')
            ->assertJsonPath('data.file.origin.caption_visible', true)
            ->assertJsonPath('data.file.origin.imported_by', $user->id)
            ->assertJsonPath('meta.marking', 'xmp');

        $file = File::withoutGlobalScopes()->findOrFail($response->json('data.id'));
        $media = $file->getFirstMedia('file');
        expect((int) $file->client_id)->toBe($client->id)
            ->and($file->author)->toBe('KI-generiert (Mistral AI)')
            ->and($media->file_name)->toStartWith('ki-waermepumpe-haus-')->toEndWith('.jpg')
            ->and($media->getCustomProperty('origin.ai_model'))->toBe('mistral-medium-2604')
            ->and(AiImageMarker::isMarked(file_get_contents($media->getPath())))->toBeTrue();

        $log = AIRequest::sole();
        // tokens: (200 + 285) * 1.5 + 70 * 7.5 per 1M, plus 0.10 per image
        expect($log->task)->toBe('builder.image')
            ->and($log->status)->toBe('ok')
            ->and((float) $log->cost_usd)->toBe(round((485 * 1.5 + 70 * 7.5) / 1_000_000 + 0.1, 6))
            ->and($log->prompt_text)->toBeNull();

        Http::assertSent(function (HttpRequest $request) {
            if (! str_ends_with($request->url(), '/conversations')) {
                return false;
            }

            return $request['tools'] === [['type' => 'image_generation']]
                && $request['model'] === 'mistral-medium-2604'
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
    });

    it('keeps and delivers the image when the search index is down', function () {
        Http::fake([
            'api.mistral.ai/v1/conversations' => Http::response(conversationWithUrl()),
            '*.blob.core.windows.net/*'       => Http::response(tinyJpeg(), 200, ['Content-Type' => 'application/octet-stream']),
        ]);
        $client = imageClient();
        $user = imageEditor($client);
        // Meilisearch down (cluster node failure 11/2026): every index write throws
        $manager = app(\Laravel\Scout\EngineManager::class);
        $manager->extend('broken', fn () => new class extends \Laravel\Scout\Engines\NullEngine
        {
            public function update($models)
            {
                throw new RuntimeException('Failed to connect to meilisearch');
            }
        });
        config(['scout.driver' => 'broken']);
        $manager->forgetDrivers();

        $response = $this->actingAs($user)->postJson('/api/v2/ai/builder/image', imageBody($client));

        $response->assertOk()->assertJsonPath('data.ai_labeling', 'generated');
        expect(File::withoutGlobalScopes()->find($response->json('data.id'))?->getFirstMedia('file'))->not->toBeNull();
    });

    it('offers only media library images whose file is on the disk (same URL as a pick)', function () {
        $client = imageClient();
        $make = function (string $alt) use ($client) {
            $file = File::withoutSyncingToSearch(fn () => tap(new File(['client_id' => $client->id, 'alt_text' => $alt, 'description' => $alt, 'author' => '', 'source' => '', 'is_global' => false]))->save());
            $file->addMediaFromString(tinyJpeg())->usingFileName(\Illuminate\Support\Str::slug($alt).'.jpg')->toMediaCollection('file', 'media');

            return $file->refresh();
        };
        $present = $make('Wärmepumpe vor dem Haus');
        $missing = $make('Wärmepumpe im Garten');
        unlink($missing->getFirstMedia('file')->getPath());

        $hits = (new \Motor\Admin\Services\AI\Builder\ScoutMediaSearch)->images($client->id, 'Wärmepumpe');

        expect(array_column($hits, 'id'))->toBe([$present->id])
            ->and($hits[0]['file']['url'])->toBe(json_decode(json_encode((new \Motor\Media\Http\Resources\FileResource($present))->resolve(request())), true)['file']['url']);
    });

    it('fetches a tool_file chunk over the Files API when the answer has one', function () {
        Http::fake([
            'api.mistral.ai/v1/conversations'          => Http::response([
                'outputs' => [['type' => 'message.output', 'content' => [
                    ['type' => 'text', 'text' => 'Hier'],
                    ['type' => 'tool_file', 'tool' => 'image_generation', 'file_id' => 'f-1', 'file_name' => 'image', 'file_type' => 'png'],
                ]]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'connectors' => ['image_generation' => 1]],
            ]),
            'api.mistral.ai/v1/files/f-1/content' => Http::response(c2paJpeg()),
        ]);
        $client = imageClient();

        $this->actingAs(imageEditor($client))->postJson('/api/v2/ai/builder/image', imageBody($client))
            ->assertOk()
            ->assertJsonPath('data.file.origin.marking', 'c2pa')
            ->assertJsonPath('data.file.origin.source_id', 'file:f-1');
    });

    it('never fetches an image from a host outside the allow list', function () {
        Http::fake(['api.mistral.ai/v1/conversations' => Http::response(conversationWithUrl('https://evil.example/x.jpg'))]);
        $client = imageClient();
        $before = File::withoutGlobalScopes()->count();

        $this->actingAs(imageEditor($client))->postJson('/api/v2/ai/builder/image', imageBody($client))
            ->assertStatus(422)->assertJsonPath('error.code', 'AI_IMAGE_FAILED');

        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), 'evil.example'));
        expect(File::withoutGlobalScopes()->count())->toBe($before)
            ->and(AIRequest::sole()->status)->toBe('invalid')
            // the tool call was billed even though the image is unusable
            ->and((float) AIRequest::sole()->cost_usd)->toBeGreaterThan(0.1);
    });

    it('answers 422 when the model does not call the tool', function () {
        Http::fake(['api.mistral.ai/v1/conversations' => Http::response(['outputs' => [['type' => 'message.output', 'content' => 'Nein']], 'usage' => []])]);
        $client = imageClient();

        $this->actingAs(imageEditor($client))->postJson('/api/v2/ai/builder/image', imageBody($client))
            ->assertStatus(422)->assertJsonPath('error.code', 'AI_IMAGE_FAILED');
    });

    it('does not repeat a failed generation (it may already be billed)', function () {
        Http::fake(['api.mistral.ai/v1/conversations' => Http::response(['message' => 'boom'], 500)]);
        $client = imageClient();

        $this->actingAs(imageEditor($client))->postJson('/api/v2/ai/builder/image', imageBody($client))
            ->assertStatus(503)->assertJsonPath('error.code', 'AI_PROVIDER_ERROR');

        Http::assertSentCount(1);
    });

    it('refuses brands and other companies before calling Mistral', function () {
        Http::fake();
        $client = imageClient();

        $this->actingAs(imageEditor($client))->postJson('/api/v2/ai/builder/image', imageBody($client, ['prompt' => 'Monteur mit Vattenfall-Logo auf der Jacke']))
            ->assertStatus(422)->assertJsonPath('error.code', 'AI_CONTENT_REFUSED');

        Http::assertNothingSent();
        expect(AIRequest::sole()->status)->toBe('refused');
    });

    it('is off for tenants without AI, without the permission and with images switched off', function () {
        Http::fake();
        $off = imageClient(false);
        $this->actingAs(imageEditor($off))->postJson('/api/v2/ai/builder/image', imageBody($off))->assertForbidden();

        $client = imageClient();
        $this->actingAs(imageEditor($client, withPermission: false))->postJson('/api/v2/ai/builder/image', imageBody($client))->assertForbidden();

        $noImages = imageClient(true, ['images' => ['generate' => false]]);
        $this->actingAs(imageEditor($noImages))->postJson('/api/v2/ai/builder/image', imageBody($noImages))
            ->assertForbidden()->assertJsonPath('error.code', 'FORBIDDEN');

        Http::assertNothingSent();
    });

    it('limits images per tenant and day, counted only after authorization', function () {
        config(['ai.images.rate_per_client_day' => 1]);
        Http::fake([
            'api.mistral.ai/v1/conversations' => Http::response(conversationWithUrl()),
            '*.blob.core.windows.net/*'       => Http::response(tinyJpeg()),
        ]);
        $client = imageClient();
        $foreign = imageEditor(imageClient());

        // a user of another tenant cannot use up this tenant's quota
        $this->actingAs($foreign)->postJson('/api/v2/ai/builder/image', imageBody($client))->assertForbidden();

        $user = imageEditor($client);
        $this->actingAs($user)->postJson('/api/v2/ai/builder/image', imageBody($client))->assertOk();
        $this->actingAs($user)->postJson('/api/v2/ai/builder/image', imageBody($client))
            ->assertStatus(429)->assertJsonPath('error.code', 'RATE_LIMITED');
    });

    it('reports image sources in the status', function () {
        $client = imageClient();
        $user = imageEditor($client);

        $this->actingAs($user)->getJson('/api/v2/ai/builder/status?client_id='.$client->id)
            ->assertOk()
            ->assertJsonPath('data.images.generate', true)
            ->assertJsonPath('data.images.stock', []);

        config(['ai.images.stock.pexels.api_key' => 'px']);
        $this->actingAs($user)->getJson('/api/v2/ai/builder/status?client_id='.$client->id)
            ->assertJsonPath('data.images.stock', ['pexels']);
    });
});

describe('MistralImageGenerator', function () {
    it('accepts only https download hosts from the allow list', function () {
        expect(MistralImageGenerator::allowedHost('https://mistralaiblackforestprod.blob.core.windows.net/a.jpg'))->toBeTrue()
            ->and(MistralImageGenerator::allowedHost('http://mistralaiblackforestprod.blob.core.windows.net/a.jpg'))->toBeFalse()
            ->and(MistralImageGenerator::allowedHost('https://blob.core.windows.net.evil.example/a.jpg'))->toBeFalse();
    });
});

describe('Stock providers (off without key)', function () {
    it('are all off without keys', function () {
        expect((new StockProviders)->names())->toBe([])
            ->and((new PexelsProvider)->search('Wald'))->toBe([]);
    });

    it('maps Pexels results with photographer credit and a link to Pexels', function () {
        config(['ai.images.stock.pexels.api_key' => 'px-key']);
        Http::fake(['api.pexels.com/v1/search*' => Http::response(['photos' => [[
            'id' => 123, 'width' => 4000, 'height' => 2600, 'url' => 'https://www.pexels.com/photo/wald-123/',
            'photographer' => 'Anna Muster', 'photographer_url' => 'https://www.pexels.com/@anna', 'alt' => 'Wald im Nebel',
            'src' => ['original' => 'https://images.pexels.com/photos/123/o.jpeg', 'large2x' => 'https://images.pexels.com/photos/123/l.jpeg', 'medium' => 'https://images.pexels.com/photos/123/m.jpeg'],
        ]]])]);

        $provider = new PexelsProvider;
        [$hit] = $provider->search('Wald', 5);
        $origin = $provider->origin($hit);

        expect($hit->downloadUrl)->toBe('https://images.pexels.com/photos/123/l.jpeg')
            ->and($origin)->toMatchArray([
                'source_type' => 'stock', 'provider' => 'pexels', 'source_url' => 'https://www.pexels.com/photo/wald-123/',
                'author_name' => 'Anna Muster', 'license' => 'Pexels License', 'attribution_text' => 'Foto: Anna Muster auf Pexels',
                'credit_required' => true,
            ])
            ->and($origin['provider_response']['id'])->toBe(123);
        Http::assertSent(fn (HttpRequest $r) => $r->hasHeader('Authorization', 'px-key') && $r['query'] === 'Wald');
    });

    it('caches Pixabay searches for a day and downloads instead of hotlinking', function () {
        config(['ai.images.stock.pixabay.api_key' => 'pb-key']);
        Http::fake([
            'pixabay.com/api/*' => Http::response(['hits' => [[
                'id' => 9, 'pageURL' => 'https://pixabay.com/photos/solar-9/', 'tags' => 'solar, dach',
                'webformatURL' => 'https://pixabay.com/get/w.jpg', 'largeImageURL' => 'https://pixabay.com/get/l.jpg',
                'imageWidth' => 1920, 'imageHeight' => 1280, 'user' => 'maxm', 'user_id' => 42, 'userImageURL' => 'https://cdn.pixabay.com/u.jpg',
            ]]]),
            'pixabay.com/get/l.jpg' => Http::response(tinyJpeg()),
        ]);

        $provider = new PixabayProvider;
        $provider->search('Solar Dach');
        [$hit] = $provider->search('Solar Dach');
        $origin = $provider->origin($hit);

        expect($origin['author_url'])->toBe('https://pixabay.com/users/maxm-42/')
            ->and($origin['license'])->toBe('Pixabay Content License')
            ->and($origin['provider_response'])->not->toHaveKey('userImageURL')
            ->and(getimagesizefromstring($provider->download($hit))[0])->toBe(8);
        Http::assertSentCount(2); // one search (cached), one download
    });

    it('refuses downloads from foreign hosts', function () {
        config(['ai.images.stock.pexels.api_key' => 'px']);
        $image = new StockImage('pexels', '1', '', '', 'https://evil.example/x.jpg', 1, 1, null, null, '');

        expect(fn () => (new PexelsProvider)->download($image))->toThrow(AIProviderException::class);
    });
});
