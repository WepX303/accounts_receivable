<?php

use App\Http\Controllers\Api\V1\Auth\ApiAuthController;
use App\Http\Controllers\Api\V1\Customers\ApiCustomerController;
use App\Http\Controllers\Api\V1\Dashboard\ApiDashboardController;
use App\Http\Controllers\Api\V1\Payments\ApiPaymentController;
use App\Http\Controllers\Api\V1\Reports\ApiReportController;
use App\Http\Controllers\Api\V1\Profile\ApiProfileController;
use App\Http\Controllers\Api\V1\Customers\ApiCustomersExportController;
use App\Http\Controllers\Api\V1\Users\ApiUserController;
use App\Http\Controllers\Api\V1\Logs\ApiLogsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Public
    |--------------------------------------------------------------------------
    */
    Route::post('/auth/login', [ApiAuthController::class, 'login'])
        ->middleware('throttle:10,1');

    /*
    |--------------------------------------------------------------------------
    | Protected
    |--------------------------------------------------------------------------
    */
    Route::middleware(['api.auth.token'])->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Auth
        |--------------------------------------------------------------------------
        */
        Route::get('/auth/me', [ApiAuthController::class, 'me']);
        Route::post('/auth/logout', [ApiAuthController::class, 'logout']);

        /*
        |--------------------------------------------------------------------------
        | Profile
        |--------------------------------------------------------------------------
        */
        Route::get('/profile', [ApiProfileController::class, 'show']);
        Route::put('/profile', [ApiProfileController::class, 'update']);
        Route::post('/profile/change-password', [ApiProfileController::class, 'changePassword']);

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */
        Route::get('/dashboard', ApiDashboardController::class);

        /*
        |--------------------------------------------------------------------------
        | Users Management
        |--------------------------------------------------------------------------
        */
        Route::get('/users', [ApiUserController::class, 'index'])
            ->middleware('api.role:SuperAdmin,Admin');

        Route::post('/users', [ApiUserController::class, 'store'])
            ->middleware('api.role:SuperAdmin,Admin');

        Route::put('/users/{user}', [ApiUserController::class, 'update'])
            ->middleware('api.role:SuperAdmin,Admin');

        Route::delete('/users/{user}', [ApiUserController::class, 'destroy'])
            ->middleware('api.role:SuperAdmin,Admin');

        /*
        |--------------------------------------------------------------------------
        | Customers
        |--------------------------------------------------------------------------
        */
        Route::get('/customers', [ApiCustomerController::class, 'index']);
        Route::get('/customers/search', [ApiCustomerController::class, 'search']);
        Route::get('/customers/export', ApiCustomersExportController::class)
            ->middleware('api.role:SuperAdmin,Admin,Manager,Analyst,Operator');
        Route::get('/customers/{credit}', [ApiCustomerController::class, 'show']);
        Route::get('/customers/{credit}/payments', [ApiPaymentController::class, 'history']);

        /*
        |--------------------------------------------------------------------------
        | Payments
        |--------------------------------------------------------------------------
        */
        Route::get('/customers/{credit}/payments', [ApiPaymentController::class, 'history']);
        Route::post('/payments', [ApiPaymentController::class, 'store']);

        Route::post('/payments/{payment}/void', [ApiPaymentController::class, 'void'])
            ->middleware('api.role:SuperAdmin,Admin');

        Route::post('/payments/{payment}/correct', [ApiPaymentController::class, 'correct'])
            ->middleware('api.role:SuperAdmin,Admin');

        /*
        |--------------------------------------------------------------------------
        | Monthly Reports
        |--------------------------------------------------------------------------
        */
        Route::get('/reports', [ApiReportController::class, 'index'])
            ->middleware('api.role:SuperAdmin,Admin,Cashier,Analyst,Operator');

        Route::get('/reports/{report}', [ApiReportController::class, 'show'])
            ->middleware('api.role:SuperAdmin,Admin,Cashier,Analyst,Operator');

        /*
        |--------------------------------------------------------------------------
        | Logs
        |--------------------------------------------------------------------------
        */
        Route::get('/logs', [ApiLogsController::class, 'index'])
            ->middleware('api.role:SuperAdmin');
    });
});
