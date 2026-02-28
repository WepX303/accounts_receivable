<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required',
            'password' => 'required',
        ], [
            'login.required' => __('validations/validations.auth.login_required'),
            'password.required' => __('validations/validations.auth.password_required'),
        ]);

        $login = $request->login;

        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
        $isPhone = preg_match('/^[0-9\+\-\s]{6,20}$/', $login);

        $user = User::where('email', $login)
            ->orWhere('phonenumber', $login)
            ->first();

        if (! $user) {
            $errorMessage = $isEmail
                ? __('validations/validations.auth.email_not_found')
                : ($isPhone ? __('validations/validations.auth.phone_not_found') : __('validations/validations.auth.invalid_login'));

            return back()->withErrors(['login' => $errorMessage])->withInput();
        }

        // Status control
        if (! $user->status) {
            return back()->withErrors(['login' => __('validations/validations.auth.account_inactive')])->withInput();
        }

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => __('validations/validations.auth.wrong_password')])->withInput();
        }

        // Create new token
        $token = bin2hex(random_bytes(32));
        $user->token = $token;
        $user->token_expires_at = now()->addDay();
        $user->save();

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
        );
    }

    // Logout
    public function logout(Request $request)
    {
        $token = $request->cookie('auth_token');

        if ($token) {
            $user = User::where('token', $token)->first();
            if ($user) {
                $user->token = null;
                $user->token_expires_at = null;
                $user->save();
            }
        }

        // Delete the cookie for all paths and domains and add a cache control header.
        $cookie = cookie()->forget('auth_token');

        return redirect()->route('login')
            ->withCookie($cookie)
            ->withHeaders([
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    }
}
