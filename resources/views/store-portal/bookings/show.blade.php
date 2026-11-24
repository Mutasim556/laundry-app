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

<!-- 2. Services & Items (Operational Details) -->
@php
    $spPricingComplete = $isFinanciallyVisible
        || ((bool) ($pricingSummary['is_pricing_complete'] ?? false)
            && in_array(\App\Models\Booking::STATUS_FINALIZED, $booking->getCompletedOperationalStatuses(), true));
    $spTotalMinor = (int) ($paymentState['total_minor'] ?? $pricingSummary['final_total_minor'] ?? $pricingSummary['calculated_total_minor'] ?? 0);
@endphp
<div class="sp-card p-3 p-md-4 mb-3" style="background: #FFF7E2; border-color: #F5BE48;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h6 class="fw-bold mb-0" style="color: #946200;"><i class="mdi mdi-basket me-1"></i>Services &amp; Items</h6>
        <div class="d-flex align-items-center gap-2">
            @if($spPricingComplete)
                <span class="fw-bold small" style="color: #171717;">
                    Final Amount: {{ $currencyService->formatMinor($spTotalMinor, $booking->currency) }}
                </span>
                <span class="badge rounded-pill px-3 py-1" style="background: #DCFCE7; color: #166534;">Finalized</span>
            @else
                <span class="badge rounded-pill px-3 py-1" style="background: #FEF3C7; color: #92400E;">Pricing not finalized</span>
            @endif
        </div>
    </div>

    <div class="table-responsive bg-white rounded-3 border">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Service / Item</th>
                    <th class="text-end">Qty / Weight</th>
                    @if($spPricingComplete)
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Line Total</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($categoryGroups as $group)
                    <tr>
                        <td colspan="{{ $spPricingComplete ? 4 : 2 }}" class="py-2 px-3 fw-bold small text-uppercase" style="background: #FFF8E5; color: #92400E;">
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
                            @if($spPricingComplete)
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
                        <td colspan="{{ $spPricingComplete ? 4 : 2 }}" class="text-center py-3 text-muted small">No active operational services recorded.</td>
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
            @elseif($spPricingComplete)
                <div class="d-flex justify-content-between small mb-2">
                    <span style="color: #475569;">Eligible Service Items</span>
                    <strong style="color: #171717;">{{ $currencyService->formatMinor((int) ($pricingSummary['items_subtotal_minor'] ?? 0), $booking->currency) }}</strong>
                </div>
                <div class="d-flex justify-content-between small mb-2">
                    <span style="color: #475569;">Final Booking Amount</span>
                    <strong style="color: #171717;">{{ $currencyService->formatMinor($spTotalMinor, $booking->currency) }}</strong>
                </div>
                <div class="d-flex justify-content-between small mb-2">
                    <span style="color: #475569;">Payment Status</span>
                    <strong class="text-dark">{{ $booking->getFinancialPaymentStatusLabel() }}</strong>
                </div>
                <div class="p-2 px-3 rounded-3 bg-white border small mt-2" style="color: #64748B;">
                    Store commission rate is not configured for this store; net store earnings are not calculated.
                </div>
            @else
                <div class="p-3 rounded-3 bg-white border small" style="color: #475569;">
                    <div class="fw-bold mb-1" style="color: #92400E;">Pricing not finalized</div>
                    <div>Finalized service amounts and net store earnings will become visible once pricing is finalized.</div>
                </div>
            @endif
        </div>
    </div>
</div>

