<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
            <tr>
                <th>Tag Code</th>
                <th>Status</th>
                <th>Assigned Booking</th>
                <th>Package</th>
                <th>Allocated At</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tags as $tag)
                @php
                    $badge = match ($tag->status) {
                        'allocated' => 'primary',
                        'assigned' => 'info text-dark',
                        'at_store' => 'warning text-dark',
                        'in_final_delivery' => 'dark',
                        default => 'secondary',
                    };
                @endphp
                <tr>
                    <td><code class="fw-bold">{{ $tag->tag_code }}</code></td>
                    <td><span class="badge bg-{{ $badge }}">{{ $tag->status_label }}</span></td>
                    <td>
                        @if($tag->currentBooking)
                            <a href="{{ route('delivery-person.bookings.show', $tag->currentBooking) }}" class="fw-semibold text-decoration-none">
                                {{ $tag->currentBooking->reference }}
                            </a>
                        @else
                            — (Ready for Use)
                        @endif
                    </td>
                    <td>{{ $tag->activeBookingAssignment?->package_label ?? '—' }}</td>
                    <td>{{ $tag->allocated_at?->format('d M Y, H:i') ?? '—' }}</td>
                    <td class="text-end">
                        @if($tag->status === \App\Models\LaundryTag::STATUS_ALLOCATED)
                            <button type="button" class="btn btn-sm btn-outline-danger btn-dp-report-tag"
                                data-url="{{ route('delivery-person.tags.exception', $tag) }}"
                                data-code="{{ $tag->tag_code }}">
                                Report Lost/Damaged
                            </button>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No laundry tags currently allocated to you. Ask Admin to allocate tag inventory.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($tags->hasPages())
    <div class="p-3 border-top dp-tags-pagination">{!! $tags->links() !!}</div>
@endif
