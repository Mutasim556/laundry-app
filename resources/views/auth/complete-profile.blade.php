<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Complete Your Profile | FreshGo Laundry</title>
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
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <div class="text-center mb-4">
                        <span class="badge rounded-pill px-3 py-2 mb-2 fw-bold text-dark" style="background: #FFF8E5; border: 1px solid #FFC629;">
                            Verified: {{ $email }}
                        </span>
                        <h1 class="h3 fw-bold text-dark mb-1">Complete Your Profile</h1>
                        <p class="text-secondary small mb-0">Just a few details to finish creating your account.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger small rounded-3">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('customer.register.store') }}" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="regName" class="form-label fw-semibold small text-dark">Full Name <span class="text-danger">*</span></label>
                            <input type="text"
                                   id="regName"
                                   name="name"
                                   class="form-control booking-form-control"
                                   placeholder="Enter your full name"
                                   value="{{ old('name') }}"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">Mobile Number <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-5 col-sm-4">
                                    @include('shared.country-code-picker', ['pickerId' => 'regCountryPicker', 'selectedCountryCode' => '+44'])
                                </div>
                                <div class="col-7 col-sm-8">
                                    <input type="tel"
                                           name="phone"
                                           class="form-control booking-form-control"
                                           placeholder="7700900077"
                                           value="{{ old('phone') }}"
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="regGender" class="form-label fw-semibold small text-dark">Gender <span class="text-muted">(Optional)</span></label>
                            <select name="gender" id="regGender" class="form-select booking-form-control">
                                <option value="">Select gender</option>
                                <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Other</option>
                                <option value="prefer_not_to_say" {{ old('gender') === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 rounded-pill py-3 fw-bold text-dark shadow-sm">
                            Create Account
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
