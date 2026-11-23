@extends('store-portal.layouts.app')

@section('title', 'Incoming Store Handovers')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0 text-dark">Incoming Store Handovers</h4>
        <div class="small text-muted">Review collected laundry arriving from Collection Delivery Persons and confirm physical receipt.</div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2 small">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger py-2 small">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-3">
    @forelse($handovers as $h)
        @php
            $b = $h->booking;
            $dp = $h->collectionDeliveryPerson;
            $itemsCount = $b?->operationalItems->count() ?? 0;
            $catCount = $b?->operationalItems->pluck('item_category_id')->filter()->unique()->count() ?? 0;
            $totalWeight = (float) ($b?->operationalItems->where('pricing_mode', 'weight')->sum('weight_kg') ?? 0);
            $totalQty = (int) ($b?->operationalItems->where('pricing_mode', '!=', 'weight')->sum('quantity') ?? 0);
            $summaryParts = [];
            if ($totalQty > 0) $summaryParts[] = $totalQty . ' pcs';
            if ($totalWeight > 0) $summaryParts[] = number_format($totalWeight, 2) . ' kg';
            $itemSummaryText = implode(', ', $summaryParts) ?: ($itemsCount . ' item(s)');
        @endphp
        <div class="col-12 col-lg-6">
            <div class="sp-card p-3 h-100 d-flex flex-column justify-content-between" style="border-left: 4px solid {{ $h->status === 'pending' ? '#F59E0B' : ($h->status === 'accepted' ? '#16A34A' : '#EF4444') }};">
                <div>
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <div class="fw-bold text-dark">{{ $b?->reference ?? 'Booking' }} <span class="text-muted small fw-normal">({{ $h->request_reference }})</span></div>
                            <div class="small text-muted">Customer: <strong>{{ $b?->customer_name ?? '-' }}</strong></div>
                        </div>
                        <span class="badge rounded-pill px-2 py-1" style="{{ $h->status === 'pending' ? 'background-color:#FEF3C7;color:#92400E;' : ($h->status === 'accepted' ? 'background-color:#DCFCE7;color:#15803D;' : 'background-color:#FEE2E2;color:#B91C1C;') }}">
                            {{ ucfirst($h->status) }}
                        </span>
                    </div>

                    <div class="small mb-2 p-2 rounded-2 bg-light border">
                        <div><strong>Collection Delivery Person:</strong> {{ $dp?->full_name ?? '-' }} ({{ $dp?->phone_display ?: 'No phone' }})</div>
                        <div><strong>Requested At:</strong> {{ $h->requested_at?->format('d M Y, H:i') }}</div>
                        <div><strong>Categories / Items:</strong> {{ $catCount }} categories &bull; {{ $itemsCount }} rows ({{ $itemSummaryText }})</div>
                        @if($h->request_note)
                            <div class="mt-1 text-muted"><strong>Note:</strong> {{ $h->request_note }}</div>
                        @endif
                    </div>

                    @if($h->status === 'accepted')
                        <div class="small text-success fw-semibold mb-2">
                            <i class="mdi mdi-check-circle me-1"></i>Accepted by {{ $h->acceptedByStoreUser?->name ?? 'Store Staff' }} at {{ $h->accepted_at?->format('d M Y, H:i') }}
                        </div>
                    @elseif($h->status === 'rejected')
                        <div class="small text-danger mb-2">
                            <strong>Issue Reported:</strong> {{ $h->rejection_reason }}
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                    @if($b)
                        <a href="{{ route('store.bookings.show', $b) }}" class="btn btn-sm btn-light border">View Booking</a>
                    @endif
                    @if($h->status === \App\Models\BookingStoreHandover::STATUS_PENDING)
                        <button type="button"
                                class="btn btn-sm btn-success js-store-open-accept"
                                data-action="{{ route('store.handovers.accept', $h) }}"
                                data-ref="{{ $b?->reference }}"
                                data-dp="{{ $dp?->full_name }}"
                                data-summary="{{ $itemSummaryText }}"
                                data-store="{{ $store->name }}">
                            Accept &amp; Confirm Received
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger js-store-open-reject"
                                data-action="{{ route('store.handovers.reject', $h) }}"
                                data-ref="{{ $b?->reference }}">
                            Report Issue
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="sp-card p-4 text-center text-muted">No incoming store handover requests yet.</div>
        </div>
    @endforelse
</div>

@if($handovers->hasPages())
    <div class="mt-3">{{ $handovers->links() }}</div>
@endif

<!-- Accept Confirmation Modal -->
<div class="modal fade" id="storeAcceptHandoverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="storeAcceptHandoverForm" action="#">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Accept Laundry Handover</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small">
                    <p class="mb-2 fw-semibold text-dark">Confirm that your store has physically received the laundry for this booking.</p>
                    <div class="p-2 rounded-2 bg-light border">
                        <div><strong>Booking:</strong> <span id="acceptModalRef"></span></div>
                        <div><strong>Collection Delivery Person:</strong> <span id="acceptModalDp"></span></div>
                        <div><strong>Items Summary:</strong> <span id="acceptModalSummary"></span></div>
                        <div><strong>Store:</strong> <span id="acceptModalStore"></span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success fw-bold">Accept &amp; Confirm Received</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Report Issue Modal -->
<div class="modal fade" id="storeRejectHandoverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="storeRejectHandoverForm" action="#">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Report Handover Issue (<span id="rejectModalRef"></span>)</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small">
                    <label class="form-label fw-semibold mb-1">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" rows="3" class="form-control form-control-sm" required placeholder="e.g. Laundry has not arrived physically or items are missing"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Submit Issue</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('click', function (e) {
        const acceptBtn = e.target.closest('.js-store-open-accept');
        if (acceptBtn) {
            document.getElementById('storeAcceptHandoverForm').setAttribute('action', acceptBtn.dataset.action);
            document.getElementById('acceptModalRef').textContent = acceptBtn.dataset.ref || '';
            document.getElementById('acceptModalDp').textContent = acceptBtn.dataset.dp || '';
            document.getElementById('acceptModalSummary').textContent = acceptBtn.dataset.summary || '';
            document.getElementById('acceptModalStore').textContent = acceptBtn.dataset.store || '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('storeAcceptHandoverModal')).show();
            return;
        }

        const rejectBtn = e.target.closest('.js-store-open-reject');
        if (rejectBtn) {
            document.getElementById('storeRejectHandoverForm').setAttribute('action', rejectBtn.dataset.action);
            document.getElementById('rejectModalRef').textContent = rejectBtn.dataset.ref || '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('storeRejectHandoverModal')).show();
        }
    });
</script>
@endpush
