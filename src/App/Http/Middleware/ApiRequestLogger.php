<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApiRequestLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $this->logRequest(
                request: $request,
                statusCode: 500,
                durationMs: $this->durationMs($startedAt),
                isSuccess: false,
                severity: 'critical',
                message: 'API request failed with exception',
                extra: [
                    'exception_class' => get_class($e),
                    'exception_message' => $e->getMessage(),
                ]
            );

            throw $e;
        }

        $statusCode = $response->getStatusCode();
        $isSuccess = $statusCode >= 200 && $statusCode < 400;

        $this->logRequest(
            request: $request,
            statusCode: $statusCode,
            durationMs: $this->durationMs($startedAt),
            isSuccess: $isSuccess,
            severity: $this->resolveSeverity($statusCode),
            message: 'API request completed',
            extra: [
                'query' => $request->query(),
                'request_size' => strlen((string) $request->getContent()),
                'response_status' => $statusCode,
            ]
        );

        return $response;
    }

    private function logRequest(
        Request $request,
        int $statusCode,
        float $durationMs,
        bool $isSuccess,
        string $severity,
        string $message,
        array $extra = []
    ): void {
        /** @var \App\Services\AuditLogger $audit */
        $audit = app(AuditLogger::class);

        $payload = $this->safePayload($request);

        $audit->log(
            action: 'api_request',
            category: 'api',
            subject: $request->user(),
            oldValues: null,
            newValues: null,
            extra: array_merge([
                'path' => $request->path(),
                'method' => $request->method(),
                'status_code' => $statusCode,
                'duration_ms' => $durationMs,
                'payload' => $payload,
            ], $extra),
            message: $message,
            isSuccess: $isSuccess,
            severity: $severity,
            isSuspicious: $statusCode >= 400
        );
    }

    private function safePayload(Request $request): array
    {
        $data = $request->except([
            'password',
            'password_confirmation',
            'old_password',
            'new_password',
            'token',
        ]);

        array_walk_recursive($data, function (&$value, $key) {
            if (in_array($key, ['password', 'password_confirmation', 'old_password', 'new_password', 'token'], true)) {
                $value = '***';
            }
        });

        return $data;
    }

    private function durationMs(float $startedAt): float
    {
        return round((microtime(true) - $startedAt) * 1000, 2);
    }

    private function resolveSeverity(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => 'critical',
            $statusCode >= 400 => 'warning',
            default => 'info',
        };
    }
}
