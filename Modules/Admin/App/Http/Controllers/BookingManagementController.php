<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\BookingNotificationMail;
use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingOperationalItem;
use App\Services\BookingFinancialSettlementService;
use App\Services\BookingInvoiceService;
use App\Services\BookingProfitService;
use App\Services\CurrencyService;
use App\Services\StoreAccountService;
use App\Services\StoreEarningsService;
use RuntimeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Modules\Admin\App\Models\BookingFeeSetting;
use Modules\Admin\App\Models\BookingStoreAssignment;
use Modules\Admin\App\Services\DeliveryLogisticsService;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryAssignmentEvent;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\UkAddress;

class BookingManagementController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    public function index(Request $request)
    {
        $query = Booking::query()->orderByDesc('id');

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($bookingDate = trim((string) $request->input('booking_date', ''))) {
            $query->whereDate('created_at', $bookingDate);
        }

        if ($collectionDate = trim((string) $request->input('collection_date', ''))) {
            $query->where(function ($q) use ($collectionDate) {
                $q->where('schedule_snapshot->collection_date', $collectionDate)
                    ->orWhere('schedule_snapshot->pickup_date', $collectionDate);
            });
        }

        if ($deliveryDate = trim((string) $request->input('delivery_date', ''))) {
            $query->where('schedule_snapshot->delivery_date', $deliveryDate);
        }

        if ($bookingStatus = trim((string) $request->input('booking_status', ''))) {
            $query->where('booking_status', $bookingStatus);
        }

        if ($paymentStatus = trim((string) $request->input('payment_status', ''))) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($paymentGateway = trim((string) $request->input('payment_gateway', ''))) {
            $query->where('payment_gateway', $paymentGateway);
        }

        $bookings = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Booking::count(),
            'active' => Booking::whereNotIn('booking_status', [Booking::STATUS_COMPLETED, Booking::STATUS_CANCELLED])->count(),
            'completed' => Booking::where('booking_status', Booking::STATUS_COMPLETED)->count(),
            'cancelled' => Booking::where('booking_status', Booking::STATUS_CANCELLED)->count(),
        ];

        $statusLabels = Booking::STATUS_LABELS;
        $currencyService = $this->currencyService;

        return view('admin::bookings.index', compact('bookings', 'stats', 'statusLabels', 'currencyService'));
    }

    public function show(Booking $booking)
    {
        if (
            $booking->booking_status === Booking::STATUS_STORE_COMPLETE
            && in_array(Booking::STATUS_STORE_COMPLETE, $booking->getCompletedOperationalStatuses(), true)
        ) {
            $booking->booking_status = Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP;
            $booking->version = ((int) $booking->version) + 1;
            $booking->save();
        }

        $booking->syncOperationalItemsFromSnapshotIfEmpty($this->currencyService);
        $booking->load([
            'operationalItems.service',
            'operationalItems.category',
            'operationalItems.item',
            'auditLogs',
            'user',
            'assignedStore',
            'currentStoreAssignment.assignedByAdmin',
            'storeAssignments.assignedByAdmin',
            'currentCollectionAssignment.deliveryPerson.postcodes',
            'currentFinalDeliveryAssignment.deliveryPerson.postcodes',
            'deliveryAssignments.deliveryPerson',
            'deliveryAssignments.store',
            'currentStoreEarning',
            'currentFinancialSettlement',
            'currentStoreHandover.acceptedByStoreUser',
            'currentStoreHandover.rejectedByStoreUser',
            'storeHandovers.store',
            'storeHandovers.collectionDeliveryPerson',
            'storeHandovers.acceptedByStoreUser',
            'storeHandovers.rejectedByStoreUser',
            'latestStorePickupRequest.deliveryPerson',
            'storePickupRequests.deliveryPerson',
            'storePickupRequests.requestedByStoreUser',
            'activeTagAssignments.tag',
            'activeTagAssignments.verifiedByStoreUser',
            'activeTagAssignments.assignedByDeliveryPerson',
            'tagAssignments.tag',
        ]);

        $currencyService = $this->currencyService;
        $statusLabels = Booking::STATUS_LABELS;
        $allowedNextStatuses = Booking::ALLOWED_STATUS_TRANSITIONS[$booking->booking_status] ?? [];
        $pricingSummary = $booking->getOperationalPricingSummary($this->currencyService);
        $categoryGroups = $this->buildCategoryGroups($booking);
        $operationalStatuses = Booking::OPERATIONAL_STATUSES;
        $completedOperationalStatuses = $booking->getCompletedOperationalStatuses();
        $stageProgress = $booking->getOperationalStageProgress();

        $rawPostcode = trim((string) (($booking->address_snapshot['postcode'] ?? '') ?: ''));
        $customerPostcode = $rawPostcode !== '' ? (UkAddress::normalizePostcode($rawPostcode) ?? strtoupper($rawPostcode)) : null;
        $hasValidStoreAssignment = $booking->hasValidActiveStoreAssignment();
        $collectionAssignment = $booking->currentCollectionAssignment;
        $finalDeliveryAssignment = $booking->currentFinalDeliveryAssignment;
        $hasValidCollectionCourier = $booking->hasValidCollectionCourierAssignment();
        $hasValidFinalDeliveryCourier = $booking->hasValidFinalDeliveryCourierAssignment();
        $isInvoiceEligible = $booking->isInvoiceEligible($this->currencyService);
        $financialAllocation = app(BookingFinancialSettlementService::class)->buildPreview($booking);
        $profitSummary = app(BookingProfitService::class)->calculateForBooking($booking);

        return view('admin::bookings.show', compact(
            'booking',
            'currencyService',
            'statusLabels',
            'allowedNextStatuses',
            'pricingSummary',
            'categoryGroups',
            'operationalStatuses',
            'completedOperationalStatuses',
            'stageProgress',
            'customerPostcode',
            'hasValidStoreAssignment',
            'collectionAssignment',
            'finalDeliveryAssignment',
            'hasValidCollectionCourier',
            'hasValidFinalDeliveryCourier',
            'isInvoiceEligible',
            'financialAllocation',
            'profitSummary'
        ));
    }

    public function edit(Booking $booking)
    {
        if (in_array($booking->booking_status, [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED], true)) {
            return redirect()
                ->route('admin.bookings.show', $booking)
                ->with('error', 'Cancelled or completed bookings cannot be edited.');
        }

        $booking->syncOperationalItemsFromSnapshotIfEmpty($this->currencyService);
        $booking->load(['operationalItems.service', 'operationalItems.category', 'operationalItems.item', 'auditLogs']);

        $categories = ItemCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->with([
                'services' => fn ($q) => $q->where('is_active', true)->with(['unit', 'item'])->orderBy('name'),
                'items' => fn ($q) => $q->where('is_active', true)->orderBy('name'),
            ])
            ->get();

        $categoriesServicesMap = $categories->mapWithKeys(function (ItemCategory $category) use ($booking) {
            return [$category->id => $this->formatCategoryServices($booking, $category, $category->services)];
        });

        $services = Service::query()
            ->where('is_active', true)
            ->with(['category', 'unit'])
            ->orderBy('name')
            ->get();

        $items = Item::query()
            ->where('is_active', true)
            ->with('category')
            ->orderBy('name')
            ->get();

        $feeSettings = BookingFeeSetting::allowed()->get()->keyBy('fee_code');
        $currencyService = $this->currencyService;
        $statusLabels = Booking::STATUS_LABELS;
        $allowedNextStatuses = Booking::ALLOWED_STATUS_TRANSITIONS[$booking->booking_status] ?? [];
        $categoryGroups = $this->buildCategoryGroups($booking, $categories, $categoriesServicesMap);
        $removedCategories = collect($booking->removed_operational_categories ?? [])->values();
        $pendingReasons = $booking->getOperationalPendingReasons($booking->operationalItems);

        $servicesSubtotalMinor = (int) $booking->operationalItems->sum('line_total_minor');
        $defaultServiceFeeMajor = $currencyService->fromMinor($this->computeDefaultServiceFeeMinor($booking, $servicesSubtotalMinor), $booking->currency);
        $defaultDeliveryFeeMajor = $currencyService->fromMinor($this->computeDefaultDeliveryFeeMinor($booking), $booking->currency);
        $tipMajor = $currencyService->fromMinor((int) $booking->tip_amount_minor, $booking->currency);

        return view('admin::bookings.edit', compact(
            'booking',
            'categories',
            'categoriesServicesMap',
            'categoryGroups',
            'removedCategories',
            'pendingReasons',
            'defaultServiceFeeMajor',
            'defaultDeliveryFeeMajor',
            'tipMajor',
            'services',
            'items',
            'feeSettings',
            'currencyService',
            'statusLabels',
            'allowedNextStatuses'
        ));
    }

    protected function buildCategoryGroups(Booking $booking, $categories = null, $categoriesServicesMap = null): array
    {
        if ($categories === null) {
            $categories = ItemCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->with(['services' => fn ($q) => $q->where('is_active', true)->with(['unit', 'item'])->orderBy('name')])
                ->get();
        }

        if ($categoriesServicesMap === null) {
            $categoriesServicesMap = $categories->mapWithKeys(function (ItemCategory $category) use ($booking) {
                return [$category->id => $this->formatCategoryServices($booking, $category, $category->services)];
            });
        }

        $categoriesById = $categories->keyBy('id');
        $operationalItems = $booking->operationalItems()->get();
        $removedCatIds = $booking->getRemovedOperationalCategoryIds();
        $groups = [];

        foreach ($booking->services_snapshot ?? [] as $snap) {
            $catId = isset($snap['category_id']) ? (int) $snap['category_id'] : null;
            if ($catId && in_array($catId, $removedCatIds, true)) {
                continue;
            }
            $catModel = $catId ? $categoriesById->get($catId) : null;
            $catName = (string) ($catModel?->name ?? $snap['name'] ?? 'Service Category');
            $key = $catId ? 'cat_' . $catId : 'snap_' . md5(strtolower($catName));

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'category_id' => $catId,
                    'category_name' => $catName,
                    'source' => 'customer',
                    'selected_option_name' => $snap['option_name'] ?? null,
                    'is_wash_fold' => ! empty($snap['is_wash_fold']),
                    'services' => $catId ? ($categoriesServicesMap[$catId] ?? []) : [],
                    'items' => collect(),
                ];
            }
        }

        foreach ($operationalItems as $opItem) {
            $catId = $opItem->item_category_id ? (int) $opItem->item_category_id : null;
            if ($catId && in_array($catId, $removedCatIds, true)) {
                continue;
            }
            $catModel = $catId ? $categoriesById->get($catId) : null;
            $catName = (string) ($opItem->category_name ?: ($catModel?->name ?? 'Additional Service'));
            $key = $catId ? 'cat_' . $catId : 'snap_' . md5(strtolower($catName));

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'category_id' => $catId,
                    'category_name' => $catName,
                    'source' => 'admin',
                    'selected_option_name' => null,
                    'is_wash_fold' => str_contains(strtolower($catName), 'wash & fold'),
                    'services' => $catId ? ($categoriesServicesMap[$catId] ?? []) : [],
                    'items' => collect(),
                ];
            }

            $groups[$key]['items']->push($opItem);
        }

        return array_values($groups);
    }

    public function categoryServices(Booking $booking, ItemCategory $category): JsonResponse
    {
        $services = Service::query()
            ->where('item_category_id', $category->id)
            ->where('is_active', true)
            ->with(['unit', 'item'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
            ],
            'services' => $this->formatCategoryServices($booking, $category, $services),
        ]);
    }

    protected function formatCategoryServices(Booking $booking, ItemCategory $category, $services): array
    {
        return $services->map(function (Service $service) use ($booking, $category) {
            $pricingMode = $this->resolvePricingMode($service);
            $unitName = $service->unit?->name ?? ($pricingMode === 'weight' ? 'Per Kg' : 'Item');
            $priceMajor = (float) ($service->price ?? 0);

            return [
                'id' => $service->id,
                'category_id' => $category->id,
                'category_name' => $category->name,
                'name' => $service->name,
                'item_id' => $service->item_id,
                'item_name' => $service->item?->name,
                'price' => $priceMajor,
                'formatted_price' => $this->currencyService->formatMoney($priceMajor, $booking->currency),
                'pricing_mode' => $pricingMode,
                'unit_name' => $unitName,
                'unit_short' => $service->unit?->short_name ?? ($pricingMode === 'weight' ? 'kg' : 'item'),
            ];
        })->values()->all();
    }

    public function addService(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        if ($resp = $this->ensureEditable($booking)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Cancelled or completed bookings cannot be modified.'], 422);
            }

            return $resp;
        }

        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.01', 'max:9999'],
            'adjustment_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $service = Service::with(['category', 'unit', 'item'])->findOrFail($validated['service_id']);
        $itemId = ! empty($validated['item_id']) ? (int) $validated['item_id'] : $service->item_id;
        $item = $itemId ? Item::find($itemId) : null;

        if ($item && (int) $item->item_category_id !== (int) $service->item_category_id) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Selected item does not belong to the chosen service category.'], 422);
            }

            return back()->withErrors(['item_id' => 'Selected item does not belong to the chosen service category.']);
        }

        $pricingMode = $this->resolvePricingMode($service);
        $catalogPriceMinor = $this->currencyService->toMinor((float) $service->price, $booking->currency);
        $isWashFold = $this->isWashFoldService($service);

        if (! $isWashFold && $booking->operationalItems()->where('service_id', $service->id)->exists()) {
            $msg = 'This service has already been added.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg, 'errors' => ['service_id' => [$msg]]], 422);
            }

            return back()->withErrors(['service_id' => $msg]);
        }

        if ($isWashFold) {
            $existingWf = $booking->operationalItems()
                ->where(function ($q) use ($service) {
                    $q->where('item_category_id', $service->item_category_id)
                        ->orWhere('service_name', 'like', '%Wash & Fold%');
                })
                ->first();

            if ($existingWf && (int) $existingWf->service_id !== (int) $service->id && trim((string) ($validated['adjustment_reason'] ?? '')) === '') {
                $msg = 'Changing the Wash & Fold option requires an adjustment reason.';
                if ($request->expectsJson()) {
                    return response()->json(['message' => $msg, 'errors' => ['adjustment_reason' => [$msg]]], 422);
                }

                return back()->withErrors(['adjustment_reason' => $msg]);
            }
        }

        DB::transaction(function () use ($request, $booking, $service, $item, $pricingMode, $catalogPriceMinor, $validated, $isWashFold) {
            $booking->syncOperationalItemsFromSnapshotIfEmpty($this->currencyService);

            if ($isWashFold) {
                $existingWf = $booking->operationalItems()
                    ->where(function ($q) use ($service) {
                        $q->where('item_category_id', $service->item_category_id)
                            ->orWhere('service_name', 'like', '%Wash & Fold%');
                    })
                    ->first();

                $weightKg = isset($validated['weight_kg']) ? round((float) $validated['weight_kg'], 3) : null;

                if ($existingWf) {
                    $previousName = $existingWf->service_name;
                    $isSwitch = (int) $existingWf->service_id !== (int) $service->id;

                    $existingWf->item_category_id = $service->item_category_id;
                    $existingWf->service_id = $service->id;
                    $existingWf->service_name = $service->name;
                    $existingWf->category_name = $service->category?->name ?? 'Wash & Fold';
                    $existingWf->pricing_mode = 'weight';
                    $existingWf->unit_name = $service->unit?->name ?? 'Per Kg';
                    $existingWf->quantity = 1;
                    if ($weightKg !== null) {
                        $existingWf->weight_kg = $weightKg;
                    }
                    $existingWf->catalog_unit_price_minor = $catalogPriceMinor;
                    $existingWf->actual_unit_price_minor = $catalogPriceMinor;
                    $existingWf->is_price_overridden = false;
                    $existingWf->adjustment_reason = trim((string) ($validated['adjustment_reason'] ?? '')) ?: $existingWf->adjustment_reason;
                    $existingWf->adjusted_by_admin_id = $this->adminId();
                    $existingWf->adjusted_at = now();
                    $existingWf->line_total_minor = $existingWf->computeLineTotalMinor();
                    $existingWf->save();

                    $booking->version = ((int) $booking->version) + 1;
                    $booking->recalculateOperationalTotals($this->currencyService);

                    $this->logAudit(
                        $request,
                        $booking,
                        $isSwitch ? 'wash_fold_option_changed' : 'wash_fold_updated',
                        $isSwitch
                            ? "Changed Wash & Fold option from {$previousName} to {$service->name}"
                            : "Updated Wash & Fold ({$service->name})",
                        [
                            'previous_service' => $previousName,
                            'service_id' => $service->id,
                            'service_name' => $service->name,
                            'weight_kg' => $existingWf->weight_kg,
                            'reason' => $validated['adjustment_reason'] ?? null,
                        ]
                    );

                    return;
                }
            }

            $qty = $pricingMode === 'weight' ? 1 : max(1, (int) ($validated['quantity'] ?? 1));
            $weightKg = $pricingMode === 'weight' && isset($validated['weight_kg']) ? round((float) $validated['weight_kg'], 3) : null;

            $line = new BookingOperationalItem([
                'booking_id' => $booking->id,
                'item_category_id' => $service->item_category_id,
                'service_id' => $service->id,
                'item_id' => $item?->id,
                'service_name' => $service->name,
                'category_name' => $service->category?->name ?? 'Service',
                'item_name' => $item?->name,
                'pricing_mode' => $pricingMode,
                'unit_name' => $service->unit?->name ?? ($pricingMode === 'weight' ? 'Per Kg' : 'Item'),
                'quantity' => $qty,
                'weight_kg' => $weightKg,
                'catalog_unit_price_minor' => $catalogPriceMinor,
                'actual_unit_price_minor' => $catalogPriceMinor,
                'is_price_overridden' => false,
                'adjustment_reason' => trim((string) ($validated['adjustment_reason'] ?? '')) ?: null,
                'adjusted_by_admin_id' => $this->adminId(),
                'adjusted_at' => now(),
            ]);
            $line->line_total_minor = $line->computeLineTotalMinor();
            $line->save();

            $booking->version = ((int) $booking->version) + 1;
            $booking->recalculateOperationalTotals($this->currencyService);

            $this->logAudit($request, $booking, 'service_added', 'Added operational service: ' . $service->name, [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'item_id' => $item?->id,
                'item_name' => $item?->name,
                'reason' => $validated['adjustment_reason'] ?? null,
            ]);
        });

        if ($request->expectsJson()) {
            $booking->refresh()->load('operationalItems');
            $categoryGroups = $this->buildCategoryGroups($booking);

            return response()->json([
                'success' => true,
                'message' => 'Service added to booking operational items.',
                'version' => $booking->version,
                'final_amount_formatted' => $booking->final_amount_minor !== null
                    ? $this->currencyService->formatMinor((int) $booking->final_amount_minor, $booking->currency)
                    : 'Pending Weight / Qty',
                'groups_html' => view('admin::bookings.partials.category-groups', [
                    'booking' => $booking,
                    'categoryGroups' => $categoryGroups,
                    'currencyService' => $this->currencyService,
                    'decimals' => $this->currencyService->decimals($booking->currency),
                    'currencyCode' => strtoupper($booking->currency ?: $this->currencyService->code()),
                    'canOverridePrices' => auth('admin')->user()?->can('override-booking-prices', 'admin') ?? false,
                ])->render(),
            ]);
        }

        return redirect()
            ->route('admin.bookings.edit', $booking)
            ->with('success', 'Service added to booking operational items.');
    }

    public function addItem(Request $request, Booking $booking): RedirectResponse
    {
        if ($resp = $this->ensureEditable($booking)) {
            return $resp;
        }

        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'adjustment_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $item = Item::with('category')->findOrFail($validated['item_id']);
        $service = Service::with(['category', 'unit'])->findOrFail($validated['service_id']);

        if ((int) $item->item_category_id !== (int) $service->item_category_id) {
            return back()->withErrors(['item_id' => 'Selected item does not belong to the chosen service category.']);
        }

        $catalogPriceMinor = $this->currencyService->toMinor((float) $service->price, $booking->currency);

        DB::transaction(function () use ($request, $booking, $item, $service, $catalogPriceMinor, $validated) {
            $booking->syncOperationalItemsFromSnapshotIfEmpty($this->currencyService);

            $existing = $booking->operationalItems()
                ->where('service_id', $service->id)
                ->where('item_id', $item->id)
                ->where('pricing_mode', 'item')
                ->first();

            if ($existing) {
                $existing->quantity += (int) $validated['quantity'];
                $existing->line_total_minor = $existing->computeLineTotalMinor();
                $existing->adjusted_by_admin_id = $this->adminId();
                $existing->adjusted_at = now();
                $existing->save();
            } else {
                $line = new BookingOperationalItem([
                    'booking_id' => $booking->id,
                    'item_category_id' => $service->item_category_id,
                    'service_id' => $service->id,
                    'item_id' => $item->id,
                    'service_name' => $service->name,
                    'category_name' => $service->category?->name ?? $item->category?->name ?? 'Service',
                    'item_name' => $item->name,
                    'pricing_mode' => 'item',
                    'unit_name' => $service->unit?->name ?? 'item',
                    'quantity' => (int) $validated['quantity'],
                    'weight_kg' => null,
                    'catalog_unit_price_minor' => $catalogPriceMinor,
                    'actual_unit_price_minor' => $catalogPriceMinor,
                    'is_price_overridden' => false,
                    'adjustment_reason' => trim((string) ($validated['adjustment_reason'] ?? '')) ?: null,
                    'adjusted_by_admin_id' => $this->adminId(),
                    'adjusted_at' => now(),
                ]);
                $line->line_total_minor = $line->computeLineTotalMinor();
                $line->save();
            }

            $booking->version = ((int) $booking->version) + 1;
            $booking->recalculateOperationalTotals($this->currencyService);

            $this->logAudit($request, $booking, 'item_added', "Added {$validated['quantity']} x {$item->name} ({$service->name})", [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'service_id' => $service->id,
                'service_name' => $service->name,
                'quantity' => (int) $validated['quantity'],
                'reason' => $validated['adjustment_reason'] ?? null,
            ]);
        });

        return redirect()
            ->route('admin.bookings.edit', $booking)
            ->with('success', 'Item added to booking operational items.');
    }

    public function removeItem(Request $request, Booking $booking, BookingOperationalItem $item): RedirectResponse
    {
        if ($resp = $this->ensureEditable($booking)) {
            return $resp;
        }

        if ((int) $item->booking_id !== (int) $booking->id) {
            abort(404);
        }

        $validated = $request->validate([
            'adjustment_reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        if (in_array($booking->payment_status, ['paid', 'refunded'], true) && ! $request->boolean('confirm_paid_adjustment')) {
            return back()->withErrors([
                'adjustment_reason' => 'This booking is already paid or refunded. Confirm paid adjustment before removing items.',
            ]);
        }

        DB::transaction(function () use ($request, $booking, $item, $validated) {
            $snapshot = [
                'id' => $item->id,
                'service_name' => $item->service_name,
                'item_name' => $item->item_name,
                'quantity' => $item->quantity,
                'weight_kg' => $item->weight_kg,
                'line_total_minor' => $item->line_total_minor,
                'reason' => trim($validated['adjustment_reason']),
            ];

            $item->delete();
            $booking->version = ((int) $booking->version) + 1;
            $booking->recalculateOperationalTotals($this->currencyService);

            $this->logAudit($request, $booking, 'item_removed', 'Removed operational item: ' . $snapshot['service_name'], $snapshot);
        });

        return redirect()
            ->route('admin.bookings.edit', $booking)
            ->with('success', 'Operational item removed and totals recalculated.');
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        if ($resp = $this->ensureEditable($booking)) {
            return $resp;
        }

        if ($request->filled('version') && (int) $request->input('version') !== (int) $booking->version) {
            return back()->withErrors([
                'version' => 'This booking was modified by another admin while you were editing. Please reload and review the latest values.',
            ])->withInput();
        }

        $validated = $request->validate([
            'version' => ['nullable', 'integer'],
            'items' => ['nullable', 'array'],
            'items.*.quantity' => ['nullable', 'integer', 'min:0', 'max:999'],
            'items.*.weight_kg' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'items.*.actual_unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'items.*.adjustment_reason' => ['nullable', 'string', 'max:500'],
            'new_items' => ['nullable', 'array'],
            'new_items.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'new_items.*.category_id' => ['nullable', 'integer', 'exists:item_categories,id'],
            'new_items.*.item_id' => ['nullable', 'integer', 'exists:items,id'],
            'new_items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'new_items.*.weight_kg' => ['nullable', 'numeric', 'min:0.01', 'max:9999'],
            'new_items.*.actual_unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'new_items.*.adjustment_reason' => ['nullable', 'string', 'max:500'],
            'deleted_item_ids' => ['nullable', 'array'],
            'deleted_item_ids.*' => ['integer'],
            'removed_categories' => ['nullable', 'array'],
            'removed_categories.*.category_id' => ['required', 'integer'],
            'removed_categories.*.reason' => ['required', 'string', 'min:3', 'max:500'],
            'restored_category_ids' => ['nullable', 'array'],
            'restored_category_ids.*' => ['integer'],
            'service_fee_override_mode' => ['required', Rule::in(['default', 'custom', 'waived'])],
            'service_fee_custom' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'service_fee_override_reason' => ['nullable', 'string', 'max:500'],
            'delivery_fee_override_mode' => ['required', Rule::in(['default', 'custom', 'free'])],
            'delivery_fee_custom' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'delivery_fee_override_reason' => ['nullable', 'string', 'max:500'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'tax' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'operational_notes' => ['nullable', 'string', 'max:2000'],
            'booking_status' => ['nullable', 'string', Rule::in(array_keys(Booking::STATUS_LABELS))],
        ]);

        $admin = Auth::guard('admin')->user();
        $canOverridePrices = $admin && $admin->can('override-booking-prices', 'admin');

        $booking->syncOperationalItemsFromSnapshotIfEmpty($this->currencyService);
        $booking->load('operationalItems');

        $errors = [];
        $itemChanges = [];
        $deletedIds = collect($validated['deleted_item_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();

        foreach ($booking->operationalItems as $line) {
            if (in_array((int) $line->id, $deletedIds, true)) {
                continue;
            }
            $input = $validated['items'][$line->id] ?? null;
            if (! is_array($input)) {
                continue;
            }

            if (array_key_exists('actual_unit_price', $input) && $input['actual_unit_price'] !== null && $input['actual_unit_price'] !== '') {
                $submittedMinor = $this->currencyService->toMinor((float) $input['actual_unit_price'], $booking->currency);
                if ($submittedMinor !== (int) $line->actual_unit_price_minor) {
                    if (! $canOverridePrices) {
                        $errors["items.{$line->id}.actual_unit_price"] = 'You do not have permission to override unit prices.';
                    } elseif (trim((string) ($input['adjustment_reason'] ?? '')) === '') {
                        $errors["items.{$line->id}.adjustment_reason"] = 'An adjustment reason is required when overriding unit price.';
                    }
                }
            }
        }

        $removedCategoryEntries = collect($validated['removed_categories'] ?? [])->keyBy(fn ($r) => (int) $r['category_id']);
        $restoredCategoryIds = collect($validated['restored_category_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();

        if ($removedCategoryEntries->isNotEmpty() && in_array($booking->payment_status, ['paid', 'refunded'], true) && ! $request->boolean('confirm_paid_adjustment')) {
            $errors['removed_categories'] = 'This booking is already paid or refunded. Confirm paid adjustment before removing an entire category.';
        }

        $remainingServiceIds = $booking->operationalItems
            ->reject(fn ($line) => in_array((int) $line->id, $deletedIds, true) || $removedCategoryEntries->has((int) $line->item_category_id))
            ->pluck('service_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $seenNewServiceIds = [];
        $newServicesMap = [];
        foreach ($validated['new_items'] ?? [] as $tmpKey => $newInput) {
            $svc = Service::with(['category', 'unit', 'item'])->find($newInput['service_id']);
            if (! $svc) {
                $errors["new_items.{$tmpKey}.service_id"] = 'Selected service no longer exists.';
                continue;
            }
            if (! empty($newInput['category_id']) && (int) $svc->item_category_id !== (int) $newInput['category_id']) {
                $errors["new_items.{$tmpKey}.service_id"] = 'Selected service does not belong to the chosen category.';
                continue;
            }
            if (in_array((int) $svc->id, $remainingServiceIds, true) || in_array((int) $svc->id, $seenNewServiceIds, true)) {
                $errors["new_items.{$tmpKey}.service_id"] = "This service ({$svc->name}) has already been added.";
                continue;
            }
            $seenNewServiceIds[] = (int) $svc->id;
            $catalogMinor = $this->currencyService->toMinor((float) ($svc->price ?? 0), $booking->currency);
            if (array_key_exists('actual_unit_price', $newInput) && $newInput['actual_unit_price'] !== null && $newInput['actual_unit_price'] !== '') {
                $submittedMinor = $this->currencyService->toMinor((float) $newInput['actual_unit_price'], $booking->currency);
                if ($submittedMinor !== $catalogMinor) {
                    if (! $canOverridePrices) {
                        $errors["new_items.{$tmpKey}.actual_unit_price"] = 'You do not have permission to override unit prices.';
                    } elseif (trim((string) ($newInput['adjustment_reason'] ?? '')) === '') {
                        $errors["new_items.{$tmpKey}.adjustment_reason"] = 'An adjustment reason is required when overriding unit price.';
                    }
                }
            }
            $newServicesMap[$tmpKey] = $svc;
        }

        if ($validated['service_fee_override_mode'] !== 'default') {
            if (trim((string) ($validated['service_fee_override_reason'] ?? '')) === '') {
                $errors['service_fee_override_reason'] = 'Please provide a reason when overriding or waiving the Service Fee.';
            }
            if ($validated['service_fee_override_mode'] === 'custom' && ($validated['service_fee_custom'] === null || $validated['service_fee_custom'] === '')) {
                $errors['service_fee_custom'] = 'Enter a custom Service Fee amount.';
            }
        }

        if ($validated['delivery_fee_override_mode'] !== 'default') {
            if (trim((string) ($validated['delivery_fee_override_reason'] ?? '')) === '') {
                $errors['delivery_fee_override_reason'] = 'Please provide a reason when overriding or waiving the Collection & Delivery Fee.';
            }
            if ($validated['delivery_fee_override_mode'] === 'custom' && ($validated['delivery_fee_custom'] === null || $validated['delivery_fee_custom'] === '')) {
                $errors['delivery_fee_custom'] = 'Enter a custom Collection & Delivery Fee amount.';
            }
        }

        $targetStatus = $validated['booking_status'] ?? $booking->booking_status;
        if ($targetStatus !== $booking->booking_status && ! $booking->canTransitionTo($targetStatus)) {
            $errors['booking_status'] = "Invalid status transition from {$booking->booking_status} to {$targetStatus}.";
        }

        if ($errors !== []) {
            return back()->withErrors($errors)->withInput();
        }

        DB::transaction(function () use ($request, $booking, $validated, &$itemChanges, $targetStatus, $deletedIds, $newServicesMap, $removedCategoryEntries, $restoredCategoryIds) {
            $lockedBooking = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();

            if ($request->filled('version') && (int) $request->input('version') !== (int) $lockedBooking->version) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'version' => 'This booking was modified by another admin while you were editing. Please reload and review the latest values.',
                ]);
            }

            $lockedBooking->load('operationalItems');

            // 0a. Restore previously removed categories if requested
            $currentRemoved = collect($lockedBooking->removed_operational_categories ?? [])->keyBy(fn ($r) => (int) ($r['category_id'] ?? 0));
            foreach ($restoredCategoryIds as $restoreCatId) {
                if ($currentRemoved->has($restoreCatId)) {
                    $restoredEntry = $currentRemoved->pull($restoreCatId);
                    $this->logAudit($request, $lockedBooking, 'category_restored', 'Restored operational category: ' . ($restoredEntry['category_name'] ?? "Category #{$restoreCatId}"), [
                        'category_id' => $restoreCatId,
                        'category_name' => $restoredEntry['category_name'] ?? null,
                    ]);
                }
            }

            // 0b. Remove entire categories if requested
            foreach ($removedCategoryEntries as $rmCatId => $rmData) {
                $catObj = ItemCategory::find($rmCatId);
                $catName = $catObj?->name ?? "Category #{$rmCatId}";
                $catItems = $lockedBooking->operationalItems->where('item_category_id', $rmCatId);
                $removedItemsSnapshot = [];

                foreach ($catItems as $catLine) {
                    $removedItemsSnapshot[] = [
                        'id' => $catLine->id,
                        'service_id' => $catLine->service_id,
                        'service_name' => $catLine->service_name,
                        'quantity' => $catLine->quantity,
                        'weight_kg' => $catLine->weight_kg,
                        'line_total_minor' => $catLine->line_total_minor,
                    ];
                    $catLine->delete();
                }

                $currentRemoved->put($rmCatId, [
                    'category_id' => $rmCatId,
                    'category_name' => $catName,
                    'reason' => trim((string) $rmData['reason']),
                    'removed_by_admin_id' => $this->adminId(),
                    'removed_at' => now()->toIso8601String(),
                ]);

                \App\Models\BookingOperationalCategory::query()
                    ->where('booking_id', $lockedBooking->id)
                    ->where('item_category_id', $rmCatId)
                    ->update([
                        'is_removed' => true,
                        'removed_by_type' => 'admin',
                        'removed_by_id' => $this->adminId(),
                        'removed_at' => now(),
                        'removal_reason' => trim((string) $rmData['reason']),
                    ]);

                $this->logAudit($request, $lockedBooking, 'category_removed', "Removed operational category: {$catName}", [
                    'category_id' => $rmCatId,
                    'category_name' => $catName,
                    'reason' => trim((string) $rmData['reason']),
                    'removed_items' => $removedItemsSnapshot,
                ]);
            }

            $lockedBooking->removed_operational_categories = $currentRemoved->values()->all();
            $lockedBooking->load('operationalItems');

            // 1. Remove deleted operational rows belonging to this booking
            if ($deletedIds !== []) {
                $toDelete = $lockedBooking->operationalItems->whereIn('id', $deletedIds);
                foreach ($toDelete as $delLine) {
                    $delSnapshot = [
                        'id' => $delLine->id,
                        'service_name' => $delLine->service_name,
                        'item_name' => $delLine->item_name,
                        'quantity' => $delLine->quantity,
                        'weight_kg' => $delLine->weight_kg,
                        'line_total_minor' => $delLine->line_total_minor,
                    ];
                    $delLine->delete();
                    $this->logAudit($request, $lockedBooking, 'item_removed', 'Removed operational item: ' . $delSnapshot['service_name'], $delSnapshot);
                }
                $lockedBooking->load('operationalItems');
            }

            // 2. Update existing operational rows
            foreach ($lockedBooking->operationalItems as $line) {
                $input = $validated['items'][$line->id] ?? null;
                if (! is_array($input)) {
                    continue;
                }

                $before = [
                    'quantity' => $line->quantity,
                    'weight_kg' => $line->weight_kg,
                    'actual_unit_price_minor' => $line->actual_unit_price_minor,
                    'line_total_minor' => $line->line_total_minor,
                ];

                if ($line->pricing_mode === 'weight') {
                    if (array_key_exists('weight_kg', $input) && $input['weight_kg'] !== null && $input['weight_kg'] !== '') {
                        $line->weight_kg = round((float) $input['weight_kg'], 3);
                    }
                } else {
                    if (array_key_exists('quantity', $input) && $input['quantity'] !== null && $input['quantity'] !== '') {
                        $line->quantity = (int) $input['quantity'];
                    }
                }

                if (array_key_exists('actual_unit_price', $input) && $input['actual_unit_price'] !== null && $input['actual_unit_price'] !== '') {
                    $newUnitMinor = $this->currencyService->toMinor((float) $input['actual_unit_price'], $lockedBooking->currency);
                    if ($newUnitMinor !== (int) $line->actual_unit_price_minor) {
                        $line->actual_unit_price_minor = $newUnitMinor;
                        $line->is_price_overridden = $newUnitMinor !== (int) $line->catalog_unit_price_minor;
                        $line->adjustment_reason = trim((string) ($input['adjustment_reason'] ?? ''));
                        $line->adjusted_by_admin_id = $this->adminId();
                        $line->adjusted_at = now();
                    }
                }

                $line->line_total_minor = $line->computeLineTotalMinor();
                $line->save();

                $after = [
                    'quantity' => $line->quantity,
                    'weight_kg' => $line->weight_kg,
                    'actual_unit_price_minor' => $line->actual_unit_price_minor,
                    'line_total_minor' => $line->line_total_minor,
                ];

                if ($before !== $after) {
                    $itemChanges[$line->service_name . ($line->item_name ? ' - ' . $line->item_name : '')] = [
                        'before' => $before,
                        'after' => $after,
                        'reason' => $line->adjustment_reason,
                    ];
                }
            }

            // 3. Create newly added operational rows
            foreach ($validated['new_items'] ?? [] as $tmpKey => $newInput) {
                $svc = $newServicesMap[$tmpKey] ?? null;
                if (! $svc) {
                    continue;
                }

                $pricingMode = $this->resolvePricingMode($svc);
                $catalogMinor = $this->currencyService->toMinor((float) ($svc->price ?? 0), $lockedBooking->currency);
                $actualMinor = (array_key_exists('actual_unit_price', $newInput) && $newInput['actual_unit_price'] !== null && $newInput['actual_unit_price'] !== '')
                    ? $this->currencyService->toMinor((float) $newInput['actual_unit_price'], $lockedBooking->currency)
                    : $catalogMinor;

                $itemId = ! empty($newInput['item_id']) ? (int) $newInput['item_id'] : $svc->item_id;
                $itemObj = $itemId ? Item::find($itemId) : null;

                $qty = $pricingMode === 'weight' ? 1 : max(1, (int) ($newInput['quantity'] ?? 1));
                $weightKg = ($pricingMode === 'weight' && isset($newInput['weight_kg']) && $newInput['weight_kg'] !== '')
                    ? round((float) $newInput['weight_kg'], 3)
                    : null;

                $newLine = new BookingOperationalItem([
                    'booking_id' => $lockedBooking->id,
                    'item_category_id' => $svc->item_category_id,
                    'service_id' => $svc->id,
                    'item_id' => $itemObj?->id,
                    'service_name' => $svc->name,
                    'category_name' => $svc->category?->name ?? 'Service',
                    'item_name' => $itemObj?->name,
                    'pricing_mode' => $pricingMode,
                    'unit_name' => $svc->unit?->name ?? ($pricingMode === 'weight' ? 'Per Kg' : 'Item'),
                    'quantity' => $qty,
                    'weight_kg' => $weightKg,
                    'catalog_unit_price_minor' => $catalogMinor,
                    'actual_unit_price_minor' => $actualMinor,
                    'is_price_overridden' => $actualMinor !== $catalogMinor,
                    'origin_type' => \App\Models\BookingOperationalCategory::ORIGIN_ADMIN,
                    'origin_actor_id' => $this->adminId(),
                    'adjustment_reason' => trim((string) ($newInput['adjustment_reason'] ?? '')) ?: null,
                    'adjusted_by_admin_id' => $this->adminId(),
                    'adjusted_at' => now(),
                ]);

                if ($svc->item_category_id) {
                    $prov = \App\Models\BookingOperationalCategory::query()
                        ->where('booking_id', $lockedBooking->id)
                        ->where('item_category_id', $svc->item_category_id)
                        ->first();
                    if (! $prov) {
                        \App\Models\BookingOperationalCategory::create([
                            'booking_id' => $lockedBooking->id,
                            'item_category_id' => $svc->item_category_id,
                            'category_name' => $svc->category?->name ?? 'Service',
                            'origin_type' => \App\Models\BookingOperationalCategory::ORIGIN_ADMIN,
                            'origin_actor_id' => $this->adminId(),
                            'is_removed' => false,
                        ]);
                    } elseif ($prov->is_removed) {
                        $prov->update(['is_removed' => false, 'removed_at' => null, 'removed_by_type' => null, 'removed_by_id' => null]);
                    }
                }
                $newLine->line_total_minor = $newLine->computeLineTotalMinor();
                $newLine->save();

                $this->logAudit($request, $lockedBooking, 'service_added', 'Added operational service: ' . $svc->name, [
                    'service_id' => $svc->id,
                    'service_name' => $svc->name,
                    'item_id' => $itemObj?->id,
                    'item_name' => $itemObj?->name,
                    'quantity' => $qty,
                    'weight_kg' => $weightKg,
                    'line_total_minor' => $newLine->line_total_minor,
                    'reason' => $newLine->adjustment_reason,
                ]);
            }

            // First compute subtotal of operational lines to support percentage default service fee if needed
            $lockedBooking->load('operationalItems');
            $servicesSubtotalMinor = (int) $lockedBooking->operationalItems->sum('line_total_minor');

            $beforeFees = [
                'service_fee_minor' => $lockedBooking->service_fee_minor,
                'service_fee_override_mode' => $lockedBooking->service_fee_override_mode,
                'collection_delivery_fee_minor' => $lockedBooking->collection_delivery_fee_minor,
                'delivery_fee_override_mode' => $lockedBooking->delivery_fee_override_mode,
                'discount_minor' => $lockedBooking->discount_minor,
                'tax_minor' => $lockedBooking->tax_minor,
                'booking_status' => $lockedBooking->booking_status,
                'final_amount_minor' => $lockedBooking->final_amount_minor,
            ];

            $lockedBooking->service_fee_override_mode = $validated['service_fee_override_mode'];
            if ($validated['service_fee_override_mode'] === 'waived') {
                $lockedBooking->service_fee_minor = 0;
            } elseif ($validated['service_fee_override_mode'] === 'custom') {
                $lockedBooking->service_fee_minor = $this->currencyService->toMinor((float) $validated['service_fee_custom'], $lockedBooking->currency);
            } else {
                $lockedBooking->service_fee_minor = $this->computeDefaultServiceFeeMinor($lockedBooking, $servicesSubtotalMinor);
            }

            $lockedBooking->delivery_fee_override_mode = $validated['delivery_fee_override_mode'];
            if ($validated['delivery_fee_override_mode'] === 'free') {
                $lockedBooking->collection_delivery_fee_minor = 0;
            } elseif ($validated['delivery_fee_override_mode'] === 'custom') {
                $lockedBooking->collection_delivery_fee_minor = $this->currencyService->toMinor((float) $validated['delivery_fee_custom'], $lockedBooking->currency);
            } else {
                $lockedBooking->collection_delivery_fee_minor = $this->computeDefaultDeliveryFeeMinor($lockedBooking);
            }

            if (array_key_exists('discount', $validated) && $validated['discount'] !== null && $validated['discount'] !== '') {
                $lockedBooking->discount_minor = $this->currencyService->toMinor((float) $validated['discount'], $lockedBooking->currency);
            }
            if (array_key_exists('tax', $validated) && $validated['tax'] !== null && $validated['tax'] !== '') {
                $lockedBooking->tax_minor = $this->currencyService->toMinor((float) $validated['tax'], $lockedBooking->currency);
            }

            $maxAllowedDiscountMinor = $servicesSubtotalMinor
                + (int) $lockedBooking->service_fee_minor
                + (int) $lockedBooking->collection_delivery_fee_minor
                + (int) $lockedBooking->tip_amount_minor
                + (int) $lockedBooking->tax_minor;

            if ((int) $lockedBooking->discount_minor > $maxAllowedDiscountMinor) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'discount' => 'Discount cannot exceed the total chargeable booking amount.',
                ]);
            }

            $lockedBooking->operational_notes = trim((string) ($validated['operational_notes'] ?? '')) ?: null;

            if ($targetStatus !== $lockedBooking->booking_status) {
                $lockedBooking->booking_status = $targetStatus;
            }

            $wasFinalized = $lockedBooking->final_amount_minor !== null;
            $lockedBooking->version = ((int) $lockedBooking->version) + 1;
            $summary = $lockedBooking->recalculateOperationalTotals($this->currencyService);

            $this->logAudit($request, $lockedBooking, 'admin_updated', 'Admin updated booking operational details & pricing', [
                'items' => $itemChanges,
                'before' => $beforeFees,
                'after' => [
                    'service_fee_minor' => $lockedBooking->service_fee_minor,
                    'service_fee_override_mode' => $lockedBooking->service_fee_override_mode,
                    'service_fee_override_reason' => $validated['service_fee_override_reason'] ?? null,
                    'collection_delivery_fee_minor' => $lockedBooking->collection_delivery_fee_minor,
                    'delivery_fee_override_mode' => $lockedBooking->delivery_fee_override_mode,
                    'delivery_fee_override_reason' => $validated['delivery_fee_override_reason'] ?? null,
                    'discount_minor' => $lockedBooking->discount_minor,
                    'tax_minor' => $lockedBooking->tax_minor,
                    'booking_status' => $lockedBooking->booking_status,
                    'final_amount_minor' => $lockedBooking->final_amount_minor,
                ],
            ]);

            if (! $wasFinalized && $summary['is_pricing_complete'] && $lockedBooking->customer_email) {
                $formattedTotal = $this->currencyService->formatMinor((int) $lockedBooking->final_amount_minor, $lockedBooking->currency);
                $orderUrl = route('orders.show', $lockedBooking->reference);
                $this->sendBookingNotification(
                    $lockedBooking,
                    "Booking {$lockedBooking->reference} Finalized — {$formattedTotal}",
                    "Your laundry order ({$lockedBooking->reference}) pricing has been finalized. Final Total: {$formattedTotal}. View your order details here: {$orderUrl}"
                );
            }
        });

        return redirect()
            ->route('admin.bookings.edit', $booking)
            ->with('success', 'Booking changes saved successfully.');
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'booking_status' => ['required', 'string', Rule::in(array_keys(Booking::OPERATIONAL_STATUSES))],
            'status_note' => ['nullable', 'string', 'max:500'],
        ]);

        $targetStatus = $validated['booking_status'];
        if ($targetStatus === $booking->booking_status) {
            return back()->with('success', 'Booking status is already ' . ($booking->getStatusLabel()) . '.');
        }

        if (! $booking->canTransitionTo($targetStatus)) {
            $fromLabel = Booking::STATUS_LABELS[$booking->booking_status] ?? $booking->booking_status;
            $toLabel = Booking::STATUS_LABELS[$targetStatus] ?? $targetStatus;

            return back()->withErrors([
                'booking_status' => "Invalid status transition from {$fromLabel} to {$toLabel}.",
            ]);
        }

        $oldStatus = $booking->booking_status;
        $changedAt = now()->toIso8601String();

        if ($oldStatus === Booking::STATUS_STORE_ASSIGNED && ! $booking->hasValidActiveStoreAssignment()) {
            return back()->withErrors([
                'booking_status' => 'Assign a store to this booking before completing Store Assigned.',
            ]);
        }

        if ($oldStatus === Booking::STATUS_DELIVERY_MAN_ASSIGNED && ! $booking->hasValidCollectionCourierAssignment()) {
            return back()->withErrors([
                'booking_status' => 'Assign a collection delivery person to this booking before completing Delivery Man Assigned.',
            ]);
        }

        if ($oldStatus === Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP && ! $booking->hasValidFinalDeliveryCourierAssignment()) {
            return back()->withErrors([
                'booking_status' => 'Assign a final delivery person to this booking before completing Delivery Man Assigned for Store Pickup.',
            ]);
        }

        try {
            DB::transaction(function () use ($request, $booking, $oldStatus, $targetStatus, $validated, $changedAt) {
                if (array_key_exists($oldStatus, Booking::OPERATIONAL_STATUSES)) {
                    $stages = is_array($booking->completed_operational_stages) ? $booking->completed_operational_stages : [];
                    $stages[$oldStatus] = [
                        'completed_at' => $changedAt,
                        'admin_id' => $this->adminId(),
                    ];
                    $booking->completed_operational_stages = $stages;
                }
                $booking->booking_status = $targetStatus;
                $booking->version = ((int) $booking->version) + 1;
                $booking->save();

                $this->logAudit($request, $booking, 'status_changed', "Status changed from {$oldStatus} to {$targetStatus}", [
                    'completed_stage' => array_key_exists($oldStatus, Booking::OPERATIONAL_STATUSES) ? $oldStatus : null,
                    'previous_status' => $oldStatus,
                    'new_status' => $targetStatus,
                    'changed_by' => $this->adminId(),
                    'changed_at' => $changedAt,
                    'reason' => $validated['status_note'] ?? null,
                ]);

                if ($oldStatus === Booking::STATUS_FINALIZED || $booking->isInvoiceEligible($this->currencyService)) {
                    app(StoreEarningsService::class)->getOrCreateSnapshotForBooking($booking, $this->adminId());
                }
                if ($oldStatus === Booking::STATUS_COMPLETED && $booking->booking_status === Booking::STATUS_COMPLETED) {
                    app(BookingFinancialSettlementService::class)->settleCompletedBooking($booking, $this->adminId());
                }
            });
        } catch (RuntimeException $e) {
            return back()->withErrors(['booking_status' => $e->getMessage()]);
        }

        $this->notifyCustomerIfApplicable($booking, $targetStatus, $request->boolean('notify_customer', true));

        $label = Booking::STATUS_LABELS[$targetStatus] ?? ucfirst($targetStatus);

        return back()->with('success', "Booking status updated to {$label}.");
    }

    public function advanceStatus(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->booking_status === Booking::STATUS_CANCELLED) {
            return back()->withErrors(['booking_status' => 'Cancelled bookings cannot be advanced.']);
        }

        if ($booking->isOperationalWorkflowFinished()) {
            return back()->withErrors(['booking_status' => 'All 11 operational stages are already completed.']);
        }

        $validated = $request->validate([
            'expected_stage' => ['nullable', 'string', 'max:64'],
            'version' => ['nullable', 'integer'],
        ]);

        try {
            $result = DB::transaction(function () use ($request, $booking, $validated) {
            $locked = Booking::where('id', $booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->booking_status === Booking::STATUS_CANCELLED) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'booking_status' => 'Cancelled bookings cannot be advanced.',
                ]);
            }

            if ($locked->isOperationalWorkflowFinished()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'booking_status' => 'All 11 operational stages are already completed.',
                ]);
            }

            if (! empty($validated['expected_stage']) && $validated['expected_stage'] !== $locked->booking_status) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'booking_status' => 'This stage was already advanced by another action. Please review the latest status.',
                ]);
            }

            if (isset($validated['version']) && (int) $validated['version'] !== (int) $locked->version) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'booking_status' => 'Booking was updated concurrently. Please reload and try again.',
                ]);
            }

            $currentStage = $locked->booking_status;
            if ($currentStage === Booking::STATUS_STORE_ASSIGNED && ! $locked->hasValidActiveStoreAssignment()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'booking_status' => 'Assign a store to this booking before completing Store Assigned.',
                ]);
            }
            if ($currentStage === Booking::STATUS_DELIVERY_MAN_ASSIGNED && ! $locked->hasValidCollectionCourierAssignment()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'booking_status' => 'Assign a collection delivery person to this booking before completing Delivery Man Assigned.',
                ]);
            }
            if ($currentStage === Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP && ! $locked->hasValidFinalDeliveryCourierAssignment()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'booking_status' => 'Assign a final delivery person to this booking before completing Delivery Man Assigned for Store Pickup.',
                ]);
            }
            $nextStage = $locked->getNextOperationalStatus();
            $nowIso = now()->toIso8601String();
            $stages = is_array($locked->completed_operational_stages) ? $locked->completed_operational_stages : [];

            if (array_key_exists($currentStage, Booking::OPERATIONAL_STATUSES)) {
                $stages[$currentStage] = [
                    'completed_at' => $nowIso,
                    'admin_id' => $this->adminId(),
                ];
                $locked->completed_operational_stages = $stages;
            }

            $targetStage = $nextStage ?? $currentStage;
            $locked->booking_status = $targetStage;
            $locked->version = ((int) $locked->version) + 1;
            $locked->save();

            $this->logAudit($request, $locked, 'stage_completed', "Marked {$currentStage} done" . ($nextStage ? " and advanced to {$nextStage}" : ' (final stage)'), [
                'completed_stage' => $currentStage,
                'previous_status' => $currentStage,
                'new_status' => $targetStage,
                'changed_by' => $this->adminId(),
                'changed_at' => $nowIso,
            ]);

            if ($currentStage === Booking::STATUS_FINALIZED || $locked->isInvoiceEligible($this->currencyService)) {
                app(StoreEarningsService::class)->getOrCreateSnapshotForBooking($locked, $this->adminId());
            }

            if ($currentStage === Booking::STATUS_COMPLETED && $locked->booking_status === Booking::STATUS_COMPLETED) {
                app(BookingFinancialSettlementService::class)->settleCompletedBooking($locked, $this->adminId());
            }

            return ['completed_stage' => $currentStage, 'new_status' => $targetStage, 'booking' => $locked];
            });
        } catch (RuntimeException $e) {
            return back()->withErrors(['booking_status' => $e->getMessage()]);
        }

        $this->notifyCustomerIfApplicable($result['booking'], $result['new_status'], $request->boolean('notify_customer', true));

        $completedLabel = Booking::STATUS_LABELS[$result['completed_stage']] ?? $result['completed_stage'];
        $nextLabel = Booking::STATUS_LABELS[$result['new_status']] ?? $result['new_status'];

        $msg = $result['completed_stage'] === Booking::STATUS_COMPLETED
            ? 'Marked Completed Done. All 11 stages are now completed.'
            : "Marked {$completedLabel} Done. Current stage is now {$nextLabel}.";

        return back()->with('success', $msg);
    }

    /**
     * AJAX Select2 search for eligible active stores, prioritizing exact customer postcode matches.
     */
    public function searchStores(Request $request, Booking $booking): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $rawCustomerPostcode = trim((string) (($booking->address_snapshot['postcode'] ?? '') ?: ''));
        $customerCompact = $rawCustomerPostcode !== '' ? UkAddress::compactPostcode($rawCustomerPostcode) : '';

        $query = Store::query()->where('status', 'active');

        if ($search !== '') {
            $compactSearch = UkAddress::compactPostcode($search);
            $query->where(function ($q) use ($search, $compactSearch) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('store_code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('postcode', 'like', "%{$search}%");
                if ($compactSearch !== '') {
                    $q->orWhereRaw("REPLACE(UPPER(COALESCE(postcode, '')), ' ', '') LIKE ?", ["%{$compactSearch}%"]);
                }
            });
        }

        $stores = $query->orderBy('name')->get();

        $mapped = $stores->map(function (Store $store) use ($customerCompact) {
            $storePostcode = filled($store->postcode)
                ? (UkAddress::normalizePostcode((string) $store->postcode) ?? strtoupper(trim((string) $store->postcode)))
                : null;
            $storeCompact = filled($store->postcode) ? UkAddress::compactPostcode((string) $store->postcode) : '';
            $isMatch = $customerCompact !== '' && $storeCompact !== '' && $customerCompact === $storeCompact;
            $address = $store->address_line_1
                ? implode(', ', array_filter([$store->address_line_1, $store->address_line_2, $store->city, $store->state_region, $storePostcode]))
                : ($store->full_address ?: 'Not provided');
            $storePhone = trim(($store->phone_country_code ? $store->phone_country_code . ' ' : '') . ($store->phone ?? ''));

            return [
                'id' => $store->id,
                'text' => $store->name . ($storePostcode ? " ({$storePostcode})" : '') . ($store->city ? " — {$store->city}" : ''),
                'store_name' => $store->name,
                'store_code' => $store->store_code ?? '',
                'store_address' => $address,
                'store_city' => $store->city ?: '',
                'store_county' => $store->state_region ?: '',
                'store_postcode' => $storePostcode ?? 'Not provided',
                'store_phone' => $storePhone !== '' ? $storePhone : 'Contact number not provided',
                'postcode_match' => $isMatch,
            ];
        })->sortByDesc('postcode_match')->values();

        $total = $mapped->count();
        $offset = ($page - 1) * $perPage;
        $results = $mapped->slice($offset, $perPage)->values();

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => ($offset + $perPage) < $total,
            ],
        ]);
    }

    /**
     * Assign or reassign an active store to a booking, preserving history & snapshot.
     */
    public function assignStore(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        if (in_array($booking->booking_status, [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED], true)) {
            $msg = 'Cancelled or completed bookings cannot be reassigned to another store.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors(['store_id' => $msg]);
        }

        $validated = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'override_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $store = Store::where('id', $validated['store_id'])->first();
        if (! $store || $store->status !== 'active' || $store->trashed()) {
            $msg = 'Selected store is not active or no longer available for assignment.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors(['store_id' => $msg]);
        }

        $rawCustomerPostcode = trim((string) (($booking->address_snapshot['postcode'] ?? '') ?: ''));
        $customerPostcode = $rawCustomerPostcode !== ''
            ? (UkAddress::normalizePostcode($rawCustomerPostcode) ?? strtoupper($rawCustomerPostcode))
            : null;
        $customerCompact = $rawCustomerPostcode !== '' ? UkAddress::compactPostcode($rawCustomerPostcode) : '';

        $storePostcode = filled($store->postcode)
            ? (UkAddress::normalizePostcode((string) $store->postcode) ?? strtoupper(trim((string) $store->postcode)))
            : null;
        $storeCompact = filled($store->postcode) ? UkAddress::compactPostcode((string) $store->postcode) : '';

        $postcodeMatched = $customerCompact !== '' && $storeCompact !== '' && $customerCompact === $storeCompact;

        $currentAssignment = $booking->currentStoreAssignment;
        if ($currentAssignment && (int) $currentAssignment->store_id === (int) $store->id) {
            $msg = "Store \"{$store->name}\" is already assigned to this booking.";
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'booking_id' => $booking->id,
                    'store_id' => $store->id,
                    'store_name' => $currentAssignment->store_name_snapshot,
                    'store_address' => $currentAssignment->store_address_snapshot,
                    'store_city' => $currentAssignment->store_city_snapshot,
                    'store_postcode' => $currentAssignment->store_postcode_snapshot,
                    'store_phone' => $currentAssignment->store_phone_snapshot ?: 'Contact number not provided',
                    'postcode_match' => (bool) $currentAssignment->postcode_matched,
                    'assigned_at' => $currentAssignment->assigned_at?->format('d M Y, H:i'),
                    'can_complete_store_stage' => true,
                ]);
            }

            return back()->with('success', $msg);
        }

        $isReassignment = $currentAssignment !== null && (int) $currentAssignment->store_id !== (int) $store->id;

        if ($isReassignment) {
            $hasActiveDelivery = DeliveryAssignment::where('booking_id', $booking->id)
                ->whereIn('status', [
                    DeliveryAssignment::STATUS_PICKED_UP,
                    DeliveryAssignment::STATUS_IN_TRANSIT,
                    DeliveryAssignment::STATUS_HANDED_OVER,
                    DeliveryAssignment::STATUS_COMPLETED,
                ])
                ->exists();

            if ($hasActiveDelivery) {
                $msg = 'Cannot reassign store while a delivery or collection assignment is already in progress.';
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => $msg], 422)
                    : back()->withErrors(['store_id' => $msg]);
            }
        }

        $reason = trim((string) ($validated['override_reason'] ?? ''));
        if ((! $postcodeMatched || $isReassignment) && $reason === '') {
            $msg = $isReassignment
                ? 'Please provide a reason for reassigning the store.'
                : 'A manual override reason is required when assigning a store with a different postcode.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg, 'errors' => ['override_reason' => [$msg]]], 422)
                : back()->withErrors(['override_reason' => $msg])->withInput();
        }

        $assignment = DB::transaction(function () use (
            $request,
            $booking,
            $store,
            $currentAssignment,
            $isReassignment,
            $customerPostcode,
            $storePostcode,
            $postcodeMatched,
            $reason
        ) {
            /** @var Booking $locked */
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $previousStoreId = $currentAssignment?->store_id;
            $previousStoreName = $currentAssignment?->store_name_snapshot;

            if ($currentAssignment) {
                $currentAssignment->update([
                    'is_current' => false,
                    'status' => 'reassigned',
                    'superseded_by_admin_id' => $this->adminId(),
                    'superseded_at' => now(),
                ]);
            }

            $storeAddress = $store->address_line_1
                ? implode(', ', array_filter([$store->address_line_1, $store->address_line_2, $store->city, $store->state_region, $storePostcode]))
                : ($store->full_address ?: 'Not provided');

            $assignment = BookingStoreAssignment::create([
                'booking_id' => $locked->id,
                'store_id' => $store->id,
                'is_current' => true,
                'status' => 'assigned',
                'customer_postcode' => $customerPostcode,
                'store_postcode' => $storePostcode,
                'postcode_matched' => $postcodeMatched,
                'override_reason' => ! $postcodeMatched ? $reason : null,
                'reassignment_reason' => $isReassignment ? $reason : null,
                'store_name_snapshot' => $store->name,
                'store_code_snapshot' => $store->store_code,
                'store_address_snapshot' => $storeAddress,
                'store_city_snapshot' => $store->city ?: ($store->district?->name ?? null),
                'store_postcode_snapshot' => $storePostcode,
                'store_phone_snapshot' => trim(($store->phone_country_code ? $store->phone_country_code . ' ' : '') . $store->phone),
                'assigned_by_admin_id' => $this->adminId(),
                'assigned_at' => now(),
            ]);

            $locked->assigned_store_id = $store->id;
            $locked->save();

            app(\App\Services\StoreHandoverService::class)->cancelPendingForStoreChange($locked, $store->id, $this->adminId());

            $this->logAudit(
                $request,
                $locked,
                $isReassignment ? 'store_reassigned' : 'store_assigned',
                ($isReassignment ? "Reassigned store from {$previousStoreName} to {$store->name}" : "Assigned store {$store->name}") . ($storePostcode ? " ({$storePostcode})" : ''),
                [
                    'previous_store_id' => $previousStoreId,
                    'previous_store_name' => $previousStoreName,
                    'new_store_id' => $store->id,
                    'new_store_name' => $store->name,
                    'customer_postcode' => $customerPostcode,
                    'store_postcode' => $storePostcode,
                    'postcode_matched' => $postcodeMatched,
                    'reason' => $reason ?: null,
                    'assigned_by' => $this->adminId(),
                ]
            );

            return $assignment;
        });

        $message = $isReassignment
            ? "Store reassigned to \"{$store->name}\"."
            : "Store \"{$store->name}\" assigned successfully. You can now mark Store Assigned done.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'booking_id' => $booking->id,
                'store_id' => $store->id,
                'store_name' => $assignment->store_name_snapshot,
                'store_address' => $assignment->store_address_snapshot,
                'store_city' => $assignment->store_city_snapshot,
                'store_postcode' => $assignment->store_postcode_snapshot,
                'store_phone' => $assignment->store_phone_snapshot ?: 'Contact number not provided',
                'postcode_match' => (bool) $assignment->postcode_matched,
                'assigned_at' => $assignment->assigned_at?->format('d M Y, H:i'),
                'can_complete_store_stage' => true,
                'profit_summary' => app(BookingProfitService::class)->calculateForBooking($booking->fresh()),
            ]);
        }

        return back()->with('success', $message);
    }

    public function searchCouriers(Request $request, Booking $booking): JsonResponse
    {
        $queryText = mb_strtolower(trim((string) $request->query('q', '')));
        $assignmentType = (string) $request->query('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION);
        if (! in_array($assignmentType, [DeliveryAssignment::TYPE_CUSTOMER_COLLECTION, DeliveryAssignment::TYPE_FINAL_DELIVERY], true)) {
            $assignmentType = DeliveryAssignment::TYPE_CUSTOMER_COLLECTION;
        }
        /** @var DeliveryLogisticsService $logistics */
        $logistics = app(DeliveryLogisticsService::class);
        $eligible = $logistics->findEligibleDeliveryPersonsForBooking($booking, $assignmentType, $booking->assignedStore);

        $personIds = array_column($eligible, 'id');
        $personsMap = DeliveryPerson::query()
            ->whereIn('id', $personIds)
            ->with('postcodes')
            ->get()
            ->keyBy('id');

        $rawCustomerPostcode = trim((string) (($booking->address_snapshot['postcode'] ?? '') ?: ''));
        $customerPostcode = $rawCustomerPostcode !== ''
            ? (UkAddress::normalizePostcode($rawCustomerPostcode) ?? strtoupper($rawCustomerPostcode))
            : null;

        $results = [];
        $hasPostcodeMatch = false;

        foreach ($eligible as $row) {
            /** @var DeliveryPerson|null $person */
            $person = $personsMap->get($row['id']);
            $phone = $person ? $person->phone_display : '';
            $postcodesList = $person ? $person->postcodes->pluck('postcode')->all() : [];

            if ($queryText !== '') {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $row['full_name'],
                    $row['code'],
                    $phone,
                    $row['store_name'],
                    implode(' ', $postcodesList),
                ])));
                if (! str_contains($haystack, $queryText)) {
                    continue;
                }
            }

            if ($row['postcode_matched']) {
                $hasPostcodeMatch = true;
            }

            $badge = $row['postcode_matched']
                ? ('Covers ' . ($row['matched_postcode'] ?? $customerPostcode))
                : 'Manual Assignment';

            $results[] = [
                'id' => $row['id'],
                'text' => "{$row['full_name']} ({$row['code']}) — {$badge}",
                'full_name' => $row['full_name'],
                'code' => $row['code'],
                'phone' => $phone !== '' ? $phone : 'Not provided',
                'store_name' => $row['store_name'],
                'availability_status' => $row['availability_status'],
                'postcode_match' => (bool) $row['postcode_matched'],
                'matched_postcode' => $row['matched_postcode'],
                'covered_postcodes' => $postcodesList,
                'active_assignments_count' => (int) $row['active_assignments_count'],
            ];
        }

        return response()->json([
            'results' => $results,
            'has_postcode_match' => $hasPostcodeMatch,
            'customer_postcode' => $customerPostcode,
        ]);
    }

    public function assignCourier(Request $request, Booking $booking): RedirectResponse|JsonResponse
    {
        if (in_array($booking->booking_status, [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED], true)) {
            $msg = 'Cancelled or completed bookings cannot be assigned a delivery person.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors(['delivery_person_id' => $msg]);
        }

        $validated = $request->validate([
            'delivery_person_id' => ['required', 'integer', 'exists:delivery_persons,id'],
            'assignment_type' => ['required', 'string', Rule::in([
                DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
                DeliveryAssignment::TYPE_FINAL_DELIVERY,
            ])],
            'delivery_payment' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'override_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $booking->hasValidActiveStoreAssignment()) {
            $msg = 'Assign an active store to this booking before assigning a delivery person.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors(['delivery_person_id' => $msg]);
        }

        $person = DeliveryPerson::with('postcodes')->find($validated['delivery_person_id']);
        if (! $person || $person->trashed() || ! $person->isEligibleForAssignment()) {
            $msg = 'Selected delivery person is inactive, suspended, or unavailable for assignment.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors(['delivery_person_id' => $msg]);
        }

        $assignmentType = $validated['assignment_type'];
        $existingAssignment = DeliveryAssignment::query()
            ->where('booking_id', $booking->id)
            ->where('assignment_type', $assignmentType)
            ->whereNotIn('status', [
                DeliveryAssignment::STATUS_CANCELLED,
                DeliveryAssignment::STATUS_FAILED,
            ])
            ->latest('id')
            ->first();

        $rawCustomerPostcode = trim((string) (($booking->address_snapshot['postcode'] ?? '') ?: ''));
        $customerCompact = $rawCustomerPostcode !== '' ? UkAddress::compactPostcode($rawCustomerPostcode) : '';
        $matchedPostcode = $customerCompact !== ''
            ? $person->postcodes->firstWhere('postcode_compact', $customerCompact)?->postcode
            : null;
        $postcodeMatched = $matchedPostcode !== null;

        $courierPhone = $person->phone_display !== '' ? $person->phone_display : 'Not provided';
        $courierAddress = $person->full_residential_address
            ?: (implode(', ', array_filter([$person->address_line_1, $person->city, $person->residential_postcode])) ?: 'Address not provided');

        $defaultSplit = BookingFinancialSettlementService::splitMinorHalf((int) ($booking->collection_delivery_fee_minor ?? 0));
        $tipSplit = BookingFinancialSettlementService::splitMinorHalf((int) ($booking->tip_amount_minor ?? 0));
        $defaultRateMinor = $assignmentType === DeliveryAssignment::TYPE_CUSTOMER_COLLECTION
            ? $defaultSplit['collection']
            : $defaultSplit['final'];
        $tipShareMinor = $assignmentType === DeliveryAssignment::TYPE_CUSTOMER_COLLECTION
            ? $tipSplit['collection']
            : $tipSplit['final'];

        $hasExplicitPayment = array_key_exists('delivery_payment', $validated) && $validated['delivery_payment'] !== null && $validated['delivery_payment'] !== '';
        $requestedRateMinor = $hasExplicitPayment
            ? StoreAccountService::decimalToMinor($validated['delivery_payment'])
            : null;

        if ($existingAssignment && $existingAssignment->earnings_status === 'credited') {
            $msg = 'Delivery assignment earnings have already been credited; use a compensating adjustment instead.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors(['delivery_person_id' => $msg]);
        }

        if ($existingAssignment && (int) $existingAssignment->delivery_person_id === (int) $person->id) {
            if ($requestedRateMinor !== null && $requestedRateMinor !== (int) $existingAssignment->rate_minor) {
                $existingAssignment->update([
                    'rate_minor' => $requestedRateMinor,
                    'tip_share_minor' => $tipShareMinor,
                    'is_manual_rate_override' => $requestedRateMinor !== $defaultRateMinor,
                    'rate_override_reason' => trim((string) ($validated['override_reason'] ?? '')) ?: $existingAssignment->rate_override_reason,
                ]);
                $this->logAudit($request, $booking, 'delivery_payment_updated', "Updated {$assignmentType} delivery payment for {$person->full_name}", [
                    'assignment_id' => $existingAssignment->id,
                    'rate_minor' => $requestedRateMinor,
                    'tip_share_minor' => $tipShareMinor,
                    'updated_by' => $this->adminId(),
                ]);
                $msg = "Updated delivery payment for \"{$person->full_name}\".";
            } else {
                $msg = "Delivery person \"{$person->full_name}\" is already assigned for this leg.";
            }

            if ($request->expectsJson()) {
                $booking->refresh();
                $preview = app(BookingFinancialSettlementService::class)->buildPreview($booking);
                $totalMinor = (int) $existingAssignment->rate_minor + (int) $existingAssignment->tip_share_minor;

                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'booking_id' => $booking->id,
                    'assignment_id' => $existingAssignment->id,
                    'assignment_type' => $assignmentType,
                    'delivery_person_id' => $person->id,
                    'courier_name' => $person->full_name,
                    'courier_code' => $person->code,
                    'courier_phone' => $courierPhone,
                    'courier_address' => $courierAddress,
                    'postcode_match' => $postcodeMatched,
                    'matched_postcode' => $matchedPostcode,
                    'status' => $existingAssignment->status,
                    'status_label' => DeliveryAssignment::STATUS_LABELS[$existingAssignment->status] ?? ucfirst((string) $existingAssignment->status),
                    'assigned_at' => $existingAssignment->assigned_at?->format('d M Y, H:i'),
                    'rate_minor' => (int) $existingAssignment->rate_minor,
                    'tip_share_minor' => (int) $existingAssignment->tip_share_minor,
                    'total_expected_earnings_minor' => $totalMinor,
                    'delivery_payment_formatted' => $this->currencyService->formatMinor((int) $existingAssignment->rate_minor),
                    'tip_share_formatted' => $this->currencyService->formatMinor((int) $existingAssignment->tip_share_minor),
                    'total_earnings_formatted' => $this->currencyService->formatMinor($totalMinor),
                    'financial_preview' => $preview,
                    'profit_summary' => app(BookingProfitService::class)->calculateForBooking($booking),
                    'can_complete_stage' => true,
                ]);
            }

            return back()->with('success', $msg);
        }

        $isReassignment = $existingAssignment !== null;
        if ($isReassignment && in_array($existingAssignment->status, [
            DeliveryAssignment::STATUS_PICKED_UP,
            DeliveryAssignment::STATUS_IN_TRANSIT,
            DeliveryAssignment::STATUS_HANDED_OVER,
            DeliveryAssignment::STATUS_COMPLETED,
        ], true)) {
            $msg = 'Cannot reassign delivery person after pickup or handover has already started.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors(['delivery_person_id' => $msg]);
        }

        $reason = trim((string) ($validated['override_reason'] ?? ''));
        if ((! $postcodeMatched || $isReassignment) && $reason === '') {
            $msg = $isReassignment
                ? 'Please provide a reason for reassigning the delivery person.'
                : 'A manual override reason is required when assigning a delivery person who does not cover the customer postcode.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg, 'errors' => ['override_reason' => [$msg]]], 422)
                : back()->withErrors(['override_reason' => $msg])->withInput();
        }

        /** @var DeliveryLogisticsService $logistics */
        $logistics = app(DeliveryLogisticsService::class);

        $assignment = DB::transaction(function () use (
            $request,
            $booking,
            $person,
            $assignmentType,
            $existingAssignment,
            $isReassignment,
            $reason,
            $postcodeMatched,
            $matchedPostcode,
            $requestedRateMinor,
            $logistics
        ) {
            if ($isReassignment && $existingAssignment) {
                $oldStatus = $existingAssignment->status;
                $existingAssignment->update([
                    'status' => DeliveryAssignment::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                    'cancellation_reason' => "Superseded by reassignment: {$reason}",
                    'earnings_status' => 'not_payable',
                ]);

                app(\App\Services\StorePickupDeliveryService::class)->cancelActiveRequestsForAssignment(
                    $existingAssignment,
                    "Superseded by reassignment: {$reason}"
                );

                DeliveryAssignmentEvent::create([
                    'delivery_assignment_id' => $existingAssignment->id,
                    'delivery_person_id' => $existingAssignment->delivery_person_id,
                    'booking_id' => $booking->id,
                    'event_type' => 'reassigned_superseded',
                    'from_status' => $oldStatus,
                    'to_status' => DeliveryAssignment::STATUS_CANCELLED,
                    'title' => 'Assignment superseded by courier reassignment',
                    'description' => "Superseded by reassignment to {$person->full_name} ({$person->code}): {$reason}",
                    'actor_admin_id' => $this->adminId(),
                    'occurred_at' => now(),
                ]);
            }

            $newAssignment = $logistics->assignToBooking(
                $booking,
                $person,
                $assignmentType,
                $booking->assignedStore,
                (int) $this->adminId(),
                $requestedRateMinor,
                $isReassignment ? $reason : null,
                $postcodeMatched ? null : $reason
            );

            if ($assignmentType === DeliveryAssignment::TYPE_FINAL_DELIVERY && $booking->booking_status === Booking::STATUS_STORE_COMPLETE) {
                $stages = is_array($booking->completed_operational_stages) ? $booking->completed_operational_stages : [];
                if (! array_key_exists(Booking::STATUS_STORE_COMPLETE, $stages)) {
                    $stages[Booking::STATUS_STORE_COMPLETE] = [
                        'completed_at' => now()->toIso8601String(),
                        'admin_id' => $this->adminId(),
                    ];
                }
                $booking->completed_operational_stages = $stages;
                $booking->booking_status = Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP;
                $booking->version = ((int) $booking->version) + 1;
                $booking->save();
            }

            $legLabel = $assignmentType === DeliveryAssignment::TYPE_FINAL_DELIVERY ? 'Final Delivery' : 'Customer Collection';
            $this->logAudit(
                $request,
                $booking,
                $isReassignment ? 'courier_reassigned' : 'courier_assigned',
                ($isReassignment ? "Reassigned {$legLabel}" : "Assigned {$legLabel}") . " to {$person->full_name} ({$person->code})",
                [
                    'assignment_type' => $assignmentType,
                    'delivery_assignment_id' => $newAssignment->id,
                    'previous_delivery_person_id' => $existingAssignment?->delivery_person_id,
                    'new_delivery_person_id' => $person->id,
                    'postcode_matched' => $postcodeMatched,
                    'matched_postcode' => $matchedPostcode,
                    'reason' => $reason !== '' ? $reason : null,
                    'assigned_by' => $this->adminId(),
                ]
            );

            return $newAssignment;
        });

        $legLabel = $assignmentType === DeliveryAssignment::TYPE_FINAL_DELIVERY ? 'Final Delivery' : 'Customer Collection';
        $message = $isReassignment
            ? "{$legLabel} delivery person reassigned to \"{$person->full_name}\"."
            : "{$legLabel} delivery person \"{$person->full_name}\" assigned successfully.";

        if ($request->expectsJson()) {
            $booking->refresh();
            $preview = app(BookingFinancialSettlementService::class)->buildPreview($booking);
            $totalMinor = (int) $assignment->rate_minor + (int) $assignment->tip_share_minor;

            return response()->json([
                'success' => true,
                'message' => $message,
                'booking_id' => $booking->id,
                'assignment_id' => $assignment->id,
                'assignment_type' => $assignmentType,
                'delivery_person_id' => $person->id,
                'courier_name' => $person->full_name,
                'courier_code' => $person->code,
                'courier_phone' => $courierPhone,
                'courier_address' => $courierAddress,
                'postcode_match' => $postcodeMatched,
                'matched_postcode' => $matchedPostcode,
                'status' => $assignment->status,
                'status_label' => DeliveryAssignment::STATUS_LABELS[$assignment->status] ?? ucfirst((string) $assignment->status),
                'assigned_at' => $assignment->assigned_at?->format('d M Y, H:i'),
                'rate_minor' => (int) $assignment->rate_minor,
                'tip_share_minor' => (int) $assignment->tip_share_minor,
                'total_expected_earnings_minor' => $totalMinor,
                'delivery_payment_formatted' => $this->currencyService->formatMinor((int) $assignment->rate_minor),
                'tip_share_formatted' => $this->currencyService->formatMinor((int) $assignment->tip_share_minor),
                'total_earnings_formatted' => $this->currencyService->formatMinor($totalMinor),
                'financial_preview' => $preview,
                'profit_summary' => app(BookingProfitService::class)->calculateForBooking($booking),
                'can_complete_stage' => true,
            ]);
        }

        return back()->with('success', $message);
    }

    public function invoicePreview(Request $request, Booking $booking): JsonResponse
    {
        if (! $booking->isInvoiceEligible($this->currencyService)) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice is only available after the Finalized booking workflow stage is completed and operational pricing is complete.',
            ], 403);
        }

        $format = in_array($request->query('format'), ['thermal', 'a4'], true)
            ? (string) $request->query('format')
            : 'thermal';
        $paperSize = in_array($request->query('paper_size'), ['58mm', '80mm'], true)
            ? (string) $request->query('paper_size')
            : '80mm';

        /** @var BookingInvoiceService $invoiceService */
        $invoiceService = app(BookingInvoiceService::class);
        $invoice = $invoiceService->issueOrGetCurrentInvoice($booking, $this->adminId());
        $currencyService = $this->currencyService;

        $viewName = $format === 'a4'
            ? 'admin::bookings.invoices._a4'
            : 'admin::bookings.invoices._thermal';

        $html = view($viewName, compact('booking', 'invoice', 'currencyService', 'format', 'paperSize'))->render();

        return response()->json([
            'success' => true,
            'invoice_number' => $invoice->invoice_number,
            'revision_number' => $invoice->revision_number,
            'issued_at' => $invoice->issued_at?->format('d M Y, H:i'),
            'format' => $format,
            'paper_size' => $paperSize,
            'html' => $html,
        ]);
    }

    public function invoicePrint(Request $request, Booking $booking)
    {
        abort_unless(
            $booking->isInvoiceEligible($this->currencyService),
            403,
            'Invoice is only available after the Finalized booking workflow stage is completed and operational pricing is complete.'
        );

        $format = in_array($request->query('format'), ['thermal', 'a4'], true)
            ? (string) $request->query('format')
            : 'thermal';
        $paperSize = in_array($request->query('paper_size'), ['58mm', '80mm'], true)
            ? (string) $request->query('paper_size')
            : '80mm';

        /** @var BookingInvoiceService $invoiceService */
        $invoiceService = app(BookingInvoiceService::class);
        $invoice = $invoiceService->issueOrGetCurrentInvoice($booking, $this->adminId());
        $currencyService = $this->currencyService;
        $autoPrint = $request->boolean('autoprint', false);

        return view('admin::bookings.invoices.print', compact('booking', 'invoice', 'currencyService', 'format', 'paperSize', 'autoPrint'));
    }

    protected function notifyCustomerIfApplicable(Booking $booking, string $targetStatus, bool $shouldNotify): void
    {
        $notifyStatuses = [
            Booking::STATUS_STORE_ASSIGNED,
            Booking::STATUS_DELIVERY_MAN_PICKED_UP,
            Booking::STATUS_STORE_RECEIVED,
            Booking::STATUS_STORE_COMPLETE,
            Booking::STATUS_ON_DELIVERY,
            Booking::STATUS_COMPLETED,
        ];

        if ($shouldNotify && $booking->customer_email && in_array($targetStatus, $notifyStatuses, true)) {
            $label = Booking::STATUS_LABELS[$targetStatus] ?? ucfirst($targetStatus);
            $this->sendBookingNotification(
                $booking,
                "Booking {$booking->reference} Status Updated: {$label}",
                "Your laundry booking ({$booking->reference}) status has been updated to {$label}."
            );
        }
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->booking_status === Booking::STATUS_CANCELLED) {
            return back()->with('error', 'Booking is already cancelled.');
        }

        if ($booking->booking_status === Booking::STATUS_COMPLETED) {
            return back()->with('error', 'Completed bookings cannot be cancelled.');
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $oldStatus = $booking->booking_status;
        $reason = trim($validated['cancellation_reason']);

        DB::transaction(function () use ($request, $booking, $oldStatus, $reason) {
            $booking->booking_status = Booking::STATUS_CANCELLED;
            $booking->cancelled_at = now();
            $booking->cancelled_by_admin_id = $this->adminId();
            $booking->cancellation_reason = $reason;
            $booking->version = ((int) $booking->version) + 1;
            $booking->save();

            $this->logAudit($request, $booking, 'cancelled', 'Booking cancelled by admin', [
                'before_status' => $oldStatus,
                'after_status' => Booking::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
            ]);
        });

        if ($booking->customer_email) {
            $this->sendBookingNotification(
                $booking,
                "Booking {$booking->reference} Cancelled",
                "Your laundry booking ({$booking->reference}) has been cancelled. Reason: {$reason}"
            );
        }

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Booking has been cancelled and the customer has been notified.');
    }

    protected function ensureEditable(Booking $booking): ?RedirectResponse
    {
        if (in_array($booking->booking_status, [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED], true)) {
            return redirect()
                ->route('admin.bookings.show', $booking)
                ->with('error', 'Cancelled or completed bookings cannot be modified.');
        }

        return null;
    }

    protected function resolvePricingMode(Service $service): string
    {
        $unitSlug = strtolower((string) ($service->unit?->short_name ?? $service->unit?->name ?? ''));
        $serviceName = strtolower((string) $service->name);

        if (str_contains($unitSlug, 'kg') || str_contains($unitSlug, 'kilogram') || str_contains($serviceName, 'wash & fold')) {
            return 'weight';
        }

        return 'item';
    }

    protected function isWashFoldService(Service $service): bool
    {
        $catName = strtolower((string) ($service->category?->name ?? ''));
        $svcName = strtolower((string) $service->name);

        return str_contains($catName, 'wash & fold') || str_contains($svcName, 'wash & fold');
    }

    protected function computeDefaultServiceFeeMinor(Booking $booking, int $servicesSubtotalMinor): int
    {
        $feeSnapshot = is_array($booking->fee_snapshot) ? $booking->fee_snapshot : [];
        if (isset($feeSnapshot['service_fee']['type'], $feeSnapshot['service_fee']['value'])) {
            if ($feeSnapshot['service_fee']['type'] === 'percentage') {
                return (int) round($servicesSubtotalMinor * ((float) $feeSnapshot['service_fee']['value'] / 100));
            }
            if (isset($feeSnapshot['service_fee']['amount_minor'])) {
                return (int) $feeSnapshot['service_fee']['amount_minor'];
            }
        }

        $setting = BookingFeeSetting::where('fee_code', 'service_fee')->where('is_active', true)->first();
        if (! $setting) {
            return 0;
        }

        if ($setting->fee_type === 'percentage') {
            return (int) round($servicesSubtotalMinor * ((float) $setting->fee_value / 100));
        }

        return $this->currencyService->toMinor((float) $setting->fee_value, $booking->currency);
    }

    protected function computeDefaultDeliveryFeeMinor(Booking $booking): int
    {
        $feeSnapshot = is_array($booking->fee_snapshot) ? $booking->fee_snapshot : [];
        if (isset($feeSnapshot['collection_delivery_fee']['amount_minor'])) {
            return (int) $feeSnapshot['collection_delivery_fee']['amount_minor'];
        }

        $setting = BookingFeeSetting::where('fee_code', 'collection_delivery_fee')->where('is_active', true)->first();
        if (! $setting) {
            return 0;
        }

        return $this->currencyService->toMinor((float) $setting->fee_value, $booking->currency);
    }

    protected function adminId(): ?int
    {
        return Auth::guard('admin')->id();
    }

    protected function logAudit(Request $request, Booking $booking, string $action, string $summary, array $changes = []): void
    {
        $admin = Auth::guard('admin')->user();

        BookingAuditLog::create([
            'booking_id' => $booking->id,
            'actor_type' => 'admin',
            'actor_id' => $admin?->id,
            'actor_name' => $admin?->name ?? 'Admin',
            'action' => $action,
            'summary' => $summary,
            'changes' => $changes,
            'ip_address' => $request->ip(),
        ]);
    }

    protected function sendBookingNotification(Booking $booking, string $subject, string $body): void
    {
        try {
            Mail::to($booking->customer_email)->send(new BookingNotificationMail($booking, $subject, $body));
        } catch (\Throwable $e) {
            Log::warning('Failed to send booking notification email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
