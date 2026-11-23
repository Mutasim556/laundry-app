<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminAccount;
use App\Models\AdminAccountTransaction;
use App\Models\Booking;
use App\Services\AdminAccountService;
use App\Services\CurrencyService;
use App\Services\StoreAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use RuntimeException;

class AdminAccountController extends Controller
{
    public function __construct(
        protected AdminAccountService $adminAccountService,
        protected CurrencyService $currencyService
    ) {}

    public function index(Request $request)
    {
        $selectedCurrency = strtoupper(trim((string) $request->input('currency', $this->currencyService->code())));
        $summaryByCurrency = $this->adminAccountService->getSummaryByCurrency();
        $activeSummary = $summaryByCurrency[$selectedCurrency] ?? [
            'currency' => $selectedCurrency,
            'current_balance_minor' => 0,
            'total_credited_minor' => 0,
            'total_debited_minor' => 0,
            'awaiting_advance_minor' => 0,
        ];

        $query = AdminAccountTransaction::query()
            ->with(['booking', 'createdByAdmin'])
            ->where('currency', $selectedCurrency)
            ->orderByDesc('id');

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->input('transaction_type'));
        }
        if ($request->filled('booking_reference')) {
            $ref = trim((string) $request->input('booking_reference'));
            $query->whereHas('booking', fn ($q) => $q->where('reference', 'like', "%{$ref}%"));
        }

        $transactions = $query->paginate(20)->withQueryString();
        $availableCurrencies = array_unique(array_merge(
            [$this->currencyService->code()],
            AdminAccount::query()->pluck('currency')->all()
        ));

        $reversedTransactionIds = AdminAccountTransaction::query()
            ->where('transaction_type', AdminAccountTransaction::TYPE_REVERSAL)
            ->whereNotNull('related_transaction_id')
            ->pluck('related_transaction_id')
            ->all();

        return view('admin::accounts.index', [
            'selectedCurrency' => $selectedCurrency,
            'availableCurrencies' => $availableCurrencies,
            'summaryByCurrency' => $summaryByCurrency,
            'activeSummary' => $activeSummary,
            'transactions' => $transactions,
            'reversedTransactionIds' => $reversedTransactionIds,
            'currencyService' => $this->currencyService,
            'transactionTypes' => [
                AdminAccountTransaction::TYPE_DELIVERY_MARGIN_CREDIT => 'Delivery Margin Credit',
                AdminAccountTransaction::TYPE_DELIVERY_SHORTFALL_DEBIT => 'Delivery Shortfall Debit',
                AdminAccountTransaction::TYPE_ADVANCE_SETTLEMENT => 'Advance Settlement',
                AdminAccountTransaction::TYPE_ADJUSTMENT => 'Manual Adjustment',
                AdminAccountTransaction::TYPE_REVERSAL => 'Compensating Reversal',
            ],
        ]);
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
