@extends('admin::layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.min.css') }}">
    <style>
        .uk-hero-banner {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-radius: 16px;
            padding: 22px 26px;
            color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
        }
        .uk-stat-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .uk-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }
        .uk-stat-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 600;
        }
        .postcode-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .postcode-pill:hover {
            border-color: #4f46e5;
            background: #eef2ff;
        }
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }
        .action-btn-edit { background: #eef2ff; color: #4f46e5; }
        .action-btn-edit:hover { background: #4f46e5; color: #fff; }
        .action-btn-toggle { background: #fef3c7; color: #d97706; }
        .action-btn-toggle:hover { background: #d97706; color: #fff; }
        .action-btn-delete { background: #fef2f2; color: #dc2626; }
        .action-btn-delete:hover { background: #dc2626; color: #fff; }
    </style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <!-- Hero Header -->
    <div class="uk-hero-banner mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2 class="mb-1 fw-bold text-white">UK Address Management</h2>
            <p class="mb-0 text-white-50 small">Manage database-driven UK postcode addresses, CSV imports, and active lookup availability.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @can('export-uk-addresses', 'admin')
                <a href="{{ route('admin.uk-addresses.export') }}" id="btnExportCsv" class="btn btn-outline-light rounded-3 px-3">
                    <i class="mdi mdi-download me-1"></i> Export CSV
                </a>
            @endcan
            @can('import-uk-addresses', 'admin')
                <button type="button" class="btn btn-warning text-dark fw-semibold rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                    <i class="mdi mdi-upload me-1"></i> Import CSV
                </button>
            @endcan
            @can('create-uk-addresses', 'admin')
                <button type="button" class="btn btn-light text-dark fw-semibold rounded-3 px-3" id="btnOpenCreateModal">
                    <i class="mdi mdi-plus-circle-outline me-1"></i> Add Address
                </button>
            @endcan
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="uk-stat-card">
                <div class="uk-stat-label">Total Addresses</div>
                <div class="uk-stat-value" id="statTotal">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="uk-stat-card">
                <div class="uk-stat-label">Active Addresses</div>
                <div class="uk-stat-value text-success" id="statActive">{{ $stats['active'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="uk-stat-card">
                <div class="uk-stat-label">Unique Postcodes</div>
                <div class="uk-stat-value text-primary" id="statPostcodes">{{ $stats['postcodes'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="uk-stat-card">
                <div class="uk-stat-label">Demo Records</div>
                <div class="uk-stat-value text-warning" id="statDemo">{{ $stats['demo'] }}</div>
            </div>
        </div>
    </div>

    <!-- Postcode Counts Overview -->
    @if ($postcodeCounts->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body py-3 px-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-semibold small text-uppercase text-muted">Addresses per Postcode (Click to filter)</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($postcodeCounts as $pc)
                        <span class="postcode-pill js-filter-postcode" data-postcode="{{ $pc->postcode }}">
                            <span class="font-monospace">{{ $pc->postcode }}</span>
                            <span class="badge bg-primary rounded-pill">{{ $pc->total_count }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Filters + DataTable -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="row g-3 mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="filterPostcode" class="form-label small fw-semibold">Filter by Postcode</label>
                    <input type="text" id="filterPostcode" class="form-control text-uppercase" placeholder="e.g. E1 0BJ">
                </div>
                <div class="col-md-4">
                    <label for="filterSearchText" class="form-label small fw-semibold">Search Street, Building or City</label>
                    <input type="text" id="filterSearchText" class="form-control" placeholder="e.g. Crowder Street, London">
                </div>
                <div class="col-md-3">
                    <label for="filterStatus" class="form-label small fw-semibold">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="button" id="btnResetFilters" class="btn btn-light w-100">Reset</button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="ukAddressesTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>Postcode</th>
                            <th>Address</th>
                            <th>City / County</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Address Modal -->
<div class="modal fade" id="addressFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold" id="addressFormModalTitle">Add UK Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="ukAddressForm">
                @csrf
                <input type="hidden" id="addr_id" value="">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="addr_postcode" class="form-label fw-semibold small">Postcode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase font-monospace" id="addr_postcode" name="postcode" placeholder="E1 0BJ" required>
                        </div>
                        <div class="col-md-8">
                            <label for="addr_line_1" class="form-label fw-semibold small">Address Line 1 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addr_line_1" name="address_line_1" placeholder="Flat 1, Brockmer House" required>
                        </div>
                        <div class="col-md-6">
                            <label for="addr_line_2" class="form-label fw-semibold small">Address Line 2</label>
                            <input type="text" class="form-control" id="addr_line_2" name="address_line_2" placeholder="Crowder Street">
                        </div>
                        <div class="col-md-6">
                            <label for="addr_street" class="form-label fw-semibold small">Street</label>
                            <input type="text" class="form-control" id="addr_street" name="street" placeholder="Crowder Street">
                        </div>
                        <div class="col-md-6">
                            <label for="addr_building_name" class="form-label fw-semibold small">Building Name</label>
                            <input type="text" class="form-control" id="addr_building_name" name="building_name" placeholder="Brockmer House">
                        </div>
                        <div class="col-md-6">
                            <label for="addr_building_number" class="form-label fw-semibold small">Building / Flat Number</label>
                            <input type="text" class="form-control" id="addr_building_number" name="building_number" placeholder="Flat 1">
                        </div>
                        <div class="col-md-4">
                            <label for="addr_city" class="form-label fw-semibold small">City / Town <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addr_city" name="city" placeholder="London" required>
                        </div>
                        <div class="col-md-4">
                            <label for="addr_county" class="form-label fw-semibold small">County</label>
                            <input type="text" class="form-control" id="addr_county" name="county" placeholder="Greater London">
                        </div>
                        <div class="col-md-4">
                            <label for="addr_country" class="form-label fw-semibold small">Country <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="addr_country" name="country" value="United Kingdom" required>
                        </div>
                        <div class="col-md-6">
                            <label for="addr_latitude" class="form-label fw-semibold small">Latitude (Optional)</label>
                            <input type="number" step="any" class="form-control" id="addr_latitude" name="latitude" placeholder="51.5112000">
                        </div>
                        <div class="col-md-6">
                            <label for="addr_longitude" class="form-label fw-semibold small">Longitude (Optional)</label>
                            <input type="number" step="any" class="form-control" id="addr_longitude" name="longitude" placeholder="-0.0561000">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="addr_is_active" name="is_active" value="1" checked>
                                <label class="form-check-label fw-semibold" for="addr_is_active">Active (Visible in customer postcode lookup)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="addr_is_demo" name="is_demo" value="1">
                                <label class="form-check-label fw-semibold" for="addr_is_demo">Demo Record</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold" id="btnSaveAddress">Save Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CSV Import Modal -->
<div class="modal fade" id="importCsvModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold">Import UK Addresses from CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Step 1: Upload CSV -->
                <div id="csvStepUpload">
                    <form id="csvPreviewForm" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="csv_file_input" class="form-label fw-semibold">Select CSV File (Max 10MB)</label>
                            <input type="file" class="form-control" id="csv_file_input" name="csv_file" accept=".csv,.txt" required>
                            <div class="form-text">Required fields after mapping: <strong>postcode</strong>, <strong>address_line_1</strong>, <strong>city</strong>.</div>
                        </div>
                        <button type="submit" class="btn btn-primary fw-semibold px-4" id="btnPreviewCsv">Upload & Preview</button>
                    </form>
                </div>

                <!-- Step 2: Map Columns & Preview -->
                <div id="csvStepMapping" class="d-none">
                    <input type="hidden" id="csv_import_token">
                    <h6 class="fw-bold mb-2">1. Sample Rows Preview (First 5 Rows)</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered small" id="csvSampleTable">
                            <thead class="table-light"></thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <h6 class="fw-bold mb-2">2. Map CSV Columns to Database Fields</h6>
                    <div class="row g-2 mb-3" id="csvMappingFields"></div>

                    <div class="row g-3 mb-3 bg-light p-3 rounded-3">
                        <div class="col-md-6">
                            <label for="csv_duplicate_strategy" class="form-label fw-semibold small">Duplicate Handling Strategy</label>
                            <select id="csv_duplicate_strategy" class="form-select">
                                <option value="skip">Skip duplicate addresses</option>
                                <option value="update">Update existing duplicate addresses</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="csv_mark_as_demo">
                                <label class="form-check-label fw-semibold" for="csv_mark_as_demo">Mark imported rows as Demo records (`is_demo = 1`)</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-light" id="btnCsvBack">Upload Different File</button>
                        <button type="button" class="btn btn-success fw-semibold px-4" id="btnProcessCsvImport">Start Batch Import</button>
                    </div>
                </div>

                <!-- Step 3: Import Report -->
                <div id="csvStepReport" class="d-none">
                    <div class="alert alert-info mb-3" id="csvReportSummary"></div>
                    <div id="csvErrorReportWrap" class="d-none">
                        <h6 class="fw-bold text-danger">Row Error Report (up to 100 rows shown)</h6>
                        <div class="table-responsive" style="max-height: 240px;">
                            <table class="table table-sm table-bordered small" id="csvErrorsTable">
                                <thead><tr><th>Row #</th><th>Error</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
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
    <script src="{{ asset('admin/dist/libs/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

            const table = $('#ukAddressesTable').DataTable({
                ajax: {
                    url: "{{ route('admin.uk-addresses.data') }}",
                    data: function (d) {
                        d.postcode = $('#filterPostcode').val().trim();
                        d.search_text = $('#filterSearchText').val().trim();
                        d.status = $('#filterStatus').val();
                    },
                    dataSrc: 'data'
                },
                columns: [
                    { data: null, render: (d, t, r, m) => `<span class="text-muted">${m.row + 1}</span>` },
                    {
                        data: null,
                        render: (d, t, r) => `<span class="badge bg-dark font-monospace">${r.postcode}</span> <span class="badge bg-light text-dark border">${r.postcode_count}</span>`
                    },
                    {
                        data: null,
                        render: (d, t, r) => `<div class="fw-semibold text-dark">${r.full_address}</div><small class="text-muted">${r.street || ''}</small>`
                    },
                    {
                        data: null,
                        render: (d, t, r) => `<div>${r.city}</div><small class="text-muted">${r.county || ''}</small>`
                    },
                    {
                        data: null,
                        render: (d, t, r) => r.is_demo
                            ? `<span class="badge bg-warning text-dark">DEMO (${r.source})</span>`
                            : `<span class="badge bg-info-subtle text-info border">${r.source}</span>`
                    },
                    {
                        data: 'is_active',
                        render: (v) => v
                            ? `<span class="badge bg-success-subtle text-success border">Active</span>`
                            : `<span class="badge bg-secondary-subtle text-secondary border">Inactive</span>`
                    },
                    {
                        data: null,
                        orderable: false,
                        className: 'text-end',
                        render: (d, t, r) => {
                            let html = '<div class="d-inline-flex gap-1">';
                            @can('edit-uk-addresses', 'admin')
                            html += `<button type="button" class="action-btn action-btn-edit js-edit-addr" data-row='${JSON.stringify(r).replace(/'/g, "&#39;")}' title="Edit"><i class="mdi mdi-pencil"></i></button>`;
                            html += `<button type="button" class="action-btn action-btn-toggle js-toggle-addr" data-id="${r.id}" title="Toggle Active"><i class="mdi ${r.is_active ? 'mdi-pause' : 'mdi-play'}"></i></button>`;
                            @endcan
                            @can('delete-uk-addresses', 'admin')
                            html += `<button type="button" class="action-btn action-btn-delete js-delete-addr" data-id="${r.id}" title="Delete"><i class="mdi mdi-delete"></i></button>`;
                            @endcan
                            html += '</div>';
                            return html;
                        }
                    }
                ]
            });

            let filterTimer = null;
            $('#filterPostcode, #filterSearchText').on('input', function () {
                clearTimeout(filterTimer);
                filterTimer = setTimeout(() => table.ajax.reload(), 250);
            });
            $('#filterStatus').on('change', () => table.ajax.reload());
            $('#btnResetFilters').on('click', () => {
                $('#filterPostcode, #filterSearchText, #filterStatus').val('');
                table.ajax.reload();
            });
            $('.js-filter-postcode').on('click', function () {
                $('#filterPostcode').val($(this).data('postcode'));
                table.ajax.reload();
            });

            // Auto-format UK postcode on blur
            const formatUkPostcode = (raw) => {
                const clean = String(raw || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
                if (clean.length >= 5 && clean.length <= 7 && /\d[A-Z]{2}$/.test(clean)) {
                    return clean.slice(0, -3) + ' ' + clean.slice(-3);
                }
                return String(raw || '').toUpperCase().trim();
            };
            $('#addr_postcode').on('blur', function () {
                $(this).val(formatUkPostcode($(this).val()));
            });

            $('#btnOpenCreateModal').on('click', function () {
                $('#ukAddressForm')[0].reset();
                $('#addr_id').val('');
                $('#addr_country').val('United Kingdom');
                $('#addr_is_active').prop('checked', true);
                $('#addressFormModalTitle').text('Add UK Address');
                $('#addressFormModal').modal('show');
            });

            $(document).on('click', '.js-edit-addr', function () {
                const r = $(this).data('row');
                $('#addr_id').val(r.id);
                $('#addr_postcode').val(r.postcode);
                $('#addr_line_1').val(r.address_line_1);
                $('#addr_line_2').val(r.address_line_2);
                $('#addr_building_name').val(r.building_name);
                $('#addr_building_number').val(r.building_number);
                $('#addr_street').val(r.street);
                $('#addr_city').val(r.city);
                $('#addr_county').val(r.county);
                $('#addr_country').val(r.country || 'United Kingdom');
                $('#addr_latitude').val(r.latitude ?? '');
                $('#addr_longitude').val(r.longitude ?? '');
                $('#addr_is_active').prop('checked', !!r.is_active);
                $('#addr_is_demo').prop('checked', !!r.is_demo);
                $('#addressFormModalTitle').text('Edit UK Address');
                $('#addressFormModal').modal('show');
            });

            $('#ukAddressForm').on('submit', function (e) {
                e.preventDefault();
                const id = $('#addr_id').val();
                const url = id
                    ? "{{ route('admin.uk-addresses.update', ':id') }}".replace(':id', id)
                    : "{{ route('admin.uk-addresses.store') }}";
                const method = id ? 'PUT' : 'POST';

                $.ajax({
                    url, method,
                    data: $(this).serialize(),
                    success: function (res) {
                        $('#addressFormModal').modal('hide');
                        toastr.success(res.message);
                        table.ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Validation error.';
                        toastr.error(msg);
                    }
                });
            });

            $(document).on('click', '.js-toggle-addr', function () {
                const id = $(this).data('id');
                $.ajax({
                    url: "{{ route('admin.uk-addresses.toggle-status', ':id') }}".replace(':id', id),
                    method: 'PATCH',
                    success: (res) => {
                        toastr.success(res.message);
                        table.ajax.reload(null, false);
                    }
                });
            });

            $(document).on('click', '.js-delete-addr', function () {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Address?',
                    text: 'Existing bookings keep their own address snapshot and will not be affected.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete'
                }).then((res) => {
                    if (!res.isConfirmed) return;
                    $.ajax({
                        url: "{{ route('admin.uk-addresses.destroy', ':id') }}".replace(':id', id),
                        method: 'DELETE',
                        success: (r) => {
                            toastr.success(r.message);
                            table.ajax.reload(null, false);
                        }
                    });
                });
            });

            // CSV Import Wizard
            const dbFields = [
                { key: 'postcode', label: 'Postcode *', required: true },
                { key: 'address_line_1', label: 'Address Line 1 *', required: true },
                { key: 'city', label: 'City *', required: true },
                { key: 'address_line_2', label: 'Address Line 2' },
                { key: 'building_name', label: 'Building Name' },
                { key: 'building_number', label: 'Building Number' },
                { key: 'street', label: 'Street' },
                { key: 'county', label: 'County' },
                { key: 'country', label: 'Country' },
                { key: 'latitude', label: 'Latitude' },
                { key: 'longitude', label: 'Longitude' },
                { key: 'is_active', label: 'Is Active' },
            ];

            $('#csvPreviewForm').on('submit', function (e) {
                e.preventDefault();
                const fd = new FormData(this);
                $.ajax({
                    url: "{{ route('admin.uk-addresses.import.preview') }}",
                    method: 'POST',
                    data: fd,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        $('#csv_import_token').val(res.import_token);
                        $('#csvSampleTable thead').html('<tr>' + res.headers.map(h => `<th>${h}</th>`).join('') + '</tr>');
                        $('#csvSampleTable tbody').html(res.sample_rows.map(r => '<tr>' + r.map(c => `<td>${c ?? ''}</td>`).join('') + '</tr>').join(''));

                        const optionsHtml = (selectedIdx) => {
                            let opts = '<option value="">-- Ignore --</option>';
                            res.headers.forEach((h, idx) => {
                                opts += `<option value="${idx}" ${selectedIdx === idx ? 'selected' : ''}>${h}</option>`;
                            });
                            return opts;
                        };

                        $('#csvMappingFields').html(dbFields.map(f => `
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">${f.label}</label>
                                <select class="form-select form-select-sm js-csv-map" data-field="${f.key}">
                                    ${optionsHtml(res.suggested_mapping[f.key])}
                                </select>
                            </div>
                        `).join(''));

                        $('#csvStepUpload, #csvStepReport').addClass('d-none');
                        $('#csvStepMapping').removeClass('d-none');
                    },
                    error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed to parse CSV.')
                });
            });

            $('#btnCsvBack').on('click', () => {
                $('#csvStepMapping, #csvStepReport').addClass('d-none');
                $('#csvStepUpload').removeClass('d-none');
            });

            $('#btnProcessCsvImport').on('click', function () {
                const mapping = {};
                $('.js-csv-map').each(function () {
                    const val = $(this).val();
                    if (val !== '') mapping[$(this).data('field')] = parseInt(val, 10);
                });

                $.ajax({
                    url: "{{ route('admin.uk-addresses.import.process') }}",
                    method: 'POST',
                    data: {
                        import_token: $('#csv_import_token').val(),
                        duplicate_strategy: $('#csv_duplicate_strategy').val(),
                        mark_as_demo: $('#csv_mark_as_demo').is(':checked') ? 1 : 0,
                        mapping: mapping,
                    },
                    success: function (res) {
                        $('#csvStepMapping').addClass('d-none');
                        $('#csvStepReport').removeClass('d-none');
                        $('#csvReportSummary').text(res.message);

                        if (res.summary.errors && res.summary.errors.length > 0) {
                            $('#csvErrorReportWrap').removeClass('d-none');
                            $('#csvErrorsTable tbody').html(res.summary.errors.map(err => `<tr><td>${err.row}</td><td>${err.error}</td></tr>`).join(''));
                        } else {
                            $('#csvErrorReportWrap').addClass('d-none');
                        }
                        table.ajax.reload(null, false);
                    },
                    error: (xhr) => toastr.error(xhr.responseJSON?.message || 'CSV import failed.')
                });
            });
        });
    </script>
@endpush
