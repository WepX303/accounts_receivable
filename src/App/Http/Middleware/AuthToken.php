<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware to authenticate users based on auth_token cookie.
 */
class AuthToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('auth_token');

        if (! $token) {
            return redirect()->route('login');
        }

        $user = User::where('token', $token)
            ->where('token_expires_at', '>', now())
            ->first();

        if (! $user) {
            // Token is invalid or has expired
            return redirect()->route('login')
                ->withCookie(cookie()->forget('auth_token'));
        }

        if (! $user->status) {
            // User is inactive, logout and delete cookies
            $cookie = cookie()->forget('auth_token');
            Auth::logout();

            return redirect()->route('login')
                ->withCookie($cookie)
                ->withErrors([
                    'login' => 'Hesabınız pasif hale getirildi, lütfen yönetici ile iletişime geçin.',
                ]);
        }

        // Provide a fresh user to Laravel auth
        Auth::login($user->fresh());
        view()->share('user', Auth::user());

        return $next($request);
    }
}
