<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customers\CustomersController;
use App\Http\Controllers\Customers\CustomersInfoController;
use App\Http\Controllers\Customers\CustomersExportController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Payments\CustomersPaymentController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Reports\AvshocrecatReportController;
use App\Http\Controllers\Stores\StoreController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

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

    /*
    |--------------------------------------------------------------------------
    | Common (Admin + Cashier + others)
    |--------------------------------------------------------------------------
    */
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    /*
    |--------------------------------------------------------------------------
    | CASHIER + ADMIN
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:Cashier,Admin'])->group(function () {

        // Customer info (kasiyer görebilir)
        Route::get('/customers/info', CustomersInfoController::class)
            ->name('customers.info');

        // Monthly payments report
        Route::get('/report', [AvshocrecatReportController::class, 'index'])
            ->name('report');

        // Payments (ödeme al)
        Route::get('/payments', CustomersPaymentController::class)
            ->name('payments');

        Route::post('/payments', [CustomersPaymentController::class, 'store'])
            ->name('payments.store');
    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN ONLY
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:Admin'])->group(function () {

        // Store
        Route::get('/store', StoreController::class)->name('store');
        Route::get('/store-details', [StoreController::class, 'details'])->name('store.details');

        // Customers list
        Route::get('/customers', CustomersController::class)->name('customers');

        Route::get('/customers/export', CustomersExportController::class)->name('customers.export');

        // Users
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', UserController::class)->name('index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Apps Pages
    |--------------------------------------------------------------------------
    */
    Route::prefix('apps')->name('apps.')->group(function () {});
});

/*
|--------------------------------------------------------------------------
| Error Test
|--------------------------------------------------------------------------
*/
Route::get('/hata', function () {
    abort(403);
});


Route::get('/phpinfo', function () {
    phpinfo();
});