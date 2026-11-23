<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StoreTransaction;
use App\Services\CurrencyService;
use App\Services\StoreAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Models\Store;
use Throwable;

class StoreAccountController extends Controller
{
    public function __construct(
        protected StoreAccountService $accountService,
        protected CurrencyService $currencyService
    ) {}

    public function show(Request $request, Store $store): View
    {
        $summary = $this->accountService->getStoreAccountSummary($store);

        $transactions = StoreTransaction::query()
            ->where('store_id', $store->id)
            ->with(['booking:id,reference', 'createdByAdmin:id,name', 'relatedTransaction:id,reference'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin::stores.account', [
            'store' => $store,
            'summary' => $summary,
            'transactions' => $transactions,
            'currencyService' => $this->currencyService,
        ]);
    }

    public function adjust(Request $request, Store $store): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', 'max:10'],
            'direction' => ['required', Rule::in([StoreTransaction::DIRECTION_CREDIT, StoreTransaction::DIRECTION_DEBIT])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'reason' => ['required', 'string', 'min:4', 'max:450'],
            'confirm_adjustment' => ['accepted'],
        ]);

        $amountMinor = StoreAccountService::decimalToMinor((string) $validated['amount']);

        try {
            $tx = $this->accountService->createManualAdjustment(
                $store,
                strtoupper(trim($validated['currency'])),
                $amountMinor,
                $validated['direction'],
                $validated['reason'],
                (int) Auth::guard('admin')->id()
            );

            return back()->with('success', "Recorded {$tx->type_label} ({$tx->reference}) for {$tx->currency} {$tx->amount}.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reverse(Request $request, Store $store, StoreTransaction $transaction): RedirectResponse
    {
        abort_unless((int) $transaction->store_id === (int) $store->id, 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:4', 'max:450'],
        ]);

        try {
            $rev = $this->accountService->createReversal(
                $transaction,
                $validated['reason'],
                (int) Auth::guard('admin')->id()
            );

            return back()->with('success', "Created compensating reversal {$rev->reference} for {$transaction->reference}.");
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
