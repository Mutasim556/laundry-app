@extends('admin::layouts.app')

@section('title', ($deliveryPerson ? 'Edit Delivery Person' : 'Add Delivery Person') . ' - Admin Panel')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/select2/dist/css/select2.min.css') }}">
@endpush

@section('content')
@php
    $isEdit = $deliveryPerson !== null;
    $defaultCountryCode = old('country_code', $deliveryPerson?->country_code ?? ($globalSettings['business_country_code'] ?? 'GB'));
    $defaultDial = old('phone_country_code', $deliveryPerson?->phone_country_code ?? '+44');
    $defaultCurrency = old('currency', $deliveryPerson?->currency ?? ($globalSettings['default_currency'] ?? 'GBP'));
    $latestDoc = $deliveryPerson?->documents?->sortByDesc('id')->first();
@endphp

<div class="saas-page-shell py-4 px-3 px-md-4">
    <div class="saas-header-card">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h1 class="saas-header-title">{{ $isEdit ? 'Edit Delivery Person: ' . $deliveryPerson->full_name : 'Add Delivery Person' }}</h1>
                <p class="saas-header-subtitle">Configure courier profile, encrypted identity documents, UK postcode coverage, and payment rates</p>
            </div>
            <div>
                <a href="{{ route('admin.delivery-persons.index') }}" class="btn btn-outline-secondary rounded-3 fw-semibold">
                    <i class="mdi mdi-arrow-left me-1"></i>Back to Delivery Persons
                </a>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger rounded-3 mb-4">
            <div class="fw-bold mb-1">Please fix the following validation errors:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('admin.delivery-persons.update', $deliveryPerson) : route('admin.delivery-persons.store') }}"
          enctype="multipart/form-data"
          class="saas-modal">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- SECTION A: PERSONAL INFORMATION --}}
        <div class="saas-form-section">
            <div class="saas-section-heading">
                <span class="saas-section-badge"><i class="mdi mdi-account-outline"></i></span>
                <span>Section A — Personal Information</span>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $deliveryPerson?->full_name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $deliveryPerson?->email) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1">Phone Number <span class="text-danger">*</span></label>
                    @include('admin::components.phone-input', [
                        'idPrefix' => 'dp_primary',
                        'dialName' => 'phone_country_code',
                        'phoneName' => 'phone',
                        'defaultDial' => $defaultDial,
                        'defaultIso' => $defaultCountryCode,
                        'countries' => $countries,
                        'required' => true,
                        'placeholder' => '7123 456789',
                    ])
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $deliveryPerson?->date_of_birth?->format('Y-m-d')) }}" max="{{ now()->subYears(18)->format('Y-m-d') }}" required>
                    <div class="form-text small">Must be 18+ years old.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Profile Photo (Optional)</label>
                    <input type="file" name="profile_photo" class="form-control" accept="image/*">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $deliveryPerson?->emergency_contact_name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1">Emergency Contact Phone</label>
                    <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $deliveryPerson?->emergency_contact_phone) }}">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold small mb-1">Internal Notes (Private)</label>
                    <textarea name="internal_notes" rows="2" class="form-control">{{ old('internal_notes', $deliveryPerson?->internal_notes) }}</textarea>
                </div>
            </div>
        </div>

        {{-- SECTION B: IDENTITY DOCUMENTS (PRIVATE STORAGE) --}}
        <div class="saas-form-section">
            <div class="saas-section-heading">
                <span class="saas-section-badge"><i class="mdi mdi-shield-lock-outline"></i></span>
                <span>Section B — Identity Documents (Encrypted &amp; Private Storage)</span>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Document Type @if(!$isEdit)<span class="text-danger">*</span>@endif</label>
                    <select name="document_type" class="form-select" @if(!$isEdit) required @endif>
                        <option value="national_id" @selected(old('document_type', $latestDoc?->document_type) === 'national_id')>National ID</option>
                        <option value="passport" @selected(old('document_type', $latestDoc?->document_type) === 'passport')>Passport</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Document Number @if(!$isEdit)<span class="text-danger">*</span>@endif</label>
                    <input type="text" name="document_number" class="form-control" value="{{ old('document_number') }}" placeholder="{{ $latestDoc ? 'Stored: ' . $latestDoc->document_number_masked . ' (enter to replace)' : 'Enter NID / Passport number' }}" @if(!$isEdit) required @endif>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Expiry Date</label>
                    <input type="date" name="document_expiry_date" class="form-control" value="{{ old('document_expiry_date', $latestDoc?->expiry_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Front Image (PDF / Image) @if(!$isEdit)<span class="text-danger">*</span>@endif</label>
                    <input type="file" name="document_front_image" class="form-control" accept=".pdf,image/*" @if(!$isEdit) required @endif>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Back Image (When Applicable)</label>
                    <input type="file" name="document_back_image" class="form-control" accept=".pdf,image/*">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Verification Status</label>
                    <select name="verification_status" class="form-select">
                        <option value="pending" @selected(old('verification_status', $latestDoc?->verification_status ?? 'pending') === 'pending')>Pending</option>
                        <option value="verified" @selected(old('verification_status', $latestDoc?->verification_status) === 'verified')>Verified</option>
                        <option value="rejected" @selected(old('verification_status', $latestDoc?->verification_status) === 'rejected')>Rejected</option>
                        <option value="expired" @selected(old('verification_status', $latestDoc?->verification_status) === 'expired')>Expired</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- SECTION C: RESIDENTIAL ADDRESS --}}
        <div class="saas-form-section">
            <div class="saas-section-heading">
                <span class="saas-section-badge"><i class="mdi mdi-home-outline"></i></span>
                <span>Section C — Residential Address (Separate from Coverage Postcodes)</span>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1">Address Line 1 <span class="text-danger">*</span></label>
                    <input type="text" name="address_line_1" class="form-control" value="{{ old('address_line_1', $deliveryPerson?->address_line_1) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1">Address Line 2</label>
                    <input type="text" name="address_line_2" class="form-control" value="{{ old('address_line_2', $deliveryPerson?->address_line_2) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">City / Town <span class="text-danger">*</span></label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $deliveryPerson?->city) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">County / Region</label>
                    <input type="text" name="county_region" class="form-control" value="{{ old('county_region', $deliveryPerson?->county_region) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Country <span class="text-danger">*</span></label>
                    <select name="country_code" class="form-select" required>
                        @foreach($countries as $country)
                            <option value="{{ $country['iso'] }}" @selected(strtoupper($defaultCountryCode) === strtoupper($country['iso']))>{{ $country['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Residential Postcode <span class="text-danger">*</span></label>
                    <input type="text" name="residential_postcode" class="form-control" value="{{ old('residential_postcode', $deliveryPerson?->residential_postcode) }}" required>
                </div>
            </div>
        </div>

        {{-- SECTION D: MULTIPLE UK POSTCODE COVERAGE --}}
        <div class="saas-form-section">
            <div class="saas-section-heading">
                <span class="saas-section-badge"><i class="mdi mdi-map-marker-multiple-outline"></i></span>
                <span>Section D — Assigned Delivery Postcodes (UK Address Database)</span>
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold small mb-1" for="coverage_postcodes">Assigned Delivery Postcodes <span class="text-danger">*</span></label>
                    <select id="coverage_postcodes" name="coverage_postcodes[]" class="form-select" multiple="multiple" required style="width: 100%;">
                        @foreach($selectedPostcodes as $pc)
                            <option value="{{ $pc }}" selected>{{ $pc }}</option>
                        @endforeach
                    </select>
                    <div class="form-text small">Select one or more distinct UK postcodes from <code>uk_addresses</code>. Duplicates and non-UK-database postcodes are rejected.</div>
                </div>
            </div>
        </div>

        {{-- SECTION E: WORK INFORMATION --}}
        <div class="saas-form-section">
            <div class="saas-section-heading">
                <span class="saas-section-badge"><i class="mdi mdi-briefcase-outline"></i></span>
                <span>Section E — Work Information</span>
            </div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Employment / Contract Type <span class="text-danger">*</span></label>
                    <select name="employment_type" class="form-select" required>
                        <option value="contractor" @selected(old('employment_type', $deliveryPerson?->employment_type) === 'contractor')>Contractor</option>
                        <option value="employee" @selected(old('employment_type', $deliveryPerson?->employment_type) === 'employee')>Employee</option>
                        <option value="other" @selected(old('employment_type', $deliveryPerson?->employment_type) === 'other')>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Work Status <span class="text-danger">*</span></label>
                    <select name="work_status" class="form-select" required>
                        <option value="active" @selected(old('work_status', $deliveryPerson?->work_status ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('work_status', $deliveryPerson?->work_status) === 'inactive')>Inactive</option>
                        <option value="suspended" @selected(old('work_status', $deliveryPerson?->work_status) === 'suspended')>Suspended</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Availability <span class="text-danger">*</span></label>
                    <select name="availability_status" class="form-select" required>
                        <option value="available" @selected(old('availability_status', $deliveryPerson?->availability_status ?? 'available') === 'available')>Available</option>
                        <option value="busy" @selected(old('availability_status', $deliveryPerson?->availability_status) === 'busy')>Busy</option>
                        <option value="off_duty" @selected(old('availability_status', $deliveryPerson?->availability_status) === 'off_duty')>Off Duty</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" value="{{ old('joining_date', $deliveryPerson?->joining_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Assigned Primary Store</label>
                    <select name="store_id" class="form-select">
                        <option value="">Any / Unassigned</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" @selected((string) old('store_id', $deliveryPerson?->store_id) === (string) $st->id)>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Vehicle Type</label>
                    <select name="vehicle_type" class="form-select">
                        <option value="">Select vehicle...</option>
                        @foreach(['Bicycle', 'Motorbike', 'Car', 'Van', 'Walking / Other'] as $vt)
                            <option value="{{ $vt }}" @selected(old('vehicle_type', $deliveryPerson?->vehicle_type) === $vt)>{{ $vt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Vehicle Registration</label>
                    <input type="text" name="vehicle_registration" class="form-control" value="{{ old('vehicle_registration', $deliveryPerson?->vehicle_registration) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1">Driving Licence Details</label>
                    <input type="text" name="license_details" class="form-control" value="{{ old('license_details', $deliveryPerson?->license_details) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold small mb-1">Work Notes</label>
                    <input type="text" name="work_notes" class="form-control" value="{{ old('work_notes', $deliveryPerson?->work_notes) }}">
                </div>
            </div>
        </div>

        {{-- SECTION F: PER-DELIVERY PAYMENT CONFIGURATION --}}
        <div class="saas-form-section">
            <div class="saas-section-heading">
                <span class="saas-section-badge"><i class="mdi mdi-cash-multiple"></i></span>
                <span>Section F — Per-Delivery Payment Configuration</span>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Payment Calculation Type <span class="text-danger">*</span></label>
                    <select name="payment_calculation_type" class="form-select" required>
                        <option value="separate_rates" @selected(old('payment_calculation_type', $deliveryPerson?->payment_calculation_type ?? 'separate_rates') === 'separate_rates')>Different Rates for Collection &amp; Delivery</option>
                        <option value="fixed_per_assignment" @selected(old('payment_calculation_type', $deliveryPerson?->payment_calculation_type) === 'fixed_per_assignment')>Fixed Payment Per Completed Assignment</option>
                        <option value="admin_override" @selected(old('payment_calculation_type', $deliveryPerson?->payment_calculation_type) === 'admin_override')>Admin-Approved Per-Assignment Override</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1">Currency <span class="text-danger">*</span></label>
                    <select name="currency" class="form-select" required>
                        @foreach($currencies as $code => $meta)
                            <option value="{{ $code }}" @selected(strtoupper($defaultCurrency) === strtoupper($code))>{{ $code }} ({{ $meta['symbol'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Per-Collection Rate <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="per_collection_rate" class="form-control" value="{{ old('per_collection_rate', $deliveryPerson ? number_format($deliveryPerson->per_collection_rate_minor / 100, 2, '.', '') : '5.00') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small mb-1">Per-Delivery Rate <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="per_delivery_rate" class="form-control" value="{{ old('per_delivery_rate', $deliveryPerson ? number_format($deliveryPerson->per_delivery_rate_minor / 100, 2, '.', '') : '6.00') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small mb-1">Effective Date</label>
                    <input type="date" name="payment_effective_date" class="form-control" value="{{ old('payment_effective_date', $deliveryPerson?->payment_effective_date?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold small mb-1">Payment Notes</label>
                    <input type="text" name="payment_notes" class="form-control" value="{{ old('payment_notes', $deliveryPerson?->payment_notes) }}" placeholder="Rate snapshot is stored with each assignment upon creation.">
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('admin.delivery-persons.index') }}" class="btn btn-light rounded-3 px-4">Cancel</a>
            <button type="submit" class="btn btn-saas-primary px-4">
                <i class="mdi mdi-content-save-outline"></i>
                <span>{{ $isEdit ? 'Update Delivery Person' : 'Save Delivery Person' }}</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/dist/libs/select2/dist/js/select2.full.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Prepopulate primary phone input value on edit/old
            $('#dp_primary_phone_input').val(@json(old('phone', $deliveryPerson?->phone ?? '')));

            // Phone dial picker behavior
            $(document).on('click', '.js-intl-dial-toggle', function(e) {
                e.stopPropagation();
                const $menu = $(this).closest('.intl-dial-wrapper').find('.js-intl-dial-menu');
                $('.js-intl-dial-menu').not($menu).addClass('d-none');
                $menu.toggleClass('d-none');
            });
            $(document).on('input', '.js-intl-dial-search', function() {
                const q = $(this).val().trim().toLowerCase();
                $(this).closest('.js-intl-dial-menu').find('.js-intl-dial-option').each(function() {
                    $(this).toggleClass('d-none', q !== '' && !String($(this).data('search') || '').includes(q));
                });
            });
            $(document).on('click', '.js-intl-dial-option', function() {
                const $w = $(this).closest('.intl-dial-wrapper');
                $w.find('.js-intl-dial-value').val($(this).data('dial'));
                $w.find('.js-intl-dial-code').text($(this).data('dial'));
                $w.find('.js-intl-dial-flag').attr('src', $(this).data('flag-url'));
                $w.find('.js-intl-dial-menu').addClass('d-none');
            });
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.intl-dial-wrapper').length) {
                    $('.js-intl-dial-menu').addClass('d-none');
                }
            });

            // Select2 multi-select UK postcodes from uk_addresses
            const $pc = $('#coverage_postcodes');
            if ($pc.length && !$pc.hasClass('select2-hidden-accessible')) {
                $pc.select2({
                    placeholder: 'Search and select UK delivery postcodes...',
                    allowClear: true,
                    tags: false,
                    multiple: true,
                    width: '100%',
                    minimumInputLength: 0,
                    ajax: {
                        url: '{{ route('admin.stores.uk-postcodes') }}',
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ q: params.term || '', page: params.page || 1 }),
                        processResults: (data, params) => {
                            params.page = params.page || 1;
                            return {
                                results: data.results || [],
                                pagination: { more: Boolean(data.pagination && data.pagination.more) }
                            };
                        },
                        cache: true
                    }
                });
            }
        });
    </script>
@endpush
