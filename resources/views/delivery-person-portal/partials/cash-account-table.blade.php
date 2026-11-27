<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Booking</th>
                <th>Channel</th>
                <th class="text-end">Collected</th>
                <th class="text-end">Settled</th>
                <th class="text-end">Balance</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $tx)
                @php
                    $amt = \App\Services\StoreAccountService::decimalToMinor($tx->amount);
                    $bal = \App\Services\StoreAccountService::decimalToMinor($tx->balance_after);
                @endphp
                <tr>
                    <td class="text-nowrap">{{ $tx->created_at?->format('d M Y, H:i') }}</td>
                    <td class="fw-semibold">{{ $tx->booking?->reference ?? '-' }}</td>
                    <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $tx->payment_channel)) }}</span></td>
                    <td class="text-end text-success fw-semibold">{{ $tx->direction === 'credit' ? $currencyService->formatMinor($amt, $tx->currency) : '-' }}</td>
                    <td class="text-end text-danger fw-semibold">{{ $tx->direction === 'debit' ? $currencyService->formatMinor($amt, $tx->currency) : '-' }}</td>
                    <td class="text-end fw-bold">{{ $currencyService->formatMinor($bal, $tx->currency) }}</td>
                    <td>{{ $tx->description }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No cash custody transactions found for {{ $monthLabel ?? 'this month' }} and selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($transactions->hasPages())
    <div class="p-3 border-top d-flex justify-content-end dp-ca-pagination">
        {{ $transactions->links('pagination::bootstrap-5') }}
    </div>
@endif
