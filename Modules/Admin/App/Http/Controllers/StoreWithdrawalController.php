<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StoreTransaction;
use App\Models\StoreWithdrawalRequest;
use App\Services\CurrencyService;
use App\Services\StoreAccountService;
use App\Services\StoreWithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Admin\Models\Store;

class StoreWithdrawalController extends Controller
{
    public function __construct(
        protected StoreWithdrawalService $withdrawalService,
        protected CurrencyService $currencyService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $status = $request->input('status');
        $storeId = $request->input('store_id');
        $rawMonth = $request->input('month');
        $showAllMonths = $rawMonth === 'all';
        $period = $this->withdrawalService->resolveMonthPeriod($showAllMonths ? null : $rawMonth);
        $activeMonth = $showAllMonths ? 'all' : $period['month'];
        $search = trim((string) $request->input('search', ''));

        $baseQuery = StoreWithdrawalRequest::query();
        if (! $showAllMonths) {
            $baseQuery->where('request_month', $period['month']);
        }
        if ($storeId) {
            $baseQuery->where('store_id', (int) $storeId);
        }

        $query = (clone $baseQuery)
            ->with(['store:id,name', 'approvedByAdmin:id,name', 'rejectedByAdmin:id,name', 'settledByAdmin:id,name'])
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

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('withdrawal_reference', 'like', "%{$search}%")
                    ->orWhere('external_reference', 'like', "%{$search}%")
                    ->orWhereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $withdrawals = $query->paginate(20)->withQueryString();

        $defaultCur = $this->currencyService->code();
        $kpis = [
            'pending' => (clone $baseQuery)->where('status', StoreWithdrawalRequest::STATUS_PENDING)->count(),
            'approved' => (clone $baseQuery)->where('status', StoreWithdrawalRequest::STATUS_APPROVED)->count(),
            'settled' => (clone $baseQuery)->where('status', StoreWithdrawalRequest::STATUS_MANUALLY_SETTLED)->count(),
            'rejected' => (clone $baseQuery)->where('status', StoreWithdrawalRequest::STATUS_REJECTED)->count(),
            'pending_formatted' => $this->currencyService->formatMinor((int) (clone $baseQuery)->where('status', StoreWithdrawalRequest::STATUS_PENDING)->sum('amount_minor'), $defaultCur),
            'approved_formatted' => $this->currencyService->formatMinor((int) (clone $baseQuery)->where('status', StoreWithdrawalRequest::STATUS_APPROVED)->sum('amount_minor'), $defaultCur),
            'settled_formatted' => $this->currencyService->formatMinor((int) (clone $baseQuery)->where('status', StoreWithdrawalRequest::STATUS_MANUALLY_SETTLED)->sum('amount_minor'), $defaultCur),
            'rejected_formatted' => $this->currencyService->formatMinor((int) (clone $baseQuery)->where('status', StoreWithdrawalRequest::STATUS_REJECTED)->sum('amount_minor'), $defaultCur),
        ];

        $stores = Store::orderBy('name')->get(['id', 'name']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'period' => $period,
                'active_month' => $activeMonth,
                'counts' => $kpis,
                'table_html' => view('admin::stores.partials.withdrawals-table', [
                    'withdrawals' => $withdrawals,
                    'currencyService' => $this->currencyService,
                ])->render(),
            ]);
        }

        return view('admin::stores.withdrawals', [
            'withdrawals' => $withdrawals,
            'stores' => $stores,
            'currencyService' => $this->currencyService,
            'period' => $period,
            'activeMonth' => $activeMonth,
            'kpis' => $kpis,
            'activeStatus' => $status,
            'activeStoreId' => $storeId,
            'search' => $search,
        ]);
    }

    public function show(StoreWithdrawalRequest $withdrawal): JsonResponse
    {
        $withdrawal->load(['store', 'approvedByAdmin:id,name', 'rejectedByAdmin:id,name', 'settledByAdmin:id,name']);
        $store = $withdrawal->store;
        $cur = $withdrawal->currency;
        $currentAvailMinor = $store ? $this->withdrawalService->getAvailableBalanceMinor($store, $cur) : 0;

        $recentEntries = $store
            ? StoreTransaction::query()
                ->where('store_id', $store->id)
                ->orderByDesc('id')
                ->limit(6)
                ->get()
                ->map(fn (StoreTransaction $tx) => [
                    'reference' => $tx->reference,
                    'type' => ucfirst(str_replace('_', ' ', (string) $tx->transaction_type)),
                    'direction' => $tx->direction,
                    'amount_formatted' => $this->currencyService->formatMinor(StoreAccountService::decimalToMinor($tx->amount), $tx->currency),
                    'occurred_at' => $tx->created_at?->format('d M Y, H:i'),
                ])->values()
            : [];

        return response()->json([
            'status' => 'ok',
            'withdrawal' => [
                'id' => $withdrawal->id,
                'withdrawal_reference' => $withdrawal->withdrawal_reference,
                'store_name' => $store?->name ?? 'Store',
                'amount_formatted' => $this->currencyService->formatMinor($withdrawal->amount_minor, $cur),
                'available_before_formatted' => $this->currencyService->formatMinor($withdrawal->available_balance_before_minor, $cur),
                'current_available_formatted' => $this->currencyService->formatMinor($currentAvailMinor, $cur),
                'currency' => $cur,
                'request_month' => $withdrawal->request_month,
                'status' => $withdrawal->status,
                'status_meta' => $withdrawal->statusMeta(),
                'bank_snapshot' => $withdrawal->bank_snapshot_json,
                'notes' => $withdrawal->notes,
                'rejection_reason' => $withdrawal->rejection_reason,
                'payment_date' => $withdrawal->payment_date?->format('d M Y'),
                'external_reference' => $withdrawal->external_reference,
                'settlement_notes' => $withdrawal->settlement_notes,
                'requested_at' => $withdrawal->requested_at?->format('d M Y, H:i'),
                'audit_history' => $withdrawal->audit_history ?? [],
                'recent_ledger' => $recentEntries,
            ],
        ]);
    }

    public function approve(Request $request, StoreWithdrawalRequest $withdrawal): JsonResponse
    {
        $validated = $request->validate([
            'payout_method' => ['nullable', 'in:bank_transfer,cash,other'],
            'paid_from_account' => ['nullable', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $this->withdrawalService->approveWithdrawal(
            $withdrawal,
            (int) Auth::guard('admin')->id(),
            $validated['note'] ?? null,
            $validated['payout_method'] ?? 'bank_transfer',
            $validated['paid_from_account'] ?? null
        );

        return response()->json([
            'status' => 'ok',
            'message' => "Store withdrawal {$updated->withdrawal_reference} approved. Reserved funds remain committed for manual transfer.",
            'withdrawal_status' => $updated->status,
        ]);
    }

    public function reject(Request $request, StoreWithdrawalRequest $withdrawal): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $updated = $this->withdrawalService->rejectWithdrawal(
            $withdrawal,
            (int) Auth::guard('admin')->id(),
            $validated['reason']
        );

        return response()->json([
            'status' => 'ok',
            'message' => "Store withdrawal {$updated->withdrawal_reference} rejected and reserved balance restored.",
            'withdrawal_status' => $updated->status,
        ]);
    }

    public function settle(Request $request, StoreWithdrawalRequest $withdrawal): JsonResponse
    {
        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'settlement_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $this->withdrawalService->markManuallySettled(
            $withdrawal,
            (int) Auth::guard('admin')->id(),
            $validated
        );

        return response()->json([
            'status' => 'ok',
            'message' => "Store withdrawal {$updated->withdrawal_reference} marked as Manually Settled.",
            'withdrawal_status' => $updated->status,
        ]);
    }
}
