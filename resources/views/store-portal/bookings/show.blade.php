@extends('store-portal.layouts.app')

@section('title', 'Booking ' . $booking->reference)

@section('content')
@php
    $addr = is_array($booking->address_snapshot) ? $booking->address_snapshot : [];
    $sched = is_array($booking->schedule_snapshot) ? $booking->schedule_snapshot : [];
@endphp

<!-- 1. Booking Header -->
<div class="sp-card p-3 p-md-4 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border-color: #FDE68A; border-left: 5px solid #FFC629;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <a href="{{ route('store.bookings.index') }}" class="text-decoration-none small fw-semibold" style="color: #475569;">&larr; Back to My Bookings</a>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                <h4 class="fw-bold mb-0" style="color: #171717;">{{ $booking->reference }}</h4>
                @php
                    $resolvedStoreStatus = $storeStatus ?? app(\App\Services\StoreOperationalStatusService::class)->getStatusForBooking($booking);
                @endphp
                <span class="badge rounded-pill px-3 py-1 fw-semibold" style="{{ $resolvedStoreStatus['operational_badge_style'] }}">
                    Operational Status: {{ $resolvedStoreStatus['operational_status_label'] }}
                </span>
                @if($resolvedStoreStatus['store_workflow_finished'])
                    <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background-color: #16A34A; color: #FFFFFF;">
                        Store Workflow Finished
                    </span>
                @endif
            </div>
        </div>
        <div class="small" style="color: #475569;">
            Assigned Store: <strong style="color: #171717;">{{ $store->name }}</strong>
        </div>
    </div>
</div>

@if($booking->currentStoreHandover && (int) $booking->currentStoreHandover->store_id === (int) $store->id)
    @php $sh = $booking->currentStoreHandover; @endphp
    <div class="sp-card p-3 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2"
         style="{{ $sh->status === 'accepted' ? 'background:#ECFDF5;border-color:#A7F3D0;' : ($sh->status === 'pending' ? 'background:#FEF3C7;border-color:#FDE68A;' : 'background:#FEF2F2;border-color:#FECACA;') }}">
        <div class="small">
            <strong>Store Handover ({{ $sh->request_reference }}):</strong>
            @if($sh->status === 'accepted')
                <span class="text-success fw-semibold">Handover Accepted ({{ $sh->accepted_at?->format('d M Y, H:i') }}) &mdash; Store Received Done</span>
            @elseif($sh->status === 'pending')
                <span class="text-warning-emphasis fw-semibold">Pending Store Acceptance from {{ $sh->collectionDeliveryPerson?->full_name ?? 'Delivery Person' }}</span>
            @else
                <span class="text-danger fw-semibold">{{ ucfirst($sh->status) }}{{ $sh->rejection_reason ? ': ' . $sh->rejection_reason : '' }}</span>
            @endif
        </div>
        @if($sh->status === 'pending')
            <a href="{{ route('store.handovers.index') }}" class="btn btn-sm btn-success fw-bold">Accept / Report Issue</a>
        @endif
    </div>
@endif

