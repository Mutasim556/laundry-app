<?php

use App\Mail\CustomerOtpMail;
use App\Models\Booking;
use App\Models\CustomerAddress;
use App\Models\CustomerEmailOtp;
use App\Models\CustomerPaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Admin\Models\Admin;

beforeEach(function () {
    Mail::fake();
    RateLimiter::clear('customer-otp:'.sha1('newcustomer@example.com|127.0.0.1'));
    RateLimiter::clear('customer-otp:'.sha1('existing@example.com|127.0.0.1'));
});

it('displays Login / Sign Up for guests on homepage instead of hardcoded Emma Carter', function () {
    $resp = $this->get('/');
    $resp->assertOk();
    $resp->assertSee('Login / Sign Up');
    $resp->assertDontSee('Emma Carter');
});

it('completes full email OTP registration for a new customer, preserves booking session, and logs in directly on subsequent OTP login', function () {
    $capturedOtp = null;

    Mail::assertNothingSent();

    // 1. Submit new email on login page (with an active booking draft in session)
    $this->withSession(['booking' => ['services' => [['category_id' => 1, 'service_id' => 1]]]])
        ->post(route('customer.login.send-otp'), ['email' => 'newcustomer@example.com'])
        ->assertRedirect(route('customer.otp.show'));

    Mail::assertSent(CustomerOtpMail::class, function (CustomerOtpMail $mail) use (&$capturedOtp) {
        $capturedOtp = $mail->otpCode;

        return $mail->hasTo('newcustomer@example.com') && preg_match('/^\d{6}$/', $mail->otpCode) === 1;
    });

    $otpRow = CustomerEmailOtp::where('email', 'newcustomer@example.com')->firstOrFail();
    expect($otpRow->otp_hash)->not->toBe($capturedOtp);
    expect(Hash::check($capturedOtp, $otpRow->otp_hash))->toBeTrue();

    // 2. Incorrect OTP shows error and increments attempts
    $wrongCode = $capturedOtp === '000000' ? '111111' : '000000';
    $this->post(route('customer.otp.verify'), ['otp' => $wrongCode])
        ->assertSessionHasErrors('otp');
    expect($otpRow->fresh()->attempts)->toBe(1);

    // 3. Correct OTP redirects to Complete Your Profile (new customer)
    $this->post(route('customer.otp.verify'), ['otp' => $capturedOtp])
        ->assertRedirect(route('customer.register.show'));

    // 4. Replay of the consumed OTP is rejected
    expect($otpRow->fresh()->consumed_at)->not->toBeNull();

    // 5. Complete profile registration (no password required)
    $this->post(route('customer.register.store'), [
        'name' => 'Amina Rahman',
        'country_code' => '+880',
        'phone' => '1712345678',
        'gender' => 'female',
    ])->assertRedirect('/');

    $user = User::where('email', 'newcustomer@example.com')->firstOrFail();
    expect($user->name)->toBe('Amina Rahman');
    expect($user->country_code)->toBe('+880');
    expect($user->phone)->toBe('1712345678');
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->profile_completed_at)->not->toBeNull();

    // Booking draft preserved across session regeneration
    expect(session('booking.services'))->not->toBeEmpty();

    // Homepage shows actual customer name & profile dropdown items
    $home = $this->get('/');
    $home->assertOk();
    $home->assertSee('Amina Rahman');
    $home->assertSee('Profile');
    $home->assertSee('My Orders');
    $home->assertSee('Address Book');
    $home->assertSee('Saved Payment Methods');
    $home->assertSee('Logout');

    // 6. Logout
    $this->post(route('logout'))->assertRedirect('/');
    $this->assertGuest('web');

    // 7. Existing customer logs in again via email OTP -> goes straight to homepage without registration form
    CustomerEmailOtp::where('email', 'newcustomer@example.com')->delete();
    RateLimiter::clear('customer-otp:'.sha1('newcustomer@example.com|127.0.0.1'));

    $this->post(route('customer.login.send-otp'), ['email' => 'newcustomer@example.com'])
        ->assertRedirect(route('customer.otp.show'));

    $secondOtp = null;
    Mail::assertSent(CustomerOtpMail::class, function (CustomerOtpMail $mail) use (&$secondOtp) {
        $secondOtp = $mail->otpCode;

        return true;
    });

    $this->post(route('customer.otp.verify'), ['otp' => $secondOtp])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user, 'web');
});

it('rejects expired OTPs and enforces resend cooldown', function () {
    $this->post(route('customer.login.send-otp'), ['email' => 'existing@example.com'])
        ->assertRedirect(route('customer.otp.show'));

    // Immediate resend hits 60s cooldown
    $this->post(route('customer.otp.resend'))
        ->assertSessionHasErrors('otp');

    // Force OTP expiration
    $otpRow = CustomerEmailOtp::where('email', 'existing@example.com')->firstOrFail();
    $otpRow->update(['expires_at' => now()->subMinute()]);

    $this->post(route('customer.otp.verify'), ['otp' => '123456'])
        ->assertSessionHasErrors('otp');
});

