<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use App\Support\ClientInfo;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleApiByAddress
{
    /**
     * A whole office may share one address, so this is generous to people
     * and still far below what a flood sends.
     */
    private const PER_MINUTE = 600;

    /**
     * The first thing an API request meets, before its token is looked up:
     * a flood from one address is turned away without costing a query. The
     * limits by account and by kind of request come after the sign-in check.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'api-address:' . ClientInfo::ip($request);

        if (RateLimiter::tooManyAttempts($key, self::PER_MINUTE)) {
            return ApiResponse::error(__('api.too_many_requests'), 429, 'too_many_requests')
                ->header('Retry-After', RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
