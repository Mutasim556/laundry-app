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
    });
});

