<?php

namespace Motor\Admin\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Motor\Admin\Http\Requests\Api\UserGetRequest;
use Motor\Admin\Http\Requests\Api\UserPatchRequest;
use Motor\Admin\Http\Requests\Api\UserPostRequest;
use Motor\Admin\Http\Resources\V2\UserCollection;
use Motor\Admin\Http\Resources\V2\UserResource;
use Motor\Admin\Models\User;
use Motor\Admin\Services\UserService;
use Motor\Core\Http\Controllers\Api\V2\ApiController;

/**
 * V2 Users Controller with standardized responses.
 *
 * @tags Users
 */
class UsersController extends ApiController
{
    protected string $model = User::class;

    protected string $modelResource = 'user';

    /**
     * List all users
     *
     * Returns a paginated list of users with standardized V2 envelope.
     *
     * @response UserCollection
     */
    public function index(UserGetRequest $request): UserCollection
    {
        $paginator = UserService::collection()
            ->getPaginator();

        return (new UserCollection($paginator))
            ->additional(['meta' => ['message' => 'Users retrieved']]);
    }

    /**
     * Get a single user
     *
     * @response UserResource
     */
    public function show(User $user): UserResource
    {
        $result = UserService::show($user)
            ->getResult();

        return (new UserResource($result))
            ->additional(['meta' => ['message' => 'User retrieved']]);
    }

    /**
     * Create a new user
     *
     * @response 201 UserResource
     */
    public function store(UserPostRequest $request): JsonResponse
    {
        $result = UserService::create($request)
            ->getResult();

        return (new UserResource($result))
            ->additional(['meta' => ['message' => 'User created']])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a user
     *
     * @response UserResource
     */
    public function update(UserPatchRequest $request, User $user): UserResource
    {
        $result = UserService::update($user, $request)
            ->getResult();

        return (new UserResource($result))
            ->additional(['meta' => ['message' => 'User updated']]);
    }

    /**
     * Delete a user
     *
     * @response 204
     */
    public function destroy(User $user): Response
    {
        UserService::delete($user);

        return $this->noContentResponse();
    }
}
