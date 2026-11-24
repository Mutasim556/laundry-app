<?php

namespace App\Http\Controllers\DeliveryPersonPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Modules\Admin\Models\DeliveryPerson;

class DeliveryPersonAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('delivery-person-portal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $throttleKey = 'dp-login:' . Str::lower(trim($validated['login'])) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'login' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('login');
        }

        $identifier = trim($validated['login']);
        $person = null;

        if (str_contains($identifier, '@')) {
            $person = DeliveryPerson::query()
                ->whereRaw('LOWER(email) = ?', [Str::lower($identifier)])
                ->first();
        } else {
            $normalized = DeliveryPerson::normalizePhone($identifier);
            if ($normalized !== null) {
                $matches = DeliveryPerson::query()
                    ->where(function ($q) use ($normalized, $identifier) {
                        $q->where('phone_normalized', $normalized)
                            ->orWhere('phone', $identifier);
                    })
                    ->get();
                if ($matches->count() === 1) {
                    $person = $matches->first();
                }
            }
        }

        if (! $person || empty($person->password) || ! Hash::check($validated['password'], $person->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'login' => 'Invalid email/phone or password.',
            ])->onlyInput('login');
        }

        if (! $person->isEligibleToLogin()) {
            return back()->withErrors([
                'login' => 'Your Delivery Person portal account is inactive or not enabled.',
            ])->onlyInput('login');
        }

        RateLimiter::clear($throttleKey);

        Auth::guard('delivery_person')->login($person, $request->boolean('remember'));
        $request->session()->regenerate();
        $person->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('delivery-person.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('delivery_person')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('delivery-person.login');
    }

    public function showForgotPassword(): View
    {
        return view('delivery-person-portal.auth.forgot-password');
    }

    public function sendResetNotice(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string', 'max:190'],
        ]);

        return back()->with('status', 'If an active Delivery Person account matches that email or phone, please contact your administrator to reset your portal password securely.');
    }

    public function showChangePassword(): View
    {
        return view('delivery-person-portal.auth.change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var DeliveryPerson $person */
        $person = Auth::guard('delivery_person')->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        if (! Hash::check($validated['current_password'], $person->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $person->forceFill(['password' => $validated['password']])->save();

        return back()->with('success', 'Your portal password has been updated.');
    }
}