@php
    $spFinalAsg = $booking->currentFinalDeliveryAssignment;
    $spFinalPerson = $spFinalAsg?->deliveryPerson;
    $spLatestPickup = $booking->latestStorePickupRequest;
    $spActivePickup = $spLatestPickup && $spLatestPickup->delivery_assignment_id === $spFinalAsg?->id
        && ! in_array($spLatestPickup->status, [\App\Models\BookingStorePickupRequest::STATUS_CANCELLED], true)
        ? $spLatestPickup
        : null;
    $spStoreCompleteDone = (bool) ($resolvedStoreStatus['store_workflow_finished'] ?? false);
    $spCanMarkComplete = ! $spStoreCompleteDone
        && $booking->cancelled_at === null
        && in_array($booking->booking_status, [\App\Models\Booking::STATUS_STORE_PROCESSING, \App\Models\Booking::STATUS_STORE_COMPLETE], true)
        && $spPricingComplete;
    $spCanRequestPickup = $spStoreCompleteDone
        && $booking->cancelled_at === null
        && $spFinalAsg !== null
        && $spFinalPerson !== null
        && $spFinalPerson->isEligibleForAssignment()
        && (! $spActivePickup || $spActivePickup->status === \App\Models\BookingStorePickupRequest::STATUS_DECLINED);
    $spCustomerAddrText = $addr['full_address'] ?? $addr['selected_address'] ?? trim(implode(', ', array_filter([$addr['address_line_1'] ?? '', $addr['city'] ?? '', $addr['postcode'] ?? ''])));
    if ($spCustomerAddrText === '') {
        $spCustomerAddrText = 'Customer Delivery Address';
    }
@endphp

