<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Commands\CommandController;
use App\Http\Controllers\Customers\CustomersController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Stores\StoreController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Customers\CustomersInfoController;
use App\Http\Controllers\Payments\CustomersPaymentController;
use App\Http\Controllers\Reports\AvshocrecatReportController;
use Illuminate\Support\Facades\Route;

// Login routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// Language switch
Route::get('/lang/{locale}', function (string $locale) {
    if (! in_array($locale, ['tk', 'en', 'ru', 'tr'], true)) {
        abort(404);
    }
    session(['locale' => $locale]);
    return redirect()->back();
})->name('lang.switch');


// ---------------------
// Protected routes
// ---------------------
Route::middleware(['auth.token'])->group(function () {

    // Dashboard
    Route::get('/', DashboardController::class)->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])
        ->name('profile');

    Route::post('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    // Store
    Route::get('/store', StoreController::class)->name('store');
    Route::get('/store-details', [StoreController::class, 'details'])->name('store.details');

    // Reports Monthly Payments
    Route::get('/report', [AvshocrecatReportController::class, 'index'])->name('report');

    // Customers
    Route::get('/customers', CustomersController::class)->name('customers');
    Route::get('/customers/info', CustomersInfoController::class)->name('customers.info');

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', UserController::class)->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Payments
    Route::get('/payments', CustomersPaymentController::class)->name('payments');
    Route::post('/payments', [CustomersPaymentController::class, 'store'])->name('payments.store');


    // Commands
    Route::get('/commands', [CommandController::class, 'index'])->name('commands.index');
    Route::post('/commands/run', [CommandController::class, 'run'])->name('commands.run');

    /*
    |--------------------------------------------------------------------------
    | Apps Pages
    |--------------------------------------------------------------------------
    */

    Route::prefix('apps')->name('apps.')->group(function () {});
});


// Route::get('/redis-test', function () {
//     Redis::set('mykey', 'Hello Redis!');
//     return Redis::get('mykey');
// });
