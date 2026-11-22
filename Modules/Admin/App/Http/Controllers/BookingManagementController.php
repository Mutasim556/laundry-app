<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\BookingNotificationMail;
use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingOperationalItem;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Modules\Admin\App\Models\BookingFeeSetting;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;

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
        $booking->syncOperationalItemsFromSnapshotIfEmpty($this->currencyService);
        $booking->load(['operationalItems.service', 'operationalItems.category', 'operationalItems.item', 'auditLogs', 'user']);

        $currencyService = $this->currencyService;
        $statusLabels = Booking::STATUS_LABELS;
        $allowedNextStatuses = Booking::ALLOWED_STATUS_TRANSITIONS[$booking->booking_status] ?? [];
        $pricingSummary = $booking->getOperationalPricingSummary($this->currencyService);
        $categoryGroups = $this->buildCategoryGroups($booking);
        $operationalStatuses = Booking::OPERATIONAL_STATUSES;
        $completedOperationalStatuses = $booking->getCompletedOperationalStatuses();
        $stageProgress = $booking->getOperationalStageProgress();

        return view('admin::bookings.show', compact('booking', 'currencyService', 'statusLabels', 'allowedNextStatuses', 'pricingSummary', 'categoryGroups', 'operationalStatuses', 'completedOperationalStatuses', 'stageProgress'));
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
                    'adjustment_reason' => trim((string) ($newInput['adjustment_reason'] ?? '')) ?: null,
                    'adjusted_by_admin_id' => $this->adminId(),
                    'adjusted_at' => now(),
                ]);
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
        });

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

            return ['completed_stage' => $currentStage, 'new_status' => $targetStage, 'booking' => $locked];
        });

        $this->notifyCustomerIfApplicable($result['booking'], $result['new_status'], $request->boolean('notify_customer', true));

        $completedLabel = Booking::STATUS_LABELS[$result['completed_stage']] ?? $result['completed_stage'];
        $nextLabel = Booking::STATUS_LABELS[$result['new_status']] ?? $result['new_status'];

        $msg = $result['completed_stage'] === Booking::STATUS_COMPLETED
            ? 'Marked Completed Done. All 11 stages are now completed.'
            : "Marked {$completedLabel} Done. Current stage is now {$nextLabel}.";

        return back()->with('success', $msg);
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
