<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\V2\AIImageGeneratePostRequest;
use Motor\Admin\Models\Client;
use Motor\Admin\Services\AI\AIAccess;
use Motor\Admin\Services\AI\AIBudget;
use Motor\Admin\Services\AI\AIProviderException;
use Motor\Admin\Services\AI\AIRequestLogger;
use Motor\Admin\Services\AI\Builder\ContentPolicy;
use Motor\Admin\Services\AI\Images\AiImageMarker;
use Motor\Admin\Services\AI\Images\ImageGenerationFailed;
use Motor\Admin\Services\AI\Images\MediaImporter;
use Motor\Admin\Services\AI\Images\MistralImageGenerator;
use Motor\Media\Http\Resources\V2\FileResource;

/**
 * KI im Page Builder: one image per request, only on the editor's explicit "Mit KI erzeugen".
 *
 * Gates: tenant switch + tenant access + ai.use (AIAccess), the cost budget (AIBudget), the image switch (config ai.images.generate
 * and clients.ai_config.images.generate, default on where AI is on), then a separate rate limit (per user per
 * minute, per tenant per day). The result goes straight into the tenant's media library with ai_labeling
 * "generated", origin metadata and the machine-readable marking in the file; ai_requests gets task builder.image
 * with token plus per-image cost.
 */
class AIImageController extends ApiController
{
    public static function generationEnabled(?Client $client): bool
    {
        return (bool) config('ai.images.generate', true)
            && AIAccess::clientEnabled($client)
            && data_get($client?->ai_config, 'images.generate', true) !== false;
    }

    public function generate(
        AIImageGeneratePostRequest $request,
        MistralImageGenerator $generator,
        MediaImporter $importer,
    ): JsonResponse {
        $user = $request->user();
        $client = AIAccess::authorize($user, AIAccess::findClient($request->integer('client_id')));
        if (! self::generationEnabled($client)) {
            return $this->error('FORBIDDEN', 'KI-Bilder sind für diesen Mandanten nicht freigeschaltet.', 403);
        }

        if ($exceeded = AIBudget::check($user, $client, 'image')) {
            return AIBudget::response($exceeded);
        }

        $limited = $this->rateLimited($user->id, $client->id);
        if ($limited !== null) {
            return $limited;
        }

        $prompt = trim((string) $request->input('prompt'));
        $model = (string) config('ai.images.model');
        $started = hrtime(true);
        $log = fn (string $status, array $usage = [], ?string $error = null, ?string $usedModel = null) => AIRequestLogger::log(
            task: 'builder.image',
            clientId: $client->id,
            userId: $user->id,
            provider: 'mistral',
            model: $usedModel ?? $model,
            status: $status,
            usage: ['prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0) + (int) ($usage['connector_tokens'] ?? 0), 'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0)],
            durationMs: (int) ((hrtime(true) - $started) / 1_000_000),
            error: $error,
            prompt: $prompt,
            extraCostUsd: self::imageCost($usage),
        );

        $policy = new ContentPolicy((array) config('ai.builder.third_party_names', []), array_filter([(string) $client->name]));
        if ($reason = $policy->checkImagePrompt($prompt)) {
            $log('refused', [], $reason);

            return $this->error('AI_CONTENT_REFUSED', 'Dazu erzeugt die KI kein Bild: keine Marken, Logos, anderen Unternehmen oder abwertenden Motive.', 422);
        }

        try {
            $image = $generator->generate($prompt);
        } catch (ImageGenerationFailed $e) {
            $log('invalid', $e->usage, $e->getMessage());

            return $this->error('AI_IMAGE_FAILED', 'Die KI hat kein Bild geliefert. Bitte die Beschreibung anpassen und erneut versuchen.', 422);
        } catch (AIProviderException $e) {
            $log('error', [], $e->getMessage());

            return $this->error('AI_PROVIDER_ERROR', 'Der KI-Dienst ist gerade nicht erreichbar. Bitte später erneut versuchen.', 503);
        }

