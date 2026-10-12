<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\LoginException;
use App\Services\Auth\LoginService;
use App\Support\Access;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request, LoginService $logins)
    {
        $data = $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:100',
        ], [
            'login.required' => __('validations/validations.auth.login_required'),
            'password.required' => __('validations/validations.auth.password_required'),
        ]);

        $login = trim($data['login']);

        try {
            $user = $logins->attempt($login, $data['password']);
        } catch (LoginException $e) {
            if ($e->reason === 'inactive_account') {
                return ApiResponse::error($e->getMessage(), 403, 'account_inactive');
            }

            // One answer for an unknown login and a wrong password, so the API
            // cannot be used to find out which accounts exist.
            return ApiResponse::error(__('api.invalid_credentials'), 422, 'invalid_credentials');
        }

        $user->tokens()->where('created_at', '<', now()->subMinutes(config('sanctum.expiration')))->delete();

        // A token of its own per device; the panel's session is not touched.
        $token = $user->createToken($data['device_name'] ?? 'mobile');

        $logins->recordSuccess($user, $login);

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'expires_at' => $this->expiresAt($token->accessToken->created_at),
            ...$this->session($user),
        ]);
    }

    public function me(Request $request)
    {
        return ApiResponse::success($this->session($request->user()));
    }

    public function logout(Request $request, LoginService $logins)
    {
        $user = $request->user();

        $logins->recordLogout($user);

        // Only this device; the user's other devices stay signed in.
        $user->currentAccessToken()->delete();

        return ApiResponse::success(null, __('api.logged_out'));
    }

    private function session(User $user): array
    {
        return [
            'user' => (new UserResource($user))->resolve(),
            'abilities' => Access::for($user),
        ];
    }

    private function expiresAt($createdAt): ?string
    {
        $minutes = config('sanctum.expiration');

        return $minutes ? $createdAt->copy()->addMinutes($minutes)->toIso8601String() : null;
    }
}
