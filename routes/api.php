<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Customers\CustomerController;
use App\Http\Controllers\Api\V1\Payments\PaymentController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Support\Access;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| The mobile app talks to these. They sign in with Sanctum tokens, apart
| from the panel's cookie session, and share the panel's services so the
| business rules stay the same on both sides.
|
*/

Route::prefix('v1')->group(function () {

    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:api-login');

    Route::middleware(['auth:sanctum', 'api.active'])->group(function () {

        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Limited further: the form takes the current password, which could be guessed at.
        Route::put('/profile', [ProfileController::class, 'update'])->middleware('throttle:10,1');

        Route::middleware(Access::middleware('customers.info'))->group(function () {
            Route::get('/customers/search', [CustomerController::class, 'search']);
            Route::get('/customers/{credit}', [CustomerController::class, 'show'])->whereNumber('credit');
            Route::get('/customers/{credit}/payments', [CustomerController::class, 'payments'])->whereNumber('credit');
        });

        Route::post('/payments', [PaymentController::class, 'store'])
            ->middleware(Access::middleware('payments.take'));
        Route::post('/payments/{payment}/void', [PaymentController::class, 'void'])
            ->whereNumber('payment')
            ->middleware(Access::middleware('payments.void'));
        Route::post('/payments/{payment}/correct', [PaymentController::class, 'correct'])
            ->whereNumber('payment')
            ->middleware(Access::middleware('payments.correct'));
    });
});