        $creator = 'Mistral AI '.$image->tool.' ('.$image->model.')';
        $marked = AiImageMarker::mark($image->bytes, $image->mime, $creator, 'KI-generiert: '.mb_substr($prompt, 0, 200));
        $alt = trim((string) $request->input('alt_text')) ?: mb_substr($prompt, 0, 190);
        $photorealistic = $request->boolean('photorealistic', true);
        $now = now()->toIso8601String();

        $file = $importer->store(
            client: $client,
            user: $user,
            bytes: $marked['bytes'],
            mime: $image->mime,
            fileName: MediaImporter::fileName('ki', (string) ($request->input('query') ?: $prompt), $image->extension()),
            fields: [
                'description' => trim((string) $request->input('description')) ?: 'KI-generiertes Bild: '.$prompt,
                'alt_text'    => $alt,
                'author'      => 'KI-generiert (Mistral AI)',
                'source'      => 'Mistral AI, image_generation',
                'ai_labeling' => 'generated',
            ],
            origin: [
                'source_type'       => 'ai',
                'provider'          => $image->provider,
                'source_url'        => null,
                'source_id'         => $image->reference,
                'author_name'       => null,
                'author_url'        => null,
                'license'           => 'KI-generiert, keine Lizenz eines Dritten (Mistral AI Nutzungsbedingungen)',
                'license_url'       => 'https://legal.mistral.ai/terms/commercial-terms-of-service',
                'attribution_text'  => 'KI-generiert',
                'credit_required'   => $photorealistic,
                'contains_people'   => $request->has('contains_people') ? $request->boolean('contains_people') : null,
                'contains_brands'   => false,
                'ai_model'          => $image->model,
                'ai_tool'           => $image->tool,
                'ai_prompt'         => $prompt,
                'ai_prompt_revised' => $image->revisedPrompt,
                'ai_generated_at'   => $now,
                'photorealistic'    => $photorealistic,
                'caption_visible'   => $request->boolean('caption_visible', $photorealistic),
                'marking'           => $marked['marking'],
            ],
        );

        $entry = $log('ok', $image->usage, null, $image->model);

        return response()->json([
            'data' => (new FileResource($file->load('tags', 'categories')))->resolve($request),
            'meta' => [
                'api_version'    => 'v2',
                'cost_usd'       => $entry?->cost_usd !== null ? (float) $entry->cost_usd : null,
                'duration_ms'    => (int) ((hrtime(true) - $started) / 1_000_000),
                'marking'        => $marked['marking'],
                'revised_prompt' => $image->revisedPrompt,
                'budget'         => AIBuilderController::budgetMeta($user, $client),
            ],
        ]);
    }

    /** @param array<string, mixed> $usage */
    public static function imageCost(array $usage): float
    {
        return (int) ($usage['images'] ?? 0) * (float) config('ai.images.price_per_image_usd', 0.10);
    }

    /**
     * Separate limit for images, counted only after authorization (a foreign client_id never uses up a quota).
     */
    protected function rateLimited(int $userId, int $clientId): ?JsonResponse
    {
        $limits = [
            ['ai-image:user:'.$userId, (int) config('ai.images.rate_per_user_min', 5), 60, 'Zu viele KI-Bilder in kurzer Zeit. Bitte kurz warten.'],
            ['ai-image:client:'.$clientId, (int) config('ai.images.rate_per_client_day', 20), 86400, 'Das Tageslimit für KI-Bilder ist erreicht. Morgen geht es weiter.'],
        ];
        foreach ($limits as [$key, $max, , $message]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return $this->error('RATE_LIMITED', $message, 429, ['retry_after' => RateLimiter::availableIn($key)]);
            }
        }
        foreach ($limits as [$key, , $decay]) {
            RateLimiter::hit($key, $decay);
        }

        return null;
    }

    protected function error(string $code, string $message, int $status, ?array $details = null): JsonResponse
    {
        return response()->json([
            'error' => array_filter([
                'code'    => $code,
                'message' => $message,
                'details' => $details,
            ], fn ($v) => $v !== null),
            'meta' => ['api_version' => 'v2'],
        ], $status);
    }
}
