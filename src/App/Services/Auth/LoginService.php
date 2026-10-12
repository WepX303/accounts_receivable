<?php

namespace App\Services\Auth;

use App\Models\LoginHistory;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\ClientInfo;
use Illuminate\Support\Facades\Hash;

/**
 * Decides whether a sign-in is accepted and keeps the login history, the audit
 * log and the alerts. Handing out the session or token is left to the caller,
 * because the panel and the API do that differently.
 */
class LoginService
{
    public function __construct(private AuditLogger $audit)
    {
    }

    /**
     * @throws LoginException
     */
    public function attempt(string $login, string $password): User
    {
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

            $this->audit->log(
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

            $this->audit->alert(
                alertType: 'login_failed_user_not_found',
                riskLevel: 'medium',
                message: 'Login attempt with unknown user',
                meta: [
                    'login' => $login,
                ]
            );

            throw new LoginException('login', 'user_not_found', $errorMessage);
        }

        if (! $user->status) {
            $this->writeLoginHistory($user, $login, 'failed', 'inactive_account', 'Inactive account login attempt', true);

            $this->audit->log(
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

            $this->audit->alert(
                alertType: 'inactive_account_login_attempt',
                riskLevel: 'high',
                message: 'Inactive account login attempt detected',
                userId: $user->id,
                meta: [
                    'user_id' => $user->id,
                    'login' => $login,
                    'ip' => ClientInfo::ip(),

                ]
            );

            throw new LoginException('login', 'inactive_account', __('validations/validations.auth.account_inactive'));
        }

        if (! Hash::check($password, $user->password)) {
            $this->writeLoginHistory($user, $login, 'failed', 'wrong_password', 'Wrong password', true);

            $this->audit->log(
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

            $this->audit->alert(
                alertType: 'wrong_password_attempt',
                riskLevel: 'medium',
                message: 'Wrong password attempt detected',
                userId: $user->id,
                meta: [
                    'login' => $login,
                ]
            );

            throw new LoginException('password', 'wrong_password', __('validations/validations.auth.wrong_password'));
        }

        return $user;
    }

    public function recordSuccess(User $user, string $login): void
    {
        $this->writeLoginHistory($user, $login, 'success', null, 'Login success', false);

        $this->audit->log(
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
    }

    public function recordLogout(User $user): void
    {
        $this->writeLoginHistory($user, $user->email ?? $user->phonenumber ?? 'unknown', 'logout', null, 'User logout', false);

        $this->audit->log(
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
    }

    private function writeLoginHistory(
        ?User $user,
        string $loginValue,
        string $status,
        ?string $failReason = null,
        ?string $message = null,
        bool $isSuspicious = false
    ): void {
        [$deviceType, $browser, $platform] = ClientInfo::agent();

        LoginHistory::create([
            'user_id' => $user?->id,
            'login_value' => $loginValue,
            'status' => $status,
            'fail_reason' => $failReason,
            'client' => ClientInfo::client(),
            'ip_address' => ClientInfo::ip(),
            'user_agent' => (string) request()->userAgent(),
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
            'is_suspicious' => $isSuspicious,
            'message' => $message,
            'created_at' => now(),
        ]);
    }
}
