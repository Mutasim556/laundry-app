@php
    $p = $prefix; // 'create' or 'edit'
    $defaultCountryCode = $globalSettings['business_country_code'] ?? 'GB';
    $defaultDial = '+44';
    foreach ($countries as $c) {
        if (strtoupper($c['iso']) === strtoupper($defaultCountryCode)) {
            $defaultDial = $c['dial'];
            break;
        }
    }
    $currencyCode = $globalSettings['default_currency'] ?? 'GBP';
    $currencySymbol = $globalSettings['currency_symbol'] ?? '£';
@endphp

<div class="alert alert-danger d-none mb-3" id="{{ $p }}VendorErrors"></div>

{{-- SECTION A: BUSINESS INFORMATION --}}
<div class="saas-form-section">
    <div class="saas-section-heading">
        <span class="saas-section-badge"><i class="mdi mdi-domain"></i></span>
        <span>Section A — Business Information</span>
    </div>
    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_name">Vendor / Company Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="{{ $p }}_v_name" name="name" placeholder="e.g. Sterling Fabric Care Ltd." required>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_code">Vendor Code</label>
            <input type="text" class="form-control" id="{{ $p }}_v_code" name="vendor_code" placeholder="Auto-generated if blank">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_status">Status <span class="text-danger">*</span></label>
            <select class="form-select" id="{{ $p }}_v_status" name="status" required>
                <option value="active">Active</option>
                <option value="pending">Pending Review</option>
                <option value="suspended">Inactive</option>
                <option value="closed">Closed</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_business_type">Business Type</label>
            <select class="form-select" id="{{ $p }}_v_business_type" name="business_type">
                <option value="">Select type...</option>
                <option value="Limited Company / LLC">Limited Company / LLC</option>
                <option value="Corporation">Corporation</option>
                <option value="Partnership">Partnership</option>
                <option value="Sole Proprietorship">Sole Proprietorship</option>
                <option value="Franchise Partner">Franchise Partner</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_reg_no">Business Registration Number</label>
            <input type="text" class="form-control" id="{{ $p }}_v_reg_no" name="trade_license_number" placeholder="Company or registration number">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_tax_vat">Tax / VAT Registration Number</label>
            <input type="text" class="form-control" id="{{ $p }}_v_tax_vat" name="tax_vat_number" placeholder="Optional VAT / EIN / Tax ID">
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_doc">Registration Document (PDF / Image)</label>
            <input type="file" class="form-control" id="{{ $p }}_v_doc" name="trade_license_document" accept=".pdf,image/*">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_description">Business Description</label>
            <input type="text" class="form-control" id="{{ $p }}_v_description" name="description" placeholder="Brief summary of capabilities or specialty">
        </div>
    </div>
</div>

{{-- SECTION B: PRIMARY CONTACT --}}
<div class="saas-form-section">
    <div class="saas-section-heading">
        <span class="saas-section-badge"><i class="mdi mdi-account"></i></span>
        <span>Section B — Primary Contact</span>
    </div>
    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_owner_name">Contact Person Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="{{ $p }}_v_owner_name" name="owner_name" placeholder="e.g. Oliver Bennett" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_email">Email Address</label>
            <input type="email" class="form-control" id="{{ $p }}_v_email" name="email" placeholder="partners@vendorcompany.com">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1">Phone Number <span class="text-danger">*</span></label>
            @include('admin::components.phone-input', [
                'idPrefix' => $p . '_vendor_primary',
                'dialName' => 'phone_country_code',
                'phoneName' => 'phone',
                'defaultDial' => $defaultDial,
                'defaultIso' => $defaultCountryCode,
                'countries' => $countries,
                'required' => true,
                'placeholder' => '7123 456789',
            ])
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1">Alternate Phone (Optional)</label>
            @include('admin::components.phone-input', [
                'idPrefix' => $p . '_vendor_alt',
                'dialName' => 'alt_phone_country_code',
                'phoneName' => 'alt_phone',
                'defaultDial' => $defaultDial,
                'defaultIso' => $defaultCountryCode,
                'countries' => $countries,
                'required' => false,
                'placeholder' => 'Optional secondary number',
            ])
        </div>
    </div>
</div>

