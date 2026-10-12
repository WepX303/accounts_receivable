<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    /**
     * The API has no session to remember a language in, so the client names it
     * on every request with Accept-Language.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Falls back to the first entry when the header is missing or names another language.
        App::setLocale($request->getPreferredLanguage(['tk', 'ru', 'en', 'tr']));

        return $next($request);
    }
}
