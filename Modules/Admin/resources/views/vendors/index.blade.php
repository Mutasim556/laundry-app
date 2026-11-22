@extends('admin::layouts.app')

@section('title', 'Vendor Management - Admin Panel')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/select2/dist/css/select2.min.css') }}">
@endpush

@section('content')
<div class="saas-page-shell vendor-management py-3 px-3">
    {{-- Header --}}
    <div class="saas-header-card">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
            <div>
                <h1 class="saas-header-title">Vendor Management</h1>
                <p class="saas-header-subtitle">Manage service partners, suppliers, and international vendors.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.stores.index') }}" class="btn btn-outline-secondary rounded-3 fw-semibold">
                    <i class="mdi mdi-store"></i>
                    <span>Stores</span>
                </a>
                <button type="button" id="btnOpenCreateVendor" class="btn btn-saas-primary">
                    <i class="mdi mdi-plus font-16"></i>
                    <span>Add Vendor</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Summary Cards (1 row on desktop, 2x2 on tablet/mobile) --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-lg-3">
            <div class="saas-stat-card tint-blue">
                <div>
                    <div class="saas-stat-label">Total Vendors</div>
                    <div class="saas-stat-value" id="statTotalVendors">{{ $stats['total'] }}</div>
                </div>
                <span class="saas-stat-icon blue"><i class="mdi mdi-account-multiple"></i></span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="saas-stat-card tint-green">
                <div>
                    <div class="saas-stat-label">Active Vendors</div>
                    <div class="saas-stat-value" id="statActiveVendors">{{ $stats['active'] }}</div>
                </div>
                <span class="saas-stat-icon green"><i class="mdi mdi-check-circle"></i></span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="saas-stat-card tint-amber">
                <div>
                    <div class="saas-stat-label">Inactive Vendors</div>
                    <div class="saas-stat-value" id="statInactiveVendors">{{ $stats['inactive'] }}</div>
                </div>
                <span class="saas-stat-icon amber"><i class="mdi mdi-pause-circle"></i></span>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="saas-stat-card tint-purple">
                <div>
                    <div class="saas-stat-label">Linked Stores</div>
                    <div class="saas-stat-value" id="statLinkedStores">{{ $stats['linked_stores'] }}</div>
                </div>
                <span class="saas-stat-icon purple"><i class="mdi mdi-store"></i></span>
            </div>
        </div>
    </div>

    {{-- Compact Filter Toolbar & Table Card --}}
    <div class="saas-surface-card">
        <div class="saas-filter-bar vendor-management__filters">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-lg">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="mdi mdi-magnify text-muted"></i></span>
                        <input type="text" id="filterVendorSearch" class="form-control border-start-0" aria-label="Search vendors" placeholder="Search vendor, code, contact, email, phone...">
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <select id="filterVendorCountry" class="form-select" aria-label="Filter by country">
                        <option value="">All Countries</option>
                        @foreach($filterCountries as $countryName)
                            <option value="{{ $countryName }}">{{ $countryName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <select id="filterVendorStatus" class="form-select" aria-label="Filter by status">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="suspended">Inactive</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-auto d-grid">
                    <button type="button" id="btnResetVendorFilters" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="mdi mdi-refresh"></i>
                        <span>Reset</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="vendor-table-scroll" tabindex="0" role="region" aria-label="Vendor management table">
            <table id="vendorsTable" class="table saas-table vendor-management-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Vendor Code</th>
                        <th>Vendor / Company</th>
                        <th>Contact Person</th>
                        <th>Country</th>
                        <th>City</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Linked Stores</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create Vendor Modal --}}
