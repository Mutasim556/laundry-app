@extends('delivery-person-portal.layouts.app')
@section('title', 'Collection Booking ' . $booking->reference)
@section('page_title', 'Collection: ' . $booking->reference)

@section('content')
@php
    $addr = is_array($booking->address_snapshot) ? $booking->address_snapshot : [];
    $schedule = is_array($booking->schedule_snapshot) ? $booking->schedule_snapshot : [];
    $contact = is_array($booking->contact_snapshot) ? $booking->contact_snapshot : [];
    $isOtpVerified = $booking->collection_otp_verified_at !== null;
    $isPaymentPaid = $paymentState['is_payment_satisfied'];
    $completedStages = $booking->getCompletedOperationalStatuses();
    $isFinalized = in_array(\App\Models\Booking::STATUS_FINALIZED, $completedStages, true);
@endphp

<!-- Header & 4-Step Collection Progress -->
<div class="dp-card p-3 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border-color: #FDE68A;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <span class="badge bg-warning-subtle text-warning-emphasis mb-1">Collection Assignment</span>
            <h5 class="fw-bold mb-0">{{ $booking->reference }}</h5>
            <div class="small text-muted">
                Customer: <strong>{{ $booking->customer_name }}</strong> &bull; {{ $booking->customer_phone }}
                &bull; Slot: {{ $booking->collection_slot_label ?: ($schedule['collection_date'] ?? '-') }}
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($ctx['can_edit_collection'])
                <a href="{{ route('delivery-person.bookings.edit', $booking) }}" class="btn btn-sm dp-btn-yellow px-3">
                    <i class="mdi mdi-pencil-outline me-1"></i>Edit Booking
                </a>
            @endif
        </div>
    </div>

    @php
        $step1Done = (bool) $pricingSummary['is_pricing_complete'];
        $step2Done = $isOtpVerified;
        $step3Done = $isPaymentPaid;
        $step4Done = $isFinalized;
        $stepDoneStyle = 'background-color: #DCFCE7; color: #15803D; border: 1px solid #86EFAC;';
        $stepActiveStyle = 'background-color: #FEF3C7; color: #92400E; border: 1px solid #FDE68A;';
        $stepWaitStyle = 'background-color: #F1F5F9; color: #475569; border: 1px solid #CBD5E1;';
    @endphp
    <div class="row g-2 text-center small">
        <div class="col-6 col-md-3">
            <div class="p-2 rounded-3 fw-semibold" style="{{ $step1Done ? $stepDoneStyle : $stepActiveStyle }}">
                @if($step1Done)<i class="mdi mdi-check-circle me-1"></i>@endif 1. Booking Reviewed
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-2 rounded-3 fw-semibold" style="{{ $step2Done ? $stepDoneStyle : ($step1Done ? $stepActiveStyle : $stepWaitStyle) }}">
                @if($step2Done)<i class="mdi mdi-check-circle me-1"></i>@endif 2. OTP Verified
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-2 rounded-3 fw-semibold" style="{{ $step3Done ? $stepDoneStyle : ($step2Done ? $stepActiveStyle : $stepWaitStyle) }}">
                @if($step3Done)<i class="mdi mdi-check-circle me-1"></i>@endif 3. Payment Confirmed
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-2 rounded-3 fw-semibold" style="{{ $step4Done ? $stepDoneStyle : ($step3Done ? $stepActiveStyle : $stepWaitStyle) }}">
                @if($step4Done)<i class="mdi mdi-check-circle me-1"></i>@endif 4. Finalized
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Customer Address & Hotel Details -->
    <div class="col-12 col-lg-6">
        <div class="dp-card p-3 h-100">
            <h6 class="fw-bold mb-2"><i class="mdi mdi-map-marker-outline text-warning-emphasis me-1"></i>Collection Address &amp; Hotel Details</h6>
            @php
                $fullAddress = trim((string) ($addr['selected_address'] ?? implode(' ', array_filter([$addr['address_line_1'] ?? '', $addr['address_line_2'] ?? '']))));
                $collectionTypeRaw = (string) ($addr['hotel_collection_type'] ?? '');
                $collectionTypeLabel = match ($collectionTypeRaw) {
                    'meet_driver' => 'Meet Delivery Person in person',
                    'use_reception' => 'Leave at Hotel Reception',
                    default => ($collectionTypeRaw !== '' ? ucfirst(str_replace('_', ' ', $collectionTypeRaw)) : ''),
                };
            @endphp
            <div class="small">
                @if($fullAddress !== '')
                    <div class="mb-1"><strong>Address:</strong> {{ $fullAddress }}</div>
                @endif
                @if(!empty($addr['city']) || !empty($addr['postcode']))
                    <div class="mb-1"><strong>City / Postcode:</strong> {{ $addr['city'] ?? '' }} {{ $addr['postcode'] ?? '' }}</div>
                @endif
                @if(!empty($addr['address_type']) || !empty($addr['collection_mode']))
                    <div class="mb-1"><strong>Location Type:</strong> {{ ucfirst(strtolower((string) ($addr['address_type'] ?: $addr['collection_mode']))) }}</div>
                @endif
                @if(!empty($addr['building']) || !empty($addr['floor']))
                    <div class="mb-1"><strong>Building / Floor:</strong> {{ $addr['building'] ?? '' }} {{ $addr['floor'] ?? '' }}</div>
                @endif
                @if(!empty($addr['hotel_name']) || !empty($addr['room_number']) || $collectionTypeLabel !== '')
                    <div class="mt-2 p-2 rounded-2" style="background: #FFF8E5; border: 1px solid #FDE68A;">
                        @if(!empty($addr['hotel_name']) || !empty($addr['room_number']))
                            <div><strong>Hotel / Room:</strong> {{ $addr['hotel_name'] ?? '' }} {{ !empty($addr['room_number']) ? '— Room ' . $addr['room_number'] : '' }}</div>
                        @endif
                        @if($collectionTypeLabel !== '')
                            <div class="mt-1"><strong>Collection Type:</strong> <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ $collectionTypeLabel }}</span></div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- High-Priority Customer Instructions -->
    <div class="col-12 col-lg-6">
        <div class="dp-card p-3 h-100" style="background-color: #FFF8E5; border-color: #FDE68A;">
            <h6 class="fw-bold mb-2"><i class="mdi mdi-comment-alert-outline text-warning-emphasis me-1"></i>CUSTOMER INSTRUCTIONS</h6>
            @php
                $additionalAddrDetails = trim((string) ($addr['address_details'] ?? ''));
                $accessInstr = trim((string) ($addr['access_instructions'] ?? ''));
                $colInstr = trim((string) ($schedule['collection_instructions'] ?? $addr['collection_instructions'] ?? ''));
                $delInstr = trim((string) ($schedule['delivery_instructions'] ?? $addr['delivery_instructions'] ?? ''));
                $driverNotes = trim((string) ($schedule['driver_notes'] ?? ''));
                $serviceInstr = trim((string) ($schedule['service_instructions'] ?? $booking->notes ?? ''));
                $contactPref = trim((string) ($contact['contact_preference'] ?? ''));
                $contactNotes = trim((string) ($contact['notes'] ?? ''));
                $hasAnyInstr = $collectionTypeLabel !== '' || $additionalAddrDetails !== '' || $accessInstr !== '' || $colInstr !== '' || $delInstr !== '' || $driverNotes !== '' || $serviceInstr !== '' || $contactPref !== '' || $contactNotes !== '';
            @endphp
            <div class="small">
                @if($collectionTypeLabel !== '')
                    <p class="mb-1"><strong>Collection Type:</strong> {{ $collectionTypeLabel }}</p>
                @endif
                @if($colInstr !== '')
                    <p class="mb-1"><strong>Collection Instructions:</strong> {{ $colInstr }}</p>
                @endif
                @if($additionalAddrDetails !== '')
                    <p class="mb-1"><strong>Additional Address Instructions:</strong> {{ $additionalAddrDetails }}</p>
                @endif
                @if($driverNotes !== '')
                    <p class="mb-1"><strong>Delivery Person Notes:</strong> {{ $driverNotes }}</p>
                @endif
                @if($serviceInstr !== '')
                    <p class="mb-1"><strong>Laundry / Service Instructions:</strong> {{ $serviceInstr }}</p>
                @endif
                @if($delInstr !== '')
                    <p class="mb-1"><strong>Final Delivery Instructions:</strong> {{ $delInstr }}</p>
                @endif
                @if($accessInstr !== '')
                    <p class="mb-1"><strong>Access Instructions:</strong> {{ $accessInstr }}</p>
                @endif
                @if($contactPref !== '')
                    <p class="mb-1"><strong>Contact Preference:</strong> {{ ucfirst($contactPref) }}</p>
                @endif
                @if($contactNotes !== '')
                    <p class="mb-0"><strong>Contact Notes:</strong> {{ $contactNotes }}</p>
                @endif
                @if(!$hasAnyInstr)
                    <div class="text-muted">No special customer instructions provided.</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Active Operational Services & Categories -->
    <div class="col-12">
        <div class="dp-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Services &amp; Operational Items</h6>
                <span class="fw-bold">{{ $currencyService->formatMinor($paymentState['total_minor'], $booking->currency) }} Total</span>
            </div>

            @forelse($categoryGroups ?? [] as $group)
                @php
                    $originType = $group['origin_type'] ?? 'unknown';
                    $borderColor = match ($originType) {
                        'customer' => '#FFC629',
                        'delivery_person' => '#16A34A',
                        'admin' => '#3B82F6',
                        default => '#64748B',
                    };
                    $badgeStyle = match ($originType) {
                        'customer' => 'background-color: #EDE9FE; color: #6D28D9;',
                        'delivery_person' => 'background-color: #DCFCE7; color: #15803D;',
                        'admin' => 'background-color: #DBEAFE; color: #1D4ED8;',
                        default => 'background-color: #F1F5F9; color: #475569;',
                    };
                @endphp
                <div class="border rounded-3 p-3 mb-2 bg-white" style="border-left: 4px solid {{ $borderColor }} !important;">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="fw-bold text-dark">{{ $group['category_name'] }}</span>
                        <span class="badge rounded-pill px-2 py-1" style="font-size: 0.72rem; {{ $badgeStyle }}">{{ $group['origin_badge_label'] ?? 'Selected' }}</span>
                        @if(!empty($group['selected_option_name']))
                            <span class="badge rounded-pill px-2 py-1" style="background-color: #E0F2FE; color: #0369A1; font-size: 0.72rem;">&check; {{ $group['selected_option_name'] }}</span>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Service / Item</th>
                                    <th>Qty / Weight</th>
                                    <th>Notes</th>
                                    <th class="text-end">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($group['items'] as $item)
                                    <tr>
                                        <td class="fw-semibold">{{ $item->item_name ?: $item->service_name }}</td>
                                        <td>{{ $item->pricing_mode === 'weight' ? number_format((float) $item->weight_kg, 2) . ' kg' : $item->quantity . ' pcs' }}</td>
                                        <td class="text-muted">{{ $item->adjustment_reason ?? '-' }}</td>
                                        <td class="text-end fw-semibold">{{ $currencyService->formatMinor((int) $item->line_total_minor, $booking->currency) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted py-2">No items added yet in this category.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="text-muted small py-2">No operational categories found for this booking.</div>
            @endforelse
        </div>
    </div>

    <!-- Step 2: Collection OTP Verification -->
    @if($ctx['can_manage_otp'] || $isOtpVerified)
        <div class="col-12 col-lg-6">
            <div class="dp-card p-3 h-100">
                <h6 class="fw-bold mb-2"><i class="mdi mdi-shield-check-outline text-primary me-1"></i>Collection Confirmation OTP</h6>
                @if($isOtpVerified)
                    <div class="alert alert-success py-2 small mb-0">
                        <i class="mdi mdi-check-circle me-1"></i>OTP Verified at {{ $booking->collection_otp_verified_at->format('d M Y, H:i') }}.
                    </div>
                @else
                    <p class="small text-muted mb-2">Send a 6-digit verification code to the customer's verified email, then enter the code they provide.</p>
                    <form method="POST" action="{{ route('delivery-person.bookings.otp.send', $booking) }}" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Send Collection OTP</button>
                    </form>
                    <form method="POST" action="{{ route('delivery-person.bookings.otp.verify', $booking) }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="otp_code" maxlength="6" inputmode="numeric" class="form-control form-control-sm" placeholder="6-digit OTP" required>
                        <button type="submit" class="btn btn-sm dp-btn-yellow px-3 text-nowrap">Verify OTP</button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <!-- Step 3: Customer Payment Collection & Finalize -->
    <div class="col-12 col-lg-6">
        <div class="dp-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0"><i class="mdi mdi-credit-card-outline text-success me-1"></i>Customer Payment</h6>
                <span class="badge bg-{{ $isPaymentPaid ? 'success' : 'warning' }}-subtle text-{{ $isPaymentPaid ? 'success' : 'warning-emphasis' }}">
                    {{ ucfirst(str_replace('_', ' ', (string) $booking->payment_status)) }}
                </span>
            </div>

            <div class="small mb-2">
                <div class="d-flex justify-content-between"><span>Total Booking Amount:</span><strong>{{ $currencyService->formatMinor($paymentState['total_minor'], $booking->currency) }}</strong></div>
                <div class="d-flex justify-content-between"><span>Confirmed Paid / Advance:</span><strong class="text-success">{{ $currencyService->formatMinor($paymentState['confirmed_paid_minor'], $booking->currency) }}</strong></div>
                <div class="d-flex justify-content-between border-top pt-1 mt-1"><span>Outstanding Balance:</span><strong class="text-danger">{{ $currencyService->formatMinor($paymentState['outstanding_minor'], $booking->currency) }}</strong></div>
            </div>

            @if($ctx['can_collect_payment'] && $isOtpVerified && $paymentState['outstanding_minor'] > 0)
                <ul class="nav nav-pills nav-fill small mb-2" role="tablist">
                    <li class="nav-item"><button class="nav-link active py-1" data-bs-toggle="pill" data-bs-target="#tabCash" type="button">Cash in Hand</button></li>
                    <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="pill" data-bs-target="#tabTransfer" type="button">Online Transfer</button></li>
                    <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="pill" data-bs-target="#tabGateway" type="button">Stripe / PayPal</button></li>
                </ul>
                <div class="tab-content small">
                    <div class="tab-pane fade show active" id="tabCash">
                        <form method="POST" action="{{ route('delivery-person.bookings.payments.cash', $booking) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label mb-1">Cash Amount Received ({{ $booking->currency }})</label>
                                <input type="number" step="0.01" min="0.01" max="{{ number_format($paymentState['outstanding_minor'] / 100, 2, '.', '') }}" name="amount" value="{{ number_format($paymentState['outstanding_minor'] / 100, 2, '.', '') }}" class="form-control form-control-sm" required>
                            </div>
                            <button type="submit" class="btn btn-sm btn-success w-100">Confirm Physical Cash Received</button>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tabTransfer">
                        <form method="POST" action="{{ route('delivery-person.bookings.payments.transfer', $booking) }}">
                            @csrf
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ number_format($paymentState['outstanding_minor'] / 100, 2, '.', '') }}" class="form-control form-control-sm" placeholder="Amount" required>
                                </div>
                                <div class="col-6">
                                    <input type="text" name="external_reference" class="form-control form-control-sm" placeholder="Bank Ref #" required>
                                </div>
                            </div>
                            <input type="text" name="receiving_account" class="form-control form-control-sm mb-2" placeholder="Receiving Business Account" value="Business Bank Account">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Submit Transfer (Awaiting Admin Verification)</button>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tabGateway">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-sm btn-outline-dark js-dp-gateway" data-url="{{ route('delivery-person.bookings.payments.stripe', $booking) }}">Generate Stripe Payment Session</button>
                            <button type="button" class="btn btn-sm btn-outline-primary js-dp-gateway" data-url="{{ route('delivery-person.bookings.payments.paypal', $booking) }}">Generate PayPal Approval Link</button>
                            <div id="dpGatewayResult" class="small text-muted"></div>
                        </div>
                    </div>
                </div>
            @elseif(!$isOtpVerified && !$isFinalized)
                <div class="alert alert-warning py-2 small mb-2">Verify Collection OTP first to unlock customer payment options.</div>
            @endif

            @if($ctx['can_finalize_collection'] && $isOtpVerified && $isPaymentPaid)
                <form method="POST" action="{{ route('delivery-person.bookings.finalize', $booking) }}" class="mt-3 pt-2 border-top">
                    @csrf
                    <button type="submit" class="btn btn-sm dp-btn-yellow w-100 fw-bold py-2">
                        <i class="mdi mdi-check-decagram me-1"></i>Finalize Collection Booking
                    </button>
                </form>
            @elseif(!$isFinalized && $isOtpVerified && !$isPaymentPaid)
                <div class="mt-2 small text-warning-emphasis fw-semibold">Awaiting Customer Payment before Finalization.</div>
            @endif

            @if($ctx['is_collection_assignee'] && $isFinalized && $booking->booking_status === \App\Models\Booking::STATUS_DELIVERY_MAN_PICKED_UP && !in_array(\App\Models\Booking::STATUS_DELIVERY_MAN_PICKED_UP, $completedStages, true))
                <form method="POST" action="{{ route('delivery-person.bookings.mark-picked-up', $booking) }}" class="mt-3 pt-2 border-top">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success w-100 fw-bold py-2">
                        <i class="mdi mdi-check-circle me-1"></i>Mark Delivery Man Picked Up Done
                    </button>
                </form>
            @elseif($isFinalized && in_array(\App\Models\Booking::STATUS_DELIVERY_MAN_PICKED_UP, $completedStages, true))
                <div class="mt-3 pt-2 border-top">
                    <div class="alert alert-success py-2 small mb-0">
                        <i class="mdi mdi-check-circle me-1"></i>Delivery Man Picked Up Marked Done.
                    </div>
                </div>
            @endif
        </div>
    </div>

    @php
        $isPickedUpDone = in_array(\App\Models\Booking::STATUS_DELIVERY_MAN_PICKED_UP, $completedStages, true);
        $isStoreReceivedDone = in_array(\App\Models\Booking::STATUS_STORE_RECEIVED, $completedStages, true);
        $assignedStore = $booking->currentStoreAssignment?->store ?? $booking->assignedStore;
        $handover = $booking->currentStoreHandover;
        $hStatus = $handover?->status;

        if ($isStoreReceivedDone || $hStatus === \App\Models\BookingStoreHandover::STATUS_ACCEPTED) {
            $handoverBadgeText = 'Accepted';
            $handoverBadgeStyle = 'background-color: #DCFCE7; color: #15803D;';
        } elseif ($hStatus === \App\Models\BookingStoreHandover::STATUS_PENDING) {
            $handoverBadgeText = 'Pending Store Acceptance';
            $handoverBadgeStyle = 'background-color: #FEF3C7; color: #92400E;';
        } elseif ($hStatus === \App\Models\BookingStoreHandover::STATUS_REJECTED) {
            $handoverBadgeText = 'Rejected / Issue Reported';
            $handoverBadgeStyle = 'background-color: #FEE2E2; color: #B91C1C;';
        } else {
            $handoverBadgeText = 'Ready to Request';
            $handoverBadgeStyle = 'background-color: #FFEDD5; color: #9A3412;';
        }
    @endphp

    @if($ctx['is_collection_assignee'] && $isPickedUpDone)
        <!-- Store Handover Section (Appears ONLY after Delivery Person Picked Up Done) -->
        <div class="col-12" id="dpStoreHandoverSection">
            <div class="dp-card p-3 p-md-4" style="border-color: #BFDBFE; background-color: #FFFFFF;">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                              style="width: 36px; height: 36px; background-color: #FFF8E5; color: #171717; border: 1px solid #FDE68A;">
                            <i class="mdi mdi-store"></i>
                        </span>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Store Handover</h6>
                            <div class="small text-muted" style="font-size: 0.78rem;">Deliver the collected laundry to the assigned store.</div>
                        </div>
                    </div>
                    <span id="dpHandoverStatusBadge" class="badge rounded-pill px-3 py-1" style="font-size: 0.76rem; {{ $handoverBadgeStyle }}">
                        {{ $handoverBadgeText }}
                    </span>
                </div>

                @if(!$assignedStore || $assignedStore->trashed() || $assignedStore->status !== 'active')
                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="mdi mdi-alert-outline me-1"></i>No active store is currently assigned to this booking. Please contact an administrator before handing over laundry.
                    </div>
                @else
                    @php
                        $storeAddressFull = implode(', ', array_filter([
                            $assignedStore->address_line_1,
                            $assignedStore->address_line_2,
                            $assignedStore->city,
                            $assignedStore->county_region,
                            $assignedStore->postcode,
                            $assignedStore->country_name,
                        ]));
                    @endphp
                    <div class="p-3 rounded-3 mb-3 small" style="background-color: #F0F7FF; border: 1px solid #BFDBFE;">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="text-muted fw-semibold">Assigned Store</span>
                            <span class="text-muted">Ref: <strong>{{ $booking->reference }}</strong> &bull; Delivery Person: <strong>{{ $person->full_name }}</strong></span>
                        </div>
                        <div class="fs-6 fw-bold text-dark mb-1">{{ $assignedStore->name }}</div>
                        <div class="text-muted mb-2">{{ $storeAddressFull ?: 'Address not provided' }}</div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span class="badge rounded-pill px-2 py-1" style="background-color: #DBEAFE; color: #1D4ED8;">{{ $assignedStore->postcode ?: 'No Postcode' }}</span>
                            <span class="text-dark fw-semibold"><i class="mdi mdi-phone text-success me-1"></i>{{ $assignedStore->phone ?: 'Public phone not listed' }}</span>
                        </div>
                    </div>

                    <!-- Handover Progress -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 small">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="fw-semibold text-dark me-1">Handover Progress:</span>
                            <span class="badge rounded-pill px-2 py-1" style="background-color: #DCFCE7; color: #15803D;">&check; Picked Up</span>
                            <span class="text-muted">&rarr;</span>
                            <span id="dpProgRequested" class="badge rounded-pill px-2 py-1" style="{{ in_array($hStatus, ['pending', 'accepted'], true) || $isStoreReceivedDone ? 'background-color: #DCFCE7; color: #15803D;' : 'background-color: #FFEDD5; color: #9A3412;' }}">
                                Acceptance Requested
                            </span>
                            <span class="text-muted">&rarr;</span>
                            <span id="dpProgReceived" class="badge rounded-pill px-2 py-1" style="{{ $isStoreReceivedDone || $hStatus === 'accepted' ? 'background-color: #DCFCE7; color: #15803D;' : 'background-color: #F1F5F9; color: #475569;' }}">
                                Store Received
                            </span>
                        </div>
                        <span id="dpHandoverHintText" class="text-muted">
                            @if($isStoreReceivedDone || $hStatus === 'accepted')
                                Store Received Completed
                            @elseif($hStatus === 'pending')
                                Waiting for store acceptance
                            @elseif($hStatus === 'rejected')
                                Issue reported by store
                            @else
                                Waiting for request
                            @endif
                        </span>
                    </div>

                    @if($hStatus === \App\Models\BookingStoreHandover::STATUS_REJECTED && $handover)
                        <div class="alert alert-danger py-2 small mb-3">
                            <strong>Store Issue Reported ({{ $handover->rejected_at?->format('d M Y, H:i') }}):</strong>
                            {{ $handover->rejection_reason }}
                        </div>
                    @endif

                    <div id="dpHandoverAlert" class="alert py-2 small d-none mb-2"></div>

                    @if($isStoreReceivedDone || $hStatus === \App\Models\BookingStoreHandover::STATUS_ACCEPTED)
                        @php
                            $colAsg = $ctx['collection_assignment'] ?? $booking->currentCollectionAssignment;
                            $colBaseMinor = (int) ($colAsg?->rate_minor ?? 0);
                            $colTipMinor = (int) ($colAsg?->tip_share_minor ?? 0);
                            $colTotalEarnedMinor = $colBaseMinor + $colTipMinor;
                            $colCur = $colAsg?->currency ?: $booking->currency;
                        @endphp
                        <div class="p-3 rounded-3 small" style="background-color: #ECFDF5; border: 1px solid #A7F3D0;">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <span class="fw-bold text-success"><i class="mdi mdi-check-circle me-1"></i>Store Handover Completed</span>
                                <span class="badge rounded-pill px-2 py-1" style="background-color: #16A34A; color: #FFFFFF;">Collection Job Completed</span>
                            </div>
                            <div><strong>Accepted By:</strong> {{ $handover?->acceptedByStoreUser?->name ?? 'Store Staff' }}</div>
                            <div><strong>Accepted At:</strong> {{ $handover?->accepted_at?->format('d M Y, H:i') ?? '-' }}</div>
                            <div><strong>Assigned Store:</strong> {{ $assignedStore->name }}</div>
                            <div><strong>Booking Status:</strong> Store Received Completed</div>
                            @if($colAsg && $colAsg->earnings_status === 'credited')
                                <div class="mt-2 pt-2 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <span>Credited Collection Earnings (Delivery {{ $currencyService->formatMinor($colBaseMinor, $colCur) }} + Tip {{ $currencyService->formatMinor($colTipMinor, $colCur) }}):</span>
                                    <strong class="text-success fs-6">{{ $currencyService->formatMinor($colTotalEarnedMinor, $colCur) }}</strong>
                                </div>
                            @endif
                        </div>
                    @elseif($hStatus === \App\Models\BookingStoreHandover::STATUS_PENDING)
                        <button type="button" class="btn btn-warning w-100 fw-bold py-2 rounded-pill" disabled>
                            <i class="mdi mdi-clock-outline me-1"></i>Pending Store Acceptance
                        </button>
                    @else
                        <button type="button"
                                id="btnRequestStoreAcceptance"
                                data-url="{{ route('delivery-person.bookings.store-handover', $booking) }}"
                                class="btn btn-dark w-100 fw-bold py-2 rounded-pill">
                            <i class="mdi mdi-send me-1"></i>Request Store Acceptance
                        </button>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    $(document).on('click', '.js-dp-gateway', function () {
        const $btn = $(this).prop('disabled', true);
        $.post($(this).data('url'), { _token: $('meta[name="csrf-token"]').attr('content') })
            .done(function (res) {
                const url = res.order?.approve_url || '';
                $('#dpGatewayResult').html(url ? '<a href="' + url + '" target="_blank" class="fw-bold">Open Customer Approval Link</a>' : 'Payment session initialized.');
            })
            .fail(function (xhr) {
                $('#dpGatewayResult').text(xhr.responseJSON?.message || 'Unable to initialize gateway payment.');
            })
            .always(function () { $btn.prop('disabled', false); });
    });

    @if($ctx['is_collection_assignee'] && $isPickedUpDone)
    $(document).on('click', '#btnRequestStoreAcceptance', function () {
        const $btn = $(this);
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Sending Request...');
        $('#dpHandoverAlert').addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: $btn.data('url'),
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#dpHandoverStatusBadge')
                    .text('Pending Store Acceptance')
                    .css({ 'background-color': '#FEF3C7', 'color': '#92400E' });
                $('#dpProgRequested').css({ 'background-color': '#DCFCE7', 'color': '#15803D' });
                $('#dpHandoverHintText').text('Waiting for store acceptance');
                $btn.removeClass('btn-dark').addClass('btn-warning').html('<i class="mdi mdi-clock-outline me-1"></i>Pending Store Acceptance');
                $('#dpHandoverAlert').addClass('alert-success').removeClass('d-none').text(res.message || 'Store handover acceptance requested.');
            },
            error: function (xhr) {
                $('#dpHandoverAlert').addClass('alert-danger').removeClass('d-none').text(xhr.responseJSON?.message || 'Unable to send handover request.');
                $btn.prop('disabled', false).html('<i class="mdi mdi-send me-1"></i>Request Store Acceptance');
            }
        });
    });
    @endif
</script>
@endpush
