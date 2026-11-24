@extends('delivery-person-portal.layouts.app')
@section('title', $heading)
@section('page_title', $heading)

@section('content')
<div class="dp-card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search booking ref, job ID (COL/DEL), customer, phone, store...">
        </div>
        @if($filterType === null)
            <div class="col-6 col-md-2">
                <select name="job_type" class="form-select form-select-sm">
                    <option value="">All Job Types</option>
                    <option value="{{ \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION }}" @selected(($jobTypeFilter ?? '') === \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)>Collection</option>
                    <option value="{{ \Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY }}" @selected(($jobTypeFilter ?? '') === \Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY)>Final Delivery</option>
                </select>
            </div>
        @endif
        <div class="col-6 col-md-2">
            <select name="status_filter" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                @if($filterType === \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)
                    <option value="pending" @selected(($statusFilter ?? '') === 'pending')>Pending</option>
                    <option value="active" @selected(($statusFilter ?? '') === 'active')>Active</option>
                    <option value="completed" @selected(($statusFilter ?? '') === 'completed')>Completed</option>
                @elseif($filterType === \Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY)
                    <option value="awaiting_store" @selected(($statusFilter ?? '') === 'awaiting_store')>Awaiting Store Completion</option>
                    <option value="awaiting_request" @selected(($statusFilter ?? '') === 'awaiting_request')>Awaiting Pickup Request</option>
                    <option value="pickup_requested" @selected(($statusFilter ?? '') === 'pickup_requested')>Pickup Requested</option>
                    <option value="accepted" @selected(($statusFilter ?? '') === 'accepted')>Accepted</option>
                    <option value="on_delivery" @selected(($statusFilter ?? '') === 'on_delivery')>On Delivery</option>
                    <option value="completed" @selected(($statusFilter ?? '') === 'completed')>Completed</option>
                @else
                    <option value="pending" @selected(($statusFilter ?? '') === 'pending')>Pending Collection</option>
                    <option value="active" @selected(($statusFilter ?? '') === 'active')>Active Collection</option>
                    <option value="awaiting_store" @selected(($statusFilter ?? '') === 'awaiting_store')>Awaiting Store Completion</option>
                    <option value="awaiting_request" @selected(($statusFilter ?? '') === 'awaiting_request')>Awaiting Pickup Request</option>
                    <option value="pickup_requested" @selected(($statusFilter ?? '') === 'pickup_requested')>Pickup Requested</option>
                    <option value="accepted" @selected(($statusFilter ?? '') === 'accepted')>Accepted</option>
                    <option value="on_delivery" @selected(($statusFilter ?? '') === 'on_delivery')>On Delivery</option>
                    <option value="completed" @selected(($statusFilter ?? '') === 'completed')>Completed</option>
                @endif
            </select>
        </div>
        <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm dp-btn-yellow px-3">Filter</button>
            <a href="{{ url()->current() }}" class="btn btn-sm btn-light border">Reset</a>
        </div>
    </form>
</div>

<div class="row g-3">
    @forelse($assignments as $asg)
        @php
            $booking = $asg->booking;
            $isCol = $asg->isCollectionJob();
            $jobStatus = $asg->getJobStatusDisplay();
            $storeName = $asg->store?->name ?? $booking?->assignedStore?->name ?? 'Assigned Store';
            $addrSnap = is_array($booking?->address_snapshot) ? $booking->address_snapshot : [];
            $customerLocation = trim((string) ($addrSnap['hotel_name'] ?? $addrSnap['full_address'] ?? $addrSnap['address_line_1'] ?? 'Customer / Hotel'));
            $customerPostcode = $isCol
                ? ($asg->pickup_postcode ?: ($addrSnap['postcode'] ?? ''))
                : ($asg->destination_postcode ?: ($addrSnap['postcode'] ?? ''));

            $fromLabel = $isCol ? ($customerLocation !== '' ? $customerLocation : 'Customer / Hotel') : $storeName;
            $toLabel = $isCol ? $storeName : ($customerLocation !== '' ? $customerLocation : 'Customer / Hotel');

            $earningMinor = (int) $asg->rate_minor + (int) $asg->tip_share_minor;
            $jobCurrency = $asg->currency ?: ($booking?->currency ?: $currencyService->code());
            $isCredited = $asg->earnings_status === 'credited';

            $jobUrl = $isCol
                ? route('delivery-person.collection-jobs.show', $asg)
                : route('delivery-person.final-delivery-jobs.show', $asg);
        @endphp
        <div class="col-12 col-lg-6">
            <div class="dp-card p-3 h-100 d-flex flex-column justify-content-between"
                 style="{{ $isCol
                    ? 'background: linear-gradient(180deg, #FFFDF7 0%, #FFFFFF 100%); border-color: #FDE68A; border-left: 4px solid #FFC629;'
                    : 'background: linear-gradient(180deg, #F8FBFF 0%, #FFFFFF 100%); border-color: #BFDBFE; border-left: 4px solid #3B82F6;' }}">
                <div>
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <span class="badge rounded-pill px-2 py-1 fw-bold"
                                      style="{{ $isCol ? 'background-color: #FEF3C7; color: #92400E;' : 'background-color: #DBEAFE; color: #1D4ED8;' }}">
                                    {{ $isCol ? 'COLLECTION' : 'FINAL DELIVERY' }}
                                </span>
                                <span class="small fw-semibold text-muted">{{ $asg->job_code }}</span>
                            </div>
                            <div class="fw-bold" style="color: #171717;">Booking: {{ $booking->reference }}</div>
                            <div class="small text-muted">{{ $booking->customer_name }} &bull; {{ $booking->customer_phone }}</div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">{{ $isCredited ? 'Earned' : 'Expected Earning' }}</div>
                            <div class="fw-bold {{ $isCredited ? 'text-success' : 'text-dark' }}">
                                {{ $currencyService->formatMinor($earningMinor, $jobCurrency) }}
                            </div>
                        </div>
                    </div>

                    <div class="p-2 px-3 rounded-3 bg-white border small mb-2">
                        <div class="mb-1"><span class="text-muted">From:</span> <strong style="color:#171717;">{{ $fromLabel }}</strong></div>
                        <div class="mb-1"><span class="text-muted">To:</span> <strong style="color:#171717;">{{ $toLabel }}</strong></div>
                        @if($customerPostcode !== '')
                            <div><span class="text-muted">{{ $isCol ? 'Customer Postcode:' : 'Destination Postcode:' }}</span> <strong>{{ $customerPostcode }}</strong></div>
                        @endif
                    </div>

                    @if(!$isCol && $jobStatus['pickup_request_label'])
                        <div class="small mb-2">
                            <span class="text-muted">Pickup Request:</span>
                            <strong style="color: #171717;">{{ $jobStatus['pickup_request_label'] }}</strong>
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2 border-top">
                    <span class="badge rounded-pill px-2 py-1" style="{{ $jobStatus['badge_style'] }}">
                        {{ $jobStatus['label'] }}
                    </span>
                    <a href="{{ $jobUrl }}"
                       class="btn btn-sm fw-bold px-3 {{ $isCol ? 'dp-btn-yellow' : 'text-white' }}"
                       style="{{ $isCol ? '' : 'background-color: #3B82F6; border-radius: 10px;' }}">
                        {{ $isCol ? 'View Collection Job' : 'View Final Delivery Job' }}
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="dp-card p-4 text-center text-muted">No delivery person jobs found for your account.</div>
        </div>
    @endforelse
</div>

@if($assignments->hasPages())
    <div class="mt-3">{{ $assignments->links() }}</div>
@endif
@endsection
