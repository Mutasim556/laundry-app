<div class="sp-card p-3 sp-anim-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h6 class="fw-bold mb-0" style="color: #171717;">Ledger Transactions — {{ $period['label'] }}</h6>
        <span class="badge rounded-pill bg-light text-dark border">{{ $transactions->total() }} {{ Str::plural('record', $transactions->total()) }}</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Date &amp; Time</th>
                    <th>Reference</th>
                    <th>Booking</th>
                    <th>Type</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Balance</th>
                    <th class="text-end">Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    <tr class="small">
                        <td class="text-nowrap">{{ $tx->created_at?->format('d M Y, H:i') }}</td>
                        <td class="fw-semibold text-primary text-nowrap">{{ $tx->reference }}</td>
                        <td class="text-nowrap">{{ $tx->booking?->reference ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $tx->type_label }}</span></td>
                        <td class="text-end fw-bold text-success">{{ $tx->direction === 'credit' ? $tx->currency . ' ' . $tx->amount : '-' }}</td>
                        <td class="text-end fw-bold text-danger">{{ $tx->direction === 'debit' ? $tx->currency . ' ' . $tx->amount : '-' }}</td>
                        <td class="text-end fw-bold">{{ $tx->balance_after !== null ? $tx->currency . ' ' . $tx->balance_after : '-' }}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 js-rep-tx-view" data-id="{{ $tx->id }}">View</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No transactions found for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="mt-3 sp-rep-tx-pagination">{{ $transactions->links() }}</div>
    @endif
</div>
