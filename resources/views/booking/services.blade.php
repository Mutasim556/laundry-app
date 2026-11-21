<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Select one or more laundry services and add custom special instructions for your clothes.">
    <title>Select Services | FreshGo Laundry</title>

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

    <!-- Booking Address & Services Flow CSS -->
    <link rel="stylesheet" href="{{ asset('css/booking-address.css') }}">

    <script src="{{ asset('js/booking-services.js') }}" defer></script>
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
        $savedAddress = $booking['address'] ?? [];
        $oldSelectedIds = old('selected_services');
        $oldServicesData = old('services_data', []);

        if (is_array($oldSelectedIds)) {
            $activeServiceIds = array_map('intval', $oldSelectedIds);
        } else {
            $activeServiceIds = $savedServices->keys()->map(fn ($id) => (int) $id)->all();
        }
    @endphp

    <main class="container booking-main-container" id="bookingServicesApp">
        <div class="booking-card-shell booking-services-shell mx-auto">

            <!-- Clickable 5-Step Booking Progress Bar -->
            @include('booking.partials.progress-bar', ['currentBookingStep' => $currentBookingStep ?? 1, 'bookingSteps' => $bookingSteps ?? null])

            <!-- Saved Collection Address Context Pill (If user already entered an address and returned Back) -->
            @if (!empty($savedAddress['selected_address']))
                <div class="booking-context-banner mb-3" id="addressContextBanner">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <span class="context-badge-icon">📍</span>
                        <div class="text-truncate">
                            <span class="context-label">Saved Address ({{ $savedAddress['address_type'] ?? 'HOME' }}):</span>
                            <strong class="context-title">{{ $savedAddress['selected_address'] }}</strong>
                        </div>
                    </div>
                    <a href="{{ route('booking.address') }}" class="context-change-link">
                        Address &rarr;
                    </a>
                </div>
            @endif

            <!-- Server-Side Validation Errors -->
            @if ($errors->any())
                <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-3 py-2 px-3 small fw-semibold" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- ===============================================================
                 STEP 1: SELECT SERVICES FORM (Multi-Service + Database Wash & Fold Options + Special Requests)
                 =============================================================== -->
            <section id="sectionSelectServices" aria-labelledby="headingSelectServices">
                <div class="mb-3">
                    <a href="{{ route('services.index', !empty($booking['category_slug']) ? ['category' => $booking['category_slug']] : []) }}"
                       class="booking-back-btn"
                       id="btnBackFromServices">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                        <span>Back</span>
                    </a>
                </div>

                <div class="booking-header-block mb-3">
                    <span class="step-pill-tag">Step 1 of Booking</span>
                    <h1 id="headingSelectServices" class="booking-screen-title">Select services</h1>
                    <p class="booking-screen-subtitle">Select one or more services needed for your clothes.</p>
                </div>

                <form id="formBookingServices" action="{{ route('booking.services.store') }}" method="POST" novalidate>
                    @csrf

                    <div id="servicesSelectionError" class="booking-field-error mb-2 d-none" role="alert"></div>

                    <div class="booking-services-list mb-4" role="group" aria-label="Available laundry services">
                        @foreach ($serviceCatalog as $service)
                            @php
                                $sid = (int) $service->id;
                                $isSelected = in_array($sid, $activeServiceIds, true);
                                $savedItem = $savedServices->get($sid, []);

                                // For Wash & Fold, restore actual database option_service_id (or service_id)
                                $currentWashOptionId = isset($oldServicesData[$sid]['wash_option_id'])
                                    ? (int) $oldServicesData[$sid]['wash_option_id']
                                    : (int) ($savedItem['option_service_id'] ?? ($savedItem['service_id'] ?? 0));

                                $selectedWashOptObj = $service->is_wash_fold && $currentWashOptionId
                                    ? $service->wash_options->firstWhere('id', $currentWashOptionId)
                                    : null;

                                $currentSpecialRequest = $oldServicesData[$sid]['special_request']
                                    ?? ($savedItem['special_request'] ?? '');

                                $hasExistingRequest = trim((string) $currentSpecialRequest) !== '';
                            @endphp

                            <div class="booking-service-card {{ $isSelected ? 'is-selected' : '' }}"
                                 data-service-card="{{ $sid }}"
                                 data-service-id="{{ $sid }}"
                                 data-service-name="{{ $service->name }}"
                                 data-service-slug="{{ $service->slug }}"
                                 data-is-wash-fold="{{ $service->is_wash_fold ? '1' : '0' }}">

                                <!-- Hidden Checkbox Input for Form Submission -->
                                <input type="checkbox"
                                       class="visually-hidden js-service-checkbox"
                                       id="service_cb_{{ $sid }}"
                                       name="selected_services[]"
                                       value="{{ $sid }}"
                                       {{ $isSelected ? 'checked' : '' }}>

                                <!-- Card Header Row (Clickable to select/deselect service) -->
                                <div class="service-card-main-row"
                                     data-service-toggle="{{ $sid }}"
                                     role="checkbox"
                                     aria-checked="{{ $isSelected ? 'true' : 'false' }}"
                                     tabindex="0">

                                    <div class="service-card-icon-circle">
                                        <img src="{{ $service->icon_url }}" alt="" width="28" height="28" loading="lazy">
                                    </div>

                                    <div class="service-card-info">
                                        <h2 class="service-card-heading">{{ $service->name }}</h2>
                                        <p class="service-card-subtext"
                                           data-service-subtitle="{{ $sid }}"
                                           data-default-subtitle="{{ $service->subtitle }}">
                                            @if ($service->is_wash_fold && $isSelected && $selectedWashOptObj)
                                                {{ $selectedWashOptObj->name }} added (BDT {{ number_format((float) $selectedWashOptObj->price, 2) }} / {{ $selectedWashOptObj->unit_name }})
                                            @else
                                                {{ $service->subtitle }}
                                            @endif
                                        </p>

                                        @if (!empty($service->badge))
                                            <div class="service-time-badge mt-1">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <polyline points="12 6 12 12 16 14"></polyline>
                                                </svg>
                                                <span>{{ $service->badge }}</span>
                                            </div>
                                        @endif

                                        <div class="mt-1">
                                            <a href="{{ route('services.index', ['category' => $service->slug]) }}"
                                               class="service-prices-more-link js-prices-more-link">
                                                Prices &amp; more
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Checkbox / Selection Circle Indicator on Right -->
                                    <div class="service-card-check-indicator" aria-hidden="true">
                                        <svg class="check-tick-svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Expandable Options Panel (Visible when service is selected) -->
                                <div class="service-card-expand-panel {{ $isSelected ? '' : 'd-none' }}"
                                     id="serviceExpandPanel_{{ $sid }}"
                                     data-expand-panel="{{ $sid }}">

                                    @if ($service->is_wash_fold && $service->wash_options->isNotEmpty())
                                        <!-- WASH & FOLD ONLY: Database-Driven Options (Mixed Wash & Fold vs Separate Wash & Fold) -->
                                        <div class="wash-type-selector-box mb-3" data-wash-type-box="{{ $sid }}">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <span class="wash-type-title">Choose Wash Option <span class="text-danger">*</span></span>
                                                <span class="wash-type-badge">Select 1 Option</span>
                                            </div>

                                            <div class="wash-type-options-grid" role="radiogroup" aria-label="Choose Wash Option for {{ $service->name }}">
                                                @foreach ($service->wash_options as $washOpt)
                                                    @php
                                                        $optId = (int) $washOpt->id;
                                                        $isOptChecked = $selectedWashOptObj && (int) $selectedWashOptObj->id === $optId;
                                                    @endphp
                                                    <label class="wash-type-radio-card {{ $isOptChecked ? 'is-checked' : '' }}"
                                                           for="wash_option_{{ $sid }}_{{ $optId }}"
                                                           data-wash-option-card="{{ $optId }}">
                                                        <input type="radio"
                                                               class="form-check-input js-wash-option-radio"
                                                               id="wash_option_{{ $sid }}_{{ $optId }}"
                                                               name="services_data[{{ $sid }}][wash_option_id]"
                                                               value="{{ $optId }}"
                                                               data-service-id="{{ $sid }}"
                                                               data-option-id="{{ $optId }}"
                                                               data-option-name="{{ $washOpt->name }}"
                                                               data-option-price="{{ number_format((float) $washOpt->price, 2) }}"
                                                               data-option-unit="{{ $washOpt->unit_name }}"
                                                               data-option-desc="{{ $washOpt->description }}"
                                                               {{ $isOptChecked ? 'checked' : '' }}
                                                               {{ $isSelected ? '' : 'disabled' }}>
                                                        <div class="wash-radio-content flex-grow-1">
                                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                                <span class="wash-radio-name">{{ $washOpt->name }}</span>
                                                                <span class="wash-radio-price">BDT {{ number_format((float) $washOpt->price, 2) }}<small>/{{ $washOpt->unit_name }}</small></span>
                                                            </div>
                                                            <span class="wash-radio-desc">{{ $washOpt->description }}</span>
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="booking-field-error d-none mt-1" data-wash-type-error="{{ $sid }}" role="alert">
                                                Please select either Mixed Wash &amp; Fold or Separate Wash &amp; Fold.
                                            </div>
                                        </div>
                                    @endif

                                    <!-- EVERY SERVICE: Independent "Add Special Request" Toggle Button -->
                                    <button type="button"
                                            class="btn-toggle-special-request w-100"
                                            id="btnToggleRequest_{{ $sid }}"
                                            data-toggle-request="{{ $sid }}"
                                            aria-expanded="{{ $hasExistingRequest ? 'true' : 'false' }}"
                                            aria-controls="specialRequestWrap_{{ $sid }}">
                                        <span class="toggle-request-label" data-toggle-label="{{ $sid }}">
                                            {{ $hasExistingRequest ? 'Hide Special Request' : 'Add Special Request' }}
                                        </span>
                                    </button>

                                    <!-- Independent Special Request Textarea Container -->
                                    <div class="special-request-textarea-wrap {{ $hasExistingRequest ? '' : 'd-none' }}"
                                         id="specialRequestWrap_{{ $sid }}"
                                         data-request-wrap="{{ $sid }}">
                                        <label for="specialRequestInput_{{ $sid }}" class="visually-hidden">
                                            Special instructions for {{ $service->name }}
                                        </label>
                                        <textarea id="specialRequestInput_{{ $sid }}"
                                                  name="services_data[{{ $sid }}][special_request]"
                                                  class="form-control special-request-textarea js-special-request-input"
                                                  data-service-id="{{ $sid }}"
                                                  rows="3"
                                                  maxlength="500"
                                                  placeholder="Enter any special instructions for {{ $service->name }}..."
                                                  {{ $isSelected ? '' : 'disabled' }}>{{ $currentSpecialRequest }}</textarea>
                                        <div class="d-flex align-items-center justify-content-between mt-1">
                                            <span class="small text-muted" style="font-size: 0.73rem;">Optional instructions for {{ $service->name }}</span>
                                            <span class="small text-muted fw-semibold" style="font-size: 0.73rem;">
                                                <span data-char-count="{{ $sid }}">{{ mb_strlen((string) $currentSpecialRequest) }}</span>/500
                                            </span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    </div>

                    <!-- Sticky / Prominent Yellow NEXT Button (Proceeds to Step 2: Address Selection) -->
                    <div class="booking-sticky-footer">
                        <button type="submit"
                                id="btnServicesNext"
                                class="btn-booking-next w-100"
                                disabled>
                            <span>NEXT</span>
                        </button>
                    </div>
                </form>
            </section>

        </div>
    </main>

    <!-- Mobile Fixed Bottom Navigation Bar (< 992px) -->
    @include('shared.bottom-nav')

    <!-- Reusable Offcanvas Modals -->
    @include('shared.offcanvas-nav')

    <!-- Bootstrap 5 JS Bundle -->
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
