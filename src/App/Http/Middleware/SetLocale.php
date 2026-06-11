<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // If the primary language is not available in the session, use tk.
        $locale = session('locale', config('app.locale', 'tk'));

        // Set only to permitted languages
        if (! in_array($locale, ['tk', 'en', 'ru', 'tr'], true)) {
            $locale = 'tk';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
