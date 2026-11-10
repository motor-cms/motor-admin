<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\V2\AIBuilderRevisePostRequest;
use Motor\Admin\Http\Requests\Api\V2\AIBuilderSectionPostRequest;
use Motor\Admin\Http\Requests\Api\V2\AIBuilderStatusGetRequest;
use Motor\Admin\Models\Client;
use Motor\Admin\Models\User;
use Motor\Admin\Services\AI\AIAccess;
use Motor\Admin\Services\AI\AIBudget;
use Motor\Admin\Services\AI\AIProviderException;
use Motor\Admin\Services\AI\AIRequestLogger;
use Motor\Admin\Services\AI\Builder\AIContentRefusedException;
use Motor\Admin\Services\AI\Builder\AIOutputInvalidException;
use Motor\Admin\Services\AI\Builder\SectionGenerator;
use Motor\Admin\Services\AI\Images\Stock\StockProviders;

/**
 * KI im Page Builder (spike): section from prompt, catalog-driven.
 */
class AIBuilderController extends ApiController
{
    /**
     * Whether the builder should show AI entries for this tenant and user.
     */
    public function status(AIBuilderStatusGetRequest $request): JsonResponse
    {
        $user = $request->user();
        $client = AIAccess::findClient($request->integer('client_id'));
        if ($client === null || ! AIAccess::userCanAccessClient($user, $client)) {
            return $this->error('FORBIDDEN', 'Kein Zugriff auf diesen Mandanten.', 403);
        }

        $enabled = AIAccess::clientEnabled($client);
        $allowed = $enabled && AIAccess::userHasPermission($user);

        return response()->json([
            'data' => [
                'enabled'  => $enabled,
                'allowed'  => $allowed,
                'provider' => 'mistral',
                'model'    => config('ai.mistral.models.builder'),
                // Image sources after the own media library; stock only with a key, generation per tenant switch
                'images' => [
                    'generate' => $enabled && AIImageController::generationEnabled($client),
                    'stock'    => app(StockProviders::class)->names(),
                    'daily_limit' => (int) config('ai.images.rate_per_client_day', 20),
                ],
                // Remaining cost budget (user today, customer this month), null when off or not allowed
                'budget' => $allowed && AIBudget::enabled() ? AIBudget::status($user, $client) : null,
            ],
            'meta' => ['api_version' => 'v2'],
        ]);
    }

