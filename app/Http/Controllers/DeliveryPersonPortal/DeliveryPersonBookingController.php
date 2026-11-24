<?php

namespace App\Http\Controllers\DeliveryPersonPortal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingOperationalEditorService;
use App\Services\CollectionOtpService;
use App\Services\CollectionPaymentService;
use App\Services\CurrencyService;
use App\Services\DeliveryPersonAuthorizationService;
use App\Services\StoreAccountService;
use App\Services\StoreHandoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\ItemCategory;
use RuntimeException;

class DeliveryPersonBookingController extends Controller
{
    public function __construct(
        protected DeliveryPersonAuthorizationService $authService,
        protected CurrencyService $currencyService,
        protected CollectionOtpService $otpService,
        protected CollectionPaymentService $paymentService,
        protected BookingOperationalEditorService $editorService,
        protected StoreHandoverService $handoverService
    ) {}

    protected function person(): DeliveryPerson
    {
        return Auth::guard('delivery_person')->user();
    }

    protected function authorizeBookingContext(Booking $booking): array
    {
        $ctx = $this->authService->resolveContext($this->person(), $booking);
        if (! $ctx['can_view']) {
            abort(403, 'You are not authorized to access this booking.');
        }

        return $ctx;
    }

    public function show(Request $request, Booking $booking): View
    {
        $person = $this->person();
        $ctx = $this->authorizeBookingContext($booking);

        $requestedType = $request->query('type');
        if ($requestedType === \Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY && $ctx['is_final_assignee']) {
            return $this->renderJobView($booking, $person, $ctx, 'final');
        }
        if ($requestedType === \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION && $ctx['is_collection_assignee']) {
            return $this->renderJobView($booking, $person, $ctx, 'collection');
        }

        $mode = (! $ctx['is_collection_assignee'] && $ctx['is_final_assignee']) ? 'final' : 'collection';

        return $this->renderJobView($booking, $person, $ctx, $mode);
    }

    public function showCollectionJob(\Modules\Admin\Models\DeliveryAssignment $assignment): View
    {
        $person = $this->person();
        if (
            (int) $assignment->delivery_person_id !== (int) $person->id
            || $assignment->assignment_type !== \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION
            || in_array($assignment->status, [
                \Modules\Admin\Models\DeliveryAssignment::STATUS_CANCELLED,
                \Modules\Admin\Models\DeliveryAssignment::STATUS_FAILED,
            ], true)
        ) {
            abort(403, 'You are not authorized to access this collection job.');
        }

        $booking = $assignment->booking()->firstOrFail();
        $ctx = $this->authorizeBookingContext($booking);

        return $this->renderJobView($booking, $person, $ctx, 'collection', $assignment);
    }

    public function showFinalDeliveryJob(\Modules\Admin\Models\DeliveryAssignment $assignment): View
    {
        $person = $this->person();
        if (
            (int) $assignment->delivery_person_id !== (int) $person->id
            || $assignment->assignment_type !== \Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY
            || in_array($assignment->status, [
                \Modules\Admin\Models\DeliveryAssignment::STATUS_CANCELLED,
                \Modules\Admin\Models\DeliveryAssignment::STATUS_FAILED,
            ], true)
        ) {
            abort(403, 'You are not authorized to access this final delivery job.');
        }

        $booking = $assignment->booking()->firstOrFail();
        $ctx = $this->authorizeBookingContext($booking);

        return $this->renderJobView($booking, $person, $ctx, 'final', $assignment);
    }

