<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Admin\App\Services\DeliveryLogisticsService;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryAssignmentEvent;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\UkAddress;

class DeliveryAssignmentController extends Controller
{
    public function __construct(
        protected DeliveryLogisticsService $logistics,
        protected CurrencyService $currencyService
    ) {}

    public function index(Request $request)
    {
        $query = DeliveryAssignment::query()
            ->with(['booking', 'deliveryPerson', 'store', 'assignedByAdmin'])
            ->latest('id');

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('assignment_reference', 'like', "%{$search}%")
                    ->orWhereHas('booking', fn ($bq) => $bq->where('reference', 'like', "%{$search}%")->orWhere('customer_name', 'like', "%{$search}%"))
                    ->orWhereHas('deliveryPerson', fn ($dp) => $dp->where('full_name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        if ($personId = $request->input('delivery_person_id')) {
            $query->where('delivery_person_id', $personId);
        }

        if ($type = trim((string) $request->input('assignment_type', ''))) {
            $query->where('assignment_type', $type);
        }

        if ($status = trim((string) $request->input('status', ''))) {
            $query->where('status', $status);
        }

        if ($storeId = $request->input('store_id')) {
            $query->where('store_id', $storeId);
        }

        if ($from = trim((string) $request->input('date_from', ''))) {
            $query->whereDate('assigned_at', '>=', $from);
        }

        if ($to = trim((string) $request->input('date_to', ''))) {
            $query->whereDate('assigned_at', '<=', $to);
        }

        $assignments = $query->paginate(20)->withQueryString();
        $deliveryPersons = DeliveryPerson::orderBy('full_name')->get(['id', 'code', 'full_name', 'work_status']);
        $stores = Store::orderBy('name')->get(['id', 'name']);
        $bookings = Booking::whereNotIn('booking_status', [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED])
            ->latest('id')
            ->limit(100)
            ->get(['id', 'reference', 'customer_name', 'booking_status', 'address_snapshot']);
        $currencyService = $this->currencyService;
        $statusLabels = DeliveryAssignment::STATUS_LABELS;

        return view('admin::delivery-persons.assignments', compact(
            'assignments',
            'deliveryPersons',
            'stores',
            'bookings',
            'currencyService',
            'statusLabels'
        ));
    }

    public function eligiblePersons(Request $request): JsonResponse
    {
        $bookingId = $request->input('booking_id');
        $type = (string) $request->input('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION);
        $storeId = $request->input('store_id');
        $search = trim((string) $request->input('q', ''));

        $booking = $bookingId ? Booking::find($bookingId) : null;
        $store = $storeId ? Store::find($storeId) : null;

        if ($booking) {
            $candidates = collect($this->logistics->findEligibleDeliveryPersonsForBooking($booking, $type, $store));
        } else {
            $candidates = DeliveryPerson::where('work_status', 'active')
                ->with(['store:id,name'])
                ->withCount(['assignments as active_assignments_count' => fn ($q) => $q->whereIn('status', [
                    DeliveryAssignment::STATUS_PENDING,
                    DeliveryAssignment::STATUS_ASSIGNED,
                    DeliveryAssignment::STATUS_ACCEPTED,
                    DeliveryAssignment::STATUS_PICKED_UP,
                    DeliveryAssignment::STATUS_IN_TRANSIT,
                ])])
                ->orderBy('full_name')
                ->get()
                ->map(fn (DeliveryPerson $p) => [
                    'id' => $p->id,
                    'code' => $p->code,
                    'full_name' => $p->full_name,
                    'store_name' => $p->store?->name ?? 'Unassigned',
                    'availability_status' => $p->availability_status,
                    'matched_postcode' => null,
                    'postcode_matched' => false,
                    'active_assignments_count' => $p->active_assignments_count,
                ]);
        }

        if ($search !== '') {
            $lower = strtolower($search);
            $candidates = $candidates->filter(fn ($row) => str_contains(strtolower($row['full_name'] . ' ' . $row['code'] . ' ' . $row['store_name'] . ' ' . ($row['matched_postcode'] ?? '')), $lower));
        }

        $results = $candidates->map(function ($row) {
            $matchTag = $row['postcode_matched'] ? "Match: {$row['matched_postcode']}" : 'No postcode match (Override req.)';
            $avail = ucfirst(str_replace('_', ' ', (string) $row['availability_status']));

            return [
                'id' => $row['id'],
                'text' => "{$row['full_name']} ({$row['code']}) — {$row['store_name']} | {$matchTag} | {$avail} | Active: {$row['active_assignments_count']}",
                'postcode_matched' => $row['postcode_matched'],
                'matched_postcode' => $row['matched_postcode'],
            ];
        })->values();

        return response()->json([
            'results' => $results,
            'has_postcode_match' => $candidates->contains('postcode_matched', true),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'delivery_person_id' => ['required', 'exists:delivery_persons,id'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'assignment_type' => ['required', Rule::in([DeliveryAssignment::TYPE_CUSTOMER_COLLECTION, DeliveryAssignment::TYPE_FINAL_DELIVERY])],
            'override_rate' => ['nullable', 'numeric', 'min:0'],
            'rate_override_reason' => ['nullable', 'string', 'max:255'],
            'coverage_override_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);
        $person = DeliveryPerson::findOrFail($validated['delivery_person_id']);
        $store = ! empty($validated['store_id']) ? Store::find($validated['store_id']) : null;

        $overrideMinor = isset($validated['override_rate']) && $validated['override_rate'] !== ''
            ? $this->currencyService->toMinor($validated['override_rate'], $person->currency)
            : null;

        $assignment = $this->logistics->assignToBooking(
            $booking,
            $person,
            $validated['assignment_type'],
            $store,
            (int) Auth::guard('admin')->id(),
            $overrideMinor,
            $validated['rate_override_reason'] ?? null,
            $validated['coverage_override_reason'] ?? null
        );

        return back()->with('success', "Assignment {$assignment->assignment_reference} created for {$person->full_name}.");
    }

    public function transition(Request $request, DeliveryAssignment $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(DeliveryAssignment::STATUS_LABELS))],
            'proof_of_delivery_notes' => ['nullable', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $this->logistics->transitionAssignment(
            $assignment,
            $validated['status'],
            (int) Auth::guard('admin')->id(),
            $validated
        );

        return back()->with('success', "Assignment {$updated->assignment_reference} updated to {$updated->status_label}.");
    }

    public function recordCod(Request $request, DeliveryAssignment $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'cod_collected' => ['required', 'numeric', 'min:0'],
            'cod_handover_status' => ['required', Rule::in(['pending_handover', 'handed_over', 'reconciled'])],
            'cod_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $collectedMinor = $this->currencyService->toMinor($validated['cod_collected'], $assignment->currency);
        $now = now();

        $assignment->update([
            'cod_collected_minor' => $collectedMinor,
            'cod_handover_status' => $validated['cod_handover_status'],
            'cod_collected_at' => $assignment->cod_collected_at ?? ($collectedMinor > 0 ? $now : null),
            'cod_handed_over_at' => in_array($validated['cod_handover_status'], ['handed_over', 'reconciled'], true) ? ($assignment->cod_handed_over_at ?? $now) : null,
            'cod_notes' => $validated['cod_notes'] ?? $assignment->cod_notes,
        ]);

        DeliveryAssignmentEvent::create([
            'delivery_assignment_id' => $assignment->id,
            'delivery_person_id' => $assignment->delivery_person_id,
            'booking_id' => $assignment->booking_id,
            'event_type' => 'cod_' . $validated['cod_handover_status'],
            'title' => 'COD Reconciliation Updated',
            'description' => 'Collected ' . $this->currencyService->formatMinor($collectedMinor, $assignment->currency, true) . " — Status: {$validated['cod_handover_status']}",
            'actor_admin_id' => Auth::guard('admin')->id(),
            'occurred_at' => $now,
        ]);

        return back()->with('success', 'COD collection and handover status recorded separately from courier earnings.');
    }

    public function activity(Request $request)
    {
        $query = DeliveryAssignment::query()
            ->with(['booking', 'deliveryPerson', 'store', 'events'])
            ->latest('id');

        if ($search = trim((string) $request->input('q', ''))) {
            $query->whereHas('booking', fn ($q) => $q->where('reference', 'like', "%{$search}%"));
        }

        if ($personId = $request->input('delivery_person_id')) {
            $query->where('delivery_person_id', $personId);
        }

        if ($type = trim((string) $request->input('assignment_type', ''))) {
            $query->where('assignment_type', $type);
        }

        if ($status = trim((string) $request->input('status', ''))) {
            $query->where('status', $status);
        }

        if ($storeId = $request->input('store_id')) {
            $query->where('store_id', $storeId);
        }

        if ($postcode = trim((string) $request->input('postcode', ''))) {
            $compact = UkAddress::compactPostcode($postcode);
            $query->where(function ($q) use ($postcode, $compact) {
                $q->where('pickup_postcode', 'like', "%{$postcode}%")
                    ->orWhere('destination_postcode', 'like', "%{$postcode}%")
                    ->orWhere('matched_postcode', 'like', "%{$compact}%");
            });
        }

        if ($from = trim((string) $request->input('date_from', ''))) {
            $query->whereDate('assigned_at', '>=', $from);
        }

        if ($to = trim((string) $request->input('date_to', ''))) {
            $query->whereDate('assigned_at', '<=', $to);
        }

        $activities = $query->paginate(20)->withQueryString();
        $deliveryPersons = DeliveryPerson::withTrashed()->orderBy('full_name')->get(['id', 'code', 'full_name']);
        $stores = Store::orderBy('name')->get(['id', 'name']);
        $currencyService = $this->currencyService;

        return view('admin::delivery-persons.activity', compact(
            'activities',
            'deliveryPersons',
            'stores',
            'currencyService'
        ));
    }
}
