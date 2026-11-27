<div class="row g-3 mb-3">
    <div class="col-md-4 text-center">
        <div class="p-3 border rounded-3 bg-light">
            <img src="{{ $qrSvgDataUri }}" alt="{{ $tag->tag_code }}" style="width: 150px; height: 150px;" class="bg-white p-2 rounded border mb-2">
            <div class="fw-bold font-monospace fs-5 text-primary">{{ $tag->tag_code }}</div>
            <div class="small text-muted">Payload: <code>{{ $tag->qr_payload }}</code></div>
        </div>
    </div>
    <div class="col-md-8">
        <table class="table table-sm table-borderless small mb-0">
            <tr><th class="text-muted" style="width: 38%;">Status</th><td><span class="badge bg-dark">{{ $tag->status_label }}</span></td></tr>
            <tr><th class="text-muted">Batch</th><td><code>{{ $tag->batch?->batch_reference }}</code> ({{ $tag->batch?->generated_at?->format('d M Y, H:i') }})</td></tr>
            <tr><th class="text-muted">Print Status</th><td>{{ ucfirst(str_replace('_', ' ', $tag->print_status)) }} ({{ $tag->print_count }} print(s))</td></tr>
            <tr><th class="text-muted">Last Printed</th><td>{{ $tag->last_printed_at?->format('d M Y, H:i') ?? 'Not yet printed' }}</td></tr>
            <tr><th class="text-muted">Current Delivery Person</th><td>{{ $tag->currentDeliveryPerson?->full_name ?? '— (Admin Stock)' }}</td></tr>
            <tr><th class="text-muted">Current Booking</th><td>
                @if($tag->currentBooking)
                    <a href="{{ route('admin.bookings.show', $tag->currentBooking) }}" class="fw-semibold">{{ $tag->currentBooking->reference }}</a>
                    @if($tag->activeBookingAssignment?->package_label)
                        ({{ $tag->activeBookingAssignment->package_label }})
                    @endif
                @else
                    —
                @endif
            </td></tr>
            <tr><th class="text-muted">Store Verification</th><td>
                @if($tag->activeBookingAssignment?->store_verified_at)
                    <span class="text-success fw-semibold">Verified by {{ $tag->activeBookingAssignment->verifiedByStoreUser?->name ?? 'Store Staff' }} at {{ $tag->activeBookingAssignment->store_verified_at->format('d M Y, H:i') }}</span>
                @else
                    —
                @endif
            </td></tr>
            @if($tag->status_reason)
                <tr><th class="text-muted">Latest Reason / Note</th><td>{{ $tag->status_reason }}</td></tr>
            @endif
        </table>
    </div>
</div>

<h6 class="fw-bold small text-uppercase text-muted mb-2">Traceable Lifecycle Timeline</h6>
<div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
    <table class="table table-sm table-hover align-middle small mb-0">
        <thead class="table-light sticky-top">
            <tr>
                <th>Time</th>
                <th>Event</th>
                <th>Status Transition</th>
                <th>Actor</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tag->events as $event)
                <tr>
                    <td class="text-nowrap">{{ $event->occurred_at?->format('d M Y, H:i:s') }}</td>
                    <td><code>{{ $event->event_type }}</code></td>
                    <td>{{ $event->previous_status ? ($event->previous_status . ' → ') : '' }}<strong>{{ $event->new_status }}</strong></td>
                    <td>{{ $event->actor_name ?? ucfirst((string) $event->actor_type) }}</td>
                    <td>{{ $event->reason ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No lifecycle events recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
