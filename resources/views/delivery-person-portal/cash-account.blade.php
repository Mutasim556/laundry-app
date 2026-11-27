@extends('delivery-person-portal.layouts.app')
@section('title', 'Cash Account')
@section('page_title', 'Customer Cash Custody Account')

@push('styles')
<style>
    .dp-ca-anim {
        animation: dpCaFadeUp 0.48s cubic-bezier(0.22, 1, 0.36, 1) both;
        transition: transform 0.22s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.22s ease;
    }
    .dp-ca-anim:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07); }
    .dp-ca-icon {
        width: 38px; height: 38px; border-radius: 11px;
        display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
        transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .dp-ca-anim:hover .dp-ca-icon { transform: scale(1.1) rotate(-6deg); }
    #dpCaTableWrap.is-loading { opacity: 0.55; pointer-events: none; transition: opacity 0.18s ease; }
    @keyframes dpCaFadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    @media (prefers-reduced-motion: reduce) {
        .dp-ca-anim { animation: none !important; }
        .dp-ca-anim, .dp-ca-icon { transition: none !important; }
    }
</style>
@endpush

@section('content')
<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="dp-card dp-ca-anim p-3 d-flex align-items-center justify-content-between gap-2" style="background: #FFF1F2; border-color: #FECACA; animation-delay: 20ms;">
            <div>
                <div class="small fw-semibold text-danger">Cash in Hand</div>
                <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['cash_in_hand_minor'], $currency) }}</div>
            </div>
            <span class="dp-ca-icon" style="background:#FFE4E6; color:#BE123C;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg></span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card dp-ca-anim p-3 d-flex align-items-center justify-content-between gap-2" style="background: #F5F3FF; border-color: #DDD6FE; animation-delay: 60ms;">
            <div>
                <div class="small fw-semibold" style="color: #6D28D9;">Online Funds Held</div>
                <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['online_transfer_confirmed_minor'], $currency) }}</div>
            </div>
            <span class="dp-ca-icon" style="background:#EDE9FE; color:#6D28D9;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22h18"/><path d="M6 18v-7"/><path d="M18 18v-7"/><polygon points="12 2 20 7 4 7"/></svg></span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card dp-ca-anim p-3 d-flex align-items-center justify-content-between gap-2" style="background: #FFF8E5; border-color: #FDE68A; animation-delay: 100ms;">
            <div>
                <div class="small fw-semibold text-warning-emphasis">Awaiting Settlement</div>
                <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['cash_in_hand_minor'], $currency) }}</div>
            </div>
            <span class="dp-ca-icon" style="background:#FEF3C7; color:#B45309;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card dp-ca-anim p-3 d-flex align-items-center justify-content-between gap-2" style="background: #ECFDF5; border-color: #A7F3D0; animation-delay: 140ms;">
            <div>
                <div class="small fw-semibold text-success">Total Handed Over</div>
                <div class="fs-4 fw-bold text-success">{{ $currencyService->formatMinor($cashSummary['total_settled_minor'], $currency) }}</div>
            </div>
            <span class="dp-ca-icon" style="background:#D1FAE5; color:#15803D;"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
        </div>
    </div>
</div>

