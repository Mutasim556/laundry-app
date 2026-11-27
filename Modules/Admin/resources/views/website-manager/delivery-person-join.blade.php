@extends('admin::layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="card border-0 shadow-sm rounded-4 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 60%, #FEF3C7 100%); border: 1px solid #FDE68A !important;">
        <div class="card-body p-3 p-md-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="badge rounded-pill text-dark mb-2" style="background: #FFC629;">Website Manager · Recruitment CMS</span>
                <h1 class="h4 fw-bold mb-1">Delivery Person Join Page</h1>
                <p class="small text-secondary mb-0">Manage recruitment content, application presentation, eligibility requirements and publishing.</p>
                <div class="small text-muted mt-1">
                    Last Draft: <span id="cmsDraftAt">{{ $record->last_draft_saved_at?->format('d M Y, H:i') ?? '—' }}</span>
                    · Last Published: <span id="cmsPublishedAt">{{ $record->published_at?->format('d M Y, H:i') ?? '—' }}</span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('join-delivery-person', ['preview' => 'draft']) }}" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-semibold">
                    Preview Draft
                </a>
                <button type="button" id="cmsPublishBtn" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold">
                    Publish Live
                </button>
            </div>
        </div>
    </div>

    <ul class="nav nav-pills bg-white border rounded-4 p-2 mb-3 gap-1 flex-wrap" id="dpJoinCmsTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active rounded-3 fw-semibold small" data-bs-toggle="pill" data-bs-target="#tabGeneral" type="button">General &amp; Hero</button></li>
        <li class="nav-item"><button class="nav-link rounded-3 fw-semibold small" data-bs-toggle="pill" data-bs-target="#tabBenefits" type="button">Benefits</button></li>
        <li class="nav-item"><button class="nav-link rounded-3 fw-semibold small" data-bs-toggle="pill" data-bs-target="#tabSteps" type="button">How It Works</button></li>
        <li class="nav-item"><button class="nav-link rounded-3 fw-semibold small" data-bs-toggle="pill" data-bs-target="#tabRequirements" type="button">Requirements</button></li>
        <li class="nav-item"><button class="nav-link rounded-3 fw-semibold small" data-bs-toggle="pill" data-bs-target="#tabForm" type="button">Application Form</button></li>
        <li class="nav-item"><button class="nav-link rounded-3 fw-semibold small" data-bs-toggle="pill" data-bs-target="#tabDocs" type="button">Verification Docs</button></li>
        <li class="nav-item"><button class="nav-link rounded-3 fw-semibold small" data-bs-toggle="pill" data-bs-target="#tabFaqs" type="button">FAQs</button></li>
        <li class="nav-item"><button class="nav-link rounded-3 fw-semibold small" data-bs-toggle="pill" data-bs-target="#tabSections" type="button">Sections &amp; SEO</button></li>
    </ul>

    <div class="tab-content">
        {{-- TAB 1: GENERAL & HERO --}}
        <div class="tab-pane fade show active" id="tabGeneral">
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white fw-bold">General &amp; Availability</div>
                        <div class="card-body">
                            <form id="formGeneral" data-endpoint="{{ route('admin.website-manager.delivery-person-join.general.save') }}">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="page_enabled" value="1" id="genPageEnabled" @checked($draft['general']['page_enabled'] ?? true)>
                                    <label class="form-check-label small fw-semibold" for="genPageEnabled">Recruitment Page Enabled</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="applications_open" value="1" id="genAppsOpen" @checked($draft['general']['applications_open'] ?? true)>
                                    <label class="form-check-label small fw-semibold" for="genAppsOpen">Applications Open (Accept New Submissions)</label>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Navigation Label</label>
                                    <input type="text" name="nav_label" class="form-control form-control-sm" value="{{ $draft['general']['nav_label'] ?? '' }}" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Footer Link Label</label>
                                    <input type="text" name="footer_label" class="form-control form-control-sm" value="{{ $draft['general']['footer_label'] ?? '' }}" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Closed State Title</label>
                                    <input type="text" name="closed_title" class="form-control form-control-sm" value="{{ $draft['general']['closed_title'] ?? '' }}" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">Closed State Message</label>
                                    <textarea name="closed_message" class="form-control form-control-sm" rows="2" required>{{ $draft['general']['closed_message'] ?? '' }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Expected Reopening (Optional)</label>
                                    <input type="text" name="expected_reopening" class="form-control form-control-sm" value="{{ $draft['general']['expected_reopening'] ?? '' }}">
                                </div>
                                <button type="submit" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold">Save General</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white fw-bold">Hero Section &amp; Media</div>
                        <div class="card-body">
                            <form id="formHero" data-endpoint="{{ route('admin.website-manager.delivery-person-join.hero.save') }}">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Eyebrow / Badge</label>
                                        <input type="text" name="badge" class="form-control form-control-sm" value="{{ $draft['hero']['badge'] ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Background Style</label>
                                        <select name="bg_preset" class="form-select form-select-sm">
                                            <option value="gold_soft" @selected(($draft['hero']['bg_preset'] ?? '') === 'gold_soft')>Gold Soft (Default)</option>
                                            <option value="white_clean" @selected(($draft['hero']['bg_preset'] ?? '') === 'white_clean')>Clean White</option>
                                            <option value="gold_gradient" @selected(($draft['hero']['bg_preset'] ?? '') === 'gold_gradient')>Gold Gradient</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Heading</label>
                                        <input type="text" name="headline" class="form-control form-control-sm" value="{{ $draft['hero']['headline'] ?? '' }}" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Subtitle</label>
                                        <textarea name="subtitle" class="form-control form-control-sm" rows="2">{{ $draft['hero']['subtitle'] ?? '' }}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Hero Image Path / URL</label>
                                        <input type="text" name="image_url" id="heroImageUrlInput" class="form-control form-control-sm" value="{{ $draft['hero']['image_url'] ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Upload Hero Image</label>
                                        <input type="file" id="heroImageUploadFile" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Primary CTA Label</label>
                                        <input type="text" name="primary_btn_label" class="form-control form-control-sm" value="{{ $draft['hero']['primary_btn_label'] ?? '' }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Secondary CTA Label</label>
                                        <input type="text" name="secondary_btn_label" class="form-control form-control-sm" value="{{ $draft['hero']['secondary_btn_label'] ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Secondary CTA URL</label>
                                        <input type="text" name="secondary_btn_url" class="form-control form-control-sm" value="{{ $draft['hero']['secondary_btn_url'] ?? '/delivery-person/login' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Alignment</label>
                                        <select name="alignment" class="form-select form-select-sm">
                                            <option value="left" @selected(($draft['hero']['alignment'] ?? 'left') === 'left')>Left</option>
                                            <option value="center" @selected(($draft['hero']['alignment'] ?? '') === 'center')>Center</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="primary_btn_visible" value="1" @checked($draft['hero']['primary_btn_visible'] ?? true)>
                                            <label class="form-check-label small">Show Primary CTA</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="secondary_btn_visible" value="1" @checked($draft['hero']['secondary_btn_visible'] ?? true)>
                                            <label class="form-check-label small">Show Secondary CTA</label>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold mt-3">Save Hero</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB 2: BENEFITS --}}
        <div class="tab-pane fade" id="tabBenefits">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Benefits Section</span>
                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3" id="addBenefitBtn">+ Add Benefit</button>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6"><label class="form-label small fw-semibold">Heading</label><input type="text" id="benTitle" class="form-control form-control-sm" value="{{ $draft['benefits']['title'] ?? '' }}"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Subtitle</label><input type="text" id="benSubtitle" class="form-control form-control-sm" value="{{ $draft['benefits']['subtitle'] ?? '' }}"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="benefitsTable">
                            <thead><tr><th>Order</th><th>Title</th><th>Description</th><th>Icon</th><th>Visible</th><th></th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <button type="button" id="saveBenefitsBtn" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold">Save Benefits</button>
                </div>
            </div>
        </div>

        {{-- TAB 3: HOW IT WORKS --}}
        <div class="tab-pane fade" id="tabSteps">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">How It Works Steps</span>
                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3" id="addStepBtn">+ Add Step</button>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6"><label class="form-label small fw-semibold">Heading</label><input type="text" id="hiwTitle" class="form-control form-control-sm" value="{{ $draft['how_it_works']['title'] ?? '' }}"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Subtitle</label><input type="text" id="hiwSubtitle" class="form-control form-control-sm" value="{{ $draft['how_it_works']['subtitle'] ?? '' }}"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="stepsTable">
                            <thead><tr><th>Order</th><th>Step #</th><th>Title</th><th>Description</th><th>Icon</th><th>Visible</th><th></th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <button type="button" id="saveStepsBtn" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold">Save Steps</button>
                </div>
            </div>
        </div>

        {{-- TAB 4: REQUIREMENTS --}}
        <div class="tab-pane fade" id="tabRequirements">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Eligibility Requirements</span>
                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3" id="addReqBtn">+ Add Requirement</button>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4"><label class="form-label small fw-semibold">Heading</label><input type="text" id="reqTitle" class="form-control form-control-sm" value="{{ $draft['requirements']['title'] ?? '' }}"></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Subtitle</label><input type="text" id="reqSubtitle" class="form-control form-control-sm" value="{{ $draft['requirements']['subtitle'] ?? '' }}"></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Region Note</label><input type="text" id="reqRegionNote" class="form-control form-control-sm" value="{{ $draft['requirements']['region_note'] ?? '' }}"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="reqsTable">
                            <thead><tr><th>Order</th><th>Title</th><th>Description</th><th>Icon</th><th>Visible</th><th></th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <button type="button" id="saveReqsBtn" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold">Save Requirements</button>
                </div>
            </div>
        </div>

        {{-- TAB 5: APPLICATION FORM SETTINGS --}}
        <div class="tab-pane fade" id="tabForm">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white fw-bold">Application Form Presentation, Calendar &amp; Phone Defaults</div>
                <div class="card-body">
                    <form id="formConfigForm" data-endpoint="{{ route('admin.website-manager.delivery-person-join.form-config.save') }}">
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label small fw-semibold">Form Heading</label><input type="text" name="heading" class="form-control form-control-sm" value="{{ $draft['form_config']['heading'] ?? '' }}" required></div>
                            <div class="col-md-6"><label class="form-label small fw-semibold">Form Subtitle</label><input type="text" name="subtitle" class="form-control form-control-sm" value="{{ $draft['form_config']['subtitle'] ?? '' }}"></div>
                            <div class="col-md-2"><label class="form-label small fw-semibold">Step 1</label><input type="text" name="step_1_title" class="form-control form-control-sm" value="{{ $draft['form_config']['step_1_title'] ?? '' }}" required></div>
                            <div class="col-md-2"><label class="form-label small fw-semibold">Step 2</label><input type="text" name="step_2_title" class="form-control form-control-sm" value="{{ $draft['form_config']['step_2_title'] ?? '' }}" required></div>
                            <div class="col-md-2"><label class="form-label small fw-semibold">Step 3</label><input type="text" name="step_3_title" class="form-control form-control-sm" value="{{ $draft['form_config']['step_3_title'] ?? '' }}" required></div>
                            <div class="col-md-3"><label class="form-label small fw-semibold">Step 4</label><input type="text" name="step_4_title" class="form-control form-control-sm" value="{{ $draft['form_config']['step_4_title'] ?? '' }}" required></div>
                            <div class="col-md-3"><label class="form-label small fw-semibold">Step 5</label><input type="text" name="step_5_title" class="form-control form-control-sm" value="{{ $draft['form_config']['step_5_title'] ?? '' }}" required></div>
                            <div class="col-md-4"><label class="form-label small fw-semibold">DOB Label</label><input type="text" name="dob_label" class="form-control form-control-sm" value="{{ $draft['form_config']['dob_label'] ?? '' }}" required></div>
                            <div class="col-md-4"><label class="form-label small fw-semibold">DOB Placeholder</label><input type="text" name="dob_placeholder" class="form-control form-control-sm" value="{{ $draft['form_config']['dob_placeholder'] ?? '' }}"></div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">DOB Display Format</label>
                                <select name="dob_format" class="form-select form-select-sm">
                                    @foreach (['d M Y', 'Y-m-d', 'm/d/Y', 'd/m/Y'] as $fmt)
                                        <option value="{{ $fmt }}" @selected(($draft['form_config']['dob_format'] ?? 'd M Y') === $fmt)>{{ $fmt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label small fw-semibold">Default Calling Country (ISO)</label><input type="text" name="default_country_code" class="form-control form-control-sm" value="{{ $draft['form_config']['default_country_code'] ?? 'GB' }}" maxlength="2" required></div>
                            <div class="col-md-4"><label class="form-label small fw-semibold">Phone Label</label><input type="text" name="phone_label" class="form-control form-control-sm" value="{{ $draft['form_config']['phone_label'] ?? '' }}" required></div>
                            <div class="col-md-4"><label class="form-label small fw-semibold">Phone Placeholder</label><input type="text" name="phone_placeholder" class="form-control form-control-sm" value="{{ $draft['form_config']['phone_placeholder'] ?? '' }}"></div>
                            <div class="col-md-4"><label class="form-label small fw-semibold">Submit Button Label</label><input type="text" name="submit_label" class="form-control form-control-sm" value="{{ $draft['form_config']['submit_label'] ?? '' }}" required></div>
                            <div class="col-md-8"><label class="form-label small fw-semibold">Success Title</label><input type="text" name="success_title" class="form-control form-control-sm" value="{{ $draft['form_config']['success_title'] ?? '' }}" required></div>
                            <div class="col-12"><label class="form-label small fw-semibold">Success Message</label><textarea name="success_message" class="form-control form-control-sm" rows="2" required>{{ $draft['form_config']['success_message'] ?? '' }}</textarea></div>
                            <div class="col-12"><label class="form-label small fw-semibold">Consent Text</label><textarea name="consent_text" class="form-control form-control-sm" rows="2" required>{{ $draft['form_config']['consent_text'] ?? '' }}</textarea></div>
                            <div class="col-md-6"><div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="show_experience" value="1" @checked($draft['form_config']['show_experience'] ?? true)><label class="form-check-label small">Show Experience Field</label></div></div>
                            <div class="col-md-6"><div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="show_profile_photo" value="1" @checked($draft['form_config']['show_profile_photo'] ?? true)><label class="form-check-label small">Show Profile Photo Upload</label></div></div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold mt-3">Save Form Settings</button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mt-3">
                <div class="card-header bg-white fw-bold">Interactive Registration Demo Settings</div>
                <div class="card-body">
                    <form id="formDemo" data-endpoint="{{ route('admin.website-manager.delivery-person-join.demo.save') }}">
                        <div class="row g-2">
                            <div class="col-md-12">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="enabled" value="1" id="demoEnabled" @checked($draft['demo']['enabled'] ?? true)>
                                    <label class="form-check-label small fw-semibold" for="demoEnabled">Show "View Registration Demo" Button</label>
                                </div>
                            </div>
                            <div class="col-md-4"><label class="form-label small fw-semibold">Demo Button Label</label><input type="text" name="button_label" class="form-control form-control-sm" value="{{ $draft['demo']['button_label'] ?? 'View Registration Demo' }}" required></div>
                            <div class="col-md-4"><label class="form-label small fw-semibold">Modal Title</label><input type="text" name="modal_title" class="form-control form-control-sm" value="{{ $draft['demo']['modal_title'] ?? '' }}" required></div>
                            <div class="col-md-4"><label class="form-label small fw-semibold">Modal Subtitle</label><input type="text" name="modal_subtitle" class="form-control form-control-sm" value="{{ $draft['demo']['modal_subtitle'] ?? '' }}"></div>
                            <div class="col-md-6"><label class="form-label small fw-semibold">Final Step Title</label><input type="text" name="final_title" class="form-control form-control-sm" value="{{ $draft['demo']['final_title'] ?? '' }}" required></div>
                            <div class="col-md-6"><label class="form-label small fw-semibold">Final CTA Button Label</label><input type="text" name="cta_label" class="form-control form-control-sm" value="{{ $draft['demo']['cta_label'] ?? 'Start My Application' }}" required></div>
                            <div class="col-12"><label class="form-label small fw-semibold">Final Step Explanation</label><textarea name="final_message" class="form-control form-control-sm" rows="2" required>{{ $draft['demo']['final_message'] ?? '' }}</textarea></div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold mt-3">Save Demo Settings</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- TAB 6: VERIFICATION DOCUMENTS --}}
        <div class="tab-pane fade" id="tabDocs">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white fw-bold">Verification Document Requirements</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="docsTable">
                            <thead><tr><th>Order</th><th>Key</th><th>Label</th><th>Description</th><th>Max MB (1-5)</th><th>Motor Only</th><th>Required</th><th>Visible</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <button type="button" id="saveDocsBtn" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold">Save Document Settings</button>
                </div>
            </div>
        </div>

        {{-- TAB 7: FAQs --}}
        <div class="tab-pane fade" id="tabFaqs">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">FAQs Accordion</span>
                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3" id="addFaqBtn">+ Add FAQ</button>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6"><label class="form-label small fw-semibold">Heading</label><input type="text" id="faqTitle" class="form-control form-control-sm" value="{{ $draft['faqs']['title'] ?? '' }}"></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Subtitle</label><input type="text" id="faqSubtitle" class="form-control form-control-sm" value="{{ $draft['faqs']['subtitle'] ?? '' }}"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="faqsTable">
                            <thead><tr><th>Order</th><th>Question</th><th>Answer</th><th>Visible</th><th></th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <button type="button" id="saveFaqsBtn" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold">Save FAQs</button>
                </div>
            </div>
        </div>

        {{-- TAB 8: SECTIONS, CTA & SEO --}}
        <div class="tab-pane fade" id="tabSections">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white fw-bold">Section Visibility &amp; Ordering</div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle" id="sectionsTable">
                                    <thead><tr><th>Order</th><th>Section</th><th>Visible</th></tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <button type="button" id="saveSectionsBtn" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold">Save Sections</button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-header bg-white fw-bold">Bottom CTA</div>
                        <div class="card-body">
                            <form id="formCta" data-endpoint="{{ route('admin.website-manager.delivery-person-join.cta.save') }}">
                                <div class="row g-2">
                                    <div class="col-12"><label class="form-label small fw-semibold">Headline</label><input type="text" name="headline" class="form-control form-control-sm" value="{{ $draft['cta']['headline'] ?? '' }}" required></div>
                                    <div class="col-12"><label class="form-label small fw-semibold">Description</label><textarea name="description" class="form-control form-control-sm" rows="2">{{ $draft['cta']['description'] ?? '' }}</textarea></div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Button Label</label><input type="text" name="button_label" class="form-control form-control-sm" value="{{ $draft['cta']['button_label'] ?? '' }}" required></div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Button URL</label><input type="text" name="button_url" class="form-control form-control-sm" value="{{ $draft['cta']['button_url'] ?? '/delivery-person/application-status' }}" required></div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Style Preset</label>
                                        <select name="bg_preset" class="form-select form-select-sm">
                                            <option value="gold_gradient" @selected(($draft['cta']['bg_preset'] ?? '') === 'gold_gradient')>Gold Gradient</option>
                                            <option value="dark_clean" @selected(($draft['cta']['bg_preset'] ?? '') === 'dark_clean')>Dark Clean</option>
                                            <option value="cream_soft" @selected(($draft['cta']['bg_preset'] ?? '') === 'cream_soft')>Cream Soft</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="button_visible" value="1" @checked($draft['cta']['button_visible'] ?? true)><label class="form-check-label small">Show CTA Button</label></div></div>
                                </div>
                                <button type="submit" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold mt-2">Save CTA</button>
                            </form>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white fw-bold">SEO Settings</div>
                        <div class="card-body">
                            <form id="formSeo" data-endpoint="{{ route('admin.website-manager.delivery-person-join.seo.save') }}">
                                <div class="row g-2">
                                    <div class="col-12"><label class="form-label small fw-semibold">Meta Title</label><input type="text" name="meta_title" class="form-control form-control-sm" value="{{ $draft['seo']['meta_title'] ?? '' }}" required></div>
                                    <div class="col-12"><label class="form-label small fw-semibold">Meta Description</label><textarea name="meta_description" class="form-control form-control-sm" rows="2">{{ $draft['seo']['meta_description'] ?? '' }}</textarea></div>
                                    <div class="col-md-6"><label class="form-label small fw-semibold">Canonical URL</label><input type="text" name="canonical_url" class="form-control form-control-sm" value="{{ $draft['seo']['canonical_url'] ?? '' }}"></div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Robots</label>
                                        <select name="robots" class="form-select form-select-sm">
                                            <option value="index, follow" @selected(($draft['seo']['robots'] ?? '') === 'index, follow')>index, follow</option>
                                            <option value="noindex, follow" @selected(($draft['seo']['robots'] ?? '') === 'noindex, follow')>noindex, follow</option>
                                            <option value="noindex, nofollow" @selected(($draft['seo']['robots'] ?? '') === 'noindex, nofollow')>noindex, nofollow</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold mt-2">Save SEO</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const ICONS = @json($allowedIcons);
    let benefits = @json($draft['benefits']['items'] ?? []);
    let steps = @json($draft['how_it_works']['steps'] ?? []);
    let reqs = @json($draft['requirements']['items'] ?? []);
    let docs = @json($draft['documents'] ?? []);
    let faqs = @json($draft['faqs']['items'] ?? []);
    let sections = @json($draft['sections'] ?? []);

    function notify(msg, ok = true) {
        if (typeof toastr !== 'undefined') { ok ? toastr.success(msg) : toastr.error(msg); }
        else { alert(msg); }
    }

    async function postJson(url, payload) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Save failed');
        if (data.draft_saved_at) document.getElementById('cmsDraftAt').textContent = data.draft_saved_at;
        if (data.published_at) document.getElementById('cmsPublishedAt').textContent = data.published_at;
        notify(data.message || 'Saved');
    }

    ['formGeneral', 'formHero', 'formConfigForm', 'formDemo', 'formCta', 'formSeo'].forEach(id => {
        const form = document.getElementById(id);
        form?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const fd = new FormData(form);
            const payload = {};
            form.querySelectorAll('input[type="checkbox"]').forEach(cb => { payload[cb.name] = cb.checked; });
            fd.forEach((v, k) => { if (!(k in payload)) payload[k] = v; });
            try { await postJson(form.dataset.endpoint, payload); }
            catch (err) { notify(err.message, false); }
        });
    });

    document.getElementById('heroImageUploadFile')?.addEventListener('change', async function () {
        if (!this.files[0]) return;
        const fd = new FormData();
        fd.append('file', this.files[0]);
        fd.append('category', 'hero');
        const res = await fetch('{{ route('admin.website-manager.homepage.media.upload') }}', {
            method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: fd
        });
        const data = await res.json();
        if (res.ok && data.media?.path) {
            document.getElementById('heroImageUrlInput').value = data.media.path;
            notify('Hero image uploaded. Click Save Hero to apply.');
        } else {
            notify(data.message || 'Upload failed', false);
        }
    });

    function iconOptions(selected) {
        return ICONS.map(ic => `<option value="${ic}" ${ic === selected ? 'selected' : ''}>${ic}</option>`).join('');
    }

    function renderBenefits() {
        document.querySelector('#benefitsTable tbody').innerHTML = benefits.map((b, i) => `
            <tr>
                <td><input type="number" class="form-control form-control-sm" style="width:65px" value="${b.order ?? i+1}" data-b="order" data-i="${i}"></td>
                <td><input type="text" class="form-control form-control-sm" value="${b.title || ''}" data-b="title" data-i="${i}"></td>
                <td><input type="text" class="form-control form-control-sm" value="${b.description || ''}" data-b="description" data-i="${i}"></td>
                <td><select class="form-select form-select-sm" data-b="icon" data-i="${i}">${iconOptions(b.icon)}</select></td>
                <td><input type="checkbox" class="form-check-input" ${b.visible ? 'checked' : ''} data-b="visible" data-i="${i}"></td>
                <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="window._delBen(${i})">×</button></td>
            </tr>`).join('');
    }
    window._delBen = (i) => { benefits.splice(i, 1); renderBenefits(); };
    document.getElementById('addBenefitBtn').onclick = () => { benefits.push({ title: 'New Benefit', description: 'Description', icon: 'check-circle', visible: true, order: benefits.length + 1 }); renderBenefits(); };
    document.getElementById('saveBenefitsBtn').onclick = async () => {
        document.querySelectorAll('[data-b]').forEach(el => { benefits[el.dataset.i][el.dataset.b] = el.type === 'checkbox' ? el.checked : (el.dataset.b === 'order' ? Number(el.value) : el.value); });
        try { await postJson('{{ route('admin.website-manager.delivery-person-join.benefits.save') }}', { title: document.getElementById('benTitle').value, subtitle: document.getElementById('benSubtitle').value, items: benefits }); } catch (e) { notify(e.message, false); }
    };

    function renderSteps() {
        document.querySelector('#stepsTable tbody').innerHTML = steps.map((s, i) => `
            <tr>
                <td><input type="number" class="form-control form-control-sm" style="width:65px" value="${s.order ?? i+1}" data-s="order" data-i="${i}"></td>
                <td><input type="text" class="form-control form-control-sm" style="width:65px" value="${s.step || ''}" data-s="step" data-i="${i}"></td>
                <td><input type="text" class="form-control form-control-sm" value="${s.title || ''}" data-s="title" data-i="${i}"></td>
                <td><input type="text" class="form-control form-control-sm" value="${s.description || ''}" data-s="description" data-i="${i}"></td>
                <td><select class="form-select form-select-sm" data-s="icon" data-i="${i}">${iconOptions(s.icon)}</select></td>
                <td><input type="checkbox" class="form-check-input" ${s.visible ? 'checked' : ''} data-s="visible" data-i="${i}"></td>
                <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="window._delStep(${i})">×</button></td>
            </tr>`).join('');
    }
    window._delStep = (i) => { steps.splice(i, 1); renderSteps(); };
    document.getElementById('addStepBtn').onclick = () => { steps.push({ step: '0' + (steps.length + 1), title: 'New Step', description: 'Step details', icon: 'check-circle', visible: true, order: steps.length + 1 }); renderSteps(); };
    document.getElementById('saveStepsBtn').onclick = async () => {
        document.querySelectorAll('[data-s]').forEach(el => { steps[el.dataset.i][el.dataset.s] = el.type === 'checkbox' ? el.checked : (el.dataset.s === 'order' ? Number(el.value) : el.value); });
        try { await postJson('{{ route('admin.website-manager.delivery-person-join.how-it-works.save') }}', { title: document.getElementById('hiwTitle').value, subtitle: document.getElementById('hiwSubtitle').value, steps }); } catch (e) { notify(e.message, false); }
    };

    function renderReqs() {
        document.querySelector('#reqsTable tbody').innerHTML = reqs.map((r, i) => `
            <tr>
                <td><input type="number" class="form-control form-control-sm" style="width:65px" value="${r.order ?? i+1}" data-r="order" data-i="${i}"></td>
                <td><input type="text" class="form-control form-control-sm" value="${r.title || ''}" data-r="title" data-i="${i}"></td>
                <td><input type="text" class="form-control form-control-sm" value="${r.description || ''}" data-r="description" data-i="${i}"></td>
                <td><select class="form-select form-select-sm" data-r="icon" data-i="${i}">${iconOptions(r.icon)}</select></td>
                <td><input type="checkbox" class="form-check-input" ${r.visible ? 'checked' : ''} data-r="visible" data-i="${i}"></td>
                <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="window._delReq(${i})">×</button></td>
            </tr>`).join('');
    }
    window._delReq = (i) => { reqs.splice(i, 1); renderReqs(); };
    document.getElementById('addReqBtn').onclick = () => { reqs.push({ title: 'Requirement', description: 'Details', icon: 'shield', visible: true, order: reqs.length + 1 }); renderReqs(); };
    document.getElementById('saveReqsBtn').onclick = async () => {
        document.querySelectorAll('[data-r]').forEach(el => { reqs[el.dataset.i][el.dataset.r] = el.type === 'checkbox' ? el.checked : (el.dataset.r === 'order' ? Number(el.value) : el.value); });
        try { await postJson('{{ route('admin.website-manager.delivery-person-join.requirements.save') }}', { title: document.getElementById('reqTitle').value, subtitle: document.getElementById('reqSubtitle').value, region_note: document.getElementById('reqRegionNote').value, items: reqs }); } catch (e) { notify(e.message, false); }
    };

    function renderDocs() {
        document.querySelector('#docsTable tbody').innerHTML = docs.map((d, i) => `
            <tr>
                <td><input type="number" class="form-control form-control-sm" style="width:65px" value="${d.order ?? i+1}" data-d="order" data-i="${i}"></td>
                <td class="small fw-semibold">${d.key}</td>
                <td><input type="text" class="form-control form-control-sm" value="${d.label || ''}" data-d="label" data-i="${i}"></td>
                <td><input type="text" class="form-control form-control-sm" value="${d.description || ''}" data-d="description" data-i="${i}"></td>
                <td><input type="number" min="1" max="5" class="form-control form-control-sm" style="width:65px" value="${d.max_mb || 5}" data-d="max_mb" data-i="${i}"></td>
                <td><input type="checkbox" class="form-check-input" ${d.motor_only ? 'checked' : ''} data-d="motor_only" data-i="${i}"></td>
                <td><input type="checkbox" class="form-check-input" ${d.required ? 'checked' : ''} data-d="required" data-i="${i}"></td>
                <td><input type="checkbox" class="form-check-input" ${d.visible ? 'checked' : ''} data-d="visible" data-i="${i}"></td>
            </tr>`).join('');
    }
    document.getElementById('saveDocsBtn').onclick = async () => {
        document.querySelectorAll('[data-d]').forEach(el => { docs[el.dataset.i][el.dataset.d] = el.type === 'checkbox' ? el.checked : (['order','max_mb'].includes(el.dataset.d) ? Number(el.value) : el.value); });
        try { await postJson('{{ route('admin.website-manager.delivery-person-join.documents.save') }}', { documents: docs }); } catch (e) { notify(e.message, false); }
    };

    function renderFaqs() {
        document.querySelector('#faqsTable tbody').innerHTML = faqs.map((f, i) => `
            <tr>
                <td><input type="number" class="form-control form-control-sm" style="width:65px" value="${f.order ?? i+1}" data-f="order" data-i="${i}"></td>
                <td><input type="text" class="form-control form-control-sm" value="${f.question || ''}" data-f="question" data-i="${i}"></td>
                <td><input type="text" class="form-control form-control-sm" value="${f.answer || ''}" data-f="answer" data-i="${i}"></td>
                <td><input type="checkbox" class="form-check-input" ${f.visible ? 'checked' : ''} data-f="visible" data-i="${i}"></td>
                <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="window._delFaq(${i})">×</button></td>
            </tr>`).join('');
    }
    window._delFaq = (i) => { faqs.splice(i, 1); renderFaqs(); };
    document.getElementById('addFaqBtn').onclick = () => { faqs.push({ question: 'New Question?', answer: 'Answer...', visible: true, order: faqs.length + 1 }); renderFaqs(); };
    document.getElementById('saveFaqsBtn').onclick = async () => {
        document.querySelectorAll('[data-f]').forEach(el => { faqs[el.dataset.i][el.dataset.f] = el.type === 'checkbox' ? el.checked : (el.dataset.f === 'order' ? Number(el.value) : el.value); });
        try { await postJson('{{ route('admin.website-manager.delivery-person-join.faqs.save') }}', { title: document.getElementById('faqTitle').value, subtitle: document.getElementById('faqSubtitle').value, items: faqs }); } catch (e) { notify(e.message, false); }
    };

    function renderSections() {
        document.querySelector('#sectionsTable tbody').innerHTML = sections.map((s, i) => `
            <tr>
                <td><input type="number" class="form-control form-control-sm" style="width:65px" value="${s.order ?? i+1}" data-sec="order" data-i="${i}"></td>
                <td class="small fw-semibold">${s.label}</td>
                <td><input type="checkbox" class="form-check-input" ${s.visible ? 'checked' : ''} data-sec="visible" data-i="${i}"></td>
            </tr>`).join('');
    }
    document.getElementById('saveSectionsBtn').onclick = async () => {
        document.querySelectorAll('[data-sec]').forEach(el => { sections[el.dataset.i][el.dataset.sec] = el.type === 'checkbox' ? el.checked : Number(el.value); });
        try { await postJson('{{ route('admin.website-manager.delivery-person-join.sections.save') }}', { sections }); } catch (e) { notify(e.message, false); }
    };

    document.getElementById('cmsPublishBtn').onclick = async () => {
        try { await postJson('{{ route('admin.website-manager.delivery-person-join.publish') }}', {}); }
        catch (e) { notify(e.message, false); }
    };

    renderBenefits(); renderSteps(); renderReqs(); renderDocs(); renderFaqs(); renderSections();
})();
</script>
@endsection
