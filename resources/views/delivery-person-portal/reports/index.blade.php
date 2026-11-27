@extends('delivery-person-portal.layouts.app')

@section('title', 'My Reports & Analytics')
@section('page_title', 'My Reports & Analytics')

@push('styles')
<style>
    .dp-rep-tabs .nav-link {
        color: #475569;
        font-weight: 600;
        font-size: 0.85rem;
        border-radius: 10px;
        padding: 0.45rem 0.85rem;
        border: 1px solid transparent;
    }
    .dp-rep-tabs .nav-link:hover { background: #FFF8E5; color: #171717; }
    .dp-rep-tabs .nav-link.active { background: #FFC629; color: #171717; border-color: #F59E0B; }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0" style="color: #171717;">My Reports &amp; Analytics</h4>
        <div class="small text-muted">Track your jobs, earnings, cash activity, and delivery performance.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" id="dpRepRefreshBtn" class="btn btn-sm btn-outline-dark">Refresh</button>
        <a href="{{ route('delivery-person.reports.export') }}" id="dpRepExportBtn" class="btn btn-sm dp-btn-yellow px-3">Export CSV</a>
    </div>
</div>

<!-- Top Filter Bar -->
<div class="dp-card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold mb-1">Period</label>
            <div class="input-group input-group-sm">
                <button type="button" id="dpRepPrevBtn" class="btn btn-outline-secondary" data-month="{{ $period['prev_month'] }}" data-year="{{ $period['prev_year'] }}">&lsaquo;</button>
                <select id="dpRepPeriod" class="form-select">
                    <option value="this_month" @selected($period['period'] === 'this_month')>Selected Month</option>
                    <option value="last_month" @selected($period['period'] === 'last_month')>Last Month</option>
                    <option value="last_3_months" @selected($period['period'] === 'last_3_months')>Last 3 Months</option>
                    <option value="last_6_months" @selected($period['period'] === 'last_6_months')>Last 6 Months</option>
                    <option value="this_year" @selected($period['period'] === 'this_year')>This Year</option>
                    <option value="custom" @selected($period['period'] === 'custom')>Custom Range</option>
                </select>
                <button type="button" id="dpRepNextBtn" class="btn btn-outline-secondary" data-month="{{ $period['next_month'] }}" data-year="{{ $period['next_year'] }}">&rsaquo;</button>
            </div>
        </div>
        <div class="col-6 col-md-2" id="dpRepMonthWrap">
            <label class="form-label small fw-semibold mb-1">Month</label>
            <select id="dpRepMonth" class="form-select form-select-sm">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($period['month'] === $m)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                @endfor
            </select>
        </div>
        <div class="col-6 col-md-1" id="dpRepYearWrap">
            <label class="form-label small fw-semibold mb-1">Year</label>
            <select id="dpRepYear" class="form-select form-select-sm">
                @for($y = $period['current_year'] - 3; $y <= $period['current_year'] + 1; $y++)
                    <option value="{{ $y }}" @selected($period['year'] === $y)>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div class="col-6 col-md-2 d-none" id="dpRepFromWrap">
            <label class="form-label small fw-semibold mb-1">From</label>
            <input type="date" id="dpRepDateFrom" value="{{ $period['date_from'] }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2 d-none" id="dpRepToWrap">
            <label class="form-label small fw-semibold mb-1">To</label>
            <input type="date" id="dpRepDateTo" value="{{ $period['date_to'] }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Compare With</label>
            <select id="dpRepCompare" class="form-select form-select-sm">
                <option value="prev_month" @selected($period['compare'] === 'prev_month')>Previous Month</option>
                <option value="prev_year" @selected($period['compare'] === 'prev_year')>Same Month Last Year</option>
                <option value="prev_equivalent" @selected($period['compare'] === 'prev_equivalent')>Previous Equivalent</option>
                <option value="none" @selected($period['compare'] === 'none')>No Comparison</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Search</label>
            <input type="text" id="dpRepSearch" class="form-control form-control-sm" placeholder="Booking ref, customer...">
        </div>
        <div class="col-12 col-md-2 d-flex align-items-center justify-content-between gap-2">
            <span class="badge rounded-pill px-2 py-1 text-truncate" id="dpRepPeriodBadge" style="background: #FFF8E5; color: #92400E; border: 1px solid #FDE68A;">{{ $period['label'] }}</span>
            <button type="button" id="dpRepResetBtn" class="btn btn-sm btn-outline-secondary">Reset</button>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav dp-rep-tabs gap-1 mb-3 flex-nowrap overflow-auto pb-1" id="dpRepTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-tab="overview" type="button">Overview</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="collections" type="button">Collections</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="final_deliveries" type="button">Final Deliveries</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="earnings" type="button">Earnings</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="withdrawals" type="button">Withdrawals</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="cash_account" type="button">Cash Account</button></li>
    <li class="nav-item"><button class="nav-link" data-tab="performance" type="button">Performance</button></li>
</ul>

<div id="dpRepErrorAlert" class="alert alert-danger d-none py-2 small" role="alert"></div>
<div id="dpRepContentWrap">
    <div class="dp-card p-4 text-center text-muted small">
        <span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading report...
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin/dist/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const endpoints = {
        overview: @json(route('delivery-person.reports.overview')),
        collections: @json(route('delivery-person.reports.collections')),
        final_deliveries: @json(route('delivery-person.reports.final-deliveries')),
        earnings: @json(route('delivery-person.reports.earnings')),
        withdrawals: @json(route('delivery-person.reports.withdrawals')),
        cash_account: @json(route('delivery-person.reports.cash-account')),
        performance: @json(route('delivery-person.reports.performance')),
    };
    const exportBaseUrl = @json(route('delivery-person.reports.export'));
    let activeTab = 'overview', ctrl = null, seq = 0, timer = null;
    const charts = {};

    function destroyCharts() {
        Object.keys(charts).forEach(k => { try { charts[k].destroy(); } catch (e) {} delete charts[k]; });
    }
    function toggleCustomDates() {
        const isCustom = document.getElementById('dpRepPeriod').value === 'custom';
        document.getElementById('dpRepMonthWrap').classList.toggle('d-none', isCustom);
        document.getElementById('dpRepYearWrap').classList.toggle('d-none', isCustom);
        document.getElementById('dpRepFromWrap').classList.toggle('d-none', !isCustom);
        document.getElementById('dpRepToWrap').classList.toggle('d-none', !isCustom);
    }
    function buildParams(page) {
        const p = new URLSearchParams();
        p.set('period', document.getElementById('dpRepPeriod').value);
        p.set('month', document.getElementById('dpRepMonth').value);
        p.set('year', document.getElementById('dpRepYear').value);
        p.set('date_from', document.getElementById('dpRepDateFrom').value);
        p.set('date_to', document.getElementById('dpRepDateTo').value);
        p.set('compare', document.getElementById('dpRepCompare').value);
        const q = (document.getElementById('dpRepSearch').value || '').trim();
        if (q) p.set('q', q);
        if (page > 1) p.set('page', String(page));
        return p;
    }
    function renderCharts(data) {
        if (typeof ApexCharts === 'undefined' || !data.charts) return;
        destroyCharts();
        if (data.charts.bar_id && data.charts.daily) {
            const el = document.getElementById(data.charts.bar_id);
            if (el && Array.isArray(data.charts.daily.series)) {
                const singleColor = activeTab === 'earnings' ? '#16A34A' : (activeTab === 'withdrawals' ? '#8B5CF6' : '#FFC629');
                charts.bar = new ApexCharts(el, {
                    chart: { type: 'bar', height: 220, toolbar: { show: false } },
                    colors: [singleColor],
                    series: [{ name: data.charts.series_name || 'Amount', data: data.charts.daily.series }],
                    xaxis: { categories: data.charts.daily.labels },
                    dataLabels: { enabled: false },
                });
                charts.bar.render();
            } else if (el) {
                const completedColor = activeTab === 'collections' ? '#16A34A' : (activeTab === 'final_deliveries' ? '#3B82F6' : '#FFC629');
                const maxVal = Math.max(30, ...(data.charts.daily.pending || [0]), ...(data.charts.daily.completed || [0]));
                charts.bar = new ApexCharts(el, {
                    chart: { type: 'bar', height: 230, toolbar: { show: false } },
                    colors: ['#F59E0B', completedColor],
                    series: [
                        { name: 'Pending', data: data.charts.daily.pending || [] },
                        { name: 'Completed', data: data.charts.daily.completed || [] }
                    ],
                    xaxis: { categories: data.charts.daily.labels },
                    yaxis: {
                        min: 0,
                        max: maxVal,
                        tickAmount: 6,
                        labels: { formatter: val => Math.round(val) }
                    },
                    legend: {
                        position: 'top',
                        formatter: function (seriesName, opts) {
                            const sum = (opts.w.globals.series[opts.seriesIndex] || []).reduce((a, b) => a + Number(b || 0), 0);
                            return seriesName + ' (' + sum + ')';
                        }
                    },
                    dataLabels: { enabled: false },
                });
                charts.bar.render();
            }
        }
        if (data.charts.donut_id && data.charts.distribution) {
            const el = document.getElementById(data.charts.donut_id);
            const donutColors = activeTab === 'collections'
                ? ['#16A34A', '#22C55E', '#86EFAC', '#F59E0B', '#EF4444']
                : ['#3B82F6', '#60A5FA', '#93C5FD', '#8B5CF6', '#EF4444'];
            if (el) {
                charts.donut = new ApexCharts(el, {
                    chart: { type: 'donut', height: 230 },
                    colors: donutColors,
                    labels: data.charts.distribution.labels,
                    series: data.charts.distribution.series,
                    legend: { position: 'bottom' },
                });
                charts.donut.render();
            }
        }
    }
    async function loadTab(page) {
        if (ctrl) ctrl.abort();
        ctrl = new AbortController();
        const mySeq = ++seq;
        const params = buildParams(page || 1);
        document.getElementById('dpRepExportBtn').href = exportBaseUrl + '?' + params.toString();
        const wrap = document.getElementById('dpRepContentWrap');
        const errEl = document.getElementById('dpRepErrorAlert');
        errEl.classList.add('d-none');
        destroyCharts();
        wrap.innerHTML = '<div class="dp-card p-4 text-center text-muted small"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading report...</div>';
        try {
            const res = await fetch(endpoints[activeTab] + '?' + params.toString(), {
                signal: ctrl.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (mySeq !== seq) return;
            if (!res.ok || data.status !== 'ok') throw new Error(data.message || 'Unable to load report.');
            if (data.period_label) document.getElementById('dpRepPeriodBadge').textContent = data.period_label;
            wrap.innerHTML = data.html;
            renderCharts(data);
        } catch (err) {
            if (err.name === 'AbortError' || mySeq !== seq) return;
            errEl.textContent = err.message || 'Failed to load report.';
            errEl.classList.remove('d-none');
        }
    }

    document.querySelectorAll('#dpRepTabs .nav-link').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#dpRepTabs .nav-link').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeTab = btn.dataset.tab;
            loadTab(1);
        });
    });
    ['dpRepPeriod', 'dpRepMonth', 'dpRepYear', 'dpRepDateFrom', 'dpRepDateTo', 'dpRepCompare'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => { toggleCustomDates(); loadTab(1); });
    });
    document.getElementById('dpRepSearch')?.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => loadTab(1), 300);
    });
    document.getElementById('dpRepRefreshBtn')?.addEventListener('click', () => loadTab(1));
    document.getElementById('dpRepResetBtn')?.addEventListener('click', () => {
        document.getElementById('dpRepPeriod').value = 'this_month';
        document.getElementById('dpRepMonth').value = '{{ $period['current_month'] }}';
        document.getElementById('dpRepYear').value = '{{ $period['current_year'] }}';
        document.getElementById('dpRepCompare').value = 'prev_month';
        document.getElementById('dpRepSearch').value = '';
        toggleCustomDates();
        loadTab(1);
    });
    document.addEventListener('click', e => {
        const a = e.target.closest('.sp-rep-col-pagination a, .sp-rep-fd-pagination a, .dp-rep-er-pagination a, .dp-rep-wd-pagination a, .dp-rep-ca-pagination a');
        if (!a) return;
        e.preventDefault();
        const p = parseInt(new URL(a.href, window.location.origin).searchParams.get('page') || '1', 10);
        loadTab(isNaN(p) ? 1 : p);
    });
    toggleCustomDates();
    loadTab(1);
});
</script>
@endpush
