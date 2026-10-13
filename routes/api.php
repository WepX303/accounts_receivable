<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Commands\CommandController;
use App\Http\Controllers\Api\V1\Customers\CustomerController;
use App\Http\Controllers\Api\V1\Dashboard\DashboardController;
use App\Http\Controllers\Api\V1\Logs\LogController;
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
            Route::get('/customers/export', CustomersExportController::class)->middleware(['throttle:api-exports', 'api.one-export']);
            Route::get('/customers/{credit}/statement', [CustomerController::class, 'statement'])->whereNumber('credit');
        });

        // Exports first: their addresses end in /export and must not be taken for a report.
        foreach (ReportController::EXPORTS as $address => [, , $ability]) {
            Route::get("/reports/{$address}", [ReportController::class, 'export'])
                ->defaults('report', $address)
                ->middleware([Access::middleware($ability), 'throttle:api-exports', 'api.one-export']);
        }

        foreach (ReportController::REPORTS as $address => [, , $ability]) {
            Route::get("/reports/{$address}", [ReportController::class, 'show'])
                ->defaults('report', $address)
                ->middleware([Access::middleware($ability), 'throttle:api-reports']);
        }

        Route::middleware(Access::middleware('sms'))->group(function () {
            Route::get('/sms/customers', [SmsController::class, 'customers']);
            Route::post('/sms/preview', [SmsController::class, 'preview']);
            // The app prepares the messages and takes them away as a spreadsheet.
            // Sending stays in the panel: there is no address for it here.
            Route::post('/sms/preview/export', [SmsController::class, 'exportPreview'])
                ->middleware(['throttle:api-exports', 'api.one-export']);
        });

        Route::middleware(Access::middleware('users'))->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store'])->middleware('throttle:api-writes');
            Route::put('/users/{user}', [UserController::class, 'update'])->whereNumber('user')->middleware('throttle:api-writes');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->whereNumber('user')->middleware('throttle:api-writes');
        });

        Route::get('/logs', LogController::class)->middleware(Access::middleware('logs'));

        Route::middleware(Access::middleware('commands'))->group(function () {
            Route::get('/commands', [CommandController::class, 'index']);
            Route::post('/commands/{command}', [CommandController::class, 'run'])
                ->whereIn('command', array_keys(CommandController::ACTIONS))
                ->middleware('throttle:6,1');
        });

        // No cashier books this many payments a minute; a script might try.
        Route::middleware('throttle:api-writes')->group(function () {
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
});
