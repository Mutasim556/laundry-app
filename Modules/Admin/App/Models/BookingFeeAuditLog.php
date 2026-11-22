<?php

namespace Modules\Admin\App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingFeeAuditLog extends Model
{
    protected $table = 'booking_fee_audit_logs';

    protected $fillable = [
        'fee_code',
        'admin_id',
        'admin_name',
        'action',
        'summary',
        'changes',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
    ];
}
