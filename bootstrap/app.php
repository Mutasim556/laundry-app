<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('store')
                ->name('store.')
                ->group(base_path('routes/store.php'));

            Route::middleware('web')
                ->prefix('delivery-person')
                ->name('delivery-person.')
                ->group(base_path('routes/delivery_person.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'paypal/webhook',
        ]);

        // When unauthenticated guests (or expired sessions) visit protected routes:
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }
            if ($request->is('store') || $request->is('store/*')) {
                return route('store.login');
            }
            if ($request->is('delivery-person') || $request->is('delivery-person/*')) {
                return route('delivery-person.login');
            }

            return Route::has('login') ? route('login') : '/';
        });

        // When already-authenticated users hit guest routes (like login pages):
        $middleware->redirectUsersTo(function (Request $request) {
            if (($request->is('admin') || $request->is('admin/*')) && Auth::guard('admin')->check()) {
                return route('admin.dashboard');
            }
            if (($request->is('store') || $request->is('store/*')) && Auth::guard('store')->check()) {
                return route('store.dashboard');
            }
            if (($request->is('delivery-person') || $request->is('delivery-person/*')) && Auth::guard('delivery_person')->check()) {
                return route('delivery-person.dashboard');
            }
            if (Auth::guard('admin')->check() && ! $request->is('store*') && ! $request->is('delivery-person*')) {
                return route('admin.dashboard');
            }

            return Route::has('dashboard') ? route('dashboard') : '/';
        });

        $middleware->alias([
            'not_blocked' => \App\Http\Middleware\EnsureAccountNotBlocked::class,
            'store.active' => \App\Http\Middleware\EnsureStoreUserActive::class,
            'delivery_person.active' => \App\Http\Middleware\EnsureDeliveryPersonActive::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
