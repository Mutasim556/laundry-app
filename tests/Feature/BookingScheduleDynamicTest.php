<?php

use Modules\Admin\Models\Admin;
use Modules\Admin\Models\BookingAvailabilitySetting;
use Modules\Admin\Models\BookingBlockedDate;
use Modules\Admin\Models\BookingDriverInstruction;
use Modules\Admin\Models\BookingTimeSlot;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'view-booking-schedule', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage-booking-schedule', 'guard_name' => 'admin']);
});

test('authorized admin can render booking schedule index page', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-booking-schedule', 'manage-booking-schedule']);

    $slot = BookingTimeSlot::create([
        'type' => 'collection',
        'label' => '08:00 - 11:00',
        'start_time' => '08:00',
        'end_time' => '11:00',
        'is_active' => true,
    ]);
    $slot->syncWeekdays([1, 2, 3]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.booking-schedule.index'))
        ->assertOk()
        ->assertSee('Booking Schedule Management')
        ->assertSee('08:00 - 11:00');
});

test('unauthenticated and unauthorized admins cannot manage booking schedule', function () {
    $this->get(route('admin.booking-schedule.index'))
        ->assertRedirect(route('admin.login'));

    $unauthorized = Admin::factory()->create();
    $this->actingAs($unauthorized, 'admin')
        ->get(route('admin.booking-schedule.index'))
        ->assertStatus(403);

    $this->actingAs($unauthorized, 'admin')
        ->postJson(route('admin.booking-schedule.slots.store'), [])
        ->assertStatus(403);
});

test('admin collection and delivery time slots dynamically control customer available slots', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-booking-schedule', 'manage-booking-schedule']);

    $targetDate = now()->addDay()->format('Y-m-d');

    // Add collection slot
    $colResp = $this->actingAs($admin, 'admin')->postJson(route('admin.booking-schedule.slots.store'), [
        'type' => 'collection',
        'start_time' => '10:00',
        'end_time' => '13:00',
        'weekdays' => [0, 1, 2, 3, 4, 5, 6],
        'is_active' => 1,
    ]);
    $colResp->assertCreated();
    $colSlotId = $colResp->json('slot.id');

    // Add delivery slot
    $delResp = $this->actingAs($admin, 'admin')->postJson(route('admin.booking-schedule.slots.store'), [
        'type' => 'delivery',
        'start_time' => '18:00',
        'end_time' => '21:00',
        'weekdays' => [0, 1, 2, 3, 4, 5, 6],
        'is_active' => 1,
    ]);
    $delResp->assertCreated();

    // Verify customer API returns new slots
    $this->getJson(route('booking.available-slots', ['type' => 'collection', 'date' => $targetDate]))
        ->assertOk()
        ->assertJsonFragment(['label' => '10:00 - 13:00']);

    $this->getJson(route('booking.available-slots', ['type' => 'delivery', 'date' => $targetDate]))
        ->assertOk()
        ->assertJsonFragment(['label' => '18:00 - 21:00']);

    // Deactivate collection slot
    $this->actingAs($admin, 'admin')
        ->patchJson(route('admin.booking-schedule.slots.toggle', $colSlotId))
        ->assertOk()
        ->assertJson(['is_active' => false]);

    // Disappears from customer lookup
    $after = $this->getJson(route('booking.available-slots', ['type' => 'collection', 'date' => $targetDate]));
    expect(collect($after->json('slots'))->pluck('label')->all())->not->toContain('10:00 - 13:00');
});

