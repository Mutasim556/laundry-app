<?php

namespace App\Services;

use App\Models\AdminAccount;
use App\Models\AdminAccountTransaction;
use App\Models\Booking;
use App\Models\BookingCustomerPayment;
use App\Models\BookingStoreEarning;
use App\Models\DeliveryPersonCashTransaction;
use App\Models\DeliveryPersonSettlement;
use App\Models\StoreAccount;
use App\Models\StoreTransaction;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use Modules\Admin\Models\DeliveryPersonPayout;

class CurrentAccountReportService
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected AdminAccountService $adminAccountService
    ) {}

    public function getAvailableCurrencies(): array
    {
        $currencies = array_merge(
            [$this->currencyService->code()],
            AdminAccount::query()->pluck('currency')->all(),
            Booking::query()->whereNotNull('currency')->distinct()->pluck('currency')->all(),
            BookingCustomerPayment::query()->whereNotNull('currency')->distinct()->pluck('currency')->all(),
            StoreAccount::query()->whereNotNull('currency')->distinct()->pluck('currency')->all(),
            DeliveryPersonLedgerEntry::query()->whereNotNull('currency')->distinct()->pluck('currency')->all(),
            DeliveryPersonSettlement::query()->whereNotNull('currency')->distinct()->pluck('currency')->all()
        );

        return array_values(array_unique(array_filter(array_map(
            fn ($c) => strtoupper(trim((string) $c)),
            $currencies
        ))));
    }

    /**
     * Resolve reporting period bounds in the application timezone converted to UTC for DB queries.
     *
     * @return array{month: string, month_label: string, tz: string, from_utc: Carbon, to_exclusive_utc: Carbon, from_date: string, to_date: string, is_custom_range: bool}
     */
    public function resolvePeriod(?string $month = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $tz = (string) config('app.timezone', 'UTC');
        $nowLocal = Carbon::now($tz);

        $selectedMonth = ($month && preg_match('/^\d{4}-\d{2}$/', $month))
            ? $month
            : $nowLocal->format('Y-m');

        $monthStartLocal = Carbon::createFromFormat('Y-m', $selectedMonth, $tz)->startOfMonth();
        $monthEndExclusiveLocal = $monthStartLocal->copy()->addMonth();

        $isCustom = ! empty($fromDate) || ! empty($toDate);
        $fromLocal = ! empty($fromDate) ? Carbon::parse($fromDate, $tz)->startOfDay() : $monthStartLocal;
        $toExclusiveLocal = ! empty($toDate) ? Carbon::parse($toDate, $tz)->addDay()->startOfDay() : $monthEndExclusiveLocal;

        return [
            'month' => $monthStartLocal->format('Y-m'),
            'month_label' => $monthStartLocal->format('F Y'),
            'prev_month' => $monthStartLocal->copy()->subMonth()->format('Y-m'),
            'next_month' => $monthStartLocal->copy()->addMonth()->format('Y-m'),
            'current_month' => $nowLocal->format('Y-m'),
            'is_current_or_future' => $monthStartLocal->format('Y-m') >= $nowLocal->format('Y-m'),
            'tz' => $tz,
            'from_utc' => $fromLocal->copy()->utc(),
            'to_exclusive_utc' => $toExclusiveLocal->copy()->utc(),
            'from_date' => $fromLocal->toDateString(),
            'to_date_inclusive' => $toExclusiveLocal->copy()->subDay()->toDateString(),
            'is_custom_range' => $isCustom,
        ];
    }

    /**
     * Build verified multi-source financial summary for a single currency & month (never mixes currencies).
     *
     * @return array<string, mixed>
     */
    public function getDashboardSummary(string $currency, ?string $fromDate = null, ?string $toDate = null, ?string $month = null): array
    {
        $cur = strtoupper(trim($currency)) ?: $this->currencyService->code();
        $period = $this->resolvePeriod($month, $fromDate, $toDate);
        $fromUtc = $period['from_utc'];
        $toExclUtc = $period['to_exclusive_utc'];

        // 1. Customer Receipts (BookingCustomerPayment: ONLY confirmed or refunded; exclude pending/failed/initiated attempts)
        $payments = BookingCustomerPayment::query()
            ->where('currency', $cur)
            ->whereIn('status', [
                BookingCustomerPayment::STATUS_CONFIRMED,
                BookingCustomerPayment::STATUS_REFUNDED,
            ])
            ->whereRaw('COALESCE(verified_at, collected_at, created_at) >= ?', [$fromUtc])
            ->whereRaw('COALESCE(verified_at, collected_at, created_at) < ?', [$toExclUtc])
            ->get();

        $gatewayReceiptsMinor = (int) $payments
            ->where('status', BookingCustomerPayment::STATUS_CONFIRMED)
            ->whereIn('payment_method', [
                BookingCustomerPayment::METHOD_STRIPE,
                BookingCustomerPayment::METHOD_PAYPAL,
                BookingCustomerPayment::METHOD_ONLINE_TRANSFER,
            ])
            ->sum('amount_minor');

        $cashCollectedByDpMinor = (int) $payments
            ->where('status', BookingCustomerPayment::STATUS_CONFIRMED)
            ->where('payment_method', BookingCustomerPayment::METHOD_CASH_IN_HAND)
            ->sum('amount_minor');

        $refundTxCompletedMinor = (int) \App\Models\RefundTransaction::query()
            ->where('currency', $cur)
            ->whereIn('status', [
                \App\Models\RefundTransaction::STATUS_SUCCEEDED,
                \App\Models\RefundTransaction::STATUS_PAID,
                \App\Models\RefundTransaction::STATUS_RECONCILED,
            ])
            ->whereRaw('COALESCE(processed_at, created_at) >= ?', [$fromUtc])
            ->whereRaw('COALESCE(processed_at, created_at) < ?', [$toExclUtc])
            ->sum('amount_minor');

        $legacyRefundPaymentIdsWithTx = \App\Models\RefundTransaction::query()
            ->whereNotNull('booking_customer_payment_id')
            ->pluck('booking_customer_payment_id')
            ->all();

        $legacyRefundsMinor = (int) $payments
            ->where('status', BookingCustomerPayment::STATUS_REFUNDED)
            ->whereNotIn('id', $legacyRefundPaymentIdsWithTx)
            ->sum('amount_minor');

        $refundsMinor = $refundTxCompletedMinor + $legacyRefundsMinor;

        $paymentMethodBreakdown = [
            'stripe' => (int) $payments->where('status', BookingCustomerPayment::STATUS_CONFIRMED)->where('payment_method', BookingCustomerPayment::METHOD_STRIPE)->sum('amount_minor'),
            'paypal' => (int) $payments->where('status', BookingCustomerPayment::STATUS_CONFIRMED)->where('payment_method', BookingCustomerPayment::METHOD_PAYPAL)->sum('amount_minor'),
            'online_transfer' => (int) $payments->where('status', BookingCustomerPayment::STATUS_CONFIRMED)->where('payment_method', BookingCustomerPayment::METHOD_ONLINE_TRANSFER)->sum('amount_minor'),
            'cash_in_hand' => $cashCollectedByDpMinor,
            'pending_gateway' => (int) $payments->whereIn('status', [BookingCustomerPayment::STATUS_PENDING, BookingCustomerPayment::STATUS_AWAITING_VERIFICATION])->sum('amount_minor'),
        ];

        // 2. Delivery Person Cash Remittances (DeliveryPersonSettlement: COALESCE(settled_at, created_at))
        $cashRemittedMinor = (int) DeliveryPersonSettlement::query()
            ->where('currency', $cur)
            ->whereRaw('COALESCE(settled_at, created_at) >= ?', [$fromUtc])
            ->whereRaw('COALESCE(settled_at, created_at) < ?', [$toExclUtc])
            ->get()
            ->sum(fn (DeliveryPersonSettlement $s) => StoreAccountService::decimalToMinor($s->amount));

        // Selected Month: Cash Held by Delivery Persons (custodial credits - debits in selected month)
        $custodyCredits = (float) DeliveryPersonCashTransaction::query()
            ->where('currency', $cur)
            ->where('direction', DeliveryPersonCashTransaction::DIRECTION_CREDIT)
            ->where('created_at', '>=', $fromUtc)
            ->where('created_at', '<', $toExclUtc)
            ->sum('amount');
        $custodyDebits = (float) DeliveryPersonCashTransaction::query()
            ->where('currency', $cur)
            ->where('direction', DeliveryPersonCashTransaction::DIRECTION_DEBIT)
            ->where('created_at', '>=', $fromUtc)
            ->where('created_at', '<', $toExclUtc)
            ->sum('amount');
        $cashHeldByDpMinor = max(0, StoreAccountService::decimalToMinor($custodyCredits) - StoreAccountService::decimalToMinor($custodyDebits));

        $platformReceiptsMinor = $gatewayReceiptsMinor + $cashRemittedMinor;

        // 3. Selected Month: Customer Outstanding Receivables (non-cancelled finalized unpaid bookings in selected month)
        $receivableBookings = Booking::query()
            ->where('currency', $cur)
            ->whereNull('cancelled_at')
            ->where('booking_status', '!=', Booking::STATUS_CANCELLED)
            ->where('payment_status', '!=', 'paid')
            ->where('created_at', '>=', $fromUtc)
            ->where('created_at', '<', $toExclUtc)
            ->withSum(['customerPayments as confirmed_paid_sum' => fn ($q) => $q->where('status', BookingCustomerPayment::STATUS_CONFIRMED)], 'amount_minor')
            ->get();

        $customerReceivablesMinor = 0;
        foreach ($receivableBookings as $b) {
            if (! $b->isInvoiceEligible($this->currencyService)) {
                continue;
            }
            $totalMinor = max(0, (int) ($b->final_amount_minor ?? $b->estimated_amount_minor ?? 0));
            $paidMinor = max(0, (int) ($b->confirmed_paid_sum ?? 0));
            $refundedMinor = max(0, (int) ($b->refunded_amount_minor ?? 0));
            $customerReceivablesMinor += max(0, $totalMinor - $paidMinor - $refundedMinor);
        }

        // 4. Store Commissions & Store Shares (BookingStoreEarning: COALESCE(finalized_at, created_at))
        $storeEarnings = BookingStoreEarning::query()
            ->where('currency', $cur)
            ->where('is_current', true)
            ->whereRaw('COALESCE(finalized_at, created_at) >= ?', [$fromUtc])
            ->whereRaw('COALESCE(finalized_at, created_at) < ?', [$toExclUtc])
            ->get();
        $eligibleServiceRevenueMinor = (int) $storeEarnings->sum('eligible_service_amount_minor');
        $platformCommissionMinor = (int) $storeEarnings->sum('commission_amount_minor');
        $storeShareTotalMinor = (int) $storeEarnings->sum('net_store_earnings_minor');

        // Monthly Store Payouts (StoreTransaction::TYPE_PAYOUT + Manually Settled StoreWithdrawalRequest)
        $storePayoutsPaidMinor = (int) StoreTransaction::query()
            ->where('currency', $cur)
            ->where('transaction_type', StoreTransaction::TYPE_PAYOUT)
            ->where('created_at', '>=', $fromUtc)
            ->where('created_at', '<', $toExclUtc)
            ->get()
            ->sum(fn (StoreTransaction $st) => StoreAccountService::decimalToMinor($st->amount));

        $storePayoutsPaidMinor += (int) \App\Models\StoreWithdrawalRequest::query()
            ->where('currency', $cur)
            ->where('status', \App\Models\StoreWithdrawalRequest::STATUS_MANUALLY_SETTLED)
            ->where('payment_date', '>=', $period['from_date'])
            ->where('payment_date', '<=', $period['to_date_inclusive'])
            ->sum('amount_minor');

        // Selected Month: Store Payables Outstanding (net store share generated - store payouts in selected month)
        $storePayablesOutstandingMinor = max(0, $storeShareTotalMinor - $storePayoutsPaidMinor);

        // 5. Delivery Person Earnings (DeliveryPersonLedgerEntry: COALESCE(occurred_at, created_at))
        $dpEntries = DeliveryPersonLedgerEntry::query()
            ->with('assignment:id,assignment_type')
            ->where('currency', $cur)
            ->whereRaw('COALESCE(occurred_at, created_at) >= ?', [$fromUtc])
            ->whereRaw('COALESCE(occurred_at, created_at) < ?', [$toExclUtc])
            ->get();

        $dpCollectionEarnedMinor = 0;
        $dpFinalEarnedMinor = 0;
        foreach ($dpEntries->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING) as $entry) {
            if ($entry->assignment?->assignment_type === DeliveryAssignment::TYPE_FINAL_DELIVERY) {
                $dpFinalEarnedMinor += (int) $entry->amount_minor;
            } else {
                $dpCollectionEarnedMinor += (int) $entry->amount_minor;
            }
        }
        $dpTotalEarnedMinor = $dpCollectionEarnedMinor + $dpFinalEarnedMinor;

        // Monthly DP Payouts (DeliveryPersonPayout: payment_date)
        $dpPayoutsPaidMinor = (int) DeliveryPersonPayout::query()
            ->where('currency', $cur)
            ->where('status', 'recorded')
            ->where('payment_date', '>=', $period['from_date'])
            ->where('payment_date', '<=', $period['to_date_inclusive'])
            ->sum('amount_minor');

        // Selected Month: DP Unpaid Balance (credits - debits in selected month)
        $monthDpCredits = (int) $dpEntries->where('direction', 'credit')->sum('amount_minor');
        $monthDpDebits = (int) $dpEntries->where('direction', 'debit')->sum('amount_minor');
        $dpPayablesOutstandingMinor = max(0, $monthDpCredits - $monthDpDebits);

        // 6. Cash Disbursements & Net Recorded Cash Movement for selected period
        $recordedDisbursementsMinor = $storePayoutsPaidMinor + $dpPayoutsPaidMinor + $refundsMinor;
        $netRecordedCashMovementMinor = $platformReceiptsMinor - $recordedDisbursementsMinor;

        // 7. Profitability Overview for selected period
        $deliveryFeeRevenueMinor = (int) Booking::query()
            ->where('currency', $cur)
            ->whereNull('cancelled_at')
            ->where('booking_status', '!=', Booking::STATUS_CANCELLED)
            ->whereIn('id', $storeEarnings->pluck('booking_id')->all())
            ->sum('collection_delivery_fee_minor');

        $netPlatformContributionMinor = $platformCommissionMinor + ($deliveryFeeRevenueMinor - $dpTotalEarnedMinor);

        // 8. Admin Platform Ledger Summary
        $adminSummaryByCur = $this->adminAccountService->getSummaryByCurrency();
        $adminLedgerSummary = $adminSummaryByCur[$cur] ?? [
            'currency' => $cur,
            'current_balance_minor' => 0,
            'total_credited_minor' => 0,
            'total_debited_minor' => 0,
            'awaiting_advance_minor' => 0,
        ];

        // 9. Stripe Processing Fee Summary (selected month, grouped by Stripe settlement currency)
        $stripeConfirmed = $payments
            ->where('payment_method', BookingCustomerPayment::METHOD_STRIPE)
            ->whereIn('status', [BookingCustomerPayment::STATUS_CONFIRMED, BookingCustomerPayment::STATUS_REFUNDED]);
        $stripeConfirmed->load('booking:id,reference,stripe_payment_intent_id');

        $stripeByCurrency = [];
        $stripeTransactions = [];
        $stripeReconciledCount = 0;
        $stripePendingCount = 0;

        foreach ($stripeConfirmed as $sp) {
            $isReconciled = $sp->fee_reconciliation_status === 'reconciled' && $sp->gateway_fee_minor !== null;
            $settleCur = strtoupper((string) ($isReconciled && $sp->gateway_fee_currency ? $sp->gateway_fee_currency : $sp->currency));

            if (! isset($stripeByCurrency[$settleCur])) {
                $stripeByCurrency[$settleCur] = [
                    'currency' => $settleCur,
                    'gross_minor' => 0,
                    'reconciled_gross_minor' => 0,
                    'fee_minor' => 0,
                    'net_minor' => 0,
                    'reconciled_count' => 0,
                    'pending_count' => 0,
                ];
            }

            $grossMinor = $isReconciled && $sp->gateway_gross_minor !== null
                ? (int) $sp->gateway_gross_minor
                : (int) $sp->amount_minor;
            $stripeByCurrency[$settleCur]['gross_minor'] += $grossMinor;

            if ($isReconciled) {
                $stripeReconciledCount++;
                $stripeByCurrency[$settleCur]['reconciled_count']++;
                $stripeByCurrency[$settleCur]['reconciled_gross_minor'] += $grossMinor;
                $stripeByCurrency[$settleCur]['fee_minor'] += (int) $sp->gateway_fee_minor;
                $stripeByCurrency[$settleCur]['net_minor'] += (int) $sp->gateway_net_minor;
            } else {
                $stripePendingCount++;
                $stripeByCurrency[$settleCur]['pending_count']++;
            }

            $dt = $sp->verified_at ?? $sp->collected_at ?? $sp->created_at ?? now();
            $stripeTransactions[] = [
                'uid' => 'bcp:' . $sp->id,
                'date' => $dt->format('d M Y, H:i'),
                'booking_id' => $sp->booking_id,
                'booking_reference' => $sp->booking?->reference ?? ('#' . $sp->booking_id),
                'reference' => $sp->stripe_payment_intent_id ?: ($sp->external_reference ?: ('PAY-' . $sp->id)),
                'gross_formatted' => $this->currencyService->formatMinor($grossMinor, $settleCur),
                'fee_formatted' => $isReconciled ? $this->currencyService->formatMinor((int) $sp->gateway_fee_minor, $settleCur) : null,
                'net_formatted' => $isReconciled ? $this->currencyService->formatMinor((int) $sp->gateway_net_minor, $settleCur) : null,
                'settlement_currency' => $settleCur,
                'is_reconciled' => $isReconciled,
                'status' => ucfirst((string) $sp->status),
                'status_badge' => $sp->status === BookingCustomerPayment::STATUS_CONFIRMED ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger',
            ];
        }

        $stripeSummary = [
            'confirmed_count' => $stripeConfirmed->count(),
            'reconciled_count' => $stripeReconciledCount,
            'pending_count' => $stripePendingCount,
            'by_settlement_currency' => array_values($stripeByCurrency),
            'transactions' => $stripeTransactions,
        ];

        return [
            'currency' => $cur,
            'period' => $period,
            'customer_receipts_platform_minor' => $platformReceiptsMinor,
            'customer_receipts_gateway_minor' => $gatewayReceiptsMinor,
            'customer_cash_collected_dp_minor' => $cashCollectedByDpMinor,
            'customer_cash_remitted_minor' => $cashRemittedMinor,
            'cash_held_by_dp_minor' => $cashHeldByDpMinor,
            'customer_receivables_minor' => $customerReceivablesMinor,
            'platform_commission_minor' => $platformCommissionMinor,
            'eligible_service_revenue_minor' => $eligibleServiceRevenueMinor,
            'store_share_total_minor' => $storeShareTotalMinor,
            'store_payables_outstanding_minor' => $storePayablesOutstandingMinor,
            'store_payouts_paid_minor' => $storePayoutsPaidMinor,
            'dp_collection_earned_minor' => $dpCollectionEarnedMinor,
            'dp_final_earned_minor' => $dpFinalEarnedMinor,
            'dp_total_earned_minor' => $dpTotalEarnedMinor,
            'dp_payouts_paid_minor' => $dpPayoutsPaidMinor,
            'dp_payables_outstanding_minor' => $dpPayablesOutstandingMinor,
            'refunds_minor' => $refundsMinor,
            'recorded_disbursements_minor' => $recordedDisbursementsMinor,
            'net_recorded_cash_movement_minor' => $netRecordedCashMovementMinor,
            'delivery_fee_revenue_minor' => $deliveryFeeRevenueMinor,
            'net_platform_contribution_minor' => $netPlatformContributionMinor,
            'payment_method_breakdown' => $paymentMethodBreakdown,
            'admin_ledger_summary' => $adminLedgerSummary,
            'stripe_summary' => $stripeSummary,
        ];
    }

    /**
     * Build 12-month financial trend series ending at the selected month.
     *
     * @return array{labels: array<int, string>, receipts: array<int, float>, outflows: array<int, float>, commissions: array<int, float>}
     */
    public function getMonthlyTrend(string $currency, string $anchorMonth): array
    {
        $cur = strtoupper(trim($currency)) ?: $this->currencyService->code();
        $tz = (string) config('app.timezone', 'UTC');
        $endMonth = Carbon::createFromFormat('Y-m', $anchorMonth, $tz)->startOfMonth();
        $startMonth = $endMonth->copy()->subMonths(11);

        $labels = [];
        $receipts = [];
        $outflows = [];
        $commissions = [];

        for ($i = 0; $i < 12; $i++) {
            $m = $startMonth->copy()->addMonths($i);
            $mFromUtc = $m->copy()->utc();
            $mToExclUtc = $m->copy()->addMonth()->utc();

            $gatewayIn = (int) BookingCustomerPayment::query()
                ->where('currency', $cur)
                ->where('status', BookingCustomerPayment::STATUS_CONFIRMED)
                ->whereIn('payment_method', [
                    BookingCustomerPayment::METHOD_STRIPE,
                    BookingCustomerPayment::METHOD_PAYPAL,
                    BookingCustomerPayment::METHOD_ONLINE_TRANSFER,
                ])
                ->whereRaw('COALESCE(verified_at, collected_at, created_at) >= ?', [$mFromUtc])
                ->whereRaw('COALESCE(verified_at, collected_at, created_at) < ?', [$mToExclUtc])
                ->sum('amount_minor');

            $remittedIn = (int) DeliveryPersonSettlement::query()
                ->where('currency', $cur)
                ->whereRaw('COALESCE(settled_at, created_at) >= ?', [$mFromUtc])
                ->whereRaw('COALESCE(settled_at, created_at) < ?', [$mToExclUtc])
                ->get()
                ->sum(fn (DeliveryPersonSettlement $s) => StoreAccountService::decimalToMinor($s->amount));

            $refundOut = (int) BookingCustomerPayment::query()
                ->where('currency', $cur)
                ->where('status', BookingCustomerPayment::STATUS_REFUNDED)
                ->whereRaw('COALESCE(verified_at, collected_at, created_at) >= ?', [$mFromUtc])
                ->whereRaw('COALESCE(verified_at, collected_at, created_at) < ?', [$mToExclUtc])
                ->sum('amount_minor');

            $storeOut = (int) StoreTransaction::query()
                ->where('currency', $cur)
                ->where('transaction_type', StoreTransaction::TYPE_PAYOUT)
                ->where('created_at', '>=', $mFromUtc)
                ->where('created_at', '<', $mToExclUtc)
                ->get()
                ->sum(fn (StoreTransaction $st) => StoreAccountService::decimalToMinor($st->amount));

            $dpOut = (int) DeliveryPersonPayout::query()
                ->where('currency', $cur)
                ->where('status', 'recorded')
                ->where('payment_date', '>=', $m->toDateString())
                ->where('payment_date', '<=', $m->copy()->endOfMonth()->toDateString())
                ->sum('amount_minor');

            $comm = (int) BookingStoreEarning::query()
                ->where('currency', $cur)
                ->where('is_current', true)
                ->whereRaw('COALESCE(finalized_at, created_at) >= ?', [$mFromUtc])
                ->whereRaw('COALESCE(finalized_at, created_at) < ?', [$mToExclUtc])
                ->sum('commission_amount_minor');

            $labels[] = $m->format('M Y');
            $receipts[] = round(($gatewayIn + $remittedIn) / 100, 2);
            $outflows[] = round(($refundOut + $storeOut + $dpOut) / 100, 2);
            $commissions[] = round($comm / 100, 2);
        }

        return [
            'labels' => $labels,
            'receipts' => $receipts,
            'outflows' => $outflows,
            'commissions' => $commissions,
        ];
    }

    /**
     * Build unified read-only transaction feed across all verified financial tables.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getUnifiedTransactions(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $rows = $this->collectNormalizedRows($filters);

        $allowedSorts = ['occurred_at', 'transaction_type', 'counterparty', 'amount', 'status', 'reference', 'booking_reference'];
        $sortBy = in_array(($filters['sort_by'] ?? 'occurred_at'), $allowedSorts, true)
            ? (string) $filters['sort_by']
            : 'occurred_at';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $sorted = $rows->sort(function (array $a, array $b) use ($sortBy, $sortDir) {
            $va = match ($sortBy) {
                'amount' => max($a['money_in_minor'], $a['money_out_minor'], $a['accounting_amount_minor']),
                'transaction_type' => strtolower($a['transaction_type_label']),
                'counterparty' => strtolower($a['counterparty']),
                'status' => strtolower($a['status']),
                'reference' => strtolower($a['reference']),
                'booking_reference' => strtolower($a['booking_reference'] ?? ''),
                default => $a['occurred_at_ts'],
            };
            $vb = match ($sortBy) {
                'amount' => max($b['money_in_minor'], $b['money_out_minor'], $b['accounting_amount_minor']),
                'transaction_type' => strtolower($b['transaction_type_label']),
                'counterparty' => strtolower($b['counterparty']),
                'status' => strtolower($b['status']),
                'reference' => strtolower($b['reference']),
                'booking_reference' => strtolower($b['booking_reference'] ?? ''),
                default => $b['occurred_at_ts'],
            };

            $cmp = $va <=> $vb;
            if ($cmp === 0) {
                $cmp = strcmp($a['uid'], $b['uid']);
            }

            return $sortDir === 'desc' ? -$cmp : $cmp;
        })->values();

        $page = ! empty($filters['page']) ? max(1, (int) $filters['page']) : Paginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            [
                'path' => route('admin.current-account.index'),
                'query' => request()->query(),
            ]
        );
    }

    /**
     * Retrieve safe, non-sensitive detail payload for a single unified transaction UID.
     */
    public function getTransactionDetail(string $uid): ?array
    {
        if (! str_contains($uid, ':')) {
            return null;
        }
        [$prefix, $idStr] = explode(':', $uid, 2);
        $id = (int) $idStr;
        if ($id <= 0) {
            return null;
        }

        $row = match ($prefix) {
            'bcp' => ($r = BookingCustomerPayment::with(['booking', 'deliveryPerson', 'verifiedByAdmin'])->find($id)) ? $this->mapCustomerPayment($r) : null,
            'bse-comm' => ($r = BookingStoreEarning::with(['booking', 'store'])->find($id)) ? $this->mapStoreCommission($r) : null,
            'bse-share' => ($r = BookingStoreEarning::with(['booking', 'store'])->find($id)) ? $this->mapStoreShare($r) : null,
            'st' => ($r = StoreTransaction::with(['booking', 'store', 'createdByAdmin'])->find($id)) ? $this->mapStoreTransaction($r) : null,
            'dple' => ($r = DeliveryPersonLedgerEntry::with(['booking', 'deliveryPerson', 'assignment', 'createdByAdmin'])->find($id)) ? $this->mapDpLedgerEntry($r) : null,
            'dpp' => ($r = DeliveryPersonPayout::with(['deliveryPerson', 'ledgerEntry', 'recordedByAdmin'])->find($id)) ? $this->mapDpPayout($r) : null,
            'dps' => ($r = DeliveryPersonSettlement::with(['deliveryPerson', 'admin'])->find($id)) ? $this->mapDpSettlement($r) : null,
            'aat' => ($r = AdminAccountTransaction::with(['booking', 'createdByAdmin'])->find($id)) ? $this->mapAdminAccountTx($r) : null,
            'rft' => ($r = \App\Models\RefundTransaction::with(['booking', 'claim', 'approvedByAdmin', 'processedByAdmin'])->find($id)) ? $this->mapRefundTransaction($r) : null,
            'dpw' => ($r = \App\Models\DeliveryPersonWithdrawalRequest::with(['deliveryPerson', 'approvedByAdmin', 'rejectedByAdmin', 'settledByAdmin'])->find($id)) ? $this->mapDpWithdrawalRequest($r) : null,
            'swd' => ($r = \App\Models\StoreWithdrawalRequest::with(['store', 'approvedByAdmin', 'rejectedByAdmin', 'settledByAdmin'])->find($id)) ? $this->mapStoreWithdrawalRequest($r) : null,
            default => null,
        };

        return $row;
    }

    public function getExportRows(array $filters): Collection
    {
        $rows = $this->collectNormalizedRows($filters);
        $sortBy = $filters['sort_by'] ?? 'occurred_at';
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return $rows->sortBy(function (array $row) use ($sortBy) {
            return match ($sortBy) {
                'amount' => max($row['money_in_minor'], $row['money_out_minor'], $row['accounting_amount_minor']),
                'reference' => $row['reference'],
                'booking_reference' => $row['booking_reference'] ?? '',
                default => $row['occurred_at_ts'],
            };
        }, SORT_REGULAR, $sortDir === 'desc')->values();
    }

    protected function collectNormalizedRows(array $filters): Collection
    {
        $cur = strtoupper(trim((string) ($filters['currency'] ?? $this->currencyService->code())));
        $period = $this->resolvePeriod(
            $filters['month'] ?? null,
            $filters['from_date'] ?? null,
            $filters['to_date'] ?? null
        );
        $fromUtc = $period['from_utc'];
        $toExclUtc = $period['to_exclusive_utc'];
        $txType = trim((string) ($filters['transaction_type'] ?? ''));
        $classification = trim((string) ($filters['classification'] ?? ''));
        $statusFilter = trim((string) ($filters['status'] ?? ''));
        $methodFilter = trim((string) ($filters['payment_method'] ?? ''));
        $bookingRef = trim((string) ($filters['booking_reference'] ?? ''));
        $storeId = ! empty($filters['store_id']) ? (int) $filters['store_id'] : null;
        $dpId = ! empty($filters['delivery_person_id']) ? (int) $filters['delivery_person_id'] : null;
        $search = trim((string) ($filters['search'] ?? ''));

        $items = collect();

        // 1. Customer Payments & Refunds (ONLY confirmed or refunded; exclude pending/failed/initiated attempts)
        $bcpQuery = BookingCustomerPayment::query()
            ->with(['booking', 'deliveryPerson', 'verifiedByAdmin'])
            ->where('currency', $cur)
            ->whereIn('status', [
                BookingCustomerPayment::STATUS_CONFIRMED,
                BookingCustomerPayment::STATUS_REFUNDED,
            ])
            ->whereRaw('COALESCE(verified_at, collected_at, created_at) >= ?', [$fromUtc])
            ->whereRaw('COALESCE(verified_at, collected_at, created_at) < ?', [$toExclUtc]);
        if ($bookingRef !== '') {
            $bcpQuery->whereHas('booking', fn ($q) => $q->where('reference', 'like', "%{$bookingRef}%"));
        }
        if ($dpId) {
            $bcpQuery->where('delivery_person_id', $dpId);
        }
        if ($storeId) {
            $bcpQuery->whereHas('booking', fn ($q) => $q->where('assigned_store_id', $storeId));
        }
        foreach ($bcpQuery->get() as $p) {
            $items->push($this->mapCustomerPayment($p));
        }

        // 2. Store Commissions & Store Share Payables (COALESCE(finalized_at, created_at))
        $bseQuery = BookingStoreEarning::query()
            ->with(['booking', 'store'])
            ->where('currency', $cur)
            ->where('is_current', true)
            ->whereRaw('COALESCE(finalized_at, created_at) >= ?', [$fromUtc])
            ->whereRaw('COALESCE(finalized_at, created_at) < ?', [$toExclUtc]);
        if ($bookingRef !== '') {
            $bseQuery->whereHas('booking', fn ($q) => $q->where('reference', 'like', "%{$bookingRef}%"));
        }
        if ($storeId) {
            $bseQuery->where('store_id', $storeId);
        }
        foreach ($bseQuery->get() as $e) {
            if ((int) $e->commission_amount_minor > 0) {
                $items->push($this->mapStoreCommission($e));
            }
            if ((int) $e->net_store_earnings_minor > 0) {
                $items->push($this->mapStoreShare($e));
            }
        }

        // 3. Store Payouts & Adjustments (created_at)
        $stQuery = StoreTransaction::query()
            ->with(['booking', 'store', 'createdByAdmin'])
            ->where('currency', $cur)
            ->whereNotIn('transaction_type', [
                StoreTransaction::TYPE_BOOKING_EARNING,
                StoreTransaction::TYPE_WITHDRAWAL_RESERVATION,
                StoreTransaction::TYPE_WITHDRAWAL_RELEASE,
            ])
            ->where('created_at', '>=', $fromUtc)
            ->where('created_at', '<', $toExclUtc);
        if ($bookingRef !== '') {
            $stQuery->whereHas('booking', fn ($q) => $q->where('reference', 'like', "%{$bookingRef}%"));
        }
        if ($storeId) {
            $stQuery->where('store_id', $storeId);
        }
        foreach ($stQuery->get() as $st) {
            $items->push($this->mapStoreTransaction($st));
        }

        // 3b. Store Withdrawal Requests
        if ($bookingRef === '' && ! $dpId) {
            $swdQuery = \App\Models\StoreWithdrawalRequest::query()
                ->with(['store', 'approvedByAdmin', 'rejectedByAdmin', 'settledByAdmin'])
                ->where('currency', $cur)
                ->whereRaw('COALESCE(settled_at, requested_at) >= ?', [$fromUtc])
                ->whereRaw('COALESCE(settled_at, requested_at) < ?', [$toExclUtc]);
            if ($storeId) {
                $swdQuery->where('store_id', $storeId);
            }
            foreach ($swdQuery->get() as $swd) {
                $items->push($this->mapStoreWithdrawalRequest($swd));
            }
        }

        // 4. Delivery Person Ledger Earnings & Adjustments (COALESCE(occurred_at, created_at))
        $dpleQuery = DeliveryPersonLedgerEntry::query()
            ->with(['booking', 'deliveryPerson', 'assignment', 'createdByAdmin'])
            ->where('currency', $cur)
            ->whereNotIn('transaction_type', [
                DeliveryPersonLedgerEntry::TYPE_PAYOUT,
                DeliveryPersonLedgerEntry::TYPE_WITHDRAWAL_RESERVATION,
                DeliveryPersonLedgerEntry::TYPE_WITHDRAWAL_RELEASE,
            ])
            ->whereRaw('COALESCE(occurred_at, created_at) >= ?', [$fromUtc])
            ->whereRaw('COALESCE(occurred_at, created_at) < ?', [$toExclUtc]);
        if ($bookingRef !== '') {
            $dpleQuery->whereHas('booking', fn ($q) => $q->where('reference', 'like', "%{$bookingRef}%"));
        }
        if ($dpId) {
            $dpleQuery->where('delivery_person_id', $dpId);
        }
        if ($storeId) {
            $dpleQuery->whereHas('booking', fn ($q) => $q->where('assigned_store_id', $storeId));
        }
        foreach ($dpleQuery->get() as $entry) {
            $items->push($this->mapDpLedgerEntry($entry));
        }

        // 5. Delivery Person Payouts (payment_date)
        if ($bookingRef === '' && ! $storeId) {
            $dppQuery = DeliveryPersonPayout::query()
                ->with(['deliveryPerson', 'ledgerEntry', 'recordedByAdmin'])
                ->where('currency', $cur)
                ->where('payment_date', '>=', $period['from_date'])
                ->where('payment_date', '<=', $period['to_date_inclusive']);
            if ($dpId) {
                $dppQuery->where('delivery_person_id', $dpId);
            }
            foreach ($dppQuery->get() as $payout) {
                $items->push($this->mapDpPayout($payout));
            }

            // 5b. Delivery Person Withdrawal Requests (pending / approved / rejected — settled ones are in DeliveryPersonPayout above)
            $dpwQuery = \App\Models\DeliveryPersonWithdrawalRequest::query()
                ->with(['deliveryPerson', 'approvedByAdmin', 'rejectedByAdmin', 'settledByAdmin'])
                ->where('currency', $cur)
                ->whereNull('payout_id')
                ->where('requested_at', '>=', $fromUtc)
                ->where('requested_at', '<', $toExclUtc);
            if ($dpId) {
                $dpwQuery->where('delivery_person_id', $dpId);
            }
            foreach ($dpwQuery->get() as $wd) {
                $items->push($this->mapDpWithdrawalRequest($wd));
            }
        }

        // 6. Delivery Person Cash Settlements (COALESCE(settled_at, created_at))
        if ($bookingRef === '' && ! $storeId) {
            $dpsQuery = DeliveryPersonSettlement::query()
                ->with(['deliveryPerson', 'admin'])
                ->where('currency', $cur)
                ->whereRaw('COALESCE(settled_at, created_at) >= ?', [$fromUtc])
                ->whereRaw('COALESCE(settled_at, created_at) < ?', [$toExclUtc]);
            if ($dpId) {
                $dpsQuery->where('delivery_person_id', $dpId);
            }
            foreach ($dpsQuery->get() as $settlement) {
                $items->push($this->mapDpSettlement($settlement));
            }
        }

        // 7. Admin Platform Account Transactions (created_at)
        if (! $dpId && ! $storeId) {
            $aatQuery = AdminAccountTransaction::query()
                ->with(['booking', 'createdByAdmin'])
                ->where('currency', $cur)
                ->where('created_at', '>=', $fromUtc)
                ->where('created_at', '<', $toExclUtc);
            if ($bookingRef !== '') {
                $aatQuery->whereHas('booking', fn ($q) => $q->where('reference', 'like', "%{$bookingRef}%"));
            }
            foreach ($aatQuery->get() as $aat) {
                $items->push($this->mapAdminAccountTx($aat));
            }
        }

        // 8. Refund Transactions (approved liabilities vs completed disbursements)
        if (! $dpId) {
            $rftQuery = \App\Models\RefundTransaction::query()
                ->with(['booking', 'claim', 'approvedByAdmin', 'processedByAdmin'])
                ->where('currency', $cur)
                ->whereRaw('COALESCE(processed_at, created_at) >= ?', [$fromUtc])
                ->whereRaw('COALESCE(processed_at, created_at) < ?', [$toExclUtc]);
            if ($bookingRef !== '') {
                $rftQuery->whereHas('booking', fn ($q) => $q->where('reference', 'like', "%{$bookingRef}%"));
            }
            if ($storeId) {
                $rftQuery->whereHas('booking', fn ($q) => $q->where('assigned_store_id', $storeId));
            }
            foreach ($rftQuery->get() as $rft) {
                $items->push($this->mapRefundTransaction($rft));
            }
        }

        return $items->filter(function (array $row) use ($fromUtc, $toExclUtc, $txType, $classification, $statusFilter, $methodFilter, $search) {
            if ($row['occurred_at_ts'] < $fromUtc->timestamp || $row['occurred_at_ts'] >= $toExclUtc->timestamp) {
                return false;
            }
            if ($txType !== '' && $row['transaction_type'] !== $txType) {
                return false;
            }
            if ($classification !== '' && $row['classification'] !== $classification) {
                return false;
            }
            if ($statusFilter !== '' && strtolower((string) $row['status']) !== strtolower($statusFilter)) {
                return false;
            }
            if ($methodFilter !== '' && strtolower((string) ($row['payment_method'] ?? '')) !== strtolower($methodFilter)) {
                return false;
            }
            if ($search !== '') {
                $haystack = strtolower(implode(' ', [
                    $row['reference'],
                    $row['booking_reference'] ?? '',
                    $row['counterparty'],
                    $row['description'],
                    $row['transaction_type_label'],
                ]));
                if (! str_contains($haystack, strtolower($search))) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    protected function mapCustomerPayment(BookingCustomerPayment $p): array
    {
        $dt = $p->verified_at ?? $p->collected_at ?? $p->created_at ?? now();
        $isRefund = $p->status === BookingCustomerPayment::STATUS_REFUNDED;
        $isConfirmed = $p->status === BookingCustomerPayment::STATUS_CONFIRMED;
        $isCash = $p->payment_method === BookingCustomerPayment::METHOD_CASH_IN_HAND;

        $type = match ($p->payment_method) {
            BookingCustomerPayment::METHOD_STRIPE => $isRefund ? 'customer_refund' : 'customer_stripe_payment',
            BookingCustomerPayment::METHOD_PAYPAL => $isRefund ? 'customer_refund' : 'customer_paypal_payment',
            BookingCustomerPayment::METHOD_CASH_IN_HAND => 'delivery_person_cash_collection',
            BookingCustomerPayment::METHOD_ONLINE_TRANSFER => 'customer_online_transfer',
            default => 'customer_payment',
        };

        $label = match ($type) {
            'customer_stripe_payment' => 'Customer Stripe Payment',
            'customer_paypal_payment' => 'Customer PayPal Payment',
            'delivery_person_cash_collection' => 'Delivery Person Cash Collection',
            'customer_online_transfer' => 'Customer Online Transfer',
            'customer_refund' => 'Customer Refund',
            default => 'Customer Payment',
        };

        $classification = $isRefund
            ? 'Refund'
            : ($isConfirmed ? ($isCash ? 'Custodial Cash' : 'Cash Receipt') : 'Informational Only');

        $moneyIn = ($isConfirmed && ! $isCash) ? (int) $p->amount_minor : 0;
        $moneyOut = $isRefund ? (int) $p->amount_minor : 0;

        return [
            'uid' => 'bcp:' . $p->id,
            'occurred_at' => $dt->format('d M Y, H:i'),
            'occurred_at_ts' => $dt->timestamp,
            'reference' => $p->external_reference ?: ('PAY-' . str_pad((string) $p->id, 6, '0', STR_PAD_LEFT)),
            'booking_id' => $p->booking_id,
            'booking_reference' => $p->booking?->reference,
            'transaction_type' => $type,
            'transaction_type_label' => $label,
            'classification' => $classification,
            'is_cash_movement' => ($moneyIn > 0 || $moneyOut > 0),
            'counterparty' => $p->booking?->customer_name ?? 'Customer',
            'payment_method' => $p->payment_method,
            'description' => $p->notes ?: ($label . ($p->booking ? ' for ' . $p->booking->reference : '')),
            'money_in_minor' => $moneyIn,
            'money_out_minor' => $moneyOut,
            'accounting_amount_minor' => (int) $p->amount_minor,
            'currency' => strtoupper((string) $p->currency),
            'status' => ucfirst(str_replace('_', ' ', (string) $p->status)),
            'status_badge' => match ($p->status) {
                BookingCustomerPayment::STATUS_CONFIRMED => 'bg-success-subtle text-success',
                BookingCustomerPayment::STATUS_REFUNDED => 'bg-danger-subtle text-danger',
                BookingCustomerPayment::STATUS_FAILED => 'bg-danger-subtle text-danger',
                default => 'bg-warning-subtle text-warning-emphasis',
            },
            'detail_payload' => [
                'source_table' => 'booking_customer_payments',
                'provider_reference' => $p->external_reference ?: 'N/A',
                'payment_channel' => $p->payment_channel,
                'collected_at' => $p->collected_at?->format('d M Y, H:i'),
                'verified_at' => $p->verified_at?->format('d M Y, H:i'),
                'delivery_person' => $p->deliveryPerson?->full_name,
                'verified_by' => $p->verifiedByAdmin?->name,
                'stripe_payment_intent_id' => $p->payment_method === BookingCustomerPayment::METHOD_STRIPE
                    ? ($p->stripe_payment_intent_id ?: ($p->external_reference ?: 'N/A'))
                    : null,
                'stripe_charge_id' => $p->stripe_charge_id,
                'stripe_balance_transaction_id' => $p->stripe_balance_transaction_id,
                'gross_payment_amount' => $p->payment_method === BookingCustomerPayment::METHOD_STRIPE
                    ? $this->currencyService->formatMinor(
                        (int) ($p->gateway_gross_minor ?? $p->amount_minor),
                        strtoupper((string) ($p->gateway_fee_currency ?: $p->currency))
                    )
                    : null,
                'actual_stripe_processing_fee' => $p->payment_method === BookingCustomerPayment::METHOD_STRIPE
                    ? ($p->fee_reconciliation_status === 'reconciled' && $p->gateway_fee_minor !== null
                        ? $this->currencyService->formatMinor((int) $p->gateway_fee_minor, strtoupper((string) ($p->gateway_fee_currency ?: $p->currency)))
                        : 'Fee Not Yet Available')
                    : null,
                'net_stripe_amount' => $p->payment_method === BookingCustomerPayment::METHOD_STRIPE
                    ? ($p->fee_reconciliation_status === 'reconciled' && $p->gateway_net_minor !== null
                        ? $this->currencyService->formatMinor((int) $p->gateway_net_minor, strtoupper((string) ($p->gateway_fee_currency ?: $p->currency)))
                        : 'Pending Reconciliation')
                    : null,
                'stripe_fee_breakdown' => ($p->payment_method === BookingCustomerPayment::METHOD_STRIPE && ! empty($p->gateway_fee_details))
                    ? collect($p->gateway_fee_details)->map(function ($fd) {
                        $cur = strtoupper((string) ($fd['currency'] ?? 'GBP'));
                        return ($fd['description'] ?: $fd['type']) . ': ' . $this->currencyService->formatMinor((int) ($fd['amount'] ?? 0), $cur);
                    })->implode(' | ')
                    : null,
                'fee_reconciliation_status' => $p->payment_method === BookingCustomerPayment::METHOD_STRIPE
                    ? ucfirst((string) ($p->fee_reconciliation_status ?: 'pending'))
                    : null,
            ],
        ];
    }

    protected function mapStoreCommission(BookingStoreEarning $e): array
    {
        $dt = $e->finalized_at ?? $e->created_at ?? now();

        return [
            'uid' => 'bse-comm:' . $e->id,
            'occurred_at' => $dt->format('d M Y, H:i'),
            'occurred_at_ts' => $dt->timestamp,
            'reference' => 'COM-' . str_pad((string) $e->id, 6, '0', STR_PAD_LEFT),
            'booking_id' => $e->booking_id,
            'booking_reference' => $e->booking?->reference,
            'transaction_type' => 'store_commission_earned',
            'transaction_type_label' => 'Store Commission Earned',
            'classification' => 'Earned Revenue',
            'is_cash_movement' => false,
            'counterparty' => $e->store?->name ?? 'Store',
            'payment_method' => null,
            'description' => sprintf('Platform commission (%s%%) on %s', number_format((float) $e->commission_rate, 2), $e->booking?->reference ?? ('#' . $e->booking_id)),
            'money_in_minor' => 0,
            'money_out_minor' => 0,
            'accounting_amount_minor' => (int) $e->commission_amount_minor,
            'currency' => strtoupper((string) $e->currency),
            'status' => 'Finalized',
            'status_badge' => 'bg-purple-subtle text-primary',
            'detail_payload' => [
                'source_table' => 'booking_store_earnings',
                'eligible_base' => $this->currencyService->formatMinor((int) $e->eligible_service_amount_minor, $e->currency),
                'commission_rate' => number_format((float) $e->commission_rate, 2) . '% (' . $e->commission_type . ')',
                'finalized_at' => $e->finalized_at?->format('d M Y, H:i'),
            ],
        ];
    }

    protected function mapStoreShare(BookingStoreEarning $e): array
    {
        $dt = $e->finalized_at ?? $e->created_at ?? now();

        return [
            'uid' => 'bse-share:' . $e->id,
            'occurred_at' => $dt->format('d M Y, H:i'),
            'occurred_at_ts' => $dt->timestamp,
            'reference' => 'STR-SHR-' . str_pad((string) $e->id, 5, '0', STR_PAD_LEFT),
            'booking_id' => $e->booking_id,
            'booking_reference' => $e->booking?->reference,
            'transaction_type' => 'store_share_payable',
            'transaction_type_label' => 'Store Share Payable',
            'classification' => 'Payable',
            'is_cash_movement' => false,
            'counterparty' => $e->store?->name ?? 'Store',
            'payment_method' => null,
            'description' => sprintf('Net store share owed to %s for %s', $e->store?->name ?? 'Store', $e->booking?->reference ?? ('#' . $e->booking_id)),
            'money_in_minor' => 0,
            'money_out_minor' => 0,
            'accounting_amount_minor' => (int) $e->net_store_earnings_minor,
            'currency' => strtoupper((string) $e->currency),
            'status' => 'Payable',
            'status_badge' => 'bg-warning-subtle text-warning-emphasis',
            'detail_payload' => [
                'source_table' => 'booking_store_earnings',
                'eligible_base' => $this->currencyService->formatMinor((int) $e->eligible_service_amount_minor, $e->currency),
                'finalized_at' => $e->finalized_at?->format('d M Y, H:i'),
            ],
        ];
    }

    protected function mapStoreTransaction(StoreTransaction $st): array
    {
        $dt = $st->created_at ?? now();
        $amountMinor = StoreAccountService::decimalToMinor($st->amount);
        $isPayout = $st->transaction_type === StoreTransaction::TYPE_PAYOUT;

        return [
            'uid' => 'st:' . $st->id,
            'occurred_at' => $dt->format('d M Y, H:i'),
            'occurred_at_ts' => $dt->timestamp,
            'reference' => $st->reference,
            'booking_id' => $st->booking_id,
            'booking_reference' => $st->booking?->reference,
            'transaction_type' => $isPayout ? 'store_settlement_paid' : 'financial_adjustment',
            'transaction_type_label' => $isPayout ? 'Store Settlement Paid' : ucfirst(str_replace('_', ' ', (string) $st->transaction_type)),
            'classification' => $isPayout ? 'Cash Disbursement' : 'Noncash Adjustment',
            'is_cash_movement' => $isPayout,
            'counterparty' => $st->store?->name ?? 'Store',
            'payment_method' => null,
            'description' => $st->description ?: 'Store account transaction',
            'money_in_minor' => 0,
            'money_out_minor' => $isPayout ? $amountMinor : 0,
            'accounting_amount_minor' => $amountMinor,
            'currency' => strtoupper((string) $st->currency),
            'status' => $isPayout ? 'Paid' : 'Recorded',
            'status_badge' => $isPayout ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary',
            'detail_payload' => [
                'source_table' => 'store_transactions',
                'direction' => $st->direction,
                'created_by' => $st->createdByAdmin?->name,
            ],
        ];
    }

    protected function mapDpLedgerEntry(DeliveryPersonLedgerEntry $entry): array
    {
        $dt = $entry->occurred_at ?? $entry->created_at ?? now();
        $isFinal = $entry->assignment?->assignment_type === DeliveryAssignment::TYPE_FINAL_DELIVERY;
        $isEarning = $entry->transaction_type === DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING;

        $type = $isEarning
            ? ($isFinal ? 'final_delivery_job_earning' : 'collection_job_earning')
            : 'financial_adjustment';

        $label = match ($type) {
            'collection_job_earning' => 'Collection Job Earning',
            'final_delivery_job_earning' => 'Final Delivery Job Earning',
            default => ucfirst(str_replace('_', ' ', (string) $entry->transaction_type)),
        };

        return [
            'uid' => 'dple:' . $entry->id,
            'occurred_at' => $dt->format('d M Y, H:i'),
            'occurred_at_ts' => $dt->timestamp,
            'reference' => $entry->entry_reference,
            'booking_id' => $entry->booking_id,
            'booking_reference' => $entry->booking?->reference,
            'transaction_type' => $type,
            'transaction_type_label' => $label,
            'classification' => $isEarning ? 'Recorded Expense' : 'Noncash Adjustment',
            'is_cash_movement' => false,
            'counterparty' => $entry->deliveryPerson?->full_name ?? 'Delivery Person',
            'payment_method' => null,
            'description' => $entry->notes ?: $label,
            'money_in_minor' => 0,
            'money_out_minor' => 0,
            'accounting_amount_minor' => (int) $entry->amount_minor,
            'currency' => strtoupper((string) $entry->currency),
            'status' => 'Credited to DP Ledger',
            'status_badge' => 'bg-info-subtle text-info-emphasis',
            'detail_payload' => [
                'source_table' => 'delivery_person_ledger_entries',
                'job_reference' => $entry->assignment?->assignment_reference,
                'job_type' => $entry->assignment?->type_label,
                'created_by' => $entry->createdByAdmin?->name,
            ],
        ];
    }

    protected function mapDpPayout(DeliveryPersonPayout $payout): array
    {
        $dt = $payout->payment_date ?? $payout->created_at ?? now();
        $isPaid = strtolower((string) $payout->status) === 'recorded';

        return [
            'uid' => 'dpp:' . $payout->id,
            'occurred_at' => $dt->format('d M Y, H:i'),
            'occurred_at_ts' => $dt->timestamp,
            'reference' => $payout->payout_reference,
            'booking_id' => null,
            'booking_reference' => null,
            'transaction_type' => 'delivery_person_payout',
            'transaction_type_label' => 'Delivery Person Payout',
            'classification' => $isPaid ? 'Cash Disbursement' : 'Payable',
            'is_cash_movement' => $isPaid,
            'counterparty' => $payout->deliveryPerson?->full_name ?? 'Delivery Person',
            'payment_method' => $payout->payment_method,
            'description' => $payout->notes ?: 'Delivery Person earnings payout',
            'money_in_minor' => 0,
            'money_out_minor' => $isPaid ? (int) $payout->amount_minor : 0,
            'accounting_amount_minor' => (int) $payout->amount_minor,
            'currency' => strtoupper((string) $payout->currency),
            'status' => ucfirst((string) $payout->status),
            'status_badge' => $isPaid ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning-emphasis',
            'detail_payload' => [
                'source_table' => 'delivery_person_payouts',
                'external_reference' => $payout->external_reference ?: 'N/A',
                'ledger_entry_ref' => $payout->ledgerEntry?->entry_reference,
                'recorded_by' => $payout->recordedByAdmin?->name,
            ],
        ];
    }

    protected function mapDpWithdrawalRequest(\App\Models\DeliveryPersonWithdrawalRequest $wd): array
    {
        $dt = $wd->requested_at ?? $wd->created_at ?? now();
        $snap = $wd->bank_snapshot_json ?? [];
        $masked = ($snap['bank_name'] ?? 'Bank') . ' (' . ($snap['account_number_masked'] ?? '••••') . ')';

        $classification = match ($wd->status) {
            \App\Models\DeliveryPersonWithdrawalRequest::STATUS_PENDING => 'Reserved Balance',
            \App\Models\DeliveryPersonWithdrawalRequest::STATUS_APPROVED => 'Payable',
            \App\Models\DeliveryPersonWithdrawalRequest::STATUS_REJECTED,
            \App\Models\DeliveryPersonWithdrawalRequest::STATUS_CANCELLED => 'Noncash Adjustment',
            default => 'Payable',
        };

        $meta = $wd->statusMeta();

        return [
            'uid' => 'dpw:' . $wd->id,
            'occurred_at' => $dt->format('d M Y, H:i'),
            'occurred_at_ts' => $dt->timestamp,
            'reference' => $wd->withdrawal_reference,
            'booking_id' => null,
            'booking_reference' => null,
            'transaction_type' => 'delivery_person_withdrawal',
            'transaction_type_label' => 'DP Withdrawal Request',
            'classification' => $classification,
            'is_cash_movement' => false,
            'counterparty' => $wd->deliveryPerson?->full_name ?? 'Delivery Person',
            'payment_method' => 'bank_transfer',
            'description' => "Withdrawal request to {$masked}" . ($wd->rejection_reason ? " — Rejected: {$wd->rejection_reason}" : ''),
            'money_in_minor' => 0,
            'money_out_minor' => 0,
            'accounting_amount_minor' => (int) $wd->amount_minor,
            'currency' => strtoupper((string) $wd->currency),
            'status' => $meta['label'],
            'status_badge' => $meta['badge'],
            'detail_payload' => [
                'source_table' => 'delivery_person_withdrawal_requests',
                'bank_account_masked' => $masked,
                'request_month' => $wd->request_month,
                'approved_by' => $wd->approvedByAdmin?->name,
                'rejected_by' => $wd->rejectedByAdmin?->name,
                'rejection_reason' => $wd->rejection_reason,
            ],
        ];
    }

    protected function mapStoreWithdrawalRequest(\App\Models\StoreWithdrawalRequest $wd): array
    {
        $isSettled = $wd->status === \App\Models\StoreWithdrawalRequest::STATUS_MANUALLY_SETTLED;
        $dt = ($isSettled ? ($wd->settled_at ?? $wd->payment_date) : $wd->requested_at) ?? $wd->created_at ?? now();
        $snap = $wd->bank_snapshot_json ?? [];
        $masked = ($snap['bank_name'] ?? 'Bank') . ' (' . ($snap['account_number_masked'] ?? '••••') . ')';

        $classification = match ($wd->status) {
            \App\Models\StoreWithdrawalRequest::STATUS_PENDING => 'Reserved Balance',
            \App\Models\StoreWithdrawalRequest::STATUS_APPROVED => 'Payable',
            \App\Models\StoreWithdrawalRequest::STATUS_MANUALLY_SETTLED => 'Cash Disbursement',
            \App\Models\StoreWithdrawalRequest::STATUS_REJECTED,
            \App\Models\StoreWithdrawalRequest::STATUS_CANCELLED => 'Noncash Adjustment',
            default => 'Payable',
        };

        $meta = $wd->statusMeta();

        return [
            'uid' => 'swd:' . $wd->id,
            'occurred_at' => $dt->format('d M Y, H:i'),
            'occurred_at_ts' => $dt->timestamp,
            'reference' => $wd->withdrawal_reference,
            'booking_id' => null,
            'booking_reference' => null,
            'transaction_type' => $isSettled ? 'store_payout' : 'store_withdrawal',
            'transaction_type_label' => $isSettled ? 'Store Withdrawal (Settled)' : 'Store Withdrawal Request',
            'classification' => $classification,
            'is_cash_movement' => $isSettled,
            'counterparty' => $wd->store?->name ?? 'Store',
            'payment_method' => $snap['admin_payout_method'] ?? 'bank_transfer',
            'description' => "Store withdrawal to {$masked}" . ($wd->rejection_reason ? " — Rejected: {$wd->rejection_reason}" : ''),
            'money_in_minor' => 0,
            'money_out_minor' => $isSettled ? (int) $wd->amount_minor : 0,
            'accounting_amount_minor' => (int) $wd->amount_minor,
            'currency' => strtoupper((string) $wd->currency),
            'status' => $meta['label'],
            'status_badge' => $meta['badge'],
            'detail_payload' => [
                'source_table' => 'store_withdrawal_requests',
                'bank_account_masked' => $masked,
                'request_month' => $wd->request_month,
                'approved_by' => $wd->approvedByAdmin?->name,
                'rejected_by' => $wd->rejectedByAdmin?->name,
                'settled_by' => $wd->settledByAdmin?->name,
                'external_reference' => $wd->external_reference,
                'rejection_reason' => $wd->rejection_reason,
            ],
        ];
    }

    protected function mapDpSettlement(DeliveryPersonSettlement $s): array
    {
        $dt = $s->settled_at ?? $s->created_at ?? now();
        $amountMinor = StoreAccountService::decimalToMinor($s->amount);

        return [
            'uid' => 'dps:' . $s->id,
            'occurred_at' => $dt->format('d M Y, H:i'),
            'occurred_at_ts' => $dt->timestamp,
            'reference' => $s->settlement_reference,
            'booking_id' => null,
            'booking_reference' => null,
            'transaction_type' => 'delivery_person_cash_remittance',
            'transaction_type_label' => 'DP Cash Handover to Platform',
            'classification' => 'Cash Receipt',
            'is_cash_movement' => true,
            'counterparty' => $s->deliveryPerson?->full_name ?? 'Delivery Person',
            'payment_method' => $s->settlement_channel,
            'description' => $s->notes ?: 'Delivery Person remitted collected customer cash to platform',
            'money_in_minor' => $amountMinor,
            'money_out_minor' => 0,
            'accounting_amount_minor' => $amountMinor,
            'currency' => strtoupper((string) $s->currency),
            'status' => 'Settled',
            'status_badge' => 'bg-success-subtle text-success',
            'detail_payload' => [
                'source_table' => 'delivery_person_settlements',
                'settlement_channel' => $s->settlement_channel,
                'settled_by_admin' => $s->admin?->name,
            ],
        ];
    }

    protected function mapAdminAccountTx(AdminAccountTransaction $tx): array
    {
        $dt = $tx->created_at ?? now();
        $amountMinor = StoreAccountService::decimalToMinor($tx->amount);
        $isCredit = $tx->direction === AdminAccountTransaction::DIRECTION_CREDIT;

        return [
            'uid' => 'aat:' . $tx->id,
            'occurred_at' => $dt->format('d M Y, H:i'),
            'occurred_at_ts' => $dt->timestamp,
            'reference' => $tx->reference,
            'booking_id' => $tx->booking_id,
            'booking_reference' => $tx->booking?->reference,
            'transaction_type' => $tx->transaction_type,
            'transaction_type_label' => ucfirst(str_replace('_', ' ', (string) $tx->transaction_type)),
            'classification' => 'Noncash Adjustment',
            'is_cash_movement' => false,
            'counterparty' => 'Platform Account',
            'payment_method' => null,
            'description' => $tx->description,
            'money_in_minor' => 0,
            'money_out_minor' => 0,
            'accounting_amount_minor' => $amountMinor,
            'currency' => strtoupper((string) $tx->currency),
            'status' => $isCredit ? 'Credit' : 'Debit',
            'status_badge' => $isCredit ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary',
            'detail_payload' => [
                'source_table' => 'admin_account_transactions',
                'direction' => $tx->direction,
                'balance_after' => $tx->balance_after,
                'created_by' => $tx->createdByAdmin?->name,
            ],
        ];
    }

    protected function mapRefundTransaction(\App\Models\RefundTransaction $rft): array
    {
        $dt = $rft->processed_at ?? $rft->created_at ?? now();
        $isCompleted = $rft->isCompletedDisbursement();

        return [
            'uid' => 'rft:' . $rft->id,
            'occurred_at' => $dt->format('d M Y, H:i'),
            'occurred_at_ts' => $dt->timestamp,
            'reference' => $rft->refund_reference,
            'booking_id' => $rft->booking_id,
            'booking_reference' => $rft->booking?->reference,
            'transaction_type' => 'customer_refund',
            'transaction_type_label' => 'Customer Refund (' . strtoupper((string) $rft->payment_method) . ')',
            'classification' => $isCompleted ? 'Refund' : 'Payable',
            'is_cash_movement' => $isCompleted,
            'counterparty' => $rft->booking?->customer_name ?? 'Customer',
            'payment_method' => $rft->payment_method,
            'description' => $rft->notes ?: ('Refund ' . $rft->refund_reference . ($rft->claim ? ' for ' . $rft->claim->case_reference : '')),
            'money_in_minor' => 0,
            'money_out_minor' => $isCompleted ? (int) $rft->amount_minor : 0,
            'accounting_amount_minor' => (int) $rft->amount_minor,
            'currency' => strtoupper((string) $rft->currency),
            'status' => ucfirst(str_replace('_', ' ', (string) $rft->status)),
            'status_badge' => $isCompleted ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning-emphasis',
            'detail_payload' => [
                'source_table' => 'refund_transactions',
                'case_reference' => $rft->claim?->case_reference,
                'provider_refund_id' => $rft->provider_refund_id ?: ($rft->external_reference ?: 'N/A'),
                'payout_recipient' => $rft->payout_recipient,
                'payout_method' => $rft->payout_method,
                'approved_by' => $rft->approvedByAdmin?->name,
                'processed_by' => $rft->processedByAdmin?->name,
            ],
        ];
    }
}