<!-- 2. Services & Items (Operational Details Only Before Finalized) -->
<div class="sp-card p-3 p-md-4 mb-3" style="background: #FFF7E2; border-color: #F5BE48;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color: #946200;"><i class="mdi mdi-basket me-1"></i>Services &amp; Items</h6>
        @if(!$isFinanciallyVisible)
            <span class="badge rounded-pill px-3 py-1" style="background: #FEF3C7; color: #92400E;">Pricing not finalized</span>
        @else
            <span class="badge rounded-pill px-3 py-1" style="background: #DCFCE7; color: #166534;">Finalized</span>
        @endif
    </div>

    <div class="table-responsive bg-white rounded-3 border">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Service / Item</th>
                    <th class="text-end">Qty / Weight</th>
                    @if($isFinanciallyVisible)
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Line Total</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($categoryGroups as $group)
                    <tr>
                        <td colspan="{{ $isFinanciallyVisible ? 4 : 2 }}" class="py-2 px-3 fw-bold small text-uppercase" style="background: #FFF8E5; color: #92400E;">
                            {{ $group['category_name'] }}
                        </td>
                    </tr>
                    @foreach($group['items'] as $line)
                        <tr class="small">
                            <td class="px-3">
                                <div class="fw-semibold" style="color: #171717;">{{ $line->service_name }}</div>
                                @if($line->item_name)
                                    <div style="color: #475569; font-size: 0.78rem;">Item: {{ $line->item_name }}</div>
                                @endif
                            </td>
                            <td class="text-end px-3 fw-semibold">
                                @if($line->pricing_mode === 'weight')
                                    {{ number_format((float) $line->weight_kg, 2) }} kg
                                @else
                                    {{ (int) $line->quantity }}
                                @endif
                            </td>
                            @if($isFinanciallyVisible)
                                <td class="text-end px-3">
                                    {{ $currencyService->formatMinor((int) ($line->actual_unit_price_minor ?? $line->catalog_unit_price_minor ?? 0), $booking->currency) }}
                                    @if($line->pricing_mode === 'weight') / kg @endif
                                </td>
                                <td class="text-end px-3 fw-bold">
                                    {{ $currencyService->formatMinor((int) $line->computeLineTotalMinor(), $booking->currency) }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="{{ $isFinanciallyVisible ? 4 : 2 }}" class="text-center py-3 text-muted small">No active operational services recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3 mb-3">
    <!-- 3. Customer & Collection Information -->
    <div class="col-12 col-lg-4">
        <div class="sp-card p-3 p-md-4 h-100" style="background: #EFF6FF; border-color: #93C5FD;">
            <h6 class="fw-bold mb-3" style="color: #1D4ED8;"><i class="mdi mdi-account me-1"></i>Customer &amp; Collection</h6>
            <div class="small mb-2"><span class="text-muted">Customer:</span> <strong style="color: #171717;">{{ $booking->customer_name }}</strong></div>
            <div class="small mb-2"><span class="text-muted">Address:</span> <strong style="color: #171717;">{{ $addr['full_address'] ?? $addr['address_line'] ?? 'Not provided' }}</strong></div>
            @if(!empty($addr['postcode']))
                <div class="small mb-2"><span class="text-muted">Postcode:</span> <strong>{{ $addr['postcode'] }}</strong></div>
            @endif
            @if(!empty($addr['hotel_name']))
                <div class="small p-2 rounded-3 bg-white border mb-2">
                    <div><strong>Hotel:</strong> {{ $addr['hotel_name'] }}</div>
                    @if(!empty($addr['room_number'])) <div><strong>Room:</strong> {{ $addr['room_number'] }}</div> @endif
                </div>
            @endif
            @if(!empty($addr['address_details']) || !empty($addr['instructions']))
                <div class="small text-muted"><strong>Instructions:</strong> {{ $addr['address_details'] ?? $addr['instructions'] }}</div>
            @endif
        </div>
    </div>

    <!-- 4. Collection & Delivery Schedule + 5. Store Processing Progress -->
    <div class="col-12 col-lg-4">
        <div class="sp-card p-3 p-md-4 h-100" style="background: #ECFDF5; border-color: #86EFAC;">
            <h6 class="fw-bold mb-3" style="color: #15803D;"><i class="mdi mdi-calendar me-1"></i>Schedule &amp; Store Progress</h6>
            <div class="p-2 px-3 rounded-3 bg-white border small mb-2">
                <div class="fw-bold text-primary">Collection</div>
                <div>{{ $sched['collection_date'] ?? '-' }} ({{ $sched['collection_slot'] ?? '-' }})</div>
            </div>
            <div class="p-2 px-3 rounded-3 bg-white border small mb-3">
                <div class="fw-bold text-success">Delivery</div>
                <div>{{ $sched['delivery_date'] ?? '-' }} ({{ $sched['delivery_slot'] ?? '-' }})</div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <span class="small fw-bold" style="color: #171717;">Store Processing Workflow</span>
                @if($resolvedStoreStatus['store_workflow_finished'])
                    <span class="badge rounded-pill px-2 py-1" style="background-color: #16A34A; color: #FFFFFF; font-size: 0.72rem;">Store Workflow Finished</span>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2 small">
                @foreach($resolvedStoreStatus['store_stages'] as $stage)
                    @php
                        if ($stage['state'] === 'completed') {
                            $stStyle = 'background-color: #DCFCE7; color: #15803D; border: 1px solid #86EFAC;';
                        } elseif ($stage['state'] === 'active') {
                            $stStyle = 'background-color: #DBEAFE; color: #1D4ED8; border: 1px solid #93C5FD;';
                        } else {
                            $stStyle = 'background-color: #F1F5F9; color: #64748B; border: 1px solid #CBD5E1;';
                        }
                    @endphp
                    <span class="badge rounded-pill px-2 py-1" style="{{ $stStyle }}">
                        @if($stage['state'] === 'completed')
                            &#10003; {{ $stage['label'] }} &mdash; Completed
                        @elseif($stage['state'] === 'active')
                            &bull; {{ $stage['label'] }} (Current)
                        @else
                            {{ $stage['label'] }}
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- 6. Store Earnings Summary (Strictly Hidden Until Finalized Stage Done) -->
    <div class="col-12 col-lg-4">
        <div class="sp-card p-3 p-md-4 h-100" style="background: #F5F3FF; border-color: #C4B5FD;">
            <h6 class="fw-bold mb-3" style="color: #6D28D9;"><i class="mdi mdi-cash-multiple me-1"></i>Store Earnings Summary</h6>

            @if($isFinanciallyVisible && $storeEarning)
                <div class="d-flex justify-content-between small mb-2">
                    <span style="color: #475569;">Eligible Service Items</span>
                    <strong style="color: #171717;">{{ $currencyService->formatMinor((int) $storeEarning->eligible_service_amount_minor, $storeEarning->currency) }}</strong>
                </div>
                <div class="d-flex justify-content-between small mb-2">
                    <span style="color: #475569;">Platform Commission ({{ number_format((float) $storeEarning->commission_rate, 2) }}%)</span>
                    <strong class="text-danger">-{{ $currencyService->formatMinor((int) $storeEarning->commission_amount_minor, $storeEarning->currency) }}</strong>
                </div>
                <div class="p-3 rounded-3 mt-3" style="background: #FFF8E5; border: 1px solid #FFC629;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold" style="color: #171717;">Net Store Earnings</span>
                        <span class="fs-5 fw-bold" style="color: #171717;">
                            {{ $currencyService->formatMinor((int) $storeEarning->net_store_earnings_minor, $storeEarning->currency) }}
                        </span>
                    </div>
                </div>
                <div class="small mt-2" style="color: #475569; font-size: 0.75rem;">
                    Finalized at {{ $storeEarning->finalized_at?->format('d M Y, H:i') }}. Platform service fees &amp; delivery charges are excluded.
                </div>
            @else
                <div class="p-3 rounded-3 bg-white border small" style="color: #475569;">
                    <div class="fw-bold mb-1" style="color: #92400E;">Pricing not finalized</div>
                    <div>Finalized service amounts and net store earnings will become visible once the admin completes the Finalized booking stage.</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
