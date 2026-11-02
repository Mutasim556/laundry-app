@extends('admin::layouts.app')

@push('styles')
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/toastr/toastr.min.css') }}">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.min.css') }}">
    <style>
        /* Modern Header Banner with Vibrant Gradient */
        .units-hero-banner {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%);
            border-radius: 16px;
            padding: 24px 28px;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.25), 0 8px 10px -6px rgba(79, 70, 229, 0.2);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .units-hero-banner::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .units-hero-title {
            color: #ffffff !important;
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }
        .units-breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        .units-breadcrumb .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.82) !important;
            font-weight: 500;
            transition: color 0.15s ease;
        }
        .units-breadcrumb .breadcrumb-item a:hover {
            color: #ffffff !important;
            text-decoration: underline;
        }
        .units-breadcrumb .breadcrumb-item.active {
            color: #ffffff !important;
            font-weight: 600;
        }
        .units-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
            color: rgba(255, 255, 255, 0.6);
        }

        /* Hero Add Button */
        .btn-hero-add {
            background: #ffffff;
            color: #4f46e5;
            font-weight: 600;
            border: none;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-hero-add:hover, .btn-hero-add:focus {
            background: #f8fafc;
            color: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.18);
        }

        /* High Visibility Typography & Cards */
        .card-units {
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
            background: #ffffff;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        #unitsTable {
            font-size: 0.95rem;
            color: #0f172a;
        }
        #unitsTable thead th {
            font-size: 0.88rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #0f172a !important;
            background-color: #f1f5f9 !important;
            border-bottom: 2px solid #cbd5e1;
            padding: 16px 18px;
            white-space: nowrap;
        }
        #unitsTable tbody td {
            padding: 16px 18px;
            vertical-align: middle;
            color: #1e293b;
            border-bottom: 1px solid #e2e8f0;
        }
        #unitsTable tbody tr:hover {
            background-color: #f8fafc;
        }
        .badge-unit {
            background: linear-gradient(135deg, #e0e7ff 0%, #ede9fe 100%);
            color: #4338ca;
            font-weight: 700;
            border: 1px solid #c7d2fe;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.88rem;
        }
        .badge-status-active {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.8rem;
        }
        .badge-status-inactive {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.8rem;
        }
        .action-btn {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid transparent;
            font-weight: bold;
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
        }
        .action-btn-edit {
            background: #fef3c7;
            color: #b45309;
            border-color: #fde68a;
        }
        .action-btn-edit:hover {
            background: #f59e0b;
            color: #ffffff;
        }
        .action-btn-toggle {
            background: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd;
        }
        .action-btn-toggle:hover {
            background: #0284c7;
            color: #ffffff;
        }
        .action-btn-delete {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fecaca;
        }
        .action-btn-delete:hover {
            background: #dc2626;
            color: #ffffff;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header Hero Banner -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="units-hero-banner d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h3 class="units-hero-title">Service Pricing Units</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb units-breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Services / Units</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    @can('create-units', 'admin')
                    <button type="button" class="btn btn-hero-add rounded-pill px-4 py-2 d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#createUnitModal">
                        <i data-feather="plus-circle" class="feather-sm me-2"></i> Add New Unit
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Units DataTable Card -->
    <div class="row">
        <div class="col-12">
            <div class="card card-units">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
                        <div>
                            <h4 class="fw-bold text-dark mb-1">Pricing Units for Laundry Services</h4>
                            <p class="text-muted small mb-0">Manage service charge units (e.g. <strong>Per Kg</strong>, <strong>Per Unit</strong>, <strong>Per Piece</strong>, <strong>Per Pair</strong>, etc.)</p>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="unitsTable" class="table table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Unit Name</th>
                                    <th>Short Symbol</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loaded via AJAX DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Unit Modal -->
<div class="modal fade" id="createUnitModal" tabindex="-1" aria-labelledby="createUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-light border-0 py-3 px-4">
                <h5 class="modal-title fw-bold text-dark" id="createUnitModalLabel">
                    <i data-feather="plus-circle" class="feather-sm text-primary me-2"></i> Add Service Pricing Unit
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createUnitForm">
                @csrf
                <div class="modal-body p-4">
                    <div id="createUnitAlert" class="alert alert-danger d-none" role="alert"></div>

                    <!-- Preset Quick Fill Pills -->
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Quick Suggestions:</label>
                        <div class="d-flex flex-wrap gap-1.5">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill py-0.5 px-2.5 small" onclick="quickFillUnit('create', 'Per Kg', 'kg', 'Charged per kilogram of laundry')">Per Kg</button>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill py-0.5 px-2.5 small" onclick="quickFillUnit('create', 'Per Unit', 'unit', 'Charged per individual garment/unit')">Per Unit</button>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill py-0.5 px-2.5 small" onclick="quickFillUnit('create', 'Per Piece', 'pc', 'Charged per single piece')">Per Piece</button>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill py-0.5 px-2.5 small" onclick="quickFillUnit('create', 'Per Pair', 'pair', 'Charged per pair (shoes, gloves, socks)')">Per Pair</button>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill py-0.5 px-2.5 small" onclick="quickFillUnit('create', 'Per Meter', 'm', 'Charged per meter (curtains, carpets)')">Per Meter</button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="create_unit_name" class="form-label fw-semibold text-dark small">Unit Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create_unit_name" name="name" placeholder="e.g. Per Kg, Per Unit" required maxlength="100">
                        <div class="form-text small text-muted">Example: <em>Per Kg</em>, <em>Per Unit</em>, <em>Per Piece</em></div>
                    </div>

                    <div class="mb-3">
                        <label for="create_short_name" class="form-label fw-semibold text-dark small">Short Symbol / Abbreviation</label>
                        <input type="text" class="form-control" id="create_short_name" name="short_name" placeholder="e.g. kg, unit, pc, pair" maxlength="50">
                    </div>

                    <div class="mb-3">
                        <label for="create_description" class="form-label fw-semibold text-dark small">Description</label>
                        <textarea class="form-control" id="create_description" name="description" rows="2" placeholder="Brief note explaining how this unit is billed..."></textarea>
                    </div>

                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="create_is_active" name="is_active" value="1" checked>
                        <label class="form-check-label fw-semibold small text-dark" for="create_is_active">Active for service pricing</label>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="createUnitSubmitBtn" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <span>Save Unit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Unit Modal -->
<div class="modal fade" id="editUnitModal" tabindex="-1" aria-labelledby="editUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-light border-0 py-3 px-4">
                <h5 class="modal-title fw-bold text-dark" id="editUnitModalLabel">
                    <i data-feather="edit-2" class="feather-sm text-warning me-2"></i> Edit Pricing Unit
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUnitForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_unit_id" name="id">
                <div class="modal-body p-4">
                    <div id="editUnitAlert" class="alert alert-danger d-none" role="alert"></div>

                    <div class="mb-3">
                        <label for="edit_unit_name" class="form-label fw-semibold text-dark small">Unit Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_unit_name" name="name" required maxlength="100">
                    </div>

                    <div class="mb-3">
                        <label for="edit_short_name" class="form-label fw-semibold text-dark small">Short Symbol / Abbreviation</label>
                        <input type="text" class="form-control" id="edit_short_name" name="short_name" maxlength="50">
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label fw-semibold text-dark small">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                    </div>

                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                        <label class="form-check-label fw-semibold small text-dark" for="edit_is_active">Active for service pricing</label>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="editUnitSubmitBtn" class="btn btn-warning text-white rounded-pill px-4 fw-semibold shadow-sm">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <span>Update Unit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- DataTables & Plugins -->
<script src="{{ asset('admin/dist/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin/dist/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('admin/dist/libs/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>

<script>
    let unitsTable;

    function quickFillUnit(formPrefix, name, shortName, description) {
        document.getElementById(formPrefix + '_unit_name').value = name;
        document.getElementById(formPrefix + '_short_name').value = shortName;
        document.getElementById(formPrefix + '_description').value = description;
    }

    $(document).ready(function() {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 3500
        };

        // Initialize DataTable
        unitsTable = $('#unitsTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: "{{ route('admin.services.units.data') }}",
                dataSrc: 'data'
            },
            columns: [
                {
                    data: null,
                    render: function (data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                {
                    data: 'name',
                    render: function(data) {
                        return '<span class="badge-unit">' + $('<div>').text(data).html() + '</span>';
                    }
                },
                {
                    data: 'short_name',
                    render: function(data) {
                        return '<code class="fw-bold text-indigo">' + $('<div>').text(data).html() + '</code>';
                    }
                },
                {
                    data: 'description',
                    render: function(data) {
                        return '<span class="text-muted small">' + $('<div>').text(data).html() + '</span>';
                    }
                },
                {
                    data: 'is_active',
                    render: function(data) {
                        if (data) {
                            return '<span class="badge-status-active"><i data-feather="check" style="width: 12px; height: 12px;" class="me-1"></i>Active</span>';
                        }
                        return '<span class="badge-status-inactive"><i data-feather="x" style="width: 12px; height: 12px;" class="me-1"></i>Inactive</span>';
                    }
                },
                {
                    data: 'created_at',
                    render: function(data) {
                        return '<span class="text-muted small">' + data + '</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-end',
                    render: function(data, type, row) {
                        let actions = '<div class="d-inline-flex gap-1">';

                        @can('edit-units', 'admin')
                        actions += `<button type="button" class="action-btn action-btn-edit" title="Edit Unit" onclick="openEditUnitModal(${row.id}, '${escapeHtml(row.name)}', '${escapeHtml(row.short_name === '-' ? '' : row.short_name)}', '${escapeHtml(row.description === '-' ? '' : row.description)}', ${row.is_active})">
                            <i data-feather="edit-2" style="width: 15px; height: 15px;"></i>
                        </button>`;

                        actions += `<button type="button" class="action-btn action-btn-toggle" title="${row.is_active ? 'Deactivate' : 'Activate'}" onclick="toggleUnitStatus(${row.id})">
                            <i data-feather="${row.is_active ? 'slash' : 'check-circle'}" style="width: 15px; height: 15px;"></i>
                        </button>`;
                        @endcan

                        @can('delete-units', 'admin')
                        actions += `<button type="button" class="action-btn action-btn-delete" title="Delete Unit" onclick="deleteUnit(${row.id}, '${escapeHtml(row.name)}')">
                            <i data-feather="trash-2" style="width: 15px; height: 15px;"></i>
                        </button>`;
                        @endcan

                        actions += '</div>';
                        return actions;
                    }
                }
            ],
            drawCallback: function() {
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }
        });

        // Create Unit AJAX Submit
        $('#createUnitForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#createUnitSubmitBtn');
            const alertBox = $('#createUnitAlert');

            btn.prop('disabled', true);
            btn.find('.spinner-border').removeClass('d-none');
            alertBox.addClass('d-none').empty();

            $.ajax({
                url: "{{ route('admin.services.units.store') }}",
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#createUnitModal').modal('hide');
                    $('#createUnitForm')[0].reset();
                    toastr.success(response.message || 'Unit created successfully.');
                    unitsTable.ajax.reload(null, false);
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON.errors) {
                        let errorHtml = '<ul class="mb-0 ps-3">';
                        $.each(xhr.responseJSON.errors, function(field, messages) {
                            messages.forEach(msg => errorHtml += `<li>${msg}</li>`);
                        });
                        errorHtml += '</ul>';
                        alertBox.html(errorHtml).removeClass('d-none');
                    } else {
                        alertBox.html(xhr.responseJSON?.message || 'Failed to create unit.').removeClass('d-none');
                    }
                },
                complete: function() {
                    btn.prop('disabled', false);
                    btn.find('.spinner-border').addClass('d-none');
                }
            });
        });

        // Edit Unit AJAX Submit
        $('#editUnitForm').on('submit', function(e) {
            e.preventDefault();
            const unitId = $('#edit_unit_id').val();
            const btn = $('#editUnitSubmitBtn');
            const alertBox = $('#editUnitAlert');

            btn.prop('disabled', true);
            btn.find('.spinner-border').removeClass('d-none');
            alertBox.addClass('d-none').empty();

            $.ajax({
                url: "{{ url('admin/services/units') }}/" + unitId,
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#editUnitModal').modal('hide');
                    toastr.success(response.message || 'Unit updated successfully.');
                    unitsTable.ajax.reload(null, false);
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON.errors) {
                        let errorHtml = '<ul class="mb-0 ps-3">';
                        $.each(xhr.responseJSON.errors, function(field, messages) {
                            messages.forEach(msg => errorHtml += `<li>${msg}</li>`);
                        });
                        errorHtml += '</ul>';
                        alertBox.html(errorHtml).removeClass('d-none');
                    } else {
                        alertBox.html(xhr.responseJSON?.message || 'Failed to update unit.').removeClass('d-none');
                    }
                },
                complete: function() {
                    btn.prop('disabled', false);
                    btn.find('.spinner-border').addClass('d-none');
                }
            });
        });
    });

    function openEditUnitModal(id, name, shortName, description, isActive) {
        $('#edit_unit_id').val(id);
        $('#edit_unit_name').val(name);
        $('#edit_short_name').val(shortName);
        $('#edit_description').val(description);
        $('#edit_is_active').prop('checked', !!isActive);
        $('#editUnitAlert').addClass('d-none').empty();
        $('#editUnitModal').modal('show');
    }

    function toggleUnitStatus(id) {
        $.ajax({
            url: "{{ url('admin/services/units') }}/" + id + "/toggle-status",
            type: 'PATCH',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                toastr.success(response.message);
                unitsTable.ajax.reload(null, false);
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to change unit status.');
            }
        });
    }

    function deleteUnit(id, name) {
        Swal.fire({
            title: 'Delete Service Unit?',
            html: `Are you sure you want to delete unit <strong>"${escapeHtml(name)}"</strong>?<br><span class="text-danger small">This action cannot be undone.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="mdi mdi-delete me-1"></i> Yes, Delete Unit',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-4 shadow-lg',
                confirmButton: 'btn btn-danger px-4 py-2 rounded-3',
                cancelButton: 'btn btn-light px-4 py-2 rounded-3 text-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('admin/services/units') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message || `Unit "${name}" was deleted successfully.`,
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: {
                                popup: 'rounded-4 shadow-lg'
                            }
                        });
                        unitsTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Delete Failed',
                            text: xhr.responseJSON?.message || 'Failed to delete unit.',
                            customClass: {
                                popup: 'rounded-4 shadow-lg'
                            }
                        });
                    }
                });
            }
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>
@endpush
