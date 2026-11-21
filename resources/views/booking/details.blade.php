<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Enter your contact details so we can keep you updated about your laundry order.">
    <title>Enter Contact Details | FreshGo Laundry</title>

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
        $savedDetails = $booking['details'] ?? [];

        // If authenticated user exists and no draft details were saved yet, auto-fill from user profile
        $authUser = auth()->user();
        $authFirstName = '';
        $authLastName = '';
        if ($authUser && !empty($authUser->name)) {
            $nameParts = preg_split('/\s+/', trim($authUser->name), 2);
            $authFirstName = $nameParts[0] ?? '';
            $authLastName = $nameParts[1] ?? '';
        }

        // Fallback if legacy customer_name was stored in session
        $savedFirstName = $savedDetails['first_name'] ?? '';
        $savedLastName = $savedDetails['last_name'] ?? '';
        if ($savedFirstName === '' && !empty($savedDetails['customer_name'])) {
            $parts = preg_split('/\s+/', trim($savedDetails['customer_name']), 2);
            $savedFirstName = $parts[0] ?? '';
            $savedLastName = $parts[1] ?? '';
        }

        $currentCustomerType = old('customer_type', $savedDetails['customer_type'] ?? 'individual');
        $currentFirstName = old('first_name', $savedFirstName !== '' ? $savedFirstName : $authFirstName);
        $currentLastName = old('last_name', $savedLastName !== '' ? $savedLastName : $authLastName);
        $currentCompanyName = old('company_name', $savedDetails['company_name'] ?? '');
        $currentTaxNumber = old('tax_number', $savedDetails['tax_number'] ?? '');

        $currentCountryCode = old('country_code', $savedDetails['country_code'] ?? '+44');
        $currentPhoneNumber = old('phone_number', $savedDetails['phone_number'] ?? '');
        $currentEmail = old('customer_email', $savedDetails['customer_email'] ?? ($authUser->email ?? ''));
    @endphp

    <main class="container booking-main-container" id="bookingDetailsApp">
        <div class="booking-card-shell mx-auto">

            <!-- Clickable 5-Step Booking Progress Bar -->
            @include('booking.partials.progress-bar', ['currentBookingStep' => 4, 'bookingSteps' => $bookingSteps ?? null])

            <!-- Server-Side Validation Alert -->
            @if ($errors->any())
                <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-3 py-2 px-3 small fw-semibold" role="alert" id="contactValidationAlert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-3">
                <a href="{{ route('booking.schedule') }}" class="booking-back-btn" id="btnBackToScheduleStep">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span>Back</span>
                </a>
            </div>

            <div class="booking-header-block mb-4">
                <h1 class="booking-screen-title">Enter contact details</h1>
                <p class="booking-screen-subtitle mb-0">
                    We only use your details to keep you updated about your order.
                    @guest
                        <a href="{{ route('admin.login') }}" class="booking-accent-link">Log in</a> if you have an existing account.
                    @endguest
                </p>
            </div>

            <form id="formBookingDetails" action="{{ route('booking.details.store') }}" method="POST" novalidate>
                @csrf

                <!-- 1. Customer Type Radio Row: Individual | Company -->
                <div class="contact-customer-type-row mb-4" role="radiogroup" aria-label="Customer Type">
                    <label class="customer-type-radio-label {{ $currentCustomerType === 'individual' ? 'is-checked' : '' }}"
                           for="customer_type_individual">
                        <input type="radio"
                               class="customer-type-radio-input js-customer-type-radio"
                               id="customer_type_individual"
                               name="customer_type"
                               value="individual"
                               {{ $currentCustomerType === 'individual' ? 'checked' : '' }}>
                        <span class="customer-type-circle"></span>
                        <span class="customer-type-text">Individual</span>
                    </label>

                    <label class="customer-type-radio-label {{ $currentCustomerType === 'company' ? 'is-checked' : '' }}"
                           for="customer_type_company">
                        <input type="radio"
                               class="customer-type-radio-input js-customer-type-radio"
                               id="customer_type_company"
                               name="customer_type"
                               value="company"
                               {{ $currentCustomerType === 'company' ? 'checked' : '' }}>
                        <span class="customer-type-circle"></span>
                        <span class="customer-type-text">Company</span>
                    </label>
                </div>

                <!-- 2. First Name -->
                <div class="mb-3">
                    <div class="contact-floating-box">
                        <label for="firstNameInput" class="contact-box-label">First name</label>
                        <input type="text"
                               id="firstNameInput"
                               name="first_name"
                               class="contact-box-input"
                               placeholder="Enter your first name"
                               value="{{ $currentFirstName }}"
                               maxlength="80"
                               autocomplete="given-name"
                               required>
                    </div>
                    <div id="firstNameError" class="booking-field-error d-none" role="alert"></div>
                </div>

                <!-- 3. Last Name -->
                <div class="mb-3">
                    <div class="contact-floating-box">
                        <label for="lastNameInput" class="contact-box-label">Last name</label>
                        <input type="text"
                               id="lastNameInput"
                               name="last_name"
                               class="contact-box-input"
                               placeholder="Enter your last name"
                               value="{{ $currentLastName }}"
                               maxlength="80"
                               autocomplete="family-name"
                               required>
                    </div>
                    <div id="lastNameError" class="booking-field-error d-none" role="alert"></div>
                </div>

                <!-- 4. Company Specific Fields (Shown only when Company is selected) -->
                <div id="companyFieldsSection" class="{{ $currentCustomerType === 'company' ? '' : 'd-none' }}">
                    <!-- Company Name (Required when Company is selected) -->
                    <div class="mb-3">
                        <div class="contact-floating-box">
                            <label for="companyNameInput" class="contact-box-label">Company name</label>
                            <input type="text"
                                   id="companyNameInput"
                                   name="company_name"
                                   class="contact-box-input"
                                   placeholder="Company name"
                                   value="{{ $currentCompanyName }}"
                                   maxlength="150"
                                   autocomplete="organization"
                                   {{ $currentCustomerType === 'company' ? 'required' : 'disabled' }}>
                        </div>
                        <div id="companyNameError" class="booking-field-error d-none" role="alert"></div>
                    </div>

                    <!-- Tax Number (Optional) -->
                    <div class="mb-3">
                        <div class="contact-floating-box">
                            <label for="taxNumberInput" class="contact-box-label">Tax number (optional)</label>
                            <input type="text"
                                   id="taxNumberInput"
                                   name="tax_number"
                                   class="contact-box-input"
                                   placeholder="Tax number (optional)"
                                   value="{{ $currentTaxNumber }}"
                                   maxlength="80"
                                   {{ $currentCustomerType === 'company' ? '' : 'disabled' }}>
                        </div>
                    </div>
                </div>

                <!-- 5. Phone Number (Country Dialing Code Dropdown + Mobile Number Input in One Row) -->
                <div class="mb-3">
                    <label for="phoneNumberInput" class="schedule-sublabel mb-1">Phone</label>
                    <div class="row g-2 align-items-stretch">
                        <div class="col-5 col-sm-4">
                            <div class="contact-floating-box h-100 d-flex align-items-center">
                                <label for="countryCodeSelect" class="visually-hidden">Country Dialing Code</label>
                                <select id="countryCodeSelect"
                                        name="country_code"
                                        class="form-select contact-country-select">
                                    @foreach ($countryCodes as $codeVal => $codeLabel)
                                        <option value="{{ $codeVal }}" @selected($currentCountryCode === $codeVal)>
                                            {{ $codeVal }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-7 col-sm-8">
                            <div class="contact-floating-box">
                                <label for="phoneNumberInput" class="contact-box-label">Your mobile number</label>
                                <input type="tel"
                                       id="phoneNumberInput"
                                       name="phone_number"
                                       class="contact-box-input"
                                       placeholder="7700900077"
                                       value="{{ $currentPhoneNumber }}"
                                       maxlength="25"
                                       autocomplete="tel-national"
                                       required>
                            </div>
                        </div>
                    </div>
                    <div id="phoneNumberError" class="booking-field-error d-none" role="alert"></div>
                </div>

                <!-- 6. Email Address -->
                <div class="mb-4">
                    <div class="contact-floating-box">
                        <label for="customerEmailInput" class="contact-box-label">Email</label>
                        <input type="email"
                               id="customerEmailInput"
                               name="customer_email"
                               class="contact-box-input"
                               placeholder="you@example.com"
                               value="{{ $currentEmail }}"
                               maxlength="150"
                               autocomplete="email"
                               required>
                    </div>
                    <div id="customerEmailError" class="booking-field-error d-none" role="alert"></div>
                </div>

                <div class="booking-sticky-footer">
                    <button type="submit" id="btnDetailsNext" class="btn-booking-next w-100">
                        <span>NEXT</span>
                    </button>
                </div>
            </form>

        </div>
    </main>

    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script>
    (() => {
        'use strict';

        const form = document.querySelector('#formBookingDetails');
        if (!form) return;

        const typeRadios = document.querySelectorAll('.js-customer-type-radio');
        const typeLabels = document.querySelectorAll('.customer-type-radio-label');
        const companySection = document.querySelector('#companyFieldsSection');
        const companyNameInput = document.querySelector('#companyNameInput');
        const taxNumberInput = document.querySelector('#taxNumberInput');

        const firstNameInput = document.querySelector('#firstNameInput');
        const lastNameInput = document.querySelector('#lastNameInput');
        const phoneNumberInput = document.querySelector('#phoneNumberInput');
        const customerEmailInput = document.querySelector('#customerEmailInput');

        const firstNameError = document.querySelector('#firstNameError');
        const lastNameError = document.querySelector('#lastNameError');
        const companyNameError = document.querySelector('#companyNameError');
        const phoneNumberError = document.querySelector('#phoneNumberError');
        const customerEmailError = document.querySelector('#customerEmailError');
        const btnDetailsNext = document.querySelector('#btnDetailsNext');

        const isCompanySelected = () => {
            const checked = document.querySelector('.js-customer-type-radio:checked');
            return checked?.value === 'company';
        };

        const isValidEmail = (email) => {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || '').trim());
        };

        const isValidPhone = (phone) => {
            const trimmed = String(phone || '').trim();
            return trimmed.length >= 6 && /^[0-9\s\-\(\)]+$/.test(trimmed);
        };

        // Toggle Individual vs Company fields while preserving typed values in DOM
        const updateCustomerTypeUI = () => {
            const isCompany = isCompanySelected();

            typeLabels.forEach(label => {
                const radio = label.querySelector('.js-customer-type-radio');
                label.classList.toggle('is-checked', Boolean(radio?.checked));
            });

            companySection?.classList.toggle('d-none', !isCompany);

            if (companyNameInput) {
                companyNameInput.disabled = !isCompany;
                companyNameInput.required = isCompany;
            }
            if (taxNumberInput) {
                taxNumberInput.disabled = !isCompany;
            }
            if (!isCompany) {
                companyNameError?.classList.add('d-none');
            }

            updateNextButtonState();
        };

        const updateNextButtonState = () => {
            if (!btnDetailsNext) return;

            const fNameOk = (firstNameInput?.value || '').trim().length >= 1;
            const lNameOk = (lastNameInput?.value || '').trim().length >= 1;
            const companyOk = !isCompanySelected() || (companyNameInput?.value || '').trim().length >= 1;
            const phoneOk = isValidPhone(phoneNumberInput?.value || '');
            const emailOk = isValidEmail(customerEmailInput?.value || '');

            btnDetailsNext.disabled = !(fNameOk && lNameOk && companyOk && phoneOk && emailOk);
        };

        typeRadios.forEach(radio => {
            radio.addEventListener('change', updateCustomerTypeUI);
        });

        [firstNameInput, lastNameInput, companyNameInput, phoneNumberInput, customerEmailInput].forEach(input => {
            if (!input) return;
            input.addEventListener('input', () => {
                if (input === firstNameInput) firstNameError?.classList.add('d-none');
                if (input === lastNameInput) lastNameError?.classList.add('d-none');
                if (input === companyNameInput) companyNameError?.classList.add('d-none');
                if (input === phoneNumberInput) phoneNumberError?.classList.add('d-none');
                if (input === customerEmailInput) customerEmailError?.classList.add('d-none');
                updateNextButtonState();
            });
        });

        form.addEventListener('submit', (e) => {
            let hasError = false;

            if ((firstNameInput?.value || '').trim().length < 1) {
                hasError = true;
                if (firstNameError) {
                    firstNameError.textContent = 'Please enter your first name.';
                    firstNameError.classList.remove('d-none');
                }
            }

            if ((lastNameInput?.value || '').trim().length < 1) {
                hasError = true;
                if (lastNameError) {
                    lastNameError.textContent = 'Please enter your last name.';
                    lastNameError.classList.remove('d-none');
                }
            }

            if (isCompanySelected() && (companyNameInput?.value || '').trim().length < 1) {
                hasError = true;
                if (companyNameError) {
                    companyNameError.textContent = 'Please enter your company name.';
                    companyNameError.classList.remove('d-none');
                }
            }

            if (!isValidPhone(phoneNumberInput?.value || '')) {
                hasError = true;
                if (phoneNumberError) {
                    phoneNumberError.textContent = 'Please enter a valid mobile number (at least 6 digits).';
                    phoneNumberError.classList.remove('d-none');
                }
            }

            if (!isValidEmail(customerEmailInput?.value || '')) {
                hasError = true;
                if (customerEmailError) {
                    customerEmailError.textContent = 'Please enter a valid email address.';
                    customerEmailError.classList.remove('d-none');
                }
            }

            if (hasError) {
                e.preventDefault();
            }
        });

        updateCustomerTypeUI();
    })();
    </script>
</body>
</html>
