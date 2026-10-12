<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\LoginException;
use App\Services\Auth\LoginService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // Login form
    public function showLoginForm(Request $request)
    {
        $token = $request->cookie('auth_token');

        if ($token) {
            $user = User::where('token', $token)
                ->where('token_expires_at', '>', now())
                ->first();

            if ($user) {
                return redirect()->route('dashboard');
            } else {
                // If there is an invalid token, delete the cookie
                return redirect()->route('login')->withCookie(cookie()->forget('auth_token'));
            }
        }

        return view('auth.login');
    }

    // Login
    public function login(Request $request, LoginService $logins)
    {
        $request->validate([
            'login' => 'required',
            'password' => 'required',
        ], [
            'login.required' => __('validations/validations.auth.login_required'),
            'password.required' => __('validations/validations.auth.password_required'),
        ]);

        $login = $request->login;

        try {
            $user = $logins->attempt($login, $request->password);
        } catch (LoginException $e) {
            return back()->withErrors([$e->field => $e->getMessage()])->withInput();
        }

        // Create new token
        $token = bin2hex(random_bytes(32));
        $user->token = $token;
        $user->token_expires_at = now()->addDay();
        $user->save();

        $logins->recordSuccess($user, $login);

        // Create a cookie and redirect
        return redirect()->route('dashboard')->withCookie(
            cookie(
                'auth_token',
                $token,
                60 * 24,    // 1 day
                '/',          // valid for all paths
                null,
                false,
                true    // httpOnly
            )

        // return redirect()->route('dashboard')->withCookie(
        //     cookie(
        //         'auth_token',
        //         $token,
        //         60 * 24,
        //         '/',
        //         null,
        //         true,
        //         true,
        //         false,
        //         'Lax'
        //     )
        );
    }

    // Logout
    public function logout(Request $request, LoginService $logins)
    {
        $token = $request->cookie('auth_token');

        if ($token) {
            $user = User::where('token', $token)->first();
            if ($user) {
                $logins->recordLogout($user);

                $user->token = null;
                $user->token_expires_at = null;
                $user->save();
            }
        }
        // Delete the cookie for all paths and domains and add a cache control header.
        // $cookie = cookie()->forget('auth_token');
        $cookie = cookie()->forget('auth_token', '/', null);

        return redirect()->route('login')
            ->withCookie($cookie)
            ->withHeaders([
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    }
}
