<!-- All-Time Account Balances (Independent of Selected Period) -->
@foreach($accountSummary as $curr => $row)
    <div class="sp-card p-3 mb-3 sp-anim-card" style="background: linear-gradient(135deg, #FFF8E5 0%, #FFFFFF 100%); border-color: #FDE68A;">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge rounded-pill px-2 py-1" style="background: #171717; color: #FFC629;">{{ $curr }} — Live Balances</span>
            <span class="text-muted" style="font-size: 0.75rem;">Independent of report period</span>
        </div>
        <div class="row g-2">
            <div class="col-6 col-md-3">
                <div class="p-2 rounded-2 bg-white border">
                    <div class="text-muted" style="font-size: 0.74rem;">Current Available Balance</div>
                    <div class="fs-5 fw-bold" style="color: #166534;">{{ $currencyService->formatMinor((int) $row['current_balance_minor'], $curr) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 rounded-2 bg-white border">
                    <div class="text-muted" style="font-size: 0.74rem;">Reserved (Pending/Approved)</div>
                    <div class="fs-5 fw-bold" style="color: #D97706;">{{ $currencyService->formatMinor((int) ($reservedByCurrency[$curr] ?? 0), $curr) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 rounded-2 bg-white border">
                    <div class="text-muted" style="font-size: 0.74rem;">Settled Withdrawals (Period)</div>
                    <div class="fs-5 fw-bold" style="color: #1D4ED8;">{{ $currencyService->formatMinor((int) ($settledPeriodByCurrency[$curr] ?? 0), $curr) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 rounded-2 bg-white border">
                    <div class="text-muted" style="font-size: 0.74rem;">All-Time Paid Out</div>
                    <div class="fs-5 fw-bold" style="color: #171717;">{{ $currencyService->formatMinor((int) $row['total_paid_out_minor'], $curr) }}</div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- Period Earnings Breakdown -->
<div class="sp-card p-3 mb-3 sp-anim-card">
    <h6 class="fw-bold mb-2" style="color: #171717;">Selected Period Earnings — {{ $period['label'] }}</h6>
    @if($earningsPeriod->isEmpty())
        <div class="text-muted small py-2">No finalized store earnings recorded in {{ $period['label'] }}.</div>
    @else
        <div class="row g-2">
            @foreach($earningsPeriod as $curr => $ep)
                <div class="col-12 col-md-6">
                    <div class="p-3 rounded-3 border bg-light">
                        <div class="fw-bold small mb-2">{{ $curr }}</div>
                        <div class="row g-2 small">
                            <div class="col-4">
                                <div class="text-muted">Eligible Revenue</div>
                                <div class="fw-bold">{{ $currencyService->formatMinor((int) $ep->revenue_minor, $curr) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Platform Commission</div>
                                <div class="fw-bold text-warning-emphasis">{{ $currencyService->formatMinor((int) $ep->commission_minor, $curr) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Net Store Share</div>
                                <div class="fw-bold text-success">{{ $currencyService->formatMinor((int) $ep->store_share_minor, $curr) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Financial Charts -->
<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="sp-card p-3 h-100 sp-anim-card">
            <h6 class="fw-bold mb-2" style="color: #171717;">Store Share vs Platform Commission</h6>
            <div id="spRepFinBarChart" style="min-height: 260px;"></div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="sp-card p-3 h-100 sp-anim-card">
            <h6 class="fw-bold mb-2" style="color: #171717;">Earnings Breakdown</h6>
            <div id="spRepFinDonutChart" style="min-height: 260px;"></div>
        </div>
    </div>
</div>
