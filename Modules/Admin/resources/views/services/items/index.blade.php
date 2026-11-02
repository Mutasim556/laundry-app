@extends('admin::layouts.app')

@push('styles')
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/toastr/toastr.min.css') }}">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.min.css') }}">
    <style>
        /* Modern Header Banner with Vibrant Indigo-Violet Gradient */
        .items-hero-banner {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%);
            border-radius: 16px;
            padding: 24px 28px;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.25), 0 8px 10px -6px rgba(79, 70, 229, 0.2);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .items-hero-banner::after {
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
        .items-hero-title {
            color: #ffffff !important;
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }
        .items-breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        .items-breadcrumb .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.82) !important;
            font-weight: 500;
            transition: color 0.15s ease;
        }
        .items-breadcrumb .breadcrumb-item a:hover {
            color: #ffffff !important;
            text-decoration: underline;
        }
        .items-breadcrumb .breadcrumb-item.active {
            color: #ffffff !important;
            font-weight: 600;
        }
        .items-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
            color: rgba(255, 255, 255, 0.55);
            content: "/";
            padding: 0 8px;
        }

        .btn-hero-add {
            background: #ffffff !important;
            color: #4f46e5 !important;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-hero-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
            color: #4338ca !important;
        }

        /* Preset quick suggestion badges */
        .preset-badge {
            cursor: pointer;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            color: #334155;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            user-select: none;
        }
        .preset-badge:hover {
            border-color: #6366f1;
            background: #eef2ff;
            color: #4f46e5;
            transform: translateY(-1px);
        }

        /* Item Media Avatar in DataTable (Image or Icon) */
        .item-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #f1f5f9;
            color: #4f46e5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            transition: all 0.2s ease;
            flex-shrink: 0;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .item-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .item-avatar i {
            line-height: 1;
        }
        tr:hover .item-avatar {
            border-color: #6366f1;
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.15);
        }

        /* Category badge in table */
        .category-pill {
            background-color: #f1f5f9;
            color: #334155;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid #e2e8f0;
        }

        /* Status Badge */
        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .status-badge.active {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .status-badge.inactive {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }
        .status-badge.active .status-dot { background-color: #10b981; }
        .status-badge.inactive .status-dot { background-color: #ef4444; }

        /* Action Buttons */
        .action-btn-edit,
        .action-btn-toggle,
        .action-btn-delete {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: none;
            transition: all 0.15s ease;
        }
        .action-btn-edit {
            background-color: #eff6ff;
            color: #2563eb;
        }
        .action-btn-edit:hover {
            background-color: #2563eb;
            color: #ffffff;
        }
        .action-btn-toggle {
            background-color: #fef3c7;
            color: #d97706;
        }
        .action-btn-toggle:hover {
            background-color: #d97706;
            color: #ffffff;
        }
        .action-btn-delete {
            background-color: #fef2f2;
            color: #dc2626;
        }
        .action-btn-delete:hover {
            background-color: #dc2626;
            color: #ffffff;
        }

        /* Modal styling enhancements */
        .modal-item-dialog {
            max-width: 660px;
        }
        .modal-item-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .modal-header-hero {
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 22px;
        }
        .modal-body-padded {
            padding: 20px 22px;
        }
        .modal-footer-padded {
            padding: 14px 22px 18px;
            border-top: 1px solid #f1f5f9;
        }
        .modal-item-card .form-control,
        .modal-item-card .form-select,
        .modal-item-card .input-group-text {
            font-size: 0.88rem;
            padding: 7px 12px;
        }
        .form-section-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        /* Image Dropzone & Preview Styles */
        .image-upload-wrapper {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            padding: 10px;
            text-align: center;
            transition: all 0.2s ease;
            position: relative;
        }
        .image-upload-wrapper:hover {
            border-color: #6366f1;
            background: #f5f7ff;
        }
        .image-preview-box {
            width: 64px;
            height: 64px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            margin-bottom: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        .image-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .image-preview-box i {
            font-size: 1.8rem;
            color: #94a3b8;
        }

        /* Icon picker pills inside modal */
        .icon-option-pill {
            cursor: pointer;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 5px 9px;
            font-size: 0.76rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #fff;
            color: #475569;
            transition: all 0.15s ease;
        }
        .icon-option-pill:hover,
        .icon-option-pill.selected {
            background: #eef2ff;
            border-color: #6366f1;
            color: #4f46e5;
            font-weight: 600;
            transform: translateY(-1px);
        }

        /* Slug input badge & indicators */
        .slug-status-indicator {
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
        }
        .slug-status-indicator.is-checking { color: #64748b; }
        .slug-status-indicator.is-unique { color: #059669; font-weight: 600; }
        .slug-status-indicator.is-auto { color: #4f46e5; }

        /* DataTables card & styling */
        .items-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }
        .table > :not(caption) > * > * {
            padding: 14px 16px;
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Hero Banner Header -->
    <div class="items-hero-banner mb-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 position-relative" style="z-index: 1;">
            <div>
                <h3 class="items-hero-title">
                    <i class="mdi mdi-tshirt-crew me-2"></i>Laundry Items
                </h3>
                <nav aria-label="breadcrumb">
                    <ol class="items-breadcrumb breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Services</li>
                        <li class="breadcrumb-item active" aria-current="page">Items</li>
                    </ol>
                </nav>
            </div>
            <div>
                @can('create-items', 'admin')
                    <button type="button" class="btn btn-hero-add" data-bs-toggle="modal" data-bs-target="#createItemModal">
                        <i class="mdi mdi-plus-circle-outline fs-5"></i>
                        <span>Add New Item</span>
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Quick Suggestion / Common Laundry Items Bar -->
    <div class="card border-0 rounded-4 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small fw-semibold me-1">
                    <i class="mdi mdi-lightbulb-on-outline text-warning me-1"></i>Common Laundry Items:
                </span>
                <span class="preset-badge" data-name="Shirt" data-icon="mdi-tshirt-crew" data-category="Shirts & Tops" data-desc="Formal and casual collared shirts">
                    <i class="mdi mdi-tshirt-crew text-primary"></i> Shirt
                </span>
                <span class="preset-badge" data-name="T-shirt" data-icon="mdi-tshirt-crew" data-category="Shirts & Tops" data-desc="Casual round-neck or polo tees">
                    <i class="mdi mdi-tshirt-crew text-primary"></i> T-shirt
                </span>
                <span class="preset-badge" data-name="Suit Jacket" data-icon="mdi-tie" data-category="Suits & Formal" data-desc="Tailored formal suit jacket or blazer">
                    <i class="mdi mdi-tie" style="color: #9333ea;"></i> Suit Jacket
                </span>
                <span class="preset-badge" data-name="Bedsheet" data-icon="mdi-hotel" data-category="Bedding" data-desc="Cotton or linen single/double bedsheets">
                    <i class="mdi mdi-hotel text-warning"></i> Bedsheet
                </span>
                <span class="preset-badge" data-name="Jeans & Trousers" data-icon="mdi-human-male" data-category="Trousers" data-desc="Denim jeans and casual or formal pants">
                    <i class="mdi mdi-human-male text-success"></i> Trousers
                </span>
                <span class="preset-badge" data-name="Evening Dress" data-icon="mdi-human-female" data-category="Dresses" data-desc="Party dress, gown, or ethnic wear">
                    <i class="mdi mdi-human-female text-danger"></i> Dress
                </span>
                <span class="preset-badge" data-name="Winter Coat" data-icon="mdi-hanger" data-category="Outerwear" data-desc="Heavy winter jacket, trench coat, or parka">
                    <i class="mdi mdi-hanger text-info"></i> Winter Coat
                </span>
            </div>
        </div>
    </div>

    <!-- Items DataTable Card with Category Filter -->
    <div class="card items-card">
        <div class="card-body p-4">
            <!-- Filter Bar -->
            <div class="row align-items-center g-3 mb-3">
                <div class="col-md-4">
                    <label for="filter_category_id" class="form-label small fw-semibold text-muted mb-1">
                        <i class="mdi mdi-filter-variant me-1"></i>Filter by Category:
                    </label>
                    <select id="filter_category_id" class="form-select form-select-sm rounded-3">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table id="itemsTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th style="width: 130px;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Item Modal -->
<div class="modal fade" id="createItemModal" tabindex="-1" aria-labelledby="createItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-item-dialog">
        <div class="modal-content modal-item-card">
            <div class="modal-header modal-header-hero">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white p-2 rounded-3 shadow-sm">
                        <i class="mdi mdi-tshirt-crew fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="createItemModalLabel">Add Laundry Item</h5>
                        <p class="text-muted small mb-0">Create an item assigned to an item category.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createItemForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body modal-body-padded">
                    <!-- Basic Information -->
                    <div class="form-section-title">
                        <i class="mdi mdi-information-outline"></i> Basic Information
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="create_item_category_id" class="form-label fw-semibold small mb-1">
                                Item Category <span class="text-danger">*</span>
                            </label>
                            <select class="form-select rounded-3" id="create_item_category_id" name="item_category_id" required>
                                <option value="" disabled selected>Select Category...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" data-icon="{{ $category->icon }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="create_name" class="form-label fw-semibold small mb-1">
                                Item Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control rounded-3" id="create_name" name="name" placeholder="e.g., Shirt, T-shirt, Bedsheet" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="create_slug" class="form-label fw-semibold small mb-0">Unique Slug</label>
                                <span class="badge bg-light text-primary font-monospace" style="font-size: 0.72rem;">auto-generated</span>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="mdi mdi-link-variant"></i></span>
                                <input type="text" class="form-control font-monospace" id="create_slug" name="slug" placeholder="e.g., shirt, suit-jacket">
                            </div>
                            <div id="create_slug_feedback" class="slug-status-indicator is-auto">
                                <i class="mdi mdi-magic-staff"></i> Auto-generates unique slug from item name
                            </div>
                        </div>
                    </div>

                    <!-- Item Media: Image Upload & Icon Fallback -->
                    <div class="form-section-title">
                        <i class="mdi mdi-image-multiple-outline"></i> Item Media (Image & Fallback Icon)
                    </div>
                    <div class="row g-3 mb-3">
                        <!-- Image Upload Column -->
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small mb-1">Item Image (Optional)</label>
                            <div class="image-upload-wrapper">
                                <div class="image-preview-box" id="create_image_preview_box">
                                    <i class="mdi mdi-image-plus"></i>
                                </div>
                                <div class="mt-1">
                                    <input type="file" class="form-control form-control-sm" id="create_image_input" name="image" accept="image/jpeg,image/png,image/jpg,image/webp,image/svg+xml">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1 px-1">
                                    <small class="text-muted" style="font-size: 0.72rem;">Max: 2MB</small>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0 d-none" id="create_clear_image_btn" style="font-size: 0.75rem;">Clear</button>
                                </div>
                            </div>
                        </div>
                        <!-- Icon Fallback Column -->
                        <div class="col-md-7">
                            <label for="create_icon" class="form-label fw-semibold small mb-1">Fallback Icon (used if no image)</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-light" id="create_icon_preview"><i class="mdi mdi-tag-text-outline"></i></span>
                                <input type="text" class="form-control rounded-end-3" id="create_icon" name="icon" value="mdi-tshirt-crew" placeholder="mdi-tshirt-crew">
                            </div>
                            <small class="text-muted d-block mb-1" style="font-size: 0.76rem;">Quick presets:</small>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-tshirt-crew"><i class="mdi mdi-tshirt-crew text-primary"></i> Tops</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-human-male"><i class="mdi mdi-human-male text-success"></i> Pants</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-human-female"><i class="mdi mdi-human-female text-danger"></i> Dress</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-hanger"><i class="mdi mdi-hanger text-info"></i> Outerwear</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-hotel"><i class="mdi mdi-hotel text-warning"></i> Bedding</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-tie"><i class="mdi mdi-tie" style="color: #9333ea;"></i> Formal</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-blinds"><i class="mdi mdi-blinds text-secondary"></i> Curtains</span>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Details Section -->
                    <div class="form-section-title">
                        <i class="mdi mdi-text-box-outline"></i> Additional Details
                    </div>
                    <div class="mb-3">
                        <label for="create_description" class="form-label fw-semibold small mb-1">Description</label>
                        <textarea class="form-control rounded-3" id="create_description" name="description" rows="2" placeholder="Brief note about this laundry item..."></textarea>
                    </div>
                    <div class="bg-light p-2 px-3 rounded-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fw-semibold d-block text-dark small">Active Status</span>
                            <small class="text-muted" style="font-size: 0.76rem;">Item is available for laundry pricing and bookings.</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="create_is_active" name="is_active" value="1" checked>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-padded d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-3 px-3 py-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 py-1 fw-semibold" id="btnSaveItem">
                        <span class="btn-spinner spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <span>Save Item</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-item-dialog">
        <div class="modal-content modal-item-card">
            <div class="modal-header modal-header-hero">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white p-2 rounded-3 shadow-sm">
                        <i class="mdi mdi-pencil-outline fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="editItemModalLabel">Edit Laundry Item</h5>
                        <p class="text-muted small mb-0">Update item details, category, image, and unique slug.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editItemForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_item_id" name="id">
                <input type="hidden" id="edit_remove_image" name="remove_image" value="0">
                <div class="modal-body modal-body-padded">
                    <!-- Basic Information -->
                    <div class="form-section-title">
                        <i class="mdi mdi-information-outline"></i> Basic Information
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit_item_category_id" class="form-label fw-semibold small mb-1">
                                Item Category <span class="text-danger">*</span>
                            </label>
                            <select class="form-select rounded-3" id="edit_item_category_id" name="item_category_id" required>
                                <option value="" disabled>Select Category...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_name" class="form-label fw-semibold small mb-1">
                                Item Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control rounded-3" id="edit_name" name="name" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="edit_slug" class="form-label fw-semibold small mb-0">Unique Slug</label>
                                <span class="badge bg-light text-primary font-monospace" style="font-size: 0.72rem;">auto-syncs</span>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="mdi mdi-link-variant"></i></span>
                                <input type="text" class="form-control font-monospace" id="edit_slug" name="slug">
                            </div>
                            <div id="edit_slug_feedback" class="slug-status-indicator is-auto">
                                <i class="mdi mdi-check-circle-outline text-success"></i> Unique slug ready
                            </div>
                        </div>
                    </div>

                    <!-- Item Media: Image Upload & Icon Fallback -->
                    <div class="form-section-title">
                        <i class="mdi mdi-image-multiple-outline"></i> Item Media (Image & Fallback Icon)
                    </div>
                    <div class="row g-3 mb-3">
                        <!-- Image Upload Column -->
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small mb-1">Item Image (Optional)</label>
                            <div class="image-upload-wrapper">
                                <div class="image-preview-box" id="edit_image_preview_box">
                                    <i class="mdi mdi-image-plus"></i>
                                </div>
                                <div class="mt-1">
                                    <input type="file" class="form-control form-control-sm" id="edit_image_input" name="image" accept="image/jpeg,image/png,image/jpg,image/webp,image/svg+xml">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1 px-1">
                                    <small class="text-muted" style="font-size: 0.72rem;">Max: 2MB</small>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0 d-none" id="edit_remove_image_btn" style="font-size: 0.75rem;">Remove</button>
                                </div>
                            </div>
                        </div>
                        <!-- Icon Fallback Column -->
                        <div class="col-md-7">
                            <label for="edit_icon" class="form-label fw-semibold small mb-1">Fallback Icon (used if no image)</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-light" id="edit_icon_preview"><i class="mdi mdi-tag-text-outline"></i></span>
                                <input type="text" class="form-control rounded-end-3" id="edit_icon" name="icon">
                            </div>
                            <small class="text-muted d-block mb-1" style="font-size: 0.76rem;">Quick presets:</small>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-tshirt-crew"><i class="mdi mdi-tshirt-crew text-primary"></i> Tops</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-human-male"><i class="mdi mdi-human-male text-success"></i> Pants</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-human-female"><i class="mdi mdi-human-female text-danger"></i> Dress</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-hanger"><i class="mdi mdi-hanger text-info"></i> Outerwear</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-hotel"><i class="mdi mdi-hotel text-warning"></i> Bedding</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-tie"><i class="mdi mdi-tie" style="color: #9333ea;"></i> Formal</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-blinds"><i class="mdi mdi-blinds text-secondary"></i> Curtains</span>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Details Section -->
                    <div class="form-section-title">
                        <i class="mdi mdi-text-box-outline"></i> Additional Details
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label fw-semibold small mb-1">Description</label>
                        <textarea class="form-control rounded-3" id="edit_description" name="description" rows="2"></textarea>
                    </div>
                    <div class="bg-light p-2 px-3 rounded-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fw-semibold d-block text-dark small">Active Status</span>
                            <small class="text-muted" style="font-size: 0.76rem;">Item is available for laundry pricing and bookings.</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="edit_is_active" name="is_active" value="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-padded d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-3 px-3 py-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 py-1 fw-semibold" id="btnUpdateItem">
                        <span class="btn-spinner spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <span>Update Item</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- DataTables JS, Toastr, & SweetAlert2 -->
    <script src="{{ asset('admin/dist/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/dist/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('admin/dist/libs/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Toastr config
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: "toast-top-right",
                timeOut: 3500
            };

            // CSRF setup
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Image Preview Helper
            function handleImagePreview(fileInput, previewBox, clearBtn) {
                const file = fileInput.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewBox.html(`<img src="${e.target.result}" alt="Preview">`);
                        clearBtn.removeClass('d-none');
                    };
                    reader.readAsDataURL(file);
                }
            }

            $('#create_image_input').on('change', function() {
                handleImagePreview(this, $('#create_image_preview_box'), $('#create_clear_image_btn'));
            });

            $('#create_clear_image_btn').on('click', function() {
                $('#create_image_input').val('');
                $('#create_image_preview_box').html('<i class="mdi mdi-image-plus"></i>');
                $(this).addClass('d-none');
            });

            $('#edit_image_input').on('change', function() {
                handleImagePreview(this, $('#edit_image_preview_box'), $('#edit_remove_image_btn'));
                $('#edit_remove_image').val('0');
            });

            $('#edit_remove_image_btn').on('click', function() {
                $('#edit_image_input').val('');
                $('#edit_image_preview_box').html('<i class="mdi mdi-image-plus"></i>');
                $('#edit_remove_image').val('1');
                $(this).addClass('d-none');
            });

            // Quick icon selector
            $(document).on('click', '.icon-option-pill', function() {
                const targetInput = $(this).data('target');
                const icon = $(this).data('icon');
                $(targetInput).val(icon).trigger('input');
            });

            $('#create_icon').on('input change', function() {
                const val = $(this).val().trim() || 'mdi-tag-text-outline';
                $('#create_icon_preview').html(`<i class="mdi ${val}"></i>`);
            });

            $('#edit_icon').on('input change', function() {
                const val = $(this).val().trim() || 'mdi-tag-text-outline';
                $('#edit_icon_preview').html(`<i class="mdi ${val}"></i>`);
            });

            // Debounce helper
            function debounce(func, wait) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            // Slug helper
            function slugify(text) {
                return text.toString().toLowerCase()
                    .replace(/\s+/g, '-')
                    .replace(/&/g, 'and')
                    .replace(/[^\w\-]+/g, '')
                    .replace(/\-\-+/g, '-')
                    .replace(/^-+/, '')
                    .replace(/-+$/, '');
            }

            // Server-verified unique slug
            function verifyUniqueSlug(name, slugVal, id, $slugInput, $feedback) {
                if (!name && !slugVal) {
                    $slugInput.val('');
                    $feedback.html('<i class="mdi mdi-magic-staff"></i> Auto-generates unique slug from item name').attr('class', 'slug-status-indicator is-auto');
                    return;
                }

                $feedback.html('<span class="spinner-border spinner-border-sm me-1" style="width: 12px; height: 12px;"></span> Checking slug...').attr('class', 'slug-status-indicator is-checking');

                $.ajax({
                    url: "{{ route('admin.services.items.check-slug') }}",
                    method: 'GET',
                    data: { name: name, slug: slugVal, id: id },
                    success: function(res) {
                        if (res.slug) {
                            $slugInput.val(res.slug);
                            $feedback.html('<i class="mdi mdi-check-circle text-success"></i> Unique slug: <strong>' + res.slug + '</strong>').attr('class', 'slug-status-indicator is-unique');
                        }
                    },
                    error: function() {
                        const localSlug = slugify(slugVal || name);
                        $slugInput.val(localSlug);
                        $feedback.html('<i class="mdi mdi-check text-success"></i> ' + localSlug).attr('class', 'slug-status-indicator is-unique');
                    }
                });
            }

            // Create Modal Slug
            let createSlugManualEdited = false;
            const debouncedCreateSlug = debounce(function() {
                const name = $('#create_name').val().trim();
                const slug = createSlugManualEdited ? $('#create_slug').val().trim() : '';
                verifyUniqueSlug(name, slug, null, $('#create_slug'), $('#create_slug_feedback'));
            }, 300);

            $('#create_name').on('input', function() {
                debouncedCreateSlug();
            });

            $('#create_slug').on('input', function() {
                createSlugManualEdited = true;
                const name = $('#create_name').val().trim();
                const slug = $(this).val().trim();
                debouncedCreateSlug();
            });

            // Edit Modal Slug
            let editSlugManualEdited = false;
            const debouncedEditSlug = debounce(function() {
                const name = $('#edit_name').val().trim();
                const slug = editSlugManualEdited ? $('#edit_slug').val().trim() : '';
                const id = $('#edit_item_id').val();
                verifyUniqueSlug(name, slug, id, $('#edit_slug'), $('#edit_slug_feedback'));
            }, 300);

            $('#edit_name').on('input', function() {
                debouncedEditSlug();
            });

            $('#edit_slug').on('input', function() {
                editSlugManualEdited = true;
                const name = $('#edit_name').val().trim();
                const slug = $(this).val().trim();
                const id = $('#edit_item_id').val();
                debouncedEditSlug();
            });

            // Quick Preset Bar Click
            $('.preset-badge').on('click', function() {
                createSlugManualEdited = false;
                const name = $(this).data('name');
                const icon = $(this).data('icon');
                const desc = $(this).data('desc');
                const categoryName = $(this).data('category');

                $('#create_name').val(name);
                $('#create_icon').val(icon).trigger('input');
                $('#create_description').val(desc);
                $('#create_slug').val('');
                $('#create_image_input').val('');
                $('#create_image_preview_box').html('<i class="mdi mdi-image-plus"></i>');
                $('#create_clear_image_btn').addClass('d-none');

                // Select matching category option if exists
                if (categoryName) {
                    $('#create_item_category_id option').filter(function() {
                        return $(this).text().trim().toLowerCase() === categoryName.toLowerCase();
                    }).prop('selected', true);
                }

                verifyUniqueSlug(name, '', null, $('#create_slug'), $('#create_slug_feedback'));

                const modal = new bootstrap.Modal(document.getElementById('createItemModal'));
                modal.show();
            });

            // Initialize DataTable
            const itemsTable = $('#itemsTable').DataTable({
                ajax: {
                    url: "{{ route('admin.services.items.data') }}",
                    data: function(d) {
                        d.category_id = $('#filter_category_id').val();
                    },
                    dataSrc: 'data'
                },
                columns: [
                    {
                        data: null,
                        render: function (data, type, row, meta) {
                            return `<span class="text-muted fw-semibold">${meta.row + 1}</span>`;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            const iconClass = (row.icon && row.icon.trim()) ? row.icon : 'mdi-tshirt-crew';
                            let avatarHtml = '';

                            if (row.image_url) {
                                avatarHtml = `<img src="${row.image_url}" alt="${row.name}">`;
                            } else {
                                avatarHtml = `<i class="mdi ${iconClass}"></i>`;
                            }

                            return `
                                <div class="d-flex align-items-center gap-3">
                                    <div class="item-avatar">
                                        ${avatarHtml}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">${row.name}</div>
                                        <small class="text-muted">
                                            ${row.image_url ? '<i class="mdi mdi-image-outline me-1 text-success"></i>Custom Image' : '<i class="mdi ' + iconClass + ' me-1"></i>' + iconClass}
                                        </small>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            const catIcon = row.category_icon || 'mdi-tag-text-outline';
                            return `<span class="category-pill"><i class="mdi ${catIcon} text-primary"></i> ${row.category_name}</span>`;
                        }
                    },
                    {
                        data: 'slug',
                        render: function(data) {
                            return `<span class="badge bg-light text-dark font-monospace">${data}</span>`;
                        }
                    },
                    {
                        data: 'description',
                        render: function(data) {
                            return `<span class="text-muted small">${data ? data : '-'}</span>`;
                        }
                    },
                    {
                        data: 'is_active',
                        render: function(data) {
                            if (data) {
                                return `<span class="status-badge active"><span class="status-dot"></span>Active</span>`;
                            }
                            return `<span class="status-badge inactive"><span class="status-dot"></span>Inactive</span>`;
                        }
                    },
                    {
                        data: 'created_at',
                        render: function(data) {
                            return `<span class="text-muted small">${data}</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end',
                        render: function(data, type, row) {
                            let buttons = '<div class="d-flex align-items-center justify-content-end gap-1">';

                            @can('edit-items', 'admin')
                                buttons += `
                                    <button type="button" class="action-btn-edit edit-item-btn"
                                        data-id="${row.id}"
                                        data-category-id="${row.category_id}"
                                        data-name="${row.name}"
                                        data-slug="${row.slug}"
                                        data-icon="${row.icon}"
                                        data-image="${row.image_url || ''}"
                                        data-description="${row.description || ''}"
                                        data-active="${row.is_active ? 1 : 0}"
                                        title="Edit Item">
                                        <i class="mdi mdi-pencil font-14"></i>
                                    </button>
                                    <button type="button" class="action-btn-toggle toggle-item-btn"
                                        data-id="${row.id}"
                                        data-active="${row.is_active ? 1 : 0}"
                                        title="Toggle Status">
                                        <i class="mdi ${row.is_active ? 'mdi-pause-circle-outline' : 'mdi-play-circle-outline'} font-14"></i>
                                    </button>
                                `;
                            @endcan

                            @can('delete-items', 'admin')
                                buttons += `
                                    <button type="button" class="action-btn-delete delete-item-btn"
                                        data-id="${row.id}"
                                        data-name="${row.name}"
                                        title="Delete Item">
                                        <i class="mdi mdi-delete font-14"></i>
                                    </button>
                                `;
                            @endcan

                            buttons += '</div>';
                            return buttons;
                        }
                    }
                ],
                order: [[0, 'asc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search items...",
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'></i>",
                        next: "<i class='mdi mdi-chevron-right'></i>"
                    }
                }
            });

            // Filter on Category change
            $('#filter_category_id').on('change', function() {
                itemsTable.ajax.reload();
            });

            // Reset create modal
            $('#createItemModal').on('show.bs.modal', function() {
                if (!$('#create_name').val()) {
                    createSlugManualEdited = false;
                    $('#create_slug').val('');
                    $('#create_slug_feedback').html('<i class="mdi mdi-magic-staff"></i> Auto-generates unique slug from item name').attr('class', 'slug-status-indicator is-auto');
                    $('#create_image_input').val('');
                    $('#create_image_preview_box').html('<i class="mdi mdi-image-plus"></i>');
                    $('#create_clear_image_btn').addClass('d-none');
                }
            });

            // Create Item Form Submit
            $('#createItemForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#btnSaveItem');
                const $spinner = $btn.find('.btn-spinner');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');

                const formData = new FormData(this);

                $.ajax({
                    url: "{{ route('admin.services.items.store') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#createItemModal').modal('hide');
                        $('#createItemForm')[0].reset();
                        createSlugManualEdited = false;
                        $('#create_icon').val('mdi-tshirt-crew').trigger('input');
                        $('#create_slug').val('');
                        $('#create_slug_feedback').html('<i class="mdi mdi-magic-staff"></i> Auto-generates unique slug from item name').attr('class', 'slug-status-indicator is-auto');
                        $('#create_image_preview_box').html('<i class="mdi mdi-image-plus"></i>');
                        $('#create_clear_image_btn').addClass('d-none');
                        $('#create_is_active').prop('checked', true);

                        Swal.fire({
                            icon: 'success',
                            title: 'Item Created!',
                            text: response.message || 'Laundry item created successfully.',
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-4 shadow-lg' }
                        });

                        itemsTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            let errorMsg = '';
                            $.each(errors, function(key, val) {
                                errorMsg += val[0] + '<br>';
                            });
                            toastr.error(errorMsg || 'Validation error');
                        } else {
                            toastr.error(xhr.responseJSON?.message || 'Failed to create item');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                    }
                });
            });

            // Open Edit Modal
            $(document).on('click', '.edit-item-btn', function() {
                editSlugManualEdited = false;
                const id = $(this).data('id');
                const catId = $(this).data('category-id');
                const name = $(this).data('name');
                const slug = $(this).data('slug');
                const icon = $(this).data('icon') || 'mdi-tshirt-crew';
                const image = $(this).data('image');
                const desc = $(this).data('description');
                const active = $(this).data('active');

                $('#edit_item_id').val(id);
                $('#edit_item_category_id').val(catId);
                $('#edit_name').val(name);
                $('#edit_slug').val(slug);
                $('#edit_icon').val(icon).trigger('input');
                $('#edit_description').val(desc === '-' ? '' : desc);
                $('#edit_is_active').prop('checked', active == 1);
                $('#edit_remove_image').val('0');
                $('#edit_image_input').val('');

                if (image) {
                    $('#edit_image_preview_box').html(`<img src="${image}" alt="${name}">`);
                    $('#edit_remove_image_btn').removeClass('d-none');
                } else {
                    $('#edit_image_preview_box').html('<i class="mdi mdi-image-plus"></i>');
                    $('#edit_remove_image_btn').addClass('d-none');
                }

                $('#edit_slug_feedback').html('<i class="mdi mdi-check-circle text-success"></i> Current unique slug: <strong>' + slug + '</strong>').attr('class', 'slug-status-indicator is-unique');

                $('#editItemModal').modal('show');
            });

            // Update Item Form Submit
            $('#editItemForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#edit_item_id').val();
                const $btn = $('#btnUpdateItem');
                const $spinner = $btn.find('.btn-spinner');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');

                let url = "{{ route('admin.services.items.update', ':id') }}";
                url = url.replace(':id', id);

                const formData = new FormData(this);

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#editItemModal').modal('hide');

                        Swal.fire({
                            icon: 'success',
                            title: 'Item Updated!',
                            text: response.message || 'Laundry item updated successfully.',
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-4 shadow-lg' }
                        });

                        itemsTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            let errorMsg = '';
                            $.each(errors, function(key, val) {
                                errorMsg += val[0] + '<br>';
                            });
                            toastr.error(errorMsg || 'Validation error');
                        } else {
                            toastr.error(xhr.responseJSON?.message || 'Failed to update item');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                    }
                });
            });

            // Toggle Item Status
            $(document).on('click', '.toggle-item-btn', function() {
                const id = $(this).data('id');

                let url = "{{ route('admin.services.items.toggle-status', ':id') }}";
                url = url.replace(':id', id);

                $.ajax({
                    url: url,
                    method: 'PATCH',
                    success: function(response) {
                        toastr.success(response.message || 'Status updated');
                        itemsTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to toggle status');
                    }
                });
            });

            // Delete Item with SweetAlert2
            $(document).on('click', '.delete-item-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Delete Laundry Item?',
                    html: `Are you sure you want to delete item <strong>"${name}"</strong>?<br><span class="text-danger small">This action cannot be undone.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="mdi mdi-delete me-1"></i> Yes, Delete Item',
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
                        let url = "{{ route('admin.services.items.destroy', ':id') }}";
                        url = url.replace(':id', id);

                        $.ajax({
                            url: url,
                            method: 'DELETE',
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message || `Item "${name}" was deleted successfully.`,
                                    timer: 2000,
                                    showConfirmButton: false,
                                    customClass: { popup: 'rounded-4 shadow-lg' }
                                });
                                itemsTable.ajax.reload(null, false);
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Delete Failed',
                                    text: xhr.responseJSON?.message || 'Failed to delete item.',
                                    customClass: { popup: 'rounded-4 shadow-lg' }
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
