<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Motor\Admin\Http\Requests\Api\V2\EntityConfigurationGetRequest;
use Motor\Admin\Http\Requests\Api\V2\EntityConfigurationPatchRequest;
use Motor\Admin\Http\Requests\Api\V2\EntityConfigurationPostRequest;
use Motor\Admin\Http\Resources\V2\EntityConfigurationCollection;
use Motor\Admin\Http\Resources\V2\EntityConfigurationResource;
use Motor\Admin\Models\EntityConfiguration;
use Motor\Admin\Services\EntityConfigurationService;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * @tags Entity Configurations
 */
class EntityConfigurationsController extends ApiController
{
    protected string $model = EntityConfiguration::class;

    protected string $modelResource = 'entity_configuration';

    /**
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<EntityConfigurationResource>>
     */
    public function index(EntityConfigurationGetRequest $request): EntityConfigurationCollection
    {
        $paginator = EntityConfigurationService::collection()
            ->getPaginator();

        $paginator->load('configVariable');

        return (new EntityConfigurationCollection($paginator))
            ->additional(['meta' => ['message' => 'Entity configurations retrieved']]);
    }

    public function show(EntityConfiguration $entityConfiguration): EntityConfigurationResource
    {
        $result = EntityConfigurationService::show($entityConfiguration)
            ->getResult();

        $result->load('configVariable');

        return (new EntityConfigurationResource($result))
            ->additional(['meta' => ['message' => 'Entity configuration retrieved']]);
    }

    public function store(EntityConfigurationPostRequest $request): JsonResponse
    {
        $result = EntityConfigurationService::create($request)
            ->getResult();

        $result->load('configVariable');

        return (new EntityConfigurationResource($result))
            ->additional(['meta' => ['message' => 'Entity configuration created']])
            ->response()
            ->setStatusCode(201);
    }

    public function update(EntityConfigurationPatchRequest $request, EntityConfiguration $entityConfiguration): EntityConfigurationResource
    {
        $result = EntityConfigurationService::update($entityConfiguration, $request)
            ->getResult();

        $result->load('configVariable');

        return (new EntityConfigurationResource($result))
            ->additional(['meta' => ['message' => 'Entity configuration updated']]);
    }

    public function destroy(EntityConfiguration $entityConfiguration): Response
    {
        EntityConfigurationService::delete($entityConfiguration);

        return $this->noContentResponse();
    }
}
