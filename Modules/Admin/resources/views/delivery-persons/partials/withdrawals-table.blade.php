<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small" style="min-width: 1020px;">
        <thead class="table-light">
            <tr>
                <th>Reference</th>
                <th>Delivery Person</th>
                <th>Request Date</th>
                <th>Month</th>
                <th>Bank Account</th>
                <th class="text-end">Requested</th>
                <th class="text-end">Avail. Before</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($withdrawals as $wd)
                @php
                    $meta = $wd->statusMeta();
                    $snap = $wd->bank_snapshot_json ?? [];
                @endphp
                <tr>
                    <td class="fw-bold text-nowrap">{{ $wd->withdrawal_reference }}</td>
                    <td>
                        <div class="fw-semibold">{{ $wd->deliveryPerson?->full_name ?? 'Delivery Person' }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">{{ $wd->deliveryPerson?->code ?? '-' }}</div>
                    </td>
                    <td class="text-nowrap">{{ $wd->requested_at?->format('d M Y, H:i') }}</td>
                    <td class="text-nowrap">{{ $wd->request_month }}</td>
                    <td>
                        <div class="fw-semibold">{{ $snap['bank_name'] ?? 'Bank' }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">{{ $snap['account_number_masked'] ?? '••••' }} ({{ $snap['country_code'] ?? '' }})</div>
                    </td>
                    <td class="text-end fw-bold text-nowrap">{{ $currencyService->formatMinor($wd->amount_minor, $wd->currency) }}</td>
                    <td class="text-end text-muted text-nowrap">{{ $currencyService->formatMinor($wd->available_balance_before_minor, $wd->currency) }}</td>
                    <td class="text-nowrap">
                        <span class="badge rounded-pill {{ $meta['badge'] }}">{{ $meta['label'] }}</span>
                    </td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-secondary adm-wd-view-btn"
                                data-url="{{ route('admin.delivery-person-withdrawals.show', $wd) }}">
                            View
                        </button>
                        @if($wd->status === \App\Models\DeliveryPersonWithdrawalRequest::STATUS_PENDING)
                            <button type="button" class="btn btn-sm btn-primary adm-wd-approve-btn"
                                    data-url="{{ route('admin.delivery-person-withdrawals.approve', $wd) }}"
                                    data-ref="{{ $wd->withdrawal_reference }}"
                                    data-dp="{{ $wd->deliveryPerson?->full_name ?? 'Delivery Person' }}"
                                    data-amount="{{ $currencyService->formatMinor($wd->amount_minor, $wd->currency) }}"
                                    data-bank="{{ ($snap['bank_name'] ?? 'Bank') . ' (' . ($snap['account_number_masked'] ?? '••••') . ')' }}">
                                Approve
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger adm-wd-reject-btn"
                                    data-url="{{ route('admin.delivery-person-withdrawals.reject', $wd) }}"
                                    data-ref="{{ $wd->withdrawal_reference }}">
                                Reject
                            </button>
                        @elseif($wd->status === \App\Models\DeliveryPersonWithdrawalRequest::STATUS_APPROVED)
                            <button type="button" class="btn btn-sm btn-success adm-wd-settle-btn"
                                    data-url="{{ route('admin.delivery-person-withdrawals.settle', $wd) }}"
                                    data-ref="{{ $wd->withdrawal_reference }}">
                                Mark Settled
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger adm-wd-reject-btn"
                                    data-url="{{ route('admin.delivery-person-withdrawals.reject', $wd) }}"
                                    data-ref="{{ $wd->withdrawal_reference }}">
                                Reject
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">No withdrawal requests match the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($withdrawals->hasPages())
    <div class="p-3 border-top adm-wd-pagination">
        {{ $withdrawals->links() }}
    </div>
@endif
