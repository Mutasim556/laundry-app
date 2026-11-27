<?php

use App\Http\Controllers\StorePortal\StoreAuthController;
use App\Http\Controllers\StorePortal\StoreBankAccountController;
use App\Http\Controllers\StorePortal\StoreDashboardController;
use App\Http\Controllers\StorePortal\StoreReportsController;
use App\Http\Controllers\StorePortal\StoreWithdrawalController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:store')->group(function () {
    Route::get('/login', [StoreAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [StoreAuthController::class, 'login'])->middleware('throttle:15,1')->name('login.submit');

    Route::get('/forgot-password', [StoreAuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [StoreAuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [StoreAuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [StoreAuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware(['auth:store', 'store.active'])->group(function () {
    Route::post('/logout', [StoreAuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [StoreDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/bookings', [StoreDashboardController::class, 'bookings'])->name('bookings.index');
    Route::get('/bookings/{booking}', [StoreDashboardController::class, 'showBooking'])->name('bookings.show');
    Route::post('/bookings/{booking}/mark-complete', [StoreDashboardController::class, 'markStoreComplete'])->name('bookings.mark-complete');
    Route::post('/bookings/{booking}/pickup-request', [StoreDashboardController::class, 'requestStorePickup'])->name('bookings.pickup-request');
    Route::get('/handovers', [StoreDashboardController::class, 'handovers'])->name('handovers.index');
    Route::post('/handovers/{handover}/accept', [StoreDashboardController::class, 'acceptHandover'])->name('handovers.accept');
    Route::post('/handovers/{handover}/reject', [StoreDashboardController::class, 'rejectHandover'])->name('handovers.reject');
    Route::get('/earnings', [StoreDashboardController::class, 'earnings'])->name('earnings.index');
    Route::get('/account', [StoreDashboardController::class, 'account'])->name('account.index');
    Route::get('/reports', [StoreReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/overview', [StoreReportsController::class, 'overview'])->name('reports.overview');
    Route::get('/reports/financial', [StoreReportsController::class, 'financial'])->name('reports.financial');
    Route::get('/reports/transactions', [StoreReportsController::class, 'transactions'])->name('reports.transactions');
    Route::get('/reports/transactions/{transaction}', [StoreReportsController::class, 'transactionDetail'])->name('reports.transactions.show');
    Route::get('/reports/bookings', [StoreReportsController::class, 'bookings'])->name('reports.bookings');
    Route::get('/reports/handovers', [StoreReportsController::class, 'handovers'])->name('reports.handovers');
    Route::get('/reports/performance', [StoreReportsController::class, 'performance'])->name('reports.performance');
    Route::get('/reports/export', [StoreReportsController::class, 'export'])->name('reports.export');
    Route::get('/profile', [StoreDashboardController::class, 'profile'])->name('profile.show');

    // Store Bank Accounts (AJAX)
    Route::get('/bank-accounts', [StoreBankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::post('/bank-accounts', [StoreBankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::put('/bank-accounts/{bankAccount}', [StoreBankAccountController::class, 'update'])->name('bank-accounts.update');
    Route::post('/bank-accounts/{bankAccount}/default', [StoreBankAccountController::class, 'setDefault'])->name('bank-accounts.default');
    Route::delete('/bank-accounts/{bankAccount}', [StoreBankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

    // Store Withdrawal Requests (AJAX)
    Route::get('/withdrawals/modal-data', [StoreWithdrawalController::class, 'modalData'])->name('withdrawals.modal-data');
    Route::post('/withdrawals', [StoreWithdrawalController::class, 'store'])->name('withdrawals.store');
    Route::get('/withdrawals/{withdrawal}', [StoreWithdrawalController::class, 'show'])->name('withdrawals.show');
    Route::put('/profile', [StoreDashboardController::class, 'updateProfile'])->name('profile.update');
    Route::put('/change-password', [StoreDashboardController::class, 'updatePassword'])->name('password.change.update');
});
