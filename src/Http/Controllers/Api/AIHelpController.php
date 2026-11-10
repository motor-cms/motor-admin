<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\AIHelpPostRequest;
use Motor\Admin\Http\Resources\AIHelpResource;
use Motor\Admin\Models\AISystemPrompt;
use Motor\Admin\Services\AI\AIAccess;
use Motor\Admin\Services\AI\AIBudget;
use Motor\Admin\Services\AI\AIProviderException;
use Motor\Admin\Services\AI\AIRequestLogger;
use Motor\Admin\Services\AI\MistralClient;

class AIHelpController extends ApiController
{
    /**
     * Return AI help response
     *
     * Supports several providers (Mistral, OpenAI, Anthropic, Google, Groq).
     * The tenant is the client of the system prompt: it must have AI switched
     * on and the user needs access to it plus ai.use, and budget left (AIBudget).
     */
    public function store(AIHelpPostRequest $request, MistralClient $mistral): JsonResponse
    {
        $system_prompt = AISystemPrompt::findOrFail($request->system_prompt);
        $user = $request->user();
        $client = AIAccess::authorize($user, AIAccess::findClient($system_prompt->client_id));
        if ($exceeded = AIBudget::check($user, $client, 'help')) {
            return AIBudget::response($exceeded);
        }

        $provider = (string) config('ai.provider');
        $model = $provider === 'mistral' ? config('ai.model') ?: config('ai.mistral.models.help') : config('ai.model');
        $started = hrtime(true);
        $usage = [];
        $status = 'ok';
        $message = $provider.' is not a valid provider';

        switch ($provider) {
            case 'mistral':
                try {
                    $result = $mistral->chat([
                        'model'    => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $system_prompt->prompt],
                            ['role' => 'user', 'content' => $request->prompt],
                        ],
                        'max_tokens' => 2048,
                    ]);
                    $message = $result['text'];
                    $usage = $result['usage'];
                    $model = $result['model'] ?: $model;
                } catch (AIProviderException $e) {
                    $status = 'error';
                    $message = 'Der KI-Dienst ist gerade nicht erreichbar.';
                }
                break;

            case 'openai':
            case 'groq':
                $response = Http::withToken(config('ai.api_key'))->post(
                    config('ai.provider') === 'openai'
                        ? 'https://api.openai.com/v1/chat/completions'
                        : 'https://api.groq.com/openai/v1/chat/completions',
                    [
                        'model'    => config('ai.model', 'gpt-4o-mini'),
                        'messages' => [
                            [
                                'role'    => 'system',
                                'content' => $system_prompt->prompt,
                            ],
                            [
                                'role'    => 'user',
                                'content' => $request->prompt,
                            ],
                        ],
                    ]
                )->json();
                $message = array_key_exists('choices', $response) ? $response['choices'][0]['message']['content'] : (array_key_exists('error', $response) ? $response['error']['message'] : '');
                $usage = $response['usage'] ?? [];
                break;

            case 'google':
                $response = Http::withQueryParameters(['key' => config('ai.api_key')])
                    ->post(
                        'https://generativelanguage.googleapis.com/v1beta/models/'
                        .config('ai.model', 'gemini-1.5-flash')
                        .':generateContent',
                        [
                            'system_instruction' => [
                                'parts' => [
                                    'text' => $system_prompt->prompt,
                                ],
                            ],
                            'contents' => [
                                'parts' => [
                                    'text' => $request->prompt,
                                ],
                            ],
                        ]
                    )->json();
                $message = array_key_exists('candidates', $response)
                    ? $response['candidates'][0]['content']['parts'][0]['text']
                    : $response['error']['message'];
                break;
            case 'anthropic':
                $response = Http::withHeaders([
                    'x-api-key'         => config('ai.api_key'),
                    'anthropic-version' => config('ai_api_version', '2023-06-01'),
                ])->post('https://api.anthropic.com/v1/messages', [
                    'model'      => config('ai.model', 'claude-3-5-sonnet-20241022'),
                    'max_tokens' => 2048,
                    'system'     => $system_prompt->prompt,
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => $request->prompt,
                        ],
                    ],
                ])->json();
                $message = array_key_exists('content', $response)
                    ? $response['content'][0]['text']
                    : $response['error']['message'];
                break;
            default:
                $status = 'error';
        }

        AIRequestLogger::log(
            task: 'help',
            clientId: $client->id,
            userId: $user->id,
            provider: $provider,
            model: $model,
            status: $status,
            usage: [
                'prompt_tokens'     => $usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0,
                'completion_tokens' => $usage['completion_tokens'] ?? $usage['output_tokens'] ?? 0,
            ],
            durationMs: (int) ((hrtime(true) - $started) / 1_000_000),
            prompt: (string) $request->prompt,
        );

        $m = new \stdClass;
        $m->message = $message;

        return new AIHelpResource($m)->additional(['message' => 'Ai Help message returned'])
            ->response()
            ->setStatusCode($status === 'ok' ? 200 : 503);
    }
}
