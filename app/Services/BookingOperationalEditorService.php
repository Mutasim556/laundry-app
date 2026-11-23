<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingOperationalCategory;
use App\Models\BookingOperationalItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use RuntimeException;

class BookingOperationalEditorService
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    public function syncSnapshotCustomerCategories(Booking $booking): void
    {
        $snapshotCatIds = $this->getSnapshotCategoryIds($booking);
        if (empty($snapshotCatIds)) {
            return;
        }

        $existing = BookingOperationalCategory::query()
            ->where('booking_id', $booking->id)
            ->pluck('item_category_id')
            ->all();

        foreach ($booking->services_snapshot ?? [] as $snap) {
            $catId = isset($snap['category_id']) ? (int) $snap['category_id'] : 0;
            if ($catId <= 0 || in_array($catId, $existing, true)) {
                continue;
            }
            if (! ItemCategory::whereKey($catId)->exists()) {
                continue;
            }

            BookingOperationalCategory::create([
                'booking_id' => $booking->id,
                'item_category_id' => $catId,
                'category_name' => (string) ($snap['name'] ?? 'Service Category'),
                'origin_type' => BookingOperationalCategory::ORIGIN_CUSTOMER,
                'origin_actor_id' => null,
                'is_removed' => false,
            ]);
            $existing[] = $catId;
        }
    }

    /**
     * @return int[]
     */
    public function getSnapshotCategoryIds(Booking $booking): array
    {
        return collect($booking->services_snapshot ?? [])
            ->pluck('category_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function canDeliveryPersonRemoveCategory(
        DeliveryPerson $person,
        Booking $booking,
        ?BookingOperationalCategory $catRecord,
        ?int $categoryId
    ): bool {
        if (! $categoryId || ! $catRecord) {
            return false;
        }

        if ((int) $catRecord->booking_id !== (int) $booking->id) {
            return false;
        }

        if (in_array($categoryId, $this->getSnapshotCategoryIds($booking), true)) {
            return false;
        }

        if ($booking->currentFinancialSettlement && $booking->currentFinancialSettlement->settlement_status === 'settled') {
            return false;
        }

        $completedStages = $booking->getCompletedOperationalStatuses();
        if (in_array(Booking::STATUS_FINALIZED, $completedStages, true)) {
            return false;
        }

        return $catRecord->origin_type === BookingOperationalCategory::ORIGIN_DELIVERY_PERSON
            && (int) $catRecord->origin_actor_id === (int) $person->id;
    }

    /**
     * Build category groups enriched with authoritative database provenance.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildCategoryGroups(
        Booking $booking,
        ?Collection $categories = null,
        ?Collection $categoriesServicesMap = null,
        ?DeliveryPerson $forDeliveryPerson = null
    ): array {
        $this->syncSnapshotCustomerCategories($booking);

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
        $provenanceRows = BookingOperationalCategory::query()
            ->where('booking_id', $booking->id)
            ->get()
            ->keyBy('item_category_id');

        $snapshotCatIds = $this->getSnapshotCategoryIds($booking);
        $groups = [];

        // 1. Customer snapshot categories
        foreach ($booking->services_snapshot ?? [] as $snap) {
            $catId = isset($snap['category_id']) ? (int) $snap['category_id'] : null;
            if ($catId && in_array($catId, $removedCatIds, true)) {
                continue;
            }
            $catModel = $catId ? $categoriesById->get($catId) : null;
            $catName = (string) ($catModel?->name ?? $snap['name'] ?? 'Service Category');
            $key = $catId ? 'cat_' . $catId : 'snap_' . md5(strtolower($catName));
            $prov = $catId ? $provenanceRows->get($catId) : null;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'category_id' => $catId,
                    'category_name' => $catName,
                    'source' => 'customer',
                    'origin_type' => BookingOperationalCategory::ORIGIN_CUSTOMER,
                    'origin_actor_id' => null,
                    'origin_badge_label' => 'Customer Selected',
                    'can_remove_category' => $forDeliveryPerson === null,
                    'selected_option_name' => $snap['option_name'] ?? null,
                    'is_wash_fold' => ! empty($snap['is_wash_fold']),
                    'services' => $catId ? ($categoriesServicesMap[$catId] ?? []) : [],
                    'items' => collect(),
                ];
            }
        }

        // 2. Persisted operational category provenance rows (covers empty added categories too)
        foreach ($provenanceRows as $catId => $prov) {
            if ($prov->is_removed || in_array((int) $catId, $removedCatIds, true)) {
                continue;
            }
            $key = 'cat_' . $catId;
            if (isset($groups[$key])) {
                continue;
            }
            $catModel = $categoriesById->get($catId);
            $catName = (string) ($catModel?->name ?? $prov->category_name);
            $originType = in_array((int) $catId, $snapshotCatIds, true)
                ? BookingOperationalCategory::ORIGIN_CUSTOMER
                : $prov->origin_type;

            $canRemove = $forDeliveryPerson === null
                ? true
                : $this->canDeliveryPersonRemoveCategory($forDeliveryPerson, $booking, $prov, (int) $catId);

            $groups[$key] = [
                'key' => $key,
                'category_id' => (int) $catId,
                'category_name' => $catName,
                'source' => $originType,
                'origin_type' => $originType,
                'origin_actor_id' => $prov->origin_actor_id,
                'origin_badge_label' => $this->originBadgeLabel($originType, $prov->origin_actor_id, $forDeliveryPerson),
                'can_remove_category' => $canRemove,
                'selected_option_name' => null,
                'is_wash_fold' => str_contains(strtolower($catName), 'wash & fold'),
                'services' => $categoriesServicesMap[$catId] ?? [],
                'items' => collect(),
            ];
        }

        // 3. Operational items (any legacy item without provenance row defaults to unknown -> protected)
        foreach ($operationalItems as $opItem) {
            $catId = $opItem->item_category_id ? (int) $opItem->item_category_id : null;
            if ($catId && in_array($catId, $removedCatIds, true)) {
                continue;
            }
            $catModel = $catId ? $categoriesById->get($catId) : null;
            $catName = (string) ($opItem->category_name ?: ($catModel?->name ?? 'Additional Service'));
            $key = $catId ? 'cat_' . $catId : 'snap_' . md5(strtolower($catName));

            if (! isset($groups[$key])) {
                $prov = $catId ? $provenanceRows->get($catId) : null;
                $originType = $prov?->origin_type ?? BookingOperationalCategory::ORIGIN_UNKNOWN;
                $canRemove = $forDeliveryPerson === null
                    ? true
                    : $this->canDeliveryPersonRemoveCategory($forDeliveryPerson, $booking, $prov, $catId);

                $groups[$key] = [
                    'key' => $key,
                    'category_id' => $catId,
                    'category_name' => $catName,
                    'source' => $originType,
                    'origin_type' => $originType,
                    'origin_actor_id' => $prov?->origin_actor_id,
                    'origin_badge_label' => $this->originBadgeLabel($originType, $prov?->origin_actor_id, $forDeliveryPerson),
                    'can_remove_category' => $canRemove,
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

    public function originBadgeLabel(string $originType, ?int $originActorId, ?DeliveryPerson $viewer = null): string
    {
        return match ($originType) {
            BookingOperationalCategory::ORIGIN_CUSTOMER => 'Customer Selected',
            BookingOperationalCategory::ORIGIN_ADMIN => 'Added by Admin',
            BookingOperationalCategory::ORIGIN_DELIVERY_PERSON => ($viewer && $originActorId && (int) $viewer->id === (int) $originActorId)
                ? 'Added by Delivery Person (You)'
                : 'Added by Delivery Person',
            default => 'Protected Category',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function formatCategoryServices(Booking $booking, ItemCategory $category, $services): array
    {
        $decimals = $this->currencyService->decimals($booking->currency);

        return $services->map(function (Service $service) use ($booking, $category, $decimals) {
            $unitText = strtolower((string) ($service->unit?->short_name ?: ($service->unit?->name ?: '')));
            $mode = str_contains($unitText, 'kg') ? 'weight' : 'fixed';
            $unitLabel = $mode === 'weight'
                ? 'kg'
                : ($service->unit?->short_name ?: ($service->unit?->name ?: 'item'));
            $priceMinor = $this->currencyService->toMinor((float) ($service->price ?? 0), $booking->currency);

            return [
                'id' => $service->id,
                'category_id' => $category->id,
                'category_name' => $category->name,
                'name' => $service->name,
                'item_name' => $service->item?->name ?: $service->name,
                'pricing_mode' => $mode,
                'unit_label' => $unitLabel,
                'price_minor' => $priceMinor,
                'price_decimal' => number_format($priceMinor / (10 ** $decimals), $decimals, '.', ''),
                'is_wash_fold' => str_contains(strtolower($category->name), 'wash & fold'),
            ];
        })->values()->all();
    }

    public function addCategoryAsDeliveryPerson(Booking $booking, DeliveryPerson $person, int $categoryId): BookingOperationalCategory
    {
        $category = ItemCategory::where('is_active', true)->findOrFail($categoryId);

        return DB::transaction(function () use ($booking, $person, $category) {
            $this->syncSnapshotCustomerCategories($booking);

            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $existing = BookingOperationalCategory::query()
                ->where('booking_id', $locked->id)
                ->where('item_category_id', $category->id)
                ->lockForUpdate()
                ->first();

            if ($existing && ! $existing->is_removed) {
                return $existing;
            }

            if ($existing && $existing->is_removed) {
                // Only restore if not a customer/admin category; otherwise if it was this DP's removed category, restore ownership cleanly
                if ($existing->origin_type === BookingOperationalCategory::ORIGIN_DELIVERY_PERSON && (int) $existing->origin_actor_id === (int) $person->id) {
                    $existing->update([
                        'is_removed' => false,
                        'removed_by_type' => null,
                        'removed_by_id' => null,
                        'removed_at' => null,
                        'removal_reason' => null,
                    ]);
                } else {
                    throw new RuntimeException('This category was previously removed and requires admin restoration.');
                }
                $record = $existing;
            } else {
                $record = BookingOperationalCategory::create([
                    'booking_id' => $locked->id,
                    'item_category_id' => $category->id,
                    'category_name' => $category->name,
                    'origin_type' => BookingOperationalCategory::ORIGIN_DELIVERY_PERSON,
                    'origin_actor_id' => $person->id,
                    'is_removed' => false,
                ]);
            }

            $locked->version = ((int) $locked->version) + 1;
            $locked->save();

            BookingAuditLog::create([
                'booking_id' => $locked->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $person->id,
                'actor_name' => $person->full_name,
                'action' => 'category_added_by_dp',
                'summary' => "Added service category {$category->name} by Delivery Person {$person->full_name} ({$person->code}).",
                'changes' => [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'origin_type' => BookingOperationalCategory::ORIGIN_DELIVERY_PERSON,
                    'origin_actor_id' => $person->id,
                ],
            ]);

            return $record;
        });
    }

    public function removeCategoryAsDeliveryPerson(Booking $booking, DeliveryPerson $person, int $categoryId, ?string $reason = null): void
    {
        DB::transaction(function () use ($booking, $person, $categoryId, $reason) {
            $this->syncSnapshotCustomerCategories($booking);

            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $catRecord = BookingOperationalCategory::query()
                ->where('booking_id', $locked->id)
                ->where('item_category_id', $categoryId)
                ->lockForUpdate()
                ->first();

            if (! $this->canDeliveryPersonRemoveCategory($person, $locked, $catRecord, $categoryId)) {
                throw new RuntimeException('You are not authorized to remove this service category. Customer, admin, unknown-origin, and other Delivery Persons\' categories are protected.');
            }

            $catItems = $locked->operationalItems()->where('item_category_id', $categoryId)->get();
            $removedItemsSnapshot = [];
            foreach ($catItems as $line) {
                $removedItemsSnapshot[] = [
                    'id' => $line->id,
                    'service_name' => $line->service_name,
                    'quantity' => $line->quantity,
                    'weight_kg' => $line->weight_kg,
                    'line_total_minor' => $line->line_total_minor,
                ];
                $line->delete();
            }

            $catRecord->update([
                'is_removed' => true,
                'removed_by_type' => 'delivery_person',
                'removed_by_id' => $person->id,
                'removed_at' => now(),
                'removal_reason' => $reason ? trim($reason) : 'Removed by Delivery Person during collection edit',
            ]);

            $currentRemoved = collect($locked->removed_operational_categories ?? [])->keyBy(fn ($r) => (int) ($r['category_id'] ?? 0));
            $currentRemoved->put($categoryId, [
                'category_id' => $categoryId,
                'category_name' => $catRecord->category_name,
                'reason' => $catRecord->removal_reason,
                'removed_by_delivery_person_id' => $person->id,
                'removed_at' => now()->toIso8601String(),
            ]);
            $locked->removed_operational_categories = $currentRemoved->values()->all();
            $locked->version = ((int) $locked->version) + 1;
            $locked->recalculateOperationalTotals($this->currencyService);

            BookingAuditLog::create([
                'booking_id' => $locked->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $person->id,
                'actor_name' => $person->full_name,
                'action' => 'category_removed_by_dp',
                'summary' => "Removed Delivery Person-added category {$catRecord->category_name}.",
                'changes' => [
                    'category_id' => $categoryId,
                    'category_name' => $catRecord->category_name,
                    'reason' => $catRecord->removal_reason,
                    'removed_items' => $removedItemsSnapshot,
                ],
            ]);
        });
    }

    /**
     * Save full edit payload (existing items, new items, deleted items, added/removed categories)
     * with strict provenance & role-aware category preservation.
     *
     * @param array<string, mixed> $payload
     */
    public function saveDeliveryPersonBookingEdit(Booking $booking, DeliveryPerson $person, array $payload): void
    {
        DB::transaction(function () use ($booking, $person, $payload) {
            $this->syncSnapshotCustomerCategories($booking);

            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            // 1. Process explicit category additions
            foreach ($payload['added_category_ids'] ?? [] as $addCatId) {
                $catId = (int) $addCatId;
                if ($catId > 0) {
                    $cat = ItemCategory::where('is_active', true)->find($catId);
                    if ($cat) {
                        $exists = BookingOperationalCategory::query()
                            ->where('booking_id', $locked->id)
                            ->where('item_category_id', $catId)
                            ->first();
                        if (! $exists) {
                            BookingOperationalCategory::create([
                                'booking_id' => $locked->id,
                                'item_category_id' => $catId,
                                'category_name' => $cat->name,
                                'origin_type' => BookingOperationalCategory::ORIGIN_DELIVERY_PERSON,
                                'origin_actor_id' => $person->id,
                                'is_removed' => false,
                            ]);
                        } elseif ($exists->is_removed && $exists->origin_type === BookingOperationalCategory::ORIGIN_DELIVERY_PERSON && (int) $exists->origin_actor_id === (int) $person->id) {
                            $exists->update(['is_removed' => false, 'removed_at' => null, 'removed_by_type' => null, 'removed_by_id' => null]);
                        }
                    }
                }
            }

            // 2. Validate & process explicit category removals (never infer from omitted categories!)
            foreach ($payload['removed_categories'] ?? [] as $rmEntry) {
                $rmCatId = (int) ($rmEntry['category_id'] ?? 0);
                if ($rmCatId <= 0) {
                    continue;
                }
                $catRecord = BookingOperationalCategory::query()
                    ->where('booking_id', $locked->id)
                    ->where('item_category_id', $rmCatId)
                    ->lockForUpdate()
                    ->first();

                if (! $this->canDeliveryPersonRemoveCategory($person, $locked, $catRecord, $rmCatId)) {
                    throw new RuntimeException('Unauthorized category removal: protected category cannot be removed by Delivery Person.');
                }

                $locked->operationalItems()->where('item_category_id', $rmCatId)->delete();
                $catRecord->update([
                    'is_removed' => true,
                    'removed_by_type' => 'delivery_person',
                    'removed_by_id' => $person->id,
                    'removed_at' => now(),
                    'removal_reason' => trim((string) ($rmEntry['reason'] ?? 'Removed during collection edit')),
                ]);
            }

            // 3. Delete individual operational items (never deletes parent category!)
            $deletedItemIds = collect($payload['deleted_item_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->all();
            if ($deletedItemIds !== []) {
                $locked->operationalItems()->whereIn('id', $deletedItemIds)->delete();
            }

            // 4. Update existing items (quantities, weights, notes; unit price overrides blocked)
            $existingItems = $locked->operationalItems()->get()->keyBy('id');
            $changes = [];
            foreach ($payload['items'] ?? [] as $row) {
                $item = $existingItems->get((int) ($row['id'] ?? 0));
                if (! $item || (int) $item->booking_id !== (int) $locked->id) {
                    continue;
                }

                $before = [
                    'quantity' => $item->quantity,
                    'weight_kg' => $item->weight_kg,
                    'adjustment_reason' => $item->adjustment_reason,
                ];

                if ($item->pricing_mode === 'weight') {
                    $item->weight_kg = isset($row['weight_kg']) ? round(max(0, (float) $row['weight_kg']), 2) : $item->weight_kg;
                } else {
                    $item->quantity = isset($row['quantity']) ? max(0, (int) $row['quantity']) : $item->quantity;
                }

                if (array_key_exists('item_notes', $row)) {
                    $item->adjustment_reason = $row['item_notes'] ? trim((string) $row['item_notes']) : null;
                }

                $item->line_total_minor = $item->computeLineTotalMinor();
                $item->save();

                $changes[] = [
                    'item_id' => $item->id,
                    'item_name' => $item->item_name ?: $item->service_name,
                    'before' => $before,
                    'after' => [
                        'quantity' => $item->quantity,
                        'weight_kg' => $item->weight_kg,
                        'line_total_minor' => $item->line_total_minor,
                    ],
                ];
            }

            // 5. Add new items inside active categories
            foreach ($payload['new_items'] ?? [] as $newRow) {
                $svc = Service::with(['category', 'unit', 'item'])->find((int) ($newRow['service_id'] ?? 0));
                if (! $svc || ! $svc->is_active) {
                    continue;
                }
                $catId = (int) $svc->item_category_id;

                // Ensure category provenance exists (if somehow added via item, mark DP-origin only if not already in booking)
                $prov = BookingOperationalCategory::query()
                    ->where('booking_id', $locked->id)
                    ->where('item_category_id', $catId)
                    ->first();
                if (! $prov) {
                    BookingOperationalCategory::create([
                        'booking_id' => $locked->id,
                        'item_category_id' => $catId,
                        'category_name' => $svc->category?->name ?? 'Service Category',
                        'origin_type' => BookingOperationalCategory::ORIGIN_DELIVERY_PERSON,
                        'origin_actor_id' => $person->id,
                        'is_removed' => false,
                    ]);
                }

                $unitText = strtolower((string) ($svc->unit?->short_name ?: ($svc->unit?->name ?: '')));
                $mode = str_contains($unitText, 'kg') ? 'weight' : 'fixed';
                $unitMinor = $this->currencyService->toMinor((float) ($svc->price ?? 0), $locked->currency);
                $qty = $mode === 'weight' ? 1 : max(1, (int) ($newRow['quantity'] ?? 1));
                $weight = $mode === 'weight' ? round(max(0.01, (float) ($newRow['weight_kg'] ?? 1)), 2) : null;

                $created = BookingOperationalItem::create([
                    'booking_id' => $locked->id,
                    'item_category_id' => $catId,
                    'service_id' => $svc->id,
                    'item_id' => $svc->item_id,
                    'service_name' => $svc->name,
                    'category_name' => $svc->category?->name ?? 'Service',
                    'item_name' => $svc->item?->name ?: $svc->name,
                    'pricing_mode' => $mode,
                    'unit_name' => $mode === 'weight' ? 'kg' : ($svc->unit?->short_name ?: 'item'),
                    'quantity' => $qty,
                    'weight_kg' => $weight,
                    'catalog_unit_price_minor' => $unitMinor,
                    'actual_unit_price_minor' => $unitMinor,
                    'line_total_minor' => 0,
                    'is_price_overridden' => false,
                    'origin_type' => BookingOperationalCategory::ORIGIN_DELIVERY_PERSON,
                    'origin_actor_id' => $person->id,
                    'adjustment_reason' => ! empty($newRow['item_notes']) ? trim((string) $newRow['item_notes']) : null,
                ]);
                $created->line_total_minor = $created->computeLineTotalMinor();
                $created->save();
            }

            $locked->version = ((int) $locked->version) + 1;
            $locked->recalculateOperationalTotals($this->currencyService);

            BookingAuditLog::create([
                'booking_id' => $locked->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $person->id,
                'actor_name' => $person->full_name,
                'action' => 'collection_booking_edited_by_dp',
                'summary' => "Booking operational items/categories updated by Delivery Person {$person->full_name} ({$person->code}).",
                'changes' => [
                    'delivery_person_id' => $person->id,
                    'updated_items' => $changes,
                    'deleted_item_ids' => $deletedItemIds,
                    'added_category_ids' => $payload['added_category_ids'] ?? [],
                    'removed_categories' => $payload['removed_categories'] ?? [],
                ],
            ]);
        });
    }
}
