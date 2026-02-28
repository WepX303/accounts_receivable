<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
    // public function register(): void
    // {
    //     $this->reportable(function (Throwable $e) {
    //         //
    //     });
    // }

    public function register(): void
    {
        // 1) HTTP hataları: 401/403/404/419/429/503 vs.
        $this->renderable(function (HttpExceptionInterface $e, $request) {
            $status = $e->getStatusCode();

            return match ($status) {
                404 => response()->view('errors.404', ['exception' => $e], 404),
                500 => response()->view('errors.500', ['exception' => $e], 500),
                default => response()->view('errors.other', ['exception' => $e, 'status' => $status], $status),
            };
        });

        // 2) HttpException olmayan tüm hatalar (gerçek 500'ler)
        $this->renderable(function (Throwable $e, $request) {
            return response()->view('errors.500', ['exception' => $e], 500);
        });
    }
}
