<div class="dp-card p-3 mb-3" style="background: #EFF6FF; border-color: #BFDBFE;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <div class="small fw-bold" style="color: #1E3A8A;">Current Outstanding Cash in Custody (All Months)</div>
            <div class="text-muted" style="font-size: 0.78rem;">Platform/customer COD funds held in custody (separate from Delivery Person earnings)</div>
        </div>
        <div class="fs-4 fw-bold text-primary">{{ $currencyService->formatMinor($custodyMinor, $currency) }}</div>
    </div>
</div>

<div class="dp-card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Cash Account Transactions — {{ $period['label'] }}</h6>
        <span class="badge rounded-pill bg-light text-dark border">{{ $transactions->total() }} {{ Str::plural('transaction', $transactions->total()) }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase text-muted">
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Booking</th>
                    <th>Type</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    <tr class="small">
                        <td class="text-nowrap">{{ $tx->created_at?->format('d M Y, H:i') }}</td>
                        <td class="fw-bold">{{ $tx->reference }}</td>
                        <td>{{ $tx->booking?->reference ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border text-capitalize">{{ str_replace('_', ' ', $tx->transaction_type) }}</span></td>
                        <td class="text-end fw-bold {{ $tx->direction === 'debit' ? 'text-primary' : 'text-success' }}">
                            {{ $tx->direction === 'debit' ? '+' : '-' }}{{ $tx->currency }} {{ $tx->amount }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">No cash account transactions in {{ $period['label'] }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
        <div class="mt-3 dp-rep-ca-pagination">{{ $transactions->links() }}</div>
    @endif
</div>
