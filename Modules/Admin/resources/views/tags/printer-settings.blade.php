@extends('admin::layouts.app')

@section('title', 'Printer Settings')

@include('admin::tags.partials.shared-styles')

@section('content')
<div class="container-fluid py-3 tm-anim">
    <!-- Page Header -->
    <div class="tm-header-card p-3 p-md-4 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background: #FFC629; color: #171717; font-size: 0.72rem;">
                        Tag Management
                    </span>
                    <span class="badge rounded-pill bg-white text-dark border" style="font-size: 0.72rem;">
                        Print Calibration
                    </span>
                </div>
                <h4 class="fw-bold mb-1" style="color: #171717;">Printer Settings</h4>
                <div class="small text-muted">Customize tag printing dimensions, page layout, and print appearance.</div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if($editingProfile)
                    <a href="{{ route('admin.tags.print-preview', ['profile_id' => $editingProfile->id]) }}" class="btn btn-sm btn-light border bg-white px-3 fw-semibold shadow-sm">
                        <i data-feather="eye" style="width: 14px; height: 14px;" class="me-1"></i>Preview Print
                    </a>
                @endif
                <a href="{{ route('admin.tags.printer-settings.index') }}" class="btn btn-sm btn-warning px-3 fw-semibold shadow-sm">
                    <i data-feather="plus-square" style="width: 14px; height: 14px;" class="me-1"></i>New Profile
                </a>
            </div>
        </div>
    </div>

    @include('admin::tags.partials.nav-pills')

    @if(session('success'))
        <div class="alert alert-success small py-2">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <!-- Saved Profiles List -->
        <div class="col-12 col-lg-3">
            <div class="tm-section-card">
                <div class="card-header fw-bold small text-dark">
                    <i data-feather="list" style="width: 14px; height: 14px;" class="me-1 text-warning"></i>Saved Profiles
                </div>
                <div class="list-group list-group-flush">
                    @foreach($profiles as $p)
                        <a href="{{ route('admin.tags.printer-settings.index', ['edit' => $p->id]) }}"
                            class="list-group-item list-group-item-action @if($editingProfile && $editingProfile->id === $p->id) active @endif">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="small">{{ $p->name }}</strong>
                                @if($p->is_default)
                                    <span class="badge bg-warning text-dark">Default</span>
                                @endif
                            </div>
                            <div class="small opacity-75">
                                {{ strtoupper($p->preset) }} ({{ $p->paper_width_mm }}×{{ $p->paper_height_mm }}mm) &bull; {{ $p->tags_per_row }}×{{ $p->tags_per_column }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Tabbed Settings Form + Live Preview -->
        <div class="col-12 col-lg-9">
            <div class="tm-section-card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-bold small text-dark">{{ $editingProfile ? "Edit Profile: {$editingProfile->name}" : 'Create New Printer Profile' }}</span>
                    <span id="psUnsavedBadge" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle d-none">Unsaved Preview Changes</span>
                </div>

                @php
                    $p = $editingProfile;
                    $action = $p ? route('admin.tags.printer-settings.update', $p) : route('admin.tags.printer-settings.store');
                @endphp
                <form id="printerSettingsForm" method="POST" action="{{ $action }}">
                    @csrf
                    @if($p) @method('PUT') @endif

                    <div class="card-body p-3 p-md-4">
                        <div id="printerSettingsAlert"></div>

                        <ul class="nav nav-pills tm-nav-pills gap-1 mb-3 flex-wrap" role="tablist">
                            <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#psTabPaper" type="button">1. Paper Setup</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#psTabTag" type="button">2. Tag Dimensions</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#psTabQr" type="button">3. QR &amp; Branding</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#psTabLayout" type="button">4. Print Layout</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#psTabPreview" type="button">5. Live Preview</button></li>
                        </ul>

                        <div class="tab-content small">
                            <!-- Tab 1: Paper Setup -->
                            <div class="tab-pane fade show active" id="psTabPaper">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Profile Name</label>
                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $p?->name ?? 'Custom Laundry Tag Profile') }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Paper Preset</label>
                                        <select name="preset" class="form-select form-select-sm" id="presetSelect">
                                            <option value="a4" @selected(old('preset', $p?->preset ?? 'a4') === 'a4')>A4 (210×297mm)</option>
                                            <option value="letter" @selected(old('preset', $p?->preset ?? '') === 'letter')>Letter (215.9×279.4mm)</option>
                                            <option value="roll" @selected(old('preset', $p?->preset ?? '') === 'roll')>Thermal Single Tag Roll</option>
                                            <option value="custom" @selected(old('preset', $p?->preset ?? '') === 'custom')>Custom Size</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Orientation</label>
                                        <select name="orientation" class="form-select form-select-sm">
                                            <option value="portrait" @selected(old('orientation', $p?->orientation ?? 'portrait') === 'portrait')>Portrait</option>
                                            <option value="landscape" @selected(old('orientation', $p?->orientation ?? '') === 'landscape')>Landscape</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3"><label class="form-label fw-semibold">Paper Width (mm)</label><input type="number" step="0.1" name="paper_width_mm" class="form-control form-control-sm" value="{{ old('paper_width_mm', $p?->paper_width_mm ?? 210) }}" required></div>
                                    <div class="col-md-3"><label class="form-label fw-semibold">Paper Height (mm)</label><input type="number" step="0.1" name="paper_height_mm" class="form-control form-control-sm" value="{{ old('paper_height_mm', $p?->paper_height_mm ?? 297) }}" required></div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="chkDef" @checked(old('is_default', $p?->is_default ?? false))>
                                            <label class="form-check-label fw-bold" for="chkDef">Set as Default Profile</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 2: Tag Dimensions -->
                            <div class="tab-pane fade" id="psTabTag">
                                <div class="row g-3">
                                    <div class="col-md-3"><label class="form-label fw-semibold">Tag Width (mm)</label><input type="number" step="0.1" name="tag_width_mm" class="form-control form-control-sm" value="{{ old('tag_width_mm', $p?->tag_width_mm ?? 40) }}" required></div>
                                    <div class="col-md-3"><label class="form-label fw-semibold">Tag Height (mm)</label><input type="number" step="0.1" name="tag_height_mm" class="form-control form-control-sm" value="{{ old('tag_height_mm', $p?->tag_height_mm ?? 125) }}" required></div>
                                    <div class="col-md-3"><label class="form-label fw-semibold">Horizontal Gap (mm)</label><input type="number" step="0.1" name="gap_horizontal_mm" class="form-control form-control-sm" value="{{ old('gap_horizontal_mm', $p?->gap_horizontal_mm ?? 6) }}" required></div>
                                    <div class="col-md-3"><label class="form-label fw-semibold">Vertical Gap (mm)</label><input type="number" step="0.1" name="gap_vertical_mm" class="form-control form-control-sm" value="{{ old('gap_vertical_mm', $p?->gap_vertical_mm ?? 12) }}" required></div>
                                    <div class="col-md-3"><label class="form-label fw-semibold">Margin Top (mm)</label><input type="number" step="0.1" name="margin_top_mm" class="form-control form-control-sm" value="{{ old('margin_top_mm', $p?->margin_top_mm ?? 10) }}" required></div>
                                    <div class="col-md-3"><label class="form-label fw-semibold">Margin Bottom (mm)</label><input type="number" step="0.1" name="margin_bottom_mm" class="form-control form-control-sm" value="{{ old('margin_bottom_mm', $p?->margin_bottom_mm ?? 10) }}" required></div>
                                    <div class="col-md-3"><label class="form-label fw-semibold">Margin Left (mm)</label><input type="number" step="0.1" name="margin_left_mm" class="form-control form-control-sm" value="{{ old('margin_left_mm', $p?->margin_left_mm ?? 14) }}" required></div>
                                    <div class="col-md-3"><label class="form-label fw-semibold">Margin Right (mm)</label><input type="number" step="0.1" name="margin_right_mm" class="form-control form-control-sm" value="{{ old('margin_right_mm', $p?->margin_right_mm ?? 14) }}" required></div>
                                </div>
                            </div>

                            <!-- Tab 3: QR & Branding -->
                            <div class="tab-pane fade" id="psTabQr">
                                <div class="row g-3">
                                    <div class="col-md-3"><label class="form-label fw-semibold">QR Size (mm)</label><input type="number" step="0.5" name="qr_size_mm" class="form-control form-control-sm" value="{{ old('qr_size_mm', $p?->qr_size_mm ?? 24) }}" required></div>
                                    <div class="col-md-3"><label class="form-label fw-semibold">Logo Size (mm)</label><input type="number" step="0.5" name="logo_size_mm" class="form-control form-control-sm" value="{{ old('logo_size_mm', $p?->logo_size_mm ?? 14) }}" required></div>
                                    <div class="col-md-3"><label class="form-label fw-semibold">Label Font (pt)</label><input type="number" step="0.5" name="font_size_pt" class="form-control form-control-sm" value="{{ old('font_size_pt', $p?->font_size_pt ?? 7.5) }}" required></div>
                                    <div class="col-md-3"><label class="form-label fw-semibold">Tag Code Font (pt)</label><input type="number" step="0.5" name="code_font_size_pt" class="form-control form-control-sm" value="{{ old('code_font_size_pt', $p?->code_font_size_pt ?? 8.5) }}" required></div>
                                    <div class="col-12 d-flex flex-wrap gap-4 pt-2 border-top">
                                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_brand_name" value="1" id="chkBrand" @checked(old('show_brand_name', $p?->show_brand_name ?? true))><label class="form-check-label" for="chkBrand">Show Brand Name</label></div>
                                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_booking_field" value="1" id="chkBooking" @checked(old('show_booking_field', $p?->show_booking_field ?? true))><label class="form-check-label" for="chkBooking">Show Booking Ref Field</label></div>
                                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_package_field" value="1" id="chkPkg" @checked(old('show_package_field', $p?->show_package_field ?? true))><label class="form-check-label" for="chkPkg">Show Package # Field</label></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 4: Print Layout -->
                            <div class="tab-pane fade" id="psTabLayout">
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="form-label fw-semibold">Tags Per Row</label><input type="number" name="tags_per_row" min="1" max="12" class="form-control form-control-sm" value="{{ old('tags_per_row', $p?->tags_per_row ?? 4) }}" required></div>
                                    <div class="col-md-4"><label class="form-label fw-semibold">Rows Per Page</label><input type="number" name="tags_per_column" min="1" max="12" class="form-control form-control-sm" value="{{ old('tags_per_column', $p?->tags_per_column ?? 2) }}" required></div>
                                    <div class="col-12 d-flex flex-wrap gap-4 pt-2 border-top">
                                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_cut_guides" value="1" id="chkCut" @checked(old('show_cut_guides', $p?->show_cut_guides ?? true))><label class="form-check-label" for="chkCut">Show Cut Guides</label></div>
                                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_hole_guide" value="1" id="chkHole" @checked(old('show_hole_guide', $p?->show_hole_guide ?? true))><label class="form-check-label" for="chkHole">Show Hole/Slot Guide</label></div>
                                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="background_printing" value="1" id="chkBg" @checked(old('background_printing', $p?->background_printing ?? true))><label class="form-check-label" for="chkBg">Print Background</label></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 5: Live Preview -->
                            <div class="tab-pane fade" id="psTabPreview">
                                <div class="p-3 bg-light border rounded-3 text-center">
                                    <div class="small text-muted mb-2" id="psPreviewCaption">Live Tag Dimension Preview</div>
                                    <div id="psLiveTagPreview" class="d-inline-block bg-white text-center p-2" style="border-radius: 14px; border: 1px dashed #94A3B8; width: 40mm; height: 125mm;">
                                        <div id="psHolePreview" style="width: 11mm; height: 11mm; border-radius: 999px; border: 1px solid #0284C7; margin: 2mm auto;"></div>
                                        <div id="psBrandPreview" class="fw-bold text-primary mt-2">Fresh Go</div>
                                        <div id="psBookingFieldPreview" class="border border-primary rounded-1 text-start px-1 mt-2" style="font-size: 7.5pt;">REF:</div>
                                        <div id="psPkgFieldPreview" class="border border-primary rounded-1 text-start px-1 mt-1" style="font-size: 7.5pt;"># Bag</div>
                                        <div id="psQrBoxPreview" class="bg-dark mx-auto mt-3" style="width: 24mm; height: 24mm; opacity: 0.85;"></div>
                                        <div class="fw-bold font-monospace text-primary mt-2">FG-T-000001</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 px-3">
                        <button type="button" id="psResetBtn" class="btn btn-sm btn-outline-secondary">Reset to Saved</button>
                        <div class="d-flex gap-2">
                            @if($editingProfile)
                                <a href="{{ route('admin.tags.print-preview', ['profile_id' => $editingProfile->id]) }}" class="btn btn-sm btn-outline-primary">Test Print</a>
                            @endif
                            <button type="submit" id="psSaveBtn" class="btn btn-sm btn-warning fw-semibold px-3">Save Settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('printerSettingsForm');
    const unsavedBadge = document.getElementById('psUnsavedBadge');

    function updatePreview() {
        if (!form) return;
        const fd = new FormData(form);
        const tagW = parseFloat(fd.get('tag_width_mm') || 40);
        const tagH = parseFloat(fd.get('tag_height_mm') || 125);
        const qrMm = parseFloat(fd.get('qr_size_mm') || 24);
        const box = document.getElementById('psLiveTagPreview');
        const qr = document.getElementById('psQrBoxPreview');
        if (box) {
            box.style.width = `${tagW}mm`;
            box.style.height = `${tagH}mm`;
            box.style.borderStyle = fd.get('show_cut_guides') ? 'dashed' : 'solid';
        }
        if (qr) {
            qr.style.width = `${qrMm}mm`;
            qr.style.height = `${qrMm}mm`;
        }
        document.getElementById('psHolePreview')?.classList.toggle('d-none', !fd.get('show_hole_guide'));
        document.getElementById('psBrandPreview')?.classList.toggle('d-none', !fd.get('show_brand_name'));
        document.getElementById('psBookingFieldPreview')?.classList.toggle('d-none', !fd.get('show_booking_field'));
        document.getElementById('psPkgFieldPreview')?.classList.toggle('d-none', !fd.get('show_package_field'));
        document.getElementById('psPreviewCaption').textContent = `Tag: ${tagW}×${tagH}mm • QR: ${qrMm}mm • Grid: ${fd.get('tags_per_row')}×${fd.get('tags_per_column')}`;
    }

    form?.addEventListener('input', () => {
        unsavedBadge?.classList.remove('d-none');
        updatePreview();
    });
    form?.addEventListener('change', () => {
        unsavedBadge?.classList.remove('d-none');
        updatePreview();
    });

    document.getElementById('psResetBtn')?.addEventListener('click', () => {
        form.reset();
        unsavedBadge?.classList.add('d-none');
        updatePreview();
    });

    form?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('psSaveBtn');
        const alertBox = document.getElementById('printerSettingsAlert');
        btn.disabled = true;
        btn.textContent = 'Saving...';
        alertBox.innerHTML = '';
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                unsavedBadge?.classList.add('d-none');
                alertBox.innerHTML = `<div class="alert alert-success small py-2">${data.message}</div>`;
                if (window.tmToast) window.tmToast(data.message, 'success');
            } else {
                alertBox.innerHTML = `<div class="alert alert-danger small py-2">${data.message || 'Validation failed.'}</div>`;
            }
        } catch (err) {
            alertBox.innerHTML = `<div class="alert alert-danger small py-2">Network error saving printer profile.</div>`;
        } finally {
            btn.disabled = false;
            btn.textContent = 'Save Settings';
        }
    });

    updatePreview();
})();
</script>
@endpush
