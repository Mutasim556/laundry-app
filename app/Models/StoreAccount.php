<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admin\Models\Store;

class StoreAccount extends Model
{
    protected $fillable = [
        'store_id',
        'currency',
        'current_balance',
        'total_credited',
        'total_debited',
    ];

    protected function casts(): array
    {
        return [
            'current_balance' => 'decimal:2',
            'total_credited' => 'decimal:2',
            'total_debited' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StoreTransaction::class, 'store_id', 'store_id')
            ->where('currency', $this->currency);
    }
}
