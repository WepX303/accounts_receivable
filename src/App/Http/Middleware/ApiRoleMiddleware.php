<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\ApiResponse;

class ApiRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::error(
                'Unauthorized.',
                401,
                'AUTH_REQUIRED'
            );
        }

        $userRole = is_object($user->role)
            ? $user->role->value
            : $user->role;

        if (!in_array($userRole, $roles, true)) {
            return ApiResponse::error(
                'Forbidden.',
                403,
                'ROLE_FORBIDDEN'
            );
        }

        return $next($request);
    }
}