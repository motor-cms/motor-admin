<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\UserGetRequest;
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
    protected string $model = User::class;

    protected string $modelResource = 'user';

    /**
     * List/search all records
     *
     * This will return a paginated response. Some limited search operations are also possible.
     *
     * @response Illuminate\Http\Resources\Json\AnonymousResourceCollection<Illuminate\Pagination\LengthAwarePaginator<UserResource>>
     */
    public function index(UserGetRequest $request): UserCollection
    {
        $paginator = UserService::collection()
            ->getPaginator();

        return new UserCollection($paginator)->additional(['message' => 'User collection read']);
    }

    /**
     * Create record
     */
    public function store(UserPostRequest $request): JsonResponse
    {
        $result = UserService::create($request)
            ->getResult();

        return new UserResource($result)->additional(['message' => 'User created'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a single record
     */
    public function show(User $user): UserResource
    {
        $result = UserService::show($user)
            ->getResult();

        return new UserResource($result)->additional(['message' => 'User read']);
    }

    /**
     * Update record
     */
    public function update(UserPatchRequest $request, User $user): UserResource
    {
        $result = UserService::update($user, $request)
            ->getResult();

        return new UserResource($result)->additional(['message' => 'User updated']);
    }

    /**
     * Delete record
     */
    public function destroy(User $user): JsonResponse
    {
        $result = UserService::delete($user)
            ->getResult();

        if ($result) {
            return response()->json(['message' => 'User deleted']);
        }

        return response()->json(['message' => 'Problem deleting user'], 400);
    }
}
