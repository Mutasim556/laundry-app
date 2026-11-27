@extends('delivery-person-portal.layouts.app')
@section('title', $heading)
@section('page_title', $heading)

@push('styles')
<style>
    .dp-bk-card {
        animation: dpBkFadeUp 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
        transition: transform 0.22s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.22s ease;
    }
    .dp-bk-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }
    .dp-bk-summary-card {
        border-radius: 14px;
        padding: 0.75rem 0.9rem;
        border: 1px solid #E2E8F0;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .dp-bk-summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.06);
    }
    #dpBookingsResultsWrap.is-loading {
        opacity: 0.55;
        pointer-events: none;
        transition: opacity 0.18s ease;
    }
    @keyframes dpBkFadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .dp-bk-card { animation: none !important; }
        .dp-bk-card, .dp-bk-summary-card { transition: none !important; }
    }
</style>
@endpush

@section('content')
<!-- Month Navigation & Heading -->
<div class="dp-card p-3 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border-color: #FDE68A;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0 text-dark" id="dpBkMonthHeading">{{ $monthLabel }}</h5>
                <span id="dpBkLoadingSpinner" class="spinner-border spinner-border-sm text-warning d-none" role="status" aria-hidden="true"></span>
            </div>
            <div class="small text-muted">Filtered by scheduled operational date (fallback: assigned/created date).</div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1" id="dpBkPrevMonthBtn" data-month="{{ $prevMonth }}" data-year="{{ $prevYear }}" aria-label="Previous Month">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                <span class="d-none d-sm-inline">Prev</span>
            </button>

            <select id="dpBkMonthSelect" class="form-select form-select-sm" style="width: auto;" aria-label="Select Month">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($selectedMonth === $m)>{{ \Carbon\Carbon::create(2026, $m, 1)->format('F') }}</option>
                @endfor
            </select>

            <select id="dpBkYearSelect" class="form-select form-select-sm" style="width: auto;" aria-label="Select Year">
                @for($y = $currentYear - 2; $y <= $currentYear + 1; $y++)
                    <option value="{{ $y }}" @selected($selectedYear === $y)>{{ $y }}</option>
                @endfor
            </select>

            <button type="button" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1" id="dpBkNextMonthBtn" data-month="{{ $nextMonth }}" data-year="{{ $nextYear }}" aria-label="Next Month">
                <span class="d-none d-sm-inline">Next</span>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>

            <button type="button" class="btn btn-sm dp-btn-yellow px-3" id="dpBkCurrentMonthBtn">Current Month</button>
        </div>
    </div>
</div>

