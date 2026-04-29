<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\AISystemPromptGetRequest;
use Motor\Admin\Http\Requests\Api\AISystemPromptPatchRequest;
use Motor\Admin\Http\Requests\Api\AISystemPromptPostRequest;
use Motor\Admin\Http\Resources\AISystemPromptCollection;
use Motor\Admin\Http\Resources\AISystemPromptResource;
use Motor\Admin\Models\AISystemPrompt;
use Motor\Admin\Services\AISystemPromptService;

class AISystemPromptController extends ApiController
{
    protected string $model = AISystemPrompt::class;

    protected string $modelResource = 'ai_system_prompt';

    /**
     * List/search all records
     *
     * This will return a paginated response. Some limited search operations are also possible.
     *
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<AISystemPromptCollection>>
     */
    public function index(AISystemPromptGetRequest $request): AISystemPromptCollection
    {
        $paginator = AISystemPromptService::collection()->getPaginator();

        return new AISystemPromptCollection($paginator)->additional(['message' => 'Ai System prompt collection read']);
    }

    /**
     * Create record
     */
    public function store(AISystemPromptPostRequest $request): JsonResponse
    {
        $result = AISystemPromptService::create($request)->getResult();

        return new AISystemPromptResource($result)->additional(['message' => 'Ai system prompt created'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a single record
     */
    public function show(AISystemPrompt $aiSystemPrompt): AISystemPromptResource
    {
        $result = AISystemPromptService::show($aiSystemPrompt)->getResult();

        return new AISystemPromptResource($result)->additional(['message' => 'Ai system prompt read']);
    }

    /**
     * Update record
     */
    public function update(AISystemPromptPatchRequest $request, AISystemPrompt $aiSystemPrompt): AISystemPromptResource
    {
        $result = AISystemPromptService::update($aiSystemPrompt, $request)->getResult();

        return new AISystemPromptResource($result)->additional(['message' => 'Ai system prompt updated']);
    }

    /**
     * Delete record
     */
    public function destroy(AISystemPrompt $aiSystemPrompt): JsonResponse
    {
        $result = AISystemPromptService::delete($aiSystemPrompt)->getResult();

        if ($result) {
            return response()->json(['message' => 'Ai system prompt deleted']);
        }

        return response()->json(['message' => 'Problem deleting ai system prompt']);
    }
}
