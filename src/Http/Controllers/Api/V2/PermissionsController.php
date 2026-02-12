<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Motor\Admin\Http\Requests\Api\PermissionGetRequest;
use Motor\Admin\Http\Requests\Api\PermissionListGetRequest;
use Motor\Admin\Http\Requests\Api\PermissionPatchRequest;
use Motor\Admin\Http\Requests\Api\PermissionPostRequest;
use Motor\Admin\Http\Resources\V2\PermissionCollection;
use Motor\Admin\Http\Resources\V2\PermissionResource;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\PermissionGroup;
use Motor\Admin\Services\PermissionService;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * @tags Permissions
 */
class PermissionsController extends ApiController
{
    protected string $model = Permission::class;

    protected string $modelResource = 'permission';

    /**
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<PermissionResource>>
     */
    public function index(PermissionGetRequest $request): PermissionCollection
    {
        $paginator = PermissionService::collection()
            ->getPaginator();

        return (new PermissionCollection($paginator))
            ->additional(['meta' => ['message' => 'Permissions retrieved']]);
    }

    public function show(Permission $permission): PermissionResource
    {
        $result = PermissionService::show($permission)
            ->getResult();

        return (new PermissionResource($result))
            ->additional(['meta' => ['message' => 'Permission retrieved']]);
    }

    public function store(PermissionPostRequest $request): JsonResponse
    {
        $result = PermissionService::create($request)
            ->getResult();

        return (new PermissionResource($result))
            ->additional(['meta' => ['message' => 'Permission created']])
            ->response()
            ->setStatusCode(201);
    }

    public function update(PermissionPatchRequest $request, Permission $permission): PermissionResource
    {
        $result = PermissionService::update($permission, $request)
            ->getResult();

        return (new PermissionResource($result))
            ->additional(['meta' => ['message' => 'Permission updated']]);
    }

    public function destroy(Permission $permission): Response
    {
        PermissionService::delete($permission);

        return $this->noContentResponse();
    }

    public function items(PermissionListGetRequest $request, PermissionGroup $permissionGroup): PermissionCollection
    {
        $paginator = Permission::where('permission_group_id', $permissionGroup->id)
            ->paginate(25);

        return (new PermissionCollection($paginator))
            ->additional(['meta' => ['message' => 'Permissions retrieved']]);
    }
}