<!-- 7. Store Completion, Final Delivery Person & Store Pickup Request Cards -->
<div class="row g-3 mb-3" id="spDeliveryWorkflowRow">
    <!-- Card A: Store Processing -->
    <div class="col-12 col-lg-4">
        <div class="sp-card p-3 p-md-4 h-100" style="background: #FFFFFF; border-color: #BBF7D0; border-top: 4px solid #16A34A;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0" style="color: #171717;"><i class="mdi mdi-check-decagram-outline text-success me-1"></i>Store Processing</h6>
                <span id="spStoreCompleteBadge" class="badge rounded-pill px-2 py-1" style="{{ $spStoreCompleteDone ? 'background:#DCFCE7;color:#15803D;' : 'background:#FEF3C7;color:#92400E;' }}">
                    {{ $spStoreCompleteDone ? 'Store Complete ✓' : 'In Progress' }}
                </span>
            </div>
            <p class="small text-muted mb-3" id="spStoreCompleteDesc">
                @if($spStoreCompleteDone)
                    Status: <strong class="text-success">Store Complete ✓</strong> &mdash; Laundry items are ready for collection.
                @elseif($spCanMarkComplete)
                    Confirm when all laundry items are cleaned, packed, and ready for Final Delivery Person collection.
                @else
                    Available once the booking reaches <strong>Store Processing</strong> with finalized pricing.
                @endif
            </p>
            <div id="spMarkCompleteActionWrap">
                @if($spCanMarkComplete)
                    <button type="button" class="btn btn-sm fw-bold text-white px-3 py-2" style="background-color: #16A34A; border-radius: 10px;" data-bs-toggle="modal" data-bs-target="#confirmStoreCompleteModal" id="spMarkCompleteBtn">
                        <i class="mdi mdi-check-circle-outline me-1"></i>Mark Store Complete
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Card B: Final Delivery Person -->
    <div class="col-12 col-lg-4">
        <div class="sp-card p-3 p-md-4 h-100" style="background: #FFFFFF; border-color: #BFDBFE; border-top: 4px solid #3B82F6;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0" style="color: #171717;"><i class="mdi mdi-truck-delivery-outline text-primary me-1"></i>Final Delivery Person</h6>
                @if($spFinalAsg && $spFinalPerson)
                    <span class="badge rounded-pill px-2 py-1" style="background:#DBEAFE;color:#1D4ED8;">Assigned</span>
                @else
                    <span class="badge rounded-pill px-2 py-1" style="background:#F1F5F9;color:#475569;">Awaiting Admin</span>
                @endif
            </div>
            @if($spFinalAsg && $spFinalPerson)
                <div class="small mb-1"><span class="text-muted">Name:</span> <strong style="color:#171717;">{{ $spFinalPerson->full_name }}</strong></div>
                <div class="small mb-1"><span class="text-muted">Phone:</span> <strong style="color:#171717;">{{ $spFinalPerson->phone_display ?: 'Not provided' }}</strong></div>
                <div class="small mb-1"><span class="text-muted">Store:</span> <strong>{{ $store->name }}</strong></div>
                <div class="small text-muted"><span>Destination:</span> <strong style="color:#171717;">{{ $spCustomerAddrText }}</strong></div>
            @else
                <div class="p-2 px-3 rounded-3 small" style="background:#FFF8E5; border:1px solid #FDE68A; color:#92400E;">
                    <strong>Awaiting Admin Assignment</strong><br>
                    Once Store Complete is confirmed, Admin will assign the Final Delivery Person for Store &rarr; Customer delivery.
                </div>
            @endif
        </div>
    </div>

    <!-- Card C: Store Pickup Request -->
    <div class="col-12 col-lg-4">
        <div class="sp-card p-3 p-md-4 h-100" style="background: #FFF8E5; border-color: #FDE68A; border-top: 4px solid #FFC629;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0" style="color: #171717;"><i class="mdi mdi-package-variant-closed-check text-warning-emphasis me-1"></i>Store Pickup Request</h6>
                <span id="spPickupStatusBadge" class="badge rounded-pill px-2 py-1" style="background:#FFFFFF; color:#171717; border:1px solid #FDE68A;">
                    @if($spActivePickup)
                        {{ $spActivePickup->getStatusLabel() }}
                    @elseif($spCanRequestPickup)
                        Ready to Request
                    @else
                        Waiting
                    @endif
                </span>
            </div>

            <div id="spPickupStatusBody" class="small mb-3">
                @if($spActivePickup)
                    <div><strong>Ref:</strong> {{ $spActivePickup->request_reference }}</div>
                    <div><strong>Status:</strong> {{ $spActivePickup->getStatusLabel() }}</div>
                    @if($spActivePickup->requested_at)
                        <div class="text-muted">Requested: {{ $spActivePickup->requested_at->format('d M Y, H:i') }}</div>
                    @endif
                    @if($spActivePickup->accepted_at)
                        <div class="text-success">Accepted: {{ $spActivePickup->accepted_at->format('d M Y, H:i') }}</div>
                    @endif
                    @if($spActivePickup->status === \App\Models\BookingStorePickupRequest::STATUS_DECLINED)
                        <div class="text-danger mt-1">Declined: {{ $spActivePickup->decline_reason }}</div>
                    @endif
                    @if($spActivePickup->collected_at)
                        <div class="text-primary">Collected From Store: {{ $spActivePickup->collected_at->format('d M Y, H:i') }}</div>
                    @endif
                    @if($spActivePickup->delivered_at)
                        <div class="text-success fw-bold">Delivered / Completed: {{ $spActivePickup->delivered_at->format('d M Y, H:i') }}</div>
                    @endif
                @elseif($spCanRequestPickup)
                    <div class="text-muted">Send a pickup request directly to <strong>{{ $spFinalPerson->full_name }}</strong>.</div>
                @else
                    <div class="text-muted">Available after Store Complete and Admin Final Delivery Person assignment.</div>
                @endif
            </div>

            <div id="spPickupActionWrap">
                @if($spCanRequestPickup)
                    <button type="button" class="btn btn-sm fw-bold px-3 py-2" style="background-color: #FFC629; color: #171717; border-radius: 10px;" data-bs-toggle="modal" data-bs-target="#confirmStorePickupModal" id="spRequestPickupBtn">
                        <i class="mdi mdi-send-outline me-1"></i>Request Store Pickup
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal: Confirm Store Complete -->
<div class="modal fade" id="confirmStoreCompleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-bold" style="color: #171717;"><i class="mdi mdi-check-circle text-success me-1"></i>Mark Store Complete</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="small mb-2" style="color:#171717;">Confirm that all laundry items have been processed and are ready for collection.</p>
                <div class="mb-2">
                    <label for="spCompleteNoteInput" class="form-label small fw-semibold mb-1">Optional Note</label>
                    <input type="text" id="spCompleteNoteInput" class="form-control form-control-sm" placeholder="e.g. Packed in 2 garment bags" maxlength="500">
                </div>
                <div id="spCompleteErrorBox" class="alert alert-danger py-2 px-3 small d-none mb-0"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm text-white fw-bold" style="background-color: #16A34A;" id="spConfirmStoreCompleteBtn">Confirm Store Complete</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Request Store Pickup -->
