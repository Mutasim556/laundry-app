<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Models\BookingAvailabilitySetting;
use Modules\Admin\Models\BookingDriverInstruction;
use Modules\Admin\Models\BookingTimeSlot;

class BookingScheduleSeeder extends Seeder
{
    /**
     * Seed default collection & delivery time slots, driver instructions, and availability settings.
     * Preserves any existing records without overwriting them.
     */
    public function run(): void
    {
        if (BookingTimeSlot::count() === 0) {
            $defaultSlots = [
                ['label' => '08:00 - 11:00', 'start_time' => '08:00', 'end_time' => '11:00', 'sort_order' => 1],
                ['label' => '11:00 - 14:00', 'start_time' => '11:00', 'end_time' => '14:00', 'sort_order' => 2],
                ['label' => '14:00 - 17:00', 'start_time' => '14:00', 'end_time' => '17:00', 'sort_order' => 3],
                ['label' => '17:00 - 20:00', 'start_time' => '17:00', 'end_time' => '20:00', 'sort_order' => 4],
                ['label' => '20:00 - 23:00', 'start_time' => '20:00', 'end_time' => '23:00', 'sort_order' => 5],
            ];

            foreach (['collection', 'delivery'] as $type) {
                foreach ($defaultSlots as $slotData) {
                    $slot = BookingTimeSlot::create([
                        'type' => $type,
                        'name' => ucfirst($type) . ' ' . $slotData['label'],
                        'label' => $slotData['label'],
                        'start_time' => $slotData['start_time'],
                        'end_time' => $slotData['end_time'],
                        'is_active' => true,
                        'sort_order' => $slotData['sort_order'],
                    ]);
                    $slot->syncWeekdays([0, 1, 2, 3, 4, 5, 6]);
                }
            }
        }

        if (BookingDriverInstruction::count() === 0) {
            $instructions = [
                [
                    'title' => 'Collect from me in person',
                    'description' => 'Driver will meet you directly at your door or lobby.',
                    'type' => 'collection',
                    'allowed_address_types' => ['HOME', 'OFFICE', 'HOTEL'],
                    'sort_order' => 1,
                ],
                [
                    'title' => 'Collect from reception',
                    'description' => 'Leave your laundry bag with reception for collection.',
                    'type' => 'collection',
                    'allowed_address_types' => ['HOTEL', 'OFFICE'],
                    'sort_order' => 2,
                ],
                [
                    'title' => 'Leave at a designated collection point',
                    'description' => 'Place your bag at a safe, agreed collection spot.',
                    'type' => 'collection',
                    'allowed_address_types' => ['HOME', 'OFFICE'],
                    'sort_order' => 3,
                ],
                [
                    'title' => 'Deliver to me in person',
                    'description' => 'Driver will hand the cleaned order directly to you.',
                    'type' => 'delivery',
                    'allowed_address_types' => ['HOME', 'OFFICE', 'HOTEL'],
                    'sort_order' => 1,
                ],
                [
                    'title' => 'Deliver to reception',
                    'description' => 'Driver will leave the cleaned order at reception.',
                    'type' => 'delivery',
                    'allowed_address_types' => ['HOTEL', 'OFFICE'],
                    'sort_order' => 2,
                ],
                [
                    'title' => 'Leave at an approved delivery location',
                    'description' => 'Driver will leave the order at your approved safe spot.',
                    'type' => 'delivery',
                    'allowed_address_types' => ['HOME', 'OFFICE'],
                    'sort_order' => 3,
                ],
            ];

            foreach ($instructions as $item) {
                BookingDriverInstruction::create(array_merge($item, ['is_active' => true]));
            }
        }

        foreach (BookingAvailabilitySetting::DEFAULTS as $key => $val) {
            BookingAvailabilitySetting::firstOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $val]
            );
        }
    }
}
