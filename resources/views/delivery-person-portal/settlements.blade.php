@extends('delivery-person-portal.layouts.app')
@section('title', 'Admin Settlements')
@section('page_title', 'My Admin Cash Settlements')

@push('styles')
<style>
    .dp-st-anim {
        animation: dpStFadeUp 0.48s cubic-bezier(0.22, 1, 0.36, 1) both;
        transition: transform 0.22s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.22s ease;
    }
    .dp-st-anim:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07); }
    .dp-st-icon {
        width: 38px; height: 38px; border-radius: 11px;
        display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
        transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .dp-st-anim:hover .dp-st-icon { transform: scale(1.1) rotate(-6deg); }
    #dpStTableWrap.is-loading { opacity: 0.55; pointer-events: none; transition: opacity 0.18s ease; }
    @keyframes dpStFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    @media (prefers-reduced-motion: reduce) {
        .dp-st-anim { animation: none !important; }
        .dp-st-anim, .dp-st-icon { transition: none !important; }
    }
</style>
@endpush

@section('content')
<!-- Monthly Summary Cards -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="dp-card dp-st-anim p-3 d-flex align-items-center justify-content-between gap-2" style="background:#ECFDF5; border-color:#A7F3D0; animation-delay:20ms;">
            <div>
                <div class="small fw-semibold text-success">Month Settled</div>
                <div class="fs-5 fw-bold text-success" id="dpStMonthTotal">{{ $monthTotalFormatted }}</div>
            </div>
            <span class="dp-st-icon" style="background:#D1FAE5; color:#15803D;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card dp-st-anim p-3 d-flex align-items-center justify-content-between gap-2" style="background:#EFF6FF; border-color:#BFDBFE; animation-delay:60ms;">
            <div>
                <div class="small fw-semibold text-primary">Month Settlements</div>
                <div class="fs-5 fw-bold text-dark" id="dpStMonthCount">{{ $monthCount }}</div>
            </div>
            <span class="dp-st-icon" style="background:#DBEAFE; color:#1D4ED8;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg></span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card dp-st-anim p-3 d-flex align-items-center justify-content-between gap-2" style="background:#FFF1F2; border-color:#FECACA; animation-delay:100ms;">
            <div>
                <div class="small fw-semibold text-danger">Current Cash in Hand</div>
                <div class="fs-5 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['cash_in_hand_minor'], $currency) }}</div>
            </div>
            <span class="dp-st-icon" style="background:#FFE4E6; color:#BE123C;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg></span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card dp-st-anim p-3 d-flex align-items-center justify-content-between gap-2" style="background:#FFF8E5; border-color:#FDE68A; animation-delay:140ms;">
            <div>
                <div class="small fw-semibold text-warning-emphasis">All-Time Settled</div>
                <div class="fs-5 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['total_settled_minor'], $currency) }}</div>
            </div>
            <span class="dp-st-icon" style="background:#FEF3C7; color:#B45309;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
        </div>
    </div>
</div>

<!-- Month Navigation Bar -->
<div class="dp-card p-3 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border-color: #FDE68A;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="fw-bold mb-0 text-dark" id="dpStMonthHeading">{{ $monthLabel }}</h5>
            <span id="dpStSpinner" class="spinner-border spinner-border-sm text-warning d-none" role="status" aria-hidden="true"></span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1" id="dpStPrevBtn" data-month="{{ $prevMonth }}" data-year="{{ $prevYear }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                <span class="d-none d-sm-inline">Prev</span>
            </button>
            <select id="dpStMonthSelect" class="form-select form-select-sm" style="width:auto;">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($selectedMonth === $m)>{{ \Carbon\Carbon::create(2026, $m, 1)->format('F') }}</option>
                @endfor
            </select>
            <select id="dpStYearSelect" class="form-select form-select-sm" style="width:auto;">
                @for($y = $currentYear - 2; $y <= $currentYear + 1; $y++)
                    <option value="{{ $y }}" @selected($selectedYear === $y)>{{ $y }}</option>
                @endfor
            </select>
            <button type="button" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1" id="dpStNextBtn" data-month="{{ $nextMonth }}" data-year="{{ $nextYear }}">
                <span class="d-none d-sm-inline">Next</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
            <button type="button" class="btn btn-sm dp-btn-yellow px-3" id="dpStCurrentBtn">Current Month</button>
        </div>
    </div>
</div>

<!-- AJAX Filter Bar -->
<div class="dp-card p-3 mb-3">
    <form id="dpStFilterForm" method="GET" action="{{ route('delivery-person.settlements') }}" class="row g-2 align-items-end small">
        <div class="col-12 col-md-4">
            <label class="form-label mb-1">Search Reference / Notes</label>
            <input type="text" id="dpStSearchInput" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Search STL-... or notes">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1">Channel</label>
            <select id="dpStChannelSelect" name="settlement_channel" class="form-select form-select-sm">
                <option value="">All Channels</option>
                <option value="cash" @selected($channel === 'cash')>Cash</option>
                <option value="bank_transfer" @selected($channel === 'bank_transfer')>Bank Transfer</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1">Sort</label>
            <select id="dpStSortSelect" name="sort" class="form-select form-select-sm">
                <option value="newest" @selected($sort === 'newest')>Newest First</option>
                <option value="oldest" @selected($sort === 'oldest')>Oldest First</option>
                <option value="amount_desc" @selected($sort === 'amount_desc')>Highest Amount</option>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button type="button" id="dpStResetBtn" class="btn btn-sm btn-outline-secondary w-100">Reset Filters</button>
        </div>
    </form>
</div>

<div id="dpStError" class="alert alert-danger d-none small py-2 mb-3"></div>

<div class="dp-card p-0 overflow-hidden" id="dpStTableWrap">
    @include('delivery-person-portal.partials.settlements-table')
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = @json(route('delivery-person.settlements'));
    const defaultM = {{ $currentMonth }};
    const defaultY = {{ $currentYear }};
    const monthEl = document.getElementById('dpStMonthSelect');
    const yearEl = document.getElementById('dpStYearSelect');
    const prevBtn = document.getElementById('dpStPrevBtn');
    const nextBtn = document.getElementById('dpStNextBtn');
    const currBtn = document.getElementById('dpStCurrentBtn');
    const searchInput = document.getElementById('dpStSearchInput');
    const chanEl = document.getElementById('dpStChannelSelect');
    const sortEl = document.getElementById('dpStSortSelect');
    const resetBtn = document.getElementById('dpStResetBtn');
    const wrapEl = document.getElementById('dpStTableWrap');
    const headEl = document.getElementById('dpStMonthHeading');
    const spinEl = document.getElementById('dpStSpinner');
    const errEl = document.getElementById('dpStError');

    let ctrl = null, seq = 0, timer = null;

    async function loadSettlements(page = 1) {
        if (ctrl) ctrl.abort();
        ctrl = new AbortController();
        const mySeq = ++seq;
        const params = new URLSearchParams();
        params.set('month', monthEl.value || String(defaultM));
        params.set('year', yearEl.value || String(defaultY));
        if (searchInput.value.trim()) params.set('search', searchInput.value.trim());
        if (chanEl.value) params.set('settlement_channel', chanEl.value);
        if (sortEl.value) params.set('sort', sortEl.value);
        if (page > 1) params.set('page', String(page));

        const url = `${baseUrl}?${params.toString()}`;
        wrapEl.classList.add('is-loading');
        spinEl.classList.remove('d-none');
        errEl.classList.add('d-none');

        try {
            const res = await fetch(url, { signal: ctrl.signal, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const data = await res.json();
            if (mySeq !== seq) return;
            if (!res.ok || data.table_html === undefined) throw new Error(data.message || 'Unable to load settlements.');
            wrapEl.innerHTML = data.table_html;
            headEl.textContent = data.month_label;
            monthEl.value = String(data.month);
            yearEl.value = String(data.year);
            prevBtn.dataset.month = String(data.prev_month);
            prevBtn.dataset.year = String(data.prev_year);
            nextBtn.dataset.month = String(data.next_month);
            nextBtn.dataset.year = String(data.next_year);
            if (data.month_total_formatted !== undefined) document.getElementById('dpStMonthTotal').textContent = data.month_total_formatted;
            if (data.month_count !== undefined) document.getElementById('dpStMonthCount').textContent = data.month_count;
            window.history?.replaceState({}, '', url);
        } catch (e) {
            if (e.name === 'AbortError' || mySeq !== seq) return;
            errEl.textContent = e.message || 'Failed to load settlements.';
            errEl.classList.remove('d-none');
        } finally {
            if (mySeq === seq) {
                wrapEl.classList.remove('is-loading');
                spinEl.classList.add('d-none');
            }
        }
    }

    prevBtn.addEventListener('click', () => { monthEl.value = prevBtn.dataset.month; yearEl.value = prevBtn.dataset.year; loadSettlements(1); });
    nextBtn.addEventListener('click', () => { monthEl.value = nextBtn.dataset.month; yearEl.value = nextBtn.dataset.year; loadSettlements(1); });
    currBtn.addEventListener('click', () => { monthEl.value = String(defaultM); yearEl.value = String(defaultY); loadSettlements(1); });
    [monthEl, yearEl, chanEl, sortEl].forEach(el => el.addEventListener('change', () => loadSettlements(1)));
    searchInput.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => loadSettlements(1), 300); });
    document.getElementById('dpStFilterForm').addEventListener('submit', e => { e.preventDefault(); loadSettlements(1); });
    resetBtn.addEventListener('click', () => {
        monthEl.value = String(defaultM); yearEl.value = String(defaultY);
        searchInput.value = ''; chanEl.value = ''; sortEl.value = 'newest';
        loadSettlements(1);
    });
    document.addEventListener('click', e => {
        const a = e.target.closest('.dp-st-pagination a');
        if (!a) return;
        e.preventDefault();
        const p = parseInt(new URL(a.href, window.location.origin).searchParams.get('page') || '1', 10);
        loadSettlements(isNaN(p) ? 1 : p);
    });
});
</script>
@endsection
