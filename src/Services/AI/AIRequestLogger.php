<?php

namespace Motor\Admin\Services\AI;

use Motor\Admin\Models\AIRequest;
use Throwable;

/**
 * Writes one ai_requests row per call. Never lets logging break the request.
 */
class AIRequestLogger
{
    /**
     * @param  array{prompt_tokens?: int, completion_tokens?: int}  $usage
     */
    public static function log(
        string $task,
        ?int $clientId,
        ?int $userId,
        string $provider,
        ?string $model,
        string $status,
        array $usage = [],
        int $durationMs = 0,
        int $repairRounds = 0,
        ?string $error = null,
        ?string $prompt = null,
        float $extraCostUsd = 0.0,
    ): ?AIRequest {
        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);

        try {
            return AIRequest::create([
                'client_id'         => $clientId,
                'user_id'           => $userId,
                'task'              => $task,
                'provider'          => $provider,
                'model'             => $model,
                'status'            => $status,
                'prompt_tokens'     => $promptTokens,
                'completion_tokens' => $completionTokens,
                // extra: per-call prices on top of tokens (e.g. one generated image)
                'cost_usd'          => round(self::costUsd($model, $promptTokens, $completionTokens) + $extraCostUsd, 6),
                'duration_ms'       => $durationMs,
                'repair_rounds'     => $repairRounds,
                'error'             => $error !== null ? mb_substr($error, 0, 255) : null,
                'prompt_hash'       => $prompt !== null ? hash('sha256', $prompt) : null,
                'prompt_text'       => $prompt !== null && config('ai.log_prompts') ? $prompt : null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public static function costUsd(?string $model, int $promptTokens, int $completionTokens): float
    {
        [$in, $out] = config('ai.pricing.'.$model) ?? [0, 0];

        return round(($promptTokens * $in + $completionTokens * $out) / 1_000_000, 6);
    }
}
