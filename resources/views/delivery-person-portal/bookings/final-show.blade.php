@extends('delivery-person-portal.layouts.app')
@section('title', 'Final Delivery ' . $booking->reference)
@section('page_title', 'Final Delivery: ' . $booking->reference)

@section('content')
@php
    $addr = is_array($booking->address_snapshot) ? $booking->address_snapshot : [];
    $schedule = is_array($booking->schedule_snapshot) ? $booking->schedule_snapshot : [];
    $contact = is_array($booking->contact_snapshot) ? $booking->contact_snapshot : [];
    $dpFinalAsg = $job ?? $ctx['final_assignment'] ?? $booking->currentFinalDeliveryAssignment;
    $dpJobStatus = $dpFinalAsg ? $dpFinalAsg->getJobStatusDisplay() : null;
@endphp

<div class="dp-card p-3 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #EFF6FF 100%); border-color: #BFDBFE; border-left: 4px solid #3B82F6;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="badge bg-info-subtle text-info-emphasis">Final Delivery Assignment</span>
                @if($dpFinalAsg)
                    <span class="small fw-semibold text-muted">{{ $dpFinalAsg->job_code }} ({{ $dpFinalAsg->assignment_reference }})</span>
                @endif
            </div>
            <h5 class="fw-bold mb-0">Booking: {{ $booking->reference }}</h5>
            <div class="small text-muted">From: <strong>{{ $booking->assignedStore?->name ?? 'Assigned Store' }}</strong> &rarr; To: <strong>{{ $booking->customer_name }}</strong> ({{ $booking->customer_phone }})</div>
        </div>
        @if($dpJobStatus)
            <span class="badge rounded-pill px-3 py-2" style="{{ $dpJobStatus['badge_style'] }}">{{ $dpJobStatus['label'] }}</span>
        @else
            <span class="badge bg-primary-subtle text-primary px-3 py-2">{{ \App\Models\Booking::STATUS_LABELS[$booking->booking_status] ?? ucfirst($booking->booking_status) }}</span>
        @endif
    </div>
</div>

@php
    $dpFinalAsg = $ctx['final_assignment'] ?? $booking->currentFinalDeliveryAssignment;
    $dpPickupReq = $booking->latestStorePickupRequest;
    $dpActivePickup = $dpPickupReq && $dpFinalAsg && (int) $dpPickupReq->delivery_assignment_id === (int) $dpFinalAsg->id
        ? $dpPickupReq
        : null;
    $dpExpectedMinor = (int) ($dpFinalAsg?->rate_minor ?? 0) + (int) ($dpFinalAsg?->tip_share_minor ?? 0);
    $dpStore = $booking->assignedStore;
@endphp

