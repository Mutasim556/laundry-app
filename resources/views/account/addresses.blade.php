<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Address Book | FreshGo Laundry</title>
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

    <main class="container py-4 py-md-5">
        <div class="booking-confirm-shell mx-auto">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <div>
                    <h1 class="booking-screen-title mb-1">Address Book</h1>
                    <p class="booking-screen-subtitle mb-0">Manage your saved Home, Office, and Hotel collection addresses.</p>
                </div>
                <a href="{{ route('account.addresses.create') }}" class="btn btn-warning fw-bold text-dark rounded-pill px-4">
                    + Add Address
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success small rounded-3">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger small rounded-3">{{ $errors->first() }}</div>
            @endif

            @if ($addresses->isEmpty())
                <div class="card border-0 shadow-sm rounded-4 text-center p-5 bg-white">
                    <h2 class="h5 fw-bold text-dark mb-2">No Saved Addresses Yet</h2>
                    <p class="text-secondary small mb-3">Add a UK address or hotel to speed up future bookings.</p>
                    <div>
                        <a href="{{ route('account.addresses.create') }}" class="btn btn-warning fw-bold text-dark rounded-pill px-4">
                            Add Your First Address
                        </a>
                    </div>
                </div>
            @else
                <div class="row g-3">
                    @foreach ($addresses as $address)
                        <div class="col-12 col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white {{ $address->is_default ? 'border border-2 border-warning' : '' }}">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <span class="badge bg-light text-dark border text-uppercase">{{ $address->address_type }}</span>
                                            @if ($address->label)
                                                <strong class="ms-1 text-dark small">{{ $address->label }}</strong>
                                            @endif
                                        </div>
                                        @if ($address->is_default)
                                            <span class="badge bg-warning text-dark rounded-pill">Default</span>
                                        @endif
                                    </div>

                                    <p class="small text-dark fw-semibold mb-1">{{ $address->display_summary }}</p>
                                    @if ($address->address_details)
                                        <p class="small text-muted mb-1">{{ $address->address_details }}</p>
                                    @endif
                                    @if ($address->hasContactDetails())
                                        <div class="small text-secondary mt-1 pt-2 border-top">
                                            @if ($address->contact_full_name)
                                                <div><strong>Contact:</strong> {{ $address->contact_full_name }}</div>
                                            @endif
                                            @if ($address->contact_phone)
                                                <div><strong>Phone:</strong> {{ trim(($address->contact_dialing_code ?? '') . ' ' . $address->contact_phone) }}</div>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="mt-auto pt-3 d-flex flex-wrap gap-2">
                                        @if (!$address->is_default)
                                            <form method="POST" action="{{ route('account.addresses.default', $address->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-dark rounded-pill">Set Default</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('account.addresses.edit', $address->id) }}" class="btn btn-sm btn-outline-warning text-dark rounded-pill">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('account.addresses.destroy', $address->id) }}"
                                              onsubmit="return confirm('Delete this saved address? Historical confirmed bookings will remain unchanged.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </main>

    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
