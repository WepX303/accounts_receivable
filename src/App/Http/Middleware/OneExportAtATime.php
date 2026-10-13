<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class OneExportAtATime
{
    /**
     * The longest a spreadsheet is given before its place is freed regardless.
     */
    private const SECONDS = 300;

    /**
     * A user gets one spreadsheet at a time. Writing one is the heaviest thing
     * the server does; a second tap, or a script, must not stack them up.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lock = Cache::lock('api-export:' . ($request->user()?->id ?: $request->ip()), self::SECONDS);

        if (! $lock->get()) {
            return ApiResponse::error(__('api.export_in_progress'), 429, 'export_in_progress')
                ->header('Retry-After', 15);
        }

        try {
            return $next($request);
        } finally {
            $lock->release();
        }
    }
}
