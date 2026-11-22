@extends('admin::layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.min.css') }}">
    <style>
        .hotel-hero-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            border-radius: 16px;
            padding: 22px 26px;
            color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
        }
        .hotel-stat-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .hotel-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }
        .hotel-stat-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 600;
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
    <div class="hotel-hero-banner mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2 class="mb-1 fw-bold text-white">Hotel Management</h2>
            <p class="mb-0 text-white-50 small">Manage UK hotels available in Step 2 customer hotel search and nearby hotel lookup.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @can('export-hotels', 'admin')
                <a href="{{ route('admin.hotels.export') }}" class="btn btn-outline-light rounded-3 px-3">
                    <i class="mdi mdi-download me-1"></i> Export CSV
                </a>
            @endcan
            @can('import-hotels', 'admin')
                <button type="button" class="btn btn-warning text-dark fw-semibold rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#importHotelCsvModal">
                    <i class="mdi mdi-upload me-1"></i> Import CSV
                </button>
            @endcan
            @can('create-hotels', 'admin')
                <button type="button" class="btn btn-light text-dark fw-semibold rounded-3 px-3" id="btnOpenCreateHotelModal">
                    <i class="mdi mdi-plus-circle-outline me-1"></i> Add Hotel
                </button>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="hotel-stat-card">
                <div class="hotel-stat-label">Total Hotels</div>
                <div class="hotel-stat-value">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="hotel-stat-card">
                <div class="hotel-stat-label">Active Hotels</div>
                <div class="hotel-stat-value text-success">{{ $stats['active'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="hotel-stat-card">
                <div class="hotel-stat-label">Cities Covered</div>
                <div class="hotel-stat-value text-primary">{{ $stats['cities'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="hotel-stat-card">
                <div class="hotel-stat-label">Demo Hotels</div>
                <div class="hotel-stat-value text-warning">{{ $stats['demo'] }}</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="row g-3 mb-3 align-items-end">
                <div class="col-md-4">
                    <label for="filterHotelName" class="form-label small fw-semibold">Search by Hotel Name</label>
                    <input type="text" id="filterHotelName" class="form-control" placeholder="e.g. Grand Royal">
                </div>
                <div class="col-md-4">
                    <label for="filterHotelLocation" class="form-label small fw-semibold">Filter by Postcode or City</label>
                    <input type="text" id="filterHotelLocation" class="form-control" placeholder="e.g. E1 1JG or London">
                </div>
                <div class="col-md-2">
                    <label for="filterHotelStatus" class="form-label small fw-semibold">Status</label>
                    <select id="filterHotelStatus" class="form-select">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btnResetHotelFilters" class="btn btn-light w-100">Reset</button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="hotelsTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>Hotel Name</th>
                            <th>Address</th>
                            <th>Postcode</th>
                            <th>City</th>
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

<!-- Add / Edit Hotel Modal -->
<div class="modal fade" id="hotelFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold" id="hotelFormModalTitle">Add Hotel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="hotelForm">
                @csrf
                <input type="hidden" id="hotel_id" value="">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="hotel_name" class="form-label fw-semibold small">Hotel Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="hotel_name" name="name" placeholder="Grand Royal Hotel" required>
                        </div>
                        <div class="col-md-4">
                            <label for="hotel_postcode" class="form-label fw-semibold small">UK Postcode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase font-monospace" id="hotel_postcode" name="postcode" placeholder="E1 1JG" required>
                        </div>
                        <div class="col-md-6">
                            <label for="hotel_line_1" class="form-label fw-semibold small">Address Line 1 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="hotel_line_1" name="address_line_1" placeholder="100 Whitechapel Road" required>
                        </div>
                        <div class="col-md-6">
                            <label for="hotel_line_2" class="form-label fw-semibold small">Address Line 2</label>
                            <input type="text" class="form-control" id="hotel_line_2" name="address_line_2" placeholder="Whitechapel">
                        </div>
                        <div class="col-md-4">
                            <label for="hotel_city" class="form-label fw-semibold small">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="hotel_city" name="city" placeholder="London" required>
                        </div>
                        <div class="col-md-4">
                            <label for="hotel_county" class="form-label fw-semibold small">County</label>
                            <input type="text" class="form-control" id="hotel_county" name="county" placeholder="Greater London">
                        </div>
                        <div class="col-md-4">
                            <label for="hotel_country" class="form-label fw-semibold small">Country <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="hotel_country" name="country" value="United Kingdom" required>
                        </div>
                        <div class="col-md-4">
                            <label for="hotel_phone" class="form-label fw-semibold small">Contact Number (Optional)</label>
                            <input type="text" class="form-control" id="hotel_phone" name="phone" placeholder="+44 20 7946 0101">
                        </div>
                        <div class="col-md-4">
                            <label for="hotel_latitude" class="form-label fw-semibold small">Latitude (Optional)</label>
                            <input type="number" step="any" class="form-control" id="hotel_latitude" name="latitude" placeholder="51.5182000">
                        </div>
                        <div class="col-md-4">
                            <label for="hotel_longitude" class="form-label fw-semibold small">Longitude (Optional)</label>
                            <input type="number" step="any" class="form-control" id="hotel_longitude" name="longitude" placeholder="-0.0628000">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="hotel_is_active" name="is_active" value="1" checked>
                                <label class="form-check-label fw-semibold" for="hotel_is_active">Active (Visible in customer hotel search)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="hotel_is_demo" name="is_demo" value="1">
                                <label class="form-check-label fw-semibold" for="hotel_is_demo">Demo Record</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 fw-semibold">Save Hotel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CSV Import Modal -->
<div class="modal fade" id="importHotelCsvModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold">Import Hotels from CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="hotelCsvStepUpload">
                    <form id="hotelCsvPreviewForm" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="hotel_csv_file" class="form-label fw-semibold">Select CSV File (Max 10MB)</label>
                            <input type="file" class="form-control" id="hotel_csv_file" name="csv_file" accept=".csv,.txt" required>
                            <div class="form-text">Required fields: <strong>name</strong>, <strong>address_line_1</strong>, <strong>city</strong>, <strong>postcode</strong>.</div>
                        </div>
                        <button type="submit" class="btn btn-primary fw-semibold px-4">Upload &amp; Preview</button>
                    </form>
                </div>

                <div id="hotelCsvStepMapping" class="d-none">
                    <input type="hidden" id="hotel_csv_token">
                    <h6 class="fw-bold mb-2">1. Sample Rows Preview</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered small" id="hotelCsvSampleTable">
                            <thead class="table-light"></thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <h6 class="fw-bold mb-2">2. Map CSV Columns</h6>
                    <div class="row g-2 mb-3" id="hotelCsvMappingFields"></div>

                    <div class="row g-3 mb-3 bg-light p-3 rounded-3">
                        <div class="col-md-6">
                            <label for="hotel_csv_strategy" class="form-label fw-semibold small">Duplicate Strategy</label>
                            <select id="hotel_csv_strategy" class="form-select">
                                <option value="skip">Skip duplicates</option>
                                <option value="update">Update existing duplicates</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="hotel_csv_demo">
                                <label class="form-check-label fw-semibold" for="hotel_csv_demo">Mark as Demo records</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-light" id="btnHotelCsvBack">Back</button>
                        <button type="button" class="btn btn-success fw-semibold px-4" id="btnProcessHotelCsv">Import Hotels</button>
                    </div>
                </div>

                <div id="hotelCsvStepReport" class="d-none">
                    <div class="alert alert-info mb-3" id="hotelCsvReportSummary"></div>
                    <div id="hotelCsvErrorWrap" class="d-none">
                        <h6 class="fw-bold text-danger">Row Errors</h6>
                        <div class="table-responsive" style="max-height: 240px;">
                            <table class="table table-sm table-bordered small" id="hotelCsvErrorsTable">
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

            const table = $('#hotelsTable').DataTable({
                ajax: {
                    url: "{{ route('admin.hotels.data') }}",
                    data: function (d) {
                        d.name = $('#filterHotelName').val().trim();
                        d.postcode_or_city = $('#filterHotelLocation').val().trim();
                        d.status = $('#filterHotelStatus').val();
                    },
                    dataSrc: 'data'
                },
                columns: [
                    { data: null, render: (d, t, r, m) => `<span class="text-muted">${m.row + 1}</span>` },
                    {
                        data: null,
                        render: (d, t, r) => `<div class="fw-bold text-dark">${r.name}</div>${r.is_demo ? '<span class="badge bg-warning text-dark">DEMO</span>' : ''}`
                    },
                    { data: 'formatted_address', render: (v) => `<span class="small">${v}</span>` },
                    { data: 'postcode', render: (v) => `<span class="badge bg-dark font-monospace">${v}</span>` },
                    { data: 'city' },
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
                            @can('edit-hotels', 'admin')
                            html += `<button type="button" class="action-btn action-btn-edit js-edit-hotel" data-row='${JSON.stringify(r).replace(/'/g, "&#39;")}' title="Edit"><i class="mdi mdi-pencil"></i></button>`;
                            html += `<button type="button" class="action-btn action-btn-toggle js-toggle-hotel" data-id="${r.id}" title="Toggle Active"><i class="mdi ${r.is_active ? 'mdi-pause' : 'mdi-play'}"></i></button>`;
                            @endcan
                            @can('delete-hotels', 'admin')
                            html += `<button type="button" class="action-btn action-btn-delete js-delete-hotel" data-id="${r.id}" title="Delete"><i class="mdi mdi-delete"></i></button>`;
                            @endcan
                            html += '</div>';
                            return html;
                        }
                    }
                ]
            });

            let timer = null;
            $('#filterHotelName, #filterHotelLocation').on('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => table.ajax.reload(), 250);
            });
            $('#filterHotelStatus').on('change', () => table.ajax.reload());
            $('#btnResetHotelFilters').on('click', () => {
                $('#filterHotelName, #filterHotelLocation, #filterHotelStatus').val('');
                table.ajax.reload();
            });

            const formatUkPostcode = (raw) => {
                const clean = String(raw || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
                if (clean.length >= 5 && clean.length <= 7 && /\d[A-Z]{2}$/.test(clean)) {
                    return clean.slice(0, -3) + ' ' + clean.slice(-3);
                }
                return String(raw || '').toUpperCase().trim();
            };
            $('#hotel_postcode').on('blur', function () {
                $(this).val(formatUkPostcode($(this).val()));
            });

            $('#btnOpenCreateHotelModal').on('click', () => {
                $('#hotelForm')[0].reset();
                $('#hotel_id').val('');
                $('#hotel_country').val('United Kingdom');
                $('#hotel_is_active').prop('checked', true);
                $('#hotelFormModalTitle').text('Add Hotel');
                $('#hotelFormModal').modal('show');
            });

            $(document).on('click', '.js-edit-hotel', function () {
                const r = $(this).data('row');
                $('#hotel_id').val(r.id);
                $('#hotel_name').val(r.name);
                $('#hotel_postcode').val(r.postcode);
                $('#hotel_line_1').val(r.address_line_1);
                $('#hotel_line_2').val(r.address_line_2);
                $('#hotel_city').val(r.city);
                $('#hotel_county').val(r.county);
                $('#hotel_country').val(r.country || 'United Kingdom');
                $('#hotel_phone').val(r.phone);
                $('#hotel_latitude').val(r.latitude ?? '');
                $('#hotel_longitude').val(r.longitude ?? '');
                $('#hotel_is_active').prop('checked', !!r.is_active);
                $('#hotel_is_demo').prop('checked', !!r.is_demo);
                $('#hotelFormModalTitle').text('Edit Hotel');
                $('#hotelFormModal').modal('show');
            });

            $('#hotelForm').on('submit', function (e) {
                e.preventDefault();
                const id = $('#hotel_id').val();
                const url = id
                    ? "{{ route('admin.hotels.update', ':id') }}".replace(':id', id)
                    : "{{ route('admin.hotels.store') }}";
                const method = id ? 'PUT' : 'POST';

                $.ajax({
                    url, method,
                    data: $(this).serialize(),
                    success: (res) => {
                        $('#hotelFormModal').modal('hide');
                        toastr.success(res.message);
                        table.ajax.reload(null, false);
                    },
                    error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Validation error.')
                });
            });

            $(document).on('click', '.js-toggle-hotel', function () {
                const id = $(this).data('id');
                $.ajax({
                    url: "{{ route('admin.hotels.toggle-status', ':id') }}".replace(':id', id),
                    method: 'PATCH',
                    success: (res) => {
                        toastr.success(res.message);
                        table.ajax.reload(null, false);
                    }
                });
            });

            $(document).on('click', '.js-delete-hotel', function () {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Hotel?',
                    text: 'Historical bookings preserve their own hotel snapshot.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete'
                }).then((res) => {
                    if (!res.isConfirmed) return;
                    $.ajax({
                        url: "{{ route('admin.hotels.destroy', ':id') }}".replace(':id', id),
                        method: 'DELETE',
                        success: (r) => {
                            toastr.success(r.message);
                            table.ajax.reload(null, false);
                        }
                    });
                });
            });

            const hotelFields = [
                { key: 'name', label: 'Hotel Name *' },
                { key: 'address_line_1', label: 'Address Line 1 *' },
                { key: 'city', label: 'City *' },
                { key: 'postcode', label: 'Postcode *' },
                { key: 'address_line_2', label: 'Address Line 2' },
                { key: 'county', label: 'County' },
                { key: 'country', label: 'Country' },
                { key: 'latitude', label: 'Latitude' },
                { key: 'longitude', label: 'Longitude' },
                { key: 'phone', label: 'Phone' },
                { key: 'is_active', label: 'Is Active' },
            ];

            $('#hotelCsvPreviewForm').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('admin.hotels.import.preview') }}",
                    method: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: (res) => {
                        $('#hotel_csv_token').val(res.import_token);
                        $('#hotelCsvSampleTable thead').html('<tr>' + res.headers.map(h => `<th>${h}</th>`).join('') + '</tr>');
                        $('#hotelCsvSampleTable tbody').html(res.sample_rows.map(r => '<tr>' + r.map(c => `<td>${c ?? ''}</td>`).join('') + '</tr>').join(''));

                        const opts = (sel) => '<option value="">-- Ignore --</option>' + res.headers.map((h, i) => `<option value="${i}" ${sel === i ? 'selected' : ''}>${h}</option>`).join('');
                        $('#hotelCsvMappingFields').html(hotelFields.map(f => `
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">${f.label}</label>
                                <select class="form-select form-select-sm js-hotel-csv-map" data-field="${f.key}">${opts(res.suggested_mapping[f.key])}</select>
                            </div>
                        `).join(''));

                        $('#hotelCsvStepUpload, #hotelCsvStepReport').addClass('d-none');
                        $('#hotelCsvStepMapping').removeClass('d-none');
                    },
                    error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed to parse CSV.')
                });
            });

            $('#btnHotelCsvBack').on('click', () => {
                $('#hotelCsvStepMapping, #hotelCsvStepReport').addClass('d-none');
                $('#hotelCsvStepUpload').removeClass('d-none');
            });

            $('#btnProcessHotelCsv').on('click', () => {
                const mapping = {};
                $('.js-hotel-csv-map').each(function () {
                    const v = $(this).val();
                    if (v !== '') mapping[$(this).data('field')] = parseInt(v, 10);
                });

                $.ajax({
                    url: "{{ route('admin.hotels.import.process') }}",
                    method: 'POST',
                    data: {
                        import_token: $('#hotel_csv_token').val(),
                        duplicate_strategy: $('#hotel_csv_strategy').val(),
                        mark_as_demo: $('#hotel_csv_demo').is(':checked') ? 1 : 0,
                        mapping,
                    },
                    success: (res) => {
                        $('#hotelCsvStepMapping').addClass('d-none');
                        $('#hotelCsvStepReport').removeClass('d-none');
                        $('#hotelCsvReportSummary').text(res.message);

                        if (res.summary.errors && res.summary.errors.length > 0) {
                            $('#hotelCsvErrorWrap').removeClass('d-none');
                            $('#hotelCsvErrorsTable tbody').html(res.summary.errors.map(e => `<tr><td>${e.row}</td><td>${e.error}</td></tr>`).join(''));
                        } else {
                            $('#hotelCsvErrorWrap').addClass('d-none');
                        }
                        table.ajax.reload(null, false);
                    },
                    error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Import failed.')
                });
            });
        });
    </script>
@endpush
