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
        .services-hero-banner {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%);
            border-radius: 16px;
            padding: 24px 28px;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.25), 0 8px 10px -6px rgba(79, 70, 229, 0.2);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }
        .services-hero-banner::after {
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
        .services-hero-title {
            color: #ffffff !important;
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }
        .services-breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        .services-breadcrumb .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.82) !important;
            font-weight: 500;
            transition: color 0.15s ease;
        }
        .services-breadcrumb .breadcrumb-item a:hover {
            color: #ffffff !important;
            text-decoration: underline;
        }
        .services-breadcrumb .breadcrumb-item.active {
            color: #ffffff !important;
            font-weight: 600;
        }
        .services-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
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

        /* Service Media Avatar in DataTable (Clickable to open carousel if has images) */
        .service-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #f1f5f9;
            color: #4f46e5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
            transition: all 0.2s ease;
            flex-shrink: 0;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            position: relative;
        }
        .service-avatar.has-gallery {
            cursor: pointer;
        }
        .service-avatar.has-gallery:hover {
            border-color: #4f46e5;
            transform: scale(1.08);
            box-shadow: 0 6px 14px rgba(79, 70, 229, 0.25);
        }
        .service-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .service-avatar .gallery-badge {
            position: absolute;
            bottom: 2px;
            right: 2px;
            background: rgba(15, 23, 42, 0.85);
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 1px 4px;
            border-radius: 4px;
            line-height: 1.1;
            display: flex;
            align-items: center;
            gap: 2px;
        }

        /* Pricing Badge in Table */
        .price-badge {
            background-color: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .price-unit {
            font-size: 0.75rem;
            font-weight: 500;
            color: #475569;
        }

        /* Scope Pills (Category & Item) */
        .scope-pill {
            font-size: 0.74rem;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .scope-pill.category {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .scope-pill.item {
            background-color: #eef2ff;
            color: #4f46e5;
            border: 1px solid #c7d2fe;
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
        .modal-service-dialog {
            max-width: 680px;
        }
        .modal-service-card {
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
        .modal-service-card .form-control,
        .modal-service-card .form-select,
        .modal-service-card .input-group-text {
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

        /* Image Dropzone & Multi-image Preview Styles */
        .image-upload-wrapper {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            padding: 12px;
            transition: all 0.2s ease;
            position: relative;
        }
        .image-upload-wrapper:hover {
            border-color: #6366f1;
            background: #f5f7ff;
        }
        .multi-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-height: 52px;
            align-items: center;
        }
        .thumb-preview-box {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            position: relative;
        }
        .thumb-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .thumb-preview-box.is-cover::after {
            content: 'Cover';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(79, 70, 229, 0.85);
            color: #ffffff;
            font-size: 0.6rem;
            text-align: center;
            line-height: 1.1;
            font-weight: 600;
        }

        /* Carousel Modal Styling */
        #serviceCarouselModal .carousel-item {
            height: 420px;
            background: #0f172a;
        }
        #serviceCarouselModal .carousel-item img {
            height: 100%;
            width: 100%;
            object-fit: contain;
        }
        .carousel-control-prev,
        .carousel-control-next {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            margin: 0 16px;
            transition: all 0.2s ease;
        }
        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            background: rgba(255, 255, 255, 0.5);
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
        .services-card {
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
    <div class="services-hero-banner mb-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 position-relative" style="z-index: 1;">
            <div>
                <h3 class="services-hero-title">
                    <i class="mdi mdi-cube-outline me-2"></i>Laundry Services
                </h3>
                <nav aria-label="breadcrumb">
                    <ol class="services-breadcrumb breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item">Services</li>
                        <li class="breadcrumb-item active" aria-current="page">Services List</li>
                    </ol>
                </nav>
            </div>
            <div>
                @can('create-services', 'admin')
                    <button type="button" class="btn btn-hero-add" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                        <i class="mdi mdi-plus-circle-outline fs-5"></i>
                        <span>Add New Service</span>
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Quick Suggestion / Common Laundry Services Bar -->
    <div class="card border-0 rounded-4 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small fw-semibold me-1">
                    <i class="mdi mdi-lightbulb-on-outline text-warning me-1"></i>Common Laundry Services:
                </span>
                <span class="preset-badge" data-name="Wash & Fold" data-icon="mdi-washing-machine" data-price="50.00" data-unit="kg" data-desc="Everyday clothes washed, dried, and neatly folded">
                    <i class="mdi mdi-washing-machine text-primary"></i> Wash & Fold
                </span>
                <span class="preset-badge" data-name="Wash & Iron" data-icon="mdi-tshirt-crew" data-price="70.00" data-unit="pc" data-desc="Complete washing with crisp professional pressing">
                    <i class="mdi mdi-tshirt-crew text-primary"></i> Wash & Iron
                </span>
                <span class="preset-badge" data-name="Dry Cleaning" data-icon="mdi-hanger" data-price="150.00" data-unit="pc" data-desc="Professional dry cleaning for suits, coats, and silk">
                    <i class="mdi mdi-hanger text-info"></i> Dry Cleaning
                </span>
                <span class="preset-badge" data-name="Steam Pressing" data-icon="mdi-spray" data-price="30.00" data-unit="pc" data-desc="High-temperature steam iron pressing for wrinkle removal">
                    <i class="mdi mdi-spray text-purple" style="color: #9333ea;"></i> Steam Pressing
                </span>
                <span class="preset-badge" data-name="Stain Removal" data-icon="mdi-basket" data-price="90.00" data-unit="pc" data-desc="Intensive spot treatment for difficult stains">
                    <i class="mdi mdi-basket text-danger"></i> Stain Removal
                </span>
                <span class="preset-badge" data-name="Blanket & Duvet Wash" data-icon="mdi-hotel" data-price="120.00" data-unit="kg" data-desc="Deep thermal sanitize wash for heavy bed linens and quilts">
                    <i class="mdi mdi-hotel text-warning"></i> Blanket Laundry
                </span>
            </div>
        </div>
    </div>

    <!-- Services DataTable Card with Unit & Category Filters -->
    <div class="card services-card">
        <div class="card-body p-4">
            <!-- Filter Bar -->
            <div class="row align-items-center g-3 mb-3">
                <div class="col-md-3">
                    <label for="filter_unit_id" class="form-label small fw-semibold text-muted mb-1">
                        <i class="mdi mdi-scale-balance me-1"></i>Filter by Unit:
                    </label>
                    <select id="filter_unit_id" class="form-select form-select-sm rounded-3">
                        <option value="">All Units</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_category_id" class="form-label small fw-semibold text-muted mb-1">
                        <i class="mdi mdi-tag-text-outline me-1"></i>Filter by Category:
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
                <table id="servicesTable" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Service</th>
                            <th>Scope (Category / Item)</th>
                            <th>Base Price / Unit</th>
                            <th>Slug</th>
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

<!-- Carousel Slider Modal for Service Images -->
<div class="modal fade" id="serviceCarouselModal" tabindex="-1" aria-labelledby="serviceCarouselModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom-0 bg-dark text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="mdi mdi-image-multiple text-warning fs-4"></i>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="serviceCarouselTitle">Service Images</h5>
                        <small class="text-white-50" id="serviceCarouselSubtitle">Gallery preview</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-dark">
                <div id="serviceCarousel" class="carousel slide" data-bs-ride="false">
                    <div class="carousel-indicators" id="carouselIndicators"></div>
                    <div class="carousel-inner" id="carouselInner"></div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#serviceCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#serviceCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
            <div class="modal-footer border-top-0 bg-dark py-2 px-4 d-flex justify-content-between text-white-50 small">
                <span id="carouselCounter">Slide 1 of 1</span>
                <button type="button" class="btn btn-outline-light btn-sm rounded-3 px-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Service Modal -->
<div class="modal fade" id="createServiceModal" tabindex="-1" aria-labelledby="createServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-service-dialog">
        <div class="modal-content modal-service-card">
            <div class="modal-header modal-header-hero">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white p-2 rounded-3 shadow-sm">
                        <i class="mdi mdi-cube-outline fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="createServiceModalLabel">Add Laundry Service</h5>
                        <p class="text-muted small mb-0">Create laundry service with unit pricing, optional category/item scope, and multiple images.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createServiceForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body modal-body-padded">
                    <!-- Basic Information -->
                    <div class="form-section-title">
                        <i class="mdi mdi-information-outline"></i> Basic Information & Pricing
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label for="create_name" class="form-label fw-semibold small mb-1">
                                Service Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control rounded-3" id="create_name" name="name" placeholder="e.g., Wash & Fold, Dry Cleaning" required>
                        </div>
                        <div class="col-md-5">
                            <label for="create_unit_id" class="form-label fw-semibold small mb-1">
                                Pricing Unit <span class="text-danger">*</span>
                            </label>
                            <select class="form-select rounded-3" id="create_unit_id" name="unit_id" required>
                                <option value="" disabled selected>Select Unit...</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" data-short="{{ $unit->short_name }}">{{ $unit->name }} ({{ $unit->short_name }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label for="create_price" class="form-label fw-semibold small mb-1">
                                Base Price (৳ BDT) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted fw-bold">৳</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="create_price" name="price" placeholder="0.00" value="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="create_slug" class="form-label fw-semibold small mb-0">Unique Slug</label>
                                <span class="badge bg-light text-primary font-monospace" style="font-size: 0.72rem;">auto-generated</span>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="mdi mdi-link-variant"></i></span>
                                <input type="text" class="form-control font-monospace" id="create_slug" name="slug" placeholder="e.g., wash-and-fold">
                            </div>
                            <div id="create_slug_feedback" class="slug-status-indicator is-auto">
                                <i class="mdi mdi-magic-staff"></i> Auto-generates unique slug from service name
                            </div>
                        </div>
                    </div>

                    <!-- Optional Category & Item Scope -->
                    <div class="form-section-title">
                        <i class="mdi mdi-shape-plus"></i> Service Scope (Optional)
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="create_item_category_id" class="form-label fw-semibold small mb-1">
                                Item Category <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <select class="form-select rounded-3" id="create_item_category_id" name="item_category_id">
                                <option value="" selected>All / Any Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="create_item_id" class="form-label fw-semibold small mb-1">
                                Specific Item <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <select class="form-select rounded-3" id="create_item_id" name="item_id">
                                <option value="" selected>All / Any Item</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-category-id="{{ $item->item_category_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Multiple Images & Fallback Icon -->
                    <div class="form-section-title">
                        <i class="mdi mdi-image-multiple-outline"></i> Service Media (Multiple Images & Fallback Icon)
                    </div>
                    <div class="row g-3 mb-3">
                        <!-- Multiple Image Upload Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Upload Multiple Images</label>
                            <div class="image-upload-wrapper">
                                <div class="multi-preview-container" id="create_multi_preview">
                                    <span class="text-muted small"><i class="mdi mdi-image-plus me-1"></i>No images selected</span>
                                </div>
                                <div class="mt-2">
                                    <input type="file" class="form-control form-control-sm" id="create_images_input" name="images[]" multiple accept="image/jpeg,image/png,image/jpg,image/webp,image/svg+xml">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1 px-1">
                                    <small class="text-muted" style="font-size: 0.72rem;">1st image becomes cover. Max: 2MB each</small>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0 d-none" id="create_clear_images_btn" style="font-size: 0.75rem;">Clear All</button>
                                </div>
                            </div>
                        </div>
                        <!-- Icon Fallback Column -->
                        <div class="col-md-6">
                            <label for="create_icon" class="form-label fw-semibold small mb-1">Fallback Icon (used if no images)</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-light" id="create_icon_preview"><i class="mdi mdi-cube-outline"></i></span>
                                <input type="text" class="form-control rounded-end-3" id="create_icon" name="icon" value="mdi-cube-outline" placeholder="mdi-washing-machine">
                            </div>
                            <small class="text-muted d-block mb-1" style="font-size: 0.76rem;">Quick presets:</small>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-washing-machine"><i class="mdi mdi-washing-machine text-primary"></i> Machine</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-tshirt-crew"><i class="mdi mdi-tshirt-crew text-primary"></i> Wash/Iron</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-hanger"><i class="mdi mdi-hanger text-info"></i> Dry Clean</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-spray"><i class="mdi mdi-spray text-purple" style="color: #9333ea;"></i> Steam</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-basket"><i class="mdi mdi-basket text-danger"></i> Stain</span>
                                <span class="icon-option-pill" data-target="#create_icon" data-icon="mdi-hotel"><i class="mdi mdi-hotel text-warning"></i> Blanket</span>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Details Section -->
                    <div class="form-section-title">
                        <i class="mdi mdi-text-box-outline"></i> Additional Details
                    </div>
                    <div class="mb-3">
                        <label for="create_description" class="form-label fw-semibold small mb-1">Description</label>
                        <textarea class="form-control rounded-3" id="create_description" name="description" rows="2" placeholder="Brief note about the service scope and process..."></textarea>
                    </div>
                    <div class="bg-light p-2 px-3 rounded-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="fw-semibold d-block text-dark small">Active Status</span>
                            <small class="text-muted" style="font-size: 0.76rem;">Service is active and available for customer orders.</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="create_is_active" name="is_active" value="1" checked>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-padded d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-3 px-3 py-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 py-1 fw-semibold" id="btnSaveService">
                        <span class="btn-spinner spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <span>Save Service</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-service-dialog">
        <div class="modal-content modal-service-card">
            <div class="modal-header modal-header-hero">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white p-2 rounded-3 shadow-sm">
                        <i class="mdi mdi-pencil-outline fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="editServiceModalLabel">Edit Laundry Service</h5>
                        <p class="text-muted small mb-0">Update service pricing, unit, images, category/item scope, and slug.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editServiceForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_service_id" name="id">
                <input type="hidden" id="edit_remove_gallery_images" name="remove_gallery_images" value="0">
                <input type="hidden" id="edit_remove_image" name="remove_image" value="0">
                <div class="modal-body modal-body-padded">
                    <!-- Basic Information -->
                    <div class="form-section-title">
                        <i class="mdi mdi-information-outline"></i> Basic Information & Pricing
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label for="edit_name" class="form-label fw-semibold small mb-1">
                                Service Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control rounded-3" id="edit_name" name="name" required>
                        </div>
                        <div class="col-md-5">
                            <label for="edit_unit_id" class="form-label fw-semibold small mb-1">
                                Pricing Unit <span class="text-danger">*</span>
                            </label>
                            <select class="form-select rounded-3" id="edit_unit_id" name="unit_id" required>
                                <option value="" disabled>Select Unit...</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->short_name }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label for="edit_price" class="form-label fw-semibold small mb-1">
                                Base Price (৳ BDT) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted fw-bold">৳</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="edit_price" name="price" required>
                            </div>
                        </div>
                        <div class="col-md-7">
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

                    <!-- Optional Category & Item Scope -->
                    <div class="form-section-title">
                        <i class="mdi mdi-shape-plus"></i> Service Scope (Optional)
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit_item_category_id" class="form-label fw-semibold small mb-1">
                                Item Category <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <select class="form-select rounded-3" id="edit_item_category_id" name="item_category_id">
                                <option value="">All / Any Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_item_id" class="form-label fw-semibold small mb-1">
                                Specific Item <span class="text-muted fw-normal">(Optional)</span>
                            </label>
                            <select class="form-select rounded-3" id="edit_item_id" name="item_id">
                                <option value="">All / Any Item</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-category-id="{{ $item->item_category_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Multiple Images & Fallback Icon -->
                    <div class="form-section-title">
                        <i class="mdi mdi-image-multiple-outline"></i> Service Media (Multiple Images & Fallback Icon)
                    </div>
                    <div class="row g-3 mb-3">
                        <!-- Multiple Image Upload Column -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small mb-1">Service Images</label>
                            <div class="image-upload-wrapper">
                                <div class="multi-preview-container" id="edit_multi_preview"></div>
                                <div class="mt-2">
                                    <input type="file" class="form-control form-control-sm" id="edit_images_input" name="images[]" multiple accept="image/jpeg,image/png,image/jpg,image/webp,image/svg+xml">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1 px-1">
                                    <small class="text-muted" style="font-size: 0.72rem;">Choose new images to append or replace.</small>
                                    <button type="button" class="btn btn-link btn-sm text-danger p-0 d-none" id="edit_remove_images_btn" style="font-size: 0.75rem;">Clear All</button>
                                </div>
                            </div>
                        </div>
                        <!-- Icon Fallback Column -->
                        <div class="col-md-6">
                            <label for="edit_icon" class="form-label fw-semibold small mb-1">Fallback Icon (used if no images)</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-light" id="edit_icon_preview"><i class="mdi mdi-cube-outline"></i></span>
                                <input type="text" class="form-control rounded-end-3" id="edit_icon" name="icon">
                            </div>
                            <small class="text-muted d-block mb-1" style="font-size: 0.76rem;">Quick presets:</small>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-washing-machine"><i class="mdi mdi-washing-machine text-primary"></i> Machine</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-tshirt-crew"><i class="mdi mdi-tshirt-crew text-primary"></i> Wash/Iron</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-hanger"><i class="mdi mdi-hanger text-info"></i> Dry Clean</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-spray"><i class="mdi mdi-spray text-purple" style="color: #9333ea;"></i> Steam</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-basket"><i class="mdi mdi-basket text-danger"></i> Stain</span>
                                <span class="icon-option-pill" data-target="#edit_icon" data-icon="mdi-hotel"><i class="mdi mdi-hotel text-warning"></i> Blanket</span>
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
                            <small class="text-muted" style="font-size: 0.76rem;">Service is active and available for customer orders.</small>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="edit_is_active" name="is_active" value="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-padded d-flex justify-content-between">
                    <button type="button" class="btn btn-light rounded-3 px-3 py-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 py-1 fw-semibold" id="btnUpdateService">
                        <span class="btn-spinner spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <span>Update Service</span>
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

            // Dynamic item dropdown filtering when category is selected
            function filterItemOptionsByCat($catSelect, $itemSelect) {
                const catId = $catSelect.val();
                $itemSelect.find('option').each(function() {
                    const optCatId = $(this).data('category-id');
                    if (!optCatId || !catId || optCatId == catId) {
                        $(this).show();
                    } else {
                        $(this).hide();
                        if ($(this).is(':selected')) {
                            $itemSelect.val('');
                        }
                    }
                });
            }

            $('#create_item_category_id').on('change', function() {
                filterItemOptionsByCat($(this), $('#create_item_id'));
            });

            $('#edit_item_category_id').on('change', function() {
                filterItemOptionsByCat($(this), $('#edit_item_id'));
            });

            // Multi-image preview handler
            function renderMultiImagePreviews(files, $container, $clearBtn) {
                $container.empty();
                if (!files || files.length === 0) {
                    $container.html('<span class="text-muted small"><i class="mdi mdi-image-plus me-1"></i>No images selected</span>');
                    $clearBtn.addClass('d-none');
                    return;
                }

                $clearBtn.removeClass('d-none');

                Array.from(files).forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const isCoverClass = index === 0 ? 'is-cover' : '';
                        const thumb = $(`
                            <div class="thumb-preview-box ${isCoverClass}">
                                <img src="${e.target.result}" alt="Preview ${index + 1}">
                            </div>
                        `);
                        $container.append(thumb);
                    };
                    reader.readAsDataURL(file);
                });
            }

            // Create Modal Multi-image
            $('#create_images_input').on('change', function() {
                renderMultiImagePreviews(this.files, $('#create_multi_preview'), $('#create_clear_images_btn'));
            });

            $('#create_clear_images_btn').on('click', function() {
                $('#create_images_input').val('');
                $('#create_multi_preview').html('<span class="text-muted small"><i class="mdi mdi-image-plus me-1"></i>No images selected</span>');
                $(this).addClass('d-none');
            });

            // Edit Modal Multi-image
            $('#edit_images_input').on('change', function() {
                renderMultiImagePreviews(this.files, $('#edit_multi_preview'), $('#edit_remove_images_btn'));
                $('#edit_remove_gallery_images').val('0');
                $('#edit_remove_image').val('0');
            });

            $('#edit_remove_images_btn').on('click', function() {
                $('#edit_images_input').val('');
                $('#edit_multi_preview').html('<span class="text-muted small"><i class="mdi mdi-image-plus me-1"></i>No images selected</span>');
                $('#edit_remove_gallery_images').val('1');
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
                const val = $(this).val().trim() || 'mdi-cube-outline';
                $('#create_icon_preview').html(`<i class="mdi ${val}"></i>`);
            });

            $('#edit_icon').on('input change', function() {
                const val = $(this).val().trim() || 'mdi-cube-outline';
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

            // Slugify helper
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
                    $feedback.html('<i class="mdi mdi-magic-staff"></i> Auto-generates unique slug from service name').attr('class', 'slug-status-indicator is-auto');
                    return;
                }

                $feedback.html('<span class="spinner-border spinner-border-sm me-1" style="width: 12px; height: 12px;"></span> Checking slug...').attr('class', 'slug-status-indicator is-checking');

                $.ajax({
                    url: "{{ route('admin.services.services.check-slug') }}",
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
                const id = $('#edit_service_id').val();
                verifyUniqueSlug(name, slug, id, $('#edit_slug'), $('#edit_slug_feedback'));
            }, 300);

            $('#edit_name').on('input', function() {
                debouncedEditSlug();
            });

            $('#edit_slug').on('input', function() {
                editSlugManualEdited = true;
                const name = $('#edit_name').val().trim();
                const slug = $(this).val().trim();
                const id = $('#edit_service_id').val();
                debouncedEditSlug();
            });

            // Quick Preset Bar Click
            $('.preset-badge').on('click', function() {
                createSlugManualEdited = false;
                const name = $(this).data('name');
                const icon = $(this).data('icon');
                const price = $(this).data('price');
                const unitShort = $(this).data('unit');
                const desc = $(this).data('desc');

                $('#create_name').val(name);
                $('#create_price').val(price);
                $('#create_icon').val(icon).trigger('input');
                $('#create_description').val(desc);
                $('#create_slug').val('');
                $('#create_images_input').val('');
                $('#create_multi_preview').html('<span class="text-muted small"><i class="mdi mdi-image-plus me-1"></i>No images selected</span>');
                $('#create_clear_images_btn').addClass('d-none');

                if (unitShort) {
                    $('#create_unit_id option').filter(function() {
                        return $(this).data('short') === unitShort;
                    }).prop('selected', true);
                }

                verifyUniqueSlug(name, '', null, $('#create_slug'), $('#create_slug_feedback'));

                const modal = new bootstrap.Modal(document.getElementById('createServiceModal'));
                modal.show();
            });

            // Initialize DataTable
            const servicesTable = $('#servicesTable').DataTable({
                ajax: {
                    url: "{{ route('admin.services.services.data') }}",
                    data: function(d) {
                        d.unit_id = $('#filter_unit_id').val();
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
                            const iconClass = (row.icon && row.icon.trim()) ? row.icon : 'mdi-cube-outline';
                            const allImages = row.all_images || [];
                            const hasImages = allImages.length > 0;
                            const encodedImages = encodeURIComponent(JSON.stringify(allImages));

                            let avatarInner = '';
                            if (row.image_url) {
                                avatarInner = `<img src="${row.image_url}" alt="${row.name}">`;
                            } else {
                                avatarInner = `<i class="mdi ${iconClass}"></i>`;
                            }

                            let galleryBadge = '';
                            if (allImages.length > 1) {
                                galleryBadge = `<span class="gallery-badge"><i class="mdi mdi-image-multiple"></i>${allImages.length}</span>`;
                            }

                            return `
                                <div class="d-flex align-items-center gap-3">
                                    <div class="service-avatar ${hasImages ? 'has-gallery open-carousel-btn' : ''}"
                                        data-name="${row.name}"
                                        data-images="${encodedImages}"
                                        title="${hasImages ? 'Click to view gallery carousel (' + allImages.length + ' images)' : ''}">
                                        ${avatarInner}
                                        ${galleryBadge}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">${row.name}</div>
                                        <small class="text-muted">
                                            ${hasImages ? '<a href="javascript:void(0)" class="text-primary open-carousel-btn" data-name="' + row.name + '" data-images="' + encodedImages + '"><i class="mdi mdi-image-multiple-outline me-1"></i>' + allImages.length + ' photo(s)</a>' : '<i class="mdi ' + iconClass + ' me-1"></i>' + iconClass}
                                        </small>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            let pills = '<div class="d-flex flex-column gap-1 align-items-start">';
                            if (row.category_name) {
                                pills += `<span class="scope-pill category"><i class="mdi mdi-tag-outline"></i> ${row.category_name}</span>`;
                            }
                            if (row.item_name) {
                                pills += `<span class="scope-pill item"><i class="mdi mdi-tshirt-crew-outline"></i> ${row.item_name}</span>`;
                            }
                            if (!row.category_name && !row.item_name) {
                                pills += `<span class="text-muted small">Global (All items)</span>`;
                            }
                            pills += '</div>';
                            return pills;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            const unitText = row.unit_short_name !== '-' ? ` / ${row.unit_short_name}` : '';
                            return `<span class="price-badge">৳${row.price}<span class="price-unit">${unitText}</span></span>`;
                        }
                    },
                    {
                        data: 'slug',
                        render: function(data) {
                            return `<span class="badge bg-light text-dark font-monospace">${data}</span>`;
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

                            @can('edit-services', 'admin')
                                buttons += `
                                    <button type="button" class="action-btn-edit edit-service-btn"
                                        data-id="${row.id}"
                                        data-unit-id="${row.unit_id || ''}"
                                        data-category-id="${row.item_category_id || ''}"
                                        data-item-id="${row.item_id || ''}"
                                        data-name="${row.name}"
                                        data-slug="${row.slug}"
                                        data-price="${row.price}"
                                        data-icon="${row.icon}"
                                        data-images='${JSON.stringify(row.all_images || [])}'
                                        data-description="${row.description || ''}"
                                        data-active="${row.is_active ? 1 : 0}"
                                        title="Edit Service">
                                        <i class="mdi mdi-pencil font-14"></i>
                                    </button>
                                    <button type="button" class="action-btn-toggle toggle-service-btn"
                                        data-id="${row.id}"
                                        data-active="${row.is_active ? 1 : 0}"
                                        title="Toggle Status">
                                        <i class="mdi ${row.is_active ? 'mdi-pause-circle-outline' : 'mdi-play-circle-outline'} font-14"></i>
                                    </button>
                                `;
                            @endcan

                            @can('delete-services', 'admin')
                                buttons += `
                                    <button type="button" class="action-btn-delete delete-service-btn"
                                        data-id="${row.id}"
                                        data-name="${row.name}"
                                        title="Delete Service">
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
                    searchPlaceholder: "Search services...",
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'></i>",
                        next: "<i class='mdi mdi-chevron-right'></i>"
                    }
                }
            });

            // Filter on Unit and Category change
            $('#filter_unit_id, #filter_category_id').on('change', function() {
                servicesTable.ajax.reload();
            });

            // Click Image or Photo link to Open Carousel Slider Modal
            $(document).on('click', '.open-carousel-btn', function() {
                const name = $(this).data('name');
                const rawImages = $(this).attr('data-images');
                if (!rawImages) return;

                let images = [];
                try {
                    images = JSON.parse(decodeURIComponent(rawImages));
                } catch(e) {
                    try {
                        images = JSON.parse(rawImages);
                    } catch(err) {
                        images = [];
                    }
                }

                if (!images || images.length === 0) return;

                $('#serviceCarouselTitle').text(name);
                $('#serviceCarouselSubtitle').text(`${images.length} Image${images.length > 1 ? 's' : ''} available`);

                const $indicators = $('#carouselIndicators').empty();
                const $inner = $('#carouselInner').empty();

                images.forEach((imgUrl, idx) => {
                    $indicators.append(`
                        <button type="button" data-bs-target="#serviceCarousel" data-bs-slide-to="${idx}" class="${idx === 0 ? 'active' : ''}" aria-current="${idx === 0 ? 'true' : 'false'}" aria-label="Slide ${idx + 1}"></button>
                    `);

                    $inner.append(`
                        <div class="carousel-item ${idx === 0 ? 'active' : ''}">
                            <img src="${imgUrl}" class="d-block w-100" alt="${name} image ${idx + 1}">
                        </div>
                    `);
                });

                // Update counter text on slide
                $('#carouselCounter').text(`Slide 1 of ${images.length}`);
                $('#serviceCarousel').off('slid.bs.carousel').on('slid.bs.carousel', function (e) {
                    $('#carouselCounter').text(`Slide ${e.to + 1} of ${images.length}`);
                });

                const carouselModal = new bootstrap.Modal(document.getElementById('serviceCarouselModal'));
                carouselModal.show();
            });

            // Reset create modal
            $('#createServiceModal').on('show.bs.modal', function() {
                if (!$('#create_name').val()) {
                    createSlugManualEdited = false;
                    $('#create_slug').val('');
                    $('#create_slug_feedback').html('<i class="mdi mdi-magic-staff"></i> Auto-generates unique slug from service name').attr('class', 'slug-status-indicator is-auto');
                    $('#create_images_input').val('');
                    $('#create_multi_preview').html('<span class="text-muted small"><i class="mdi mdi-image-plus me-1"></i>No images selected</span>');
                    $('#create_clear_images_btn').addClass('d-none');
                }
            });

            // Create Service Form Submit
            $('#createServiceForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#btnSaveService');
                const $spinner = $btn.find('.btn-spinner');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');

                const formData = new FormData(this);

                $.ajax({
                    url: "{{ route('admin.services.services.store') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#createServiceModal').modal('hide');
                        $('#createServiceForm')[0].reset();
                        createSlugManualEdited = false;
                        $('#create_icon').val('mdi-cube-outline').trigger('input');
                        $('#create_price').val('0.00');
                        $('#create_slug').val('');
                        $('#create_slug_feedback').html('<i class="mdi mdi-magic-staff"></i> Auto-generates unique slug from service name').attr('class', 'slug-status-indicator is-auto');
                        $('#create_multi_preview').html('<span class="text-muted small"><i class="mdi mdi-image-plus me-1"></i>No images selected</span>');
                        $('#create_clear_images_btn').addClass('d-none');
                        $('#create_is_active').prop('checked', true);

                        Swal.fire({
                            icon: 'success',
                            title: 'Service Created!',
                            text: response.message || 'Laundry service created successfully.',
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-4 shadow-lg' }
                        });

                        servicesTable.ajax.reload(null, false);
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
                            toastr.error(xhr.responseJSON?.message || 'Failed to create service');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                    }
                });
            });

            // Open Edit Modal
            $(document).on('click', '.edit-service-btn', function() {
                editSlugManualEdited = false;
                const id = $(this).data('id');
                const unitId = $(this).data('unit-id');
                const categoryId = $(this).data('category-id');
                const itemId = $(this).data('item-id');
                const name = $(this).data('name');
                const slug = $(this).data('slug');
                const price = $(this).data('price');
                const icon = $(this).data('icon') || 'mdi-cube-outline';
                const desc = $(this).data('description');
                const active = $(this).data('active');

                let existingImages = $(this).attr('data-images');
                try {
                    existingImages = JSON.parse(existingImages);
                } catch(e) {
                    existingImages = [];
                }

                $('#edit_service_id').val(id);
                $('#edit_unit_id').val(unitId);
                $('#edit_item_category_id').val(categoryId).trigger('change');
                $('#edit_item_id').val(itemId);
                $('#edit_name').val(name);
                $('#edit_price').val(price);
                $('#edit_slug').val(slug);
                $('#edit_icon').val(icon).trigger('input');
                $('#edit_description').val(desc === '-' ? '' : desc);
                $('#edit_is_active').prop('checked', active == 1);
                $('#edit_remove_gallery_images').val('0');
                $('#edit_remove_image').val('0');
                $('#edit_images_input').val('');

                const $editPreview = $('#edit_multi_preview').empty();
                if (existingImages && existingImages.length > 0) {
                    existingImages.forEach((img, idx) => {
                        const isCoverClass = idx === 0 ? 'is-cover' : '';
                        $editPreview.append(`
                            <div class="thumb-preview-box ${isCoverClass}">
                                <img src="${img}" alt="${name}">
                            </div>
                        `);
                    });
                    $('#edit_remove_images_btn').removeClass('d-none');
                } else {
                    $editPreview.html('<span class="text-muted small"><i class="mdi mdi-image-plus me-1"></i>No images uploaded</span>');
                    $('#edit_remove_images_btn').addClass('d-none');
                }

                $('#edit_slug_feedback').html('<i class="mdi mdi-check-circle text-success"></i> Current unique slug: <strong>' + slug + '</strong>').attr('class', 'slug-status-indicator is-unique');

                $('#editServiceModal').modal('show');
            });

            // Update Service Form Submit
            $('#editServiceForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#edit_service_id').val();
                const $btn = $('#btnUpdateService');
                const $spinner = $btn.find('.btn-spinner');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');

                let url = "{{ route('admin.services.services.update', ':id') }}";
                url = url.replace(':id', id);

                const formData = new FormData(this);

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#editServiceModal').modal('hide');

                        Swal.fire({
                            icon: 'success',
                            title: 'Service Updated!',
                            text: response.message || 'Laundry service updated successfully.',
                            timer: 2000,
                            showConfirmButton: false,
                            customClass: { popup: 'rounded-4 shadow-lg' }
                        });

                        servicesTable.ajax.reload(null, false);
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
                            toastr.error(xhr.responseJSON?.message || 'Failed to update service');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                    }
                });
            });

            // Toggle Service Status
            $(document).on('click', '.toggle-service-btn', function() {
                const id = $(this).data('id');

                let url = "{{ route('admin.services.services.toggle-status', ':id') }}";
                url = url.replace(':id', id);

                $.ajax({
                    url: url,
                    method: 'PATCH',
                    success: function(response) {
                        toastr.success(response.message || 'Status updated');
                        servicesTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to toggle status');
                    }
                });
            });

            // Delete Service with SweetAlert2
            $(document).on('click', '.delete-service-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Delete Laundry Service?',
                    html: `Are you sure you want to delete service <strong>"${name}"</strong>?<br><span class="text-danger small">This action cannot be undone.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="mdi mdi-delete me-1"></i> Yes, Delete Service',
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
                        let url = "{{ route('admin.services.services.destroy', ':id') }}";
                        url = url.replace(':id', id);

                        $.ajax({
                            url: url,
                            method: 'DELETE',
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message || `Service "${name}" was deleted successfully.`,
                                    timer: 2000,
                                    showConfirmButton: false,
                                    customClass: { popup: 'rounded-4 shadow-lg' }
                                });
                                servicesTable.ajax.reload(null, false);
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Delete Failed',
                                    text: xhr.responseJSON?.message || 'Failed to delete service.',
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