{{-- SECTION C: BUSINESS ADDRESS --}}
<div class="saas-form-section">
    <div class="saas-section-heading">
        <span class="saas-section-badge"><i class="mdi mdi-map-marker"></i></span>
        <span>Section C — Business Address</span>
    </div>
    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_country_code">Country</label>
            <select class="form-select js-vendor-country-selector" id="{{ $p }}_v_country_code" name="country_code" data-prefix="{{ $p }}">
                <option value="">Select country...</option>
                @foreach($countries as $country)
                    <option value="{{ $country['iso'] }}" data-dial="{{ $country['dial'] }}" @if($p === 'create' && strtoupper($country['iso']) === strtoupper($defaultCountryCode)) selected @endif>
                        {{ $country['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_city">City / Town</label>
            <input type="text" class="form-control" id="{{ $p }}_v_city" name="city" placeholder="e.g. Manchester">
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_address_line_1">Address Line 1</label>
            <input type="text" class="form-control" id="{{ $p }}_v_address_line_1" name="address_line_1" placeholder="Registered street address">
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_address_line_2">Address Line 2</label>
            <input type="text" class="form-control" id="{{ $p }}_v_address_line_2" name="address_line_2" placeholder="Suite, unit, or building (optional)">
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_state_region" id="{{ $p }}_v_state_label">County / Region</label>
            <input type="text" class="form-control" id="{{ $p }}_v_state_region" name="state_region" placeholder="e.g. Greater Manchester">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" id="{{ $p }}_v_postal_label">Postcode</label>
            <div id="{{ $p }}_v_uk_postcode_wrap">
                <select class="form-select vendor-uk-postcode-select" id="{{ $p }}_v_postcode_uk" style="width: 100%;">
                    <option value="">Search and select UK postcode</option>
                </select>
            </div>
            <div id="{{ $p }}_v_intl_postcode_wrap" class="d-none">
                <input type="text" class="form-control" id="{{ $p }}_v_postcode_intl" placeholder="Enter postal or ZIP code">
            </div>
            <input type="hidden" id="{{ $p }}_v_postcode_value" name="postcode" value="">
        </div>
    </div>
</div>

{{-- SECTION D: COMMERCIAL INFORMATION --}}
<div class="saas-form-section mb-0">
    <div class="saas-section-heading">
        <span class="saas-section-badge"><i class="mdi mdi-cash"></i></span>
        <span>Section D — Commercial Information</span>
    </div>
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_commission_type">Commission Type <span class="text-danger">*</span></label>
            <select class="form-select" id="{{ $p }}_v_commission_type" name="commission_type" required>
                <option value="percentage">Percentage (%)</option>
                <option value="fixed">Fixed Per Order ({{ $currencySymbol }} {{ $currencyCode }})</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_commission_rate">Commission Rate</label>
            <input type="number" step="0.01" min="0" class="form-control" id="{{ $p }}_v_commission_rate" name="commission_rate" value="10.00">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_payment_terms">Payment Terms</label>
            <select class="form-select" id="{{ $p }}_v_payment_terms" name="payment_terms">
                <option value="">Select terms...</option>
                <option value="Weekly Settlement">Weekly Settlement</option>
                <option value="Bi-Weekly Settlement">Bi-Weekly Settlement</option>
                <option value="Monthly Settlement">Monthly Settlement</option>
                <option value="Net 7">Net 7 Days</option>
                <option value="Net 15">Net 15 Days</option>
                <option value="Net 30">Net 30 Days</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_payout_method">Payout Method</label>
            <select class="form-select" id="{{ $p }}_v_payout_method" name="payout_method">
                <option value="">Select payout method...</option>
                <option value="Bank Transfer / Wire">Bank Transfer / Wire</option>
                <option value="ACH / Direct Deposit">ACH / Direct Deposit</option>
                <option value="SEPA Transfer">SEPA Transfer</option>
                <option value="Stripe Connect">Stripe Connect</option>
                <option value="PayPal">PayPal</option>
                <option value="Wise">Wise</option>
                <option value="Cheque">Cheque</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_payout_details">Payout Account Details</label>
            <input type="text" class="form-control" id="{{ $p }}_v_payout_details" name="payout_details" placeholder="Account holder, IBAN / Sort Code / Routing, or settlement email">
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold small mb-2">Services Provided</label>
            <div class="row g-2">
                @foreach($services as $service)
                    <div class="col-6 col-md-4">
                        <div class="form-check border rounded-2 px-3 py-2 bg-light-subtle">
                            <input class="form-check-input {{ $p }}-vendor-service-cb" type="checkbox" name="service_ids[]" value="{{ $service->id }}" id="{{ $p }}_vsrv_{{ $service->id }}">
                            <label class="form-check-label small fw-medium" for="{{ $p }}_vsrv_{{ $service->id }}">{{ $service->name }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($stores->isNotEmpty())
        <div class="col-12">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_assigned_stores">Assigned Stores (Optional)</label>
            @if($p === 'edit')
                <input type="hidden" name="assigned_store_ids_present" value="1">
            @endif
            <select class="form-select vendor-stores-select" id="{{ $p }}_v_assigned_stores" name="assigned_store_ids[]" multiple style="width: 100%;">
                @foreach($stores as $st)
                    <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->store_code ?: 'STR-' . str_pad((string) $st->id, 4, '0', STR_PAD_LEFT) }})</option>
                @endforeach
            </select>
            <div class="form-text small">Link existing store branches to this vendor partner.</div>
        </div>
        @endif

        <div class="col-12">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_v_notes">Internal Notes</label>
            <textarea class="form-control" id="{{ $p }}_v_notes" name="verification_notes" rows="2" placeholder="Contract remarks, SLA notes, or verification history..."></textarea>
        </div>
    </div>
</div>
