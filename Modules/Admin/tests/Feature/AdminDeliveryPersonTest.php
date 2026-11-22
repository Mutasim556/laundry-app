<?php

use App\Models\Booking;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonDocument;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\UkAddress;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');

    foreach ([
        'view-delivery-persons',
        'create-delivery-persons',
        'edit-delivery-persons',
        'delete-delivery-persons',
        'manage-delivery-assignments',
        'manage-delivery-payouts',
        'view-delivery-documents',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin']);
    }

    UkAddress::create([
        'postcode' => 'M1 2PF',
        'postcode_compact' => 'M12PF',
        'address_line_1' => '10 Portland Street',
        'street' => 'Portland Street',
        'city' => 'Manchester',
        'is_active' => true,
    ]);

    UkAddress::create([
        'postcode' => 'M2 4AA',
        'postcode_compact' => 'M24AA',
        'address_line_1' => '25 Oxford Road',
        'street' => 'Oxford Road',
        'city' => 'Manchester',
        'is_active' => true,
    ]);

    $this->store = Store::create([
        'ownership_type' => 'my_store',
        'status' => 'active',
        'name' => 'Manchester Hub',
        'slug' => 'manchester-hub',
        'store_type' => 'both',
        'phone_country_code' => '+44',
        'phone' => '7123456789',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'address_line_1' => '10 Portland Street',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'full_address' => '10 Portland Street, Manchester, M1 2PF, United Kingdom',
    ]);
});

function fullAdminWithDeliveryPermissions(): Admin
{
    $admin = Admin::factory()->create();
    $admin->givePermissionTo([
        'view-delivery-persons',
        'create-delivery-persons',
        'edit-delivery-persons',
        'delete-delivery-persons',
        'manage-delivery-assignments',
        'manage-delivery-payouts',
        'view-delivery-documents',
    ]);

    return $admin;
}

test('unauthorized users cannot access delivery persons or private documents', function () {
    $this->get(route('admin.delivery-persons.index'))->assertRedirect(route('admin.login'));

    $limitedAdmin = Admin::factory()->create();
    $limitedAdmin->givePermissionTo('view-delivery-persons');

    $person = DeliveryPerson::create([
        'code' => 'DP-0001',
        'full_name' => 'John Courier',
        'email' => 'john@example.com',
        'phone_country_code' => '+44',
        'phone' => '7700900111',
        'date_of_birth' => '1990-05-10',
        'address_line_1' => '1 Street',
        'city' => 'Manchester',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'residential_postcode' => 'M1 2PF',
        'employment_type' => 'contractor',
        'work_status' => 'active',
        'availability_status' => 'available',
    ]);

    $doc = new DeliveryPersonDocument([
        'delivery_person_id' => $person->id,
        'document_type' => 'passport',
        'front_image_path' => 'delivery-persons/1/documents/test.pdf',
        'verification_status' => 'pending',
    ]);
    $doc->setPlainDocumentNumber('GB99887766');
    $doc->save();

    Storage::disk('local')->put('delivery-persons/1/documents/test.pdf', 'secret');

    $this->actingAs($limitedAdmin, 'admin')
        ->get(route('admin.delivery-persons.documents.download', [$person, $doc, 'front']))
        ->assertStatus(403);
});

test('admin can create delivery person with private encrypted document and deduplicated uk postcodes', function () {
    $admin = fullAdminWithDeliveryPermissions();

    // Reject invalid UK postcode
    $invalidRes = $this->actingAs($admin, 'admin')->post(route('admin.delivery-persons.store'), [
        'full_name' => 'Alex Mercer',
        'email' => 'alex@courier.test',
        'phone_country_code' => '+44',
        'phone' => '7700900123',
        'date_of_birth' => '1992-03-15',
        'address_line_1' => '50 Deansgate',
        'city' => 'Manchester',
        'country_code' => 'GB',
        'residential_postcode' => 'M1 2PF',
        'coverage_postcodes' => ['ZZ99 9ZZ'],
        'employment_type' => 'contractor',
        'work_status' => 'active',
        'availability_status' => 'available',
        'payment_calculation_type' => 'separate_rates',
        'per_collection_rate' => '5.00',
        'per_delivery_rate' => '6.00',
        'currency' => 'GBP',
        'document_type' => 'passport',
        'document_number' => 'P12345678',
        'document_front_image' => UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf'),
    ]);

    $invalidRes->assertSessionHasErrors(['coverage_postcodes']);

    // Valid creation with duplicate casing/spacing variants
    $validRes = $this->actingAs($admin, 'admin')->post(route('admin.delivery-persons.store'), [
        'full_name' => 'Alex Mercer',
        'email' => 'alex@courier.test',
        'phone_country_code' => '+44',
        'phone' => '7700900123',
        'date_of_birth' => '1992-03-15',
        'address_line_1' => '50 Deansgate',
        'city' => 'Manchester',
        'country_code' => 'GB',
        'residential_postcode' => 'm1  2pf',
        'coverage_postcodes' => ['m1 2pf', 'M1  2PF', 'm2 4aa'],
        'employment_type' => 'contractor',
        'work_status' => 'active',
        'availability_status' => 'available',
        'store_id' => $this->store->id,
        'payment_calculation_type' => 'separate_rates',
        'per_collection_rate' => '5.00',
        'per_delivery_rate' => '6.00',
        'currency' => 'GBP',
        'document_type' => 'passport',
        'document_number' => 'P12345678',
        'document_front_image' => UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf'),
    ]);

    $person = DeliveryPerson::where('email', 'alex@courier.test')->first();
    expect($person)->not->toBeNull();
    $validRes->assertRedirect(route('admin.delivery-persons.show', $person));

    expect($person->residential_postcode)->toBe('M1 2PF');
    expect($person->postcodes()->pluck('postcode')->all())->toBe(['M1 2PF', 'M2 4AA']);

    $doc = $person->documents()->first();
    expect($doc)->not->toBeNull();
    expect($doc->document_number_masked)->toBe('*****5678');
    expect($doc->getDecryptedDocumentNumber())->toBe('P12345678');
    Storage::disk('local')->assertExists($doc->front_image_path);

    // Authorized download works
    $this->actingAs($admin, 'admin')
        ->get(route('admin.delivery-persons.documents.download', [$person, $doc, 'front']))
        ->assertStatus(200);
});

