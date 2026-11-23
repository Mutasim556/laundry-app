<?php

namespace App\Services;

use App\Models\AdminAccount;
use App\Models\AdminAccountTransaction;
use App\Models\Booking;
use App\Models\BookingFinancialSettlement;
use App\Models\BookingStoreEarning;
use App\Models\StoreTransaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Models\DeliveryAssignment;
use RuntimeException;

class AdminAccountService
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    public function getOrCreateAccount(string $currency): AdminAccount
    {
        $code = strtoupper(trim($currency)) ?: $this->currencyService->code();
        $account = AdminAccount::query()->where('currency', $code)->first();
        if ($account) {
            return $account;
        }

        try {
            return AdminAccount::query()->create([
                'currency' => $code,
                'opening_balance' => '0.00',
                'current_balance' => '0.00',
                'total_credited' => '0.00',
                'total_debited' => '0.00',
            ]);
        } catch (UniqueConstraintViolationException) {
            return AdminAccount::query()->where('currency', $code)->firstOrFail();
        }
    }

    public function lockOrCreateAccount(string $currency): AdminAccount
    {
        $code = strtoupper(trim($currency)) ?: $this->currencyService->code();
        $account = AdminAccount::query()->where('currency', $code)->lockForUpdate()->first();
        if ($account) {
            return $account;
        }

        $this->getOrCreateAccount($code);

        return AdminAccount::query()->where('currency', $code)->lockForUpdate()->firstOrFail();
    }

    public function postDeliveryMarginOrShortfall(
        Booking $booking,
        int $marginMinor,
        int $settlementId,
        ?int $adminId = null
    ): ?AdminAccountTransaction {
        if ($marginMinor === 0) {
            return null;
        }

        $currency = strtoupper((string) ($booking->currency ?: $this->currencyService->code()));
        $idempotencyKey = "booking:{$booking->id}:admin-delivery-margin:{$settlementId}";

        $existing = AdminAccountTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $isCredit = $marginMinor > 0;
        $type = $isCredit
            ? AdminAccountTransaction::TYPE_DELIVERY_MARGIN_CREDIT
            : AdminAccountTransaction::TYPE_DELIVERY_SHORTFALL_DEBIT;
        $direction = $isCredit
            ? AdminAccountTransaction::DIRECTION_CREDIT
            : AdminAccountTransaction::DIRECTION_DEBIT;
        $absMinor = abs($marginMinor);

        $description = $isCredit
            ? sprintf('Delivery fee margin retained on completed booking %s', $booking->reference)
            : sprintf('Delivery payout shortfall absorbed on completed booking %s', $booking->reference);

        return $this->postTransaction(
            currency: $currency,
            transactionType: $type,
            direction: $direction,
            amountMinor: $absMinor,
            description: $description,
            idempotencyKey: $idempotencyKey,
            bookingId: $booking->id,
            relatedTransactionId: null,
            adminId: $adminId
        );
    }

    public function createAdjustment(
        string $currency,
        string $direction,
        int $amountMinor,
        string $description,
        int $adminId,
        ?int $bookingId = null
    ): AdminAccountTransaction {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Adjustment amount must be greater than zero.');
        }

        if (! in_array($direction, [AdminAccountTransaction::DIRECTION_CREDIT, AdminAccountTransaction::DIRECTION_DEBIT], true)) {
            throw new RuntimeException('Invalid adjustment direction.');
        }

        return DB::transaction(function () use ($currency, $direction, $amountMinor, $description, $adminId, $bookingId) {
            return $this->postTransaction(
                currency: strtoupper(trim($currency)) ?: $this->currencyService->code(),
                transactionType: AdminAccountTransaction::TYPE_ADJUSTMENT,
                direction: $direction,
                amountMinor: $amountMinor,
                description: trim($description),
                idempotencyKey: 'admin-adjustment:' . Str::uuid(),
                bookingId: $bookingId,
                relatedTransactionId: null,
                adminId: $adminId
            );
        });
    }

    public function createReversal(
        AdminAccountTransaction $original,
        string $reason,
        int $adminId
    ): AdminAccountTransaction {
        if ($original->transaction_type === AdminAccountTransaction::TYPE_REVERSAL) {
            throw new RuntimeException('Cannot reverse a reversal transaction.');
        }

        return DB::transaction(function () use ($original, $reason, $adminId) {
            $alreadyReversed = AdminAccountTransaction::query()
                ->where('related_transaction_id', $original->id)
                ->where('transaction_type', AdminAccountTransaction::TYPE_REVERSAL)
                ->lockForUpdate()
                ->exists();

            if ($alreadyReversed) {
                throw new RuntimeException("Transaction {$original->reference} has already been reversed.");
            }

            $reverseDirection = $original->direction === AdminAccountTransaction::DIRECTION_CREDIT
                ? AdminAccountTransaction::DIRECTION_DEBIT
                : AdminAccountTransaction::DIRECTION_CREDIT;

            return $this->postTransaction(
                currency: $original->currency,
                transactionType: AdminAccountTransaction::TYPE_REVERSAL,
                direction: $reverseDirection,
                amountMinor: StoreAccountService::decimalToMinor($original->amount),
                description: sprintf('Reversal of %s: %s', $original->reference, trim($reason)),
                idempotencyKey: 'admin-reversal:' . $original->id,
                bookingId: $original->booking_id,
                relatedTransactionId: $original->id,
                adminId: $adminId
            );
        });
    }

    protected function postTransaction(
        string $currency,
        string $transactionType,
        string $direction,
        int $amountMinor,
        string $description,
        string $idempotencyKey,
        ?int $bookingId,
        ?int $relatedTransactionId,
        ?int $adminId
    ): AdminAccountTransaction {
        $existing = AdminAccountTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $account = $this->lockOrCreateAccount($currency);
        $currentMinor = StoreAccountService::decimalToMinor($account->current_balance);
        $creditedMinor = StoreAccountService::decimalToMinor($account->total_credited);
        $debitedMinor = StoreAccountService::decimalToMinor($account->total_debited);

        if ($direction === AdminAccountTransaction::DIRECTION_CREDIT) {
            $newBalanceMinor = $currentMinor + $amountMinor;
            $creditedMinor += $amountMinor;
        } else {
            $newBalanceMinor = $currentMinor - $amountMinor;
            $debitedMinor += $amountMinor;
        }

        $tx = AdminAccountTransaction::query()->create([
            'admin_account_id' => $account->id,
            'booking_id' => $bookingId,
            'transaction_type' => $transactionType,
            'direction' => $direction,
            'amount' => StoreAccountService::minorToDecimal($amountMinor),
            'currency' => $account->currency,
            'balance_after' => StoreAccountService::minorToDecimal($newBalanceMinor),
            'reference' => $this->generateReference(),
            'idempotency_key' => $idempotencyKey,
            'related_transaction_id' => $relatedTransactionId,
            'description' => $description,
            'created_by_admin_id' => $adminId,
        ]);

        $account->update([
            'current_balance' => StoreAccountService::minorToDecimal($newBalanceMinor),
            'total_credited' => StoreAccountService::minorToDecimal($creditedMinor),
            'total_debited' => StoreAccountService::minorToDecimal($debitedMinor),
        ]);

        return $tx;
    }

    protected function generateReference(): string
    {
        return 'ADM-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
    }

    /**
     * @return array<string, array{currency: string, current_balance_minor: int, total_credited_minor: int, total_debited_minor: int, awaiting_advance_minor: int}>
     */
    public function getSummaryByCurrency(?string $currencyFilter = null): array
    {
        $accountsQuery = AdminAccount::query();
        if ($currencyFilter) {
            $accountsQuery->where('currency', strtoupper($currencyFilter));
        }

        $accounts = $accountsQuery->get();
        $result = [];

        foreach ($accounts as $account) {
            $code = strtoupper($account->currency);
            $result[$code] = [
                'currency' => $code,
                'current_balance_minor' => StoreAccountService::decimalToMinor($account->current_balance),
                'total_credited_minor' => StoreAccountService::decimalToMinor($account->total_credited),
                'total_debited_minor' => StoreAccountService::decimalToMinor($account->total_debited),
                'awaiting_advance_minor' => 0,
            ];
        }

        if (empty($result)) {
            $defaultCode = strtoupper($currencyFilter ?: $this->currencyService->code());
            $result[$defaultCode] = [
                'currency' => $defaultCode,
                'current_balance_minor' => 0,
                'total_credited_minor' => 0,
                'total_debited_minor' => 0,
                'awaiting_advance_minor' => 0,
            ];
        }

        $awaitingBookings = Booking::query()
            ->whereIn('payment_status', ['paid', 'authorized'])
            ->where('booking_status', '!=', Booking::STATUS_COMPLETED)
            ->whereNull('cancelled_at')
            ->when($currencyFilter, fn ($q) => $q->where('currency', strtoupper($currencyFilter)))
            ->get(['currency', 'final_amount_minor', 'estimated_amount_minor', 'refunded_amount_minor']);

        foreach ($awaitingBookings as $booking) {
            $code = strtoupper((string) ($booking->currency ?: $this->currencyService->code()));
            if (! isset($result[$code])) {
                $result[$code] = [
                    'currency' => $code,
                    'current_balance_minor' => 0,
                    'total_credited_minor' => 0,
                    'total_debited_minor' => 0,
                    'awaiting_advance_minor' => 0,
                ];
            }

            $grossMinor = (int) ($booking->final_amount_minor ?? $booking->estimated_amount_minor ?? 0);
            $refundedMinor = (int) ($booking->refunded_amount_minor ?? 0);
            $netAdvanceMinor = max(0, $grossMinor - $refundedMinor);
            $result[$code]['awaiting_advance_minor'] += $netAdvanceMinor;
        }

        return $result;
    }

    /**
     * Read-only diagnostic across completed bookings and AdminAccount balances.
     *
     * @return array{issues: array<int, array<string, mixed>>, checked_bookings: int, checked_accounts: int}
     */
    public function reconcileAll(): array
    {
        $issues = [];
        $checkedBookings = 0;

        $completedBookings = Booking::query()
            ->where('booking_status', Booking::STATUS_COMPLETED)
            ->whereNull('cancelled_at')
            ->with(['currentStoreAssignment', 'currentCollectionAssignment', 'currentFinalDeliveryAssignment', 'currentFinancialSettlement'])
            ->get();

        foreach ($completedBookings as $booking) {
            $checkedBookings++;
            $settlement = $booking->currentFinancialSettlement;

            if (! $settlement) {
                $issues[] = [
                    'type' => 'missing_financial_settlement',
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->reference,
                    'detail' => 'Completed booking has no current BookingFinancialSettlement record.',
                ];
                continue;
            }

            $storeCreditsCount = StoreTransaction::query()
                ->where('booking_id', $booking->id)
                ->where('transaction_type', StoreTransaction::TYPE_BOOKING_EARNING)
                ->count();
            if ($booking->currentStoreAssignment && $storeCreditsCount !== 1) {
                $issues[] = [
                    'type' => $storeCreditsCount === 0 ? 'missing_store_credit' : 'duplicate_store_credit',
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->reference,
                    'detail' => "Expected 1 store booking_credit transaction, found {$storeCreditsCount}.",
                ];
            }

            $collectionAssignment = $booking->currentCollectionAssignment;
            if ($collectionAssignment) {
                $colKey = "booking:{$booking->id}:collection-earning:{$collectionAssignment->id}";
                $colLedgerCount = DB::table('delivery_person_ledger_entries')
                    ->where('idempotency_key', $colKey)
                    ->count();
                if ($colLedgerCount !== 1) {
                    $issues[] = [
                        'type' => $colLedgerCount === 0 ? 'missing_collection_ledger' : 'duplicate_collection_ledger',
                        'booking_id' => $booking->id,
                        'booking_reference' => $booking->reference,
                        'detail' => "Expected 1 collection delivery ledger entry ({$colKey}), found {$colLedgerCount}.",
                    ];
                }
            }

            $finalAssignment = $booking->currentFinalDeliveryAssignment;
            if ($finalAssignment) {
                $finalKey = "booking:{$booking->id}:final-earning:{$finalAssignment->id}";
                $finalLedgerCount = DB::table('delivery_person_ledger_entries')
                    ->where('idempotency_key', $finalKey)
                    ->count();
                if ($finalLedgerCount !== 1) {
                    $issues[] = [
                        'type' => $finalLedgerCount === 0 ? 'missing_final_ledger' : 'duplicate_final_ledger',
                        'booking_id' => $booking->id,
                        'booking_reference' => $booking->reference,
                        'detail' => "Expected 1 final delivery ledger entry ({$finalKey}), found {$finalLedgerCount}.",
                    ];
                }
            }

            $expectedMargin = (int) $settlement->delivery_fee_minor
                - ((int) $settlement->collection_delivery_payment_minor + (int) $settlement->final_delivery_payment_minor);
            if ($expectedMargin !== (int) $settlement->admin_delivery_margin_minor) {
                $issues[] = [
                    'type' => 'margin_mismatch',
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->reference,
                    'detail' => "Settlement margin {$settlement->admin_delivery_margin_minor} != expected {$expectedMargin}.",
                ];
            }

            $marginKey = "booking:{$booking->id}:admin-delivery-margin:{$settlement->id}";
            $adminTxCount = AdminAccountTransaction::query()->where('idempotency_key', $marginKey)->count();
            if ($expectedMargin !== 0 && $adminTxCount !== 1) {
                $issues[] = [
                    'type' => $adminTxCount === 0 ? 'missing_admin_margin_tx' : 'duplicate_admin_margin_tx',
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->reference,
                    'detail' => "Expected 1 admin margin transaction ({$marginKey}), found {$adminTxCount}.",
                ];
            } elseif ($expectedMargin === 0 && $adminTxCount !== 0) {
                $issues[] = [
                    'type' => 'unexpected_zero_margin_tx',
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->reference,
                    'detail' => "Zero margin should have 0 admin margin transactions, found {$adminTxCount}.",
                ];
            }

            $tipSum = (int) $settlement->collection_tip_share_minor + (int) $settlement->final_tip_share_minor;
            if ($tipSum !== (int) $settlement->customer_tip_minor) {
                $issues[] = [
                    'type' => 'tip_split_mismatch',
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->reference,
                    'detail' => "Tip split sum {$tipSum} != customer tip {$settlement->customer_tip_minor}.",
                ];
            }
        }

        $accounts = AdminAccount::query()->get();
        foreach ($accounts as $account) {
            $creditsMinor = 0;
            $debitsMinor = 0;
            $txs = AdminAccountTransaction::query()->where('admin_account_id', $account->id)->get(['direction', 'amount']);
            foreach ($txs as $tx) {
                $amtMinor = StoreAccountService::decimalToMinor($tx->amount);
                if ($tx->direction === AdminAccountTransaction::DIRECTION_CREDIT) {
                    $creditsMinor += $amtMinor;
                } else {
                    $debitsMinor += $amtMinor;
                }
            }
            $expectedBalanceMinor = $creditsMinor - $debitsMinor;
            $cachedBalanceMinor = StoreAccountService::decimalToMinor($account->current_balance);
            if ($expectedBalanceMinor !== $cachedBalanceMinor) {
                $issues[] = [
                    'type' => 'admin_account_balance_mismatch',
                    'currency' => $account->currency,
                    'detail' => "AdminAccount ({$account->currency}) cached balance {$cachedBalanceMinor} != computed {$expectedBalanceMinor}.",
                ];
            }
        }

        return [
            'issues' => $issues,
            'checked_bookings' => $checkedBookings,
            'checked_accounts' => $accounts->count(),
        ];
    }
}
