<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\LoginHistory;
use App\Services\AuditLogger;

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

        $audit = app(AuditLogger::class);


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

            $this->writeLoginHistory(null, $login, 'failed', 'user_not_found', 'User not found', true);

            $audit->log(
                action: 'login_failed',
                category: 'auth',
                subject: null,
                oldValues: null,
                newValues: null,
                extra: [
                    'login' => $login,
                    'reason' => 'user_not_found',
                ],
                message: 'Login failed: user not found',
                isSuccess: false,
                severity: 'warning',
                isSuspicious: true
            );

            $audit->alert(
                alertType: 'login_failed_user_not_found',
                riskLevel: 'medium',
                message: 'Login attempt with unknown user',
                meta: [
                    'login' => $login,
                ]
            );

            return back()->withErrors(['login' => $errorMessage])->withInput();
        }

        if (! $user->status) {
            $this->writeLoginHistory($user, $login, 'failed', 'inactive_account', 'Inactive account login attempt', true);

            $audit->log(
                action: 'login_failed',
                category: 'auth',
                subject: $user,
                oldValues: null,
                newValues: null,
                extra: [
                    'login' => $login,
                    'reason' => 'inactive_account',
                ],
                message: 'Login failed: inactive account',
                isSuccess: false,
                severity: 'warning',
                isSuspicious: true
            );

            $audit->alert(
                alertType: 'inactive_account_login_attempt',
                riskLevel: 'high',
                message: 'Inactive account login attempt detected',
                userId: $user->id,
                meta: [
                    'user_id' => $user->id,
                    'login' => $login,
                    'ip' => request()->ip(),

                ]
            );

            return back()->withErrors(['login' => __('validations/validations.auth.account_inactive')])->withInput();
        }

        if (! Hash::check($request->password, $user->password)) {
            $this->writeLoginHistory($user, $login, 'failed', 'wrong_password', 'Wrong password', true);

            $audit->log(
                action: 'login_failed',
                category: 'auth',
                subject: $user,
                oldValues: null,
                newValues: null,
                extra: [
                    'login' => $login,
                    'reason' => 'wrong_password',
                ],
                message: 'Login failed: wrong password',
                isSuccess: false,
                severity: 'warning',
                isSuspicious: true
            );

            $audit->alert(
                alertType: 'wrong_password_attempt',
                riskLevel: 'medium',
                message: 'Wrong password attempt detected',
                userId: $user->id,
                meta: [
                    'login' => $login,
                ]
            );

            return back()->withErrors(['password' => __('validations/validations.auth.wrong_password')])->withInput();
        }

        // Create new token
        $token = bin2hex(random_bytes(32));
        $user->token = $token;
        $user->token_expires_at = now()->addDay();
        $user->save();

        $this->writeLoginHistory($user, $login, 'success', null, 'Login success', false);

        $audit->log(
            action: 'login_success',
            category: 'auth',
            subject: $user,
            oldValues: null,
            newValues: null,
            extra: [
                'login' => $login,
            ],
            message: 'User login successful',
            isSuccess: true,
            severity: 'info',
            isSuspicious: false
        );

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
    public function logout(Request $request)
    {
        $audit = app(AuditLogger::class);
        $user = null;

        $token = $request->cookie('auth_token');

        if ($token) {
            $user = User::where('token', $token)->first();
            if ($user) {
                $this->writeLoginHistory($user, $user->email ?? $user->phonenumber ?? 'unknown', 'logout', null, 'User logout', false);

                $audit->log(
                    action: 'logout',
                    category: 'auth',
                    subject: $user,
                    oldValues: null,
                    newValues: null,
                    extra: null,
                    message: 'User logout',
                    isSuccess: true,
                    severity: 'info',
                    isSuspicious: false
                );

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

    private function writeLoginHistory(
        ?User $user,
        string $loginValue,
        string $status,
        ?string $failReason = null,
        ?string $message = null,
        bool $isSuspicious = false
    ): void {
        $ua = (string) request()->userAgent();
        $uaLower = mb_strtolower($ua);

        $deviceType = 'desktop';
        if (str_contains($uaLower, 'mobile')) {
            $deviceType = 'mobile';
        } elseif (str_contains($uaLower, 'tablet') || str_contains($uaLower, 'ipad')) {
            $deviceType = 'tablet';
        }

        $browser = 'Unknown';
        if (str_contains($uaLower, 'edg')) {
            $browser = 'Edge';
        } elseif (str_contains($uaLower, 'chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($uaLower, 'firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($uaLower, 'safari') && !str_contains($uaLower, 'chrome')) {
            $browser = 'Safari';
        }

        $platform = 'Unknown';
        if (str_contains($uaLower, 'windows')) {
            $platform = 'Windows';
        } elseif (str_contains($uaLower, 'linux')) {
            $platform = 'Linux';
        } elseif (str_contains($uaLower, 'android')) {
            $platform = 'Android';
        } elseif (str_contains($uaLower, 'iphone') || str_contains($uaLower, 'ios')) {
            $platform = 'iPhone';
        } elseif (str_contains($uaLower, 'macintosh') || str_contains($uaLower, 'mac os')) {
            $platform = 'Mac';
        }

        LoginHistory::create([
            'user_id' => $user?->id,
            'login_value' => $loginValue,
            'status' => $status,
            'fail_reason' => $failReason,
            'ip_address' => request()->ip(),
            'user_agent' => $ua,
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
            'is_suspicious' => $isSuspicious,
            'message' => $message,
            'created_at' => now(),
        ]);
    }
}
