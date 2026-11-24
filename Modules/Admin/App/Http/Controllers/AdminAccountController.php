<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminAccount;
use App\Models\AdminAccountTransaction;
use App\Models\Booking;
use App\Services\AdminAccountService;
use App\Services\CurrencyService;
use App\Services\CurrentAccountReportService;
use App\Services\StoreAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Store;
use RuntimeException;

class AdminAccountController extends Controller
{
    public function __construct(
        protected AdminAccountService $adminAccountService,
        protected CurrencyService $currencyService,
        protected CurrentAccountReportService $reportService
    ) {}

    public function index(Request $request)
    {
        $selectedCurrency = strtoupper(trim((string) $request->input('currency', $this->currencyService->code())));
        $month = $request->input('month');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $reportSummary = $this->reportService->getDashboardSummary($selectedCurrency, $fromDate, $toDate, $month);
        $period = $reportSummary['period'];
        $monthlyTrend = $this->reportService->getMonthlyTrend($selectedCurrency, $period['month']);
        $activeSummary = $reportSummary['admin_ledger_summary'];
        $availableCurrencies = $this->reportService->getAvailableCurrencies();

        $filters = [
            'currency' => $selectedCurrency,
            'month' => $period['month'],
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'transaction_type' => $request->input('transaction_type'),
            'classification' => $request->input('classification'),
            'status' => $request->input('status'),
            'payment_method' => $request->input('payment_method'),
            'booking_reference' => $request->input('booking_reference'),
            'store_id' => $request->input('store_id'),
            'delivery_person_id' => $request->input('delivery_person_id'),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by', 'occurred_at'),
            'sort_dir' => $request->input('sort_dir', 'desc'),
        ];

        $unifiedTransactions = $this->reportService->getUnifiedTransactions($filters, 20);

        $adminTransactions = AdminAccountTransaction::query()
            ->with(['booking', 'createdByAdmin'])
            ->where('currency', $selectedCurrency)
            ->where('created_at', '>=', $period['from_utc'])
            ->where('created_at', '<', $period['to_exclusive_utc'])
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        $reversedTransactionIds = AdminAccountTransaction::query()
            ->where('transaction_type', AdminAccountTransaction::TYPE_REVERSAL)
            ->whereNotNull('related_transaction_id')
            ->pluck('related_transaction_id')
            ->all();

        $stores = Store::query()->orderBy('name')->get(['id', 'name']);
        $deliveryPersons = DeliveryPerson::query()->orderBy('full_name')->get(['id', 'full_name', 'code']);

        return view('admin::accounts.index', [
            'selectedCurrency' => $selectedCurrency,
            'availableCurrencies' => $availableCurrencies,
            'period' => $period,
            'monthlyTrend' => $monthlyTrend,
            'reportSummary' => $reportSummary,
            'activeSummary' => $activeSummary,
            'unifiedTransactions' => $unifiedTransactions,
            'transactions' => $adminTransactions,
            'reversedTransactionIds' => $reversedTransactionIds,
            'currencyService' => $this->currencyService,
            'stores' => $stores,
            'deliveryPersons' => $deliveryPersons,
            'classifications' => [
                'Cash Receipt',
                'Custodial Cash',
                'Earned Revenue',
                'Payable',
                'Recorded Expense',
                'Cash Disbursement',
                'Refund',
                'Noncash Adjustment',
            ],
            'transactionTypes' => [
                'customer_stripe_payment' => 'Customer Stripe Payment',
                'customer_paypal_payment' => 'Customer PayPal Payment',
                'customer_online_transfer' => 'Customer Online Transfer',
                'delivery_person_cash_collection' => 'Delivery Person Cash Collection',
                'delivery_person_cash_remittance' => 'DP Cash Handover to Platform',
                'store_commission_earned' => 'Store Commission Earned',
                'store_share_payable' => 'Store Share Payable',
                'store_settlement_paid' => 'Store Settlement Paid',
                'collection_job_earning' => 'Collection Job Earning',
                'final_delivery_job_earning' => 'Final Delivery Job Earning',
                'delivery_person_payout' => 'Delivery Person Payout',
                'customer_refund' => 'Customer Refund',
                'financial_adjustment' => 'Financial Adjustment',
                AdminAccountTransaction::TYPE_DELIVERY_MARGIN_CREDIT => 'Delivery Margin Credit',
                AdminAccountTransaction::TYPE_DELIVERY_SHORTFALL_DEBIT => 'Delivery Shortfall Debit',
                AdminAccountTransaction::TYPE_ADVANCE_SETTLEMENT => 'Advance Settlement',
                AdminAccountTransaction::TYPE_ADJUSTMENT => 'Manual Adjustment',
                AdminAccountTransaction::TYPE_REVERSAL => 'Compensating Reversal',
            ],
        ]);
    }

