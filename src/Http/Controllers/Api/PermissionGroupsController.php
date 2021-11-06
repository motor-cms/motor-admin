<?php

namespace Motor\Admin\Http\Controllers\Api;

use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\PermissionGroupRequest;
use Motor\Admin\Http\Resources\PermissionGroupCollection;
use Motor\Admin\Http\Resources\PermissionGroupResource;
use Motor\Admin\Models\Permission;
use Motor\Admin\Models\PermissionGroup;
use Motor\Admin\Services\PermissionGroupService;

/**
 * Class PermissionGroupsController
 *
 * @package Motor\Admin\Http\Controllers\Api
 */
class  PermissionGroupsController extends ApiController
{
    protected string $model = 'Motor\Admin\Models\PermissionGroup';

    protected string $modelResource = 'permission_group';

    /**
     * @OA\Get (
     *   tags={"PermissionGroupsController"},
     *   path="/api/permission_groups",
     *   summary="Get permission group collection",
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
     *         @OA\Items(ref="#/components/schemas/PermissionGroupResource")
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
     * @return \Motor\Admin\Http\Resources\PermissionGroupCollection
     */
    public function index()
    {
        $paginator = PermissionGroupService::collection()
                                           ->getPaginator();

        return (new PermissionGroupCollection($paginator))->additional(['message' => 'Permission collection read']);
    }

    /**
     * @OA\Post (
     *   tags={"PermissionGroupsController"},
     *   path="/api/permission_groups",
     *   summary="Create new permission group",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/PermissionGroupRequest")
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
     *         ref="#/components/schemas/PermissionGroupResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Permission group created"
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
     * @param \Motor\Admin\Http\Requests\Api\PermissionGroupRequest $request
     * @return \Illuminate\Http\JsonResponse|object
     */
    public function store(PermissionGroupRequest $request)
    {
        $result = PermissionGroupService::create($request)
                                        ->getResult();

        return (new PermissionGroupResource($result))->additional(['message' => 'Permission group created'])
                                                     ->response()
                                                     ->setStatusCode(201);
    }

    /**
     * @OA\Get (
     *   tags={"PermissionGroupsController"},
     *   path="/api/permission_groups/{permission_group}",
     *   summary="Get single permission group",
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
     *     name="permission_group",
     *     parameter="permission_group",
     *     description="Permission group id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/PermissionGroupResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Permission group read"
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
     * @param \Motor\Admin\Models\PermissionGroup $record
     * @return \Motor\Admin\Http\Resources\PermissionGroupResource
     */
    public function show(PermissionGroup $record)
    {
        $result = PermissionGroupService::show($record)
                                        ->getResult();

        return (new PermissionGroupResource($result->load('permissions')))->additional(['message' => 'Permission group read']);
    }

    /**
     * @OA\Put (
     *   tags={"PermissionGroupsController"},
     *   path="/api/permission_groups/{permission_group}",
     *   summary="Update an existing permission group",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/PermissionGroupRequest")
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
     *     name="permission_group",
     *     parameter="permission_group",
     *     description="Permission group id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/PermissionGroupResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Permission group updated"
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
     * @param \Motor\Admin\Http\Requests\Api\PermissionGroupRequest $request
     * @param \Motor\Admin\Models\Permission $record
     * @return \Motor\Admin\Http\Resources\PermissionGroupResource
     */
    public function update(PermissionGroupRequest $request, Permission $record)
    {
        $result = PermissionGroupService::update($record, $request)
                                        ->getResult();

        return (new PermissionGroupResource($result))->additional(['message' => 'Permission group updated']);
    }

    /**
     * @OA\Delete (
     *   tags={"PermissionGroupsController"},
     *   path="/api/permission_groups/{permission_group}",
     *   summary="Delete a permission group",
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
     *     name="permission_group",
     *     parameter="permission_group",
     *     description="Permission group id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Permission group deleted"
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
     *         example="Problem deleting permission group"
     *       )
     *     )
     *   )
     * )
     *
     * Remove the specified resource from storage.
     *
     * @param \Motor\Admin\Models\PermissionGroup $record
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(PermissionGroup $record)
    {
        $result = PermissionGroupService::delete($record)
                                        ->getResult();

        if ($result) {
            return response()->json(['message' => 'Permission group deleted']);
        }

        return response()->json(['message' => 'Problem deleting permission group'], 400);
    }
}
