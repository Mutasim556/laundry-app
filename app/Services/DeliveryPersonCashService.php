<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingCustomerPayment;
use App\Models\DeliveryPersonCashTransaction;
use App\Models\DeliveryPersonSettlement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Models\DeliveryPerson;
use RuntimeException;

class DeliveryPersonCashService
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    /**
     * @return array{
     *   currency: string,
     *   cash_collected_minor: int,
     *   cash_settled_minor: int,
     *   cash_in_hand_minor: int,
     *   online_transfer_confirmed_minor: int,
     *   online_transfer_awaiting_verification_minor: int,
     *   total_settled_minor: int
     * }
     */
    public function getSummary(DeliveryPerson $person, ?string $currency = null): array
    {
        $code = strtoupper((string) ($currency ?: $person->currency ?: $this->currencyService->code()));

        $txs = DeliveryPersonCashTransaction::query()
            ->where('delivery_person_id', $person->id)
            ->where('currency', $code)
            ->orderBy('id')
            ->get(['transaction_type', 'direction', 'amount', 'payment_channel']);

        $cashCollectedMinor = 0;
        $cashSettledMinor = 0;
        $onlineConfirmedMinor = 0;
        $totalSettledMinor = 0;
        $adjustmentsNetMinor = 0;

        foreach ($txs as $tx) {
            $amt = StoreAccountService::decimalToMinor($tx->amount);
            if ($tx->transaction_type === DeliveryPersonCashTransaction::TYPE_CUSTOMER_CASH_COLLECTED && $tx->direction === 'credit') {
                $cashCollectedMinor += $amt;
            } elseif ($tx->transaction_type === DeliveryPersonCashTransaction::TYPE_CASH_HANDOVER_TO_ADMIN && $tx->direction === 'debit') {
                $cashSettledMinor += $amt;
                $totalSettledMinor += $amt;
            } elseif ($tx->transaction_type === DeliveryPersonCashTransaction::TYPE_CUSTOMER_TRANSFER_CONFIRMED && $tx->direction === 'credit') {
                $onlineConfirmedMinor += $amt;
            } elseif ($tx->transaction_type === DeliveryPersonCashTransaction::TYPE_ONLINE_FUNDS_SETTLEMENT && $tx->direction === 'debit') {
                $totalSettledMinor += $amt;
            } elseif ($tx->transaction_type === DeliveryPersonCashTransaction::TYPE_AUTHORIZED_ADJUSTMENT) {
                $adjustmentsNetMinor += ($tx->direction === 'credit' ? $amt : -$amt);
            }
        }

        $cashInHandMinor = max(0, $cashCollectedMinor - $cashSettledMinor + $adjustmentsNetMinor);

        $awaitingVerificationMinor = (int) BookingCustomerPayment::query()
            ->where('delivery_person_id', $person->id)
            ->where('currency', $code)
            ->where('payment_method', BookingCustomerPayment::METHOD_ONLINE_TRANSFER)
            ->where('status', BookingCustomerPayment::STATUS_AWAITING_VERIFICATION)
            ->sum('amount_minor');

        return [
            'currency' => $code,
            'cash_collected_minor' => $cashCollectedMinor,
            'cash_settled_minor' => $cashSettledMinor,
            'cash_in_hand_minor' => $cashInHandMinor,
            'online_transfer_confirmed_minor' => $onlineConfirmedMinor,
            'online_transfer_awaiting_verification_minor' => $awaitingVerificationMinor,
            'total_settled_minor' => $totalSettledMinor,
        ];
    }

    public function getEarningsBalanceMinor(DeliveryPerson $person, ?string $currency = null): int
    {
        $code = strtoupper((string) ($currency ?: $person->currency ?: $this->currencyService->code()));

        $credits = (int) DB::table('delivery_person_ledger_entries')
            ->where('delivery_person_id', $person->id)
            ->where('currency', $code)
            ->where('direction', 'credit')
            ->sum('amount_minor');

        $debits = (int) DB::table('delivery_person_ledger_entries')
            ->where('delivery_person_id', $person->id)
            ->where('currency', $code)
            ->where('direction', 'debit')
            ->sum('amount_minor');

        return max(0, $credits - $debits);
    }

    public function postCashCustodyTransaction(
        DeliveryPerson $person,
        string $transactionType,
        string $direction,
        int $amountMinor,
        string $currency,
        string $paymentChannel,
        string $description,
        string $idempotencyKey,
        ?int $bookingId = null,
        ?int $settlementId = null,
        string $createdByType = 'system',
        ?int $createdById = null
    ): DeliveryPersonCashTransaction {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Cash custody transaction amount must be greater than zero.');
        }

        return DB::transaction(function () use (
            $person,
            $transactionType,
            $direction,
            $amountMinor,
            $currency,
            $paymentChannel,
            $description,
            $idempotencyKey,
            $bookingId,
            $settlementId,
            $createdByType,
            $createdById
        ) {
            $existing = DeliveryPersonCashTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing) {
                return $existing;
            }

            $code = strtoupper(trim($currency)) ?: $this->currencyService->code();

            $latest = DeliveryPersonCashTransaction::query()
                ->where('delivery_person_id', $person->id)
                ->where('currency', $code)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $currentMinor = $latest ? StoreAccountService::decimalToMinor($latest->balance_after) : 0;
            $newMinor = $direction === 'credit'
                ? $currentMinor + $amountMinor
                : $currentMinor - $amountMinor;

            return DeliveryPersonCashTransaction::query()->create([
                'delivery_person_id' => $person->id,
                'booking_id' => $bookingId,
                'settlement_id' => $settlementId,
                'transaction_type' => $transactionType,
                'direction' => $direction,
                'amount' => StoreAccountService::minorToDecimal($amountMinor),
                'currency' => $code,
                'balance_after' => StoreAccountService::minorToDecimal($newMinor),
                'payment_channel' => $paymentChannel,
                'reference' => 'DPC-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                'idempotency_key' => $idempotencyKey,
                'description' => $description,
                'created_by_type' => $createdByType,
                'created_by_id' => $createdById,
            ]);
        });
    }

    public function recordAdminSettlement(
        DeliveryPerson $person,
        int $amountMinor,
        string $currency,
        string $settlementChannel,
        int $adminId,
        ?string $notes = null,
        ?string $idempotencyKey = null
    ): DeliveryPersonSettlement {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Settlement amount must be greater than zero.');
        }

        $code = strtoupper(trim($currency)) ?: $this->currencyService->code();
        $key = $idempotencyKey ?: ('dp-settlement:' . $person->id . ':' . Str::uuid());

        return DB::transaction(function () use ($person, $amountMinor, $code, $settlementChannel, $adminId, $notes, $key) {
            $existing = DeliveryPersonSettlement::query()->where('idempotency_key', $key)->first();
            if ($existing) {
                return $existing;
            }

            // Lock existing custody rows for this Delivery Person
            DeliveryPersonCashTransaction::query()
                ->where('delivery_person_id', $person->id)
                ->where('currency', $code)
                ->lockForUpdate()
                ->get(['id']);

            $summary = $this->getSummary($person, $code);
            if ($amountMinor > $summary['cash_in_hand_minor']) {
                throw new RuntimeException(
                    'Cannot settle more than verified Cash in Hand ('
                    . $this->currencyService->formatMinor($summary['cash_in_hand_minor'], $code)
                    . ').'
                );
            }

            $settlement = DeliveryPersonSettlement::query()->create([
                'settlement_reference' => 'DPS-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5)),
                'delivery_person_id' => $person->id,
                'admin_id' => $adminId,
                'amount' => StoreAccountService::minorToDecimal($amountMinor),
                'currency' => $code,
                'settlement_channel' => $settlementChannel ?: 'cash_handover',
                'settled_at' => now(),
                'notes' => $notes ? trim($notes) : null,
                'idempotency_key' => $key,
            ]);

            $this->postCashCustodyTransaction(
                person: $person,
                transactionType: DeliveryPersonCashTransaction::TYPE_CASH_HANDOVER_TO_ADMIN,
                direction: DeliveryPersonCashTransaction::DIRECTION_DEBIT,
                amountMinor: $amountMinor,
                currency: $code,
                paymentChannel: DeliveryPersonCashTransaction::CHANNEL_ADMIN_SETTLEMENT,
                description: sprintf('Cash handover settled with admin (%s)', $settlement->settlement_reference),
                idempotencyKey: 'dp-settlement-tx:' . $settlement->id,
                bookingId: null,
                settlementId: $settlement->id,
                createdByType: 'admin',
                createdById: $adminId
            );

            return $settlement;
        });
    }
}
