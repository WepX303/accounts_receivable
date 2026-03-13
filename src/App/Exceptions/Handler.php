<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
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
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        //
    }

    public function render($request, Throwable $e)
    {
        if ($request->is('api/*')) {
            if ($e instanceof ValidationException) {
                return ApiResponse::error(
                    'Validation failed.',
                    422,
                    'VALIDATION_ERROR',
                    $e->errors()
                );
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error(
                    'Unauthorized.',
                    401,
                    'UNAUTHORIZED'
                );
            }

            if ($e instanceof ModelNotFoundException) {
                return ApiResponse::error(
                    'Resource not found.',
                    404,
                    'RESOURCE_NOT_FOUND'
                );
            }

            if ($e instanceof ThrottleRequestsException) {
                return ApiResponse::error(
                    'Too many requests.',
                    429,
                    'TOO_MANY_REQUESTS'
                );
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();

                $message = match ($status) {
                    401 => 'Unauthorized.',
                    403 => 'Forbidden.',
                    404 => 'Resource not found.',
                    405 => 'Method not allowed.',
                    419 => 'Page expired.',
                    422 => 'Validation failed.',
                    429 => 'Too many requests.',
                    default => $status >= 500 ? 'Server error.' : ($e->getMessage() ?: 'Request failed.'),
                };

                return ApiResponse::error(
                    $message,
                    $status,
                    'HTTP_' . $status
                );
            }

            report($e);

            return ApiResponse::error(
                'Server error.',
                500,
                'SERVER_ERROR'
            );
        }

        return parent::render($request, $e);
    }
}