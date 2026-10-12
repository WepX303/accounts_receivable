<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\SecurityAlert;
use App\Support\ClientInfo;

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

        [$deviceType, $browser, $platform] = ClientInfo::agent($request);

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
            'subject_type' => is_object($subject) ? get_class($subject) : null,
            'subject_id' => is_object($subject) && method_exists($subject, 'getKey') ? $subject->getKey() : null,
            'client' => ClientInfo::client($request),
            'ip_address' => ClientInfo::ip($request),
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
            'client' => ClientInfo::client(),
            'ip_address' => $ipAddress ?? ClientInfo::ip(),
            'message' => $message,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}
