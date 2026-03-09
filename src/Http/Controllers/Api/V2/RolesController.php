<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Motor\Admin\Http\Requests\Api\V2\RoleGetRequest;
use Motor\Admin\Http\Requests\Api\V2\RolePatchRequest;
use Motor\Admin\Http\Requests\Api\V2\RolePostRequest;
use Motor\Admin\Http\Resources\V2\RoleCollection;
use Motor\Admin\Http\Resources\V2\RoleResource;
use Motor\Admin\Models\Role;
use Motor\Admin\Services\RoleService;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * @tags Roles
 */
class RolesController extends ApiController
{
    protected string $model = Role::class;

    protected string $modelResource = 'role';

    /**
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<RoleResource>>
     */
    public function index(RoleGetRequest $request): RoleCollection
    {
        $paginator = RoleService::collection()
            ->getPaginator();

        return (new RoleCollection($paginator))
            ->additional(['meta' => ['message' => 'Roles retrieved']]);
    }

    public function show(Role $role): RoleResource
    {
        $result = RoleService::show($role)
            ->getResult();

        return (new RoleResource($result))
            ->additional(['meta' => ['message' => 'Role retrieved']]);
    }

    public function store(RolePostRequest $request): JsonResponse
    {
        $result = RoleService::create($request)
            ->getResult();

        return (new RoleResource($result))
            ->additional(['meta' => ['message' => 'Role created']])
            ->response()
            ->setStatusCode(201);
    }

    public function update(RolePatchRequest $request, Role $role): RoleResource
    {
        $result = RoleService::update($role, $request)
            ->getResult();

        return (new RoleResource($result))
            ->additional(['meta' => ['message' => 'Role updated']]);
    }

    public function destroy(Role $role): Response
    {
        RoleService::delete($role);

        return $this->noContentResponse();
    }
}
