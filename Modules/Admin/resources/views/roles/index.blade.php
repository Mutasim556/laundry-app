@extends('admin::layouts.app')

@push('styles')
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/toastr/toastr.min.css') }}">
    <style>
        /* Modern Header Banner with Vibrant Gradient */
        .roles-hero-banner {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%);
            border-radius: 16px;
            padding: 24px 28px;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.25), 0 8px 10px -6px rgba(79, 70, 229, 0.2);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .roles-hero-banner::after {
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
        .roles-hero-title {
            color: #ffffff !important;
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }
        .roles-breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        .roles-breadcrumb .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.82) !important;
            font-weight: 500;
            transition: color 0.15s ease;
        }
        .roles-breadcrumb .breadcrumb-item a:hover {
            color: #ffffff !important;
            text-decoration: underline;
        }
        .roles-breadcrumb .breadcrumb-item.active {
            color: #ffffff !important;
            font-weight: 600;
        }
        .roles-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
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
        .card-roles {
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
            background: #ffffff;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        #rolesTable {
            font-size: 0.95rem;
            color: #0f172a;
        }
        #rolesTable thead th {
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
        #rolesTable tbody td {
            font-size: 0.96rem;
            font-weight: 600;
            color: #0f172a !important;
            padding: 16px 18px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
        }
        #rolesTable tbody tr:hover {
            background-color: #f8fafc;
        }
        .role-title-text {
            color: #0f172a !important;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: -0.01em;
        }
        .role-created-date {
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

        /* Crisp Badges */
        .role-badge-admin {
            background-color: #dbeafe !important;
            color: #1e40af !important;
            font-weight: 700 !important;
            border: 1.5px solid #93c5fd !important;
            padding: 6px 14px !important;
            border-radius: 9999px;
            font-size: 0.85rem !important;
            display: inline-flex;
            align-items: center;
        }
        .role-badge-web {
            background-color: #cffafe !important;
            color: #0e7490 !important;
            font-weight: 700 !important;
            border: 1.5px solid #67e8f9 !important;
            padding: 6px 14px !important;
            border-radius: 9999px;
            font-size: 0.85rem !important;
            display: inline-flex;
            align-items: center;
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
        .action-btn-perm {
            background: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd;
        }
        .action-btn-perm:hover {
            background: #0284c7;
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
        .btn-users-count {
            background-color: #eef2ff !important;
            color: #4338ca !important;
            border: 1.5px solid #c7d2fe !important;
            font-weight: 700 !important;
            font-size: 0.88rem !important;
            padding: 6px 14px !important;
            transition: all 0.2s ease;
        }
        .btn-users-count:hover {
            background-color: #4338ca !important;
            color: #ffffff !important;
            border-color: #4338ca !important;
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25);
        }
        .btn-perms-count {
            background-color: #f0fdf4 !important;
            color: #15803d !important;
            border: 1.5px solid #bbf7d0 !important;
            font-weight: 700 !important;
            font-size: 0.88rem !important;
            padding: 6px 14px !important;
            transition: all 0.2s ease;
        }
        .btn-perms-count:hover {
            background-color: #16a34a !important;
            color: #ffffff !important;
            border-color: #16a34a !important;
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
        }
        .badge-super-bypass {
            background-color: #fef2f2 !important;
            color: #b91c1c !important;
            border: 1.5px solid #fecaca !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
            padding: 6px 14px !important;
        }
        .action-btn:hover {
            transform: translateY(-2px);
        }
        .permission-checkbox-card {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            transition: all 0.2s ease;
            background: #ffffff;
        }
        .permission-checkbox-card:hover {
            border-color: #6366f1;
            background: #f5f7ff;
        }
        .permission-checkbox-card input:checked ~ span {
            color: #4338ca;
            font-weight: 700;
        }
        .users-count-btn {
            transition: all 0.2s ease;
            cursor: pointer;
            font-weight: 600;
        }
        .users-count-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        /* Attractive, Modern Form Inputs */
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
    </style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">
    <!-- Header Banner with Gradient Background -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="roles-hero-banner d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h3 class="roles-hero-title">Roles and Permissions Management</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb roles-breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Roles</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    @can('create-roles', 'admin')
                    <button type="button" class="btn btn-hero-add rounded-pill px-4 py-2 d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                        <i data-feather="plus-circle" class="feather-sm me-2"></i> Add New Role
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- AJAX Alert Notifications Container -->
    <div id="ajaxAlertContainer"></div>

    <!-- Roles DataTable Card -->
    <div class="row">
        <div class="col-12">
            <div class="card card-roles border-0">
                <div class="card-header bg-transparent border-bottom border-light px-4 py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Defined System Roles</h5>
                        <p class="text-muted small mb-0">Role name must be unique within the same guard.</p>
                    </div>
                    <span class="badge bg-light-primary text-primary rounded-pill px-3 py-2 fw-semibold fs-2" id="totalRolesBadge">
                        Roles
                    </span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <!-- Horizontally Scrollable Table Wrapper -->
                    <div class="table-responsive w-100">
                        <table id="rolesTable" class="table table-hover align-middle text-nowrap mb-0" style="width:100%;">
                            <thead>
                                <tr>
                                    <th class="border-top-0 px-3" style="width: 50px;">#</th>
                                    <th class="border-top-0 px-3">Role Name</th>
                                    <th class="border-top-0 px-3">Guard</th>
                                    <th class="border-top-0 px-3 text-center">Users (by Guard)</th>
                                    <th class="border-top-0 px-3">Assigned Permissions</th>
                                    <th class="border-top-0 px-3">Created Date</th>
                                    <th class="border-top-0 px-3 text-center" style="width: 150px;">Actions</th>
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

<!-- ================= 1. Create Role Modal ================= -->
<div class="modal fade" id="createRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i data-feather="shield" class="feather-sm me-2 text-primary"></i> Add New Role
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createRoleForm">
                @csrf
                <div class="modal-body">
                    <div id="createRoleErrors" class="alert alert-danger d-none border-0 rounded-3 mb-3"></div>
                    <div class="mb-3">
                        <label for="new_role_name" class="form-label fw-bold text-dark mb-1">Role Name</label>
                        <input
                            type="text"
                            class="form-control form-control-modern"
                            id="new_role_name"
                            name="name"
                            placeholder="e.g. manager, accountant, staff"
                            required
                        />
                    </div>
                    <div class="mb-3">
                        <label for="new_guard_name" class="form-label fw-bold text-dark mb-1">Select Guard</label>
                        <select class="form-select form-select-modern" id="new_guard_name" name="guard_name" required>
                            <option value="admin" selected>admin (Admin Portal Users)</option>
                            <option value="web">web (Frontend Application Users)</option>
                        </select>
                        <small class="text-muted d-block mt-1">Unique per guard: e.g. "manager" can exist in admin and "manager" in web independently.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="createRoleSubmitBtn" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                        Create Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= 2. Edit Role Modal ================= -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i data-feather="edit-2" class="feather-sm me-2 text-warning"></i> Edit Role: <span id="editModalRoleTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editRoleForm">
                @csrf
                <input type="hidden" id="edit_role_id" name="role_id" />
                <div class="modal-body">
                    <div id="editRoleErrors" class="alert alert-danger d-none border-0 rounded-3 mb-3"></div>
                    <div class="mb-3">
                        <label for="edit_role_name" class="form-label fw-bold text-dark mb-1">Role Name</label>
                        <input
                            type="text"
                            class="form-control form-control-modern"
                            id="edit_role_name"
                            name="name"
                            required
                        />
                    </div>
                    <div class="mb-3">
                        <label for="edit_guard_name" class="form-label fw-bold text-dark mb-1">Guard Name</label>
                        <select class="form-select form-select-modern" id="edit_guard_name" name="guard_name" required>
                            <option value="admin">admin (Admin Portal Users)</option>
                            <option value="web">web (Frontend Application Users)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="editRoleSubmitBtn" class="btn btn-warning text-white rounded-pill px-4 fw-semibold">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= 3. Manage Permissions Modal ================= -->
<div class="modal fade" id="permissionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">
                        <i data-feather="key" class="feather-sm me-2 text-info"></i> Manage Permissions: <span id="permModalRoleTitle"></span>
                    </h5>
                    <span class="badge bg-light-primary text-primary rounded-pill px-3 py-1 fw-semibold" id="permModalRoleGuard"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="permissionsForm">
                @csrf
                <input type="hidden" id="perm_role_id" name="role_id" />
                <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                    <div id="superAdminPermNotice" class="alert alert-info border-0 rounded-3 mb-0 d-none">
                        <i data-feather="info" class="feather-sm me-2"></i>
                        The <strong>super-admin</strong> role automatically has unrestricted access to all abilities across the entire application via Laravel Gate. Explicit permission assignment is not required.
                    </div>

                    <div id="normalPermSection">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom bg-light-subtle p-2 rounded-3">
                            <span class="text-dark fw-bold small">
                                Select permissions to assign (<span id="permGuardLabel" class="text-primary fw-bold"></span> guard):
                            </span>
                            <div class="form-check d-flex align-items-center gap-2 mb-0">
                                <input 
                                    type="checkbox" 
                                    class="form-check-input cursor-pointer" 
                                    id="selectAllPermissionsCheckbox" 
                                    style="width: 18px; height: 18px;" 
                                    onchange="handleSelectAllPermissions(this.checked)"
                                />
                                <label for="selectAllPermissionsCheckbox" class="form-check-label fw-bold text-primary cursor-pointer small mb-0 user-select-none">
                                    Select All Permissions
                                </label>
                            </div>
                        </div>

                        <!-- 1. Admin Guard Permissions Container -->
                        <div id="adminGuardPermissionsContainer" class="guard-perms-container">
                            @forelse ($adminPermissionsGrouped as $groupName => $groupPerms)
                                <div class="mb-4">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                                            <i data-feather="folder" class="feather-sm text-primary me-2"></i>
                                            {{ $groupName }}
                                        </h6>
                                        <div class="form-check d-flex align-items-center gap-2 mb-0">
                                            <input 
                                                type="checkbox" 
                                                class="form-check-input group-select-all-cb cursor-pointer" 
                                                id="group_cb_admin_{{ Str::slug($groupName) }}"
                                                style="width: 16px; height: 16px;"
                                                onchange="handleGroupSelectAll('admin_{{ Str::slug($groupName) }}', this.checked)"
                                            />
                                            <label for="group_cb_admin_{{ Str::slug($groupName) }}" class="form-check-label text-secondary fw-semibold cursor-pointer small mb-0 user-select-none" style="font-size: 0.8rem;">
                                                Select Group
                                            </label>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        @foreach ($groupPerms as $permission)
                                            <div class="col-md-6 col-lg-4">
                                                <label class="permission-checkbox-card d-flex align-items-center gap-2 cursor-pointer w-100 mb-0">
                                                    <input
                                                        type="checkbox"
                                                        class="form-check-input mt-0 perm-check perm-guard-admin perm-group-admin_{{ Str::slug($groupName) }}"
                                                        data-group="admin_{{ Str::slug($groupName) }}"
                                                        data-guard="admin"
                                                        name="permissions[]"
                                                        value="{{ $permission->name }}"
                                                        id="perm_check_admin_{{ $permission->name }}"
                                                        onchange="updateSelectAllStates()"
                                                    />
                                                    <span class="fs-2 text-dark font-monospace fw-medium">{{ $permission->name }}</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    No admin permissions found. Seed them using <code>php artisan db:seed --class=AdminPermissionSeeder</code>.
                                </div>
                            @endforelse
                        </div>

                        <!-- 2. Web Guard Permissions Container -->
                        <div id="webGuardPermissionsContainer" class="guard-perms-container d-none">
                            @forelse ($webPermissionsGrouped as $groupName => $groupPerms)
                                <div class="mb-4">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                                            <i data-feather="folder" class="feather-sm text-info me-2"></i>
                                            {{ $groupName }}
                                        </h6>
                                        <div class="form-check d-flex align-items-center gap-2 mb-0">
                                            <input 
                                                type="checkbox" 
                                                class="form-check-input group-select-all-cb cursor-pointer" 
                                                id="group_cb_web_{{ Str::slug($groupName) }}"
                                                style="width: 16px; height: 16px;"
                                                onchange="handleGroupSelectAll('web_{{ Str::slug($groupName) }}', this.checked)"
                                            />
                                            <label for="group_cb_web_{{ Str::slug($groupName) }}" class="form-check-label text-secondary fw-semibold cursor-pointer small mb-0 user-select-none" style="font-size: 0.8rem;">
                                                Select Group
                                            </label>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        @foreach ($groupPerms as $permission)
                                            <div class="col-md-6 col-lg-4">
                                                <label class="permission-checkbox-card d-flex align-items-center gap-2 cursor-pointer w-100 mb-0">
                                                    <input
                                                        type="checkbox"
                                                        class="form-check-input mt-0 perm-check perm-guard-web perm-group-web_{{ Str::slug($groupName) }}"
                                                        data-group="web_{{ Str::slug($groupName) }}"
                                                        data-guard="web"
                                                        name="permissions[]"
                                                        value="{{ $permission->name }}"
                                                        id="perm_check_web_{{ $permission->name }}"
                                                        onchange="updateSelectAllStates()"
                                                    />
                                                    <span class="fs-2 text-dark font-monospace fw-medium">{{ $permission->name }}</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    No web permissions found. Seed them using <code>php artisan db:seed --class=WebPermissionSeeder</code>.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="savePermissionsSubmitBtn" class="btn btn-info rounded-pill px-4 fw-semibold text-white">
                        Save Permissions
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= 4. View Users by Role Modal ================= -->
<div class="modal fade" id="roleUsersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">
                        <i data-feather="users" class="feather-sm me-2 text-primary"></i> Assigned Users: <span id="usersModalRoleTitle"></span>
                    </h5>
                    <span class="badge bg-light-primary text-primary rounded-pill px-3 py-1 fw-semibold" id="usersModalGuardBadge"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                <div id="usersModalLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted small mt-2">Fetching users for this role...</div>
                </div>
                <div id="usersModalContent" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-top-0 px-3 fw-bold text-dark">User</th>
                                    <th class="border-top-0 px-3 fw-bold text-dark">Email</th>
                                    <th class="border-top-0 px-3 fw-bold text-dark">Assigned Since</th>
                                </tr>
                            </thead>
                            <tbody id="roleUsersTableBody">
                                <!-- Users appended via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="usersModalEmpty" class="text-center py-4 d-none text-muted">
                    <i data-feather="user-x" class="feather-lg mb-2 text-muted"></i>
                    <div>No users currently hold this role in the specified guard.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= 5. Delete Role Confirmation Modal ================= -->
<div class="modal fade" id="deleteRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i data-feather="alert-triangle" class="feather-sm me-2 text-danger"></i> Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="bg-light-danger text-danger rounded-circle p-3 d-inline-flex mb-3">
                    <i data-feather="trash-2" style="width: 32px; height: 32px;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Delete Role "<span id="deleteRoleName"></span>"?</h5>
                <p class="text-muted mb-0 small">Are you sure you want to permanently delete this role? Any users holding this role will lose its permissions.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmDeleteRoleBtn" class="btn btn-danger rounded-pill px-4 text-white fw-semibold">
                    Yes, Delete Role
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
    <script>
        const canEditRole = @json(auth('admin')->user()->can('edit-roles', 'admin'));
        const canDeleteRole = @json(auth('admin')->user()->can('delete-roles', 'admin'));

        let rolesTable;
        let cachedRolesData = [];

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
            rolesTable = $('#rolesTable').DataTable({
                ajax: {
                    url: "{{ route('admin.roles.data') }}",
                    dataSrc: function (json) {
                        cachedRolesData = json.data;
                        $('#totalRolesBadge').text('Total Roles: ' + json.data.length);
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
                            let badge = row.is_super_admin 
                                ? '<div class="mt-1"><span class="badge bg-danger text-white rounded-pill px-2 py-1 fw-bold" style="font-size: 0.72rem; letter-spacing: 0.02em;">SUPER ADMIN (ALL ACCESS)</span></div>' 
                                : '';
                            return `
                                <div class="d-flex align-items-center">
                                    <div class="bg-indigo-subtle text-primary border border-primary-subtle rounded-circle p-2 d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 42px; height: 42px; min-width: 42px; background: #eef2ff;">
                                        <i data-feather="shield" style="width: 20px; height: 20px; color: #4f46e5;"></i>
                                    </div>
                                    <div>
                                        <div class="role-title-text mb-0">${escapeHtml(row.name)}</div>
                                        ${badge}
                                    </div>
                                </div>
                            `;
                        },
                        className: "px-3 align-middle"
                    },
                    {
                        data: 'guard_name',
                        render: function (data) {
                            if (data === 'admin') {
                                return `<span class="badge role-badge-admin"><i data-feather="lock" style="width:13px;height:13px;" class="me-1"></i> admin</span>`;
                            }
                            return `<span class="badge role-badge-web"><i data-feather="globe" style="width:13px;height:13px;" class="me-1"></i> web</span>`;
                        },
                        className: "px-3 align-middle"
                    },
                    {
                        data: 'users_count',
                        render: function (data, type, row) {
                            return `
                                <button type="button" class="btn btn-sm btn-users-count rounded-pill px-3 shadow-sm" onclick="showRoleUsersModal(${row.id})" title="Click to view assigned users">
                                    <i data-feather="users" style="width: 14px; height: 14px;" class="me-1"></i> ${data} ${data === 1 ? 'User' : 'Users'}
                                </button>
                            `;
                        },
                        className: "px-3 text-center align-middle"
                    },
                    {
                        data: 'permissions_count',
                        render: function (data, type, row) {
                            if (row.is_super_admin) {
                                return `<span class="badge badge-super-bypass rounded-pill shadow-sm"><i data-feather="check-circle" style="width:14px;height:14px;" class="me-1"></i> All Permissions (Gate Bypass)</span>`;
                            }
                            if (canEditRole) {
                                return `
                                    <button type="button" class="btn btn-sm btn-perms-count rounded-pill px-3 shadow-sm" onclick="openPermissionsModal(${row.id})" title="Manage permissions">
                                        <i data-feather="key" style="width: 14px; height: 14px;" class="me-1"></i> ${data} Permissions
                                    </button>
                                `;
                            }
                            return `<span class="badge bg-light text-dark border px-3 py-2 rounded-pill"><i data-feather="key" style="width: 14px; height: 14px;" class="me-1"></i> ${data} Permissions</span>`;
                        },
                        className: "px-3 align-middle"
                    },
                    {
                        data: 'created_at',
                        render: function (data) {
                            return `<span class="role-created-date">${escapeHtml(data)}</span>`;
                        },
                        className: "px-3 align-middle"
                    },
                    {
                        data: null,
                        render: function (data, type, row) {
                            const isSuper = row.is_super_admin;
                            let actions = '<div class="d-inline-flex gap-2">';

                            if (canEditRole) {
                                actions += `
                                    <button type="button" class="action-btn action-btn-perm shadow-sm" onclick="openPermissionsModal(${row.id})" title="Manage Permissions">
                                        <i data-feather="key" style="width: 16px; height: 16px;"></i>
                                    </button>
                                    <button type="button" class="action-btn action-btn-edit shadow-sm" onclick="openEditRoleModal(${row.id})" title="Edit Role" ${isSuper ? 'disabled style="opacity: 0.4; cursor: not-allowed;"' : ''}>
                                        <i data-feather="edit-2" style="width: 16px; height: 16px;"></i>
                                    </button>
                                `;
                            }

                            if (canDeleteRole) {
                                actions += `
                                    <button type="button" class="action-btn action-btn-delete shadow-sm" onclick="openDeleteRoleModal(${row.id}, '${escapeHtml(row.name)}')" title="Delete Role" ${isSuper ? 'disabled style="opacity: 0.4; cursor: not-allowed;"' : ''}>
                                        <i data-feather="trash-2" style="width: 16px; height: 16px;"></i>
                                    </button>
                                `;
                            }

                            if (!canEditRole && !canDeleteRole) {
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
                    searchPlaceholder: "Search roles...",
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

            // Auto-slugify role name inputs (lowercase and spaces replaced with hyphen)
            function slugify(text) {
                return text
                    .toString()
                    .toLowerCase()
                    .trim()
                    .replace(/\s+/g, '-')       // Replace spaces with -
                    .replace(/&/g, '-and-')     // Replace & with 'and'
                    .replace(/[^\w\-]+/g, '')   // Remove all non-word chars except hyphen
                    .replace(/\-\-+/g, '-');    // Replace multiple - with single -
            }

            $('#new_role_name, #edit_role_name').on('input', function () {
                const cursorPos = this.selectionStart;
                const oldVal = $(this).val();
                const newVal = oldVal
                    .toLowerCase()
                    .replace(/\s+/g, '-')
                    .replace(/[^a-z0-9\-_]/g, '');
                
                $(this).val(newVal);
            });

            // Handle AJAX Create Role Form
            $('#createRoleForm').on('submit', function (e) {
                e.preventDefault();
                const $btn = $('#createRoleSubmitBtn');
                $btn.prop('disabled', true).text('Creating...');
                $('#createRoleErrors').addClass('d-none').html('');

                $.ajax({
                    url: "{{ route('admin.roles.store') }}",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function (res) {
                        $('#createRoleModal').modal('hide');
                        $('#createRoleForm')[0].reset();
                        rolesTable.ajax.reload(null, false);
                        showAlert('success', res.message);
                    },
                    error: function (xhr) {
                        handleFormErrors(xhr, '#createRoleErrors');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text('Create Role');
                    }
                });
            });

            // Handle AJAX Edit Role Form
            $('#editRoleForm').on('submit', function (e) {
                e.preventDefault();
                const roleId = $('#edit_role_id').val();
                const $btn = $('#editRoleSubmitBtn');
                $btn.prop('disabled', true).text('Saving...');
                $('#editRoleErrors').addClass('d-none').html('');

                $.ajax({
                    url: "{{ url('admin/roles') }}/" + roleId,
                    method: "PUT",
                    data: $(this).serialize(),
                    success: function (res) {
                        $('#editRoleModal').modal('hide');
                        rolesTable.ajax.reload(null, false);
                        showAlert('success', res.message);
                    },
                    error: function (xhr) {
                        handleFormErrors(xhr, '#editRoleErrors');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text('Save Changes');
                    }
                });
            });

            // Handle AJAX Permissions Form
            $('#permissionsForm').on('submit', function (e) {
                e.preventDefault();
                const roleId = $('#perm_role_id').val();
                const $btn = $('#savePermissionsSubmitBtn');
                $btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: "{{ url('admin/roles') }}/" + roleId + "/permissions",
                    method: "PUT",
                    data: $(this).serialize(),
                    success: function (res) {
                        $('#permissionsModal').modal('hide');
                        rolesTable.ajax.reload(null, false);
                        showAlert('success', res.message);
                    },
                    error: function (xhr) {
                        showAlert('danger', 'Failed to update permissions.');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text('Save Permissions');
                    }
                });
            });

            // Handle AJAX Delete Role
            let roleIdToDelete = null;
            window.openDeleteRoleModal = function (roleId, roleName) {
                roleIdToDelete = roleId;
                $('#deleteRoleName').text(roleName);
                $('#deleteRoleModal').modal('show');
            };

            $('#confirmDeleteRoleBtn').on('click', function () {
                if (!roleIdToDelete) return;
                const $btn = $(this);
                $btn.prop('disabled', true).text('Deleting...');

                $.ajax({
                    url: "{{ url('admin/roles') }}/" + roleIdToDelete,
                    method: "DELETE",
                    success: function (res) {
                        $('#deleteRoleModal').modal('hide');
                        rolesTable.ajax.reload(null, false);
                        showAlert('success', res.message);
                    },
                    error: function (xhr) {
                        $('#deleteRoleModal').modal('hide');
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error deleting role.';
                        showAlert('danger', msg);
                    },
                    complete: function () {
                        $btn.prop('disabled', false).text('Yes, Delete Role');
                        roleIdToDelete = null;
                    }
                });
            });
        });

        // Open Edit Modal
        function openEditRoleModal(roleId) {
            const role = cachedRolesData.find(r => r.id === roleId);
            if (!role) return;

            $('#edit_role_id').val(role.id);
            $('#edit_role_name').val(role.name);
            $('#edit_guard_name').val(role.guard_name);
            $('#editModalRoleTitle').text(role.name);
            $('#editRoleErrors').addClass('d-none').html('');
            $('#editRoleModal').modal('show');
        }

        // Open Permissions Modal
        function openPermissionsModal(roleId) {
            const role = cachedRolesData.find(r => r.id === roleId);
            if (!role) return;

            $('#perm_role_id').val(role.id);
            $('#permModalRoleTitle').text(role.name);
            $('#permModalRoleGuard').text('Guard: ' + role.guard_name);
            $('#permGuardLabel').text(role.guard_name);

            // Filter permission containers according to role's guard strictly
            if (role.guard_name === 'admin') {
                $('#adminGuardPermissionsContainer').removeClass('d-none');
                $('#webGuardPermissionsContainer').addClass('d-none');
            } else {
                $('#webGuardPermissionsContainer').removeClass('d-none');
                $('#adminGuardPermissionsContainer').addClass('d-none');
            }

            if (role.is_super_admin) {
                $('#superAdminPermNotice').removeClass('d-none');
                $('#normalPermSection').addClass('d-none');
                $('#savePermissionsSubmitBtn').addClass('d-none');
            } else {
                $('#superAdminPermNotice').addClass('d-none');
                $('#normalPermSection').removeClass('d-none');
                $('#savePermissionsSubmitBtn').removeClass('d-none');

                // Check or uncheck permissions based on role's assigned permissions and guard
                $('.perm-check').prop('checked', false);
                $('.perm-guard-' + role.guard_name).each(function () {
                    const permName = $(this).val();
                    $(this).prop('checked', role.permission_names.includes(permName));
                });

                // Update select-all and group-select-all checkboxes state
                updateSelectAllStates();
            }

            $('#permissionsModal').modal('show');
            if (window.feather) {
                feather.replace();
            }
        }

        // Show Users by Role Modal
        function showRoleUsersModal(roleId) {
            const role = cachedRolesData.find(r => r.id === roleId);
            const roleName = role ? role.name : '';
            const guardName = role ? role.guard_name : '';

            $('#usersModalRoleTitle').text(roleName);
            $('#usersModalGuardBadge').text('Guard: ' + guardName);
            $('#usersModalLoading').removeClass('d-none');
            $('#usersModalContent').addClass('d-none');
            $('#usersModalEmpty').addClass('d-none');
            $('#roleUsersTableBody').empty();

            $('#roleUsersModal').modal('show');

            $.ajax({
                url: "{{ url('admin/roles') }}/" + roleId + "/users",
                method: "GET",
                success: function (res) {
                    $('#usersModalLoading').addClass('d-none');
                    if (res.users && res.users.length > 0) {
                        res.users.forEach(function (user) {
                            $('#roleUsersTableBody').append(`
                                <tr>
                                    <td class="px-3">
                                        <div class="d-flex align-items-center">
                                            <img src="${user.avatar_url}" alt="${escapeHtml(user.name)}" class="rounded-circle object-fit-cover me-2 shadow-sm" width="34" height="34" />
                                            <span class="fw-semibold text-dark">${escapeHtml(user.name)}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 text-muted">${escapeHtml(user.email)}</td>
                                    <td class="px-3 text-muted">${escapeHtml(user.created_at)}</td>
                                </tr>
                            `);
                        });
                        $('#usersModalContent').removeClass('d-none');
                    } else {
                        $('#usersModalEmpty').removeClass('d-none');
                    }
                    if (window.feather) {
                        feather.replace();
                    }
                },
                error: function () {
                    $('#usersModalLoading').addClass('d-none');
                    $('#usersModalEmpty').removeClass('d-none').text('Failed to load users.');
                }
            });
        }

        function handleSelectAllPermissions(isChecked) {
            const activeContainer = $('.guard-perms-container:not(.d-none)');
            activeContainer.find('.perm-check').prop('checked', isChecked);
            activeContainer.find('.group-select-all-cb').prop('checked', isChecked);
            updateSelectAllStates();
        }

        function handleGroupSelectAll(groupSlug, isChecked) {
            $('.perm-group-' + groupSlug).prop('checked', isChecked);
            updateSelectAllStates();
        }

        function updateSelectAllStates() {
            const activeContainer = $('.guard-perms-container:not(.d-none)');
            const allPerms = activeContainer.find('.perm-check');
            const checkedPerms = activeContainer.find('.perm-check:checked');
            const selectAllCb = $('#selectAllPermissionsCheckbox');

            if (allPerms.length > 0 && allPerms.length === checkedPerms.length) {
                selectAllCb.prop('checked', true);
                selectAllCb.prop('indeterminate', false);
            } else if (checkedPerms.length > 0) {
                selectAllCb.prop('checked', false);
                selectAllCb.prop('indeterminate', true);
            } else {
                selectAllCb.prop('checked', false);
                selectAllCb.prop('indeterminate', false);
            }

            // Check each group inside the active guard container
            activeContainer.find('.group-select-all-cb').each(function () {
                const groupSlug = $(this).attr('id').replace('group_cb_', '');
                const groupPerms = $('.perm-group-' + groupSlug);
                const groupChecked = $('.perm-group-' + groupSlug + ':checked');

                if (groupPerms.length > 0 && groupPerms.length === groupChecked.length) {
                    $(this).prop('checked', true);
                    $(this).prop('indeterminate', false);
                } else if (groupChecked.length > 0) {
                    $(this).prop('checked', false);
                    $(this).prop('indeterminate', true);
                } else {
                    $(this).prop('checked', false);
                    $(this).prop('indeterminate', false);
                }
            });
        }

        function toggleAllModalPermissions() {
            const checkboxes = document.querySelectorAll('.perm-check');
            const anyUnchecked = Array.from(checkboxes).some(cb => !cb.checked);
            checkboxes.forEach(cb => {
                cb.checked = anyUnchecked;
            });
            updateSelectAllStates();
        }

        function toggleGroupModalPermissions(groupSlug) {
            const checkboxes = document.querySelectorAll('.perm-group-' + groupSlug);
            const anyUnchecked = Array.from(checkboxes).some(cb => !cb.checked);
            checkboxes.forEach(cb => {
                cb.checked = anyUnchecked;
            });
            updateSelectAllStates();
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
