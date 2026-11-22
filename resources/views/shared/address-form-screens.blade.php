@php
    $isBooking = (bool) ($isBookingContext ?? true);
    $submitLabel = $submitButtonLabel ?? ($isBooking ? 'Next' : 'Save Address');
    $backHref = $backUrl ?? route('booking.services');
    $backText = $backLabel ?? 'Back';
    $formActionUrl = $formAction ?? route('booking.address.store');
    $usePutMethod = (bool) ($isEditMode ?? false);
@endphp

<!-- SCREEN 2: HOME / OFFICE UK POSTCODE & ADDRESS SCREEN -->
<section id="screenPostcodeAddress" class="booking-step-screen {{ $activeScreen === 'postcode' ? '' : 'd-none' }}" aria-labelledby="headingAddAddress">
    <form id="formPostcodeAddress" action="{{ $formActionUrl }}" method="POST" novalidate>
        @csrf
        @if ($usePutMethod)
            @method('PUT')
        @endif
        <input type="hidden" name="collection_mode" value="home_office">
        <input type="hidden" name="is_manual" id="inputIsManual" value="{{ $currentIsManual ? '1' : '0' }}">
        <input type="hidden" name="return_to" value="{{ $returnTo ?? '' }}">
        @if ($isBooking)
            <input type="hidden" name="saved_customer_address_id" id="savedCustomerAddressIdInput" value="{{ old('saved_customer_address_id', $savedAddress['saved_customer_address_id'] ?? '') }}">
        @endif

        <div class="mb-3">
            <a href="{{ $backHref }}" class="booking-back-btn" id="btnBackToServicesFromPostcode">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <span>{{ $backText }}</span>
            </a>
        </div>

        <div class="booking-header-block mb-3">
            @if ($isBooking)
                <span class="step-pill-tag">Step 2 of Booking</span>
            @endif
            <h1 id="headingAddAddress" class="booking-screen-title">{{ $isEditMode ?? false ? 'Edit your address' : 'Add your address' }}</h1>
            <p class="booking-screen-subtitle mb-0">
                Enter your address so the driver can find you.
            </p>
        </div>

        @if ($isBooking)
            @if (session('booking_address_saved_notice'))
                <div class="alert alert-success rounded-3 py-2 px-3 small fw-semibold mb-3" role="alert">
                    {{ session('booking_address_saved_notice') }}
                </div>
            @endif
            <div id="savedAddressActionNotice" class="alert alert-success rounded-3 py-2 px-3 small fw-semibold mb-3 d-none" role="alert"></div>

            <div class="saved-addresses-box mb-3 p-3 rounded-4" style="background: #FAFAF7; border: 1.5px solid #EAE6DC;">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <label class="form-label booking-field-label mb-0">Recent Saved Addresses</label>
                    @auth('web')
                        <a href="{{ route('account.addresses.create', ['return_to' => 'booking_address']) }}"
                           class="btn btn-sm btn-warning fw-bold text-dark rounded-pill px-3 py-1"
                           id="btnStep2AddSavedAddress">
                            + Add Address
                        </a>
                    @endauth
                </div>

                @auth('web')
                    @php
                        $savedList = collect($savedAddresses ?? []);
                        $selectedSavedId = (int) old('saved_customer_address_id', $savedAddress['saved_customer_address_id'] ?? 0);
                    @endphp
                    @if ($savedList->isEmpty())
                        <p class="small text-secondary mb-0" id="emptySavedAddressesText">
                            No saved addresses yet. Click <strong>+ Add Address</strong> or enter your postcode below.
                        </p>
                    @else
                        <div class="d-flex flex-column gap-2" id="savedAddressesCardList">
                            @foreach ($savedList as $idx => $savedItem)
                                @php
                                    $itemType = strtolower((string) ($savedItem->address_type ?? 'home'));
                                    $isItemSelected = $selectedSavedId > 0 && $selectedSavedId === (int) $savedItem->id;
                                    $isHiddenOverflow = $idx >= 3;
                                @endphp
                                <div class="saved-address-card p-2 px-3 rounded-3 bg-white d-flex align-items-start justify-content-between gap-2 w-100 {{ $isItemSelected ? 'is-selected' : '' }} {{ $isHiddenOverflow ? 'd-none js-saved-address-extra' : '' }}"
                                     data-saved-card-id="{{ $savedItem->id }}"
                                     style="border: 1.5px solid {{ $isItemSelected ? '#FFC629' : '#EAE6DC' }}; {{ $isItemSelected ? 'background: #FFF8E5 !important;' : '' }}">
                                    <button type="button"
                                            class="saved-address-select-btn p-0 border-0 bg-transparent text-start flex-grow-1 min-w-0 js-select-saved-address"
                                            data-id="{{ $savedItem->id }}"
                                            data-type="{{ strtoupper($itemType) }}"
                                            data-postcode="{{ $savedItem->postcode ?? '' }}"
                                            data-address="{{ $itemType === 'hotel' ? ($savedItem->hotel_address ?? '') : ($savedItem->formatted_address ?? '') }}"
                                            data-details="{{ $savedItem->address_details ?? '' }}"
                                            data-hotel-name="{{ $savedItem->hotel_name ?? '' }}"
                                            data-hotel-address="{{ $savedItem->hotel_address ?? '' }}"
                                            data-room-number="{{ $savedItem->room_number ?? '' }}"
                                            data-hotel-collection-type="{{ $savedItem->hotel_collection_type ?? 'use_reception' }}">
                                        <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                            <span class="badge bg-light text-dark border text-uppercase" style="font-size: 0.65rem;">
                                                {{ $itemType === 'hotel' ? '🏨' : ($itemType === 'office' ? '🏢' : '🏠') }}
                                                {{ $savedItem->label ?: ucfirst($itemType) }}
                                            </span>
                                            @if ($savedItem->is_default)
                                                <span class="badge bg-warning text-dark" style="font-size: 0.64rem;">Default</span>
                                            @endif
                                        </div>
                                        <div class="small fw-semibold text-dark saved-address-summary">
                                            {{ $savedItem->display_summary }}
                                        </div>
                                        @if ($savedItem->postcode)
                                            <div class="text-muted" style="font-size: 0.72rem;">{{ $savedItem->postcode }}</div>
                                        @endif
                                    </button>

                                    <div class="d-flex align-items-center gap-1 flex-shrink-0 saved-address-actions">
                                        <a href="{{ route('account.addresses.edit', [$savedItem->id, 'return_to' => 'booking_address']) }}"
                                           class="btn btn-sm btn-light border rounded-circle p-1 d-inline-flex align-items-center justify-content-center"
                                           style="width: 30px; height: 30px;"
                                           title="Edit address"
                                           aria-label="Edit saved address">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                        </a>
                                        <button type="button"
                                                class="btn btn-sm btn-light border rounded-circle p-1 d-inline-flex align-items-center justify-content-center text-danger js-remove-saved-address"
                                                style="width: 30px; height: 30px;"
                                                data-delete-url="{{ route('account.addresses.destroy', $savedItem->id) }}"
                                                data-card-id="{{ $savedItem->id }}"
                                                title="Remove address"
                                                aria-label="Remove saved address">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if ($savedList->count() > 3)
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-link btn-sm text-dark fw-semibold text-decoration-none p-0" id="btnViewAllSavedAddresses">
                                    View All ({{ $savedList->count() }})
                                </button>
                            </div>
                        @endif
                    @endif
                @else
                    <div class="small text-secondary">
                        <a href="{{ route('login') }}" class="booking-accent-link fw-bold">Log in</a> to view or save your addresses.
                    </div>
                @endauth
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

        <!-- "Don't know your postcode?" Manual Entry Trigger -->
        <div class="mb-3">
            <button type="button" id="btnDontKnowPostcode" class="booking-inline-action-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <span>Don't know your postcode? Enter address manually</span>
            </button>
        </div>

        <!-- SELECT YOUR ADDRESS DROPDOWN SECTION -->
        <div id="addressDropdownSection" class="mb-3 {{ (!empty($currentPostcode) && !$currentIsManual) ? '' : 'd-none' }}">
            <label for="selectedAddressInput" class="form-label booking-field-label">Select Your Address</label>
            <input type="hidden" name="uk_address_id" id="ukAddressIdInput" value="{{ $currentUkAddressId }}">
            <input type="hidden" name="selected_address" id="selectedAddressInput" value="{{ $currentSelectedAddress }}">

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
                    <div class="address-not-listed-footer">
                        <button type="button" id="btnAddressNotListed" class="btn-address-not-listed">
                            <span>+ My address isn't listed</span>
                        </button>
                    </div>
                </div>
            </div>
            <div id="addressSelectError" class="booking-field-error d-none" role="alert"></div>
        </div>

        <!-- MANUAL ADDRESS ENTRY SECTION -->
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

        <!-- ADDRESS TYPE & ADDRESS DETAILS -->
        @php
            $showFollowupFields = !empty($currentSelectedAddress) || !empty($currentManualAddress) || $currentIsManual;
        @endphp
        <div id="addressFollowupSection" class="{{ $showFollowupFields ? '' : 'd-none' }}">
            <div class="mb-3">
                <label class="form-label booking-field-label">Address Type</label>
                <input type="hidden" name="address_type" id="addressTypeInput" value="{{ $currentAddressType ?: 'HOME' }}">

                <div class="address-type-row" role="radiogroup" aria-label="Address Type">
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

            <div class="mb-3">
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

        @if (!$isBooking)
            <div class="mb-3">
                <label for="postcodeLabelInput" class="form-label booking-field-label">Address Label <span class="text-muted fw-normal">(Optional)</span></label>
                <input type="text"
                       id="postcodeLabelInput"
                       name="label"
                       class="form-control booking-form-control"
                       placeholder="e.g. Home, Work, London Hotel"
                       value="{{ $currentLabel ?? '' }}"
                       maxlength="80">
            </div>
            @include('shared.address-contact-fields', ['fieldPrefix' => 'postcode', 'defaultContact' => $defaultContact ?? []])
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="postcodeDefaultCheckbox" {{ !empty($currentIsDefault) ? 'checked' : '' }}>
                <label class="form-check-label small fw-semibold text-dark" for="postcodeDefaultCheckbox">
                    Set as default address
                </label>
            </div>
        @endif

        <!-- "Staying in a hotel?" Promotional Switch Card -->
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

        <button type="submit" id="btnPostcodeNext" class="btn-booking-next w-100" {{ $showFollowupFields ? '' : 'disabled' }}>
            <span>{{ $submitLabel }}</span>
            @if ($isBooking)
                <span class="ms-1">&rarr;</span>
            @endif
        </button>
    </form>
