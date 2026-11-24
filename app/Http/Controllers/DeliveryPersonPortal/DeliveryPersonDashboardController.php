<?php

namespace App\Http\Controllers\DeliveryPersonPortal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\DeliveryPersonCashTransaction;
use App\Models\DeliveryPersonSettlement;
use App\Services\CurrencyService;
use App\Services\DeliveryPersonCashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;

class DeliveryPersonDashboardController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected DeliveryPersonCashService $cashService
    ) {}

    protected function person(): DeliveryPerson
    {
        return Auth::guard('delivery_person')->user();
    }

    public function dashboard(): View
    {
        $person = $this->person();
        $currency = strtoupper((string) ($person->currency ?: $this->currencyService->code()));

        $activeBase = DeliveryAssignment::query()
            ->where('delivery_person_id', $person->id)
            ->whereNotIn('status', [DeliveryAssignment::STATUS_CANCELLED, DeliveryAssignment::STATUS_FAILED]);

        $collectionJobsCount = (clone $activeBase)
            ->where('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)
            ->count();

        $finalDeliveryJobsCount = (clone $activeBase)
            ->where('assignment_type', DeliveryAssignment::TYPE_FINAL_DELIVERY)
            ->count();

        $pendingCollectionsCount = (clone $activeBase)
            ->where('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)
            ->whereNotIn('status', [DeliveryAssignment::STATUS_HANDED_OVER, DeliveryAssignment::STATUS_COMPLETED])
            ->count();

        $completedJobsCount = DeliveryAssignment::query()
            ->where('delivery_person_id', $person->id)
            ->whereIn('status', [DeliveryAssignment::STATUS_HANDED_OVER, DeliveryAssignment::STATUS_COMPLETED])
            ->count();

        $cashSummary = $this->cashService->getSummary($person, $currency);
        $earningsBalanceMinor = $this->cashService->getEarningsBalanceMinor($person, $currency);

        $recentAssignments = DeliveryAssignment::query()
            ->with(['booking', 'store'])
            ->where('delivery_person_id', $person->id)
            ->whereNotIn('status', [DeliveryAssignment::STATUS_CANCELLED])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('delivery-person-portal.dashboard', [
            'person' => $person,
            'currency' => $currency,
            'currencyService' => $this->currencyService,
            'collectionJobsCount' => $collectionJobsCount,
            'finalDeliveryJobsCount' => $finalDeliveryJobsCount,
            'pendingCollectionsCount' => $pendingCollectionsCount,
            'completedJobsCount' => $completedJobsCount,
            'cashSummary' => $cashSummary,
            'earningsBalanceMinor' => $earningsBalanceMinor,
            'recentAssignments' => $recentAssignments,
        ]);
    }

    public function bookings(Request $request): View
    {
        return $this->listAssignments($request, null, 'My Assigned Bookings');
    }

    public function collections(Request $request): View
    {
        return $this->listAssignments($request, DeliveryAssignment::TYPE_CUSTOMER_COLLECTION, 'Collection Bookings');
    }

    public function finalDeliveries(Request $request): View
    {
        return $this->listAssignments($request, DeliveryAssignment::TYPE_FINAL_DELIVERY, 'Final Delivery Bookings');
    }

    protected function listAssignments(Request $request, ?string $type, string $heading): View
    {
        $person = $this->person();

        $query = Booking::query()
            ->with(['assignedStore', 'currentCollectionAssignment', 'currentFinalDeliveryAssignment'])
            ->whereHas('deliveryAssignments', function ($q) use ($person, $type) {
                $q->where('delivery_person_id', $person->id)
                    ->whereNotIn('status', [DeliveryAssignment::STATUS_CANCELLED, DeliveryAssignment::STATUS_FAILED]);
                if ($type !== null) {
                    $q->where('assignment_type', $type);
                }
            })
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('reference', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%");
            });
        }

        $bookings = $query->paginate(15)->withQueryString();

        return view('delivery-person-portal.bookings.index', [
            'person' => $person,
            'heading' => $heading,
            'filterType' => $type,
            'bookings' => $bookings,
            'currencyService' => $this->currencyService,
        ]);
    }

    public function cashAccount(Request $request): View
    {
        $person = $this->person();
        $currency = strtoupper((string) ($person->currency ?: $this->currencyService->code()));
        $cashSummary = $this->cashService->getSummary($person, $currency);

        $txQuery = DeliveryPersonCashTransaction::query()
            ->with(['booking', 'settlement'])
            ->where('delivery_person_id', $person->id)
            ->orderByDesc('id');

        if ($request->filled('from_date')) {
            $txQuery->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $txQuery->whereDate('created_at', '<=', $request->input('to_date'));
        }
        if ($request->filled('payment_channel')) {
            $txQuery->where('payment_channel', $request->input('payment_channel'));
        }
        if ($request->filled('transaction_type')) {
            $txQuery->where('transaction_type', $request->input('transaction_type'));
        }
        if ($request->filled('booking_reference')) {
            $ref = trim((string) $request->input('booking_reference'));
            $txQuery->whereHas('booking', fn ($q) => $q->where('reference', 'like', "%{$ref}%"));
        }

        $transactions = $txQuery->paginate(20)->withQueryString();

        return view('delivery-person-portal.cash-account', [
            'person' => $person,
            'currency' => $currency,
            'currencyService' => $this->currencyService,
            'cashSummary' => $cashSummary,
            'transactions' => $transactions,
        ]);
    }

    public function settlements(): View
    {
        $person = $this->person();
        $currency = strtoupper((string) ($person->currency ?: $this->currencyService->code()));
        $cashSummary = $this->cashService->getSummary($person, $currency);
        $settlements = DeliveryPersonSettlement::query()
            ->with('admin')
            ->where('delivery_person_id', $person->id)
            ->orderByDesc('settled_at')
            ->paginate(20);

        return view('delivery-person-portal.settlements', [
            'person' => $person,
            'currency' => $currency,
            'currencyService' => $this->currencyService,
            'cashSummary' => $cashSummary,
            'settlements' => $settlements,
        ]);
    }

    public function earnings(): View
    {
        $person = $this->person();
        $currency = strtoupper((string) ($person->currency ?: $this->currencyService->code()));
        $earningsBalanceMinor = $this->cashService->getEarningsBalanceMinor($person, $currency);

        $entries = DB::table('delivery_person_ledger_entries')
            ->leftJoin('bookings', 'bookings.id', '=', 'delivery_person_ledger_entries.booking_id')
            ->where('delivery_person_ledger_entries.delivery_person_id', $person->id)
            ->orderByDesc('delivery_person_ledger_entries.id')
            ->select('delivery_person_ledger_entries.*', 'bookings.reference as booking_reference')
            ->paginate(20);

        return view('delivery-person-portal.earnings', [
            'person' => $person,
            'currency' => $currency,
            'currencyService' => $this->currencyService,
            'earningsBalanceMinor' => $earningsBalanceMinor,
            'entries' => $entries,
        ]);
    }

    public function profile(): View
    {
        $person = $this->person()->load(['store', 'postcodes']);

        return view('delivery-person-portal.profile', [
            'person' => $person,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $person = $this->person();
        $validated = $request->validate([
            'availability_status' => ['required', 'in:available,busy,off_duty'],
        ]);

        $person->update($validated);

        return back()->with('success', 'Availability status updated.');
    }
}
