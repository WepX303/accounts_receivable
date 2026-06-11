<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            return redirect()->route('login')
                ->withCookie(cookie()->forget('auth_token', '/', null));
        }

        if (! $user->status) {
            $user->token = null;
            $user->token_expires_at = null;
            $user->save();

            Auth::logout();

            return redirect()->route('login')
                ->withCookie(cookie()->forget('auth_token', '/', null))
                ->withErrors([
                    'login' => 'Your account has been deactivated. Please contact the administrator.',
                ]);
        }

        Auth::setUser($user);

        view()->share('user', $user);

        return $next($request);
    }
}