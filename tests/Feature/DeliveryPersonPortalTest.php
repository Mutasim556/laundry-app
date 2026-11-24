<?php

use App\Mail\CollectionOtpMail;
use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingCollectionOtp;
use App\Models\BookingCustomerPayment;
use App\Models\BookingOperationalItem;
use App\Models\DeliveryPersonCashTransaction;
use App\Models\DeliveryPersonSettlement;
use App\Services\DeliveryPersonCashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-bookings', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-bookings', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'view-delivery-settlements', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-delivery-settlements', 'guard_name' => 'admin']);
});

function createPortalAdmin(): Admin
{
    $admin = Admin::create([
        'name' => 'Portal Admin',
        'email' => 'portaladmin' . random_int(1000, 9999) . '@example.com',
        'password' => bcrypt('Secret123!'),
        'is_active' => true,
    ]);
    $admin->givePermissionTo(['view-bookings', 'manage-bookings', 'view-delivery-settlements', 'manage-delivery-settlements']);

    return $admin;
}

function createPortalDeliveryPerson(string $name, bool $portalEnabled = true, string $workStatus = 'active'): DeliveryPerson
{
    $phone = '077009' . random_int(10000, 99999);

    return DeliveryPerson::create([
        'code' => 'DP-' . random_int(1000, 9999),
        'full_name' => $name,
        'date_of_birth' => '1990-01-01',
        'email' => strtolower(str_replace(' ', '.', $name)) . random_int(100, 999) . '@example.com',
        'phone_country_code' => '+44',
        'phone' => $phone,
        'phone_normalized' => DeliveryPerson::normalizePhone($phone, '+44'),
        'address_line_1' => '10 High St',
        'city' => 'Manchester',
        'residential_postcode' => 'M1 2PF',
        'work_status' => $workStatus,
        'availability_status' => 'available',
        'currency' => 'GBP',
        'portal_access_enabled' => $portalEnabled,
        'password' => 'DriverPass123!',
    ]);
}

function createPortalBooking(?DeliveryPerson $collectionDp = null, ?DeliveryPerson $finalDp = null, int $unitPriceMinor = 2500): array
{
    $store = Store::create([
        'name' => 'Portal Store ' . random_int(100, 999),
        'slug' => 'portal-store-' . random_int(1000, 9999),
        'store_code' => 'PST-' . random_int(1000, 9999),
        'email' => 'pstore' . random_int(1000, 9999) . '@example.com',
        'phone' => '161496' . random_int(1000, 9999),
        'address_line_1' => '5 Store St',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'country_code' => 'GB',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => 20.00,
    ]);

    $booking = Booking::create([
        'reference' => 'LND-2026-' . random_int(100000, 999999),
        'customer_name' => 'Alice Traveler',
        'customer_email' => 'alice@example.com',
        'customer_phone' => '+447700900555',
        'address_snapshot' => [
            'address_line_1' => '100 Portland St',
            'city' => 'Manchester',
            'postcode' => 'M1 4GX',
            'hotel_name' => 'Midland Hotel',
            'room_number' => '408',
            'access_instructions' => 'Leave at concierge desk if unavailable',
        ],
        'schedule_snapshot' => [
            'collection_date' => '2026-11-24',
            'collection_slot' => '09:00 - 11:00',
            'collection_instructions' => 'Ring bell twice',
            'delivery_instructions' => 'Deliver before 6 PM',
        ],
        'services_snapshot' => [['category_id' => 1, 'name' => 'Dry Cleaning']],
        'contact_snapshot' => ['notes' => 'Call on arrival'],
        'currency' => 'GBP',
        'estimated_amount_minor' => ($unitPriceMinor * 2) + 500,
        'service_fee_minor' => 200,
        'collection_delivery_fee_minor' => 300,
        'tip_amount_minor' => 0,
        'discount_minor' => 0,
        'tax_minor' => 0,
        'final_amount_minor' => ($unitPriceMinor * 2) + 500,
        'payment_status' => 'pending',
        'payment_gateway' => 'cod',
        'booking_status' => Booking::STATUS_FINALIZED,
        'completed_operational_stages' => [],
        'assigned_store_id' => $store->id,
        'version' => 1,
    ]);

    BookingStoreAssignment::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => '5 Store St, Manchester',
        'assigned_at' => now(),
        'is_current' => true,
    ]);

    $cat = ItemCategory::create(['name' => 'Dry Cleaning ' . random_int(100, 999), 'slug' => 'dc-' . random_int(1000, 9999), 'is_active' => true]);
    $srv = Service::create(['item_category_id' => $cat->id, 'name' => 'Suit Clean', 'slug' => 'suit-' . random_int(1000, 9999), 'pricing_mode' => 'fixed', 'price_minor' => $unitPriceMinor, 'is_active' => true]);

    $item = BookingOperationalItem::create([
        'booking_id' => $booking->id,
        'service_id' => $srv->id,
        'service_name' => $srv->name,
        'item_category_id' => $cat->id,
        'category_name' => $cat->name,
        'item_name' => 'Two-Piece Suit',
        'quantity' => 2,
        'pricing_mode' => 'fixed',
        'catalog_unit_price_minor' => $unitPriceMinor,
        'actual_unit_price_minor' => $unitPriceMinor,
        'line_total_minor' => $unitPriceMinor * 2,
        'currency' => 'GBP',
        'is_active' => true,
    ]);

    if ($collectionDp) {
        DeliveryAssignment::create([
            'assignment_reference' => 'ASG-COL-' . random_int(1000, 9999),
            'booking_id' => $booking->id,
            'delivery_person_id' => $collectionDp->id,
            'store_id' => $store->id,
            'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
            'status' => DeliveryAssignment::STATUS_ASSIGNED,
            'pickup_location' => '100 Portland St',
            'destination_location' => $store->name,
            'assigned_at' => now(),
            'rate_minor' => 150,
            'currency' => 'GBP',
        ]);
    }

    if ($finalDp) {
        DeliveryAssignment::create([
            'assignment_reference' => 'ASG-FIN-' . random_int(1000, 9999),
            'booking_id' => $booking->id,
            'delivery_person_id' => $finalDp->id,
            'store_id' => $store->id,
            'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
            'status' => DeliveryAssignment::STATUS_ASSIGNED,
            'pickup_location' => $store->name,
            'destination_location' => '100 Portland St',
            'assigned_at' => now(),
            'rate_minor' => 150,
            'currency' => 'GBP',
        ]);
    }

    return [$booking, $item];
}

