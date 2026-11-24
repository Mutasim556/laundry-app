<?php

use App\Models\Booking;
use App\Models\BookingStoreEarning;
use App\Models\StoreAccount;
use App\Models\StoreBankAccount;
use App\Models\StoreTransaction;
use App\Models\StoreUser;
use App\Models\StoreWithdrawalRequest;
use App\Services\CurrentAccountReportService;
use App\Services\StoreAccountService;
use App\Services\StoreWithdrawalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Store;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function makeStoreWdAdmin(): Admin
{
    $admin = Admin::create([
        'name' => 'Store Finance Admin',
        'email' => 'storefin' . random_int(100, 999) . '@example.com',
        'password' => bcrypt('Admin2026!'),
        'status' => true,
    ]);

    foreach (['view-stores', 'view-store-accounts', 'adjust-store-accounts', 'view-admin-accounts'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin']);
    }
    $admin->givePermissionTo(['view-stores', 'view-store-accounts', 'adjust-store-accounts', 'view-admin-accounts']);

    return $admin;
}

function makeStoreAndUser(string $name = 'Mayfair Laundry'): array
{
    $store = Store::create([
        'name' => $name,
        'slug' => \Illuminate\Support\Str::slug($name) . '-' . random_int(100, 999),
        'email' => 'store' . random_int(100, 999) . '@example.com',
        'phone' => '02079460000',
        'address_line_1' => '12 Bond St',
        'city' => 'London',
        'postcode' => 'W1S 1AA',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'status' => 'active',
        'commission_rate' => 20.00,
    ]);

    $user = StoreUser::create([
        'store_id' => $store->id,
        'name' => $name . ' Manager',
        'email' => 'mgr' . random_int(1000, 9999) . '@example.com',
        'password' => bcrypt('Store2026!'),
        'is_active' => true,
    ]);

    return [$store, $user];
}

function seedStoreCompletedEarning(Store $store, int $revenueMinor, float $commissionRate, Carbon $finalizedAt): BookingStoreEarning
{
    $commMinor = (int) round($revenueMinor * ($commissionRate / 100));
    $netMinor = $revenueMinor - $commMinor;

    $booking = Booking::create([
        'reference' => 'LND-ST-' . random_int(100000, 999999),
        'customer_name' => 'Store Customer',
        'customer_email' => 'scust' . random_int(100, 999) . '@example.com',
        'customer_phone' => '+447700900010',
        'booking_status' => Booking::STATUS_COMPLETED,
        'completed_operational_stages' => [Booking::STATUS_COMPLETED => ['completed_at' => $finalizedAt->toIso8601String()]],
        'currency' => 'GBP',
        'estimated_amount_minor' => $revenueMinor,
        'final_amount_minor' => $revenueMinor,
        'payment_gateway' => 'stripe',
        'payment_status' => 'paid',
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'fee_snapshot' => [],
        'address_snapshot' => ['full_address' => '12 Bond St', 'postcode' => 'W1S 1AA', 'country_code' => 'GB'],
        'schedule_snapshot' => [],
    ]);
    $booking->assigned_store_id = $store->id;
    $booking->save();

    $earning = BookingStoreEarning::create([
        'booking_id' => $booking->id,
        'store_id' => $store->id,
        'currency' => 'GBP',
        'eligible_service_amount_minor' => $revenueMinor,
        'commission_rate' => $commissionRate,
        'commission_amount_minor' => $commMinor,
        'net_store_earnings_minor' => $netMinor,
        'is_current' => true,
        'snapshot_hash' => hash('sha256', 'test-' . random_int(1000, 999999)),
        'finalized_at' => $finalizedAt,
    ]);

    $account = StoreAccount::firstOrCreate(
        ['store_id' => $store->id, 'currency' => 'GBP'],
        ['current_balance' => '0.00', 'total_credited' => '0.00', 'total_debited' => '0.00']
    );
    $newBalMinor = StoreAccountService::decimalToMinor($account->current_balance) + $netMinor;
    $newCredMinor = StoreAccountService::decimalToMinor($account->total_credited) + $netMinor;
    $account->update([
        'current_balance' => StoreAccountService::minorToDecimal($newBalMinor),
        'total_credited' => StoreAccountService::minorToDecimal($newCredMinor),
    ]);

    StoreTransaction::create([
        'store_id' => $store->id,
        'booking_id' => $booking->id,
        'booking_store_earning_id' => $earning->id,
        'transaction_type' => StoreTransaction::TYPE_BOOKING_EARNING,
        'direction' => StoreTransaction::DIRECTION_CREDIT,
        'amount' => StoreAccountService::minorToDecimal($netMinor),
        'currency' => 'GBP',
        'balance_after' => StoreAccountService::minorToDecimal($newBalMinor),
        'reference' => 'STR-TX-' . random_int(100000, 999999),
        'idempotency_key' => 'test-earn-' . $booking->id,
        'description' => 'Completed booking earning',
    ]);

    return $earning;
}

test('store bank account ajax management, encryption, masking, ownership, and default switching', function () {
    [$store1, $user1] = makeStoreAndUser('Store One');
    [$store2, $user2] = makeStoreAndUser('Store Two');

    // No bank account initially
    $this->actingAs($user1, 'store')
        ->getJson(route('store.withdrawals.modal-data'))
        ->assertOk()
        ->assertJsonPath('has_bank_account', false);

    // Add first bank account via AJAX
    $res1 = $this->actingAs($user1, 'store')
        ->postJson(route('store.bank-accounts.store'), [
            'country_code' => 'GB',
            'currency' => 'GBP',
            'account_holder_name' => $store1->name,
            'bank_name' => 'Barclays Business',
            'account_type' => 'business',
            'account_number' => '87654321',
            'routing_code' => '20-00-00',
        ])->assertCreated()
        ->assertJsonPath('account.account_number_masked', '•••• 4321')
        ->assertJsonPath('account.is_default', true);

    $bank1Id = $res1->json('account.id');
    $rawDb = DB::table('store_bank_accounts')->where('id', $bank1Id)->first();
    expect($rawDb->account_number_encrypted)->not->toBe('87654321');

    // Add second bank account and set default
    $res2 = $this->actingAs($user1, 'store')
        ->postJson(route('store.bank-accounts.store'), [
            'country_code' => 'GB',
            'currency' => 'GBP',
            'account_holder_name' => $store1->name,
            'bank_name' => 'HSBC Business',
            'account_number' => '11223344',
            'routing_code' => '400000',
        ])->assertCreated();

    $bank2Id = $res2->json('account.id');

    $this->actingAs($user1, 'store')
        ->postJson(route('store.bank-accounts.default', $bank2Id))
        ->assertOk()
        ->assertJsonPath('account.is_default', true);

    expect(StoreBankAccount::find($bank1Id)->is_default)->toBeFalse();
    expect(StoreBankAccount::find($bank2Id)->is_default)->toBeTrue();

    // Another store cannot access or modify store1's bank account
    $this->actingAs($user2, 'store')
        ->postJson(route('store.bank-accounts.default', $bank1Id))
        ->assertForbidden();

    $this->actingAs($user2, 'store')
        ->deleteJson(route('store.bank-accounts.destroy', $bank1Id))
        ->assertForbidden();
});

test('store month-wise earnings, carried forward balance, withdrawal reservation, admin approval, manual settlement, rejection, and current account integration', function () {
    $admin = makeStoreWdAdmin();
    [$store, $storeUser] = makeStoreAndUser('Kensington Dry Cleaners');
    [$otherStore, $otherUser] = makeStoreAndUser('Chelsea Cleaners');

    $now = Carbon::now();
    $prevMonth = (clone $now)->subMonth()->startOfMonth()->addDays(4);
    $currMonth = (clone $now)->startOfMonth()->addDays(2);

    // Prior month: £1,000.00 revenue, 20% commission = £200.00, store share = £800.00
    seedStoreCompletedEarning($store, 100000, 20.00, $prevMonth);
    // Current month: £500.00 revenue, 20% commission = £100.00, store share = £400.00
    seedStoreCompletedEarning($store, 50000, 20.00, $currMonth);

    // Check /store/earnings HTML & AJAX JSON
    $this->actingAs($storeUser, 'store')
        ->get(route('store.earnings.index'))
        ->assertOk();

    $this->actingAs($storeUser, 'store')
        ->getJson(route('store.earnings.index', ['month' => $currMonth->format('Y-m')]))
        ->assertOk()
        ->assertJsonPath('summary.revenue_month_minor', 50000)
        ->assertJsonPath('summary.commission_month_minor', 10000)
        ->assertJsonPath('summary.store_share_month_minor', 40000)
        ->assertJsonPath('summary.available_balance_minor', 120000); // £800 carried forward + £400 this month = £1,200.00

    $bank = StoreBankAccount::create([
        'store_id' => $store->id,
        'account_holder_name' => $store->name,
        'bank_name' => 'Barclays Business',
        'country_code' => 'GB',
        'currency' => 'GBP',
        'account_number_encrypted' => '12345678',
        'account_number_last4' => '5678',
        'account_number_masked' => '•••• 5678',
        'routing_code_encrypted' => '200000',
        'routing_code_label' => 'Sort Code',
        'is_default' => true,
        'is_active' => true,
    ]);

    $otherBank = StoreBankAccount::create([
        'store_id' => $otherStore->id,
        'account_holder_name' => $otherStore->name,
        'bank_name' => 'Lloyds',
        'country_code' => 'GB',
        'currency' => 'GBP',
        'account_number_encrypted' => '99887766',
        'account_number_last4' => '7766',
        'account_number_masked' => '•••• 7766',
        'routing_code_encrypted' => '300000',
        'routing_code_label' => 'Sort Code',
        'is_default' => true,
        'is_active' => true,
    ]);

    // Validation guards: zero, over-balance, another store's bank
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.withdrawals.store'), ['bank_account_id' => $bank->id, 'amount' => 0])
        ->assertUnprocessable();

    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.withdrawals.store'), ['bank_account_id' => $bank->id, 'amount' => 2000.00])
        ->assertUnprocessable();

    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.withdrawals.store'), ['bank_account_id' => $otherBank->id, 'amount' => 50.00])
        ->assertUnprocessable();

    // Submit valid withdrawal #1 (£700.00) with idempotency key
    $wd1Res = $this->actingAs($storeUser, 'store')
        ->postJson(route('store.withdrawals.store'), [
            'bank_account_id' => $bank->id,
            'amount' => 700.00,
            'idempotency_key' => 'store-idem-1',
        ])->assertCreated();

    $wd1Id = $wd1Res->json('withdrawal.id');
    expect($wd1Res->json('summary.available_balance_minor'))->toBe(50000); // £500.00 left
    expect($wd1Res->json('summary.pending_withdrawal_minor'))->toBe(70000);

    // Duplicate idempotency key does NOT create a second withdrawal or deduct twice
    $this->actingAs($storeUser, 'store')
        ->postJson(route('store.withdrawals.store'), [
            'bank_account_id' => $bank->id,
            'amount' => 700.00,
            'idempotency_key' => 'store-idem-1',
        ])->assertCreated();

    expect(StoreWithdrawalRequest::count())->toBe(1);
    expect(app(StoreWithdrawalService::class)->getAvailableBalanceMinor($store, 'GBP'))->toBe(50000);

    // Submit valid withdrawal #2 (£200.00)
    $wd2Res = $this->actingAs($storeUser, 'store')
        ->postJson(route('store.withdrawals.store'), [
            'bank_account_id' => $bank->id,
            'amount' => 200.00,
            'idempotency_key' => 'store-idem-2',
        ])->assertCreated();
    $wd2Id = $wd2Res->json('withdrawal.id');
    expect(app(StoreWithdrawalService::class)->getAvailableBalanceMinor($store, 'GBP'))->toBe(30000);

    // Another store cannot view wd1
    $this->actingAs($otherUser, 'store')
        ->getJson(route('store.withdrawals.show', $wd1Id))
        ->assertForbidden();

    // Admin views /admin/store-withdrawals & approves #1 with payout method -> balance stays £300.00
    $this->actingAs($admin, 'admin')
        ->get(route('admin.store-withdrawals.index'))
        ->assertOk();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.store-withdrawals.approve', $wd1Id), [
            'payout_method' => 'bank_transfer',
            'paid_from_account' => 'Company HSBC Business',
        ])->assertOk()
        ->assertJsonPath('withdrawal_status', StoreWithdrawalRequest::STATUS_APPROVED);

    expect(app(StoreWithdrawalService::class)->getAvailableBalanceMinor($store, 'GBP'))->toBe(30000);

    // Repeated approval fails
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.store-withdrawals.approve', $wd1Id))
        ->assertUnprocessable();

    // Admin marks #1 Manually Settled -> balance stays £300.00 (no double-deduction)
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.store-withdrawals.settle', $wd1Id), [
            'payment_date' => $currMonth->toDateString(),
            'external_reference' => 'BACS-STORE-700',
        ])->assertOk()
        ->assertJsonPath('withdrawal_status', StoreWithdrawalRequest::STATUS_MANUALLY_SETTLED);

    expect(app(StoreWithdrawalService::class)->getAvailableBalanceMinor($store, 'GBP'))->toBe(30000);

    // Settled withdrawal cannot be rejected
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.store-withdrawals.reject', $wd1Id), ['reason' => 'Too late'])
        ->assertUnprocessable();

    // Admin rejects #2 (£200.00) -> balance restored to £500.00
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.store-withdrawals.reject', $wd2Id), ['reason' => 'Account verification pending'])
        ->assertOk()
        ->assertJsonPath('withdrawal_status', StoreWithdrawalRequest::STATUS_REJECTED);

    expect(app(StoreWithdrawalService::class)->getAvailableBalanceMinor($store, 'GBP'))->toBe(50000);

    // Repeated rejection fails and does NOT duplicate release
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.store-withdrawals.reject', $wd2Id), ['reason' => 'Retry'])
        ->assertUnprocessable();

    expect(StoreTransaction::where('transaction_type', StoreTransaction::TYPE_WITHDRAWAL_RELEASE)->count())->toBe(1);

    // Current Account integration: settled withdrawal (£700.00) appears once as Cash Disbursement, rejected (£200.00) as Noncash Adjustment
    $caPaginator = app(CurrentAccountReportService::class)->getUnifiedTransactions([
        'month' => $currMonth->format('Y-m'),
        'currency' => 'GBP',
        'store_id' => $store->id,
        'sort_by' => 'date_desc',
    ]);
    $rows = collect($caPaginator->items());
    expect($rows->where('transaction_type', 'store_payout')->sum('money_out_minor'))->toBe(70000);
    expect($rows->where('transaction_type', 'store_withdrawal')->count())->toBe(1);
});
