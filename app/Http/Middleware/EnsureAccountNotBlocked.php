<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountNotBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $user = $guard ? Auth::guard($guard)->user() : $request->user();

        if ($user && $user->is_blocked) {
            if ($guard) {
                Auth::guard($guard)->logout();
            } else {
                Auth::logout();
            }

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Your account has been blocked. Please contact support.';
            if ($guard === 'admin') {
                $message = 'Your administrator account has been blocked. Please contact system support.';
                return redirect()->route('admin.login')->withErrors(['email' => $message]);
            }

            if (Route::has('login')) {
                return redirect()->route('login')->withErrors(['email' => $message]);
            }

            return redirect('/')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
