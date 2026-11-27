@extends('admin::layouts.app')

@section('title', 'Tag Inventory')

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
                        Live Inventory
                    </span>
                </div>
                <h4 class="fw-bold mb-1" style="color: #171717;">Tag Inventory</h4>
                <div class="small text-muted">Manage and track generated Fresh Go laundry tags across Admin stock, Delivery Persons, Bookings, and Exceptions.</div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" id="btnPrintSelected" class="btn btn-sm btn-light border bg-white px-3 fw-semibold shadow-sm" disabled>
                    <i data-feather="printer" style="width: 14px; height: 14px;" class="me-1"></i>Reprint Selected
                </button>
                <a href="{{ route('admin.tags.allocations.index') }}" class="btn btn-sm btn-light border bg-white px-3 fw-semibold shadow-sm">
                    <i data-feather="user-plus" style="width: 14px; height: 14px;" class="me-1"></i>Assign to Delivery Person
                </a>
                <a href="{{ route('admin.tags.generate') }}" class="btn btn-sm btn-warning px-3 fw-semibold shadow-sm">
                    <i data-feather="plus-square" style="width: 14px; height: 14px;" class="me-1"></i>Generate Tags
                </a>
            </div>
        </div>
    </div>

    @include('admin::tags.partials.nav-pills')

    <!-- Summary KPI Cards -->
    <div class="row g-2 g-md-3 mb-3">
        @php
            $cards = [
                ['label' => 'Total Tags', 'key' => 'total', 'bar' => '#171717', 'bg' => '#F1F5F9', 'fg' => '#171717', 'icon' => 'hash'],
                ['label' => 'Available Tags', 'key' => 'available', 'bar' => '#16A34A', 'bg' => '#ECFDF5', 'fg' => '#16A34A', 'icon' => 'check-circle'],
                ['label' => 'Allocated Tags', 'key' => 'allocated', 'bar' => '#3B82F6', 'bg' => '#EFF6FF', 'fg' => '#2563EB', 'icon' => 'user-check'],
                ['label' => 'In Use', 'key' => 'in_use', 'bar' => '#8B5CF6', 'bg' => '#F5F3FF', 'fg' => '#7C3AED', 'icon' => 'activity'],
                ['label' => 'Lost / Damaged', 'key' => 'lost_damaged', 'bar' => '#EF4444', 'bg' => '#FEF2F2', 'fg' => '#DC2626', 'icon' => 'alert-triangle'],
            ];
        @endphp
        @foreach($cards as $c)
            <div class="col-6 col-md-4 col-lg">
                <div class="tm-kpi-card">
                    <div class="tm-kpi-bar" style="background: {{ $c['bar'] }};"></div>
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <div class="small text-muted fw-semibold">{{ $c['label'] }}</div>
                            <div class="fs-4 fw-bold" style="color: {{ $c['fg'] }};" data-summary-key="{{ $c['key'] }}">{{ $summary[$c['key']] }}</div>
                        </div>
                        <span class="tm-kpi-icon" style="background: {{ $c['bg'] }}; color: {{ $c['fg'] }};">
                            <i data-feather="{{ $c['icon'] }}" style="width: 18px; height: 18px;"></i>
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Compact Filter Toolbar & Table Card -->
    <div class="tm-section-card">
        <div class="card-header">
            <form id="tagInventoryFilterForm" method="GET" action="{{ route('admin.tags.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Search Tag / Booking / DP</label>
                    <input type="text" name="search" id="fltSearch" value="{{ $filters['search'] ?? '' }}" class="form-control form-control-sm" placeholder="FG-T-000001 or ref...">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Status</label>
                    <select name="status" id="fltStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Batch</label>
                    <select name="batch_id" id="fltBatch" class="form-select form-select-sm">
                        <option value="">All Batches</option>
                        @foreach($batches as $b)
                            <option value="{{ $b->id }}" @selected((int) ($filters['batch_id'] ?? 0) === $b->id)>{{ $b->batch_reference }} ({{ $b->quantity }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Delivery Person</label>
                    <select name="delivery_person_id" id="fltDp" class="form-select form-select-sm">
                        <option value="">Any Delivery Person</option>
                        @foreach($deliveryPersons as $dp)
                            <option value="{{ $dp->id }}" @selected((int) ($filters['delivery_person_id'] ?? 0) === $dp->id)>{{ $dp->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Sort</label>
                    <select name="sort" id="fltSort" class="form-select form-select-sm">
                        <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest First</option>
                        <option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Oldest First</option>
                        <option value="code_asc" @selected(($filters['sort'] ?? '') === 'code_asc')>Code A-Z</option>
                        <option value="code_desc" @selected(($filters['sort'] ?? '') === 'code_desc')>Code Z-A</option>
                    </select>
                </div>
                <div class="col-12 col-md-1 d-grid">
                    <button type="button" id="btnResetFilters" class="btn btn-outline-secondary btn-sm">Reset</button>
                </div>
            </form>
        </div>
        <div class="card-body p-0" id="tagInventoryTableWrapper">
            @include('admin::tags.partials.inventory-table', compact('tags'))
        </div>
    </div>
</div>

{{-- Read-only Tag Details Modal --}}
<div class="modal fade" id="tagDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-light border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold mb-0">Laundry Tag Details — <span id="mdlTagCodeTitle" class="text-primary"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3" id="tagDetailsModalBody">
                <div class="text-center py-4 text-muted">Loading...</div>
            </div>
            <div class="modal-footer bg-light border-top py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Exception / Retire Modal --}}
<div class="modal fade" id="tagExceptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="tagExceptionForm" class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-light border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold mb-0">Report Exception / Retire — <span id="excTagCode" class="text-danger"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div id="excActiveWarning" class="alert alert-warning small py-2 d-none">
                    This tag is currently attached to an active booking. Submitting will release the lock only if "Allow Release" is checked.
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Target Status</label>
                    <select name="status" class="form-select form-select-sm" required>
                        <option value="lost">Lost</option>
                        <option value="damaged">Damaged</option>
                        <option value="retired">Retired</option>
                        <option value="voided">Voided</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Reason (Required)</label>
                    <textarea name="reason" rows="2" class="form-control form-control-sm" required minlength="3" placeholder="Explain why this tag is being marked lost/damaged/retired..."></textarea>
                </div>
                <div class="form-check d-none" id="excAllowReleaseWrap">
                    <input class="form-check-input" type="checkbox" name="allow_release" value="1" id="excAllowRelease">
                    <label class="form-check-label small" for="excAllowRelease">Release from active booking assignment</label>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2 px-3">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-danger fw-semibold">Update Tag Status</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const filterForm = document.getElementById('tagInventoryFilterForm');
    const wrapper = document.getElementById('tagInventoryTableWrapper');
    const btnPrintSel = document.getElementById('btnPrintSelected');
    let timer = null;
    let ctrl = null;
    let reqSeq = 0;

    async function loadInventory(page = 1) {
        if (ctrl) ctrl.abort();
        ctrl = new AbortController();
        const mySeq = ++reqSeq;
        wrapper.classList.add('tm-loading-overlay');

        const params = new URLSearchParams(new FormData(filterForm));
        params.set('page', String(page));

        try {
            const res = await fetch(`${filterForm.action}?${params.toString()}`, {
                signal: ctrl.signal,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (mySeq !== reqSeq) return;
            if (res.ok && data.success) {
                wrapper.innerHTML = data.table_html;
                Object.entries(data.summary || {}).forEach(([k, v]) => {
                    const el = document.querySelector(`[data-summary-key="${k}"]`);
                    if (el) el.textContent = v;
                });
                updateSelectedPrintState();
                if (window.feather) feather.replace();
            }
        } catch (err) {
            if (err.name !== 'AbortError' && window.tmToast) {
                window.tmToast('Failed to load tag inventory.', 'error');
            }
        } finally {
            if (mySeq === reqSeq) wrapper.classList.remove('tm-loading-overlay');
        }
    }

    function updateSelectedPrintState() {
        const checked = document.querySelectorAll('.fg-tag-checkbox:checked');
        btnPrintSel.disabled = checked.length === 0;
        btnPrintSel.innerHTML = `<i data-feather="printer" style="width: 14px; height: 14px;" class="me-1"></i>` + (checked.length > 0 ? `Reprint Selected (${checked.length})` : 'Reprint Selected');
        if (window.feather) feather.replace();
    }

    document.getElementById('fltSearch')?.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => loadInventory(1), 300);
    });
    ['fltStatus', 'fltBatch', 'fltDp', 'fltSort'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => loadInventory(1));
    });
    document.getElementById('btnResetFilters')?.addEventListener('click', () => {
        filterForm.reset();
        loadInventory(1);
    });

    btnPrintSel?.addEventListener('click', () => {
        const ids = Array.from(document.querySelectorAll('.fg-tag-checkbox:checked')).map(c => c.value);
        if (!ids.length) return;
        const url = new URL(`{{ route('admin.tags.print-preview') }}`, window.location.origin);
        ids.forEach(id => url.searchParams.append('tag_ids[]', id));
        window.location.href = url.toString();
    });

    document.addEventListener('change', (e) => {
        if (e.target.id === 'selectAllTags') {
            document.querySelectorAll('.fg-tag-checkbox').forEach(cb => { cb.checked = e.target.checked; });
            updateSelectedPrintState();
        } else if (e.target.classList.contains('fg-tag-checkbox')) {
            updateSelectedPrintState();
        }
    });

    document.addEventListener('click', async (e) => {
        const pageLink = e.target.closest('.fg-ajax-pagination a');
        if (pageLink) {
            e.preventDefault();
            const p = new URL(pageLink.href, window.location.origin).searchParams.get('page') || 1;
            loadInventory(parseInt(p, 10));
            return;
        }

        const viewBtn = e.target.closest('.btn-view-tag-details');
        if (viewBtn) {
            const modalEl = document.getElementById('tagDetailsModal');
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            document.getElementById('tagDetailsModalBody').innerHTML = '<div class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading tag details...</div>';
            bsModal.show();
            const res = await fetch(viewBtn.dataset.url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.success) {
                document.getElementById('mdlTagCodeTitle').textContent = data.tag.tag_code;
                document.getElementById('tagDetailsModalBody').innerHTML = data.html;
            }
            return;
        }

        const excBtn = e.target.closest('.btn-open-exception');
        if (excBtn) {
            const form = document.getElementById('tagExceptionForm');
            form.dataset.url = excBtn.dataset.url;
            form.reset();
            document.getElementById('excTagCode').textContent = excBtn.dataset.code;
            const isActive = excBtn.dataset.active === '1';
            document.getElementById('excActiveWarning').classList.toggle('d-none', !isActive);
            document.getElementById('excAllowReleaseWrap').classList.toggle('d-none', !isActive);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('tagExceptionModal')).show();
            return;
        }

        const retBtn = e.target.closest('.btn-open-return');
        if (retBtn) {
            const reason = prompt(`Return ${retBtn.dataset.code} back to Available Admin Stock. Reason:`);
            if (!reason || reason.trim().length < 3) return;
            const res = await fetch(retBtn.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ reason: reason.trim() }),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                if (window.tmToast) window.tmToast(data.message, 'success');
                loadInventory(1);
            } else if (window.tmToast) {
                window.tmToast(data.message || 'Unable to return tag.', 'error');
            }
        }
    });

    document.getElementById('tagExceptionForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const form = this;
        const res = await fetch(form.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: new FormData(form),
        });
        const data = await res.json();
        if (res.ok && data.success) {
            bootstrap.Modal.getInstance(document.getElementById('tagExceptionModal'))?.hide();
            if (window.tmToast) window.tmToast(data.message, 'success');
            loadInventory(1);
        } else if (window.tmToast) {
            window.tmToast(data.message || 'Unable to update tag.', 'error');
        }
    });
})();
</script>
@endpush
