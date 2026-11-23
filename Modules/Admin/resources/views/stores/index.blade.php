@extends('admin::layouts.app')

@section('title', 'Store Management - Admin Panel')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/select2/dist/css/select2.min.css') }}">
@endpush

@section('content')
<div class="saas-page-shell store-management py-3 px-3">
    {{-- Header --}}
    <div class="saas-header-card">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
            <div>
                <h1 class="saas-header-title">Store Management</h1>
                <p class="saas-header-subtitle">Manage stores, international locations, and operations.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.stores.vendors.index') }}" class="btn btn-outline-secondary rounded-3 fw-semibold">
                    <i class="mdi mdi-account-multiple"></i>
                    <span>Vendors</span>
                </a>
                <button type="button" id="btnOpenCreateStore" class="btn btn-saas-primary">
                    <i class="mdi mdi-plus font-16"></i>
                    <span>Add Store</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Summary Cards (1 row on desktop, 2x2 on tablet/mobile) --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-lg-3">
            <div class="saas-stat-card tint-blue">
                <div>
                    <div class="saas-stat-label">Total Stores</div>
                    <div class="saas-stat-value" id="statTotalStores">{{ $stats['total'] }}</div>
                </div>
                <span class="saas-stat-icon blue"><i class="mdi mdi-store"></i></span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="saas-stat-card tint-green">
                <div>
                    <div class="saas-stat-label">Active Stores</div>
                    <div class="saas-stat-value" id="statActiveStores">{{ $stats['active'] }}</div>
                </div>
                <span class="saas-stat-icon green"><i class="mdi mdi-check-circle"></i></span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="saas-stat-card tint-amber">
                <div>
                    <div class="saas-stat-label">Inactive Stores</div>
                    <div class="saas-stat-value" id="statInactiveStores">{{ $stats['inactive'] }}</div>
                </div>
                <span class="saas-stat-icon amber"><i class="mdi mdi-pause-circle"></i></span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="saas-stat-card tint-purple">
                <div>
                    <div class="saas-stat-label">Linked Vendors</div>
                    <div class="saas-stat-value" id="statLinkedVendors">{{ $stats['linked_vendors'] }}</div>
                </div>
                <span class="saas-stat-icon purple"><i class="mdi mdi-account-multiple"></i></span>
            </div>
        </div>
    </div>

    {{-- Compact Filter Toolbar & Table Card --}}
    <div class="saas-surface-card">
        <div class="saas-filter-bar store-management__filters">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-lg">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="mdi mdi-magnify text-muted"></i></span>
                        <input type="text" id="filterStoreSearch" class="form-control border-start-0" aria-label="Search stores" placeholder="Search store name, code, postcode, phone...">
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select id="filterStoreCountry" class="form-select" aria-label="Filter by country">
                        <option value="">All Countries</option>
                        @foreach($filterCountries as $countryName)
                            <option value="{{ $countryName }}">{{ $countryName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select id="filterStoreCity" class="form-select" aria-label="Filter by city">
                        <option value="">All Cities</option>
                        @foreach($filterCities as $cityName)
                            <option value="{{ $cityName }}">{{ $cityName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-auto">
                    <select id="filterStoreStatus" class="form-select" aria-label="Filter by status">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="suspended">Inactive</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <select id="filterStoreVendor" class="form-select" aria-label="Filter by vendor">
                        <option value="">All Vendors</option>
                        <option value="In-House Store">In-House Store</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->name }}">{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-auto d-grid">
                    <button type="button" id="btnResetStoreFilters" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="mdi mdi-refresh"></i>
                        <span>Reset</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="store-table-scroll" tabindex="0" role="region" aria-label="Store management table">
            <table id="storesTable" class="table saas-table store-management-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Code / ID</th>
                        <th>Store Name</th>
                        <th>Country</th>
                        <th>City</th>
                        <th>Postcode / ZIP</th>
                        <th>Assigned Vendor</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create Store Modal --}}
<div class="modal fade saas-modal store-management__modal" id="createStoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom bg-white py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="saas-section-badge"><i class="mdi mdi-store"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Add New Store</h5>
                        <small class="text-muted">Configure store profile, international address, contact, and operations</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createStoreForm" enctype="multipart/form-data" class="d-flex flex-column flex-grow-1 overflow-hidden" style="min-height: 0;">
                @csrf
                <div class="modal-body p-3" style="background: #f6f8fc; overflow-y: auto;">
                    @include('admin::stores._form', ['prefix' => 'create'])
                </div>
                <div class="modal-footer bg-white border-top py-2 px-3 flex-shrink-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-saas-primary px-4" id="btnSaveStore">
                        <i class="mdi mdi-content-save"></i>
                        <span>Save Store</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Store Modal --}}
