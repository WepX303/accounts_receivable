<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customers\CustomersController;
use App\Http\Controllers\Customers\CustomersInfoController;
use App\Http\Controllers\Customers\CustomersExportController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Payments\CustomersPaymentController;
use App\Http\Controllers\Payments\PaymentVoidController;
use App\Http\Controllers\Payments\PaymentCorrectController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Reports\AvshocrecatReportController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Logs\LogsController;


/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Language
|--------------------------------------------------------------------------
*/
Route::get('/lang/{locale}', function (string $locale) {
    if (! in_array($locale, ['tk', 'en', 'ru', 'tr'], true)) {
        abort(404);
    }
    session(['locale' => $locale]);

    return redirect()->back();
})->name('lang.switch');

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth.token'])->group(function () {

    // Everyone authenticated
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    /**
     * CUSTOMER INFO / PAYMENTS / REPORT
     * Admin + Cashier (+ Operator için isteniyor) + Analyst report istiyor
     */

    // Customer info: Admin, Cashier, Operator
    Route::middleware(['role:SuperAdmin,Admin,Cashier,Operator'])->group(function () {
        Route::get('/customers/info', CustomersInfoController::class)->name('customers.info');
    });

    // Payments (ödeme al): Admin, Cashier, Operator
    Route::middleware(['role:SuperAdmin,Admin,Cashier,Operator'])->group(function () {
        Route::get('/payments', CustomersPaymentController::class)->name('payments');
        Route::post('/payments', [CustomersPaymentController::class, 'store'])->name('payments.store');
    });

    // Report: Admin, Cashier, Analyst, Operator
    Route::middleware(['role:SuperAdmin,Admin,Cashier,Analyst,Operator'])->group(function () {
        Route::get('/report', [AvshocrecatReportController::class, 'index'])->name('report');
    });

    /**
     * CUSTOMERS LIST + EXPORT
     * Manager/Analyst/Operator/Admin
     */
    Route::middleware(['role:SuperAdmin,Admin,Manager,Analyst,Operator'])->group(function () {
        Route::get('/customers', CustomersController::class)->name('customers');
    });

    // Export: Admin + Operator + Manager + Analyst
    Route::middleware(['role:SuperAdmin,Admin,Manager,Analyst,Operator'])->group(function () {
        Route::get('/customers/export', CustomersExportController::class)->name('customers.export');
    });

    /**
     * PAYMENT EDIT ACTIONS (void/correct)
     * Admin + Operator
     */
    Route::middleware(['role:SuperAdmin,Admin'])->group(function () {
        Route::post('/payments/{payment}/void', PaymentVoidController::class)->name('payments.void');
        Route::post('/payments/{payment}/correct', PaymentCorrectController::class)->name('payments.correct');
    });

    /**
     * LOGS (ONLY SUPERADMIN )
     */
    Route::middleware(['role:SuperAdmin'])->group(function () {
        Route::get('/logs', LogsController::class)->name('logs');
    });

    /**
     * SETTINGS (USERS MANAGEMENT) - SuperAdmin + Admin   
     */
    Route::middleware(['role:SuperAdmin,Admin'])->group(function () {
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', UserController::class)->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });
    });
});
