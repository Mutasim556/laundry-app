@php
    $fp = $fieldPrefix ?? 'addr';
    $cType = old('contact_type', $defaultContact['contact_type'] ?? 'individual');
    $cFirst = old('contact_first_name', $defaultContact['contact_first_name'] ?? '');
    $cLast = old('contact_last_name', $defaultContact['contact_last_name'] ?? '');
    $cCompany = old('contact_company_name', $defaultContact['contact_company_name'] ?? '');
    $cTax = old('contact_tax_number', $defaultContact['contact_tax_number'] ?? '');
    $cDial = old('contact_dialing_code', $defaultContact['contact_dialing_code'] ?? '+44');
    $cIso = old('contact_country_iso', $defaultContact['contact_country_iso'] ?? '');
    $cPhone = old('contact_phone', $defaultContact['contact_phone'] ?? '');
    $cEmail = old('contact_email', $defaultContact['contact_email'] ?? '');

    $countryItems = $countryList ?? \App\Support\CountryDialCodes::all();
    $selectedCountry = \App\Support\CountryDialCodes::findByDial($cDial ?: '+44', $cIso ?: null);
@endphp

<div class="border-top pt-4 mt-3 mb-4" id="{{ $fp }}_contactDetailsSection">
    <div class="mb-3">
        <h2 class="h5 fw-bold text-dark mb-1">Contact Details</h2>
        <p class="small text-secondary mb-0">Add contact information for this address.</p>
    </div>

    <!-- Customer Type: Individual | Company -->
    <div class="contact-customer-type-row mb-3" role="radiogroup" aria-label="Customer Type">
        <label class="customer-type-radio-label {{ $cType === 'individual' ? 'is-checked' : '' }}" for="{{ $fp }}_ctype_individual">
            <input type="radio"
                   class="customer-type-radio-input js-ab-ctype-radio"
                   id="{{ $fp }}_ctype_individual"
                   name="contact_type"
                   value="individual"
                   data-target-prefix="{{ $fp }}"
                   {{ $cType === 'individual' ? 'checked' : '' }}>
            <span class="customer-type-circle"></span>
            <span class="customer-type-text">Individual</span>
        </label>

        <label class="customer-type-radio-label {{ $cType === 'company' ? 'is-checked' : '' }}" for="{{ $fp }}_ctype_company">
            <input type="radio"
                   class="customer-type-radio-input js-ab-ctype-radio"
                   id="{{ $fp }}_ctype_company"
                   name="contact_type"
                   value="company"
                   data-target-prefix="{{ $fp }}"
                   {{ $cType === 'company' ? 'checked' : '' }}>
            <span class="customer-type-circle"></span>
            <span class="customer-type-text">Company</span>
        </label>
    </div>

    <!-- First Name -->
    <div class="mb-3">
        <div class="contact-floating-box">
            <label for="{{ $fp }}_first_name" class="contact-box-label">First Name</label>
            <input type="text"
                   id="{{ $fp }}_first_name"
                   name="contact_first_name"
                   class="contact-box-input"
                   placeholder="Enter first name"
                   value="{{ $cFirst }}"
                   maxlength="80"
                   required>
        </div>
    </div>

    <!-- Last Name -->
    <div class="mb-3">
        <div class="contact-floating-box">
            <label for="{{ $fp }}_last_name" class="contact-box-label">Last Name</label>
            <input type="text"
                   id="{{ $fp }}_last_name"
                   name="contact_last_name"
                   class="contact-box-input"
                   placeholder="Enter last name"
                   value="{{ $cLast }}"
                   maxlength="80"
                   required>
        </div>
    </div>

    <!-- Company Fields -->
    <div id="{{ $fp }}_company_fields" class="{{ $cType === 'company' ? '' : 'd-none' }}">
        <div class="mb-3">
            <div class="contact-floating-box">
                <label for="{{ $fp }}_company_name" class="contact-box-label">Company Name</label>
                <input type="text"
                       id="{{ $fp }}_company_name"
                       name="contact_company_name"
                       class="contact-box-input"
                       placeholder="Company name"
                       value="{{ $cCompany }}"
                       maxlength="150"
                       {{ $cType === 'company' ? 'required' : 'disabled' }}>
            </div>
        </div>
        <div class="mb-3">
            <div class="contact-floating-box">
                <label for="{{ $fp }}_tax_number" class="contact-box-label">Tax Number (Optional)</label>
                <input type="text"
                       id="{{ $fp }}_tax_number"
                       name="contact_tax_number"
                       class="contact-box-input"
                       placeholder="Tax number (optional)"
                       value="{{ $cTax }}"
                       maxlength="80"
                       {{ $cType === 'company' ? '' : 'disabled' }}>
            </div>
        </div>
    </div>

    <!-- Phone (Dialing Code + Flag Dropdown + Mobile Number in One Row) -->
    <div class="mb-3">
        <label for="{{ $fp }}_phone" class="schedule-sublabel mb-1">Phone</label>
        <div class="row g-2 align-items-stretch">
            <div class="col-5 col-sm-4">
                <input type="hidden" id="{{ $fp }}_dial_input" name="contact_dialing_code" value="{{ $selectedCountry['dial'] }}">
                <input type="hidden" id="{{ $fp }}_iso_input" name="contact_country_iso" value="{{ $selectedCountry['iso'] }}">

                <div class="country-code-picker position-relative h-100" id="{{ $fp }}_country_picker">
                    <button type="button"
                            class="contact-select-trigger w-100 h-100 d-flex align-items-center justify-content-between px-3 js-ab-country-trigger"
                            aria-haspopup="listbox"
                            aria-expanded="false">
                        <span class="fw-bold text-dark text-nowrap js-ab-country-label" style="font-size: 0.96rem;">{{ $selectedCountry['dial'] }}</span>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" class="text-secondary flex-shrink-0 ms-1"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>

                    <div class="country-code-menu d-none js-ab-country-menu"
                         role="listbox"
                         style="position: absolute; top: calc(100% + 6px); left: 0; width: 295px; max-width: calc(100vw - 40px); z-index: 1050; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 14px; box-shadow: 0 12px 30px rgba(0,0,0,0.12); overflow: hidden;">
                        <div class="p-2 border-bottom bg-light">
                            <input type="text"
                                   class="form-control form-control-sm js-ab-country-search"
                                   placeholder="Search country or +dial code..."
                                   autocomplete="off">
                        </div>
                        <ul class="list-unstyled mb-0 js-ab-country-list" style="max-height: 240px; overflow-y: auto;">
                            @foreach ($countryItems as $c)
                                @php $isoLower = strtolower($c['iso']); @endphp
                                <li class="d-flex align-items-center gap-2 px-3 py-2 js-ab-country-opt"
                                    role="option"
                                    data-dial="{{ $c['dial'] }}"
                                    data-iso="{{ $c['iso'] }}"
                                    data-search="{{ strtolower($c['name'] . ' ' . $c['dial']) }}"
                                    style="cursor: pointer; font-size: 0.88rem; color: #171717;">
                                    <span class="fw-bold text-start flex-shrink-0" style="min-width: 50px;">{{ $c['dial'] }}</span>
                                    <img src="{{ asset('admin/dist/css/icons/flag-icon-css/flags/' . $isoLower . '.svg') }}"
                                         width="22" height="16" alt="{{ $c['name'] }} flag" loading="lazy"
                                         class="flex-shrink-0" style="width: 22px; height: 16px; object-fit: cover; border-radius: 2px; border: 1px solid rgba(0,0,0,0.08);">
                                    <span class="text-truncate flex-grow-1">{{ $c['name'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-7 col-sm-8">
                <div class="contact-floating-box h-100">
                    <label for="{{ $fp }}_phone" class="contact-box-label">Your mobile number</label>
                    <input type="tel"
                           id="{{ $fp }}_phone"
                           name="contact_phone"
                           class="contact-box-input"
                           placeholder="7700900077"
                           value="{{ $cPhone }}"
                           maxlength="24"
                           inputmode="tel"
                           required>
                </div>
            </div>
        </div>
    </div>

    <!-- Email -->
    <div class="mb-2">
        <div class="contact-floating-box">
            <label for="{{ $fp }}_email" class="contact-box-label">Email <span class="text-danger">*</span></label>
            <input type="email"
                   id="{{ $fp }}_email"
                   name="contact_email"
                   class="contact-box-input"
                   placeholder="name@example.com"
                   value="{{ $cEmail }}"
                   maxlength="160"
                   required>
        </div>
    </div>
</div>