    protected function renderJobView(
        Booking $booking,
        DeliveryPerson $person,
        array $ctx,
        string $mode,
        ?\Modules\Admin\Models\DeliveryAssignment $explicitAssignment = null
    ): View {
        $booking->syncOperationalItemsFromSnapshotIfEmpty($this->currencyService);
        $booking->load([
            'operationalItems.service',
            'operationalItems.category',
            'operationalItems.item',
            'assignedStore',
            'currentStoreAssignment.store',
            'currentStoreHandover.acceptedByStoreUser',
            'currentStoreHandover.rejectedByStoreUser',
            'storeHandovers',
            'latestStorePickupRequest.store',
            'storePickupRequests.store',
            'user',
            'customerPayments' => fn ($q) => $q->orderByDesc('id'),
        ]);

        $pricingSummary = $booking->getOperationalPricingSummary($this->currencyService, false);
        $paymentState = $this->paymentService->getPaymentState($booking);
        $categoryGroups = $this->editorService->buildCategoryGroups($booking, null, null, $person);

        if ($mode === 'final') {
            $job = $explicitAssignment ?? $ctx['final_assignment'];
            $job?->loadMissing(['latestPickupRequest', 'ledgerEntries', 'store']);

            return view('delivery-person-portal.bookings.final-show', [
                'person' => $person,
                'booking' => $booking,
                'job' => $job,
                'ctx' => $ctx,
                'categoryGroups' => $categoryGroups,
                'pricingSummary' => $pricingSummary,
                'paymentState' => $paymentState,
                'currencyService' => $this->currencyService,
            ]);
        }

        $job = $explicitAssignment ?? $ctx['collection_assignment'];
        $job?->loadMissing(['ledgerEntries', 'store']);

        return view('delivery-person-portal.bookings.show', [
            'person' => $person,
            'booking' => $booking,
            'job' => $job,
            'ctx' => $ctx,
            'categoryGroups' => $categoryGroups,
            'pricingSummary' => $pricingSummary,
            'paymentState' => $paymentState,
            'currencyService' => $this->currencyService,
        ]);
    }

    public function edit(Booking $booking): View
    {
        $person = $this->person();
        $ctx = $this->authorizeBookingContext($booking);

        if (! $ctx['can_edit_collection']) {
            abort(403, 'Only an assigned Collection Delivery Person can edit operational items before finalization.');
        }

        $booking->syncOperationalItemsFromSnapshotIfEmpty($this->currencyService);
        $booking->load(['operationalItems.service', 'operationalItems.category', 'operationalItems.item', 'assignedStore']);

        $categories = ItemCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->with(['services' => fn ($q) => $q->where('is_active', true)->with(['unit', 'item'])->orderBy('name')])
            ->get();

        $categoriesServicesMap = $categories->mapWithKeys(function (ItemCategory $category) use ($booking) {
            return [$category->id => $this->editorService->formatCategoryServices($booking, $category, $category->services)];
        });

        $categoryGroups = $this->editorService->buildCategoryGroups($booking, $categories, $categoriesServicesMap, $person);
        $pricingSummary = $booking->getOperationalPricingSummary($this->currencyService, false);

        return view('delivery-person-portal.bookings.edit', [
            'person' => $person,
            'booking' => $booking,
            'ctx' => $ctx,
            'categories' => $categories,
            'categoriesServicesMap' => $categoriesServicesMap,
            'categoryGroups' => $categoryGroups,
            'pricingSummary' => $pricingSummary,
            'currencyService' => $this->currencyService,
        ]);
    }

