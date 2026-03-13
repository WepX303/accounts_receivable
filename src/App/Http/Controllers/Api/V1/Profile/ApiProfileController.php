<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiProfileController extends Controller
{
    public function show(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return ApiResponse::success(
            new UserResource($user),
            'Profile fetched successfully.'
        );
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $audit = app(AuditLogger::class);

        $old = $user->only([
            'firstname',
            'lastname',
            'email',
            'phonenumber',
        ]);

        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phonenumber' => ['required', 'string', 'max:50', 'unique:users,phonenumber,' . $user->id],
        ]);

        $changed = false;

        foreach (['firstname', 'lastname', 'email', 'phonenumber'] as $field) {
            $newValue = $validated[$field] ?? null;

            if ($newValue !== $user->$field) {
                $user->$field = $newValue;
                $changed = true;
            }
        }

        if (!$changed) {
            return ApiResponse::success(
                new UserResource($user),
                'No changes detected.'
            );
        }

        $user->save();

        $new = $user->only([
            'firstname',
            'lastname',
            'email',
            'phonenumber',
        ]);

        $audit->log(
            action: 'api_profile_updated',
            category: 'profile',
            subject: $user,
            oldValues: $old,
            newValues: $new,
            extra: [
                'password_changed' => false,
            ],
            message: 'Profile updated via API',
            isSuccess: true,
            severity: 'info',
            isSuspicious: false
        );

        return ApiResponse::success(
            new UserResource($user->fresh()),
            'Profile updated successfully.'
        );
    }

    public function changePassword(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $audit = app(AuditLogger::class);

        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'confirmed', 'min:6'],
        ]);

        if (!Hash::check($validated['old_password'], $user->password)) {
            return ApiResponse::error(
                'Old password is incorrect.',
                422,
                'OLD_PASSWORD_INCORRECT'
            );
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        $audit->log(
            action: 'api_profile_password_changed',
            category: 'profile',
            subject: $user,
            oldValues: null,
            newValues: null,
            extra: [
                'password_changed' => true,
            ],
            message: 'Password changed via API',
            isSuccess: true,
            severity: 'warning',
            isSuspicious: true
        );

        $audit->alert(
            alertType: 'api_password_changed',
            riskLevel: 'medium',
            message: 'User password changed via API',
            meta: [
                'user_id' => $user->id,
                'email' => $user->email,
            ]
        );

        return ApiResponse::success(
            null,
            'Password updated successfully.'
        );
    }
}