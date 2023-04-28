<?php

namespace Motor\Admin\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use Motor\Admin\Http\Controllers\ApiController;
use Motor\Admin\Http\Requests\Api\ProfileEditRequest;
use Motor\Admin\Http\Resources\UserResource;
use Motor\Admin\Services\ProfileEditService;

/**
 * Class ProfileEditController
 */
class ProfileEditController extends ApiController
{
    /**
     * @OA\Put (
     *   tags={"UserProfileController"},
     *   path="/api/profile",
     *   summary="Update an existing user",
     *
     *   @OA\MediaType(
     *     mediaType="application/json",
     *   ),
     *
     *   @OA\RequestBody(
     *
     *     @OA\JsonContent(ref="#/components/schemas/ProfileEditRequest"),
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
     *         ref="#/components/schemas/UserResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="User profile updated"
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
     */
    public function update(ProfileEditRequest $request): UserResource
    {
        $result = ProfileEditService::update(Auth::user(), $request)
            ->getResult();

        return (new UserResource($result))->additional(['message' => 'Profile updated']);
    }

    /**
     * @OA\Get (
     *   tags={"UserProfileController"},
     *   path="/api/profile",
     *   summary="Get user profile",
     *
     *   @OA\MediaType(
     *     mediaType="application/json",
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
     *         ref="#/components/schemas/UserResource"
     *       ),
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         example="User profile read"
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
     * Get current users profile
     */
    public function me(): UserResource
    {
        $result = ProfileEditService::show(Auth::user())
            ->getResult();

        return (new UserResource($result))->additional(['message' => 'Profile read']);
    }
}
