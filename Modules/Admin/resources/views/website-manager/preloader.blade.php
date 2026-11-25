@extends('admin::layouts.app')

@section('title', 'Preloader Settings - Website Manager')

@push('styles')
<style>
    .wm-header-card {
        background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 55%, #FEF3C7 100%);
        border: 1px solid #FDE68A;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(255, 198, 41, 0.14);
    }
    .wm-style-option {
        border: 1.5px solid #E2E8F0;
        border-radius: 14px;
        padding: 0.9rem;
        cursor: pointer;
        transition: border-color 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
        height: 100%;
    }
    .wm-style-option:hover { border-color: #FCD34D; transform: translateY(-2px); }
    .wm-style-option.is-selected { border-color: #F59E0B; background: #FFFDF5; box-shadow: 0 6px 16px rgba(245, 158, 11, 0.15); }
    .wm-preview-stage {
        border-radius: 16px;
        border: 1px solid #E2E8F0;
        min-height: 290px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        position: relative;
        overflow: hidden;
        transition: background-color 0.2s ease, color 0.2s ease;
    }
    .wm-pl-spinner {
        width: 44px; height: 44px; border-radius: 999px;
        border: 3.5px solid rgba(23,23,23,0.12);
        animation: wmPlSpin 0.95s linear infinite;
    }
    .wm-pl-garment {
        width: 54px; height: 54px; border-radius: 16px;
        display: inline-flex; align-items: center; justify-content: center;
        animation: wmPlFloat 0.95s ease-in-out infinite;
    }
    .wm-pl-dots { display: inline-flex; gap: 6px; }
    .wm-pl-dots span { width: 8px; height: 8px; border-radius: 999px; animation: wmPlDot 0.95s ease-in-out infinite; }
    .wm-pl-dots span:nth-child(2) { animation-delay: 0.16s; }
    .wm-pl-dots span:nth-child(3) { animation-delay: 0.32s; }
    .wm-pl-track { width: 200px; height: 6px; border-radius: 999px; background: rgba(23,23,23,0.12); overflow: hidden; position: relative; }
    .wm-pl-bar { position: absolute; top: 0; bottom: 0; left: -45%; width: 45%; border-radius: 999px; animation: wmPlSlide 0.95s ease-in-out infinite; }
    @keyframes wmPlSpin { to { transform: rotate(360deg); } }
    @keyframes wmPlFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
    @keyframes wmPlDot { 0%, 100% { transform: scale(0.75); opacity: 0.45; } 50% { transform: scale(1.2); opacity: 1; } }
    @keyframes wmPlSlide { 0% { left: -45%; } 100% { left: 105%; } }
    #wmFullscreenPreloaderTest {
        position: fixed; inset: 0; z-index: 10800;
        display: none; align-items: center; justify-content: center; padding: 24px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <div class="wm-header-card p-3 p-md-4 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill px-2 py-1 fw-bold" style="background: #FFC629; color: #171717; font-size: 0.72rem;">Website Manager</span>
                    <span class="badge rounded-pill bg-white text-dark border" style="font-size: 0.72rem;">Version #{{ $record->current_version_number }}</span>
                </div>
                <h4 class="fw-bold mb-1">Preloader Settings</h4>
                <div class="small text-muted">Customize the loading experience for your Fresh Go website.</div>
                <div class="small mt-2 d-flex flex-wrap gap-2">
                    <span id="wmPlDraftMeta" class="badge rounded-pill bg-light text-dark border px-3 py-1 fw-normal">
                        <strong>Last Draft:</strong> {{ $record->last_draft_saved_at?->format('d M Y, H:i') ?? 'Initialized' }}
                    </span>
                    <span id="wmPlPubMeta" class="badge rounded-pill {{ $record->published_at ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }} px-3 py-1 fw-normal">
                        <strong>Published:</strong> {{ $record->published_at?->format('d M Y, H:i') ?? 'Not yet published' }}
                    </span>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-warning fw-semibold px-3 shadow-sm" id="wmPlSaveDraftTopBtn">Save Draft</button>
                <button type="button" class="btn btn-sm btn-success fw-semibold px-3 shadow-sm" id="wmPlPublishBtn">Publish</button>
            </div>
        </div>
    </div>

    <div id="wmPlAlertBox" class="alert d-none py-2 small mb-3"></div>

    <form id="wmPreloaderForm">
        <!-- Prominent ON/OFF Card -->
        <div class="card border-0 shadow-sm mb-3 rounded-4">
            <div class="card-body p-3 p-md-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h6 class="fw-bold mb-1">Enable Website Preloader</h6>
                    <div class="small text-muted">When ON, eligible customer-facing pages display the configured preloader until initial content is ready.</div>
                </div>
                <div class="form-check form-switch form-switch-lg m-0">
                    <input class="form-check-input wm-pl-live" type="checkbox" role="switch" id="plEnabled" name="enabled" value="1" @checked(!empty($draft['enabled'])) style="width: 3.1em; height: 1.55em; cursor: pointer;">
                    <label class="form-check-label fw-bold ms-2" for="plEnabled" id="plEnabledLabel">{{ !empty($draft['enabled']) ? 'ON' : 'OFF' }}</label>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start">
            <!-- LEFT: Configuration -->
            <div class="col-12 col-lg-7">
                <!-- Style Selection -->
                <div class="card border-0 shadow-sm mb-3 rounded-4">
                    <div class="card-body p-3 p-md-4 small">
                        <h6 class="fw-bold mb-3">1. Preloader Style</h6>
                        <div class="row g-2">
                            @foreach([
                                'brand_spinner' => ['title' => 'Fresh Go Brand Spinner', 'desc' => 'Circular spinner with logo & loading text'],
                                'laundry_animation' => ['title' => 'Laundry Animation', 'desc' => 'Floating garment icon with subtle loading dots'],
                                'progress_indicator' => ['title' => 'Progress Indicator', 'desc' => 'Logo with smooth indeterminate progress bar'],
                            ] as $sKey => $sInfo)
                                <div class="col-12 col-md-4">
                                    <label class="wm-style-option d-block {{ ($draft['style'] ?? 'brand_spinner') === $sKey ? 'is-selected' : '' }}">
                                        <input type="radio" name="style" value="{{ $sKey }}" class="d-none wm-pl-live wm-style-radio" @checked(($draft['style'] ?? 'brand_spinner') === $sKey)>
                                        <div class="fw-bold mb-1">{{ $sInfo['title'] }}</div>
                                        <div class="text-muted" style="font-size: 0.78rem;">{{ $sInfo['desc'] }}</div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Branding & Colors -->
                <div class="card border-0 shadow-sm mb-3 rounded-4">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <h6 class="fw-bold mb-0">2. Branding &amp; Colors</h6>
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-1" id="wmPlResetBrandColors">Reset Fresh Go Palette</button>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="plLoadingText">Loading Text</label>
                                <input type="text" name="loading_text" id="plLoadingText" class="form-control form-control-sm wm-pl-live" maxlength="120" value="{{ $draft['loading_text'] ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input wm-pl-live" type="checkbox" id="plShowLogo" name="show_logo" value="1" @checked(!empty($draft['show_logo']))>
                                    <label class="form-check-label fw-semibold" for="plShowLogo">Show Logo / Brand Badge</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input wm-pl-live" type="checkbox" id="plShowText" name="show_text" value="1" @checked(!empty($draft['show_text']))>
                                    <label class="form-check-label fw-semibold" for="plShowText">Show Loading Text</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Background Color</label>
                                <div class="input-group input-group-sm">
                                    <input type="color" class="form-control form-control-color wm-pl-color-picker" data-target="#plBgHex" value="{{ $draft['background_color'] ?? '#FFF8E5' }}">
                                    <input type="text" name="background_color" id="plBgHex" class="form-control wm-pl-live wm-pl-hex" maxlength="7" value="{{ $draft['background_color'] ?? '#FFF8E5' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Accent / Spinner Color</label>
                                <div class="input-group input-group-sm">
                                    <input type="color" class="form-control form-control-color wm-pl-color-picker" data-target="#plAccentHex" value="{{ $draft['accent_color'] ?? '#FFC629' }}">
                                    <input type="text" name="accent_color" id="plAccentHex" class="form-control wm-pl-live wm-pl-hex" maxlength="7" value="{{ $draft['accent_color'] ?? '#FFC629' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Text Color</label>
                                <div class="input-group input-group-sm">
                                    <input type="color" class="form-control form-control-color wm-pl-color-picker" data-target="#plTextHex" value="{{ $draft['text_color'] ?? '#171717' }}">
                                    <input type="text" name="text_color" id="plTextHex" class="form-control wm-pl-live wm-pl-hex" maxlength="7" value="{{ $draft['text_color'] ?? '#171717' }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logo Settings -->
                <div class="card border-0 shadow-sm mb-3 rounded-4">
                    <div class="card-body p-3 p-md-4 small">
                        <h6 class="fw-bold mb-3">3. Logo Settings</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Logo Source</label>
                                <select name="logo_source" id="plLogoSource" class="form-select form-select-sm wm-pl-live">
                                    <option value="site_logo" @selected(($draft['logo_source'] ?? 'site_logo') === 'site_logo')>Use Current Website Logo</option>
                                    <option value="custom_media" @selected(($draft['logo_source'] ?? '') === 'custom_media')>Select / Upload Custom Logo</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Logo Size Preset</label>
                                <select name="logo_size" id="plLogoSize" class="form-select form-select-sm wm-pl-live">
                                    <option value="sm" @selected(($draft['logo_size'] ?? 'md') === 'sm')>Small (36px)</option>
                                    <option value="md" @selected(($draft['logo_size'] ?? 'md') === 'md')>Medium (52px)</option>
                                    <option value="lg" @selected(($draft['logo_size'] ?? 'md') === 'lg')>Large (68px)</option>
                                </select>
                            </div>
                            <div class="col-12 {{ ($draft['logo_source'] ?? 'site_logo') === 'custom_media' ? '' : 'd-none' }}" id="plCustomLogoBox">
                                <label class="form-label fw-semibold">Custom Preloader Logo URL / Media</label>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" name="custom_logo_url" id="plCustomLogoUrl" class="form-control wm-pl-live" value="{{ $draft['custom_logo_url'] ?? '' }}" placeholder="/storage/homepage-media/...">
                                    <label class="btn btn-outline-primary mb-0" for="plLogoUploadFile">Upload Logo</label>
                                    <input type="file" id="plLogoUploadFile" class="d-none" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                                </div>
                                @if($mediaItems->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        @foreach($mediaItems->take(8) as $m)
                                            <button type="button" class="btn btn-sm btn-light border p-1 wm-pl-pick-media" data-url="{{ $m->url }}" title="{{ $m->original_name }}">
                                                <img src="{{ $m->url }}" alt="" style="height: 28px; width: 28px; object-fit: contain;">
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Animation & Behavior -->
                <div class="card border-0 shadow-sm mb-3 rounded-4">
                    <div class="card-body p-3 p-md-4 small">
                        <h6 class="fw-bold mb-3">4. Animation &amp; Page Applicability</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Speed</label>
                                <select name="animation_speed" id="plAnimSpeed" class="form-select form-select-sm wm-pl-live">
                                    <option value="slow" @selected(($draft['animation_speed'] ?? 'normal') === 'slow')>Slow</option>
                                    <option value="normal" @selected(($draft['animation_speed'] ?? 'normal') === 'normal')>Normal</option>
                                    <option value="fast" @selected(($draft['animation_speed'] ?? 'normal') === 'fast')>Fast</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fade-Out (ms)</label>
                                <input type="number" name="fade_duration_ms" class="form-control form-control-sm" min="100" max="800" value="{{ $draft['fade_duration_ms'] ?? 250 }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Min Display (ms)</label>
                                <input type="number" name="minimum_duration_ms" class="form-control form-control-sm" min="0" max="1500" value="{{ $draft['minimum_duration_ms'] ?? 0 }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Safety Timeout (ms)</label>
                                <input type="number" name="maximum_timeout_ms" class="form-control form-control-sm" min="1500" max="8000" value="{{ $draft['maximum_timeout_ms'] ?? 4000 }}">
                            </div>
                        </div>
                        <div class="row g-2 pt-2 border-top">
                            <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="plPublic" name="enable_public" value="1" @checked(!empty($draft['enable_public']))><label class="form-check-label" for="plPublic">Public Website (Home, Services, About, Contact)</label></div></div>
                            <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="plBooking" name="enable_booking" value="1" @checked(!empty($draft['enable_booking']))><label class="form-check-label" for="plBooking">Customer Booking Pages</label></div></div>
                            <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="plOrders" name="enable_orders" value="1" @checked(!empty($draft['enable_orders']))><label class="form-check-label" for="plOrders">Customer My Orders &amp; Account</label></div></div>
                            <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="plAuth" name="enable_auth" value="1" @checked(!empty($draft['enable_auth']))><label class="form-check-label" for="plAuth">Customer Auth / Login Pages</label></div></div>
                            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="plReduced" name="respect_reduced_motion" value="1" @checked(!empty($draft['respect_reduced_motion']))><label class="form-check-label fw-semibold" for="plReduced">Respect User Reduced Motion Preference (prefers-reduced-motion)</label></div></div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-4" id="wmPlSaveDraftBtn">Save Draft</button>
                    </div>
                </div>

                @if($versions->isNotEmpty())
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 pt-3 pb-1 px-4"><h6 class="fw-bold mb-0">Version History</h6></div>
                    <div class="card-body p-3 small">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Version</th><th>Action</th><th>By</th><th>Date</th><th class="text-end">Restore</th></tr></thead>
                                <tbody>
                                    @foreach($versions->take(6) as $ver)
                                    <tr>
                                        <td>#{{ $ver->version_number }}</td>
                                        <td><span class="badge {{ $ver->action === 'published' ? 'bg-success-subtle text-success' : 'bg-light text-dark border' }}">{{ ucfirst($ver->action) }}</span></td>
                                        <td>{{ $ver->createdByAdmin?->name ?? 'System' }}</td>
                                        <td>{{ $ver->created_at?->format('d M, H:i') }}</td>
                                        <td class="text-end"><button type="button" class="btn btn-xs btn-outline-dark wm-restore-ver-btn" data-url="{{ route('admin.website-manager.preloader.versions.restore', $ver) }}">Restore</button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- RIGHT: Sticky Live Preview -->
            <div class="col-12 col-lg-5" style="position: sticky; top: 82px;">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 pt-3 pb-2 px-4 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0">Live Preview</h6>
                        <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3" id="wmPlTestFullscreenBtn">Test Fullscreen Preloader</button>
                    </div>
                    <div class="card-body p-3">
                        <div class="wm-preview-stage" id="wmPlPreviewStage"></div>
                        <div class="small text-muted text-center mt-2" id="wmPlPreviewStatusNote">Preview updates immediately as you edit settings.</div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Fullscreen Test Overlay (auto-closes & has Close button) -->
<div id="wmFullscreenPreloaderTest" role="dialog" aria-modal="true" aria-label="Fullscreen Preloader Preview">
    <button type="button" class="btn btn-dark btn-sm rounded-pill px-3 position-absolute top-0 end-0 m-3 shadow" id="wmCloseFullscreenBtn">Close Preview &times;</button>
    <div id="wmFullscreenStageInner"></div>
</div>
@endsection

@push('scripts')
<script>
jQuery(function($) {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const siteLogoUrl = @json($siteLogoUrl);
    let fullscreenTimer = null;

    function showAlert(msg, type = 'success') {
        $('#wmPlAlertBox').removeClass('d-none alert-success alert-danger').addClass('alert-' + type).text(msg);
    }

    function getSpeedSec() {
        const s = $('#plAnimSpeed').val();
        return s === 'slow' ? '1.45s' : (s === 'fast' ? '0.6s' : '0.95s');
    }

    function getLogoHeight() {
        const sz = $('#plLogoSize').val();
        return sz === 'sm' ? '36px' : (sz === 'lg' ? '68px' : '52px');
    }

    function buildPreviewHtml() {
        const showLogo = $('#plShowLogo').is(':checked');
        const showText = $('#plShowText').is(':checked');
        const text = $('#plLoadingText').val() || '';
        const style = $('input[name="style"]:checked').val() || 'brand_spinner';
        const accent = $('#plAccentHex').val() || '#FFC629';
        const textCol = $('#plTextHex').val() || '#171717';
        const speed = getSpeedSec();
        const logoSrc = ($('#plLogoSource').val() === 'custom_media' && $('#plCustomLogoUrl').val().trim()) ? $('#plCustomLogoUrl').val().trim() : siteLogoUrl;

        let html = '<div class="d-flex flex-column align-items-center text-center gap-3">';
        if (showLogo) {
            if (logoSrc) {
                html += `<img src="${$('<div>').text(logoSrc).html()}" alt="Fresh Go" style="max-height:${getLogoHeight()}; max-width:200px; object-fit:contain;">`;
            } else {
                html += `<span class="fw-bolder rounded-pill px-3 py-2" style="background:${accent}; color:#171717; letter-spacing:0.08em;">FRESH GO</span>`;
            }
        }
        if (style === 'laundry_animation') {
            html += `<div class="wm-pl-garment" style="background:${accent}; color:#171717; animation-duration:${speed};"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/></svg></div>`;
            html += `<div class="wm-pl-dots"><span style="background:${accent}; animation-duration:${speed};"></span><span style="background:${accent}; animation-duration:${speed};"></span><span style="background:${accent}; animation-duration:${speed};"></span></div>`;
        } else if (style === 'progress_indicator') {
            html += `<div class="wm-pl-track"><div class="wm-pl-bar" style="background:${accent}; animation-duration:${speed};"></div></div>`;
        } else {
            html += `<div class="wm-pl-spinner" style="border-top-color:${accent}; animation-duration:${speed};"></div>`;
        }
        if (showText && text) {
            html += `<p class="fw-semibold mb-0 small" style="color:${textCol};">${$('<div>').text(text).html()}</p>`;
        }
        html += '</div>';
        return html;
    }

    function renderPreview() {
        const bg = $('#plBgHex').val() || '#FFF8E5';
        const textCol = $('#plTextHex').val() || '#171717';
        const enabled = $('#plEnabled').is(':checked');
        $('#plEnabledLabel').text(enabled ? 'ON' : 'OFF');
        $('#wmPlPreviewStage').css({ backgroundColor: bg, color: textCol }).html(buildPreviewHtml());
        $('#wmPlPreviewStatusNote').text(enabled ? 'Preloader is currently ON. Preview updates live.' : 'Preloader is currently OFF (preview shown for editing only).');
    }

    $('.wm-style-radio').on('change', function() {
        $('.wm-style-option').removeClass('is-selected');
        $(this).closest('.wm-style-option').addClass('is-selected');
    });

    $('.wm-pl-color-picker').on('input', function() {
        $($(this).data('target')).val($(this).val().toUpperCase());
        renderPreview();
    });
    $('.wm-pl-hex').on('input', function() {
        const val = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            $(this).closest('.input-group').find('.wm-pl-color-picker').val(val);
        }
        renderPreview();
    });

    $('#plLogoSource').on('change', function() {
        $('#plCustomLogoBox').toggleClass('d-none', $(this).val() !== 'custom_media');
        renderPreview();
    });
    $(document).on('click', '.wm-pl-pick-media', function() {
        $('#plLogoSource').val('custom_media').trigger('change');
        $('#plCustomLogoUrl').val($(this).data('url'));
        renderPreview();
    });

    $('#wmPlResetBrandColors').on('click', function() {
        $('#plBgHex').val('#FFF8E5').trigger('input');
        $('#plAccentHex').val('#FFC629').trigger('input');
        $('#plTextHex').val('#171717').trigger('input');
    });

    $(document).on('input change', '.wm-pl-live', renderPreview);

    $('#plLogoUploadFile').on('change', function() {
        const file = this.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('file', file);
        fd.append('label', 'Preloader Logo');
        $.ajax({
            url: @json(route('admin.website-manager.homepage.media.upload')),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: fd, processData: false, contentType: false,
            success: (res) => {
                if (res.media?.url) {
                    $('#plCustomLogoUrl').val(res.media.url);
                    renderPreview();
                    showAlert('Logo uploaded and selected.', 'success');
                }
            },
            error: (xhr) => showAlert(xhr.responseJSON?.message || 'Logo upload failed.', 'danger')
        });
    });

    function saveDraft(afterSuccess) {
        const $btns = $('#wmPlSaveDraftBtn, #wmPlSaveDraftTopBtn').prop('disabled', true);
        $.ajax({
            url: @json(route('admin.website-manager.preloader.save')),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: $('#wmPreloaderForm').serialize(),
            success: (res) => {
                showAlert(res.message, 'success');
                if (res.draft_saved_at) $('#wmPlDraftMeta').html('<strong>Last Draft:</strong> ' + res.draft_saved_at);
                if (typeof afterSuccess === 'function') afterSuccess();
            },
            error: (xhr) => showAlert(xhr.responseJSON?.message || 'Failed to save draft.', 'danger'),
            complete: () => $btns.prop('disabled', false)
        });
    }

    $('#wmPreloaderForm').on('submit', function(e) { e.preventDefault(); saveDraft(); });
    $('#wmPlSaveDraftTopBtn').on('click', function() { saveDraft(); });
    $('#wmPlPublishBtn').on('click', function() {
        const $btn = $(this).prop('disabled', true).text('Publishing...');
        saveDraft(() => {
            $.ajax({
                url: @json(route('admin.website-manager.preloader.publish')),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: (res) => {
                    showAlert(res.message, 'success');
                    if (res.published_at) $('#wmPlPubMeta').html('<strong>Published:</strong> ' + res.published_at);
                },
                error: (xhr) => showAlert(xhr.responseJSON?.message || 'Publish failed.', 'danger'),
                complete: () => $btn.prop('disabled', false).text('Publish')
            });
        });
    });

    function closeFullscreenTest() {
        clearTimeout(fullscreenTimer);
        $('#wmFullscreenPreloaderTest').fadeOut(180);
    }
    $('#wmPlTestFullscreenBtn').on('click', function() {
        const bg = $('#plBgHex').val() || '#FFF8E5';
        const textCol = $('#plTextHex').val() || '#171717';
        $('#wmFullscreenStageInner').html(buildPreviewHtml());
        $('#wmFullscreenPreloaderTest').css({ backgroundColor: bg, color: textCol, display: 'flex' }).hide().fadeIn(180);
        clearTimeout(fullscreenTimer);
        fullscreenTimer = setTimeout(closeFullscreenTest, 2600);
    });
    $('#wmCloseFullscreenBtn').on('click', closeFullscreenTest);

    $(document).on('click', '.wm-restore-ver-btn', function() {
        $.ajax({
            url: $(this).data('url'), method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: (res) => { showAlert(res.message, 'success'); setTimeout(() => window.location.reload(), 800); }
        });
    });

    renderPreview();
});
</script>
@endpush
