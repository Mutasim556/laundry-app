@extends('admin::layouts.app')

@section('title', 'Services Page Contents - Website Manager')

@push('styles')
<style>
    .wm-header-card {
        background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 55%, #FEF3C7 100%);
        border: 1px solid #FDE68A;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(255, 198, 41, 0.14);
    }
    .wm-kpi-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 0.9rem 1rem;
        position: relative;
        overflow: hidden;
        height: 100%;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
    }
    .wm-kpi-bar { position: absolute; top: 0; left: 0; right: 0; height: 4px; }
    .wm-kpi-icon {
        width: 38px; height: 38px; border-radius: 11px;
        display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .wm-nav-pills {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 0.45rem;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    }
    .wm-nav-pills .nav-link {
        color: #475569;
        font-weight: 600;
        font-size: 0.83rem;
        border-radius: 10px;
        padding: 0.48rem 0.88rem;
        display: inline-flex;
        align-items: center;
        gap: 0.38rem;
    }
    .wm-nav-pills .nav-link:hover { background-color: #FFF8E5; color: #171717; }
    .wm-nav-pills .nav-link.active {
        background: linear-gradient(135deg, #FFC629 0%, #F59E0B 100%);
        color: #171717;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
    }
    .wm-media-card { border: 1px solid #E2E8F0; border-radius: 14px; overflow: hidden; background: #FFFFFF; }
    .wm-media-thumb { height: 110px; background: #F8FAFC; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .wm-media-thumb img { max-height: 100%; max-width: 100%; object-fit: contain; }
</style>
@endpush

@section('content')
@php
    $visibleSectionsCount = collect($draft['sections'] ?? [])->where('visible', true)->count();
    $totalSectionsCount = count($draft['sections'] ?? []);
    $featuredCount = count($draft['featured_config']['service_ids'] ?? []);
@endphp
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="wm-header-card p-3 p-md-4 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill px-2 py-1 fw-bold" style="background: #FFC629; color: #171717; font-size: 0.72rem;">Website Manager</span>
                    <span class="badge rounded-pill bg-white text-dark border" style="font-size: 0.72rem;">Version #{{ $record->current_version_number }}</span>
                </div>
                <h4 class="fw-bold mb-1" style="color: #171717;">Services Page Contents</h4>
                <div class="small text-muted">Manage your public service catalog presentation, banner, featured services, and page sections.</div>
                <div class="small mt-2 d-flex flex-wrap gap-2">
                    <span id="wmSvcDraftMeta" class="badge rounded-pill bg-light text-dark border px-3 py-1 fw-normal">
                        <strong>Last Draft:</strong> {{ $record->last_draft_saved_at?->format('d M Y, H:i') ?? 'Initialized' }}
                    </span>
                    <span id="wmSvcPubMeta" class="badge rounded-pill {{ $record->published_at ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }} px-3 py-1 fw-normal">
                        <strong>Published:</strong> {{ $record->published_at?->format('d M Y, H:i') ?? 'Not yet published' }}
                    </span>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ url('/services?preview=draft') }}" target="_blank" rel="noopener" class="btn btn-sm btn-light border bg-white px-3 fw-semibold shadow-sm">
                    <i data-feather="eye" style="width: 14px; height: 14px;" class="me-1"></i>Preview Page
                </a>
                <button type="button" class="btn btn-sm btn-success px-3 fw-semibold shadow-sm" id="wmSvcPublishBtn">
                    <i data-feather="upload-cloud" style="width: 14px; height: 14px;" class="me-1"></i>Publish
                </button>
            </div>
        </div>
    </div>

    <!-- 4 KPI Cards in Single Line -->
    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #8B5CF6;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Visible Sections</div>
                        <div class="fs-4 fw-bold" style="color: #6D28D9;">{{ $visibleSectionsCount }} / {{ $totalSectionsCount }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Ordered page blocks</div>
                    </div>
                    <span class="wm-kpi-icon" style="background: #F5F3FF; color: #7C3AED;"><i data-feather="layers" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #3B82F6;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Active Categories</div>
                        <div class="fs-4 fw-bold text-primary">{{ $categories->where('is_active', true)->count() }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Linked to catalog</div>
                    </div>
                    <span class="wm-kpi-icon" style="background: #EFF6FF; color: #2563EB;"><i data-feather="grid" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #16A34A;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Active Services</div>
                        <div class="fs-4 fw-bold text-success">{{ $services->where('is_active', true)->count() }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">{{ $featuredCount }} featured selected</div>
                    </div>
                    <span class="wm-kpi-icon" style="background: #ECFDF5; color: #16A34A;"><i data-feather="tag" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #F59E0B;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Media &amp; Versions</div>
                        <div class="fs-4 fw-bold" style="color: #D97706;">{{ $mediaItems->count() }} <span class="fs-6 fw-normal text-muted">assets</span></div>
                        <div class="text-muted" style="font-size: 0.72rem;">{{ $versions->count() }} saved versions</div>
                    </div>
                    <span class="wm-kpi-icon" style="background: #FFFBEB; color: #D97706;"><i data-feather="image" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div id="wmSvcAlertBox" class="alert d-none py-2 small mb-3"></div>

    <!-- Tabs -->
    <ul class="nav nav-pills wm-nav-pills gap-1 mb-3 flex-wrap" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#svcTabSections" type="button"><i data-feather="layers" style="width:14px;height:14px;"></i>Sections</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#svcTabBanner" type="button"><i data-feather="star" style="width:14px;height:14px;"></i>Banner</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#svcTabSearch" type="button"><i data-feather="search" style="width:14px;height:14px;"></i>Search Settings</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#svcTabCategories" type="button"><i data-feather="grid" style="width:14px;height:14px;"></i>Categories</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#svcTabCatalog" type="button"><i data-feather="list" style="width:14px;height:14px;"></i>All Services</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#svcTabFeatured" type="button"><i data-feather="award" style="width:14px;height:14px;"></i>Featured Services</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#svcTabCta" type="button"><i data-feather="zap" style="width:14px;height:14px;"></i>Call to Action</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#svcTabMedia" type="button"><i data-feather="image" style="width:14px;height:14px;"></i>Media</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#svcTabSeo" type="button"><i data-feather="globe" style="width:14px;height:14px;"></i>SEO &amp; History</button></li>
    </ul>

    <div class="tab-content">
        <!-- 1. Sections -->
        <div class="tab-pane fade show active" id="svcTabSections">
            <div class="card border-0 shadow-sm">
                <form id="wmSvcSectionsForm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small" style="min-width: 650px;">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 90px;">Order</th>
                                        <th>Section</th>
                                        <th>Description</th>
                                        <th style="width: 130px;">Visibility</th>
                                        <th class="text-end" style="width: 120px;">Move</th>
                                    </tr>
                                </thead>
                                <tbody id="wmSvcSectionsBody">
                                    @foreach(collect($draft['sections'])->sortBy('order')->values() as $sec)
                                        <tr>
                                            <td>
                                                <input type="number" class="form-control form-control-sm wm-sec-order" value="{{ $sec['order'] }}" min="1" max="20" style="width: 72px;">
                                                <input type="hidden" class="wm-sec-key" value="{{ $sec['key'] }}">
                                                <input type="hidden" class="wm-sec-label" value="{{ $sec['label'] }}">
                                                <input type="hidden" class="wm-sec-sub" value="{{ $sec['subtitle'] ?? '' }}">
                                            </td>
                                            <td class="fw-bold">{{ $sec['label'] }}</td>
                                            <td class="text-muted">{{ $sec['subtitle'] ?? '' }}</td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input wm-sec-visible" type="checkbox" {{ !empty($sec['visible']) ? 'checked' : '' }}>
                                                    <label class="form-check-label">{{ !empty($sec['visible']) ? 'Visible' : 'Hidden' }}</label>
                                                </div>
                                            </td>
                                            <td class="text-end text-nowrap">
                                                <button type="button" class="btn btn-sm btn-light border wm-move-up">&uarr;</button>
                                                <button type="button" class="btn btn-sm btn-light border wm-move-down">&darr;</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Sections Draft</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Banner -->
        <div class="tab-pane fade" id="svcTabBanner">
            <div class="card border-0 shadow-sm">
                <form id="wmSvcBannerForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Small Badge Text</label>
                                <input type="text" name="badge" class="form-control form-control-sm" value="{{ $draft['banner']['badge'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Background Preset</label>
                                <select name="bg_preset" class="form-select form-select-sm">
                                    <option value="gold_soft" {{ ($draft['banner']['bg_preset'] ?? 'gold_soft') === 'gold_soft' ? 'selected' : '' }}>Soft Yellow Gradient</option>
                                    <option value="cream" {{ ($draft['banner']['bg_preset'] ?? '') === 'cream' ? 'selected' : '' }}>Soft Cream (#FFF8E5)</option>
                                    <option value="white" {{ ($draft['banner']['bg_preset'] ?? '') === 'white' ? 'selected' : '' }}>Pure White</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Main Headline <span class="text-danger">*</span></label>
                                <input type="text" name="headline" class="form-control form-control-sm" value="{{ $draft['banner']['headline'] ?? '' }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control form-control-sm" rows="2">{{ $draft['banner']['description'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Banner Image Path / URL</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="image_url" id="wmSvcBannerImg" class="form-control" value="{{ $draft['banner']['image_url'] ?? '' }}">
                                    <button type="button" class="btn btn-outline-secondary wm-pick-media-btn" data-target="#wmSvcBannerImg">Choose Media</button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Image Alt Text</label>
                                <input type="text" name="image_alt" class="form-control form-control-sm" value="{{ $draft['banner']['image_alt'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Primary Button Text</label>
                                <input type="text" name="primary_btn_label" class="form-control form-control-sm" value="{{ $draft['banner']['primary_btn_label'] ?? '' }}">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Primary Button Destination</label>
                                <input type="text" name="primary_btn_url" class="form-control form-control-sm" value="{{ $draft['banner']['primary_btn_url'] ?? '/booking/services' }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="primary_btn_visible" value="1" {{ !empty($draft['banner']['primary_btn_visible']) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold">Show Primary CTA</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Secondary Button Text</label>
                                <input type="text" name="secondary_btn_label" class="form-control form-control-sm" value="{{ $draft['banner']['secondary_btn_label'] ?? '' }}">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Secondary Button Destination</label>
                                <input type="text" name="secondary_btn_url" class="form-control form-control-sm" value="{{ $draft['banner']['secondary_btn_url'] ?? '#catalog-results' }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="secondary_btn_visible" value="1" {{ !empty($draft['banner']['secondary_btn_visible']) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold">Show Secondary CTA</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Banner Draft</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. Search -->
        <div class="tab-pane fade" id="svcTabSearch">
            <div class="card border-0 shadow-sm">
                <form id="wmSvcSearchForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Search Input Placeholder <span class="text-danger">*</span></label>
                                <input type="text" name="placeholder" class="form-control form-control-sm" value="{{ $draft['search']['placeholder'] ?? '' }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Max Suggestions</label>
                                <input type="number" name="max_suggestions" class="form-control form-control-sm" min="1" max="15" value="{{ $draft['search']['max_suggestions'] ?? 5 }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="show_sort" value="1" {{ !empty($draft['search']['show_sort']) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold">Show Sort Dropdown</label>
                                </div>
                            </div>
                            <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="visible" value="1" {{ !empty($draft['search']['visible']) ? 'checked' : '' }}><label class="form-check-label">Search Bar Visible</label></div></div>
                            <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_categories" value="1" {{ !empty($draft['search']['show_categories']) ? 'checked' : '' }}><label class="form-check-label">Search Categories</label></div></div>
                            <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_services" value="1" {{ !empty($draft['search']['show_services']) ? 'checked' : '' }}><label class="form-check-label">Search Services</label></div></div>
                            <div class="col-md-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_items" value="1" {{ !empty($draft['search']['show_items']) ? 'checked' : '' }}><label class="form-check-label">Search Laundry Items</label></div></div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Search Draft</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4. Categories -->
        <div class="tab-pane fade" id="svcTabCategories">
            <div class="card border-0 shadow-sm">
                <form id="wmSvcCategoriesForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Section Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control form-control-sm" value="{{ $draft['categories_config']['title'] ?? 'Our Categories' }}" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Section Subtitle</label>
                                <input type="text" name="subtitle" class="form-control form-control-sm" value="{{ $draft['categories_config']['subtitle'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Display Mode</label>
                                <select name="mode" class="form-select form-select-sm">
                                    <option value="all_active" {{ ($draft['categories_config']['mode'] ?? 'all_active') === 'all_active' ? 'selected' : '' }}>All Active Categories</option>
                                    <option value="selected" {{ ($draft['categories_config']['mode'] ?? '') === 'selected' ? 'selected' : '' }}>Selected Only</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover align-middle mb-0" style="min-width: 720px;">
                                <thead class="table-light">
                                    <tr><th>Show</th><th style="width:85px;">Order</th><th>Master Category</th><th>Short Description Override</th><th>Custom Icon/Image URL</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($categories as $cat)
                                        @php
                                            $ov = $draft['categories_config']['overrides'][$cat->id] ?? ($draft['categories_config']['overrides'][(string) $cat->id] ?? []);
                                            $isVis = array_key_exists('visible', $ov) ? !empty($ov['visible']) : true;
                                        @endphp
                                        <tr>
                                            <td><input type="checkbox" class="form-check-input" name="overrides[{{ $cat->id }}][visible]" value="1" {{ $isVis ? 'checked' : '' }} {{ !$cat->is_active ? 'disabled' : '' }}></td>
                                            <td><input type="number" class="form-control form-control-sm" name="overrides[{{ $cat->id }}][order]" value="{{ $ov['order'] ?? $cat->id }}" min="0"></td>
                                            <td><strong>{{ $cat->name }}</strong> <span class="badge {{ $cat->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $cat->is_active ? 'Active' : 'Inactive' }}</span></td>
                                            <td><input type="text" class="form-control form-control-sm" name="overrides[{{ $cat->id }}][custom_description]" value="{{ $ov['custom_description'] ?? '' }}"></td>
                                            <td><input type="text" class="form-control form-control-sm" name="overrides[{{ $cat->id }}][custom_image_url]" value="{{ $ov['custom_image_url'] ?? '' }}"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Categories Draft</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 5. All Services Catalog -->
        <div class="tab-pane fade" id="svcTabCatalog">
            <div class="card border-0 shadow-sm">
                <form id="wmSvcCatalogForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Catalog Heading Override (Blank = Active Category)</label>
                                <input type="text" name="title" class="form-control form-control-sm" value="{{ $draft['catalog_config']['title'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Subtitle Override</label>
                                <input type="text" name="subtitle" class="form-control form-control-sm" value="{{ $draft['catalog_config']['subtitle'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Per Page</label>
                                <select name="per_page" class="form-select form-select-sm">
                                    @foreach([8, 12, 16, 24] as $pp)
                                        <option value="{{ $pp }}" {{ (int) ($draft['catalog_config']['per_page'] ?? 12) === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Desktop Cols</label>
                                <select name="desktop_columns" class="form-select form-select-sm">
                                    <option value="3" {{ (int) ($draft['catalog_config']['desktop_columns'] ?? 3) === 3 ? 'selected' : '' }}>3 (With Sidebar)</option>
                                    <option value="4" {{ (int) ($draft['catalog_config']['desktop_columns'] ?? 3) === 4 ? 'selected' : '' }}>4 Cards</option>
                                    <option value="2" {{ (int) ($draft['catalog_config']['desktop_columns'] ?? 3) === 2 ? 'selected' : '' }}>2 Cards</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover align-middle mb-0" style="min-width: 780px;">
                                <thead class="table-light">
                                    <tr><th>Show</th><th style="width:85px;">Order</th><th>Service (Live Price)</th><th>Badge Override</th><th>Description Override</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($services as $svc)
                                        @php
                                            $ov = $draft['catalog_config']['overrides'][$svc->id] ?? ($draft['catalog_config']['overrides'][(string) $svc->id] ?? []);
                                            $isVis = array_key_exists('visible', $ov) ? !empty($ov['visible']) : true;
                                        @endphp
                                        <tr>
                                            <td><input type="checkbox" class="form-check-input" name="overrides[{{ $svc->id }}][visible]" value="1" {{ $isVis ? 'checked' : '' }} {{ !$svc->is_active ? 'disabled' : '' }}></td>
                                            <td><input type="number" class="form-control form-control-sm" name="overrides[{{ $svc->id }}][order]" value="{{ $ov['order'] ?? $svc->id }}" min="0"></td>
                                            <td>
                                                <strong>{{ $svc->name }}</strong>
                                                <div class="text-muted">{{ $svc->price !== null ? formatMoney($svc->price, $svc->currency_code ?? null) : 'Variable' }} &middot; {{ $svc->is_active ? 'Active' : 'Inactive' }}</div>
                                            </td>
                                            <td><input type="text" class="form-control form-control-sm" name="overrides[{{ $svc->id }}][custom_badge]" value="{{ $ov['custom_badge'] ?? '' }}"></td>
                                            <td><input type="text" class="form-control form-control-sm" name="overrides[{{ $svc->id }}][custom_description]" value="{{ $ov['custom_description'] ?? '' }}"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save All Services Draft</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 6. Featured Services -->
        <div class="tab-pane fade" id="svcTabFeatured">
            <div class="card border-0 shadow-sm">
                <form id="wmSvcFeaturedForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Section Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control form-control-sm" value="{{ $draft['featured_config']['title'] ?? 'Featured Services' }}" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Subtitle</label>
                                <input type="text" name="subtitle" class="form-control form-control-sm" value="{{ $draft['featured_config']['subtitle'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Interval (ms)</label>
                                <input type="number" name="interval_ms" class="form-control form-control-sm" min="1500" max="15000" step="100" value="{{ $draft['featured_config']['interval_ms'] ?? 4200 }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Desktop Cards</label>
                                <select name="desktop_per_view" class="form-select form-select-sm">
                                    <option value="4" {{ (int) ($draft['featured_config']['desktop_per_view'] ?? 4) === 4 ? 'selected' : '' }}>4 Cards</option>
                                    <option value="3" {{ (int) ($draft['featured_config']['desktop_per_view'] ?? 4) === 3 ? 'selected' : '' }}>3 Cards</option>
                                    <option value="2" {{ (int) ($draft['featured_config']['desktop_per_view'] ?? 4) === 2 ? 'selected' : '' }}>2 Cards</option>
                                </select>
                            </div>
                            <div class="col-md-9 d-flex flex-wrap align-items-end gap-3">
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="autoplay" value="1" {{ !empty($draft['featured_config']['autoplay']) ? 'checked' : '' }}><label class="form-check-label">Autoplay</label></div>
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_arrows" value="1" {{ !empty($draft['featured_config']['show_arrows']) ? 'checked' : '' }}><label class="form-check-label">Arrows</label></div>
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="pause_on_hover" value="1" {{ !empty($draft['featured_config']['pause_on_hover']) ? 'checked' : '' }}><label class="form-check-label">Pause Hover</label></div>
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="loop" value="1" {{ !empty($draft['featured_config']['loop']) ? 'checked' : '' }}><label class="form-check-label">Loop</label></div>
                            </div>
                        </div>
                        <label class="form-label fw-semibold">Select Featured Services (Active Services Only)</label>
                        <div class="row g-2">
                            @php $selectedFeatured = $draft['featured_config']['service_ids'] ?? []; @endphp
                            @foreach($services->where('is_active', true) as $svc)
                                <div class="col-md-4">
                                    <label class="d-flex align-items-center gap-2 p-2 border rounded bg-light cursor-pointer">
                                        <input type="checkbox" class="form-check-input mt-0" name="service_ids[]" value="{{ $svc->id }}" {{ in_array($svc->id, $selectedFeatured) ? 'checked' : '' }}>
                                        <span><strong>{{ $svc->name }}</strong> <span class="text-muted">({{ $svc->price !== null ? formatMoney($svc->price, $svc->currency_code ?? null) : 'Variable' }})</span></span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Featured Draft</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 7. CTA -->
        <div class="tab-pane fade" id="svcTabCta">
            <div class="card border-0 shadow-sm">
                <form id="wmSvcCtaForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Headline <span class="text-danger">*</span></label>
                                <input type="text" name="headline" class="form-control form-control-sm" value="{{ $draft['cta']['headline'] ?? '' }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Background</label>
                                <select name="bg_preset" class="form-select form-select-sm">
                                    <option value="gold_gradient" {{ ($draft['cta']['bg_preset'] ?? 'gold_gradient') === 'gold_gradient' ? 'selected' : '' }}>Fresh Go Gold Gradient</option>
                                    <option value="cream" {{ ($draft['cta']['bg_preset'] ?? '') === 'cream' ? 'selected' : '' }}>Soft Cream</option>
                                    <option value="dark_gold" {{ ($draft['cta']['bg_preset'] ?? '') === 'dark_gold' ? 'selected' : '' }}>Dark Charcoal</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="button_visible" value="1" {{ !empty($draft['cta']['button_visible']) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold">Show CTA Button</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control form-control-sm" rows="2">{{ $draft['cta']['description'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Button Text</label>
                                <input type="text" name="button_label" class="form-control form-control-sm" value="{{ $draft['cta']['button_label'] ?? '' }}">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Button Destination</label>
                                <input type="text" name="button_url" class="form-control form-control-sm" value="{{ $draft['cta']['button_url'] ?? '/booking/services' }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save CTA Draft</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 8. Shared Media -->
        <div class="tab-pane fade" id="svcTabMedia">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3 small">
                    <form id="wmSvcMediaUploadForm" class="row g-2 align-items-end" enctype="multipart/form-data">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold mb-1">Upload Image (JPG, PNG, WebP, max 4MB)</label>
                            <input type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold mb-1">Alt Text</label>
                            <input type="text" name="alt_text" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-warning fw-semibold w-100" id="wmSvcMediaUploadBtn">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row g-3" id="wmSvcMediaGrid">
                @forelse($mediaItems as $m)
                    <div class="col-6 col-md-3 col-xl-2" data-media-id="{{ $m['id'] }}">
                        <div class="wm-media-card h-100 d-flex flex-column">
                            <div class="wm-media-thumb p-2"><img src="{{ $m['url'] }}" alt="{{ $m['alt_text'] }}"></div>
                            <div class="p-2 small">
                                <div class="fw-semibold text-truncate">{{ $m['filename'] }}</div>
                                <div class="d-flex gap-1 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 flex-grow-1 wm-copy-path" data-path="{{ $m['url'] }}">Copy</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 wm-delete-media" data-url="{{ route('admin.website-manager.homepage.media.delete', $m['id']) }}">Del</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><div class="p-4 text-center text-muted bg-white rounded border small">No custom media uploaded yet.</div></div>
                @endforelse
            </div>
        </div>

        <!-- 9. SEO & History -->
        <div class="tab-pane fade" id="svcTabSeo">
            <div class="card border-0 shadow-sm mb-3">
                <form id="wmSvcSeoForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Meta Title</label>
                                <input type="text" name="meta_title" class="form-control form-control-sm" value="{{ $draft['seo']['meta_title'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Robots</label>
                                <select name="robots" class="form-select form-select-sm">
                                    @foreach(['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'] as $rob)
                                        <option value="{{ $rob }}" {{ ($draft['seo']['robots'] ?? 'index, follow') === $rob ? 'selected' : '' }}>{{ $rob }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Meta Description</label>
                                <textarea name="meta_description" class="form-control form-control-sm" rows="2">{{ $draft['seo']['meta_description'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Open Graph Title</label>
                                <input type="text" name="og_title" class="form-control form-control-sm" value="{{ $draft['seo']['og_title'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Open Graph Image URL</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="og_image_url" id="wmSvcOgImg" class="form-control" value="{{ $draft['seo']['og_image_url'] ?? '' }}">
                                    <button type="button" class="btn btn-outline-secondary wm-pick-media-btn" data-target="#wmSvcOgImg">Choose Media</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save SEO Draft</button>
                    </div>
                </form>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3"><h6 class="fw-bold mb-0">Content Version History</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 small" style="min-width: 650px;">
                        <thead class="table-light"><tr><th>Version</th><th>Action</th><th>Section</th><th>Changed By</th><th>Date</th><th class="text-end">Restore</th></tr></thead>
                        <tbody>
                            @forelse($versions as $ver)
                                <tr>
                                    <td class="fw-bold">#{{ $ver->version_number }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $ver->action)) }}</span></td>
                                    <td>{{ $ver->section_key ?: 'All' }}</td>
                                    <td>{{ $ver->createdByAdmin?->name ?? 'System' }}</td>
                                    <td>{{ $ver->created_at?->format('d M Y, H:i') }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 wm-restore-ver-btn" data-url="{{ route('admin.website-manager.services.versions.restore', $ver) }}">Restore</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">No version history yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Media Picker Modal -->
<div class="modal fade" id="wmSvcMediaPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold">Select Media</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2" id="wmSvcPickerGrid">
                    @foreach($mediaItems as $m)
                        <div class="col-6 col-md-3">
                            <div class="wm-media-card p-2 text-center">
                                <div class="wm-media-thumb mb-2"><img src="{{ $m['url'] }}" alt=""></div>
                                <button type="button" class="btn btn-sm btn-warning w-100 wm-select-media-item" data-url="{{ $m['url'] }}">Use Image</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
jQuery(function($) {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    let pickerTarget = null;

    function showAlert(msg, type = 'success') {
        $('#wmSvcAlertBox').removeClass('d-none alert-success alert-danger').addClass(type === 'success' ? 'alert-success' : 'alert-danger').text(msg);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function extractError(xhr, fallback) {
        if (xhr.responseJSON?.errors) {
            const k = Object.keys(xhr.responseJSON.errors)[0];
            if (k && xhr.responseJSON.errors[k][0]) return xhr.responseJSON.errors[k][0];
        }
        return xhr.responseJSON?.message || fallback;
    }

    function postDraft(url, data, btn, label) {
        btn.prop('disabled', true).text('Saving...');
        $.ajax({
            url: url, method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, data: data,
            success: function(res) { showAlert(res.message, 'success'); if (res.draft_saved_at) $('#wmSvcDraftMeta').html('<strong>Last Draft:</strong> ' + res.draft_saved_at); },
            error: function(xhr) { showAlert(extractError(xhr, 'Failed to save draft.'), 'danger'); },
            complete: function() { btn.prop('disabled', false).text(label); }
        });
    }

    $(document).on('click', '.wm-move-up', function() {
        const r = $(this).closest('tr'), p = r.prev('tr');
        if (p.length) { r.insertBefore(p); $('#wmSvcSectionsBody tr').each((i, el) => $(el).find('.wm-sec-order').val(i + 1)); }
    });
    $(document).on('click', '.wm-move-down', function() {
        const r = $(this).closest('tr'), n = r.next('tr');
        if (n.length) { r.insertAfter(n); $('#wmSvcSectionsBody tr').each((i, el) => $(el).find('.wm-sec-order').val(i + 1)); }
    });

    $('#wmSvcSectionsForm').on('submit', function(e) {
        e.preventDefault();
        const sections = [];
        $('#wmSvcSectionsBody tr').each(function() {
            sections.push({
                key: $(this).find('.wm-sec-key').val(), label: $(this).find('.wm-sec-label').val(), subtitle: $(this).find('.wm-sec-sub').val(),
                order: parseInt($(this).find('.wm-sec-order').val() || '1', 10), visible: $(this).find('.wm-sec-visible').is(':checked') ? 1 : 0
            });
        });
        postDraft(@json(route('admin.website-manager.services.sections.save')), { sections }, $(this).find('button[type="submit"]'), 'Save Sections Draft');
    });

    $('#wmSvcBannerForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.services.banner.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Banner Draft'); });
    $('#wmSvcSearchForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.services.search.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Search Draft'); });
    $('#wmSvcCategoriesForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.services.categories.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Categories Draft'); });
    $('#wmSvcCatalogForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.services.catalog.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save All Services Draft'); });
    $('#wmSvcFeaturedForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.services.featured.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Featured Draft'); });
    $('#wmSvcCtaForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.services.cta.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save CTA Draft'); });
    $('#wmSvcSeoForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.services.seo.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save SEO Draft'); });

    $('#wmSvcPublishBtn').on('click', function() {
        const btn = $(this).prop('disabled', true).text('Publishing...');
        $.ajax({
            url: @json(route('admin.website-manager.services.publish')), method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function(res) { showAlert(res.message, 'success'); $('#wmSvcPubMeta').html('<strong>Published:</strong> ' + res.published_at); },
            error: function(xhr) { showAlert(extractError(xhr, 'Publish failed.'), 'danger'); },
            complete: function() { btn.prop('disabled', false).text('Publish'); }
        });
    });

    $(document).on('click', '.wm-restore-ver-btn', function() {
        $.ajax({ url: $(this).data('url'), method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, success: function(res) { showAlert(res.message, 'success'); setTimeout(() => window.location.reload(), 900); } });
    });

    $('#wmSvcMediaUploadForm').on('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const btn = $('#wmSvcMediaUploadBtn').prop('disabled', true).text('Uploading...');
        $.ajax({
            url: @json(route('admin.website-manager.homepage.media.upload')), method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: fd, processData: false, contentType: false,
            success: function(res) {
                showAlert(res.message, 'success');
                const m = res.media;
                $('#wmSvcMediaGrid').prepend(`<div class="col-6 col-md-3 col-xl-2" data-media-id="${m.id}"><div class="wm-media-card h-100 d-flex flex-column"><div class="wm-media-thumb p-2"><img src="${m.url}" alt=""></div><div class="p-2 small"><div class="fw-semibold text-truncate">${m.filename}</div><div class="d-flex gap-1 mt-2"><button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 flex-grow-1 wm-copy-path" data-path="${m.url}">Copy</button></div></div></div></div>`);
                $('#wmSvcPickerGrid').prepend(`<div class="col-6 col-md-3"><div class="wm-media-card p-2 text-center"><div class="wm-media-thumb mb-2"><img src="${m.url}" alt=""></div><button type="button" class="btn btn-sm btn-warning w-100 wm-select-media-item" data-url="${m.url}">Use Image</button></div></div>`);
                $('#wmSvcMediaUploadForm')[0].reset();
            },
            error: function(xhr) { showAlert(extractError(xhr, 'Upload failed.'), 'danger'); },
            complete: function() { btn.prop('disabled', false).text('Upload'); }
        });
    });

    $(document).on('click', '.wm-copy-path', function() { navigator.clipboard?.writeText($(this).data('path')); showAlert('Image URL copied.', 'success'); });
    $(document).on('click', '.wm-delete-media', function() {
        const card = $(this).closest('[data-media-id]');
        $.ajax({ url: $(this).data('url'), method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, success: function(res) { card.remove(); showAlert(res.message, 'success'); }, error: function(xhr) { showAlert(extractError(xhr, 'Cannot delete.'), 'danger'); } });
    });
    $(document).on('click', '.wm-pick-media-btn', function() { pickerTarget = $(this).data('target'); bootstrap.Modal.getOrCreateInstance(document.getElementById('wmSvcMediaPickerModal')).show(); });
    $(document).on('click', '.wm-select-media-item', function() { if (pickerTarget) $(pickerTarget).val($(this).data('url')); bootstrap.Modal.getOrCreateInstance(document.getElementById('wmSvcMediaPickerModal')).hide(); });
});
</script>
@endpush
