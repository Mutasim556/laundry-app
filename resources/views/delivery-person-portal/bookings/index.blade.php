@extends('delivery-person-portal.layouts.app')
@section('title', $heading)
@section('page_title', $heading)

@section('content')
<div class="dp-card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search booking reference, customer name, or phone...">
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm dp-btn-yellow px-3">Search</button>
            <a href="{{ url()->current() }}" class="btn btn-sm btn-light">Reset</a>
        </div>
    </form>
</div>

<div class="row g-2 g-md-3">
    @forelse($bookings as $booking)
        @php
            $isCol = $booking->currentCollectionAssignment && (int) $booking->currentCollectionAssignment->delivery_person_id === (int) $person->id;
            $isFin = $booking->currentFinalDeliveryAssignment && (int) $booking->currentFinalDeliveryAssignment->delivery_person_id === (int) $person->id;
            $completedStages = $booking->getCompletedOperationalStatuses();
            $isCollectionJobCompleted = $isCol && (
                in_array(\App\Models\Booking::STATUS_STORE_RECEIVED, $completedStages, true)
                || $booking->currentCollectionAssignment?->status === \Modules\Admin\Models\DeliveryAssignment::STATUS_COMPLETED
            );
        @endphp
        <div class="col-12 col-md-6">
            <div class="dp-card p-3 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <div class="fw-bold">{{ $booking->reference }}</div>
                            <div class="small text-muted">{{ $booking->customer_name }} &bull; {{ $booking->customer_phone }}</div>
                        </div>
                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                            @if($isCol)<span class="badge bg-warning-subtle text-warning-emphasis">Collection</span>@endif
                            @if($isFin)<span class="badge bg-info-subtle text-info-emphasis">Final Delivery</span>@endif
                        </div>
                    </div>
                    <div class="small text-muted mb-2">
                        <i class="mdi mdi-map-marker-outline me-1"></i>{{ $booking->address_snapshot['address_line_1'] ?? '' }} {{ $booking->address_snapshot['postcode'] ?? '' }}
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    @if($isCollectionJobCompleted && ($filterType === \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION || !$isFin))
                        <span class="badge rounded-pill px-2 py-1" style="background-color: #DCFCE7; color: #15803D; border: 1px solid #86EFAC;">
                            &check; Completed
                        </span>
                    @else
                        <span class="badge bg-light text-dark border">{{ \App\Models\Booking::STATUS_LABELS[$booking->booking_status] ?? ucfirst($booking->booking_status) }}</span>
                    @endif
                    <a href="{{ route('delivery-person.bookings.show', $booking) }}" class="btn btn-sm dp-btn-yellow px-3">View Details</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="dp-card p-4 text-center text-muted">No bookings found for your account.</div>
        </div>
    @endforelse
</div>

@if($bookings->hasPages())
    <div class="mt-3">{{ $bookings->links() }}</div>
@endif
@endsection
