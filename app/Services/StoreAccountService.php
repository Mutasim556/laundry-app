<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingStoreEarning;
use App\Models\StoreAccount;
use App\Models\StoreTransaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Models\Store;
use RuntimeException;

class StoreAccountService
{
    public function __construct(
        protected StoreEarningsService $earningsService,
        protected CurrencyService $currencyService
    ) {}

    public static function minorToDecimal(int $minor): string
    {
        $sign = $minor < 0 ? '-' : '';
        $abs = abs($minor);
        $whole = intdiv($abs, 100);
        $frac = $abs % 100;

        return sprintf('%s%d.%02d', $sign, $whole, $frac);
    }

    public static function decimalToMinor(string|int|float $decimal): int
    {
        $str = trim((string) $decimal);
        $neg = str_starts_with($str, '-');
        $str = ltrim($str, '+-');
        [$whole, $frac] = array_pad(explode('.', $str, 2), 2, '0');
        $wholeInt = (int) preg_replace('/\D+/', '', $whole);
        $fracPadded = str_pad(substr(preg_replace('/\D+/', '', $frac), 0, 2), 2, '0');
        $minor = ($wholeInt * 100) + (int) $fracPadded;

        return $neg ? -$minor : $minor;
    }

    public function creditCompletedBooking(Booking $booking, ?int $adminId = null): ?StoreTransaction
    {
        if ($booking->booking_status !== Booking::STATUS_COMPLETED) {
            return null;
        }

        $completedStages = $booking->getCompletedOperationalStatuses();
        if (! in_array(Booking::STATUS_COMPLETED, $completedStages, true)) {
            return null;
        }

        $earning = $this->earningsService->getOrCreateSnapshotForBooking($booking, $adminId);
        if (! $earning) {
            throw new RuntimeException("Cannot credit store account for booking {$booking->reference}: missing valid finalized store earning snapshot.");
        }

        $netMinor = (int) $earning->net_store_earnings_minor;
        if ($netMinor < 0) {
            throw new RuntimeException("Booking {$booking->reference} has negative net store earnings; posting blocked for financial review.");
        }

        if ($netMinor === 0) {
            if (! str_contains((string) $earning->notes, 'Zero net earnings')) {
                $earning->update(['notes' => trim(($earning->notes ? $earning->notes . ' ' : '') . 'Zero net earnings at completion; no positive credit posted.')]);
            }

            return null;
        }

        $idempotencyKey = "store-booking-earning:{$booking->id}:{$earning->id}";

        return DB::transaction(function () use ($booking, $earning, $netMinor, $idempotencyKey, $adminId) {
            $existing = StoreTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing) {
                return $existing;
            }

            /** @var StoreAccount $account */
            $account = $this->lockOrCreateAccount($earning->store_id, $earning->currency);

            $currentMinor = self::decimalToMinor($account->current_balance);
            $creditedMinor = self::decimalToMinor($account->total_credited);
            $newBalanceMinor = $currentMinor + $netMinor;
            $newCreditedMinor = $creditedMinor + $netMinor;

            try {
                $tx = StoreTransaction::create([
                    'store_id' => $earning->store_id,
                    'booking_id' => $booking->id,
                    'booking_store_earning_id' => $earning->id,
                    'transaction_type' => StoreTransaction::TYPE_BOOKING_EARNING,
                    'direction' => StoreTransaction::DIRECTION_CREDIT,
                    'amount' => self::minorToDecimal($netMinor),
                    'currency' => $earning->currency,
                    'balance_after' => self::minorToDecimal($newBalanceMinor),
                    'reference' => $this->generateUniqueReference(),
                    'idempotency_key' => $idempotencyKey,
                    'description' => "Booking {$booking->reference} final delivery completed (net after {$earning->commission_rate}% commission).",
                    'created_by_admin_id' => $adminId,
                ]);
            } catch (UniqueConstraintViolationException) {
                return StoreTransaction::where('idempotency_key', $idempotencyKey)->firstOrFail();
            }

            $account->update([
                'current_balance' => self::minorToDecimal($newBalanceMinor),
                'total_credited' => self::minorToDecimal($newCreditedMinor),
            ]);

            return $tx;
        });
    }

    public function createManualAdjustment(
        Store $store,
        string $currency,
        int $amountMinor,
        string $direction,
        string $reason,
        int $adminId,
        ?Booking $booking = null,
        ?string $idempotencyKey = null
    ): StoreTransaction {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Adjustment amount must be greater than zero.');
        }
        if (! in_array($direction, [StoreTransaction::DIRECTION_CREDIT, StoreTransaction::DIRECTION_DEBIT], true)) {
            throw new RuntimeException('Invalid adjustment direction.');
        }

        $type = $direction === StoreTransaction::DIRECTION_CREDIT
            ? StoreTransaction::TYPE_POSITIVE_ADJUSTMENT
            : StoreTransaction::TYPE_NEGATIVE_ADJUSTMENT;

        $key = $idempotencyKey ?: ('store-adj:' . $store->id . ':' . Str::uuid()->toString());

        return DB::transaction(function () use ($store, $currency, $amountMinor, $direction, $type, $reason, $adminId, $booking, $key) {
            if ($existing = StoreTransaction::where('idempotency_key', $key)->first()) {
                return $existing;
            }

            $account = $this->lockOrCreateAccount($store->id, $currency);
            $currentMinor = self::decimalToMinor($account->current_balance);
            $creditedMinor = self::decimalToMinor($account->total_credited);
            $debitedMinor = self::decimalToMinor($account->total_debited);

            if ($direction === StoreTransaction::DIRECTION_CREDIT) {
                $newBalanceMinor = $currentMinor + $amountMinor;
                $creditedMinor += $amountMinor;
            } else {
                $newBalanceMinor = $currentMinor - $amountMinor;
                $debitedMinor += $amountMinor;
            }

            $tx = StoreTransaction::create([
                'store_id' => $store->id,
                'booking_id' => $booking?->id,
                'transaction_type' => $type,
                'direction' => $direction,
                'amount' => self::minorToDecimal($amountMinor),
                'currency' => strtoupper($currency),
                'balance_after' => self::minorToDecimal($newBalanceMinor),
                'reference' => $this->generateUniqueReference(),
                'idempotency_key' => $key,
                'description' => trim($reason),
                'created_by_admin_id' => $adminId,
            ]);

            $account->update([
                'current_balance' => self::minorToDecimal($newBalanceMinor),
                'total_credited' => self::minorToDecimal($creditedMinor),
                'total_debited' => self::minorToDecimal($debitedMinor),
            ]);

            return $tx;
        });
    }

    public function postWithdrawalReservation(
        Store $store,
        string $currency,
        int $amountMinor,
        string $description,
        string $idempotencyKey
    ): StoreTransaction {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Withdrawal reservation amount must be greater than zero.');
        }

        return DB::transaction(function () use ($store, $currency, $amountMinor, $description, $idempotencyKey) {
            if ($existing = StoreTransaction::where('idempotency_key', $idempotencyKey)->first()) {
                return $existing;
            }

            $account = $this->lockOrCreateAccount($store->id, $currency);
            $currentMinor = self::decimalToMinor($account->current_balance);
            $debitedMinor = self::decimalToMinor($account->total_debited);

            if ($amountMinor > $currentMinor) {
                throw new RuntimeException('Insufficient store available balance for withdrawal reservation.');
            }

            $newBalanceMinor = $currentMinor - $amountMinor;
            $debitedMinor += $amountMinor;

            $tx = StoreTransaction::create([
                'store_id' => $store->id,
                'transaction_type' => StoreTransaction::TYPE_WITHDRAWAL_RESERVATION,
                'direction' => StoreTransaction::DIRECTION_DEBIT,
                'amount' => self::minorToDecimal($amountMinor),
                'currency' => strtoupper($currency),
                'balance_after' => self::minorToDecimal($newBalanceMinor),
                'reference' => $this->generateUniqueReference(),
                'idempotency_key' => $idempotencyKey,
                'description' => trim($description),
            ]);

            $account->update([
                'current_balance' => self::minorToDecimal($newBalanceMinor),
                'total_debited' => self::minorToDecimal($debitedMinor),
            ]);

            return $tx;
        });
    }

    public function postWithdrawalRelease(
        Store $store,
        string $currency,
        int $amountMinor,
        string $description,
        string $idempotencyKey,
        int $adminId
    ): StoreTransaction {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Withdrawal release amount must be greater than zero.');
        }

        return DB::transaction(function () use ($store, $currency, $amountMinor, $description, $idempotencyKey, $adminId) {
            if ($existing = StoreTransaction::where('idempotency_key', $idempotencyKey)->first()) {
                return $existing;
            }

            $account = $this->lockOrCreateAccount($store->id, $currency);
            $currentMinor = self::decimalToMinor($account->current_balance);
            $creditedMinor = self::decimalToMinor($account->total_credited);

            $newBalanceMinor = $currentMinor + $amountMinor;
            $creditedMinor += $amountMinor;

            $tx = StoreTransaction::create([
                'store_id' => $store->id,
                'transaction_type' => StoreTransaction::TYPE_WITHDRAWAL_RELEASE,
                'direction' => StoreTransaction::DIRECTION_CREDIT,
                'amount' => self::minorToDecimal($amountMinor),
                'currency' => strtoupper($currency),
                'balance_after' => self::minorToDecimal($newBalanceMinor),
                'reference' => $this->generateUniqueReference(),
                'idempotency_key' => $idempotencyKey,
                'description' => trim($description),
                'created_by_admin_id' => $adminId,
            ]);

            $account->update([
                'current_balance' => self::minorToDecimal($newBalanceMinor),
                'total_credited' => self::minorToDecimal($creditedMinor),
            ]);

            return $tx;
        });
    }

    public function createReversal(
        StoreTransaction $originalTx,
        string $reason,
        int $adminId
    ): StoreTransaction {
        $key = "store-reversal:{$originalTx->id}";

        return DB::transaction(function () use ($originalTx, $reason, $adminId, $key) {
            if ($existing = StoreTransaction::where('idempotency_key', $key)->first()) {
                return $existing;
            }

            $account = $this->lockOrCreateAccount($originalTx->store_id, $originalTx->currency);
            $amountMinor = self::decimalToMinor($originalTx->amount);
            $reverseDirection = $originalTx->direction === StoreTransaction::DIRECTION_CREDIT
                ? StoreTransaction::DIRECTION_DEBIT
                : StoreTransaction::DIRECTION_CREDIT;

            $currentMinor = self::decimalToMinor($account->current_balance);
            $creditedMinor = self::decimalToMinor($account->total_credited);
            $debitedMinor = self::decimalToMinor($account->total_debited);

            if ($reverseDirection === StoreTransaction::DIRECTION_CREDIT) {
                $newBalanceMinor = $currentMinor + $amountMinor;
                $creditedMinor += $amountMinor;
            } else {
                $newBalanceMinor = $currentMinor - $amountMinor;
                $debitedMinor += $amountMinor;
            }

            $tx = StoreTransaction::create([
                'store_id' => $originalTx->store_id,
                'booking_id' => $originalTx->booking_id,
                'booking_store_earning_id' => $originalTx->booking_store_earning_id,
                'transaction_type' => StoreTransaction::TYPE_REVERSAL,
                'direction' => $reverseDirection,
                'amount' => self::minorToDecimal($amountMinor),
                'currency' => $originalTx->currency,
                'balance_after' => self::minorToDecimal($newBalanceMinor),
                'reference' => $this->generateUniqueReference(),
                'idempotency_key' => $key,
                'related_transaction_id' => $originalTx->id,
                'description' => "Reversal of {$originalTx->reference}: " . trim($reason),
                'created_by_admin_id' => $adminId,
            ]);

            $account->update([
                'current_balance' => self::minorToDecimal($newBalanceMinor),
                'total_credited' => self::minorToDecimal($creditedMinor),
                'total_debited' => self::minorToDecimal($debitedMinor),
            ]);

            return $tx;
        });
    }

    /**
     * Build per-currency summary for a store (Current Balance, Pending Earnings, Total Earned, Total Paid Out).
     *
     * @return array<string, array{currency: string, current_balance_minor: int, pending_earnings_minor: int, total_earned_minor: int, total_paid_out_minor: int}>
     */
    public function getStoreAccountSummary(Store $store): array
    {
        $summary = [];

        $accounts = StoreAccount::where('store_id', $store->id)->get();
        foreach ($accounts as $acc) {
            $curr = strtoupper($acc->currency);
            $summary[$curr] = [
                'currency' => $curr,
                'current_balance_minor' => self::decimalToMinor($acc->current_balance),
                'pending_earnings_minor' => 0,
                'total_earned_minor' => 0,
                'total_paid_out_minor' => 0,
            ];
        }

        $txRows = StoreTransaction::where('store_id', $store->id)->get(['currency', 'transaction_type', 'direction', 'amount']);
        foreach ($txRows as $tx) {
            $curr = strtoupper($tx->currency);
            $summary[$curr] ??= [
                'currency' => $curr,
                'current_balance_minor' => 0,
                'pending_earnings_minor' => 0,
                'total_earned_minor' => 0,
                'total_paid_out_minor' => 0,
            ];

            $amt = self::decimalToMinor($tx->amount);
            if ($tx->transaction_type === StoreTransaction::TYPE_BOOKING_EARNING && $tx->direction === StoreTransaction::DIRECTION_CREDIT) {
                $summary[$curr]['total_earned_minor'] += $amt;
            } elseif ($tx->transaction_type === StoreTransaction::TYPE_REVERSAL && $tx->direction === StoreTransaction::DIRECTION_DEBIT && $tx->booking_store_earning_id !== null) {
                $summary[$curr]['total_earned_minor'] -= $amt;
            } elseif ($tx->transaction_type === StoreTransaction::TYPE_PAYOUT && $tx->direction === StoreTransaction::DIRECTION_DEBIT) {
                $summary[$curr]['total_paid_out_minor'] += $amt;
            }
        }

        // Pending earnings: finalized BookingStoreEarning for this store not yet posted as a StoreTransaction and not cancelled
        $postedEarningIds = StoreTransaction::where('store_id', $store->id)
            ->where('transaction_type', StoreTransaction::TYPE_BOOKING_EARNING)
            ->whereNotNull('booking_store_earning_id')
            ->pluck('booking_store_earning_id')
            ->all();

        $pendingEarnings = BookingStoreEarning::query()
            ->where('store_id', $store->id)
            ->where('is_current', true)
            ->when($postedEarningIds !== [], fn ($q) => $q->whereNotIn('id', $postedEarningIds))
            ->whereHas('booking', fn ($q) => $q->where('booking_status', '!=', Booking::STATUS_CANCELLED))
            ->get();

        foreach ($pendingEarnings as $pe) {
            $curr = strtoupper($pe->currency);
            $summary[$curr] ??= [
                'currency' => $curr,
                'current_balance_minor' => 0,
                'pending_earnings_minor' => 0,
                'total_earned_minor' => 0,
                'total_paid_out_minor' => 0,
            ];
            $summary[$curr]['pending_earnings_minor'] += (int) $pe->net_store_earnings_minor;
        }

        return $summary;
    }

    /**
     * Read-only financial reconciliation check across all store accounts and completed bookings.
     *
     * @return array<string, mixed>
     */
    public function reconcileAll(): array
    {
        $issues = [];

        // 1. Ledger vs cached balance
        foreach (StoreAccount::all() as $acc) {
            $txs = StoreTransaction::where('store_id', $acc->store_id)->where('currency', $acc->currency)->get();
            $netMinor = 0;
            foreach ($txs as $tx) {
                $amt = self::decimalToMinor($tx->amount);
                $netMinor += $tx->direction === StoreTransaction::DIRECTION_CREDIT ? $amt : -$amt;
            }
            $cachedMinor = self::decimalToMinor($acc->current_balance);
            if ($netMinor !== $cachedMinor) {
                $issues[] = "Balance mismatch for store #{$acc->store_id} ({$acc->currency}): cached={$acc->current_balance}, ledger=" . self::minorToDecimal($netMinor);
            }
            if ($cachedMinor < 0) {
                $issues[] = "Negative cached balance for store #{$acc->store_id} ({$acc->currency}): {$acc->current_balance}";
            }
        }

        // 2. Completed bookings with finalized earning > 0 missing booking_earning credit
        $missingCredits = Booking::query()
            ->where('booking_status', Booking::STATUS_COMPLETED)
            ->whereHas('currentStoreEarning', fn ($q) => $q->where('net_store_earnings_minor', '>', 0))
            ->whereDoesntHave('storeTransactions', fn ($q) => $q->where('transaction_type', StoreTransaction::TYPE_BOOKING_EARNING))
            ->pluck('reference')
            ->all();

        foreach ($missingCredits as $ref) {
            $issues[] = "Completed booking {$ref} is missing its store earning credit.";
        }

        // 3. Reversals without related_transaction_id
        $orphanReversals = StoreTransaction::where('transaction_type', StoreTransaction::TYPE_REVERSAL)
            ->whereNull('related_transaction_id')
            ->pluck('reference')
            ->all();
        foreach ($orphanReversals as $ref) {
            $issues[] = "Reversal transaction {$ref} is missing related_transaction_id.";
        }

        return [
            'ok' => $issues === [],
            'issues' => $issues,
        ];
    }

    protected function lockOrCreateAccount(int $storeId, string $currency): StoreAccount
    {
        $curr = strtoupper($currency);
        $account = StoreAccount::where('store_id', $storeId)->where('currency', $curr)->lockForUpdate()->first();
        if ($account) {
            return $account;
        }

        try {
            StoreAccount::create([
                'store_id' => $storeId,
                'currency' => $curr,
                'current_balance' => '0.00',
                'total_credited' => '0.00',
                'total_debited' => '0.00',
            ]);
        } catch (UniqueConstraintViolationException) {
            // Concurrent create raced; lock below
        }

        return StoreAccount::where('store_id', $storeId)->where('currency', $curr)->lockForUpdate()->firstOrFail();
    }

    protected function generateUniqueReference(): string
    {
        return 'STX-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
    }
}
