<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPersonPayout extends Model
{
    protected $table = 'delivery_person_payouts';

    protected $fillable = [
        'payout_reference',
        'delivery_person_id',
        'ledger_entry_id',
        'amount_minor',
        'currency',
        'payment_method',
        'external_reference',
        'payment_date',
        'status',
        'notes',
        'idempotency_key',
        'recorded_by_admin_id',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'payment_date' => 'date',
    ];

    public function deliveryPerson(): BelongsTo
    {
        return $this->belongsTo(DeliveryPerson::class)->withTrashed();
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(DeliveryPersonLedgerEntry::class, 'ledger_entry_id');
    }

    public function recordedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'recorded_by_admin_id');
    }
}
