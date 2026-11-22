<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'customer_email',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'card_brand',
        'card_last4',
        'card_exp_month',
        'card_exp_year',
        'is_default',
        'consent_timestamp',
    ];

    protected function casts(): array
    {
        return [
            'card_exp_month' => 'integer',
            'card_exp_year' => 'integer',
            'is_default' => 'boolean',
            'consent_timestamp' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayLabelAttribute(): string
    {
        $brand = strtoupper($this->card_brand);
        $exp = str_pad((string) $this->card_exp_month, 2, '0', STR_PAD_LEFT) . '/' . substr((string) $this->card_exp_year, -2);

        return "{$brand} •••• {$this->card_last4} (Exp {$exp})";
    }
}
