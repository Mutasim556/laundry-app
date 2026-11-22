<?php

namespace Modules\Admin\App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyChangeAuditLog extends Model
{
    protected $table = 'currency_change_audit_logs';

    protected $fillable = [
        'admin_id',
        'admin_name',
        'from_country',
        'to_country',
        'from_currency',
        'to_currency',
        'repricing_policy',
        'exchange_rate',
        'rate_source',
        'affected_services_count',
        'summary',
        'ip_address',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'affected_services_count' => 'integer',
    ];
}
