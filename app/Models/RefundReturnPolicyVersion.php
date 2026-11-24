<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;

class RefundReturnPolicyVersion extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'policy_id',
        'version_number',
        'status',
        'rules_json',
        'public_text',
        'legal_disclaimer_note',
        'effective_at',
        'expires_at',
        'published_by_admin_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'rules_json' => 'array',
            'effective_at' => 'datetime',
            'expires_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(RefundReturnPolicy::class, 'policy_id');
    }

    public function publishedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'published_by_admin_id');
    }

    public static function defaultRules(): array
    {
        return [
            'policy_enabled' => true,
            'customer_cancel_enabled' => true,
            'cancel_cutoff_hours_before_collection' => 2,
            'cancel_allowed_before_stages' => [
                Booking::STATUS_PROCESSING,
                Booking::STATUS_STORE_ASSIGNED,
                Booking::STATUS_DELIVERY_MAN_ASSIGNED,
                Booking::STATUS_FINALIZED,
            ],
            'stage_refund_percentages' => [
                Booking::STATUS_PROCESSING => 100,
                Booking::STATUS_STORE_ASSIGNED => 100,
                Booking::STATUS_DELIVERY_MAN_ASSIGNED => 90,
                Booking::STATUS_FINALIZED => 75,
                Booking::STATUS_DELIVERY_MAN_PICKED_UP => 50,
            ],
            'fixed_deduction_minor' => 0,
            'auto_approve_max_minor' => 0,
            'manual_review_always_required' => true,
            'rewash_enabled' => true,
            'rewash_deadline_hours_after_delivery' => 72,
            'rewash_pickup_included' => true,
            'rewash_max_attempts' => 1,
            'rewash_allowed_reasons' => [
                'stain_remains' => 'Stain Remains',
                'poor_cleaning_quality' => 'Poor Cleaning Quality',
                'incorrect_ironing' => 'Incorrect Ironing',
                'incorrect_folding' => 'Incorrect Folding',
                'incomplete_service' => 'Incomplete Service',
                'wrong_service_performed' => 'Wrong Service Performed',
            ],
            'claims_enabled' => true,
            'damage_claim_deadline_hours' => 48,
            'missing_claim_deadline_hours' => 48,
            'evidence_required' => true,
            'max_auto_compensation_minor' => 0,
            'allowed_resolutions' => ['full_refund', 'partial_refund', 'free_rewash', 'item_replacement_credit'],
            'cancellation_reasons' => [
                'changed_plans' => 'Plans Changed / No Longer Needed',
                'schedule_conflict' => 'Pickup Schedule Conflict',
                'duplicate_booking' => 'Duplicate Booking',
                'service_not_performed' => 'Service Not Performed',
                'other' => 'Other Reason',
            ],
            'claim_reasons' => [
                'damaged_garment' => 'Damaged Garment',
                'missing_garment' => 'Missing Garment',
                'wrong_garment' => 'Wrong Garment Delivered',
                'mixed_order' => 'Mixed With Another Customer Order',
                'incomplete_delivery' => 'Incomplete Delivery',
                'unacceptable_condition' => 'Returned in Unacceptable Condition',
            ],
        ];
    }
}
