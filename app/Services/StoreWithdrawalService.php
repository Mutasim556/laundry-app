<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingStoreEarning;
use App\Models\StoreAccount;
use App\Models\StoreBankAccount;
use App\Models\StoreTransaction;
use App\Models\StoreWithdrawalRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Models\Store;

class StoreWithdrawalService
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected StoreAccountService $storeAccountService,
        protected DeliveryPersonWithdrawalService $dpWithdrawalService
    ) {}

    public function resolveMonthPeriod(?string $month): array
    {
        return $this->dpWithdrawalService->resolveMonthPeriod($month);
    }

    public function getStorePrimaryCurrency(Store $store): string
    {
        $accCurrency = StoreAccount::query()
            ->where('store_id', $store->id)
            ->orderByDesc('current_balance')
            ->value('currency');

        if ($accCurrency) {
            return strtoupper((string) $accCurrency);
        }

        $earningCurrency = BookingStoreEarning::query()
            ->where('store_id', $store->id)
            ->where('is_current', true)
            ->orderByDesc('id')
            ->value('currency');

        return strtoupper((string) ($earningCurrency ?: $this->currencyService->code()));
    }

    public function getAvailableBalanceMinor(Store $store, string $currency): int
    {
        $acc = StoreAccount::query()
            ->where('store_id', $store->id)
            ->where('currency', strtoupper($currency))
            ->first();

        return $acc ? max(0, StoreAccountService::decimalToMinor($acc->current_balance)) : 0;
    }

    public function getMonthlySummary(Store $store, ?string $month, ?string $currency = null): array
    {
        $period = $this->resolveMonthPeriod($month);
        $cur = strtoupper((string) ($currency ?: $this->getStorePrimaryCurrency($store)));

        // Eligible completed booking earnings finalized in the selected month
        $monthEarnings = BookingStoreEarning::query()
            ->where('store_id', $store->id)
            ->where('is_current', true)
            ->where('currency', $cur)
            ->whereHas('booking', fn ($q) => $q->where('booking_status', Booking::STATUS_COMPLETED))
            ->whereRaw('COALESCE(finalized_at, created_at) >= ?', [$period['from_utc']])
            ->whereRaw('COALESCE(finalized_at, created_at) < ?', [$period['to_excl_utc']])
            ->get(['eligible_service_amount_minor', 'commission_amount_minor', 'net_store_earnings_minor']);

        $revenueMonthMinor = (int) $monthEarnings->sum('eligible_service_amount_minor');
        $commissionMonthMinor = (int) $monthEarnings->sum('commission_amount_minor');
        $storeShareMonthMinor = (int) $monthEarnings->sum('net_store_earnings_minor');

        $availableBalanceMinor = $this->getAvailableBalanceMinor($store, $cur);

        // Currently reserved pending withdrawals (pending + approved awaiting manual settlement)
        $pendingWithdrawalMinor = (int) StoreWithdrawalRequest::query()
            ->where('store_id', $store->id)
            ->where('currency', $cur)
            ->whereIn('status', [
                StoreWithdrawalRequest::STATUS_PENDING,
                StoreWithdrawalRequest::STATUS_APPROVED,
            ])
            ->sum('amount_minor');

        $approvedMonthMinor = (int) StoreWithdrawalRequest::query()
            ->where('store_id', $store->id)
            ->where('currency', $cur)
            ->where('request_month', $period['month'])
            ->whereIn('status', [
                StoreWithdrawalRequest::STATUS_APPROVED,
                StoreWithdrawalRequest::STATUS_MANUALLY_SETTLED,
            ])
            ->sum('amount_minor');

        $settledMonthMinor = (int) StoreWithdrawalRequest::query()
            ->where('store_id', $store->id)
            ->where('currency', $cur)
            ->where('request_month', $period['month'])
            ->where('status', StoreWithdrawalRequest::STATUS_MANUALLY_SETTLED)
            ->sum('amount_minor');

        return [
            'currency' => $cur,
            'period' => $period,
            'revenue_month_minor' => $revenueMonthMinor,
            'commission_month_minor' => $commissionMonthMinor,
            'store_share_month_minor' => $storeShareMonthMinor,
            'available_balance_minor' => $availableBalanceMinor,
            'pending_withdrawal_minor' => $pendingWithdrawalMinor,
            'approved_month_minor' => $approvedMonthMinor,
            'settled_month_minor' => $settledMonthMinor,
        ];
    }

    public function getMonthlyEarnings(
        Store $store,
        string $month,
        ?string $search = null,
        ?string $currency = null,
        int $perPage = 15,
        string $pageName = 'earnings_page'
    ): LengthAwarePaginator {
        $period = $this->resolveMonthPeriod($month);
        $cur = strtoupper((string) ($currency ?: $this->getStorePrimaryCurrency($store)));

        $query = BookingStoreEarning::query()
            ->with(['booking:id,reference,booking_status,payment_status'])
            ->where('store_id', $store->id)
            ->where('is_current', true)
            ->where('currency', $cur)
            ->whereRaw('COALESCE(finalized_at, created_at) >= ?', [$period['from_utc']])
            ->whereRaw('COALESCE(finalized_at, created_at) < ?', [$period['to_excl_utc']])
            ->orderByDesc('finalized_at')
            ->orderByDesc('id');

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->whereHas('booking', fn ($q) => $q->where('reference', 'like', "%{$term}%"));
        }

        return $query->paginate($perPage, ['*'], $pageName);
    }

    public function getMonthlyWithdrawals(
        Store $store,
        string $month,
        ?string $status = null,
        ?string $currency = null,
        int $perPage = 15,
        string $pageName = 'withdrawals_page'
    ): LengthAwarePaginator {
        $period = $this->resolveMonthPeriod($month);
        $cur = strtoupper((string) ($currency ?: $this->getStorePrimaryCurrency($store)));

        $query = StoreWithdrawalRequest::query()
            ->where('store_id', $store->id)
            ->where('currency', $cur)
            ->where('request_month', $period['month'])
            ->orderByDesc('id');

        if ($status && in_array($status, [
            StoreWithdrawalRequest::STATUS_PENDING,
            StoreWithdrawalRequest::STATUS_APPROVED,
            StoreWithdrawalRequest::STATUS_REJECTED,
            StoreWithdrawalRequest::STATUS_MANUALLY_SETTLED,
            StoreWithdrawalRequest::STATUS_CANCELLED,
        ], true)) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage, ['*'], $pageName);
    }

    public function getMonthlyAccountActivity(
        Store $store,
        string $month,
        ?string $currency = null,
        int $perPage = 15,
        string $pageName = 'ledger_page'
    ): LengthAwarePaginator {
        $period = $this->resolveMonthPeriod($month);
        $cur = strtoupper((string) ($currency ?: $this->getStorePrimaryCurrency($store)));

        return StoreTransaction::query()
            ->with('booking:id,reference')
            ->where('store_id', $store->id)
            ->where('currency', $cur)
            ->where('created_at', '>=', $period['from_utc'])
            ->where('created_at', '<', $period['to_excl_utc'])
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], $pageName);
    }

    public function submitWithdrawal(
        Store $store,
        int $bankAccountId,
        int $amountMinor,
        ?int $storeUserId = null,
        ?string $notes = null,
        ?string $idempotencyKey = null
    ): StoreWithdrawalRequest {
        if ($amountMinor <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Withdrawal amount must be greater than zero.'],
            ]);
        }

        if ($idempotencyKey) {
            $existing = StoreWithdrawalRequest::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($store, $bankAccountId, $amountMinor, $storeUserId, $notes, $idempotencyKey) {
            /** @var Store $lockedStore */
            $lockedStore = Store::query()->whereKey($store->id)->lockForUpdate()->firstOrFail();

            if ($lockedStore->status !== 'active') {
                throw ValidationException::withMessages([
                    'store' => ['Only active stores may request withdrawals.'],
                ]);
            }

            /** @var StoreBankAccount|null $bank */
            $bank = StoreBankAccount::query()
                ->where('id', $bankAccountId)
                ->where('store_id', $lockedStore->id)
                ->where('is_active', true)
                ->first();

            if (! $bank) {
                throw ValidationException::withMessages([
                    'bank_account_id' => ['Please select a valid active bank account belonging to this store.'],
                ]);
            }

            $cur = $this->getStorePrimaryCurrency($lockedStore);
            if (strtoupper((string) $bank->currency) !== $cur) {
                throw ValidationException::withMessages([
                    'bank_account_id' => ["Selected bank account currency ({$bank->currency}) must match your store account currency ({$cur})."],
                ]);
            }

            $availableBeforeMinor = $this->getAvailableBalanceMinor($lockedStore, $cur);

            if ($availableBeforeMinor <= 0 || $amountMinor > $availableBeforeMinor) {
                $formattedAvail = $this->currencyService->formatMinor($availableBeforeMinor, $cur);
                throw ValidationException::withMessages([
                    'amount' => ["Withdrawal amount exceeds your current available store balance ({$formattedAvail})."],
                ]);
            }

            $now = now();
            $requestMonth = $now->copy()->timezone(config('app.timezone', 'UTC'))->format('Y-m');
            $tempRef = 'SWD-' . $now->format('Y') . '-' . strtoupper(Str::random(6));

            $snapshot = [
                'bank_name' => $bank->bank_name,
                'account_holder_name' => $bank->account_holder_name,
                'country_code' => $bank->country_code,
                'currency' => $bank->currency,
                'account_type' => $bank->account_type,
                'account_number_last4' => $bank->account_number_last4,
                'account_number_masked' => $bank->account_number_masked,
                'routing_code_label' => $bank->routing_code_label,
                'swift_bic' => $bank->swift_bic,
            ];

            $resTx = $this->storeAccountService->postWithdrawalReservation(
                $lockedStore,
                $cur,
                $amountMinor,
                "Store withdrawal reservation to {$bank->bank_name} ({$bank->account_number_masked})",
                'store-wdl-res:' . ($idempotencyKey ?: Str::uuid()->toString())
            );

            $withdrawal = StoreWithdrawalRequest::create([
                'withdrawal_reference' => $tempRef,
                'store_id' => $lockedStore->id,
                'requested_by_store_user_id' => $storeUserId,
                'bank_account_id' => $bank->id,
                'bank_snapshot_json' => $snapshot,
                'amount_minor' => $amountMinor,
                'currency' => $cur,
                'request_month' => $requestMonth,
                'available_balance_before_minor' => $availableBeforeMinor,
                'status' => StoreWithdrawalRequest::STATUS_PENDING,
                'notes' => $notes ? trim($notes) : null,
                'idempotency_key' => $idempotencyKey,
                'reservation_transaction_id' => $resTx->id,
                'requested_at' => $now,
                'audit_history' => [
                    [
                        'event' => 'requested',
                        'actor_type' => 'store_user',
                        'actor_id' => $storeUserId,
                        'at' => $now->toIso8601String(),
                        'note' => "Requested store withdrawal to {$bank->bank_name} ({$bank->account_number_masked}). Balance reserved.",
                    ],
                ],
            ]);

            $withdrawal->withdrawal_reference = 'SWD-' . $now->format('Y') . '-' . str_pad((string) $withdrawal->id, 6, '0', STR_PAD_LEFT);
            $withdrawal->save();

            return $withdrawal->fresh();
        });
    }

    public function approveWithdrawal(
        StoreWithdrawalRequest $withdrawal,
        int $adminId,
        ?string $note = null,
        ?string $payoutMethod = 'bank_transfer',
        ?string $paidFromAccount = null
    ): StoreWithdrawalRequest {
        return DB::transaction(function () use ($withdrawal, $adminId, $note, $payoutMethod, $paidFromAccount) {
            /** @var StoreWithdrawalRequest $locked */
            $locked = StoreWithdrawalRequest::query()
                ->whereKey($withdrawal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== StoreWithdrawalRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => ['Only pending store withdrawal requests can be approved.'],
                ]);
            }

            if (! $locked->reservation_transaction_id) {
                throw ValidationException::withMessages([
                    'status' => ['Missing store ledger reservation entry for this withdrawal request.'],
                ]);
            }

            $method = in_array($payoutMethod, ['bank_transfer', 'cash', 'other'], true) ? $payoutMethod : 'bank_transfer';
            $methodLabel = match ($method) {
                'cash' => 'Hand Cash',
                'other' => 'Other Manual Method',
                default => 'Bank Transfer',
            };
            $fromAccount = $paidFromAccount ? trim($paidFromAccount) : null;

            $snapshot = is_array($locked->bank_snapshot_json) ? $locked->bank_snapshot_json : [];
            $snapshot['admin_payout_method'] = $method;
            $snapshot['admin_payout_method_label'] = $methodLabel;
            if ($fromAccount !== null && $fromAccount !== '') {
                $snapshot['admin_paid_from_account'] = $fromAccount;
            }

            $now = now();
            $history = is_array($locked->audit_history) ? $locked->audit_history : [];
            $detail = "Approved by Admin ({$methodLabel}" . ($fromAccount ? " from {$fromAccount}" : '') . ').';
            if ($note) {
                $detail .= ' ' . trim($note);
            }
            $history[] = [
                'event' => 'approved',
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'at' => $now->toIso8601String(),
                'note' => $detail,
            ];

            $locked->update([
                'status' => StoreWithdrawalRequest::STATUS_APPROVED,
                'bank_snapshot_json' => $snapshot,
                'approved_by_admin_id' => $adminId,
                'approved_at' => $now,
                'audit_history' => $history,
            ]);

            return $locked->fresh();
        });
    }

    public function rejectWithdrawal(
        StoreWithdrawalRequest $withdrawal,
        int $adminId,
        string $reason
    ): StoreWithdrawalRequest {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages([
                'reason' => ['A rejection reason is required.'],
            ]);
        }

        return DB::transaction(function () use ($withdrawal, $adminId, $cleanReason) {
            /** @var StoreWithdrawalRequest $locked */
            $locked = StoreWithdrawalRequest::query()
                ->with('store')
                ->whereKey($withdrawal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === StoreWithdrawalRequest::STATUS_MANUALLY_SETTLED || $locked->settled_at !== null) {
                throw ValidationException::withMessages([
                    'status' => ['A manually settled withdrawal cannot be rejected. Use a controlled adjustment if required.'],
                ]);
            }

            if (! in_array($locked->status, [
                StoreWithdrawalRequest::STATUS_PENDING,
                StoreWithdrawalRequest::STATUS_APPROVED,
            ], true) || $locked->release_transaction_id !== null) {
                throw ValidationException::withMessages([
                    'status' => ['This store withdrawal request has already been rejected or finalized.'],
                ]);
            }

            $releaseTx = $this->storeAccountService->postWithdrawalRelease(
                $locked->store,
                $locked->currency,
                $locked->amount_minor,
                "Store withdrawal {$locked->withdrawal_reference} rejected: {$cleanReason}",
                "store-wdl-rel:{$locked->id}",
                $adminId
            );

            $now = now();
            $history = is_array($locked->audit_history) ? $locked->audit_history : [];
            $history[] = [
                'event' => 'rejected',
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'at' => $now->toIso8601String(),
                'note' => "Rejected: {$cleanReason}. Reserved balance released.",
            ];

            $locked->update([
                'status' => StoreWithdrawalRequest::STATUS_REJECTED,
                'rejected_by_admin_id' => $adminId,
                'rejected_at' => $now,
                'rejection_reason' => $cleanReason,
                'release_transaction_id' => $releaseTx->id,
                'audit_history' => $history,
            ]);

            return $locked->fresh();
        });
    }

    public function markManuallySettled(
        StoreWithdrawalRequest $withdrawal,
        int $adminId,
        array $data
    ): StoreWithdrawalRequest {
        return DB::transaction(function () use ($withdrawal, $adminId, $data) {
            /** @var StoreWithdrawalRequest $locked */
            $locked = StoreWithdrawalRequest::query()
                ->whereKey($withdrawal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === StoreWithdrawalRequest::STATUS_MANUALLY_SETTLED || $locked->settled_at !== null) {
                throw ValidationException::withMessages([
                    'status' => ['This store withdrawal request has already been marked as manually settled.'],
                ]);
            }

            if ($locked->status !== StoreWithdrawalRequest::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'status' => ['Only approved store withdrawal requests can be marked as manually settled.'],
                ]);
            }

            $externalRef = ! empty($data['external_reference']) ? trim((string) $data['external_reference']) : null;
            if ($externalRef !== null) {
                $dup = StoreWithdrawalRequest::query()
                    ->where('external_reference', $externalRef)
                    ->where('id', '!=', $locked->id)
                    ->exists();
                if ($dup) {
                    throw ValidationException::withMessages([
                        'external_reference' => ['This external bank transaction reference has already been used.'],
                    ]);
                }
            }

            $paymentDate = (string) ($data['payment_date'] ?? now()->toDateString());
            $notes = ! empty($data['settlement_notes']) ? trim((string) $data['settlement_notes']) : null;
            $now = now();

            $history = is_array($locked->audit_history) ? $locked->audit_history : [];
            $history[] = [
                'event' => 'manually_settled',
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'at' => $now->toIso8601String(),
                'note' => "Marked manually settled on {$paymentDate}" . ($externalRef ? " (Ref: {$externalRef})" : '') . '.',
            ];

            $locked->update([
                'status' => StoreWithdrawalRequest::STATUS_MANUALLY_SETTLED,
                'settled_by_admin_id' => $adminId,
                'settled_at' => $now,
                'payment_date' => $paymentDate,
                'external_reference' => $externalRef,
                'settlement_notes' => $notes,
                'audit_history' => $history,
            ]);

            return $locked->fresh();
        });
    }
}
