<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BookingCustomerPayment;
use App\Models\DeliveryPersonSettlement;
use App\Services\CollectionPaymentService;
use App\Services\CurrencyService;
use App\Services\DeliveryPersonCashService;
use App\Services\StoreAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Modules\Admin\Models\DeliveryPerson;
use RuntimeException;

class DeliveryPersonSettlementController extends Controller
{
    public function __construct(
        protected DeliveryPersonCashService $cashService,
        protected CollectionPaymentService $paymentService,
        protected CurrencyService $currencyService
    ) {}

    public function index(Request $request)
    {
        $people = DeliveryPerson::query()->orderBy('full_name')->get();
        $selectedPersonId = $request->integer('delivery_person_id') ?: $people->first()?->id;
        $selectedPerson = $selectedPersonId ? $people->firstWhere('id', $selectedPersonId) : null;

        $personBalances = [];
        foreach ($people as $p) {
            $personBalances[$p->id] = $this->cashService->getSummary($p);
        }

        $settlements = DeliveryPersonSettlement::query()
            ->with(['deliveryPerson', 'admin'])
            ->when($selectedPersonId && $request->filled('delivery_person_id'), fn ($q) => $q->where('delivery_person_id', $selectedPersonId))
            ->orderByDesc('settled_at')
            ->paginate(20)
            ->withQueryString();

        $pendingTransfers = BookingCustomerPayment::query()
            ->with(['booking', 'deliveryPerson'])
            ->where('payment_method', BookingCustomerPayment::METHOD_ONLINE_TRANSFER)
            ->where('status', BookingCustomerPayment::STATUS_AWAITING_VERIFICATION)
            ->orderByDesc('id')
            ->get();

        return view('admin::delivery-persons.settlements', [
            'people' => $people,
            'selectedPerson' => $selectedPerson,
            'personBalances' => $personBalances,
            'settlements' => $settlements,
            'pendingTransfers' => $pendingTransfers,
            'currencyService' => $this->currencyService,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_person_id' => ['required', 'integer', 'exists:delivery_persons,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'settlement_channel' => ['required', 'in:cash_handover,bank_transfer,other'],
            'notes' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:150'],
        ]);

        $person = DeliveryPerson::findOrFail($validated['delivery_person_id']);
        $currency = strtoupper((string) ($person->currency ?: $this->currencyService->code()));
        $amountMinor = StoreAccountService::decimalToMinor($validated['amount']);
        $adminId = (int) Auth::guard('admin')->id();

        try {
            $settlement = $this->cashService->recordAdminSettlement(
                $person,
                $amountMinor,
                $currency,
                $validated['settlement_channel'],
                $adminId,
                $validated['notes'] ?? null,
                $validated['idempotency_key'] ?? null
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.delivery-person-settlements.index', ['delivery_person_id' => $person->id])
            ->with('success', "Recorded daily settlement {$settlement->settlement_reference}.");
    }

    public function verifyTransfer(Request $request, BookingCustomerPayment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $adminId = (int) Auth::guard('admin')->id();

        try {
            $this->paymentService->verifyOnlineTransfer(
                $payment,
                $adminId,
                $validated['decision'] === 'approve',
                $validated['notes'] ?? null
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['transfer' => $e->getMessage()]);
        }

        return back()->with('success', 'Online transfer verification updated.');
    }

    public function updatePortalAccess(Request $request, DeliveryPerson $deliveryPerson): RedirectResponse
    {
        $validated = $request->validate([
            'portal_access_enabled' => ['required', 'boolean'],
            'password' => ['nullable', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $updates = [
            'portal_access_enabled' => (bool) $validated['portal_access_enabled'],
            'phone_normalized' => DeliveryPerson::normalizePhone($deliveryPerson->phone, $deliveryPerson->phone_country_code),
        ];

        if (! empty($validated['password'])) {
            $updates['password'] = $validated['password'];
        }

        if ($updates['portal_access_enabled'] && empty($deliveryPerson->password) && empty($updates['password'])) {
            return back()->withErrors(['password' => 'Set an initial password when enabling portal access.']);
        }

        $deliveryPerson->forceFill($updates)->save();

        return back()->with('success', "Updated portal access for {$deliveryPerson->full_name}.");
    }
}
