<?php

namespace App\Http\Controllers;

use App\Mail\CustomerOtpMail;
use App\Models\CustomerEmailOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    protected function throttleKey(string $email, string $ip): string
    {
        return 'customer-otp:'.sha1(strtolower($email).'|'.$ip);
    }

    protected function issueAndSendOtp(Request $request, string $email): ?string
    {
        $key = $this->throttleKey($email, (string) $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return "Too many verification requests. Please try again in {$seconds} seconds.";
        }

        $activeOtp = CustomerEmailOtp::where('email', $email)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($activeOtp && $activeOtp->resend_available_at && $activeOtp->resend_available_at->isFuture()) {
            $wait = (int) now()->diffInSeconds($activeOtp->resend_available_at, false);
            $wait = max(1, $wait);

            return "Please wait {$wait} seconds before requesting a new verification code.";
        }

        RateLimiter::hit($key, 600);

        // Invalidate any previous unconsumed OTPs for this email
        CustomerEmailOtp::where('email', $email)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $record = CustomerEmailOtp::create([
            'email' => $email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(CustomerEmailOtp::EXPIRY_MINUTES),
            'attempts' => 0,
            'resend_available_at' => now()->addSeconds(CustomerEmailOtp::RESEND_COOLDOWN_SECONDS),
            'ip_address' => $request->ip(),
        ]);

        try {
            Mail::to($email)->send(new CustomerOtpMail($otp, CustomerEmailOtp::EXPIRY_MINUTES));
        } catch (\Throwable) {
            $record->delete();

            return 'Unable to send verification email right now. Please check your mail configuration or try again.';
        }

        return null;
    }

    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect('/');
        }

        return view('auth.login');
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:160'],
        ]);

        $email = strtolower(trim($validated['email']));
        if ($error = $this->issueAndSendOtp($request, $email)) {
            return back()->withErrors(['email' => $error])->withInput();
        }

        $request->session()->put('otp_pending_email', $email);

        return redirect()->route('customer.otp.show')
            ->with('status', 'A 6-digit verification code has been sent to your email address.');
    }

    public function showOtpForm(Request $request): View|RedirectResponse
    {
        $email = (string) $request->session()->get('otp_pending_email', '');
        if ($email === '') {
            return redirect()->route('login');
        }

        $latest = CustomerEmailOtp::where('email', $email)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        $cooldownSeconds = 0;
        if ($latest && $latest->resend_available_at && $latest->resend_available_at->isFuture()) {
            $cooldownSeconds = max(0, (int) now()->diffInSeconds($latest->resend_available_at, false));
        }

        return view('auth.verify-otp', [
            'email' => $email,
            'cooldownSeconds' => $cooldownSeconds,
        ]);
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $email = (string) $request->session()->get('otp_pending_email', '');
        if ($email === '') {
            return redirect()->route('login');
        }

        if ($error = $this->issueAndSendOtp($request, $email)) {
            return back()->withErrors(['otp' => $error]);
        }

        return redirect()->route('customer.otp.show')
            ->with('status', 'A new 6-digit verification code has been sent to your email.');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $email = (string) $request->session()->get('otp_pending_email', '');
        if ($email === '') {
            return redirect()->route('login');
        }

        $rawOtp = (string) $request->input('otp', '');
        if ($rawOtp === '' && is_array($request->input('digits'))) {
            $rawOtp = implode('', $request->input('digits'));
        }
        $rawOtp = preg_replace('/\D+/', '', $rawOtp) ?? '';

        if (strlen($rawOtp) !== 6) {
            return back()->withErrors(['otp' => 'Please enter the complete 6-digit verification code.']);
        }

        $record = CustomerEmailOtp::where('email', $email)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $record) {
            return back()->withErrors(['otp' => 'No active verification code found. Please request a new code.']);
        }

        if ($record->isExpired()) {
            $record->update(['consumed_at' => now()]);

            return back()->withErrors(['otp' => 'This verification code has expired. Please request a new code.']);
        }

        if ($record->hasExceededAttempts()) {
            $record->update(['consumed_at' => now()]);

            return back()->withErrors(['otp' => 'Maximum verification attempts exceeded. Please request a new code.']);
        }

        $record->increment('attempts');

        if (! Hash::check($rawOtp, $record->otp_hash)) {
            $remaining = max(0, CustomerEmailOtp::MAX_ATTEMPTS - $record->attempts);

            return back()->withErrors([
                'otp' => $remaining > 0
                    ? "Invalid verification code. {$remaining} attempt(s) remaining."
                    : 'Maximum verification attempts exceeded. Please request a new code.',
            ]);
        }

        // Single-use OTP: consume immediately to prevent replay
        $record->update(['consumed_at' => now()]);
        $request->session()->forget('otp_pending_email');

        $existingUser = User::where('email', $email)->first();

        if ($existingUser && $existingUser->is_blocked) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been suspended. Please contact support.',
            ]);
        }

        if ($existingUser && $existingUser->hasCompletedProfile()) {
            if (! $existingUser->email_verified_at) {
                $existingUser->forceFill(['email_verified_at' => now()])->save();
            }

            $bookingDraft = $request->session()->get('booking');
            Auth::guard('web')->login($existingUser, true);
            $request->session()->regenerate();
            if ($bookingDraft !== null) {
                $request->session()->put('booking', $bookingDraft);
            }

            return redirect()->intended(url('/'));
        }

        $request->session()->put('otp_verified_email', $email);

        return redirect()->route('customer.register.show');
    }

    public function showRegister(Request $request): View|RedirectResponse
    {
        $email = (string) $request->session()->get('otp_verified_email', '');
        if ($email === '') {
            return redirect()->route('login');
        }

        $countryList = \App\Support\CountryDialCodes::all();

        return view('auth.complete-profile', [
            'email' => $email,
            'countryList' => $countryList,
        ]);
    }

    public function completeRegister(Request $request): RedirectResponse
    {
        $email = (string) $request->session()->get('otp_verified_email', '');
        if ($email === '') {
            return redirect()->route('login');
        }

        $allowedDialCodes = collect(\App\Support\CountryDialCodes::all())->pluck('dial')->unique()->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'country_code' => ['required', 'string', Rule::in($allowedDialCodes)],
            'phone' => ['required', 'string', 'regex:/^[0-9\s\-]{6,20}$/'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
        ]);

        $user = User::firstOrNew(['email' => $email]);
        $user->name = trim($validated['name']);
        $user->country_code = $validated['country_code'];
        $user->phone = preg_replace('/\s+/', '', trim($validated['phone']));
        $user->gender = $validated['gender'] ?: null;
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->profile_completed_at = now();
        $user->save();

        $bookingDraft = $request->session()->get('booking');
        $request->session()->forget('otp_verified_email');
        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();
        if ($bookingDraft !== null) {
            $request->session()->put('booking', $bookingDraft);
        }

        return redirect()->intended(url('/'))->with('status', 'Welcome to FreshGo Laundry!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
