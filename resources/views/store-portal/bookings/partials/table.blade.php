<div class="sp-card p-3">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Booking Ref</th>
                    <th>Assigned Date</th>
                    <th>Collection Schedule</th>
                    <th>Delivery Schedule</th>
                    <th>Customer / Hotel</th>
                    <th>Workflow Status</th>
                    <th>Pricing</th>
                    <th>Store Net</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $bk)
                    @php
                        $sched = $bk->schedule_snapshot ?? [];
                        $addr = $bk->address_snapshot ?? [];
                        $earning = $bk->currentStoreEarning;
                        $stStatus = $storeStatusMap[$bk->id] ?? null;
                        $assignDt = $bk->currentStoreAssignment?->assigned_at ?? $bk->created_at;
                    @endphp
                    <tr class="small">
                        <td class="fw-bold text-nowrap">{{ $bk->reference }}</td>
                        <td class="text-nowrap text-muted">{{ $assignDt?->format('d M Y') }}</td>
                        <td class="text-nowrap">{{ $sched['collection_date'] ?? '-' }}<br><span class="text-muted">{{ $sched['collection_slot'] ?? '' }}</span></td>
                        <td class="text-nowrap">{{ $sched['delivery_date'] ?? '-' }}<br><span class="text-muted">{{ $sched['delivery_slot'] ?? '' }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ $bk->customer_name }}</div>
                            @if(!empty($addr['postcode']))<div class="text-muted">{{ $addr['postcode'] }}</div>@endif
                        </td>
                        <td>
                            <span class="badge rounded-pill px-2 py-1" style="{{ $stStatus['badge_style'] ?? 'background:#F1F5F9;color:#475569;' }}">
                                {{ $stStatus['label'] ?? $bk->getStatusLabel() }}
                            </span>
                        </td>
                        <td>
                            @if($earning)
                                <span class="badge bg-success-subtle text-success">Finalized</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis">Pending</span>
                            @endif
                        </td>
                        <td class="fw-bold text-nowrap">{{ $earning ? $currencyService->formatMinor((int) $earning->net_store_earnings_minor, $earning->currency) : '-' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('store.bookings.show', $bk) }}" class="btn btn-xs sp-btn-yellow px-2 py-1">View Details</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <div class="fw-semibold text-dark mb-1">No bookings found for {{ $monthLabel ?? 'this month' }}.</div>
                            <div class="small">Try switching months or resetting your filters.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($bookings->hasPages())
        <div class="pt-3 mt-3 border-top d-flex justify-content-end sp-bk-pagination">
            {{ $bookings->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