<div class="modal fade saas-modal vendor-management__modal" id="createVendorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom bg-white py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="saas-section-badge"><i class="mdi mdi-account"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Add New Vendor</h5>
                        <small class="text-muted">Register an international partner company and commercial terms</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createVendorForm" enctype="multipart/form-data" class="d-flex flex-column flex-grow-1 overflow-hidden" style="min-height: 0;">
                @csrf
                <div class="modal-body p-3" style="background: #f6f8fc; overflow-y: auto;">
                    @include('admin::vendors._form', ['prefix' => 'create'])
                </div>
                <div class="modal-footer bg-white border-top py-2 px-3 flex-shrink-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-saas-primary px-4" id="btnSaveVendor">
                        <i class="mdi mdi-content-save"></i>
                        <span>Save Vendor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Vendor Modal --}}
<div class="modal fade saas-modal vendor-management__modal" id="editVendorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom bg-white py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="saas-section-badge"><i class="mdi mdi-pencil"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Edit Vendor</h5>
                        <small class="text-muted">Update vendor profile, address, and commercial terms safely</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editVendorForm" enctype="multipart/form-data" class="d-flex flex-column flex-grow-1 overflow-hidden" style="min-height: 0;">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_vendor_id" name="id">
                <div class="modal-body p-3" style="background: #f6f8fc; overflow-y: auto;">
                    @include('admin::vendors._form', ['prefix' => 'edit'])
                </div>
                <div class="modal-footer bg-white border-top py-2 px-3 flex-shrink-0">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-saas-primary px-4" id="btnUpdateVendor">
                        <i class="mdi mdi-content-save"></i>
                        <span>Update Vendor</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Vendor Details Modal --}}
