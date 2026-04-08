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
     * Update the user profile
     */
    public function update(ProfileEditRequest $request): UserResource
    {
        $result = ProfileEditService::update(Auth::user(), $request)
            ->getResult();

        return new UserResource($result)->additional(['message' => 'Profile updated']);
    }

    /**
     * Set show_onboarding flag so the tour restarts on next dashboard visit
     */
    public function resetOnboarding(): UserResource
    {
        $user = Auth::user();
        $user->update(['show_onboarding' => true]);

        return new UserResource($user)->additional(['message' => 'Onboarding reset']);
    }

    /**
     * Clear show_onboarding flag after the tour has completed
     */
    public function completeOnboarding(): UserResource
    {
        $user = Auth::user();
        $user->update(['show_onboarding' => false]);

        return new UserResource($user)->additional(['message' => 'Onboarding completed']);
    }

    /**
     * Get current users profile
     */
    public function me(): UserResource
    {
        $result = ProfileEditService::show(Auth::user())
            ->getResult();

        return new UserResource($result)->additional(['message' => 'Profile read']);
    }
}
