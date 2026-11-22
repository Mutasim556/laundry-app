<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Book your laundry collection - Select your Home, Office, or Hotel address.">
    <title>Book Collection Address | FreshGo Laundry</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">

    <!-- Shared Laundry Home Theme CSS (Header, Navbar, Offcanvas, Bottom Nav) -->
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">

    <!-- Booking Address Flow CSS -->
    <link rel="stylesheet" href="{{ asset('css/booking-address.css') }}">

    <script src="{{ asset('js/booking-address.js') }}" defer></script>
</head>
<body class="booking-page-body">
    <!-- Mobile Header (< 992px) - Shared with Homepage & Services -->
    <div class="d-lg-none px-3 pt-3 bg-white border-bottom">
        @include('shared.header-mobile')
    </div>

    <!-- Desktop Navbar (>= 992px) - Shared with Homepage & Services -->
    <div class="d-none d-lg-block">
        @include('shared.header-desktop')
    </div>

    @php
        $savedAddress = $booking['address'] ?? [];
        $currentMode = old('collection_mode', $savedAddress['collection_mode'] ?? '');
        $currentPostcode = old('postcode', $savedAddress['postcode'] ?? '');
        $currentUkAddressId = old('uk_address_id', $savedAddress['uk_address_id'] ?? '');
        $currentSelectedAddress = old('selected_address', $savedAddress['selected_address'] ?? '');
        $currentManualAddress = old('manual_address', $savedAddress['manual_address'] ?? '');
        $currentIsManual = (bool) old('is_manual', $savedAddress['is_manual'] ?? false);
        $currentAddressType = old('address_type', $savedAddress['address_type'] ?? 'HOME');
        $currentRoomNumber = old('room_number', $savedAddress['room_number'] ?? '');
        $currentHotelCollectionType = old('hotel_collection_type', $savedAddress['hotel_collection_type'] ?? '');
        $currentAddressDetails = old('address_details', $savedAddress['address_details'] ?? '');
        $currentHotelId = old('hotel_id', $savedAddress['hotel_id'] ?? '');
        $currentHotelName = old('hotel_name', $savedAddress['hotel_name'] ?? '');
        $currentHotelAddress = old('hotel_address', ($currentMode === 'hotel' ? ($savedAddress['selected_address'] ?? '') : ''));

        // Determine active view screen
        if ($errors->has('hotel_name') || $errors->has('hotel_address') || $errors->has('hotel_collection_type') || $initialStep === 'hotel') {
            $activeScreen = 'hotel';
        } elseif ($errors->any() || $initialStep === 'postcode') {
            $activeScreen = 'postcode';
        } elseif ($initialStep === 'choose') {
            $activeScreen = 'choose';
        } elseif (($initialStep === 'summary' || session('booking_address_saved')) && !empty($savedAddress['selected_address'])) {
            $activeScreen = 'summary';
        } elseif (!empty($savedAddress['selected_address'])) {
            $activeScreen = ($savedAddress['collection_mode'] ?? 'home_office') === 'hotel' ? 'hotel' : 'postcode';
        } else {
            $activeScreen = 'postcode';
        }
    @endphp

    <main class="container booking-main-container"
          id="bookingAddressApp"
          data-active-screen="{{ $activeScreen }}"
          data-lookup-url="{{ route('booking.postcode.lookup') }}"
          data-hotel-url="{{ route('booking.hotel.search') }}"
          data-services-url="{{ route('booking.services') }}">

        <div class="booking-card-shell mx-auto">

            <!-- Clickable 5-Step Booking Progress Bar -->
            @include('booking.partials.progress-bar', ['currentBookingStep' => $currentBookingStep ?? 2, 'bookingSteps' => $bookingSteps ?? null])

            <!-- Selected Services Context Pill (Preserved from Step 1: Select Services) -->
            @if (!empty($booking['services']) && is_array($booking['services']))
                <div class="booking-context-banner mb-3" id="bookingContextBanner">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <span class="context-badge-icon">🧺</span>
                        <div class="text-truncate">
                            <span class="context-label">Selected Services ({{ count($booking['services']) }}):</span>
                            <strong class="context-title">
                                {{ collect($booking['services'])->map(fn ($s) => $s['option_name'] ?? $s['name'] ?? 'Service')->implode(', ') }}
                            </strong>
                        </div>
                    </div>
                    <a href="{{ route('booking.services') }}" class="context-change-link" id="linkEditSelectedServices">
                        Edit Services
                    </a>
                </div>
            @elseif (!empty($booking['service_name']) || !empty($booking['category_name']))
                <div class="booking-context-banner mb-3" id="bookingContextBanner">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <span class="context-badge-icon">🧺</span>
                        <div class="text-truncate">
                            <span class="context-label">Booking:</span>
                            <strong class="context-title">
                                {{ $booking['service_name'] ?? $booking['category_name'] }}
                            </strong>
                            @if (!empty($booking['price']))
                                <span class="context-price ms-1">
                                    ({{ formatMoney($booking['price']) }}@if(!empty($booking['unit'])) / {{ $booking['unit'] }}@endif)
                                </span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('booking.services') }}" class="context-change-link">
                        Edit Services
                    </a>
                </div>
            @endif

            <!-- Server-Side Validation Alert -->
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
                 SCREEN 1: CHOOSE COLLECTION TYPE (Home / Office vs Hotel)
                 =============================================================== -->
            <section id="screenChooseType" class="booking-step-screen {{ $activeScreen === 'choose' ? '' : 'd-none' }}" aria-labelledby="headingChooseType">
                <div class="mb-3">
                    <a href="{{ route('booking.services') }}" class="booking-back-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                        <span>Back</span>
                    </a>
                </div>

                <div class="booking-header-block mb-4">
                    <span class="step-pill-tag">Step 2 of Booking</span>
                    <h1 id="headingChooseType" class="booking-screen-title">Where should we collect your laundry?</h1>
                    <p class="booking-screen-subtitle">Choose your collection location type below to get started.</p>
                </div>

                <div class="collection-options-grid mb-4" role="radiogroup" aria-label="Collection location type">
                    <!-- Option 1: Home / Office Address -->
                    <div class="collection-option-card {{ $currentMode === 'home_office' ? 'is-selected' : '' }}"
                         data-collection-option="home_office"
                         role="radio"
                         aria-checked="{{ $currentMode === 'home_office' ? 'true' : 'false' }}"
                         tabindex="0">
                        <div class="option-card-icon">
                            <!-- House / Location Pin Icon -->
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <div class="option-card-body">
                            <h2 class="option-card-title">Home / Office Address</h2>
                            <p class="option-card-desc">Enter your postcode to find your address.</p>
                        </div>
                        <div class="option-card-check" aria-hidden="true">
                            <span class="check-dot"></span>
                        </div>
                    </div>

                    <!-- Option 2: Hotel -->
                    <div class="collection-option-card {{ $currentMode === 'hotel' ? 'is-selected' : '' }}"
                         data-collection-option="hotel"
                         role="radio"
                         aria-checked="{{ $currentMode === 'hotel' ? 'true' : 'false' }}"
                         tabindex="0">
                        <div class="option-card-icon">
                            <!-- Hotel Building Icon -->
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                                <path d="M9 22v-4h6v4"></path>
                                <path d="M8 6h.01"></path>
                                <path d="M16 6h.01"></path>
                                <path d="M12 6h.01"></path>
                                <path d="M12 10h.01"></path>
                                <path d="M12 14h.01"></path>
                                <path d="M16 10h.01"></path>
                                <path d="M16 14h.01"></path>
                                <path d="M8 10h.01"></path>
                                <path d="M8 14h.01"></path>
                            </svg>
                        </div>
                        <div class="option-card-body">
                            <h2 class="option-card-title">Hotel</h2>
                            <p class="option-card-desc">Search for your hotel and arrange collection.</p>
                        </div>
                        <div class="option-card-check" aria-hidden="true">
                            <span class="check-dot"></span>
                        </div>
                    </div>
                </div>

                <button type="button"
                        id="btnChooseTypeNext"
                        class="btn-booking-next w-100"
                        {{ in_array($currentMode, ['home_office', 'hotel'], true) ? '' : 'disabled' }}>
                    <span>Next</span>
                    <span class="ms-1">&rarr;</span>
                </button>
            </section>

            @include('shared.address-form-screens', [
                'isBookingContext' => true,
                'formAction' => route('booking.address.store'),
                'isEditMode' => false,
                'backUrl' => route('booking.services'),
                'backLabel' => 'Back',
                'submitButtonLabel' => 'Next',
            ])

            <!-- ===============================================================
                 SCREEN 4: SAVED ADDRESS CONFIRMATION / NEXT STEP READY
                 Allows testing Back navigation & persistence cleanly
                 =============================================================== -->
            <section id="screenAddressSummary" class="booking-step-screen {{ $activeScreen === 'summary' ? '' : 'd-none' }}" aria-labelledby="headingSummary">
                <div class="mb-3">
                    <button type="button"
                            class="booking-back-btn"
                            id="btnBackFromSummary"
                            data-go-screen="{{ ($savedAddress['collection_mode'] ?? 'home_office') === 'hotel' ? 'hotel' : 'postcode' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                        <span>Back to edit address</span>
                    </button>
                </div>

                <div class="booking-summary-card">
                    <div class="summary-check-icon">✓</div>
                    <span class="step-pill-tag mb-2">Step 2 Complete</span>
                    <h1 id="headingSummary" class="booking-screen-title mb-2">Services &amp; Collection Address Confirmed</h1>
                    <p class="booking-screen-subtitle mb-4">
                        Your selected laundry services and collection address have been saved to your booking draft.
                    </p>

                    @if (!empty($booking['services']) && is_array($booking['services']))
                        <div class="summary-details-box text-start mb-3" id="summaryServicesBox">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small fw-semibold">SELECTED SERVICES ({{ count($booking['services']) }})</span>
                                <a href="{{ route('booking.services') }}" class="small fw-bold text-decoration-none" style="color: #B58000;">Edit Services</a>
                            </div>
                            @foreach ($booking['services'] as $savedSvc)
                                <div class="py-2 {{ !$loop->last ? 'border-bottom' : '' }}" data-summary-service-id="{{ $savedSvc['service_id'] }}">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <strong class="text-dark">{{ $savedSvc['option_name'] ?? $savedSvc['name'] }}</strong>
                                        @if (!empty($savedSvc['price']))
                                            <span class="badge rounded-pill bg-warning text-dark fw-bold">
                                                {{ formatMoney($savedSvc['price']) }}@if(!empty($savedSvc['unit'])) / {{ $savedSvc['unit'] }}@endif
                                            </span>
                                        @else
                                            <span class="badge rounded-pill bg-light text-dark border fw-semibold">Selected</span>
                                        @endif
                                    </div>
                                    @if (!empty($savedSvc['option_description']))
                                        <div class="small text-muted">{{ $savedSvc['option_description'] }}</div>
                                    @endif
                                    @if (!empty($savedSvc['special_request']))
                                        <div class="small text-secondary mt-1 p-2 rounded-3 bg-white border">
                                            <span class="fw-bold text-dark">Special Request:</span>
                                            <span>{{ $savedSvc['special_request'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="summary-details-box text-start mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small fw-semibold">COLLECTION TYPE</span>
                            <span class="badge rounded-pill bg-warning text-dark fw-bold px-3 py-1" id="summaryAddressType">
                                {{ $savedAddress['address_type'] ?? 'HOME' }}
                            </span>
                        </div>

                        @if (!empty($savedAddress['hotel_name']))
                            <div class="mb-2">
                                <div class="text-muted small fw-semibold">HOTEL NAME</div>
                                <div class="fw-bold text-dark" id="summaryHotelName">{{ $savedAddress['hotel_name'] }}</div>
                            </div>
                        @endif

                        @if (!empty($savedAddress['postcode']))
                            <div class="mb-2">
                                <div class="text-muted small fw-semibold">UK POSTCODE</div>
                                <div class="fw-bold text-dark" id="summaryPostcode">{{ $savedAddress['postcode'] }}</div>
                            </div>
                        @endif

                        <div class="mb-2">
                            <div class="text-muted small fw-semibold">COLLECTION ADDRESS</div>
                            <div class="fw-bold text-dark" id="summaryFullAddress">{{ $savedAddress['selected_address'] ?? '' }}</div>
                        </div>

                        @if (!empty($savedAddress['room_number']))
                            <div class="mb-2">
                                <div class="text-muted small fw-semibold">ROOM NUMBER</div>
                                <div class="fw-bold text-dark" id="summaryRoomNumber">{{ $savedAddress['room_number'] }}</div>
                            </div>
                        @endif

                        @if (!empty($savedAddress['hotel_collection_type']))
                            <div class="mb-2">
                                <div class="text-muted small fw-semibold">HOTEL COLLECTION METHOD</div>
                                <div class="fw-bold text-dark" id="summaryHotelCollectionType">
                                    {{ $savedAddress['hotel_collection_type'] === 'use_reception' ? 'Use reception' : 'Meet driver' }}
                                </div>
                            </div>
                        @endif

                        @if (!empty($savedAddress['address_details']))
                            <div class="mb-0">
                                <div class="text-muted small fw-semibold">ADDRESS DETAILS / INSTRUCTIONS</div>
                                <div class="fw-semibold text-secondary" id="summaryAddressDetails">{{ $savedAddress['address_details'] }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <button type="button"
                                class="btn btn-outline-dark rounded-pill py-2 px-4 fw-bold flex-grow-1"
                                data-go-screen="{{ ($savedAddress['collection_mode'] ?? 'home_office') === 'hotel' ? 'hotel' : 'postcode' }}">
                            &larr; Edit Address
                        </button>
                        <a href="{{ route('booking.services') }}"
                           class="btn-booking-next text-decoration-none flex-grow-1 justify-content-center">
                            <span>Edit Selected Services &rarr;</span>
                        </a>
                    </div>
                </div>
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