<div class="modal fade saas-modal vendor-management__modal" id="viewVendorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom bg-white py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="saas-section-badge"><i class="mdi mdi-domain"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="viewVendorTitle">Vendor Details</h5>
                        <small class="text-muted" id="viewVendorCode"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background: #f6f8fc;" id="viewVendorBody"></div>
            <div class="modal-footer bg-white border-top py-2 px-3 flex-shrink-0">
                <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Close</button>
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

            $('#btnOpenCreateVendor').on('click', function() {
                $('#createVendorErrors').addClass('d-none').empty();
                applyVendorCountryMode('create', $('#create_v_country_code').val() || DEFAULT_COUNTRY_CODE, true);
                showModal('createVendorModal');
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
            function initVendorUkPostcodeSelect($select, $modalParent, $hiddenInput) {
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
                    }
                });
                $select.on('change', function() {
                    $hiddenInput.val($(this).val() || '');
                });
            }

            function initAssignedStoresSelect($select, $modalParent) {
                if (!$select.length || $select.hasClass('select2-hidden-accessible')) {
                    return;
                }
                $select.select2({
                    dropdownParent: $modalParent,
                    placeholder: 'Select stores to assign...',
                    allowClear: true,
                    width: '100%'
                });
            }

            initVendorUkPostcodeSelect($('#create_v_postcode_uk'), $('#createVendorModal'), $('#create_v_postcode_value'));
            initVendorUkPostcodeSelect($('#edit_v_postcode_uk'), $('#editVendorModal'), $('#edit_v_postcode_value'));
            initAssignedStoresSelect($('#create_v_assigned_stores'), $('#createVendorModal'));
            initAssignedStoresSelect($('#edit_v_assigned_stores'), $('#editVendorModal'));

            $('#create_v_postcode_intl').on('input', function() { $('#create_v_postcode_value').val($(this).val()); });
            $('#edit_v_postcode_intl').on('input', function() { $('#edit_v_postcode_value').val($(this).val()); });

            function applyVendorCountryMode(prefix, countryCode, preservePostcode = false) {
                const iso = (countryCode || '').toUpperCase();
                const cfg = ADDRESS_CONFIG[iso] || ADDRESS_CONFIG.DEFAULT;
                $(`#${prefix}_v_state_label`).text(cfg.state_label);
                $(`#${prefix}_v_state_region`).attr('placeholder', cfg.state_placeholder);
                $(`#${prefix}_v_postal_label`).text(cfg.postal_label);
                $(`#${prefix}_v_postcode_intl`).attr('placeholder', cfg.postal_placeholder);

                const isUk = iso === 'GB';
                $(`#${prefix}_v_uk_postcode_wrap`).toggleClass('d-none', !isUk);
                $(`#${prefix}_v_intl_postcode_wrap`).toggleClass('d-none', isUk);

                if (!preservePostcode) {
                    $(`#${prefix}_v_postcode_value`).val('');
                    if (isUk) {
                        $(`#${prefix}_v_postcode_uk`).val(null).trigger('change');
                    } else {
                        $(`#${prefix}_v_postcode_intl`).val('');
                    }
                }
            }

            $('.js-vendor-country-selector').on('change', function() {
                const prefix = $(this).data('prefix');
                const iso = $(this).val();
                const dial = $(this).find(':selected').data('dial');
                applyVendorCountryMode(prefix, iso, false);
                if (prefix === 'create' && dial) {
                    setDialPickerValue($('#create_vendor_primary_dial_picker'), dial, iso);
                }
            });

            $('#createVendorModal').on('show.bs.modal', function() {
                $('#createVendorErrors').addClass('d-none').empty();
                applyVendorCountryMode('create', $('#create_v_country_code').val() || DEFAULT_COUNTRY_CODE, true);
            });

            // DataTable Initialization
            const vendorsTable = $('#vendorsTable').DataTable({
                ajax: '{{ route('admin.stores.vendors.data') }}',
                autoWidth: false,
                scrollX: false,
                pageLength: 15,
                lengthMenu: [10, 15, 25, 50],
                dom: '<"p-3 d-flex flex-wrap justify-content-between align-items-center gap-2"l>rt<"p-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-top"ip>',
                language: {
                    emptyTable: '<div class="py-4 text-center text-muted"><i class="mdi mdi-account d-block font-24 mb-1"></i>No vendors found matching your filters.</div>'
                },
                columns: [
                    {
                        data: 'vendor_code',
                        render: code => `<span class="saas-code-pill">${escapeHtml(code)}</span>`
                    },
                    {
                        data: null,
                        render: (data, type, row) => `
                            <div>
                                <div class="fw-bold text-dark">${escapeHtml(row.name)}</div>
                                <div class="small text-muted">${escapeHtml(row.business_type || 'Partner Vendor')}</div>
                            </div>`
                    },
                    { data: 'owner_name', render: v => escapeHtml(v) },
                    {
                        data: 'country_name',
                        render: v => v ? `<span class="fw-medium">${escapeHtml(v)}</span>` : '<span class="text-muted">—</span>'
                    },
                    {
                        data: 'city',
                        render: v => v ? escapeHtml(v) : '<span class="text-muted">—</span>'
                    },
                    {
                        data: 'email',
                        render: v => v ? escapeHtml(v) : '<span class="text-muted">—</span>'
                    },
                    {
                        data: 'phone_display',
                        render: v => v ? `<span class="text-nowrap">${escapeHtml(v)}</span>` : '<span class="text-muted">—</span>'
                    },
                    {
                        data: 'stores_count',
                        render: count => `<span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">${count} ${count === 1 ? 'Store' : 'Stores'}</span>`
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
                            const toggleTitle = row.status === 'active' ? 'Deactivate Vendor' : 'Activate Vendor';
                            const toggleIcon = row.status === 'active' ? 'mdi-pause' : 'mdi-play';
                            return `
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="saas-action-btn view view-vendor-btn" data-id="${row.id}" title="View Details">
                                        <i class="mdi mdi-eye font-16"></i>
                                    </button>
                                    <button type="button" class="saas-action-btn edit edit-vendor-btn" data-id="${row.id}" title="Edit Vendor">
                                        <i class="mdi mdi-pencil font-16"></i>
                                    </button>
                                    <button type="button" class="saas-action-btn toggle toggle-vendor-status-btn" data-id="${row.id}" title="${toggleTitle}">
                                        <i class="mdi ${toggleIcon} font-16"></i>
                                    </button>
                                    <button type="button" class="saas-action-btn delete delete-vendor-btn" data-id="${row.id}" data-name="${escapeHtml(row.name)}" title="Archive Vendor">
                                        <i class="mdi mdi-archive font-16"></i>
                                    </button>
                                </div>`;
                        }
                    }
                ],
                order: [[0, 'asc']]
            });

            // Filters
            $('#filterVendorSearch').on('input', function() { vendorsTable.search(this.value).draw(); });
            $('#filterVendorCountry').on('change', function() {
                const v = $.fn.dataTable.util.escapeRegex(this.value);
                vendorsTable.column(3).search(v ? `^${v}$` : '', true, false).draw();
            });
            $('#filterVendorStatus').on('change', function() {
                const val = this.value === 'suspended' ? 'Inactive' : this.value;
                const v = $.fn.dataTable.util.escapeRegex(val);
                vendorsTable.column(8).search(v ? `^${v}$` : '', true, false).draw();
            });
            $('#btnResetVendorFilters').on('click', function() {
                $('#filterVendorSearch').val('');
                $('#filterVendorCountry, #filterVendorStatus').val('');
                vendorsTable.search('').columns().search('').draw();
            });

            function refreshVendorStats() {
                const rows = vendorsTable.rows().data().toArray();
                const total = rows.length;
                const active = rows.filter(r => r.status === 'active').length;
                const linked = rows.reduce((sum, r) => sum + (parseInt(r.stores_count || 0, 10)), 0);
                $('#statTotalVendors').text(total);
                $('#statActiveVendors').text(active);
                $('#statInactiveVendors').text(total - active);
                $('#statLinkedStores').text(linked);
            }

            // Create Vendor
            $('#createVendorForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#btnSaveVendor').prop('disabled', true);
                const origHtml = $btn.html();
                $btn.html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

                $.ajax({
                    url: '{{ route('admin.stores.vendors.store') }}',
                    method: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: res => {
                        hideModal('createVendorModal');
                        $('#createVendorForm')[0].reset();
                        $('#create_v_postcode_uk, #create_v_assigned_stores').val(null).trigger('change');
                        $('#create_v_postcode_intl, #create_v_postcode_value').val('');
                        $('#createVendorErrors').addClass('d-none').empty();
                        if (typeof toastr !== 'undefined') toastr.success(res.message);
                        vendorsTable.ajax.reload(refreshVendorStats, false);
                    },
                    error: xhr => {
                        const errs = xhr.responseJSON?.errors;
                        let msg = '<ul class="mb-0 ps-3">';
                        if (errs) {
                            Object.values(errs).forEach(errArr => msg += `<li>${escapeHtml(errArr[0])}</li>`);
                        } else {
                            msg += `<li>${escapeHtml(xhr.responseJSON?.message || 'Error creating vendor')}</li>`;
                        }
                        msg += '</ul>';
                        $('#createVendorErrors').removeClass('d-none').html(msg);
                    },
                    complete: () => $btn.prop('disabled', false).html(origHtml)
                });
            });

            // View Vendor Details
            $(document).on('click', '.view-vendor-btn', function() {
                let $tr = $(this).closest('tr');
                if ($tr.hasClass('child')) {
                    $tr = $tr.prev();
                }
                const v = vendorsTable.row($tr).data();
                if (!v) return;

                const valOrFallback = (val) => (val !== null && val !== undefined && String(val).trim() !== '') ? escapeHtml(val) : '<span class="text-muted">Not provided</span>';
                const statusLabel = v.status === 'suspended' ? 'Inactive' : (v.status || 'Not provided');
                $('#viewVendorTitle').text(v.name || 'Vendor Details');
                $('#viewVendorCode').text(`${v.vendor_code || ''} • ${statusLabel}`);
                const addrParts = [v.address_line_1, v.address_line_2, v.city, v.state_region, v.postcode, v.country_name].filter(Boolean);
                const addr = addrParts.length ? addrParts.join(', ') : (v.business_address || '');
                const storesList = (v.linked_store_names && v.linked_store_names.length) ? v.linked_store_names.join(', ') : 'None';
                const commDisplay = v.commission_type === 'percentage'
                    ? `${Number(v.commission_rate || 0)}%`
                    : `${v.currency_symbol || ''}${Number(v.commission_rate || 0).toFixed(2)}`;

                $('#viewVendorBody').html(`
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="saas-form-section h-100 mb-0">
                                <div class="saas-section-heading"><span class="saas-section-badge"><i class="mdi mdi-account"></i></span><span>Business &amp; Contact</span></div>
                                <p class="small mb-2"><strong>Business Type:</strong> ${valOrFallback(v.business_type)}</p>
                                <p class="small mb-2"><strong>Contact Person:</strong> ${valOrFallback(v.owner_name)}</p>
                                <p class="small mb-2"><strong>Phone:</strong> ${valOrFallback(v.phone_display)}</p>
                                <p class="small mb-2"><strong>Email:</strong> ${valOrFallback(v.email)}</p>
                                <p class="small mb-2"><strong>Reg. No:</strong> ${valOrFallback(v.trade_license_number)}</p>
                                <p class="small mb-0"><strong>Tax / VAT No:</strong> ${valOrFallback(v.tax_vat_number)}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="saas-form-section h-100 mb-0">
                                <div class="saas-section-heading"><span class="saas-section-badge"><i class="mdi mdi-cash"></i></span><span>Address &amp; Commercial</span></div>
                                <p class="small mb-2"><strong>Address:</strong> ${valOrFallback(addr)}</p>
                                <p class="small mb-2"><strong>Commission:</strong> ${escapeHtml(commDisplay)}</p>
                                <p class="small mb-2"><strong>Payment Terms:</strong> ${valOrFallback(v.payment_terms)}</p>
                                <p class="small mb-2"><strong>Payout Method:</strong> ${valOrFallback(v.payout_method)}</p>
                                <p class="small mb-2"><strong>Payout Details:</strong> ${valOrFallback(v.payout_details)}</p>
                                <p class="small mb-2"><strong>Linked Stores:</strong> ${escapeHtml(storesList)}</p>
                                <p class="small mb-2"><strong>Created Date:</strong> ${valOrFallback(v.created_at)}</p>
                                <p class="small mb-0"><strong>Updated Date:</strong> ${valOrFallback(v.updated_at)}</p>
                            </div>
                        </div>
                    </div>
                `);
                showModal('viewVendorModal');
            });

            // Open Edit Vendor Modal
            $(document).on('click', '.edit-vendor-btn', function() {
                let $tr = $(this).closest('tr');
                if ($tr.hasClass('child')) {
                    $tr = $tr.prev();
                }
                const v = vendorsTable.row($tr).data();
                if (!v) return;

                $('#editVendorErrors').addClass('d-none').empty();
                $('#edit_vendor_id').val(v.id);
                $('#edit_v_name').val(v.name);
                $('#edit_v_code').val(v.vendor_code || '');
                $('#edit_v_status').val(v.status);
                $('#edit_v_business_type').val(v.business_type || '');
                $('#edit_v_reg_no').val(v.trade_license_number || '');
                $('#edit_v_tax_vat').val(v.tax_vat_number || '');
                $('#edit_v_description').val(v.description || '');

                $('#edit_v_owner_name').val(v.owner_name);
                $('#edit_v_email').val(v.email || '');
                const countryIso = (v.country_code || '').toUpperCase();
                setDialPickerValue($('#edit_vendor_primary_dial_picker'), v.phone_country_code, countryIso || DEFAULT_COUNTRY_CODE);
                $('#edit_vendor_primary_phone_input').val(v.phone || '');
                setDialPickerValue($('#edit_vendor_alt_dial_picker'), v.alt_phone_country_code, countryIso || DEFAULT_COUNTRY_CODE);
                $('#edit_vendor_alt_phone_input').val(v.alt_phone || '');

                $('#edit_v_country_code').val(countryIso);
                applyVendorCountryMode('edit', countryIso, true);
                $('#edit_v_address_line_1').val(v.address_line_1 || v.business_address || '');
                $('#edit_v_address_line_2').val(v.address_line_2 || '');
                $('#edit_v_city').val(v.city || '');
                $('#edit_v_state_region').val(v.state_region || '');
                $('#edit_v_postcode_value').val(v.postcode || '');

                const $editUk = $('#edit_v_postcode_uk');
                $editUk.find('option[data-dynamic-postcode="true"]').remove();
                if (countryIso === 'GB') {
                    if (v.postcode) {
                        const opt = new Option(v.postcode, v.postcode, true, true);
                        opt.setAttribute('data-dynamic-postcode', 'true');
                        $editUk.append(opt).trigger('change');
                    } else {
                        $editUk.val(null).trigger('change');
                    }
                } else {
                    $('#edit_v_postcode_intl').val(v.postcode || '');
                }

                $('#edit_v_commission_type').val(v.commission_type);
                $('#edit_v_commission_rate').val(v.commission_rate);
                $('#edit_v_payment_terms').val(v.payment_terms || '');
                $('#edit_v_payout_method').val(v.payout_method || '');
                $('#edit_v_payout_details').val(v.payout_details || '');
                $('#edit_v_notes').val(v.verification_notes || '');

                $('.edit-vendor-service-cb').prop('checked', false);
                if (Array.isArray(v.service_ids)) {
                    v.service_ids.forEach(id => $(`#edit_vsrv_${id}`).prop('checked', true));
                }

                if ($('#edit_v_assigned_stores').length) {
                    $('#edit_v_assigned_stores').val(v.assigned_store_ids || []).trigger('change');
                }

                showModal('editVendorModal');
            });

            // Update Vendor
            $('#editVendorForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#edit_vendor_id').val();
                const $btn = $('#btnUpdateVendor').prop('disabled', true);
                const origHtml = $btn.html();
                $btn.html('<span class="spinner-border spinner-border-sm me-1"></span>Updating...');

                $.ajax({
                    url: `{{ url('admin/stores/vendors') }}/${id}`,
                    method: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: res => {
                        hideModal('editVendorModal');
                        $('#editVendorErrors').addClass('d-none').empty();
                        if (typeof toastr !== 'undefined') toastr.success(res.message);
                        vendorsTable.ajax.reload(refreshVendorStats, false);
                    },
                    error: xhr => {
                        const errs = xhr.responseJSON?.errors;
                        let msg = '<ul class="mb-0 ps-3">';
                        if (errs) {
                            Object.values(errs).forEach(errArr => msg += `<li>${escapeHtml(errArr[0])}</li>`);
                        } else {
                            msg += `<li>${escapeHtml(xhr.responseJSON?.message || 'Error updating vendor')}</li>`;
                        }
                        msg += '</ul>';
                        $('#editVendorErrors').removeClass('d-none').html(msg);
                    },
                    complete: () => $btn.prop('disabled', false).html(origHtml)
                });
            });

            // Toggle Vendor Status
            $(document).on('click', '.toggle-vendor-status-btn', function() {
                const id = $(this).data('id');
                $.ajax({
                    url: `{{ url('admin/stores/vendors') }}/${id}/toggle-status`,
                    method: 'PATCH',
                    success: res => {
                        toastr.success(res.message);
                        vendorsTable.ajax.reload(refreshVendorStats, false);
                    },
                    error: () => toastr.error('Failed to update vendor status.')
                });
            });

            // Archive Vendor
            $(document).on('click', '.delete-vendor-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                Swal.fire({
                    title: 'Archive Vendor?',
                    text: `"${name}" will be archived safely. Historical store and booking records will remain intact.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Yes, archive vendor'
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ url('admin/stores/vendors') }}/${id}`,
                            method: 'DELETE',
                            success: res => {
                                toastr.success(res.message);
                                vendorsTable.ajax.reload(refreshVendorStats, false);
                            },
                            error: () => toastr.error('Failed to archive vendor.')
                        });
                    }
                });
            });
        });
    </script>
@endpush