it('authenticates Delivery Persons via email or phone, enforces active status, and supports admin portal access management', function () {
    $admin = createPortalAdmin();
    $driver = createPortalDeliveryPerson('Sam Driver', false);

    // Disabled portal access cannot login
    $this->post(route('delivery-person.login.submit'), [
        'login' => $driver->email,
        'password' => 'DriverPass123!',
    ])->assertSessionHasErrors('login');

    // Admin enables portal access & sets new password
    $this->actingAs($admin, 'admin')
        ->post(route('admin.delivery-persons.portal-access', $driver), [
            'portal_access_enabled' => '1',
            'password' => 'NewSecret123!',
            'password_confirmation' => 'NewSecret123!',
        ])
        ->assertSessionHas('success');

    // Login via email
    $this->post(route('delivery-person.login.submit'), [
        'login' => $driver->email,
        'password' => 'NewSecret123!',
    ])->assertRedirect(route('delivery-person.dashboard'));

    $this->post(route('delivery-person.logout'))->assertRedirect(route('delivery-person.login'));

    // Login via normalized phone number
    $this->post(route('delivery-person.login.submit'), [
        'login' => $driver->phone_normalized,
        'password' => 'NewSecret123!',
    ])->assertRedirect(route('delivery-person.dashboard'));

    $this->actingAs($driver->fresh(), 'delivery_person')
        ->get(route('delivery-person.dashboard'))
        ->assertOk()
        ->assertSee('Delivery Person Earnings Balance')
        ->assertSee('Cash in Hand');
});

