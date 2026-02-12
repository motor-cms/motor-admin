<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Motor\Admin\Http\Requests\Api\LanguageGetRequest;
use Motor\Admin\Http\Requests\Api\LanguagePatchRequest;
use Motor\Admin\Http\Requests\Api\LanguagePostRequest;
use Motor\Admin\Http\Resources\V2\LanguageCollection;
use Motor\Admin\Http\Resources\V2\LanguageResource;
use Motor\Admin\Models\Language;
use Motor\Admin\Services\LanguageService;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * @tags Languages
 */
class LanguagesController extends ApiController
{
    protected string $model = Language::class;

    protected string $modelResource = 'language';

    /**
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<LanguageResource>>
     */
    public function index(LanguageGetRequest $request): LanguageCollection
    {
        $paginator = LanguageService::collection()
            ->getPaginator();

        return (new LanguageCollection($paginator))
            ->additional(['meta' => ['message' => 'Languages retrieved']]);
    }

    public function show(Language $language): LanguageResource
    {
        $result = LanguageService::show($language)
            ->getResult();

        return (new LanguageResource($result))
            ->additional(['meta' => ['message' => 'Language retrieved']]);
    }

    public function store(LanguagePostRequest $request): JsonResponse
    {
        $result = LanguageService::create($request)
            ->getResult();

        return (new LanguageResource($result))
            ->additional(['meta' => ['message' => 'Language created']])
            ->response()
            ->setStatusCode(201);
    }

    public function update(LanguagePatchRequest $request, Language $language): LanguageResource
    {
        $result = LanguageService::update($language, $request)
            ->getResult();

        return (new LanguageResource($result))
            ->additional(['meta' => ['message' => 'Language updated']]);
    }

    public function destroy(Language $language): Response
    {
        LanguageService::delete($language);

        return $this->noContentResponse();
    }
}
