<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\PermissionGroupRequest;
use Motor\Admin\Http\Resources\PermissionGroupCollection;
use Motor\Admin\Http\Resources\PermissionGroupResource;
use Motor\Admin\Models\Permission;
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
     * @OA\Get (
     *   tags={"PermissionGroupsController"},
     *   path="/api/permission_groups",
     *   summary="Get permission group collection",
     *   security={ {"sanctum": {} }},
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *
     *         @OA\Items(ref="#/components/schemas/PermissionGroupResource")
     *       ),
     *
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
     *
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   )
     * )
     *
     * Display a listing of the resource.
     */
    public function index(): PermissionGroupCollection
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
     *
     *   @OA\RequestBody(
     *
     *     @OA\JsonContent(ref="#/components/schemas/PermissionGroupRequest")
     *   ),
     *   security={ {"sanctum": {} }},
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *
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
     *
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Store a newly created resource in storage.
     */
    public function store(PermissionGroupRequest $request): JsonResponse
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
     *   security={ {"sanctum": {} }},
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="permission_group",
     *     parameter="permission_group",
     *     description="Permission group id"
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *
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
     *
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Display the specified resource.
     */
    public function show(PermissionGroup $permissionGroup): PermissionGroupResource
    {
        $result = PermissionGroupService::show($permissionGroup)
            ->getResult();

        return (new PermissionGroupResource($result->load('permissions')))->additional(['message' => 'Permission group read']);
    }

    /**
     * @OA\Put (
     *   tags={"PermissionGroupsController"},
     *   path="/api/permission_groups/{permission_group}",
     *   summary="Update an existing permission group",
     *
     *   @OA\RequestBody(
     *
     *     @OA\JsonContent(ref="#/components/schemas/PermissionGroupRequest")
     *   ),
     *   security={ {"sanctum": {} }},
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="permission_group",
     *     parameter="permission_group",
     *     description="Permission group id"
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *
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
     *
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   )
     * )
     *
     * Update the specified resource in storage.
     *
     * @param  \Motor\Admin\Models\Permission  $permissionGroup
     */
    public function update(PermissionGroupRequest $request, PermissionGroup $permissionGroup): PermissionGroupResource
    {
        $result = PermissionGroupService::update($permissionGroup, $request)
            ->getResult();

        $permissions = $result->permissions()->get();
        foreach ($permissions as $permission) {
            // We want to replace the prefix of the permission, so we need to explode the name
            $permissionNameExploded = explode('.', $permission->name);
            //We removed the prefix before the dot, so we need to remove it from the array
            array_shift($permissionNameExploded);
            //We need to reassemble the name with the new prefix, so we implode the array
            $newPermissionName = implode('.', $permissionNameExploded);
            //We set the new name of the permission with the new prefix
            $permission->name = $result->name.'.'.$newPermissionName;
            //We save the permission
            $permission->save();
        }

        return (new PermissionGroupResource($result))->additional(['message' => 'Permission group updated']);
    }

    /**
     * @OA\Delete (
     *   tags={"PermissionGroupsController"},
     *   path="/api/permission_groups/{permission_group}",
     *   summary="Delete a permission group",
     *   security={ {"sanctum": {} }},
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *
     *   @OA\Parameter(
     *
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="permission_group",
     *     parameter="permission_group",
     *     description="Permission group id"
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *
     *     @OA\JsonContent(
     *
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Permission group deleted"
     *       )
     *     )
     *   ),
     *
     *   @OA\Response(
     *     response="403",
     *     description="Access denied",
     *
     *     @OA\JsonContent(ref="#/components/schemas/AccessDenied"),
     *   ),
     *
     *   @OA\Response(
     *     response="404",
     *     description="Not found",
     *
     *     @OA\JsonContent(ref="#/components/schemas/NotFound"),
     *   ),
     *
     *   @OA\Response(
     *     response="400",
     *     description="Bad request",
     *
     *     @OA\JsonContent(
     *
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
