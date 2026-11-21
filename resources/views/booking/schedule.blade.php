<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Select your preferred order collection and delivery time slots.">
    <title>Select Time Slots | FreshGo Laundry</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">

    <!-- Shared Laundry Home Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">

    <!-- Booking Flow CSS -->
    <link rel="stylesheet" href="{{ asset('css/booking-address.css') }}">
</head>
<body class="booking-page-body">
    <!-- Mobile Header (< 992px) -->
    <div class="d-lg-none px-3 pt-3 bg-white border-bottom">
        @include('shared.header-mobile')
    </div>

    <!-- Desktop Navbar (>= 992px) -->
    <div class="d-none d-lg-block">
        @include('shared.header-desktop')
    </div>

    @php
        $savedSchedule = $booking['schedule'] ?? [];
        $minDays = $minTurnaroundDays ?? 1;

        $defaultColDate = $dateOptions[0]['value'] ?? now()->format('Y-m-d');
        $defaultDelDate = $dateOptions[$minDays]['value'] ?? now()->addDays($minDays)->format('Y-m-d');

        $currentColDate = old('collection_date', $savedSchedule['collection_date'] ?? $defaultColDate);
        $currentColSlot = old('collection_slot', $savedSchedule['collection_slot'] ?? (end($timeSlots) ?: '20:00 - 23:00'));
        $currentColInstr = old('collection_instructions', $savedSchedule['collection_instructions'] ?? ($collectionInstructions[0] ?? 'Collect from me in person'));

        $currentDelDate = old('delivery_date', $savedSchedule['delivery_date'] ?? $defaultDelDate);
        $currentDelSlot = old('delivery_slot', $savedSchedule['delivery_slot'] ?? (end($timeSlots) ?: '20:00 - 23:00'));
        $currentDelInstr = old('delivery_instructions', $savedSchedule['delivery_instructions'] ?? ($deliveryInstructions[0] ?? 'Deliver to me in person'));

        $currentDriverNotes = old('driver_notes', $savedSchedule['driver_notes'] ?? '');
        $hasExistingNote = trim((string) $currentDriverNotes) !== '';

        $currentRepeatEnabled = (bool) old('repeat_order', $savedSchedule['repeat_order'] ?? false);
        $currentRepeatFreq = old('repeat_frequency', $savedSchedule['repeat_frequency'] ?? 'weekly');
    @endphp

    <main class="container booking-main-container"
          id="bookingScheduleApp"
          data-min-turnaround-days="{{ $minDays }}">
        <div class="booking-card-shell mx-auto">

            <!-- Clickable 5-Step Booking Progress Bar -->
            @include('booking.partials.progress-bar', ['currentBookingStep' => 3, 'bookingSteps' => $bookingSteps ?? null])

            <!-- Server-Side Validation Alert -->
            @if ($errors->any())
                <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-3 py-2 px-3 small fw-semibold" role="alert" id="scheduleValidationAlert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-3">
                <a href="{{ route('booking.address') }}" class="booking-back-btn" id="btnBackToAddressStep">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span>Back</span>
                </a>
            </div>

            <div class="booking-header-block mb-4">
                <h1 class="booking-screen-title">Select time slots</h1>
                <p class="booking-screen-subtitle">Select your preferred order collection and delivery time slots.</p>
                @if ($minDays >= 3)
                    <div class="mt-2 small fw-semibold px-3 py-1 rounded-pill d-inline-block" style="background: var(--soft-cream); color: #9A6B00; border: 1px solid rgba(255,198,41,0.45);">
                        ⏱ Includes 3-day turnaround service (Duvets &amp; Bulky Items)
                    </div>
                @endif
            </div>

            <form id="formBookingSchedule" action="{{ route('booking.schedule.store') }}" method="POST" novalidate>
                @csrf

                <!-- ===========================================================
                     1. COLLECTION TIME SECTION
                     =========================================================== -->
                <div class="schedule-section-block mb-4">
                    <h2 class="schedule-section-heading mb-2">Collection time</h2>

                    <div class="row g-2 g-sm-3 mb-3">
                        <!-- LEFT: Select day -->
                        <div class="col-6">
                            <label for="collectionDateSelect" class="schedule-sublabel">Select day</label>
                            <div class="schedule-select-wrapper">
                                <span class="schedule-select-icon" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                        <polyline points="9 16 11 18 15 14"></polyline>
                                    </svg>
                                </span>
                                <select id="collectionDateSelect"
                                        name="collection_date"
                                        class="form-select schedule-custom-select"
                                        required>
                                    @foreach ($dateOptions as $opt)
                                        <option value="{{ $opt['value'] }}"
                                                data-day-offset="{{ $opt['day_offset'] }}"
                                                @selected($currentColDate === $opt['value'])>
                                            {{ $opt['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- RIGHT: Select time -->
                        <div class="col-6">
                            <label for="collectionSlotSelect" class="schedule-sublabel">Select time</label>
                            <div class="schedule-select-wrapper">
                                <span class="schedule-select-icon" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </span>
                                <select id="collectionSlotSelect"
                                        name="collection_slot"
                                        class="form-select schedule-custom-select"
                                        required>
                                    @foreach ($timeSlots as $slot)
                                        <option value="{{ $slot }}" @selected($currentColSlot === $slot)>
                                            {{ $slot }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Collection Driver Instructions -->
                    <div>
                        <label for="collectionInstructionsSelect" class="schedule-sublabel">Driver instructions</label>
                        <div class="schedule-select-wrapper">
                            <span class="schedule-select-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <circle cx="18" cy="8" r="3"></circle>
                                    <polyline points="18 6.5 18 8 19 8.5"></polyline>
                                </svg>
                            </span>
                            <select id="collectionInstructionsSelect"
                                    name="collection_instructions"
                                    class="form-select schedule-custom-select">
                                @foreach ($collectionInstructions as $instr)
                                    <option value="{{ $instr }}" @selected($currentColInstr === $instr)>
                                        {{ $instr }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ===========================================================
                     2. DELIVERY TIME SECTION
                     =========================================================== -->
                <div class="schedule-section-block mb-3">
                    <h2 class="schedule-section-heading mb-2">Delivery time</h2>

                    <div class="row g-2 g-sm-3 mb-3">
                        <!-- LEFT: Select day -->
                        <div class="col-6">
                            <label for="deliveryDateSelect" class="schedule-sublabel">Select day</label>
                            <div class="schedule-select-wrapper">
                                <span class="schedule-select-icon" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                        <polyline points="9 16 11 18 15 14"></polyline>
                                    </svg>
                                </span>
                                <select id="deliveryDateSelect"
                                        name="delivery_date"
                                        class="form-select schedule-custom-select"
                                        required>
                                    @foreach ($dateOptions as $opt)
                                        <option value="{{ $opt['value'] }}"
                                                data-day-offset="{{ $opt['day_offset'] }}"
                                                @selected($currentDelDate === $opt['value'])>
                                            {{ $opt['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- RIGHT: Select time -->
                        <div class="col-6">
                            <label for="deliverySlotSelect" class="schedule-sublabel">Select time</label>
                            <div class="schedule-select-wrapper">
                                <span class="schedule-select-icon" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </span>
                                <select id="deliverySlotSelect"
                                        name="delivery_slot"
                                        class="form-select schedule-custom-select"
                                        required>
                                    @foreach ($timeSlots as $slot)
                                        <option value="{{ $slot }}" @selected($currentDelSlot === $slot)>
                                            {{ $slot }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="deliveryTurnaroundError" class="booking-field-error mb-2 d-none" role="alert"></div>

                    <!-- Delivery Driver Instructions -->
                    <div>
                        <label for="deliveryInstructionsSelect" class="schedule-sublabel">Driver instructions</label>
                        <div class="schedule-select-wrapper">
                            <span class="schedule-select-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <circle cx="18" cy="8" r="3"></circle>
                                    <polyline points="18 6.5 18 8 19 8.5"></polyline>
                                </svg>
                            </span>
                            <select id="deliveryInstructionsSelect"
                                    name="delivery_instructions"
                                    class="form-select schedule-custom-select">
                                @foreach ($deliveryInstructions as $instr)
                                    <option value="{{ $instr }}" @selected($currentDelInstr === $instr)>
                                        {{ $instr }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ===========================================================
                     3. ADD A NOTE FOR THE DRIVER (Inline Expandable Textarea)
                     =========================================================== -->
                <div class="mb-4">
                    <button type="button"
                            id="btnToggleDriverNote"
                            class="btn-toggle-special-request w-100"
                            aria-expanded="{{ $hasExistingNote ? 'true' : 'false' }}"
                            aria-controls="driverNoteWrap">
                        <span id="driverNoteToggleLabel">
                            {{ $hasExistingNote ? 'Hide note for the driver' : 'Add a note for the driver' }}
                        </span>
                    </button>

                    <div id="driverNoteWrap" class="special-request-textarea-wrap {{ $hasExistingNote ? '' : 'd-none' }}">
                        <label for="driverNotesInput" class="visually-hidden">Note for the driver</label>
                        <textarea id="driverNotesInput"
                                  name="driver_notes"
                                  class="form-control special-request-textarea"
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Gate code, parking information, directions, or other instructions...">{{ $currentDriverNotes }}</textarea>
                        <div class="d-flex align-items-center justify-content-between mt-1">
                            <span class="small text-muted" style="font-size: 0.73rem;">Optional driver instructions</span>
                            <span class="small text-muted fw-semibold" style="font-size: 0.73rem;">
                                <span id="driverNoteCharCount">{{ mb_strlen((string) $currentDriverNotes) }}</span>/500
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ===========================================================
                     4. REPEAT ORDER (Toggle Switch + Weekly / Bi-weekly / Monthly)
                     =========================================================== -->
                <div class="repeat-order-section mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label for="repeatOrderToggle" class="schedule-section-heading mb-0 cursor-pointer">Repeat order</label>
                        <div class="form-check form-switch repeat-custom-switch m-0">
                            <input class="form-check-input"
                                   type="checkbox"
                                   role="switch"
                                   id="repeatOrderToggle"
                                   name="repeat_order"
                                   value="1"
                                   {{ $currentRepeatEnabled ? 'checked' : '' }}>
                        </div>
                    </div>

                    <div id="repeatFrequencyWrap" class="{{ $currentRepeatEnabled ? '' : 'd-none' }} mt-3">
                        <div class="repeat-frequency-row" role="radiogroup" aria-label="Repeat order frequency">
                            @foreach (['weekly' => 'Weekly', 'bi_weekly' => 'Bi-weekly', 'monthly' => 'Monthly'] as $freqVal => $freqLabel)
                                @php
                                    $isFreqChecked = $currentRepeatFreq === $freqVal;
                                @endphp
                                <label class="repeat-frequency-btn {{ $isFreqChecked ? 'is-selected' : '' }}"
                                       for="repeat_freq_{{ $freqVal }}"
                                       data-freq-option="{{ $freqVal }}">
                                    <input type="radio"
                                           class="visually-hidden js-repeat-freq-radio"
                                           id="repeat_freq_{{ $freqVal }}"
                                           name="repeat_frequency"
                                           value="{{ $freqVal }}"
                                           {{ $isFreqChecked ? 'checked' : '' }}
                                           {{ $currentRepeatEnabled ? '' : 'disabled' }}>
                                    <span>{{ $freqLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div id="repeatFrequencyError" class="booking-field-error d-none mt-1" role="alert">
                            Please select Weekly, Bi-weekly, or Monthly for your repeat order.
                        </div>
                    </div>
                </div>

                <div class="booking-sticky-footer">
                    <button type="submit" id="btnScheduleNext" class="btn-booking-next w-100">
                        <span>NEXT</span>
                    </button>
                </div>
            </form>

        </div>
    </main>

    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script>
    (() => {
        'use strict';

        const app = document.querySelector('#bookingScheduleApp');
        if (!app) return;

        const minTurnaroundDays = parseInt(app.dataset.minTurnaroundDays || '1', 10);
        const colDateSelect = document.querySelector('#collectionDateSelect');
        const delDateSelect = document.querySelector('#deliveryDateSelect');
        const turnaroundError = document.querySelector('#deliveryTurnaroundError');

        const btnToggleNote = document.querySelector('#btnToggleDriverNote');
        const noteToggleLabel = document.querySelector('#driverNoteToggleLabel');
        const noteWrap = document.querySelector('#driverNoteWrap');
        const noteInput = document.querySelector('#driverNotesInput');
        const noteCharCount = document.querySelector('#driverNoteCharCount');

        const repeatToggle = document.querySelector('#repeatOrderToggle');
        const repeatFreqWrap = document.querySelector('#repeatFrequencyWrap');
        const repeatFreqRadios = document.querySelectorAll('.js-repeat-freq-radio');
        const repeatFreqBtns = document.querySelectorAll('.repeat-frequency-btn');
        const repeatFreqError = document.querySelector('#repeatFrequencyError');

        const form = document.querySelector('#formBookingSchedule');

        // 1. Enforce minimum turnaround time between Collection Day and Delivery Day
        const syncEligibleDeliveryDates = (autoAdvance = true) => {
            if (!colDateSelect || !delDateSelect) return;

            const selectedColOption = colDateSelect.options[colDateSelect.selectedIndex];
            const colOffset = parseInt(selectedColOption?.dataset.dayOffset || '0', 10);
            const minDelOffset = colOffset + minTurnaroundDays;

            let firstEligibleValue = null;
            let currentDelIsValid = false;

            Array.from(delDateSelect.options).forEach(opt => {
                const delOffset = parseInt(opt.dataset.dayOffset || '0', 10);
                const isEligible = delOffset >= minDelOffset;
                opt.disabled = !isEligible;

                if (isEligible && firstEligibleValue === null) {
                    firstEligibleValue = opt.value;
                }
                if (opt.value === delDateSelect.value && isEligible) {
                    currentDelIsValid = true;
                }
            });

            if (!currentDelIsValid && autoAdvance && firstEligibleValue) {
                delDateSelect.value = firstEligibleValue;
                turnaroundError?.classList.add('d-none');
            }
        };

        colDateSelect?.addEventListener('change', () => {
            turnaroundError?.classList.add('d-none');
            syncEligibleDeliveryDates(true);
        });

        delDateSelect?.addEventListener('change', () => {
            const colOffset = parseInt(colDateSelect.options[colDateSelect.selectedIndex]?.dataset.dayOffset || '0', 10);
            const delOffset = parseInt(delDateSelect.options[delDateSelect.selectedIndex]?.dataset.dayOffset || '0', 10);
            if (delOffset < colOffset + minTurnaroundDays) {
                if (turnaroundError) {
                    turnaroundError.textContent = `Delivery day must be at least ${minTurnaroundDays} ${minTurnaroundDays === 1 ? 'day' : 'days'} after collection.`;
                    turnaroundError.classList.remove('d-none');
                }
            } else {
                turnaroundError?.classList.add('d-none');
            }
        });

        // Initial sync without clobbering server validation state if user intentionally tested an invalid date
        syncEligibleDeliveryDates(true);

        // 2. "Add a note for the driver" inline toggle (preserves text when collapsed)
        btnToggleNote?.addEventListener('click', () => {
            if (!noteWrap || !noteToggleLabel) return;
            const isHidden = noteWrap.classList.contains('d-none');
            noteWrap.classList.toggle('d-none', !isHidden);
            btnToggleNote.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            noteToggleLabel.textContent = isHidden ? 'Hide note for the driver' : 'Add a note for the driver';
            if (isHidden && noteInput) {
                noteInput.focus();
            }
        });

        noteInput?.addEventListener('input', () => {
            if (noteCharCount) {
                noteCharCount.textContent = String(noteInput.value.length);
            }
        });

        // 3. Repeat Order toggle switch + Weekly / Bi-weekly / Monthly radio buttons
        let cachedRepeatFreq = document.querySelector('.js-repeat-freq-radio:checked')?.value || 'weekly';

        const updateRepeatOrderUI = () => {
            if (!repeatToggle || !repeatFreqWrap) return;
            const enabled = repeatToggle.checked;

            repeatFreqWrap.classList.toggle('d-none', !enabled);
            repeatFreqError?.classList.add('d-none');

            repeatFreqRadios.forEach(radio => {
                radio.disabled = !enabled;
                if (enabled && radio.value === cachedRepeatFreq) {
                    radio.checked = true;
                }
            });

            repeatFreqBtns.forEach(label => {
                const radio = label.querySelector('.js-repeat-freq-radio');
                label.classList.toggle('is-selected', Boolean(enabled && radio?.checked));
            });
        };

        repeatToggle?.addEventListener('change', updateRepeatOrderUI);

        repeatFreqRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                if (radio.checked) {
                    cachedRepeatFreq = radio.value;
                }
                repeatFreqBtns.forEach(label => {
                    const r = label.querySelector('.js-repeat-freq-radio');
                    label.classList.toggle('is-selected', Boolean(r?.checked));
                });
                repeatFreqError?.classList.add('d-none');
            });
        });

        // Form submission validation check
        form?.addEventListener('submit', (e) => {
            const colDateVal = colDateSelect?.value || '';
            const delDateVal = delDateSelect?.value || '';

            if (colDateVal && delDateVal && delDateVal < colDateVal) {
                e.preventDefault();
                if (turnaroundError) {
                    turnaroundError.textContent = 'Delivery date cannot be earlier than the collection date.';
                    turnaroundError.classList.remove('d-none');
                }
                return;
            }

            if (repeatToggle?.checked) {
                const checkedFreq = document.querySelector('.js-repeat-freq-radio:checked');
                if (!checkedFreq) {
                    e.preventDefault();
                    repeatFreqError?.classList.remove('d-none');
                }
            }
        });
    })();
    </script>
</body>
</html>
