<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\Models\DeliveryPerson;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeliveryPersonActive
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var DeliveryPerson|null $person */
        $person = Auth::guard('delivery_person')->user();

        if (! $person || ! $person->isEligibleToLogin()) {
            Auth::guard('delivery_person')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('delivery-person.login')
                ->withErrors(['login' => 'Your Delivery Person portal account is inactive or disabled.']);
        }

        return $next($request);
    }
}