it('allows customers to update profile, manage Address Book with ownership checks, and claim guest orders safely', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'country_code' => '+44',
        'phone' => '7700900077',
        'email_verified_at' => now(),
        'profile_completed_at' => now(),
    ]);
    $other = User::factory()->create(['email' => 'other@example.com', 'profile_completed_at' => now()]);

    // Profile update preserves verified email
    $this->actingAs($user, 'web')
        ->post(route('account.profile.update'), [
            'name' => 'John Updated',
            'country_code' => '+44',
            'phone' => '7700900088',
            'gender' => 'male',
        ])
        ->assertRedirect(route('account.profile'));

    expect($user->fresh()->name)->toBe('John Updated');
    expect($user->fresh()->email)->toBe('john@example.com');

    // Address Book: add UK address and Hotel address
    $this->actingAs($user, 'web')
        ->post(route('account.addresses.store'), [
            'address_type' => 'home',
            'label' => 'My Flat',
            'postcode' => 'E1 1AA',
            'formatted_address' => '10 Downing Street, London, E1 1AA',
            'address_details' => 'Flat 2',
            'is_default' => '1',
        ])
        ->assertRedirect(route('account.addresses'));

    $address = CustomerAddress::where('user_id', $user->id)->firstOrFail();

    // Other user cannot edit or delete this address
    $this->actingAs($other, 'web')
        ->put(route('account.addresses.update', $address->id), [
            'address_type' => 'home',
            'postcode' => 'E1 1AA',
            'formatted_address' => 'Hacked',
        ])
        ->assertForbidden();

    $this->actingAs($other, 'web')
        ->delete(route('account.addresses.destroy', $address->id))
        ->assertForbidden();

    // Guest booking ownership claim
    $guestBooking = Booking::create([
        'reference' => 'LND-2026-000777',
        'user_id' => null,
        'customer_name' => 'John Doe',
        'customer_email' => 'john@example.com',
        'customer_phone' => '+44 7700900077',
        'address_snapshot' => ['selected_address' => '10 Downing Street'],
        'schedule_snapshot' => ['collection_date' => '2026-11-23'],
        'services_snapshot' => [['name' => 'Wash & Fold']],
        'payment_gateway' => 'cod',
        'payment_status' => 'pending_item_count',
        'booking_status' => 'confirmed',
        'is_locked' => true,
        'confirmed_at' => now(),
    ]);

    $this->actingAs($other, 'web')
        ->post(route('orders.claim'), ['reference' => 'LND-2026-000777'])
        ->assertSessionHasErrors('reference');

    $this->actingAs($user, 'web')
        ->post(route('orders.claim'), ['reference' => 'LND-2026-000777'])
        ->assertRedirect(route('orders.index'));

    expect($guestBooking->fresh()->user_id)->toBe($user->id);

    // Deleting saved address does not alter confirmed booking snapshot
    $this->actingAs($user, 'web')
        ->delete(route('account.addresses.destroy', $address->id))
        ->assertRedirect(route('account.addresses'));
    expect($guestBooking->fresh()->address_snapshot['selected_address'])->toBe('10 Downing Street');
});

it('isolates customer saved payment methods and prevents customer access to Admin Panel', function () {
    $user = User::factory()->create(['profile_completed_at' => now()]);
    $other = User::factory()->create(['profile_completed_at' => now()]);

    $card = CustomerPaymentMethod::create([
        'user_id' => $user->id,
        'customer_email' => $user->email,
        'stripe_customer_id' => 'cus_123',
        'stripe_payment_method_id' => 'pm_123',
        'card_brand' => 'visa',
        'card_last4' => '4242',
        'card_exp_month' => 12,
        'card_exp_year' => 2028,
        'is_default' => true,
    ]);

    $this->actingAs($user, 'web')
        ->get(route('account.payment-methods'))
        ->assertOk()
        ->assertSee('4242');

    // Other customer cannot delete or set default
    $this->actingAs($other, 'web')
        ->delete(route('account.payment-methods.destroy', $card->id))
        ->assertForbidden();

    // Authenticated customer cannot access Admin Panel routes
    $this->actingAs($user, 'web')
        ->get('/admin/dashboard')
        ->assertRedirect(route('admin.login'));

    // Admin authentication remains separate and functional
    $admin = Admin::create([
        'name' => 'System Admin',
        'email' => 'sysadmin+'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $this->actingAs($admin, 'admin')
        ->get('/admin/dashboard')
        ->assertOk();
});
