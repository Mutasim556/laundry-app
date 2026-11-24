<?php

namespace App\Models;

use App\Services\CurrencyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Booking extends Model
{
    use HasFactory;

    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_AWAITING_COLLECTION = 'awaiting_collection';
    public const STATUS_COLLECTED = 'collected';
    public const STATUS_WEIGHED = 'weighed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_READY_FOR_DELIVERY = 'ready_for_delivery';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';

    // 11-stage operational workflow statuses
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_STORE_ASSIGNED = 'store_assigned';
    public const STATUS_DELIVERY_MAN_ASSIGNED = 'delivery_man_assigned';
    public const STATUS_FINALIZED = 'finalized';
    public const STATUS_DELIVERY_MAN_PICKED_UP = 'delivery_man_picked_up';
    public const STATUS_STORE_RECEIVED = 'store_received';
    public const STATUS_STORE_PROCESSING = 'store_processing';
    public const STATUS_STORE_COMPLETE = 'store_complete';
    public const STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP = 'delivery_man_assigned_for_store_pickup';
    public const STATUS_ON_DELIVERY = 'on_delivery';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const CUSTOMER_STATUS_PENDING = 'pending';
    public const CUSTOMER_STATUS_IN_PROGRESS = 'in_progress';
    public const CUSTOMER_STATUS_COMPLETED = 'completed';
    public const CUSTOMER_STATUS_CANCELLED = 'cancelled';

    public const CUSTOMER_PENDING_BOOKING_STATUSES = [
        self::STATUS_CONFIRMED,
        self::STATUS_PROCESSING,
    ];

    public const OPERATIONAL_STATUSES = [
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_STORE_ASSIGNED => 'Store Assigned',
        self::STATUS_DELIVERY_MAN_ASSIGNED => 'Delivery Man Assigned',
        self::STATUS_FINALIZED => 'Finalized',
        self::STATUS_DELIVERY_MAN_PICKED_UP => 'Delivery Man Picked Up',
        self::STATUS_STORE_RECEIVED => 'Store Received',
        self::STATUS_STORE_PROCESSING => 'Store Processing',
        self::STATUS_STORE_COMPLETE => 'Store Complete',
        self::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP => 'Delivery Man Assigned for Store Pickup',
        self::STATUS_ON_DELIVERY => 'On Delivery',
        self::STATUS_COMPLETED => 'Completed',
    ];

    public const STATUS_LABELS = [
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_STORE_ASSIGNED => 'Store Assigned',
        self::STATUS_DELIVERY_MAN_ASSIGNED => 'Delivery Man Assigned',
        self::STATUS_FINALIZED => 'Finalized',
        self::STATUS_DELIVERY_MAN_PICKED_UP => 'Delivery Man Picked Up',
        self::STATUS_STORE_RECEIVED => 'Store Received',
        self::STATUS_STORE_PROCESSING => 'Store Processing',
        self::STATUS_STORE_COMPLETE => 'Store Complete',
        self::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP => 'Delivery Man Assigned for Store Pickup',
        self::STATUS_ON_DELIVERY => 'On Delivery',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
        // Legacy labels preserved for historical bookings
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_AWAITING_COLLECTION => 'Awaiting Collection',
        self::STATUS_COLLECTED => 'Collected',
        self::STATUS_WEIGHED => 'Weighed',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_READY_FOR_DELIVERY => 'Ready for Delivery',
        self::STATUS_OUT_FOR_DELIVERY => 'Out for Delivery',
        self::STATUS_DELIVERED => 'Delivered',
    ];

    public const ALLOWED_STATUS_TRANSITIONS = [
        self::STATUS_PROCESSING => [self::STATUS_STORE_ASSIGNED, self::STATUS_CANCELLED],
        self::STATUS_STORE_ASSIGNED => [self::STATUS_DELIVERY_MAN_ASSIGNED, self::STATUS_CANCELLED],
        self::STATUS_DELIVERY_MAN_ASSIGNED => [self::STATUS_FINALIZED, self::STATUS_CANCELLED],
        self::STATUS_FINALIZED => [self::STATUS_DELIVERY_MAN_PICKED_UP, self::STATUS_CANCELLED],
        self::STATUS_DELIVERY_MAN_PICKED_UP => [self::STATUS_STORE_RECEIVED, self::STATUS_CANCELLED],
        self::STATUS_STORE_RECEIVED => [self::STATUS_STORE_PROCESSING, self::STATUS_CANCELLED],
        self::STATUS_STORE_PROCESSING => [self::STATUS_STORE_COMPLETE, self::STATUS_CANCELLED],
        self::STATUS_STORE_COMPLETE => [self::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP, self::STATUS_CANCELLED],
        self::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP => [self::STATUS_ON_DELIVERY, self::STATUS_CANCELLED],
        self::STATUS_ON_DELIVERY => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_COMPLETED => [],
        self::STATUS_CANCELLED => [],
        // Legacy transitions into the 11-stage workflow
        self::STATUS_CONFIRMED => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
        self::STATUS_AWAITING_COLLECTION => [self::STATUS_PROCESSING, self::STATUS_STORE_ASSIGNED, self::STATUS_CANCELLED],
        self::STATUS_COLLECTED => [self::STATUS_PROCESSING, self::STATUS_STORE_RECEIVED, self::STATUS_CANCELLED],
        self::STATUS_WEIGHED => [self::STATUS_PROCESSING, self::STATUS_FINALIZED, self::STATUS_CANCELLED],
        self::STATUS_IN_PROGRESS => [self::STATUS_STORE_PROCESSING, self::STATUS_STORE_COMPLETE, self::STATUS_CANCELLED],
        self::STATUS_READY_FOR_DELIVERY => [self::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP, self::STATUS_ON_DELIVERY, self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_ON_DELIVERY, self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_DELIVERED => [self::STATUS_COMPLETED],
    ];

    public static function statusBadgeStyle(?string $status): string
    {
        return match ((string) $status) {
            self::STATUS_PROCESSING, self::STATUS_STORE_PROCESSING, self::STATUS_IN_PROGRESS => 'background-color: #EDE9FE; color: #6D28D9;',
            self::STATUS_STORE_ASSIGNED, self::STATUS_DELIVERY_MAN_PICKED_UP, self::STATUS_CONFIRMED, self::STATUS_OUT_FOR_DELIVERY => 'background-color: #DBEAFE; color: #1D4ED8;',
            self::STATUS_DELIVERY_MAN_ASSIGNED, self::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP, self::STATUS_COLLECTED => 'background-color: #CFFAFE; color: #0E7490;',
            self::STATUS_STORE_RECEIVED => 'background-color: #CCFBF1; color: #0F766E;',
            self::STATUS_FINALIZED, self::STATUS_STORE_COMPLETE, self::STATUS_COMPLETED, self::STATUS_DELIVERED => 'background-color: #D1FAE5; color: #047857;',
            self::STATUS_ON_DELIVERY, self::STATUS_READY_FOR_DELIVERY => 'background-color: #FEF3C7; color: #B45309;',
            self::STATUS_CANCELLED => 'background-color: #FEE2E2; color: #B91C1C;',
            default => 'background-color: #F1F5F9; color: #334155;',
        };
    }

    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->booking_status]
            ?? ucfirst(str_replace('_', ' ', (string) $this->booking_status));
    }

    public function getCompletedOperationalStatuses(): array
    {
        $validKeys = array_keys(self::OPERATIONAL_STATUSES);
        $completed = [];

        foreach ($this->completed_operational_stages ?? [] as $stageKey => $meta) {
            $key = is_int($stageKey) ? (is_string($meta) ? $meta : null) : $stageKey;
            if ($key && in_array($key, $validKeys, true)) {
                $completed[$key] = true;
            }
        }

        $logs = $this->auditLogs()->whereIn('action', ['status_changed', 'stage_completed'])->get();

        foreach ($logs as $log) {
            $rawChanges = $log->getAttribute('changes');
            $changes = is_array($rawChanges) ? $rawChanges : [];
            foreach (['completed_stage', 'previous_status', 'before'] as $field) {
                $val = $changes[$field] ?? null;
                if ($val && in_array($val, $validKeys, true)) {
                    $completed[$val] = true;
                }
            }
        }

        return array_keys($completed);
    }

    public function getNextOperationalStatus(): ?string
    {
        $keys = array_keys(self::OPERATIONAL_STATUSES);
        $idx = array_search($this->booking_status, $keys, true);

        if ($idx === false) {
            return self::STATUS_PROCESSING;
        }

        return $keys[$idx + 1] ?? null;
    }

    public function isOperationalWorkflowFinished(): bool
    {
        return $this->booking_status === self::STATUS_COMPLETED
            && in_array(self::STATUS_COMPLETED, $this->getCompletedOperationalStatuses(), true);
    }

    public function getOperationalStageProgress(): array
    {
        $keys = array_keys(self::OPERATIONAL_STATUSES);
        $completedKeys = $this->getCompletedOperationalStatuses();
        $isFinished = $this->isOperationalWorkflowFinished();
        $currentIdx = array_search($this->booking_status, $keys, true);

        $stages = [];
        foreach (self::OPERATIONAL_STATUSES as $key => $label) {
            $isDone = in_array($key, $completedKeys, true);
            $isCurrent = ! $isFinished && $this->booking_status === $key;
            $stages[] = [
                'key' => $key,
                'label' => $label,
                'step' => array_search($key, $keys, true) + 1,
                'is_completed' => $isDone,
                'is_current' => $isCurrent,
                'state' => $isDone ? 'completed' : ($isCurrent ? 'current' : 'upcoming'),
            ];
        }

        return [
            'current_stage_key' => $this->booking_status,
            'current_stage_label' => $this->getStatusLabel(),
            'current_step_number' => $currentIdx === false ? 0 : ($currentIdx + 1),
            'completed_count' => count($completedKeys),
            'total_steps' => count($keys),
            'is_finished' => $isFinished,
            'is_cancelled' => $this->booking_status === self::STATUS_CANCELLED,
            'stages' => $stages,
        ];
    }

    protected $fillable = [
        'reference',
        'idempotency_key',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_type',
        'address_snapshot',
        'schedule_snapshot',
        'services_snapshot',
        'removed_operational_categories',
        'contact_snapshot',
        'payment_gateway',
        'payment_status',
        'booking_status',
        'completed_operational_stages',
        'is_locked',
        'version',
        'currency',
        'estimated_amount_minor',
        'service_fee_minor',
        'service_fee_override_mode',
        'collection_delivery_fee_minor',
        'delivery_fee_override_mode',
        'discount_minor',
        'tax_minor',
        'fee_snapshot',
        'payment_snapshot',
        'final_amount_minor',
        'actual_weight_kg',
        'item_quantities',
        'tip_amount_minor',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'stripe_setup_intent_id',
        'stripe_payment_intent_id',
        'paypal_order_id',
        'paypal_capture_id',
        'stripe_refund_id',
        'refund_status',
        'refunded_amount_minor',
        'policy_version_id',
        'consent_accepted',
        'consent_timestamp',
        'consent_text',
        'confirmed_at',
        'cancelled_at',
        'cancelled_by_admin_id',
        'cancellation_reason',
        'operational_notes',
        'paid_at',
        'last_payment_error',
    ];

    protected function casts(): array
    {
        return [
            'address_snapshot' => 'array',
            'schedule_snapshot' => 'array',
            'services_snapshot' => 'array',
            'removed_operational_categories' => 'array',
            'completed_operational_stages' => 'array',
            'contact_snapshot' => 'array',
            'fee_snapshot' => 'array',
            'payment_snapshot' => 'array',
            'item_quantities' => 'array',
            'is_locked' => 'boolean',
            'collection_otp_verified_at' => 'datetime',
            'version' => 'integer',
            'estimated_amount_minor' => 'integer',
            'service_fee_minor' => 'integer',
            'collection_delivery_fee_minor' => 'integer',
            'discount_minor' => 'integer',
            'tax_minor' => 'integer',
            'final_amount_minor' => 'integer',
            'actual_weight_kg' => 'decimal:2',
            'tip_amount_minor' => 'integer',
            'refunded_amount_minor' => 'integer',
            'consent_accepted' => 'boolean',
            'consent_timestamp' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(BookingAuditLog::class)->orderByDesc('id');
    }

    public function operationalItems(): HasMany
    {
        return $this->hasMany(BookingOperationalItem::class)->orderBy('id');
    }

    public function operationalCategories(): HasMany
    {
        return $this->hasMany(BookingOperationalCategory::class)->orderBy('id');
    }

    public function deliveryAssignments(): HasMany
    {
        return $this->hasMany(\Modules\Admin\Models\DeliveryAssignment::class)->orderByDesc('id');
    }

    public function assignedStore(): BelongsTo
    {
        return $this->belongsTo(\Modules\Admin\Models\Store::class, 'assigned_store_id');
    }

    public function storeAssignments(): HasMany
    {
        return $this->hasMany(\Modules\Admin\App\Models\BookingStoreAssignment::class)->orderByDesc('id');
    }

    public function storeEarnings(): HasMany
    {
        return $this->hasMany(BookingStoreEarning::class)->orderByDesc('id');
    }

    public function currentStoreEarning(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BookingStoreEarning::class)->where('is_current', true)->latest('id');
    }

    public function storeTransactions(): HasMany
    {
        return $this->hasMany(StoreTransaction::class)->orderByDesc('id');
    }

    public function financialSettlements(): HasMany
    {
        return $this->hasMany(BookingFinancialSettlement::class)->orderByDesc('id');
    }

    public function currentFinancialSettlement(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BookingFinancialSettlement::class)->where('is_current', true)->latest('id');
    }

    public function adminAccountTransactions(): HasMany
    {
        return $this->hasMany(AdminAccountTransaction::class)->orderByDesc('id');
    }

    public function collectionOtps(): HasMany
    {
        return $this->hasMany(BookingCollectionOtp::class)->orderByDesc('id');
    }

    public function customerPayments(): HasMany
    {
        return $this->hasMany(BookingCustomerPayment::class)->orderByDesc('id');
    }

    public function storeHandovers(): HasMany
    {
        return $this->hasMany(BookingStoreHandover::class)->orderByDesc('id');
    }

    public function currentStoreHandover(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BookingStoreHandover::class)->latest('id');
    }

    public function storePickupRequests(): HasMany
    {
        return $this->hasMany(BookingStorePickupRequest::class)->orderByDesc('id');
    }

    public function currentStorePickupRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BookingStorePickupRequest::class)
            ->whereNotIn('status', [BookingStorePickupRequest::STATUS_CANCELLED])
            ->latest('id');
    }

    public function latestStorePickupRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BookingStorePickupRequest::class)->latest('id');
    }

    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(RefundReturnPolicyVersion::class, 'policy_version_id');
    }

    public function claims(): HasMany
    {
        return $this->hasMany(CustomerServiceClaim::class, 'booking_id')->orderByDesc('id');
    }

    public function refundTransactions(): HasMany
    {
        return $this->hasMany(RefundTransaction::class, 'booking_id')->orderByDesc('id');
    }

    public function currentStoreAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\Modules\Admin\App\Models\BookingStoreAssignment::class)->where('is_current', true)->latest('id');
    }

    public function hasValidActiveStoreAssignment(): bool
    {
        return $this->currentStoreAssignment()
            ->whereHas('store', fn ($q) => $q->where('status', 'active')->whereNull('deleted_at'))
            ->exists();
    }

    public function currentCollectionAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\Modules\Admin\Models\DeliveryAssignment::class)
            ->where('assignment_type', \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)
            ->whereNotIn('status', [
                \Modules\Admin\Models\DeliveryAssignment::STATUS_CANCELLED,
                \Modules\Admin\Models\DeliveryAssignment::STATUS_FAILED,
            ])
            ->latest('id');
    }

    public function currentFinalDeliveryAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\Modules\Admin\Models\DeliveryAssignment::class)
            ->where('assignment_type', \Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY)
            ->whereNotIn('status', [
                \Modules\Admin\Models\DeliveryAssignment::STATUS_CANCELLED,
                \Modules\Admin\Models\DeliveryAssignment::STATUS_FAILED,
            ])
            ->latest('id');
    }

    public function hasValidCollectionCourierAssignment(): bool
    {
        return $this->currentCollectionAssignment()
            ->whereHas('deliveryPerson', fn ($q) => $q->where('work_status', 'active')->whereNull('deleted_at'))
            ->exists();
    }

    public function hasValidFinalDeliveryCourierAssignment(): bool
    {
        return $this->currentFinalDeliveryAssignment()
            ->whereHas('deliveryPerson', fn ($q) => $q->where('work_status', 'active')->whereNull('deleted_at'))
            ->exists();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(BookingInvoice::class)->orderByDesc('revision_number');
    }

    public function currentInvoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BookingInvoice::class)->where('is_current', true)->latest('revision_number');
    }

    public function isInvoiceEligible(CurrencyService $currencyService): bool
    {
        if ($this->booking_status === self::STATUS_CANCELLED) {
            return false;
        }

        $completedStages = $this->getCompletedOperationalStatuses();
        if (! in_array(self::STATUS_FINALIZED, $completedStages, true)) {
            return false;
        }

        $pricingSummary = $this->getOperationalPricingSummary($currencyService);

        return (bool) ($pricingSummary['is_pricing_complete'] ?? false);
    }

    public function getCustomerStatusGroup(): string
    {
        if ($this->booking_status === self::STATUS_CANCELLED) {
            return self::CUSTOMER_STATUS_CANCELLED;
        }

        $completedStages = $this->getCompletedOperationalStatuses();
        if (
            $this->booking_status === self::STATUS_COMPLETED
            || $this->booking_status === self::STATUS_DELIVERED
            || in_array(self::STATUS_COMPLETED, $completedStages, true)
        ) {
            return self::CUSTOMER_STATUS_COMPLETED;
        }

        if (
            in_array($this->booking_status, self::CUSTOMER_PENDING_BOOKING_STATUSES, true)
            && ! in_array(self::STATUS_PROCESSING, $completedStages, true)
        ) {
            return self::CUSTOMER_STATUS_PENDING;
        }

        return self::CUSTOMER_STATUS_IN_PROGRESS;
    }

    public function getCustomerStatusBadgeStyle(): array
    {
        return match ($this->getCustomerStatusGroup()) {
            self::CUSTOMER_STATUS_COMPLETED => ['label' => 'Completed', 'style' => 'background-color: #DCFCE7; color: #166534;'],
            self::CUSTOMER_STATUS_CANCELLED => ['label' => 'Cancelled', 'style' => 'background-color: #FEE2E2; color: #B91C1C;'],
            self::CUSTOMER_STATUS_PENDING => ['label' => 'Pending', 'style' => 'background-color: #FEF3C7; color: #92400E;'],
            default => ['label' => $this->getStatusLabel(), 'style' => 'background-color: #EDE9FE; color: #5B21B6;'],
        };
    }

    public static function associateVerifiedUserBookings(User $user): int
    {
        if (! $user->email_verified_at || trim((string) $user->email) === '') {
            return 0;
        }

        return self::query()
            ->whereNull('user_id')
            ->whereNotNull('confirmed_at')
            ->whereRaw('LOWER(customer_email) = ?', [strtolower(trim((string) $user->email))])
            ->update(['user_id' => $user->id]);
    }

    public function isConfirmedAndLocked(): bool
    {
        return $this->confirmed_at !== null && (bool) $this->is_locked;
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        if ($this->booking_status === $targetStatus) {
            return true;
        }

        $allowed = self::ALLOWED_STATUS_TRANSITIONS[$this->booking_status] ?? [];

        return in_array($targetStatus, $allowed, true);
    }

    public function syncOperationalItemsFromSnapshotIfEmpty(CurrencyService $currencyService): void
    {
        $this->cleanupUnpricedCategoryPlaceholders();

        if ($this->operationalItems()->exists()) {
            return;
        }

        $currency = strtoupper((string) ($this->currency ?: 'BDT'));
        $removedCatIds = $this->getRemovedOperationalCategoryIds();

        foreach ($this->services_snapshot ?? [] as $svc) {
            $catId = isset($svc['category_id']) ? (int) $svc['category_id'] : null;
            if ($catId && in_array($catId, $removedCatIds, true)) {
                continue;
            }
            $unitPrice = (float) ($svc['price'] ?? 0);
            $hasOptionService = ! empty($svc['option_service_id']);

            // Skip parent category placeholders that have no independently billable service/price
            if (! $hasOptionService && $unitPrice <= 0) {
                continue;
            }

            $unitStr = strtolower((string) ($svc['unit'] ?? $svc['unit_name'] ?? ''));
            $isWashFold = ! empty($svc['is_wash_fold'])
                || (($svc['pricing_mode'] ?? null) === 'weight')
                || str_contains($unitStr, 'kg')
                || str_contains($unitStr, 'kilogram');
            $unitMinor = $unitPrice > 0 ? $currencyService->toMinor($unitPrice, $currency) : 0;

            $this->operationalItems()->create([
                'item_category_id' => $svc['category_id'] ?? null,
                'service_id' => $svc['option_service_id'] ?? ($svc['service_id'] ?? null),
                'item_id' => null,
                'service_name' => (string) ($svc['option_name'] ?? $svc['name'] ?? 'Laundry Service'),
                'category_name' => (string) ($svc['name'] ?? ''),
                'item_name' => null,
                'pricing_mode' => $isWashFold ? 'weight' : 'item',
                'unit_name' => (string) ($svc['unit'] ?? ($isWashFold ? 'kg' : 'item')),
                'quantity' => 1,
                'weight_kg' => $isWashFold && $this->actual_weight_kg ? (float) $this->actual_weight_kg : null,
                'catalog_unit_price_minor' => $unitMinor,
                'actual_unit_price_minor' => $unitMinor,
                'line_total_minor' => 0,
            ]);
        }
    }

    public function cleanupUnpricedCategoryPlaceholders(): void
    {
        $placeholderNames = collect($this->services_snapshot ?? [])
            ->filter(fn ($svc) => empty($svc['option_service_id']) && (float) ($svc['price'] ?? 0) <= 0)
            ->map(fn ($svc) => (string) ($svc['name'] ?? ''))
            ->filter()
            ->values()
            ->all();

        if ($placeholderNames === []) {
            return;
        }

        $this->operationalItems()
            ->whereNull('item_id')
            ->where('catalog_unit_price_minor', 0)
            ->where('actual_unit_price_minor', 0)
            ->where('line_total_minor', 0)
            ->where('is_price_overridden', false)
            ->whereIn('service_name', $placeholderNames)
            ->delete();
    }

    public function getRemovedOperationalCategoryIds(): array
    {
        return collect($this->removed_operational_categories ?? [])
            ->pluck('category_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function getOperationalPendingReasons($items = null): array
    {
        $items = $items ?? $this->operationalItems()->get();
        $removedCatIds = $this->getRemovedOperationalCategoryIds();
        $reasons = [];

        foreach ($this->services_snapshot ?? [] as $snap) {
            $catId = isset($snap['category_id']) ? (int) $snap['category_id'] : null;
            if (! $catId || in_array($catId, $removedCatIds, true)) {
                continue;
            }
            if (! $items->contains(fn ($line) => (int) $line->item_category_id === $catId)) {
                $catName = (string) ($snap['name'] ?? 'Selected category');
                $reasons[] = "{$catName} has no selected items.";
            }
        }

        foreach ($items as $item) {
            if ($item->pricing_mode === 'weight') {
                if ((float) ($item->weight_kg ?? 0) <= 0) {
                    $reasons[] = "{$item->service_name} is missing measured weight (kg).";
                }
            } else {
                if ((int) $item->quantity <= 0) {
                    $reasons[] = "{$item->service_name} is missing quantity.";
                }
            }
        }

        if ($items->isEmpty() && $reasons === []) {
            $reasons[] = 'No active operational services.';
        }

        return array_values(array_unique($reasons));
    }

    public function getFinancialPaymentStatusLabel(): string
    {
        return match (strtolower((string) $this->payment_status)) {
            'paid' => 'Paid',
            'partially_paid' => 'Partially Paid',
            'authorized', 'authorized_for_later_charge' => 'Authorized',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            'partial_refund' => 'Partially Refunded',
            default => 'Unpaid',
        };
    }

    public function getPricingStatusLabel(array $pendingReasons): string
    {
        if ($pendingReasons === []) {
            return 'Finalized';
        }

        $joined = strtolower(implode(' ', $pendingReasons));
        if (str_contains($joined, 'weight')) {
            return 'Awaiting Weight';
        }
        if (str_contains($joined, 'selected items') || str_contains($joined, 'quantity')) {
            return 'Awaiting Item Count';
        }

        return 'Pricing Incomplete';
    }

    public function getOperationalPricingSummary(CurrencyService $currencyService, bool $persistLines = false): array
    {
        $items = $this->operationalItems()->get();
        $washFoldMinor = 0;
        $itemBasedMinor = 0;

        foreach ($items as $item) {
            $lineMinor = $item->computeLineTotalMinor();
            if ($persistLines && (int) $item->line_total_minor !== $lineMinor) {
                $item->line_total_minor = $lineMinor;
                $item->save();
            }

            if ($item->pricing_mode === 'weight') {
                $washFoldMinor += $lineMinor;
            } else {
                $itemBasedMinor += $lineMinor;
            }
        }

        $itemsSubtotalMinor = $washFoldMinor + $itemBasedMinor;
        $pendingReasons = $this->getOperationalPendingReasons($items);
        $hasPendingPricing = count($pendingReasons) > 0;

        $serviceFeeMinor = (int) $this->service_fee_minor;
        $deliveryFeeMinor = (int) $this->collection_delivery_fee_minor;
        $tipMinor = (int) $this->tip_amount_minor;
        $taxMinor = (int) $this->tax_minor;
        $discountMinor = (int) $this->discount_minor;

        $calculatedTotalMinor = max(
            0,
            $itemsSubtotalMinor + $serviceFeeMinor + $deliveryFeeMinor + $tipMinor + $taxMinor - $discountMinor
        );

        return [
            'wash_fold_minor' => $washFoldMinor,
            'item_based_minor' => $itemBasedMinor,
            'items_subtotal_minor' => $itemsSubtotalMinor,
            'service_fee_minor' => $serviceFeeMinor,
            'delivery_fee_minor' => $deliveryFeeMinor,
            'tip_minor' => $tipMinor,
            'tax_minor' => $taxMinor,
            'discount_minor' => $discountMinor,
            'calculated_total_minor' => $calculatedTotalMinor,
            'final_total_minor' => $hasPendingPricing ? null : $calculatedTotalMinor,
            'has_pending_pricing' => $hasPendingPricing,
            'is_pricing_complete' => ! $hasPendingPricing,
            'pending_reasons' => $pendingReasons,
            'pricing_status_label' => $this->getPricingStatusLabel($pendingReasons),
            'payment_status_label' => $this->getFinancialPaymentStatusLabel(),
        ];
    }

    public function recalculateOperationalTotals(CurrencyService $currencyService): array
    {
        $summary = $this->getOperationalPricingSummary($currencyService, true);

        $this->final_amount_minor = $summary['final_total_minor'];
        if ($summary['is_pricing_complete'] && in_array($this->payment_status, ['pending_item_count', 'pending_weight'], true)) {
            $this->payment_status = 'pending';
        }
        $this->save();

        return $summary;
    }

    public function getActiveOperationalCategoryGroups(): array
    {
        $removedCatIds = $this->getRemovedOperationalCategoryIds();
        $operationalItems = $this->relationLoaded('operationalItems')
            ? $this->operationalItems
            : $this->operationalItems()->with(['category', 'item'])->get();

        $groups = [];

        foreach ($this->services_snapshot ?? [] as $snap) {
            $catId = isset($snap['category_id']) ? (int) $snap['category_id'] : null;
            if ($catId && in_array($catId, $removedCatIds, true)) {
                continue;
            }
            $catName = (string) ($snap['name'] ?? 'Service Category');
            $key = $catId ? 'cat_' . $catId : 'snap_' . md5(strtolower($catName));

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'category_id' => $catId,
                    'category_name' => $catName,
                    'source' => 'customer',
                    'items' => collect(),
                ];
            }
        }

        foreach ($operationalItems as $opItem) {
            $catId = $opItem->item_category_id ? (int) $opItem->item_category_id : null;
            if ($catId && in_array($catId, $removedCatIds, true)) {
                continue;
            }
            $catName = (string) ($opItem->category_name ?: ($opItem->category?->name ?? 'Additional Service'));
            $key = $catId ? 'cat_' . $catId : 'snap_' . md5(strtolower($catName));

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'category_id' => $catId,
                    'category_name' => $catName,
                    'source' => 'admin',
                    'items' => collect(),
                ];
            }

            $groups[$key]['items']->push($opItem);
        }

        return array_values($groups);
    }

    public static function generateUniqueReference(): string
    {
        $year = (int) now()->format('Y');

        return DB::transaction(function () use ($year) {
            $seq = DB::table('booking_sequences')->where('year', $year)->lockForUpdate()->first();

            if (! $seq) {
                DB::table('booking_sequences')->insertOrIgnore([
                    'year' => $year,
                    'last_number' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $seq = DB::table('booking_sequences')->where('year', $year)->lockForUpdate()->first();
            }

            $next = ((int) $seq->last_number) + 1;
            $reference = sprintf('LND-%d-%06d', $year, $next);

            while (self::where('reference', $reference)->exists()) {
                $next++;
                $reference = sprintf('LND-%d-%06d', $year, $next);
            }

            DB::table('booking_sequences')->where('year', $year)->update([
                'last_number' => $next,
                'updated_at' => now(),
            ]);

            return $reference;
        });
    }

    public function getFinalAmountFormattedAttribute(): ?string
    {
        if ($this->final_amount_minor === null) {
            return null;
        }

        return app(CurrencyService::class)->formatMinor((int) $this->final_amount_minor, $this->currency);
    }
}
