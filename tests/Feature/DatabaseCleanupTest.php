<?php

use App\Models\AdminAccount;
use App\Models\AdminAccountTransaction;
use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingCustomerPayment;
use App\Models\BookingOperationalCategory;
use App\Models\BookingOperationalItem;
use App\Models\BookingStoreEarning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\App\Models\DatabaseBackup;
use Modules\Admin\App\Models\DatabaseCleanupLog;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\Vendor;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeCleanupAdmin(bool $withPerm = true): Admin
{
    $admin = Admin::create([
        'name' => 'Cleanup Admin',
        'email' => 'cleanup-' . uniqid() . '@example.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);

    if ($withPerm) {
        $role = Role::firstOrCreate(['name' => 'cleanup-operator', 'guard_name' => 'admin']);
        $perm = Permission::firstOrCreate(['name' => 'manage-database-cleanup', 'guard_name' => 'admin']);
        $role->givePermissionTo($perm);
        $admin->assignRole($role);
    }

    return $admin;
}

function makeVerifiedBackupFixture(): DatabaseBackup
{
    Storage::fake('local');
    $path = 'database-backups/test-verified-backup.sql.enc';
    Storage::disk('local')->put($path, 'encrypted-backup-payload');

    return DatabaseBackup::create([
        'filename' => 'test-verified-backup.sql.enc',
        'storage_disk' => 'local',
        'storage_path' => $path,
        'backup_type' => 'full',
        'database_connection' => 'sqlite',
        'database_name' => ':memory:',
        'file_size_bytes' => 24,
        'checksum_sha256' => hash('sha256', 'encrypted-backup-payload'),
        'is_encrypted' => true,
        'backup_status' => 'completed',
        'verification_status' => 'archive_integrity_verified',
        'verified_at' => now(),
    ]);
}

function makeCleanupSeedData(): array
{
    $user = User::create([
        'name' => 'Test Customer',
        'email' => 'customer@example.com',
        'password' => Hash::make('password'),
    ]);

    $vendor = Vendor::create([
        'name' => 'Cleanup Vendor',
        'owner_name' => 'Cleanup Owner',
        'email' => 'vendor@example.com',
        'phone' => '7700900001',
        'status' => 'active',
    ]);

    $store = Store::create([
        'vendor_id' => $vendor->id,
        'name' => 'Cleanup Store',
        'slug' => 'cleanup-store',
        'code' => 'CLN-01',
        'phone' => '7700900003',
        'address' => '1 Cleanup Street',
        'postcode' => 'M1 2PF',
        'status' => 'active',
        'commission_rate' => 20.00,
    ]);

    $dp = DeliveryPerson::create([
        'full_name' => 'Cleanup Driver',
        'code' => 'DP-CLN-01',
        'email' => 'dp-cleanup@example.com',
        'phone' => '7700900002',
        'date_of_birth' => '1992-01-01',
        'address_line_1' => '10 Driver Rd',
        'city' => 'Manchester',
        'residential_postcode' => 'M1 2PF',
        'work_status' => 'active',
        'store_id' => $store->id,
    ]);

    $category = ItemCategory::create(['name' => 'Wash & Fold', 'slug' => 'wash-fold', 'is_active' => true]);
    $service = Service::create([
        'name' => 'Standard Wash',
        'slug' => 'standard-wash',
        'pricing_type' => 'weight',
        'price' => 5.00,
        'is_active' => true,
    ]);

    $snapshots = [
        'services_snapshot' => [['name' => 'Standard Wash']],
        'address_snapshot' => ['postcode' => 'M1 2PF', 'selected_address' => '10 Test St'],
        'schedule_snapshot' => ['collection_date' => '2026-11-25', 'collection_slot' => '09:00-12:00'],
        'contact_snapshot' => ['name' => 'Test Customer', 'phone' => '+447700900111'],
    ];

    // 1. Disposable safe test booking (no financial dependencies)
    $safeBooking = Booking::create(array_merge($snapshots, [
        'reference' => 'TEST-2026-0001',
        'user_id' => $user->id,
        'customer_name' => 'Test Customer',
        'customer_email' => 'test-user@example.com',
        'customer_phone' => '+447700900111',
        'booking_status' => Booking::STATUS_PROCESSING,
        'payment_status' => 'pending',
        'payment_gateway' => 'cod',
        'currency' => 'GBP',
        'total_amount' => 25.00,
        'confirmed_at' => now(),
    ]));

    BookingOperationalCategory::create([
        'booking_id' => $safeBooking->id,
        'item_category_id' => $category->id,
        'category_name' => 'Wash & Fold',
        'source' => 'checkout',
        'status' => 'active',
    ]);

    BookingOperationalItem::create([
        'booking_id' => $safeBooking->id,
        'item_category_id' => $category->id,
        'service_id' => $service->id,
        'service_name' => 'Standard Wash',
        'pricing_mode' => 'weight',
        'weight_kg' => 2.00,
        'actual_unit_price_minor' => 500,
        'line_total_minor' => 1000,
    ]);

    BookingStoreAssignment::create([
        'booking_id' => $safeBooking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => '1 Cleanup Street',
        'assigned_at' => now(),
        'is_current' => true,
    ]);

    DeliveryAssignment::create([
        'assignment_reference' => 'ASG-TEST-001',
        'booking_id' => $safeBooking->id,
        'delivery_person_id' => $dp->id,
        'store_id' => $store->id,
        'assignment_type' => DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
        'pickup_location' => 'Customer Address',
        'destination_location' => 'Cleanup Store',
        'status' => DeliveryAssignment::STATUS_ASSIGNED,
        'is_current' => true,
    ]);

    BookingAuditLog::create([
        'booking_id' => $safeBooking->id,
        'actor_type' => 'admin',
        'action' => 'booking_created',
        'summary' => 'Test booking created',
    ]);

    // 2. Protected test booking (has confirmed payment, store earning, DP ledger, admin account tx)
    $protectedBooking = Booking::create(array_merge($snapshots, [
        'reference' => 'TEST-2026-0002',
        'user_id' => $user->id,
        'customer_name' => 'Paid Customer',
        'customer_email' => 'paid@example.com',
        'customer_phone' => '+447700900222',
        'booking_status' => Booking::STATUS_COMPLETED,
        'payment_status' => 'paid',
        'payment_gateway' => 'stripe',
        'currency' => 'GBP',
        'total_amount' => 50.00,
        'confirmed_at' => now(),
    ]));

    BookingCustomerPayment::create([
        'booking_id' => $protectedBooking->id,
        'payment_method' => 'stripe',
        'payment_channel' => 'online',
        'idempotency_key' => 'pay-test-1',
        'status' => 'confirmed',
        'amount_minor' => 5000,
        'currency' => 'GBP',
        'confirmed_at' => now(),
    ]);

    $storeAsg = BookingStoreAssignment::create([
        'booking_id' => $protectedBooking->id,
        'store_id' => $store->id,
        'store_name_snapshot' => $store->name,
        'store_address_snapshot' => '1 Cleanup Street',
        'assigned_at' => now(),
        'is_current' => true,
    ]);

    BookingStoreEarning::create([
        'booking_id' => $protectedBooking->id,
        'booking_store_assignment_id' => $storeAsg->id,
        'store_id' => $store->id,
        'currency' => 'GBP',
        'eligible_service_amount_minor' => 4000,
        'commission_rate' => 20.00,
        'commission_amount_minor' => 800,
        'net_store_earnings_minor' => 3200,
        'snapshot_hash' => hash('sha256', 'store-earning-test'),
        'finalized_at' => now(),
        'is_current' => true,
    ]);

    DeliveryPersonLedgerEntry::create([
        'entry_reference' => 'DP-LED-001',
        'delivery_person_id' => $dp->id,
        'booking_id' => $protectedBooking->id,
        'transaction_type' => 'assignment_earning',
        'direction' => 'credit',
        'amount_minor' => 500,
        'balance_after_minor' => 500,
        'currency' => 'GBP',
        'occurred_at' => now(),
    ]);

    $adminAcc = AdminAccount::create([
        'account_code' => 'ADMIN-GBP',
        'name' => 'Main GBP Account',
        'currency' => 'GBP',
        'current_balance' => 50.00,
    ]);

    AdminAccountTransaction::create([
        'admin_account_id' => $adminAcc->id,
        'booking_id' => $protectedBooking->id,
        'transaction_type' => 'customer_receipt',
        'direction' => 'credit',
        'amount' => 50.00,
        'currency' => 'GBP',
        'reference' => 'ADM-TEST-1',
        'idempotency_key' => 'adm-tx-test-1',
        'description' => 'Test customer receipt',
    ]);

    return compact('user', 'vendor', 'store', 'dp', 'category', 'service', 'safeBooking', 'protectedBooking');
}

it('enforces admin authorization and blocks disabled or production environments', function () {
    $unauthorizedAdmin = makeCleanupAdmin(false);
    $authorizedAdmin = makeCleanupAdmin(true);
    $customer = User::create([
        'name' => 'Customer',
        'email' => 'cust@example.com',
        'password' => Hash::make('password'),
    ]);

    // Unauthorized admin & customer blocked
    $this->actingAs($unauthorizedAdmin, 'admin')
        ->get(route('admin.database-cleanup.index'))
        ->assertForbidden();

    $custRes = $this->actingAs($customer, 'web')
        ->get(route('admin.database-cleanup.index'));
    expect(in_array($custRes->status(), [302, 401, 403], true))->toBeTrue();

    // Authorized admin can open page even when disabled, and sees disabled notice
    config(['database_cleanup.enabled' => false]);
    $this->actingAs($authorizedAdmin, 'admin')
        ->get(route('admin.database-cleanup.index'))
        ->assertOk()
        ->assertSee('Database Cleanup is disabled in this environment.');

    // Execute endpoint strictly returns 403 when disabled
    $this->actingAs($authorizedAdmin, 'admin')
        ->postJson(route('admin.database-cleanup.execute'), [
            'module' => 'booking_service',
            'preview_id' => 'prv_test',
            'confirmation_phrase' => 'DELETE BOOKING TEST DATA',
            'idempotency_key' => 'k1',
        ])
        ->assertStatus(403)
        ->assertJsonPath('message', 'Database Cleanup is disabled in this environment.');

    // Production environment check blocks execution even if enabled=true
    config(['database_cleanup.enabled' => true, 'database_cleanup.allowed_environments' => ['local']]);
    $this->actingAs($authorizedAdmin, 'admin')
        ->postJson(route('admin.database-cleanup.execute'), [
            'module' => 'booking_service',
            'preview_id' => 'prv_test',
            'confirmation_phrase' => 'DELETE BOOKING TEST DATA',
            'idempotency_key' => 'k2',
        ])
        ->assertStatus(403);
});

it('previews booking service tables, detects financial blockers, and blocks missing backup or invalid confirmation', function () {
    config(['database_cleanup.enabled' => true, 'database_cleanup.allowed_environments' => ['local', 'testing']]);
    $admin = makeCleanupAdmin(true);
    makeCleanupSeedData();

    // Dry-run preview in safe_test mode
    $previewRes = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-cleanup.preview'), [
            'module' => 'booking_service',
            'mode' => 'safe_test',
        ])
        ->assertOk()
        ->assertJsonPath('data.summary.total_bookings', 2)
        ->assertJsonPath('data.summary.targeted_bookings', 1)
        ->assertJsonPath('data.summary.blocked_bookings', 1)
        ->assertJsonPath('data.summary.is_partial_only', true)
        ->assertJsonPath('data.backup.verified', false)
        ->assertJsonPath('data.summary.can_execute', false);

    // Dry-run preview did NOT delete anything
    expect(Booking::count())->toBe(2);

    // Option B (all_eligible) is blocked when protected financial bookings exist
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-cleanup.preview'), [
            'module' => 'booking_service',
            'mode' => 'all_eligible',
        ])
        ->assertOk()
        ->assertJsonPath('data.summary.is_full_clear_blocked', true)
        ->assertJsonPath('data.summary.can_execute', false);

    $previewId = $previewRes->json('data.preview_id');

    // Wrong confirmation phrase rejected (422)
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-cleanup.execute'), [
            'module' => 'booking_service',
            'preview_id' => $previewId,
            'confirmation_phrase' => 'WRONG PHRASE',
            'idempotency_key' => 'idemp-1',
        ])
        ->assertStatus(422);

    // Missing verified backup blocks execution (422)
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-cleanup.execute'), [
            'module' => 'booking_service',
            'preview_id' => $previewId,
            'confirmation_phrase' => 'DELETE BOOKING TEST DATA',
            'idempotency_key' => 'idemp-2',
        ])
        ->assertStatus(422);
});

