<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Orders | FreshGo Laundry</title>
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

    <main class="container booking-main-container py-4">
        <div class="booking-confirm-shell mx-auto">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <div>
                    <h1 class="booking-screen-title mb-1">My Orders</h1>
                    <p class="booking-screen-subtitle mb-0">View your confirmed laundry bookings. Confirmed orders are locked and read-only.</p>
                </div>
                <a href="{{ route('booking.services', ['new' => 1]) }}" class="btn btn-warning fw-bold text-dark rounded-pill px-4">
                    + New Booking
                </a>
            </div>

            @if (!auth()->check())
                <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: #FFF8E5; border: 1px solid #FFC629 !important;">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold text-dark mb-2">Guest Order Lookup</h2>
                        <p class="small text-secondary mb-3">Enter your booking reference and email address to view your confirmed order.</p>
                        <form method="POST" action="{{ route('orders.lookup') }}" class="row g-2">
                            @csrf
                            <div class="col-12 col-md-5">
                                <input type="text" name="reference" class="form-control text-uppercase" placeholder="e.g. LND-2026-000001" value="{{ old('reference') }}" required>
                            </div>
                            <div class="col-12 col-md-5">
                                <input type="email" name="email" class="form-control" placeholder="Booking email address" value="{{ old('email') }}" required>
                            </div>
                            <div class="col-12 col-md-2 d-grid">
                                <button type="submit" class="btn btn-dark fw-semibold">Find Order</button>
                            </div>
                        </form>
                        @if ($errors->any())
                            <div class="text-danger small fw-semibold mt-2">{{ $errors->first() }}</div>
                        @endif
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-body p-3 p-md-4">
                        <form method="POST" action="{{ route('orders.claim') }}" class="row g-2 align-items-center">
                            @csrf
                            <div class="col-12 col-md-5 small fw-semibold text-dark">
                                Placed a guest order with <strong>{{ auth()->user()->email }}</strong>?
                            </div>
                            <div class="col-8 col-md-5">
                                <input type="text" name="reference" class="form-control form-control-sm text-uppercase" placeholder="Enter Booking Reference (e.g. LND-2026-000001)" required>
                            </div>
                            <div class="col-4 col-md-2 d-grid">
                                <button type="submit" class="btn btn-sm btn-dark fw-semibold">Link Order</button>
                            </div>
                        </form>
                        @if (session('success'))
                            <div class="text-success small fw-semibold mt-2">{{ session('success') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="text-danger small fw-semibold mt-2">{{ $errors->first() }}</div>
                        @endif
                    </div>
                </div>
            @endif

            @if ($bookings->isEmpty())
                <div class="card border-0 shadow-sm rounded-4 text-center p-5">
                    <h2 class="h5 fw-bold text-dark mb-2">No Confirmed Orders Found</h2>
                    <p class="text-secondary small mb-3">Once you confirm a laundry booking, it will appear here permanently.</p>
                    <div>
                        <a href="{{ route('booking.services', ['new' => 1]) }}" class="btn btn-warning fw-bold text-dark rounded-pill px-4">
                            Book Laundry Service
                        </a>
                    </div>
                </div>
            @else
                <div class="row g-3">
                    @foreach ($bookings as $booking)
                        @php
                            $schedule = $booking->schedule_snapshot ?? [];
                            $services = collect($booking->services_snapshot ?? [])->pluck('name')->filter()->implode(', ');
                        @endphp
                        <div class="col-12">
                            <div class="card border-0 shadow-sm rounded-4">
                                <div class="card-body p-3 p-md-4">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <span class="badge bg-warning text-dark fw-bold rounded-pill px-3 py-1">{{ $booking->reference }}</span>
                                            <span class="small text-muted ms-2">{{ $booking->confirmed_at?->format('d M Y, H:i') }}</span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge rounded-pill px-2 py-1 fw-semibold" style="{{ \App\Models\Booking::statusBadgeStyle($booking->booking_status) }}">{{ $booking->getStatusLabel() }}</span>
                                            <span class="badge bg-dark text-white text-uppercase">{{ str_replace('_', ' ', $booking->payment_status) }}</span>
                                        </div>
                                    </div>

                                    <div class="row g-2 small mt-1 align-items-center">
                                        <div class="col-12 col-md-5">
                                            <div class="text-muted">Services</div>
                                            <div class="fw-semibold text-dark">{{ $services ?: 'Laundry Order' }}</div>
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <div class="text-muted">Collection &amp; Delivery</div>
                                            <div class="text-dark">
                                                Col: {{ $schedule['collection_date'] ?? '-' }} ({{ $schedule['collection_slot'] ?? '-' }})<br>
                                                Del: {{ $schedule['delivery_date'] ?? '-' }} ({{ $schedule['delivery_slot'] ?? '-' }})
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-2 text-md-end mt-2 mt-md-0">
                                            <a href="{{ route('orders.show', $booking->reference) }}" class="btn btn-sm btn-warning fw-bold text-dark rounded-pill px-3">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </main>

    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
