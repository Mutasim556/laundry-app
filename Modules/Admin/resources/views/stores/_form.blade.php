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
    $currencySymbol = $globalSettings['currency_symbol'] ?? '£';
@endphp

<div class="alert alert-danger d-none mb-3" id="{{ $p }}StoreErrors"></div>

{{-- SECTION A: BASIC STORE INFORMATION --}}
<div class="saas-form-section">
    <div class="saas-section-heading">
        <span class="saas-section-badge"><i class="mdi mdi-store"></i></span>
        <span>Section A — Basic Store Information</span>
    </div>
    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_name">Store Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="{{ $p }}_name" name="name" placeholder="e.g. Central London Laundry Hub" required>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_store_code">Store Code</label>
            <input type="text" class="form-control" id="{{ $p }}_store_code" name="store_code" placeholder="Auto-generated if blank">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_status">Store Status <span class="text-danger">*</span></label>
            <select class="form-select" id="{{ $p }}_status" name="status" required>
                <option value="active">Active</option>
                <option value="pending">Pending Review</option>
                <option value="suspended">Inactive</option>
                <option value="closed">Closed</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_store_type">Store Type <span class="text-danger">*</span></label>
            <select class="form-select" id="{{ $p }}_store_type" name="store_type" required>
                <option value="both">Laundry &amp; Dry Cleaning</option>
                <option value="laundry">Laundry Only</option>
                <option value="dry_cleaning">Dry Cleaning Only</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_ownership_type">Ownership Type <span class="text-danger">*</span></label>
            <select class="form-select" id="{{ $p }}_ownership_type" name="ownership_type" required>
                <option value="my_store">In-House Store</option>
                <option value="vendor_store">Partner Vendor Store</option>
            </select>
        </div>
        <div class="col-md-4" id="{{ $p }}VendorSelectCol" style="display: none;">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_vendor_id">Assigned Vendor <span class="text-danger">*</span></label>
            <select class="form-select" id="{{ $p }}_vendor_id" name="vendor_id">
                <option value="">Select vendor...</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_admin_id">Store Manager</label>
            <select class="form-select" id="{{ $p }}_admin_id" name="admin_id">
                <option value="">Unassigned</option>
                @foreach($admins as $admin)
                    <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_slug">URL Slug</label>
            <input type="text" class="form-control" id="{{ $p }}_slug" name="slug" placeholder="auto-generated-from-name">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_logo">Store Logo</label>
            <input type="file" class="form-control" id="{{ $p }}_logo" name="logo" accept="image/*">
            @if($p === 'edit')
                <div id="edit_logo_preview_wrap" class="mt-2 d-none">
                    <img id="edit_logo_preview" src="" alt="Current store logo" class="rounded-2 border" style="height: 44px; width: 44px; object-fit: cover;">
                    <span class="small text-muted ms-2">Current logo</span>
                </div>
            @endif
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_description">Store Description</label>
            <textarea class="form-control" id="{{ $p }}_description" name="description" rows="2" placeholder="Brief overview of services, facilities, or branch notes..."></textarea>
        </div>
    </div>
</div>

{{-- SECTION B: LOCATION & ADDRESS --}}
<div class="saas-form-section">
    <div class="saas-section-heading">
        <span class="saas-section-badge"><i class="mdi mdi-map-marker"></i></span>
        <span>Section B — Location &amp; Address</span>
    </div>
    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_country_code">Country <span class="text-danger">*</span></label>
            <select class="form-select js-country-selector" id="{{ $p }}_country_code" name="country_code" data-prefix="{{ $p }}" required>
                <option value="">Select country...</option>
                @foreach($countries as $country)
                    <option value="{{ $country['iso'] }}" data-dial="{{ $country['dial'] }}" @if($p === 'create' && strtoupper($country['iso']) === strtoupper($defaultCountryCode)) selected @endif>
                        {{ $country['name'] }}
                    </option>
                @endforeach
            </select>
            @if($p === 'edit')
                <div id="edit_country_change_warning" class="form-text text-warning-emphasis small d-none">
                    <i class="mdi mdi-alert me-1"></i>Changing the country updates address validation and may affect existing service area coverage.
                </div>
            @endif
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_city">City / Town <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="{{ $p }}_city" name="city" placeholder="e.g. London" required>
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_address_line_1">Address Line 1 <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="{{ $p }}_address_line_1" name="address_line_1" placeholder="Street number and street name" required>
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_address_line_2">Address Line 2</label>
            <input type="text" class="form-control" id="{{ $p }}_address_line_2" name="address_line_2" placeholder="Suite, unit, building, or floor (optional)">
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_state_region" id="{{ $p }}_state_label">County / Region</label>
            <input type="text" class="form-control" id="{{ $p }}_state_region" name="state_region" placeholder="e.g. Greater London">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" id="{{ $p }}_postal_label">Postcode</label>
            {{-- UK Postcode Select2 (visible when Country = GB) --}}
            <div id="{{ $p }}_uk_postcode_wrap">
                <select class="form-select store-uk-postcode-select" id="{{ $p }}_postcode_uk" style="width: 100%;">
                    <option value="">Search and select UK postcode</option>
                </select>
                <div class="form-text small">Distinct postcodes loaded from the UK address database.</div>
                @if($p === 'edit')
                    <div id="edit_legacy_postcode_notice" class="form-text text-warning-emphasis small d-none">
                        Current postcode is a legacy value not in <code>uk_addresses</code>. Keep it unchanged or select a valid UK postcode.
                    </div>
                @endif
            </div>
            {{-- International Postcode / ZIP input (visible when Country != GB) --}}
            <div id="{{ $p }}_intl_postcode_wrap" class="d-none">
                <input type="text" class="form-control" id="{{ $p }}_postcode_intl" placeholder="Enter postal or ZIP code">
            </div>
            <input type="hidden" id="{{ $p }}_postcode_value" name="postcode" value="">
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_latitude">Latitude (Optional)</label>
            <input type="number" step="0.00000001" class="form-control" id="{{ $p }}_latitude" name="latitude" placeholder="e.g. 51.5074">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_longitude">Longitude (Optional)</label>
            <input type="number" step="0.00000001" class="form-control" id="{{ $p }}_longitude" name="longitude" placeholder="e.g. -0.1278">
        </div>
    </div>
