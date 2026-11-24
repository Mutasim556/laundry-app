<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small" style="min-width: 780px;">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Booking Reference</th>
                <th class="text-end">Eligible Service Revenue</th>
                <th class="text-end">Platform Commission</th>
                <th class="text-end">Store Share</th>
                <th>Earnings Status</th>
                <th>Credited Date</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($earnings as $e)
                @php
                    $isCompleted = $e->booking?->booking_status === \App\Models\Booking::STATUS_COMPLETED;
                    $dt = $e->finalized_at ?? $e->created_at;
                @endphp
                <tr>
                    <td class="text-nowrap">{{ $dt?->format('d M Y, H:i') }}</td>
                    <td class="fw-semibold text-nowrap">{{ $e->booking?->reference ?? '-' }}</td>
                    <td class="text-end text-nowrap">{{ $currencyService->formatMinor((int) $e->eligible_service_amount_minor, $e->currency) }}</td>
                    <td class="text-end text-danger text-nowrap">
                        -{{ $currencyService->formatMinor((int) $e->commission_amount_minor, $e->currency) }}
                        <span class="text-muted">({{ number_format((float) $e->commission_rate, 2) }}%)</span>
                    </td>
                    <td class="text-end fw-bold text-success text-nowrap">{{ $currencyService->formatMinor((int) $e->net_store_earnings_minor, $e->currency) }}</td>
                    <td class="text-nowrap">
                        @if($isCompleted)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Eligible &amp; Credited</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Pending Completion</span>
                        @endif
                    </td>
                    <td class="text-nowrap text-muted">{{ $isCompleted ? $dt?->format('d M Y') : '-' }}</td>
                    <td class="text-end text-nowrap">
                        @if($e->booking)
                            <a href="{{ route('store.bookings.show', $e->booking) }}" class="btn btn-sm btn-outline-secondary py-1 px-2">
                                <i class="mdi mdi-eye-outline me-1"></i>Booking
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="mdi mdi-cash-remove fs-4 d-block mb-1"></i>
                        No store booking earnings recorded for this month.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($earnings->hasPages())
    <div class="p-3 border-top sp-ajax-pagination" data-target="earnings">
        {{ $earnings->links() }}
    </div>
@endif
