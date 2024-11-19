<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\AIHelpPostRequest;
use Motor\Admin\Models\AISystemPrompt;

class AIHelpController extends ApiController
{
    public function store(AIHelpPostRequest $request): JsonResponse
    {
        ds($request);
        $system_prompt = AISystemPrompt::find($request->system_prompt);
        ds($system_prompt);
        $response = Http::withToken(config("openai.api_key"))->post("https://api.openai.com/v1/chat/completions", [
            "model" => config("openai.model"),
            "messages" => [
                [
                    "role" => "system",
                    "content" => $system_prompt->prompt
                ],
                [
                    "role" => "user",
                    "content" => $request->prompt
                ]
            ]
        ])->json();
        return response()->json([
            "message" => array_key_exists("choices", $response) ? $response["choices"][0]["message"]["content"] : (array_key_exists("error", $response) ? $response["error"]["message"] : ""),
            "rest" => $response
        ]);
    }
}
