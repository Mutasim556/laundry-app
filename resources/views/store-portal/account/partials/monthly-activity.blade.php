<div class="sp-card p-3 mb-3 sp-anim-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <span class="sp-icon-box" style="background: #FEF3C7; color: #92400E;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </span>
            <div>
                <h6 class="fw-bold mb-0" style="color: #171717;">Selected Month Activity — {{ $monthLabel }}</h6>
                <div class="text-muted" style="font-size: 0.78rem;">Ledger credits &amp; payouts posted during {{ $monthLabel }} (overall account balances above remain all-time)</div>
            </div>
        </div>
    </div>

    @if(empty($monthlyActivityByCurrency))
        <div class="p-3 rounded-2 text-center text-muted small" style="background: #F8FAFC; border: 1px dashed #CBD5E1;">
            No posted earnings or payouts in {{ $monthLabel }}.
        </div>
    @else
        <div class="row g-2">
            @foreach($monthlyActivityByCurrency as $row)
                <div class="col-12 {{ count($monthlyActivityByCurrency) > 1 ? 'col-lg-6' : '' }}">
                    <div class="p-3 rounded-3 border" style="background: #FFFDF7; border-color: #FDE68A !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge rounded-pill px-2 py-1" style="background: #171717; color: #FFC629;">{{ $row['currency'] }}</span>
                            <span class="small text-muted">{{ $monthLabel }}</span>
                        </div>
                        <div class="row g-2">
                            <div class="col-4">
                                <div class="text-muted" style="font-size: 0.75rem;">Store Share Earned</div>
                                <div class="fw-bold fs-6" style="color: #166534;">
                                    {{ $currencyService->formatMinor((int) $row['store_share_minor'], $row['currency']) }}
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size: 0.75rem;">Platform Commission</div>
                                <div class="fw-bold fs-6" style="color: #92400E;">
                                    {{ $currencyService->formatMinor((int) $row['commission_minor'], $row['currency']) }}
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size: 0.75rem;">Payouts Disbursed</div>
                                <div class="fw-bold fs-6" style="color: #1D4ED8;">
                                    {{ $currencyService->formatMinor((int) $row['payout_minor'], $row['currency']) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
