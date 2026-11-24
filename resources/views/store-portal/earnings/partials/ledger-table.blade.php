<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small" style="min-width: 720px;">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Booking</th>
                <th>Account Event</th>
                <th class="text-end">Balance Impact</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledgerEntries as $tx)
                @php
                    $amtMinor = \App\Services\StoreAccountService::decimalToMinor($tx->amount);
                    $typeLabel = match ($tx->transaction_type) {
                        \App\Models\StoreTransaction::TYPE_BOOKING_EARNING => 'Store Earnings Credit',
                        \App\Models\StoreTransaction::TYPE_WITHDRAWAL_RESERVATION => 'Withdrawal Reservation',
                        \App\Models\StoreTransaction::TYPE_WITHDRAWAL_RELEASE => 'Withdrawal Release',
                        \App\Models\StoreTransaction::TYPE_PAYOUT => 'Direct Store Payout',
                        \App\Models\StoreTransaction::TYPE_POSITIVE_ADJUSTMENT,
                        \App\Models\StoreTransaction::TYPE_NEGATIVE_ADJUSTMENT => 'Authorized Adjustment',
                        default => ucfirst(str_replace('_', ' ', (string) $tx->transaction_type)),
                    };
                @endphp
                <tr>
                    <td class="text-nowrap">{{ $tx->created_at?->format('d M Y, H:i') }}</td>
                    <td class="fw-semibold text-nowrap">{{ $tx->reference }}</td>
                    <td class="text-nowrap">{{ $tx->booking?->reference ?? '-' }}</td>
                    <td class="text-nowrap"><span class="badge bg-light text-dark border">{{ $typeLabel }}</span></td>
                    <td class="text-end fw-bold text-nowrap {{ $tx->direction === 'credit' ? 'text-success' : 'text-danger' }}">
                        {{ $tx->direction === 'credit' ? '+' : '-' }}{{ $currencyService->formatMinor($amtMinor, $tx->currency) }}
                    </td>
                    <td>{{ $tx->description ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="mdi mdi-book-open-variant fs-4 d-block mb-1"></i>
                        No store account ledger activity in this month.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($ledgerEntries->hasPages())
    <div class="p-3 border-top sp-ajax-pagination" data-target="ledger">
        {{ $ledgerEntries->links() }}
    </div>
@endif
