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
    });
});

