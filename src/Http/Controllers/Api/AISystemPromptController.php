<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\AISystemPromptPatchRequest;
use Motor\Admin\Http\Requests\Api\AISystemPromptPostRequest;
use Motor\Admin\Http\Resources\AISystemPromptCollection;
use Motor\Admin\Http\Resources\AISystemPromptResource;
use Motor\Admin\Models\AISystemPrompt;
use Motor\Admin\Services\AISystemPromptService;

class AISystemPromptcontroller extends ApiController
{
    protected string $model = AISystemPrompt::class;

    public function index(): AISystemPromptCollection
    {
        $paginator = AISystemPromptService::collection()->getPaginator();
        return (new AISystemPromptCollection($paginator))->additional(['message' => 'Ai System prompt collection read']);
    }

    public function store(AISystemPromptPostRequest $request): JsonResponse
    {
        $result = AISystemPromptService::create($request)->getResult();

        return (new AISystemPromptResource($result))->additional(['message' => 'Ai system prompt created'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(AISystemPrompt $aiSystemPrompt): AISystemPromptResource
    {
        $result = AISystemPromptService::show($aiSystemPrompt)->getResult();
        return (new AISystemPromptResource($result))->additional(['message' => 'Ai system prompt read']);
    }

    public function update(AISystemPromptPatchRequest $request, AISystemPrompt $aiSystemPrompt): AISystemPromptResource
    {
        $result = AISystemPromptService::update($aiSystemPrompt, $request)->getResult();
        return (new AISystemPromptResource($result))->additional(['message' => 'Ai system prompt updated']);
    }

    public function destroy(AISystemPrompt $aiSystemPrompt): JsonResponse
    {
        $result = AISystemPromptService::delete($aiSystemPrompt)->getResult();

        if ($result) {
            return response()->json(['message' => 'Ai system prompt deleted']);
        }

        return response()->json(['message' => 'Problem deleting ai system prompt']);
    }
}
