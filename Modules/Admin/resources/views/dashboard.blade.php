@extends('admin::layouts.app')

@section('title', 'Fresh Go Dashboard')

@push('styles')
<style>
    @keyframes dbFadeUp {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .db-anim { animation: dbFadeUp 0.25s ease-out both; }
    @media (prefers-reduced-motion: reduce) {
        .db-anim, .db-kpi-card, .db-section-card { animation: none !important; transition: none !important; }
    }
    .db-header-card {
        background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 55%, #FEF3C7 100%);
        border: 1px solid #FDE68A;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(255, 198, 41, 0.14);
    }
    .db-kpi-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 0.9rem 1rem;
        position: relative;
        overflow: hidden;
        height: 100%;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .db-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    }
    .db-kpi-bar { position: absolute; top: 0; left: 0; right: 0; height: 4px; }
    .db-kpi-icon {
        width: 38px; height: 38px; border-radius: 11px;
        display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .db-section-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        min-width: 0;
    }
    .db-section-card > .card-header {
        background: linear-gradient(90deg, #F8FAFC 0%, #FFFFFF 100%);
        border-bottom: 1px solid #E2E8F0;
        padding: 0.8rem 1.1rem;
    }
    .db-nav-pills {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 0.4rem;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    }
    .db-nav-pills .nav-link {
        color: #475569; font-weight: 600; font-size: 0.82rem;
        border-radius: 10px; padding: 0.45rem 0.9rem;
        display: inline-flex; align-items: center; gap: 0.4rem;
    }
    .db-nav-pills .nav-link.active {
        background: linear-gradient(135deg, #FFC629 0%, #F59E0B 100%);
        color: #171717;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
    }
    .db-table-scroll { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .db-table-scroll > table { min-width: 640px; margin-bottom: 0; }
    .db-stage-pill {
        display: inline-flex; align-items: center; gap: 0.4rem;
        padding: 0.38rem 0.7rem; border-radius: 10px;
        border: 1px solid #E2E8F0; background: #F8FAFC;
        color: #171717; font-size: 0.76rem; text-decoration: none;
        transition: all 0.15s ease;
    }
    .db-stage-pill:hover { background: #FFF8E5; border-color: #FFC629; color: #171717; }
    .db-loading { opacity: 0.6; pointer-events: none; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 db-anim">
    <!-- ROW 1: Compact Dashboard Header & Period Controls -->
    <div class="db-header-card p-3 p-md-4 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background: #FFC629; color: #171717; font-size: 0.72rem;">
                    Fresh Go Admin
                </span>
                <span class="badge rounded-pill bg-white text-dark border" id="dbPeriodBadge" style="font-size: 0.72rem;">
                    {{ $period['month_label'] }}
                </span>
            </div>
            <h4 class="fw-bold mb-1" style="color: #171717;">Fresh Go Dashboard</h4>
            <div class="small text-muted">Monitor bookings, operations, payments and business performance.</div>
        </div>

        <hr class="my-3" style="border-color: #FDE68A; opacity: 0.8;">

        @php
            $dbParts = explode('-', (string) ($period['month'] ?? now()->format('Y-m')));
            $dbSelYear = (int) ($dbParts[0] ?? now()->year);
            $dbSelMonth = (int) ($dbParts[1] ?? now()->month);
        @endphp
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <div class="fw-bold fs-6" id="dbFilterMonthHeading" style="color: #171717;">{{ $period['month_label'] }}</div>
                <div class="small text-muted">Filtered by reporting period (event/created date).</div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-light border bg-white fw-semibold" id="dbPrevMonthBtn" data-month="{{ $period['prev_month'] }}">&lsaquo; Prev</button>
                <select id="dbMonthSelect" class="form-select form-select-sm bg-white" style="width: auto;">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected($m === $dbSelMonth)>{{ \Carbon\Carbon::create(2000, $m, 1)->format('F') }}</option>
                    @endfor
                </select>
                <select id="dbYearSelect" class="form-select form-select-sm bg-white" style="width: auto;">
                    @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                        <option value="{{ $y }}" @selected($y === $dbSelYear)>{{ $y }}</option>
                    @endfor
                </select>
                <button type="button" class="btn btn-sm btn-light border bg-white fw-semibold" id="dbNextMonthBtn" data-month="{{ $period['next_month'] }}">Next &rsaquo;</button>
                <button type="button" class="btn btn-sm btn-warning fw-semibold" id="dbCurrentMonthBtn" data-month="{{ now()->format('Y-m') }}">Current Month</button>
                <select id="dbCurrencySelect" class="form-select form-select-sm bg-white" style="width: auto;">
                    @foreach($currencies as $cur)
                        <option value="{{ $cur }}" @selected($cur === $selectedCurrency)>{{ $cur }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn-dark fw-semibold px-3 shadow-sm" id="dbRefreshBtn">
                    <i data-feather="refresh-cw" style="width: 14px; height: 14px;" class="me-1"></i>Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- ROW 2: Primary Selected-Period Booking KPIs -->
    <div class="row g-2 g-md-3 mb-3" id="dbBookingKpis">
        <div class="col-6 col-lg-3">
            <div class="db-kpi-card">
                <div class="db-kpi-bar" style="background: #3B82F6;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">New Bookings (Period)</div>
                        <div class="fs-4 fw-bold text-primary" id="kpiNewBookings">{{ $overview['kpis']['new_bookings'] }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Prev period: <span id="kpiPrevBookings">{{ $overview['kpis']['prev_new_bookings'] }}</span></div>
                    </div>
                    <span class="db-kpi-icon" style="background: #EFF6FF; color: #2563EB;"><i data-feather="calendar" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="db-kpi-card">
                <div class="db-kpi-bar" style="background: #16A34A;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Store Completed</div>
                        <div class="fs-4 fw-bold text-success" id="kpiStoreCompleted">{{ $overview['kpis']['store_completed'] }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Ready for / in final delivery</div>
                    </div>
                    <span class="db-kpi-icon" style="background: #ECFDF5; color: #16A34A;"><i data-feather="check-circle" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="db-kpi-card">
                <div class="db-kpi-bar" style="background: #8B5CF6;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Deliveries Completed</div>
                        <div class="fs-4 fw-bold" style="color: #6D28D9;" id="kpiDeliveriesCompleted">{{ $overview['kpis']['deliveries_completed'] }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Delivered to customer</div>
                    </div>
                    <span class="db-kpi-icon" style="background: #F5F3FF; color: #7C3AED;"><i data-feather="truck" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="db-kpi-card">
                <div class="db-kpi-bar" style="background: #EF4444;"></div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <div class="small text-muted fw-semibold">Cancelled Bookings</div>
                        <div class="fs-4 fw-bold text-danger" id="kpiCancelledBookings">{{ $overview['kpis']['cancelled_bookings'] }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Excluded from revenue</div>
                    </div>
                    <span class="db-kpi-icon" style="background: #FEF2F2; color: #DC2626;"><i data-feather="x-circle" style="width: 18px; height: 18px;"></i></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Action Required (Always Live, Independent of Selected Month) -->
    <div class="db-section-card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-bold small text-dark"><i data-feather="alert-triangle" style="width: 15px; height: 15px;" class="me-1 text-warning"></i>Current Action Required (Live Snapshot)</span>
            <span class="small text-muted">Always shows current unfinished operational &amp; financial tasks</span>
        </div>
        <div class="card-body p-3">
            <div class="row g-2" id="dbActionRequiredGrid">
                @foreach($operations['action_required'] as $alert)
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="{{ $alert['url'] }}" class="d-flex justify-content-between align-items-center p-2 px-3 rounded-3 border text-decoration-none {{ $alert['count'] > 0 ? 'bg-warning-subtle border-warning-subtle text-dark' : 'bg-light text-muted' }}">
                            <span class="small fw-semibold">{{ $alert['label'] }}</span>
                            <span class="badge rounded-pill {{ $alert['count'] > 0 ? 'bg-warning text-dark' : 'bg-secondary-subtle text-secondary' }}">{{ $alert['count'] }}</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Booking Workflow Distribution -->
    <div class="db-section-card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-bold small text-dark"><i data-feather="git-commit" style="width: 15px; height: 15px;" class="me-1 text-primary"></i>Booking Workflow Stages (Selected Period)</span>
            <a href="{{ route('admin.bookings.index') }}" class="small text-decoration-none fw-semibold">View All Bookings &rarr;</a>
        </div>
        <div class="card-body p-3">
            <div class="d-flex flex-wrap gap-2" id="dbWorkflowStagesWrap">
                @foreach($overview['workflow_stages'] as $st)
                    <a href="{{ $st['url'] }}" class="db-stage-pill">
                        <span>{{ $st['label'] }}</span>
                        <span class="badge bg-dark rounded-pill">{{ $st['count'] }}</span>
                        <span class="text-muted">({{ $st['percent'] }}%)</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Section Tabs: Operations | Finance | QR Tags & Activity -->
    <ul class="nav nav-pills db-nav-pills gap-1 mb-3 flex-wrap" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#dbTabOps" type="button"><i data-feather="activity" style="width:14px;height:14px;"></i>Operations &amp; Charts</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#dbTabFinance" type="button"><i data-feather="dollar-sign" style="width:14px;height:14px;"></i>Finance &amp; Settlements</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#dbTabTags" type="button"><i data-feather="tag" style="width:14px;height:14px;"></i>QR Tags &amp; Recent Activity</button></li>
    </ul>

    <div class="tab-content">
        <!-- TAB 1: Operations & Charts -->
        <div class="tab-pane fade show active" id="dbTabOps">
            <div class="row g-3 mb-3">
                <div class="col-12 col-lg-8">
                    <div class="db-section-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-dark">Booking Activity (Current vs Previous Period)</span>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary active db-group-btn" data-group="daily">Daily</button>
                                <button type="button" class="btn btn-outline-secondary db-group-btn" data-group="weekly">Weekly</button>
                                <button type="button" class="btn btn-outline-secondary db-group-btn" data-group="monthly">Monthly</button>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div id="chartBookingTrend" style="min-height: 260px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="db-section-card h-100">
                        <div class="card-header fw-bold small text-dark">Booking Status Overview</div>
                        <div class="card-body p-3">
                            <div id="chartStatusDonut" style="min-height: 260px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-lg-5">
                    <div class="db-section-card h-100">
                        <div class="card-header fw-bold small text-dark">Collection vs Final Delivery Jobs (Period)</div>
                        <div class="card-body p-3">
                            <div id="chartDeliveryJobs" style="min-height: 240px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="db-section-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-dark">Store Workload &amp; Processing</span>
                            <a href="{{ route('admin.stores.index') }}" class="small text-decoration-none">View All Stores &rarr;</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="db-table-scroll">
                                <table class="table table-hover align-middle small mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Store</th>
                                            <th>Active Work</th>
                                            <th>Received</th>
                                            <th>Completed</th>
                                            <th>Pending Handover</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dbStoreWorkloadBody">
                                        @forelse($operations['store_workload'] as $sw)
                                            <tr>
                                                <td><a href="{{ $sw['url'] }}" class="fw-semibold text-decoration-none">{{ $sw['name'] }}</a></td>
                                                <td><span class="badge bg-primary-subtle text-primary">{{ $sw['active_work'] }}</span></td>
                                                <td>{{ $sw['received_period'] }}</td>
                                                <td><span class="badge bg-success-subtle text-success">{{ $sw['completed_period'] }}</span></td>
                                                <td><span class="badge bg-warning-subtle text-warning-emphasis">{{ $sw['pending_handover'] }}</span></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-3">No active stores found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: Finance & Settlements -->
        <div class="tab-pane fade" id="dbTabFinance">
            <div class="row g-2 g-md-3 mb-3">
                <div class="col-6 col-lg-3">
                    <div class="db-kpi-card">
                        <div class="db-kpi-bar" style="background: #16A34A;"></div>
                        <div class="small text-muted fw-semibold">Verified Receipts (<span class="db-cur-code">{{ $finance['currency'] }}</span>)</div>
                        <div class="fs-5 fw-bold text-success" id="finReceipts">{{ $finance['kpis']['verified_receipts_formatted'] }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Stripe fees: <span id="finStripeFee">{{ $finance['kpis']['stripe_fee_formatted'] }}</span></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="db-kpi-card">
                        <div class="db-kpi-bar" style="background: #FFC629;"></div>
                        <div class="small text-muted fw-semibold">Platform Commission</div>
                        <div class="fs-5 fw-bold text-dark" id="finCommission">{{ $finance['kpis']['platform_commission_formatted'] }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Store Share: <span id="finStoreShare">{{ $finance['kpis']['store_share_formatted'] }}</span></div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="db-kpi-card">
                        <div class="db-kpi-bar" style="background: #3B82F6;"></div>
                        <div class="small text-muted fw-semibold">Delivery Person Earnings</div>
                        <div class="fs-5 fw-bold text-primary" id="finDpEarnings">{{ $finance['kpis']['dp_earnings_formatted'] }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Credited job earnings</div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="db-kpi-card">
                        <div class="db-kpi-bar" style="background: #8B5CF6;"></div>
                        <div class="small text-muted fw-semibold">COD Cash Custody (Current)</div>
                        <div class="fs-5 fw-bold" style="color: #6D28D9;" id="finCodCustody">{{ $finance['kpis']['cod_custody_formatted'] }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Held by Delivery Persons</div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <div class="db-section-card h-100">
                        <div class="card-header fw-bold small text-dark">Verified Customer Receipts by Method</div>
                        <div class="card-body p-3">
                            <div id="chartPaymentMethods" style="min-height: 240px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="db-section-card h-100">
                        <div class="card-header fw-bold small text-dark">Store Share vs Platform Commission</div>
                        <div class="card-body p-3">
                            <div id="chartStoreVsCommission" style="min-height: 240px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: QR Tags & Recent Activity -->
        <div class="tab-pane fade" id="dbTabTags">
            <div class="row g-3">
                <div class="col-12 col-lg-5">
                    <div class="db-section-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-dark">QR Laundry Tag Overview</span>
                            <a href="{{ route('admin.tags.index') }}" class="small text-decoration-none">Manage Tags &rarr;</a>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2 text-center mb-3 small">
                                <div class="col-4"><div class="p-2 border rounded-3 bg-light"><div class="text-muted">Generated</div><div class="fs-5 fw-bold">{{ $tags['total_generated'] }}</div></div></div>
                                <div class="col-4"><div class="p-2 border rounded-3 bg-light"><div class="text-muted">Available</div><div class="fs-5 fw-bold text-success">{{ $tags['available'] }}</div></div></div>
                                <div class="col-4"><div class="p-2 border rounded-3 bg-light"><div class="text-muted">Allocated</div><div class="fs-5 fw-bold text-primary">{{ $tags['allocated'] }}</div></div></div>
                            </div>
                            <div id="chartQrTags" style="min-height: 210px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="db-section-card h-100">
                        <div class="card-header fw-bold small text-dark">Recent System Activity</div>
                        <div class="card-body p-0">
                            <div class="db-table-scroll">
                                <table class="table table-hover align-middle small mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Module</th>
                                            <th>Reference</th>
                                            <th>Description</th>
                                            <th>Actor</th>
                                            <th>When</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dbActivityBody">
                                        @forelse($activity as $act)
                                            <tr>
                                                <td><span class="badge bg-light text-dark border">{{ $act['module'] }}</span></td>
                                                <td><code>{{ $act['reference'] }}</code></td>
                                                <td>{{ $act['description'] }}</td>
                                                <td>{{ $act['actor'] }}</td>
                                                <td class="text-nowrap text-muted">{{ $act['timestamp'] }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted py-3">No recent activity recorded.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    if (typeof ApexCharts === 'undefined') return;

    const state = {
        month: '{{ $period['month'] }}',
        preset: '{{ $period['preset'] }}',
        currency: '{{ $selectedCurrency }}',
        groupBy: 'daily',
    };

    const charts = {};

    function renderOrUpdate(id, options) {
        const el = document.getElementById(id);
        if (!el) return;
        if (charts[id]) {
            charts[id].updateOptions(options, true, true);
        } else {
            charts[id] = new ApexCharts(el, options);
            charts[id].render();
        }
    }

    function drawOverviewCharts(overview) {
        renderOrUpdate('chartBookingTrend', {
            chart: { type: 'area', height: 260, toolbar: { show: false } },
            series: [
                { name: 'Current Period', data: overview.trend.current },
                { name: 'Previous Period', data: overview.trend.previous },
            ],
            xaxis: { categories: overview.trend.labels },
            colors: ['#3B82F6', '#94A3B8'],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
            dataLabels: { enabled: false },
        });

        const nonZeroStages = (overview.workflow_stages || []).filter(s => s.count > 0);
        renderOrUpdate('chartStatusDonut', {
            chart: { type: 'donut', height: 260 },
            series: nonZeroStages.length ? nonZeroStages.map(s => s.count) : [1],
            labels: nonZeroStages.length ? nonZeroStages.map(s => s.label) : ['No Bookings'],
            colors: nonZeroStages.length ? ['#FFC629', '#3B82F6', '#16A34A', '#8B5CF6', '#F59E0B', '#EF4444', '#0EA5E9'] : ['#E2E8F0'],
            legend: { position: 'bottom', fontSize: '11px' },
        });
    }

    function drawOperationsCharts(ops) {
        renderOrUpdate('chartDeliveryJobs', {
            chart: { type: 'bar', height: 240, toolbar: { show: false } },
            series: [
                { name: 'Assigned', data: [ops.delivery_jobs.collection_assigned, ops.delivery_jobs.final_assigned] },
                { name: 'Completed', data: [ops.delivery_jobs.collection_completed, ops.delivery_jobs.final_completed] },
            ],
            xaxis: { categories: ['Collection Jobs', 'Final Delivery Jobs'] },
            colors: ['#FFC629', '#16A34A'],
            plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
        });
    }

    function drawFinanceCharts(fin) {
        renderOrUpdate('chartPaymentMethods', {
            chart: { type: 'donut', height: 240 },
            series: [
                Math.round(fin.by_method.stripe_minor / 100),
                Math.round(fin.by_method.paypal_minor / 100),
                Math.round(fin.by_method.cod_minor / 100),
            ],
            labels: ['Stripe', 'PayPal', 'COD'],
            colors: ['#635BFF', '#0070BA', '#16A34A'],
            legend: { position: 'bottom' },
        });

        renderOrUpdate('chartStoreVsCommission', {
            chart: { type: 'bar', height: 240, toolbar: { show: false } },
            series: [{
                name: fin.currency,
                data: [
                    Math.round(fin.kpis.store_share_minor / 100),
                    Math.round(fin.kpis.platform_commission_minor / 100),
                    Math.round(fin.kpis.dp_earnings_minor / 100),
                ],
            }],
            xaxis: { categories: ['Store Share', 'Platform Commission', 'DP Earnings'] },
            colors: ['#3B82F6', '#FFC629', '#16A34A'],
            plotOptions: { bar: { distributed: true, borderRadius: 6, columnWidth: '45%' } },
        });
    }

    function drawTagChart(tags) {
        renderOrUpdate('chartQrTags', {
            chart: { type: 'donut', height: 210 },
            series: [tags.pending_store_tags, tags.completed_store_tags],
            labels: ['Pending Store Tags', 'Completed Store Tags'],
            colors: ['#F59E0B', '#16A34A'],
            legend: { position: 'bottom' },
        });
    }

    // Initial render
    drawOverviewCharts(@json($overview));
    drawOperationsCharts(@json($operations));
    drawFinanceCharts(@json($finance));
    drawTagChart(@json($tags));

    async function refreshDashboard() {
        const params = new URLSearchParams({
            month: state.month,
            preset: state.preset,
            currency: state.currency,
            group_by: state.groupBy,
        });

        const [ovRes, opRes, finRes] = await Promise.all([
            fetch(`{{ route('admin.dashboard.overview') }}?${params}`, { headers: { 'Accept': 'application/json' } }).then(r => r.json()),
            fetch(`{{ route('admin.dashboard.operations') }}?${params}`, { headers: { 'Accept': 'application/json' } }).then(r => r.json()),
            fetch(`{{ route('admin.dashboard.finance') }}?${params}`, { headers: { 'Accept': 'application/json' } }).then(r => r.json()),
        ]);

        if (ovRes.success) {
            document.getElementById('dbPeriodBadge').textContent = ovRes.period.month_label;
            const fh = document.getElementById('dbFilterMonthHeading');
            if (fh) fh.textContent = ovRes.period.month_label;
            const parts = String(ovRes.period.month || '').split('-');
            if (parts.length === 2) {
                const yEl = document.getElementById('dbYearSelect');
                const mEl = document.getElementById('dbMonthSelect');
                if (yEl) yEl.value = String(parseInt(parts[0], 10));
                if (mEl) mEl.value = String(parseInt(parts[1], 10));
            }
            document.getElementById('dbPrevMonthBtn').dataset.month = ovRes.period.prev_month;
            document.getElementById('dbNextMonthBtn').dataset.month = ovRes.period.next_month;
            document.getElementById('kpiNewBookings').textContent = ovRes.data.kpis.new_bookings;
            document.getElementById('kpiPrevBookings').textContent = ovRes.data.kpis.prev_new_bookings;
            document.getElementById('kpiStoreCompleted').textContent = ovRes.data.kpis.store_completed;
            document.getElementById('kpiDeliveriesCompleted').textContent = ovRes.data.kpis.deliveries_completed;
            document.getElementById('kpiCancelledBookings').textContent = ovRes.data.kpis.cancelled_bookings;
            drawOverviewCharts(ovRes.data);
        }
        if (opRes.success) {
            drawOperationsCharts(opRes.data);
        }
        if (finRes.success) {
            const k = finRes.data.kpis;
            document.querySelectorAll('.db-cur-code').forEach(el => el.textContent = finRes.data.currency);
            document.getElementById('finReceipts').textContent = k.verified_receipts_formatted;
            document.getElementById('finStripeFee').textContent = k.stripe_fee_formatted;
            document.getElementById('finCommission').textContent = k.platform_commission_formatted;
            document.getElementById('finStoreShare').textContent = k.store_share_formatted;
            document.getElementById('finDpEarnings').textContent = k.dp_earnings_formatted;
            document.getElementById('finCodCustody').textContent = k.cod_custody_formatted;
            drawFinanceCharts(finRes.data);
        }
    }

    document.getElementById('dbPrevMonthBtn')?.addEventListener('click', function () {
        state.month = this.dataset.month;
        state.preset = 'month';
        refreshDashboard();
    });
    document.getElementById('dbNextMonthBtn')?.addEventListener('click', function () {
        state.month = this.dataset.month;
        state.preset = 'month';
        refreshDashboard();
    });
    document.getElementById('dbMonthSelect')?.addEventListener('change', function () {
        const y = document.getElementById('dbYearSelect')?.value || '{{ $dbSelYear }}';
        state.month = `${y}-${String(this.value).padStart(2, '0')}`;
        state.preset = 'month';
        refreshDashboard();
    });
    document.getElementById('dbYearSelect')?.addEventListener('change', function () {
        const m = document.getElementById('dbMonthSelect')?.value || '{{ $dbSelMonth }}';
        state.month = `${this.value}-${String(m).padStart(2, '0')}`;
        state.preset = 'month';
        refreshDashboard();
    });
    document.getElementById('dbCurrentMonthBtn')?.addEventListener('click', function () {
        state.month = this.dataset.month;
        state.preset = 'month';
        refreshDashboard();
    });
    document.getElementById('dbCurrencySelect')?.addEventListener('change', function () {
        state.currency = this.value;
        refreshDashboard();
    });
    document.getElementById('dbRefreshBtn')?.addEventListener('click', refreshDashboard);
    document.querySelectorAll('.db-group-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.db-group-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            state.groupBy = this.dataset.group;
            refreshDashboard();
        });
    });
})();
</script>
@endpush
