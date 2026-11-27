<div class="sp-card p-3 sp-anim-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <h6 class="fw-bold mb-0" style="color: #171717;">
                @if(!empty($filters['pending_only']))
                    All Pending Handovers
                @else
                    Handovers — {{ $monthLabel }}
                @endif
            </h6>
            <span class="badge rounded-pill" style="background: #F1F5F9; color: #475569;">{{ $handovers->total() }} {{ Str::plural('record', $handovers->total()) }}</span>
        </div>
        <span class="small text-muted">Sorted by {{ match($filters['sort'] ?? 'pending_first') { 'newest' => 'Newest First', 'oldest' => 'Oldest First', default => 'Pending First' } }}</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Request Ref</th>
                    <th>Booking</th>
                    <th>Delivery Person</th>
                    <th>Items / Notes</th>
                    <th>Status</th>
                    <th>Timestamps</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($handovers as $req)
                    @php
                        $statusBadge = match ($req->status) {
                            'accepted' => ['bg' => '#DCFCE7', 'text' => '#166534', 'label' => 'Accepted'],
                            'rejected' => ['bg' => '#FEE2E2', 'text' => '#991B1B', 'label' => 'Issue Reported'],
                            default => ['bg' => '#FEF3C7', 'text' => '#92400E', 'label' => 'Pending Verification'],
                        };
                        $bookingRef = $req->booking?->reference ?? ('#' . $req->booking_id);
                        $dpName = $req->collectionDeliveryPerson?->full_name ?? $req->collectionDeliveryPerson?->name ?? 'Delivery Person';
                        $itemsSummary = $req->items_summary ?? ($req->booking?->items?->map(fn ($i) => ($i->quantity ?? 1) . 'x ' . ($i->service_name ?? $i->item_name ?? 'Item'))->implode(', ') ?: 'Standard laundry bag');
                    @endphp
                    <tr class="small">
                        <td class="text-nowrap">
                            <span class="fw-bold" style="color: #171717;">{{ $req->request_reference ?? ('REQ-' . $req->id) }}</span>
                        </td>
                        <td>
                            @if($req->booking)
                                <a href="{{ route('store.bookings.show', $req->booking) }}" class="fw-bold text-decoration-none" style="color: #1D4ED8;">{{ $bookingRef }}</a>
                                <div class="text-muted">{{ $req->booking->customer_name ?? 'Customer' }}</div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold" style="color: #171717;">{{ $dpName }}</div>
                            @if($req->collectionDeliveryPerson?->phone)
                                <div class="text-muted">{{ $req->collectionDeliveryPerson->phone_country_code }} {{ $req->collectionDeliveryPerson->phone }}</div>
                            @endif
                        </td>
                        <td style="max-width: 240px;">
                            <div class="text-truncate" title="{{ $itemsSummary }}">{{ $itemsSummary }}</div>
                            @if($req->status === 'rejected' && !empty($req->rejection_reason))
                                <div class="text-danger mt-1" style="font-size: 0.78rem;">Reason: {{ $req->rejection_reason }}</div>
                            @elseif(!empty($req->request_note))
                                <div class="text-muted mt-1" style="font-size: 0.78rem;">{{ $req->request_note }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge rounded-pill px-2 py-1" style="background: {{ $statusBadge['bg'] }}; color: {{ $statusBadge['text'] }};">
                                {{ $statusBadge['label'] }}
                            </span>
                        </td>
                        <td class="text-nowrap text-muted">
                            <div>Req: {{ ($req->requested_at ?? $req->created_at)?->format('d M Y, H:i') }}</div>
                            @if($req->accepted_at)
                                <div class="text-success">Acc: {{ $req->accepted_at->format('d M Y, H:i') }}</div>
                            @elseif($req->rejected_at)
                                <div class="text-danger">Rej: {{ $req->rejected_at->format('d M Y, H:i') }}</div>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if($req->status === 'pending')
                                <div class="d-inline-flex gap-1">
                                    <button type="button"
                                            class="btn btn-sm btn-success fw-semibold js-store-open-accept"
                                            data-action="{{ route('store.handovers.accept', $req) }}"
                                            data-ref="{{ $bookingRef }}"
                                            data-dp="{{ $dpName }}"
                                            data-summary="{{ $itemsSummary }}"
                                            data-store="{{ $store->name ?? 'Store' }}">
                                        Accept
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger js-store-open-reject"
                                            data-action="{{ route('store.handovers.reject', $req) }}"
                                            data-ref="{{ $bookingRef }}">
                                        Report Issue
                                    </button>
                                </div>
                            @elseif($req->booking)
                                <a href="{{ route('store.bookings.show', $req->booking) }}" class="btn btn-sm btn-outline-dark">View Booking</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            No handovers match the selected month and filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($handovers->hasPages())
        <div class="mt-3 sp-ho-pagination">
            {{ $handovers->links() }}
        </div>
    @endif
</div>
