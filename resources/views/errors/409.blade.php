<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Locked | FreshGo Laundry</title>
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">
</head>
<body class="bg-light d-flex align-items-center min-vh-100 py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-7 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                         style="width: 60px; height: 60px; background: #FFF8E5; border: 2px solid #FFC629;">
                        <span class="fw-bold fs-4 text-dark">🔒</span>
                    </div>
                    <h1 class="h4 fw-bold text-dark mb-2">Booking Already Confirmed</h1>
                    <p class="text-secondary small mb-4">
                        {{ $message ?? 'This booking has been confirmed and permanently locked. Confirmed order details cannot be edited or deleted.' }}
                    </p>
                    <div class="d-grid gap-2">
                        @if (!empty($reference))
                            <a href="{{ route('orders.show', $reference) }}" class="btn btn-warning fw-bold text-dark rounded-pill py-2">
                                View Confirmed Booking ({{ $reference }})
                            </a>
                        @endif
                        <a href="{{ route('orders.index') }}" class="btn btn-outline-dark fw-semibold rounded-pill py-2">
                            My Orders
                        </a>
                        <a href="{{ route('booking.services', ['new' => 1]) }}" class="btn btn-light fw-semibold rounded-pill py-2">
                            Start a New Booking
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
