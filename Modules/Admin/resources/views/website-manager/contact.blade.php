@extends('admin::layouts.app')

@section('title', 'Contact Page Contents - Website Manager')

@push('styles')
<style>
    .wm-header-card { background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 55%, #FEF3C7 100%); border: 1px solid #FDE68A; border-radius: 18px; box-shadow: 0 8px 24px rgba(255, 198, 41, 0.14); }
    .wm-kpi-card { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 0.9rem 1rem; position: relative; overflow: hidden; height: 100%; }
    .wm-kpi-bar { position: absolute; top: 0; left: 0; right: 0; height: 4px; }
    .wm-kpi-icon { width: 38px; height: 38px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .wm-nav-pills { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px; padding: 0.45rem; }
    .wm-nav-pills .nav-link { color: #475569; font-weight: 600; font-size: 0.83rem; border-radius: 10px; padding: 0.48rem 0.88rem; display: inline-flex; align-items: center; gap: 0.38rem; }
    .wm-nav-pills .nav-link.active { background: linear-gradient(135deg, #FFC629 0%, #F59E0B 100%); color: #171717; }
</style>
@endpush

@section('content')
@php
    $visibleSectionsCount = collect($draft['sections'] ?? [])->where('visible', true)->count();
    $totalSectionsCount = count($draft['sections'] ?? []);
    $openDaysCount = collect($draft['business_hours'] ?? [])->where('is_open', true)->count();
@endphp
<div class="container-fluid py-3">
    <div class="wm-header-card p-3 p-md-4 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill px-2 py-1 fw-bold" style="background: #FFC629; color: #171717; font-size: 0.72rem;">Website Manager</span>
                    <span class="badge rounded-pill bg-white text-dark border" style="font-size: 0.72rem;">Version #{{ $record->current_version_number }}</span>
                </div>
                <h4 class="fw-bold mb-1">Contact Page Contents</h4>
                <div class="small text-muted">Manage the public Contact Us page (/contact) details, business hours, enquiry form, map &amp; social links.</div>
                <div class="small mt-2 d-flex flex-wrap gap-2">
                    <span id="wmCntDraftMeta" class="badge rounded-pill bg-light text-dark border px-3 py-1 fw-normal">
                        <strong>Last Draft:</strong> {{ $record->last_draft_saved_at?->format('d M Y, H:i') ?? 'Initialized' }}
                    </span>
                    <span id="wmCntPubMeta" class="badge rounded-pill {{ $record->published_at ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }} px-3 py-1 fw-normal">
                        <strong>Published:</strong> {{ $record->published_at?->format('d M Y, H:i') ?? 'Not yet published' }}
                    </span>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ url('/contact?preview=draft') }}" target="_blank" rel="noopener" class="btn btn-sm btn-light border bg-white px-3 fw-semibold shadow-sm">Preview Page</a>
                <button type="button" class="btn btn-sm btn-success px-3 fw-semibold shadow-sm" id="wmCntPublishBtn">Publish</button>
            </div>
        </div>
    </div>

    <!-- 4 KPI Cards in Single Line -->
    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #8B5CF6;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div><div class="small text-muted fw-semibold">Visible Sections</div><div class="fs-4 fw-bold" style="color: #6D28D9;">{{ $visibleSectionsCount }} / {{ $totalSectionsCount }}</div></div>
                    <span class="wm-kpi-icon" style="background: #F5F3FF; color: #7C3AED;"><i data-feather="layers" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #3B82F6;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div><div class="small text-muted fw-semibold">Support Email</div><div class="small fw-bold text-primary text-truncate" style="max-width:140px;">{{ $draft['contact_info']['support_email'] ?: 'Not set' }}</div></div>
                    <span class="wm-kpi-icon" style="background: #EFF6FF; color: #2563EB;"><i data-feather="mail" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #16A34A;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div><div class="small text-muted fw-semibold">Open Days</div><div class="fs-4 fw-bold text-success">{{ $openDaysCount }} / 7</div></div>
                    <span class="wm-kpi-icon" style="background: #ECFDF5; color: #16A34A;"><i data-feather="clock" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #F59E0B;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div><div class="small text-muted fw-semibold">Saved Versions</div><div class="fs-4 fw-bold" style="color: #D97706;">{{ $versions->count() }}</div></div>
                    <span class="wm-kpi-icon" style="background: #FFFBEB; color: #D97706;"><i data-feather="archive" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div id="wmCntAlertBox" class="alert d-none py-2 small mb-3"></div>

    <ul class="nav nav-pills wm-nav-pills gap-1 mb-3 flex-wrap" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#cntTabSections" type="button">Sections</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#cntTabHero" type="button">Hero</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#cntTabInfo" type="button">Contact Info</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#cntTabForm" type="button">Enquiry Form</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#cntTabHours" type="button">Business Hours</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#cntTabMapSocial" type="button">Map &amp; Social</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#cntTabSeo" type="button">SEO &amp; History</button></li>
    </ul>

    <div class="tab-content">
        <!-- Sections -->
        <div class="tab-pane fade show active" id="cntTabSections">
            <div class="card border-0 shadow-sm">
                <form id="wmCntSectionsForm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small" style="min-width:650px;">
                                <thead class="table-light"><tr><th style="width:90px;">Order</th><th>Section</th><th>Description</th><th>Visibility</th><th class="text-end">Move</th></tr></thead>
                                <tbody id="wmCntSectionsBody">
                                    @foreach(collect($draft['sections'])->sortBy('order')->values() as $sec)
                                        <tr>
                                            <td>
                                                <input type="number" class="form-control form-control-sm wm-sec-order" value="{{ $sec['order'] }}" min="1" max="20" style="width:72px;">
                                                <input type="hidden" class="wm-sec-key" value="{{ $sec['key'] }}">
                                                <input type="hidden" class="wm-sec-label" value="{{ $sec['label'] }}">
                                                <input type="hidden" class="wm-sec-sub" value="{{ $sec['subtitle'] ?? '' }}">
                                            </td>
                                            <td class="fw-bold">{{ $sec['label'] }}</td>
                                            <td class="text-muted">{{ $sec['subtitle'] ?? '' }}</td>
                                            <td><div class="form-check form-switch"><input class="form-check-input wm-sec-visible" type="checkbox" {{ !empty($sec['visible']) ? 'checked' : '' }}><label class="form-check-label">{{ !empty($sec['visible']) ? 'Visible' : 'Hidden' }}</label></div></td>
                                            <td class="text-end"><button type="button" class="btn btn-sm btn-light border wm-move-up">&uarr;</button> <button type="button" class="btn btn-sm btn-light border wm-move-down">&darr;</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span class="small text-muted">Layout: <strong>Contact Info + Map</strong> render in one row, and <strong>Send Us a Message + Business Hours</strong> render in the next row.</span>
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Sections Draft</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Hero -->
        <div class="tab-pane fade" id="cntTabHero">
            <div class="card border-0 shadow-sm">
                <form id="wmCntHeroForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">Badge</label><input type="text" name="badge" class="form-control form-control-sm" value="{{ $draft['hero']['badge'] ?? '' }}"></div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Background Preset</label>
                                <select name="bg_preset" class="form-select form-select-sm">
                                    <option value="gold_soft" {{ ($draft['hero']['bg_preset'] ?? 'gold_soft') === 'gold_soft' ? 'selected' : '' }}>Soft Yellow Gradient</option>
                                    <option value="cream" {{ ($draft['hero']['bg_preset'] ?? '') === 'cream' ? 'selected' : '' }}>Soft Cream</option>
                                    <option value="white" {{ ($draft['hero']['bg_preset'] ?? '') === 'white' ? 'selected' : '' }}>White</option>
                                </select>
                            </div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Headline <span class="text-danger">*</span></label><input type="text" name="headline" class="form-control form-control-sm" value="{{ $draft['hero']['headline'] ?? '' }}" required></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Description</label><textarea name="description" class="form-control form-control-sm" rows="2">{{ $draft['hero']['description'] ?? '' }}</textarea></div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Hero Draft</button></div>
                </form>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="tab-pane fade" id="cntTabInfo">
            <div class="card border-0 shadow-sm">
                <form id="wmCntInfoForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label fw-semibold">Brand Name <span class="text-danger">*</span></label><input type="text" name="brand_name" class="form-control form-control-sm" value="{{ $draft['contact_info']['brand_name'] ?? '' }}" required></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Public Support Email</label><input type="email" name="support_email" class="form-control form-control-sm" value="{{ $draft['contact_info']['support_email'] ?? '' }}" placeholder="support@example.com"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">WhatsApp Number</label><input type="text" name="whatsapp_number" class="form-control form-control-sm" value="{{ $draft['contact_info']['whatsapp_number'] ?? '' }}"></div>
                            <div class="col-md-2"><label class="form-label fw-semibold">Dial Code</label><input type="text" name="phone_country_code" class="form-control form-control-sm" value="{{ $draft['contact_info']['phone_country_code'] ?? '' }}" placeholder="+44"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Public Phone Number</label><input type="text" name="phone_number" class="form-control form-control-sm" value="{{ $draft['contact_info']['phone_number'] ?? '' }}"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Office Address</label><input type="text" name="address" class="form-control form-control-sm" value="{{ $draft['contact_info']['address'] ?? '' }}"></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">City / Region</label><input type="text" name="city" class="form-control form-control-sm" value="{{ $draft['contact_info']['city'] ?? '' }}"></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">Country</label><input type="text" name="country" class="form-control form-control-sm" value="{{ $draft['contact_info']['country'] ?? '' }}"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Support Availability Text</label><input type="text" name="availability_text" class="form-control form-control-sm" value="{{ $draft['contact_info']['availability_text'] ?? '' }}"></div>
                            <div class="col-md-12"><label class="form-label fw-semibold">Contact Section Description</label><textarea name="description" class="form-control form-control-sm" rows="2">{{ $draft['contact_info']['description'] ?? '' }}</textarea></div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Contact Info Draft</button></div>
                </form>
            </div>
        </div>

        <!-- Enquiry Form Config -->
        <div class="tab-pane fade" id="cntTabForm">
            <div class="card border-0 shadow-sm">
                <form id="wmCntFormCfg">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">Form Heading <span class="text-danger">*</span></label><input type="text" name="heading" class="form-control form-control-sm" value="{{ $draft['form_config']['heading'] ?? '' }}" required></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Submit Button Text <span class="text-danger">*</span></label><input type="text" name="submit_label" class="form-control form-control-sm" value="{{ $draft['form_config']['submit_label'] ?? '' }}" required></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Subtitle</label><input type="text" name="subtitle" class="form-control form-control-sm" value="{{ $draft['form_config']['subtitle'] ?? '' }}"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Success Confirmation Message <span class="text-danger">*</span></label><input type="text" name="success_message" class="form-control form-control-sm" value="{{ $draft['form_config']['success_message'] ?? '' }}" required></div>
                            <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_phone" value="1" {{ !empty($draft['form_config']['show_phone']) ? 'checked' : '' }}><label class="form-check-label">Show Optional Phone Field</label></div></div>
                            <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_booking_reference" value="1" {{ !empty($draft['form_config']['show_booking_reference']) ? 'checked' : '' }}><label class="form-check-label">Show Optional Booking Reference</label></div></div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Form Settings Draft</button></div>
                </form>
            </div>
        </div>

        <!-- Business Hours -->
        <div class="tab-pane fade" id="cntTabHours">
            <div class="card border-0 shadow-sm">
                <form id="wmCntHoursForm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0 small" style="min-width:650px;">
                                <thead class="table-light"><tr><th>Day</th><th>Open</th><th>Opening Time</th><th>Closing Time</th><th>Special Note</th></tr></thead>
                                <tbody>
                                    @foreach(($draft['business_hours'] ?? []) as $idx => $row)
                                        <tr>
                                            <td class="fw-bold">{{ $row['day'] }}<input type="hidden" name="hours[{{ $idx }}][day]" value="{{ $row['day'] }}"></td>
                                            <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="hours[{{ $idx }}][is_open]" value="1" {{ !empty($row['is_open']) ? 'checked' : '' }}></div></td>
                                            <td><input type="text" name="hours[{{ $idx }}][open_time]" class="form-control form-control-sm" value="{{ $row['open_time'] ?? '' }}" placeholder="09:00"></td>
                                            <td><input type="text" name="hours[{{ $idx }}][close_time]" class="form-control form-control-sm" value="{{ $row['close_time'] ?? '' }}" placeholder="18:00"></td>
                                            <td><input type="text" name="hours[{{ $idx }}][note]" class="form-control form-control-sm" value="{{ $row['note'] ?? '' }}"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Business Hours Draft</button></div>
                </form>
            </div>
        </div>

        <!-- Map & Social -->
        <div class="tab-pane fade" id="cntTabMapSocial">
            <div class="card border-0 shadow-sm">
                <form id="wmCntMapSocialForm">
                    <div class="card-body p-3 p-md-4 small">
                        <h6 class="fw-bold mb-2">Location &amp; Map</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4"><label class="form-label fw-semibold">Location Title</label><input type="text" name="map[title]" class="form-control form-control-sm" value="{{ $draft['map']['title'] ?? '' }}"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Public Address</label><input type="text" name="map[public_address]" class="form-control form-control-sm" value="{{ $draft['map']['public_address'] ?? '' }}" placeholder="e.g. 123 High Street, London"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Map Share URL (https://)</label><input type="text" name="map[map_url]" class="form-control form-control-sm" value="{{ $draft['map']['map_url'] ?? '' }}" placeholder="https://maps.app.goo.gl/..."></div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Google Maps Embed URL or &lt;iframe&gt; (Optional)</label>
                                <input type="text" name="map[embed_url]" id="wmCntMapEmbedInput" class="form-control form-control-sm" value="{{ $draft['map']['embed_url'] ?? '' }}" placeholder="https://www.google.com/maps/embed?pb=... or paste full &lt;iframe&gt; code">
                                <div class="form-text">To show an interactive map on the Contact page, paste a Google Maps Embed URL (from Share → Embed a map), a Google Maps share link above, or enter a Public Address. Short links like maps.app.goo.gl auto-resolve on save.</div>
                            </div>
                            <div class="col-12 {{ empty($draft['map']['embed_url']) ? 'd-none' : '' }}" id="wmCntMapPreviewWrap">
                                <label class="form-label fw-semibold">Live Map Preview</label>
                                <div class="rounded-3 overflow-hidden border" style="height: 240px; background: #F8FAFC;">
                                    <iframe id="wmCntMapPreviewFrame" src="{{ $draft['map']['embed_url'] ?? '' }}" width="100%" height="100%" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                                </div>
                            </div>
                        </div>
                        <h6 class="fw-bold mb-2">Social Media Links (https://)</h6>
                        <div class="row g-3">
                            @foreach(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'whatsapp' => 'WhatsApp'] as $sKey => $sLbl)
                                <div class="col-md-4"><label class="form-label fw-semibold">{{ $sLbl }}</label><input type="url" name="social[{{ $sKey }}]" class="form-control form-control-sm" value="{{ $draft['social'][$sKey] ?? '' }}"></div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Map &amp; Social Draft</button></div>
                </form>
            </div>
        </div>

        <!-- SEO & History -->
        <div class="tab-pane fade" id="cntTabSeo">
            <div class="card border-0 shadow-sm mb-3">
                <form id="wmCntSeoForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">Meta Title</label><input type="text" name="meta_title" class="form-control form-control-sm" value="{{ $draft['seo']['meta_title'] ?? '' }}"></div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Robots</label>
                                <select name="robots" class="form-select form-select-sm">
                                    @foreach(['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'] as $rob)
                                        <option value="{{ $rob }}" {{ ($draft['seo']['robots'] ?? 'index, follow') === $rob ? 'selected' : '' }}>{{ $rob }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12"><label class="form-label fw-semibold">Meta Description</label><textarea name="meta_description" class="form-control form-control-sm" rows="2">{{ $draft['seo']['meta_description'] ?? '' }}</textarea></div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save SEO Draft</button></div>
                </form>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3"><h6 class="fw-bold mb-0">Version History</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 small">
                        <thead class="table-light"><tr><th>Version</th><th>Action</th><th>Section</th><th>Date</th><th class="text-end">Restore</th></tr></thead>
                        <tbody>
                            @forelse($versions as $ver)
                                <tr>
                                    <td class="fw-bold">#{{ $ver->version_number }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $ver->action)) }}</span></td>
                                    <td>{{ $ver->section_key ?: 'All' }}</td>
                                    <td>{{ $ver->created_at?->format('d M Y, H:i') }}</td>
                                    <td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 wm-restore-ver-btn" data-url="{{ route('admin.website-manager.contact.versions.restore', $ver) }}">Restore</button></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No history yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
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
    function showAlert(msg, type = 'success') { $('#wmCntAlertBox').removeClass('d-none alert-success alert-danger').addClass(type === 'success' ? 'alert-success' : 'alert-danger').text(msg); window.scrollTo({ top: 0, behavior: 'smooth' }); }
    function extractError(xhr, fallback) { if (xhr.responseJSON?.errors) { const k = Object.keys(xhr.responseJSON.errors)[0]; if (k && xhr.responseJSON.errors[k][0]) return xhr.responseJSON.errors[k][0]; } return xhr.responseJSON?.message || fallback; }
    function postDraft(url, data, btn, label) {
        btn.prop('disabled', true).text('Saving...');
        $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, data,
            success: (res) => { showAlert(res.message, 'success'); if (res.draft_saved_at) $('#wmCntDraftMeta').html('<strong>Last Draft:</strong> ' + res.draft_saved_at); },
            error: (xhr) => showAlert(extractError(xhr, 'Save failed.'), 'danger'),
            complete: () => btn.prop('disabled', false).text(label)
        });
    }
    $(document).on('click', '.wm-move-up', function() { const r = $(this).closest('tr'), p = r.prev('tr'); if (p.length) { r.insertBefore(p); $('#wmCntSectionsBody tr').each((i, el) => $(el).find('.wm-sec-order').val(i + 1)); } });
    $(document).on('click', '.wm-move-down', function() { const r = $(this).closest('tr'), n = r.next('tr'); if (n.length) { r.insertAfter(n); $('#wmCntSectionsBody tr').each((i, el) => $(el).find('.wm-sec-order').val(i + 1)); } });
    $('#wmCntSectionsForm').on('submit', function(e) {
        e.preventDefault(); const sections = [];
        $('#wmCntSectionsBody tr').each(function() { sections.push({ key: $(this).find('.wm-sec-key').val(), label: $(this).find('.wm-sec-label').val(), subtitle: $(this).find('.wm-sec-sub').val(), order: parseInt($(this).find('.wm-sec-order').val() || '1', 10), visible: $(this).find('.wm-sec-visible').is(':checked') ? 1 : 0 }); });
        postDraft(@json(route('admin.website-manager.contact.sections.save')), { sections }, $(this).find('button[type="submit"]'), 'Save Sections Draft');
    });
    $('#wmCntHeroForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.contact.hero.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Hero Draft'); });
    $('#wmCntInfoForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.contact.info.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Contact Info Draft'); });
    $('#wmCntFormCfg').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.contact.form.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Form Settings Draft'); });
    $('#wmCntHoursForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.contact.hours.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Business Hours Draft'); });
    $('#wmCntMapSocialForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]').prop('disabled', true).text('Saving...');
        $.ajax({
            url: @json(route('admin.website-manager.contact.map-social.save')),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: $(this).serialize(),
            success: (res) => {
                showAlert(res.message, 'success');
                if (res.draft_saved_at) $('#wmCntDraftMeta').html('<strong>Last Draft:</strong> ' + res.draft_saved_at);
                if (typeof res.embed_url === 'string') {
                    $('#wmCntMapEmbedInput').val(res.embed_url);
                    if (res.embed_url) {
                        $('#wmCntMapPreviewFrame').attr('src', res.embed_url);
                        $('#wmCntMapPreviewWrap').removeClass('d-none');
                    } else {
                        $('#wmCntMapPreviewWrap').addClass('d-none');
                    }
                }
            },
            error: (xhr) => showAlert(extractError(xhr, 'Failed to save Map & Social Draft.'), 'danger'),
            complete: () => btn.prop('disabled', false).text('Save Map & Social Draft')
        });
    });
    $('#wmCntSeoForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.contact.seo.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save SEO Draft'); });
    $('#wmCntPublishBtn').on('click', function() {
        const btn = $(this).prop('disabled', true).text('Publishing...');
        $.ajax({ url: @json(route('admin.website-manager.contact.publish')), method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: (res) => { showAlert(res.message, 'success'); $('#wmCntPubMeta').html('<strong>Published:</strong> ' + res.published_at); },
            error: (xhr) => showAlert(extractError(xhr, 'Publish failed.'), 'danger'),
            complete: () => btn.prop('disabled', false).text('Publish')
        });
    });
    $(document).on('click', '.wm-restore-ver-btn', function() { $.ajax({ url: $(this).data('url'), method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, success: (res) => { showAlert(res.message, 'success'); setTimeout(() => window.location.reload(), 900); } }); });
});
</script>
@endpush
