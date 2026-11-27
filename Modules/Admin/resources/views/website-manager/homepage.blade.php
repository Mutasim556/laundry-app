@extends('admin::layouts.app')

@section('title', 'Homepage Contents - Website Manager')

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
    .wm-kpi-bar {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
    }
    .wm-kpi-icon {
        width: 38px;
        height: 38px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
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
        font-size: 0.84rem;
        border-radius: 10px;
        padding: 0.5rem 0.95rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.15s ease;
    }
    .wm-nav-pills .nav-link:hover {
        background-color: #FFF8E5;
        color: #171717;
    }
    .wm-nav-pills .nav-link.active {
        background: linear-gradient(135deg, #FFC629 0%, #F59E0B 100%);
        color: #171717;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
    }
    .wm-section-card {
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    }
    .wm-section-card > .card-header {
        background: linear-gradient(90deg, #F8FAFC 0%, #FFFFFF 100%);
    }
    .wm-media-card {
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        overflow: hidden;
        background: #FFFFFF;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .wm-media-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.07);
    }
    .wm-media-thumb {
        height: 110px;
        background: #F8FAFC;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .wm-media-thumb img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
    }
</style>
@endpush

@section('content')
@php
    $visibleSectionsCount = collect($draft['sections'] ?? [])->where('visible', true)->count();
    $totalSectionsCount = count($draft['sections'] ?? []);
    $activeCatCount = $categories->where('is_active', true)->count();
    $activeSvcCount = $services->where('is_active', true)->count();
@endphp
<div class="container-fluid py-3">
    <!-- Page Header -->
    <div class="wm-header-card p-3 p-md-4 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background: #FFC629; color: #171717; font-size: 0.72rem;">
                        Website Manager
                    </span>
                    <span class="badge rounded-pill bg-white text-dark border" style="font-size: 0.72rem;">
                        Version #{{ $record->current_version_number }}
                    </span>
                </div>
                <h4 class="fw-bold mb-1" style="color: #171717;">Homepage Contents</h4>
                <div class="small text-muted">
                    Manage the Fresh Go homepage content, layout, carousels, media, and SEO.
                </div>
                <div class="small mt-2 d-flex flex-wrap gap-2">
                    <span id="wmDraftMeta" class="badge rounded-pill bg-light text-dark border px-3 py-1 fw-normal">
                        <strong>Last Draft:</strong> {{ $record->last_draft_saved_at?->format('d M Y, H:i') ?? 'Initialized' }}
                        @if($record->lastDraftSavedBy) ({{ $record->lastDraftSavedBy->name }}) @endif
                    </span>
                    <span id="wmPubMeta" class="badge rounded-pill {{ $record->published_at ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }} px-3 py-1 fw-normal">
                        <strong>Published:</strong>
                        @if($record->published_at)
                            {{ $record->published_at->format('d M Y, H:i') }}
                            @if($record->publishedBy) ({{ $record->publishedBy->name }}) @endif
                        @else
                            Not yet published
                        @endif
                    </span>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ url('/?preview=draft') }}" target="_blank" rel="noopener" class="btn btn-sm btn-light border bg-white px-3 fw-semibold shadow-sm">
                    <i data-feather="eye" style="width: 14px; height: 14px;" class="me-1"></i>Preview Homepage
                </a>
                <button type="button" class="btn btn-sm btn-success px-3 fw-semibold shadow-sm" id="wmPublishBtn">
                    <i data-feather="upload-cloud" style="width: 14px; height: 14px;" class="me-1"></i>Publish Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Compact Colorful Overview KPIs -->
    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #8B5CF6;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Visible Sections</div>
                        <div class="fs-4 fw-bold" style="color: #6D28D9;">{{ $visibleSectionsCount }} / {{ $totalSectionsCount }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Ordered homepage blocks</div>
                    </div>
                    <span class="wm-kpi-icon" style="background: #F5F3FF; color: #7C3AED;">
                        <i data-feather="layers" style="width: 18px; height: 18px;"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #3B82F6;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Active Categories</div>
                        <div class="fs-4 fw-bold text-primary">{{ $activeCatCount }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">{{ !empty($draft['categories_config']['autoplay']) ? 'Carousel Autoplay ON' : 'Carousel Autoplay OFF' }}</div>
                    </div>
                    <span class="wm-kpi-icon" style="background: #EFF6FF; color: #2563EB;">
                        <i data-feather="grid" style="width: 18px; height: 18px;"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #16A34A;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Active Services</div>
                        <div class="fs-4 fw-bold text-success">{{ $activeSvcCount }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Live DB pricing enabled</div>
                    </div>
                    <span class="wm-kpi-icon" style="background: #ECFDF5; color: #16A34A;">
                        <i data-feather="tag" style="width: 18px; height: 18px;"></i>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #F59E0B;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Media &amp; History</div>
                        <div class="fs-4 fw-bold" style="color: #D97706;">{{ $mediaItems->count() }} <span class="fs-6 fw-normal text-muted">assets</span></div>
                        <div class="text-muted" style="font-size: 0.72rem;">{{ $versions->count() }} saved versions</div>
                    </div>
                    <span class="wm-kpi-icon" style="background: #FFFBEB; color: #D97706;">
                        <i data-feather="image" style="width: 18px; height: 18px;"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div id="wmAlertBox" class="alert d-none py-2 small mb-3"></div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-pills wm-nav-pills gap-1 mb-3 flex-wrap" id="wmTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabSections" type="button"><i data-feather="layers" style="width:14px;height:14px;"></i>Section Manager</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabHero" type="button"><i data-feather="star" style="width:14px;height:14px;"></i>Hero Banner</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabSearch" type="button"><i data-feather="search" style="width:14px;height:14px;"></i>Homepage Search</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabCategories" type="button"><i data-feather="grid" style="width:14px;height:14px;"></i>Laundry Categories</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabPromo" type="button"><i data-feather="award" style="width:14px;height:14px;"></i>Promo Banner</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabServices" type="button"><i data-feather="tag" style="width:14px;height:14px;"></i>Popular Services</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabMedia" type="button"><i data-feather="image" style="width:14px;height:14px;"></i>Media Library</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabSeo" type="button"><i data-feather="globe" style="width:14px;height:14px;"></i>SEO &amp; History</button></li>
    </ul>

    <div class="tab-content">
        <!-- 1. Section Manager -->
        <div class="tab-pane fade show active" id="tabSections">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0">Homepage Section Visibility &amp; Ordering</h6>
                        <div class="small text-muted">Hidden sections retain their saved content and are omitted from the live homepage.</div>
                    </div>
                </div>
                <form id="wmSectionsForm">
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
                                <tbody id="wmSectionsBody">
                                    @foreach(collect($draft['sections'])->sortBy('order')->values() as $idx => $sec)
                                        <tr data-key="{{ $sec['key'] }}">
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
                                                <button type="button" class="btn btn-sm btn-light border wm-move-up" title="Move Up">&uarr;</button>
                                                <button type="button" class="btn btn-sm btn-light border wm-move-down" title="Move Down">&darr;</button>
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

        <!-- 2. Hero Banner -->
        <div class="tab-pane fade" id="tabHero">
            <div class="card border-0 shadow-sm">
                <form id="wmHeroForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Badge Text</label>
                                <input type="text" name="badge" class="form-control form-control-sm" value="{{ $draft['hero']['badge'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Background Preset</label>
                                <select name="bg_preset" class="form-select form-select-sm">
                                    <option value="cream" {{ ($draft['hero']['bg_preset'] ?? 'cream') === 'cream' ? 'selected' : '' }}>Soft Cream (#FFFDF7)</option>
                                    <option value="white" {{ ($draft['hero']['bg_preset'] ?? '') === 'white' ? 'selected' : '' }}>Pure White (#FFFFFF)</option>
                                    <option value="soft_yellow" {{ ($draft['hero']['bg_preset'] ?? '') === 'soft_yellow' ? 'selected' : '' }}>Warm Yellow (#FFF8E5)</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="show_heart_icon" value="1" {{ !empty($draft['hero']['show_heart_icon']) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold">Show Heart Icon</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Main Headline <span class="text-danger">*</span></label>
                                <textarea name="headline" class="form-control form-control-sm" rows="2" required>{{ $draft['hero']['headline'] ?? '' }}</textarea>
                                <div class="form-text">Line breaks render as separate lines.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Subtitle / Description</label>
                                <textarea name="subtitle" class="form-control form-control-sm" rows="3">{{ $draft['hero']['subtitle'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Hero Image Path / URL</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="image_url" id="wmHeroImgInput" class="form-control" value="{{ $draft['hero']['image_url'] ?? '' }}">
                                    <button type="button" class="btn btn-outline-secondary wm-pick-media-btn" data-target="#wmHeroImgInput">Choose Media</button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Hero Image Alt Text</label>
                                <input type="text" name="image_alt" class="form-control form-control-sm" value="{{ $draft['hero']['image_alt'] ?? '' }}">
                            </div>

                            <div class="col-12"><hr class="my-1"></div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Primary Button Label</label>
                                <input type="text" name="primary_btn_label" class="form-control form-control-sm" value="{{ $draft['hero']['primary_btn_label'] ?? '' }}">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Primary Button Destination</label>
                                <input type="text" name="primary_btn_url" class="form-control form-control-sm" value="{{ $draft['hero']['primary_btn_url'] ?? '/booking/services' }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="primary_btn_visible" value="1" {{ !empty($draft['hero']['primary_btn_visible']) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold">Show Primary Button</label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Secondary Button Label</label>
                                <input type="text" name="secondary_btn_label" class="form-control form-control-sm" value="{{ $draft['hero']['secondary_btn_label'] ?? '' }}">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Secondary Button Destination</label>
                                <input type="text" name="secondary_btn_url" class="form-control form-control-sm" value="{{ $draft['hero']['secondary_btn_url'] ?? '/services' }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="secondary_btn_visible" value="1" {{ !empty($draft['hero']['secondary_btn_visible']) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold">Show Secondary Button</label>
                                </div>
                            </div>

                            <div class="col-12"><hr class="my-1"><div class="fw-bold mb-2">Hero Highlights (up to 3)</div></div>
                            @for($i = 0; $i < 3; $i++)
                                @php $hl = $draft['hero']['highlights'][$i] ?? ['value' => '', 'label' => '']; @endphp
                                <div class="col-md-4">
                                    <div class="p-2 border rounded bg-light">
                                        <input type="text" name="highlights[{{ $i }}][value]" class="form-control form-control-sm mb-1" placeholder="Value (e.g. 24h)" value="{{ $hl['value'] ?? '' }}">
                                        <input type="text" name="highlights[{{ $i }}][label]" class="form-control form-control-sm" placeholder="Label (e.g. Express Delivery)" value="{{ $hl['label'] ?? '' }}">
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Hero Draft</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. Homepage Search -->
        <div class="tab-pane fade" id="tabSearch">
            <div class="card border-0 shadow-sm">
                <form id="wmSearchForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="visible" value="1" {{ !empty($draft['search']['visible']) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold">Show Homepage Search Bar</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Placeholder Text <span class="text-danger">*</span></label>
                                <input type="text" name="placeholder" class="form-control form-control-sm" value="{{ $draft['search']['placeholder'] ?? '' }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Button Text <span class="text-danger">*</span></label>
                                <input type="text" name="button_text" class="form-control form-control-sm" value="{{ $draft['search']['button_text'] ?? 'Search' }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Max Suggestions Per Group</label>
                                <input type="number" name="max_suggestions" class="form-control form-control-sm" min="1" max="15" value="{{ $draft['search']['max_suggestions'] ?? 5 }}">
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="show_icon" value="1" {{ !empty($draft['search']['show_icon']) ? 'checked' : '' }}>
                                    <label class="form-check-label">Show Search Icon</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="show_categories" value="1" {{ !empty($draft['search']['show_categories']) ? 'checked' : '' }}>
                                    <label class="form-check-label">Show Categories in Results</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="show_services" value="1" {{ !empty($draft['search']['show_services']) ? 'checked' : '' }}>
                                    <label class="form-check-label">Show Services in Results</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="show_items" value="1" {{ !empty($draft['search']['show_items']) ? 'checked' : '' }}>
                                    <label class="form-check-label">Show Laundry Items in Results</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Search Draft</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4. Laundry Categories -->
        <div class="tab-pane fade" id="tabCategories">
            <div class="card border-0 shadow-sm">
                <form id="wmCategoriesForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Section Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control form-control-sm" value="{{ $draft['categories_config']['title'] ?? '' }}" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Section Subtitle</label>
                                <input type="text" name="subtitle" class="form-control form-control-sm" value="{{ $draft['categories_config']['subtitle'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Display Mode</label>
                                <select name="mode" class="form-select form-select-sm">
                                    <option value="all_active" {{ ($draft['categories_config']['mode'] ?? 'all_active') === 'all_active' ? 'selected' : '' }}>All Active Categories</option>
                                    <option value="selected" {{ ($draft['categories_config']['mode'] ?? '') === 'selected' ? 'selected' : '' }}>Selected Featured Only</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Desktop Cards / Row</label>
                                <select name="desktop_per_view" class="form-select form-select-sm">
                                    <option value="4" {{ (int) ($draft['categories_config']['desktop_per_view'] ?? 4) === 4 ? 'selected' : '' }}>4 Cards (Default)</option>
                                    <option value="3" {{ (int) ($draft['categories_config']['desktop_per_view'] ?? 4) === 3 ? 'selected' : '' }}>3 Cards</option>
                                    <option value="2" {{ (int) ($draft['categories_config']['desktop_per_view'] ?? 4) === 2 ? 'selected' : '' }}>2 Cards</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Autoplay Interval (ms)</label>
                                <input type="number" name="interval_ms" class="form-control form-control-sm" min="1500" max="15000" step="100" value="{{ $draft['categories_config']['interval_ms'] ?? 4000 }}">
                            </div>
                            <div class="col-md-6 d-flex flex-wrap align-items-end gap-3">
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="carousel_enabled" value="1" {{ !empty($draft['categories_config']['carousel_enabled']) ? 'checked' : '' }}><label class="form-check-label">Carousel</label></div>
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="autoplay" value="1" {{ !empty($draft['categories_config']['autoplay']) ? 'checked' : '' }}><label class="form-check-label">Autoplay</label></div>
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_arrows" value="1" {{ !empty($draft['categories_config']['show_arrows']) ? 'checked' : '' }}><label class="form-check-label">Arrows</label></div>
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="pause_on_hover" value="1" {{ !empty($draft['categories_config']['pause_on_hover']) ? 'checked' : '' }}><label class="form-check-label">Pause Hover</label></div>
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="loop" value="1" {{ !empty($draft['categories_config']['loop']) ? 'checked' : '' }}><label class="form-check-label">Loop</label></div>
                            </div>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover align-middle mb-0" style="min-width: 760px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Show</th>
                                        <th style="width: 85px;">Order</th>
                                        <th>Master Category</th>
                                        <th>Homepage Description Override</th>
                                        <th>Custom Image URL (Optional)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categories as $cat)
                                        @php
                                            $ov = $draft['categories_config']['overrides'][$cat->id] ?? ($draft['categories_config']['overrides'][(string) $cat->id] ?? []);
                                            $isVis = array_key_exists('visible', $ov) ? !empty($ov['visible']) : true;
                                        @endphp
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input" name="overrides[{{ $cat->id }}][visible]" value="1" {{ $isVis ? 'checked' : '' }} {{ !$cat->is_active ? 'disabled' : '' }}>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm" name="overrides[{{ $cat->id }}][order]" value="{{ $ov['order'] ?? $cat->id }}" min="0">
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $cat->name }}</div>
                                                <span class="badge {{ $cat->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $cat->is_active ? 'Active' : 'Inactive' }}</span>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="overrides[{{ $cat->id }}][custom_description]" value="{{ $ov['custom_description'] ?? '' }}" placeholder="{{ \Illuminate\Support\Str::limit($cat->short_description ?: $cat->description, 45) }}">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="overrides[{{ $cat->id }}][custom_image_url]" value="{{ $ov['custom_image_url'] ?? '' }}" placeholder="Optional path/URL">
                                            </td>
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

        <!-- 5. Promo Banner -->
        <div class="tab-pane fade" id="tabPromo">
            <div class="card border-0 shadow-sm">
                <form id="wmPromoForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Badge Text</label>
                                <input type="text" name="badge" class="form-control form-control-sm" value="{{ $draft['promo_banner']['badge'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Background Style</label>
                                <select name="bg_preset" class="form-select form-select-sm">
                                    <option value="gold_gradient" {{ ($draft['promo_banner']['bg_preset'] ?? 'gold_gradient') === 'gold_gradient' ? 'selected' : '' }}>Fresh Go Gold Gradient</option>
                                    <option value="cream" {{ ($draft['promo_banner']['bg_preset'] ?? '') === 'cream' ? 'selected' : '' }}>Soft Cream</option>
                                    <option value="dark_gold" {{ ($draft['promo_banner']['bg_preset'] ?? '') === 'dark_gold' ? 'selected' : '' }}>Dark Charcoal + Gold Accent</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="button_visible" value="1" {{ !empty($draft['promo_banner']['button_visible']) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold">Show CTA Button</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Headline <span class="text-danger">*</span></label>
                                <input type="text" name="headline" class="form-control form-control-sm" value="{{ $draft['promo_banner']['headline'] ?? '' }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control form-control-sm" rows="2">{{ $draft['promo_banner']['description'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Button Label</label>
                                <input type="text" name="button_label" class="form-control form-control-sm" value="{{ $draft['promo_banner']['button_label'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Button Destination</label>
                                <input type="text" name="button_url" class="form-control form-control-sm" value="{{ $draft['promo_banner']['button_url'] ?? '/booking/services' }}">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Banner Image Path / URL</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="image_url" id="wmPromoImgInput" class="form-control" value="{{ $draft['promo_banner']['image_url'] ?? '' }}">
                                    <button type="button" class="btn btn-outline-secondary wm-pick-media-btn" data-target="#wmPromoImgInput">Choose Media</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Promo Banner Draft</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 6. Popular Services -->
        <div class="tab-pane fade" id="tabServices">
            <div class="card border-0 shadow-sm">
                <form id="wmServicesForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Section Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control form-control-sm" value="{{ $draft['services_config']['title'] ?? '' }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Section Subtitle</label>
                                <input type="text" name="subtitle" class="form-control form-control-sm" value="{{ $draft['services_config']['subtitle'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Catalog Link Text</label>
                                <input type="text" name="catalog_link_text" class="form-control form-control-sm" value="{{ $draft['services_config']['catalog_link_text'] ?? 'View Full Catalog →' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Display Mode</label>
                                <select name="mode" class="form-select form-select-sm">
                                    <option value="all_active" {{ ($draft['services_config']['mode'] ?? 'all_active') === 'all_active' ? 'selected' : '' }}>All Active Services</option>
                                    <option value="selected" {{ ($draft['services_config']['mode'] ?? '') === 'selected' ? 'selected' : '' }}>Selected Featured Only</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Desktop Cards / Row</label>
                                <select name="desktop_per_view" class="form-select form-select-sm">
                                    <option value="4" {{ (int) ($draft['services_config']['desktop_per_view'] ?? 4) === 4 ? 'selected' : '' }}>4 Cards (Default)</option>
                                    <option value="3" {{ (int) ($draft['services_config']['desktop_per_view'] ?? 4) === 3 ? 'selected' : '' }}>3 Cards</option>
                                    <option value="2" {{ (int) ($draft['services_config']['desktop_per_view'] ?? 4) === 2 ? 'selected' : '' }}>2 Cards</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Autoplay Interval (ms)</label>
                                <input type="number" name="interval_ms" class="form-control form-control-sm" min="1500" max="15000" step="100" value="{{ $draft['services_config']['interval_ms'] ?? 4600 }}">
                            </div>
                            <div class="col-md-6 d-flex flex-wrap align-items-end gap-3">
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="carousel_enabled" value="1" {{ !empty($draft['services_config']['carousel_enabled']) ? 'checked' : '' }}><label class="form-check-label">Carousel</label></div>
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="autoplay" value="1" {{ !empty($draft['services_config']['autoplay']) ? 'checked' : '' }}><label class="form-check-label">Autoplay</label></div>
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_arrows" value="1" {{ !empty($draft['services_config']['show_arrows']) ? 'checked' : '' }}><label class="form-check-label">Arrows</label></div>
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="pause_on_hover" value="1" {{ !empty($draft['services_config']['pause_on_hover']) ? 'checked' : '' }}><label class="form-check-label">Pause Hover</label></div>
                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="loop" value="1" {{ !empty($draft['services_config']['loop']) ? 'checked' : '' }}><label class="form-check-label">Loop</label></div>
                            </div>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover align-middle mb-0" style="min-width: 820px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Show</th>
                                        <th style="width: 85px;">Order</th>
                                        <th>Service (Live DB Price)</th>
                                        <th>Badge Override</th>
                                        <th>Homepage Description Override</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($services as $svc)
                                        @php
                                            $ov = $draft['services_config']['overrides'][$svc->id] ?? ($draft['services_config']['overrides'][(string) $svc->id] ?? []);
                                            $isVis = array_key_exists('visible', $ov) ? !empty($ov['visible']) : true;
                                        @endphp
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input" name="overrides[{{ $svc->id }}][visible]" value="1" {{ $isVis ? 'checked' : '' }} {{ !$svc->is_active ? 'disabled' : '' }}>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm" name="overrides[{{ $svc->id }}][order]" value="{{ $ov['order'] ?? $svc->id }}" min="0">
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $svc->name }}</div>
                                                <div class="text-muted">
                                                    {{ $svc->price !== null ? formatMoney($svc->price, $svc->currency_code ?? null) : 'Variable' }}
                                                    &middot; <span class="{{ $svc->is_active ? 'text-success' : 'text-secondary' }}">{{ $svc->is_active ? 'Active' : 'Inactive' }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="overrides[{{ $svc->id }}][custom_badge]" value="{{ $ov['custom_badge'] ?? '' }}" placeholder="{{ $svc->category?->name ?? 'Category' }}">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="overrides[{{ $svc->id }}][custom_description]" value="{{ $ov['custom_description'] ?? '' }}" placeholder="{{ \Illuminate\Support\Str::limit($svc->description, 45) }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Services Draft</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 7. Media Library -->
        <div class="tab-pane fade" id="tabMedia">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3 small">
                    <form id="wmMediaUploadForm" class="row g-2 align-items-end" enctype="multipart/form-data">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold mb-1">Upload Homepage Image (JPG, PNG, WebP, max 4MB)</label>
                            <input type="file" name="image" id="wmMediaFile" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold mb-1">Alt Text (Optional)</label>
                            <input type="text" name="alt_text" id="wmMediaAlt" class="form-control form-control-sm" placeholder="Describe the image">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-warning fw-semibold w-100" id="wmMediaUploadBtn">Upload Image</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row g-3" id="wmMediaGrid">
                @forelse($mediaItems as $m)
                    <div class="col-6 col-md-3 col-xl-2" data-media-id="{{ $m['id'] }}">
                        <div class="wm-media-card h-100 d-flex flex-column">
                            <div class="wm-media-thumb p-2">
                                <img src="{{ $m['url'] }}" alt="{{ $m['alt_text'] }}">
                            </div>
                            <div class="p-2 small flex-grow-1 d-flex flex-column justify-content-between">
                                <div class="fw-semibold text-truncate" title="{{ $m['filename'] }}">{{ $m['filename'] }}</div>
                                <div class="text-muted" style="font-size: 0.72rem;">{{ round($m['size_bytes'] / 1024) }} KB</div>
                                <div class="d-flex gap-1 mt-2">
                                    <button type="button" class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2 flex-grow-1 wm-copy-path" data-path="{{ $m['url'] }}">Copy URL</button>
                                    <button type="button" class="btn btn-xs btn-outline-danger btn-sm py-0 px-2 wm-delete-media" data-url="{{ route('admin.website-manager.homepage.media.delete', $m['id']) }}">Del</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><div class="p-4 text-center text-muted bg-white rounded border small">No custom media uploaded yet.</div></div>
                @endforelse
            </div>
        </div>

        <!-- 8. SEO & History -->
        <div class="tab-pane fade" id="tabSeo">
            <div class="card border-0 shadow-sm mb-3">
                <form id="wmSeoForm">
                    <div class="card-body p-3 p-md-4 small">
                        <h6 class="fw-bold mb-3">Homepage SEO &amp; Open Graph Settings</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Meta Title</label>
                                <input type="text" name="meta_title" class="form-control form-control-sm" value="{{ $draft['seo']['meta_title'] ?? '' }}" placeholder="Defaults to Site Name - Professional Laundry & Dry Cleaning">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Robots Indexing</label>
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
                                <label class="form-label fw-semibold">Canonical URL (Optional)</label>
                                <input type="text" name="canonical_url" class="form-control form-control-sm" value="{{ $draft['seo']['canonical_url'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Open Graph Title</label>
                                <input type="text" name="og_title" class="form-control form-control-sm" value="{{ $draft['seo']['og_title'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Open Graph Description</label>
                                <input type="text" name="og_description" class="form-control form-control-sm" value="{{ $draft['seo']['og_description'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Open Graph Image URL</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="og_image_url" id="wmOgImgInput" class="form-control" value="{{ $draft['seo']['og_image_url'] ?? '' }}">
                                    <button type="button" class="btn btn-outline-secondary wm-pick-media-btn" data-target="#wmOgImgInput">Choose Media</button>
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
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0">Content Version History</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 small" style="min-width: 650px;">
                        <thead class="table-light">
                            <tr>
                                <th>Version</th>
                                <th>Action</th>
                                <th>Section</th>
                                <th>Changed By</th>
                                <th>Date</th>
                                <th class="text-end">Restore</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($versions as $ver)
                                <tr>
                                    <td class="fw-bold">#{{ $ver->version_number }}</td>
                                    <td>
                                        <span class="badge {{ $ver->action === 'published' ? 'bg-success-subtle text-success' : 'bg-light text-dark border' }}">
                                            {{ ucfirst(str_replace('_', ' ', $ver->action)) }}
                                        </span>
                                    </td>
                                    <td>{{ $ver->section_key ?: 'All' }}</td>
                                    <td>{{ $ver->createdByAdmin?->name ?? 'System' }}</td>
                                    <td>{{ $ver->created_at?->format('d M Y, H:i') }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 wm-restore-ver-btn"
                                                data-url="{{ route('admin.website-manager.homepage.versions.restore', $ver) }}">
                                            Restore to Draft
                                        </button>
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
<div class="modal fade" id="wmMediaPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold">Select Homepage Media</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2" id="wmPickerGrid">
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
    let pickerTargetSelector = null;

    function showAlert(msg, type = 'success') {
        $('#wmAlertBox').removeClass('d-none alert-success alert-danger').addClass(type === 'success' ? 'alert-success' : 'alert-danger').text(msg);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function extractError(xhr, fallback) {
        if (xhr.responseJSON?.errors) {
            const k = Object.keys(xhr.responseJSON.errors)[0];
            if (k && xhr.responseJSON.errors[k][0]) return xhr.responseJSON.errors[k][0];
        }
        return xhr.responseJSON?.message || fallback;
    }

    function postDraft(url, data, btn, defaultLabel) {
        btn.prop('disabled', true).text('Saving...');
        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: data,
            success: function(res) {
                showAlert(res.message, 'success');
                if (res.draft_saved_at) {
                    $('#wmDraftMeta').html('<strong>Last Draft:</strong> ' + res.draft_saved_at);
                }
            },
            error: function(xhr) {
                showAlert(extractError(xhr, 'Failed to save draft.'), 'danger');
            },
            complete: function() {
                btn.prop('disabled', false).text(defaultLabel);
            }
        });
    }

    // Reorder arrows in Section Manager
    $(document).on('click', '.wm-move-up', function() {
        const row = $(this).closest('tr');
        const prev = row.prev('tr');
        if (prev.length) {
            row.insertBefore(prev);
            $('#wmSectionsBody tr').each((i, el) => $(el).find('.wm-sec-order').val(i + 1));
        }
    });
    $(document).on('click', '.wm-move-down', function() {
        const row = $(this).closest('tr');
        const next = row.next('tr');
        if (next.length) {
            row.insertAfter(next);
            $('#wmSectionsBody tr').each((i, el) => $(el).find('.wm-sec-order').val(i + 1));
        }
    });

    $('#wmSectionsForm').on('submit', function(e) {
        e.preventDefault();
        const sections = [];
        $('#wmSectionsBody tr').each(function() {
            sections.push({
                key: $(this).find('.wm-sec-key').val(),
                label: $(this).find('.wm-sec-label').val(),
                subtitle: $(this).find('.wm-sec-sub').val(),
                order: parseInt($(this).find('.wm-sec-order').val() || '1', 10),
                visible: $(this).find('.wm-sec-visible').is(':checked') ? 1 : 0
            });
        });
        postDraft(@json(route('admin.website-manager.homepage.sections.save')), { sections }, $(this).find('button[type="submit"]'), 'Save Sections Draft');
    });

    $('#wmHeroForm').on('submit', function(e) {
        e.preventDefault();
        postDraft(@json(route('admin.website-manager.homepage.hero.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Hero Draft');
    });

    $('#wmSearchForm').on('submit', function(e) {
        e.preventDefault();
        postDraft(@json(route('admin.website-manager.homepage.search.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Search Draft');
    });

    $('#wmCategoriesForm').on('submit', function(e) {
        e.preventDefault();
        postDraft(@json(route('admin.website-manager.homepage.categories.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Categories Draft');
    });

    $('#wmPromoForm').on('submit', function(e) {
        e.preventDefault();
        postDraft(@json(route('admin.website-manager.homepage.promo-banner.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Promo Banner Draft');
    });

    $('#wmServicesForm').on('submit', function(e) {
        e.preventDefault();
        postDraft(@json(route('admin.website-manager.homepage.services.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Services Draft');
    });

    $('#wmSeoForm').on('submit', function(e) {
        e.preventDefault();
        postDraft(@json(route('admin.website-manager.homepage.seo.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save SEO Draft');
    });

    $('#wmPublishBtn').on('click', function() {
        const btn = $(this).prop('disabled', true).text('Publishing...');
        $.ajax({
            url: @json(route('admin.website-manager.homepage.publish')),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function(res) {
                showAlert(res.message, 'success');
                $('#wmPubMeta').html('<strong>Published:</strong> <span class="text-success fw-semibold">' + res.published_at + '</span>');
            },
            error: function(xhr) {
                showAlert(extractError(xhr, 'Failed to publish homepage.'), 'danger');
            },
            complete: function() {
                btn.prop('disabled', false).text('Publish Changes');
            }
        });
    });

    $(document).on('click', '.wm-restore-ver-btn', function() {
        const url = $(this).data('url');
        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function(res) {
                showAlert(res.message, 'success');
                setTimeout(() => window.location.reload(), 900);
            },
            error: function(xhr) {
                showAlert(extractError(xhr, 'Restore failed.'), 'danger');
            }
        });
    });

    $('#wmMediaUploadForm').on('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const btn = $('#wmMediaUploadBtn').prop('disabled', true).text('Uploading...');
        $.ajax({
            url: @json(route('admin.website-manager.homepage.media.upload')),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: fd,
            processData: false,
            contentType: false,
            success: function(res) {
                showAlert(res.message, 'success');
                const m = res.media;
                const card = `
                    <div class="col-6 col-md-3 col-xl-2" data-media-id="${m.id}">
                        <div class="wm-media-card h-100 d-flex flex-column">
                            <div class="wm-media-thumb p-2"><img src="${m.url}" alt=""></div>
                            <div class="p-2 small flex-grow-1 d-flex flex-column justify-content-between">
                                <div class="fw-semibold text-truncate">${m.filename}</div>
                                <div class="d-flex gap-1 mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 flex-grow-1 wm-copy-path" data-path="${m.url}">Copy URL</button>
                                </div>
                            </div>
                        </div>
                    </div>`;
                $('#wmMediaGrid').prepend(card);
                $('#wmPickerGrid').prepend(`<div class="col-6 col-md-3"><div class="wm-media-card p-2 text-center"><div class="wm-media-thumb mb-2"><img src="${m.url}" alt=""></div><button type="button" class="btn btn-sm btn-warning w-100 wm-select-media-item" data-url="${m.url}">Use Image</button></div></div>`);
                $('#wmMediaUploadForm')[0].reset();
            },
            error: function(xhr) {
                showAlert(extractError(xhr, 'Upload failed.'), 'danger');
            },
            complete: function() {
                btn.prop('disabled', false).text('Upload Image');
            }
        });
    });

    $(document).on('click', '.wm-copy-path', function() {
        navigator.clipboard?.writeText($(this).data('path'));
        showAlert('Image URL copied to clipboard.', 'success');
    });

    $(document).on('click', '.wm-delete-media', function() {
        const card = $(this).closest('[data-media-id]');
        $.ajax({
            url: $(this).data('url'),
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function(res) {
                card.remove();
                showAlert(res.message, 'success');
            },
            error: function(xhr) {
                showAlert(extractError(xhr, 'Cannot delete media.'), 'danger');
            }
        });
    });

    $(document).on('click', '.wm-pick-media-btn', function() {
        pickerTargetSelector = $(this).data('target');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('wmMediaPickerModal')).show();
    });

    $(document).on('click', '.wm-select-media-item', function() {
        if (pickerTargetSelector) {
            $(pickerTargetSelector).val($(this).data('url'));
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('wmMediaPickerModal')).hide();
    });
});
</script>
@endpush
