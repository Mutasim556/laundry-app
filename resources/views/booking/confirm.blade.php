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
        $serviceFee = (float) ($pricing['service_fee'] ?? 0.0);
        $collectionDeliveryFee = (float) ($pricing['collection_delivery_fee'] ?? 40.0);
        $baseDeliveryFee = (float) ($pricing['base_delivery_fee'] ?? 40.0);
        $freeDeliveryThreshold = (float) ($pricing['free_delivery_threshold'] ?? 600.0);
        $isFreeDelivery = (bool) ($pricing['is_free_delivery'] ?? false);
        $minOrderAmount = (float) ($pricing['min_order_amount'] ?? 150.0);
        $tipOption = (string) ($pricing['tip_option'] ?? ($confirmOptions['tip_option'] ?? '0'));
        $tipAmount = (float) ($pricing['tip_amount'] ?? ($confirmOptions['tip_amount'] ?? 0.0));
        $estimatedTotal = (float) ($pricing['estimated_total'] ?? 0.0);
        $dueToday = (float) ($pricing['due_today'] ?? 0.0);

        $repeatEnabled = !empty($savedSchedule['repeat_order']);
        $currentRepeatFrequency = $repeatEnabled ? ($savedSchedule['repeat_frequency'] ?? 'weekly') : 'none';

        $selectedPaymentMethod = $confirmOptions['payment_method'] ?? null;
        $selectedPaymentMeta = ($selectedPaymentMethod && isset($paymentMethods[$selectedPaymentMethod]))
            ? $paymentMethods[$selectedPaymentMethod]
            : null;

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
          data-delivery-fee="{{ number_format($collectionDeliveryFee, 2, '.', '') }}">

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
                                        @elseif (!empty($svc['price']))
                                            <span class="rc-price-pill">ITEM BASED (From {{ $currency }} {{ number_format((float) $svc['price'], 2) }}/{{ $svc['unit'] ?: 'pc' }})</span>
                                        @else
                                            <span class="rc-price-pill">ITEM BASED</span>
                                        @endif
                                    </span>
                                </div>
                            @endforeach

                            <!-- Selected Services Estimated Base Rate -->
                            <div class="rc-price-row">
                                <span class="rc-price-label text-secondary">Selected Services (Base Estimate)</span>
                                <span class="rc-price-value fw-bold" id="priceSelectedServicesValue">
                                    {{ $currency }} {{ number_format($estimatedServicesTotal, 2) }}
                                </span>
                            </div>

                            <!-- Service Fee -->
                            <div class="rc-price-row">
                                <span class="rc-price-label text-secondary">Service Fee</span>
                                <span class="rc-price-value" id="priceServiceFeeValue">
                                    {{ $currency }} {{ number_format($serviceFee, 2) }}
                                </span>
                            </div>

                            <!-- Collection & Delivery Fee (Respects Store Free Threshold >= BDT 600.00) -->
                            <div class="rc-price-row">
                                <span class="rc-price-label text-secondary">
                                    Collection &amp; Delivery
                                    @if (!$isFreeDelivery && $freeDeliveryThreshold > 0)
                                        <small class="d-block text-muted" style="font-size: 0.72rem;">
                                            Free on orders over {{ $currency }} {{ number_format($freeDeliveryThreshold, 0) }}
                                        </small>
                                    @endif
                                </span>
                                <span class="rc-price-value" id="priceDeliveryFeeValue">
                                    @if ($isFreeDelivery)
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

                            <!-- Estimated Total After Item Count -->
                            <div class="rc-price-row rc-price-estimated-row pt-2 mt-1 border-top">
                                <span class="rc-price-label fw-semibold text-dark">
                                    Estimated Total <small class="text-muted fw-normal">(charged after item count)</small>
                                </span>
                                <span class="rc-price-value fw-bold text-dark" id="priceEstimatedTotalValue">
                                    {{ $currency }} {{ number_format($estimatedTotal, 2) }}
                                </span>
                            </div>

                            <!-- Prominent Due Today Total Row -->
                            <div class="rc-due-today-bar mt-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="rc-due-today-label">Due Today</span>
                                    <span class="rc-due-today-amount" id="priceDueTodayValue">
                                        {{ $currency }} {{ number_format($dueToday, 2) }}
                                    </span>
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

                    <!-- 7. SUBSCRIBE & SAVE / REPEAT ORDER SCHEDULE (Synchronized with Step 3 Repeat Order setting) -->
                    <section class="rc-section-block mb-4" aria-labelledby="headingSubscribeSave" id="subscribeSaveSection">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h2 id="headingSubscribeSave" class="rc-section-heading mb-0">Subscribe &amp; Repeat Schedule</h2>
                            <span class="rc-sub-note-pill">Synced with Step 3</span>
                        </div>

                        <div class="rc-subscribe-cards-stack" role="radiogroup" aria-label="Recurring collection schedule">
                            <!-- One-Time Order (No recurring schedule) -->
                            <label class="rc-subscribe-card {{ $currentRepeatFrequency === 'none' ? 'is-selected' : '' }}"
                                   for="sub_freq_none"
                                   data-repeat-card="none">
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="radio"
                                               class="form-check-input js-confirm-repeat-radio m-0"
                                               id="sub_freq_none"
                                               name="confirm_repeat_frequency"
                                               value="none"
                                               {{ $currentRepeatFrequency === 'none' ? 'checked' : '' }}>
                                        <span class="rc-sub-title">One-time order only</span>
                                    </div>
                                    <span class="rc-sub-price-tag">Standard</span>
                                </div>
                                <div class="rc-sub-desc">Single collection and delivery with no recurring schedule.</div>
                            </label>

                            <!-- Weekly Repeat Plan -->
                            <label class="rc-subscribe-card {{ $currentRepeatFrequency === 'weekly' ? 'is-selected' : '' }}"
                                   for="sub_freq_weekly"
                                   data-repeat-card="weekly">
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="radio"
                                               class="form-check-input js-confirm-repeat-radio m-0"
                                               id="sub_freq_weekly"
                                               name="confirm_repeat_frequency"
                                               value="weekly"
                                               {{ $currentRepeatFrequency === 'weekly' ? 'checked' : '' }}>
                                        <span class="rc-sub-title">Weekly Collection</span>
                                    </div>
                                    <span class="rc-sub-price-tag">Every 7 days</span>
                                </div>
                                <div class="rc-sub-desc">
                                    Reserve your preferred weekly collection slot. Standard service rates apply; no automatic recurring card charge.
                                </div>
                            </label>

                            <!-- Bi-weekly Repeat Plan -->
                            <label class="rc-subscribe-card {{ $currentRepeatFrequency === 'bi_weekly' ? 'is-selected' : '' }}"
                                   for="sub_freq_bi_weekly"
                                   data-repeat-card="bi_weekly">
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="radio"
                                               class="form-check-input js-confirm-repeat-radio m-0"
                                               id="sub_freq_bi_weekly"
                                               name="confirm_repeat_frequency"
                                               value="bi_weekly"
                                               {{ $currentRepeatFrequency === 'bi_weekly' ? 'checked' : '' }}>
                                        <span class="rc-sub-title">Bi-weekly Collection</span>
                                    </div>
                                    <span class="rc-sub-price-tag">Every 14 days</span>
                                </div>
                                <div class="rc-sub-desc">
                                    Scheduled collection every two weeks. Standard service rates apply; cancel or reschedule anytime.
                                </div>
                            </label>

                            <!-- Monthly Repeat Plan -->
                            <label class="rc-subscribe-card {{ $currentRepeatFrequency === 'monthly' ? 'is-selected' : '' }}"
                                   for="sub_freq_monthly"
                                   data-repeat-card="monthly">
                                <div class="d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="radio"
                                               class="form-check-input js-confirm-repeat-radio m-0"
                                               id="sub_freq_monthly"
                                               name="confirm_repeat_frequency"
                                               value="monthly"
                                               {{ $currentRepeatFrequency === 'monthly' ? 'checked' : '' }}>
                                        <span class="rc-sub-title">Monthly Collection</span>
                                    </div>
                                    <span class="rc-sub-price-tag">Once a month</span>
                                </div>
                                <div class="rc-sub-desc">
                                    Convenient monthly laundry pickup at standard database pricing.
                                </div>
                            </label>
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

                                            @if (!empty($svc['price']))
                                                <span class="badge rounded-pill bg-warning text-dark fw-bold">
                                                    {{ $currency }} {{ number_format((float) $svc['price'], 2) }}@if(!empty($svc['unit'])) / {{ $svc['unit'] }}@endif
                                                </span>
                                            @else
                                                <span class="badge rounded-pill bg-light text-dark border fw-semibold">Item Based</span>
                                            @endif
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

                        <!-- 8F & 9. PAYMENT CARD & SUPPORTED PAYMENT METHOD SELECTOR -->
                        <div class="rc-summary-card mb-2" id="confirmPaymentSection">
                            <div class="rc-summary-card-header">
                                <h3 class="rc-summary-card-title mb-0">Payment</h3>
                                <button type="button"
                                        class="rc-btn-payment-add"
                                        id="btnTogglePaymentSelector"
                                        aria-expanded="{{ $selectedPaymentMeta ? 'false' : 'true' }}"
                                        aria-controls="paymentMethodSelectorPanel">
                                    {{ $selectedPaymentMeta ? 'Edit' : 'Add' }}
                                </button>
                            </div>

                            <!-- Current Payment Summary Line -->
                            <div id="selectedPaymentDisplay" class="rc-summary-body-text">
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
                                        Add a payment method to confirm your order.
                                    </div>
                                @endif
                            </div>

                            <!-- Expandable Payment Method Selection Panel (Uses actual supported business payment methods) -->
                            <div id="paymentMethodSelectorPanel" class="rc-payment-methods-panel mt-3 pt-2 border-top d-none">
                                <div class="small fw-bold text-dark mb-2">Select payment method:</div>

                                <div class="rc-payment-methods-list" role="radiogroup" aria-label="Select payment method">
                                    @foreach ($paymentMethods as $pmKey => $pm)
                                        @php
                                            $isPmChecked = $selectedPaymentMethod === $pmKey;
                                        @endphp
                                        <label class="rc-payment-option-card {{ $isPmChecked ? 'is-selected' : '' }}"
                                               for="pm_radio_{{ $pmKey }}"
                                               data-payment-card="{{ $pmKey }}">
                                            <div class="d-flex align-items-start gap-2">
                                                <input type="radio"
                                                       class="form-check-input js-payment-method-radio mt-1"
                                                       id="pm_radio_{{ $pmKey }}"
                                                       name="payment_method_choice"
                                                       value="{{ $pmKey }}"
                                                       data-pm-name="{{ $pm['name'] }}"
                                                       data-pm-desc="{{ $pm['description'] }}"
                                                       data-pm-badge="{{ $pm['badge'] }}"
                                                       {{ $isPmChecked ? 'checked' : '' }}>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                                        <span class="fw-bold text-dark" style="font-size: 0.86rem;">{{ $pm['name'] }}</span>
                                                        <span class="badge rounded-pill bg-light text-dark border" style="font-size: 0.68rem;">{{ $pm['badge'] }}</span>
                                                    </div>
                                                    <div class="small text-secondary" style="font-size: 0.76rem; line-height: 1.35;">
                                                        {{ $pm['description'] }}
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div id="paymentMethodError" class="booking-field-error d-none mt-2" role="alert">
                                Please click "Add" and select a payment method to continue.
                            </div>
                        </div>

                    </section>
                </div>
            </div>

            <!-- 10. CONTINUE TO PAYMENT FORM & STICKY BUTTON -->
            <form id="formBookingConfirm" action="{{ route('booking.confirm.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="payment_method" id="formInputPaymentMethod" value="{{ $selectedPaymentMethod ?? 'cod' }}">
                <input type="hidden" name="tip_option" id="formInputTipOption" value="{{ $tipOption }}">
                <input type="hidden" name="custom_tip_amount" id="formInputCustomTipAmount" value="{{ $tipAmount }}">
                <input type="hidden" name="repeat_frequency" id="formInputRepeatFrequency" value="{{ $currentRepeatFrequency }}">

                <div class="booking-sticky-footer">
                    <button type="submit"
                            id="btnConfirmBooking"
                            class="btn-booking-next w-100">
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
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/booking-confirm.js') }}" defer></script>
</body>
</html>
