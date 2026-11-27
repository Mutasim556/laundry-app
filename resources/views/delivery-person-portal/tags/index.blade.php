@extends('delivery-person-portal.layouts.app')

@section('title', 'My Laundry Tags')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-1">My Allocated Laundry Tags</h4>
        <div class="small text-muted">Physical QR hanging tags assigned to you by Admin for customer collection and Store handover.</div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-4">
        <div class="dp-card p-3">
            <div class="small text-muted">Allocated Unused</div>
            <div class="fs-4 fw-bold text-primary" id="dpSumAvailable">{{ $summary['available_to_use'] }}</div>
        </div>
    </div>
    <div class="col-4">
        <div class="dp-card p-3">
            <div class="small text-muted">Active On Bookings</div>
            <div class="fs-4 fw-bold text-info" id="dpSumActive">{{ $summary['active_on_bookings'] }}</div>
        </div>
    </div>
    <div class="col-4">
        <div class="dp-card p-3">
            <div class="small text-muted">Total In Your Possession</div>
            <div class="fs-4 fw-bold text-dark" id="dpSumTotal">{{ $summary['total_allocated'] }}</div>
        </div>
    </div>
</div>

<div class="dp-card p-3 mb-3">
    <form id="dpTagFilterForm" action="{{ route('delivery-person.tags.index') }}" class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small fw-semibold mb-1">Search Tag Code / Booking Ref</label>
            <input type="text" name="search" id="dpTagSearch" class="form-control form-control-sm" placeholder="FG-T-000001 or FGL-...">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select name="status" id="dpTagStatus" class="form-select form-select-sm">
                <option value="">All My Tags</option>
                <option value="allocated">Available To Use</option>
                <option value="assigned">Assigned To Booking</option>
                <option value="at_store">At Store</option>
                <option value="in_final_delivery">In Final Delivery</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="button" id="dpTagResetBtn" class="btn btn-outline-secondary btn-sm w-100">Reset Filters</button>
        </div>
    </form>
</div>

<div class="dp-card overflow-hidden">
    <div id="dpTagsTableWrapper">
        @include('delivery-person-portal.tags.partials.tags-table', compact('tags'))
    </div>
</div>

<div class="modal fade" id="dpTagExceptionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="dpTagExceptionForm" class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Report Lost / Damaged Tag — <span id="dpExcCode"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Condition</label>
                    <select name="status" class="form-select form-select-sm" required>
                        <option value="lost">Lost</option>
                        <option value="damaged">Damaged / Unreadable</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-semibold">Reason</label>
                    <textarea name="reason" rows="2" class="form-control form-control-sm" required minlength="3" placeholder="Briefly describe what happened..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger btn-sm">Report Issue</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('dpTagFilterForm');
    const wrapper = document.getElementById('dpTagsTableWrapper');
    let timer = null;

    async function loadDpTags(page = 1) {
        const params = new URLSearchParams(new FormData(form));
        params.set('page', String(page));
        const res = await fetch(`${form.action}?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            wrapper.innerHTML = data.table_html;
            if (data.summary) {
                document.getElementById('dpSumAvailable').textContent = data.summary.available_to_use;
                document.getElementById('dpSumActive').textContent = data.summary.active_on_bookings;
                document.getElementById('dpSumTotal').textContent = data.summary.total_allocated;
            }
        }
    }

    document.getElementById('dpTagSearch')?.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => loadDpTags(1), 300);
    });
    document.getElementById('dpTagStatus')?.addEventListener('change', () => loadDpTags(1));
    document.getElementById('dpTagResetBtn')?.addEventListener('click', () => {
        form.reset();
        loadDpTags(1);
    });

    document.addEventListener('click', (e) => {
        const a = e.target.closest('.dp-tags-pagination a');
        if (a) {
            e.preventDefault();
            const p = new URL(a.href, window.location.origin).searchParams.get('page') || 1;
            loadDpTags(parseInt(p, 10));
            return;
        }
        const excBtn = e.target.closest('.btn-dp-report-tag');
        if (excBtn) {
            const excForm = document.getElementById('dpTagExceptionForm');
            excForm.dataset.url = excBtn.dataset.url;
            excForm.reset();
            document.getElementById('dpExcCode').textContent = excBtn.dataset.code;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('dpTagExceptionModal')).show();
        }
    });

    document.getElementById('dpTagExceptionForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const res = await fetch(this.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: new FormData(this),
        });
        const data = await res.json();
        if (res.ok && data.success) {
            bootstrap.Modal.getInstance(document.getElementById('dpTagExceptionModal'))?.hide();
            loadDpTags(1);
        } else {
            alert(data.message || 'Unable to report tag exception.');
        }
    });
})();
</script>
@endpush
