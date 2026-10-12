<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Who is on the other end of a request, for the audit trail: the panel or the
 * app, from which address, on what device. Read in one place so the activity
 * log, the login history and the alerts all say the same.
 */
class ClientInfo
{
    public const WEB = 'web';

    public const MOBILE = 'mobile';

    public const SYSTEM = 'system';

    /**
     * The start of the app's User-Agent: "AccountsReceivableMobile/1.0.0 (android; mobile)".
     */
    private const APP_AGENT = 'accountsreceivablemobile/';

    public static function client(?Request $request = null): string
    {
        $request ??= request();

        if ($request->is('api/*')) {
            return self::MOBILE;
        }

        // A scheduled or hand-run command has no visitor at all.
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return self::SYSTEM;
        }

        return self::WEB;
    }

    /**
     * The address the visitor reached us from. Behind a proxy the connection
     * itself comes from the proxy, so the forwarded address is preferred
     * whenever the plain one is not a public address.
     */
    public static function ip(?Request $request = null): ?string
    {
        $request ??= request();

        $ip = $request->ip();

        if (self::isPublic($ip)) {
            return $ip;
        }

        foreach (['CF-Connecting-IP', 'X-Real-IP', 'X-Forwarded-For'] as $header) {
            foreach (explode(',', (string) $request->headers->get($header)) as $candidate) {
                $candidate = trim($candidate);

                if (self::isPublic($candidate)) {
                    return $candidate;
                }
            }
        }

        return $ip;
    }

    /**
     * @return array{0: string, 1: string, 2: string} device type, browser, platform
     */
    public static function agent(?Request $request = null): array
    {
        $ua = mb_strtolower((string) ($request ?? request())->userAgent());

        // The app names itself; nothing about it has to be guessed.
        if (str_starts_with($ua, self::APP_AGENT)) {
            $version = strtok(substr($ua, strlen(self::APP_AGENT)), ' ') ?: '';

            return [
                'mobile',
                trim('Mobile App ' . $version),
                match (true) {
                    str_contains($ua, '(android') => 'Android',
                    str_contains($ua, '(ios') => 'iPhone',
                    default => 'Unknown',
                },
            ];
        }

        $deviceType = 'desktop';
        if (str_contains($ua, 'mobile')) {
            $deviceType = 'mobile';
        } elseif (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            $deviceType = 'tablet';
        }

        $browser = 'Unknown';
        if (str_contains($ua, 'edg')) {
            $browser = 'Edge';
        } elseif (str_contains($ua, 'chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($ua, 'safari') && ! str_contains($ua, 'chrome')) {
            $browser = 'Safari';
        } elseif (str_contains($ua, 'opera') || str_contains($ua, 'opr/')) {
            $browser = 'Opera';
        }

        $platform = 'Unknown';
        if (str_contains($ua, 'windows')) {
            $platform = 'Windows';
        } elseif (str_contains($ua, 'linux')) {
            $platform = 'Linux';
        } elseif (str_contains($ua, 'android')) {
            $platform = 'Android';
        } elseif (str_contains($ua, 'iphone') || str_contains($ua, 'ios')) {
            $platform = 'iPhone';
        } elseif (str_contains($ua, 'mac os') || str_contains($ua, 'macintosh')) {
            $platform = 'Mac';
        }

        return [$deviceType, $browser, $platform];
    }

    private static function isPublic(?string $ip): bool
    {
        return $ip !== null && filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
