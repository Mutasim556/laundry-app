@extends('admin::layouts.app')

@section('title', 'Stores Management - Branches & Locations')

@push('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/sweetalert2/dist/sweetalert2.min.css') }}">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/toastr/toastr.min.css') }}">

    <style>
        .stores-hero {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border-radius: 1.25rem;
            padding: 2.25rem 2.5rem;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(2, 132, 199, 0.25);
            margin-bottom: 2rem;
        }
        .stores-hero::after {
            content: '';
            position: absolute;
            top: -40%;
            right: -10%;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.18) 0%, rgba(255, 255, 255, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .stores-hero-title {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            margin-bottom: 0.35rem;
            color: #ffffff;
        }
        .stores-hero-subtitle {
            font-size: 0.95rem;
            color: #e0f2fe;
            margin-bottom: 0;
        }
        .btn-hero-add {
            background: #ffffff;
            color: #0369a1 !important;
            border: none;
            border-radius: 50rem;
            padding: 0.75rem 1.6rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        .btn-hero-add:hover {
            background: #f0f9ff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.18);
        }
        .stores-card {
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.05);
            background: #ffffff;
            overflow: hidden;
        }
        .store-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #0284c7;
        }
        .badge-ownership {
            font-size: 0.72rem;
            padding: 0.25rem 0.65rem;
            border-radius: 50rem;
            font-weight: 700;
        }
        .badge-ownership.my_store { background: #e0e7ff; color: #4338ca; }
        .badge-ownership.vendor_store { background: #fef3c7; color: #b45309; }

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

        .location-pill {
            background: #f1f5f9;
            color: #334155;
            border-radius: 6px;
            padding: 2px 7px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .service-badge-pill {
            background: #e0f2fe;
            color: #0369a1;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            margin-right: 2px;
            margin-bottom: 2px;
            display: inline-block;
        }

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
        .action-btn.btn-edit:hover { color: #0284c7; border-color: #bae6fd; background: #f0f9ff; }
        .action-btn.btn-toggle:hover { color: #d97706; border-color: #fde68a; background: #fffbeb; }
        .action-btn.btn-delete:hover { color: #ef4444; border-color: #fecaca; background: #fef2f2; }

        /* Form Modal Tabs & Sections */
        .modal-section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            padding-bottom: 0.4rem;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .nav-tabs-store .nav-link {
            font-weight: 600;
            color: #64748b;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 0.75rem 1.25rem;
        }
        .nav-tabs-store .nav-link.active {
            color: #0284c7;
            background: transparent;
            border-bottom-color: #0284c7;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Hero Banner -->
    <div class="stores-hero">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2" style="background: transparent; padding: 0;">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-white-50 text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item text-white-50">Store Management</li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Stores</li>
                    </ol>
                </nav>
                <h1 class="stores-hero-title">Laundry Stores & Branches</h1>
                <p class="stores-hero-subtitle">Manage company-owned stores and vendor branches with linked Bangladesh divisions, districts, upazilas, and delivery coverage zones.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                @can('create-stores', 'admin')
                <button type="button" class="btn btn-hero-add" data-bs-toggle="modal" data-bs-target="#createStoreModal">
                    <i class="mdi mdi-plus-circle font-18"></i>
                    <span>Add New Store</span>
                </button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Stores Table Card -->
    <div class="stores-card">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="storesTable" class="table table-hover align-middle mb-0 w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Store Details</th>
                            <th>Ownership & Manager</th>
                            <th>BD Location</th>
                            <th>Service & Delivery</th>
                            <th>Status & Orders</th>
                            <th class="text-end" style="width: 110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Store Modal (Multi-Tab Comprehensive Store Form) -->
<div class="modal fade" id="createStoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-light border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="mdi mdi-store text-primary font-24"></i>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Create New Laundry Store / Branch</h5>
                        <small class="text-muted">Configure ownership, BD geo locations, delivery areas, and operating details</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="createStoreForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-danger d-none" id="createStoreErrors"></div>

                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs nav-tabs-store mb-4" id="createStoreTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-ownership" type="button">
                                <i class="mdi mdi-domain me-1"></i> 1. Ownership & Admin
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-basic" type="button">
                                <i class="mdi mdi-information-outline me-1"></i> 2. Basic Info & Branding
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-location" type="button">
                                <i class="mdi mdi-map-marker me-1"></i> 3. Bangladesh Location
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-services" type="button">
                                <i class="mdi mdi-truck me-1"></i> 4. Services & Delivery
                            </button>
                        </li>
                        <li class="nav-item" id="createVendorTabNav">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-vendor-info" type="button">
                                <i class="mdi mdi-certificate me-1"></i> 5. Vendor Details
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="createStoreTabsContent">
                        <!-- TAB 1: Ownership -->
                        <div class="tab-pane fade show active" id="tab-ownership">
                            <div class="modal-section-title">
                                <i class="mdi mdi-shield-account text-primary"></i> Store Ownership & Status
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Store Ownership Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="create_ownership_type" name="ownership_type" required>
                                        <option value="my_store" selected>My Store (Company-Owned Branch)</option>
                                        <option value="vendor_store">Vendor Store (Partner Franchise / Marketplace)</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="createVendorSelectCol" style="display: none;">
                                    <label class="form-label fw-semibold small mb-1">Select Registered Vendor <span class="text-danger">*</span></label>
                                    <select class="form-select" id="create_vendor_id" name="vendor_id">
                                        <option value="">Select Vendor...</option>
                                        @foreach($vendors as $v)
                                            <option value="{{ $v->id }}">{{ $v->name }} (Owner: {{ $v->owner_name }})</option>
                                        @endforeach
                                    </select>
                                    <div class="small text-muted mt-1">Don't see vendor? <a href="{{ route('admin.stores.vendors.index') }}" target="_blank">Register vendor here</a></div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Store Status <span class="text-danger">*</span></label>
                                    <select class="form-select" name="status" required>
                                        <option value="active" selected>Active (Operational)</option>
                                        <option value="pending">Pending Approval</option>
                                        <option value="suspended">Suspended</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Store Administrator (Manager)</label>
                                    <select class="form-select" name="admin_id">
                                        <option value="">Select Administrator...</option>
                                        @foreach($admins as $adm)
                                            <option value="{{ $adm->id }}">{{ $adm->name }} ({{ $adm->email }})</option>
                                        @endforeach
                                    </select>
                                    <div class="small text-muted mt-1">Person responsible for store orders and operations</div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: Basic Info & Branding -->
                        <div class="tab-pane fade" id="tab-basic">
                            <div class="modal-section-title">
                                <i class="mdi mdi-storefront text-primary"></i> Basic Store Information
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Store Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="create_name" name="name" placeholder="e.g. Laundry Express Dhanmondi" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Bangla Name (ঐচ্ছিক বাংলা নাম)</label>
                                    <input type="text" class="form-control" name="bn_name" placeholder="যেমন: লন্ড্রি এক্সপ্রেস ধানমন্ডি">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Unique Slug</label>
                                    <input type="text" class="form-control font-monospace" id="create_slug" name="slug" placeholder="e.g. laundry-express-dhanmondi">
                                    <small class="text-muted" id="create_slug_feedback">Auto-generated from store name</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Store Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="store_type" required>
                                        <option value="laundry" selected>Laundry Services Only</option>
                                        <option value="dry_cleaning">Dry Cleaning Only</option>
                                        <option value="both">Both Laundry & Dry Cleaning</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Contact Person</label>
                                    <input type="text" class="form-control" name="contact_person" placeholder="Branch Manager">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Primary Phone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="phone" placeholder="017xxxxxxxx" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Alternative Phone</label>
                                    <input type="text" class="form-control" name="alt_phone" placeholder="018xxxxxxxx">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Email Address</label>
                                    <input type="email" class="form-control" name="email" placeholder="dhanmondi@laundry.com">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small mb-1">Store Logo</label>
                                    <input type="file" class="form-control" name="logo" accept="image/*">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small mb-1">Cover Image</label>
                                    <input type="file" class="form-control" name="cover_image" accept="image/*">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small mb-1">Short Description / Bio</label>
                                <textarea class="form-control" name="description" rows="2" placeholder="Brief description of this branch or facility..."></textarea>
                            </div>
                        </div>

                        <!-- TAB 3: Bangladesh Location -->
                        <div class="tab-pane fade" id="tab-location">
                            <div class="modal-section-title">
                                <i class="mdi mdi-map-marker-radius text-primary"></i> Linked Bangladesh Location (Division &rarr; District &rarr; Upazila/Thana &rarr; Area)
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small mb-1">Division <span class="text-danger">*</span></label>
                                    <select class="form-select geo-division" id="create_division_id" name="division_id" required>
                                        <option value="">Select Division...</option>
                                        @foreach($divisions as $div)
                                            <option value="{{ $div->id }}">{{ $div->name }} ({{ $div->bn_name }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small mb-1">District <span class="text-danger">*</span></label>
                                    <select class="form-select geo-district" id="create_district_id" name="district_id" required disabled>
                                        <option value="">Select District...</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small mb-1">Upazila / Thana <span class="text-danger">*</span></label>
                                    <select class="form-select geo-upazila" id="create_upazila_id" name="upazila_id" required disabled>
                                        <option value="">Select Upazila/Thana...</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small mb-1">Local Area / Neighbourhood</label>
                                    <select class="form-select geo-area" id="create_local_area_id" name="local_area_id" disabled>
                                        <option value="">Select Local Area...</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Custom Area Name (if not in list)</label>
                                    <input type="text" class="form-control" name="area_name" placeholder="e.g. Shankar, Jigatola">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">House / Holding No.</label>
                                    <input type="text" class="form-control" name="holding_no" placeholder="House 42/A">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Road No. / Name</label>
                                    <input type="text" class="form-control" name="road_no" placeholder="Road 9/A, Satmasjid Road">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Nearby Landmark</label>
                                    <input type="text" class="form-control" name="nearby_landmark" placeholder="e.g. Opposite to Dhanmondi Lake / Near Shimanto Square">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Postcode</label>
                                    <input type="text" class="form-control" id="create_postcode" name="postcode" placeholder="1205">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small mb-1">Full Street Address <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="create_full_address" name="full_address" rows="2" placeholder="House #, Road #, Area, Upazila, District" required></textarea>
                            </div>
                        </div>

                        <!-- TAB 4: Services & Delivery -->
                        <div class="tab-pane fade" id="tab-services">
                            <div class="modal-section-title">
                                <i class="mdi mdi-washing-machine text-primary"></i> Services Offered & Turnaround
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small mb-2">Select Services Offered by this Store:</label>
                                <div class="row g-2">
                                    @foreach($services as $srv)
                                        <div class="col-md-4">
                                            <div class="form-check p-2 border rounded-3 bg-light">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" name="service_ids[]" value="{{ $srv->id }}" id="create_srv_{{ $srv->id }}">
                                                <label class="form-check-label fw-semibold text-dark small" for="create_srv_{{ $srv->id }}">
                                                    {{ $srv->name }} (৳{{ $srv->price }})
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Pricing Model</label>
                                    <select class="form-select" name="pricing_model">
                                        <option value="per_item">Per Item</option>
                                        <option value="per_kg">Per Kilogram (Kg)</option>
                                        <option value="per_bag">Per Bag</option>
                                        <option value="hybrid" selected>Standard Menu Pricing</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Minimum Order Amount (৳ BDT)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="min_order_amount" value="150.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Estimated Turnaround Time</label>
                                    <input type="text" class="form-control" name="turnaround_time" placeholder="e.g. 24-48 Hours, Express 12 Hours">
                                </div>
                            </div>

                            <div class="modal-section-title mt-4">
                                <i class="mdi mdi-truck-delivery text-primary"></i> Pickup & Delivery Settings & Service Areas
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" id="create_has_delivery" name="has_delivery" value="1" checked>
                                        <label class="form-check-label fw-bold text-dark" for="create_has_delivery">
                                            Pickup & Delivery Available
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Delivery Fee (৳ BDT)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="delivery_fee" value="50.00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Free Delivery Above (৳ BDT)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="free_delivery_threshold" placeholder="e.g. 500.00 (Optional)">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small mb-1">
                                    Service Coverage Areas (Separated from store physical address)
                                </label>
                                <textarea class="form-control" name="service_areas_text" rows="2" placeholder="e.g. Dhanmondi, Mohammadpur, Lalmatia, Kalabagan, Sobhanbag, Shankar"></textarea>
                                <small class="text-muted">Customers in these neighbourhoods will find this store during search.</small>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="create_is_accepting_orders" name="is_accepting_orders" value="1" checked>
                                <label class="form-check-label fw-bold text-dark" for="create_is_accepting_orders">
                                    Accepting Customer Orders Now (Toggle off when temporarily overloaded)
                                </label>
                            </div>
                        </div>

                        <!-- TAB 5: Vendor Details -->
                        <div class="tab-pane fade" id="tab-vendor-info">
                            <div class="modal-section-title">
                                <i class="mdi mdi-account-cash text-primary"></i> Marketplace Vendor Agreement
                            </div>
                            <p class="text-muted small">Applicable when ownership is set to <strong>Vendor store</strong>.</p>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Commission Override Type</label>
                                    <select class="form-select" name="commission_type">
                                        <option value="">Inherit from Vendor Account Profile</option>
                                        <option value="percentage">Percentage (%)</option>
                                        <option value="fixed">Fixed Per Order (৳ BDT)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Commission Rate</label>
                                    <input type="number" step="0.01" min="0" class="form-control" name="commission_rate" placeholder="e.g. 12.00">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small mb-1">Branch Specific Verification Notes</label>
                                <textarea class="form-control" name="verification_notes" rows="2" placeholder="Branch inspection notes, physical setup checks..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnSaveStore">Save Store</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Store Modal -->
<div class="modal fade" id="editStoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-light border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="mdi mdi-pencil text-primary font-24"></i>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">Edit Store / Branch</h5>
                        <small class="text-muted">Update store ownership, location and delivery preferences</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="editStoreForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_store_id" name="id">

                <div class="modal-body p-4">
                    <div class="alert alert-danger d-none" id="editStoreErrors"></div>

                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs nav-tabs-store mb-4" id="editStoreTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#edit-tab-ownership" type="button">
                                <i class="mdi mdi-domain me-1"></i> 1. Ownership & Admin
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#edit-tab-basic" type="button">
                                <i class="mdi mdi-information-outline me-1"></i> 2. Basic Info & Branding
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#edit-tab-location" type="button">
                                <i class="mdi mdi-map-marker me-1"></i> 3. Bangladesh Location
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#edit-tab-services" type="button">
                                <i class="mdi mdi-truck me-1"></i> 4. Services & Delivery
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#edit-tab-vendor-info" type="button">
                                <i class="mdi mdi-certificate me-1"></i> 5. Vendor Details
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="editStoreTabsContent">
                        <!-- TAB 1: Ownership -->
                        <div class="tab-pane fade show active" id="edit-tab-ownership">
                            <div class="modal-section-title">
                                <i class="mdi mdi-shield-account text-primary"></i> Store Ownership & Status
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Store Ownership Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_ownership_type" name="ownership_type" required>
                                        <option value="my_store">My Store (Company-Owned Branch)</option>
                                        <option value="vendor_store">Vendor Store (Partner Franchise / Marketplace)</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="editVendorSelectCol">
                                    <label class="form-label fw-semibold small mb-1">Select Registered Vendor</label>
                                    <select class="form-select" id="edit_vendor_id" name="vendor_id">
                                        <option value="">Select Vendor...</option>
                                        @foreach($vendors as $v)
                                            <option value="{{ $v->id }}">{{ $v->name }} (Owner: {{ $v->owner_name }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Store Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_status" name="status" required>
                                        <option value="active">Active (Operational)</option>
                                        <option value="pending">Pending Approval</option>
                                        <option value="suspended">Suspended</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Store Administrator (Manager)</label>
                                    <select class="form-select" id="edit_admin_id" name="admin_id">
                                        <option value="">Select Administrator...</option>
                                        @foreach($admins as $adm)
                                            <option value="{{ $adm->id }}">{{ $adm->name }} ({{ $adm->email }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: Basic Info & Branding -->
                        <div class="tab-pane fade" id="edit-tab-basic">
                            <div class="modal-section-title">
                                <i class="mdi mdi-storefront text-primary"></i> Basic Store Information
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Store Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Bangla Name</label>
                                    <input type="text" class="form-control" id="edit_bn_name" name="bn_name">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Unique Slug</label>
                                    <input type="text" class="form-control font-monospace" id="edit_slug" name="slug">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Store Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_store_type" name="store_type" required>
                                        <option value="laundry">Laundry Services Only</option>
                                        <option value="dry_cleaning">Dry Cleaning Only</option>
                                        <option value="both">Both Laundry & Dry Cleaning</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Contact Person</label>
                                    <input type="text" class="form-control" id="edit_contact_person" name="contact_person">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Primary Phone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_phone" name="phone" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Alternative Phone</label>
                                    <input type="text" class="form-control" id="edit_alt_phone" name="alt_phone">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Email Address</label>
                                    <input type="email" class="form-control" id="edit_email" name="email">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small mb-1">Replace Logo</label>
                                    <input type="file" class="form-control" name="logo" accept="image/*">
                                    <div id="edit_logo_preview" class="mt-1 small"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small mb-1">Replace Cover Image</label>
                                    <input type="file" class="form-control" name="cover_image" accept="image/*">
                                    <div id="edit_cover_preview" class="mt-1 small"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small mb-1">Short Description / Bio</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- TAB 3: Bangladesh Location -->
                        <div class="tab-pane fade" id="edit-tab-location">
                            <div class="modal-section-title">
                                <i class="mdi mdi-map-marker-radius text-primary"></i> Linked Bangladesh Location
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small mb-1">Division <span class="text-danger">*</span></label>
                                    <select class="form-select geo-division" id="edit_division_id" name="division_id" required>
                                        <option value="">Select Division...</option>
                                        @foreach($divisions as $div)
                                            <option value="{{ $div->id }}">{{ $div->name }} ({{ $div->bn_name }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small mb-1">District <span class="text-danger">*</span></label>
                                    <select class="form-select geo-district" id="edit_district_id" name="district_id" required>
                                        <option value="">Select District...</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small mb-1">Upazila / Thana <span class="text-danger">*</span></label>
                                    <select class="form-select geo-upazila" id="edit_upazila_id" name="upazila_id" required>
                                        <option value="">Select Upazila/Thana...</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small mb-1">Local Area / Neighbourhood</label>
                                    <select class="form-select geo-area" id="edit_local_area_id" name="local_area_id">
                                        <option value="">Select Local Area...</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Custom Area Name</label>
                                    <input type="text" class="form-control" id="edit_area_name" name="area_name">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">House / Holding No.</label>
                                    <input type="text" class="form-control" id="edit_holding_no" name="holding_no">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Road No. / Name</label>
                                    <input type="text" class="form-control" id="edit_road_no" name="road_no">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Nearby Landmark</label>
                                    <input type="text" class="form-control" id="edit_nearby_landmark" name="nearby_landmark">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Postcode</label>
                                    <input type="text" class="form-control" id="edit_postcode" name="postcode">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small mb-1">Full Street Address <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="edit_full_address" name="full_address" rows="2" required></textarea>
                            </div>
                        </div>

                        <!-- TAB 4: Services & Delivery -->
                        <div class="tab-pane fade" id="edit-tab-services">
                            <div class="modal-section-title">
                                <i class="mdi mdi-washing-machine text-primary"></i> Services Offered & Turnaround
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small mb-2">Select Services Offered by this Store:</label>
                                <div class="row g-2">
                                    @foreach($services as $srv)
                                        <div class="col-md-4">
                                            <div class="form-check p-2 border rounded-3 bg-light">
                                                <input class="form-check-input edit-srv-cb ms-0 me-2" type="checkbox" name="service_ids[]" value="{{ $srv->id }}" id="edit_srv_{{ $srv->id }}">
                                                <label class="form-check-label fw-semibold text-dark small" for="edit_srv_{{ $srv->id }}">
                                                    {{ $srv->name }} (৳{{ $srv->price }})
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Pricing Model</label>
                                    <select class="form-select" id="edit_pricing_model" name="pricing_model">
                                        <option value="per_item">Per Item</option>
                                        <option value="per_kg">Per Kilogram (Kg)</option>
                                        <option value="per_bag">Per Bag</option>
                                        <option value="hybrid">Standard Menu Pricing</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Minimum Order Amount (৳ BDT)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="edit_min_order_amount" name="min_order_amount">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Estimated Turnaround Time</label>
                                    <input type="text" class="form-control" id="edit_turnaround_time" name="turnaround_time">
                                </div>
                            </div>

                            <div class="modal-section-title mt-4">
                                <i class="mdi mdi-truck-delivery text-primary"></i> Pickup & Delivery Settings & Service Areas
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" id="edit_has_delivery" name="has_delivery" value="1">
                                        <label class="form-check-label fw-bold text-dark" for="edit_has_delivery">
                                            Pickup & Delivery Available
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Delivery Fee (৳ BDT)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="edit_delivery_fee" name="delivery_fee">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold small mb-1">Free Delivery Above (৳ BDT)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="edit_free_delivery_threshold" name="free_delivery_threshold">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small mb-1">
                                    Service Coverage Areas
                                </label>
                                <textarea class="form-control" id="edit_service_areas_text" name="service_areas_text" rows="2"></textarea>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="edit_is_accepting_orders" name="is_accepting_orders" value="1">
                                <label class="form-check-label fw-bold text-dark" for="edit_is_accepting_orders">
                                    Accepting Customer Orders Now
                                </label>
                            </div>
                        </div>

                        <!-- TAB 5: Vendor Details -->
                        <div class="tab-pane fade" id="edit-tab-vendor-info">
                            <div class="modal-section-title">
                                <i class="mdi mdi-account-cash text-primary"></i> Marketplace Vendor Agreement
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Commission Override Type</label>
                                    <select class="form-select" id="edit_commission_type" name="commission_type">
                                        <option value="">Inherit from Vendor Account Profile</option>
                                        <option value="percentage">Percentage (%)</option>
                                        <option value="fixed">Fixed Per Order (৳ BDT)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small mb-1">Commission Rate</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="edit_commission_rate" name="commission_rate">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small mb-1">Branch Specific Verification Notes</label>
                                <textarea class="form-control" id="edit_verification_notes" name="verification_notes" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnUpdateStore">Save Changes</button>
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

            // Ownership type toggles vendor selection
            $('#create_ownership_type').on('change', function() {
                if ($(this).val() === 'vendor_store') {
                    $('#createVendorSelectCol').slideDown();
                    $('#create_vendor_id').prop('required', true);
                } else {
                    $('#createVendorSelectCol').slideUp();
                    $('#create_vendor_id').prop('required', false).val('');
                }
            });

            $('#edit_ownership_type').on('change', function() {
                if ($(this).val() === 'vendor_store') {
                    $('#editVendorSelectCol').slideDown();
                    $('#edit_vendor_id').prop('required', true);
                } else {
                    $('#editVendorSelectCol').slideUp();
                    $('#edit_vendor_id').prop('required', false).val('');
                }
            });

            // Auto-slug generator with debouncing
            let slugTimer;
            $('#create_name').on('input', function() {
                clearTimeout(slugTimer);
                const name = $(this).val();
                slugTimer = setTimeout(function() {
                    if (name.trim()) {
                        $.get('{{ route('admin.stores.check-slug') }}', { name: name }, function(res) {
                            $('#create_slug').val(res.slug);
                        });
                    }
                }, 300);
            });

            // Bangladesh Linked Dropdowns Loader Helper
            function loadDistricts(divId, $districtSelect, selectedId = null, callback = null) {
                $districtSelect.html('<option value="">Loading districts...</option>').prop('disabled', true);
                if (!divId) {
                    $districtSelect.html('<option value="">Select District...</option>').prop('disabled', true);
                    return;
                }
                $.get('{{ route('admin.stores.geo.districts') }}', { division_id: divId }, function(data) {
                    let html = '<option value="">Select District...</option>';
                    data.forEach(d => {
                        const sel = selectedId && selectedId == d.id ? 'selected' : '';
                        html += `<option value="${d.id}" ${sel}>${d.name} (${d.bn_name})</option>`;
                    });
                    $districtSelect.html(html).prop('disabled', false);
                    if (callback) callback();
                });
            }

            function loadUpazilas(distId, $upazilaSelect, selectedId = null, callback = null) {
                $upazilaSelect.html('<option value="">Loading upazilas...</option>').prop('disabled', true);
                if (!distId) {
                    $upazilaSelect.html('<option value="">Select Upazila/Thana...</option>').prop('disabled', true);
                    return;
                }
                $.get('{{ route('admin.stores.geo.upazilas') }}', { district_id: distId }, function(data) {
                    let html = '<option value="">Select Upazila/Thana...</option>';
                    data.forEach(u => {
                        const sel = selectedId && selectedId == u.id ? 'selected' : '';
                        html += `<option value="${u.id}" ${sel}>${u.name} (${u.bn_name})</option>`;
                    });
                    $upazilaSelect.html(html).prop('disabled', false);
                    if (callback) callback();
                });
            }

            function loadLocalAreas(upaId, $areaSelect, selectedId = null, callback = null) {
                $areaSelect.html('<option value="">Loading areas...</option>').prop('disabled', true);
                if (!upaId) {
                    $areaSelect.html('<option value="">Select Local Area...</option>').prop('disabled', true);
                    return;
                }
                $.get('{{ route('admin.stores.geo.local-areas') }}', { upazila_id: upaId }, function(data) {
                    let html = '<option value="">Select Local Area...</option>';
                    data.forEach(a => {
                        const sel = selectedId && selectedId == a.id ? 'selected' : '';
                        html += `<option value="${a.id}" data-postcode="${a.postcode || ''}" ${sel}>${a.name} (${a.bn_name})</option>`;
                    });
                    $areaSelect.html(html).prop('disabled', false);
                    if (callback) callback();
                });
            }

            // Create Modal Linked Dropdowns
            $('#create_division_id').on('change', function() {
                loadDistricts($(this).val(), $('#create_district_id'));
                $('#create_upazila_id').html('<option value="">Select Upazila/Thana...</option>').prop('disabled', true);
                $('#create_local_area_id').html('<option value="">Select Local Area...</option>').prop('disabled', true);
            });

            $('#create_district_id').on('change', function() {
                loadUpazilas($(this).val(), $('#create_upazila_id'));
                $('#create_local_area_id').html('<option value="">Select Local Area...</option>').prop('disabled', true);
            });

            $('#create_upazila_id').on('change', function() {
                loadLocalAreas($(this).val(), $('#create_local_area_id'));
            });

            $('#create_local_area_id').on('change', function() {
                const pc = $(this).find(':selected').data('postcode');
                if (pc) $('#create_postcode').val(pc);
            });

            // Edit Modal Linked Dropdowns
            $('#edit_division_id').on('change', function() {
                loadDistricts($(this).val(), $('#edit_district_id'));
                $('#edit_upazila_id').html('<option value="">Select Upazila/Thana...</option>').prop('disabled', true);
                $('#edit_local_area_id').html('<option value="">Select Local Area...</option>').prop('disabled', true);
            });

            $('#edit_district_id').on('change', function() {
                loadUpazilas($(this).val(), $('#edit_upazila_id'));
                $('#edit_local_area_id').html('<option value="">Select Local Area...</option>').prop('disabled', true);
            });

            $('#edit_upazila_id').on('change', function() {
                loadLocalAreas($(this).val(), $('#edit_local_area_id'));
            });

            $('#edit_local_area_id').on('change', function() {
                const pc = $(this).find(':selected').data('postcode');
                if (pc) $('#edit_postcode').val(pc);
            });

            // DataTable Initialization
            const storesTable = $('#storesTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: '{{ route('admin.stores.data') }}',
                columns: [
                    {
                        data: null,
                        render: (d, t, r, meta) => `<span class="fw-bold text-muted">${meta.row + 1}</span>`
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            const logo = row.logo_url 
                                ? `<img src="${row.logo_url}" class="store-avatar me-3">` 
                                : `<div class="store-avatar me-3"><i class="mdi mdi-store"></i></div>`;
                            return `
                                <div class="d-flex align-items-center">
                                    ${logo}
                                    <div>
                                        <div class="fw-bold text-dark fs-6">${row.name}</div>
                                        ${row.bn_name !== '-' ? `<div class="text-muted small">${row.bn_name}</div>` : ''}
                                        <div class="font-monospace small text-muted"><i class="mdi mdi-phone font-12 text-primary me-1"></i>${row.phone}</div>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <div>
                                    <span class="badge-ownership ${row.ownership_type}">
                                        ${row.ownership_type === 'my_store' ? '<i class="mdi mdi-home me-1"></i>My Store' : '<i class="mdi mdi-briefcase me-1"></i>Vendor: ' + row.vendor_name}
                                    </span>
                                    <div class="small text-muted mt-1">
                                        <i class="mdi mdi-account-star text-info me-1"></i>Mgr: <strong>${row.admin_name}</strong>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <div>
                                    <span class="location-pill mb-1"><i class="mdi mdi-map-marker text-danger"></i> ${row.upazila_name}, ${row.district_name}</span>
                                    <div class="small text-dark fw-semibold">${row.area_name || row.division_name}</div>
                                    <div class="small text-muted text-truncate" style="max-width: 200px;" title="${row.full_address}">${row.full_address}</div>
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            const deliveryBadge = row.has_delivery 
                                ? `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-11"><i class="mdi mdi-truck me-1"></i>Delivery (৳${row.delivery_fee})</span>` 
                                : `<span class="badge bg-secondary-subtle text-muted rounded-pill font-11">Pickup Only</span>`;
                            const minOrder = row.min_order_amount > 0 ? `<div class="text-muted small mt-1">Min: ৳${row.min_order_amount}</div>` : '';
                            return `
                                <div>
                                    ${deliveryBadge}
                                    ${minOrder}
                                    ${row.turnaround_time ? `<div class="small text-muted"><i class="mdi mdi-clock-outline me-1"></i>${row.turnaround_time}</div>` : ''}
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            const statusBadge = `<span class="status-badge ${row.status}">${row.status}</span>`;
                            const ordersBadge = row.is_accepting_orders 
                                ? `<span class="badge bg-success text-white rounded-pill font-11 d-block mt-1">Accepting Orders</span>` 
                                : `<span class="badge bg-danger text-white rounded-pill font-11 d-block mt-1">Orders Paused</span>`;
                            return `<div>${statusBadge}${ordersBadge}</div>`;
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
                                    <button type="button" class="action-btn btn-toggle toggle-orders-btn" data-id="${row.id}" title="Toggle Order Acceptance">
                                        <i class="mdi ${row.is_accepting_orders ? 'mdi-pause' : 'mdi-play'} font-15"></i>
                                    </button>
                                    <button type="button" class="action-btn btn-edit edit-store-btn" data-store='${JSON.stringify(row).replace(/'/g, "&apos;")}' title="Edit Store">
                                        <i class="mdi mdi-pencil font-15"></i>
                                    </button>
                                    <button type="button" class="action-btn btn-delete delete-store-btn" data-id="${row.id}" data-name="${row.name}" title="Delete Store">
                                        <i class="mdi mdi-delete font-15"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[0, 'asc']]
            });

            // Create Store Form Submission
            $('#createStoreForm').on('submit', function(e) {
                e.preventDefault();
                const $btn = $('#btnSaveStore').prop('disabled', true);
                const formData = new FormData(this);

                $.ajax({
                    url: '{{ route('admin.stores.store') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#createStoreModal').modal('hide');
                        $('#createStoreForm')[0].reset();
                        $('#createStoreErrors').addClass('d-none').empty();
                        toastr.success(res.message);
                        storesTable.ajax.reload();
                    },
                    error: function(xhr) {
                        const errs = xhr.responseJSON?.errors;
                        let msg = '<ul>';
                        if (errs) {
                            Object.values(errs).forEach(e => msg += `<li>${e[0]}</li>`);
                        } else {
                            msg += `<li>${xhr.responseJSON?.message || 'Error creating store'}</li>`;
                        }
                        msg += '</ul>';
                        $('#createStoreErrors').removeClass('d-none').html(msg);
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });

            // Open Edit Store Modal
            $(document).on('click', '.edit-store-btn', function() {
                const store = $(this).data('store');
                $('#edit_store_id').val(store.id);
                $('#edit_ownership_type').val(store.ownership_type).trigger('change');
                $('#edit_vendor_id').val(store.vendor_id || '');
                $('#edit_admin_id').val(store.admin_id || '');
                $('#edit_status').val(store.status);
                $('#edit_name').val(store.name);
                $('#edit_bn_name').val(store.bn_name !== '-' ? store.bn_name : '');
                $('#edit_slug').val(store.slug);
                $('#edit_store_type').val(store.store_type);
                $('#edit_contact_person').val(store.contact_person);
                $('#edit_phone').val(store.phone);
                $('#edit_alt_phone').val(store.alt_phone);
                $('#edit_email').val(store.email);
                $('#edit_description').val(store.description);

                // Address fields
                $('#edit_holding_no').val(store.holding_no);
                $('#edit_road_no').val(store.road_no);
                $('#edit_nearby_landmark').val(store.nearby_landmark);
                $('#edit_full_address').val(store.full_address);
                $('#edit_area_name').val(store.area_name);
                $('#edit_postcode').val(store.postcode);

                // Services & Delivery
                $('#edit_pricing_model').val(store.pricing_model);
                $('#edit_min_order_amount').val(store.min_order_amount);
                $('#edit_turnaround_time').val(store.turnaround_time);
                $('#edit_has_delivery').prop('checked', store.has_delivery);
                $('#edit_delivery_fee').val(store.delivery_fee);
                $('#edit_free_delivery_threshold').val(store.free_delivery_threshold || '');
                $('#edit_service_areas_text').val(store.service_areas_text);
                $('#edit_is_accepting_orders').prop('checked', store.is_accepting_orders);

                // Checkboxes for services
                $('.edit-srv-cb').prop('checked', false);
                if (Array.isArray(store.service_ids)) {
                    store.service_ids.forEach(id => {
                        $(`#edit_srv_${id}`).prop('checked', true);
                    });
                }

                // Vendor specifics
                $('#edit_commission_type').val(store.commission_type || '');
                $('#edit_commission_rate').val(store.commission_rate || '');
                $('#edit_verification_notes').val(store.verification_notes);

                // Image previews
                if (store.logo_url) {
                    $('#edit_logo_preview').html(`<img src="${store.logo_url}" width="36" height="36" class="rounded"> Current logo`);
                } else {
                    $('#edit_logo_preview').empty();
                }

                if (store.cover_image_url) {
                    $('#edit_cover_preview').html(`<img src="${store.cover_image_url}" width="60" height="36" class="rounded object-fit-cover"> Current cover`);
                } else {
                    $('#edit_cover_preview').empty();
                }

                // Load BD Linked Dropdowns dynamically for Edit Modal
                $('#edit_division_id').val(store.division_id);
                loadDistricts(store.division_id, $('#edit_district_id'), store.district_id, function() {
                    loadUpazilas(store.district_id, $('#edit_upazila_id'), store.upazila_id, function() {
                        loadLocalAreas(store.upazila_id, $('#edit_local_area_id'), store.local_area_id);
                    });
                });

                $('#editStoreErrors').addClass('d-none').empty();
                $('#editStoreModal').modal('show');
            });

            // Update Store Form Submission
            $('#editStoreForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#edit_store_id').val();
                const $btn = $('#btnUpdateStore').prop('disabled', true);
                const formData = new FormData(this);

                $.ajax({
                    url: '{{ url('admin/stores') }}/' + id,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#editStoreModal').modal('hide');
                        toastr.success(res.message);
                        storesTable.ajax.reload();
                    },
                    error: function(xhr) {
                        const errs = xhr.responseJSON?.errors;
                        let msg = '<ul>';
                        if (errs) {
                            Object.values(errs).forEach(e => msg += `<li>${e[0]}</li>`);
                        } else {
                            msg += `<li>${xhr.responseJSON?.message || 'Error updating store'}</li>`;
                        }
                        msg += '</ul>';
                        $('#editStoreErrors').removeClass('d-none').html(msg);
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });

            // Toggle accepting orders
            $(document).on('click', '.toggle-orders-btn', function() {
                const id = $(this).data('id');
                $.ajax({
                    url: '{{ url('admin/stores') }}/' + id + '/toggle-orders',
                    method: 'PATCH',
                    success: function(res) {
                        toastr.info(res.message);
                        storesTable.ajax.reload();
                    },
                    error: function(xhr) {
                        toastr.error('Could not toggle order acceptance status');
                    }
                });
            });

            // Delete Store
            $(document).on('click', '.delete-store-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Delete Store?',
                    text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, Delete Store'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ url('admin/stores') }}/' + id,
                            method: 'DELETE',
                            success: function(res) {
                                toastr.success(res.message);
                                storesTable.ajax.reload();
                            },
                            error: function(xhr) {
                                toastr.error(xhr.responseJSON?.message || 'Error deleting store');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
