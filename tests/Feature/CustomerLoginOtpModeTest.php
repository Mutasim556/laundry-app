<?php

use App\Mail\CustomerOtpMail;
use App\Models\CustomerEmailOtp;
use App\Models\HomepageContent;
use App\Models\User;
use App\Services\CustomerLoginOtpSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function makeOtpCmsAdmin(bool $withPerm = true): Admin
{
    $admin = Admin::create([
        'name' => 'OTP Settings Admin',
        'email' => 'otpadmin' . random_int(100, 9999) . '@example.com',
        'password' => bcrypt('Admin2026!'),
        'status' => true,
    ]);

    if ($withPerm) {
        Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'admin']);
        $admin->givePermissionTo('manage-settings');
    }

    return $admin;
}

function makeCompletedCustomer(string $email = 'cust@example.com'): User
{
    return User::create([
        'name' => 'Test Customer',
        'email' => $email,
        'country_code' => '+44',
        'phone' => '7700900111',
        'email_verified_at' => now(),
        'profile_completed_at' => now(),
        'is_blocked' => false,
    ]);
}

test('admin authorization and server-side testing gate are enforced on customer login otp settings', function () {
    $unauth = makeOtpCmsAdmin(false);
    $admin = makeOtpCmsAdmin(true);

    $this->actingAs($unauth, 'admin')->get(route('admin.website-manager.customer-login-otp.index'))->assertForbidden();
    $this->actingAs($unauth, 'admin')->postJson(route('admin.website-manager.customer-login-otp.save'), ['mode' => 'email'])->assertForbidden();

    // Gate disabled by default (CUSTOMER_TEST_OTP_ALLOWED=false)
    config(['customer_otp.testing_allowed' => false]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.website-manager.customer-login-otp.index'))
        ->assertOk()
        ->assertSee('Customer Login OTP Settings')
        ->assertDontSee('123456');

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.customer-login-otp.save'), ['mode' => 'testing'])
        ->assertStatus(422);

    // Forged DB setting cannot bypass gate when testing_allowed is false
    HomepageContent::updateOrCreate(
        ['slug' => CustomerLoginOtpSettingService::PAGE_SLUG],
        ['draft_payload' => ['mode' => 'testing'], 'published_payload' => ['mode' => 'testing']]
    );
    Cache::forget(CustomerLoginOtpSettingService::CACHE_KEY);

    expect(app(CustomerLoginOtpSettingService::class)->getEffectiveMode())
        ->toBe(CustomerLoginOtpSettingService::MODE_EMAIL);

    // Gate also blocked in production even if testing_allowed=true
    config(['customer_otp.testing_allowed' => true]);
    app()->detectEnvironment(fn () => 'production');
    expect(app(CustomerLoginOtpSettingService::class)->isEnvironmentAuthorizedForTesting())->toBeFalse();
    expect(app(CustomerLoginOtpSettingService::class)->getEffectiveMode())->toBe(CustomerLoginOtpSettingService::MODE_EMAIL);
    app()->detectEnvironment(fn () => 'testing');
});

test('email otp mode sends real email, logs customer in, and enforces expiry, attempts, and single-use', function () {
    Mail::fake();
    config(['customer_otp.testing_allowed' => false]);
    $customer = makeCompletedCustomer('emailmode@example.com');

    $this->post(route('customer.login.send-otp'), ['email' => 'emailmode@example.com'])
        ->assertRedirect(route('customer.otp.show'));

    Mail::assertSent(CustomerOtpMail::class, 1);

    $otpRecord = CustomerEmailOtp::where('email', 'emailmode@example.com')->latest('id')->first();
    expect($otpRecord)->not->toBeNull();
    expect($otpRecord->mode)->toBe(CustomerLoginOtpSettingService::MODE_EMAIL);

    // Invalid OTP rejected
    $this->post(route('customer.otp.verify'), ['otp' => '000000'])
        ->assertSessionHasErrors('otp');

    // Replace hash with known OTP to verify login & single-use
    $otpRecord->update(['otp_hash' => Hash::make('654321')]);

    $this->post(route('customer.otp.verify'), ['otp' => '654321'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($customer, 'web');
    $otpRecord->refresh();
    expect($otpRecord->consumed_at)->not->toBeNull();
});

test('testing otp mode skips email, never exposes 123456 in public html, verifies 123456, and respects security checks', function () {
    Mail::fake();
    config(['customer_otp.testing_allowed' => true, 'customer_otp.test_otp_code' => '123456']);
    $admin = makeOtpCmsAdmin(true);
    $customer = makeCompletedCustomer('testmode@example.com');

    // Admin enables testing mode
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.customer-login-otp.save'), ['mode' => 'testing'])
        ->assertOk()
        ->assertJsonPath('effective_mode', 'testing');

    // Public login page shows subtle testing notice without exposing 123456
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Testing environment')
        ->assertDontSee('123456');

    // Cannot verify 123456 without prior pending challenge
    $this->withSession(['otp_pending_email' => 'testmode@example.com'])
        ->post(route('customer.otp.verify'), ['otp' => '123456'])
        ->assertSessionHasErrors('otp');

    // Request OTP -> creates pending challenge without sending email
    $this->post(route('customer.login.send-otp'), ['email' => 'testmode@example.com'])
        ->assertRedirect(route('customer.otp.show'));

    Mail::assertNothingSent();

    $this->get(route('customer.otp.show'))
        ->assertOk()
        ->assertSee('Testing environment')
        ->assertDontSee('123456');

    $challenge = CustomerEmailOtp::where('email', 'testmode@example.com')->latest('id')->first();
    expect($challenge->mode)->toBe(CustomerLoginOtpSettingService::MODE_TESTING);

    // Wrong OTP fails
    $this->post(route('customer.otp.verify'), ['otp' => '999999'])
        ->assertSessionHasErrors('otp');

    // Correct test OTP 123456 logs in
    $this->post(route('customer.otp.verify'), ['otp' => '123456'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($customer, 'web');
    expect($challenge->fresh()->consumed_at)->not->toBeNull();

    // Consumed challenge cannot be reused
    Auth::guard('web')->logout();
    $this->withSession(['otp_pending_email' => 'testmode@example.com'])
        ->post(route('customer.otp.verify'), ['otp' => '123456'])
        ->assertSessionHasErrors('otp');
});

test('testing otp enforces expiry, attempt limits, and mode switching invalidates incompatible challenges', function () {
    Mail::fake();
    config(['customer_otp.testing_allowed' => true, 'customer_otp.test_otp_code' => '123456']);
    $admin = makeOtpCmsAdmin(true);
    makeCompletedCustomer('switch@example.com');

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.customer-login-otp.save'), ['mode' => 'testing'])
        ->assertOk();

    // Issue testing challenge
    $this->post(route('customer.login.send-otp'), ['email' => 'switch@example.com'])
        ->assertRedirect(route('customer.otp.show'));

    $challenge = CustomerEmailOtp::where('email', 'switch@example.com')->latest('id')->first();

    // Switch mode back to email -> invalidates testing challenge
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.customer-login-otp.save'), ['mode' => 'email'])
        ->assertOk()
        ->assertJsonPath('effective_mode', 'email');

    expect($challenge->fresh()->consumed_at)->not->toBeNull();

    $this->withSession(['otp_pending_email' => 'switch@example.com'])
        ->post(route('customer.otp.verify'), ['otp' => '123456'])
        ->assertSessionHasErrors('otp');

    // Switch back to testing and verify expired challenge fails
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.customer-login-otp.save'), ['mode' => 'testing'])
        ->assertOk();

    $expired = CustomerEmailOtp::create([
        'email' => 'switch@example.com',
        'mode' => CustomerLoginOtpSettingService::MODE_TESTING,
        'otp_hash' => Hash::make('123456'),
        'expires_at' => now()->subMinute(),
        'attempts' => 0,
        'resend_available_at' => now()->subMinute(),
    ]);

    $this->withSession(['otp_pending_email' => 'switch@example.com'])
        ->post(route('customer.otp.verify'), ['otp' => '123456'])
        ->assertSessionHasErrors('otp');

    expect($expired->fresh()->consumed_at)->not->toBeNull();

    // Attempt limit check
    $maxed = CustomerEmailOtp::create([
        'email' => 'switch@example.com',
        'mode' => CustomerLoginOtpSettingService::MODE_TESTING,
        'otp_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(5),
        'attempts' => CustomerEmailOtp::MAX_ATTEMPTS,
        'resend_available_at' => now()->addMinutes(1),
    ]);

    $this->withSession(['otp_pending_email' => 'switch@example.com'])
        ->post(route('customer.otp.verify'), ['otp' => '123456'])
        ->assertSessionHasErrors('otp');

    expect($maxed->fresh()->consumed_at)->not->toBeNull();
});
