<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small" style="min-width: 700px;">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Booking</th>
                <th>Ledger Event</th>
                <th class="text-end">Balance Impact</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledgerEntries as $entry)
                @php
                    $dt = $entry->occurred_at ?? $entry->created_at;
                    $typeLabel = match ($entry->transaction_type) {
                        \Modules\Admin\Models\DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING => 'Earnings Credit',
                        \Modules\Admin\Models\DeliveryPersonLedgerEntry::TYPE_WITHDRAWAL_RESERVATION => 'Withdrawal Reservation',
                        \Modules\Admin\Models\DeliveryPersonLedgerEntry::TYPE_WITHDRAWAL_RELEASE => 'Withdrawal Release',
                        \Modules\Admin\Models\DeliveryPersonLedgerEntry::TYPE_PAYOUT => 'Direct Payout',
                        \Modules\Admin\Models\DeliveryPersonLedgerEntry::TYPE_AUTHORIZED_CORRECTION => 'Authorized Correction',
                        default => ucfirst(str_replace('_', ' ', (string) $entry->transaction_type)),
                    };
                @endphp
                <tr>
                    <td class="text-nowrap">{{ $dt?->format('d M Y, H:i') }}</td>
                    <td class="fw-semibold text-nowrap">{{ $entry->entry_reference }}</td>
                    <td class="text-nowrap">{{ $entry->booking?->reference ?? '-' }}</td>
                    <td class="text-nowrap">
                        <span class="badge bg-light text-dark border">{{ $typeLabel }}</span>
                    </td>
                    <td class="text-end fw-bold text-nowrap {{ $entry->direction === 'credit' ? 'text-success' : 'text-danger' }}">
                        {{ $entry->direction === 'credit' ? '+' : '-' }}{{ $currencyService->formatMinor((int) $entry->amount_minor, $entry->currency) }}
                    </td>
                    <td>{{ $entry->notes ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="mdi mdi-book-open-variant fs-4 d-block mb-1"></i>
                        No account ledger activity in this month.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($ledgerEntries->hasPages())
    <div class="p-3 border-top dp-ajax-pagination" data-target="ledger">
        {{ $ledgerEntries->links() }}
    </div>
@endif