<!-- Monthly Summary Cards -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md">
        <div class="dp-bk-summary-card bg-white">
            <div class="small text-muted fw-semibold">Total Jobs</div>
            <div class="fs-5 fw-bold text-dark" id="dpSumTotal">{{ $summary['total'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="dp-bk-summary-card" style="background:#FFF8E5; border-color:#FDE68A;">
            <div class="small fw-semibold text-warning-emphasis">Collection Jobs</div>
            <div class="fs-5 fw-bold text-dark" id="dpSumCollection">{{ $summary['collection'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="dp-bk-summary-card" style="background:#EFF6FF; border-color:#BFDBFE;">
            <div class="small fw-semibold text-primary">Final Delivery Jobs</div>
            <div class="fs-5 fw-bold text-dark" id="dpSumFinal">{{ $summary['final_delivery'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md">
        <div class="dp-bk-summary-card" style="background:#ECFDF5; border-color:#A7F3D0;">
            <div class="small fw-semibold text-success">Completed Jobs</div>
            <div class="fs-5 fw-bold text-dark" id="dpSumCompleted">{{ $summary['completed'] }}</div>
        </div>
    </div>
    <div class="col-12 col-md">
        <div class="dp-bk-summary-card" style="background:#F5F3FF; border-color:#DDD6FE;">
            <div class="small fw-semibold" style="color:#6D28D9;">Pending / Active</div>
            <div class="fs-5 fw-bold text-dark" id="dpSumPending">{{ $summary['pending'] }}</div>
        </div>
    </div>
</div>

<!-- Compact AJAX Filters Toolbar -->
<div class="dp-card p-3 mb-3">
    <form id="dpBkFilterForm" method="GET" action="{{ url()->current() }}" class="row g-2 align-items-center">
        <div class="col-12 col-lg-4">
            <input type="text" id="dpBkSearchInput" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search booking ref, job ID, customer, postcode, store...">
        </div>
        @if($filterType === null)
            <div class="col-6 col-md-3 col-lg-2">
                <select id="dpBkJobTypeSelect" name="job_type" class="form-select form-select-sm">
                    <option value="">All Jobs</option>
                    <option value="{{ \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION }}" @selected($jobTypeFilter === \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)>Collection Jobs</option>
                    <option value="{{ \Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY }}" @selected($jobTypeFilter === \Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY)>Final Delivery Jobs</option>
                </select>
            </div>
        @endif
        <div class="col-6 col-md-3 col-lg-2">
            <select id="dpBkStatusSelect" name="status_filter" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                @if($filterType === \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)
                    <option value="pending" @selected($statusFilter === 'pending')>Pending</option>
                    <option value="active" @selected($statusFilter === 'active')>Active</option>
                    <option value="completed" @selected($statusFilter === 'completed')>Completed</option>
                @elseif($filterType === \Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY)
                    <option value="awaiting_store" @selected($statusFilter === 'awaiting_store')>Awaiting Store Completion</option>
                    <option value="awaiting_request" @selected($statusFilter === 'awaiting_request')>Awaiting Pickup Request</option>
                    <option value="pickup_requested" @selected($statusFilter === 'pickup_requested')>Pickup Requested</option>
                    <option value="accepted" @selected($statusFilter === 'accepted')>Accepted</option>
                    <option value="on_delivery" @selected($statusFilter === 'on_delivery')>On Delivery</option>
                    <option value="declined" @selected($statusFilter === 'declined')>Declined</option>
                    <option value="completed" @selected($statusFilter === 'completed')>Completed</option>
                @else
                    <option value="pending" @selected($statusFilter === 'pending')>Pending Collection</option>
                    <option value="active" @selected($statusFilter === 'active')>Active Collection</option>
                    <option value="awaiting_store" @selected($statusFilter === 'awaiting_store')>Awaiting Store Completion</option>
                    <option value="awaiting_request" @selected($statusFilter === 'awaiting_request')>Awaiting Pickup Request</option>
                    <option value="pickup_requested" @selected($statusFilter === 'pickup_requested')>Pickup Requested</option>
                    <option value="accepted" @selected($statusFilter === 'accepted')>Accepted</option>
                    <option value="on_delivery" @selected($statusFilter === 'on_delivery')>On Delivery</option>
                    <option value="declined" @selected($statusFilter === 'declined')>Declined</option>
                    <option value="completed" @selected($statusFilter === 'completed')>Completed</option>
                @endif
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <select id="dpBkSortSelect" name="sort" class="form-select form-select-sm">
                <option value="scheduled_desc" @selected($sort === 'scheduled_desc')>Scheduled: Newest</option>
                <option value="scheduled_asc" @selected($sort === 'scheduled_asc')>Scheduled: Oldest</option>
                <option value="assigned_desc" @selected($sort === 'assigned_desc')>Recently Assigned</option>
                <option value="updated_desc" @selected($sort === 'updated_desc')>Recently Updated</option>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2 d-flex gap-2 justify-content-end">
            <button type="button" id="dpBkResetBtn" class="btn btn-sm btn-outline-secondary w-100">Reset Filters</button>
        </div>
    </form>
</div>

<div id="dpBkAjaxError" class="alert alert-danger d-none small py-2 mb-3"></div>

<div id="dpBookingsResultsWrap">
    @include('delivery-person-portal.bookings.partials.list')
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = @json(url()->current());
    const currentMonthDefault = {{ $currentMonth }};
    const currentYearDefault = {{ $currentYear }};

    const monthSelect = document.getElementById('dpBkMonthSelect');
    const yearSelect = document.getElementById('dpBkYearSelect');
    const prevBtn = document.getElementById('dpBkPrevMonthBtn');
    const nextBtn = document.getElementById('dpBkNextMonthBtn');
    const currentMonthBtn = document.getElementById('dpBkCurrentMonthBtn');
    const searchInput = document.getElementById('dpBkSearchInput');
    const jobTypeSelect = document.getElementById('dpBkJobTypeSelect');
    const statusSelect = document.getElementById('dpBkStatusSelect');
    const sortSelect = document.getElementById('dpBkSortSelect');
    const resetBtn = document.getElementById('dpBkResetBtn');
    const filterForm = document.getElementById('dpBkFilterForm');
    const resultsWrap = document.getElementById('dpBookingsResultsWrap');
    const headingEl = document.getElementById('dpBkMonthHeading');
    const spinnerEl = document.getElementById('dpBkLoadingSpinner');
    const errorBox = document.getElementById('dpBkAjaxError');

    let activeController = null;
    let requestSeq = 0;
    let debounceTimer = null;

    function buildParams(page = 1) {
        const params = new URLSearchParams();
        params.set('month', monthSelect?.value || String(currentMonthDefault));
        params.set('year', yearSelect?.value || String(currentYearDefault));
        if (jobTypeSelect && jobTypeSelect.value) params.set('job_type', jobTypeSelect.value);
        if (statusSelect && statusSelect.value) params.set('status_filter', statusSelect.value);
        if (searchInput && searchInput.value.trim()) params.set('search', searchInput.value.trim());
        if (sortSelect && sortSelect.value) params.set('sort', sortSelect.value);
        if (page > 1) params.set('page', String(page));
        return params;
    }

    async function fetchBookings(page = 1) {
        if (activeController) activeController.abort();
        activeController = new AbortController();
        const mySeq = ++requestSeq;

        const params = buildParams(page);
        const reqUrl = `${baseUrl}?${params.toString()}`;

        resultsWrap?.classList.add('is-loading');
        spinnerEl?.classList.remove('d-none');
        errorBox?.classList.add('d-none');

        try {
            const res = await fetch(reqUrl, {
                signal: activeController.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (mySeq !== requestSeq) return;
            if (!res.ok || data.results_html === undefined) {
                throw new Error(data.message || 'Unable to load bookings.');
            }

            resultsWrap.innerHTML = data.results_html;
            if (headingEl && data.month_label) headingEl.textContent = data.month_label;
            if (monthSelect && data.month) monthSelect.value = String(data.month);
            if (yearSelect && data.year) yearSelect.value = String(data.year);
            if (prevBtn) {
                prevBtn.dataset.month = String(data.prev_month);
                prevBtn.dataset.year = String(data.prev_year);
            }
            if (nextBtn) {
                nextBtn.dataset.month = String(data.next_month);
                nextBtn.dataset.year = String(data.next_year);
            }
            if (data.summary) {
                document.getElementById('dpSumTotal').textContent = data.summary.total ?? 0;
                document.getElementById('dpSumCollection').textContent = data.summary.collection ?? 0;
                document.getElementById('dpSumFinal').textContent = data.summary.final_delivery ?? 0;
                document.getElementById('dpSumCompleted').textContent = data.summary.completed ?? 0;
                document.getElementById('dpSumPending').textContent = data.summary.pending ?? 0;
            }
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, '', reqUrl);
            }
        } catch (err) {
            if (err.name === 'AbortError' || mySeq !== requestSeq) return;
            if (errorBox) {
                errorBox.textContent = err.message || 'Failed to load booking jobs. Please try again.';
                errorBox.classList.remove('d-none');
            }
        } finally {
            if (mySeq === requestSeq) {
                resultsWrap?.classList.remove('is-loading');
                spinnerEl?.classList.add('d-none');
            }
        }
    }

    prevBtn?.addEventListener('click', () => {
        if (monthSelect) monthSelect.value = prevBtn.dataset.month || String(currentMonthDefault);
        if (yearSelect) yearSelect.value = prevBtn.dataset.year || String(currentYearDefault);
        fetchBookings(1);
    });

    nextBtn?.addEventListener('click', () => {
        if (monthSelect) monthSelect.value = nextBtn.dataset.month || String(currentMonthDefault);
        if (yearSelect) yearSelect.value = nextBtn.dataset.year || String(currentYearDefault);
        fetchBookings(1);
    });

    currentMonthBtn?.addEventListener('click', () => {
        if (monthSelect) monthSelect.value = String(currentMonthDefault);
        if (yearSelect) yearSelect.value = String(currentYearDefault);
        fetchBookings(1);
    });

    [monthSelect, yearSelect, jobTypeSelect, statusSelect, sortSelect].forEach((el) => {
        el?.addEventListener('change', () => fetchBookings(1));
    });

    searchInput?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchBookings(1), 300);
    });

    filterForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        fetchBookings(1);
    });

    resetBtn?.addEventListener('click', () => {
        if (monthSelect) monthSelect.value = String(currentMonthDefault);
        if (yearSelect) yearSelect.value = String(currentYearDefault);
        if (jobTypeSelect) jobTypeSelect.value = '';
        if (statusSelect) statusSelect.value = '';
        if (searchInput) searchInput.value = '';
        if (sortSelect) sortSelect.value = 'scheduled_desc';
        fetchBookings(1);
    });

    document.addEventListener('click', (e) => {
        const link = e.target.closest('.dp-bk-pagination a');
        if (!link) return;
        e.preventDefault();
        const urlObj = new URL(link.href, window.location.origin);
        const page = parseInt(urlObj.searchParams.get('page') || '1', 10);
        fetchBookings(isNaN(page) ? 1 : page);
    });
});
</script>
@endsection
