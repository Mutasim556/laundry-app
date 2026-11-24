<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admin\Models\Store;

class StoreBankAccount extends Model
{
    protected $table = 'store_bank_accounts';

    public const VERIFICATION_UNVERIFIED = 'unverified';
    public const VERIFICATION_PENDING = 'pending_verification';
    public const VERIFICATION_VERIFIED = 'verified';

    protected $fillable = [
        'store_id',
        'account_holder_name',
        'bank_name',
        'country_code',
        'currency',
        'account_type',
        'account_number_encrypted',
        'account_number_last4',
        'account_number_masked',
        'iban_encrypted',
        'routing_code_encrypted',
        'routing_code_label',
        'swift_bic',
        'is_default',
        'is_active',
        'verification_status',
        'archived_at',
    ];

    protected $hidden = [
        'account_number_encrypted',
        'iban_encrypted',
        'routing_code_encrypted',
    ];

    protected $casts = [
        'account_number_encrypted' => 'encrypted',
        'iban_encrypted' => 'encrypted',
        'routing_code_encrypted' => 'encrypted',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(StoreWithdrawalRequest::class, 'bank_account_id');
    }

    public function toSafeArray(): array
    {
        return [
            'id' => $this->id,
            'account_holder_name' => $this->account_holder_name,
            'bank_name' => $this->bank_name,
            'country_code' => $this->country_code,
            'currency' => $this->currency,
            'account_type' => $this->account_type,
            'account_number_last4' => $this->account_number_last4,
            'account_number_masked' => $this->account_number_masked,
            'routing_code_label' => $this->routing_code_label,
            'has_iban' => ! empty($this->iban_encrypted),
            'has_routing_code' => ! empty($this->routing_code_encrypted),
            'swift_bic' => $this->swift_bic,
            'is_default' => (bool) $this->is_default,
            'is_active' => (bool) $this->is_active,
            'verification_status' => $this->verification_status,
            'verification_label' => match ($this->verification_status) {
                self::VERIFICATION_VERIFIED => 'Verified',
                self::VERIFICATION_PENDING => 'Pending Verification',
                default => 'Saved (Unverified)',
            },
            'display_label' => "{$this->bank_name} ({$this->account_number_masked})",
        ];
    }
}
