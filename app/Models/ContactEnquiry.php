<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\Admin;

class ContactEnquiry extends Model
{
    protected $table = 'contact_enquiries';

    public const STATUS_NEW = 'new';
    public const STATUS_READ = 'read';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'reference',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'booking_reference',
        'booking_id',
        'status',
        'internal_notes',
        'assigned_admin_id',
        'idempotency_key',
        'ip_address',
        'user_agent',
        'email_notification_status',
        'email_notification_error',
        'submitted_at',
        'read_at',
        'resolved_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'read_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    public function statusMeta(): array
    {
        return match ($this->status) {
            self::STATUS_NEW => ['label' => 'New', 'badge' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'],
            self::STATUS_READ => ['label' => 'Read', 'badge' => 'bg-info-subtle text-info-emphasis border border-info-subtle'],
            self::STATUS_IN_PROGRESS => ['label' => 'In Progress', 'badge' => 'bg-primary-subtle text-primary border border-primary-subtle'],
            self::STATUS_RESOLVED => ['label' => 'Resolved', 'badge' => 'bg-success-subtle text-success border border-success-subtle'],
            default => ['label' => ucfirst((string) $this->status), 'badge' => 'bg-light text-dark border'],
        };
    }
}
