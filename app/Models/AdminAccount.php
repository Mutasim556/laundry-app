<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminAccount extends Model
{
    protected $fillable = [
        'currency',
        'opening_balance',
        'current_balance',
        'total_credited',
        'total_debited',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'total_credited' => 'decimal:2',
            'total_debited' => 'decimal:2',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AdminAccountTransaction::class)->orderByDesc('id');
    }
}
