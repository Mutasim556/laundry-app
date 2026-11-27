@php
    $deltaBadge = function (array $d, bool $higherIsGood = true): string {
        if ($d['previous'] === null) {
            return '';
        }
        $pctText = $d['pct'] === null ? 'N/A' : (($d['pct'] > 0 ? '+' : '') . $d['pct'] . '%');
        $isGood = $higherIsGood ? $d['direction'] === 'up' : $d['direction'] === 'down';
        $color = $d['direction'] === 'flat' ? '#64748B' : ($isGood ? '#166534' : '#991B1B');
        $bg = $d['direction'] === 'flat' ? '#F1F5F9' : ($isGood ? '#DCFCE7' : '#FEE2E2');
        $arrow = $d['direction'] === 'up' ? '&uarr;' : ($d['direction'] === 'down' ? '&darr;' : '&rarr;');
        return '<span class="badge rounded-pill px-2 py-0" style="background:' . $bg . ';color:' . $color . ';font-size:0.7rem;">' . $arrow . ' ' . $pctText . ' (vs ' . e($d['previous']) . ')</span>';
    };
@endphp

<!-- Current Outstanding Work (Live Across All Months) -->
<div class="sp-card p-3 mb-3 sp-anim-card" style="background: {{ $outstanding['total'] > 0 ? '#FFF8E5' : '#F8FAFC' }}; border-color: {{ $outstanding['total'] > 0 ? '#FDE68A' : '#E2E8F0' }};">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="sp-icon-box" style="background: #FEF3C7; color: #92400E;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <div>
                <div class="fw-bold small" style="color: #171717;">Current Outstanding Work (All Months)</div>
                <div class="text-muted" style="font-size: 0.78rem;">Live operational obligations independent of the selected report period</div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 small">
            <span class="badge bg-white text-dark border px-2 py-1">Processing: <strong>{{ $outstanding['processing'] }}</strong></span>
            <span class="badge bg-white text-dark border px-2 py-1">Pending Handovers: <strong>{{ $outstanding['pending_handovers'] }}</strong></span>
            <span class="badge bg-white text-dark border px-2 py-1">Awaiting Collection: <strong>{{ $outstanding['awaiting_collection'] }}</strong></span>
        </div>
    </div>
</div>

<!-- 6 Core Overview KPIs -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="sp-card p-3 h-100 sp-anim-card">
            <div class="small text-muted">Assigned Bookings</div>
            <div class="fs-4 fw-bold mt-1" style="color: #171717;">{{ $stats['assigned'] }}</div>
            <div class="mt-1">{!! $deltaBadge($deltas['assigned'], true) !!}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="sp-card p-3 h-100 sp-anim-card">
            <div class="small text-muted">Store Received</div>
            <div class="fs-4 fw-bold mt-1" style="color: #0E7490;">{{ $stats['received'] }}</div>
            <div class="mt-1">{!! $deltaBadge($deltas['received'], true) !!}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="sp-card p-3 h-100 sp-anim-card">
            <div class="small text-muted">Store Completed</div>
            <div class="fs-4 fw-bold mt-1" style="color: #166534;">{{ $stats['completed'] }}</div>
            <div class="mt-1">{!! $deltaBadge($deltas['completed'], true) !!}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="sp-card p-3 h-100 sp-anim-card">
            <div class="small text-muted">Confirmed Handovers</div>
            <div class="fs-4 fw-bold mt-1" style="color: #6D28D9;">{{ $stats['handovers'] }}</div>
            <div class="mt-1">{!! $deltaBadge($deltas['handovers'], true) !!}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="sp-card p-3 h-100 sp-anim-card">
            <div class="small text-muted">Avg Processing</div>
            <div class="fs-4 fw-bold mt-1" style="color: #B45309;">{{ $stats['avg_processing_hours'] !== null ? $stats['avg_processing_hours'] . 'h' : 'N/A' }}</div>
            <div class="mt-1">{!! $deltaBadge($deltas['avg_processing_hours'], false) !!}</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="sp-card p-3 h-100 sp-anim-card" style="background: #F0FDF4; border-color: #BBF7D0;">
            <div class="small text-muted">Store Share Earned</div>
            @if(empty($stats['earnings_by_currency']))
                <div class="fs-5 fw-bold mt-1" style="color: #166534;">0.00</div>
            @else
                @foreach($stats['earnings_by_currency'] as $er)
                    <div class="fw-bold mt-1" style="color: #166534; font-size: 1.05rem;">
                        {{ $currencyService->formatMinor($er['store_share_minor'], $er['currency']) }}
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
