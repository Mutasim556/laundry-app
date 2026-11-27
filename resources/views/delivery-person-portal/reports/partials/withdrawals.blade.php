<div class="row g-2 mb-3">
    <div class="col-6">
        <div class="dp-card p-3">
            <div class="small text-muted">Currently Reserved (Pending/Approved)</div>
            <div class="fs-4 fw-bold text-warning-emphasis">{{ $currencyService->formatMinor($reservedMinor, $currency) }}</div>
        </div>
    </div>
    <div class="col-6">
        <div class="dp-card p-3">
            <div class="small text-muted">Settled in {{ $period['label'] }}</div>
            <div class="fs-4 fw-bold text-success">{{ $currencyService->formatMinor($settledMinor, $currency) }}</div>
        </div>
    </div>
</div>

<div class="dp-card p-3 mb-3">
    <h6 class="fw-bold mb-2">Daily Withdrawal Requests — {{ $period['label'] }}</h6>
    <div id="dpRepWdBarChart" style="min-height: 220px;"></div>
</div>

<div class="dp-card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Withdrawal Requests — {{ $period['label'] }}</h6>
        <span class="badge rounded-pill bg-light text-dark border">{{ $withdrawals->total() }} {{ Str::plural('request', $withdrawals->total()) }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase text-muted">
                    <th>Reference</th>
                    <th>Date</th>
                    <th>Bank Account</th>
                    <th>Status</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($withdrawals as $w)
                    <tr class="small">
                        <td class="fw-bold">{{ $w->reference }}</td>
                        <td class="text-nowrap">{{ $w->created_at?->format('d M Y, H:i') }}</td>
                        <td>{{ $w->bankAccount ? ($w->bankAccount->bank_name . ' (' . $w->bankAccount->account_number_masked . ')') : '-' }}</td>
                        <td><span class="badge bg-light text-dark border text-capitalize">{{ str_replace('_', ' ', $w->status) }}</span></td>
                        <td class="text-end fw-bold">{{ $currencyService->formatMinor((int) $w->amount_minor, $w->currency) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">No withdrawal requests in {{ $period['label'] }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($withdrawals->hasPages())
        <div class="mt-3 dp-rep-wd-pagination">{{ $withdrawals->links() }}</div>
    @endif
</div>
