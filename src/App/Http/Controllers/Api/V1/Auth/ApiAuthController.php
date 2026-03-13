<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $audit = app(AuditLogger::class);

        $login = trim((string) $request->input('login'));

        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
        $isPhone = preg_match('/^[0-9\+\-\s]{6,20}$/', $login);

        $user = User::query()
            ->where('email', $login)
            ->orWhere('phonenumber', $login)
            ->first();

        if (!$user) {
            $this->writeLoginHistory(
                user: null,
                loginValue: $login,
                status: 'failed',
                failReason: 'user_not_found',
                message: 'API login failed: user not found',
                isSuspicious: true
            );

            $audit->log(
                action: 'api_login_failed',
                category: 'auth',
                subject: null,
                oldValues: null,
                newValues: null,
                extra: [
                    'login' => $login,
                    'reason' => 'user_not_found',
                ],
                message: 'API login failed: user not found',
                isSuccess: false,
                severity: 'warning',
                isSuspicious: true
            );

            $audit->alert(
                alertType: 'api_login_failed_user_not_found',
                riskLevel: 'medium',
                message: 'API login attempt with unknown user',
                meta: [
                    'login' => $login,
                ]
            );

            return ApiResponse::error(
                $isEmail || $isPhone ? 'Invalid credentials.' : 'Invalid login value.',
                422,
                'INVALID_CREDENTIALS'
            );
        }

        if (!$user->status) {
            $this->writeLoginHistory(
                user: $user,
                loginValue: $login,
                status: 'failed',
                failReason: 'inactive_account',
                message: 'API login failed: inactive account',
                isSuspicious: true
            );

            $audit->log(
                action: 'api_login_failed',
                category: 'auth',
                subject: $user,
                oldValues: null,
                newValues: null,
                extra: [
                    'login' => $login,
                    'reason' => 'inactive_account',
                ],
                message: 'API login failed: inactive account',
                isSuccess: false,
                severity: 'warning',
                isSuspicious: true
            );

            $audit->alert(
                alertType: 'api_inactive_account_login_attempt',
                riskLevel: 'high',
                message: 'Inactive account API login attempt detected',
                userId: $user->id,
                meta: [
                    'user_id' => $user->id,
                    'login' => $login,
                    'ip' => $request->ip(),
                ]
            );

            return ApiResponse::error(
                'Your account is inactive.',
                403,
                'ACCOUNT_INACTIVE'
            );
        }

        if (!Hash::check((string) $request->input('password'), $user->password)) {
            $this->writeLoginHistory(
                user: $user,
                loginValue: $login,
                status: 'failed',
                failReason: 'wrong_password',
                message: 'API login failed: wrong password',
                isSuspicious: true
            );

            $audit->log(
                action: 'api_login_failed',
                category: 'auth',
                subject: $user,
                oldValues: null,
                newValues: null,
                extra: [
                    'login' => $login,
                    'reason' => 'wrong_password',
                ],
                message: 'API login failed: wrong password',
                isSuccess: false,
                severity: 'warning',
                isSuspicious: true
            );

            $audit->alert(
                alertType: 'api_wrong_password_attempt',
                riskLevel: 'medium',
                message: 'Wrong password API attempt detected',
                userId: $user->id,
                meta: [
                    'login' => $login,
                ]
            );

            return ApiResponse::error(
                'Invalid credentials.',
                422,
                'INVALID_CREDENTIALS'
            );
        }

        $token = bin2hex(random_bytes(32));

        $user->token = $token;
        $user->token_expires_at = now()->addDay();
        $user->save();

        $this->writeLoginHistory(
            user: $user,
            loginValue: $login,
            status: 'success',
            failReason: null,
            message: 'API login success',
            isSuspicious: false
        );

        $audit->log(
            action: 'api_login_success',
            category: 'auth',
            subject: $user,
            oldValues: null,
            newValues: null,
            extra: [
                'login' => $login,
            ],
            message: 'API login successful',
            isSuccess: true,
            severity: 'info',
            isSuspicious: false
        );

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => optional($user->token_expires_at)?->format('Y-m-d H:i:s'),
            'user' => new UserResource($user),
            'permissions' => [
                'can_manage_users' => $user->canManageUsers(),
                'can_void_payments' => $user->canVoidPayments(),
                'can_correct_payments' => $user->canCorrectPayments(),
            ],
        ], 'Login successful.');
    }

    public function me(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return ApiResponse::success([
            'user' => new UserResource($user),
            'permissions' => [
                'can_manage_users' => $user->canManageUsers(),
                'can_void_payments' => $user->canVoidPayments(),
                'can_correct_payments' => $user->canCorrectPayments(),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        $audit = app(AuditLogger::class);

        if ($user) {
            $this->writeLoginHistory(
                user: $user,
                loginValue: $user->email ?? $user->phonenumber ?? 'unknown',
                status: 'logout',
                failReason: null,
                message: 'API logout',
                isSuspicious: false
            );

            $audit->log(
                action: 'api_logout',
                category: 'auth',
                subject: $user,
                oldValues: null,
                newValues: null,
                extra: null,
                message: 'API logout',
                isSuccess: true,
                severity: 'info',
                isSuspicious: false
            );

            $user->token = null;
            $user->token_expires_at = null;
            $user->save();
        }

        return ApiResponse::success(null, 'Logout successful.');
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
        } elseif (str_contains($uaLower, 'opera') || str_contains($uaLower, 'opr/')) {
            $browser = 'Opera';
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