    public function export(Request $request)
    {
        $selectedCurrency = strtoupper(trim((string) $request->input('currency', $this->currencyService->code())));
        $period = $this->reportService->resolvePeriod(
            $request->input('month'),
            $request->input('from_date'),
            $request->input('to_date')
        );

        $rows = $this->reportService->getExportRows([
            'currency' => $selectedCurrency,
            'month' => $period['month'],
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'transaction_type' => $request->input('transaction_type'),
            'classification' => $request->input('classification'),
            'status' => $request->input('status'),
            'payment_method' => $request->input('payment_method'),
            'booking_reference' => $request->input('booking_reference'),
            'store_id' => $request->input('store_id'),
            'delivery_person_id' => $request->input('delivery_person_id'),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by', 'occurred_at'),
            'sort_dir' => $request->input('sort_dir', 'desc'),
        ]);

        $filename = 'current-account-' . $period['month'] . '-' . strtolower($selectedCurrency) . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Date',
                'Reference',
                'Booking',
                'Type',
                'Classification',
                'Counterparty',
                'Description',
                'Money In',
                'Money Out',
                'Accounting Amount',
                'Currency',
                'Status',
            ]);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['occurred_at'],
                    $r['reference'],
                    $r['booking_reference'] ?? '-',
                    $r['transaction_type_label'],
                    $r['classification'],
                    $r['counterparty'],
                    $r['description'],
                    $r['money_in_minor'] > 0 ? number_format($r['money_in_minor'] / 100, 2, '.', '') : '0.00',
                    $r['money_out_minor'] > 0 ? number_format($r['money_out_minor'] / 100, 2, '.', '') : '0.00',
                    ! $r['is_cash_movement'] ? number_format($r['accounting_amount_minor'] / 100, 2, '.', '') : '0.00',
                    $r['currency'],
                    $r['status'],
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function detail(string $uid): JsonResponse
    {
        $detail = $this->reportService->getTransactionDetail($uid);
        if (! $detail) {
            return response()->json(['success' => false, 'message' => 'Transaction not found.'], 404);
        }

        $detail['money_in_formatted'] = $this->currencyService->formatMinor((int) $detail['money_in_minor'], $detail['currency']);
        $detail['money_out_formatted'] = $this->currencyService->formatMinor((int) $detail['money_out_minor'], $detail['currency']);
        $detail['accounting_amount_formatted'] = $this->currencyService->formatMinor((int) $detail['accounting_amount_minor'], $detail['currency']);
        $detail['booking_url'] = $detail['booking_id'] ? route('admin.bookings.show', $detail['booking_id']) : null;

        return response()->json(['success' => true, 'transaction' => $detail]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['nullable', 'string', 'size:3'],
            'month' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'transaction_type' => ['nullable', 'string', 'max:64'],
            'classification' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'max:64'],
            'payment_method' => ['nullable', 'string', 'max:64'],
            'booking_reference' => ['nullable', 'string', 'max:100'],
            'store_id' => ['nullable', 'integer', 'min:1'],
            'delivery_person_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:120'],
            'sort_by' => ['nullable', Rule::in(['occurred_at', 'transaction_type', 'counterparty', 'amount', 'status', 'reference', 'booking_reference'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $selectedCurrency = strtoupper(trim((string) ($validated['currency'] ?? $this->currencyService->code())));
        $period = $this->reportService->resolvePeriod(
            $validated['month'] ?? null,
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null
        );

        $perPage = (int) ($validated['per_page'] ?? 20);
        $unifiedTransactions = $this->reportService->getUnifiedTransactions([
            'currency' => $selectedCurrency,
            'month' => $period['month'],
            'from_date' => $validated['from_date'] ?? null,
            'to_date' => $validated['to_date'] ?? null,
            'transaction_type' => $validated['transaction_type'] ?? null,
            'classification' => $validated['classification'] ?? null,
            'status' => $validated['status'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'booking_reference' => $validated['booking_reference'] ?? null,
            'store_id' => $validated['store_id'] ?? null,
            'delivery_person_id' => $validated['delivery_person_id'] ?? null,
            'search' => $validated['search'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'occurred_at',
            'sort_dir' => $validated['sort_dir'] ?? 'desc',
            'page' => $validated['page'] ?? 1,
        ], $perPage);

        $rowsHtml = view('admin::accounts._transactions_rows', [
            'unifiedTransactions' => $unifiedTransactions,
            'currencyService' => $this->currencyService,
            'period' => $period,
        ])->render();

        $paginationHtml = $unifiedTransactions->hasPages()
            ? $unifiedTransactions->links()->toHtml()
            : '';

        return response()->json([
            'success' => true,
            'total' => $unifiedTransactions->total(),
            'period_label' => $period['month_label'],
            'rows_html' => $rowsHtml,
            'pagination_html' => $paginationHtml,
        ]);
    }

    public function syncStripeFees(Request $request): RedirectResponse
    {
        $currency = $request->filled('currency') ? strtoupper(trim((string) $request->input('currency'))) : null;
        $result = app(\App\Services\StripeFeeReconciliationService::class)->reconcilePendingPayments($currency, 30);

        return back()->with(
            'success',
            sprintf(
                'Stripe fee sync checked %d payment(s): %d reconciled, %d pending.',
                $result['checked'],
                $result['reconciled'],
                $result['pending']
            )
        );
    }

    public function adjust(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', 'size:3'],
            'direction' => ['required', Rule::in([
                AdminAccountTransaction::DIRECTION_CREDIT,
                AdminAccountTransaction::DIRECTION_DEBIT,
            ])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'description' => ['required', 'string', 'min:4', 'max:500'],
            'booking_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $bookingId = null;
        if (! empty($validated['booking_reference'])) {
            $booking = Booking::query()->where('reference', trim($validated['booking_reference']))->first();
            if (! $booking) {
                return back()->withErrors(['booking_reference' => 'Booking reference not found.'])->withInput();
            }
            $bookingId = $booking->id;
        }

        $amountMinor = StoreAccountService::decimalToMinor($validated['amount']);
        $adminId = (int) Auth::guard('admin')->id();

        try {
            $tx = $this->adminAccountService->createAdjustment(
                $validated['currency'],
                $validated['direction'],
                $amountMinor,
                $validated['description'],
                $adminId,
                $bookingId
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.current-account.index', ['currency' => $tx->currency])
            ->with('success', "Recorded admin account adjustment {$tx->reference}.");
    }

    public function reverse(Request $request, AdminAccountTransaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:4', 'max:500'],
        ]);

        $adminId = (int) Auth::guard('admin')->id();

        try {
            $reversal = $this->adminAccountService->createReversal($transaction, $validated['reason'], $adminId);
        } catch (RuntimeException $e) {
            return back()->withErrors(['reversal' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.current-account.index', ['currency' => $reversal->currency])
            ->with('success', "Recorded compensating reversal {$reversal->reference} for {$transaction->reference}.");
    }
}
