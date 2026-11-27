@php
    $isStoreProcessing = $booking->booking_status === \App\Models\Booking::STATUS_STORE_PROCESSING;
    $isStoreComplete = in_array(\App\Models\Booking::STATUS_STORE_COMPLETE, $booking->getCompletedOperationalStatuses(), true);
    $collectionDpName = $booking->currentCollectionAssignment?->deliveryPerson?->full_name ?? 'Collection Delivery Person';
    $tags = $booking->activeTagAssignments;
@endphp

<div class="row g-3">
    <!-- Booking Details & Progress -->
    <div class="col-12 col-lg-5">
        <div class="sp-card p-3 p-md-4 h-100" style="border-left: 4px solid #FFC629;">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <div class="small text-muted fw-semibold">Verified Booking</div>
                    <h5 class="fw-bold mb-1">
                        <a href="{{ route('store.bookings.show', $booking) }}" class="text-decoration-none text-dark">{{ $booking->reference }}</a>
                    </h5>
                    <span class="badge rounded-pill px-2 py-1" style="{{ $storeStatus['operational_badge_style'] ?? 'background:#E0F2FE;color:#0369A1;' }}">
                        {{ $storeStatus['operational_status_label'] ?? ucfirst(str_replace('_', ' ', $booking->booking_status)) }}
                    </span>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                    Verified: <code>{{ $verifiedAssignment->tag?->tag_code }}</code>
                </span>
            </div>

            <div class="small mb-3">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Store</span>
                    <strong>{{ $store->name }}</strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Collection Delivery Person</span>
                    <strong>{{ $collectionDpName }}</strong>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Operational Items</span>
                    <strong>{{ $booking->operationalItems->count() }} item(s)</strong>
                </div>
            </div>

            <!-- Tag Completion Progress -->
            <div class="p-3 rounded-3 bg-light border">
                <div class="d-flex justify-content-between align-items-center mb-1 small">
                    <span class="fw-bold text-dark">Tag Completion Progress</span>
                    <span class="fw-bold text-primary">{{ $progress['completed'] }} / {{ $progress['total'] }} ({{ $progress['percent'] }}%)</span>
                </div>
                <div class="progress mb-2" style="height: 10px; border-radius: 999px;">
                    <div class="progress-bar {{ $progress['percent'] === 100 ? 'bg-success' : 'bg-warning' }}"
                         role="progressbar"
                         style="width: {{ $progress['percent'] }}%;"
                         aria-valuenow="{{ $progress['percent'] }}"
                         aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between text-muted" style="font-size: 0.76rem;">
                    <span>Required: <strong>{{ $progress['total'] }}</strong></span>
                    <span>Completed: <strong class="text-success">{{ $progress['completed'] }}</strong></span>
                    <span>Remaining: <strong class="text-danger">{{ $progress['remaining'] }}</strong></span>
                </div>
            </div>

            @if($isStoreComplete)
                <div class="alert alert-success small py-2 mt-3 mb-0">
                    <i class="mdi mdi-check-circle me-1"></i>This booking is marked <strong>Store Complete</strong>.
                </div>
            @endif
        </div>
    </div>

    <!-- All Tags / Bags for This Booking -->
    <div class="col-12 col-lg-7">
        <div class="sp-card p-3 p-md-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">All Laundry Bags / Tags ({{ $tags->count() }})</h6>
                <a href="{{ route('store.bookings.show', $booking) }}" class="btn btn-sm btn-outline-secondary">Open Booking Details</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0" style="min-width: 560px;">
                    <thead class="table-light">
                        <tr>
                            <th>Bag / Pkg</th>
                            <th>Tag Code</th>
                            <th>Verification</th>
                            <th>Processing</th>
                            <th>Completed By</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tags as $idx => $tagAsg)
                            @php
                                $bagNum = $tagAsg->bag_sequence ?: ($idx + 1);
                                $pkgText = $tagAsg->package_label ?: ('Bag ' . $bagNum);
                                $isHighlighted = $tagAsg->id === $verifiedAssignment->id;
                                $isDone = $tagAsg->isStoreProcessingCompleted();
                            @endphp
                            <tr class="{{ $isHighlighted ? 'table-warning' : '' }}">
                                <td class="fw-semibold">{{ $pkgText }}</td>
                                <td><code class="fw-bold text-dark">{{ $tagAsg->tag?->tag_code }}</code></td>
                                <td>
                                    @if($tagAsg->isVerifiedByStore())
                                        <span class="badge bg-success-subtle text-success">&check; Verified</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isDone)
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Processing</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if($isDone)
                                        <div>{{ $tagAsg->storeProcessingCompletedBy?->name ?? 'Store Staff' }}</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ $tagAsg->store_processing_completed_at?->format('d M, H:i') }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if(! $isDone && $isStoreProcessing && ! $isStoreComplete)
                                        <button type="button"
                                                class="btn btn-sm btn-success fw-semibold js-open-complete-tag"
                                                data-url="{{ route('store.tag-verification.complete', $tagAsg) }}"
                                                data-code="{{ $tagAsg->tag?->tag_code }}"
                                                data-ref="{{ $booking->reference }}"
                                                data-bag="{{ $pkgText }}"
                                                data-total="{{ $progress['total'] }}"
                                                data-completed="{{ $progress['completed'] }}"
                                                data-remaining="{{ $progress['remaining'] }}"
                                                data-status="{{ $storeStatus['operational_status_label'] ?? ucfirst(str_replace('_', ' ', $booking->booking_status)) }}">
                                            Mark Tag Completed
                                        </button>
                                    @elseif($isDone)
                                        <span class="text-success small fw-semibold">&check; Done</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