it('executes safe test booking cleanup, preserves master and financial data, rejects stale/duplicate requests, and logs audit history', function () {
    config(['database_cleanup.enabled' => true, 'database_cleanup.allowed_environments' => ['local', 'testing']]);
    $admin = makeCleanupAdmin(true);
    $seed = makeCleanupSeedData();
    makeVerifiedBackupFixture();

    $previewRes = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-cleanup.preview'), [
            'module' => 'booking_service',
            'mode' => 'safe_test',
        ])
        ->assertOk()
        ->assertJsonPath('data.summary.can_execute', true);

    $previewId = $previewRes->json('data.preview_id');

    // Execute authorized cleanup
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-cleanup.execute'), [
            'module' => 'booking_service',
            'preview_id' => $previewId,
            'confirmation_phrase' => 'DELETE BOOKING TEST DATA',
            'idempotency_key' => 'idemp-exec-1',
        ])
        ->assertOk()
        ->assertJsonPath('result.targeted_bookings_deleted', 1)
        ->assertJsonPath('result.post_verification.orphans_remaining', 0)
        ->assertJsonPath('result.post_verification.master_data_intact', true);

    // Safe booking and its dependent records were deleted
    expect(Booking::find($seed['safeBooking']->id))->toBeNull()
        ->and(BookingOperationalItem::where('booking_id', $seed['safeBooking']->id)->count())->toBe(0)
        ->and(BookingStoreAssignment::where('booking_id', $seed['safeBooking']->id)->count())->toBe(0)
        ->and(DeliveryAssignment::where('booking_id', $seed['safeBooking']->id)->count())->toBe(0);

    // Protected booking and ALL financial/ledger records remain untouched
    expect(Booking::find($seed['protectedBooking']->id))->not->toBeNull()
        ->and(BookingCustomerPayment::count())->toBe(1)
        ->and(BookingStoreEarning::count())->toBe(1)
        ->and(DeliveryPersonLedgerEntry::count())->toBe(1)
        ->and(AdminAccountTransaction::count())->toBe(1);

    // Master data preserved
    expect(User::count())->toBe(1)
        ->and(Store::count())->toBe(1)
        ->and(Vendor::count())->toBe(1)
        ->and(DeliveryPerson::count())->toBe(1)
        ->and(Service::count())->toBe(1)
        ->and(ItemCategory::count())->toBe(1);

    // Audit log recorded
    expect(DatabaseCleanupLog::count())->toBe(1)
        ->and(DatabaseCleanupLog::first()->targeted_bookings_count)->toBe(1)
        ->and(DatabaseCleanupLog::first()->status)->toBe('completed');

    // Duplicate idempotency_key rejected
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-cleanup.execute'), [
            'module' => 'booking_service',
            'preview_id' => $previewId,
            'confirmation_phrase' => 'DELETE BOOKING TEST DATA',
            'idempotency_key' => 'idemp-exec-1',
        ])
        ->assertStatus(422);

    // Stale preview_id rejected
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.database-cleanup.execute'), [
            'module' => 'booking_service',
            'preview_id' => $previewId,
            'confirmation_phrase' => 'DELETE BOOKING TEST DATA',
            'idempotency_key' => 'idemp-exec-2',
        ])
        ->assertStatus(422);

    // New booking can be created cleanly afterward
    $newBooking = Booking::create([
        'reference' => 'LND-2026-999999',
        'user_id' => $seed['user']->id,
        'customer_name' => 'Fresh Booking',
        'customer_email' => 'fresh@example.com',
        'customer_phone' => '+447700900999',
        'services_snapshot' => [['name' => 'Standard Wash']],
        'address_snapshot' => ['postcode' => 'M1 2PF', 'selected_address' => '10 Test St'],
        'schedule_snapshot' => ['collection_date' => '2026-11-25', 'collection_slot' => '09:00-12:00'],
        'contact_snapshot' => ['name' => 'Fresh Booking', 'phone' => '+447700900999'],
        'booking_status' => Booking::STATUS_PROCESSING,
        'payment_status' => 'pending',
        'payment_gateway' => 'cod',
        'currency' => 'GBP',
        'total_amount' => 15.00,
    ]);
    expect($newBooking->exists)->toBeTrue();
});
