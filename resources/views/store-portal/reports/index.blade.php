@extends('store-portal.layouts.app')

@section('title', 'Reports & Analytics — Store Portal')

@push('styles')
<style>
    .sp-rep-tabs .nav-link {
        color: #475569;
        font-weight: 600;
        font-size: 0.875rem;
        border-radius: 10px;
        padding: 0.5rem 0.9rem;
        border: 1px solid transparent;
    }
    .sp-rep-tabs .nav-link:hover {
        background: #FFF8E5;
        color: #171717;
    }
    .sp-rep-tabs .nav-link.active {
        background: #FFC629;
        color: #171717;
        border-color: #F59E0B;
    }
    .sp-rep-wrap {
        min-width: 0;
    }
</style>
@endpush

@section('content')
<div class="sp-rep-wrap">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-0" style="color: #171717;">Store Reports &amp; Analytics</h4>
            <div class="small text-muted">Track your store's financial performance, booking activity, and operational efficiency.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="spRepRefreshBtn" class="btn btn-sm btn-outline-dark">Refresh</button>
            <a href="{{ route('store.reports.export') }}" id="spRepExportBtn" class="btn btn-sm sp-btn-yellow px-3">Export CSV</a>
        </div>
    </div>

    <!-- Top Filter Bar -->
    <div class="sp-card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold mb-1">Period Preset</label>
                <div class="input-group input-group-sm">
                    <button type="button" id="spRepPrevBtn" class="btn btn-outline-secondary" data-month="{{ $period['prev_month'] }}" data-year="{{ $period['prev_year'] }}" title="Previous Month">&lsaquo;</button>
                    <select id="spRepPeriod" class="form-select">
                        <option value="this_month" @selected($period['period'] === 'this_month')>Selected Month</option>
                        <option value="last_month" @selected($period['period'] === 'last_month')>Last Month</option>
                        <option value="last_3_months" @selected($period['period'] === 'last_3_months')>Last 3 Months</option>
                        <option value="last_6_months" @selected($period['period'] === 'last_6_months')>Last 6 Months</option>
                        <option value="this_year" @selected($period['period'] === 'this_year')>This Year</option>
                        <option value="custom" @selected($period['period'] === 'custom')>Custom Range</option>
                    </select>
                    <button type="button" id="spRepNextBtn" class="btn btn-outline-secondary" data-month="{{ $period['next_month'] }}" data-year="{{ $period['next_year'] }}" title="Next Month">&rsaquo;</button>
                </div>
            </div>

            <div class="col-6 col-md-2" id="spRepMonthWrap">
                <label class="form-label small fw-semibold mb-1">Month</label>
                <select id="spRepMonth" class="form-select form-select-sm">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected($period['month'] === $m)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-6 col-md-1" id="spRepYearWrap">
                <label class="form-label small fw-semibold mb-1">Year</label>
                <select id="spRepYear" class="form-select form-select-sm">
                    @for($y = $period['current_year'] - 3; $y <= $period['current_year'] + 1; $y++)
                        <option value="{{ $y }}" @selected($period['year'] === $y)>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-6 col-md-2 d-none" id="spRepFromWrap">
                <label class="form-label small fw-semibold mb-1">From</label>
                <input type="date" id="spRepDateFrom" value="{{ $period['date_from'] }}" class="form-control form-control-sm">
            </div>

            <div class="col-6 col-md-2 d-none" id="spRepToWrap">
                <label class="form-label small fw-semibold mb-1">To</label>
                <input type="date" id="spRepDateTo" value="{{ $period['date_to'] }}" class="form-control form-control-sm">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Compare With</label>
                <select id="spRepCompare" class="form-select form-select-sm">
                    <option value="prev_month" @selected($period['compare'] === 'prev_month')>Previous Month</option>
                    <option value="prev_year" @selected($period['compare'] === 'prev_year')>Same Month Last Year</option>
                    <option value="prev_equivalent" @selected($period['compare'] === 'prev_equivalent')>Previous Equivalent</option>
                    <option value="none" @selected($period['compare'] === 'none')>No Comparison</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <input type="text" id="spRepSearch" class="form-control form-control-sm" placeholder="Ref, customer...">
            </div>

            <div class="col-12 col-md-2 d-flex align-items-center justify-content-between gap-2">
                <span class="badge rounded-pill px-2 py-1 text-truncate" id="spRepPeriodBadge" style="background: #FFF8E5; color: #92400E; border: 1px solid #FDE68A;">{{ $period['label'] }}</span>
                <button type="button" id="spRepResetBtn" class="btn btn-sm btn-outline-secondary">Reset</button>
            </div>
        </div>
    </div>

    <!-- Report Tabs -->
    <ul class="nav sp-rep-tabs gap-1 mb-3 flex-nowrap overflow-auto pb-1" id="spRepTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-tab="overview" type="button">Overview</button></li>
        <li class="nav-item"><button class="nav-link" data-tab="financial" type="button">Financial</button></li>
        <li class="nav-item"><button class="nav-link" data-tab="transactions" type="button">Transactions</button></li>
        <li class="nav-item"><button class="nav-link" data-tab="bookings" type="button">Bookings</button></li>
        <li class="nav-item"><button class="nav-link" data-tab="handovers" type="button">Handovers</button></li>
        <li class="nav-item"><button class="nav-link" data-tab="performance" type="button">Performance</button></li>
    </ul>

    <div id="spRepErrorAlert" class="alert alert-danger d-none py-2 small" role="alert"></div>

    <div id="spRepContentWrap" class="sp-ajax-wrap">
        <div class="sp-card p-4 text-center text-muted small">
            <span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading report data...
        </div>
    </div>
