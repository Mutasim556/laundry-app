@extends('admin::layouts.app')

@push('styles')
<!-- Toastr CSS -->
<link rel="stylesheet" href="{{ asset('admin/dist/libs/toastr/toastr.min.css') }}">
<style>
    .asset-card {
        transition: all 0.25s ease;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        background: #ffffff;
    }
    .asset-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    }
    .preview-box {
        min-height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        padding: 1.25rem;
        transition: background-color 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .bg-checkerboard {
        background-color: #ffffff;
        background-image: linear-gradient(45deg, #f1f5f9 25%, transparent 25%),
                          linear-gradient(-45deg, #f1f5f9 25%, transparent 25%),
                          linear-gradient(45deg, transparent 75%, #f1f5f9 75%),
                          linear-gradient(-45deg, transparent 75%, #f1f5f9 75%);
        background-size: 16px 16px;
        background-position: 0 0, 0 8px, 8px -8px, -8px 0px;
    }
    .bg-preview-dark {
        background-color: #0f172a !important;
    }
    .bg-preview-light {
        background-color: #f8fafc !important;
    }
    .dropzone-box {
        border: 2px dashed #cbd5e1;
        border-radius: 0.75rem;
        padding: 1.5rem 1rem;
        text-align: center;
        background-color: #f8fafc;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .dropzone-box:hover, .dropzone-box.dragover {
        border-color: #1e88e5;
        background-color: #f0f7ff;
    }
    .badge-soft-success {
        background-color: rgba(16, 185, 129, 0.12);
        color: #059669;
        font-weight: 600;
    }
    .badge-soft-secondary {
        background-color: #f1f5f9;
        color: #475569;
        font-weight: 600;
    }
    .badge-soft-primary {
        background-color: rgba(30, 136, 229, 0.12);
        color: #1e88e5;
        font-weight: 600;
    }
    .btn-animate {
        transition: all 0.2s ease;
    }
    .btn-animate:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(30, 136, 229, 0.25);
    }
    .btn-animate:active {
        transform: translateY(0);
    }
    .theme-toggle-btn {
        width: 24px;
        height: 24px;
        padding: 0;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
    }
    /* Enhanced Animated Action Button with State Machine */
    .btn-minimal-save, .btn-save-glow {
        background-color: #1e88e5;
        color: #ffffff !important;
        font-weight: 500;
        font-size: 0.875rem;
        line-height: 1.25rem;
        padding: 0.55rem 1.35rem;
        border-radius: 8px;
        border: 1px solid #1e88e5;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        position: relative;
        user-select: none;
        min-width: 145px;
    }
    .btn-minimal-save:hover, .btn-save-glow:hover {
        background-color: #1976d2;
        border-color: #1976d2;
        color: #ffffff !important;
        box-shadow: 0 4px 10px rgba(30, 136, 229, 0.25);
        transform: translateY(-1px);
    }
    .btn-minimal-save:active, .btn-save-glow:active {
        background-color: #1565c0;
        border-color: #1565c0;
        transform: translateY(0);
        box-shadow: none;
    }
    .btn-minimal-save.is-saving {
        background-color: #1976d2 !important;
        border-color: #1976d2 !important;
        opacity: 0.9 !important;
        cursor: wait !important;
        pointer-events: none;
        transform: none !important;
    }
    .btn-minimal-save.is-success {
        background-color: #10b981 !important;
        border-color: #059669 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35) !important;
        animation: pulseSuccess 0.45s ease-out;
        cursor: default !important;
        transform: none !important;
    }
    .btn-minimal-save:disabled:not(.is-saving):not(.is-success) {
        opacity: 0.6;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    @keyframes pulseSuccess {
        0% { transform: scale(0.95); }
        50% { transform: scale(1.04); }
        100% { transform: scale(1); }
    }

    .btn-minimal-clear, .btn-clear-pill {
        background-color: #ffffff;
        color: #475569 !important;
        font-weight: 500;
        font-size: 0.875rem;
        line-height: 1.25rem;
        padding: 0.55rem 1.15rem;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
        transition: all 0.2s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }
    .btn-minimal-clear:hover, .btn-clear-pill:hover {
        background-color: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    }
    .btn-minimal-clear:active, .btn-clear-pill:active {
        background-color: #f1f5f9;
        transform: translateY(0);
    }
    .btn-minimal-clear.is-clearing i, .btn-minimal-clear.is-clearing svg {
        animation: spinReverse 0.4s ease;
    }

    @keyframes spinReverse {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(-360deg); }
    }

    /* Modern Header Hero Banner (Consistent with Roles module) */
    .settings-hero-banner {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #9333ea 100%);
        border-radius: 16px;
        padding: 24px 28px;
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.25), 0 8px 10px -6px rgba(79, 70, 229, 0.2);
        color: #ffffff;
        position: relative;
        overflow: hidden;
    }
    .settings-hero-banner::after {
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
    .settings-hero-title {
        color: #ffffff !important;
        font-size: 1.55rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        margin-bottom: 4px;
    }
    .settings-breadcrumb {
        background: transparent;
        padding: 0;
        margin: 0;
    }
    .settings-breadcrumb .breadcrumb-item a {
        color: rgba(255, 255, 255, 0.82) !important;
        font-weight: 500;
        transition: color 0.15s ease;
    }
    .settings-breadcrumb .breadcrumb-item a:hover {
        color: #ffffff !important;
        text-decoration: underline;
    }
    .settings-breadcrumb .breadcrumb-item.active {
        color: #ffffff !important;
        font-weight: 600;
    }
    .settings-breadcrumb .breadcrumb-item + .breadcrumb-item::before {
        color: rgba(255, 255, 255, 0.6);
    }

    /* Hero Action Button */
    .btn-hero-action {
        background: #ffffff;
        color: #4f46e5;
        font-weight: 600;
        border: none;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .btn-hero-action:hover, .btn-hero-action:focus {
        background: #f8fafc;
        color: #4338ca;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.18);
    }

    /* Floating Bar that NEVER disappears or jumps */
    .sticky-action-bar {
        position: sticky;
        bottom: 1.25rem;
        z-index: 1020;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        margin-top: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Modern Header & Breadcrumbs Hero Banner (matching roles index.blade.php) -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="settings-hero-banner d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h3 class="settings-hero-title">Brand & Logo Settings</h3>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb settings-breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Brand & Logos</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-hero-action rounded-pill px-4 py-2 d-inline-flex align-items-center text-decoration-none">
                        <i data-feather="arrow-left" class="feather-sm me-2"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main AJAX Form -->
    <form id="branding-form" enctype="multipart/form-data">
        @csrf

        <!-- Application Identity & Footer Text Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold text-dark mb-1">Application Identity & Copyright</h5>
                <p class="text-muted small mb-4">Configure global site branding, organization title, and footer copyright text.</p>

                <div class="row g-4">
                    <!-- Brand Name -->
                    <div class="col-md-6">
                        <label for="site_name" class="form-label text-dark small fw-semibold">Application / Brand Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-feather="tag" class="feather-sm text-muted"></i></span>
                            <input
                                type="text"
                                class="form-control border-start-0"
                                id="site_name"
                                name="site_name"
                                value="{{ old('site_name', $settings['site_name']) }}"
                                placeholder="e.g. Laundry App, CleanWash Express"
                                maxlength="100"
                            >
                        </div>
                        <small class="text-muted">Used across browser titles, meta headers, and email notifications.</small>
                    </div>

                    <!-- Footer Copyright Text -->
                    <div class="col-md-6">
                        <label for="footer_text" class="form-label text-dark small fw-semibold">Footer Copyright Text</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-feather="shield" class="feather-sm text-muted"></i></span>
                            <input
                                type="text"
                                class="form-control border-start-0"
                                id="footer_text"
                                name="footer_text"
                                value="{{ old('footer_text', $settings['footer_text']) }}"
                                placeholder="e.g. All site reserved by Laundry App."
                                maxlength="255"
                            >
                        </div>
                        <small class="text-muted">Shown in the admin footer bar. Example: <em>All site reserved by Laundry App.</em></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asset Cards Grid (Logo & Icon Upload with Comparison) -->
        <div class="row g-4 mb-4">
            @php
                $assetCards = [
                    'site_logo' => [
                        'title' => 'Primary Brand Logo',
                        'badge' => 'Main Header & Navbar',
                        'recommended' => 'Recommended: 200×50px transparent PNG or SVG',
                        'max_height' => '55px',
                    ],
                    'site_logo_dark' => [
                        'title' => 'Dark Mode / Inverted Logo',
                        'badge' => 'Dark Backdrops & Footers',
                        'recommended' => 'Recommended: 200×50px white/light SVG or PNG',
                        'max_height' => '55px',
                    ],
                    'site_logo_login' => [
                        'title' => 'Login Page Logo',
                        'badge' => 'Admin Login Portal',
                        'recommended' => 'Recommended: 220×60px transparent PNG or SVG',
                        'max_height' => '55px',
                    ],
                    'site_icon' => [
                        'title' => 'App Icon / Mini Logo',
                        'badge' => 'Collapsed Sidebar & Mobile',
                        'recommended' => 'Recommended: 64×64px square PNG or SVG',
                        'max_height' => '50px',
                    ],
                    'site_favicon' => [
                        'title' => 'Browser Favicon',
                        'badge' => 'Browser Tabs & Bookmarks',
                        'recommended' => 'Recommended: 32×32px or 64×64px ICO, PNG, or SVG',
                        'max_height' => '40px',
                    ],
                    'site_preloader' => [
                        'title' => 'Page Preloader / Reloader Animation',
                        'badge' => 'Page Loading & Transitions',
                        'recommended' => 'Recommended: 80×80px to 120×120px animated GIF, SVG, APNG, WebP, or PNG',
                        'max_height' => '70px',
                    ],
                ];
            @endphp

            @foreach ($assetCards as $key => $cardMeta)
                @php
                    $asset = $settings[$key];
                @endphp
                <div class="col-lg-6">
                    <div class="card asset-card h-100 shadow-sm p-4">
                        <!-- Card Header -->
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div>
                                <h5 class="fw-bold text-dark mb-1">{{ $cardMeta['title'] }}</h5>
                                <span class="badge badge-soft-primary px-2 py-1 rounded-pill small">{{ $cardMeta['badge'] }}</span>
                            </div>
                            <span class="text-muted small"><i data-feather="info" class="feather-sm"></i></span>
                        </div>
                        <p class="text-muted small mb-3">{{ $asset['description'] }}</p>

                        <!-- Comparison Row: Previous (Current) vs New Preview -->
                        <div class="row g-3 mb-3">
                            <!-- Previous / Current Asset (Live Production Image) -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                        Previous (Current)
                                    </span>
                                    <!-- Background Preview Controls -->
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-secondary theme-toggle-btn" title="Checkerboard" onclick="switchBg('current-preview-{{ $key }}', 'bg-checkerboard')">
                                            🏁
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-dark theme-toggle-btn" title="Dark bg" onclick="switchBg('current-preview-{{ $key }}', 'bg-preview-dark')">
                                            🌙
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary theme-toggle-btn" title="Light bg" onclick="switchBg('current-preview-{{ $key }}', 'bg-preview-light')">
                                            ☀️
                                        </button>
                                    </div>
                                </div>

                                <div id="current-preview-{{ $key }}" class="preview-box bg-checkerboard border">
                                    <img
                                        id="current-img-{{ $key }}"
                                        src="{{ $asset['url'] }}?v={{ time() }}"
                                        alt="{{ $cardMeta['title'] }}"
                                        style="max-height: {{ $cardMeta['max_height'] }}; max-width: 100%; object-fit: contain;"
                                    >
                                </div>

                                <div class="d-flex align-items-center justify-content-between mt-2">
                                    <span id="custom-badge-{{ $key }}" class="badge {{ $asset['is_custom'] ? 'badge-soft-success' : 'badge-soft-secondary' }} rounded-pill small px-2 py-0.5">
                                        {{ $asset['is_custom'] ? 'Custom Uploaded' : 'Default System Asset' }}
                                    </span>
                                    <button
                                        type="button"
                                        id="reset-btn-{{ $key }}"
                                        class="btn btn-sm btn-link text-danger p-0 text-decoration-none small {{ $asset['is_custom'] ? '' : 'd-none' }}"
                                        onclick="ajaxResetAsset('{{ $key }}', '{{ $cardMeta['title'] }}')"
                                    >
                                        <i data-feather="rotate-ccw" class="feather-sm"></i> Reset to Default
                                    </button>
                                </div>
                            </div>

                            <!-- New Asset Preview (Before Upload, Selected File) -->
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                        New Asset Preview
                                    </span>
                                    <!-- Background Preview Controls -->
                                    <div class="d-flex gap-1 new-preview-controls-{{ $key }}" style="display: none !important;">
                                        <button type="button" class="btn btn-sm btn-outline-secondary theme-toggle-btn" title="Checkerboard" onclick="switchBg('new-preview-box-{{ $key }}', 'bg-checkerboard')">
                                            🏁
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-dark theme-toggle-btn" title="Dark bg" onclick="switchBg('new-preview-box-{{ $key }}', 'bg-preview-dark')">
                                            🌙
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary theme-toggle-btn" title="Light bg" onclick="switchBg('new-preview-box-{{ $key }}', 'bg-preview-light')">
                                            ☀️
                                        </button>
                                    </div>
                                </div>

                                <!-- Box when no new file is chosen yet -->
                                <div id="no-preview-box-{{ $key }}" class="preview-box bg-light border border-dashed text-muted small text-center p-3">
                                    <div class="py-2">
                                        <i data-feather="image" class="feather-lg text-muted mb-1 opacity-50" style="width: 28px; height: 28px;"></i>
                                        <p class="mb-0 text-muted" style="font-size: 0.8rem;">No new file chosen.<br>Select a file below to preview.</p>
                                    </div>
                                </div>

                                <!-- Box when new file is chosen -->
                                <div id="new-preview-box-{{ $key }}" class="preview-box bg-checkerboard border" style="display: none;">
                                    <img
                                        id="new-preview-img-{{ $key }}"
                                        src=""
                                        alt="New Preview"
                                        style="max-height: {{ $cardMeta['max_height'] }}; max-width: 100%; object-fit: contain;"
                                    >
                                </div>

                                <div id="new-preview-meta-{{ $key }}" class="d-flex align-items-center justify-content-between mt-2" style="display: none !important;">
                                    <span class="badge bg-primary text-white rounded-pill small px-2 py-0.5">
                                        <i data-feather="upload" class="feather-sm"></i> Ready to Save
                                    </span>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-link text-danger p-0 text-decoration-none small"
                                        onclick="clearFileInput('{{ $key }}')"
                                    >
                                        <i data-feather="x" class="feather-sm"></i> Cancel
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Dropzone & File Input -->
                        <div class="mt-auto">
                            <input
                                type="file"
                                id="input-{{ $key }}"
                                name="{{ $key }}"
                                class="d-none"
                                accept="{{ $key === 'site_favicon' ? '.ico,.png,.svg,.webp' : ($key === 'site_preloader' ? '.gif,.png,.svg,.webp,.jpg,.jpeg' : '.png,.jpg,.jpeg,.svg,.webp') }}"
                                onchange="handleFileSelect(event, '{{ $key }}')"
                            >
                            <div
                                class="dropzone-box"
                                onclick="document.getElementById('input-{{ $key }}').click()"
                                ondragover="handleDragOver(event)"
                                ondragleave="handleDragLeave(event)"
                                ondrop="handleDrop(event, '{{ $key }}')"
                            >
                                <i data-feather="upload-cloud" class="feather-sm text-primary mb-1"></i>
                                <div class="fw-semibold text-dark small">Click to browse or drag & drop file</div>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $cardMeta['recommended'] }} (Max 2MB)</div>
                                <div id="filename-badge-{{ $key }}" class="mt-2 text-primary fw-bold small" style="display: none;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Sticky Floating Action Bar -->
        <div class="card border-0 rounded-3 sticky-bottom mb-4 sticky-action-bar">
            <div class="card-body p-3 px-4 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i data-feather="info" class="text-muted" style="width: 15px; height: 15px;"></i>
                    <span class="text-muted small">
                        All uploaded assets and brand details update immediately across the platform.
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="clear-previews-btn" class="btn btn-minimal-clear" onclick="resetAllPreviews()">
                        <i data-feather="rotate-ccw" style="width: 14px; height: 14px;"></i>
                        <span>Clear Previews</span>
                    </button>
                    <button type="submit" id="save-branding-btn" class="btn btn-minimal-save">
                        <span id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" style="width: 14px; height: 14px; border-width: 2px;"></span>
                        <span id="btn-icon" class="d-inline-flex align-items-center">
                            <i data-feather="check" style="width: 15px; height: 15px;"></i>
                        </span>
                        <span id="btn-text">Save Changes</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<!-- Toastr JS -->
<script src="{{ asset('admin/dist/libs/toastr/toastr.min.js') }}"></script>
<script>
    // Toastr setup
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 4000
    };

    // Preview background switcher
    function switchBg(elementId, bgClass) {
        const el = document.getElementById(elementId);
        if (!el) return;
        el.classList.remove('bg-checkerboard', 'bg-preview-dark', 'bg-preview-light');
        el.classList.add(bgClass);
    }

    // Drag & Drop handlers
    function handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.add('dragover');
    }

    function handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.remove('dragover');
    }

    function handleDrop(e, key) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.classList.remove('dragover');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const input = document.getElementById('input-' + key);
            input.files = files;
            renderPreview(files[0], key);
        }
    }

    // Input change handler
    function handleFileSelect(e, key) {
        const file = e.target.files[0];
        if (file) {
            renderPreview(file, key);
        }
    }

    // Render Preview
    function renderPreview(file, key) {
        const maxSize = 2 * 1024 * 1024;
        if (file.size > maxSize) {
            toastr.error('File exceeds 2MB limit: ' + (file.size / (1024 * 1024)).toFixed(2) + 'MB', 'File Too Large');
            clearFileInput(key);
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const noBox = document.getElementById('no-preview-box-' + key);
            const newBox = document.getElementById('new-preview-box-' + key);
            const newImg = document.getElementById('new-preview-img-' + key);
            const metaBar = document.getElementById('new-preview-meta-' + key);
            const controls = document.querySelector('.new-preview-controls-' + key);
            const filenameBadge = document.getElementById('filename-badge-' + key);

            newImg.src = e.target.result;
            noBox.style.display = 'none';
            newBox.style.display = 'flex';
            metaBar.style.removeProperty('display');
            if (controls) controls.style.removeProperty('display');

            const sizeKb = (file.size / 1024).toFixed(1) + ' KB';
            if (filenameBadge) {
                filenameBadge.textContent = 'Selected: ' + file.name + ' (' + sizeKb + ')';
                filenameBadge.style.display = 'block';
            }

            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        };
        reader.readAsDataURL(file);
    }

    // Clear individual file input
    function clearFileInput(key) {
        const input = document.getElementById('input-' + key);
        if (input) input.value = '';

        const noBox = document.getElementById('no-preview-box-' + key);
        const newBox = document.getElementById('new-preview-box-' + key);
        const newImg = document.getElementById('new-preview-img-' + key);
        const metaBar = document.getElementById('new-preview-meta-' + key);
        const controls = document.querySelector('.new-preview-controls-' + key);
        const filenameBadge = document.getElementById('filename-badge-' + key);

        if (newImg) newImg.src = '';
        if (newBox) newBox.style.display = 'none';
        if (noBox) noBox.style.display = 'flex';
        if (metaBar) metaBar.style.setProperty('display', 'none', 'important');
        if (controls) controls.style.setProperty('display', 'none', 'important');
        if (filenameBadge) filenameBadge.style.display = 'none';
    }

    // Reset all pending file previews
    function resetAllPreviews() {
        const clearBtn = document.getElementById('clear-previews-btn');
        if (clearBtn) {
            clearBtn.classList.add('is-clearing');
            setTimeout(() => clearBtn.classList.remove('is-clearing'), 400);
        }
        ['site_logo', 'site_logo_dark', 'site_logo_login', 'site_icon', 'site_favicon', 'site_preloader'].forEach(clearFileInput);
    }

    // Update Live DOM elements immediately without reload
    function updateLiveDom(assets, customStatus) {
        const timestamp = Date.now();

        // 1. Update Current Images on this page
        for (const [key, url] of Object.entries(assets)) {
            const currentImg = document.getElementById('current-img-' + key);
            if (currentImg && url) {
                currentImg.style.transition = 'opacity 0.2s ease';
                currentImg.style.opacity = '0.3';
                setTimeout(() => {
                    currentImg.src = url + '?v=' + timestamp;
                    currentImg.onload = () => {
                        currentImg.style.opacity = '1';
                    };
                }, 100);
            }

            // Update custom status badge & reset button
            if (customStatus && typeof customStatus[key] !== 'undefined') {
                const badge = document.getElementById('custom-badge-' + key);
                const resetBtn = document.getElementById('reset-btn-' + key);

                if (badge) {
                    if (customStatus[key]) {
                        badge.className = 'badge badge-soft-success rounded-pill small px-2 py-0.5';
                        badge.textContent = 'Custom Uploaded';
                    } else {
                        badge.className = 'badge badge-soft-secondary rounded-pill small px-2 py-0.5';
                        badge.textContent = 'Default System Asset';
                    }
                }

                if (resetBtn) {
                    if (customStatus[key]) {
                        resetBtn.classList.remove('d-none');
                    } else {
                        resetBtn.classList.add('d-none');
                    }
                }
            }

            // Clear the file input and pending preview for that key
            clearFileInput(key);
        }

        // 2. Update Topbar Header Logos in Layout
        const headerDarkLogo = document.getElementById('header-dark-logo');
        if (headerDarkLogo && assets.site_logo) {
            headerDarkLogo.src = assets.site_logo + '?v=' + timestamp;
        }

        const headerLightLogo = document.getElementById('header-light-logo');
        if (headerLightLogo && assets.site_logo_dark) {
            headerLightLogo.src = assets.site_logo_dark + '?v=' + timestamp;
        }

        const headerDarkIcon = document.getElementById('header-dark-icon');
        if (headerDarkIcon && assets.site_icon) {
            headerDarkIcon.src = assets.site_icon + '?v=' + timestamp;
        }

        const headerLightIcon = document.getElementById('header-light-icon');
        if (headerLightIcon && assets.site_icon) {
            headerLightIcon.src = assets.site_icon + '?v=' + timestamp;
        }

        // 3. Update Browser Favicon
        const faviconEl = document.getElementById('app-favicon');
        if (faviconEl && assets.site_favicon) {
            faviconEl.href = assets.site_favicon + '?v=' + timestamp;
        }

        // 4. Update Footer Text
        const footerTextEl = document.getElementById('live-footer-text');
        if (footerTextEl && assets.footer_text) {
            footerTextEl.textContent = assets.footer_text;
        }

        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }

    // AJAX Form Submission with Smooth Animation & Guaranteed Stability
    document.getElementById('branding-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);
        const submitBtn = document.getElementById('save-branding-btn');
        const spinner = document.getElementById('btn-spinner');
        const iconSpan = document.getElementById('btn-icon');
        const text = document.getElementById('btn-text');

        if (submitBtn.classList.contains('is-saving') || submitBtn.classList.contains('is-success')) {
            return;
        }

        // Show loading state with smooth animation
        const startTime = Date.now();
        submitBtn.classList.add('is-saving');
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        iconSpan.classList.add('d-none');
        text.textContent = 'Saving...';

        fetch("{{ route('admin.settings.update') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => { throw data; });
            }
            return response.json();
        })
        .then(data => {
            const elapsed = Date.now() - startTime;
            const minDelay = Math.max(0, 450 - elapsed);

            setTimeout(() => {
                if (data.success) {
                    // Success state with pulse animation
                    submitBtn.classList.remove('is-saving');
                    submitBtn.classList.add('is-success');
                    spinner.classList.add('d-none');
                    iconSpan.innerHTML = '<i data-feather="check-circle" style="width: 15px; height: 15px;"></i>';
                    if (typeof feather !== 'undefined') feather.replace();
                    iconSpan.classList.remove('d-none');
                    text.textContent = 'Saved!';

                    toastr.success(data.message || 'Branding updated successfully!', 'Success');
                    updateLiveDom(data.assets, data.custom_status);

                    // Revert back to normal state smoothly after 1.5 seconds
                    setTimeout(() => {
                        submitBtn.classList.remove('is-success');
                        iconSpan.innerHTML = '<i data-feather="check" style="width: 15px; height: 15px;"></i>';
                        if (typeof feather !== 'undefined') feather.replace();
                        text.textContent = 'Save Changes';
                        submitBtn.disabled = false;
                    }, 1500);
                }
            }, minDelay);
        })
        .catch(error => {
            console.error('Save error:', error);
            submitBtn.classList.remove('is-saving');
            spinner.classList.add('d-none');
            iconSpan.innerHTML = '<i data-feather="check" style="width: 15px; height: 15px;"></i>';
            if (typeof feather !== 'undefined') feather.replace();
            iconSpan.classList.remove('d-none');
            text.textContent = 'Save Changes';
            submitBtn.disabled = false;

            if (error.errors) {
                const messages = Object.values(error.errors).flat().join('<br>');
                toastr.error(messages, 'Validation Error');
            } else {
                toastr.error(error.message || 'An error occurred while saving branding.', 'Error');
            }
        });
    });

    // AJAX Reset Asset to Default (No page reload)
    function ajaxResetAsset(key, title) {
        if (!confirm('Are you sure you want to reset ' + title + ' back to the default system asset?')) {
            return;
        }

        const resetBtn = document.getElementById('reset-btn-' + key);
        if (resetBtn) resetBtn.disabled = true;

        fetch("{{ url('admin/settings') }}/" + key, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => { throw data; });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                toastr.info(title + ' has been reset to default.', 'Restored');

                // Update current image
                const currentImg = document.getElementById('current-img-' + key);
                if (currentImg && data.default_url) {
                    currentImg.src = data.default_url + '?v=' + Date.now();
                }

                // Update badge and hide reset button
                const badge = document.getElementById('custom-badge-' + key);
                if (badge) {
                    badge.className = 'badge badge-soft-secondary rounded-pill small px-2 py-0.5';
                    badge.textContent = 'Default System Asset';
                }

                if (resetBtn) {
                    resetBtn.classList.add('d-none');
                }

                // Update live header / favicon
                updateLiveDom(data.assets || {}, { [key]: false });
            }
        })
        .catch(error => {
            console.error('Reset error:', error);
            toastr.error(error.message || 'Failed to reset asset.', 'Error');
        })
        .finally(() => {
            if (resetBtn) resetBtn.disabled = false;
        });
    }

    // Initialize feather icons
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>
@endpush
