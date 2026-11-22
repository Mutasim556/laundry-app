<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BookingAvailabilitySetting extends Model
{
    protected $table = 'booking_availability_settings';

    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    public const DEFAULTS = [
        'collection_weekdays' => '[0,1,2,3,4,5,6]',
        'delivery_weekdays' => '[0,1,2,3,4,5,6]',
        'same_day_collection' => '1',
        'min_booking_notice_minutes' => '60',
        'max_advance_days' => '14',
        'min_delivery_turnaround_days' => '1',
        'timezone' => 'Europe/London',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $all = self::getAllSettings();

        return $all[$key] ?? ($default ?? (self::DEFAULTS[$key] ?? null));
    }

    public static function setValue(string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => is_array($value) ? json_encode(array_values($value)) : (string) $value]
        );

        Cache::forget('booking_availability_settings.all');
    }

    public static function getAllSettings(): array
    {
        return Cache::remember('booking_availability_settings.all', 3600, function () {
            $rows = self::query()->pluck('setting_value', 'setting_key')->all();

            return array_merge(self::DEFAULTS, $rows);
        });
    }

    public static function getWeekdays(string $key): array
    {
        $raw = self::getValue($key, '[0,1,2,3,4,5,6]');
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

        if (! is_array($decoded)) {
            return [0, 1, 2, 3, 4, 5, 6];
        }

        return array_values(array_unique(array_map('intval', $decoded)));
    }
}
