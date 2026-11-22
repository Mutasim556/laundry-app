<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Models\BookingAvailabilitySetting;
use Modules\Admin\Models\BookingBlockedDate;
use Modules\Admin\Models\BookingDriverInstruction;
use Modules\Admin\Models\BookingTimeSlot;

class BookingScheduleController extends Controller
{
    public function index(): View
    {
        $collectionSlots = BookingTimeSlot::with('weekdays')
            ->ofType('collection')
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get();

        $deliverySlots = BookingTimeSlot::with('weekdays')
            ->ofType('delivery')
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get();

        $instructions = BookingDriverInstruction::query()
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $settings = BookingAvailabilitySetting::getAllSettings();
        $collectionWeekdays = BookingAvailabilitySetting::getWeekdays('collection_weekdays');
        $deliveryWeekdays = BookingAvailabilitySetting::getWeekdays('delivery_weekdays');

        $blockedDates = BookingBlockedDate::query()
            ->orderBy('date')
            ->get();

        $weekdayNames = [
            0 => 'Sun',
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
        ];

        return view('admin::booking-schedule.index', compact(
            'collectionSlots',
            'deliverySlots',
            'instructions',
            'settings',
            'collectionWeekdays',
            'deliveryWeekdays',
            'blockedDates',
            'weekdayNames'
        ));
    }

    public function storeSlot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['collection', 'delivery'])],
            'name' => ['nullable', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'label' => ['nullable', 'string', 'max:60'],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'end_time.after' => 'End time must be after start time.',
            'weekdays.required' => 'Please select at least one available weekday.',
        ]);

        $label = trim((string) ($validated['label'] ?? ''));
        if ($label === '') {
            $label = $validated['start_time'] . ' - ' . $validated['end_time'];
        }

        $slot = BookingTimeSlot::create([
            'type' => $validated['type'],
            'name' => $validated['name'] ?? null,
            'label' => $label,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        $slot->syncWeekdays($validated['weekdays']);

        return response()->json([
            'success' => true,
            'message' => ucfirst($slot->type) . " slot '{$slot->label}' added.",
            'slot' => $slot->load('weekdays'),
        ], 201);
    }

    public function updateSlot(Request $request, BookingTimeSlot $slot): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'label' => ['nullable', 'string', 'max:60'],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $label = trim((string) ($validated['label'] ?? ''));
        if ($label === '') {
            $label = $validated['start_time'] . ' - ' . $validated['end_time'];
        }

        $slot->update([
            'name' => $validated['name'] ?? null,
            'label' => $label,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'sort_order' => (int) ($validated['sort_order'] ?? $slot->sort_order),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $slot->is_active,
        ]);

        $slot->syncWeekdays($validated['weekdays']);

        return response()->json([
            'success' => true,
            'message' => "Time slot '{$slot->label}' updated.",
            'slot' => $slot->load('weekdays'),
        ]);
    }

    public function toggleSlot(BookingTimeSlot $slot): JsonResponse
    {
        $slot->update(['is_active' => ! $slot->is_active]);

        return response()->json([
            'success' => true,
            'message' => "Slot '{$slot->label}' " . ($slot->is_active ? 'activated' : 'deactivated') . '.',
            'is_active' => (bool) $slot->is_active,
        ]);
    }

    public function destroySlot(BookingTimeSlot $slot): JsonResponse
    {
        $label = $slot->label;
        $slot->delete();

        return response()->json([
            'success' => true,
            'message' => "Slot '{$label}' deleted.",
        ]);
    }

    public function storeInstruction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::in(['collection', 'delivery', 'both'])],
            'allowed_address_types' => ['nullable', 'array'],
            'allowed_address_types.*' => [Rule::in(['HOME', 'OFFICE', 'HOTEL'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $instr = BookingDriverInstruction::create([
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'allowed_address_types' => $validated['allowed_address_types'] ?? ['HOME', 'OFFICE', 'HOTEL'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Driver instruction '{$instr->title}' added.",
            'instruction' => $instr,
        ], 201);
    }

    public function updateInstruction(Request $request, BookingDriverInstruction $instruction): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::in(['collection', 'delivery', 'both'])],
            'allowed_address_types' => ['nullable', 'array'],
            'allowed_address_types.*' => [Rule::in(['HOME', 'OFFICE', 'HOTEL'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $instruction->update([
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'allowed_address_types' => $validated['allowed_address_types'] ?? ['HOME', 'OFFICE', 'HOTEL'],
            'sort_order' => (int) ($validated['sort_order'] ?? $instruction->sort_order),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $instruction->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Driver instruction '{$instruction->title}' updated.",
            'instruction' => $instruction->fresh(),
        ]);
    }

    public function toggleInstruction(BookingDriverInstruction $instruction): JsonResponse
    {
        $instruction->update(['is_active' => ! $instruction->is_active]);

        return response()->json([
            'success' => true,
            'message' => "Instruction '{$instruction->title}' " . ($instruction->is_active ? 'activated' : 'deactivated') . '.',
            'is_active' => (bool) $instruction->is_active,
        ]);
    }

    public function destroyInstruction(BookingDriverInstruction $instruction): JsonResponse
    {
        $title = $instruction->title;
        $instruction->delete();

        return response()->json([
            'success' => true,
            'message' => "Instruction '{$title}' deleted.",
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'collection_weekdays' => ['required', 'array', 'min:1'],
            'collection_weekdays.*' => ['integer', 'between:0,6'],
            'delivery_weekdays' => ['required', 'array', 'min:1'],
            'delivery_weekdays.*' => ['integer', 'between:0,6'],
            'same_day_collection' => ['nullable', 'boolean'],
            'min_booking_notice_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'max_advance_days' => ['required', 'integer', 'min:1', 'max:90'],
            'min_delivery_turnaround_days' => ['required', 'integer', 'min:1', 'max:14'],
            'timezone' => ['required', 'timezone'],
        ]);

        BookingAvailabilitySetting::setValue('collection_weekdays', array_map('intval', $validated['collection_weekdays']));
        BookingAvailabilitySetting::setValue('delivery_weekdays', array_map('intval', $validated['delivery_weekdays']));
        BookingAvailabilitySetting::setValue('same_day_collection', $request->boolean('same_day_collection') ? '1' : '0');
        BookingAvailabilitySetting::setValue('min_booking_notice_minutes', $validated['min_booking_notice_minutes']);
        BookingAvailabilitySetting::setValue('max_advance_days', $validated['max_advance_days']);
        BookingAvailabilitySetting::setValue('min_delivery_turnaround_days', $validated['min_delivery_turnaround_days']);
        BookingAvailabilitySetting::setValue('timezone', $validated['timezone']);

        return response()->json([
            'success' => true,
            'message' => 'Availability settings saved successfully.',
        ]);
    }

    public function storeBlockedDate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', Rule::in(['collection', 'delivery', 'both'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $blocked = BookingBlockedDate::updateOrCreate(
            ['date' => $validated['date'], 'type' => $validated['type']],
            ['reason' => $validated['reason'] ?? null]
        );

        return response()->json([
            'success' => true,
            'message' => "Blocked date {$validated['date']} ({$validated['type']}) saved.",
            'blocked_date' => $blocked,
        ], 201);
    }

    public function destroyBlockedDate(BookingBlockedDate $blockedDate): JsonResponse
    {
        $dateStr = $blockedDate->date?->format('Y-m-d');
        $blockedDate->delete();

        return response()->json([
            'success' => true,
            'message' => "Blocked date {$dateStr} removed.",
        ]);
    }
}
