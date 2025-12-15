<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Motor\Admin\Http\Requests\Api\PermissionGroupGetRequest;
use Motor\Admin\Http\Requests\Api\PermissionGroupPatchRequest;
use Motor\Admin\Http\Requests\Api\PermissionGroupPostRequest;
use Motor\Admin\Http\Resources\V2\PermissionGroupCollection;
use Motor\Admin\Http\Resources\V2\PermissionGroupResource;
use Motor\Admin\Models\PermissionGroup;
use Motor\Admin\Services\PermissionGroupService;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * @tags Permission Groups
 */
class PermissionGroupsController extends ApiController
{
    protected string $model = PermissionGroup::class;

    protected string $modelResource = 'permission_group';

    public function index(PermissionGroupGetRequest $request): PermissionGroupCollection
    {
        $paginator = PermissionGroupService::collection()
            ->getPaginator();

        return (new PermissionGroupCollection($paginator))
            ->additional(['meta' => ['message' => 'Permission groups retrieved']]);
    }

    public function show(PermissionGroup $permissionGroup): PermissionGroupResource
    {
        $result = PermissionGroupService::show($permissionGroup)
            ->getResult();

        return (new PermissionGroupResource($result->load('permissions')))
            ->additional(['meta' => ['message' => 'Permission group retrieved']]);
    }

    public function store(PermissionGroupPostRequest $request): JsonResponse
    {
        $result = PermissionGroupService::create($request)
            ->getResult();

        return (new PermissionGroupResource($result))
            ->additional(['meta' => ['message' => 'Permission group created']])
            ->response()
            ->setStatusCode(201);
    }

    public function update(PermissionGroupPatchRequest $request, PermissionGroup $permissionGroup): PermissionGroupResource
    {
        $result = PermissionGroupService::update($permissionGroup, $request)
            ->getResult();

        return (new PermissionGroupResource($result))
            ->additional(['meta' => ['message' => 'Permission group updated']]);
    }

    public function destroy(PermissionGroup $permissionGroup): Response
    {
        PermissionGroupService::delete($permissionGroup);

        return $this->noContentResponse();
    }
}
