<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\RolePatchRequest;
use Motor\Admin\Http\Requests\Api\RolePostRequest;
use Motor\Admin\Http\Resources\RoleCollection;
use Motor\Admin\Http\Resources\RoleResource;
use Motor\Admin\Models\Role;
use Motor\Admin\Services\RoleService;

/**
 * Class RolesController
 *
 * @package Motor\Admin\Http\Controllers\Api
 */
class RolesController extends ApiController
{
    protected string $model = 'Motor\Admin\Models\Role';

    protected string $modelResource = 'role';

    /**
     * @OA\Get (
     *   tags={"RolesController"},
     *   path="/api/roles",
     *   summary="Get role collection",
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="query",
     *     allowReserved=true,
     *     name="api_token",
     *     parameter="api_token",
     *     description="Personal api_token of the user"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/RoleResource")
     *       ),
     *       @OA\Property(
     *         property="meta",
     *         ref="#/components/schemas/PaginationMeta"
     *       ),
     *       @OA\Property(
     *         property="links",
     *         ref="#/components/schemas/PaginationLinks"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Collection read"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   )
     * )
     *
     * Display a listing of the resource.
     *
     * @return \Motor\Admin\Http\Resources\RoleCollection
     */
    public function index(): RoleCollection
    {
        $paginator = RoleService::collection()
                                ->getPaginator();

        return (new RoleCollection($paginator))->additional(['message' => 'Role collection read']);
    }

    /**
     * @OA\Post (
     *   tags={"RolesController"},
     *   path="/api/roles",
     *   summary="Create new role",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/RolePostRequest")
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="query",
     *     allowReserved=true,
     *     name="api_token",
     *     parameter="api_token",
     *     description="Personal api_token of the user"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/RoleResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Role created"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Store a newly created resource in storage.
     *
     * @param \Motor\Admin\Http\Requests\Api\RolePostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(RolePostRequest $request): JsonResponse
    {
        $result = RoleService::create($request)
                             ->getResult();

        return (new RoleResource($result))->additional(['message' => 'Role created'])
                                          ->response()
                                          ->setStatusCode(201);
    }

    /**
     * @OA\Get (
     *   tags={"RolesController"},
     *   path="/api/roles/{role}",
     *   summary="Get single role",
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="query",
     *     allowReserved=true,
     *     name="api_token",
     *     parameter="api_token",
     *     description="Personal api_token of the user"
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="role",
     *     parameter="role",
     *     description="Role id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/RoleResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Role read"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Display the specified resource.
     *
     * @param \Motor\Admin\Models\Role $record
     * @return \Motor\Admin\Http\Resources\RoleResource
     */
    public function show(Role $record): RoleResource
    {
        $result = RoleService::show($record)
                             ->getResult();

        return (new RoleResource($result))->additional(['message' => 'Role read']);
    }

    /**
     * @OA\Put (
     *   tags={"RolesController"},
     *   path="/api/roles/{role}",
     *   summary="Update an existing role",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/RolePatchRequest")
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="query",
     *     allowReserved=true,
     *     name="api_token",
     *     parameter="api_token",
     *     description="Personal api_token of the user"
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="role",
     *     parameter="role",
     *     description="Role id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/RoleResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Role updated"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Update the specified resource in storage.
     *
     * @param \Motor\Admin\Http\Requests\Api\RolePatchRequest $request
     * @param \Motor\Admin\Models\Role $record
     * @return \Motor\Admin\Http\Resources\RoleResource
     */
    public function update(RolePatchRequest $request, Role $record): RoleResource
    {
        $result = RoleService::update($record, $request)
                             ->getResult();

        return (new RoleResource($result))->additional(['message' => 'Role updated']);
    }

    /**
     * @OA\Delete (
     *   tags={"RolesController"},
     *   path="/api/roles/{role}",
     *   summary="Delete a role",
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="query",
     *     allowReserved=true,
     *     name="api_token",
     *     parameter="api_token",
     *     description="Personal api_token of the user"
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="role",
     *     parameter="role",
     *     description="Role id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Role deleted"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   ),
     *   @OA\Response(
     *     response="400",
     *     description="Bad request",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Problem deleting role"
     *       )
     *     )
     *   )
     * )
     *
     * Remove the specified resource from storage.
     *
     * @param \Motor\Admin\Models\Role $record
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Role $record): JsonResponse
    {
        $result = RoleService::delete($record)
                             ->getResult();

        if ($result) {
            return response()->json(['message' => 'Role deleted']);
        }

        return response()->json(['message' => 'Problem deleting role'], 400);
    }
}
