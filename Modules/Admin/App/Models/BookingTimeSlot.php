<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingTimeSlot extends Model
{
    protected $table = 'booking_time_slots';

    protected $fillable = [
        'type',
        'name',
        'label',
        'start_time',
        'end_time',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function weekdays(): HasMany
    {
        return $this->hasMany(BookingTimeSlotWeekday::class, 'time_slot_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForWeekday(Builder $query, int $weekday): Builder
    {
        return $query->whereHas('weekdays', fn (Builder $q) => $q->where('weekday', $weekday));
    }

    public function syncWeekdays(array $weekdays): void
    {
        $clean = collect($weekdays)
            ->map(fn ($d) => (int) $d)
            ->filter(fn (int $d) => $d >= 0 && $d <= 6)
            ->unique()
            ->values();

        $this->weekdays()->delete();
        foreach ($clean as $day) {
            $this->weekdays()->create(['weekday' => $day]);
        }
    }
}
