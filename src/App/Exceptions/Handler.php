<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * API clients always get the JSON envelope; the panel keeps the framework's pages.
     */
    public function render($request, Throwable $e)
    {
        if (! $request->is('api/*')) {
            return parent::render($request, $e);
        }

        // A response someone already built, e.g. by a rate limiter.
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }

        if ($e instanceof ValidationException) {
            return ApiResponse::error(__('api.validation_failed'), 422, 'validation_failed', $e->errors());
        }

        if ($e instanceof AuthenticationException) {
            return ApiResponse::error(__('api.unauthenticated'), 401, 'unauthenticated');
        }

        if ($e instanceof ModelNotFoundException) {
            return ApiResponse::error(__('api.not_found'), 404, 'not_found');
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            [$code, $message] = match ($status) {
                401 => ['unauthenticated', __('api.unauthenticated')],
                403 => ['forbidden', __('api.forbidden')],
                404 => ['not_found', __('api.not_found')],
                405 => ['method_not_allowed', __('api.method_not_allowed')],
                429 => ['too_many_requests', __('api.too_many_requests')],
                default => $status >= 500
                    ? ['server_error', __('api.server_error')]
                    : ['request_failed', __('api.request_failed')],
            };

            return ApiResponse::error($message, $status, $code)->withHeaders($e->getHeaders());
        }

        // Never the exception text: it can carry SQL or file paths.
        return ApiResponse::error(__('api.server_error'), 500, 'server_error');
    }
}
