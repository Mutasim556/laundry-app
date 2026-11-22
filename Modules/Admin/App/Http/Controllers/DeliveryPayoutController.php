<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Admin\App\Services\DeliveryLogisticsService;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use Modules\Admin\Models\DeliveryPersonPayout;

class DeliveryPayoutController extends Controller
{
    public function __construct(
        protected DeliveryLogisticsService $logistics,
        protected CurrencyService $currencyService
    ) {}

    public function index(Request $request)
    {
        $persons = DeliveryPerson::withTrashed()->orderBy('full_name')->get();
        $summaries = [];
        foreach ($persons as $person) {
            $summaries[$person->id] = $this->logistics->getBalanceSummary($person);
        }

        $payouts = DeliveryPersonPayout::query()
            ->with(['deliveryPerson', 'recordedByAdmin'])
            ->when($request->filled('delivery_person_id'), fn ($q) => $q->where('delivery_person_id', $request->input('delivery_person_id')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $ledgerEntries = DeliveryPersonLedgerEntry::query()
            ->with(['deliveryPerson', 'booking', 'assignment', 'createdByAdmin'])
            ->when($request->filled('delivery_person_id'), fn ($q) => $q->where('delivery_person_id', $request->input('delivery_person_id')))
            ->latest('id')
            ->limit(50)
            ->get();

        $currencyService = $this->currencyService;

        return view('admin::delivery-persons.payouts', compact(
            'persons',
            'summaries',
            'payouts',
            'ledgerEntries',
            'currencyService'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_person_id' => ['required', 'exists:delivery_persons,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'cash', 'other'])],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
        ]);

        $person = DeliveryPerson::withTrashed()->findOrFail($validated['delivery_person_id']);
        $currency = $person->currency ?: $this->currencyService->code();
        $amountMinor = $this->currencyService->toMinor($validated['amount'], $currency);

        $payout = $this->logistics->recordPayout(
            $person,
            $amountMinor,
            $currency,
            $validated['payment_method'],
            $validated['payment_date'],
            $validated['external_reference'] ?? null,
            $validated['notes'] ?? null,
            (int) Auth::guard('admin')->id(),
            $validated['idempotency_key'] ?? null
        );

        return back()->with('success', "Payout {$payout->payout_reference} recorded for {$person->full_name}.");
    }

    public function storeAdjustment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_person_id' => ['required', 'exists:delivery_persons,id'],
            'transaction_type' => ['required', Rule::in([
                DeliveryPersonLedgerEntry::TYPE_EARNING_ADJUSTMENT,
                DeliveryPersonLedgerEntry::TYPE_EARNING_REVERSAL,
                DeliveryPersonLedgerEntry::TYPE_PAYOUT_REVERSAL,
                DeliveryPersonLedgerEntry::TYPE_AUTHORIZED_CORRECTION,
            ])],
            'direction' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $person = DeliveryPerson::withTrashed()->findOrFail($validated['delivery_person_id']);
        $currency = $person->currency ?: $this->currencyService->code();
        $amountMinor = $this->currencyService->toMinor($validated['amount'], $currency);

        $entry = $this->logistics->recordLedgerAdjustment(
            $person,
            $validated['transaction_type'],
            $validated['direction'],
            $amountMinor,
            $currency,
            $validated['notes'],
            (int) Auth::guard('admin')->id()
        );

        return back()->with('success', "Ledger entry {$entry->entry_reference} recorded.");
    }
}
