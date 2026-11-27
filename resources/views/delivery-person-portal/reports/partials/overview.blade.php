@php
    $deltaBadge = function (array $d): string {
        if ($d['previous'] === null) return '';
        $pct = $d['pct'] === null ? 'N/A' : (($d['pct'] > 0 ? '+' : '') . $d['pct'] . '%');
        $color = $d['direction'] === 'up' ? '#166534' : ($d['direction'] === 'down' ? '#991B1B' : '#64748B');
        $bg = $d['direction'] === 'up' ? '#DCFCE7' : ($d['direction'] === 'down' ? '#FEE2E2' : '#F1F5F9');
        $arrow = $d['direction'] === 'up' ? '&uarr;' : ($d['direction'] === 'down' ? '&darr;' : '&rarr;');
        return '<span class="badge rounded-pill px-2 py-0" style="background:' . $bg . ';color:' . $color . ';font-size:0.7rem;">' . $arrow . ' ' . $pct . '</span>';
    };
@endphp

<!-- Current Snapshot Metrics (Independent of Selected Month) -->
<div class="dp-card p-3 mb-3" style="background: #FFF8E5; border-color: #FDE68A;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="fw-bold small" style="color: #171717;">Current Live Snapshot (All Months)</div>
            <div class="text-muted" style="font-size: 0.78rem;">True current balances and active jobs regardless of selected report period</div>
        </div>
        <div class="d-flex flex-wrap gap-2 small">
            <span class="badge bg-white text-dark border px-2 py-1">Active Jobs: <strong>{{ $activeJobsCount }}</strong></span>
            <span class="badge bg-white text-success border px-2 py-1">Available Earnings: <strong>{{ $currencyService->formatMinor($earningsBalanceMinor, $currency) }}</strong></span>
            <span class="badge bg-white text-primary border px-2 py-1">Cash in Custody: <strong>{{ $currencyService->formatMinor($cashBalanceMinor, $currency) }}</strong></span>
        </div>
    </div>
</div>

<!-- Selected-Period Metrics -->
<div class="row g-2 g-md-3">
    <div class="col-6 col-md-3">
        <div class="dp-card p-3 h-100">
            <div class="small text-muted">Total Assigned Jobs</div>
            <div class="fs-4 fw-bold mt-1">{{ $stats['total_jobs'] }}</div>
            <div class="mt-1">{!! $deltaBadge($deltas['total_jobs']) !!}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card p-3 h-100">
            <div class="small text-muted">Collection Jobs</div>
            <div class="fs-4 fw-bold mt-1 text-primary">{{ $stats['collections'] }}</div>
            <div class="mt-1">{!! $deltaBadge($deltas['collections']) !!}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card p-3 h-100">
            <div class="small text-muted">Final Delivery Jobs</div>
            <div class="fs-4 fw-bold mt-1 text-info-emphasis">{{ $stats['final_deliveries'] }}</div>
            <div class="mt-1">{!! $deltaBadge($deltas['final_deliveries']) !!}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card p-3 h-100">
            <div class="small text-muted">Completed Jobs</div>
            <div class="fs-4 fw-bold mt-1 text-success">{{ $stats['completed'] }}</div>
            <div class="mt-1">{!! $deltaBadge($deltas['completed']) !!}</div>
        </div>
    </div>
</div>
