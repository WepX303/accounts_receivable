<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\ViewErrorBag;

/**
 * Lets an API endpoint run the panel action that already does the job, and
 * read its outcome. The panel reports back by redirecting with flashed
 * messages; here those messages become the JSON answer.
 */
class Panel
{
    private const REFUSAL_KEYS = ['export_error', 'error', 'warning'];

    public static function call(string $controller, string $method, array $parameters = []): mixed
    {
        // Nothing may carry over from an earlier call; the API keeps no session.
        session()->flush();

        return app()->call([app($controller), $method], $parameters);
    }

    /**
     * The refusal a redirect carries, or null when it reports success.
     */
    public static function refusal(RedirectResponse $response): ?JsonResponse
    {
        $session = $response->getSession();
        $errors = $session?->get('errors');

        if ($errors instanceof ViewErrorBag && $errors->any()) {
            return ApiResponse::error($errors->first(), 422, 'request_failed', $errors->getBag('default')->toArray());
        }

        foreach (self::REFUSAL_KEYS as $key) {
            if ($message = $session?->get($key)) {
                return ApiResponse::error((string) $message, 422, 'request_failed');
            }
        }

        return null;
    }

    public static function flashed(RedirectResponse $response, string $key): mixed
    {
        return $response->getSession()?->get($key);
    }
}
