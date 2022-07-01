<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\UserPatchRequest;
use Motor\Admin\Http\Requests\Api\UserPostRequest;
use Motor\Admin\Http\Resources\UserCollection;
use Motor\Admin\Http\Resources\UserResource;
use Motor\Admin\Models\User;
use Motor\Admin\Services\UserService;

/**
 * Class UsersController
 */
class UsersController extends ApiController
{
    protected string $model = \Motor\Admin\Models\User::class;

    protected string $modelResource = 'user';

    /**
     * @OA\Get (
     *   tags={"UsersController"},
     *   path="/api/users",
     *   summary="Get user collection",
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/UserResource")
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
     * @return \Motor\Admin\Http\Resources\UserCollection
     */
    public function index(): UserCollection
    {
        $paginator = UserService::collection()
                                ->getPaginator();

        return (new UserCollection($paginator))->additional(['message' => 'User collection read']);
    }

    /**
     * @OA\Post (
     *   tags={"UsersController"},
     *   path="/api/users",
     *   summary="Create new user",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/UserPostRequest")
     *   ),
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/UserResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="User created"
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
     * @param  \Motor\Admin\Http\Requests\Api\UserPostRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(UserPostRequest $request): JsonResponse
    {
        $result = UserService::create($request)
                             ->getResult();

        return (new UserResource($result))->additional(['message' => 'User created'])
                                          ->response()
                                          ->setStatusCode(201);
    }

    /**
     * @OA\Get (
     *   tags={"UsersController"},
     *   path="/api/users/{user}",
     *   summary="Get single user",
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="user",
     *     parameter="user",
     *     description="User id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/UserResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="User read"
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
     * @param  \Motor\Admin\Models\User  $record
     * @return \Motor\Admin\Http\Resources\UserResource
     */
    public function show(User $record): UserResource
    {
        $result = UserService::show($record)
                             ->getResult();

        return (new UserResource($result))->additional(['message' => 'User read']);
    }

    /**
     * @OA\Put (
     *   tags={"UsersController"},
     *   path="/api/users/{user}",
     *   summary="Update an existing user",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/UserPatchRequest")
     *   ),
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="user",
     *     parameter="user",
     *     description="User id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         ref="#/components/schemas/UserResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="User updated"
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
     * @param  \Motor\Admin\Http\Requests\Api\UserPatchRequest  $request
     * @param  \Motor\Admin\Models\User  $record
     * @return \Motor\Admin\Http\Resources\UserResource
     */
    public function update(UserPatchRequest $request, User $record): UserResource
    {
        $result = UserService::update($record, $request)
                             ->getResult();

        return (new UserResource($result))->additional(['message' => 'User updated']);
    }

    /**
     * @OA\Delete (
     *   tags={"UsersController"},
     *   path="/api/users/{user}",
     *   summary="Delete a user",
     *   security={ {"sanctum": {} }},
     *   @OA\Parameter(
     *     @OA\Schema(type="string"),
     *     in="header",
     *     name="Accept",
     *     example="application/json"
     *   ),
     *   @OA\Parameter(
     *     @OA\Schema(type="integer"),
     *     in="path",
     *     name="user",
     *     parameter="user",
     *     description="User id"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="User deleted"
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
     *         example="Problem deleting user"
     *       )
     *     )
     *   )
     * )
     *
     * Remove the specified resource from storage.
     *
     * @param  \Motor\Admin\Models\User  $record
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $record): JsonResponse
    {
        $result = UserService::delete($record)
                             ->getResult();

        if ($result) {
            return response()->json(['message' => 'User deleted']);
        }

        return response()->json(['message' => 'Problem deleting user'], 400);
    }
}
