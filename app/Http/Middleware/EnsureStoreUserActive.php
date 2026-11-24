<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $storeUser = Auth::guard('store')->user();

        if (! $storeUser || ! $storeUser->isEligibleToLogin()) {
            Auth::guard('store')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Store account is inactive or unauthorized.'], 403);
            }

            return redirect()->route('store.login')->withErrors([
                'login' => 'Your store account or assigned store is currently inactive.',
            ]);
        }

        return $next($request);
    }
}
