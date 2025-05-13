<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\LanguageGetRequest;
use Motor\Admin\Http\Requests\Api\LanguagePatchRequest;
use Motor\Admin\Http\Requests\Api\LanguagePostRequest;
use Motor\Admin\Http\Requests\Api\LanguageRequest;
use Motor\Admin\Http\Resources\LanguageCollection;
use Motor\Admin\Http\Resources\LanguageResource;
use Motor\Admin\Models\Language;
use Motor\Admin\Services\LanguageService;

/**
 * Class LanguagesController
 */
class LanguagesController extends ApiController
{
    protected string $model = Language::class;

    protected string $modelResource = 'language';

    /**
     * List/search all records
     *
     * This will return a paginated response. Some limited search operations are also possible.
     *
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<LanguageCollection>>
     */
    public function index(LanguageGetRequest $request): LanguageCollection
    {
        $paginator = LanguageService::collection()
                                    ->getPaginator();

        return new LanguageCollection($paginator)->additional(['message' => 'Language collection read']);
    }

    /**
     * Create record
     */
    public function store(LanguagePostRequest $request): JsonResponse
    {
        $result = LanguageService::create($request)
                                 ->getResult();

        return new LanguageResource($result)->additional(['message' => 'Language created'])
                                            ->response()
                                            ->setStatusCode(201);
    }

    /**
     * Get a single record
     */
    public function show(Language $language): LanguageResource
    {
        $result = LanguageService::show($language)
                                 ->getResult();

        return new LanguageResource($result)->additional(['message' => 'Language read']);
    }

    /**
     * Update record
     */
    public function update(LanguagePatchRequest $request, Language $language): LanguageResource
    {
        $result = LanguageService::update($language, $request)
                                 ->getResult();

        return new LanguageResource($result)->additional(['message' => 'Language updated']);
    }

    /**
     * Delete record
     */
    public function destroy(Language $language): JsonResponse
    {
        $result = LanguageService::delete($language)
                                 ->getResult();

        if ($result) {
            return response()->json(['message' => 'Language deleted']);
        }

        return response()->json(['message' => 'Problem deleting language'], 400);
    }
}
