<?php

use App\Http\Controllers\DeliveryPersonPortal\DeliveryPersonAuthController;
use App\Http\Controllers\DeliveryPersonPortal\DeliveryPersonBankAccountController;
use App\Http\Controllers\DeliveryPersonPortal\DeliveryPersonBookingController;
use App\Http\Controllers\DeliveryPersonPortal\DeliveryPersonDashboardController;
use App\Http\Controllers\DeliveryPersonPortal\DeliveryPersonReportsController;
use App\Http\Controllers\DeliveryPersonPortal\DeliveryPersonWithdrawalController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:delivery_person')->group(function () {
    Route::get('login', [DeliveryPersonAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [DeliveryPersonAuthController::class, 'login'])->name('login.submit');
    Route::get('forgot-password', [DeliveryPersonAuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('forgot-password', [DeliveryPersonAuthController::class, 'sendResetNotice'])->name('password.email');
});

Route::middleware(['auth:delivery_person', 'delivery_person.active'])->group(function () {
    Route::post('logout', [DeliveryPersonAuthController::class, 'logout'])->name('logout');

    Route::get('dashboard', [DeliveryPersonDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('bookings', [DeliveryPersonDashboardController::class, 'bookings'])->name('bookings.index');
    Route::get('collections', [DeliveryPersonDashboardController::class, 'collections'])->name('collections.index');
    Route::get('final-deliveries', [DeliveryPersonDashboardController::class, 'finalDeliveries'])->name('final-deliveries.index');

    Route::get('bookings/{booking}', [DeliveryPersonBookingController::class, 'show'])->name('bookings.show');
    Route::get('collection-jobs/{assignment}', [DeliveryPersonBookingController::class, 'showCollectionJob'])->name('collection-jobs.show');
    Route::get('final-delivery-jobs/{assignment}', [DeliveryPersonBookingController::class, 'showFinalDeliveryJob'])->name('final-delivery-jobs.show');
    Route::get('bookings/{booking}/edit', [DeliveryPersonBookingController::class, 'edit'])->name('bookings.edit');
    Route::put('bookings/{booking}', [DeliveryPersonBookingController::class, 'update'])->name('bookings.update');
    Route::post('bookings/{booking}/categories', [DeliveryPersonBookingController::class, 'addCategory'])->name('bookings.categories.add');
    Route::delete('bookings/{booking}/categories/{category}', [DeliveryPersonBookingController::class, 'removeCategory'])->name('bookings.categories.remove');

    Route::post('bookings/{booking}/otp/send', [DeliveryPersonBookingController::class, 'sendOtp'])->name('bookings.otp.send');
    Route::post('bookings/{booking}/otp/verify', [DeliveryPersonBookingController::class, 'verifyOtp'])->name('bookings.otp.verify');

    Route::post('bookings/{booking}/payments/cash', [DeliveryPersonBookingController::class, 'recordCash'])->name('bookings.payments.cash');
    Route::post('bookings/{booking}/payments/transfer', [DeliveryPersonBookingController::class, 'recordTransfer'])->name('bookings.payments.transfer');
    Route::post('bookings/{booking}/payments/stripe', [DeliveryPersonBookingController::class, 'initiateStripe'])->name('bookings.payments.stripe');
    Route::post('bookings/{booking}/payments/paypal', [DeliveryPersonBookingController::class, 'initiatePayPal'])->name('bookings.payments.paypal');
    Route::get('bookings/{booking}/payment-status', [DeliveryPersonBookingController::class, 'paymentStatus'])->name('bookings.payment-status');
    Route::post('bookings/{booking}/finalize', [DeliveryPersonBookingController::class, 'finalize'])->name('bookings.finalize');
    Route::post('bookings/{booking}/mark-picked-up', [DeliveryPersonBookingController::class, 'markPickedUp'])->name('bookings.mark-picked-up');
    Route::post('bookings/{booking}/store-handover', [DeliveryPersonBookingController::class, 'requestStoreHandover'])->name('bookings.store-handover');
    Route::post('pickup-requests/{pickupRequest}/accept', [DeliveryPersonBookingController::class, 'acceptPickupRequest'])->name('pickup-requests.accept');
    Route::post('pickup-requests/{pickupRequest}/decline', [DeliveryPersonBookingController::class, 'declinePickupRequest'])->name('pickup-requests.decline');
    Route::post('bookings/{booking}/confirm-store-pickup', [DeliveryPersonBookingController::class, 'confirmStorePickup'])->name('bookings.confirm-store-pickup');
    Route::post('bookings/{booking}/confirm-delivered', [DeliveryPersonBookingController::class, 'confirmDelivered'])->name('bookings.confirm-delivered');

    Route::get('cash-account', [DeliveryPersonDashboardController::class, 'cashAccount'])->name('cash-account');
    Route::get('settlements', [DeliveryPersonDashboardController::class, 'settlements'])->name('settlements');
    Route::get('earnings', [DeliveryPersonDashboardController::class, 'earnings'])->name('earnings');
    Route::get('reports', [DeliveryPersonReportsController::class, 'index'])->name('reports.index');
    Route::get('reports/overview', [DeliveryPersonReportsController::class, 'overview'])->name('reports.overview');
    Route::get('reports/collections', [DeliveryPersonReportsController::class, 'collections'])->name('reports.collections');
    Route::get('reports/final-deliveries', [DeliveryPersonReportsController::class, 'finalDeliveries'])->name('reports.final-deliveries');
    Route::get('reports/earnings', [DeliveryPersonReportsController::class, 'earnings'])->name('reports.earnings');
    Route::get('reports/withdrawals', [DeliveryPersonReportsController::class, 'withdrawals'])->name('reports.withdrawals');
    Route::get('reports/cash-account', [DeliveryPersonReportsController::class, 'cashAccount'])->name('reports.cash-account');
    Route::get('reports/performance', [DeliveryPersonReportsController::class, 'performance'])->name('reports.performance');
    Route::get('reports/export', [DeliveryPersonReportsController::class, 'export'])->name('reports.export');

    // Bank Accounts (AJAX)
    Route::get('bank-accounts', [DeliveryPersonBankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::post('bank-accounts', [DeliveryPersonBankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::put('bank-accounts/{bankAccount}', [DeliveryPersonBankAccountController::class, 'update'])->name('bank-accounts.update');
    Route::post('bank-accounts/{bankAccount}/default', [DeliveryPersonBankAccountController::class, 'setDefault'])->name('bank-accounts.default');
    Route::delete('bank-accounts/{bankAccount}', [DeliveryPersonBankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

    // Withdrawal Requests (AJAX)
    Route::get('withdrawals/modal-data', [DeliveryPersonWithdrawalController::class, 'modalData'])->name('withdrawals.modal-data');
    Route::post('withdrawals', [DeliveryPersonWithdrawalController::class, 'store'])->name('withdrawals.store');
    Route::get('withdrawals/{withdrawal}', [DeliveryPersonWithdrawalController::class, 'show'])->name('withdrawals.show');

    Route::post('bookings/{booking}/store-handover', [DeliveryPersonBookingController::class, 'requestStoreHandover'])->name('bookings.store-handover');
    Route::post('bookings/{booking}/tags', [\App\Http\Controllers\DeliveryPersonPortal\DeliveryPersonTagController::class, 'assignToBooking'])->name('bookings.tags.assign');
    Route::delete('bookings/{booking}/tags/{tag}', [\App\Http\Controllers\DeliveryPersonPortal\DeliveryPersonTagController::class, 'unassignFromBooking'])->name('bookings.tags.unassign');

    Route::get('tags', [\App\Http\Controllers\DeliveryPersonPortal\DeliveryPersonTagController::class, 'index'])->name('tags.index');
    Route::post('tags/{tag}/exception', [\App\Http\Controllers\DeliveryPersonPortal\DeliveryPersonTagController::class, 'reportException'])->name('tags.exception');

    Route::get('profile', [DeliveryPersonDashboardController::class, 'profile'])->name('profile');
    Route::post('profile', [DeliveryPersonDashboardController::class, 'updateProfile'])->name('profile.update');
    Route::post('change-password', [DeliveryPersonAuthController::class, 'updatePassword'])->name('password.update');
});
