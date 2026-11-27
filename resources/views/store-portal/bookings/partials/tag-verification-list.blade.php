@php
    $tags = $booking->activeTagAssignments;
    $verifiedCount = $tags->filter->isVerifiedByStore()->count();
    $completedCount = $tags->filter->isStoreProcessingCompleted()->count();
    $totalCount = $tags->count();
    $progressPercent = $totalCount > 0 ? (int) round(($completedCount / $totalCount) * 100) : 0;
    $canCompleteTags = $booking->booking_status === \App\Models\Booking::STATUS_STORE_PROCESSING
        && ! in_array(\App\Models\Booking::STATUS_STORE_COMPLETE, $booking->getCompletedOperationalStatuses(), true);
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <div class="small fw-semibold">
        Laundry Tags: <span class="badge bg-dark">{{ $totalCount }}</span>
        &bull; Verified: <span class="badge bg-primary">{{ $verifiedCount }} / {{ $totalCount }}</span>
        &bull; Processing Completed: <span class="badge bg-success">{{ $completedCount }} / {{ $totalCount }} ({{ $progressPercent }}%)</span>
    </div>
    <div class="d-flex gap-2">
        @if($tags->isNotEmpty() && $verifiedCount < $totalCount)
            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold js-open-store-verify-tag"
                    data-url="{{ route('store.bookings.verify-tag', $booking) }}"
                    data-ref="{{ $booking->reference }}">
                <i class="mdi mdi-qrcode-scan me-1"></i>Scan / Verify Tag
            </button>
        @endif
        @if($tags->isNotEmpty() && $canCompleteTags)
            <a href="{{ route('store.tag-verification.index', ['tag_code' => $tags->first()?->tag?->tag_code]) }}" class="btn btn-sm btn-warning fw-semibold">
                <i class="mdi mdi-qrcode me-1"></i>Open QR Verification
            </a>
        @endif
    </div>
</div>

@if($tags->isEmpty())
    <div class="small text-muted">No physical laundry tags were attached to this booking by the Collection Delivery Person.</div>
@else
    <div class="progress mb-2" style="height: 6px;">
        <div class="progress-bar {{ $progressPercent === 100 ? 'bg-success' : 'bg-warning' }}" style="width: {{ $progressPercent }}%;"></div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @foreach($tags as $idx => $tagAsg)
            <div class="px-3 py-2 rounded-3 border bg-white small d-inline-flex align-items-center gap-2">
                <code>{{ $tagAsg->tag?->tag_code }}</code>
                <span class="text-muted">({{ $tagAsg->package_label ?: ('Bag ' . ($idx + 1)) }})</span>
                @if($tagAsg->isStoreProcessingCompleted())
                    <span class="badge bg-success">&check; Completed</span>
                @elseif($tagAsg->isVerifiedByStore())
                    <span class="badge bg-success-subtle text-success">&check; Verified ({{ $tagAsg->verifiedByStoreUser?->name ?? 'Staff' }})</span>
                @else
                    <button type="button"
                            class="btn btn-sm btn-outline-success py-0 px-2 js-store-quick-verify"
                            data-url="{{ route('store.bookings.verify-tag', $booking) }}"
                            data-code="{{ $tagAsg->tag?->tag_code }}">
                        Verify Match
                    </button>
                @endif
            </div>
        @endforeach
    </div>
@endif
