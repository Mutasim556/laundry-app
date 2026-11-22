<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Review and confirm your FreshGo Laundry booking.">
    <title>Review and Confirm | FreshGo Laundry</title>

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
        $savedServices = $pricing['verified_services'] ?? ($booking['services'] ?? []);
        $savedAddress = $booking['address'] ?? [];
        $savedSchedule = $booking['schedule'] ?? [];
        $savedDetails = $booking['details'] ?? [];
        $confirmOptions = $booking['confirm_options'] ?? [];
        $isConfirmed = !empty($booking['confirmed_at']);

        $currency = $pricing['currency'] ?? 'BDT';
        $estimatedServicesTotal = (float) ($pricing['estimated_services_total'] ?? 0.0);
        $serviceFeeDisplayName = $pricing['service_fee_display_name'] ?? 'Service Fee';
        $serviceFeeType = $pricing['service_fee_type'] ?? 'fixed';
        $serviceFeeValue = (float) ($pricing['service_fee_value'] ?? 0.0);
        $serviceFeeActive = (bool) ($pricing['service_fee_active'] ?? false);
        $serviceFee = (float) ($pricing['service_fee'] ?? 0.0);
        $deliveryFeeDisplayName = $pricing['delivery_fee_display_name'] ?? 'Collection & Delivery';
        $collectionDeliveryFee = (float) ($pricing['collection_delivery_fee'] ?? 0.0);
        $baseDeliveryFee = (float) ($pricing['base_delivery_fee'] ?? 0.0);
        $thresholdEnabled = (bool) ($pricing['threshold_enabled'] ?? false);
        $freeDeliveryThreshold = (float) ($pricing['free_delivery_threshold'] ?? 0.0);
        $thresholdAfterDiscount = (bool) ($pricing['threshold_after_discount'] ?? true);
        $isFreeDelivery = (bool) ($pricing['is_free_delivery'] ?? false);
        $minOrderAmount = (float) ($pricing['min_order_amount'] ?? 150.0);
        $tipOption = (string) ($pricing['tip_option'] ?? ($confirmOptions['tip_option'] ?? '0'));
        $tipAmount = (float) ($pricing['tip_amount'] ?? ($confirmOptions['tip_amount'] ?? 0.0));
        $estimatedTotal = (float) ($pricing['estimated_total'] ?? 0.0);
        $dueToday = (float) ($pricing['due_today'] ?? 0.0);

        $repeatEnabled = !empty($savedSchedule['repeat_order']);
        $currentRepeatFrequency = $repeatEnabled ? ($savedSchedule['repeat_frequency'] ?? 'weekly') : 'none';

        $selectedPaymentMethod = $confirmOptions['payment_method'] ?? null;
        if ($selectedPaymentMethod && !isset($paymentMethods[$selectedPaymentMethod])) {
            $selectedPaymentMethod = null;
        }
        $selectedPaymentMeta = $selectedPaymentMethod ? $paymentMethods[$selectedPaymentMethod] : null;
        $hasPaymentMethods = !empty($paymentMethods);

        $addressType = strtoupper((string) ($savedAddress['address_type'] ?? 'HOME'));
        $isHotelAddress = $addressType === 'HOTEL' || ($savedAddress['collection_mode'] ?? '') === 'hotel';
        $hotelCollectionType = $savedAddress['hotel_collection_type'] ?? null;
        $hotelCollectionLabel = $hotelCollectionType === 'use_reception'
            ? 'Use Reception'
            : ($hotelCollectionType === 'meet_driver' ? 'Meet Driver' : null);

        $customerType = strtolower((string) ($savedDetails['customer_type'] ?? 'individual'));
        $firstName = $savedDetails['first_name'] ?? '';
        $lastName = $savedDetails['last_name'] ?? '';
        $fullName = trim($firstName . ' ' . $lastName) ?: ($savedDetails['customer_name'] ?? '');
    @endphp

    <main class="container booking-main-container"
          id="bookingConfirmApp"
          data-options-url="{{ route('booking.confirm.options') }}"
          data-currency="{{ $currency }}"
          data-min-order="{{ number_format($minOrderAmount, 2, '.', '') }}"
          data-services-subtotal="{{ number_format($estimatedServicesTotal, 2, '.', '') }}"
          data-service-fee="{{ number_format($serviceFee, 2, '.', '') }}"
          data-delivery-fee="{{ number_format($collectionDeliveryFee, 2, '.', '') }}"
          data-stripe-key="{{ $stripePublishableKey ?? '' }}"
          data-stripe-configured="{{ !empty($stripeConfigured) ? '1' : '0' }}"
          data-setup-intent-url="{{ route('booking.stripe.setup-intent') }}">

        <div class="booking-card-shell booking-confirm-shell mx-auto">

            <!-- Clickable 5-Step Booking Progress Bar -->
            @include('booking.partials.progress-bar', ['currentBookingStep' => 5, 'bookingSteps' => $bookingSteps ?? null])

            <!-- Confirmed Order Banner -->
            @if (session('booking_confirmed_success'))
                <div class="rc-confirmed-banner mb-3" role="alert" id="bookingConfirmedBanner">
                    <div class="d-flex align-items-center gap-2">
                        <span class="rc-confirmed-check">✓</span>
                        <div>
                            <div class="fw-bold text-dark">{{ session('booking_confirmed_success') }}</div>
                            @if (!empty($booking['reference']))
                                <div class="small text-secondary">
                                    Reference: <strong id="confirmedBookingReference">{{ $booking['reference'] }}</strong>
                                    &bull; Status: <span class="badge bg-warning text-dark">Pending Item Count &amp; Final Invoice</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-3 py-2 px-3 small" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- 1. Back Button -->
            <div class="mb-3">
                <a href="{{ route('booking.details') }}" class="booking-back-btn" id="btnBackToDetailsStep">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span>Back</span>
                </a>
            </div>

            <!-- 2. Page Header -->
            <div class="booking-header-block mb-4">
                <span class="step-pill-tag">{{ $isConfirmed ? 'Booking Confirmed' : 'Step 5 of Booking' }}</span>
                <h1 class="booking-screen-title">Review and confirm</h1>
                <p class="booking-screen-subtitle mb-0">
                    Verify order details before adding a payment method.
                </p>
            </div>

            <div class="rc-desktop-grid">
                <!-- ===========================================================
                     LEFT / TOP COLUMN: PRICING, PROMO, TIP, SUBSCRIBE & SAVE
                     =========================================================== -->
                <div class="rc-pricing-column">

                    <!-- 3. PRICE BREAKDOWN -->
                    <section class="rc-section-block mb-3" aria-labelledby="headingPriceBreakdown" id="priceBreakdownSection">
                        <h2 id="headingPriceBreakdown" class="rc-section-heading mb-2">Price breakdown</h2>

                        <div class="rc-price-card">
                            <!-- Individual Selected Service Pricing Rows -->
                            @foreach ($savedServices as $svc)
                                <div class="rc-price-row" data-price-service-id="{{ $svc['service_id'] }}">
                                    <span class="rc-price-label">
                                        {{ $svc['option_name'] ?? $svc['name'] }}
                                    </span>
                                    <span class="rc-price-value">
                                        @if (!empty($svc['is_wash_fold']) && !empty($svc['price']))
                                            <span class="rc-price-pill">{{ $currency }} {{ number_format((float) $svc['price'], 2) }} / {{ $svc['unit'] ?: 'kg' }}</span>
                                        @else
                                            <span class="rc-price-pill">Item-Based</span>
                                        @endif
                                    </span>
                                </div>
                            @endforeach

                            <!-- Service Fee -->
                            <div class="rc-price-row">
                                <span class="rc-price-label text-secondary">
                                    {{ $serviceFeeDisplayName }}
                                    @if ($serviceFeeActive && $serviceFeeType === 'percentage')
                                        <small class="d-block text-muted" style="font-size: 0.72rem;">
                                            {{ number_format($serviceFeeValue, 2) }}% applied to confirmed order subtotal
                                        </small>
                                    @endif
                                </span>
                                <span class="rc-price-value" id="priceServiceFeeValue">
                                    @if ($serviceFeeActive && $serviceFeeType === 'percentage' && $serviceFee == 0)
                                        {{ number_format($serviceFeeValue, 2) }}%
                                    @else
                                        {{ $currency }} {{ number_format($serviceFee, 2) }}
                                    @endif
                                </span>
                            </div>

                            <!-- Collection & Delivery Fee -->
                            <div class="rc-price-row">
                                <span class="rc-price-label text-secondary">
                                    {{ $deliveryFeeDisplayName }}
                                    @if (!$isFreeDelivery && $thresholdEnabled && $freeDeliveryThreshold > 0)
                                        <small class="d-block text-muted" style="font-size: 0.72rem;">
                                            Free on confirmed orders of {{ $currency }} {{ number_format($freeDeliveryThreshold, 0) }}+ ({{ $thresholdAfterDiscount ? 'after' : 'before' }} discounts)
                                        </small>
                                    @endif
                                </span>
                                <span class="rc-price-value" id="priceDeliveryFeeValue">
                                    @if ($isFreeDelivery || $collectionDeliveryFee <= 0)
                                        <span class="rc-free-badge">FREE</span>
                                    @else
                                        {{ $currency }} {{ number_format($collectionDeliveryFee, 2) }}
                                    @endif
                                </span>
                            </div>

                            <!-- Driver Tip Row (Shown dynamically when tip > 0) -->
                            <div class="rc-price-row {{ $tipAmount > 0 ? '' : 'd-none' }}" id="priceDriverTipRow">
                                <span class="rc-price-label text-secondary">Driver Tip</span>
                                <span class="rc-price-value fw-bold text-dark" id="priceDriverTipValue">
                                    {{ $currency }} {{ number_format($tipAmount, 2) }}
                                </span>
                            </div>

                            <!-- Final Total Pending Weighing & Item Count -->
                            <div class="rc-due-today-bar mt-2">
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="rc-due-today-label">Order Total</span>
                                        <span class="badge bg-warning text-dark fw-bold">Pending Inspection</span>
                                    </div>
                                    <div class="small text-dark fw-semibold" id="pricePendingNotice">
                                        Final amount calculated after collection and inspection.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 4. PROMO CODE -->
                    <section class="rc-section-block mb-3" aria-labelledby="labelPromoCode" id="promoCodeSection">
                        <div class="rc-promo-box">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="promoCodeInput" id="labelPromoCode" class="rc-promo-label mb-0">Promo code</label>
                                <span class="rc-promo-status-badge" id="promoSystemBadge">No active promotions</span>
                            </div>
                            <div class="rc-promo-input-group">
                                <input type="text"
                                       id="promoCodeInput"
                                       class="form-control booking-form-control rc-promo-input text-uppercase"
                                       placeholder="Enter promo code"
                                       maxlength="40"
                                       autocomplete="off">
                                <button type="button" id="btnApplyPromoCode" class="rc-btn-promo-apply">
                                    Apply
                                </button>
                            </div>
                            <div id="promoCodeFeedback" class="booking-field-error d-none mt-2" role="alert"></div>
                        </div>
                    </section>

                    <!-- 5. PAYMENT INFORMATION NOTICE -->
                    <div class="rc-payment-notice-box mb-4" id="paymentInfoNoticeBox">
                        <div class="rc-notice-content">
                            <p class="rc-notice-text mb-1">
                                <strong>{{ $currency }} {{ number_format($minOrderAmount, 2) }} minimum order</strong> applies.
                                No payment is taken until your order is confirmed, subject to the selected payment method and existing payment rules.
                            </p>
                            <button type="button"
                                    class="rc-notice-link"
                                    id="btnToggleHowChargesWork"
                                    aria-expanded="false"
                                    aria-controls="howChargesWorkCollapse">
                                How charges work
                            </button>
                        </div>
                        <div class="rc-notice-icon" aria-hidden="true">i</div>
                    </div>

                    <div id="howChargesWorkCollapse" class="rc-charges-explain-box mb-4 d-none">
                        <div class="small text-dark fw-bold mb-1">How FreshGo Laundry billing works:</div>
                        <ul class="small text-secondary mb-0 ps-3">
                            <li>Wash &amp; Fold is weighed per kg ({{ $currency }} 500–600/kg) and Wash &amp; Iron / Dry Cleaning is counted per item at our facility.</li>
                            <li>A minimum order of <strong>{{ $currency }} {{ number_format($minOrderAmount, 2) }}</strong> applies. Delivery is <strong>{{ $currency }} {{ number_format($baseDeliveryFee, 2) }}</strong> (FREE on orders over <strong>{{ $currency }} {{ number_format($freeDeliveryThreshold, 2) }}</strong>).</li>
                            <li>You will receive an itemized invoice once your clothes are inspected and counted.</li>
                        </ul>
                    </div>

                    <!-- 6. TIP YOUR DRIVER -->
                    <section class="rc-section-block mb-4" aria-labelledby="headingTipDriver" id="tipDriverSection">
                        <h2 id="headingTipDriver" class="rc-section-heading mb-2">Tip your driver</h2>

                        <div class="rc-tip-card">
                            <!-- 4 Preset Options in a Single Row: No Tip | BDT 50 | BDT 100 | BDT 150 -->
                            <div class="rc-tip-presets-row" role="radiogroup" aria-label="Tip your driver">
                                @foreach ($tipOptions as $opt)
                                    @if ($opt['key'] !== 'custom')
                                        @php
                                            $isTipActive = $tipOption === (string) $opt['key'];
                                        @endphp
                                        <button type="button"
                                                class="rc-tip-btn {{ $isTipActive ? 'is-selected' : '' }}"
                                                data-tip-option="{{ $opt['key'] }}"
                                                data-tip-amount="{{ $opt['amount'] }}"
                                                role="radio"
                                                aria-checked="{{ $isTipActive ? 'true' : 'false' }}">
                                            <span>{{ $opt['label'] }}</span>
                                        </button>
                                    @endif
                                @endforeach
                            </div>

                            <!-- 5th Option: Custom Tip + Helper Banner -->
                            <div class="d-flex align-items-center justify-content-between gap-2 mt-2 pt-1">
                                <span class="rc-tip-helper-badge">100% of your tip goes directly to your driver.</span>
                                <button type="button"
                                        id="btnCustomTipToggle"
                                        class="rc-tip-custom-btn {{ $tipOption === 'custom' ? 'is-selected' : '' }}"
                                        data-tip-option="custom"
                                        role="radio"
                                        aria-checked="{{ $tipOption === 'custom' ? 'true' : 'false' }}">
                                    Custom Tip
                                </button>
                            </div>

                            <!-- Custom Tip Input Container (Shown when "Custom Tip" is selected) -->
                            <div id="customTipInputWrap" class="rc-custom-tip-wrap mt-2 {{ $tipOption === 'custom' ? '' : 'd-none' }}">
                                <label for="customTipInput" class="small fw-bold text-dark d-block mb-1">
                                    Enter custom tip amount ({{ $currency }})
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text rc-currency-addon">{{ $currency }}</span>
                                    <input type="number"
                                           id="customTipInput"
                                           class="form-control booking-form-control"
                                           placeholder="0.00"
                                           min="0"
                                           max="5000"
                                           step="10"
                                           value="{{ $tipOption === 'custom' ? number_format($tipAmount, 2, '.', '') : '' }}">
                                    <button type="button" id="btnApplyCustomTip" class="rc-btn-promo-apply">
                                        Set Tip
                                    </button>
                                </div>
                                <div id="customTipError" class="booking-field-error d-none mt-1" role="alert"></div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- ===========================================================
                     RIGHT / BOTTOM COLUMN: ORDER DETAILS SUMMARY CARDS
                     =========================================================== -->
                <div class="rc-details-column">
                    <section class="rc-section-block mb-4" aria-labelledby="headingOrderDetails" id="orderDetailsSection">
                        <h2 id="headingOrderDetails" class="rc-section-heading mb-2">Order details</h2>

                        <!-- 8A. SERVICES CARD -->
                        <div class="rc-summary-card mb-3" id="confirmServicesSection">
                            <div class="rc-summary-card-header">
                                <h3 class="rc-summary-card-title">Services</h3>
                                <a href="{{ route('booking.services', ['return_to' => 'confirm']) }}"
                                   class="rc-edit-pill-link"
                                   id="btnEditServices">
                                    Edit
                                </a>
                            </div>

                            <div class="rc-services-items-list">
                                @foreach ($savedServices as $svc)
                                    <div class="rc-service-item-row {{ !$loop->last ? 'border-bottom pb-2 mb-2' : '' }}"
                                         data-confirm-service-id="{{ $svc['service_id'] }}">
                                        <div class="d-flex align-items-center justify-content-between gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                @if (!empty($svc['icon_url']))
                                                    <span class="rc-service-mini-icon">
                                                        <img src="{{ $svc['icon_url'] }}" alt="" width="20" height="20">
                                                    </span>
                                                @endif
                                                <div>
                                                    <div class="fw-bold text-dark" style="font-size: 0.90rem;">
                                                        {{ $svc['name'] }}
                                                    </div>
                                                    @if (!empty($svc['option_name']))
                                                        <div class="small fw-semibold" style="color: #9A6B00;">
                                                            {{ $svc['option_name'] }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        @if (!empty($svc['special_request']))
                                            <div class="rc-special-request-note mt-2">
                                                <span class="fw-bold text-dark">Special Request:</span>
                                                <span>{{ $svc['special_request'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 8B. COLLECTION CARD -->
                        <div class="rc-summary-card mb-3" id="confirmCollectionSection">
                            <div class="rc-summary-card-header">
                                <h3 class="rc-summary-card-title">Collection</h3>
                                <a href="{{ route('booking.schedule', ['return_to' => 'confirm']) }}"
                                   class="rc-edit-pill-link"
                                   id="btnEditCollection">
                                    Edit
                                </a>
                            </div>
                            <div class="rc-summary-body-text">
                                <div class="fw-bold text-dark">
                                    {{ $savedSchedule['collection_day_label'] ?? '' }}
                                    @if (!empty($savedSchedule['collection_date']))
                                        <span class="text-muted fw-normal">({{ $savedSchedule['collection_date'] }})</span>
                                    @endif
                                    @if (!empty($savedSchedule['collection_slot']))
                                        &bull; {{ $savedSchedule['collection_slot'] }}
                                    @endif
                                </div>
                                @if (!empty($savedSchedule['collection_instructions']))
                                    <div class="small text-secondary mt-1" id="confirmCollectionInstructions">
                                        {{ $savedSchedule['collection_instructions'] }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- 8C. DELIVERY CARD -->
                        <div class="rc-summary-card mb-3" id="confirmDeliverySection">
                            <div class="rc-summary-card-header">
                                <h3 class="rc-summary-card-title">Delivery</h3>
                                <a href="{{ route('booking.schedule', ['return_to' => 'confirm']) }}"
                                   class="rc-edit-pill-link"
                                   id="btnEditDelivery">
                                    Edit
                                </a>
                            </div>
                            <div class="rc-summary-body-text">
                                <div class="fw-bold text-dark">
                                    {{ $savedSchedule['delivery_day_label'] ?? '' }}
                                    @if (!empty($savedSchedule['delivery_date']))
                                        <span class="text-muted fw-normal">({{ $savedSchedule['delivery_date'] }})</span>
                                    @endif
                                    @if (!empty($savedSchedule['delivery_slot']))
                                        &bull; {{ $savedSchedule['delivery_slot'] }}
                                    @endif
                                </div>
                                @if (!empty($savedSchedule['delivery_instructions']))
                                    <div class="small text-secondary mt-1" id="confirmDeliveryInstructions">
                                        {{ $savedSchedule['delivery_instructions'] }}
                                    </div>
                                @endif
                                @if (!empty($savedSchedule['driver_notes']))
                                    <div class="small text-secondary mt-1 pt-1 border-top">
                                        <strong class="text-dark">Driver Note:</strong> {{ $savedSchedule['driver_notes'] }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- 8D. ADDRESS DETAILS CARD -->
                        <div class="rc-summary-card mb-3" id="confirmAddressSection">
                            <div class="rc-summary-card-header">
                                <div class="d-flex align-items-center gap-2">
                                    <h3 class="rc-summary-card-title mb-0">Address details</h3>
                                    <span class="badge rounded-pill bg-warning text-dark fw-bold" style="font-size: 0.70rem;">
                                        {{ $addressType }}
                                    </span>
                                </div>
                                <a href="{{ route('booking.address', ['return_to' => 'confirm']) }}"
                                   class="rc-edit-pill-link"
                                   id="btnEditAddress">
                                    Edit
                                </a>
                            </div>
                            <div class="rc-summary-body-text">
                                @if (!empty($savedAddress['hotel_name']))
                                    <div class="fw-bold text-dark" id="confirmHotelName">{{ $savedAddress['hotel_name'] }}</div>
                                @endif

                                <div class="fw-semibold text-dark" id="confirmFullAddress">
                                    {{ $savedAddress['selected_address'] ?? '' }}
                                </div>

                                @if (!empty($savedAddress['postcode']) && !str_contains((string) ($savedAddress['selected_address'] ?? ''), (string) $savedAddress['postcode']))
                                    <div class="small text-secondary">Postcode: <strong class="text-dark">{{ $savedAddress['postcode'] }}</strong></div>
                                @endif

                                @if ($isHotelAddress && !empty($savedAddress['room_number']))
                                    <div class="small text-dark mt-1" id="confirmRoomNumber">
                                        <strong>Room Number:</strong> {{ $savedAddress['room_number'] }}
                                    </div>
                                @endif

                                @if ($isHotelAddress && $hotelCollectionLabel)
                                    <div class="small text-dark mt-1" id="confirmHotelCollectionType">
                                        <strong>Collection Type:</strong>
                                        <span class="badge rounded-pill bg-light text-dark border fw-bold">{{ $hotelCollectionLabel }}</span>
                                    </div>
                                @endif

                                @if (!empty($savedAddress['address_details']))
                                    <div class="small text-secondary mt-1" id="confirmAddressDetails">
                                        "{{ $savedAddress['address_details'] }}"
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- 8E. CONTACT DETAILS CARD -->
                        <div class="rc-summary-card mb-3" id="confirmDetailsSection">
                            <div class="rc-summary-card-header">
                                <div class="d-flex align-items-center gap-2">
                                    <h3 class="rc-summary-card-title mb-0">Contact details</h3>
                                    <span class="badge rounded-pill bg-light text-dark border fw-semibold" style="font-size: 0.70rem;">
                                        {{ ucfirst($customerType) }}
                                    </span>
                                </div>
                                <a href="{{ route('booking.details', ['return_to' => 'confirm']) }}"
                                   class="rc-edit-pill-link"
                                   id="btnEditContact">
                                    Edit
                                </a>
                            </div>
                            <div class="rc-summary-body-text">
                                <div class="fw-bold text-dark" id="confirmCustomerFullName">
                                    {{ $fullName }}
                                    @if (!empty($savedDetails['customer_phone']))
                                        <span class="fw-normal text-secondary">, {{ $savedDetails['customer_phone'] }}</span>
                                    @endif
                                </div>

                                @if ($customerType === 'company' && !empty($savedDetails['company_name']))
                                    <div class="small fw-semibold text-dark mt-1" id="confirmCompanyName">
                                        Company: {{ $savedDetails['company_name'] }}
                                        @if (!empty($savedDetails['tax_number']))
                                            <span class="text-muted">(Tax No: {{ $savedDetails['tax_number'] }})</span>
                                        @endif
                                    </div>
                                @endif

                                @if (!empty($savedDetails['customer_email']))
                                    <div class="small text-secondary mt-1" id="confirmCustomerEmail">
                                        {{ $savedDetails['customer_email'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- ===========================================================
                 ROW 2: FULL-WIDTH PAYMENT DETAILS
                 =========================================================== -->
            <div class="row mt-2 mb-3">
                <div class="col-12">
                    <div class="rc-summary-card mb-2" id="confirmPaymentSection">
                        <div class="rc-summary-card-header">
                            <h3 class="rc-summary-card-title mb-0">Enter payment details</h3>
                            <button type="button"
                                    class="rc-btn-payment-add"
                                    id="btnTogglePaymentSelector"
                                    aria-expanded="{{ $selectedPaymentMeta ? 'false' : 'true' }}"
                                    aria-controls="paymentMethodSelectorPanel">
                                {{ $selectedPaymentMeta ? 'Edit' : 'Change' }}
                            </button>
                        </div>

                        <!-- Current Payment Summary Line -->
                        <div id="selectedPaymentDisplay" class="rc-summary-body-text mb-2">
                            @if ($selectedPaymentMeta)
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="fw-bold text-dark" id="selectedPaymentName">{{ $selectedPaymentMeta['name'] }}</div>
                                        <div class="small text-secondary" id="selectedPaymentDesc">{{ $selectedPaymentMeta['description'] }}</div>
                                    </div>
                                    <span class="badge rounded-pill bg-warning text-dark fw-bold" id="selectedPaymentBadge">
                                        {{ $selectedPaymentMeta['badge'] }}
                                    </span>
                                </div>
                            @else
                                <div class="text-secondary small" id="unselectedPaymentPrompt">
                                    Select a payment method below to confirm your order.
                                </div>
                            @endif
                        </div>

                        <!-- Expandable Payment Method Selection Panel -->
                        <div id="paymentMethodSelectorPanel" class="rc-payment-methods-panel mt-2 pt-3 border-top">
                            @if (!$hasPaymentMethods)
                                <div class="alert alert-warning mb-0 small fw-semibold" id="noPaymentMethodsAlert" role="alert">
                                    No payment methods are currently available. Please contact support.
                                </div>
                            @else
                                <div class="rc-payment-methods-list" role="radiogroup" aria-label="Enter payment details">
                                    @foreach ($paymentMethods as $pmKey => $pm)
                                        @php
                                            $isPmChecked = $selectedPaymentMethod === $pmKey;
                                        @endphp
                                        <label class="rc-payment-option-card {{ $isPmChecked ? 'is-selected' : '' }}"
                                               for="pm_radio_{{ $pmKey }}"
                                               data-payment-card="{{ $pmKey }}">
                                            <div class="d-flex align-items-start gap-2 h-100">
                                                <input type="radio"
                                                       class="form-check-input js-payment-method-radio mt-1 flex-shrink-0"
                                                       id="pm_radio_{{ $pmKey }}"
                                                       name="payment_method_choice"
                                                       value="{{ $pmKey }}"
                                                       data-pm-name="{{ $pm['name'] }}"
                                                       data-pm-desc="{{ $pm['description'] }}"
                                                       data-pm-badge="{{ $pm['badge'] }}"
                                                       {{ $isPmChecked ? 'checked' : '' }}>
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="rc-payment-method-icon" aria-hidden="true">
                                                            @if ($pmKey === 'stripe')
                                                                <svg width="18" height="14" viewBox="0 0 24 18" fill="none"><rect x="1" y="1" width="22" height="16" rx="3" stroke="#171717" stroke-width="1.8"/><path d="M1 6H23" stroke="#171717" stroke-width="1.8"/><rect x="4" y="10" width="6" height="3" rx="1" fill="#FFC629"/></svg>
                                                            @elseif ($pmKey === 'paypal')
                                                                <span style="color:#003087;">P</span><span style="color:#0079C1;">P</span>
                                                            @else
                                                                <svg width="18" height="14" viewBox="0 0 24 18" fill="none"><rect x="1" y="2" width="22" height="14" rx="2.5" stroke="#171717" stroke-width="1.8"/><circle cx="12" cy="9" r="3" fill="#FFC629" stroke="#171717" stroke-width="1.4"/></svg>
                                                            @endif
                                                        </span>
                                                        <span class="fw-bold text-dark" style="font-size: 0.86rem;">{{ $pm['name'] }}</span>
                                                    </div>
                                                    <div class="mb-1">
                                                        <span class="badge rounded-pill bg-light text-dark border" style="font-size: 0.66rem;">{{ $pm['badge'] }}</span>
                                                    </div>
                                                    <div class="small text-secondary" style="font-size: 0.76rem; line-height: 1.35;">
                                                        {{ $pm['description'] }}
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>

                                <!-- Full-Width Selected Payment Method Details Area -->
                                <div id="paymentDetailsArea" class="mt-3">
                                    @if (isset($paymentMethods['stripe']))
                                        <div id="stripeCardPanel" class="p-3 rounded-4 border {{ $selectedPaymentMethod === 'stripe' ? '' : 'd-none' }}" style="background: #FFFDF7; border-color: #FFC629 !important;">
                                            @if (!empty($stripeConfigured))
                                                @if (isset($savedCards) && $savedCards->isNotEmpty())
                                                    <div class="mb-3" id="savedCardsListWrap">
                                                        <div class="small fw-bold text-dark mb-2">Your Saved Cards</div>
                                                        @foreach ($savedCards as $card)
                                                            <div class="d-flex align-items-center justify-content-between border rounded-3 p-2 mb-1 bg-white">
                                                                <label class="d-flex align-items-center gap-2 mb-0 flex-grow-1" style="cursor: pointer;">
                                                                    <input type="radio" name="saved_card_choice" class="form-check-input js-saved-card-radio m-0" value="{{ $card->id }}">
                                                                    <span class="small fw-semibold text-dark">{{ $card->display_label }}</span>
                                                                </label>
                                                                <button type="button"
                                                                        class="btn btn-sm btn-link text-danger p-0 text-decoration-none js-delete-saved-card"
                                                                        data-delete-url="{{ route('booking.stripe.saved-cards.destroy', $card->id) }}"
                                                                        title="Remove saved card">Remove</button>
                                                            </div>
                                                        @endforeach
                                                        <label class="d-flex align-items-center gap-2 mt-2 small fw-semibold text-dark" style="cursor: pointer;">
                                                            <input type="radio" name="saved_card_choice" class="form-check-input js-saved-card-radio m-0" value="" checked>
                                                            <span>Use a new card</span>
                                                        </label>
                                                    </div>
                                                @endif

                                                <div id="stripeNewCardWrap">
                                                    <div id="stripeElementLoading" class="small text-secondary py-2">
                                                        <span class="spinner-border spinner-border-sm me-1"></span> Initializing secure Stripe card fields...
                                                    </div>
                                                    <div id="stripePaymentElement"></div>
                                                    <div id="stripeElementError" class="booking-field-error d-none mt-2" role="alert"></div>

                                                    <div class="form-check mt-3">
                                                        <input class="form-check-input" type="checkbox" id="stripeSaveCardCheckbox" {{ auth()->check() ? '' : 'disabled' }}>
                                                        <label class="form-check-label small fw-semibold text-dark" for="stripeSaveCardCheckbox">
                                                            Save this card for future payments
                                                        </label>
                                                        @guest
                                                            <div class="small text-muted" style="font-size: 0.72rem;">Sign in to save cards for future orders. Stripe still stores your card securely for this order's final charge.</div>
                                                        @endguest
                                                    </div>
                                                </div>

                                                <div class="p-2 rounded-3 mt-3 small" style="background: #FFF8E5; border: 1px solid rgba(255,198,41,0.5); font-size: 0.74rem;">
                                                    <div class="form-check mb-0">
                                                        <input class="form-check-input" type="checkbox" id="stripeConsentCheckbox">
                                                        <label class="form-check-label text-dark" for="stripeConsentCheckbox">
                                                            I authorize FreshGo Laundry &amp; Stripe to securely store my payment method and charge the verified final order amount once my laundry is collected, weighed, and itemized. You will be notified of the final invoice before payment is initiated.
                                                        </label>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="small text-danger fw-semibold">
                                                    Stripe is not configured yet. Please select another payment method.
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if (isset($paymentMethods['paypal']))
                                        <div id="paypalDetailsPanel" class="p-3 rounded-4 border {{ $selectedPaymentMethod === 'paypal' ? '' : 'd-none' }}" style="background: #FFFDF7; border-color: #FFC629 !important;">
                                            <div class="fw-bold text-dark small mb-1">{{ $paymentMethods['paypal']['name'] }}</div>
                                            <div class="small text-secondary">
                                                {{ $paymentMethods['paypal']['description'] }} Your PayPal payment link will be available once your laundry is weighed and the final invoice is confirmed.
                                            </div>
                                        </div>
                                    @endif

                                    @if (isset($paymentMethods['cod']))
                                        <div id="codDetailsPanel" class="p-3 rounded-4 border {{ $selectedPaymentMethod === 'cod' ? '' : 'd-none' }}" style="background: #FFFDF7; border-color: #FFC629 !important;">
                                            <div class="fw-bold text-dark small mb-1">{{ $paymentMethods['cod']['name'] }}</div>
                                            <div class="small text-secondary">
                                                {{ $paymentMethods['cod']['instructions'] ?: $paymentMethods['cod']['description'] }}
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="small text-secondary mt-2 pt-2 border-top" style="font-size: 0.78rem;">
                                    You will not be charged until your order is weighed and the final amount is confirmed.
                                </div>
                            @endif
                        </div>

                        <div id="paymentMethodError" class="booking-field-error d-none mt-2" role="alert">
                            Please select a payment method to continue.
                        </div>
                    </div>
                </div>
            </div>

            <!-- 10. CONTINUE TO PAYMENT FORM & STICKY BUTTON -->
            <form id="formBookingConfirm" action="{{ route('booking.confirm.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="payment_method" id="formInputPaymentMethod" value="{{ $selectedPaymentMethod ?? ($hasPaymentMethods ? array_key_first($paymentMethods) : '') }}">
                <input type="hidden" name="tip_option" id="formInputTipOption" value="{{ $tipOption }}">
                <input type="hidden" name="custom_tip_amount" id="formInputCustomTipAmount" value="{{ $tipAmount }}">
                <input type="hidden" name="repeat_frequency" id="formInputRepeatFrequency" value="{{ $currentRepeatFrequency }}">
                <input type="hidden" name="stripe_setup_intent_id" id="formInputStripeSetupIntentId" value="">
                <input type="hidden" name="stripe_payment_method_id" id="formInputStripePaymentMethodId" value="">
                <input type="hidden" name="saved_payment_method_id" id="formInputSavedPaymentMethodId" value="">
                <input type="hidden" name="save_card" id="formInputSaveCard" value="0">
                <input type="hidden" name="stripe_consent" id="formInputStripeConsent" value="0">

                <div class="booking-sticky-footer">
                    <button type="submit"
                            id="btnConfirmBooking"
                            class="btn-booking-next w-100"
                            {{ !$hasPaymentMethods ? 'disabled' : '' }}>
                        <span id="btnConfirmBookingText">
                            {{ $isConfirmed ? 'BOOKING CONFIRMED ✓ (UPDATE & CONTINUE TO PAYMENT →)' : 'CONTINUE TO PAYMENT →' }}
                        </span>
                        <span id="btnConfirmBookingSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>

        </div>
    </main>

    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')
    @if (!empty($stripeConfigured))
        <script src="https://js.stripe.com/v3/"></script>
    @endif
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/booking-confirm.js') }}" defer></script>
</body>
</html>
