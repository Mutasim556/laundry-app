@extends('delivery-person-portal.layouts.app')
@section('title', 'Final Delivery ' . $booking->reference)
@section('page_title', 'Final Delivery: ' . $booking->reference)

@section('content')
@php
    $addr = is_array($booking->address_snapshot) ? $booking->address_snapshot : [];
    $schedule = is_array($booking->schedule_snapshot) ? $booking->schedule_snapshot : [];
    $contact = is_array($booking->contact_snapshot) ? $booking->contact_snapshot : [];
@endphp

<div class="dp-card p-3 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #EFF6FF 100%); border-color: #BFDBFE;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="badge bg-info-subtle text-info-emphasis mb-1">Final Delivery Assignment</span>
            <h5 class="fw-bold mb-0">{{ $booking->reference }}</h5>
            <div class="small text-muted">Customer: {{ $booking->customer_name }} &bull; {{ $booking->customer_phone }}</div>
        </div>
        <span class="badge bg-primary-subtle text-primary px-3 py-2">{{ \App\Models\Booking::STATUS_LABELS[$booking->booking_status] ?? ucfirst($booking->booking_status) }}</span>
    </div>
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
