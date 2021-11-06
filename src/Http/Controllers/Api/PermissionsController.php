<?php

namespace Motor\Admin\Http\Controllers\Api;

use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\PermissionRequest;
use Motor\Admin\Http\Resources\PermissionCollection;
use Motor\Admin\Http\Resources\PermissionResource;
use Motor\Admin\Models\Permission;
use Motor\Admin\Services\PermissionService;

/**
 * Class PermissionsController
 *
 * @package Motor\Admin\Http\Controllers\Api
 */
class PermissionsController extends ApiController
{
    protected string $model = 'Motor\Admin\Models\Permission';

    protected string $modelResource = 'permission';

    /**
     * @OA\Get (
     *   tags={"PermissionsController"},
     *   path="/api/permissions",
     *   summary="Get permission collection",
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
     *         @OA\Items(ref="#/components/schemas/PermissionResource")
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
     * @return \Motor\Admin\Http\Resources\PermissionCollection
     */
    public function index()
    {
        $paginator = PermissionService::collection()
                                      ->getPaginator();

        return (new PermissionCollection($paginator))->additional(['message' => 'Permission collection read']);
    }

    /**
     * @OA\Post (
     *   tags={"PermissionsController"},
     *   path="/api/permissions",
     *   summary="Create new permission",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/PermissionRequest")
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
     *         ref="#/components/schemas/PermissionResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Permission created"
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
     * @param PermissionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PermissionRequest $request)
    {
        $result = PermissionService::create($request)
                                   ->getResult();

        return (new PermissionResource($result))->additional(['message' => 'Permission created'])
                                                ->response()
                                                ->setStatusCode(201);
    }

    /**
     * @OA\Get (
     *   tags={"PermissionsController"},
     *   path="/api/permissions/{permission}",
     *   summary="Get single permission",
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
     *     name="permission",
     *     parameter="permission",
     *     description="Permission id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/PermissionResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Permission read"
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
     * @param \Motor\Admin\Models\Permission $record
     * @return \Motor\Admin\Http\Resources\PermissionResource
     */
    public function show(Permission $record)
    {
        $result = PermissionService::show($record)
                                   ->getResult();

        return (new PermissionResource($result))->additional(['message' => 'Permission read']);
    }

    /**
     * @OA\Put (
     *   tags={"PermissionsController"},
     *   path="/api/permissions/{permission}",
     *   summary="Update an existing permission",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/PermissionRequest")
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
     *     name="permission",
     *     parameter="permission",
     *     description="Permission id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/PermissionResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Permission updated"
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
     * @param \Motor\Admin\Http\Requests\Api\PermissionRequest $request
     * @param \Motor\Admin\Models\Permission $record
     * @return \Motor\Admin\Http\Resources\PermissionResource
     */
    public function update(PermissionRequest $request, Permission $record)
    {
        $result = PermissionService::update($record, $request)
                                   ->getResult();

        return (new PermissionResource($result))->additional(['message' => 'Permission updated']);
    }

    /**
     * @OA\Delete (
     *   tags={"PermissionsController"},
     *   path="/api/permissions/{permission}",
     *   summary="Delete a permission",
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
     *     name="permission",
     *     parameter="permission",
     *     description="Permission id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="Permission deleted"
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
     *         example="Problem deleting permission"
     *       )
     *     )
     *   )
     * )
     *
     * Remove the specified resource from storage.
     *
     * @param \Motor\Admin\Models\Permission $record
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Permission $record)
    {
        $result = PermissionService::delete($record)
                                   ->getResult();

        if ($result) {
            return response()->json(['message' => 'Permission deleted']);
        }

        return response()->json(['message' => 'Problem deleting permission'], 400);
    }
}