<div class="modal fade saas-modal store-management__modal" id="editStoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom bg-white py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="saas-section-badge"><i class="mdi mdi-pencil"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Edit Store</h5>
                        <small class="text-muted">Update store configuration without disrupting historical bookings</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editStoreForm" enctype="multipart/form-data" class="d-flex flex-column flex-grow-1 overflow-hidden" style="min-height: 0;">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_store_id" name="id">
                <div class="modal-body p-3" style="background: #f6f8fc; overflow-y: auto;">
                    @include('admin::stores._form', ['prefix' => 'edit'])
                </div>
                <div class="modal-footer bg-white border-top py-2 px-3 flex-shrink-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-saas-primary px-4" id="btnUpdateStore">
                        <i class="mdi mdi-content-save"></i>
                        <span>Update Store</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Store Details Modal --}}
<div class="modal fade saas-modal store-management__modal" id="viewStoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom bg-white py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="saas-section-badge"><i class="mdi mdi-store"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="viewStoreTitle">Store Details</h5>
                        <small class="text-muted" id="viewStoreCode"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background: #f6f8fc;" id="viewStoreBody"></div>
            <div class="modal-footer bg-white border-top py-2 px-3 flex-shrink-0">
                <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Store Portal Access & Users Modal -->
<div class="modal fade saas-modal store-management__modal" id="storePortalUsersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom bg-white py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="saas-section-badge"><i class="mdi mdi-account-key"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Store Portal Access</h5>
                        <small class="text-muted" id="portalUsersStoreName"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" style="background: #f6f8fc;">
                <div id="portalUsersErrors" class="alert alert-danger small d-none mb-3"></div>

                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2">Linked Store Portal Users</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="portalUsersTableBody" class="small"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2">Create Store User</h6>
                        <form id="createStoreUserForm" class="row g-2 small">
                            @csrf
                            <input type="hidden" id="portalUsersStoreId">
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold mb-1">Name *</label>
                                <input type="text" name="name" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold mb-1">Email *</label>
                                <input type="email" name="email" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold mb-1">Role *</label>
                                <select name="role" class="form-select form-select-sm" required>
                                    <option value="owner">Owner</option>
                                    <option value="manager">Manager</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label fw-semibold mb-1">Code</label>
                                <input type="text" name="phone_country_code" class="form-control form-control-sm" placeholder="+44">
                            </div>
                            <div class="col-8 col-md-4">
                                <label class="form-label fw-semibold mb-1">Phone</label>
                                <input type="text" name="phone" class="form-control form-control-sm" placeholder="7700900111">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fw-semibold mb-1">Password *</label>
                                <input type="password" name="password" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fw-semibold mb-1">Confirm *</label>
                                <input type="password" name="password_confirmation" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12 d-flex justify-content-end mt-2">
                                <button type="submit" class="btn btn-sm btn-warning fw-bold px-3">Create Store User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/dist/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/dist/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('admin/dist/libs/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('admin/dist/libs/select2/dist/js/select2.full.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });

            const ADDRESS_CONFIG = @json($addressConfigMap);
            const DEFAULT_COUNTRY_CODE = @json(strtoupper($globalSettings['business_country_code'] ?? 'GB'));
            const UK_POSTCODES_URL = @json(route('admin.stores.uk-postcodes'));

            function escapeHtml(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function getModal(id) {
                const el = document.getElementById(id);
                if (!el) return null;
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    return bootstrap.Modal.getOrCreateInstance(el);
                }
                return null;
            }

            function showModal(id) {
                const instance = getModal(id);
                if (instance) {
                    instance.show();
                } else {
                    $(`#${id}`).modal('show');
                }
            }

            function hideModal(id) {
                const instance = getModal(id);
                if (instance) {
                    instance.hide();
                } else {
                    $(`#${id}`).modal('hide');
                }
            }

            $('#btnOpenCreateStore').on('click', function() {
                $('#createStoreErrors').addClass('d-none').empty();
                applyCountryAddressMode('create', $('#create_country_code').val() || DEFAULT_COUNTRY_CODE, true);
                showModal('createStoreModal');
            });

            // International Phone Dial Picker helpers
            function setDialPickerValue($wrapper, dial, iso) {
                if (!$wrapper.length) return;
                const $opt = dial
                    ? $wrapper.find(`.js-intl-dial-option[data-dial="${dial}"]`).first()
                    : $wrapper.find(`.js-intl-dial-option[data-iso="${(iso || '').toLowerCase()}"]`).first();
                const $target = $opt.length ? $opt : $wrapper.find('.js-intl-dial-option').first();
                if ($target.length) {
                    $wrapper.find('.js-intl-dial-value').val($target.data('dial'));
                    $wrapper.find('.js-intl-dial-code').text($target.data('dial'));
                    $wrapper.find('.js-intl-dial-flag').attr('src', $target.data('flag-url'));
                }
            }

            $(document).on('click', '.js-intl-dial-toggle', function(e) {
                e.stopPropagation();
                const $wrapper = $(this).closest('.intl-dial-wrapper');
                const $menu = $wrapper.find('.js-intl-dial-menu');
                const isOpen = !$menu.hasClass('d-none');
                $('.js-intl-dial-menu').addClass('d-none');
                $('.js-intl-dial-toggle').attr('aria-expanded', 'false');
                if (!isOpen) {
                    $menu.removeClass('d-none');
                    $(this).attr('aria-expanded', 'true');
                    $menu.find('.js-intl-dial-search').val('').trigger('input').trigger('focus');
                }
            });

            $(document).on('input', '.js-intl-dial-search', function() {
                const q = $(this).val().trim().toLowerCase();
                $(this).closest('.js-intl-dial-menu').find('.js-intl-dial-option').each(function() {
                    const hay = String($(this).data('search') || '');
                    $(this).toggleClass('d-none', q !== '' && !hay.includes(q));
                });
            });

            $(document).on('click', '.js-intl-dial-option', function() {
                const $wrapper = $(this).closest('.intl-dial-wrapper');
                $wrapper.find('.js-intl-dial-value').val($(this).data('dial'));
                $wrapper.find('.js-intl-dial-code').text($(this).data('dial'));
                $wrapper.find('.js-intl-dial-flag').attr('src', $(this).data('flag-url'));
                $wrapper.find('.js-intl-dial-menu').addClass('d-none');
                $wrapper.find('.js-intl-dial-toggle').attr('aria-expanded', 'false');
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.intl-dial-wrapper').length) {
                    $('.js-intl-dial-menu').addClass('d-none');
                    $('.js-intl-dial-toggle').attr('aria-expanded', 'false');
                }
            });

            // Select2 initialization (single init guard)
            function initUkPostcodeSelect($select, $modalParent, $hiddenInput) {
                if (!$select.length || $select.hasClass('select2-hidden-accessible')) {
                    return;
                }
                $select.select2({
                    dropdownParent: $modalParent,
                    placeholder: 'Search and select UK postcode',
                    allowClear: true,
                    tags: false,
                    width: '100%',
                    minimumInputLength: 0,
                    ajax: {
                        url: UK_POSTCODES_URL,
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
                    },
                    language: {
                        noResults: () => 'No matching UK postcodes found',
                        searching: () => 'Searching postcodes...'
                    }
                });

                $select.on('change', function() {
                    $hiddenInput.val($(this).val() || '');
                    const selectedText = $(this).find('option:selected').text();
                    if (!selectedText.includes('(Legacy - not in UK addresses)')) {
                        $('#edit_legacy_postcode_notice').addClass('d-none');
                    }
                });
            }

            initUkPostcodeSelect($('#create_postcode_uk'), $('#createStoreModal'), $('#create_postcode_value'));
            initUkPostcodeSelect($('#edit_postcode_uk'), $('#editStoreModal'), $('#edit_postcode_value'));

            $('#create_postcode_intl').on('input', function() {
                $('#create_postcode_value').val($(this).val());
            });
            $('#edit_postcode_intl').on('input', function() {
                $('#edit_postcode_value').val($(this).val());
            });

            // Country-aware address labels & postcode mode switching
            function applyCountryAddressMode(prefix, countryCode, preservePostcode = false) {
                const iso = (countryCode || '').toUpperCase();
                const cfg = ADDRESS_CONFIG[iso] || ADDRESS_CONFIG.DEFAULT;

                $(`#${prefix}_state_label`).text(cfg.state_label);
                $(`#${prefix}_state_region`).attr('placeholder', cfg.state_placeholder);
                $(`#${prefix}_postal_label`).html(
                    cfg.postal_label + (cfg.postal_required ? ' <span class="text-danger">*</span>' : '')
                );
                $(`#${prefix}_postcode_intl`).attr('placeholder', cfg.postal_placeholder);

                const isUk = iso === 'GB';
                $(`#${prefix}_uk_postcode_wrap`).toggleClass('d-none', !isUk);
                $(`#${prefix}_intl_postcode_wrap`).toggleClass('d-none', isUk);

                if (!preservePostcode) {
                    $(`#${prefix}_postcode_value`).val('');
                    if (isUk) {
                        $(`#${prefix}_postcode_uk`).val(null).trigger('change');
                    } else {
                        $(`#${prefix}_postcode_intl`).val('');
                    }
                }
            }

            $('.js-country-selector').on('change', function() {
                const prefix = $(this).data('prefix');
                const iso = $(this).val();
                const dial = $(this).find(':selected').data('dial');
                applyCountryAddressMode(prefix, iso, false);

                if (prefix === 'create' && dial) {
                    setDialPickerValue($('#create_primary_dial_picker'), dial, iso);
                }
                if (prefix === 'edit') {
                    const originalCountry = $(this).data('original-country') || '';
                    $('#edit_country_change_warning').toggleClass('d-none', !originalCountry || originalCountry === iso);
                }
            });

            // Ownership type toggles vendor selection
            function toggleVendorCol(prefix) {
                const isVendor = $(`#${prefix}_ownership_type`).val() === 'vendor_store';
                $(`#${prefix}VendorSelectCol`).toggle(isVendor);
                $(`#${prefix}_vendor_id`).prop('required', isVendor);
                if (!isVendor) $(`#${prefix}_vendor_id`).val('');
            }
            $('#create_ownership_type').on('change', () => toggleVendorCol('create'));
            $('#edit_ownership_type').on('change', () => toggleVendorCol('edit'));

            // Auto-slug generator
            let slugTimer;
            $('#create_name').on('input', function() {
                clearTimeout(slugTimer);
                const name = $(this).val();
                slugTimer = setTimeout(() => {
                    $.get('{{ route('admin.stores.check-slug') }}', { name }, res => $('#create_slug').val(res.slug));
                }, 350);
            });

            // Reset Create Modal cleanly on open
            $('#createStoreModal').on('show.bs.modal', function() {
                $('#createStoreErrors').addClass('d-none').empty();
                applyCountryAddressMode('create', $('#create_country_code').val() || DEFAULT_COUNTRY_CODE, true);
            });

            // DataTable Initialization
            const storesTable = $('#storesTable').DataTable({
                ajax: '{{ route('admin.stores.data') }}',
                autoWidth: false,
                scrollX: false,
                pageLength: 15,
                lengthMenu: [10, 15, 25, 50],
                dom: '<"p-3 d-flex flex-wrap justify-content-between align-items-center gap-2"l>rt<"p-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-top"ip>',
                language: {
                    emptyTable: '<div class="py-4 text-center text-muted"><i class="mdi mdi-store d-block font-24 mb-1"></i>No stores found matching your filters.</div>'
                },
                columns: [
                    {
                        data: 'store_code',
                        render: code => `<span class="saas-code-pill">${escapeHtml(code)}</span>`
                    },
                    {
                        data: null,
                        render: (data, type, row) => {
                            const avatar = row.logo_url
                                ? `<img src="${row.logo_url}" class="rounded-3 border" style="width: 38px; height: 38px; object-fit: cover;">`
                                : `<span class="rounded-3 d-inline-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; background: #fff8e5; color: #b45309; border: 1px solid rgba(255,198,41,0.45);">${escapeHtml((row.name || 'S').charAt(0).toUpperCase())}</span>`;
                            return `
                                <div class="d-flex align-items-center gap-2">
                                    ${avatar}
                                    <div>
                                        <div class="fw-bold text-dark">${escapeHtml(row.name)}</div>
                                        <div class="small text-muted">${escapeHtml((row.store_type || '').replace('_', ' '))}</div>
                                    </div>
                                </div>`;
                        }
                    },
                    {
                        data: 'country_name',
                        render: val => val ? `<span class="fw-medium">${escapeHtml(val)}</span>` : '<span class="text-muted">—</span>'
                    },
                    {
                        data: 'city',
                        render: val => val ? escapeHtml(val) : '<span class="text-muted">—</span>'
                    },
                    {
                        data: 'postcode',
                        render: val => val ? `<span class="badge bg-light text-dark border">${escapeHtml(val)}</span>` : '<span class="text-muted">—</span>'
                    },
                    {
                        data: 'vendor_name',
                        render: (val, type, row) => row.ownership_type === 'my_store'
                            ? '<span class="badge bg-primary-subtle text-primary rounded-pill">In-House Store</span>'
                            : `<span class="fw-medium">${escapeHtml(val)}</span>`
                    },
                    {
                        data: 'contact_person',
                        render: val => val ? escapeHtml(val) : '<span class="text-muted">—</span>'
                    },
                    {
                        data: 'phone_display',
                        render: val => val ? `<span class="text-nowrap">${escapeHtml(val)}</span>` : '<span class="text-muted">—</span>'
                    },
                    {
                        data: 'status',
                        render: status => {
                            const label = status === 'suspended' ? 'Inactive' : status;
                            return `<span class="saas-status-badge ${escapeHtml(status)}">${escapeHtml(label)}</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: (data, type, row) => {
                            const toggleTitle = row.status === 'active' ? 'Deactivate Store' : 'Activate Store';
                            const toggleIcon = row.status === 'active' ? 'mdi-pause' : 'mdi-play';
                            return `
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="saas-action-btn view view-store-btn" data-id="${row.id}" title="View Details">
                                        <i class="mdi mdi-eye font-16"></i>
                                    </button>
                                    <button type="button" class="saas-action-btn view portal-users-btn" data-id="${row.id}" data-name="${escapeHtml(row.name)}" title="Store Portal Access">
                                        <i class="mdi mdi-account-key font-16"></i>
                                    </button>
                                    <a href="{{ url('admin/stores') }}/${row.id}/account" class="saas-action-btn view" title="View Account">
                                        <i class="mdi mdi-wallet font-16"></i>
                                    </a>
                                    <button type="button" class="saas-action-btn edit edit-store-btn" data-id="${row.id}" title="Edit Store">
                                        <i class="mdi mdi-pencil font-16"></i>
                                    </button>
                                    <button type="button" class="saas-action-btn toggle toggle-status-btn" data-id="${row.id}" title="${toggleTitle}">
                                        <i class="mdi ${toggleIcon} font-16"></i>
                                    </button>
                                    <button type="button" class="saas-action-btn delete delete-store-btn" data-id="${row.id}" data-name="${escapeHtml(row.name)}" title="Archive Store">
                                        <i class="mdi mdi-archive font-16"></i>
                                    </button>
                                </div>`;
                        }
                    }
                ],
                order: [[0, 'asc']]
            });

            // Custom Filters
            $('#filterStoreSearch').on('input', function() {
                storesTable.search(this.value).draw();
            });
            $('#filterStoreCountry').on('change', function() {
                const v = $.fn.dataTable.util.escapeRegex(this.value);
                storesTable.column(2).search(v ? `^${v}$` : '', true, false).draw();
            });
            $('#filterStoreCity').on('change', function() {
                const v = $.fn.dataTable.util.escapeRegex(this.value);
                storesTable.column(3).search(v ? `^${v}$` : '', true, false).draw();
            });
            $('#filterStoreStatus').on('change', function() {
                const val = this.value === 'suspended' ? 'Inactive' : this.value;
                const v = $.fn.dataTable.util.escapeRegex(val);
                storesTable.column(8).search(v ? `^${v}$` : '', true, false).draw();
            });
            $('#filterStoreVendor').on('change', function() {
                const v = $.fn.dataTable.util.escapeRegex(this.value);
                storesTable.column(5).search(v ? `^${v}$` : '', true, false).draw();
            });
            $('#btnResetStoreFilters').on('click', function() {
                $('#filterStoreSearch').val('');
                $('#filterStoreCountry, #filterStoreCity, #filterStoreStatus, #filterStoreVendor').val('');
                storesTable.search('').columns().search('').draw();
            });

            // Refresh summary stats from DataTable data
            function refreshStoreStats() {
                const rows = storesTable.rows().data().toArray();
                const total = rows.length;
                const active = rows.filter(r => r.status === 'active').length;
                const inactive = total - active;
                const vendorIds = new Set();
                rows.forEach(r => {
                    if (r.vendor_id) vendorIds.add(r.vendor_id);
                });
                $('#statTotalStores').text(total);
                $('#statActiveStores').text(active);
                $('#statInactiveStores').text(inactive);
                $('#statLinkedVendors').text(vendorIds.size);
            }

            // Create Store Submission
            $('#createStoreForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#btnSaveStore').prop('disabled', true);
                const origHtml = $btn.html();
                $btn.html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

                $.ajax({
                    url: '{{ route('admin.stores.store') }}',
                    method: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        hideModal('createStoreModal');
                        $('#createStoreForm')[0].reset();
                        $('#create_postcode_uk').val(null).trigger('change');
                        $('#create_postcode_intl, #create_postcode_value').val('');
                        $('#createStoreErrors').addClass('d-none').empty();
                        if (typeof toastr !== 'undefined') toastr.success(res.message);
                        storesTable.ajax.reload(refreshStoreStats, false);
                    },
                    error: function(xhr) {
                        const errs = xhr.responseJSON?.errors;
                        let msg = '<ul class="mb-0 ps-3">';
                        if (errs) {
                            Object.values(errs).forEach(errArr => msg += `<li>${escapeHtml(errArr[0])}</li>`);
                        } else {
                            msg += `<li>${escapeHtml(xhr.responseJSON?.message || 'Error creating store')}</li>`;
                        }
                        msg += '</ul>';
                        $('#createStoreErrors').removeClass('d-none').html(msg);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(origHtml);
                    }
                });
            });

            // View Store Details
            $(document).on('click', '.view-store-btn', function() {
                let $tr = $(this).closest('tr');
                if ($tr.hasClass('child')) {
                    $tr = $tr.prev();
                }
                const s = storesTable.row($tr).data();
                if (!s) return;

                const valOrFallback = (v) => (v !== null && v !== undefined && String(v).trim() !== '') ? escapeHtml(v) : '<span class="text-muted">Not provided</span>';
                const statusLabel = s.status === 'suspended' ? 'Inactive' : (s.status || 'Not provided');
                const storeTypeLabel = (s.store_type || '').replace('_', ' ') || 'Not provided';
                const ownershipLabel = s.ownership_type === 'vendor_store' ? 'Partner Vendor Store' : 'In-House Store';

                $('#viewStoreTitle').text(s.name || 'Store Details');
                $('#viewStoreCode').text(`${s.store_code || ''} • ${statusLabel}`);
                const addrParts = [s.address_line_1, s.address_line_2, s.city, s.state_region, s.postcode, s.country_name].filter(Boolean);
                const addressDisplay = addrParts.length ? addrParts.join(', ') : (s.full_address || '');
                const logoHtml = s.logo_url
                    ? `<img src="${s.logo_url}" alt="${escapeHtml(s.name)}" class="rounded-3 border mb-3" style="width: 64px; height: 64px; object-fit: cover;">`
                    : '';

                $('#viewStoreBody').html(`
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="saas-form-section h-100 mb-0">
                                <div class="saas-section-heading"><span class="saas-section-badge"><i class="mdi mdi-information"></i></span><span>Store &amp; Contact</span></div>
                                ${logoHtml}
                                <p class="small mb-2"><strong>Store Name:</strong> ${valOrFallback(s.name)}</p>
                                <p class="small mb-2"><strong>Store Code:</strong> ${valOrFallback(s.store_code)}</p>
                                <p class="small mb-2"><strong>Store Type:</strong> ${valOrFallback(storeTypeLabel)}</p>
                                <p class="small mb-2"><strong>Ownership Type:</strong> ${valOrFallback(ownershipLabel)}</p>
                                <p class="small mb-2"><strong>Assigned Vendor:</strong> ${valOrFallback(s.vendor_name)}</p>
                                <p class="small mb-2"><strong>Store Manager:</strong> ${valOrFallback(s.admin_name)}</p>
                                <p class="small mb-2"><strong>Contact Person:</strong> ${valOrFallback(s.contact_person)}</p>
                                <p class="small mb-2"><strong>Phone:</strong> ${valOrFallback(s.phone_display)}</p>
                                <p class="small mb-2"><strong>Email:</strong> ${valOrFallback(s.email)}</p>
                                <p class="small mb-2"><strong>Website:</strong> ${valOrFallback(s.website)}</p>
                                <p class="small mb-0"><strong>Description:</strong> ${valOrFallback(s.description)}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="saas-form-section h-100 mb-0">
                                <div class="saas-section-heading"><span class="saas-section-badge"><i class="mdi mdi-map-marker"></i></span><span>Address &amp; Operations</span></div>
                                <p class="small mb-2"><strong>Country:</strong> ${valOrFallback(s.country_name)}</p>
                                <p class="small mb-2"><strong>Full Address:</strong> ${valOrFallback(addressDisplay)}</p>
                                <p class="small mb-2"><strong>Postcode / ZIP:</strong> ${valOrFallback(s.postcode)}</p>
                                <p class="small mb-2"><strong>Turnaround:</strong> ${valOrFallback(s.turnaround_time)}</p>
                                <p class="small mb-2"><strong>Daily Capacity:</strong> ${s.capacity ? escapeHtml(s.capacity) + ' orders' : '<span class="text-muted">Not provided</span>'}</p>
                                <p class="small mb-2"><strong>Delivery:</strong> ${s.has_delivery ? escapeHtml((s.currency_symbol || '') + Number(s.delivery_fee || 0).toFixed(2)) : 'Pickup Only'}</p>
                                <p class="small mb-2"><strong>Min Order:</strong> ${escapeHtml((s.currency_symbol || '') + Number(s.min_order_amount || 0).toFixed(2))}</p>
                                <p class="small mb-2"><strong>Accepting Orders:</strong> ${s.is_accepting_orders ? 'Yes' : 'Paused'}</p>
                                <p class="small mb-2"><strong>Created Date:</strong> ${valOrFallback(s.created_at)}</p>
                                <p class="small mb-0"><strong>Updated Date:</strong> ${valOrFallback(s.updated_at)}</p>
                            </div>
                        </div>
                    </div>
                `);
                showModal('viewStoreModal');
            });

            // Open Edit Store Modal
            $(document).on('click', '.edit-store-btn', function() {
                let $tr = $(this).closest('tr');
                if ($tr.hasClass('child')) {
                    $tr = $tr.prev();
                }
                const store = storesTable.row($tr).data();
                if (!store) return;

                $('#editStoreErrors').addClass('d-none').empty();
                $('#edit_store_id').val(store.id);
                $('#edit_name').val(store.name);
                $('#edit_store_code').val(store.store_code || '');
                $('#edit_slug').val(store.slug || '');
                $('#edit_status').val(store.status);
                $('#edit_store_type').val(store.store_type);
                $('#edit_ownership_type').val(store.ownership_type).trigger('change');
                $('#edit_vendor_id').val(store.vendor_id || '');
                $('#edit_admin_id').val(store.admin_id || '');
                $('#edit_description').val(store.description || '');

                if (store.logo_url) {
                    $('#edit_logo_preview').attr('src', store.logo_url);
                    $('#edit_logo_preview_wrap').removeClass('d-none');
                } else {
                    $('#edit_logo_preview_wrap').addClass('d-none');
                }

                // Country & Address
                const countryIso = (store.country_code || '').toUpperCase();
                $('#edit_country_code').data('original-country', countryIso).val(countryIso);
                $('#edit_country_change_warning').addClass('d-none');
                applyCountryAddressMode('edit', countryIso, true);

                $('#edit_address_line_1').val(store.address_line_1 || store.full_address || '');
                $('#edit_address_line_2').val(store.address_line_2 || '');
                $('#edit_city').val(store.city || '');
                $('#edit_state_region').val(store.state_region || '');
                $('#edit_latitude').val(store.latitude ?? '');
                $('#edit_longitude').val(store.longitude ?? '');

                // Postcode population
                const $editUkSelect = $('#edit_postcode_uk');
                const $legacyNotice = $('#edit_legacy_postcode_notice');
                $editUkSelect.find('option[data-dynamic-postcode="true"]').remove();
                $('#edit_postcode_value').val(store.postcode || '');

                if (countryIso === 'GB') {
                    if (store.postcode) {
                        const isValidUk = store.postcode_in_uk_addresses !== false;
                        const label = isValidUk ? store.postcode : `${store.postcode} (Legacy - not in UK addresses)`;
                        const opt = new Option(label, store.postcode, true, true);
                        opt.setAttribute('data-dynamic-postcode', 'true');
                        $editUkSelect.append(opt).trigger('change');
                        $legacyNotice.toggleClass('d-none', isValidUk);
                    } else {
                        $editUkSelect.val(null).trigger('change');
                        $legacyNotice.addClass('d-none');
                    }
                } else {
                    $('#edit_postcode_intl').val(store.postcode || '');
                    $legacyNotice.addClass('d-none');
                }

                // Contact & Phone Dialing Codes
                $('#edit_contact_person').val(store.contact_person || '');
                $('#edit_email').val(store.email || '');
                $('#edit_website').val(store.website || '');
                setDialPickerValue($('#edit_primary_dial_picker'), store.phone_country_code, countryIso || DEFAULT_COUNTRY_CODE);
                $('#edit_primary_phone_input').val(store.phone || '');
                setDialPickerValue($('#edit_alt_dial_picker'), store.alt_phone_country_code, countryIso || DEFAULT_COUNTRY_CODE);
                $('#edit_alt_phone_input').val(store.alt_phone || '');

                // Operations
                $('#edit_turnaround_time').val(store.turnaround_time || '');
                $('#edit_capacity').val(store.capacity || '');
                $('#edit_min_order_amount').val(store.min_order_amount ?? 0);
                $('#edit_delivery_fee').val(store.delivery_fee ?? 0);
                $('#edit_free_delivery_threshold').val(store.free_delivery_threshold ?? '');
                $('#edit_has_delivery').prop('checked', Boolean(store.has_delivery));
                $('#edit_is_accepting_orders').prop('checked', Boolean(store.is_accepting_orders));
                $('#edit_service_areas_text').val(store.service_areas_text || '');
                $('#edit_verification_notes').val(store.verification_notes || '');

                $('.edit-service-checkbox').prop('checked', false);
                if (Array.isArray(store.service_ids)) {
                    store.service_ids.forEach(id => $(`#edit_srv_${id}`).prop('checked', true));
                }

                showModal('editStoreModal');
            });

            // Edit Store Submission
            $('#editStoreForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#edit_store_id').val();
                const $btn = $('#btnUpdateStore').prop('disabled', true);
                const origHtml = $btn.html();
                $btn.html('<span class="spinner-border spinner-border-sm me-1"></span>Updating...');

                $.ajax({
                    url: `{{ url('admin/stores') }}/${id}`,
                    method: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        hideModal('editStoreModal');
                        $('#editStoreErrors').addClass('d-none').empty();
                        if (typeof toastr !== 'undefined') toastr.success(res.message);
                        storesTable.ajax.reload(refreshStoreStats, false);
                    },
                    error: function(xhr) {
                        const errs = xhr.responseJSON?.errors;
                        let msg = '<ul class="mb-0 ps-3">';
                        if (errs) {
                            Object.values(errs).forEach(errArr => msg += `<li>${escapeHtml(errArr[0])}</li>`);
                        } else {
                            msg += `<li>${escapeHtml(xhr.responseJSON?.message || 'Error updating store')}</li>`;
                        }
                        msg += '</ul>';
                        $('#editStoreErrors').removeClass('d-none').html(msg);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(origHtml);
                    }
                });
            });

            // Activate / Deactivate Store Status
            $(document).on('click', '.toggle-status-btn', function() {
                const id = $(this).data('id');
                $.ajax({
                    url: `{{ url('admin/stores') }}/${id}/toggle-status`,
                    method: 'PATCH',
                    success: res => {
                        toastr.success(res.message);
                        storesTable.ajax.reload(refreshStoreStats, false);
                    },
                    error: () => toastr.error('Failed to update store status.')
                });
            });

            // Archive / Soft-Delete Store
            $(document).on('click', '.delete-store-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                Swal.fire({
                    title: 'Archive Store?',
                    text: `"${name}" will be archived safely. Historical booking records will remain intact.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, archive store'
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ url('admin/stores') }}/${id}`,
                            method: 'DELETE',
                            success: res => {
                                toastr.success(res.message);
                                storesTable.ajax.reload(refreshStoreStats, false);
                            },
                            error: () => toastr.error('Failed to archive store.')
                        });
                    }
                });
            });

            // Store Portal Users Management
            function loadStorePortalUsers(storeId) {
                $.getJSON(`{{ url('admin/stores') }}/${storeId}/users`, function(res) {
                    const $tbody = $('#portalUsersTableBody').empty();
                    if (!res.users || !res.users.length) {
                        $tbody.append('<tr><td colspan="6" class="text-center text-muted py-3">No Store Portal users linked yet.</td></tr>');
                        return;
                    }
                    res.users.forEach(u => {
                        const badge = u.is_active
                            ? '<span class="badge bg-success-subtle text-success">Active</span>'
                            : '<span class="badge bg-danger-subtle text-danger">Inactive</span>';
                        $tbody.append(`
                            <tr>
                                <td class="fw-semibold">${escapeHtml(u.name)}</td>
                                <td>${escapeHtml(u.email)}</td>
                                <td>${escapeHtml(u.phone_display)}</td>
                                <td class="text-capitalize">${escapeHtml(u.role)}</td>
                                <td>${badge}</td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-xs btn-outline-secondary toggle-portal-user-btn" data-store-id="${storeId}" data-user-id="${u.id}">
                                        ${u.is_active ? 'Deactivate' : 'Activate'}
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                });
            }

            $(document).on('click', '.portal-users-btn', function() {
                const storeId = $(this).data('id');
                $('#portalUsersStoreId').val(storeId);
                $('#portalUsersStoreName').text($(this).data('name'));
                $('#portalUsersErrors').addClass('d-none').empty();
                loadStorePortalUsers(storeId);
                showModal('storePortalUsersModal');
            });

            $('#createStoreUserForm').on('submit', function(e) {
                e.preventDefault();
                const storeId = $('#portalUsersStoreId').val();
                $('#portalUsersErrors').addClass('d-none').empty();
                $.ajax({
                    url: `{{ url('admin/stores') }}/${storeId}/users`,
                    method: 'POST',
                    data: $(this).serialize(),
                    success: res => {
                        if (typeof toastr !== 'undefined') toastr.success(res.message);
                        this.reset();
                        loadStorePortalUsers(storeId);
                    },
                    error: xhr => {
                        const errs = xhr.responseJSON?.errors;
                        const msg = errs ? Object.values(errs).map(a => a[0]).join(' ') : (xhr.responseJSON?.message || 'Unable to create store user.');
                        $('#portalUsersErrors').removeClass('d-none').text(msg);
                    }
                });
            });

            $(document).on('click', '.toggle-portal-user-btn', function() {
                const storeId = $(this).data('store-id');
                const userId = $(this).data('user-id');
                $.ajax({
                    url: `{{ url('admin/stores') }}/${storeId}/users/${userId}/toggle`,
                    method: 'PATCH',
                    success: res => {
                        if (typeof toastr !== 'undefined') toastr.success(res.message);
                        loadStorePortalUsers(storeId);
                    }
                });
            });
        });
    </script>
@endpush
