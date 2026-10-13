<?php

namespace App\Providers;

use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Everyday use of the app stays far below these; a script hammering
        // the API does not. A signed-in user is counted by account, wherever
        // the requests come from; anyone else by address, and more tightly.
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(180)->by('user:' . $request->user()->id)
                : Limit::perMinute(40)->by('ip:' . $request->ip());
        });

        // Reports run the heaviest queries of the panel.
        RateLimiter::for('api-reports', function (Request $request) {
            return Limit::perMinute(40)->by('user:' . ($request->user()?->id ?: $request->ip()));
        });

        // A spreadsheet can take the server minutes and a great deal of memory.
        RateLimiter::for('api-exports', function (Request $request) {
            return Limit::perMinute(6)->by('user:' . ($request->user()?->id ?: $request->ip()));
        });

        // Whatever changes something: payments, users, the profile, commands.
        RateLimiter::for('api-writes', function (Request $request) {
            return Limit::perMinute(40)->by('user:' . ($request->user()?->id ?: $request->ip()));
        });

        // Guessing one account's password is slowed down wherever the guesses
        // come from, and so is one address trying many accounts.
        RateLimiter::for('api-login', function (Request $request) {
            $login = mb_strtolower(trim((string) $request->input('login')));

            return [
                Limit::perMinute(5)->by('login:' . sha1($login))->response($this->loginThrottled('login', $login)),
                Limit::perMinute(20)->by('ip:' . $request->ip())->response($this->loginThrottled('ip', $login)),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    private function loginThrottled(string $by, string $login): \Closure
    {
        return function (Request $request, array $headers) use ($by, $login) {
            $key = $by === 'ip' ? $request->ip() : sha1($login);

            // One alert per burst; a flood of blocked requests must not flood the table.
            if (Cache::add("api-login-throttled:{$by}:{$key}", true, 60)) {
                app(AuditLogger::class)->alert(
                    alertType: 'login_throttled',
                    riskLevel: 'high',
                    message: 'Too many API login attempts',
                    meta: [
                        'login' => $login,
                        'limited_by' => $by,
                    ]
                );
            }

            return ApiResponse::error(__('api.too_many_requests'), 429, 'too_many_requests')->withHeaders($headers);
        };
    }
}
