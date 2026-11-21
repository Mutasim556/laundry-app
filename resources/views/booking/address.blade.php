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
        $currentSelectedAddress = old('selected_address', $savedAddress['selected_address'] ?? '');
        $currentManualAddress = old('manual_address', $savedAddress['manual_address'] ?? '');
        $currentIsManual = (bool) old('is_manual', $savedAddress['is_manual'] ?? false);
        $currentAddressType = old('address_type', $savedAddress['address_type'] ?? 'HOME');
        $currentRoomNumber = old('room_number', $savedAddress['room_number'] ?? '');
        $currentHotelCollectionType = old('hotel_collection_type', $savedAddress['hotel_collection_type'] ?? '');
        $currentAddressDetails = old('address_details', $savedAddress['address_details'] ?? '');
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
                                    (BDT {{ number_format((float) $booking['price'], 2) }}@if(!empty($booking['unit'])) / {{ $booking['unit'] }}@endif)
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

            <!-- ===============================================================
                 SCREEN 2: HOME / OFFICE UK POSTCODE & ADDRESS SCREEN
                 Matches Screenshot 1 & Screenshot 3
                 =============================================================== -->
            <section id="screenPostcodeAddress" class="booking-step-screen {{ $activeScreen === 'postcode' ? '' : 'd-none' }}" aria-labelledby="headingAddAddress">
                <form id="formPostcodeAddress" action="{{ route('booking.address.store') }}" method="POST" novalidate>
                    @csrf
                    <input type="hidden" name="collection_mode" value="home_office">
                    <input type="hidden" name="is_manual" id="inputIsManual" value="{{ $currentIsManual ? '1' : '0' }}">

                    <div class="mb-3">
                        <a href="{{ route('booking.services') }}" class="booking-back-btn" id="btnBackToServicesFromPostcode">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            <span>Back</span>
                        </a>
                    </div>

                    <div class="booking-header-block mb-3">
                        <span class="step-pill-tag">Step 2 of Booking</span>
                        <h1 id="headingAddAddress" class="booking-screen-title">Add your address</h1>
                        <p class="booking-screen-subtitle mb-0">
                            Enter your address so the driver can find you.
                            @guest
                                <a href="{{ route('admin.login') }}" class="booking-accent-link">Log in</a> to see saved addresses.
                            @endguest
                        </p>
                    </div>

                    <!-- Saved Addresses Quick Picker (When available in session or logged in) -->
                    @if (!empty($savedAddresses))
                        <div class="saved-addresses-box mb-3">
                            <label class="form-label booking-field-label mb-1">Recent Saved Addresses</label>
                            <div class="d-flex flex-column gap-2">
                                @foreach ($savedAddresses as $idx => $savedItem)
                                    @if (($savedItem['collection_mode'] ?? 'home_office') === 'home_office')
                                        <button type="button"
                                                class="saved-address-pill text-start"
                                                data-saved-postcode="{{ $savedItem['postcode'] ?? '' }}"
                                                data-saved-address="{{ $savedItem['selected_address'] ?? '' }}"
                                                data-saved-type="{{ $savedItem['address_type'] ?? 'HOME' }}"
                                                data-saved-details="{{ $savedItem['address_details'] ?? '' }}">
                                            <span class="badge bg-warning text-dark me-2">{{ $savedItem['address_type'] ?? 'HOME' }}</span>
                                            <span class="fw-semibold">{{ $savedItem['selected_address'] ?? '' }}</span>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- UK Postcode Input -->
                    <div class="mb-3">
                        <label for="ukPostcodeInput" class="form-label booking-field-label">Enter UK Postcode</label>
                        <div class="postcode-input-group">
                            <input type="text"
                                   id="ukPostcodeInput"
                                   name="postcode"
                                   class="form-control booking-form-control text-uppercase"
                                   placeholder="Enter UK postcode, such as E1 0BJ"
                                   value="{{ $currentPostcode }}"
                                   maxlength="10"
                                   autocomplete="postal-code"
                                   required>
                            <button type="button" id="btnFindPostcode" class="btn-find-address">
                                <span id="findBtnText">Find Address</span>
                                <span id="findBtnSpinner" class="spinner-border spinner-border-sm ms-1 d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                        <div id="postcodeFeedback" class="booking-field-error d-none" role="alert"></div>
                        <div id="postcodeSuccessHint" class="booking-field-hint d-none"></div>
                    </div>

                    <!-- "Don't know your postcode?" Manual Entry Trigger (Matches Screenshot 1) -->
                    <div class="mb-3">
                        <button type="button" id="btnDontKnowPostcode" class="booking-inline-action-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span>Don't know your postcode? Enter address manually</span>
                        </button>
                    </div>

                    <!-- SELECT YOUR ADDRESS DROPDOWN SECTION (Shown after valid postcode lookup) -->
                    <div id="addressDropdownSection" class="mb-3 {{ (!empty($currentPostcode) && !$currentIsManual) ? '' : 'd-none' }}">
                        <label for="selectedAddressInput" class="form-label booking-field-label">Select Your Address</label>
                        <input type="hidden" name="selected_address" id="selectedAddressInput" value="{{ $currentSelectedAddress }}">

                        <!-- Custom Accessible Searchable/Scrollable Dropdown matching Screenshot 3 -->
                        <div class="address-select-widget" id="addressSelectWidget">
                            <button type="button"
                                    id="addressDropdownTrigger"
                                    class="address-dropdown-trigger"
                                    aria-haspopup="listbox"
                                    aria-expanded="false">
                                <span id="addressTriggerLabel" class="text-truncate">
                                    {{ $currentSelectedAddress ?: 'CHOOSE YOUR ADDRESS FROM THE LIST...' }}
                                </span>
                                <svg class="dropdown-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>

                            <div id="addressDropdownMenu" class="address-dropdown-menu" role="listbox" aria-label="Matching UK addresses">
                                <div class="address-dropdown-search-wrap">
                                    <input type="text"
                                           id="addressListFilterInput"
                                           class="form-control form-control-sm text-uppercase"
                                           placeholder="FILTER ADDRESSES (E.G. FLAT NUMBER OR STREET)..."
                                           autocomplete="off">
                                </div>
                                <ul id="addressOptionsList" class="address-options-list">
                                    @if ($currentSelectedAddress && !$currentIsManual)
                                        <li class="address-option-item is-selected" role="option" aria-selected="true" data-address-value="{{ $currentSelectedAddress }}">
                                            {{ $currentSelectedAddress }}
                                        </li>
                                    @endif
                                </ul>
                                <!-- Bottom Fixed Option: "My address isn't listed" -->
                                <div class="address-not-listed-footer">
                                    <button type="button" id="btnAddressNotListed" class="btn-address-not-listed">
                                        <span>+ My address isn't listed</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="addressSelectError" class="booking-field-error d-none" role="alert"></div>
                    </div>

                    <!-- MANUAL ADDRESS ENTRY SECTION (Shown when "My address isn't listed" clicked or fallback) -->
                    <div id="manualAddressSection" class="mb-3 {{ $currentIsManual ? '' : 'd-none' }}">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label for="manualAddressInput" class="form-label booking-field-label mb-0">Enter Your Address</label>
                            <button type="button" id="btnBackToDropdown" class="btn-link-small d-none">
                                Select from dropdown instead
                            </button>
                        </div>
                        <input type="text"
                               id="manualAddressInput"
                               name="manual_address"
                               class="form-control booking-form-control text-uppercase"
                               placeholder="E.G. FLAT 64, BROCKMER HOUSE, CROWDER STREET, LONDON"
                               value="{{ $currentManualAddress ?: ($currentIsManual ? $currentSelectedAddress : '') }}"
                               maxlength="300"
                               autocomplete="street-address">
                        <div id="manualAddressError" class="booking-field-error d-none" role="alert"></div>
                    </div>

                    <!-- ADDRESS TYPE & ADDRESS DETAILS (Shown once an address is selected or manually entered) -->
                    @php
                        $showFollowupFields = !empty($currentSelectedAddress) || !empty($currentManualAddress) || $currentIsManual;
                    @endphp
                    <div id="addressFollowupSection" class="{{ $showFollowupFields ? '' : 'd-none' }}">
                        <!-- 3 Horizontal Address Types: HOME | OFFICE | HOTEL -->
                        <div class="mb-3">
                            <label class="form-label booking-field-label">Address Type</label>
                            <input type="hidden" name="address_type" id="addressTypeInput" value="{{ $currentAddressType ?: 'HOME' }}">

                            <div class="address-type-row" role="radiogroup" aria-label="Address Type">
                                <!-- HOME -->
                                <button type="button"
                                        class="address-type-btn {{ ($currentAddressType ?: 'HOME') === 'HOME' ? 'is-active' : '' }}"
                                        data-address-type="HOME"
                                        role="radio"
                                        aria-checked="{{ ($currentAddressType ?: 'HOME') === 'HOME' ? 'true' : 'false' }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                    </svg>
                                    <span>HOME</span>
                                </button>

                                <!-- OFFICE -->
                                <button type="button"
                                        class="address-type-btn {{ $currentAddressType === 'OFFICE' ? 'is-active' : '' }}"
                                        data-address-type="OFFICE"
                                        role="radio"
                                        aria-checked="{{ $currentAddressType === 'OFFICE' ? 'true' : 'false' }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                                    </svg>
                                    <span>OFFICE</span>
                                </button>

                                <!-- HOTEL -->
                                <button type="button"
                                        class="address-type-btn {{ $currentAddressType === 'HOTEL' ? 'is-active' : '' }}"
                                        data-address-type="HOTEL"
                                        role="radio"
                                        aria-checked="{{ $currentAddressType === 'HOTEL' ? 'true' : 'false' }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                                        <path d="M9 22v-4h6v4"></path>
                                        <path d="M8 6h.01"></path>
                                        <path d="M16 6h.01"></path>
                                        <path d="M12 6h.01"></path>
                                        <path d="M12 10h.01"></path>
                                        <path d="M8 10h.01"></path>
                                        <path d="M16 10h.01"></path>
                                    </svg>
                                    <span>HOTEL</span>
                                </button>
                            </div>
                        </div>

                        <!-- Hotel Room Number Input (Shown immediately below Address Type ONLY when HOTEL is selected) -->
                        <div id="postcodeRoomNumberWrap" class="mb-3 {{ $currentAddressType === 'HOTEL' ? '' : 'd-none' }}">
                            <label for="postcodeRoomNumberInput" class="form-label booking-field-label">Room Number</label>
                            <input type="text"
                                   id="postcodeRoomNumberInput"
                                   name="room_number"
                                   class="form-control booking-form-control text-uppercase"
                                   placeholder="Enter hotel room number"
                                   value="{{ $currentRoomNumber }}"
                                   maxlength="50"
                                   {{ $currentAddressType === 'HOTEL' ? '' : 'disabled' }}>
                            <div id="postcodeRoomNumberError" class="booking-field-error d-none" role="alert"></div>
                        </div>

                        <!-- Address Details Input -->
                        <div class="mb-4">
                            <label for="addressDetailsInput" class="form-label booking-field-label">Address Details</label>
                            <input type="text"
                                   id="addressDetailsInput"
                                   name="address_details"
                                   class="form-control booking-form-control text-uppercase"
                                   placeholder="Flat number, floor, building name, or delivery instructions"
                                   value="{{ $currentAddressDetails }}"
                                   maxlength="255">
                            <div class="form-text small text-muted mt-1">
                                Example: FLAT 5, 2ND FLOOR, LEFT SIDE ENTRANCE
                            </div>
                        </div>
                    </div>

                    <!-- "Staying in a hotel?" Promotional Switch Card (Matches Screenshot 1) -->
                    <div class="booking-switch-info-card mb-4">
                        <div class="switch-card-content">
                            <h3 class="switch-card-title">Staying in a hotel?</h3>
                            <p class="switch-card-desc">No need to wait for our driver - leave your items at the reception.</p>
                            <button type="button" class="switch-card-link" data-go-screen="hotel">
                                Search for your hotel
                            </button>
                        </div>
                        <div class="switch-card-pin" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                    </div>

                    <button type="submit" id="btnPostcodeNext" class="btn-booking-next w-100" disabled>
                        <span>Next</span>
                        <span class="ms-1">&rarr;</span>
                    </button>
                </form>
            </section>

            <!-- ===============================================================
                 SCREEN 3: HOTEL SEARCH SCREEN
                 Matches Screenshot 2
                 =============================================================== -->
            <section id="screenHotelSearch" class="booking-step-screen {{ $activeScreen === 'hotel' ? '' : 'd-none' }}" aria-labelledby="headingHotelSearch">
                <form id="formHotelAddress" action="{{ route('booking.address.store') }}" method="POST" novalidate>
                    @csrf
                    <input type="hidden" name="collection_mode" value="hotel">
                    <input type="hidden" name="address_type" value="HOTEL">

                    <div class="mb-3">
                        <a href="{{ route('booking.services') }}" class="booking-back-btn" id="btnBackToServicesFromHotel">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            <span>Back</span>
                        </a>
                    </div>

                    <div class="booking-header-block mb-3">
                        <span class="step-pill-tag">Step 2 of Booking</span>
                        <h1 id="headingHotelSearch" class="booking-screen-title">Search for your hotel</h1>
                        <p class="booking-screen-subtitle mb-0">
                            No need to wait for our driver - leave your items at the reception.
                        </p>
                    </div>

                    <!-- Hotel Name Search Input -->
                    <div class="mb-2">
                        <label for="hotelSearchInput" class="visually-hidden">Enter your hotel name</label>
                        <div class="position-relative">
                            <input type="text"
                                   id="hotelSearchInput"
                                   name="hotel_name"
                                   class="form-control booking-form-control text-uppercase"
                                   placeholder="Enter your hotel name"
                                   value="{{ $currentHotelName }}"
                                   maxlength="180"
                                   autocomplete="off"
                                   required>
                            <span id="hotelSearchSpinner" class="spinner-border spinner-border-sm position-absolute top-50 end-0 translate-middle-y me-3 d-none" role="status"></span>
                        </div>
                        <div id="hotelNameError" class="booking-field-error d-none" role="alert"></div>
                    </div>

                    <!-- "Show hotels near me" Geolocation Trigger (Matches Screenshot 2) -->
                    <div class="mb-3">
                        <button type="button" id="btnHotelsNearMe" class="booking-inline-action-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span>Show hotels near me</span>
                        </button>
                        <div id="hotelGeoStatus" class="small text-muted mt-1 d-none"></div>
                    </div>

                    <!-- Hotel Search Results Dropdown / List -->
                    <div id="hotelResultsContainer" class="hotel-results-box mb-3 d-none">
                        <div class="small fw-bold text-secondary mb-1">Matching Hotels:</div>
                        <ul id="hotelResultsList" class="hotel-results-list" role="listbox" aria-label="Matching hotels"></ul>
                    </div>

                    <!-- Hotel Street Address, Room Number, and Collection Type Cards -->
                    @php
                        $hasValidHotelAddr = mb_strlen(trim((string) $currentHotelName)) >= 2 && mb_strlen(trim((string) $currentHotelAddress)) >= 5;
                    @endphp
                    <div id="hotelAddressFields" class="mb-3">
                        <div class="mb-3">
                            <label for="hotelAddressInput" class="form-label booking-field-label">Hotel Address &amp; Postcode</label>
                            <input type="text"
                                   id="hotelAddressInput"
                                   name="hotel_address"
                                   class="form-control booking-form-control text-uppercase"
                                   placeholder="E.G. 100 WHITECHAPEL ROAD, LONDON, E1 1JG"
                                   value="{{ $currentHotelAddress }}"
                                   maxlength="350"
                                   required>
                            <div id="hotelAddressError" class="booking-field-error d-none" role="alert"></div>
                        </div>

                        <!-- Room Number Input (Matches Reference Image 2) -->
                        <div class="mb-3">
                            <label for="hotelRoomNumberInput" class="form-label booking-field-label">Room Number</label>
                            <input type="text"
                                   id="hotelRoomNumberInput"
                                   name="room_number"
                                   class="form-control booking-form-control text-uppercase"
                                   placeholder="Enter hotel room number"
                                   value="{{ $currentRoomNumber }}"
                                   maxlength="50"
                                   required>
                            <div id="hotelRoomNumberError" class="booking-field-error d-none" role="alert"></div>
                        </div>

                        <!-- HOTEL COLLECTION TYPE: Shown only after a hotel address is selected or entered (Matches Reference Image 2) -->
                        <div id="hotelCollectionTypeSection" class="mb-3 {{ $hasValidHotelAddr ? '' : 'd-none' }}">
                            <h3 class="hotel-collection-type-heading mb-2">Select collection type</h3>

                            <div class="hotel-collection-cards-grid" role="radiogroup" aria-label="Select collection type">
                                <!-- OPTION 1: USE RECEPTION -->
                                <label class="hotel-collection-card {{ $currentHotelCollectionType === 'use_reception' ? 'is-selected' : '' }}"
                                       for="hotel_col_use_reception"
                                       data-hotel-collection-card="use_reception">
                                    <input type="radio"
                                           class="visually-hidden js-hotel-collection-radio"
                                           id="hotel_col_use_reception"
                                           name="hotel_collection_type"
                                           value="use_reception"
                                           {{ $currentHotelCollectionType === 'use_reception' ? 'checked' : '' }}>
                                    <div class="hotel-collection-icon-circle" aria-hidden="true">
                                        <!-- Reception Bell Icon -->
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 4v2"></path>
                                            <path d="M10 4h4"></path>
                                            <path d="M4 17a8 8 0 0 1 16 0H4z"></path>
                                            <line x1="2" y1="20" x2="22" y2="20"></line>
                                        </svg>
                                    </div>
                                    <div class="hotel-collection-title">Use reception</div>
                                    <p class="hotel-collection-desc">My hotel has a reception and I can leave my order there.</p>
                                </label>

                                <!-- OPTION 2: MEET DRIVER -->
                                <label class="hotel-collection-card {{ $currentHotelCollectionType === 'meet_driver' ? 'is-selected' : '' }}"
                                       for="hotel_col_meet_driver"
                                       data-hotel-collection-card="meet_driver">
                                    <input type="radio"
                                           class="visually-hidden js-hotel-collection-radio"
                                           id="hotel_col_meet_driver"
                                           name="hotel_collection_type"
                                           value="meet_driver"
                                           {{ $currentHotelCollectionType === 'meet_driver' ? 'checked' : '' }}>
                                    <div class="hotel-collection-icon-circle" aria-hidden="true">
                                        <!-- Delivery Truck Icon -->
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="1" y="4" width="14" height="12" rx="1"></rect>
                                            <polygon points="15 8 19 8 23 12 23 16 15 16 15 8"></polygon>
                                            <circle cx="5.5" cy="18.5" r="2.5"></circle>
                                            <circle cx="18.5" cy="18.5" r="2.5"></circle>
                                        </svg>
                                    </div>
                                    <div class="hotel-collection-title">Meet driver</div>
                                    <p class="hotel-collection-desc">I can't use the reception so I will meet the driver in person.</p>
                                </label>
                            </div>

                            <div id="hotelCollectionTypeError" class="booking-field-error d-none mt-2" role="alert">
                                Please select a collection type (Use reception or Meet driver).
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="hotelDetailsInput" class="form-label booking-field-label">Additional Instructions (Optional)</label>
                            <input type="text"
                                   id="hotelDetailsInput"
                                   name="address_details"
                                   class="form-control booking-form-control text-uppercase"
                                   placeholder="REGISTERED GUEST NAME OR CONCIERGE NOTES"
                                   value="{{ $currentMode === 'hotel' ? $currentAddressDetails : '' }}"
                                   maxlength="255">
                        </div>
                    </div>

                    <!-- "Not in a hotel?" Light Informational Card (Matches Screenshot 2) -->
                    <div class="booking-switch-info-card mb-4">
                        <div class="switch-card-content">
                            <h3 class="switch-card-title">Not in a hotel?</h3>
                            <button type="button" class="switch-card-link" data-go-screen="postcode">
                                Search by postcode or address
                            </button>
                        </div>
                        <div class="switch-card-pin" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                    </div>

                    <button type="submit" id="btnHotelNext" class="btn-booking-next w-100" disabled>
                        <span>Next</span>
                        <span class="ms-1">&rarr;</span>
                    </button>
                </form>
            </section>

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
                                                BDT {{ number_format((float) $savedSvc['price'], 2) }}@if(!empty($savedSvc['unit'])) / {{ $savedSvc['unit'] }}@endif
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