</div>

<!-- Transaction Detail Modal -->
<div class="modal fade" id="spRepTxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Transaction Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body small" id="spRepTxModalBody">Loading...</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-dark" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin/dist/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const endpoints = {
        overview: @json(route('store.reports.overview')),
        financial: @json(route('store.reports.financial')),
        transactions: @json(route('store.reports.transactions')),
        bookings: @json(route('store.reports.bookings')),
        handovers: @json(route('store.reports.handovers')),
        performance: @json(route('store.reports.performance')),
    };
    const exportBaseUrl = @json(route('store.reports.export'));
    const txDetailBaseUrl = @json(url('store/reports/transactions'));

    let activeTab = 'overview';
    let ctrl = null;
    let seq = 0;
    let timer = null;
    const charts = {};

    function destroyCharts() {
        Object.keys(charts).forEach(k => {
            if (charts[k]) {
                try { charts[k].destroy(); } catch (e) {}
                delete charts[k];
            }
        });
    }

    function toggleCustomDates() {
        const isCustom = document.getElementById('spRepPeriod').value === 'custom';
        document.getElementById('spRepMonthWrap').classList.toggle('d-none', isCustom);
        document.getElementById('spRepYearWrap').classList.toggle('d-none', isCustom);
        document.getElementById('spRepFromWrap').classList.toggle('d-none', !isCustom);
        document.getElementById('spRepToWrap').classList.toggle('d-none', !isCustom);
    }

    function buildParams(page) {
        const params = new URLSearchParams();
        params.set('period', document.getElementById('spRepPeriod').value);
        params.set('month', document.getElementById('spRepMonth').value);
        params.set('year', document.getElementById('spRepYear').value);
        params.set('date_from', document.getElementById('spRepDateFrom').value);
        params.set('date_to', document.getElementById('spRepDateTo').value);
        params.set('compare', document.getElementById('spRepCompare').value);
        const q = (document.getElementById('spRepSearch').value || '').trim();
        if (q) params.set('q', q);
        if (page > 1) params.set('page', String(page));
        return params;
    }

    function updateExportLink(params) {
        document.getElementById('spRepExportBtn').href = exportBaseUrl + '?' + params.toString();
    }

    function renderCharts(tab, data) {
        if (typeof ApexCharts === 'undefined' || !data.charts) return;
        destroyCharts();

        if (tab === 'financial' && data.charts.share_vs_commission) {
            const elBar = document.getElementById('spRepFinBarChart');
            if (elBar) {
                charts.finBar = new ApexCharts(elBar, {
                    chart: { type: 'bar', height: 260, toolbar: { show: false } },
                    colors: ['#16A34A', '#F59E0B'],
                    series: [
                        { name: 'Store Share', data: data.charts.share_vs_commission.store_share },
                        { name: 'Platform Commission', data: data.charts.share_vs_commission.commission }
                    ],
                    xaxis: { categories: data.charts.share_vs_commission.labels },
                    dataLabels: { enabled: false },
                });
                charts.finBar.render();
            }
            const elDonut = document.getElementById('spRepFinDonutChart');
            if (elDonut && data.charts.breakdown) {
                charts.finDonut = new ApexCharts(elDonut, {
                    chart: { type: 'donut', height: 260 },
                    colors: ['#16A34A', '#F59E0B'],
                    labels: data.charts.breakdown.labels,
                    series: data.charts.breakdown.series,
                    legend: { position: 'bottom' }
                });
                charts.finDonut.render();
            }
        }

        if (tab === 'bookings') {
            const elBar = document.getElementById('spRepBookBarChart');
            if (elBar && data.charts.daily_activity) {
                charts.bookBar = new ApexCharts(elBar, {
                    chart: { type: 'bar', height: 240, toolbar: { show: false } },
                    colors: ['#3B82F6'],
                    series: [{ name: 'Bookings Assigned', data: data.charts.daily_activity.series }],
                    xaxis: { categories: data.charts.daily_activity.labels },
                    dataLabels: { enabled: false },
                });
                charts.bookBar.render();
            }
            const elDonut = document.getElementById('spRepBookDonutChart');
            if (elDonut && data.charts.stage_distribution) {
                charts.bookDonut = new ApexCharts(elDonut, {
                    chart: { type: 'donut', height: 240 },
                    labels: data.charts.stage_distribution.labels,
                    series: data.charts.stage_distribution.series,
                    legend: { position: 'bottom' }
                });
                charts.bookDonut.render();
            }
        }

        if (tab === 'handovers' && data.charts.status_bar) {
            const elHo = document.getElementById('spRepHoBarChart');
            if (elHo) {
                charts.hoBar = new ApexCharts(elHo, {
                    chart: { type: 'bar', height: 220, toolbar: { show: false } },
                    colors: ['#FFC629'],
                    series: [{ name: 'Handovers', data: data.charts.status_bar.series }],
                    xaxis: { categories: data.charts.status_bar.labels },
                    dataLabels: { enabled: false },
                });
                charts.hoBar.render();
            }
        }
    }

    async function loadTab(page) {
        if (ctrl) ctrl.abort();
        ctrl = new AbortController();
        const mySeq = ++seq;
        const params = buildParams(page || 1);
        updateExportLink(params);

        const wrap = document.getElementById('spRepContentWrap');
        const errEl = document.getElementById('spRepErrorAlert');
        wrap.classList.add('is-loading');
        errEl.classList.add('d-none');

        try {
            const res = await fetch(endpoints[activeTab] + '?' + params.toString(), {
                signal: ctrl.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (mySeq !== seq) return;
            if (!res.ok || data.status !== 'ok') throw new Error(data.message || 'Unable to load report.');

            if (data.period_label) document.getElementById('spRepPeriodBadge').textContent = data.period_label;
            if (data.prev_month) {
                const prevBtn = document.getElementById('spRepPrevBtn');
                prevBtn.dataset.month = String(data.prev_month);
                prevBtn.dataset.year = String(data.prev_year);
            }
            if (data.next_month) {
                const nextBtn = document.getElementById('spRepNextBtn');
                nextBtn.dataset.month = String(data.next_month);
                nextBtn.dataset.year = String(data.next_year);
            }

            wrap.innerHTML = data.html;
            renderCharts(activeTab, data);
        } catch (err) {
            if (err.name === 'AbortError' || mySeq !== seq) return;
            errEl.textContent = err.message || 'Failed to load report.';
            errEl.classList.remove('d-none');
        } finally {
            if (mySeq === seq) wrap.classList.remove('is-loading');
        }
    }

    document.querySelectorAll('#spRepTabs .nav-link').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#spRepTabs .nav-link').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeTab = btn.dataset.tab;
            loadTab(1);
        });
    });

    ['spRepPeriod', 'spRepMonth', 'spRepYear', 'spRepDateFrom', 'spRepDateTo', 'spRepCompare'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => {
            toggleCustomDates();
            loadTab(1);
        });
    });

    document.getElementById('spRepSearch')?.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => loadTab(1), 300);
    });

    document.getElementById('spRepPrevBtn')?.addEventListener('click', function () {
        document.getElementById('spRepPeriod').value = 'this_month';
        document.getElementById('spRepMonth').value = this.dataset.month;
        document.getElementById('spRepYear').value = this.dataset.year;
        toggleCustomDates();
        loadTab(1);
    });

    document.getElementById('spRepNextBtn')?.addEventListener('click', function () {
        document.getElementById('spRepPeriod').value = 'this_month';
        document.getElementById('spRepMonth').value = this.dataset.month;
        document.getElementById('spRepYear').value = this.dataset.year;
        toggleCustomDates();
        loadTab(1);
    });

    document.getElementById('spRepRefreshBtn')?.addEventListener('click', () => loadTab(1));
    document.getElementById('spRepResetBtn')?.addEventListener('click', () => {
        document.getElementById('spRepPeriod').value = 'this_month';
        document.getElementById('spRepMonth').value = '{{ $period['current_month'] }}';
        document.getElementById('spRepYear').value = '{{ $period['current_year'] }}';
        document.getElementById('spRepCompare').value = 'prev_month';
        document.getElementById('spRepSearch').value = '';
        toggleCustomDates();
        loadTab(1);
    });

    document.addEventListener('click', e => {
        const pagLink = e.target.closest('.sp-rep-tx-pagination a, .sp-rep-bk-pagination a, .sp-rep-ho-pagination a');
        if (pagLink) {
            e.preventDefault();
            const p = parseInt(new URL(pagLink.href, window.location.origin).searchParams.get('page') || '1', 10);
            loadTab(isNaN(p) ? 1 : p);
            return;
        }

        const txBtn = e.target.closest('.js-rep-tx-view');
        if (txBtn) {
            const id = txBtn.dataset.id;
            const body = document.getElementById('spRepTxModalBody');
            body.innerHTML = 'Loading...';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('spRepTxModal')).show();
            fetch(`${txDetailBaseUrl}/${id}`, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(d => {
                    const tx = d.transaction;
                    body.innerHTML = `
                        <div class="p-3 rounded-2 bg-light border d-flex flex-column gap-2">
                            <div><strong>Reference:</strong> ${tx.reference}</div>
                            <div><strong>Date:</strong> ${tx.created_at}</div>
                            <div><strong>Type:</strong> ${tx.type_label} (${tx.direction})</div>
                            <div><strong>Amount:</strong> ${tx.amount}</div>
                            <div><strong>Balance After:</strong> ${tx.balance_after}</div>
                            <div><strong>Booking:</strong> ${tx.booking_reference}</div>
                            <div><strong>Description:</strong> ${tx.description}</div>
                        </div>`;
                })
                .catch(() => { body.innerHTML = '<div class="text-danger">Unable to load transaction details.</div>'; });
        }
    });

    toggleCustomDates();
    loadTab(1);
});
</script>
@endpush