<!-- Store Pickup & Final Delivery Workflow Card -->
<div class="dp-card p-3 mb-3" style="background: #FFF8E5; border-color: #FDE68A; border-left: 4px solid #FFC629;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div>
            <h6 class="fw-bold mb-0" style="color: #171717;"><i class="mdi mdi-truck-check-outline text-warning-emphasis me-1"></i>Store Pickup &amp; Final Delivery</h6>
            <div class="small text-muted">
                Store: <strong>{{ $dpStore?->name ?? 'Assigned Store' }}</strong>
                @if($dpStore?->phone) &bull; {{ $dpStore->phone }} @endif
                @if($dpExpectedMinor > 0)
                    &bull; Expected Earning: <strong class="text-success">{{ $currencyService->formatMinor($dpExpectedMinor, $dpFinalAsg?->currency ?? $booking->currency) }}</strong>
                    @if($dpFinalAsg?->earnings_status === 'credited')
                        <span class="badge bg-success-subtle text-success ms-1">Credited</span>
                    @endif
                @endif
            </div>
        </div>
        @if($dpActivePickup)
            <span class="badge rounded-pill px-3 py-1" style="background:#FFFFFF; color:#171717; border:1px solid #FDE68A;">
                {{ $dpActivePickup->getStatusLabel() }}
            </span>
        @endif
    </div>

    @if($booking->booking_status === \App\Models\Booking::STATUS_COMPLETED)
        <div class="p-2 px-3 rounded-3 bg-white border border-success-subtle small text-success fw-semibold">
            <i class="mdi mdi-check-circle me-1"></i>Delivered to Customer &mdash; Booking Completed
            @if($dpActivePickup?->delivered_at)
                ({{ $dpActivePickup->delivered_at->format('d M Y, H:i') }})
            @endif
        </div>
    @elseif($dpActivePickup && $dpActivePickup->status === \App\Models\BookingStorePickupRequest::STATUS_PENDING)
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <form method="POST" action="{{ route('delivery-person.pickup-requests.accept', $dpActivePickup) }}">
                @csrf
                <button type="submit" class="btn btn-sm text-white fw-bold px-3" style="background-color:#16A34A;">Accept Request</button>
            </form>
            <button type="button" class="btn btn-sm btn-outline-danger fw-semibold px-3" data-bs-toggle="modal" data-bs-target="#dpFinalDeclineModal">Decline Request</button>
            <span class="small text-muted">Requested at {{ $dpActivePickup->requested_at?->format('d M Y, H:i') }}</span>
        </div>

        <div class="modal fade" id="dpFinalDeclineModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('delivery-person.pickup-requests.decline', $dpActivePickup) }}" class="modal-content" style="border-radius:16px;">
                    @csrf
                    <div class="modal-header border-0 pb-1">
                        <h6 class="modal-title fw-bold">Decline Store Pickup Request</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-2">
                        <label class="form-label small fw-semibold mb-1">Reason for declining <span class="text-danger">*</span></label>
                        <textarea name="decline_reason" class="form-control form-control-sm" rows="3" required minlength="3" maxlength="500" placeholder="Provide a brief reason..."></textarea>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-danger fw-bold">Submit Decline</button>
                    </div>
                </form>
            </div>
        </div>
    @elseif($dpActivePickup && $dpActivePickup->status === \App\Models\BookingStorePickupRequest::STATUS_ACCEPTED)
        <form method="POST" action="{{ route('delivery-person.bookings.confirm-store-pickup', $booking) }}" class="d-flex flex-wrap gap-2 align-items-center">
            @csrf
            <input type="text" name="notes" class="form-control form-control-sm" style="max-width: 260px;" placeholder="Optional pickup note">
            <button type="submit" class="btn btn-sm text-white fw-bold px-3" style="background-color:#3B82F6;">
                <i class="mdi mdi-package-variant-closed-check me-1"></i>Confirm Collected From Store
            </button>
        </form>
    @elseif($dpActivePickup && $dpActivePickup->status === \App\Models\BookingStorePickupRequest::STATUS_COLLECTED && $booking->booking_status === \App\Models\Booking::STATUS_ON_DELIVERY)
        <form method="POST" action="{{ route('delivery-person.bookings.confirm-delivered', $booking) }}" class="d-flex flex-wrap gap-2 align-items-center">
            @csrf
            <input type="text" name="proof_of_delivery_notes" class="form-control form-control-sm" style="max-width: 280px;" placeholder="Delivery confirmation / recipient note (optional)">
            <button type="submit" class="btn btn-sm text-white fw-bold px-3" style="background-color:#16A34A;">
                <i class="mdi mdi-check-circle-outline me-1"></i>Confirm Delivered to Customer
            </button>
        </form>
    @else
        <div class="small text-muted">Awaiting store pickup request from <strong>{{ $dpStore?->name ?? 'Assigned Store' }}</strong>.</div>
    @endif
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="dp-card p-3 h-100">
            <h6 class="fw-bold mb-2"><i class="mdi mdi-map-marker-check-outline text-primary me-1"></i>Delivery Destination &amp; Hotel Details</h6>
            @php
                $fullAddress = trim((string) ($addr['selected_address'] ?? implode(' ', array_filter([$addr['address_line_1'] ?? '', $addr['address_line_2'] ?? '']))));
                $collectionTypeRaw = (string) ($addr['hotel_collection_type'] ?? '');
                $collectionTypeLabel = match ($collectionTypeRaw) {
                    'meet_driver' => 'Meet Delivery Person in person',
                    'use_reception' => 'Leave at Hotel Reception',
                    default => ($collectionTypeRaw !== '' ? ucfirst(str_replace('_', ' ', $collectionTypeRaw)) : ''),
                };
            @endphp
            <div class="small mb-2">
                @if($fullAddress !== '')
                    <div><strong>Address:</strong> {{ $fullAddress }}</div>
                @endif
                @if(!empty($addr['city']) || !empty($addr['postcode']))
                    <div><strong>City / Postcode:</strong> {{ $addr['city'] ?? '' }} {{ $addr['postcode'] ?? '' }}</div>
                @endif
                @if(!empty($addr['building']) || !empty($addr['floor']))
                    <div><strong>Building / Floor:</strong> {{ $addr['building'] ?? '' }} {{ $addr['floor'] ?? '' }}</div>
                @endif
                @if(!empty($addr['hotel_name']) || !empty($addr['room_number']) || $collectionTypeLabel !== '')
                    <div class="mt-1 p-2 rounded-2" style="background: #FFF8E5; border: 1px solid #FDE68A;">
                        @if(!empty($addr['hotel_name']) || !empty($addr['room_number']))
                            <div><strong>Hotel / Room:</strong> {{ $addr['hotel_name'] ?? '' }} {{ !empty($addr['room_number']) ? '— Room ' . $addr['room_number'] : '' }}</div>
                        @endif
                        @if($collectionTypeLabel !== '')
                            <div class="mt-1"><strong>Collection Type:</strong> {{ $collectionTypeLabel }}</div>
                        @endif
                    </div>
                @endif
            </div>
            <div class="small text-muted">
                <strong>Delivery Schedule:</strong> {{ $booking->delivery_slot_label ?: ($schedule['delivery_date'] ?? 'Scheduled') }}<br>
                <strong>Assigned Store:</strong> {{ $booking->assignedStore?->name ?? 'Not assigned' }}
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="dp-card p-3 h-100" style="background-color: #FFF8E5; border-color: #FDE68A;">
            <h6 class="fw-bold mb-2"><i class="mdi mdi-comment-text-outline text-warning-emphasis me-1"></i>Customer Instructions</h6>
            @php
                $additionalAddrDetails = trim((string) ($addr['address_details'] ?? ''));
                $delInstr = trim((string) ($schedule['delivery_instructions'] ?? $addr['delivery_instructions'] ?? ''));
                $accessInstr = trim((string) ($addr['access_instructions'] ?? ''));
                $driverNotes = trim((string) ($schedule['driver_notes'] ?? ''));
                $contactPref = trim((string) ($contact['contact_preference'] ?? ''));
                $hasAnyFinalInstr = $delInstr !== '' || $additionalAddrDetails !== '' || $accessInstr !== '' || $driverNotes !== '' || $contactPref !== '' || !empty($booking->notes);
            @endphp
            <div class="small">
                @if($delInstr !== '')
                    <p class="mb-1"><strong>Final Delivery Instructions:</strong> {{ $delInstr }}</p>
                @endif
                @if($additionalAddrDetails !== '')
                    <p class="mb-1"><strong>Additional Address Instructions:</strong> {{ $additionalAddrDetails }}</p>
                @endif
                @if($driverNotes !== '')
                    <p class="mb-1"><strong>Delivery Person Notes:</strong> {{ $driverNotes }}</p>
                @endif
                @if($accessInstr !== '')
                    <p class="mb-1"><strong>Access Instructions:</strong> {{ $accessInstr }}</p>
                @endif
                @if($contactPref !== '')
                    <p class="mb-1"><strong>Contact Preference:</strong> {{ ucfirst($contactPref) }}</p>
                @endif
                @if(!empty($booking->notes) || !empty($contact['notes']))
                    <p class="mb-0"><strong>Customer Notes:</strong> {{ $booking->notes ?? $contact['notes'] }}</p>
                @endif
                @if(!$hasAnyFinalInstr)
                    <div class="text-muted">No special delivery instructions recorded.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12">
        @php
            $fpState = $paymentState ?? ['total_minor' => (int) ($booking->final_amount_minor ?? $booking->estimated_amount_minor ?? 0), 'confirmed_paid_minor' => 0, 'outstanding_minor' => 0, 'is_pricing_complete' => false, 'currency' => $booking->currency];
            $fpPricingComplete = (bool) ($fpState['is_pricing_complete'] ?? false);
            $fpPricingLabel = $fpState['pricing']['pricing_status_label'] ?? ($fpPricingComplete ? 'Finalized' : 'Pending');
            $fpGateway = strtolower((string) ($booking->payment_gateway ?? 'cod'));
            $fpGatewayLabel = match ($fpGateway) {
                'stripe' => 'Stripe',
                'paypal' => 'PayPal',
                'cod' => 'Cash on Delivery',
                default => strtoupper($fpGateway),
            };
            $fpPayLabel = $booking->getFinancialPaymentStatusLabel();
            $fpPayBadge = match (strtolower((string) $booking->payment_status)) {
                'paid' => 'bg-success-subtle text-success',
                'partially_paid' => 'bg-info-subtle text-info-emphasis',
                'failed' => 'bg-danger-subtle text-danger',
                default => 'bg-warning-subtle text-warning-emphasis',
            };
        @endphp
        <div class="dp-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0"><i class="mdi mdi-cash-check text-success me-1"></i>Booking Pricing &amp; Payment Summary</h6>
                <span class="badge {{ $fpPayBadge }}">{{ $fpPayLabel }}</span>
            </div>
            <div class="row g-2 small">
                <div class="col-6 col-md-3">
                    <div class="text-muted">{{ $fpPricingComplete ? 'Final Booking Amount' : 'Estimated Booking Amount' }}</div>
                    <div class="fw-bold text-dark">{{ $currencyService->formatMinor((int) $fpState['total_minor'], $booking->currency) }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted">Pricing Status</div>
                    <div><span class="badge {{ $fpPricingComplete ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis' }}">{{ $fpPricingLabel }}</span></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted">Confirmed Paid / Advance</div>
                    <div class="fw-bold text-success">{{ $currencyService->formatMinor((int) $fpState['confirmed_paid_minor'], $booking->currency) }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="text-muted">Outstanding Balance ({{ $fpGatewayLabel }})</div>
                    <div class="fw-bold text-danger">{{ $currencyService->formatMinor((int) $fpState['outstanding_minor'], $booking->currency) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="dp-card p-3">
            <h6 class="fw-bold mb-2">Categories &amp; Items Summary</h6>
            @forelse($categoryGroups ?? [] as $group)
                <div class="border rounded-3 p-2 px-3 mb-2 bg-white">
                    <div class="fw-bold small mb-1">{{ $group['category_name'] }}</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Service / Item</th>
                                    <th>Qty / Weight</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($group['items'] as $item)
                                    <tr>
                                        <td class="fw-semibold">{{ $item->item_name ?: $item->service_name }}</td>
                                        <td>{{ $item->pricing_mode === 'weight' ? number_format((float) $item->weight_kg, 2) . ' kg' : $item->quantity . ' pcs' }}</td>
                                        <td class="text-muted">{{ $item->adjustment_reason ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted py-1">No items added yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="text-muted small py-2">No categories or items recorded.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
