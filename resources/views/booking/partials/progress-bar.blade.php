@php
    $currentStep = $currentBookingStep ?? 1;
    $steps = $bookingSteps ?? \App\Http\Controllers\BookingAddressController::evaluateBookingSteps(session('booking', []), $currentStep);
    $warningMsg = session('booking_step_warning');
@endphp

<!-- Reusable 5-Step Clickable Booking Progress Bar -->
<nav class="booking-stepper-wrapper mb-4" aria-label="Booking Progress" id="bookingProgressStepper">
    <ol class="booking-stepper-list">
        @foreach ($steps as $num => $step)
            @php
                $isCurrent = (bool) ($step['is_current'] ?? false);
                $isCompleted = (bool) ($step['completed'] ?? false);
                $isAvailable = (bool) ($step['available'] ?? false);

                // Line after step N is yellow if step N is completed
                $lineCompleted = $isCompleted;
            @endphp

            <li class="booking-stepper-item is-{{ $step['state'] }} {{ $isAvailable ? 'is-available' : 'is-locked' }}"
                data-step-number="{{ $num }}"
                data-step-state="{{ $step['state'] }}"
                data-step-available="{{ $isAvailable ? '1' : '0' }}"
                data-step-completed="{{ $isCompleted ? '1' : '0' }}">

                @if ($isAvailable)
                    <a href="{{ $step['url'] }}"
                       class="booking-stepper-node js-booking-step-trigger"
                       data-step-num="{{ $num }}"
                       data-step-available="1"
                       data-step-url="{{ $step['url'] }}"
                       @if ($isCurrent) aria-current="step" @endif
                       aria-label="Step {{ $num }}: {{ $step['label'] }}{{ $isCompleted && !$isCurrent ? ' (Completed)' : ($isCurrent ? ' (Current Step)' : '') }}">
                        <span class="stepper-circle">
                            @if ($isCompleted && !$isCurrent)
                                <svg class="stepper-check-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            @else
                                <span class="stepper-number">{{ $num }}</span>
                            @endif
                        </span>
                        <span class="stepper-label-desktop">{{ $step['label'] }}</span>
                        <span class="stepper-label-mobile">{{ $step['short_label'] }}</span>
                    </a>
                @else
                    <button type="button"
                            class="booking-stepper-node is-disabled-node js-booking-step-trigger"
                            data-step-num="{{ $num }}"
                            data-step-available="0"
                            data-step-missing="{{ $step['missing_requirement'] }}"
                            aria-disabled="true"
                            aria-label="Step {{ $num }}: {{ $step['label'] }} (Locked - {{ $step['missing_requirement'] }})">
                        <span class="stepper-circle">
                            <span class="stepper-number">{{ $num }}</span>
                        </span>
                        <span class="stepper-label-desktop">{{ $step['label'] }}</span>
                        <span class="stepper-label-mobile">{{ $step['short_label'] }}</span>
                    </button>
                @endif

                @if ($num < 5)
                    <div class="stepper-connector-line {{ $lineCompleted ? 'is-completed' : '' }}" aria-hidden="true"></div>
                @endif
            </li>
        @endforeach
    </ol>

    <!-- Feedback Alert when an incomplete future step is clicked or redirected -->
    <div id="bookingStepperAlert"
         class="booking-stepper-alert {{ $warningMsg ? '' : 'd-none' }}"
         role="alert"
         aria-live="polite">
        <div class="d-flex align-items-center gap-2">
            <span class="stepper-alert-icon" aria-hidden="true">⚠️</span>
            <span id="bookingStepperAlertText">{{ $warningMsg }}</span>
        </div>
        <button type="button"
                class="stepper-alert-close"
                id="btnCloseStepperAlert"
                aria-label="Dismiss notice">&times;</button>
    </div>
</nav>

<script>
(() => {
    const stepper = document.querySelector('#bookingProgressStepper');
    if (!stepper || stepper.dataset.bound === '1') return;
    stepper.dataset.bound = '1';

    const alertBox = stepper.querySelector('#bookingStepperAlert');
    const alertText = stepper.querySelector('#bookingStepperAlertText');
    const closeBtn = stepper.querySelector('#btnCloseStepperAlert');
    let alertTimer = null;

    const showStepWarning = (message) => {
        if (!alertBox || !alertText) return;
        alertText.textContent = message || 'Please complete the required earlier steps before proceeding.';
        alertBox.classList.remove('d-none');
        clearTimeout(alertTimer);
        alertTimer = setTimeout(() => {
            alertBox.classList.add('d-none');
        }, 6000);
    };

    closeBtn?.addEventListener('click', () => {
        alertBox?.classList.add('d-none');
    });

    stepper.querySelectorAll('.js-booking-step-trigger').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            const isAvailable = trigger.dataset.stepAvailable === '1';
            if (!isAvailable) {
                e.preventDefault();
                showStepWarning(trigger.dataset.stepMissing);
            }
        });
    });
})();
</script>
