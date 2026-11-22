<?php

namespace Modules\Admin\App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewayAuditLog extends Model
{
    protected $table = 'payment_gateway_audit_logs';

    protected $fillable = [
        'gateway_code',
        'admin_id',
        'admin_name',
        'action',
        'summary',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
