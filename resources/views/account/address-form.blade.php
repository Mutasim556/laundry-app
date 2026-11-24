<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $isEditMode ? 'Edit Address' : 'Add Address' }} | FreshGo Laundry</title>
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/booking-address.css') }}">
</head>
<body class="booking-page-body">
    
    @include('shared.header')

    @php
        $isHotel = $address && $address->address_type === 'hotel';
        $currentMode = old('collection_mode', $isHotel ? 'hotel' : 'home_office');
        $activeScreen = $currentMode === 'hotel' ? 'hotel' : 'postcode';

        $currentPostcode = old('postcode', $address?->postcode ?? '');
        $currentSelectedAddress = old('selected_address', $address?->formatted_address ?? '');
        $currentManualAddress = old('manual_address', ($address && !$isHotel && empty($address->postcode)) ? $address->formatted_address : '');
        $currentIsManual = (bool) old('is_manual', ($address && !$isHotel && empty($address->postcode)));
        $currentUkAddressId = old('uk_address_id', '');
        $currentAddressType = strtoupper((string) old('address_type', $address?->address_type ?? 'HOME'));
        $currentAddressDetails = old('address_details', $address?->address_details ?? '');
        $currentHotelId = old('hotel_id', '');
        $currentHotelName = old('hotel_name', $address?->hotel_name ?? '');
        $currentHotelAddress = old('hotel_address', $address?->hotel_address ?? '');
        $currentRoomNumber = old('room_number', $address?->room_number ?? '');
        $currentHotelCollectionType = old('hotel_collection_type', $address?->hotel_collection_type ?? 'use_reception');
        $currentLabel = old('label', $address?->label ?? '');
        $currentIsDefault = old('is_default', $address?->is_default ?? false);

        $authCustomer = auth('web')->user();
        $nameParts = preg_split('/\s+/', trim((string) ($authCustomer?->name ?? '')), 2);
        $profileFirst = $nameParts[0] ?? '';
        $profileLast = $nameParts[1] ?? '';

        $defaultContact = ($isEditMode && $address)
            ? [
                'contact_type' => $address->contact_type ?: 'individual',
                'contact_first_name' => $address->contact_first_name ?? $profileFirst,
                'contact_last_name' => $address->contact_last_name ?? $profileLast,
                'contact_company_name' => $address->contact_company_name ?? '',
                'contact_tax_number' => $address->contact_tax_number ?? '',
                'contact_country_iso' => $address->contact_country_iso ?? '',
                'contact_dialing_code' => $address->contact_dialing_code ?: ($authCustomer?->country_code ?: '+44'),
                'contact_phone' => $address->contact_phone ?? ($authCustomer?->phone ?? ''),
                'contact_email' => $address->contact_email ?? ($authCustomer?->email ?? ''),
            ]
            : [
                'contact_type' => 'individual',
                'contact_first_name' => $profileFirst,
                'contact_last_name' => $profileLast,
                'contact_company_name' => '',
                'contact_tax_number' => '',
                'contact_country_iso' => '',
                'contact_dialing_code' => $authCustomer?->country_code ?: '+44',
                'contact_phone' => $authCustomer?->phone ?? '',
                'contact_email' => $authCustomer?->email ?? '',
            ];
    @endphp

    <main class="container booking-main-container"
          id="bookingAddressApp"
          data-active-screen="{{ $activeScreen }}"
          data-lookup-url="{{ route('booking.postcode.lookup') }}"
          data-hotel-url="{{ route('booking.hotel.search') }}"
          data-services-url="{{ route('account.addresses') }}">

        <div class="booking-card-shell mx-auto">
            @if ($errors->any())
                <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-3 py-2 px-3 small fw-semibold" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('shared.address-form-screens', [
                'isBookingContext' => false,
                'formAction' => $isEditMode ? route('account.addresses.update', $address->id) : route('account.addresses.store'),
                'isEditMode' => $isEditMode,
                'returnTo' => $returnTo ?? null,
                'backUrl' => ($returnTo ?? '') === 'booking_address' ? route('booking.address') : route('account.addresses'),
                'backLabel' => 'Back',
                'submitButtonLabel' => $isEditMode ? 'Update Address' : 'Save Address',
                'savedAddresses' => [],
                'defaultContact' => $defaultContact,
            ])
        </div>
    </main>

    @include('shared.footer')
    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/booking-address.js') }}" defer></script>
</body>
</html>
