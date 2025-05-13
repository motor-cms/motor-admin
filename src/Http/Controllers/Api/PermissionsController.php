<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\PermissionGetRequest;
use Motor\Admin\Http\Requests\Api\PermissionListGetRequest;
use Motor\Admin\Http\Requests\Api\PermissionPatchRequest;
use Motor\Admin\Http\Requests\Api\PermissionPostRequest;
use Motor\Admin\Http\Resources\PermissionCollection;
use Motor\Admin\Http\Resources\PermissionResource;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\PermissionGroup;
use Motor\Admin\Services\PermissionService;

/**
 * Class PermissionsController
 */
class PermissionsController extends ApiController
{
    protected string $model = Permission::class;

    protected string $modelResource = 'permission';

    /**
     * List/search all records
     *
     * This will return a paginated response. Some limited search operations are also possible.
     *
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<PermissionResource>>
     */
    public function index(PermissionGetRequest $request): PermissionCollection
    {
        $paginator = PermissionService::collection()
                                      ->getPaginator();

        return new PermissionCollection($paginator)->additional(['message' => 'Permission collection read']);
    }

    /**
     * Create record
     */
    public function store(PermissionPostRequest $request): JsonResponse
    {
        $result = PermissionService::create($request)
                                   ->getResult();

        return new PermissionResource($result)->additional(['message' => 'Permission created'])
                                              ->response()
                                              ->setStatusCode(201);
    }

    /**
     * Get a single record
     */
    public function show(Permission $permission): PermissionResource
    {
        $result = PermissionService::show($permission)
                                   ->getResult();

        return new PermissionResource($result)->additional(['message' => 'Permission read']);
    }

    /**
     * Update record
     */
    public function update(PermissionPatchRequest $request, Permission $permission): PermissionResource
    {
        $result = PermissionService::update($permission, $request)
                                   ->getResult();

        return new PermissionResource($result)->additional(['message' => 'Permission updated']);
    }

    /**
     * Delete record
     */
    public function destroy(Permission $permission): JsonResponse
    {
        $result = PermissionService::delete($permission)
                                   ->getResult();

        if ($result) {
            return response()->json(['message' => 'Permission deleted']);
        }

        return response()->json(['message' => 'Problem deleting permission'], 400);
    }

    /**
     * Get permissions for a permission group
     *
     * This will return a paginated response with 25 records per page
     *
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<PermissionResource>>
     */
    public function items(PermissionListGetRequest $request, PermissionGroup $permissionGroup): PermissionCollection
    {
        $paginator = Permission::where('permission_group_id', $permissionGroup->id)
                               ->paginate(25);

        return new PermissionCollection($paginator)->additional(['message' => 'Permission collection read']);
    }
}
