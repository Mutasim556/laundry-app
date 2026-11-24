<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small" style="min-width: 740px;">
        <thead class="table-light">
            <tr>
                <th>Request Date</th>
                <th>Withdrawal Reference</th>
                <th>Bank Account</th>
                <th class="text-end">Amount</th>
                <th>Status</th>
                <th>Decision / Settled Date</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($withdrawals as $wd)
                @php
                    $meta = $wd->statusMeta();
                    $snap = $wd->bank_snapshot_json ?? [];
                    $decisionDate = $wd->settled_at ?? $wd->rejected_at ?? $wd->approved_at;
                @endphp
                <tr>
                    <td class="text-nowrap">{{ $wd->requested_at?->format('d M Y, H:i') }}</td>
                    <td class="fw-semibold text-nowrap">{{ $wd->withdrawal_reference }}</td>
                    <td>
                        <div class="fw-semibold">{{ $snap['bank_name'] ?? 'Bank Account' }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">{{ $snap['account_number_masked'] ?? '••••' }}</div>
                    </td>
                    <td class="text-end fw-bold text-nowrap">
                        {{ $currencyService->formatMinor((int) $wd->amount_minor, $wd->currency) }}
                    </td>
                    <td class="text-nowrap">
                        <span class="badge rounded-pill {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                        @if($wd->status === \App\Models\DeliveryPersonWithdrawalRequest::STATUS_REJECTED && $wd->rejection_reason)
                            <div class="text-danger mt-1" style="font-size: 0.72rem; max-width: 180px; white-space: normal;">
                                {{ \Illuminate\Support\Str::limit($wd->rejection_reason, 60) }}
                            </div>
                        @endif
                    </td>
                    <td class="text-nowrap text-muted">{{ $decisionDate ? $decisionDate->format('d M Y, H:i') : '-' }}</td>
                    <td class="text-end text-nowrap">
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary dp-view-wd-btn"
                                data-url="{{ route('delivery-person.withdrawals.show', $wd) }}">
                            <i class="mdi mdi-eye-outline me-1"></i>Details
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="mdi mdi-bank-transfer-out fs-4 d-block mb-1"></i>
                        No withdrawal requests created in this month.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($withdrawals->hasPages())
    <div class="p-3 border-top dp-ajax-pagination" data-target="withdrawals">
        {{ $withdrawals->links() }}
    </div>
@endif
