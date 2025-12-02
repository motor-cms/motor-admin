<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Motor\Admin\Http\Requests\Api\AISystemPromptGetRequest;
use Motor\Admin\Http\Requests\Api\AISystemPromptPatchRequest;
use Motor\Admin\Http\Requests\Api\AISystemPromptPostRequest;
use Motor\Admin\Http\Resources\V2\AISystemPromptCollection;
use Motor\Admin\Http\Resources\V2\AISystemPromptResource;
use Motor\Admin\Models\AISystemPrompt;
use Motor\Admin\Services\AISystemPromptService;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * @tags AI System Prompts
 */
class AISystemPromptsController extends ApiController
{
    public function index(AISystemPromptGetRequest $request): AISystemPromptCollection
    {
        $paginator = AISystemPromptService::collection()
            ->getPaginator();

        return (new AISystemPromptCollection($paginator))
            ->additional(['meta' => ['message' => 'AI system prompts retrieved']]);
    }

    public function show(AISystemPrompt $aiSystemPrompt): AISystemPromptResource
    {
        $result = AISystemPromptService::show($aiSystemPrompt)
            ->getResult();

        return (new AISystemPromptResource($result))
            ->additional(['meta' => ['message' => 'AI system prompt retrieved']]);
    }

    public function store(AISystemPromptPostRequest $request): JsonResponse
    {
        $result = AISystemPromptService::create($request)
            ->getResult();

        return (new AISystemPromptResource($result))
            ->additional(['meta' => ['message' => 'AI system prompt created']])
            ->response()
            ->setStatusCode(201);
    }

    public function update(AISystemPromptPatchRequest $request, AISystemPrompt $aiSystemPrompt): AISystemPromptResource
    {
        $result = AISystemPromptService::update($aiSystemPrompt, $request)
            ->getResult();

        return (new AISystemPromptResource($result))
            ->additional(['meta' => ['message' => 'AI system prompt updated']]);
    }

    public function destroy(AISystemPrompt $aiSystemPrompt): Response
    {
        AISystemPromptService::delete($aiSystemPrompt);

        return $this->noContentResponse();
    }
}