test('full booking collection and delivery assignment workflow credits earnings once and supports payouts and archival', function () {
    $admin = fullAdminWithDeliveryPermissions();

    $collector = DeliveryPerson::create([
        'code' => 'DP-0101',
        'full_name' => 'Collector One',
        'email' => 'collector@courier.test',
        'phone_country_code' => '+44',
        'phone' => '7700900201',
        'date_of_birth' => '1991-01-01',
        'address_line_1' => '10 Portland St',
        'city' => 'Manchester',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'residential_postcode' => 'M1 2PF',
        'employment_type' => 'contractor',
        'work_status' => 'active',
        'availability_status' => 'available',
        'store_id' => $this->store->id,
        'payment_calculation_type' => 'separate_rates',
        'per_collection_rate_minor' => 500,
        'per_delivery_rate_minor' => 600,
        'currency' => 'GBP',
    ]);
    $collector->postcodes()->create(['postcode' => 'M1 2PF', 'postcode_compact' => 'M12PF']);

    $deliverer = DeliveryPerson::create([
        'code' => 'DP-0102',
        'full_name' => 'Deliverer Two',
        'email' => 'deliverer@courier.test',
        'phone_country_code' => '+44',
        'phone' => '7700900202',
        'date_of_birth' => '1993-02-02',
        'address_line_1' => '25 Oxford Rd',
        'city' => 'Manchester',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'residential_postcode' => 'M2 4AA',
        'employment_type' => 'contractor',
        'work_status' => 'active',
        'availability_status' => 'available',
        'store_id' => $this->store->id,
        'payment_calculation_type' => 'separate_rates',
        'per_collection_rate_minor' => 500,
        'per_delivery_rate_minor' => 600,
        'currency' => 'GBP',
    ]);
    $deliverer->postcodes()->create(['postcode' => 'M1 2PF', 'postcode_compact' => 'M12PF']);

    $inactive = DeliveryPerson::create([
        'code' => 'DP-0103',
        'full_name' => 'Inactive Courier',
        'email' => 'inactive@courier.test',
        'phone_country_code' => '+44',
        'phone' => '7700900203',
        'date_of_birth' => '1990-01-01',
        'address_line_1' => '1 Road',
        'city' => 'Manchester',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'residential_postcode' => 'M1 2PF',
        'employment_type' => 'contractor',
        'work_status' => 'inactive',
        'availability_status' => 'off_duty',
    ]);

    $booking = Booking::create([
        'reference' => 'LND-2026-000099',
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '+447700900999',
        'customer_type' => 'guest',
        'address_snapshot' => ['address_line_1' => '99 Piccadilly', 'city' => 'Manchester', 'postcode' => 'M1 2PF'],
        'schedule_snapshot' => ['collection_date' => '2026-11-23'],
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'payment_gateway' => 'cod',
        'payment_status' => 'pending',
        'booking_status' => Booking::STATUS_DELIVERY_MAN_ASSIGNED,
        'currency' => 'GBP',
        'estimated_amount_minor' => 2500,
    ]);

    // Inactive courier must be rejected
    $this->actingAs($admin, 'admin')->post(route('admin.delivery-assignments.store'), [
        'booking_id' => $booking->id,
        'delivery_person_id' => $inactive->id,
        'store_id' => $this->store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
    ])->assertSessionHasErrors(['delivery_person_id']);

    // Assign collector to Customer Collection
    $this->actingAs($admin, 'admin')->post(route('admin.delivery-assignments.store'), [
        'booking_id' => $booking->id,
        'delivery_person_id' => $collector->id,
        'store_id' => $this->store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
    ])->assertSessionHasNoErrors();

    $collectionAsg = DeliveryAssignment::where('booking_id', $booking->id)
        ->where('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)
        ->firstOrFail();

    expect($collectionAsg->matched_postcode)->toBe('M1 2PF');
    expect($collectionAsg->rate_minor)->toBe(500);

    // Pickup & Handover to store
    $this->actingAs($admin, 'admin')->patch(route('admin.delivery-assignments.transition', $collectionAsg), ['status' => 'picked_up']);
    $this->actingAs($admin, 'admin')->patch(route('admin.delivery-assignments.transition', $collectionAsg), ['status' => 'handed_over']);
    // Duplicate completion request should NOT create a second earning
    $this->actingAs($admin, 'admin')->patch(route('admin.delivery-assignments.transition', $collectionAsg), ['status' => 'completed']);
    $this->actingAs($admin, 'admin')->patch(route('admin.delivery-assignments.transition', $collectionAsg), ['status' => 'completed']);

    expect(DeliveryPersonLedgerEntry::where('delivery_assignment_id', $collectionAsg->id)->count())->toBe(1);

    // Assign deliverer to Final Delivery
    $this->actingAs($admin, 'admin')->post(route('admin.delivery-assignments.store'), [
        'booking_id' => $booking->id,
        'delivery_person_id' => $deliverer->id,
        'store_id' => $this->store->id,
        'assignment_type' => DeliveryAssignment::TYPE_FINAL_DELIVERY,
    ])->assertSessionHasNoErrors();

    $deliveryAsg = DeliveryAssignment::where('booking_id', $booking->id)
        ->where('assignment_type', DeliveryAssignment::TYPE_FINAL_DELIVERY)
        ->firstOrFail();

    expect($deliveryAsg->cod_expected_minor)->toBe(2500);
    $this->actingAs($admin, 'admin')->patch(route('admin.delivery-assignments.transition', $deliveryAsg), [
        'status' => 'picked_up',
    ]);
    $this->actingAs($admin, 'admin')->patch(route('admin.delivery-assignments.transition', $deliveryAsg), [
        'status' => 'completed',
        'proof_of_delivery_notes' => 'Signed by recipient',
    ]);

    // Record COD separately (does not alter courier earnings)
    $this->actingAs($admin, 'admin')->patch(route('admin.delivery-assignments.cod', $deliveryAsg), [
        'cod_collected' => '25.00',
        'cod_handover_status' => 'handed_over',
    ]);

    $logistics = app(\Modules\Admin\App\Services\DeliveryLogisticsService::class);
    $colBal = $logistics->getBalanceSummary($collector);
    $delBal = $logistics->getBalanceSummary($deliverer);

    expect($colBal['outstanding_balance_minor'])->toBe(500);
    expect($delBal['outstanding_balance_minor'])->toBe(600);

    // Partial payout with idempotency key
    $idem = 'payout-idem-123';
    $this->actingAs($admin, 'admin')->post(route('admin.delivery-payouts.store'), [
        'delivery_person_id' => $deliverer->id,
        'amount' => '4.00',
        'payment_method' => 'bank_transfer',
        'payment_date' => '2026-11-22',
        'external_reference' => 'TXN-001',
        'idempotency_key' => $idem,
    ])->assertSessionHasNoErrors();

    // Replay same idempotency key -> no duplicate payout
    $this->actingAs($admin, 'admin')->post(route('admin.delivery-payouts.store'), [
        'delivery_person_id' => $deliverer->id,
        'amount' => '4.00',
        'payment_method' => 'bank_transfer',
        'payment_date' => '2026-11-22',
        'external_reference' => 'TXN-001',
        'idempotency_key' => $idem,
    ])->assertSessionHasNoErrors();

    $delBalAfter = $logistics->getBalanceSummary($deliverer);
    expect($delBalAfter['total_paid_minor'])->toBe(400);
    expect($delBalAfter['outstanding_balance_minor'])->toBe(200);

    // Overdraft payout rejected
    $this->actingAs($admin, 'admin')->post(route('admin.delivery-payouts.store'), [
        'delivery_person_id' => $deliverer->id,
        'amount' => '5.00',
        'payment_method' => 'cash',
        'payment_date' => '2026-11-22',
    ])->assertSessionHasErrors(['amount']);

    // Archive delivery person with history -> soft deleted, booking & ledger preserved
    $this->actingAs($admin, 'admin')->delete(route('admin.delivery-persons.destroy', $deliverer), [
        'archive_reason' => 'Left region',
    ]);

    expect(DeliveryPerson::withTrashed()->find($deliverer->id)?->deleted_at)->not->toBeNull();
    expect(Booking::find($booking->id))->not->toBeNull();
    expect(DeliveryPersonLedgerEntry::where('delivery_person_id', $deliverer->id)->count())->toBe(2);
});
