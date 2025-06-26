<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\RoleGetRequest;
use Motor\Admin\Http\Requests\Api\RolePatchRequest;
use Motor\Admin\Http\Requests\Api\RolePostRequest;
use Motor\Admin\Http\Resources\RoleCollection;
use Motor\Admin\Http\Resources\RoleResource;
use Motor\Admin\Models\Role;
use Motor\Admin\Services\RoleService;

/**
 * Class RolesController
 */
class RolesController extends ApiController
{
    protected string $model = Role::class;

    protected string $modelResource = 'role';

    /**
     * List/search all records
     *
     * This will return a paginated response. Some limited search operations are also possible.
     *
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<RoleResource>>
     */
    public function index(RoleGetRequest $request): RoleCollection
    {
        $paginator = RoleService::collection()
            ->getPaginator();

        return new RoleCollection($paginator)->additional(['message' => 'Role collection read']);
    }

    /**
     * Create record
     */
    public function store(RolePostRequest $request): JsonResponse
    {
        $result = RoleService::create($request)
            ->getResult();

        return new RoleResource($result)->additional(['message' => 'Role created'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a single record
     */
    public function show(Role $role): RoleResource
    {
        $result = RoleService::show($role)
            ->getResult();

        return new RoleResource($result)->additional(['message' => 'Role read']);
    }

    /**
     * Update record
     */
    public function update(RolePatchRequest $request, Role $role): RoleResource
    {
        $result = RoleService::update($role, $request)
            ->getResult();

        return new RoleResource($result)->additional(['message' => 'Role updated']);
    }

    /**
     * Delete record
     */
    public function destroy(Role $role): JsonResponse
    {
        $result = RoleService::delete($role)
            ->getResult();

        if ($result) {
            return response()->json(['message' => 'Role deleted']);
        }

        return response()->json(['message' => 'Problem deleting role'], 400);
    }
}
