<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Profile\ProfileException;
use App\Services\Profile\ProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function update(Request $request, ProfileService $profiles)
    {
        $user = $request->user();

        $validated = $request->validate(ProfileService::rules($user), ProfileService::messages());

        try {
            $result = $profiles->update($user, $validated);
        } catch (ProfileException $e) {
            return ApiResponse::error($e->getMessage(), 422, 'validation_failed', [$e->field => [$e->getMessage()]]);
        }

        if ($result !== ProfileService::UNCHANGED && ! empty($validated['new_password'])) {
            // Whoever else holds a token for this account is signed out; this device stays.
            $user->tokens()->whereKeyNot($user->currentAccessToken()->getKey())->delete();
        }

        $message = match ($result) {
            ProfileService::UNCHANGED => __('validations/validations.profile.no_changes'),
            ProfileService::PASSWORD_UPDATED => __('validations/validations.profile.password_updated'),
            default => __('validations/validations.profile.profile_updated'),
        };

        return ApiResponse::success(['user' => (new UserResource($user))->resolve()], $message);
    }
}
