<?php

use App\Http\Controllers\StorePortal\StoreAuthController;
use App\Http\Controllers\StorePortal\StoreDashboardController;
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
    Route::get('/handovers', [StoreDashboardController::class, 'handovers'])->name('handovers.index');
    Route::post('/handovers/{handover}/accept', [StoreDashboardController::class, 'acceptHandover'])->name('handovers.accept');
    Route::post('/handovers/{handover}/reject', [StoreDashboardController::class, 'rejectHandover'])->name('handovers.reject');
    Route::get('/earnings', [StoreDashboardController::class, 'earnings'])->name('earnings.index');
    Route::get('/account', [StoreDashboardController::class, 'account'])->name('account.index');
    Route::get('/profile', [StoreDashboardController::class, 'profile'])->name('profile.show');
    Route::put('/profile', [StoreDashboardController::class, 'updateProfile'])->name('profile.update');
    Route::get('/change-password', [StoreDashboardController::class, 'showChangePassword'])->name('password.change');
    Route::put('/change-password', [StoreDashboardController::class, 'updatePassword'])->name('password.change.update');
});