</section>

<!-- SCREEN 3: HOTEL SEARCH SCREEN -->
<section id="screenHotelSearch" class="booking-step-screen {{ $activeScreen === 'hotel' ? '' : 'd-none' }}" aria-labelledby="headingHotelSearch">
    <form id="formHotelAddress" action="{{ $formActionUrl }}" method="POST" novalidate>
        @csrf
        @if ($usePutMethod)
            @method('PUT')
        @endif
        <input type="hidden" name="collection_mode" value="hotel">
        <input type="hidden" name="address_type" value="HOTEL">
        <input type="hidden" name="hotel_id" id="hotelIdInput" value="{{ $currentHotelId }}">
        <input type="hidden" name="return_to" value="{{ $returnTo ?? '' }}">
        @if ($isBooking)
            <input type="hidden" name="saved_customer_address_id" id="hotelSavedCustomerAddressIdInput" value="{{ old('saved_customer_address_id', $savedAddress['saved_customer_address_id'] ?? '') }}">
        @endif

        <div class="mb-3">
            <a href="{{ $backHref }}" class="booking-back-btn" id="btnBackToServicesFromHotel">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <span>{{ $backText }}</span>
            </a>
        </div>

        <div class="booking-header-block mb-3">
            @if ($isBooking)
                <span class="step-pill-tag">Step 2 of Booking</span>
            @endif
            <h1 id="headingHotelSearch" class="booking-screen-title">Search for your hotel</h1>
            <p class="booking-screen-subtitle mb-0">
                No need to wait for our driver - leave your items at the reception.
            </p>
        </div>

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

        <div id="hotelResultsContainer" class="hotel-results-box mb-3 d-none">
            <div class="small fw-bold text-secondary mb-1">Matching Hotels:</div>
            <ul id="hotelResultsList" class="hotel-results-list" role="listbox" aria-label="Matching hotels"></ul>
            <div class="mt-2 pt-2 border-top text-end">
                <button type="button" id="btnManualHotelEntry" class="booking-inline-action-link small">
                    <span>Can't find your hotel? Enter it manually</span>
                </button>
            </div>
        </div>

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

            <div id="hotelCollectionTypeSection" class="mb-3 {{ $hasValidHotelAddr ? '' : 'd-none' }}">
                <h3 class="hotel-collection-type-heading mb-2">Select collection type</h3>

                <div class="hotel-collection-cards-grid" role="radiogroup" aria-label="Select collection type">
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
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 4v2"></path><path d="M10 4h4"></path><path d="M4 17a8 8 0 0 1 16 0H4z"></path><line x1="2" y1="20" x2="22" y2="20"></line>
                            </svg>
                        </div>
                        <div class="hotel-collection-title">Use reception</div>
                        <p class="hotel-collection-desc">My hotel has a reception and I can leave my order there.</p>
                    </label>

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
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="4" width="14" height="12" rx="1"></rect><polygon points="15 8 19 8 23 12 23 16 15 16 15 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle>
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

        @if (!$isBooking)
            <div class="mb-3">
                <label for="hotelLabelInput" class="form-label booking-field-label">Address Label <span class="text-muted fw-normal">(Optional)</span></label>
                <input type="text"
                       id="hotelLabelInput"
                       name="label"
                       class="form-control booking-form-control"
                       placeholder="e.g. Home, Work, London Hotel"
                       value="{{ $currentLabel ?? '' }}"
                       maxlength="80">
            </div>
            @include('shared.address-contact-fields', ['fieldPrefix' => 'hotel', 'defaultContact' => $defaultContact ?? []])
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="hotelDefaultCheckbox" {{ !empty($currentIsDefault) ? 'checked' : '' }}>
                <label class="form-check-label small fw-semibold text-dark" for="hotelDefaultCheckbox">
                    Set as default address
                </label>
            </div>
        @endif

        <!-- "Not in a hotel?" Light Informational Card -->
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

        <button type="submit" id="btnHotelNext" class="btn-booking-next w-100" {{ $hasValidHotelAddr ? '' : 'disabled' }}>
            <span>{{ $submitLabel }}</span>
            @if ($isBooking)
                <span class="ms-1">&rarr;</span>
            @endif
        </button>
    </form>
