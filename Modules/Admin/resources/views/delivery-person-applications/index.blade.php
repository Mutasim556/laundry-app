@extends('admin::layouts.app')

@section('content')
<style>
    .dpa-header {
        background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 55%, #FEF3C7 100%);
        border: 1px solid #FDE68A;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(255, 198, 41, 0.14);
    }
    .dpa-kpi-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 0.85rem 1rem;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        height: 100%;
    }
    .dpa-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    }
    .dpa-kpi-card.active {
        border-color: #FFC629;
        background: #FFFDF5;
        box-shadow: 0 6px 18px rgba(255, 198, 41, 0.22);
    }
    .dpa-kpi-bar { position: absolute; top: 0; left: 0; right: 0; height: 4px; }
    .dpa-avatar {
        width: 38px; height: 38px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.82rem; flex-shrink: 0;
        background: #FFF8E5; color: #92400E; border: 1px solid #FDE68A;
    }
    .dpa-detail-card {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 0.9rem 1rem;
        height: 100%;
    }
    .dpa-doc-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        overflow: hidden;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .dpa-doc-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.07);
    }
    .dpa-doc-thumb-wrap {
        height: 130px;
        background: #F8FAFC;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        overflow: hidden;
        border-bottom: 1px solid #E2E8F0;
    }
    .dpa-doc-thumb-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: opacity 0.2s ease;
    }
    @media (prefers-reduced-motion: reduce) {
        .dpa-kpi-card, .dpa-doc-card { transition: none !important; }
    }
</style>