test('availability weekdays and blocked dates filter customer available dates and reject blocked submissions', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-booking-schedule', 'manage-booking-schedule']);

    // Create active collection and delivery slots for all days
    $colSlot = BookingTimeSlot::create([
        'type' => 'collection',
        'label' => '09:00 - 12:00',
        'start_time' => '09:00',
        'end_time' => '12:00',
        'is_active' => true,
    ]);
    $colSlot->syncWeekdays([0, 1, 2, 3, 4, 5, 6]);

    $delSlot = BookingTimeSlot::create([
        'type' => 'delivery',
        'label' => '12:00 - 15:00',
        'start_time' => '12:00',
        'end_time' => '15:00',
        'is_active' => true,
    ]);
    $delSlot->syncWeekdays([0, 1, 2, 3, 4, 5, 6]);

    $blockedDate = now()->addDays(2)->format('Y-m-d');

    $this->actingAs($admin, 'admin')->postJson(route('admin.booking-schedule.blocked-dates.store'), [
        'date' => $blockedDate,
        'type' => 'both',
        'reason' => 'Bank Holiday',
    ])->assertCreated();

    $datesResp = $this->getJson(route('booking.available-dates', ['type' => 'collection']))->assertOk();
    expect(collect($datesResp->json('dates'))->pluck('value')->all())->not->toContain($blockedDate);

    // Restrict collection weekdays to only one specific weekday
    $allowedWd = (int) now()->addDay()->dayOfWeek;
    $this->actingAs($admin, 'admin')->postJson(route('admin.booking-schedule.settings.update'), [
        'collection_weekdays' => [$allowedWd],
        'delivery_weekdays' => [0, 1, 2, 3, 4, 5, 6],
        'same_day_collection' => 1,
        'min_booking_notice_minutes' => 60,
        'max_advance_days' => 14,
        'min_delivery_turnaround_days' => 1,
        'timezone' => 'Europe/London',
    ])->assertOk();

    $wdResp = $this->getJson(route('booking.available-dates', ['type' => 'collection']))->assertOk();
    $returnedWeekdays = collect($wdResp->json('dates'))->pluck('weekday')->unique()->all();
    expect($returnedWeekdays)->toBe([$allowedWd]);

    // Submitting a blocked date fails validation
    $this->withSession([
        'booking' => [
            'services' => [['service_id' => 1, 'name' => 'Wash & Fold', 'quantity' => 1, 'price' => 100]],
            'address' => ['selected_address' => '10 DOWNING ST, LONDON, SW1A 2AA', 'address_type' => 'HOME'],
        ],
    ])->post(route('booking.schedule.store'), [
        'collection_date' => $blockedDate,
        'collection_slot' => '09:00 - 12:00',
        'delivery_date' => now()->addDays(4)->format('Y-m-d'),
        'delivery_slot' => '12:00 - 15:00',
    ])->assertSessionHasErrors(['collection_date']);
});

test('driver instructions filter by type and address type and show warning if deactivated', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo(['view-booking-schedule', 'manage-booking-schedule']);

    $this->actingAs($admin, 'admin')->postJson(route('admin.booking-schedule.instructions.store'), [
        'title' => 'Collect from building concierge',
        'type' => 'collection',
        'allowed_address_types' => ['HOTEL', 'OFFICE'],
        'is_active' => 1,
    ])->assertCreated();

    $this->actingAs($admin, 'admin')->postJson(route('admin.booking-schedule.instructions.store'), [
        'title' => 'Ring doorbell twice',
        'type' => 'collection',
        'allowed_address_types' => ['HOME'],
        'is_active' => 1,
    ])->assertCreated();

    $hotelInstr = $this->getJson(route('booking.driver-instructions', ['type' => 'collection', 'address_type' => 'HOTEL']))->assertOk();
    $hotelTitles = collect($hotelInstr->json('instructions'))->pluck('title')->all();
    expect($hotelTitles)->toContain('Collect from building concierge');
    expect($hotelTitles)->not->toContain('Ring doorbell twice');

    // Warning shown on Step 3 if previously saved slot was deactivated
    $slot = BookingTimeSlot::create([
        'type' => 'collection',
        'label' => '15:00 - 18:00',
        'start_time' => '15:00',
        'end_time' => '18:00',
        'is_active' => false,
    ]);
    $slot->syncWeekdays([0, 1, 2, 3, 4, 5, 6]);

    BookingTimeSlot::create([
        'type' => 'collection',
        'label' => '08:00 - 11:00',
        'start_time' => '08:00',
        'end_time' => '11:00',
        'is_active' => true,
    ])->syncWeekdays([0, 1, 2, 3, 4, 5, 6]);

    $step3 = $this->withSession([
        'booking' => [
            'services' => [['service_id' => 1, 'name' => 'Wash & Fold', 'quantity' => 1, 'price' => 100]],
            'address' => ['selected_address' => '10 DOWNING ST, LONDON, SW1A 2AA', 'address_type' => 'HOME'],
            'schedule' => [
                'collection_date' => now()->addDay()->format('Y-m-d'),
                'collection_slot' => '15:00 - 18:00',
                'delivery_date' => now()->addDays(2)->format('Y-m-d'),
                'delivery_slot' => '08:00 - 11:00',
            ],
        ],
    ])->get(route('booking.schedule'));

    $step3->assertOk();
    $step3->assertSee('is no longer available');
});
