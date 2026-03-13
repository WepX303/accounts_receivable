<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\ApiResponse;

class ApiAuthToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return ApiResponse::error(
                'Authorization token missing.',
                401,
                'AUTH_TOKEN_MISSING'
            );
        }

        $user = User::query()
            ->where('token', $token)
            ->where('token_expires_at', '>', now())
            ->first();

        if (!$user) {
            return ApiResponse::error(
                'Token is invalid or expired.',
                401,
                'AUTH_TOKEN_INVALID'
            );
        }

        if (!$user->status) {
            return ApiResponse::error(
                'Your account is inactive.',
                403,
                'ACCOUNT_INACTIVE'
            );
        }

        Auth::login($user->fresh());

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = (string) $request->header('Authorization');

        if (!$header) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}