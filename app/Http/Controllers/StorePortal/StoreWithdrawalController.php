<?php

namespace App\Http\Controllers\StorePortal;

use App\Http\Controllers\Controller;
use App\Models\StoreBankAccount;
use App\Models\StoreUser;
use App\Models\StoreWithdrawalRequest;
use App\Services\CurrencyService;
use App\Services\StoreWithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreWithdrawalController extends Controller
{
    public function __construct(
        protected StoreWithdrawalService $withdrawalService,
        protected CurrencyService $currencyService
    ) {}

    protected function storeUser(): StoreUser
    {
        return Auth::guard('store')->user()->load('store');
    }

    public function modalData(): JsonResponse
    {
        $store = $this->storeUser()->store;
        $cur = $this->withdrawalService->getStorePrimaryCurrency($store);
        $availableMinor = $this->withdrawalService->getAvailableBalanceMinor($store, $cur);

        $accounts = StoreBankAccount::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toSafeArray()
            ->values();

        return response()->json([
            'status' => 'ok',
            'currency' => $cur,
            'available_balance_minor' => $availableMinor,
            'available_balance_decimal' => number_format($this->currencyService->fromMinor($availableMinor, $cur), $this->currencyService->decimals($cur), '.', ''),
            'available_balance_formatted' => $this->currencyService->formatMinor($availableMinor, $cur),
            'has_bank_account' => $accounts->isNotEmpty(),
            'bank_accounts' => $accounts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;
        $cur = $this->withdrawalService->getStorePrimaryCurrency($store);

        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
        ]);

        $amountMinor = $this->currencyService->toMinor($validated['amount'], $cur);
        $withdrawal = $this->withdrawalService->submitWithdrawal(
            $store,
            (int) $validated['bank_account_id'],
            $amountMinor,
            $storeUser->id,
            $validated['notes'] ?? null,
            $validated['idempotency_key'] ?? null
        );

        $month = (string) ($request->input('month') ?: $withdrawal->request_month);
        $summary = $this->withdrawalService->getMonthlySummary($store, $month, $cur);

        return response()->json([
            'status' => 'ok',
            'message' => "Withdrawal request {$withdrawal->withdrawal_reference} submitted. Amount reserved from available store balance.",
            'withdrawal' => [
                'id' => $withdrawal->id,
                'withdrawal_reference' => $withdrawal->withdrawal_reference,
                'amount_minor' => $withdrawal->amount_minor,
                'amount_formatted' => $this->currencyService->formatMinor($withdrawal->amount_minor, $cur),
                'status' => $withdrawal->status,
                'status_meta' => $withdrawal->statusMeta(),
                'bank_snapshot' => $withdrawal->bank_snapshot_json,
                'requested_at' => $withdrawal->requested_at?->format('d M Y, H:i'),
            ],
            'summary' => $summary,
        ], 201);
    }

    public function show(StoreWithdrawalRequest $withdrawal): JsonResponse
    {
        $store = $this->storeUser()->store;
        if ((int) $withdrawal->store_id !== (int) $store->id) {
            abort(403, 'You do not have permission to view this store withdrawal request.');
        }

        return response()->json([
            'status' => 'ok',
            'withdrawal' => [
                'id' => $withdrawal->id,
                'withdrawal_reference' => $withdrawal->withdrawal_reference,
                'amount_formatted' => $this->currencyService->formatMinor($withdrawal->amount_minor, $withdrawal->currency),
                'available_before_formatted' => $this->currencyService->formatMinor($withdrawal->available_balance_before_minor, $withdrawal->currency),
                'currency' => $withdrawal->currency,
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
                'approved_at' => $withdrawal->approved_at?->format('d M Y, H:i'),
                'rejected_at' => $withdrawal->rejected_at?->format('d M Y, H:i'),
                'settled_at' => $withdrawal->settled_at?->format('d M Y, H:i'),
                'audit_history' => $withdrawal->audit_history ?? [],
            ],
        ]);
    }
}