    public function addCategory(Request $request, Booking $booking): JsonResponse|RedirectResponse
    {
        $person = $this->person();
        $ctx = $this->authorizeBookingContext($booking);

        if (! $ctx['can_edit_collection']) {
            abort(403, 'Only an assigned Collection Delivery Person can add service categories before finalization.');
        }

        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:item_categories,id'],
        ]);

        try {
            $record = $this->editorService->addCategoryAsDeliveryPerson($booking, $person, (int) $validated['category_id']);
        } catch (RuntimeException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->withErrors(['category_id' => $e->getMessage()]);
        }

        $category = ItemCategory::with(['services' => fn ($q) => $q->where('is_active', true)->with(['unit', 'item'])->orderBy('name')])
            ->findOrFail($record->item_category_id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Added category {$category->name}.",
                'category' => [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'origin_type' => $record->origin_type,
                    'origin_badge_label' => 'Added by Delivery Person (You)',
                    'can_remove_category' => true,
                    'services' => $this->editorService->formatCategoryServices($booking, $category, $category->services),
                ],
            ]);
        }

        return back()->with('success', "Added category {$category->name}.");
    }

    public function removeCategory(Request $request, Booking $booking, ItemCategory $category): JsonResponse|RedirectResponse
    {
        $person = $this->person();
        $ctx = $this->authorizeBookingContext($booking);

        if (! $ctx['can_edit_collection']) {
            abort(403, 'Only an assigned Collection Delivery Person can remove eligible categories before finalization.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->editorService->removeCategoryAsDeliveryPerson($booking, $person, (int) $category->id, $validated['reason'] ?? null);
        } catch (RuntimeException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 403)
                : back()->withErrors(['category' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Removed category {$category->name}.",
            ]);
        }

        return back()->with('success', "Removed category {$category->name}.");
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $person = $this->person();
        $ctx = $this->authorizeBookingContext($booking);

        if (! $ctx['can_edit_collection']) {
            abort(403, 'Only an assigned Collection Delivery Person can edit operational items before finalization.');
        }

        // Reject unauthorized fee/store/snapshot/unit-price tampering
        if ($request->hasAny(['assigned_store_id', 'service_fee_minor', 'collection_delivery_fee_minor', 'tip_amount_minor', 'discount_minor', 'services_snapshot', 'actual_unit_price'])) {
            abort(403, 'Delivery Persons cannot modify protected fees, store assignments, unit prices, or customer snapshots.');
        }

        // Reject legacy crafted category deletion payloads if targeting protected categories
        if ($request->hasAny(['remove_category_ids', 'deleted_category_ids'])) {
            $craftedIds = array_merge((array) $request->input('remove_category_ids', []), (array) $request->input('deleted_category_ids', []));
            $removedPayload = collect($craftedIds)->map(fn ($id) => ['category_id' => (int) $id, 'reason' => 'Requested removal'])->all();
        } else {
            $removedPayload = $request->input('removed_categories', []);
        }

        $validated = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:booking_operational_items,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0', 'max:500'],
            'items.*.weight_kg' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'items.*.item_notes' => ['nullable', 'string', 'max:500'],
            'new_items' => ['nullable', 'array'],
            'new_items.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'new_items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'new_items.*.weight_kg' => ['nullable', 'numeric', 'min:0.01', 'max:500'],
            'new_items.*.item_notes' => ['nullable', 'string', 'max:500'],
            'deleted_item_ids' => ['nullable', 'array'],
            'deleted_item_ids.*' => ['integer'],
            'added_category_ids' => ['nullable', 'array'],
            'added_category_ids.*' => ['integer'],
            'removed_categories' => ['nullable', 'array'],
            'removed_categories.*.category_id' => ['required', 'integer'],
            'removed_categories.*.reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (! empty($removedPayload)) {
            $validated['removed_categories'] = $removedPayload;
        }

        try {
            $this->editorService->saveDeliveryPersonBookingEdit($booking, $person, $validated);
        } catch (RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        return redirect()
            ->route('delivery-person.bookings.show', $booking)
            ->with('success', 'Booking items, categories, and totals updated.');
    }

    public function sendOtp(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        $ctx = $this->authorizeBookingContext($booking);
        if (! $ctx['can_manage_otp']) {
            abort(403, 'Only the assigned Collection Delivery Person can send Collection OTP.');
        }

        try {
            $otp = $this->otpService->sendOtp($booking, $this->person());
        } catch (RuntimeException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->withErrors(['otp' => $e->getMessage()]);
        }

        $msg = 'Collection verification code sent to the customer email.';

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg, 'expires_at' => $otp->expires_at->toIso8601String()])
            : back()->with('success', $msg);
    }

    public function verifyOtp(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        $ctx = $this->authorizeBookingContext($booking);
        if (! $ctx['can_manage_otp']) {
            abort(403, 'Only the assigned Collection Delivery Person can verify Collection OTP.');
        }

        $validated = $request->validate([
            'otp_code' => ['required', 'string', 'max:12'],
        ]);

        try {
            $this->otpService->verifyOtp($booking, $this->person(), $validated['otp_code']);
        } catch (RuntimeException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->withErrors(['otp_code' => $e->getMessage()]);
        }

        $msg = 'Collection OTP verified successfully.';

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $msg])
            : back()->with('success', $msg);
    }

    public function recordCash(Request $request, Booking $booking): RedirectResponse
    {
        $ctx = $this->authorizeBookingContext($booking);
        if (! $ctx['can_collect_payment']) {
            abort(403, 'Only the assigned Collection Delivery Person can record customer payment.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:150'],
        ]);

        try {
            $this->paymentService->recordCashInHand(
                $booking,
                $this->person(),
                StoreAccountService::decimalToMinor($validated['amount']),
                $validated['notes'] ?? null,
                $validated['idempotency_key'] ?? null
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['cash_amount' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Customer Cash in Hand payment recorded.');
    }

    public function recordTransfer(Request $request, Booking $booking): RedirectResponse
    {
        $ctx = $this->authorizeBookingContext($booking);
        if (! $ctx['can_collect_payment']) {
            abort(403, 'Only the assigned Collection Delivery Person can record customer transfer.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'external_reference' => ['required', 'string', 'min:3', 'max:190'],
            'receiving_account' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:150'],
        ]);

        try {
            $this->paymentService->recordOnlineTransferClaim(
                $booking,
                $this->person(),
                StoreAccountService::decimalToMinor($validated['amount']),
                $validated['external_reference'],
                $validated['receiving_account'] ?? 'Business Bank Account',
                $validated['notes'] ?? null,
                $validated['idempotency_key'] ?? null
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['transfer_amount' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Online Transfer recorded as Awaiting Verification by admin.');
    }

    public function initiateStripe(Booking $booking): JsonResponse
    {
        $this->authorizeBookingContext($booking);

        return response()->json([
            'success' => false,
            'message' => 'Online payments must be completed by the customer on their own account.',
        ], 403);
    }

    public function initiatePayPal(Booking $booking): JsonResponse
    {
        $this->authorizeBookingContext($booking);

        return response()->json([
            'success' => false,
            'message' => 'Online payments must be completed by the customer on their own account.',
        ], 403);
    }

    public function paymentStatus(Booking $booking): JsonResponse
    {
        $this->authorizeBookingContext($booking);
        $booking->refresh();
        $state = $this->paymentService->getPaymentState($booking);

        return response()->json([
            'success' => true,
            'payment_status' => $booking->payment_status,
            'payment_status_label' => $booking->getFinancialPaymentStatusLabel(),
            'is_pricing_complete' => $state['is_pricing_complete'],
            'pricing_status_label' => $state['pricing']['pricing_status_label'] ?? ($state['is_pricing_complete'] ? 'Finalized' : 'Pending'),
            'total_minor' => $state['total_minor'],
            'confirmed_paid_minor' => $state['confirmed_paid_minor'],
            'outstanding_minor' => $state['outstanding_minor'],
            'is_payment_satisfied' => $state['is_payment_satisfied'],
            'formatted_total' => $this->currencyService->formatMinor($state['total_minor'], $state['currency']),
            'formatted_paid' => $this->currencyService->formatMinor($state['confirmed_paid_minor'], $state['currency']),
            'formatted_outstanding' => $this->currencyService->formatMinor($state['outstanding_minor'], $state['currency']),
        ]);
    }

    public function finalize(Booking $booking): RedirectResponse
    {
        $ctx = $this->authorizeBookingContext($booking);
        if (! $ctx['can_finalize_collection']) {
            abort(403, 'Only the assigned Collection Delivery Person can finalize collection.');
        }

        try {
            $this->paymentService->finalizeCollectionBooking($booking, $this->person());
        } catch (RuntimeException $e) {
            return back()->withErrors(['finalize' => $e->getMessage()]);
        }

        return redirect()
            ->route('delivery-person.bookings.show', $booking)
            ->with('success', 'Booking finalized successfully.');
    }

    public function markPickedUp(Booking $booking): RedirectResponse
    {
        $ctx = $this->authorizeBookingContext($booking);
        if (! $ctx['is_collection_assignee']) {
            abort(403, 'Only the assigned Collection Delivery Person can mark Delivery Man Picked Up done.');
        }

        try {
            $this->paymentService->markCollectionPickedUp($booking, $this->person());
        } catch (RuntimeException $e) {
            return back()->withErrors(['pickup' => $e->getMessage()]);
        }

        return redirect()
            ->route('delivery-person.bookings.show', $booking)
            ->with('success', 'Delivery Man Picked Up marked done. Booking advanced to Store Received.');
    }

    public function requestStoreHandover(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        $ctx = $this->authorizeBookingContext($booking);
        if (! $ctx['is_collection_assignee']) {
            abort(403, 'Only the assigned Collection Delivery Person can request store acceptance.');
        }

        $validated = $request->validate([
            'request_note' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:150'],
        ]);

        try {
            $handover = $this->handoverService->requestHandover(
                $booking,
                $this->person(),
                $validated['request_note'] ?? null,
                $validated['idempotency_key'] ?? null
            );
        } catch (RuntimeException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->withErrors(['handover' => $e->getMessage()]);
        }

        $msg = 'Store handover acceptance requested. Waiting for store confirmation.';

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => $msg,
                'handover' => [
                    'id' => $handover->id,
                    'request_reference' => $handover->request_reference,
                    'status' => $handover->status,
                    'requested_at' => $handover->requested_at?->format('d M Y, H:i'),
                ],
            ])
            : back()->with('success', $msg);
    }

    public function acceptPickupRequest(Request $request, \App\Models\BookingStorePickupRequest $pickupRequest): RedirectResponse|JsonResponse
    {
        $person = $this->person();
        if ((int) $pickupRequest->delivery_person_id !== (int) $person->id) {
            abort(403, 'You are not authorized to accept this pickup request.');
        }

        try {
            $accepted = app(\App\Services\StorePickupDeliveryService::class)->acceptPickupRequest(
                $pickupRequest,
                $person,
                $request->ip()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to accept pickup request.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg, 'errors' => $e->errors()], 422)
                : back()->withErrors($e->errors());
        }

        $msg = "Accepted store pickup request {$accepted->request_reference}. Proceed to store to collect parcel.";

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => $msg,
                'pickup_request' => [
                    'id' => $accepted->id,
                    'status' => $accepted->status,
                    'status_label' => $accepted->getStatusLabel(),
                    'accepted_at' => $accepted->accepted_at?->format('d M Y, H:i'),
                ],
            ])
            : back()->with('success', $msg);
    }

    public function declinePickupRequest(Request $request, \App\Models\BookingStorePickupRequest $pickupRequest): RedirectResponse|JsonResponse
    {
        $person = $this->person();
        if ((int) $pickupRequest->delivery_person_id !== (int) $person->id) {
            abort(403, 'You are not authorized to decline this pickup request.');
        }

        $validated = $request->validate([
            'decline_reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $declined = app(\App\Services\StorePickupDeliveryService::class)->declinePickupRequest(
                $pickupRequest,
                $person,
                $validated['decline_reason'],
                $request->ip()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to decline pickup request.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg, 'errors' => $e->errors()], 422)
                : back()->withErrors($e->errors());
        }

        $msg = "Declined store pickup request {$declined->request_reference}.";

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => $msg,
                'pickup_request' => [
                    'id' => $declined->id,
                    'status' => $declined->status,
                    'status_label' => $declined->getStatusLabel(),
                    'declined_at' => $declined->declined_at?->format('d M Y, H:i'),
                ],
            ])
            : back()->with('success', $msg);
    }

    public function confirmStorePickup(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        $person = $this->person();
        $ctx = $this->authorizeBookingContext($booking);
        if (! $ctx['is_final_assignee']) {
            abort(403, 'Only the assigned Final Delivery Person can confirm physical store pickup.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $updated = app(\App\Services\StorePickupDeliveryService::class)->confirmCollectedFromStore(
                $booking,
                $person,
                $validated['notes'] ?? null,
                $request->ip()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to confirm store pickup.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg, 'errors' => $e->errors()], 422)
                : back()->withErrors($e->errors());
        }

        $msg = "Confirmed collection from store for {$updated->reference}. Booking is now On Delivery.";

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => $msg,
                'booking_status' => $updated->booking_status,
                'booking_status_label' => $updated->getStatusLabel(),
            ])
            : back()->with('success', $msg);
    }

    public function confirmDelivered(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        $person = $this->person();
        $ctx = $this->authorizeBookingContext($booking);
        if (! $ctx['is_final_assignee']) {
            abort(403, 'Only the assigned Final Delivery Person can confirm customer delivery.');
        }

        $validated = $request->validate([
            'proof_of_delivery_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $completed = app(\App\Services\StorePickupDeliveryService::class)->confirmDeliveredToCustomer(
                $booking,
                $person,
                $validated['proof_of_delivery_notes'] ?? null,
                $request->ip()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?: 'Unable to complete final delivery.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg, 'errors' => $e->errors()], 422)
                : back()->withErrors($e->errors());
        }

        $msg = "Delivery confirmed for {$completed->reference}. Booking is now Completed and earnings have been credited.";

        return $request->expectsJson()
            ? response()->json([
                'success' => true,
                'message' => $msg,
                'booking_status' => $completed->booking_status,
                'booking_status_label' => $completed->getStatusLabel(),
            ])
            : back()->with('success', $msg);
    }
}
