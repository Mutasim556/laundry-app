<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Profile | FreshGo Laundry</title>
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

    <main class="container pt-4 pt-md-5" style="padding-bottom: calc(116px + env(safe-area-inset-bottom, 0px));">
        <div class="row justify-content-center">
            <div class="col-12 col-md-9 col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-dark fs-4 flex-shrink-0"
                             style="width: 60px; height: 60px; background: #FFF8E5; border: 2px solid #FFC629;">
                            {{ $user->initials }}
                        </div>
                        <div>
                            <h1 class="h4 fw-bold text-dark mb-0">{{ $user->name }}</h1>
                            <div class="small text-secondary">{{ $user->email }} <span class="badge bg-success-subtle text-success ms-1">Verified</span></div>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="alert alert-success small rounded-3">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger small rounded-3">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('account.profile.update') }}" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="profileName" class="form-label fw-semibold small text-dark">Full Name</label>
                            <input type="text" id="profileName" name="name" class="form-control booking-form-control" value="{{ old('name', $user->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">Verified Email</label>
                            <input type="email" class="form-control booking-form-control bg-light" value="{{ $user->email }}" readonly disabled>
                            <small class="text-muted">Verified email cannot be changed directly without re-verification.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-dark">Mobile Number</label>
                            <div class="row g-2">
                                <div class="col-5 col-sm-4">
                                    @include('shared.country-code-picker', ['pickerId' => 'profileCountryPicker', 'selectedCountryCode' => $user->country_code ?: '+44'])
                                </div>
                                <div class="col-7 col-sm-8">
                                    <input type="tel" name="phone" class="form-control booking-form-control" value="{{ old('phone', $user->phone) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="profileGender" class="form-label fw-semibold small text-dark">Gender</label>
                            <select name="gender" id="profileGender" class="form-select booking-form-control">
                                <option value="">Prefer not to specify</option>
                                <option value="male" {{ old('gender', $user->gender) === 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $user->gender) === 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender', $user->gender) === 'other' ? 'selected' : '' }}>Other</option>
                                <option value="prefer_not_to_say" {{ old('gender', $user->gender) === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-warning rounded-pill px-5 py-2 fw-bold text-dark shadow-sm">
                            Save Changes
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
