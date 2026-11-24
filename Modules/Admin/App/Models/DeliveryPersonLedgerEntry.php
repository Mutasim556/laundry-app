<?php

namespace Modules\Admin\Models;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPersonLedgerEntry extends Model
{
    protected $table = 'delivery_person_ledger_entries';

    public const TYPE_ASSIGNMENT_EARNING = 'assignment_earning';
    public const TYPE_EARNING_ADJUSTMENT = 'earning_adjustment';
    public const TYPE_PAYOUT = 'payout';
    public const TYPE_PAYOUT_REVERSAL = 'payout_reversal';
    public const TYPE_EARNING_REVERSAL = 'earning_reversal';
    public const TYPE_AUTHORIZED_CORRECTION = 'authorized_correction';

    protected $fillable = [
        'entry_reference',
        'delivery_person_id',
        'delivery_assignment_id',
        'booking_id',
        'transaction_type',
        'direction',
        'amount_minor',
        'currency',
        'notes',
        'created_by_admin_id',
        'occurred_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'occurred_at' => 'datetime',
    ];

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class)->withTrashed();
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignment::class, 'delivery_assignment_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }
}
