<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\Auth\LoginController;
use Modules\Admin\Http\Controllers\DashboardController;
use Modules\Admin\Http\Controllers\ProfileController;
use Modules\Admin\Http\Controllers\RoleController;

Route::prefix('admin')->name('admin.')->group(function () {
    // Guest Admin Routes
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');
    });

    // Authenticated Admin Routes
    Route::middleware(['auth:admin', 'not_blocked:admin'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        // Admin Profile Routes
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

        // Roles & Permissions Management
        Route::middleware('permission:view-roles,admin')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('roles/data', [RoleController::class, 'data'])->name('roles.data');
            Route::get('roles/{role}/users', [RoleController::class, 'users'])->name('roles.users');
        });
        Route::post('roles', [RoleController::class, 'store'])->middleware('permission:create-roles,admin')->name('roles.store');
        Route::match(['put', 'patch'], 'roles/{role}', [RoleController::class, 'update'])->middleware('permission:edit-roles,admin')->name('roles.update');
        Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->middleware('permission:edit-roles,admin')->name('roles.permissions.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:delete-roles,admin')->name('roles.destroy');

        // Admins Management
        Route::middleware('permission:view-admins,admin')->group(function () {
            Route::get('admins', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'index'])->name('admins.index');
            Route::get('admins/data', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'data'])->name('admins.data');
        });
        Route::post('admins', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'store'])->middleware('permission:create-admins,admin')->name('admins.store');
        Route::match(['put', 'patch'], 'admins/{admin}', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'update'])->middleware('permission:edit-admins,admin')->name('admins.update');
        Route::put('admins/{admin}/roles', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'updateRoles'])->middleware('permission:edit-admins,admin')->name('admins.roles.update');
        Route::patch('admins/{admin}/toggle-block', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'toggleBlock'])->middleware('permission:edit-admins,admin')->name('admins.toggle-block');
        Route::delete('admins/{admin}', [\Modules\Admin\Http\Controllers\AdminUserController::class, 'destroy'])->middleware('permission:delete-admins,admin')->name('admins.destroy');

        // Users Management (Web Guard)
        Route::middleware('permission:view-users,admin')->group(function () {
            Route::get('users', [\Modules\Admin\Http\Controllers\UserController::class, 'index'])->name('users.index');
            Route::get('users/data', [\Modules\Admin\Http\Controllers\UserController::class, 'data'])->name('users.data');
        });
        Route::post('users', [\Modules\Admin\Http\Controllers\UserController::class, 'store'])->middleware('permission:create-users,admin')->name('users.store');
        Route::match(['put', 'patch'], 'users/{user}', [\Modules\Admin\Http\Controllers\UserController::class, 'update'])->middleware('permission:edit-users,admin')->name('users.update');
        Route::put('users/{user}/roles', [\Modules\Admin\Http\Controllers\UserController::class, 'updateRoles'])->middleware('permission:edit-users,admin')->name('users.roles.update');
        Route::patch('users/{user}/toggle-block', [\Modules\Admin\Http\Controllers\UserController::class, 'toggleBlock'])->middleware('permission:edit-users,admin')->name('users.toggle-block');
        Route::delete('users/{user}', [\Modules\Admin\Http\Controllers\UserController::class, 'destroy'])->middleware('permission:delete-users,admin')->name('users.destroy');

        // System & Branding Settings
        Route::middleware('permission:manage-settings,admin')->group(function () {
            Route::get('settings', [\Modules\Admin\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
            Route::match(['post', 'put', 'patch'], 'settings', [\Modules\Admin\Http\Controllers\SettingController::class, 'update'])->name('settings.update');
            Route::delete('settings/{key}', [\Modules\Admin\Http\Controllers\SettingController::class, 'destroy'])->name('settings.destroy');
        });

        // Services: Units & Categories Management
        Route::prefix('services')->name('services.')->group(function () {
            // Units
            Route::middleware('permission:view-units,admin')->group(function () {
                Route::get('units', [\Modules\Admin\Http\Controllers\UnitController::class, 'index'])->name('units.index');
                Route::get('units/data', [\Modules\Admin\Http\Controllers\UnitController::class, 'data'])->name('units.data');
            });
            Route::post('units', [\Modules\Admin\Http\Controllers\UnitController::class, 'store'])->middleware('permission:create-units,admin')->name('units.store');
            Route::match(['put', 'patch'], 'units/{unit}', [\Modules\Admin\Http\Controllers\UnitController::class, 'update'])->middleware('permission:edit-units,admin')->name('units.update');
            Route::patch('units/{unit}/toggle-status', [\Modules\Admin\Http\Controllers\UnitController::class, 'toggleStatus'])->middleware('permission:edit-units,admin')->name('units.toggle-status');
            Route::delete('units/{unit}', [\Modules\Admin\Http\Controllers\UnitController::class, 'destroy'])->middleware('permission:delete-units,admin')->name('units.destroy');

            // Item Categories
            Route::middleware('permission:view-item-categories,admin')->group(function () {
                Route::get('categories', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'index'])->name('categories.index');
                Route::get('categories/data', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'data'])->name('categories.data');
                Route::get('categories/check-slug', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'checkSlug'])->name('categories.check-slug');
            });
            Route::post('categories', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'store'])->middleware('permission:create-item-categories,admin')->name('categories.store');
            Route::match(['put', 'patch'], 'categories/{itemCategory}', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'update'])->middleware('permission:edit-item-categories,admin')->name('categories.update');
            Route::patch('categories/{itemCategory}/toggle-status', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'toggleStatus'])->middleware('permission:edit-item-categories,admin')->name('categories.toggle-status');
            Route::delete('categories/{itemCategory}', [\Modules\Admin\Http\Controllers\ItemCategoryController::class, 'destroy'])->middleware('permission:delete-item-categories,admin')->name('categories.destroy');

            // Laundry Items
            Route::middleware('permission:view-items,admin')->group(function () {
                Route::get('items', [\Modules\Admin\Http\Controllers\ItemController::class, 'index'])->name('items.index');
                Route::get('items/data', [\Modules\Admin\Http\Controllers\ItemController::class, 'data'])->name('items.data');
                Route::get('items/check-slug', [\Modules\Admin\Http\Controllers\ItemController::class, 'checkSlug'])->name('items.check-slug');
            });
            Route::post('items', [\Modules\Admin\Http\Controllers\ItemController::class, 'store'])->middleware('permission:create-items,admin')->name('items.store');
            Route::match(['put', 'patch'], 'items/{item}', [\Modules\Admin\Http\Controllers\ItemController::class, 'update'])->middleware('permission:edit-items,admin')->name('items.update');
            Route::patch('items/{item}/toggle-status', [\Modules\Admin\Http\Controllers\ItemController::class, 'toggleStatus'])->middleware('permission:edit-items,admin')->name('items.toggle-status');
            Route::delete('items/{item}', [\Modules\Admin\Http\Controllers\ItemController::class, 'destroy'])->middleware('permission:delete-items,admin')->name('items.destroy');

            // Laundry Services
            Route::middleware('permission:view-services,admin')->group(function () {
                Route::get('services', [\Modules\Admin\Http\Controllers\ServiceController::class, 'index'])->name('services.index');
                Route::get('services/data', [\Modules\Admin\Http\Controllers\ServiceController::class, 'data'])->name('services.data');
                Route::get('services/check-slug', [\Modules\Admin\Http\Controllers\ServiceController::class, 'checkSlug'])->name('services.check-slug');
                Route::get('services/items-by-category', [\Modules\Admin\Http\Controllers\ServiceController::class, 'getItemsByCategory'])->name('services.items-by-category');
            });
            Route::post('services', [\Modules\Admin\Http\Controllers\ServiceController::class, 'store'])->middleware('permission:create-services,admin')->name('services.store');
            Route::match(['put', 'patch'], 'services/{service}', [\Modules\Admin\Http\Controllers\ServiceController::class, 'update'])->middleware('permission:edit-services,admin')->name('services.update');
            Route::patch('services/{service}/toggle-status', [\Modules\Admin\Http\Controllers\ServiceController::class, 'toggleStatus'])->middleware('permission:edit-services,admin')->name('services.toggle-status');
            Route::delete('services/{service}', [\Modules\Admin\Http\Controllers\ServiceController::class, 'destroy'])->middleware('permission:delete-services,admin')->name('services.destroy');
        });

        // Store Section: Stores & Vendors Management
        Route::prefix('stores')->name('stores.')->group(function () {
            // Bangladesh Location API helpers for dynamic linked dropdowns
            Route::get('geo/districts', [\Modules\Admin\Http\Controllers\StoreController::class, 'getDistricts'])->name('geo.districts');
            Route::get('geo/upazilas', [\Modules\Admin\Http\Controllers\StoreController::class, 'getUpazilas'])->name('geo.upazilas');
            Route::get('geo/local-areas', [\Modules\Admin\Http\Controllers\StoreController::class, 'getLocalAreas'])->name('geo.local-areas');

            // Stores Management
            Route::middleware('permission:view-stores,admin')->group(function () {
                Route::get('/', [\Modules\Admin\Http\Controllers\StoreController::class, 'index'])->name('index');
                Route::get('data', [\Modules\Admin\Http\Controllers\StoreController::class, 'data'])->name('data');
                Route::get('check-slug', [\Modules\Admin\Http\Controllers\StoreController::class, 'checkSlug'])->name('check-slug');
            });
            Route::post('/', [\Modules\Admin\Http\Controllers\StoreController::class, 'store'])->middleware('permission:create-stores,admin')->name('store');
            Route::match(['put', 'patch'], '{store}', [\Modules\Admin\Http\Controllers\StoreController::class, 'update'])->middleware('permission:edit-stores,admin')->name('update');
            Route::patch('{store}/toggle-orders', [\Modules\Admin\Http\Controllers\StoreController::class, 'toggleAcceptingOrders'])->middleware('permission:edit-stores,admin')->name('toggle-orders');
            Route::delete('{store}', [\Modules\Admin\Http\Controllers\StoreController::class, 'destroy'])->middleware('permission:delete-stores,admin')->name('destroy');

            // Vendors Management
            Route::prefix('vendors')->name('vendors.')->group(function () {
                Route::middleware('permission:manage-vendors,admin')->group(function () {
                    Route::get('/', [\Modules\Admin\Http\Controllers\VendorController::class, 'index'])->name('index');
                    Route::get('data', [\Modules\Admin\Http\Controllers\VendorController::class, 'data'])->name('data');
                    Route::post('/', [\Modules\Admin\Http\Controllers\VendorController::class, 'store'])->name('store');
                    Route::match(['put', 'patch'], '{vendor}', [\Modules\Admin\Http\Controllers\VendorController::class, 'update'])->name('update');
                    Route::delete('{vendor}', [\Modules\Admin\Http\Controllers\VendorController::class, 'destroy'])->name('destroy');
                });
            });
        });

        // UK Address Management
        Route::prefix('uk-addresses')->name('uk-addresses.')->group(function () {
            Route::middleware('permission:view-uk-addresses,admin')->group(function () {
                Route::get('/', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'index'])->name('index');
                Route::get('data', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'data'])->name('data');
            });
            Route::post('/', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'store'])->middleware('permission:create-uk-addresses,admin')->name('store');
            Route::match(['put', 'patch'], '{ukAddress}', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'update'])->middleware('permission:edit-uk-addresses,admin')->name('update');
            Route::patch('{ukAddress}/toggle-status', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'toggleStatus'])->middleware('permission:edit-uk-addresses,admin')->name('toggle-status');
            Route::delete('{ukAddress}', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'destroy'])->middleware('permission:delete-uk-addresses,admin')->name('destroy');
            Route::get('export', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'export'])->middleware('permission:export-uk-addresses,admin')->name('export');
            Route::post('import/preview', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'previewCsv'])->middleware('permission:import-uk-addresses,admin')->name('import.preview');
            Route::post('import/process', [\Modules\Admin\Http\Controllers\UkAddressController::class, 'importCsv'])->middleware('permission:import-uk-addresses,admin')->name('import.process');
        });

        // Hotel Management
        Route::prefix('hotels')->name('hotels.')->group(function () {
            Route::middleware('permission:view-hotels,admin')->group(function () {
                Route::get('/', [\Modules\Admin\Http\Controllers\HotelController::class, 'index'])->name('index');
                Route::get('data', [\Modules\Admin\Http\Controllers\HotelController::class, 'data'])->name('data');
            });
            Route::post('/', [\Modules\Admin\Http\Controllers\HotelController::class, 'store'])->middleware('permission:create-hotels,admin')->name('store');
            Route::match(['put', 'patch'], '{hotel}', [\Modules\Admin\Http\Controllers\HotelController::class, 'update'])->middleware('permission:edit-hotels,admin')->name('update');
            Route::patch('{hotel}/toggle-status', [\Modules\Admin\Http\Controllers\HotelController::class, 'toggleStatus'])->middleware('permission:edit-hotels,admin')->name('toggle-status');
            Route::delete('{hotel}', [\Modules\Admin\Http\Controllers\HotelController::class, 'destroy'])->middleware('permission:delete-hotels,admin')->name('destroy');
            Route::get('export', [\Modules\Admin\Http\Controllers\HotelController::class, 'export'])->middleware('permission:export-hotels,admin')->name('export');
            Route::post('import/preview', [\Modules\Admin\Http\Controllers\HotelController::class, 'previewCsv'])->middleware('permission:import-hotels,admin')->name('import.preview');
            Route::post('import/process', [\Modules\Admin\Http\Controllers\HotelController::class, 'importCsv'])->middleware('permission:import-hotels,admin')->name('import.process');
        });

        // Booking Schedule Management
        Route::prefix('booking-schedule')->name('booking-schedule.')->group(function () {
            Route::get('/', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'index'])
                ->middleware('permission:view-booking-schedule,admin')
                ->name('index');

            Route::middleware('permission:manage-booking-schedule,admin')->group(function () {
                Route::post('slots', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'storeSlot'])->name('slots.store');
                Route::put('slots/{slot}', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'updateSlot'])->name('slots.update');
                Route::patch('slots/{slot}/toggle', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'toggleSlot'])->name('slots.toggle');
                Route::delete('slots/{slot}', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'destroySlot'])->name('slots.destroy');

                Route::post('instructions', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'storeInstruction'])->name('instructions.store');
                Route::put('instructions/{instruction}', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'updateInstruction'])->name('instructions.update');
                Route::patch('instructions/{instruction}/toggle', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'toggleInstruction'])->name('instructions.toggle');
                Route::delete('instructions/{instruction}', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'destroyInstruction'])->name('instructions.destroy');

                Route::post('settings', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'updateSettings'])->name('settings.update');
                Route::post('blocked-dates', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'storeBlockedDate'])->name('blocked-dates.store');
                Route::delete('blocked-dates/{blockedDate}', [\Modules\Admin\Http\Controllers\BookingScheduleController::class, 'destroyBlockedDate'])->name('blocked-dates.destroy');
            });
        });

        // Booking Payments & Stripe Management
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [\Modules\Admin\Http\Controllers\BookingPaymentController::class, 'index'])
                ->middleware('permission:view-payments,admin')
                ->name('index');
            Route::post('{booking}/finalize', [\Modules\Admin\Http\Controllers\BookingPaymentController::class, 'finalizeAndCharge'])
                ->middleware('permission:manage-payments,admin')
                ->name('finalize');
            Route::post('{booking}/retry', [\Modules\Admin\Http\Controllers\BookingPaymentController::class, 'retryCharge'])
                ->middleware('permission:manage-payments,admin')
                ->name('retry');
            Route::post('{booking}/refund', [\Modules\Admin\Http\Controllers\BookingPaymentController::class, 'refund'])
                ->middleware('permission:refund-payments,admin')
                ->name('refund');
        });

        // Dynamic Payment Gateway Management (Stripe, PayPal, COD)
        Route::prefix('payment-gateways')->name('payment-gateways.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'index'])
                ->middleware('permission:view-payment-gateways,admin')
                ->name('index');
            Route::post('/stripe', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'updateStripe'])
                ->middleware('permission:manage-payment-gateways,admin')
                ->name('stripe.update');
            Route::post('/paypal', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'updatePayPal'])
                ->middleware('permission:manage-payment-gateways,admin')
                ->name('paypal.update');
            Route::post('/cod', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'updateCod'])
                ->middleware('permission:manage-payment-gateways,admin')
                ->name('cod.update');
            Route::post('/{gatewayCode}/toggle', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'toggleStatus'])
                ->middleware('permission:manage-payment-gateways,admin')
                ->name('toggle');
            Route::post('/{gatewayCode}/test', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'testConnection'])
                ->middleware('permission:manage-payment-gateways,admin')
                ->name('test');
            Route::delete('/{gatewayCode}/configuration', [\Modules\Admin\App\Http\Controllers\PaymentGatewayController::class, 'removeConfiguration'])
                ->middleware('permission:manage-payment-gateways,admin')
                ->name('remove');
        });

        // Dynamic Booking Fee Settings (Service Fee & Collection & Delivery Fee)
        Route::prefix('booking-fees')->name('booking-fees.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\BookingFeeSettingController::class, 'index'])
                ->middleware('permission:view-booking-fees,admin')
                ->name('index');
            Route::post('/service-fee', [\Modules\Admin\App\Http\Controllers\BookingFeeSettingController::class, 'updateServiceFee'])
                ->middleware('permission:manage-booking-fees,admin')
                ->name('service-fee.update');
            Route::post('/delivery-fee', [\Modules\Admin\App\Http\Controllers\BookingFeeSettingController::class, 'updateDeliveryFee'])
                ->middleware('permission:manage-booking-fees,admin')
                ->name('delivery-fee.update');
        });

        // Global Country & Currency Settings
        Route::prefix('currency-settings')->name('currency-settings.')->middleware('permission:manage-settings,admin')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\CurrencySettingController::class, 'index'])->name('index');
            Route::post('/', [\Modules\Admin\App\Http\Controllers\CurrencySettingController::class, 'update'])->name('update');
        });

        // Advanced Admin Booking Management
        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'index'])
                ->middleware('permission:view-bookings,admin')
                ->name('index');
            Route::get('/{booking}', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'show'])
                ->middleware('permission:view-bookings,admin')
                ->name('show');
            Route::get('/{booking}/edit', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'edit'])
                ->middleware('permission:manage-bookings,admin')
                ->name('edit');
            Route::get('/{booking}/categories/{category}/services', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'categoryServices'])
                ->middleware('permission:manage-bookings,admin')
                ->name('category-services');
            Route::put('/{booking}', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'update'])
                ->middleware('permission:manage-bookings,admin')
                ->name('update');
            Route::post('/{booking}/services', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'addService'])
                ->middleware('permission:manage-bookings,admin')
                ->name('services.add');
            Route::post('/{booking}/items', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'addItem'])
                ->middleware('permission:manage-bookings,admin')
                ->name('items.add');
            Route::delete('/{booking}/items/{item}', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'removeItem'])
                ->middleware('permission:manage-bookings,admin')
                ->name('items.remove');
            Route::post('/{booking}/status', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'updateStatus'])
                ->middleware('permission:manage-bookings,admin')
                ->name('status.update');
            Route::post('/{booking}/status/advance', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'advanceStatus'])
                ->middleware('permission:manage-bookings,admin')
                ->name('status.advance');
            Route::post('/{booking}/cancel', [\Modules\Admin\App\Http\Controllers\BookingManagementController::class, 'cancel'])
                ->middleware('permission:cancel-bookings,admin')
                ->name('cancel');
        });
    });
});

