<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookingBlockedDate extends Model
{
    protected $table = 'booking_blocked_dates';

    protected $fillable = [
        'date',
        'type',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->whereIn('type', [$type, 'both']);
    }
}
