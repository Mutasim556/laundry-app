<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryPerson;

class DeliveryPersonApplication extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_ADDITIONAL_INFO_REQUIRED = 'additional_info_required';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'reference',
        'tracking_token_hash',
        'full_name',
        'email',
        'phone_country_code',
        'phone',
        'phone_normalized',
        'date_of_birth',
        'address_line_1',
        'address_line_2',
        'city',
        'postcode',
        'country_code',
        'country_name',
        'service_area',
        'delivery_method',
        'vehicle_type',
        'vehicle_registration',
        'driving_licence_number',
        'experience_notes',
        'password',
        'consent_accepted',
        'status',
        'submitted_at',
        'reviewed_by_admin_id',
        'reviewed_at',
        'decision_notes',
        'applicant_message',
        'additional_info_requested',
        'approved_delivery_person_id',
        'ip_address',
        'user_agent',
    ];

    protected $hidden = [
        'password',
        'tracking_token_hash',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'consent_accepted' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(DeliveryPersonApplicationDocument::class, 'application_id')
            ->where('is_active', true)
            ->orderByDesc('id');
    }

    public function allDocuments(): HasMany
    {
        return $this->hasMany(DeliveryPersonApplicationDocument::class, 'application_id')
            ->orderByDesc('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeliveryPersonApplicationEvent::class, 'application_id')
            ->orderByDesc('id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }

    public function approvedDeliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class, 'approved_delivery_person_id');
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_ADDITIONAL_INFO_REQUIRED,
        ], true);
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_WITHDRAWN,
        ], true);
    }

    public function recordEvent(
        string $eventType,
        string $actorType = 'system',
        ?int $actorId = null,
        ?string $notes = null,
        array $metadata = []
    ): DeliveryPersonApplicationEvent {
        return $this->events()->create([
            'event_type' => $eventType,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'notes' => $notes,
            'metadata' => empty($metadata) ? null : $metadata,
            'created_at' => now(),
        ]);
    }

    public static function generateReference(): string
    {
        do {
            $ref = 'DPA-' . now()->format('Ym') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (self::where('reference', $ref)->exists());

        return $ref;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_ADDITIONAL_INFO_REQUIRED => 'Additional Information Required',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_WITHDRAWN => 'Withdrawn',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
