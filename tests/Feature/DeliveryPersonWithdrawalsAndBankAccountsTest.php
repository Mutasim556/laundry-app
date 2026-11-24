<?php

use App\Models\DeliveryPersonBankAccount;
use App\Models\DeliveryPersonWithdrawalRequest;
use App\Services\CurrentAccountReportService;
use App\Services\DeliveryPersonCashService;
use App\Services\DeliveryPersonWithdrawalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use Modules\Admin\Models\DeliveryPersonPayout;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function makeWdTestAdmin(): Admin
{
    $admin = Admin::create([
        'name' => 'Finance Admin',
        'email' => 'finadmin' . random_int(100, 999) . '@example.com',
        'password' => bcrypt('Admin2026!'),
        'status' => true,
    ]);

    foreach (['view-delivery-persons', 'manage-delivery-payouts', 'view-admin-accounts'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin']);
    }
    $admin->givePermissionTo(['view-delivery-persons', 'manage-delivery-payouts', 'view-admin-accounts']);

    return $admin;
}

function makeWdTestDeliveryPerson(string $currency = 'GBP'): DeliveryPerson
{
    return DeliveryPerson::create([
        'code' => 'DP-' . random_int(10000, 99999),
        'full_name' => 'Alex Rider ' . random_int(10, 99),
        'date_of_birth' => '1992-05-10',
        'email' => 'dp' . random_int(1000, 9999) . '@example.com',
        'phone_country_code' => '+44',
        'phone' => '07700' . random_int(100000, 999999),
        'phone_normalized' => '+447700' . random_int(100000, 999999),
        'address_line_1' => '10 Baker St',
        'city' => 'London',
        'residential_postcode' => 'NW1 6XE',
        'work_status' => 'active',
        'availability_status' => 'available',
        'currency' => $currency,
        'portal_access_enabled' => true,
        'password' => 'Driver2026!',
    ]);
}

function seedDpEarning(DeliveryPerson $dp, string $type, int $amountMinor, Carbon $occurredAt): DeliveryPersonLedgerEntry
{
    $booking = \App\Models\Booking::create([
        'reference' => 'LND-WD-' . random_int(100000, 999999),
        'customer_name' => 'Test Customer',
        'customer_email' => 'cust' . random_int(100, 999) . '@example.com',
        'customer_phone' => '+447700900001',
        'booking_status' => \App\Models\Booking::STATUS_COMPLETED,
        'currency' => $dp->currency,
        'estimated_amount_minor' => 5000,
        'final_amount_minor' => 5000,
        'payment_gateway' => 'stripe',
        'payment_status' => 'paid',
        'services_snapshot' => [],
        'contact_snapshot' => [],
        'fee_snapshot' => [],
        'address_snapshot' => ['full_address' => '10 Baker St', 'postcode' => 'NW1 6XE', 'country_code' => 'GB'],
        'schedule_snapshot' => [],
    ]);

    $asg = DeliveryAssignment::create([
        'assignment_reference' => ($type === DeliveryAssignment::TYPE_FINAL_DELIVERY ? 'DEL-' : 'COL-') . random_int(10000, 99999),
        'booking_id' => $booking->id,
        'delivery_person_id' => $dp->id,
        'assignment_type' => $type,
        'status' => DeliveryAssignment::STATUS_COMPLETED,
        'pickup_location' => '10 Baker St, London',
        'destination_location' => 'Demo Store, London',
        'rate_minor' => $amountMinor,
        'currency' => $dp->currency,
        'earnings_status' => 'credited',
    ]);

    return DeliveryPersonLedgerEntry::create([
        'entry_reference' => 'ERN-' . $occurredAt->format('Ymd') . '-' . random_int(1000, 9999),
        'delivery_person_id' => $dp->id,
        'delivery_assignment_id' => $asg->id,
        'transaction_type' => DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING,
        'direction' => 'credit',
        'amount_minor' => $amountMinor,
        'currency' => $dp->currency,
        'notes' => 'Completed job earning',
        'occurred_at' => $occurredAt,
    ]);
}

test('bank account ajax management, encryption, masking, ownership, and default switching', function () {
    $dp1 = makeWdTestDeliveryPerson('GBP');
    $dp2 = makeWdTestDeliveryPerson('GBP');

    // No bank account initially
    $this->actingAs($dp1, 'delivery_person')
        ->getJson(route('delivery-person.withdrawals.modal-data'))
        ->assertOk()
        ->assertJsonPath('has_bank_account', false);

    // Add first bank account via AJAX (auto-default)
    $res1 = $this->actingAs($dp1, 'delivery_person')
        ->postJson(route('delivery-person.bank-accounts.store'), [
            'country_code' => 'GB',
            'currency' => 'GBP',
            'account_holder_name' => $dp1->full_name,
            'bank_name' => 'Barclays UK',
            'account_type' => 'current',
            'account_number' => '12345678',
            'routing_code' => '20-00-00',
        ])->assertCreated()
        ->assertJsonPath('account.account_number_masked', '•••• 5678')
        ->assertJsonPath('account.is_default', true);

    $bank1Id = $res1->json('account.id');
    $rawDb = DB::table('delivery_person_bank_accounts')->where('id', $bank1Id)->first();
    expect($rawDb->account_number_encrypted)->not->toBe('12345678');
    expect($rawDb->account_number_masked)->toBe('•••• 5678');

    // Add second bank account
    $res2 = $this->actingAs($dp1, 'delivery_person')
        ->postJson(route('delivery-person.bank-accounts.store'), [
            'country_code' => 'GB',
            'currency' => 'GBP',
            'account_holder_name' => $dp1->full_name,
            'bank_name' => 'Monzo Bank',
            'account_number' => '87654321',
            'routing_code' => '040004',
        ])->assertCreated();

    $bank2Id = $res2->json('account.id');

    // Change default to second account
    $this->actingAs($dp1, 'delivery_person')
        ->postJson(route('delivery-person.bank-accounts.default', $bank2Id))
        ->assertOk()
        ->assertJsonPath('account.is_default', true);

    expect(DeliveryPersonBankAccount::find($bank1Id)->is_default)->toBeFalse();
    expect(DeliveryPersonBankAccount::find($bank2Id)->is_default)->toBeTrue();

    // Another DP cannot access or modify dp1's bank account
    $this->actingAs($dp2, 'delivery_person')
        ->postJson(route('delivery-person.bank-accounts.default', $bank1Id))
        ->assertForbidden();

    $this->actingAs($dp2, 'delivery_person')
        ->deleteJson(route('delivery-person.bank-accounts.destroy', $bank1Id))
        ->assertForbidden();
});

test('month-wise earnings carry forward, withdrawal reservation, admin approval, manual settlement, rejection release, and current account integration', function () {
    $admin = makeWdTestAdmin();
    $dp = makeWdTestDeliveryPerson('GBP');
    $otherDp = makeWdTestDeliveryPerson('GBP');

    // Disable FK check on booking_id for isolated unit-style assignment seeding in SQLite
    DB::statement('PRAGMA foreign_keys = OFF;');

    $now = Carbon::now();
    $prevMonth = (clone $now)->subMonth()->startOfMonth()->addDays(5);
    $currMonth = (clone $now)->startOfMonth()->addDays(2);

    // Prior month: £50.00 collection
    seedDpEarning($dp, DeliveryAssignment::TYPE_CUSTOMER_COLLECTION, 5000, $prevMonth);
    // Current month: £60.00 collection + £40.00 final delivery = £100.00
    seedDpEarning($dp, DeliveryAssignment::TYPE_CUSTOMER_COLLECTION, 6000, $currMonth);
    seedDpEarning($dp, DeliveryAssignment::TYPE_FINAL_DELIVERY, 4000, $currMonth);

    // Check month-wise summary via AJAX
    $this->actingAs($dp, 'delivery_person')
        ->getJson(route('delivery-person.earnings', ['month' => $currMonth->format('Y-m')]))
        ->assertOk()
        ->assertJsonPath('summary.collection_month_minor', 6000)
        ->assertJsonPath('summary.final_delivery_month_minor', 4000)
        ->assertJsonPath('summary.earned_month_minor', 10000)
        ->assertJsonPath('summary.available_balance_minor', 15000); // £50 carried forward + £100 this month

    // Save bank account for dp and otherDp
    $bank = DeliveryPersonBankAccount::create([
        'delivery_person_id' => $dp->id,
        'account_holder_name' => $dp->full_name,
        'bank_name' => 'HSBC UK',
        'country_code' => 'GB',
        'currency' => 'GBP',
        'account_number_encrypted' => '11223344',
        'account_number_last4' => '3344',
        'account_number_masked' => '•••• 3344',
        'routing_code_encrypted' => '400000',
        'routing_code_label' => 'Sort Code',
        'is_default' => true,
        'is_active' => true,
    ]);

    $otherBank = DeliveryPersonBankAccount::create([
        'delivery_person_id' => $otherDp->id,
        'account_holder_name' => $otherDp->full_name,
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

    // Validation guards: zero/negative, over-balance, another DP's bank
    $this->actingAs($dp, 'delivery_person')
        ->postJson(route('delivery-person.withdrawals.store'), ['bank_account_id' => $bank->id, 'amount' => 0])
        ->assertUnprocessable();

    $this->actingAs($dp, 'delivery_person')
        ->postJson(route('delivery-person.withdrawals.store'), ['bank_account_id' => $bank->id, 'amount' => 200.00])
        ->assertUnprocessable();

    $this->actingAs($dp, 'delivery_person')
        ->postJson(route('delivery-person.withdrawals.store'), ['bank_account_id' => $otherBank->id, 'amount' => 20.00])
        ->assertUnprocessable();

    // Submit valid withdrawal #1 (£100.00) with idempotency key
    $wd1Res = $this->actingAs($dp, 'delivery_person')
        ->postJson(route('delivery-person.withdrawals.store'), [
            'bank_account_id' => $bank->id,
            'amount' => 100.00,
            'idempotency_key' => 'idem-wd-1',
        ])->assertCreated();

    $wd1Id = $wd1Res->json('withdrawal.id');
    expect($wd1Res->json('summary.available_balance_minor'))->toBe(5000);
    expect($wd1Res->json('summary.pending_withdrawal_minor'))->toBe(10000);

    // Duplicate idempotency key does NOT create a second withdrawal or deduct twice
    $this->actingAs($dp, 'delivery_person')
        ->postJson(route('delivery-person.withdrawals.store'), [
            'bank_account_id' => $bank->id,
            'amount' => 100.00,
            'idempotency_key' => 'idem-wd-1',
        ])->assertCreated();

    expect(DeliveryPersonWithdrawalRequest::count())->toBe(1);
    expect(app(DeliveryPersonCashService::class)->getEarningsBalanceMinor($dp, 'GBP'))->toBe(5000);

    // Submit valid withdrawal #2 (£30.00)
    $wd2Res = $this->actingAs($dp, 'delivery_person')
        ->postJson(route('delivery-person.withdrawals.store'), [
            'bank_account_id' => $bank->id,
            'amount' => 30.00,
            'idempotency_key' => 'idem-wd-2',
        ])->assertCreated();
    $wd2Id = $wd2Res->json('withdrawal.id');
    expect(app(DeliveryPersonCashService::class)->getEarningsBalanceMinor($dp, 'GBP'))->toBe(2000);

    // Admin approves #1 -> available balance stays £20.00 (no second deduction)
    $this->actingAs($admin, 'admin')
        ->get(route('admin.delivery-person-withdrawals.index'))
        ->assertOk();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.delivery-person-withdrawals.approve', $wd1Id))
        ->assertOk()
        ->assertJsonPath('withdrawal_status', DeliveryPersonWithdrawalRequest::STATUS_APPROVED);

    expect(app(DeliveryPersonCashService::class)->getEarningsBalanceMinor($dp, 'GBP'))->toBe(2000);

    // Repeated approval fails
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.delivery-person-withdrawals.approve', $wd1Id))
        ->assertUnprocessable();

    // Admin marks #1 Manually Settled -> creates DeliveryPersonPayout without double-deducting ledger
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.delivery-person-withdrawals.settle', $wd1Id), [
            'payment_date' => $currMonth->toDateString(),
            'external_reference' => 'BACS-10001',
            'settlement_notes' => 'Transferred via manual bank transfer',
        ])->assertOk()
        ->assertJsonPath('withdrawal_status', DeliveryPersonWithdrawalRequest::STATUS_MANUALLY_SETTLED);

    expect(DeliveryPersonPayout::count())->toBe(1);
    expect(app(DeliveryPersonCashService::class)->getEarningsBalanceMinor($dp, 'GBP'))->toBe(2000);

    // Settled withdrawal cannot be rejected
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.delivery-person-withdrawals.reject', $wd1Id), ['reason' => 'Too late'])
        ->assertUnprocessable();

    // Admin rejects #2 (£30.00) -> balance restored to £50.00
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.delivery-person-withdrawals.reject', $wd2Id), ['reason' => 'Bank details mismatch'])
        ->assertOk()
        ->assertJsonPath('withdrawal_status', DeliveryPersonWithdrawalRequest::STATUS_REJECTED);

    expect(app(DeliveryPersonCashService::class)->getEarningsBalanceMinor($dp, 'GBP'))->toBe(5000);

    // Repeated rejection fails and does NOT duplicate release
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.delivery-person-withdrawals.reject', $wd2Id), ['reason' => 'Retry'])
        ->assertUnprocessable();

    expect(DeliveryPersonLedgerEntry::where('transaction_type', DeliveryPersonLedgerEntry::TYPE_WITHDRAWAL_RELEASE)->count())->toBe(1);

    // Current Account integration: settled payout appears once as Cash Disbursement (£100.00), rejected withdrawal appears as Noncash Adjustment
    $caPaginator = app(CurrentAccountReportService::class)->getUnifiedTransactions([
        'month' => $currMonth->format('Y-m'),
        'currency' => 'GBP',
        'delivery_person_id' => $dp->id,
        'sort_by' => 'date_desc',
    ]);
    $rows = collect($caPaginator->items());
    expect($rows->where('transaction_type', 'delivery_person_payout')->sum('money_out_minor'))->toBe(10000);
    expect($rows->where('transaction_type', 'delivery_person_withdrawal')->count())->toBe(1);
});