<div class="container-fluid py-3">
    <div class="dpa-header p-3 p-md-4 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <span class="badge rounded-pill text-dark mb-2" style="background: #FFC629;">Operations · Onboarding</span>
            <h1 class="h4 fw-bold mb-1 text-dark">Delivery Person Applications</h1>
            <p class="small text-secondary mb-0">Review applications, check documents, and manage approval.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.delivery-persons.index') }}" class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-semibold">
                Active Delivery Persons
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-2 mb-3">
        @foreach ([
            ['id' => 'cntTotal', 'status' => '', 'label' => 'Total Applications', 'val' => $counts['total'], 'bar' => '#171717'],
            ['id' => 'cntSubmitted', 'status' => 'submitted', 'label' => 'Pending Review', 'val' => $counts['submitted'], 'bar' => '#3B82F6'],
            ['id' => 'cntUnderReview', 'status' => 'under_review', 'label' => 'Under Review', 'val' => $counts['under_review'], 'bar' => '#8B5CF6'],
            ['id' => 'cntInfoReq', 'status' => 'additional_info_required', 'label' => 'Info Required', 'val' => $counts['additional_info_required'], 'bar' => '#F59E0B'],
            ['id' => 'cntApproved', 'status' => 'approved', 'label' => 'Approved', 'val' => $counts['approved'], 'bar' => '#16A34A'],
            ['id' => 'cntRejected', 'status' => 'rejected', 'label' => 'Rejected', 'val' => $counts['rejected'], 'bar' => '#EF4444'],
        ] as $kpi)
            <div class="col-6 col-md-4 col-lg-2">
                <div class="dpa-kpi-card" data-kpi-status="{{ $kpi['status'] }}" role="button" tabindex="0">
                    <div class="dpa-kpi-bar" style="background: {{ $kpi['bar'] }};"></div>
                    <div class="small text-secondary mt-1">{{ $kpi['label'] }}</div>
                    <div class="h4 fw-bold mb-0 text-dark" id="{{ $kpi['id'] }}">{{ $kpi['val'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Search & Filters --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="appSearch" class="form-label small fw-semibold mb-1">Search</label>
                    <input type="text" id="appSearch" class="form-control form-control-sm rounded-3" placeholder="Name, email, phone, or reference...">
                </div>
                <div class="col-md-3">
                    <label for="appStatus" class="form-label small fw-semibold mb-1">Status</label>
                    <select id="appStatus" class="form-select form-select-sm rounded-3">
                        <option value="review_queue" selected>Review Queue (Pending &amp; Under Review)</option>
                        <option value="">All Applications</option>
                        <option value="submitted">Pending Review</option>
                        <option value="under_review">Under Review</option>
                        <option value="additional_info_required">Additional Info Required</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="appDateFrom" class="form-label small fw-semibold mb-1">From</label>
                    <input type="date" id="appDateFrom" class="form-control form-control-sm rounded-3">
                </div>
                <div class="col-6 col-md-2">
                    <label for="appDateTo" class="form-label small fw-semibold mb-1">To</label>
                    <input type="date" id="appDateTo" class="form-control form-control-sm rounded-3">
                </div>
                <div class="col-md-1">
                    <button type="button" id="appResetBtn" class="btn btn-sm btn-outline-secondary rounded-3 w-100">Reset</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Desktop Table --}}
    <div class="card border-0 shadow-sm rounded-4 d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 760px;">
                <thead class="table-light">
                    <tr>
                        <th>Applicant</th>
                        <th>Contact</th>
                        <th>Service Area</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="appTableBody">
                    <tr><td colspan="6" class="text-center py-4 text-muted">Loading applications...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-2 px-3" id="appPaginationBar"></div>
    </div>

    {{-- Mobile Compact Cards --}}
    <div class="d-md-none" id="appMobileList"></div>
</div>

{{-- Applicant Details & Review Modal --}}
<div class="modal fade" id="appReviewModal" tabindex="-1" aria-labelledby="modalAppTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom" style="background: #FFFDF5;">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="modalAppTitle">Applicant Review</h5>
                    <div class="small text-secondary" id="modalAppSub"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" id="modalAppBody">Loading...</div>
        </div>
    </div>
</div>

{{-- Reusable Secure Document Preview Modal --}}
<div class="modal fade" id="docPreviewModal" tabindex="-1" aria-labelledby="docPreviewTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="docPreviewTitle">Document Preview</h5>
                    <div class="small text-secondary" id="docPreviewMeta"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light text-center p-3 position-relative" id="docPreviewBody" style="min-height: 320px;">
                <div id="docPreviewLoader" class="py-5 text-muted">Loading secure document...</div>
            </div>
            <div class="modal-footer border-top justify-content-between flex-wrap gap-2">
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="docPrevBtn">← Prev</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="docNextBtn">Next →</button>
                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 d-none" id="docZoomInBtn">Zoom +</button>
                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 d-none" id="docZoomOutBtn">Fit</button>
                </div>
                <div class="d-flex gap-2">
                    <a href="#" id="docDownloadLink" class="btn btn-sm btn-outline-primary rounded-pill px-3">Download</a>
                    <button type="button" class="btn btn-sm btn-dark rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());
    }
    const tbody = document.getElementById('appTableBody');
    const mobileList = document.getElementById('appMobileList');
    const paginationBar = document.getElementById('appPaginationBar');
    let debounceTimer = null;
    let currentPage = 1;
    let currentDocs = [];
    let currentDocIdx = 0;
    let currentZoom = 1;
    let activeAppRequestId = 0;

    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function initials(name) {
        return String(name || 'DP').trim().split(/\s+/).slice(0, 2).map(p => p[0]?.toUpperCase() || '').join('') || 'DP';
    }

    function statusBadge(status, label) {
        const map = {
            submitted: 'bg-primary-subtle text-primary border-primary-subtle',
            under_review: 'bg-info-subtle text-info-emphasis border-info-subtle',
            additional_info_required: 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
            approved: 'bg-success-subtle text-success border-success-subtle',
            rejected: 'bg-danger-subtle text-danger border-danger-subtle',
        };
        return `<span class="badge border rounded-pill px-2 py-1 ${map[status] || 'bg-light text-dark'}">${esc(label)}</span>`;
    }

    function toastMsg(msg, ok = true) {
        if (typeof toastr !== 'undefined') { ok ? toastr.success(msg) : toastr.error(msg); }
        else { console.log(msg); }
    }

    function updateCounts(c) {
        if (!c) return;
        document.getElementById('cntTotal').textContent = c.total ?? 0;
        document.getElementById('cntSubmitted').textContent = c.submitted ?? 0;
        document.getElementById('cntUnderReview').textContent = c.under_review ?? 0;
        document.getElementById('cntInfoReq').textContent = c.additional_info_required ?? 0;
        document.getElementById('cntApproved').textContent = c.approved ?? 0;
        document.getElementById('cntRejected').textContent = c.rejected ?? 0;
    }

    async function loadApplications(page = 1) {
        currentPage = page;
        const params = new URLSearchParams({
            page,
            search: document.getElementById('appSearch').value,
            status: document.getElementById('appStatus').value,
            date_from: document.getElementById('appDateFrom').value,
            date_to: document.getElementById('appDateTo').value,
        });
        const res = await fetch(`{{ route('admin.delivery-person-applications.index') }}?${params}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        if (!json.success) return;

        updateCounts(json.counts);
        const rows = json.data?.data || [];

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No applications match the selected filters.</td></tr>';
            mobileList.innerHTML = '<div class="card border-0 shadow-sm rounded-4 p-4 text-center text-muted small">No applications match the selected filters.</div>';
            paginationBar.innerHTML = '';
            return;
        }

        tbody.innerHTML = rows.map(r => `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="dpa-avatar">${esc(initials(r.full_name))}</span>
                        <div>
                            <div class="fw-semibold text-dark">${esc(r.full_name)}</div>
                            <div class="small text-muted">${esc(r.reference)} · ${esc(r.delivery_method)}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="small fw-semibold">${esc(r.email)}</div>
                    <div class="small text-muted">${esc(r.phone)}</div>
                </td>
                <td class="small">${esc(r.service_area)}</td>
                <td class="small text-muted">${esc(r.submitted_at || '—')}</td>
                <td>${statusBadge(r.status, r.status_label)}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-dark rounded-pill px-3 fw-semibold review-app-btn" data-id="${r.id}">
                        Review Application
                    </button>
                </td>
            </tr>
        `).join('');

        mobileList.innerHTML = rows.map(r => `
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-2">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="dpa-avatar">${esc(initials(r.full_name))}</span>
                        <div>
                            <div class="fw-bold small">${esc(r.full_name)}</div>
                            <div class="text-muted" style="font-size:0.75rem;">${esc(r.reference)}</div>
                        </div>
                    </div>
                    ${statusBadge(r.status, r.status_label)}
                </div>
                <div class="small text-secondary mb-2">${esc(r.email)} · ${esc(r.submitted_at || '—')}</div>
                <button type="button" class="btn btn-sm btn-dark rounded-pill w-100 fw-semibold review-app-btn" data-id="${r.id}">
                    Review Application
                </button>
            </div>
        `).join('');

        const p = json.pagination || {};
        paginationBar.innerHTML = `
            <span class="small text-muted">Total: ${p.total ?? rows.length} · Page ${p.current_page ?? 1} of ${p.last_page ?? 1}</span>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" ${(p.current_page <= 1) ? 'disabled' : ''} onclick="window._dpaPage(${(p.current_page || 1) - 1})">Prev</button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" ${(p.current_page >= p.last_page) ? 'disabled' : ''} onclick="window._dpaPage(${(p.current_page || 1) + 1})">Next</button>
            </div>`;
    }
    window._dpaPage = (p) => loadApplications(p);

    async function openReview(id) {
        const reqId = ++activeAppRequestId;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('appReviewModal'));
        const body = document.getElementById('modalAppBody');
        body.innerHTML = '<div class="text-center py-5 text-muted">Loading applicant details...</div>';
        modal.show();

        const res = await fetch(`/admin/delivery-person-applications/${id}`, { headers: { 'Accept': 'application/json' } });
        if (reqId !== activeAppRequestId) return;
        const json = await res.json();
        const a = json.application;
        currentDocs = a.documents || [];

        document.getElementById('modalAppTitle').textContent = `${a.full_name}`;
        document.getElementById('modalAppSub').innerHTML = `${esc(a.reference)} · Submitted ${esc(a.submitted_at || '—')} · ${statusBadge(a.status, a.status_label)}`;

        const docsHtml = currentDocs.length ? currentDocs.map((d, idx) => `
            <div class="col-6 col-md-3">
                <div class="dpa-doc-card">
                    <div class="dpa-doc-thumb-wrap doc-open-btn" data-idx="${idx}" title="Click to preview">
                        ${d.is_image
                            ? `<img src="${esc(d.preview_url)}" alt="${esc(d.document_type)}" loading="lazy">`
                            : `<div class="text-center"><div class="fw-bold text-danger fs-4">PDF</div><div class="text-muted" style="font-size:0.72rem;">Click to view</div></div>`}
                    </div>
                    <div class="p-2 d-flex flex-column flex-grow-1 justify-content-between">
                        <div>
                            <div class="fw-bold small text-truncate">${esc(d.document_type)}</div>
                            <div class="text-muted text-truncate" style="font-size:0.72rem;">${esc(d.original_name)}</div>
                        </div>
                        <div class="d-flex gap-1 mt-2">
                            <button type="button" class="btn btn-xs btn-sm btn-dark rounded-pill px-2 py-0 flex-grow-1 doc-open-btn" data-idx="${idx}">View</button>
                            <a href="${esc(d.download_url)}" class="btn btn-xs btn-sm btn-outline-secondary rounded-pill px-2 py-0">↓</a>
                        </div>
                    </div>
                </div>
            </div>`).join('') : '<div class="col-12"><div class="p-3 rounded-3 bg-light text-muted small">Document not provided.</div></div>';

        const eventsHtml = (a.events || []).length ? a.events.map(e => `
            <li class="small mb-1">
                <strong>${esc(e.event_type)}</strong> <span class="text-muted">(${esc(e.actor_type)} · ${esc(e.created_at)})</span>
                ${e.notes ? `<div class="text-secondary">${esc(e.notes)}</div>` : ''}
            </li>`).join('') : '<li class="small text-muted">No recorded events.</li>';

        const finalized = ['approved', 'rejected', 'withdrawn'].includes(a.status);

        body.innerHTML = `
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="dpa-detail-card">
                        <div class="fw-bold small mb-2 text-primary">Personal Information</div>
                        <div class="small mb-1"><strong>Name:</strong> ${esc(a.full_name)}</div>
                        <div class="small mb-1"><strong>Email:</strong> ${esc(a.email)}</div>
                        <div class="small mb-1"><strong>Phone:</strong> ${esc(a.phone)}</div>
                        <div class="small"><strong>Date of Birth:</strong> ${esc(a.date_of_birth || 'Not provided')}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="dpa-detail-card">
                        <div class="fw-bold small mb-2 text-success">Location &amp; Delivery</div>
                        <div class="small mb-1"><strong>Address:</strong> ${esc(a.address)}</div>
                        <div class="small mb-1"><strong>Service Area:</strong> ${esc(a.service_area || '—')}</div>
                        <div class="small mb-1"><strong>Method:</strong> ${esc(a.delivery_method)} ${a.vehicle_type ? `(${esc(a.vehicle_type)})` : ''}</div>
                        <div class="small"><strong>Registration:</strong> ${esc(a.vehicle_registration || '—')}</div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="fw-bold small mb-2">Verification Documents (${currentDocs.length})</div>
                <div class="row g-2">${docsHtml}</div>
            </div>

            <div class="dpa-detail-card mb-3">
                <div class="fw-bold small mb-2">Application History</div>
                <ul class="mb-0 ps-3">${eventsHtml}</ul>
            </div>

            <div id="reviewActionAlert" class="alert d-none mb-3"></div>

            ${finalized ? `<div class="alert alert-light border small mb-0">This application is finalized (${esc(a.status_label)}).</div>` : `
            <div class="border-top pt-3">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold" id="btnBeginReview" data-id="${a.id}">Begin Review</button>
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold" id="btnShowApprove">Approve Application</button>
                    <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 fw-semibold" id="btnShowReqInfo">Request More Information</button>
                    <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 fw-semibold" id="btnShowReject">Reject Application</button>
                </div>

                <div id="panelApprove" class="d-none p-3 rounded-4 border border-success-subtle bg-success-subtle mb-2">
                    <p class="small fw-semibold mb-2">This will approve the application and activate the Delivery Person according to the existing eligibility rules.</p>
                    <input type="text" id="approveNotes" class="form-control form-control-sm mb-2" placeholder="Optional internal approval notes...">
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-4 fw-bold" id="confirmApproveBtn" data-id="${a.id}">Confirm Approval</button>
                </div>

                <div id="panelReqInfo" class="d-none p-3 rounded-4 border border-warning-subtle bg-warning-subtle mb-2">
                    <label class="form-label small fw-bold">Message to Applicant <span class="text-danger">*</span></label>
                    <textarea id="reqInfoMsg" class="form-control form-control-sm mb-2" rows="2" placeholder="Describe missing or unclear documents/details..."></textarea>
                    <button type="button" class="btn btn-sm btn-warning rounded-pill px-4 fw-bold" id="confirmReqInfoBtn" data-id="${a.id}">Send Request</button>
                </div>

                <div id="panelReject" class="d-none p-3 rounded-4 border border-danger-subtle bg-danger-subtle mb-2">
                    <label class="form-label small fw-bold">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea id="rejectReason" class="form-control form-control-sm mb-2" rows="2" placeholder="Enter reason for rejection..."></textarea>
                    <button type="button" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold" id="confirmRejectBtn" data-id="${a.id}">Confirm Rejection</button>
                </div>
            </div>`}
        `;
    }

    function openDocPreview(idx) {
        if (!currentDocs.length) return;
        currentDocIdx = (idx + currentDocs.length) % currentDocs.length;
        const d = currentDocs[currentDocIdx];
        currentZoom = 1;

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('docPreviewModal'));
        document.getElementById('docPreviewTitle').textContent = d.document_type;
        document.getElementById('docPreviewMeta').textContent = `${d.original_name} · ${d.uploaded_at || ''}`;
        document.getElementById('docDownloadLink').href = d.download_url;

        const body = document.getElementById('docPreviewBody');
        const zoomInBtn = document.getElementById('docZoomInBtn');
        const zoomOutBtn = document.getElementById('docZoomOutBtn');

        if (d.is_image) {
            zoomInBtn.classList.remove('d-none');
            zoomOutBtn.classList.remove('d-none');
            body.innerHTML = `<div class="py-4 text-muted" id="docPreviewSpinner">Loading image...</div>
                <img src="${esc(d.preview_url)}" id="docFullImage" alt="${esc(d.document_type)}" style="max-width:100%;max-height:68vh;object-fit:contain;border-radius:10px;display:none;transition:transform 0.2s ease;">`;
            const img = document.getElementById('docFullImage');
            img.onload = () => {
                document.getElementById('docPreviewSpinner')?.remove();
                img.style.display = 'inline-block';
            };
            img.onerror = () => {
                body.innerHTML = `<div class="alert alert-warning small">Preview unavailable. <a href="${esc(d.download_url)}">Download authorized document.</a></div>`;
            };
        } else if (d.is_pdf) {
            zoomInBtn.classList.add('d-none');
            zoomOutBtn.classList.add('d-none');
            body.innerHTML = `<iframe src="${esc(d.preview_url)}" title="${esc(d.document_type)}" style="width:100%;height:460px;border:0;border-radius:10px;background:#fff;"></iframe>
                <div class="small text-muted mt-2">If preview is unavailable in your browser, <a href="${esc(d.download_url)}">Download authorized document.</a></div>`;
        } else {
            zoomInBtn.classList.add('d-none');
            zoomOutBtn.classList.add('d-none');
            body.innerHTML = `<div class="alert alert-light border small">Preview unavailable. <a href="${esc(d.download_url)}">Download authorized document.</a></div>`;
        }

        document.getElementById('docPrevBtn').disabled = currentDocs.length <= 1;
        document.getElementById('docNextBtn').disabled = currentDocs.length <= 1;
        modal.show();
    }

    document.getElementById('docPrevBtn').onclick = () => openDocPreview(currentDocIdx - 1);
    document.getElementById('docNextBtn').onclick = () => openDocPreview(currentDocIdx + 1);
    document.getElementById('docZoomInBtn').onclick = () => {
        currentZoom = Math.min(2.5, currentZoom + 0.35);
        const img = document.getElementById('docFullImage');
        if (img) img.style.transform = `scale(${currentZoom})`;
    };
    document.getElementById('docZoomOutBtn').onclick = () => {
        currentZoom = 1;
        const img = document.getElementById('docFullImage');
        if (img) img.style.transform = 'scale(1)';
    };
    document.getElementById('docPreviewModal').addEventListener('hidden.bs.modal', () => {
        document.getElementById('docPreviewBody').innerHTML = '';
    });

    async function sendReviewAction(id, action, payload = {}) {
        const token = getCsrfToken();
        const res = await fetch(`/admin/delivery-person-applications/${id}/${action}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify(Object.assign({}, payload, { _token: token })),
        });
        if (res.status === 419) {
            const msg = 'Your admin session/CSRF token expired. Reloading page...';
            toastMsg(msg, false);
            setTimeout(() => window.location.reload(), 900);
            return;
        }
        const json = await res.json();
        if (!res.ok || !json.success) {
            const alertEl = document.getElementById('reviewActionAlert');
            if (alertEl) {
                alertEl.className = 'alert alert-danger small mb-3';
                alertEl.textContent = json.message || 'Action failed.';
            }
            toastMsg(json.message || 'Action failed.', false);
            return;
        }
        toastMsg(json.message || 'Updated successfully.');
        bootstrap.Modal.getInstance(document.getElementById('appReviewModal'))?.hide();
        loadApplications(currentPage);
    }

    document.addEventListener('click', (e) => {
        const reviewBtn = e.target.closest('.review-app-btn');
        if (reviewBtn) return openReview(reviewBtn.dataset.id);

        const docBtn = e.target.closest('.doc-open-btn');
        if (docBtn) return openDocPreview(Number(docBtn.dataset.idx));

        const kpiCard = e.target.closest('[data-kpi-status]');
        if (kpiCard) {
            document.querySelectorAll('[data-kpi-status]').forEach(c => c.classList.remove('active'));
            kpiCard.classList.add('active');
            document.getElementById('appStatus').value = kpiCard.dataset.kpiStatus;
            loadApplications(1);
        }

        if (e.target.id === 'btnShowApprove') {
            document.getElementById('panelApprove').classList.toggle('d-none');
            document.getElementById('panelReqInfo').classList.add('d-none');
            document.getElementById('panelReject').classList.add('d-none');
        }
        if (e.target.id === 'btnShowReqInfo') {
            document.getElementById('panelReqInfo').classList.toggle('d-none');
            document.getElementById('panelApprove').classList.add('d-none');
            document.getElementById('panelReject').classList.add('d-none');
        }
        if (e.target.id === 'btnShowReject') {
            document.getElementById('panelReject').classList.toggle('d-none');
            document.getElementById('panelApprove').classList.add('d-none');
            document.getElementById('panelReqInfo').classList.add('d-none');
        }
        if (e.target.id === 'btnBeginReview') sendReviewAction(e.target.dataset.id, 'begin-review');
        if (e.target.id === 'confirmApproveBtn') sendReviewAction(e.target.dataset.id, 'approve', { decision_notes: document.getElementById('approveNotes').value });
        if (e.target.id === 'confirmReqInfoBtn') sendReviewAction(e.target.dataset.id, 'request-info', { additional_info_requested: document.getElementById('reqInfoMsg').value });
        if (e.target.id === 'confirmRejectBtn') sendReviewAction(e.target.dataset.id, 'reject', { decision_notes: document.getElementById('rejectReason').value });
    });

    ['appSearch', 'appStatus', 'appDateFrom', 'appDateTo'].forEach(id => {
        document.getElementById(id).addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadApplications(1), 250);
        });
    });
    document.getElementById('appResetBtn').addEventListener('click', () => {
        document.getElementById('appSearch').value = '';
        document.getElementById('appStatus').value = 'review_queue';
        document.getElementById('appDateFrom').value = '';
        document.getElementById('appDateTo').value = '';
        document.querySelectorAll('[data-kpi-status]').forEach(c => c.classList.remove('active'));
        loadApplications(1);
    });

    loadApplications();
})();
</script>
@endsection
