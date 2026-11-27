@extends('admin::layouts.app')

@section('title', 'About Page Contents - Website Manager')

@push('styles')
<style>
    .wm-header-card { background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 55%, #FEF3C7 100%); border: 1px solid #FDE68A; border-radius: 18px; box-shadow: 0 8px 24px rgba(255, 198, 41, 0.14); }
    .wm-kpi-card { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 0.9rem 1rem; position: relative; overflow: hidden; height: 100%; }
    .wm-kpi-bar { position: absolute; top: 0; left: 0; right: 0; height: 4px; }
    .wm-kpi-icon { width: 38px; height: 38px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .wm-nav-pills { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 14px; padding: 0.45rem; }
    .wm-nav-pills .nav-link { color: #475569; font-weight: 600; font-size: 0.83rem; border-radius: 10px; padding: 0.48rem 0.88rem; display: inline-flex; align-items: center; gap: 0.38rem; }
    .wm-nav-pills .nav-link.active { background: linear-gradient(135deg, #FFC629 0%, #F59E0B 100%); color: #171717; }
    .wm-media-card { border: 1px solid #E2E8F0; border-radius: 14px; overflow: hidden; background: #FFFFFF; }
    .wm-media-thumb { height: 110px; background: #F8FAFC; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .wm-media-thumb img { max-height: 100%; max-width: 100%; object-fit: contain; }
</style>
@endpush

@section('content')
@php
    $visibleSectionsCount = collect($draft['sections'] ?? [])->where('visible', true)->count();
    $totalSectionsCount = count($draft['sections'] ?? []);
    $benefitsCount = collect($draft['benefits']['items'] ?? [])->where('visible', true)->count();
    $stepsCount = collect($draft['how_it_works']['steps'] ?? [])->where('visible', true)->count();
@endphp
<div class="container-fluid py-3">
    <div class="wm-header-card p-3 p-md-4 mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-pill px-2 py-1 fw-bold" style="background: #FFC629; color: #171717; font-size: 0.72rem;">Website Manager</span>
                    <span class="badge rounded-pill bg-white text-dark border" style="font-size: 0.72rem;">Version #{{ $record->current_version_number }}</span>
                </div>
                <h4 class="fw-bold mb-1">About Page Contents</h4>
                <div class="small text-muted">Manage the public About Us page (/about) story, mission, benefits, workflow, and SEO.</div>
                <div class="small mt-2 d-flex flex-wrap gap-2">
                    <span id="wmAbtDraftMeta" class="badge rounded-pill bg-light text-dark border px-3 py-1 fw-normal">
                        <strong>Last Draft:</strong> {{ $record->last_draft_saved_at?->format('d M Y, H:i') ?? 'Initialized' }}
                    </span>
                    <span id="wmAbtPubMeta" class="badge rounded-pill {{ $record->published_at ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }} px-3 py-1 fw-normal">
                        <strong>Published:</strong> {{ $record->published_at?->format('d M Y, H:i') ?? 'Not yet published' }}
                    </span>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ url('/about?preview=draft') }}" target="_blank" rel="noopener" class="btn btn-sm btn-light border bg-white px-3 fw-semibold shadow-sm">
                    <i data-feather="eye" style="width: 14px; height: 14px;" class="me-1"></i>Preview Page
                </a>
                <button type="button" class="btn btn-sm btn-success px-3 fw-semibold shadow-sm" id="wmAbtPublishBtn">
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
                    <div><div class="small text-muted fw-semibold">Visible Sections</div><div class="fs-4 fw-bold" style="color: #6D28D9;">{{ $visibleSectionsCount }} / {{ $totalSectionsCount }}</div></div>
                    <span class="wm-kpi-icon" style="background: #F5F3FF; color: #7C3AED;"><i data-feather="layers" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #3B82F6;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div><div class="small text-muted fw-semibold">Benefit Cards</div><div class="fs-4 fw-bold text-primary">{{ $benefitsCount }}</div></div>
                    <span class="wm-kpi-icon" style="background: #EFF6FF; color: #2563EB;"><i data-feather="award" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #16A34A;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div><div class="small text-muted fw-semibold">Workflow Steps</div><div class="fs-4 fw-bold text-success">{{ $stepsCount }}</div></div>
                    <span class="wm-kpi-icon" style="background: #ECFDF5; color: #16A34A;"><i data-feather="check-circle" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wm-kpi-card">
                <div class="wm-kpi-bar" style="background: #F59E0B;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div><div class="small text-muted fw-semibold">Media &amp; Versions</div><div class="fs-4 fw-bold" style="color: #D97706;">{{ $mediaItems->count() }} <span class="fs-6 fw-normal text-muted">/ {{ $versions->count() }}</span></div></div>
                    <span class="wm-kpi-icon" style="background: #FFFBEB; color: #D97706;"><i data-feather="image" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div id="wmAbtAlertBox" class="alert d-none py-2 small mb-3"></div>

    <ul class="nav nav-pills wm-nav-pills gap-1 mb-3 flex-wrap" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#abtTabSections" type="button">Sections</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#abtTabHero" type="button">Hero</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#abtTabStory" type="button">Our Story</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#abtTabMV" type="button">Mission &amp; Vision</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#abtTabBenefits" type="button">Benefits</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#abtTabHow" type="button">How It Works</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#abtTabStats" type="button">Statistics</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#abtTabCta" type="button">CTA</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#abtTabSeo" type="button">SEO &amp; History</button></li>
    </ul>

    <div class="tab-content">
        <!-- Sections -->
        <div class="tab-pane fade show active" id="abtTabSections">
            <div class="card border-0 shadow-sm">
                <form id="wmAbtSectionsForm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small" style="min-width: 650px;">
                                <thead class="table-light"><tr><th style="width:90px;">Order</th><th>Section</th><th>Description</th><th>Visibility</th><th class="text-end">Move</th></tr></thead>
                                <tbody id="wmAbtSectionsBody">
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
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Sections Draft</button></div>
                </form>
            </div>
        </div>

        <!-- Hero -->
        <div class="tab-pane fade" id="abtTabHero">
            <div class="card border-0 shadow-sm">
                <form id="wmAbtHeroForm">
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
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Hero Image Path / URL</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="image_url" id="wmAbtHeroImg" class="form-control" value="{{ $draft['hero']['image_url'] ?? '' }}">
                                    <button type="button" class="btn btn-outline-secondary wm-pick-media-btn" data-target="#wmAbtHeroImg">Choose Media</button>
                                </div>
                            </div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Image Alt Text</label><input type="text" name="image_alt" class="form-control form-control-sm" value="{{ $draft['hero']['image_alt'] ?? '' }}"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Primary Button Text</label><input type="text" name="primary_btn_label" class="form-control form-control-sm" value="{{ $draft['hero']['primary_btn_label'] ?? '' }}"></div>
                            <div class="col-md-5"><label class="form-label fw-semibold">Primary Button URL</label><input type="text" name="primary_btn_url" class="form-control form-control-sm" value="{{ $draft['hero']['primary_btn_url'] ?? '/booking/services' }}"></div>
                            <div class="col-md-3 d-flex align-items-end"><div class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" name="primary_btn_visible" value="1" {{ !empty($draft['hero']['primary_btn_visible']) ? 'checked' : '' }}><label class="form-check-label">Show Primary</label></div></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Secondary Button Text</label><input type="text" name="secondary_btn_label" class="form-control form-control-sm" value="{{ $draft['hero']['secondary_btn_label'] ?? '' }}"></div>
                            <div class="col-md-5"><label class="form-label fw-semibold">Secondary Button URL</label><input type="text" name="secondary_btn_url" class="form-control form-control-sm" value="{{ $draft['hero']['secondary_btn_url'] ?? '/services' }}"></div>
                            <div class="col-md-3 d-flex align-items-end"><div class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" name="secondary_btn_visible" value="1" {{ !empty($draft['hero']['secondary_btn_visible']) ? 'checked' : '' }}><label class="form-check-label">Show Secondary</label></div></div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Hero Draft</button></div>
                </form>
            </div>
        </div>

        <!-- Story -->
        <div class="tab-pane fade" id="abtTabStory">
            <div class="card border-0 shadow-sm">
                <form id="wmAbtStoryForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label fw-semibold">Intro Label</label><input type="text" name="eyebrow" class="form-control form-control-sm" value="{{ $draft['story']['eyebrow'] ?? '' }}"></div>
                            <div class="col-md-8"><label class="form-label fw-semibold">Heading <span class="text-danger">*</span></label><input type="text" name="heading" class="form-control form-control-sm" value="{{ $draft['story']['heading'] ?? '' }}" required></div>
                            <div class="col-md-12"><label class="form-label fw-semibold">Company Story <span class="text-danger">*</span></label><textarea name="body" class="form-control form-control-sm" rows="5" required>{{ $draft['story']['body'] ?? '' }}</textarea></div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Story Image</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="image_url" id="wmAbtStoryImg" class="form-control" value="{{ $draft['story']['image_url'] ?? '' }}">
                                    <button type="button" class="btn btn-outline-secondary wm-pick-media-btn" data-target="#wmAbtStoryImg">Choose Media</button>
                                </div>
                            </div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Image Alt Text</label><input type="text" name="image_alt" class="form-control form-control-sm" value="{{ $draft['story']['image_alt'] ?? '' }}"></div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Story Draft</button></div>
                </form>
            </div>
        </div>

        <!-- Mission & Vision -->
        <div class="tab-pane fade" id="abtTabMV">
            <div class="card border-0 shadow-sm">
                <form id="wmAbtMvForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="mission_visible" value="1" {{ !empty($draft['mission_vision']['mission_visible']) ? 'checked' : '' }}><label class="form-check-label fw-bold">Show Mission Card</label></div>
                                <input type="text" name="mission_title" class="form-control form-control-sm mb-2" value="{{ $draft['mission_vision']['mission_title'] ?? '' }}" required placeholder="Mission Title">
                                <textarea name="mission_description" class="form-control form-control-sm" rows="3" required>{{ $draft['mission_vision']['mission_description'] ?? '' }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="vision_visible" value="1" {{ !empty($draft['mission_vision']['vision_visible']) ? 'checked' : '' }}><label class="form-check-label fw-bold">Show Vision Card</label></div>
                                <input type="text" name="vision_title" class="form-control form-control-sm mb-2" value="{{ $draft['mission_vision']['vision_title'] ?? '' }}" placeholder="Vision Title">
                                <textarea name="vision_description" class="form-control form-control-sm" rows="3">{{ $draft['mission_vision']['vision_description'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Mission &amp; Vision Draft</button></div>
                </form>
            </div>
        </div>

        <!-- Benefits -->
        <div class="tab-pane fade" id="abtTabBenefits">
            <div class="card border-0 shadow-sm">
                <form id="wmAbtBenefitsForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3 mb-3">
                            <div class="col-md-5"><label class="form-label fw-semibold">Section Title <span class="text-danger">*</span></label><input type="text" name="title" class="form-control form-control-sm" value="{{ $draft['benefits']['title'] ?? '' }}" required></div>
                            <div class="col-md-7"><label class="form-label fw-semibold">Subtitle</label><input type="text" name="subtitle" class="form-control form-control-sm" value="{{ $draft['benefits']['subtitle'] ?? '' }}"></div>
                        </div>
                        <div class="row g-2">
                            @foreach(($draft['benefits']['items'] ?? []) as $idx => $b)
                                <div class="col-md-6">
                                    <div class="p-3 border rounded bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="items[{{ $idx }}][visible]" value="1" {{ !empty($b['visible']) ? 'checked' : '' }}><label class="form-check-label fw-semibold">Visible</label></div>
                                            <input type="number" name="items[{{ $idx }}][order]" class="form-control form-control-sm" style="width:70px;" value="{{ $b['order'] ?? ($idx + 1) }}" min="1">
                                        </div>
                                        <input type="text" name="items[{{ $idx }}][title]" class="form-control form-control-sm mb-2" value="{{ $b['title'] ?? '' }}" required>
                                        <textarea name="items[{{ $idx }}][description]" class="form-control form-control-sm" rows="2" required>{{ $b['description'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Benefits Draft</button></div>
                </form>
            </div>
        </div>

        <!-- How It Works -->
        <div class="tab-pane fade" id="abtTabHow">
            <div class="card border-0 shadow-sm">
                <form id="wmAbtHowForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3 mb-3">
                            <div class="col-md-5"><label class="form-label fw-semibold">Section Title <span class="text-danger">*</span></label><input type="text" name="title" class="form-control form-control-sm" value="{{ $draft['how_it_works']['title'] ?? '' }}" required></div>
                            <div class="col-md-7"><label class="form-label fw-semibold">Subtitle</label><input type="text" name="subtitle" class="form-control form-control-sm" value="{{ $draft['how_it_works']['subtitle'] ?? '' }}"></div>
                        </div>
                        <div class="row g-2">
                            @foreach(($draft['how_it_works']['steps'] ?? []) as $idx => $st)
                                <div class="col-md-6">
                                    <div class="p-3 border rounded bg-light">
                                        <div class="d-flex gap-2 align-items-center mb-2">
                                            <input type="text" name="steps[{{ $idx }}][step]" class="form-control form-control-sm" style="width:60px;" value="{{ $st['step'] ?? '' }}" required>
                                            <input type="number" name="steps[{{ $idx }}][order]" class="form-control form-control-sm" style="width:70px;" value="{{ $st['order'] ?? ($idx + 1) }}" min="1">
                                            <div class="form-check form-switch ms-auto"><input class="form-check-input" type="checkbox" name="steps[{{ $idx }}][visible]" value="1" {{ !empty($st['visible']) ? 'checked' : '' }}><label class="form-check-label">Visible</label></div>
                                        </div>
                                        <input type="text" name="steps[{{ $idx }}][title]" class="form-control form-control-sm mb-2" value="{{ $st['title'] ?? '' }}" required>
                                        <textarea name="steps[{{ $idx }}][description]" class="form-control form-control-sm" rows="2" required>{{ $st['description'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Steps Draft</button></div>
                </form>
            </div>
        </div>

        <!-- Statistics -->
        <div class="tab-pane fade" id="abtTabStats">
            <div class="card border-0 shadow-sm">
                <form id="wmAbtStatsForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="mb-3"><label class="form-label fw-semibold">Section Title</label><input type="text" name="title" class="form-control form-control-sm" value="{{ $draft['stats']['title'] ?? 'Fresh Go at a Glance' }}"></div>
                        <div class="row g-2">
                            @for($i = 0; $i < 4; $i++)
                                @php $st = $draft['stats']['items'][$i] ?? ['value' => '', 'label' => '']; @endphp
                                <div class="col-md-3">
                                    <div class="p-2 border rounded bg-light">
                                        <input type="text" name="items[{{ $i }}][value]" class="form-control form-control-sm mb-1" placeholder="Verified Value" value="{{ $st['value'] ?? '' }}">
                                        <input type="text" name="items[{{ $i }}][label]" class="form-control form-control-sm" placeholder="Label" value="{{ $st['label'] ?? '' }}">
                                    </div>
                                </div>
                            @endfor
                        </div>
                        <div class="form-text mt-2">Enable the Statistics section in the Sections tab only after adding verified figures.</div>
                    </div>
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save Statistics Draft</button></div>
                </form>
            </div>
        </div>

        <!-- CTA -->
        <div class="tab-pane fade" id="abtTabCta">
            <div class="card border-0 shadow-sm">
                <form id="wmAbtCtaForm">
                    <div class="card-body p-3 p-md-4 small">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">Headline <span class="text-danger">*</span></label><input type="text" name="headline" class="form-control form-control-sm" value="{{ $draft['cta']['headline'] ?? '' }}" required></div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Background</label>
                                <select name="bg_preset" class="form-select form-select-sm">
                                    <option value="gold_gradient" {{ ($draft['cta']['bg_preset'] ?? 'gold_gradient') === 'gold_gradient' ? 'selected' : '' }}>Fresh Go Gold Gradient</option>
                                    <option value="cream" {{ ($draft['cta']['bg_preset'] ?? '') === 'cream' ? 'selected' : '' }}>Soft Cream</option>
                                    <option value="dark_gold" {{ ($draft['cta']['bg_preset'] ?? '') === 'dark_gold' ? 'selected' : '' }}>Dark Charcoal</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end"><div class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" name="button_visible" value="1" {{ !empty($draft['cta']['button_visible']) ? 'checked' : '' }}><label class="form-check-label">Show Button</label></div></div>
                            <div class="col-md-12"><label class="form-label fw-semibold">Description</label><textarea name="description" class="form-control form-control-sm" rows="2">{{ $draft['cta']['description'] ?? '' }}</textarea></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Button Text</label><input type="text" name="button_label" class="form-control form-control-sm" value="{{ $draft['cta']['button_label'] ?? '' }}"></div>
                            <div class="col-md-8"><label class="form-label fw-semibold">Button URL</label><input type="text" name="button_url" class="form-control form-control-sm" value="{{ $draft['cta']['button_url'] ?? '/booking/services' }}"></div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top text-end"><button type="submit" class="btn btn-sm btn-warning fw-semibold px-3">Save CTA Draft</button></div>
                </form>
            </div>
        </div>

        <!-- SEO & History -->
        <div class="tab-pane fade" id="abtTabSeo">
            <div class="card border-0 shadow-sm mb-3">
                <form id="wmAbtSeoForm">
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
                            <div class="col-md-6"><label class="form-label fw-semibold">OG Title</label><input type="text" name="og_title" class="form-control form-control-sm" value="{{ $draft['seo']['og_title'] ?? '' }}"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">OG Image URL</label><input type="text" name="og_image_url" class="form-control form-control-sm" value="{{ $draft['seo']['og_image_url'] ?? '' }}"></div>
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
                                    <td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 wm-restore-ver-btn" data-url="{{ route('admin.website-manager.about.versions.restore', $ver) }}">Restore</button></td>
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

<!-- Media Picker Modal -->
<div class="modal fade" id="wmAbtMediaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light"><h6 class="modal-title fw-bold">Select Media</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-2">
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
    function showAlert(msg, type = 'success') { $('#wmAbtAlertBox').removeClass('d-none alert-success alert-danger').addClass(type === 'success' ? 'alert-success' : 'alert-danger').text(msg); window.scrollTo({ top: 0, behavior: 'smooth' }); }
    function extractError(xhr, fallback) { if (xhr.responseJSON?.errors) { const k = Object.keys(xhr.responseJSON.errors)[0]; if (k && xhr.responseJSON.errors[k][0]) return xhr.responseJSON.errors[k][0]; } return xhr.responseJSON?.message || fallback; }
    function postDraft(url, data, btn, label) {
        btn.prop('disabled', true).text('Saving...');
        $.ajax({ url, method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, data,
            success: (res) => { showAlert(res.message, 'success'); if (res.draft_saved_at) $('#wmAbtDraftMeta').html('<strong>Last Draft:</strong> ' + res.draft_saved_at); },
            error: (xhr) => showAlert(extractError(xhr, 'Save failed.'), 'danger'),
            complete: () => btn.prop('disabled', false).text(label)
        });
    }
    $(document).on('click', '.wm-move-up', function() { const r = $(this).closest('tr'), p = r.prev('tr'); if (p.length) { r.insertBefore(p); $('#wmAbtSectionsBody tr').each((i, el) => $(el).find('.wm-sec-order').val(i + 1)); } });
    $(document).on('click', '.wm-move-down', function() { const r = $(this).closest('tr'), n = r.next('tr'); if (n.length) { r.insertAfter(n); $('#wmAbtSectionsBody tr').each((i, el) => $(el).find('.wm-sec-order').val(i + 1)); } });
    $('#wmAbtSectionsForm').on('submit', function(e) {
        e.preventDefault(); const sections = [];
        $('#wmAbtSectionsBody tr').each(function() { sections.push({ key: $(this).find('.wm-sec-key').val(), label: $(this).find('.wm-sec-label').val(), subtitle: $(this).find('.wm-sec-sub').val(), order: parseInt($(this).find('.wm-sec-order').val() || '1', 10), visible: $(this).find('.wm-sec-visible').is(':checked') ? 1 : 0 }); });
        postDraft(@json(route('admin.website-manager.about.sections.save')), { sections }, $(this).find('button[type="submit"]'), 'Save Sections Draft');
    });
    $('#wmAbtHeroForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.about.hero.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Hero Draft'); });
    $('#wmAbtStoryForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.about.story.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Story Draft'); });
    $('#wmAbtMvForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.about.mission-vision.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Mission & Vision Draft'); });
    $('#wmAbtBenefitsForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.about.benefits.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Benefits Draft'); });
    $('#wmAbtHowForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.about.how-it-works.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Steps Draft'); });
    $('#wmAbtStatsForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.about.stats.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save Statistics Draft'); });
    $('#wmAbtCtaForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.about.cta.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save CTA Draft'); });
    $('#wmAbtSeoForm').on('submit', function(e) { e.preventDefault(); postDraft(@json(route('admin.website-manager.about.seo.save')), $(this).serialize(), $(this).find('button[type="submit"]'), 'Save SEO Draft'); });
    $('#wmAbtPublishBtn').on('click', function() {
        const btn = $(this).prop('disabled', true).text('Publishing...');
        $.ajax({ url: @json(route('admin.website-manager.about.publish')), method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: (res) => { showAlert(res.message, 'success'); $('#wmAbtPubMeta').html('<strong>Published:</strong> ' + res.published_at); },
            error: (xhr) => showAlert(extractError(xhr, 'Publish failed.'), 'danger'),
            complete: () => btn.prop('disabled', false).text('Publish')
        });
    });
    $(document).on('click', '.wm-restore-ver-btn', function() { $.ajax({ url: $(this).data('url'), method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, success: (res) => { showAlert(res.message, 'success'); setTimeout(() => window.location.reload(), 900); } }); });
    $(document).on('click', '.wm-pick-media-btn', function() { pickerTarget = $(this).data('target'); bootstrap.Modal.getOrCreateInstance(document.getElementById('wmAbtMediaModal')).show(); });
    $(document).on('click', '.wm-select-media-item', function() { if (pickerTarget) $(pickerTarget).val($(this).data('url')); bootstrap.Modal.getOrCreateInstance(document.getElementById('wmAbtMediaModal')).hide(); });
});
</script>
@endpush
