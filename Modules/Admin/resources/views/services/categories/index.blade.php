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
        .categories-hero-banner {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%);
            border-radius: 16px;
            padding: 24px 28px;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.25), 0 8px 10px -6px rgba(79, 70, 229, 0.2);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .categories-hero-banner::after {
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
        .categories-hero-title {
            color: #ffffff !important;
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }
        .categories-breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        .categories-breadcrumb .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.82) !important;
            font-weight: 500;
            transition: color 0.15s ease;
        }
        .categories-breadcrumb .breadcrumb-item a:hover {
            color: #ffffff !important;
            text-decoration: underline;
        }
        .categories-breadcrumb .breadcrumb-item.active {
            color: #ffffff !important;
            font-weight: 600;
        }
        .categories-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
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

        /* Preset category badges */
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

        /* Category Media Avatar in DataTable (Image or Icon) */
        .category-avatar {
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
        .category-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .category-avatar i {
            line-height: 1;
        }
        tr:hover .category-avatar {
            border-color: #6366f1;
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.15);
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
        .modal-category-dialog {
            max-width: 660px;
        }
        .modal-category-card {
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
        .modal-category-card .form-control,
        .modal-category-card .input-group-text {
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
        .categories-card {
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
    <div class="categories-hero-banner mb-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 position-relative" style="z-index: 1;">
            <div>
                <h3 class="categories-hero-title">
                    <i class="mdi mdi-tag-text-outline me-2"></i>Item Categories
                </h3>
                <nav aria-label="breadcrumb">
                    <ol class="categories-breadcrumb breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Services</li>
                        <li class="breadcrumb-item active" aria-current="page">Item Categories</li>
                    </ol>
                </nav>
            </div>
            <div>
                @can('create-item-categories', 'admin')
                    <button type="button" class="btn btn-hero-add" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                        <i class="mdi mdi-plus-circle-outline fs-5"></i>
                        <span>Add New Category</span>
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Quick Suggestion / Presets Bar -->
    <div class="card border-0 rounded-4 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small fw-semibold me-1">
                    <i class="mdi mdi-lightbulb-on-outline text-warning me-1"></i>Common Laundry Categories:
                </span>
                <span class="preset-badge" data-name="Shirts & Tops" data-icon="mdi-tshirt-crew" data-desc="Shirts, t-shirts, tops, and casual polo items">
                    <i class="mdi mdi-tshirt-crew text-primary"></i> Shirts & Tops
                </span>
                <span class="preset-badge" data-name="Trousers" data-icon="mdi-human-male" data-desc="Pants, jeans, trousers, and shorts">
                    <i class="mdi mdi-human-male text-success"></i> Trousers
                </span>
                <span class="preset-badge" data-name="Dresses" data-icon="mdi-human-female" data-desc="Dresses, skirts, gowns, and ethnic wear">
                    <i class="mdi mdi-human-female text-danger"></i> Dresses
                </span>
                <span class="preset-badge" data-name="Outerwear" data-icon="mdi-hanger" data-desc="Jackets, coats, blazers, and cardigans">
                    <i class="mdi mdi-hanger text-info"></i> Outerwear
                </span>
                <span class="preset-badge" data-name="Bedding" data-icon="mdi-hotel" data-desc="Bedsheets, pillow covers, blankets, and duvets">
                    <i class="mdi mdi-hotel text-warning"></i> Bedding
                </span>
                <span class="preset-badge" data-name="Suits & Formal" data-icon="mdi-tie" data-desc="Two-piece suits, tuxedos, and formal attire">
                    <i class="mdi mdi-tie" style="color: #9333ea;"></i> Suits & Formal
                </span>
                <span class="preset-badge" data-name="Curtains & Drapes" data-icon="mdi-blinds" data-desc="Window curtains, drapes, and fabric coverings">
                    <i class="mdi mdi-blinds text-secondary"></i> Curtains & Drapes
                </span>
            </div>
        </div>
    </div>

    <!-- Categories DataTable Card -->
    <div class="card categories-card">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="categoriesTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
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

<!-- Create Category Modal (Bigger Size with Image Upload & Live Preview) -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-category-dialog">
        <div class="modal-content modal-category-card">
            <div class="modal-header modal-header-hero">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white p-3 rounded-4 shadow-sm">
                        <i class="mdi mdi-tag-text-outline fs-3"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-1" id="createCategoryModalLabel">Add Item Category</h4>
                        <p class="text-muted small mb-0">Create category with optional cover image, working icon, and unique slug.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createCategoryForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body modal-body-padded">
                    <!-- Basic Information -->
                    <div class="form-section-title">
                        <i class="mdi mdi-information-outline"></i> Basic Information
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="create_name" class="form-label fw-semibold small mb-1">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="create_name" name="name" placeholder="e.g., Shirts & Tops, Trousers" required>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="create_slug" class="form-label fw-semibold small mb-0">Unique Slug</label>
                                <span class="badge bg-light text-primary font-monospace" style="font-size: 0.72rem;">auto-generated</span>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="mdi mdi-link-variant"></i></span>
                                <input type="text" class="form-control font-monospace" id="create_slug" name="slug" placeholder="e.g., shirts-tops">
                            </div>
                            <div id="create_slug_feedback" class="slug-status-indicator is-auto">
                                <i class="mdi mdi-magic-staff"></i> Auto-generates unique slug from category name
                            </div>
                        </div>
                    </div>

                    <!-- Category Media: Image Upload & Icon Fallback -->
                    <div class="form-section-title">
                        <i class="mdi mdi-image-multiple-outline"></i> Category Media (Image & Fallback Icon)
                    </div>
                    <div class="row g-3 mb-4">
                        <!-- Image Upload Column -->
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Category Image (Optional)</label>
                            <div class="image-upload-wrapper">
                                <div class="image-preview-box" id="create_image_preview_box">
                                    <i class="mdi mdi-image-plus"></i>
                                </div>
                                <div class="mt-2">
                                    <input type="file" class="form-control form-control-sm" id="create_image_input" name="image" accept="image/jpeg,image/png,image/jpg,image/webp,image/svg+xml">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                                    <small class="text-muted">Max: 2MB (JPG, PNG, WEBP)</small>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0 d-none" id="create_clear_image_btn">Clear</button>
                                </div>
                            </div>
                        </div>
                        <!-- Icon Fallback Column -->
                        <div class="col-md-7">
                            <label for="create_icon" class="form-label fw-semibold">Fallback Icon (shown if no image is uploaded)</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-light" id="create_icon_preview"><i class="mdi mdi-tag-text-outline fs-5"></i></span>
                                <input type="text" class="form-control rounded-end-3" id="create_icon" name="icon" value="mdi-tag-text-outline" placeholder="mdi-tshirt-crew">
                            </div>
                            <small class="text-muted d-block mb-2">Select from quick laundry presets:</small>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-tshirt-crew"><i class="mdi mdi-tshirt-crew text-primary"></i> Tops</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-human-male"><i class="mdi mdi-human-male text-success"></i> Trousers</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-human-female"><i class="mdi mdi-human-female text-danger"></i> Dresses</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-hanger"><i class="mdi mdi-hanger text-info"></i> Outerwear</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-hotel"><i class="mdi mdi-hotel text-warning"></i> Bedding</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-tie"><i class="mdi mdi-tie" style="color: #9333ea;"></i> Formal</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-blinds"><i class="mdi mdi-blinds text-secondary"></i> Curtains</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-basket"><i class="mdi mdi-basket text-primary"></i> Laundry</span>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Details Section -->
                    <div class="form-section-title">
                        <i class="mdi mdi-text-box-outline"></i> Additional Details
                    </div>
                    <div class="mb-3">
                        <label for="create_description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control rounded-3" id="create_description" name="description" rows="2" placeholder="Brief note about garments or articles belonging to this laundry category..."></textarea>
                    </div>
                    <div class="bg-light p-3 rounded-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fw-semibold d-block text-dark">Category Visibility</span>
                            <small class="text-muted">Active categories appear immediately in items and service pricing.</small>
                        </div>
                        <div class="form-check form-switch mb-0 fs-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="create_is_active" name="is_active" value="1" checked>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-padded d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-3 px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-5 py-2 fw-semibold" id="btnSaveCategory">
                        <span class="btn-spinner spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <span>Save Category</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal (Bigger Size with Image Upload & Live Preview) -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-category-dialog">
        <div class="modal-content modal-category-card">
            <div class="modal-header modal-header-hero">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white p-3 rounded-4 shadow-sm">
                        <i class="mdi mdi-pencil-outline fs-3"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-1" id="editCategoryModalLabel">Edit Item Category</h4>
                        <p class="text-muted small mb-0">Update category details, cover image, and unique slug.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCategoryForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_category_id" name="id">
                <input type="hidden" id="edit_remove_image" name="remove_image" value="0">
                <div class="modal-body modal-body-padded">
                    <!-- Basic Information -->
                    <div class="form-section-title">
                        <i class="mdi mdi-information-outline"></i> Basic Information
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit_name" class="form-label fw-semibold small mb-1">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-3" id="edit_name" name="name" required>
                        </div>
                        <div class="col-md-6">
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

                    <!-- Category Media: Image Upload & Icon Fallback -->
                    <div class="form-section-title">
                        <i class="mdi mdi-image-multiple-outline"></i> Category Media (Image & Fallback Icon)
                    </div>
                    <div class="row g-3 mb-4">
                        <!-- Image Upload Column -->
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Category Image (Optional)</label>
                            <div class="image-upload-wrapper">
                                <div class="image-preview-box" id="edit_image_preview_box">
                                    <i class="mdi mdi-image-plus"></i>
                                </div>
                                <div class="mt-2">
                                    <input type="file" class="form-control form-control-sm" id="edit_image_input" name="image" accept="image/jpeg,image/png,image/jpg,image/webp,image/svg+xml">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                                    <small class="text-muted">Max: 2MB (JPG, PNG, WEBP)</small>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0 d-none" id="edit_remove_image_btn">Remove</button>
                                </div>
                            </div>
                        </div>
                        <!-- Icon Fallback Column -->
                        <div class="col-md-7">
                            <label for="edit_icon" class="form-label fw-semibold">Fallback Icon (shown if no image is uploaded)</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-light" id="edit_icon_preview"><i class="mdi mdi-tag-text-outline fs-5"></i></span>
                                <input type="text" class="form-control rounded-end-3" id="edit_icon" name="icon">
                            </div>
                            <small class="text-muted d-block mb-2">Select from quick laundry presets:</small>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-tshirt-crew"><i class="mdi mdi-tshirt-crew text-primary"></i> Tops</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-human-male"><i class="mdi mdi-human-male text-success"></i> Trousers</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-human-female"><i class="mdi mdi-human-female text-danger"></i> Dresses</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-hanger"><i class="mdi mdi-hanger text-info"></i> Outerwear</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-hotel"><i class="mdi mdi-hotel text-warning"></i> Bedding</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-tie"><i class="mdi mdi-tie" style="color: #9333ea;"></i> Formal</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-blinds"><i class="mdi mdi-blinds text-secondary"></i> Curtains</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-basket"><i class="mdi mdi-basket text-primary"></i> Laundry</span>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Details Section -->
                    <div class="form-section-title">
                        <i class="mdi mdi-text-box-outline"></i> Additional Details
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control rounded-3" id="edit_description" name="description" rows="2"></textarea>
                    </div>
                    <div class="bg-light p-3 rounded-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fw-semibold d-block text-dark">Category Visibility</span>
                            <small class="text-muted">Active categories appear immediately in items and service pricing.</small>
                        </div>
                        <div class="form-check form-switch mb-0 fs-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="edit_is_active" name="is_active" value="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-padded d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-3 px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-5 py-2 fw-semibold" id="btnUpdateCategory">
                        <span class="btn-spinner spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <span>Update Category</span>
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

            // Setup CSRF header for AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Image Preview Handler Helper
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

            // Create image change & clear events
            $('#create_image_input').on('change', function() {
                handleImagePreview(this, $('#create_image_preview_box'), $('#create_clear_image_btn'));
            });

            $('#create_clear_image_btn').on('click', function() {
                $('#create_image_input').val('');
                $('#create_image_preview_box').html('<i class="mdi mdi-image-plus"></i>');
                $(this).addClass('d-none');
            });

            // Edit image change & remove events
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

            // Quick icon pill selector
            $(document).on('click', '.icon-option-pill', function() {
                const targetInput = $(this).data('target');
                const icon = $(this).data('icon');
                $(targetInput).val(icon).trigger('input');
            });

            // Update icon previews when typing/selecting
            $('#create_icon').on('input change', function() {
                const val = $(this).val().trim() || 'mdi-tag-text-outline';
                $('#create_icon_preview').html(`<i class="mdi ${val} fs-5"></i>`);
            });

            $('#edit_icon').on('input change', function() {
                const val = $(this).val().trim() || 'mdi-tag-text-outline';
                $('#edit_icon_preview').html(`<i class="mdi ${val} fs-5"></i>`);
            });

            // Debounced Slug Generator & Uniqueness Validator
            function debounce(func, wait) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            // Client-side helper slug generator
            function slugify(text) {
                return text.toString().toLowerCase()
                    .replace(/\s+/g, '-')
                    .replace(/&/g, 'and')
                    .replace(/[^\w\-]+/g, '')
                    .replace(/\-\-+/g, '-')
                    .replace(/^-+/, '')
                    .replace(/-+$/, '');
            }

            // Fetch server-verified unique slug
            function verifyUniqueSlug(name, slugVal, id, $slugInput, $feedback) {
                if (!name && !slugVal) {
                    $slugInput.val('');
                    $feedback.html('<i class="mdi mdi-magic-staff"></i> Auto-generates unique slug from category name').attr('class', 'slug-status-indicator is-auto');
                    return;
                }

                $feedback.html('<span class="spinner-border spinner-border-sm me-1" style="width: 12px; height: 12px;"></span> Checking unique slug...').attr('class', 'slug-status-indicator is-checking');

                $.ajax({
                    url: "{{ route('admin.services.categories.check-slug') }}",
                    method: 'GET',
                    data: {
                        name: name,
                        slug: slugVal,
                        id: id
                    },
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

            // Handle create modal automatic slug generation
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

            // Handle edit modal automatic slug generation
            let editSlugManualEdited = false;
            const debouncedEditSlug = debounce(function() {
                const name = $('#edit_name').val().trim();
                const slug = editSlugManualEdited ? $('#edit_slug').val().trim() : '';
                const id = $('#edit_category_id').val();
                verifyUniqueSlug(name, slug, id, $('#edit_slug'), $('#edit_slug_feedback'));
            }, 300);

            $('#edit_name').on('input', function() {
                debouncedEditSlug();
            });

            $('#edit_slug').on('input', function() {
                editSlugManualEdited = true;
                const name = $('#edit_name').val().trim();
                const slug = $(this).val().trim();
                const id = $('#edit_category_id').val();
                debouncedEditSlug();
            });

            // Quick preset click handler
            $('.preset-badge').on('click', function() {
                createSlugManualEdited = false;
                const name = $(this).data('name');
                const icon = $(this).data('icon');
                const desc = $(this).data('desc');

                $('#create_name').val(name);
                $('#create_icon').val(icon).trigger('input');
                $('#create_description').val(desc);
                $('#create_slug').val('');
                $('#create_image_input').val('');
                $('#create_image_preview_box').html('<i class="mdi mdi-image-plus"></i>');
                $('#create_clear_image_btn').addClass('d-none');

                verifyUniqueSlug(name, '', null, $('#create_slug'), $('#create_slug_feedback'));

                const modal = new bootstrap.Modal(document.getElementById('createCategoryModal'));
                modal.show();
            });

            // Initialize DataTable
            const categoriesTable = $('#categoriesTable').DataTable({
                ajax: {
                    url: "{{ route('admin.services.categories.data') }}",
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
                            const iconClass = (row.icon && row.icon.trim()) ? row.icon : 'mdi-tag-text-outline';
                            let avatarHtml = '';

                            if (row.image_url) {
                                avatarHtml = `<img src="${row.image_url}" alt="${row.name}">`;
                            } else {
                                avatarHtml = `<i class="mdi ${iconClass}"></i>`;
                            }

                            return `
                                <div class="d-flex align-items-center gap-3">
                                    <div class="category-avatar">
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

                            @can('edit-item-categories', 'admin')
                                buttons += `
                                    <button type="button" class="action-btn-edit edit-category-btn"
                                        data-id="${row.id}"
                                        data-name="${row.name}"
                                        data-slug="${row.slug}"
                                        data-icon="${row.icon}"
                                        data-image="${row.image_url || ''}"
                                        data-description="${row.description || ''}"
                                        data-active="${row.is_active ? 1 : 0}"
                                        title="Edit Category">
                                        <i class="mdi mdi-pencil font-14"></i>
                                    </button>
                                    <button type="button" class="action-btn-toggle toggle-category-btn"
                                        data-id="${row.id}"
                                        data-active="${row.is_active ? 1 : 0}"
                                        title="Toggle Status">
                                        <i class="mdi ${row.is_active ? 'mdi-pause-circle-outline' : 'mdi-play-circle-outline'} font-14"></i>
                                    </button>
                                `;
                            @endcan

                            @can('delete-item-categories', 'admin')
                                buttons += `
                                    <button type="button" class="action-btn-delete delete-category-btn"
                                        data-id="${row.id}"
                                        data-name="${row.name}"
                                        title="Delete Category">
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
                    searchPlaceholder: "Search categories...",
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'></i>",
                        next: "<i class='mdi mdi-chevron-right'></i>"
                    }
                }
            });

            // Reset create modal on open
            $('#createCategoryModal').on('show.bs.modal', function() {
                if (!$('#create_name').val()) {
                    createSlugManualEdited = false;
                    $('#create_slug').val('');
                    $('#create_slug_feedback').html('<i class="mdi mdi-magic-staff"></i> Auto-generates unique slug from category name').attr('class', 'slug-status-indicator is-auto');
                    $('#create_image_input').val('');
                    $('#create_image_preview_box').html('<i class="mdi mdi-image-plus"></i>');
                    $('#create_clear_image_btn').addClass('d-none');
                }
            });

            // Create Category Form Submission (FormData for Image Support)
            $('#createCategoryForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#btnSaveCategory');
                const $spinner = $btn.find('.btn-spinner');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');

                const formData = new FormData(this);

                $.ajax({
                    url: "{{ route('admin.services.categories.store') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#createCategoryModal').modal('hide');
                        $('#createCategoryForm')[0].reset();
                        createSlugManualEdited = false;
                        $('#create_icon').val('mdi-tag-text-outline').trigger('input');
                        $('#create_slug').val('');
                        $('#create_slug_feedback').html('<i class="mdi mdi-magic-staff"></i> Auto-generates unique slug from category name').attr('class', 'slug-status-indicator is-auto');
                        $('#create_image_preview_box').html('<i class="mdi mdi-image-plus"></i>');
                        $('#create_clear_image_btn').addClass('d-none');
                        $('#create_is_active').prop('checked', true);

                        Swal.fire({
                            icon: 'success',
                            title: 'Category Created!',
                            text: response.message || 'Item category created successfully.',
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: {
                                popup: 'rounded-4 shadow-lg'
                            }
                        });

                        categoriesTable.ajax.reload(null, false);
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
                            toastr.error(xhr.responseJSON?.message || 'Failed to create category');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                    }
                });
            });

            // Open Edit Modal
            $(document).on('click', '.edit-category-btn', function() {
                editSlugManualEdited = false;
                const id = $(this).data('id');
                const name = $(this).data('name');
                const slug = $(this).data('slug');
                const icon = $(this).data('icon') || 'mdi-tag-text-outline';
                const image = $(this).data('image');
                const desc = $(this).data('description');
                const active = $(this).data('active');

                $('#edit_category_id').val(id);
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

                $('#editCategoryModal').modal('show');
            });

            // Update Category Form Submission (FormData for Image Support via POST method spoofing)
            $('#editCategoryForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#edit_category_id').val();
                const $btn = $('#btnUpdateCategory');
                const $spinner = $btn.find('.btn-spinner');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');

                let url = "{{ route('admin.services.categories.update', ':id') }}";
                url = url.replace(':id', id);

                const formData = new FormData(this);

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#editCategoryModal').modal('hide');

                        Swal.fire({
                            icon: 'success',
                            title: 'Category Updated!',
                            text: response.message || 'Item category updated successfully.',
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: {
                                popup: 'rounded-4 shadow-lg'
                            }
                        });

                        categoriesTable.ajax.reload(null, false);
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
                            toastr.error(xhr.responseJSON?.message || 'Failed to update category');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                    }
                });
            });

            // Toggle Category Status
            $(document).on('click', '.toggle-category-btn', function() {
                const id = $(this).data('id');

                let url = "{{ route('admin.services.categories.toggle-status', ':id') }}";
                url = url.replace(':id', id);

                $.ajax({
                    url: url,
                    method: 'PATCH',
                    success: function(response) {
                        toastr.success(response.message || 'Status updated');
                        categoriesTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to toggle status');
                    }
                });
            });

            // Delete Category with SweetAlert2
            $(document).on('click', '.delete-category-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Delete Item Category?',
                    html: `Are you sure you want to delete category <strong>"${name}"</strong>?<br><span class="text-danger small">This action cannot be undone.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="mdi mdi-delete me-1"></i> Yes, Delete Category',
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
                        let url = "{{ route('admin.services.categories.destroy', ':id') }}";
                        url = url.replace(':id', id);

                        $.ajax({
                            url: url,
                            method: 'DELETE',
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message || `Category "${name}" was deleted successfully.`,
                                    timer: 2000,
                                    showConfirmButton: false,
                                    customClass: {
                                        popup: 'rounded-4 shadow-lg'
                                    }
                                });
                                categoriesTable.ajax.reload(null, false);
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Delete Failed',
                                    text: xhr.responseJSON?.message || 'Failed to delete category.',
                                    customClass: {
                                        popup: 'rounded-4 shadow-lg'
                                    }
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