</section>

@if ($isBooking)
    <!-- Remove Saved Address Confirmation Modal -->
    <div class="modal fade" id="removeSavedAddressModal" tabindex="-1" aria-labelledby="removeSavedAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="removeSavedAddressModalLabel">Remove this saved address?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body small text-secondary pt-2">
                    This address will be removed from your Address Book. Existing confirmed bookings will not be affected.
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4 fw-semibold" id="btnConfirmRemoveSavedAddress">Remove Address</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const noticeEl = document.getElementById('savedAddressActionNotice');

        // View All toggle
        const viewAllBtn = document.getElementById('btnViewAllSavedAddresses');
        viewAllBtn?.addEventListener('click', () => {
            document.querySelectorAll('.js-saved-address-extra').forEach(el => el.classList.remove('d-none'));
            viewAllBtn.closest('div')?.classList.add('d-none');
        });

        // Select saved address
        document.querySelectorAll('.js-select-saved-address').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const type = (btn.dataset.type || 'HOME').toUpperCase();
                const postcode = btn.dataset.postcode || '';
                const address = btn.dataset.address || '';
                const details = btn.dataset.details || '';
                const hotelName = btn.dataset.hotelName || '';
                const hotelAddress = btn.dataset.hotelAddress || '';
                const roomNumber = btn.dataset.roomNumber || '';
                const hotelColType = btn.dataset.hotelCollectionType || 'use_reception';

                document.querySelectorAll('.saved-address-card').forEach(card => {
                    const active = card.dataset.savedCardId === id;
                    card.classList.toggle('is-selected', active);
                    card.style.borderColor = active ? '#FFC629' : '#EAE6DC';
                    card.style.background = active ? '#FFF8E5' : '#FFFFFF';
                });

                const hiddenSavedId = document.getElementById('savedCustomerAddressIdInput');
                const hiddenHotelSavedId = document.getElementById('hotelSavedCustomerAddressIdInput');
                if (hiddenSavedId) hiddenSavedId.value = id;
                if (hiddenHotelSavedId) hiddenHotelSavedId.value = id;

                if (type === 'HOTEL' && hotelName) {
                    document.querySelector('[data-go-screen="hotel"]')?.click();
                    const hName = document.getElementById('hotelSearchInput');
                    const hAddr = document.getElementById('hotelAddressInput');
                    const hRoom = document.getElementById('hotelRoomNumberInput');
                    const hDet = document.getElementById('hotelDetailsInput');
                    if (hName) hName.value = hotelName;
                    if (hAddr) hAddr.value = hotelAddress || address;
                    if (hRoom) hRoom.value = roomNumber;
                    if (hDet) hDet.value = details;
                    document.getElementById('hotelCollectionTypeSection')?.classList.remove('d-none');
                    const colRadio = document.querySelector(`.js-hotel-collection-radio[value="${hotelColType}"]`);
                    if (colRadio) {
                        colRadio.checked = true;
                        colRadio.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    const btnHotelNext = document.getElementById('btnHotelNext');
                    if (btnHotelNext) btnHotelNext.disabled = false;
                    return;
                }

                // Home / Office (postcode or manual)
                const pcInput = document.getElementById('ukPostcodeInput');
                const selInput = document.getElementById('selectedAddressInput');
                const trigLabel = document.getElementById('addressTriggerLabel');
                const manInput = document.getElementById('manualAddressInput');
                const isManInput = document.getElementById('inputIsManual');
                const detInput = document.getElementById('addressDetailsInput');
                const typeInput = document.getElementById('addressTypeInput');

                if (postcode) {
                    if (isManInput) isManInput.value = '0';
                    if (pcInput) pcInput.value = postcode;
                    if (selInput) selInput.value = address;
                    if (trigLabel) trigLabel.textContent = address;
                    document.getElementById('addressDropdownSection')?.classList.remove('d-none');
                    document.getElementById('manualAddressSection')?.classList.add('d-none');
                } else {
                    if (isManInput) isManInput.value = '1';
                    if (manInput) manInput.value = address;
                    if (selInput) selInput.value = address;
                    document.getElementById('manualAddressSection')?.classList.remove('d-none');
                    document.getElementById('addressDropdownSection')?.classList.add('d-none');
                }

                if (detInput) detInput.value = details;
                if (typeInput) typeInput.value = type;
                document.getElementById('addressFollowupSection')?.classList.remove('d-none');
                document.querySelector(`.address-type-btn[data-address-type="${type}"]`)?.click();

                const btnNext = document.getElementById('btnPostcodeNext');
                if (btnNext) btnNext.disabled = false;
            });
        });

        // Remove saved address via confirmation modal + AJAX
        let pendingDeleteUrl = null;
        let pendingCardId = null;
        const modalEl = document.getElementById('removeSavedAddressModal');
        const modal = modalEl && window.bootstrap ? new window.bootstrap.Modal(modalEl) : null;

        document.querySelectorAll('.js-remove-saved-address').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                pendingDeleteUrl = btn.dataset.deleteUrl;
                pendingCardId = btn.dataset.cardId;
                modal?.show();
            });
        });

        document.getElementById('btnConfirmRemoveSavedAddress')?.addEventListener('click', async () => {
            if (!pendingDeleteUrl) return;
            const res = await fetch(pendingDeleteUrl, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            if (!res.ok) return;
            const data = await res.json();
            modal?.hide();

            document.querySelector(`.saved-address-card[data-saved-card-id="${pendingCardId}"]`)?.remove();

            if (data.cleared_selected) {
                const hiddenSavedId = document.getElementById('savedCustomerAddressIdInput');
                const selInput = document.getElementById('selectedAddressInput');
                const trigLabel = document.getElementById('addressTriggerLabel');
                const btnNext = document.getElementById('btnPostcodeNext');
                if (hiddenSavedId) hiddenSavedId.value = '';
                if (selInput) selInput.value = '';
                if (trigLabel) trigLabel.textContent = 'CHOOSE YOUR ADDRESS FROM THE LIST...';
                if (btnNext) btnNext.disabled = true;
            }

            if (noticeEl) {
                noticeEl.textContent = data.message || 'Saved address removed.';
                noticeEl.classList.remove('d-none');
            }
        });
    });
    </script>
