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
        // tk ana dil, session yoksa tk kullan
        $locale = session('locale', config('app.locale', 'tk'));

        // sadece izin verilen dillere set et
        if (! in_array($locale, ['tk', 'en', 'ru', 'tr'], true)) {
            $locale = 'tk';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
