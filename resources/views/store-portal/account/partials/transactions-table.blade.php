<div class="sp-card p-3 sp-anim-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <h6 class="fw-bold mb-0" style="color: #171717;">Transaction History — {{ $monthLabel }}</h6>
            <span class="badge rounded-pill" style="background: #F1F5F9; color: #475569;">{{ $transactions->total() }} {{ Str::plural('transaction', $transactions->total()) }}</span>
        </div>
        <span class="small text-muted">Click a row's Details button for full ledger info</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Date &amp; Time</th>
                    <th>Transaction Ref</th>
                    <th>Booking Ref</th>
                    <th>Type</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Running Balance</th>
                    <th>Description</th>
                    <th class="text-end">Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    <tr class="small">
                        <td class="text-nowrap">{{ $tx->created_at?->format('d M Y, H:i:s') }}</td>
                        <td class="fw-semibold text-primary text-nowrap">{{ $tx->reference }}</td>
                        <td class="text-nowrap">
                            @if($tx->booking)
                                <a href="{{ route('store.bookings.show', $tx->booking) }}" class="fw-semibold text-decoration-none">{{ $tx->booking->reference }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $tx->type_label }}</span></td>
                        <td class="text-end fw-bold" style="color: #16A34A;">
                            {{ $tx->direction === 'credit' ? $tx->currency . ' ' . $tx->amount : '-' }}
                        </td>
                        <td class="text-end fw-bold" style="color: #EF4444;">
                            {{ $tx->direction === 'debit' ? $tx->currency . ' ' . $tx->amount : '-' }}
                        </td>
                        <td class="text-end fw-bold" style="color: #171717;">
                            {{ $tx->balance_after !== null ? $tx->currency . ' ' . $tx->balance_after : '-' }}
                        </td>
                        <td>{{ $tx->description }}</td>
                        <td class="text-end text-nowrap">
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary py-0 px-2 js-sp-tx-detail"
                                    data-ref="{{ $tx->reference }}"
                                    data-date="{{ $tx->created_at?->format('d M Y, H:i:s') }}"
                                    data-type="{{ $tx->type_label }}"
                                    data-direction="{{ ucfirst($tx->direction) }}"
                                    data-amount="{{ $tx->currency . ' ' . $tx->amount }}"
                                    data-balance="{{ $tx->balance_after !== null ? $tx->currency . ' ' . $tx->balance_after : '-' }}"
                                    data-booking="{{ $tx->booking?->reference ?? '-' }}"
                                    data-desc="{{ $tx->description ?? '-' }}">
                                View
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            No posted account transactions in {{ $monthLabel }}. Credits appear automatically when final delivery is Completed.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->hasPages())
        <div class="mt-3 sp-acc-pagination">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
