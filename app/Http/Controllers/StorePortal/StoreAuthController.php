<?php

namespace App\Http\Controllers\StorePortal;

use App\Http\Controllers\Controller;
use App\Models\StoreUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class StoreAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('store-portal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $throttleKey = 'store-login:' . Str::lower(trim($validated['login'])) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'login' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('login');
        }

        $identifier = trim($validated['login']);
        $storeUser = null;

        if (str_contains($identifier, '@')) {
            $storeUser = StoreUser::with('store')
                ->whereRaw('LOWER(email) = ?', [Str::lower($identifier)])
                ->first();
        } else {
            $normalized = StoreUser::normalizePhone($identifier);
            if ($normalized !== null) {
                $matches = StoreUser::with('store')
                    ->where('phone_normalized', $normalized)
                    ->get();
                if ($matches->count() === 1) {
                    $storeUser = $matches->first();
                }
            }
        }

        if (! $storeUser || ! Hash::check($validated['password'], $storeUser->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'login' => 'Invalid email/phone or password.',
            ])->onlyInput('login');
        }

        if (! $storeUser->isEligibleToLogin()) {
            return back()->withErrors([
                'login' => 'This store account or its assigned store is currently inactive.',
            ])->onlyInput('login');
        }

        RateLimiter::clear($throttleKey);

        Auth::guard('store')->login($storeUser, $request->boolean('remember'));
        $request->session()->regenerate();
        $storeUser->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('store.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('store')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('store.login');
    }

    public function showForgotPassword(): View
    {
        return view('store-portal.auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);
        Password::broker('store_users')->sendResetLink(['email' => $validated['email']]);

        return back()->with('status', 'If a matching store account exists, a password reset link has been sent.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('store-portal.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::broker('store_users')->reset(
            $validated,
            function (StoreUser $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('store.login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