<div class="modal fade" id="confirmStorePickupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-1" style="background:#FFF8E5; border-radius: 16px 16px 0 0;">
                <h6 class="modal-title fw-bold" style="color: #171717;">REQUEST STORE PICKUP</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3 small">
                <div class="mb-1"><strong>Booking:</strong> {{ $booking->reference }}</div>
                <div class="mb-1"><strong>Assigned Delivery Person:</strong> {{ $spFinalPerson?->full_name ?? 'N/A' }}</div>
                <div class="mb-1"><strong>Pickup From:</strong> {{ $store->name }}</div>
                <div class="mb-3"><strong>Deliver To:</strong> {{ $spCustomerAddrText }}</div>
                <div class="mb-2">
                    <label for="spPickupNoteInput" class="form-label small fw-semibold mb-1">Pickup Note (Optional)</label>
                    <input type="text" id="spPickupNoteInput" class="form-control form-control-sm" placeholder="e.g. Front desk collection" maxlength="500">
                </div>
                <div id="spPickupErrorBox" class="alert alert-danger py-2 px-3 small d-none mb-0"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm fw-bold" style="background-color: #FFC629; color: #171717;" id="spConfirmPickupRequestBtn">Send Pickup Request</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = '{{ csrf_token() }}';

    const confirmCompleteBtn = document.getElementById('spConfirmStoreCompleteBtn');
    if (confirmCompleteBtn) {
        confirmCompleteBtn.addEventListener('click', async function () {
            const errBox = document.getElementById('spCompleteErrorBox');
            errBox.classList.add('d-none');
            confirmCompleteBtn.disabled = true;
            confirmCompleteBtn.textContent = 'Saving...';

            try {
                const res = await fetch('{{ route('store.bookings.mark-complete', $booking) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        note: document.getElementById('spCompleteNoteInput')?.value || null,
                    }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Unable to mark Store Complete.');
                }

                const modalEl = document.getElementById('confirmStoreCompleteModal');
                if (window.bootstrap && modalEl) {
                    window.bootstrap.Modal.getInstance(modalEl)?.hide();
                }

                document.getElementById('spStoreCompleteBadge').textContent = 'Store Complete ✓';
                document.getElementById('spStoreCompleteBadge').setAttribute('style', 'background:#DCFCE7;color:#15803D;');
                document.getElementById('spStoreCompleteDesc').innerHTML = 'Status: <strong class="text-success">Store Complete ✓</strong> &mdash; Laundry items are ready for collection.';
                document.getElementById('spMarkCompleteActionWrap').innerHTML = '';

                if (data.has_final_assignment) {
                    window.location.reload();
                }
            } catch (err) {
                errBox.textContent = err.message || 'Request failed.';
                errBox.classList.remove('d-none');
                confirmCompleteBtn.disabled = false;
                confirmCompleteBtn.textContent = 'Confirm Store Complete';
            }
        });
    }

    const confirmPickupBtn = document.getElementById('spConfirmPickupRequestBtn');
    if (confirmPickupBtn) {
        confirmPickupBtn.addEventListener('click', async function () {
            const errBox = document.getElementById('spPickupErrorBox');
            errBox.classList.add('d-none');
            confirmPickupBtn.disabled = true;
            confirmPickupBtn.textContent = 'Sending...';

            try {
                const res = await fetch('{{ route('store.bookings.pickup-request', $booking) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        note: document.getElementById('spPickupNoteInput')?.value || null,
                    }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Unable to send store pickup request.');
                }

                const modalEl = document.getElementById('confirmStorePickupModal');
                if (window.bootstrap && modalEl) {
                    window.bootstrap.Modal.getInstance(modalEl)?.hide();
                }

                const pr = data.pickup_request || {};
                document.getElementById('spPickupStatusBadge').textContent = pr.status_label || 'Pending Acceptance';
                document.getElementById('spPickupStatusBody').innerHTML =
                    `<div><strong>Ref:</strong> ${pr.request_reference || ''}</div>` +
                    `<div><strong>Status:</strong> ${pr.status_label || 'Pending Acceptance'}</div>` +
                    `<div class="text-muted">Requested: ${pr.requested_at || 'Just now'}</div>`;
                document.getElementById('spPickupActionWrap').innerHTML = '';
            } catch (err) {
                errBox.textContent = err.message || 'Request failed.';
                errBox.classList.remove('d-none');
                confirmPickupBtn.disabled = false;
                confirmPickupBtn.textContent = 'Send Pickup Request';
            }
        });
    }
});
</script>
@endsection
