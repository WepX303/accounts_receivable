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
            // Token geçersiz veya süresi dolmuş
            return redirect()->route('login')
                ->withCookie(cookie()->forget('auth_token'));
        }

        if (! $user->status) {
            // Kullanıcı pasif, logout ve cookie sil
            $cookie = cookie()->forget('auth_token');
            Auth::logout();

            return redirect()->route('login')
                ->withCookie($cookie)
                ->withErrors([
                    'login' => 'Hesabınız pasif hale getirildi, lütfen yönetici ile iletişime geçin.',
                ]);
        }

        // Laravel auth'a fresh user ver
        Auth::login($user->fresh());

        // Blade için paylaş
        view()->share('user', Auth::user());

        return $next($request);
    }
}
