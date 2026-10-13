<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LimitApiBodySize
{
    /**
     * Nothing the app sends comes near this: a form, or a list of customer ids.
     */
    private const MAX_BYTES = 512 * 1024;

    /**
     * Turns an oversized request away before it is parsed or validated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $length = (int) $request->headers->get('Content-Length', 0);

        if ($length > self::MAX_BYTES || strlen((string) $request->getContent()) > self::MAX_BYTES) {
            return ApiResponse::error(__('api.payload_too_large'), 413, 'payload_too_large');
        }

        return $next($request);
    }
}
