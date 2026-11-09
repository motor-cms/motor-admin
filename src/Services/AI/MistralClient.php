<?php

namespace Motor\Admin\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around Mistral's chat completions endpoint (EU, api.mistral.ai).
 *
 * Supports tools/function calling and `response_format` json_schema; retries
 * 429 and 5xx with backoff. Returns the decoded response plus the plain text
 * of the first choice (reasoning models answer with a list of chunks, the
 * `thinking` chunks are dropped).
 */
class MistralClient
{
    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $baseUrl = null,
        protected ?int $timeout = null,
    ) {
        $this->apiKey ??= config('ai.mistral.api_key');
        $this->baseUrl ??= config('ai.mistral.base_url', 'https://api.mistral.ai/v1');
        $this->timeout ??= (int) config('ai.mistral.timeout', 60);
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * @param  array<string, mixed>  $payload  chat completions body (model, messages, tools, response_format, …)
     * @return array{text: string, tool_calls: array, finish_reason: ?string, usage: array, model: string, raw: array}
     */
    public function chat(array $payload): array
    {
        if (! $this->isConfigured()) {
            throw new AIProviderException('Mistral is not configured (MISTRAL_API_KEY missing).');
        }

        $effort = config('ai.mistral.reasoning_effort.'.($payload['model'] ?? ''));
        if ($effort && ! array_key_exists('reasoning_effort', $payload)) {
            $payload['reasoning_effort'] = $effort;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->timeout($this->timeout)
                ->retry(3, fn (int $attempt) => $attempt * 1000, function ($exception) {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    return $exception instanceof RequestException
                        && ($exception->response->status() === 429 || $exception->response->serverError());
                }, throw: false)
                ->post('/chat/completions', $payload);
        } catch (ConnectionException) {
            throw new AIProviderException('Mistral is not reachable.');
        }

        if (! $response->successful()) {
            throw new AIProviderException($this->errorMessage($response), $response->status());
        }

        $data = $response->json() ?? [];
        $message = $data['choices'][0]['message'] ?? [];

        return [
            'text'          => self::textOf($message['content'] ?? ''),
            'tool_calls'    => $message['tool_calls'] ?? [],
            'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
            'usage'         => $data['usage'] ?? [],
            'model'         => $data['model'] ?? ($payload['model'] ?? ''),
            'raw'           => $data,
        ];
    }

    /**
     * Conversations API (`POST /conversations`), needed for built-in tools such as image_generation.
     *
     * @param  array<string, mixed>  $payload  inputs, model, tools, instructions, completion_args, store
     * @return array{outputs: array, usage: array, conversation_id: ?string, raw: array}
     */
    public function conversation(array $payload, ?int $timeout = null): array
    {
        // Only 429 is retried: a timeout or 5xx may still have produced (and billed) an image.
        $response = $this->send(fn ($http) => $http->acceptJson()->timeout($timeout ?? $this->timeout)->post('/conversations', $payload), safeToRepeat: false);
        $data = $response->json() ?? [];

        return [
            'outputs'         => (array) ($data['outputs'] ?? []),
            'usage'           => (array) ($data['usage'] ?? []),
            'conversation_id' => $data['conversation_id'] ?? null,
            'raw'             => $data,
        ];
    }

    /**
     * Binary content of a file the API produced (`GET /files/{id}/content`).
     */
    public function fileContent(string $fileId): string
    {
        return $this->send(fn ($http) => $http->timeout($this->timeout)->get('/files/'.rawurlencode($fileId).'/content'))->body();
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     */
    protected function send(callable $call, bool $safeToRepeat = true): Response
    {
        if (! $this->isConfigured()) {
            throw new AIProviderException('Mistral is not configured (MISTRAL_API_KEY missing).');
        }

        try {
            $response = $call(Http::withToken($this->apiKey)
                ->baseUrl($this->baseUrl)
                ->retry(3, fn (int $attempt) => $attempt * 1000, function ($exception) use ($safeToRepeat) {
                    if ($exception instanceof ConnectionException) {
                        return $safeToRepeat;
                    }

                    return $exception instanceof RequestException
                        && ($exception->response->status() === 429 || ($safeToRepeat && $exception->response->serverError()));
                }, throw: false));
        } catch (ConnectionException) {
            throw new AIProviderException('Mistral is not reachable.');
        }

        if (! $response->successful()) {
            throw new AIProviderException($this->errorMessage($response), $response->status());
        }

        return $response;
    }

    /**
     * Content is either a string or (reasoning models) a list of typed chunks.
     */
    public static function textOf(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }
        if (! is_array($content)) {
            return '';
        }

        return collect($content)
            ->filter(fn ($chunk) => is_array($chunk) && ($chunk['type'] ?? null) === 'text')
            ->map(fn ($chunk) => (string) ($chunk['text'] ?? ''))
            ->implode('');
    }

    protected function errorMessage(Response $response): string
    {
        $detail = $response->json('message') ?? $response->json('error.message') ?? $response->json('detail');
        $detail = is_string($detail) ? mb_substr($detail, 0, 200) : '';

        return trim('Mistral returned HTTP '.$response->status().'. '.$detail);
    }
}
