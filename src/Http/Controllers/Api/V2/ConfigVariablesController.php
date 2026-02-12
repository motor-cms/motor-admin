<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Motor\Admin\Http\Requests\Api\ConfigVariableGetRequest;
use Motor\Admin\Http\Requests\Api\ConfigVariablePatchRequest;
use Motor\Admin\Http\Requests\Api\ConfigVariablePostRequest;
use Motor\Admin\Http\Resources\V2\ConfigVariableCollection;
use Motor\Admin\Http\Resources\V2\ConfigVariableResource;
use Motor\Admin\Models\ConfigVariable;
use Motor\Admin\Services\ConfigVariableService;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * @tags Config Variables
 */
class ConfigVariablesController extends ApiController
{
    protected string $model = ConfigVariable::class;

    protected string $modelResource = 'config_variable';

    /**
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<ConfigVariableResource>>
     */
    public function index(ConfigVariableGetRequest $request): ConfigVariableCollection
    {
        $paginator = ConfigVariableService::collection()
            ->getPaginator();

        return (new ConfigVariableCollection($paginator))
            ->additional(['meta' => ['message' => 'Config variables retrieved']]);
    }

    public function show(ConfigVariable $configVariable): ConfigVariableResource
    {
        $result = ConfigVariableService::show($configVariable)
            ->getResult();

        return (new ConfigVariableResource($result))
            ->additional(['meta' => ['message' => 'Config variable retrieved']]);
    }

    public function store(ConfigVariablePostRequest $request): JsonResponse
    {
        $result = ConfigVariableService::create($request)
            ->getResult();

        return (new ConfigVariableResource($result))
            ->additional(['meta' => ['message' => 'Config variable created']])
            ->response()
            ->setStatusCode(201);
    }

    public function update(ConfigVariablePatchRequest $request, ConfigVariable $configVariable): ConfigVariableResource
    {
        $result = ConfigVariableService::update($configVariable, $request)
            ->getResult();

        return (new ConfigVariableResource($result))
            ->additional(['meta' => ['message' => 'Config variable updated']]);
    }

    public function destroy(ConfigVariable $configVariable): Response
    {
        ConfigVariableService::delete($configVariable);

        return $this->noContentResponse();
    }
}
