<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * A deactivated account loses every token it holds, the same way the panel
     * drops its session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->status) {
            $user->tokens()->delete();

            return ApiResponse::error(__('validations/validations.auth.account_inactive'), 403, 'account_inactive');
        }

        return $next($request);
    }
}
