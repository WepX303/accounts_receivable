<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\SecurityAlert;

class AuditLogger
{

    public function log(
        string $action,
        ?string $category = null,
        ?object $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $extra = null,
        ?string $message = null,
        bool $isSuccess = true,
        string $severity = 'info',
        bool $isSuspicious = false
    ): void {
        $request = request();

        $authUser = auth()->user();

        $logUser = $authUser;
        if (! $logUser && $subject instanceof \App\Models\User) {
            $logUser = $subject;
        }

        [$deviceType, $browser, $platform] = $this->parseUserAgent((string) $request->userAgent());

        ActivityLog::create([
            'user_id' => $logUser?->id,
            'user_name' => $logUser?->full_name,
            'user_email' => $logUser?->email,
            'user_phone' => $logUser?->phonenumber,
            'user_role' => $logUser?->role?->value ?? (string) ($logUser?->role ?? ''),
            'action' => $action,
            'category' => $category,
            'severity' => $severity,
            'is_suspicious' => $isSuspicious,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => method_exists($subject, 'getKey') ? $subject->getKey() : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
            'http_method' => $request->method(),
            'url' => $request->fullUrl(),
            'route_name' => optional($request->route())->getName(),
            'is_success' => $isSuccess,
            'message' => $message,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'extra' => $extra,
            'created_at' => now(),
        ]);
    }

    public function alert(
        string $alertType,
        string $riskLevel = 'medium',
        ?string $message = null,
        ?array $meta = null,
        ?int $userId = null,
        ?string $ipAddress = null
    ): void {
        SecurityAlert::create([
            'user_id' => $userId ?? auth()->id(),
            'alert_type' => $alertType,
            'risk_level' => $riskLevel,
            'is_resolved' => false,
            'ip_address' => $ipAddress ?? request()->ip(),
            'message' => $message,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }

    private function parseUserAgent(string $ua): array
    {
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
        } elseif (str_contains($uaLower, 'mac os') || str_contains($uaLower, 'macintosh')) {
            $platform = 'Mac';
        }

        return [$deviceType, $browser, $platform];
    }
}
