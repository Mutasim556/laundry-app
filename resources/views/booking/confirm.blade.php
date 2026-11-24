<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Review your booking summary, choose a payment method, and confirm your laundry booking.">
    <title>Review &amp; Confirm | FreshGo Laundry</title>

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

    <style>
        .rc-summary-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 1.25rem;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        }
        .rc-section-block {
            padding: 0.9rem 0;
            border-bottom: 1px solid #F1F5F9;
        }
        .rc-section-block:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }
        .rc-section-block:first-child {
            padding-top: 0;
        }
        .rc-section-title {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748B;
        }
        .rc-edit-link {
            font-size: 0.8rem;
            font-weight: 600;
            color: #171717;
            text-decoration: none;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            background: #FFF8E5;
            border: 1px solid #FFC629;
            transition: background-color 0.15s ease;
        }
        .rc-edit-link:hover {
            background: #FFC629;
            color: #171717;
        }
        .rc-payment-option-card {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem;
            border: 2px solid #E2E8F0;
            border-radius: 14px;
            background: #FFFFFF;
            cursor: pointer;
            transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        }
        .rc-payment-option-card:hover {
            border-color: #CBD5E1;
        }
        .rc-payment-option-card.is-selected {
            border-color: #FFC629;
            background: #FFF8E5;
            box-shadow: 0 4px 12px rgba(255, 198, 41, 0.16);
        }
        .rc-payment-radio {
            margin-top: 0.2rem;
            accent-color: #171717;
            width: 1.1rem;
            height: 1.1rem;
            cursor: pointer;
        }
        .rc-payment-detail-box {
            background: #FFF8E5;
            border: 1px solid #FDE68A;
            border-radius: 12px;
            padding: 1rem;
        }
        .rc-confirm-btn {
            background-color: #FFC629;
            color: #171717;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1rem;
            padding: 0.95rem 1.5rem;
            transition: transform 0.12s ease, background-color 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 6px 16px rgba(255, 198, 41, 0.28);
        }
        .rc-confirm-btn:hover:not(:disabled) {
            background-color: #F5B800;
            color: #171717;
            transform: translateY(-1px);
        }
        .rc-confirm-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
    </style>
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
        $currency = $pricing['currency'] ?? 'BDT';
        $services = $booking['services'] ?? [];
        $lineItems = $pricing['line_items'] ?? $pricing['items'] ?? $pricing['services'] ?? [];
        $address = $booking['address'] ?? [];
        $schedule = $booking['schedule'] ?? [];
        $contact = $booking['details'] ?? ($booking['contact'] ?? []);
        $confirmData = $booking['confirm'] ?? [];

        $selectedPaymentMethod = old('payment_method', $confirmData['payment_method'] ?? '');
        $serviceFee = (float) ($pricing['service_fee'] ?? 0);
        $deliveryFee = (float) ($pricing['collection_delivery_fee'] ?? $pricing['delivery_fee'] ?? 0);
        $isFreeDelivery = !empty($pricing['is_free_delivery']);
        $estimatedSubtotal = (float) ($pricing['estimated_services_total'] ?? $pricing['estimated_subtotal'] ?? 0);
        $estimatedTotal = (float) ($pricing['estimated_total'] ?? ($estimatedSubtotal + $serviceFee + $deliveryFee));

        $fullName = trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? ''));
        if ($fullName === '' && !empty($contact['customer_name'])) {
            $fullName = (string) $contact['customer_name'];
        }
        $phoneDisplay = trim(($contact['country_code'] ?? '') . ' ' . ($contact['phone_number'] ?? ''));
        if ($phoneDisplay === '' && !empty($contact['customer_phone'])) {
            $phoneDisplay = (string) $contact['customer_phone'];
        }
        $addressType = strtoupper((string) ($address['address_type'] ?? 'HOME'));
        $hotelCollectionLabel = !empty($address['hotel_collection_type'])
            ? ucwords(str_replace('_', ' ', (string) $address['hotel_collection_type']))
            : '';
    @endphp

    <main class="container booking-main-container"
          id="bookingConfirmApp"
          data-options-url="{{ route('booking.confirm.options') }}"
          data-currency="{{ $currency }}"
          data-stripe-key="{{ $stripePublishableKey ?? '' }}"
          data-stripe-configured="{{ !empty($stripeConfigured) ? '1' : '0' }}"
          data-setup-intent-url="{{ route('booking.stripe.setup-intent') }}">
        <div class="booking-card-shell mx-auto">

            <!-- Clickable 5-Step Booking Progress Bar -->
            @include('booking.partials.progress-bar', ['currentBookingStep' => 5, 'bookingSteps' => $bookingSteps ?? null])

            <!-- Back & Heading -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <a href="{{ route('booking.details') }}" class="btn btn-link text-decoration-none text-dark fw-semibold px-0">
                    &larr; Back
                </a>
                <span class="badge rounded-pill text-dark fw-semibold px-3 py-2" style="background-color: #FFF8E5; border: 1px solid #FFC629;">
                    Step 5 of 5
                </span>
            </div>

            <div class="mb-4">
                <h1 class="h3 fw-bold mb-1" style="color: #171717;">Review and confirm</h1>
                <p class="mb-0 small" style="color: #64748B;">
                    Review your booking details, select a payment method, and click Confirm Booking.
                </p>
            </div>

            <!-- Flash / Validation Alerts -->
            @if (session('error'))
                <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-3 py-2 px-3 small fw-semibold" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success rounded-4 border-0 shadow-sm mb-3 py-2 px-3 small fw-semibold" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-3 py-2 px-3 small fw-semibold" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- 1. Compact Read-Only Booking Summary -->
            <section class="rc-summary-card mb-4" aria-label="Booking Summary">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h6 fw-bold mb-0" style="color: #171717;">Price breakdown</h2>
                    <span class="small fw-medium" style="color: #64748B;">Read-only</span>
                </div>

                <!-- Selected Services & Estimated Pricing -->
                <div class="rc-section-block">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="rc-section-title">Selected Services</span>
                        <a href="{{ route('booking.services') }}" class="rc-edit-link">Edit</a>
                    </div>

                    @php
                        $displayServices = !empty($lineItems) ? $lineItems : $services;
                    @endphp
                    @if (!empty($displayServices))
                        <div class="d-flex flex-column gap-2 mb-2">
                            @foreach ($displayServices as $idx => $svc)
                                @php
                                    $rawSvc = $services[$idx] ?? [];
                                    $svcName = $svc['category_name'] ?? $svc['name'] ?? $svc['service_name'] ?? ($rawSvc['name'] ?? 'Laundry Service');
                                    $subName = $svc['option_name'] ?? $svc['sub_service_name'] ?? ($rawSvc['option_name'] ?? $rawSvc['sub_service_name'] ?? null);
                                    $specialReq = $svc['special_request'] ?? ($rawSvc['special_request'] ?? null);
                                    $unitPrice = isset($svc['unit_price'])
                                        ? (float) $svc['unit_price']
                                        : (isset($svc['estimated_price']) ? (float) $svc['estimated_price'] : null);
                                    $pricingType = $svc['pricing_type'] ?? ($unitPrice !== null && $unitPrice > 0 ? 'per_kg' : 'item_based');
                                @endphp
                                <div class="d-flex justify-content-between align-items-start gap-2 small">
                                    <div>
                                        <div class="fw-semibold" style="color: #171717;">{{ $svcName }}</div>
                                        @if (!empty($subName))
                                            <div style="color: #64748B;">{{ $subName }}</div>
                                        @endif
                                        @if (!empty($specialReq))
                                            <div class="fst-italic" style="color: #64748B;">Note: {{ $specialReq }}</div>
                                        @endif
                                    </div>
                                    <div class="fw-semibold text-end text-nowrap" style="color: #171717;">
                                        @if ($unitPrice !== null && $unitPrice > 0)
                                            {{ $currency }} {{ number_format($unitPrice, 2) }}{{ $pricingType === 'per_kg' ? ' / kg' : '' }}
                                        @else
                                            Item-Based
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="small mb-2" style="color: #64748B;">No services selected.</p>
                    @endif

                    <div class="pt-2 mt-2 border-top small">
                        <div class="d-flex justify-content-between mb-1" style="color: #64748B;">
                            <span>Estimated Services</span>
                            <span>{{ $currency }} {{ number_format($estimatedSubtotal, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1" style="color: #64748B;">
                            <span>Service Fee</span>
                            <span>{{ $currency }} {{ number_format($serviceFee, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1" style="color: #64748B;">
                            <span>Collection &amp; Delivery Fee</span>
                            <span>
                                @if ($isFreeDelivery)
                                    <span class="badge rounded-pill bg-success-subtle text-success fw-semibold me-1">FREE</span>
                                @endif
                                {{ $currency }} {{ number_format($deliveryFee, 2) }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold pt-1" style="color: #171717;">
                            <span>Estimated Total</span>
                            <span>{{ $currency }} {{ number_format($estimatedTotal, 2) }}</span>
                        </div>
                        <div class="mt-1" style="font-size: 0.75rem; color: #64748B;">
                            Final amount calculated after collection and inspection.
                        </div>
                    </div>
                </div>

                <!-- Collection & Delivery Schedule -->
                <div class="rc-section-block">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="rc-section-title">Collection &amp; Delivery Schedule</span>
                        <a href="{{ route('booking.schedule') }}" class="rc-edit-link">Edit</a>
                    </div>
                    <div class="row g-2 small">
                        <div class="col-sm-6">
                            <div class="fw-semibold" style="color: #171717;">Collection</div>
                            <div style="color: #64748B;">
                                {{ $schedule['collection_date'] ?? 'Not set' }}
                                @if (!empty($schedule['collection_slot']))
                                    &bull; {{ $schedule['collection_slot'] }}
                                @endif
                            </div>
                            @if (!empty($schedule['collection_instructions']))
                                <div style="color: #64748B;">{{ $schedule['collection_instructions'] }}</div>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <div class="fw-semibold" style="color: #171717;">Delivery</div>
                            <div style="color: #64748B;">
                                {{ $schedule['delivery_date'] ?? 'Not set' }}
                                @if (!empty($schedule['delivery_slot']))
                                    &bull; {{ $schedule['delivery_slot'] }}
                                @endif
                            </div>
                            @if (!empty($schedule['delivery_instructions']))
                                <div style="color: #64748B;">{{ $schedule['delivery_instructions'] }}</div>
                            @endif
                        </div>
                    </div>
                    @if (!empty($schedule['driver_notes']))
                        <div class="small mt-2" style="color: #64748B;">
                            <strong style="color: #171717;">Driver Note:</strong> {{ $schedule['driver_notes'] }}
                        </div>
                    @endif
                </div>

                <!-- Collection Address / Hotel Details -->
                <div class="rc-section-block">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="rc-section-title">Collection Address</span>
                        <a href="{{ route('booking.address') }}" class="rc-edit-link">Edit</a>
                    </div>
                    <div class="small">
                        <span class="badge rounded-pill text-dark fw-semibold mb-1" style="background-color: #FFF8E5; border: 1px solid #FFC629;">
                            {{ $addressType }}
                        </span>
                        @if (!empty($address['hotel_name']))
                            <div class="fw-semibold" style="color: #171717;">{{ $address['hotel_name'] }}</div>
                        @endif
                        <div class="fw-medium" style="color: #171717;">
                            {{ $address['selected_address'] ?? 'Address not provided' }}
                        </div>
                        <div style="color: #64748B;">
                            @if (!empty($address['postcode']))
                                Postcode: {{ $address['postcode'] }}
                            @endif
                            @if (!empty($address['room_number']))
                                &bull; Room: {{ $address['room_number'] }}
                            @endif
                            @if (!empty($hotelCollectionLabel))
                                &bull; {{ $hotelCollectionLabel }}
                            @endif
                            @if (!empty($address['address_details']))
                                &bull; {{ $address['address_details'] }}
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Contact Details -->
                <div class="rc-section-block">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="rc-section-title">Contact Details</span>
                        <a href="{{ route('booking.details') }}" class="rc-edit-link">Edit</a>
                    </div>
                    <div class="small">
                        <div class="fw-semibold" style="color: #171717;">{{ $fullName ?: 'Guest Customer' }}</div>
                        @if (!empty($contact['company_name']))
                            <div style="color: #64748B;">
                                {{ $contact['company_name'] }}
                                @if (!empty($contact['tax_number']))
                                    &bull; Tax ID: {{ $contact['tax_number'] }}
                                @endif
                            </div>
                        @endif
                        <div style="color: #64748B;">
                            @if ($phoneDisplay)
                                {{ $phoneDisplay }}
                            @endif
                            @if (!empty($contact['customer_email']))
                                &bull; {{ $contact['customer_email'] }}
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. Choose Payment Method -->
            <section class="rc-summary-card mb-4" aria-label="Choose Payment Method">
                <h2 class="h6 fw-bold mb-1" style="color: #171717;">Enter payment details</h2>
                <p class="small mb-3" style="color: #64748B;">
                    You will not be charged until your order is weighed and the final amount is confirmed.
                </p>

                <div class="d-flex flex-column gap-2" id="paymentMethodList">
                    @forelse ($paymentMethods as $pmKey => $pm)
                        @php
                            $key = is_array($pm) ? ($pm['key'] ?? $pmKey) : $pmKey;
                            $name = is_array($pm) ? ($pm['name'] ?? $pm['label'] ?? ucfirst((string) $key)) : (string) $pm;
                            $desc = is_array($pm) ? ($pm['description'] ?? '') : '';
                            $badge = is_array($pm) ? ($pm['badge'] ?? '') : '';
                            $isSelected = ($selectedPaymentMethod === $key);
                        @endphp
                        <label class="rc-payment-option-card {{ $isSelected ? 'is-selected' : '' }}" data-payment-card="{{ $key }}">
                            <input type="radio"
                                   class="rc-payment-radio js-payment-radio"
                                   name="payment_method_choice"
                                   value="{{ $key }}"
                                   data-pm-name="{{ $name }}"
                                   data-pm-desc="{{ $desc }}"
                                   data-pm-badge="{{ $badge }}"
                                   {{ $isSelected ? 'checked' : '' }}>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <span class="fw-bold small" style="color: #171717;">{{ $name }}</span>
                                    @if (!empty($badge))
                                        <span class="badge rounded-pill text-dark fw-semibold" style="background-color: #FFC629; font-size: 0.7rem;">
                                            {{ $badge }}
                                        </span>
                                    @endif
                                </div>
                                @if (!empty($desc))
                                    <div class="small mt-1" style="color: #64748B;">{{ $desc }}</div>
                                @endif
                            </div>
                        </label>
                    @empty
                        <div class="alert alert-warning rounded-3 small mb-0">
                            No payment methods are currently available. Please contact support.
                        </div>
                    @endforelse
                </div>

                <div id="paymentMethodError" class="text-danger small fw-semibold mt-2 {{ $errors->has('payment_method') ? '' : 'd-none' }}">
                    {{ $errors->first('payment_method', 'Please select a payment method to continue.') }}
                </div>

                <!-- Payment Method Details Area -->
                <div id="paymentDetailsArea" class="mt-3">
                    <!-- Stripe Card Panel -->
                    <div id="stripeCardPanel" class="rc-payment-detail-box {{ $selectedPaymentMethod === 'stripe' ? '' : 'd-none' }}">
                        <div class="small fw-semibold mb-1" style="color: #171717;">Stripe Card Payment</div>
                        <div class="small" style="color: #64748B;">
                            Pay securely via Stripe once your booking is confirmed and your final order amount is verified.
                        </div>
                    </div>

                    <!-- PayPal Panel -->
                    <div id="paypalDetailsPanel" class="rc-payment-detail-box {{ $selectedPaymentMethod === 'paypal' ? '' : 'd-none' }}">
                        <div class="small fw-semibold mb-1" style="color: #171717;">PayPal Checkout</div>
                        <div class="small" style="color: #64748B;">
                            You will be able to complete payment via PayPal after your booking is confirmed.
                        </div>
                    </div>

                    <!-- Cash on Delivery Panel -->
                    <div id="codDetailsPanel" class="rc-payment-detail-box {{ $selectedPaymentMethod === 'cod' ? '' : 'd-none' }}">
                        <div class="small fw-semibold mb-1" style="color: #171717;">Cash on Delivery</div>
                        <div class="small" style="color: #64748B;">
                            Pay in cash when your cleaned laundry is delivered. Final charges are calculated after item count/weighing.
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. Confirm Booking Form & Submit Button -->
            <form method="POST" action="{{ route('booking.confirm.store') }}" id="formBookingConfirm" novalidate>
                @csrf
                <input type="hidden" name="payment_method" id="formInputPaymentMethod" value="{{ $selectedPaymentMethod }}">
                <input type="hidden" name="stripe_setup_intent_id" id="formInputStripeSetupIntentId" value="">
                <input type="hidden" name="stripe_payment_method_id" id="formInputStripePaymentMethodId" value="">
                <input type="hidden" name="saved_payment_method_id" id="formInputSavedPaymentMethodId" value="">
                <input type="hidden" name="save_card" id="formInputSaveCard" value="1">
                <input type="hidden" name="stripe_consent" id="formInputStripeConsent" value="0">

                <button type="submit" id="btnConfirmBooking" class="btn rc-confirm-btn w-100 d-flex align-items-center justify-content-center gap-2" {{ empty($paymentMethods) ? 'disabled' : '' }}>
                    <span id="btnConfirmBookingText">Confirm Booking</span>
                    <span id="btnConfirmBookingSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </form>

        </div>
    </main>

    @if (!empty($stripeConfigured))
        <script src="https://js.stripe.com/v3/"></script>
    @endif
    <script src="{{ asset('js/booking-confirm.js') }}?v={{ filemtime(public_path('js/booking-confirm.js')) }}"></script>
</body>
</html>
