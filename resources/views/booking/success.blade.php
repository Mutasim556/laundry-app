<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Booking Confirmed | FreshGo Laundry</title>
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/booking-address.css') }}">
</head>
<body class="booking-page-body">
    <div class="d-lg-none px-3 pt-3 bg-white border-bottom">
        @include('shared.header-mobile')
    </div>
    <div class="d-none d-lg-block">
        @include('shared.header-desktop')
    </div>

    @php
        $address = $booking->address_snapshot ?? [];
        $schedule = $booking->schedule_snapshot ?? [];
        $services = $booking->services_snapshot ?? [];
        $paymentSnap = $booking->payment_snapshot ?? [];
        $gatewayLabel = $paymentSnap['payment_method_name'] ?? strtoupper((string) $booking->payment_gateway);
        $paymentStatusLabel = ucwords(str_replace('_', ' ', (string) $booking->payment_status));
    @endphp

    <main class="container booking-main-container py-4">
        <div class="booking-confirm-shell mx-auto" style="max-width: 780px;">
            <div class="text-center mb-4">
                <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle"
                     style="width: 68px; height: 68px; background: #FFF8E5; border: 2px solid #FFC629;">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h1 class="booking-screen-title mb-1">Booking Confirmed!</h1>
                <p class="text-secondary mb-3">
                    Your booking has been successfully received. Your order details are now locked.
                </p>

                <div class="d-inline-flex flex-wrap align-items-center justify-content-center gap-2 px-4 py-2 rounded-pill"
                     style="background: #FFF8E5; border: 1.5px solid #FFC629;">
                    <span class="small text-secondary fw-semibold">Booking Reference:</span>
                    <strong class="text-dark fs-6" id="bookingRefCode">{{ $booking->reference }}</strong>
                    <button type="button"
                            class="btn btn-sm btn-warning fw-bold text-dark rounded-pill px-2 py-0"
                            onclick="navigator.clipboard?.writeText('{{ $booking->reference }}'); this.textContent='Copied';">
                        Copy
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: #FFFFFF;">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-3 h-100" style="background: #FAFAF7; border: 1px solid #EAE6DC;">
                                <div class="small text-muted fw-semibold mb-1">Selected Services</div>
                                <ul class="list-unstyled mb-0 small">
                                    @foreach ($services as $svc)
                                        <li class="mb-1">
                                            <strong class="text-dark">{{ $svc['name'] ?? 'Laundry Service' }}</strong>
                                            @if (!empty($svc['option_name']))
                                                <span class="text-secondary"> — {{ $svc['option_name'] }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-3 h-100" style="background: #FAFAF7; border: 1px solid #EAE6DC;">
                                <div class="small text-muted fw-semibold mb-1">Payment Details</div>
                                <div class="fw-bold text-dark small">{{ $gatewayLabel }}</div>
                                <div class="mt-1">
                                    <span class="badge bg-warning text-dark fw-semibold">{{ $paymentStatusLabel }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-3 h-100" style="background: #FAFAF7; border: 1px solid #EAE6DC;">
                                <div class="small text-muted fw-semibold mb-1">Collection</div>
                                <div class="fw-bold text-dark small">
                                    {{ $schedule['collection_date_label'] ?? ($schedule['collection_date'] ?? '-') }}
                                    ({{ $schedule['collection_date'] ?? '' }})
                                </div>
                                <div class="small text-secondary">{{ $schedule['collection_slot'] ?? '-' }}</div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-3 h-100" style="background: #FAFAF7; border: 1px solid #EAE6DC;">
                                <div class="small text-muted fw-semibold mb-1">Delivery</div>
                                <div class="fw-bold text-dark small">
                                    {{ $schedule['delivery_date_label'] ?? ($schedule['delivery_date'] ?? '-') }}
                                    ({{ $schedule['delivery_date'] ?? '' }})
                                </div>
                                <div class="small text-secondary">{{ $schedule['delivery_slot'] ?? '-' }}</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 rounded-3" style="background: #FAFAF7; border: 1px solid #EAE6DC;">
                                <div class="small text-muted fw-semibold mb-1">Collection Address</div>
                                @if (!empty($address['hotel_name']))
                                    <div class="fw-bold text-dark small">{{ $address['hotel_name'] }}</div>
                                @endif
                                <div class="small text-dark">{{ $address['selected_address'] ?? '-' }}</div>
                                @if (!empty($address['room_number']))
                                    <div class="small text-secondary">Room: {{ $address['room_number'] }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="{{ route('orders.show', $booking->reference) }}" class="btn btn-warning fw-bold text-dark rounded-pill px-4 py-2">
                    View Booking
                </a>
                <a href="{{ route('orders.index') }}" class="btn btn-dark fw-semibold rounded-pill px-4 py-2">
                    My Orders
                </a>
                <a href="{{ url('/') }}" class="btn btn-outline-dark fw-semibold rounded-pill px-4 py-2">
                    Back to Home
                </a>
            </div>
        </div>
    </main>

    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