<!-- Month Navigation Bar -->
<div class="dp-card p-3 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border-color: #FDE68A;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="fw-bold mb-0 text-dark" id="dpCaMonthHeading">{{ $monthLabel }}</h5>
            <span id="dpCaSpinner" class="spinner-border spinner-border-sm text-warning d-none" role="status" aria-hidden="true"></span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1" id="dpCaPrevBtn" data-month="{{ $prevMonth }}" data-year="{{ $prevYear }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                <span class="d-none d-sm-inline">Prev</span>
            </button>
            <select id="dpCaMonthSelect" class="form-select form-select-sm" style="width:auto;">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($selectedMonth === $m)>{{ \Carbon\Carbon::create(2026, $m, 1)->format('F') }}</option>
                @endfor
            </select>
            <select id="dpCaYearSelect" class="form-select form-select-sm" style="width:auto;">
                @for($y = $currentYear - 2; $y <= $currentYear + 1; $y++)
                    <option value="{{ $y }}" @selected($selectedYear === $y)>{{ $y }}</option>
                @endfor
            </select>
            <button type="button" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1" id="dpCaNextBtn" data-month="{{ $nextMonth }}" data-year="{{ $nextYear }}">
                <span class="d-none d-sm-inline">Next</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
            <button type="button" class="btn btn-sm dp-btn-yellow px-3" id="dpCaCurrentBtn">Current Month</button>
        </div>
    </div>
</div>

<div class="dp-card p-3 mb-3">
    <form id="dpCaFilterForm" method="GET" action="{{ route('delivery-person.cash-account') }}" class="row g-2 align-items-end small">
        <div class="col-12 col-md-4">
            <label class="form-label mb-1">Booking Reference / Description</label>
            <input type="text" id="dpCaRefInput" name="booking_reference" value="{{ $bookingReference }}" class="form-control form-control-sm" placeholder="Search LND-... or description">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1">Channel</label>
            <select id="dpCaChannelSelect" name="payment_channel" class="form-select form-select-sm">
                <option value="">All Channels</option>
                <option value="cash_in_hand" @selected($paymentChannel === 'cash_in_hand')>Cash in Hand</option>
                <option value="admin_settlement" @selected($paymentChannel === 'admin_settlement')>Admin Settlement</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label mb-1">Sort</label>
            <select id="dpCaSortSelect" name="sort" class="form-select form-select-sm">
                <option value="newest" @selected($sort === 'newest')>Newest First</option>
                <option value="oldest" @selected($sort === 'oldest')>Oldest First</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="button" id="dpCaResetBtn" class="btn btn-sm btn-outline-secondary w-100">Reset Filters</button>
        </div>
    </form>
</div>

<div id="dpCaError" class="alert alert-danger d-none small py-2 mb-3"></div>

<div class="dp-card p-0 overflow-hidden" id="dpCaTableWrap">
    @include('delivery-person-portal.partials.cash-account-table')
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const baseUrl = @json(route('delivery-person.cash-account'));
    const defaultM = {{ $currentMonth }};
    const defaultY = {{ $currentYear }};
    const monthEl = document.getElementById('dpCaMonthSelect');
    const yearEl = document.getElementById('dpCaYearSelect');
    const prevBtn = document.getElementById('dpCaPrevBtn');
    const nextBtn = document.getElementById('dpCaNextBtn');
    const currBtn = document.getElementById('dpCaCurrentBtn');
    const refInput = document.getElementById('dpCaRefInput');
    const chanEl = document.getElementById('dpCaChannelSelect');
    const sortEl = document.getElementById('dpCaSortSelect');
    const resetBtn = document.getElementById('dpCaResetBtn');
    const wrapEl = document.getElementById('dpCaTableWrap');
    const headEl = document.getElementById('dpCaMonthHeading');
    const spinEl = document.getElementById('dpCaSpinner');
    const errEl = document.getElementById('dpCaError');

    let ctrl = null, seq = 0, timer = null;

    async function loadCashAccount(page = 1) {
        if (ctrl) ctrl.abort();
        ctrl = new AbortController();
        const mySeq = ++seq;
        const params = new URLSearchParams();
        params.set('month', monthEl.value || String(defaultM));
        params.set('year', yearEl.value || String(defaultY));
        if (refInput.value.trim()) params.set('booking_reference', refInput.value.trim());
        if (chanEl.value) params.set('payment_channel', chanEl.value);
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
            if (!res.ok || data.table_html === undefined) throw new Error(data.message || 'Unable to load transactions.');
            wrapEl.innerHTML = data.table_html;
            headEl.textContent = data.month_label;
            monthEl.value = String(data.month);
            yearEl.value = String(data.year);
            prevBtn.dataset.month = String(data.prev_month);
            prevBtn.dataset.year = String(data.prev_year);
            nextBtn.dataset.month = String(data.next_month);
            nextBtn.dataset.year = String(data.next_year);
            window.history?.replaceState({}, '', url);
        } catch (e) {
            if (e.name === 'AbortError' || mySeq !== seq) return;
            errEl.textContent = e.message || 'Failed to load data.';
            errEl.classList.remove('d-none');
        } finally {
            if (mySeq === seq) {
                wrapEl.classList.remove('is-loading');
                spinEl.classList.add('d-none');
            }
        }
    }

    prevBtn.addEventListener('click', () => { monthEl.value = prevBtn.dataset.month; yearEl.value = prevBtn.dataset.year; loadCashAccount(1); });
    nextBtn.addEventListener('click', () => { monthEl.value = nextBtn.dataset.month; yearEl.value = nextBtn.dataset.year; loadCashAccount(1); });
    currBtn.addEventListener('click', () => { monthEl.value = String(defaultM); yearEl.value = String(defaultY); loadCashAccount(1); });
    [monthEl, yearEl, chanEl, sortEl].forEach(el => el.addEventListener('change', () => loadCashAccount(1)));
    refInput.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => loadCashAccount(1), 300); });
    document.getElementById('dpCaFilterForm').addEventListener('submit', e => { e.preventDefault(); loadCashAccount(1); });
    resetBtn.addEventListener('click', () => {
        monthEl.value = String(defaultM); yearEl.value = String(defaultY);
        refInput.value = ''; chanEl.value = ''; sortEl.value = 'newest';
        loadCashAccount(1);
    });
    document.addEventListener('click', e => {
        const a = e.target.closest('.dp-ca-pagination a');
        if (!a) return;
        e.preventDefault();
        const p = parseInt(new URL(a.href, window.location.origin).searchParams.get('page') || '1', 10);
        loadCashAccount(isNaN(p) ? 1 : p);
    });
});
</script>
@endsection
