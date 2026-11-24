<?php

namespace App\Services;

use App\Models\DeliveryPersonBankAccount;
use App\Models\DeliveryPersonWithdrawalRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use Modules\Admin\Models\DeliveryPersonPayout;

class DeliveryPersonWithdrawalService
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected DeliveryPersonCashService $cashService
    ) {}

    public function resolveMonthPeriod(?string $month): array
    {
        $tz = config('app.timezone', 'UTC');
        if ($month && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $startLocal = Carbon::createFromFormat('Y-m', $month, $tz)->startOfMonth();
        } else {
            $startLocal = Carbon::now($tz)->startOfMonth();
        }

        $endExclLocal = (clone $startLocal)->addMonth();
        $currentMonth = Carbon::now($tz)->format('Y-m');

        return [
            'month' => $startLocal->format('Y-m'),
            'label' => $startLocal->format('F Y'),
            'prev_month' => (clone $startLocal)->subMonth()->format('Y-m'),
            'next_month' => (clone $startLocal)->addMonth()->format('Y-m'),
            'current_month' => $currentMonth,
            'is_current_month' => $startLocal->format('Y-m') === $currentMonth,
            'from_utc' => $startLocal->copy()->utc(),
            'to_excl_utc' => $endExclLocal->copy()->utc(),
            'from_date' => $startLocal->toDateString(),
            'to_date_inclusive' => (clone $endExclLocal)->subDay()->toDateString(),
        ];
    }

    public function getMonthlySummary(DeliveryPerson $person, string $month, ?string $currency = null): array
    {
        $period = $this->resolveMonthPeriod($month);
        $cur = strtoupper((string) ($currency ?: $person->currency ?: $this->currencyService->code()));

        $monthEntries = DeliveryPersonLedgerEntry::query()
            ->with('assignment:id,assignment_type')
            ->where('delivery_person_id', $person->id)
            ->where('currency', $cur)
            ->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING)
            ->whereRaw('COALESCE(occurred_at, created_at) >= ?', [$period['from_utc']])
            ->whereRaw('COALESCE(occurred_at, created_at) < ?', [$period['to_excl_utc']])
            ->get();

        $collectionMonthMinor = 0;
        $finalDeliveryMonthMinor = 0;
        foreach ($monthEntries as $entry) {
            if ($entry->assignment?->assignment_type === DeliveryAssignment::TYPE_FINAL_DELIVERY) {
                $finalDeliveryMonthMinor += (int) $entry->amount_minor;
            } else {
                $collectionMonthMinor += (int) $entry->amount_minor;
            }
        }
        $earnedMonthMinor = $collectionMonthMinor + $finalDeliveryMonthMinor;

        // Current available balance across all months (unpaid prior-month earnings carry forward)
        $availableBalanceMinor = $this->cashService->getEarningsBalanceMinor($person, $cur);

        // Current reserved pending withdrawals (pending + approved but not manually settled)
        $pendingReservedTotalMinor = (int) DeliveryPersonWithdrawalRequest::query()
            ->where('delivery_person_id', $person->id)
            ->where('currency', $cur)
            ->whereIn('status', [
                DeliveryPersonWithdrawalRequest::STATUS_PENDING,
                DeliveryPersonWithdrawalRequest::STATUS_APPROVED,
            ])
            ->sum('amount_minor');

        $pendingMonthMinor = (int) DeliveryPersonWithdrawalRequest::query()
            ->where('delivery_person_id', $person->id)
            ->where('currency', $cur)
            ->where('request_month', $period['month'])
            ->where('status', DeliveryPersonWithdrawalRequest::STATUS_PENDING)
            ->sum('amount_minor');

        $approvedMonthMinor = (int) DeliveryPersonWithdrawalRequest::query()
            ->where('delivery_person_id', $person->id)
            ->where('currency', $cur)
            ->where('request_month', $period['month'])
            ->whereIn('status', [
                DeliveryPersonWithdrawalRequest::STATUS_APPROVED,
                DeliveryPersonWithdrawalRequest::STATUS_MANUALLY_SETTLED,
            ])
            ->sum('amount_minor');

        $rejectedMonthMinor = (int) DeliveryPersonWithdrawalRequest::query()
            ->where('delivery_person_id', $person->id)
            ->where('currency', $cur)
            ->where('request_month', $period['month'])
            ->where('status', DeliveryPersonWithdrawalRequest::STATUS_REJECTED)
            ->sum('amount_minor');

        $settledMonthMinor = (int) DeliveryPersonWithdrawalRequest::query()
            ->where('delivery_person_id', $person->id)
            ->where('currency', $cur)
            ->where('request_month', $period['month'])
            ->where('status', DeliveryPersonWithdrawalRequest::STATUS_MANUALLY_SETTLED)
            ->sum('amount_minor');

        return [
            'currency' => $cur,
            'period' => $period,
            'earned_month_minor' => $earnedMonthMinor,
            'collection_month_minor' => $collectionMonthMinor,
            'final_delivery_month_minor' => $finalDeliveryMonthMinor,
            'available_balance_minor' => $availableBalanceMinor,
            'pending_withdrawal_minor' => $pendingReservedTotalMinor,
            'pending_month_minor' => $pendingMonthMinor,
            'approved_month_minor' => $approvedMonthMinor,
            'rejected_month_minor' => $rejectedMonthMinor,
            'settled_month_minor' => $settledMonthMinor,
        ];
    }

    public function getMonthlyEarningsEntries(
        DeliveryPerson $person,
        string $month,
        ?string $jobType = null,
        int $perPage = 15,
        string $pageName = 'earnings_page'
    ): LengthAwarePaginator {
        $period = $this->resolveMonthPeriod($month);
        $cur = strtoupper((string) ($person->currency ?: $this->currencyService->code()));

        $query = DeliveryPersonLedgerEntry::query()
            ->with(['booking:id,reference', 'assignment:id,assignment_reference,assignment_type,earnings_status,rate_minor'])
            ->where('delivery_person_id', $person->id)
            ->where('currency', $cur)
            ->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING)
            ->whereRaw('COALESCE(occurred_at, created_at) >= ?', [$period['from_utc']])
            ->whereRaw('COALESCE(occurred_at, created_at) < ?', [$period['to_excl_utc']])
            ->orderByDesc('id');

        if ($jobType && in_array($jobType, [
            DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
            DeliveryAssignment::TYPE_FINAL_DELIVERY,
        ], true)) {
            $query->whereHas('assignment', fn ($aq) => $aq->where('assignment_type', $jobType));
        }

        return $query->paginate($perPage, ['*'], $pageName);
    }

    public function getMonthlyWithdrawals(
        DeliveryPerson $person,
        string $month,
        ?string $status = null,
        int $perPage = 15,
        string $pageName = 'withdrawals_page'
    ): LengthAwarePaginator {
        $period = $this->resolveMonthPeriod($month);
        $cur = strtoupper((string) ($person->currency ?: $this->currencyService->code()));

        $query = DeliveryPersonWithdrawalRequest::query()
            ->where('delivery_person_id', $person->id)
            ->where('currency', $cur)
            ->where('request_month', $period['month'])
            ->orderByDesc('id');

        if ($status && in_array($status, [
            DeliveryPersonWithdrawalRequest::STATUS_PENDING,
            DeliveryPersonWithdrawalRequest::STATUS_APPROVED,
            DeliveryPersonWithdrawalRequest::STATUS_REJECTED,
            DeliveryPersonWithdrawalRequest::STATUS_MANUALLY_SETTLED,
            DeliveryPersonWithdrawalRequest::STATUS_CANCELLED,
        ], true)) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage, ['*'], $pageName);
    }

    public function getMonthlyLedgerActivity(
        DeliveryPerson $person,
        string $month,
        int $perPage = 15,
        string $pageName = 'ledger_page'
    ): LengthAwarePaginator {
        $period = $this->resolveMonthPeriod($month);
        $cur = strtoupper((string) ($person->currency ?: $this->currencyService->code()));

        return DeliveryPersonLedgerEntry::query()
            ->with(['booking:id,reference', 'assignment:id,assignment_reference,assignment_type'])
            ->where('delivery_person_id', $person->id)
            ->where('currency', $cur)
            ->whereRaw('COALESCE(occurred_at, created_at) >= ?', [$period['from_utc']])
            ->whereRaw('COALESCE(occurred_at, created_at) < ?', [$period['to_excl_utc']])
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], $pageName);
    }

    public function submitWithdrawal(
        DeliveryPerson $person,
        int $bankAccountId,
        int $amountMinor,
        ?string $notes = null,
        ?string $idempotencyKey = null
    ): DeliveryPersonWithdrawalRequest {
        if ($amountMinor <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Withdrawal amount must be greater than zero.'],
            ]);
        }

        if ($idempotencyKey) {
            $existing = DeliveryPersonWithdrawalRequest::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($person, $bankAccountId, $amountMinor, $notes, $idempotencyKey) {
            /** @var DeliveryPerson $lockedPerson */
            $lockedPerson = DeliveryPerson::query()->whereKey($person->id)->lockForUpdate()->firstOrFail();
            $cur = strtoupper((string) ($lockedPerson->currency ?: $this->currencyService->code()));

            /** @var DeliveryPersonBankAccount|null $bank */
            $bank = DeliveryPersonBankAccount::query()
                ->where('id', $bankAccountId)
                ->where('delivery_person_id', $lockedPerson->id)
                ->where('is_active', true)
                ->first();

            if (! $bank) {
                throw ValidationException::withMessages([
                    'bank_account_id' => ['Please select a valid active bank account belonging to your profile.'],
                ]);
            }

            if (strtoupper((string) $bank->currency) !== $cur) {
                throw ValidationException::withMessages([
                    'bank_account_id' => ["Selected bank account currency ({$bank->currency}) must match your earnings currency ({$cur})."],
                ]);
            }

            // Recalculate real available balance inside the DB lock
            $availableBeforeMinor = $this->cashService->getEarningsBalanceMinor($lockedPerson, $cur);

            if ($availableBeforeMinor <= 0 || $amountMinor > $availableBeforeMinor) {
                $formattedAvail = $this->currencyService->formatMinor($availableBeforeMinor, $cur);
                throw ValidationException::withMessages([
                    'amount' => ["Withdrawal amount exceeds your current available balance ({$formattedAvail})."],
                ]);
            }

            $now = now();
            $requestMonth = $now->copy()->timezone(config('app.timezone', 'UTC'))->format('Y-m');
            $tempRef = 'WDL-' . $now->format('Y') . '-' . strtoupper(Str::random(6));

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

            $ledgerEntry = DeliveryPersonLedgerEntry::create([
                'entry_reference' => 'WDR-' . $now->format('Ymd') . '-' . strtoupper(Str::random(6)),
                'delivery_person_id' => $lockedPerson->id,
                'transaction_type' => DeliveryPersonLedgerEntry::TYPE_WITHDRAWAL_RESERVATION,
                'direction' => 'debit',
                'amount_minor' => $amountMinor,
                'currency' => $cur,
                'notes' => "Withdrawal reservation to {$bank->bank_name} ({$bank->account_number_masked})",
                'occurred_at' => $now,
            ]);

            $withdrawal = DeliveryPersonWithdrawalRequest::create([
                'withdrawal_reference' => $tempRef,
                'delivery_person_id' => $lockedPerson->id,
                'bank_account_id' => $bank->id,
                'bank_snapshot_json' => $snapshot,
                'amount_minor' => $amountMinor,
                'currency' => $cur,
                'request_month' => $requestMonth,
                'available_balance_before_minor' => $availableBeforeMinor,
                'status' => DeliveryPersonWithdrawalRequest::STATUS_PENDING,
                'notes' => $notes ? trim($notes) : null,
                'idempotency_key' => $idempotencyKey,
                'reservation_ledger_entry_id' => $ledgerEntry->id,
                'requested_at' => $now,
                'audit_history' => [
                    [
                        'event' => 'requested',
                        'actor_type' => 'delivery_person',
                        'actor_id' => $lockedPerson->id,
                        'at' => $now->toIso8601String(),
                        'note' => "Requested withdrawal to {$bank->bank_name} ({$bank->account_number_masked}). Balance reserved.",
                    ],
                ],
            ]);

            $withdrawal->withdrawal_reference = 'WDL-' . $now->format('Y') . '-' . str_pad((string) $withdrawal->id, 6, '0', STR_PAD_LEFT);
            $withdrawal->save();

            $ledgerEntry->notes = "Withdrawal reservation {$withdrawal->withdrawal_reference} ({$bank->bank_name} {$bank->account_number_masked})";
            $ledgerEntry->save();

            return $withdrawal->fresh();
        });
    }

    public function approveWithdrawal(
        DeliveryPersonWithdrawalRequest $withdrawal,
        int $adminId,
        ?string $note = null,
        ?string $payoutMethod = 'bank_transfer',
        ?string $paidFromAccount = null
    ): DeliveryPersonWithdrawalRequest {
        return DB::transaction(function () use ($withdrawal, $adminId, $note, $payoutMethod, $paidFromAccount) {
            /** @var DeliveryPersonWithdrawalRequest $locked */
            $locked = DeliveryPersonWithdrawalRequest::query()
                ->whereKey($withdrawal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== DeliveryPersonWithdrawalRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => ['Only pending withdrawal requests can be approved.'],
                ]);
            }

            if (! $locked->reservation_ledger_entry_id) {
                throw ValidationException::withMessages([
                    'status' => ['Missing ledger reservation entry for this withdrawal request.'],
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
                'status' => DeliveryPersonWithdrawalRequest::STATUS_APPROVED,
                'bank_snapshot_json' => $snapshot,
                'approved_by_admin_id' => $adminId,
                'approved_at' => $now,
                'audit_history' => $history,
            ]);

            return $locked->fresh();
        });
    }

    public function rejectWithdrawal(
        DeliveryPersonWithdrawalRequest $withdrawal,
        int $adminId,
        string $reason
    ): DeliveryPersonWithdrawalRequest {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages([
                'reason' => ['A rejection reason is required.'],
            ]);
        }

        return DB::transaction(function () use ($withdrawal, $adminId, $cleanReason) {
            /** @var DeliveryPersonWithdrawalRequest $locked */
            $locked = DeliveryPersonWithdrawalRequest::query()
                ->whereKey($withdrawal->id)
                ->lockForUpdate()
                ->firstOrFail();

            DeliveryPerson::query()->whereKey($locked->delivery_person_id)->lockForUpdate()->firstOrFail();

            if ($locked->status === DeliveryPersonWithdrawalRequest::STATUS_MANUALLY_SETTLED || $locked->settled_at !== null) {
                throw ValidationException::withMessages([
                    'status' => ['A manually settled withdrawal cannot be rejected. Use a controlled ledger adjustment if a post-settlement correction is required.'],
                ]);
            }

            if (! in_array($locked->status, [
                DeliveryPersonWithdrawalRequest::STATUS_PENDING,
                DeliveryPersonWithdrawalRequest::STATUS_APPROVED,
            ], true) || $locked->release_ledger_entry_id !== null) {
                throw ValidationException::withMessages([
                    'status' => ['This withdrawal request has already been rejected or finalized.'],
                ]);
            }

            $now = now();
            $releaseEntry = DeliveryPersonLedgerEntry::create([
                'entry_reference' => 'WDR-REL-' . $now->format('Ymd') . '-' . strtoupper(Str::random(5)),
                'delivery_person_id' => $locked->delivery_person_id,
                'transaction_type' => DeliveryPersonLedgerEntry::TYPE_WITHDRAWAL_RELEASE,
                'direction' => 'credit',
                'amount_minor' => $locked->amount_minor,
                'currency' => $locked->currency,
                'notes' => "Withdrawal {$locked->withdrawal_reference} rejected: {$cleanReason}",
                'created_by_admin_id' => $adminId,
                'occurred_at' => $now,
            ]);

            $history = is_array($locked->audit_history) ? $locked->audit_history : [];
            $history[] = [
                'event' => 'rejected',
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'at' => $now->toIso8601String(),
                'note' => "Rejected: {$cleanReason}. Reserved balance released.",
            ];

            $locked->update([
                'status' => DeliveryPersonWithdrawalRequest::STATUS_REJECTED,
                'rejected_by_admin_id' => $adminId,
                'rejected_at' => $now,
                'rejection_reason' => $cleanReason,
                'release_ledger_entry_id' => $releaseEntry->id,
                'audit_history' => $history,
            ]);

            return $locked->fresh();
        });
    }

    public function markManuallySettled(
        DeliveryPersonWithdrawalRequest $withdrawal,
        int $adminId,
        array $data
    ): DeliveryPersonWithdrawalRequest {
        return DB::transaction(function () use ($withdrawal, $adminId, $data) {
            /** @var DeliveryPersonWithdrawalRequest $locked */
            $locked = DeliveryPersonWithdrawalRequest::query()
                ->whereKey($withdrawal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === DeliveryPersonWithdrawalRequest::STATUS_MANUALLY_SETTLED || $locked->payout_id !== null) {
                throw ValidationException::withMessages([
                    'status' => ['This withdrawal request has already been marked as manually settled.'],
                ]);
            }

            if ($locked->status !== DeliveryPersonWithdrawalRequest::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'status' => ['Only approved withdrawal requests can be marked as manually settled.'],
                ]);
            }

            $externalRef = ! empty($data['external_reference']) ? trim((string) $data['external_reference']) : null;
            if ($externalRef !== null) {
                $dup = DeliveryPersonWithdrawalRequest::query()
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
            $snap = is_array($locked->bank_snapshot_json) ? $locked->bank_snapshot_json : [];
            $payoutMethod = in_array($snap['admin_payout_method'] ?? null, ['bank_transfer', 'cash', 'other'], true)
                ? $snap['admin_payout_method']
                : 'bank_transfer';
            $now = now();

            // Record DeliveryPersonPayout linked to the existing reservation ledger entry (NO second debit!)
            $payout = DeliveryPersonPayout::create([
                'payout_reference' => 'POUT-' . $now->format('Y') . '-' . strtoupper(Str::random(6)),
                'delivery_person_id' => $locked->delivery_person_id,
                'ledger_entry_id' => $locked->reservation_ledger_entry_id,
                'amount_minor' => $locked->amount_minor,
                'currency' => $locked->currency,
                'payment_method' => $payoutMethod,
                'external_reference' => $externalRef,
                'payment_date' => $paymentDate,
                'status' => 'recorded',
                'notes' => $notes ?: "Manual bank transfer settlement for {$locked->withdrawal_reference}",
                'idempotency_key' => "wdl-settle:{$locked->id}",
                'recorded_by_admin_id' => $adminId,
            ]);
            $payout->payout_reference = 'POUT-' . $now->format('Y') . '-' . str_pad((string) $payout->id, 6, '0', STR_PAD_LEFT);
            $payout->save();

            $history = is_array($locked->audit_history) ? $locked->audit_history : [];
            $history[] = [
                'event' => 'manually_settled',
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'at' => $now->toIso8601String(),
                'note' => "Marked manually settled on {$paymentDate}" . ($externalRef ? " (Ref: {$externalRef})" : '') . '.',
            ];

            $locked->update([
                'status' => DeliveryPersonWithdrawalRequest::STATUS_MANUALLY_SETTLED,
                'settled_by_admin_id' => $adminId,
                'settled_at' => $now,
                'payment_date' => $paymentDate,
                'external_reference' => $externalRef,
                'settlement_notes' => $notes,
                'payout_id' => $payout->id,
                'audit_history' => $history,
            ]);

            return $locked->fresh();
        });
    }
}
