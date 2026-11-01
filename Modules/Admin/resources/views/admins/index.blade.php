@extends('admin::layouts.app')

@push('styles')
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/toastr/toastr.min.css') }}">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.min.css') }}">
    <style>
        /* Modern Header Banner with Vibrant Indigo-Purple Gradient */
        .admins-hero-banner {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%);
            border-radius: 16px;
            padding: 24px 28px;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.25), 0 8px 10px -6px rgba(79, 70, 229, 0.2);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .admins-hero-banner::after {
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
        .admins-hero-title {
            color: #ffffff !important;
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }
        .admins-breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        .admins-breadcrumb .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.82) !important;
            font-weight: 500;
            transition: color 0.15s ease;
        }
        .admins-breadcrumb .breadcrumb-item a:hover {
            color: #ffffff !important;
            text-decoration: underline;
        }
        .admins-breadcrumb .breadcrumb-item.active {
            color: #ffffff !important;
            font-weight: 600;
        }
        .admins-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
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
        .card-admins {
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
            background: #ffffff;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        #adminsTable {
            font-size: 0.95rem;
            color: #0f172a;
        }
        #adminsTable thead th {
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
        #adminsTable tbody td {
            font-size: 0.96rem;
            font-weight: 600;
            color: #0f172a !important;
            padding: 16px 18px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
        }
        #adminsTable tbody tr:hover {
            background-color: #f8fafc;
        }
        .admin-name-text {
            color: #0f172a !important;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: -0.01em;
        }
        .admin-email-text {
            color: #475569 !important;
            font-weight: 500;
            font-size: 0.88rem;
        }
        .admin-date-text {
            color: #334155 !important;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* DataTables Controls High Visibility */
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label,
        .dataTables_wrapper .dataTables_info {
            color: #1e293b !important;
            font-weight: 600 !important;
            font-size: 0.92rem;
        }
        .dataTables_wrapper .dataTables_filter input {
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 6px 12px !important;
            font-weight: 600;
            color: #0f172a !important;
            background: #ffffff !important;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.18) !important;
            outline: none;
        }
        .dataTables_wrapper .dataTables_paginate .page-link {
            font-weight: 700 !important;
            color: #334155 !important;
            border-radius: 6px !important;
            margin: 0 2px;
        }
        .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
            background-color: #4f46e5 !important;
            border-color: #4f46e5 !important;
            color: #ffffff !important;
        }

        /* Action Buttons */
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
        .action-btn-role {
            background: #e0e7ff;
            color: #4338ca;
            border-color: #c7d2fe;
        }
        .action-btn-role:hover {
            background: #4f46e5;
            color: #ffffff;
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
        .action-btn-delete {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fecaca;
        }
        .action-btn-delete:hover {
            background: #dc2626;
            color: #ffffff;
        }
        .action-btn-block {
            background: #f1f5f9;
            color: #475569;
            border-color: #cbd5e1;
        }
        .action-btn-block:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .action-btn-unblock {
            background: #dcfce7;
            color: #15803d;
            border-color: #86efac;
        }
        .action-btn-unblock:hover {
            background: #22c55e;
            color: #ffffff;
        }

        /* Status Badges */
        .status-badge-active {
            background-color: #ecfdf5 !important;
            color: #047857 !important;
            border: 1.5px solid #a7f3d0 !important;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
        }
        .status-badge-blocked {
            background-color: #fef2f2 !important;
            color: #b91c1c !important;
            border: 1.5px solid #fecaca !important;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
        }

        /* Role Badges */
        .role-pill-badge {
            background-color: #eff6ff;
            color: #1d4ed8;
            font-weight: 700;
            border: 1.5px solid #bfdbfe;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 0.82rem;
            display: inline-flex;
            align-items: center;
        }
        .role-pill-super {
            background-color: #fef2f2 !important;
            color: #b91c1c !important;
            border: 1.5px solid #fecaca !important;
            font-weight: 700 !important;
        }

        /* Attractive Modern Form Inputs */
        .form-control-modern, .form-select-modern {
            background-color: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.92rem;
            font-weight: 500;
            color: #0f172a;
            transition: all 0.2s ease;
        }
        .form-control-modern::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }
        .form-control-modern:focus, .form-select-modern:focus {
            background-color: #ffffff;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
            outline: none;
            color: #0f172a;
        }

        /* Decreased Margin-Top and Modern Smooth Slide-Up Modal Animation */
        .modal {
            padding-top: 1.5rem !important;
        }
        .modal-dialog {
            margin-top: 1.5rem !important;
            margin-bottom: 1.5rem !important;
        }
        .modal.fade .modal-dialog {
            opacity: 0;
            transform: translateY(-30px) scale(0.97);
            transition: transform 0.26s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.22s ease-out;
        }
        .modal.show .modal-dialog {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
        .modal-content {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 18px;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.25);
            overflow: hidden;
        }
        .modal-header {
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
            padding: 18px 24px;
        }
        .modal-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 1.15rem;
        }
        .modal-body {
            padding: 22px 24px;
        }
        .modal-footer {
            background-color: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 14px 24px;
        }

        .role-option-card {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            transition: all 0.2s ease;
            background: #ffffff;
            cursor: pointer;
        }
        .role-option-card:hover {
            border-color: #6366f1;
            background: #f5f7ff;
        }
        .role-option-card input:checked ~ span {
            color: #4338ca;
            font-weight: 700;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">
    <!-- Header Banner with Gradient Background -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="admins-hero-banner d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h3 class="admins-hero-title">Admin Users Management</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb admins-breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}" class="text-decoration-none">Roles & Permissions</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Admins</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    @can('create-admins', 'admin')
                    <button type="button" class="btn btn-hero-add rounded-pill px-4 py-2 d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                        <i data-feather="user-plus" class="feather-sm me-2"></i> Add New Admin
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- AJAX Alert Notifications Container -->
    <div id="ajaxAlertContainer"></div>

    <!-- Admins DataTable Card -->
    <div class="row">
        <div class="col-12">
            <div class="card card-admins border-0">
                <div class="card-header bg-transparent border-bottom border-light px-4 py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">System Administrators</h5>
                        <p class="text-muted small mb-0">Manage admin portal users and assign their administrative roles.</p>
                    </div>
                    <span class="badge bg-light-primary text-primary rounded-pill px-3 py-2 fw-semibold fs-2" id="totalAdminsBadge">
                        Admins
                    </span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <!-- Horizontally Scrollable Table Wrapper -->
                    <div class="table-responsive w-100">
                        <table id="adminsTable" class="table table-hover align-middle text-nowrap mb-0" style="width:100%;">
                            <thead>
                                <tr>
                                    <th class="border-top-0 px-3 text-center" style="width: 50px;">#</th>
                                    <th class="border-top-0 px-3">Admin User</th>
                                    <th class="border-top-0 px-3">Email Address</th>
                                    <th class="border-top-0 px-3">Assigned Roles (admin guard)</th>
                                    <th class="border-top-0 px-3 text-center">Status</th>
                                    <th class="border-top-0 px-3">Joined Date</th>
                                    <th class="border-top-0 px-3 text-center" style="width: 190px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loaded dynamically via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= 1. Create Admin Modal ================= -->
<div class="modal fade" id="createAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i data-feather="user-plus" class="feather-sm me-2 text-primary"></i> Add New Administrator
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createAdminForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div id="createAdminErrors" class="alert alert-danger d-none border-0 rounded-3 mb-3"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="create_admin_name" class="form-label fw-bold text-dark mb-1">Full Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control form-control-modern"
                                id="create_admin_name"
                                name="name"
                                placeholder="e.g. John Doe"
                                required
                            />
                        </div>
                        <div class="col-md-6">
                            <label for="create_admin_email" class="form-label fw-bold text-dark mb-1">Email Address <span class="text-danger">*</span></label>
                            <input
                                type="email"
                                class="form-control form-control-modern"
                                id="create_admin_email"
                                name="email"
                                placeholder="e.g. john@laundry.com"
                                required
                            />
                        </div>
                        <div class="col-md-6">
                            <label for="create_admin_password" class="form-label fw-bold text-dark mb-1">Password <span class="text-danger">*</span></label>
                            <input
                                type="password"
                                class="form-control form-control-modern"
                                id="create_admin_password"
                                name="password"
                                placeholder="At least 8 characters"
                                required
                            />
                        </div>
                        <div class="col-md-6">
                            <label for="create_admin_avatar" class="form-label fw-bold text-dark mb-1">Profile Photo (Optional)</label>
                            <input
                                type="file"
                                class="form-control form-control-modern"
                                id="create_admin_avatar"
                                name="avatar"
                                accept="image/*"
                                onchange="previewAdminAvatar(this, '#createAvatarPreview', '#createAvatarPreviewWrapper')"
                            />
                            <!-- Image Preview Area -->
                            <div id="createAvatarPreviewWrapper" class="d-none mt-2 align-items-center gap-3 p-2 bg-light rounded-3 border">
                                <img id="createAvatarPreview" src="#" alt="Preview" class="rounded-circle object-fit-cover border shadow-sm" width="54" height="54" />
                                <div>
                                    <div class="small fw-bold text-dark">Selected Image Preview</div>
                                    <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0 small fw-semibold" onclick="removeAvatarPreview('#create_admin_avatar', '#createAvatarPreview', '#createAvatarPreviewWrapper')">
                                        <i data-feather="x" style="width: 13px; height: 13px;"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Guard Role Selection (Single Role via Checkbox) -->
                        <div class="col-12 mt-3">
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                                <label class="form-label fw-bold text-dark mb-0">
                                    <i data-feather="shield" class="feather-sm text-primary me-1"></i> Select Role (admin guard) <span class="text-danger">*</span>
                                </label>
                                <span class="text-muted small">Only one role can be checked</span>
                            </div>
                            <div class="row g-2">
                                @forelse ($adminRoles as $role)
                                    <div class="col-md-6 col-lg-4">
                                        <label class="role-option-card d-flex align-items-center gap-2 w-100 mb-0">
                                            <input
                                                type="checkbox"
                                                class="form-check-input mt-0 create-role-single-cb"
                                                name="roles[]"
                                                value="{{ $role->name }}"
                                                id="create_role_{{ $role->id }}"
                                                onchange="handleSingleRoleCheckbox('.create-role-single-cb', this)"
                                            />
                                            <span class="fs-2 text-dark fw-bold">{{ $role->name }}</span>
                                            @if ($role->name === 'super-admin')
                                                <span class="badge bg-danger text-white rounded-pill ms-auto" style="font-size: 0.68rem;">Super</span>
                                            @endif
                                        </label>
                                    </div>
                                @empty
                                    <div class="col-12 text-muted small py-2">
                                        No roles found for admin guard. Create them on the <a href="{{ route('admin.roles.index') }}">Roles page</a>.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="createAdminSubmitBtn" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                        Create Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= 2. Edit Admin Modal ================= -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i data-feather="edit-2" class="feather-sm me-2 text-warning"></i> Edit Administrator: <span id="editAdminModalTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAdminForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_admin_id" name="admin_id" />
                <div class="modal-body">
                    <div id="editAdminErrors" class="alert alert-danger d-none border-0 rounded-3 mb-3"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_admin_name" class="form-label fw-bold text-dark mb-1">Full Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control form-control-modern"
                                id="edit_admin_name"
                                name="name"
                                required
                            />
                        </div>
                        <div class="col-md-6">
                            <label for="edit_admin_email" class="form-label fw-bold text-dark mb-1">Email Address <span class="text-danger">*</span></label>
                            <input
                                type="email"
                                class="form-control form-control-modern"
                                id="edit_admin_email"
                                name="email"
                                required
                            />
                        </div>
                        <div class="col-md-6">
                            <label for="edit_admin_password" class="form-label fw-bold text-dark mb-1">Password (Leave blank to keep unchanged)</label>
                            <input
                                type="password"
                                class="form-control form-control-modern"
                                id="edit_admin_password"
                                name="password"
                                placeholder="New password (optional)"
                            />
                        </div>
                        <div class="col-md-6">
                            <label for="edit_admin_avatar" class="form-label fw-bold text-dark mb-1">Update Photo (Optional)</label>
                            <input
                                type="file"
                                class="form-control form-control-modern"
                                id="edit_admin_avatar"
                                name="avatar"
                                accept="image/*"
                                onchange="previewAdminAvatar(this, '#editAvatarPreview', '#editAvatarPreviewWrapper', '#editCurrentAvatarWrapper')"
                            />
                            <!-- Current Photo Display -->
                            <div id="editCurrentAvatarWrapper" class="mt-2 d-flex align-items-center gap-3 p-2 bg-light-subtle rounded-3 border">
                                <img id="editCurrentAvatarImg" src="#" alt="Current Photo" class="rounded-circle object-fit-cover border shadow-sm" width="50" height="50" />
                                <div>
                                    <div class="small fw-bold text-dark">Current Photo</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Uploading a new image will replace this</div>
                                </div>
                            </div>
                            <!-- New Image Preview Area -->
                            <div id="editAvatarPreviewWrapper" class="d-none mt-2 align-items-center gap-3 p-2 bg-light rounded-3 border border-primary-subtle">
                                <img id="editAvatarPreview" src="#" alt="Preview" class="rounded-circle object-fit-cover border shadow-sm" width="50" height="50" />
                                <div>
                                    <div class="small fw-bold text-primary">New Image Preview</div>
                                    <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0 small fw-semibold" onclick="removeAvatarPreview('#edit_admin_avatar', '#editAvatarPreview', '#editAvatarPreviewWrapper', '#editCurrentAvatarWrapper')">
                                        <i data-feather="x" style="width: 13px; height: 13px;"></i> Cancel New Photo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Guard Role Selection (Single Role via Checkbox) -->
                        <div class="col-12 mt-3">
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                                <label class="form-label fw-bold text-dark mb-0">
                                    <i data-feather="shield" class="feather-sm text-warning me-1"></i> Assigned Role (admin guard) <span class="text-danger">*</span>
                                </label>
                                <span class="text-muted small">Only one role can be checked</span>
                            </div>
                            <div class="row g-2">
                                @foreach ($adminRoles as $role)
                                    <div class="col-md-6 col-lg-4">
                                        <label class="role-option-card d-flex align-items-center gap-2 w-100 mb-0">
                                            <input
                                                type="checkbox"
                                                class="form-check-input mt-0 edit-role-single-cb"
                                                name="roles[]"
                                                value="{{ $role->name }}"
                                                id="edit_role_{{ $role->id }}"
                                                onchange="handleSingleRoleCheckbox('.edit-role-single-cb', this)"
                                            />
                                            <span class="fs-2 text-dark fw-bold">{{ $role->name }}</span>
                                            @if ($role->name === 'super-admin')
                                                <span class="badge bg-danger text-white rounded-pill ms-auto" style="font-size: 0.68rem;">Super</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="editAdminSubmitBtn" class="btn btn-warning text-white rounded-pill px-4 fw-semibold shadow-sm">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= 3. Assign Role Modal ================= -->
<div class="modal fade" id="assignRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">
                        <i data-feather="shield" class="feather-sm me-2 text-primary"></i> Assign Role: <span id="assignRoleModalAdminName"></span>
                    </h5>
                    <span class="text-muted small">Select a single role under the <strong>admin</strong> guard</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignRoleForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="assign_role_admin_id" name="admin_id" />
                <div class="modal-body">
                    <div id="assignRoleErrors" class="alert alert-danger d-none border-0 rounded-3 mb-3"></div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                            <label class="form-label fw-bold text-dark mb-0">Available Admin Roles:</label>
                            <span class="text-muted small">Only one role can be checked</span>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            @foreach ($adminRoles as $role)
                                <label class="role-option-card d-flex align-items-center gap-2 w-100 mb-0">
                                    <input
                                        type="checkbox"
                                        class="form-check-input mt-0 assign-role-single-cb"
                                        name="roles[]"
                                        value="{{ $role->name }}"
                                        id="assign_cb_role_{{ $role->id }}"
                                        onchange="handleSingleRoleCheckbox('.assign-role-single-cb', this)"
                                    />
                                    <span class="fs-3 text-dark fw-bold">{{ $role->name }}</span>
                                    @if ($role->name === 'super-admin')
                                        <span class="badge bg-danger text-white rounded-pill ms-auto px-2 py-1" style="font-size: 0.72rem;">Full Access</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="assignRoleSubmitBtn" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                        Save Assigned Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= 4. Delete Admin Confirmation Modal ================= -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i data-feather="alert-triangle" class="feather-sm me-2 text-danger"></i> Confirm Delete Admin
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="bg-light-danger text-danger rounded-circle p-3 d-inline-flex mb-3">
                    <i data-feather="trash-2" style="width: 32px; height: 32px;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Delete Admin "<span id="deleteAdminName"></span>"?</h5>
                <p class="text-muted mb-0 small">Are you sure you want to delete this administrator account? All access privileges will be revoked immediately.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmDeleteAdminBtn" class="btn btn-danger rounded-pill px-4 text-white fw-semibold">
                    Yes, Delete Admin
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- DataTables JS -->
    <script src="{{ asset('admin/dist/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/dist/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <!-- Toastr JS -->
    <script src="{{ asset('admin/dist/libs/toastr/toastr.min.js') }}"></script>
    <!-- SweetAlert2 JS -->
    <script src="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
    <script>
        const canEditAdmin = @json(auth('admin')->user()->can('edit-admins', 'admin'));
        const canDeleteAdmin = @json(auth('admin')->user()->can('delete-admins', 'admin'));

        let adminsTable;
        let cachedAdminsData = [];
        let adminIdToDelete = null;

        // Configure Toastr defaults
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: "4000",
            extendedTimeOut: "1000",
            showEasing: "swing",
            hideEasing: "linear",
            showMethod: "fadeIn",
            hideMethod: "fadeOut"
        };

        $(document).ready(function () {
            // Setup CSRF token for all AJAX calls
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
                }
            });

            // Initialize DataTable with AJAX loading
            adminsTable = $('#adminsTable').DataTable({
                ajax: {
                    url: "{{ route('admin.admins.data') }}",
                    dataSrc: function (json) {
                        cachedAdminsData = json.data;
                        $('#totalAdminsBadge').text('Total Admins: ' + json.data.length);
                        return json.data;
                    }
                },
                scrollX: true,
                autoWidth: false,
                pageLength: 10,
                order: [[0, 'asc']],
                columns: [
                    {
                        data: null,
                        render: function (data, type, row, meta) {
                            return `<span class="fw-bold text-dark fs-3">${meta.row + 1}</span>`;
                        },
                        className: "px-3 text-center align-middle"
                    },
                    {
                        data: 'name',
                        render: function (data, type, row) {
                            let youBadge = row.is_current_admin 
                                ? '<span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-0 ms-1 fw-bold" style="font-size: 0.68rem;">You</span>' 
                                : '';
                            return `
                                <div class="d-flex align-items-center">
                                    <img src="${row.avatar_url}" alt="${escapeHtml(row.name)}" class="rounded-circle object-fit-cover me-3 border shadow-sm" width="42" height="42" />
                                    <div>
                                        <div class="admin-name-text mb-0">${escapeHtml(row.name)} ${youBadge}</div>
                                        <div class="admin-email-text">${escapeHtml(row.email)}</div>
                                    </div>
                                </div>
                            `;
                        },
                        className: "px-3 align-middle"
                    },
                    {
                        data: 'email',
                        render: function (data) {
                            return `<span class="fw-semibold text-dark">${escapeHtml(data)}</span>`;
                        },
                        className: "px-3 align-middle"
                    },
                    {
                        data: 'role_names',
                        render: function (data, type, row) {
                            if (!data || data.length === 0) {
                                return `<span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 fw-semibold">No Role Assigned</span>`;
                            }
                            return data.map(function(r) {
                                if (r.is_super_admin) {
                                    return `<span class="badge role-pill-badge role-pill-super me-1"><i data-feather="shield" style="width:12px;height:12px;" class="me-1"></i> ${escapeHtml(r.name)}</span>`;
                                }
                                return `<span class="badge role-pill-badge me-1"><i data-feather="check" style="width:12px;height:12px;" class="me-1"></i> ${escapeHtml(r.name)}</span>`;
                            }).join(' ');
                        },
                        className: "px-3 align-middle"
                    },
                    {
                        data: 'is_blocked',
                        render: function (data) {
                            if (data) {
                                return `<span class="status-badge-blocked"><i data-feather="slash" style="width:12px;height:12px;" class="me-1"></i> Blocked</span>`;
                            }
                            return `<span class="status-badge-active"><i data-feather="check-circle" style="width:12px;height:12px;" class="me-1"></i> Active</span>`;
                        },
                        className: "px-3 text-center align-middle"
                    },
                    {
                        data: 'created_at',
                        render: function (data) {
                            return `<span class="admin-date-text">${escapeHtml(data)}</span>`;
                        },
                        className: "px-3 align-middle"
                    },
                    {
                        data: null,
                        render: function (data, type, row) {
                            const isSelf = row.is_current_admin;
                            const isMasterSuper = row.email === 'admin@laundry.com';
                            const isBlocked = row.is_blocked;

                            let actions = '<div class="d-inline-flex gap-2">';

                            if (canEditAdmin) {
                                const blockBtn = isBlocked
                                    ? `<button type="button" class="action-btn action-btn-unblock shadow-sm" onclick="toggleAdminBlock(${row.id}, false, '${escapeHtml(row.name)}')" title="Unblock Admin" ${(isSelf || isMasterSuper) ? 'disabled style="opacity: 0.4; cursor: not-allowed;"' : ''}>
                                            <i data-feather="check-circle" style="width: 16px; height: 16px;"></i>
                                       </button>`
                                    : `<button type="button" class="action-btn action-btn-block shadow-sm" onclick="toggleAdminBlock(${row.id}, true, '${escapeHtml(row.name)}')" title="Block Admin" ${(isSelf || isMasterSuper) ? 'disabled style="opacity: 0.4; cursor: not-allowed;"' : ''}>
                                            <i data-feather="slash" style="width: 16px; height: 16px;"></i>
                                       </button>`;

                                actions += `
                                    <button type="button" class="action-btn action-btn-role shadow-sm" onclick="openAssignRoleModal(${row.id})" title="Assign Roles">
                                        <i data-feather="shield" style="width: 16px; height: 16px;"></i>
                                    </button>
                                    <button type="button" class="action-btn action-btn-edit shadow-sm" onclick="openEditAdminModal(${row.id})" title="Edit Admin">
                                        <i data-feather="edit-2" style="width: 16px; height: 16px;"></i>
                                    </button>
                                    ${blockBtn}
                                `;
                            }

                            if (canDeleteAdmin) {
                                actions += `
                                    <button type="button" class="action-btn action-btn-delete shadow-sm" onclick="openDeleteAdminModal(${row.id}, '${escapeHtml(row.name)}')" title="Delete Admin" ${(isSelf || isMasterSuper) ? 'disabled style="opacity: 0.4; cursor: not-allowed;"' : ''}>
                                        <i data-feather="trash-2" style="width: 16px; height: 16px;"></i>
                                    </button>
                                `;
                            }

                            if (!canEditAdmin && !canDeleteAdmin) {
                                actions += '<span class="text-muted small">No actions permitted</span>';
                            }

                            actions += '</div>';
                            return actions;
                        },
                        className: "px-3 text-center align-middle",
                        orderable: false
                    }
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search admins...",
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'></i>",
                        next: "<i class='mdi mdi-chevron-right'></i>"
                    }
                },
                drawCallback: function () {
                    if (window.feather) {
                        feather.replace();
                    }
                }
            });

            // Handle Create Admin AJAX Form Submit
            $('#createAdminForm').on('submit', function (e) {
                e.preventDefault();
                const $btn = $('#createAdminSubmitBtn');
                $btn.prop('disabled', true).text('Creating...');
                $('#createAdminErrors').addClass('d-none').html('');

                let formData = new FormData(this);

                $.ajax({
                    url: "{{ route('admin.admins.store') }}",
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        $('#createAdminModal').modal('hide');
                        $('#createAdminForm')[0].reset();
                        removeAvatarPreview('#create_admin_avatar', '#createAvatarPreview', '#createAvatarPreviewWrapper');
                        adminsTable.ajax.reload(null, false);
                        showAlert('success', res.message);
                    },
                    error: function (xhr) {
                        handleFormErrors(xhr, '#createAdminErrors');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text('Create Admin');
                    }
                });
            });

            // Handle Edit Admin AJAX Form Submit
            $('#editAdminForm').on('submit', function (e) {
                e.preventDefault();
                const adminId = $('#edit_admin_id').val();
                const $btn = $('#editAdminSubmitBtn');
                $btn.prop('disabled', true).text('Saving...');
                $('#editAdminErrors').addClass('d-none').html('');

                let formData = new FormData(this);

                $.ajax({
                    url: "{{ url('admin/admins') }}/" + adminId,
                    method: "POST", // Method spoofed with _method PUT in form
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        $('#editAdminModal').modal('hide');
                        adminsTable.ajax.reload(null, false);
                        showAlert('success', res.message);
                    },
                    error: function (xhr) {
                        handleFormErrors(xhr, '#editAdminErrors');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text('Save Changes');
                    }
                });
            });

            // Handle Assign Role AJAX Form Submit
            $('#assignRoleForm').on('submit', function (e) {
                e.preventDefault();
                const adminId = $('#assign_role_admin_id').val();
                const $btn = $('#assignRoleSubmitBtn');
                $btn.prop('disabled', true).text('Saving...');
                $('#assignRoleErrors').addClass('d-none').html('');

                $.ajax({
                    url: "{{ url('admin/admins') }}/" + adminId + "/roles",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function (res) {
                        $('#assignRoleModal').modal('hide');
                        adminsTable.ajax.reload(null, false);
                        showAlert('success', res.message);
                    },
                    error: function (xhr) {
                        handleFormErrors(xhr, '#assignRoleErrors');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text('Save Assigned Roles');
                    }
                });
            });

            // Handle Confirm Delete Admin
            $('#confirmDeleteAdminBtn').on('click', function () {
                if (!adminIdToDelete) return;
                const $btn = $(this);
                $btn.prop('disabled', true).text('Deleting...');

                $.ajax({
                    url: "{{ url('admin/admins') }}/" + adminIdToDelete,
                    method: "DELETE",
                    success: function (res) {
                        $('#deleteAdminModal').modal('hide');
                        adminsTable.ajax.reload(null, false);
                        showAlert('success', res.message);
                    },
                    error: function (xhr) {
                        $('#deleteAdminModal').modal('hide');
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error deleting admin.';
                        showAlert('danger', msg);
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text('Yes, Delete Admin');
                        adminIdToDelete = null;
                    }
                });
            });
        });

        // Open Edit Admin Modal
        function openEditAdminModal(adminId) {
            const admin = cachedAdminsData.find(a => a.id === adminId);
            if (!admin) return;

            $('#edit_admin_id').val(admin.id);
            $('#edit_admin_name').val(admin.name);
            $('#edit_admin_email').val(admin.email);
            $('#edit_admin_password').val('');
            $('#edit_admin_avatar').val('');
            removeAvatarPreview('#edit_admin_avatar', '#editAvatarPreview', '#editAvatarPreviewWrapper', '#editCurrentAvatarWrapper');
            
            // Set current avatar preview
            $('#editCurrentAvatarImg').attr('src', admin.avatar_url);
            $('#editCurrentAvatarWrapper').removeClass('d-none').addClass('d-flex');

            $('#editAdminModalTitle').text(admin.name);
            $('#editAdminErrors').addClass('d-none').html('');

            // Set single role checkbox
            const roleName = admin.role || (admin.roles && admin.roles[0]) || '';
            $('.edit-role-single-cb').each(function () {
                $(this).prop('checked', $(this).val() === roleName);
            });

            $('#editAdminModal').modal('show');
            if (window.feather) {
                feather.replace();
            }
        }

        // Open Assign Role Modal
        function openAssignRoleModal(adminId) {
            const admin = cachedAdminsData.find(a => a.id === adminId);
            if (!admin) return;

            $('#assign_role_admin_id').val(admin.id);
            $('#assignRoleModalAdminName').text(admin.name);
            $('#assignRoleErrors').addClass('d-none').html('');

            const currentRole = admin.role || (admin.roles && admin.roles[0]) || '';
            $('.assign-role-single-cb').each(function () {
                $(this).prop('checked', $(this).val() === currentRole);
            });

            $('#assignRoleModal').modal('show');
            if (window.feather) {
                feather.replace();
            }
        }

        // Open Delete Admin Modal
        function openDeleteAdminModal(adminId, adminName) {
            adminIdToDelete = adminId;
            $('#deleteAdminName').text(adminName);
            $('#deleteAdminModal').modal('show');
            if (window.feather) {
                feather.replace();
            }
        }

        // Toggle Block / Unblock Admin with SweetAlert2
        function toggleAdminBlock(adminId, shouldBlock, adminName) {
            const actionText = shouldBlock ? 'Block' : 'Unblock';
            const confirmBtnText = shouldBlock ? 'Yes, Block Admin' : 'Yes, Unblock Admin';
            const confirmBtnColor = shouldBlock ? '#dc2626' : '#16a34a';
            const iconType = shouldBlock ? 'warning' : 'question';
            const promptText = shouldBlock
                ? `Are you sure you want to block administrator "${adminName}"? They will be immediately prevented from logging into the administration portal.`
                : `Are you sure you want to unblock administrator "${adminName}"? Their portal access privileges will be restored.`;

            Swal.fire({
                title: `${actionText} Administrator?`,
                text: promptText,
                icon: iconType,
                showCancelButton: true,
                confirmButtonColor: confirmBtnColor,
                cancelButtonColor: '#64748b',
                confirmButtonText: confirmBtnText,
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                customClass: {
                    popup: 'rounded-4 shadow-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('admin/admins') }}/" + adminId + "/toggle-block",
                        method: "PATCH",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (res) {
                            adminsTable.ajax.reload(null, false);
                            Swal.fire({
                                icon: 'success',
                                title: shouldBlock ? 'Administrator Blocked!' : 'Administrator Unblocked!',
                                text: res.message,
                                timer: 2000,
                                showConfirmButton: false,
                                customClass: {
                                    popup: 'rounded-4'
                                }
                            });
                        },
                        error: function (xhr) {
                            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error updating admin status.';
                            Swal.fire({
                                icon: 'error',
                                title: 'Action Failed',
                                text: msg,
                                customClass: {
                                    popup: 'rounded-4'
                                }
                            });
                        }
                    });
                }
            });
        }

        // Ensure only one role checkbox can be checked in a given group
        function handleSingleRoleCheckbox(groupSelector, clickedCheckbox) {
            if (clickedCheckbox.checked) {
                $(groupSelector).not(clickedCheckbox).prop('checked', false);
            }
        }

        // Avatar Preview Handlers
        function previewAdminAvatar(input, previewImgSelector, previewWrapperSelector, currentWrapperSelector = null) {
            if (input.files && input.files[0]) {
                const file = input.files[0];

                // Validate file size (< 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    toastr.error('The selected image exceeds 2MB. Please choose a smaller image.', 'File Too Large');
                    removeAvatarPreview(input, previewImgSelector, previewWrapperSelector, currentWrapperSelector);
                    return;
                }

                // Validate file type
                if (!file.type.match('image.*')) {
                    toastr.error('Please choose a valid image file (JPEG, PNG, GIF, WebP).', 'Invalid File');
                    removeAvatarPreview(input, previewImgSelector, previewWrapperSelector, currentWrapperSelector);
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    $(previewImgSelector).attr('src', e.target.result);
                    $(previewWrapperSelector).removeClass('d-none').addClass('d-flex');
                    if (currentWrapperSelector) {
                        $(currentWrapperSelector).addClass('d-none').removeClass('d-flex');
                    }
                    if (window.feather) {
                        feather.replace();
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        function removeAvatarPreview(inputSelector, previewImgSelector, previewWrapperSelector, currentWrapperSelector = null) {
            $(inputSelector).val('');
            $(previewImgSelector).attr('src', '#');
            $(previewWrapperSelector).addClass('d-none').removeClass('d-flex');
            if (currentWrapperSelector) {
                $(currentWrapperSelector).removeClass('d-none').addClass('d-flex');
            }
        }

        function showAlert(type, message) {
            if (type === 'success') {
                toastr.success(message, 'Success');
            } else if (type === 'danger' || type === 'error') {
                toastr.error(message, 'Error');
            } else if (type === 'warning') {
                toastr.warning(message, 'Warning');
            } else {
                toastr.info(message, 'Notice');
            }
        }

        function handleFormErrors(xhr, errorContainerSelector) {
            let errorMsg = 'An error occurred. Please try again.';
            if (xhr.responseJSON) {
                if (xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else if (xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
            }
            $(errorContainerSelector).removeClass('d-none').html(errorMsg);
        }

        function escapeHtml(string) {
            if (!string) return '';
            return String(string)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
@endpush
