<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\ConfigVariableGetRequest;
use Motor\Admin\Http\Requests\Api\ConfigVariablePatchRequest;
use Motor\Admin\Http\Requests\Api\ConfigVariablePostRequest;
use Motor\Admin\Http\Resources\ConfigVariableCollection;
use Motor\Admin\Http\Resources\ConfigVariableResource;
use Motor\Admin\Models\ConfigVariable;
use Motor\Admin\Services\ConfigVariableService;

/**
 * Class ConfigVariablesController
 */
class ConfigVariablesController extends ApiController
{
    protected string $model = ConfigVariable::class;

    protected string $modelResource = 'config_variable';

    /**
     * List/search all records
     *
     * This will return a paginated response. Some limited search operations are also possible.
     *
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<ConfigVariableCollection>>
     */
    public function index(ConfigVariableGetRequest $request): ConfigVariableCollection
    {
        $paginator = ConfigVariableService::collection()
            ->getPaginator();

        return new ConfigVariableCollection($paginator)->additional(['message' => 'Config variable collection read']);
    }

    /**
     * Create record
     */
    public function store(ConfigVariablePostRequest $request): JsonResponse
    {
        $result = ConfigVariableService::create($request)
            ->getResult();

        return new ConfigVariableResource($result)->additional(['message' => 'Config variable created'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a single record
     */
    public function show(ConfigVariable $configVariable): ConfigVariableResource
    {
        $result = ConfigVariableService::show($configVariable)
            ->getResult();

        return new ConfigVariableResource($result)->additional(['message' => 'Config variable read']);
    }

    /**
     * Update record
     */
    public function update(ConfigVariablePatchRequest $request, ConfigVariable $configVariable): ConfigVariableResource
    {
        $result = ConfigVariableService::update($configVariable, $request)
            ->getResult();

        return new ConfigVariableResource($result)->additional(['message' => 'Config variable updated']);
    }

    /**
     * Delete record
     */
    public function destroy(ConfigVariable $configVariable): JsonResponse
    {
        $result = ConfigVariableService::delete($configVariable)
            ->getResult();

        if ($result) {
            return response()->json(['message' => 'Config variable deleted']);
        }

        return response()->json(['message' => 'Problem deleting config variable'], 400);
    }
}
