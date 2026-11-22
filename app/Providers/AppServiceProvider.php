<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\CustomerAddress;
use App\Policies\BookingPolicy;
use App\Policies\CustomerAddressPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(CustomerAddress::class, CustomerAddressPolicy::class);

        // Grant super-admin role all permissions implicitly using Gate::before
        Gate::before(function ($user, string $ability) {
            return method_exists($user, 'hasRole') && $user->hasRole('super-admin', 'admin') ? true : null;
        });
    }
}
