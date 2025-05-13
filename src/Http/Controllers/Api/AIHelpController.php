<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\AIHelpPostRequest;
use Motor\Admin\Http\Resources\AIHelpResource;
use Motor\Admin\Models\AISystemPrompt;

class AIHelpController extends ApiController
{
    /**
     * Return AI help response
     *
     * Supports several providers (OpenAI, Anthropic, Google, Groq)
     */
    public function store(AIHelpPostRequest $request): JsonResponse
    {
        $system_prompt = AISystemPrompt::find($request->system_prompt);
        $message = config('ai.provider').'is not a valid provider';
        switch (config('ai.provider')) {
            case 'openai':
            case 'groq':
                $response = Http::withToken(config('ai.api_key'))->post(
                    config('ai.provider') === 'openai'
                        ? 'https://api.openai.com/v1/chat/completions'
                        : 'https://api.groq.com/openai/v1/chat/completions',
                    [
                        'model' => config('ai.model', 'gpt-4o-mini'),
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $system_prompt->prompt,
                            ],
                            [
                                'role' => 'user',
                                'content' => $request->prompt,
                            ],
                        ],
                    ]
                )->json();
                $message = array_key_exists('choices', $response) ? $response['choices'][0]['message']['content'] : (array_key_exists('error', $response) ? $response['error']['message'] : '');
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
                    'x-api-key' => config('ai.api_key'),
                    'anthropic-version' => config('ai_api_version', '2023-06-01'),
                ])->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('ai.model', 'claude-3-5-sonnet-20241022'),
                    'max_tokens' => 2048,
                    'system' => $system_prompt->prompt,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $request->prompt,
                        ],
                    ],
                ])->json();
                $message = array_key_exists('content', $response)
                    ? $response['content'][0]['text']
                    : $response['error']['message'];
                break;
        }

        $m = new \stdClass();
        $m->message = $message;

        return new AIHelpResource($m)->additional(['message' => 'Ai Help message returned'])
                                     ->response()
                                     ->setStatusCode(200);
    }
}
