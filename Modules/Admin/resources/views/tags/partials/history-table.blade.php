<div class="tm-table-scroll">
    <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
            <tr>
                <th>Date / Time</th>
                <th>Tag Code</th>
                <th>Event</th>
                <th>Status</th>
                <th>Delivery Person</th>
                <th>Booking Reference</th>
                <th>Store</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $event)
                @php
                    $detailPayload = [
                        'tag_code' => $event->tag?->tag_code ?? '—',
                        'event_type' => $event->event_type,
                        'previous_status' => $event->previous_status ?? '—',
                        'new_status' => $event->new_status ?? '—',
                        'actor' => $event->actor_name ?? ucfirst((string) $event->actor_type),
                        'timestamp' => $event->occurred_at?->format('d M Y, H:i:s') ?? '—',
                        'booking_ref' => $event->booking?->reference ?? '—',
                        'store_name' => $event->store?->name ?? '—',
                        'dp_name' => $event->deliveryPerson?->full_name ?? '—',
                        'reason' => $event->reason ?? '—',
                    ];
                @endphp
                <tr>
                    <td class="text-nowrap">{{ $event->occurred_at?->format('d M Y, H:i') }}</td>
                    <td><code class="fw-bold">{{ $event->tag?->tag_code ?? '—' }}</code></td>
                    <td><span class="badge bg-light text-dark border">{{ $event->event_type }}</span></td>
                    <td class="text-nowrap">{{ $event->previous_status ? ($event->previous_status . ' → ') : '' }}<strong>{{ $event->new_status }}</strong></td>
                    <td>{{ $event->deliveryPerson?->full_name ?? '—' }}</td>
                    <td>
                        @if($event->booking)
                            <a href="{{ route('admin.bookings.show', $event->booking) }}" class="fw-semibold text-decoration-none">{{ $event->booking->reference }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $event->store?->name ?? '—' }}</td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-dark btn-history-detail"
                            data-detail='@json($detailPayload)'>Details</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="tm-empty-state">
                            <div class="tm-empty-icon"><i data-feather="clock" style="width:22px;height:22px;"></i></div>
                            <div class="fw-semibold text-dark">No tag activity found for the selected period.</div>
                            <div class="small">Try switching months or clearing your search filters.</div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($events->hasPages())
    <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2 fg-history-pagination">
        <div class="small text-muted">Showing {{ $events->firstItem() }}–{{ $events->lastItem() }} of {{ $events->total() }} events</div>
        <div>{!! $events->links() !!}</div>
    </div>
@endif
