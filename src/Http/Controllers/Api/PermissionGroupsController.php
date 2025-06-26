<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\PermissionGroupGetRequest;
use Motor\Admin\Http\Requests\Api\PermissionGroupPatchRequest;
use Motor\Admin\Http\Requests\Api\PermissionGroupPostRequest;
use Motor\Admin\Http\Resources\PermissionGroupCollection;
use Motor\Admin\Http\Resources\PermissionGroupResource;
use Motor\Admin\Models\PermissionGroup;
use Motor\Admin\Services\PermissionGroupService;

/**
 * Class PermissionGroupsController
 */
class PermissionGroupsController extends ApiController
{
    protected string $model = PermissionGroup::class;

    protected string $modelResource = 'permission_group';

    /**
     * List/search all records
     *
     * This will return a paginated response. Some limited search operations are also possible.
     *
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<PermissionGrouprResource>>
     */
    public function index(PermissionGroupGetRequest $request): PermissionGroupCollection
    {
        $paginator = PermissionGroupService::collection()
            ->getPaginator();

        return new PermissionGroupCollection($paginator)->additional(['message' => 'Permission collection read']);
    }

    /**
     * Create record
     */
    public function store(PermissionGroupPostRequest $request): JsonResponse
    {
        $result = PermissionGroupService::create($request)
            ->getResult();

        return new PermissionGroupResource($result)->additional(['message' => 'Permission group created'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a single record
     */
    public function show(PermissionGroup $permissionGroup): PermissionGroupResource
    {
        $result = PermissionGroupService::show($permissionGroup)
            ->getResult();

        return new PermissionGroupResource($result->load('permissions'))->additional(['message' => 'Permission group read']);
    }

    /**
     * Update record
     */
    public function update(PermissionGroupPatchRequest $request, PermissionGroup $permissionGroup): PermissionGroupResource
    {
        $result = PermissionGroupService::update($permissionGroup, $request)
            ->getResult();

        /* edit permission names
        * $permissions = $result->permissions()->get();
        * foreach ($permissions as $permission) {
        *     // We want to replace the prefix of the permission, so we need to explode the name
        *     $permissionNameExploded = explode('.', $permission->name);
        *     //We removed the prefix before the dot, so we need to remove it from the array
        *     array_shift($permissionNameExploded);
        *     //We need to reassemble the name with the new prefix, so we implode the array
        *     $newPermissionName = implode('.', $permissionNameExploded);
        *     //We set the new name of the permission with the new prefix
        *     $permission->name = $result->name.'.'.$newPermissionName;
        *     //We save the permission
        *     $permission->save();
        }*/

        return new PermissionGroupResource($result)->additional(['message' => 'Permission group updated']);
    }

    /**
     * Delete record
     */
    public function destroy(PermissionGroup $permissionGroup): JsonResponse
    {
        $result = PermissionGroupService::delete($permissionGroup)
            ->getResult();

        if ($result) {
            return response()->json(['message' => 'Permission group deleted']);
        }

        return response()->json(['message' => 'Problem deleting permission group'], 400);
    }
}