it('enforces role-based authorization between Collection, Final Delivery, and unassigned Delivery Persons, and protects service categories on edit', function () {
    $colDp = createPortalDeliveryPerson('Col Person');
    $finDp = createPortalDeliveryPerson('Fin Person');
    $otherDp = createPortalDeliveryPerson('Other Person');

    [$booking, $item] = createPortalBooking($colDp, $finDp);

    // Unassigned driver gets 403
    $this->actingAs($otherDp, 'delivery_person')
        ->get(route('delivery-person.bookings.show', $booking))
        ->assertForbidden();

    // Collection driver sees full instructions, hotel/room, and edit controls
    $this->actingAs($colDp, 'delivery_person')
        ->get(route('delivery-person.bookings.show', $booking))
        ->assertOk()
        ->assertSee('CUSTOMER INSTRUCTIONS')
        ->assertSee('Midland Hotel')
        ->assertSee('Ring bell twice')
        ->assertSee('Send Collection OTP');

    // Final Delivery driver sees simplified view without OTP/payment/edit controls and gets 403 on edit/OTP/finalize
    $this->actingAs($finDp, 'delivery_person')
        ->get(route('delivery-person.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Final Delivery Assignment')
        ->assertDontSee('Send Collection OTP');

    $this->actingAs($finDp, 'delivery_person')
        ->get(route('delivery-person.bookings.edit', $booking))
        ->assertForbidden();

    $this->actingAs($finDp, 'delivery_person')
        ->post(route('delivery-person.bookings.otp.send', $booking))
        ->assertForbidden();

    // Collection driver cannot delete category / omit existing item
    $this->actingAs($colDp, 'delivery_person')
        ->put(route('delivery-person.bookings.update', $booking), [
            'items' => [],
            'remove_category_ids' => [1],
        ])
        ->assertForbidden();

    // Valid quantity update recalculates totals and creates audit log
    $this->actingAs($colDp, 'delivery_person')
        ->put(route('delivery-person.bookings.update', $booking), [
            'items' => [
                ['id' => $item->id, 'quantity' => 3, 'item_notes' => 'Pressed carefully'],
            ],
        ])
        ->assertRedirect(route('delivery-person.bookings.show', $booking));

    $booking->refresh();
    expect((int) $booking->final_amount_minor)->toBe(8000); // 3 * 2500 + 200 + 300
    expect(BookingAuditLog::where('booking_id', $booking->id)->where('action', 'collection_booking_edited_by_dp')->exists())->toBeTrue();
});

it('validates Collection OTP securely, records Cash in Hand vs Online Transfers, and supports Admin daily settlements', function () {
    Mail::fake();

    $admin = createPortalAdmin();
    $colDp = createPortalDeliveryPerson('Cash Collector');
    [$booking1] = createPortalBooking($colDp, null, 2500); // total 5500 (GBP 55.00)
    [$booking2] = createPortalBooking($colDp, null, 1500); // total 3500 (GBP 35.00)

    // Cannot finalize before OTP and payment
    $this->actingAs($colDp, 'delivery_person')
        ->post(route('delivery-person.bookings.finalize', $booking1))
        ->assertSessionHasErrors('finalize');

    // Send OTP -> Mail sent, plaintext never stored
    $capturedCode = null;
    $this->actingAs($colDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.otp.send', $booking1))
        ->assertOk()
        ->assertJsonMissing(['otp_code']);

    Mail::assertSent(CollectionOtpMail::class, function (CollectionOtpMail $mail) use (&$capturedCode) {
        $capturedCode = $mail->otpCode;

        return true;
    });
    expect($capturedCode)->toMatch('/^\d{6}$/');

    $otpRow = BookingCollectionOtp::where('booking_id', $booking1->id)->firstOrFail();
    expect($otpRow->otp_hash)->not->toBe($capturedCode);
    expect(Hash::check($capturedCode, $otpRow->otp_hash))->toBeTrue();

    // Wrong OTP fails
    $this->actingAs($colDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.otp.verify', $booking1), ['otp_code' => '000000'])
        ->assertStatus(422);

    // Cross-booking OTP fails on booking2
    $this->actingAs($colDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.otp.verify', $booking2), ['otp_code' => $capturedCode])
        ->assertStatus(422);

    // Valid OTP verifies booking1
    $this->actingAs($colDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.otp.verify', $booking1), ['otp_code' => $capturedCode])
        ->assertOk();

    // Cannot reuse verified OTP
    $this->actingAs($colDp, 'delivery_person')
        ->postJson(route('delivery-person.bookings.otp.verify', $booking1), ['otp_code' => $capturedCode])
        ->assertStatus(422);

    // Record partial Cash in Hand (GBP 30.00) + Online Transfer awaiting verification (GBP 25.00)
    $this->actingAs($colDp, 'delivery_person')
        ->post(route('delivery-person.bookings.payments.cash', $booking1), [
            'amount' => '30.00',
            'idempotency_key' => 'cash-b1-part1',
        ])
        ->assertSessionHas('success');

    // Duplicate idempotency_key is ignored
    $this->actingAs($colDp, 'delivery_person')
        ->post(route('delivery-person.bookings.payments.cash', $booking1), [
            'amount' => '30.00',
            'idempotency_key' => 'cash-b1-part1',
        ])
        ->assertSessionHas('success');

    $booking1->refresh();
    expect($booking1->payment_status)->toBe('partially_paid');
    expect((int) $booking1->cash_collected_minor)->toBe(3000);

    // Cash in Hand increased, Earnings Balance stayed 0
    $cashService = app(DeliveryPersonCashService::class);
    expect($cashService->getSummary($colDp, 'GBP')['cash_in_hand_minor'])->toBe(3000);
    expect($cashService->getEarningsBalanceMinor($colDp, 'GBP'))->toBe(0);

    // Online Transfer claim stays awaiting_verification and does NOT mark booking paid or increase Cash in Hand
    $this->actingAs($colDp, 'delivery_person')
        ->post(route('delivery-person.bookings.payments.transfer', $booking1), [
            'amount' => '25.00',
            'external_reference' => 'BANK-TX-991',
            'receiving_account' => 'Main Business Account',
        ])
        ->assertSessionHas('success');

    expect($cashService->getSummary($colDp, 'GBP')['cash_in_hand_minor'])->toBe(3000);
    $transferPayment = BookingCustomerPayment::where('external_reference', 'BANK-TX-991')->firstOrFail();
    expect($transferPayment->status)->toBe(BookingCustomerPayment::STATUS_AWAITING_VERIFICATION);

    // Admin verifies transfer -> booking1 becomes paid
    $this->actingAs($admin, 'admin')
        ->post(route('admin.booking-payments.verify', $transferPayment), ['decision' => 'approve'])
        ->assertSessionHas('success');

    $booking1->refresh();
    expect($booking1->payment_status)->toBe('paid');

    // Now Delivery Person can finalize booking1
    $this->actingAs($colDp, 'delivery_person')
        ->post(route('delivery-person.bookings.finalize', $booking1))
        ->assertRedirect(route('delivery-person.bookings.show', $booking1));

    $booking1->refresh();
    expect(in_array(Booking::STATUS_FINALIZED, $booking1->getCompletedOperationalStatuses(), true))->toBeTrue();
    expect($booking1->booking_status)->toBe(Booking::STATUS_DELIVERY_MAN_PICKED_UP);

    // After Finalized (4th stage) is done, Delivery Person can mark Delivery Man Picked Up Done
    $this->actingAs($colDp, 'delivery_person')
        ->post(route('delivery-person.bookings.mark-picked-up', $booking1))
        ->assertRedirect(route('delivery-person.bookings.show', $booking1));

    $booking1->refresh();
    expect(in_array(Booking::STATUS_DELIVERY_MAN_PICKED_UP, $booking1->getCompletedOperationalStatuses(), true))->toBeTrue();
    expect($booking1->booking_status)->toBe(Booking::STATUS_STORE_RECEIVED);
    expect($booking1->currentCollectionAssignment->status)->toBe(DeliveryAssignment::STATUS_PICKED_UP);

    // Admin daily cash settlement: cannot settle more than 30.00
    $this->actingAs($admin, 'admin')
        ->post(route('admin.delivery-person-settlements.store'), [
            'delivery_person_id' => $colDp->id,
            'amount' => '50.00',
            'settlement_channel' => 'cash_handover',
        ])
        ->assertSessionHasErrors('amount');

    // Partial settlement 20.00 -> Cash in Hand drops to 10.00
    $this->actingAs($admin, 'admin')
        ->post(route('admin.delivery-person-settlements.store'), [
            'delivery_person_id' => $colDp->id,
            'amount' => '20.00',
            'settlement_channel' => 'cash_handover',
            'notes' => 'Evening cash drop',
        ])
        ->assertRedirect();

    expect($cashService->getSummary($colDp, 'GBP')['cash_in_hand_minor'])->toBe(1000);
    expect($cashService->getSummary($colDp, 'GBP')['total_settled_minor'])->toBe(2000);
    expect(DeliveryPersonSettlement::count())->toBe(1);

    // Custody transactions are immutable
    $tx = DeliveryPersonCashTransaction::firstOrFail();
    expect(fn () => $tx->update(['description' => 'Tampered']))->toThrow(RuntimeException::class);
    expect(fn () => $tx->delete())->toThrow(RuntimeException::class);
});
