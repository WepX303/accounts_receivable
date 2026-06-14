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
use App\Http\Controllers\Admin\Settings\CommandCenterController;
use App\Http\Controllers\Payments\CustomerPaymentStatementController;
use App\Http\Controllers\Sms\CustomerSmsController;
use App\Http\Controllers\Reports\PaymentCalendarReportController;
use App\Http\Controllers\Reports\PaymentCalendarReportDetailController;
use App\Http\Controllers\Reports\PaymentCalendarDetailsExportController;
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
     * SuperAdmin + Admin + Cashier (+ Operator için isteniyor) + Analyst report istiyor
     */

    // Customer info: Admin, Cashier, Operator
    Route::middleware(['role:SuperAdmin,Admin,Cashier,Operator'])->group(function () {
        Route::get('/customers/info', CustomersInfoController::class)->name('customers.info');
    });

    // Payments: SuperAdmin + Admin, Cashier, Operator
    Route::middleware(['role:SuperAdmin,Admin,Cashier,Operator'])->group(function () {
        Route::get('/payments', CustomersPaymentController::class)->name('payments');
        Route::post('/payments', [CustomersPaymentController::class, 'store'])->name('payments.store');
    });

    // Report: SuperAdmin + Admin, Cashier, Analyst, Operator
    Route::middleware(['role:SuperAdmin,Admin,Cashier,Analyst,Operator'])->group(function () {
        Route::get('/report', [AvshocrecatReportController::class, 'index'])->name('report');
        Route::get('/reports/avshocrecat/export', [AvshocrecatReportController::class, 'export'])
            ->name('report.export');
    });

    /**
     * CUSTOMERS LIST + EXPORT
     * SuperAdmin + Admin + Manager + Analyst + Operator
     */
    Route::middleware(['role:SuperAdmin,Admin,Manager,Analyst,Operator'])->group(function () {
        Route::get('/customers', CustomersController::class)->name('customers');
        Route::get('/payments/customer/{credit}/statement', [CustomerPaymentStatementController::class, 'show'])
            ->name('payments.customer.statement');
    });

    // Export: SyeprAdmin + Admin  + Manager + Analyst + Operator
    Route::middleware(['role:SuperAdmin,Admin,Manager,Analyst,Operator'])->group(function () {
        Route::get('/customers/export', CustomersExportController::class)->name('customers.export');
    });

    /**
     * PAYMENT EDIT ACTIONS (void/correct)
     * SuperAdmin + Admin
     */
    Route::middleware(['role:SuperAdmin,Admin'])->group(function () {
        Route::post('/payments/{payment}/void', PaymentVoidController::class)->name('payments.void');
        Route::post('/payments/{payment}/correct', PaymentCorrectController::class)->name('payments.correct');
    });

    /**
     * REPORTS
     * SuperAdmin + Analyst
     */
    Route::middleware(['role:SuperAdmin,Analyst'])->group(function () {
        Route::get('/reports/payment-calendar', PaymentCalendarReportController::class)
            ->name('reports.payment-calendar');
        Route::get('/reports/payment-calendar/export', [PaymentCalendarReportController::class, 'export'])
            ->name('reports.payment-calendar.export');
        Route::get('/reports/payment-calendar/details', PaymentCalendarReportDetailController::class)
            ->name('reports.payment-calendar.details');
        Route::get('/reports/payment-calendar/details/export', PaymentCalendarDetailsExportController::class)
            ->name('reports.payment-calendar.details.export');
    });


    /**
     * SMS DISTRIBUTION
     * SuperAdmin + Admin + Operator
     */
    Route::middleware(['role:SuperAdmin,Admin,Operator'])->group(function () {
        Route::get('/sms', [CustomerSmsController::class, 'index'])->name('sms.index');
        Route::post('/sms/preview', [CustomerSmsController::class, 'preview'])->name('sms.preview');
        Route::post('/sms/send', [CustomerSmsController::class, 'send'])->name('sms.send');
        Route::get('/sms/export-preview', [CustomerSmsController::class, 'exportPreview'])
            ->name('sms.export.preview');
        Route::delete('/sms/preview/clear', [CustomerSmsController::class, 'clearPreview'])->name('sms.preview.clear');
    });

    /**
     * COMMANDS
     * Superadmin
     */
    Route::middleware(['role:SuperAdmin'])->group(function () {
        Route::get('/commands', [CommandCenterController::class, 'index'])->name('commands.index');
        Route::post('/clear-all-caches', [CommandCenterController::class, 'clearAllCaches'])->name('clear-all-caches');
        Route::post('/credits/resync-amount-local', [CommandCenterController::class, 'resyncAmountLocal'])->name('credits.resync-amount-local');
        Route::post('/sync-credits', [CommandCenterController::class, 'syncCredits'])
            ->name('sync-credits');
        Route::post('/sync-avshocrecat', [CommandCenterController::class, 'syncAvshocrecat'])
            ->name('sync-avshocrecat');
    });



    /**
     * LOGS (ONLY SUPERADMIN )
     */
    Route::middleware(['role:SuperAdmin'])->group(function () {
        Route::get('/logs', LogsController::class)->name('logs');
    });

    /**
     * SETTINGS (USERS MANAGEMENT)
     * SuperAdmin + Admin   
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