</div>

{{-- SECTION C: CONTACT INFORMATION --}}
<div class="saas-form-section">
    <div class="saas-section-heading">
        <span class="saas-section-badge"><i class="mdi mdi-phone"></i></span>
        <span>Section C — Contact Information</span>
    </div>
    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_contact_person">Contact Person</label>
            <input type="text" class="form-control" id="{{ $p }}_contact_person" name="contact_person" placeholder="Primary branch contact name">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_email">Contact Email</label>
            <input type="email" class="form-control" id="{{ $p }}_email" name="email" placeholder="branch@company.com">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold small mb-1">Phone Number <span class="text-danger">*</span></label>
            @include('admin::components.phone-input', [
                'idPrefix' => $p . '_primary',
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
                'idPrefix' => $p . '_alt',
                'dialName' => 'alt_phone_country_code',
                'phoneName' => 'alt_phone',
                'defaultDial' => $defaultDial,
                'defaultIso' => $defaultCountryCode,
                'countries' => $countries,
                'required' => false,
                'placeholder' => 'Optional secondary number',
            ])
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_website">Website (Optional)</label>
            <input type="url" class="form-control" id="{{ $p }}_website" name="website" placeholder="https://example.com">
        </div>
    </div>
</div>

{{-- SECTION D: OPERATIONS --}}
<div class="saas-form-section mb-0">
    <div class="saas-section-heading">
        <span class="saas-section-badge"><i class="mdi mdi-settings"></i></span>
        <span>Section D — Operations</span>
    </div>
    <div class="row g-2">
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_turnaround_time">Turnaround Time</label>
            <input type="text" class="form-control" id="{{ $p }}_turnaround_time" name="turnaround_time" placeholder="e.g. 24 - 48 Hours">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_capacity">Daily Capacity (Orders)</label>
            <input type="number" min="1" class="form-control" id="{{ $p }}_capacity" name="capacity" placeholder="e.g. 120">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_min_order_amount">Min Order ({{ $currencySymbol }})</label>
            <input type="number" step="0.01" min="0" class="form-control" id="{{ $p }}_min_order_amount" name="min_order_amount" value="0.00">
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_delivery_fee">Delivery Fee ({{ $currencySymbol }})</label>
            <input type="number" step="0.01" min="0" class="form-control" id="{{ $p }}_delivery_fee" name="delivery_fee" value="0.00">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_free_delivery_threshold">Free Delivery Over ({{ $currencySymbol }})</label>
            <input type="number" step="0.01" min="0" class="form-control" id="{{ $p }}_free_delivery_threshold" name="free_delivery_threshold" placeholder="Optional">
        </div>
        <div class="col-md-4 d-flex flex-column justify-content-end gap-2 pb-1">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="{{ $p }}_has_delivery" name="has_delivery" value="1" checked>
                <label class="form-check-label small fw-semibold" for="{{ $p }}_has_delivery">Collection &amp; Delivery Available</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="{{ $p }}_is_accepting_orders" name="is_accepting_orders" value="1" checked>
                <label class="form-check-label small fw-semibold" for="{{ $p }}_is_accepting_orders">Accepting New Orders</label>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold small mb-2">Supported Service Categories</label>
            <div class="row g-2">
                @foreach($services as $service)
                    <div class="col-6 col-md-4">
                        <div class="form-check border rounded-2 px-3 py-2 bg-light-subtle">
                            <input class="form-check-input {{ $p }}-service-checkbox" type="checkbox" name="service_ids[]" value="{{ $service->id }}" id="{{ $p }}_srv_{{ $service->id }}">
                            <label class="form-check-label small fw-medium" for="{{ $p }}_srv_{{ $service->id }}">{{ $service->name }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_service_areas_text">Service Coverage Notes</label>
            <input type="text" class="form-control" id="{{ $p }}_service_areas_text" name="service_areas_text" placeholder="Postal districts, zones, or neighborhoods served">
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold small mb-1" for="{{ $p }}_verification_notes">Internal Notes</label>
            <textarea class="form-control" id="{{ $p }}_verification_notes" name="verification_notes" rows="2" placeholder="Internal operational or verification notes..."></textarea>
        </div>
    </div>
</div>
