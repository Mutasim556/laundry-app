<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingTimeSlotWeekday extends Model
{
    public $timestamps = false;

    protected $table = 'booking_time_slot_weekdays';

    protected $fillable = [
        'time_slot_id',
        'weekday',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
        ];
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(BookingTimeSlot::class, 'time_slot_id');
    }
}