@endif


@if (!$isBooking)
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-ab-ctype-radio').forEach(radio => {
        radio.addEventListener('change', () => {
            const fp = radio.dataset.targetPrefix;
            const isComp = radio.value === 'company';
            const section = document.getElementById(`${fp}_contactDetailsSection`);
            section?.querySelectorAll('.customer-type-radio-label').forEach(lbl => {
                const inp = lbl.querySelector('.js-ab-ctype-radio');
                lbl.classList.toggle('is-checked', !!inp?.checked);
            });
            const compWrap = document.getElementById(`${fp}_company_fields`);
            const compName = document.getElementById(`${fp}_company_name`);
            const taxNum = document.getElementById(`${fp}_tax_number`);
            compWrap?.classList.toggle('d-none', !isComp);
            if (compName) {
                compName.disabled = !isComp;
                compName.required = isComp;
            }
            if (taxNum) {
                taxNum.disabled = !isComp;
            }
        });
    });

    document.querySelectorAll('.country-code-picker').forEach(picker => {
        const trigger = picker.querySelector('.js-ab-country-trigger');
        const menu = picker.querySelector('.js-ab-country-menu');
        const search = picker.querySelector('.js-ab-country-search');
        const label = picker.querySelector('.js-ab-country-label');
        const wrapper = picker.closest('.col-5');
        const dialInput = wrapper?.querySelector('input[name="contact_dialing_code"]');
        const isoInput = wrapper?.querySelector('input[name="contact_country_iso"]');
        const opts = Array.from(picker.querySelectorAll('.js-ab-country-opt'));

        trigger?.addEventListener('click', () => {
            const open = menu.classList.contains('d-none');
            menu.classList.toggle('d-none', !open);
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) search?.focus();
        });

        search?.addEventListener('input', () => {
            const q = search.value.trim().toLowerCase();
            opts.forEach(opt => opt.classList.toggle('d-none', q !== '' && !opt.dataset.search.includes(q)));
        });

        opts.forEach(opt => {
            opt.addEventListener('click', () => {
                if (dialInput) dialInput.value = opt.dataset.dial;
                if (isoInput) isoInput.value = opt.dataset.iso;
                if (label) label.textContent = opt.dataset.dial;
                menu.classList.add('d-none');
                trigger?.setAttribute('aria-expanded', 'false');
            });
        });

        document.addEventListener('click', (e) => {
            if (!picker.contains(e.target)) {
                menu?.classList.add('d-none');
                trigger?.setAttribute('aria-expanded', 'false');
            }
        });
    });
});
</script>
@endif