    public function section(AIBuilderSectionPostRequest $request, SectionGenerator $generator): JsonResponse
    {
        $user = $request->user();
        $client = AIAccess::authorize($user, AIAccess::findClient($request->integer('client_id')));
        if ($exceeded = AIBudget::check($user, $client, 'section')) {
            return AIBudget::response($exceeded);
        }
        $prompt = (string) $request->input('prompt');
        $model = (string) config('ai.mistral.models.builder');
        $log = fn (string $status, array $meta = [], ?string $error = null) => AIRequestLogger::log(
            task: 'builder.section',
            clientId: $client->id,
            userId: $user->id,
            provider: 'mistral',
            model: $meta['model'] ?? $model,
            status: $status,
            usage: $meta['usage'] ?? [],
            durationMs: $meta['duration_ms'] ?? 0,
            repairRounds: $meta['repair_rounds'] ?? 0,
            error: $error,
            prompt: $prompt,
        );

        $started = hrtime(true);
        try {
            $result = $generator->generate(
                prompt: $prompt,
                manifest: $request->input('catalog_manifest'),
                context: (array) $request->input('context', []),
                maxSections: $request->integer('max_sections', 3),
                model: $model,
                tonePrompt: AIAccess::tonePrompt($client),
                content: $request->boolean('content'),
                linkTargets: (array) $request->input('context.link_targets', []),
                clientId: $client->id,
                ownNames: array_filter([(string) $client->name]),
            );
        } catch (AIContentRefusedException $e) {
            $log('refused', $e->meta, $e->reason);

            return $this->error($e->code(), $e->getMessage(), 422);
        } catch (AIOutputInvalidException $e) {
            $log('invalid', $e->meta, 'output invalid after repairs');

            return $this->error('AI_OUTPUT_INVALID', $e->getMessage(), 422, ['errors' => $e->errors]);
        } catch (AIProviderException $e) {
            $log('error', ['duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000)], $e->getMessage());

            return $this->error('AI_PROVIDER_ERROR', 'Der KI-Dienst ist gerade nicht erreichbar. Bitte später erneut versuchen.', 503);
        }

        $log('ok', $result['meta']);

        return response()->json([
            'data' => $result['data'],
            'meta' => $result['meta'] + ['api_version' => 'v2', 'budget' => self::budgetMeta($user, $client)],
        ]);
    }

    /**
     * "Mit KI überarbeiten": one section (scope section) or the whole page (scope page) as operations
     * keep/move/update/delete/add. Same guard rails, rate limit and cost log (task builder.revise).
     */
    public function revise(AIBuilderRevisePostRequest $request, SectionGenerator $generator): JsonResponse
    {
        $user = $request->user();
        $client = AIAccess::authorize($user, AIAccess::findClient($request->integer('client_id')));
        if ($exceeded = AIBudget::check($user, $client, 'revise')) {
            return AIBudget::response($exceeded);
        }
        $prompt = (string) $request->input('prompt');
        $model = (string) config('ai.mistral.models.builder');
        $log = fn (string $status, array $meta = [], ?string $error = null) => AIRequestLogger::log(
            task: 'builder.revise',
            clientId: $client->id,
            userId: $user->id,
            provider: 'mistral',
            model: $meta['model'] ?? $model,
            status: $status,
            usage: $meta['usage'] ?? [],
            durationMs: $meta['duration_ms'] ?? 0,
            repairRounds: $meta['repair_rounds'] ?? 0,
            error: $error,
            prompt: $prompt,
        );

        $started = hrtime(true);
        try {
            $result = $generator->revise(
                instruction: $prompt,
                manifest: $request->input('catalog_manifest'),
                current: (array) $request->input('sections', []),
                scope: (string) $request->input('scope'),
                target: $request->input('target'),
                context: (array) $request->input('context', []),
                maxNew: $request->integer('max_new', 3),
                model: $model,
                tonePrompt: AIAccess::tonePrompt($client),
                linkTargets: (array) $request->input('context.link_targets', []),
                clientId: $client->id,
                ownNames: array_filter([(string) $client->name]),
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', 'Der Abschnitt lässt sich nicht überarbeiten (gesperrt oder unbekannt).', 422);
        } catch (AIContentRefusedException $e) {
            $log('refused', $e->meta, $e->reason);

            return $this->error($e->code(), $e->getMessage(), 422);
        } catch (AIOutputInvalidException $e) {
            $log('invalid', $e->meta, 'output invalid after repairs');

            return $this->error('AI_OUTPUT_INVALID', $e->getMessage(), 422, ['errors' => $e->errors]);
        } catch (AIProviderException $e) {
            $log('error', ['duration_ms' => (int) ((hrtime(true) - $started) / 1_000_000)], $e->getMessage());

            return $this->error('AI_PROVIDER_ERROR', 'Der KI-Dienst ist gerade nicht erreichbar. Bitte später erneut versuchen.', 503);
        }

        $log('ok', $result['meta']);

        return response()->json([
            'data' => $result['data'],
            'meta' => $result['meta'] + ['api_version' => 'v2', 'budget' => self::budgetMeta($user, $client)],
        ]);
    }

    /**
     * Budget after the call, so the dialog can update "Heute noch ca. … €" without another request.
     *
     * @return array<string, mixed>|null
     */
    public static function budgetMeta(User $user, Client $client): ?array
    {
        return AIBudget::enabled() ? AIBudget::status($user, $client) : null;
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
