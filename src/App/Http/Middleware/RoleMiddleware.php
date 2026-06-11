<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        // If there is an enum cast, $user->role becomes an enum object
        $userRole = is_object($user->role) ? $user->role->value : $user->role;

        if (! in_array($userRole, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
