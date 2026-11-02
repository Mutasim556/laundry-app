@extends('admin::layouts.app')

@section('title', 'Registered Vendors - Laundry Marketplace')

@push('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.min.css') }}">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/toastr/toastr.min.css') }}">

    <style>
        .vendors-hero {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 1.25rem;
            padding: 2.25rem 2.5rem;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.2);
            margin-bottom: 2rem;
        }
        .vendors-hero::after {
            content: '';
            position: absolute;
            top: -40%;
            right: -10%;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, rgba(99, 102, 241, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .vendors-hero-title {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            margin-bottom: 0.35rem;
            color: #ffffff;
        }
        .vendors-hero-subtitle {
            font-size: 0.95rem;
            color: #94a3b8;
            margin-bottom: 0;
        }
        .btn-hero-add {
            background: #6366f1;
            color: #ffffff !important;
            border: none;
            border-radius: 50rem;
            padding: 0.75rem 1.6rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
        }
        .btn-hero-add:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
        }
        .vendors-card {
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
            background: #ffffff;
            overflow: hidden;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 50rem;
            text-transform: capitalize;
        }
        .status-badge.active { background: #dcfce7; color: #15803d; }
        .status-badge.pending { background: #fef9c3; color: #a16207; }
        .status-badge.suspended { background: #fee2e2; color: #b91c1c; }
        .status-badge.closed { background: #f1f5f9; color: #64748b; }
        .action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #475569;
            transition: all 0.15s ease;
        }
        .action-btn:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #1e293b;
        }
        .action-btn.btn-edit:hover { color: #6366f1; border-color: #c7d2fe; background: #eef2ff; }
        .action-btn.btn-delete:hover { color: #ef4444; border-color: #fecaca; background: #fef2f2; }
    </style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Hero Banner -->
    <div class="vendors-hero">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2" style="background: transparent; padding: 0;">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-white-50 text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.stores.index') }}" class="text-white-50 text-decoration-none">Store Management</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Vendors</li>
                    </ol>
                </nav>
                <h1 class="vendors-hero-title">Vendor Accounts & Verification</h1>
                <p class="vendors-hero-subtitle">Manage partner businesses, owners, trade licences, and payout accounts. Separate vendor entities from individual branch stores.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <button type="button" class="btn btn-hero-add" data-bs-toggle="modal" data-bs-target="#createVendorModal">
                    <i class="mdi mdi-plus-circle font-18"></i>
                    <span>Register New Vendor</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Vendors Table Card -->
    <div class="vendors-card">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="vendorsTable" class="table table-hover align-middle mb-0 w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Business & Owner</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Stores</th>
                            <th>Commission</th>
                            <th>Trade Licence</th>
                            <th class="text-end" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Vendor Modal -->
<div class="modal fade" id="createVendorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-light border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="mdi mdi-briefcase text-primary font-22"></i>
                    <h5 class="modal-title fw-bold text-dark mb-0">Register New Vendor Account</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createVendorForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-danger d-none" id="createVendorErrors"></div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Vendor / Business Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. QuickClean Services Ltd." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Owner's Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="owner_name" class="form-control" placeholder="e.g. Rafiqul Islam" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" placeholder="017xxxxxxxx" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Alternative Phone</label>
                            <input type="text" name="alt_phone" class="form-control" placeholder="018xxxxxxxx">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="contact@vendor.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small mb-1">Business Registered Address</label>
                        <textarea name="business_address" rows="2" class="form-control" placeholder="Headquarters or official registered business address..."></textarea>
                    </div>

                    <hr class="text-muted my-3">

                    <h6 class="fw-bold text-dark mb-2"><i class="mdi mdi-certificate text-warning me-1"></i> Verification & Business Agreement</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Approval Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="pending">Pending Approval</option>
                                <option value="active" selected>Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Commission Type <span class="text-danger">*</span></label>
                            <select name="commission_type" class="form-select" required>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Per Order (৳ BDT)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Commission Rate</label>
                            <input type="number" step="0.01" min="0" name="commission_rate" class="form-control" value="10.00">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Trade Licence Number</label>
                            <input type="text" name="trade_license_number" class="form-control" placeholder="e.g. TRAD/DNCC/123456/2026">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Trade Licence Document (PDF/Image)</label>
                            <input type="file" name="trade_license_document" class="form-control" accept=".pdf,image/*">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small mb-1">Verification / Administrative Notes</label>
                        <textarea name="verification_notes" rows="2" class="form-control" placeholder="Internal verification notes, contract status, or remarks..."></textarea>
                    </div>

                    <hr class="text-muted my-3">

                    <h6 class="fw-bold text-dark mb-2"><i class="mdi mdi-cash text-success me-1"></i> Payout Method & Account Details</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Payout Method</label>
                            <select name="payout_method" class="form-select">
                                <option value="">Select Method...</option>
                                <option value="bKash">bKash Merchant / Personal</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Rocket">Rocket</option>
                                <option value="Bank Transfer">Bank Transfer (BEFTN/NPSB)</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small mb-1">Payout Account Details</label>
                            <input type="text" name="payout_details" class="form-control" placeholder="e.g. bKash 01711000000 or Bank Name, A/C No, Branch, Routing">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnSaveVendor">Register Vendor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Vendor Modal -->
<div class="modal fade" id="editVendorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-light border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="mdi mdi-pencil text-primary font-22"></i>
                    <h5 class="modal-title fw-bold text-dark mb-0">Edit Vendor Account</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editVendorForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_vendor_id" name="id">
                <div class="modal-body p-4">
                    <div class="alert alert-danger d-none" id="editVendorErrors"></div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Vendor / Business Name <span class="text-danger">*</span></label>
                            <input type="text" id="edit_name" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Owner's Full Name <span class="text-danger">*</span></label>
                            <input type="text" id="edit_owner_name" name="owner_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" id="edit_phone" name="phone" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Alternative Phone</label>
                            <input type="text" id="edit_alt_phone" name="alt_phone" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Email Address</label>
                            <input type="email" id="edit_email" name="email" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small mb-1">Business Registered Address</label>
                        <textarea id="edit_business_address" name="business_address" rows="2" class="form-control"></textarea>
                    </div>

                    <hr class="text-muted my-3">

                    <h6 class="fw-bold text-dark mb-2"><i class="mdi mdi-certificate text-warning me-1"></i> Verification & Business Agreement</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Approval Status <span class="text-danger">*</span></label>
                            <select id="edit_status" name="status" class="form-select" required>
                                <option value="pending">Pending Approval</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Commission Type <span class="text-danger">*</span></label>
                            <select id="edit_commission_type" name="commission_type" class="form-select" required>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Per Order (৳ BDT)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Commission Rate</label>
                            <input type="number" step="0.01" min="0" id="edit_commission_rate" name="commission_rate" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Trade Licence Number</label>
                            <input type="text" id="edit_trade_license_number" name="trade_license_number" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Trade Licence Document</label>
                            <input type="file" name="trade_license_document" class="form-control" accept=".pdf,image/*">
                            <div id="edit_doc_preview" class="small mt-1 text-muted"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small mb-1">Verification / Administrative Notes</label>
                        <textarea id="edit_verification_notes" name="verification_notes" rows="2" class="form-control"></textarea>
                    </div>

                    <hr class="text-muted my-3">

                    <h6 class="fw-bold text-dark mb-2"><i class="mdi mdi-cash text-success me-1"></i> Payout Method & Account Details</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small mb-1">Payout Method</label>
                            <select id="edit_payout_method" name="payout_method" class="form-select">
                                <option value="">Select Method...</option>
                                <option value="bKash">bKash</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Rocket">Rocket</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small mb-1">Payout Account Details</label>
                            <input type="text" id="edit_payout_details" name="payout_details" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnUpdateVendor">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- DataTables JS -->
    <script src="{{ asset('admin/dist/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/dist/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <!-- SweetAlert2 JS -->
    <script src="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
    <!-- Toastr JS -->
    <script src="{{ asset('admin/dist/libs/toastr/toastr.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const vendorsTable = $('#vendorsTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: '{{ route('admin.stores.vendors.data') }}',
                columns: [
                    {
                        data: null,
                        render: (d, t, r, meta) => `<span class="fw-bold text-muted">${meta.row + 1}</span>`
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <div>
                                    <div class="fw-bold text-dark">${row.name}</div>
                                    <div class="text-muted small"><i class="mdi mdi-account-circle me-1"></i>${row.owner_name}</div>
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            let info = `<div><i class="mdi mdi-phone me-1 text-primary"></i>${row.phone}</div>`;
                            if (row.email) {
                                info += `<div class="text-muted small"><i class="mdi mdi-email me-1"></i>${row.email}</div>`;
                            }
                            return info;
                        }
                    },
                    {
                        data: 'status',
                        render: function(status) {
                            return `<span class="status-badge ${status}">${status}</span>`;
                        }
                    },
                    {
                        data: 'stores_count',
                        render: (count) => `<span class="badge bg-light text-primary font-monospace fw-bold">${count} Store(s)</span>`
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            const unit = row.commission_type === 'percentage' ? '%' : ' ৳';
                            return `<span class="fw-semibold text-dark">${row.commission_rate}${unit}</span>`;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (row.trade_license_document_url) {
                                return `<a href="${row.trade_license_document_url}" target="_blank" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2 font-12"><i class="mdi mdi-file-document me-1"></i>View Doc</a>`;
                            }
                            return row.trade_license_number ? `<span class="badge bg-light text-muted font-monospace">${row.trade_license_number}</span>` : `<span class="text-muted small">Not provided</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: function(data, type, row) {
                            return `
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="action-btn btn-edit edit-vendor-btn" data-vendor='${JSON.stringify(row).replace(/'/g, "&apos;")}' title="Edit Vendor">
                                        <i class="mdi mdi-pencil font-15"></i>
                                    </button>
                                    <button type="button" class="action-btn btn-delete delete-vendor-btn" data-id="${row.id}" data-name="${row.name}" title="Delete Vendor">
                                        <i class="mdi mdi-delete font-15"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[0, 'asc']]
            });

            // Create Vendor Form Submit
            $('#createVendorForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#btnSaveVendor').prop('disabled', true);
                const formData = new FormData(this);

                $.ajax({
                    url: '{{ route('admin.stores.vendors.store') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#createVendorModal').modal('hide');
                        $('#createVendorForm')[0].reset();
                        $('#createVendorErrors').addClass('d-none').empty();
                        toastr.success(res.message);
                        vendorsTable.ajax.reload();
                    },
                    error: function(xhr) {
                        const errs = xhr.responseJSON?.errors;
                        let msg = '<ul>';
                        if (errs) {
                            Object.values(errs).forEach(e => msg += `<li>${e[0]}</li>`);
                        } else {
                            msg += `<li>${xhr.responseJSON?.message || 'Error creating vendor'}</li>`;
                        }
                        msg += '</ul>';
                        $('#createVendorErrors').removeClass('d-none').html(msg);
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });

            // Open Edit Modal
            $(document).on('click', '.edit-vendor-btn', function() {
                const vendor = $(this).data('vendor');
                $('#edit_vendor_id').val(vendor.id);
                $('#edit_name').val(vendor.name);
                $('#edit_owner_name').val(vendor.owner_name);
                $('#edit_phone').val(vendor.phone);
                $('#edit_alt_phone').val(vendor.alt_phone);
                $('#edit_email').val(vendor.email);
                $('#edit_business_address').val(vendor.business_address);
                $('#edit_status').val(vendor.status);
                $('#edit_commission_type').val(vendor.commission_type);
                $('#edit_commission_rate').val(vendor.commission_rate);
                $('#edit_trade_license_number').val(vendor.trade_license_number);
                $('#edit_verification_notes').val(vendor.verification_notes);
                $('#edit_payout_method').val(vendor.payout_method);
                $('#edit_payout_details').val(vendor.payout_details);

                if (vendor.trade_license_document_url) {
                    $('#edit_doc_preview').html(`<a href="${vendor.trade_license_document_url}" target="_blank" class="text-primary"><i class="mdi mdi-link-variant"></i> View Current Document</a>`);
                } else {
                    $('#edit_doc_preview').empty();
                }

                $('#editVendorErrors').addClass('d-none').empty();
                $('#editVendorModal').modal('show');
            });

            // Update Vendor Form Submit
            $('#editVendorForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#edit_vendor_id').val();
                const $btn = $('#btnUpdateVendor').prop('disabled', true);
                const formData = new FormData(this);

                $.ajax({
                    url: '{{ url('admin/stores/vendors') }}/' + id,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#editVendorModal').modal('hide');
                        toastr.success(res.message);
                        vendorsTable.ajax.reload();
                    },
                    error: function(xhr) {
                        const errs = xhr.responseJSON?.errors;
                        let msg = '<ul>';
                        if (errs) {
                            Object.values(errs).forEach(e => msg += `<li>${e[0]}</li>`);
                        } else {
                            msg += `<li>${xhr.responseJSON?.message || 'Error updating vendor'}</li>`;
                        }
                        msg += '</ul>';
                        $('#editVendorErrors').removeClass('d-none').html(msg);
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });

            // Delete Vendor
            $(document).on('click', '.delete-vendor-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Delete Vendor?',
                    text: `Are you sure you want to delete vendor "${name}"? Stores owned by this vendor will be preserved or detached.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete Vendor'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ url('admin/stores/vendors') }}/' + id,
                            method: 'DELETE',
                            success: function(res) {
                                toastr.success(res.message);
                                vendorsTable.ajax.reload();
                            },
                            error: function(xhr) {
                                toastr.error(xhr.responseJSON?.message || 'Error deleting vendor');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
