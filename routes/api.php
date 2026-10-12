<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Customers\CustomerController;
use App\Http\Controllers\Api\V1\Dashboard\DashboardController;
use App\Http\Controllers\Api\V1\Payments\PaymentController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Controllers\Api\V1\Reports\ReportController;
use App\Http\Controllers\Api\V1\Sms\SmsController;
use App\Http\Controllers\Api\V1\Users\UserController;
use App\Http\Controllers\Customers\CustomersExportController;
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

        Route::get('/dashboard', DashboardController::class);

        // Limited further: the form takes the current password, which could be guessed at.
        Route::put('/profile', [ProfileController::class, 'update'])->middleware('throttle:10,1');

        Route::middleware(Access::middleware('customers.info'))->group(function () {
            Route::get('/customers/search', [CustomerController::class, 'search']);
            Route::get('/customers/{credit}', [CustomerController::class, 'show'])->whereNumber('credit');
            Route::get('/customers/{credit}/payments', [CustomerController::class, 'payments'])->whereNumber('credit');
        });

        Route::middleware(Access::middleware('customers.list'))->group(function () {
            Route::get('/customers', [CustomerController::class, 'index']);
            // The panel's own export: same filters, same file, same audit entry.
            Route::get('/customers/export', CustomersExportController::class);
            Route::get('/customers/{credit}/statement', [CustomerController::class, 'statement'])->whereNumber('credit');
        });

        // Exports first: their addresses end in /export and must not be taken for a report.
        foreach (ReportController::EXPORTS as $address => [, , $ability]) {
            Route::get("/reports/{$address}", [ReportController::class, 'export'])
                ->defaults('report', $address)
                ->middleware(Access::middleware($ability));
        }

        foreach (ReportController::REPORTS as $address => [, , $ability]) {
            Route::get("/reports/{$address}", [ReportController::class, 'show'])
                ->defaults('report', $address)
                ->middleware(Access::middleware($ability));
        }

        Route::middleware(Access::middleware('sms'))->group(function () {
            Route::get('/sms/customers', [SmsController::class, 'customers']);
            Route::post('/sms/preview', [SmsController::class, 'preview']);
            Route::post('/sms/preview/export', [SmsController::class, 'exportPreview']);
            // Sending is the one action here that reaches outside; keep bursts small.
            Route::post('/sms/send', [SmsController::class, 'send'])->middleware('throttle:6,1');
        });

        Route::middleware(Access::middleware('users'))->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::put('/users/{user}', [UserController::class, 'update'])->whereNumber('user');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->whereNumber('user');
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
