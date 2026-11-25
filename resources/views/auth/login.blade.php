<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login / Sign Up | FreshGo Laundry</title>
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/booking-address.css') }}">
</head>
<body class="booking-page-body">
    
    @include('shared.header')

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <div class="text-center mb-4">
                        <span class="badge rounded-pill px-3 py-2 mb-2 fw-bold text-dark" style="background: #FFF8E5; border: 1px solid #FFC629;">
                            Passwordless Email Sign-In
                        </span>
                        <h1 class="h3 fw-bold text-dark mb-1">Welcome Back</h1>
                        <p class="text-secondary small mb-0">Enter your email address to continue.</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success small rounded-3">{{ session('status') }}</div>
                    @endif

                    @if (!empty($isTestingOtpActive))
                        <div class="alert alert-warning small rounded-3 py-2">
                            Testing environment — use the configured test OTP.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('customer.login.send-otp') }}" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="loginEmail" class="form-label fw-semibold small text-dark">Email Address</label>
                            <input type="email"
                                   id="loginEmail"
                                   name="email"
                                   class="form-control booking-form-control @error('email') is-invalid @enderror"
                                   placeholder="you@example.com"
                                   value="{{ old('email') }}"
                                   required
                                   autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-warning w-100 rounded-pill py-3 fw-bold text-dark shadow-sm">
                            Continue
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    @include('shared.footer')
    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
