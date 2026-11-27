@extends('admin::layouts.app')

@section('title', 'Tag History')

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
                        Audit Trail
                    </span>
                </div>
                <h4 class="fw-bold mb-1" style="color: #171717;">Tag History</h4>
                <div class="small text-muted">Track every tag generation, allocation, assignment, and handover event.</div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('admin.tags.index') }}" class="btn btn-sm btn-light border bg-white px-3 fw-semibold shadow-sm">
                    <i data-feather="layers" style="width: 14px; height: 14px;" class="me-1"></i>Back to Tag Inventory
                </a>
            </div>
        </div>
    </div>

    @include('admin::tags.partials.nav-pills')

    <!-- Month Navigation & Filter Toolbar -->
    <div class="tm-section-card">
        <div class="card-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="histPrevMonthBtn" data-month="{{ $prevMonth }}" data-year="{{ $prevYear }}">
                        &larr; Prev Month
                    </button>
                    <span class="fw-bold small px-2" id="histMonthHeading">{{ $monthLabel }}</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="histNextMonthBtn" data-month="{{ $nextMonth }}" data-year="{{ $nextYear }}">
                        Next Month &rarr;
                    </button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select id="histMonthSelect" class="form-select form-select-sm" style="width: auto;">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($m === $selectedMonth)>{{ \Illuminate\Support\Carbon::create(2000, $m, 1)->format('F') }}</option>
                        @endfor
                    </select>
                    <select id="histYearSelect" class="form-select form-select-sm" style="width: auto;">
                        @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" @selected($y === $selectedYear)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <form id="tagHistoryFilterForm" method="GET" action="{{ route('admin.tags.history') }}" class="row g-2 align-items-end">
                <input type="hidden" name="month" id="histHiddenMonth" value="{{ $selectedMonth }}">
                <input type="hidden" name="year" id="histHiddenYear" value="{{ $selectedYear }}">
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Tag Code</label>
                    <input type="text" name="tag_code" id="histTagCode" value="{{ $filters['tag_code'] ?? '' }}" class="form-control form-control-sm" placeholder="FG-T-000001">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Booking Ref</label>
                    <input type="text" name="booking_reference" id="histBookingRef" value="{{ $filters['booking_reference'] ?? '' }}" class="form-control form-control-sm" placeholder="FGL-...">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Event Type</label>
                    <select name="event_type" id="histEventType" class="form-select form-select-sm">
                        <option value="">All Events</option>
                        @foreach($eventTypes as $k => $v)
                            <option value="{{ $k }}" @selected(($filters['event_type'] ?? '') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Delivery Person</label>
                    <select name="delivery_person_id" id="histDpId" class="form-select form-select-sm">
                        <option value="">All Delivery Persons</option>
                        @foreach($deliveryPersons as $dp)
                            <option value="{{ $dp->id }}" @selected((int) ($filters['delivery_person_id'] ?? 0) === $dp->id)>{{ $dp->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Store</label>
                    <select name="store_id" id="histStoreId" class="form-select form-select-sm">
                        <option value="">All Stores</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" @selected((int) ($filters['store_id'] ?? 0) === $st->id)>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2 d-grid">
                    <button type="button" id="histResetBtn" class="btn btn-outline-secondary btn-sm">Reset</button>
                </div>
            </form>
        </div>

        <div class="card-body p-0" id="tagHistoryTableWrapper">
            @include('admin::tags.partials.history-table', compact('events'))
        </div>
    </div>
</div>

{{-- History Event Detail Modal --}}
<div class="modal fade" id="historyDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-light border-bottom py-2 px-3">
                <h6 class="modal-title fw-bold mb-0">Tag Event Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 small" id="historyDetailBody"></div>
            <div class="modal-footer bg-light border-top py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('tagHistoryFilterForm');
    const wrapper = document.getElementById('tagHistoryTableWrapper');
    const hiddenMonth = document.getElementById('histHiddenMonth');
    const hiddenYear = document.getElementById('histHiddenYear');
    const monthSelect = document.getElementById('histMonthSelect');
    const yearSelect = document.getElementById('histYearSelect');
    const prevBtn = document.getElementById('histPrevMonthBtn');
    const nextBtn = document.getElementById('histNextMonthBtn');
    const heading = document.getElementById('histMonthHeading');
    let timer = null;
    let ctrl = null;
    let reqSeq = 0;

    async function loadHistory(page = 1) {
        if (ctrl) ctrl.abort();
        ctrl = new AbortController();
        const mySeq = ++reqSeq;
        wrapper.classList.add('tm-loading-overlay');

        const params = new URLSearchParams(new FormData(form));
        params.set('page', String(page));

        try {
            const res = await fetch(`${form.action}?${params.toString()}`, {
                signal: ctrl.signal,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (mySeq !== reqSeq) return;
            if (res.ok && data.success) {
                wrapper.innerHTML = data.table_html;
                if (data.month_label) heading.textContent = data.month_label;
                if (data.month) {
                    hiddenMonth.value = String(data.month);
                    monthSelect.value = String(data.month);
                }
                if (data.year) {
                    hiddenYear.value = String(data.year);
                    yearSelect.value = String(data.year);
                }
                if (data.prev_month) {
                    prevBtn.dataset.month = String(data.prev_month);
                    prevBtn.dataset.year = String(data.prev_year);
                }
                if (data.next_month) {
                    nextBtn.dataset.month = String(data.next_month);
                    nextBtn.dataset.year = String(data.next_year);
                }
                if (window.feather) feather.replace();
            }
        } catch (err) {
            if (err.name !== 'AbortError' && window.tmToast) {
                window.tmToast('Failed to load tag history.', 'error');
            }
        } finally {
            if (mySeq === reqSeq) wrapper.classList.remove('tm-loading-overlay');
        }
    }

    prevBtn?.addEventListener('click', () => {
        hiddenMonth.value = prevBtn.dataset.month;
        hiddenYear.value = prevBtn.dataset.year;
        loadHistory(1);
    });
    nextBtn?.addEventListener('click', () => {
        hiddenMonth.value = nextBtn.dataset.month;
        hiddenYear.value = nextBtn.dataset.year;
        loadHistory(1);
    });
    monthSelect?.addEventListener('change', () => {
        hiddenMonth.value = monthSelect.value;
        loadHistory(1);
    });
    yearSelect?.addEventListener('change', () => {
        hiddenYear.value = yearSelect.value;
        loadHistory(1);
    });

    ['histTagCode', 'histBookingRef'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => loadHistory(1), 300);
        });
    });
    ['histEventType', 'histDpId', 'histStoreId'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => loadHistory(1));
    });
    document.getElementById('histResetBtn')?.addEventListener('click', () => {
        form.reset();
        hiddenMonth.value = String({{ (int) now()->month }});
        hiddenYear.value = String({{ (int) now()->year }});
        loadHistory(1);
    });

    document.addEventListener('click', (e) => {
        const a = e.target.closest('.fg-history-pagination a');
        if (a) {
            e.preventDefault();
            const p = new URL(a.href, window.location.origin).searchParams.get('page') || 1;
            loadHistory(parseInt(p, 10));
            return;
        }
        const detBtn = e.target.closest('.btn-history-detail');
        if (detBtn) {
            const d = JSON.parse(detBtn.dataset.detail || '{}');
            document.getElementById('historyDetailBody').innerHTML = `
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" style="width: 38%;">Tag Code</th><td><code class="fw-bold">${d.tag_code}</code></td></tr>
                    <tr><th class="text-muted">Event Type</th><td><code>${d.event_type}</code></td></tr>
                    <tr><th class="text-muted">Previous Status</th><td>${d.previous_status}</td></tr>
                    <tr><th class="text-muted">New Status</th><td><strong>${d.new_status}</strong></td></tr>
                    <tr><th class="text-muted">Actor</th><td>${d.actor}</td></tr>
                    <tr><th class="text-muted">Timestamp</th><td>${d.timestamp}</td></tr>
                    <tr><th class="text-muted">Related Booking</th><td>${d.booking_ref}</td></tr>
                    <tr><th class="text-muted">Delivery Person</th><td>${d.dp_name}</td></tr>
                    <tr><th class="text-muted">Store</th><td>${d.store_name}</td></tr>
                    <tr><th class="text-muted">Notes</th><td>${d.reason}</td></tr>
                </table>
            `;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('historyDetailModal')).show();
        }
    });
})();
</script>
@endpush
