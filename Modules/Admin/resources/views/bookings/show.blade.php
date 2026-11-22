@extends('admin::layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/dist/libs/select2/dist/css/select2.min.css') }}">
<style>
    .bk-detail-page {
        background-color: #F6F8FC;
        border-radius: 16px;
    }
    .bk-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        transition: transform 0.16s ease, box-shadow 0.16s ease;
    }
    .bk-card:hover {
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.07);
    }
    .bk-header-card {
        background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%);
        border: 1px solid #FDE68A;
        border-left: 5px solid #FFC629;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(255, 198, 41, 0.12);
    }
    .bk-btn-yellow {
        background-color: #FFC629;
        border-color: #FFC629;
        color: #171717;
        font-weight: 600;
        border-radius: 10px;
        transition: background-color 0.15s ease, transform 0.15s ease;
    }
    .bk-btn-yellow:hover, .bk-btn-yellow:focus {
        background-color: #f2b718;
        border-color: #f2b718;
        color: #171717;
        transform: translateY(-1px);
    }
    .bk-btn-outline-danger {
        border-radius: 10px;
        font-weight: 600;
    }
    .bk-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FFC629 0%, #F59E0B 100%);
        color: #171717;
        font-weight: 700;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .bk-services-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748B;
        border-bottom: 1px solid #E2E8F0;
        background: #F8FAFC;
    }
    .bk-services-table tbody tr.bk-service-row {
        transition: background-color 0.14s ease;
    }
    .bk-services-table tbody tr.bk-service-row:hover {
        background-color: #F8FAFC;
    }
    .bk-subtotal-row td {
        background-color: #FFF8E5;
        border-top: 2px solid #FFC629;
        color: #171717;
    }
    .bk-schedule-box-collect {
        background-color: #EFF6FF;
        border: 1px solid #BFDBFE;
        border-radius: 12px;
    }
    .bk-schedule-box-deliver {
        background-color: #ECFDF5;
        border: 1px solid #A7F3D0;
        border-radius: 12px;
    }
    .bk-final-total-box {
        background: linear-gradient(135deg, #FFF8E5 0%, #FEF3C7 100%);
        border: 1px solid #FCD34D;
        border-radius: 12px;
    }
    .bk-status-select-wrap .select2-container {
        width: 100% !important;
    }
    .bk-status-select-wrap .select2-container--default .select2-selection--single {
        min-height: 36px;
        border: 1px solid #CBD5E1;
        border-radius: 10px;
        display: flex;
        align-items: center;
        background-color: #FFFFFF;
    }
    .bk-status-select-wrap .select2-container--default.select2-container--open .select2-selection--single,
    .bk-status-select-wrap .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #FFC629;
        box-shadow: 0 0 0 0.2rem rgba(255, 198, 41, 0.25);
    }
    .bk-status-select-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #171717;
        font-size: 0.85rem;
        font-weight: 600;
        line-height: 34px;
        padding-left: 10px;
        padding-right: 28px;
    }
    .bk-status-select-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 34px;
        right: 6px;
    }
    .select2-dropdown {
        border: 1px solid #E2E8F0;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
        overflow: hidden;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #CBD5E1;
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 0.85rem;
    }
    .select2-container--default .select2-results > .select2-results__options {
        max-height: 280px;
    }
    .select2-container--default .select2-results__option {
        padding: 0;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #FFF8E5;
        color: #171717;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #FFF8E5;
        color: #171717;
    }
    .s2-st-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 8px 12px;
        font-size: 0.85rem;
        border-radius: 6px;
    }
    .s2-st-completed {
        background-color: #DCFCE7;
        color: #171717;
    }
    .s2-st-current {
        background-color: #FFF8E5;
        color: #171717;
        font-weight: 700;
    }
    .s2-st-upcoming {
        color: #777777;
    }
    .s2-st-check {
        color: #16A34A;
        font-weight: 700;
        margin-right: 6px;
    }
    .s2-st-current-pill {
        background-color: #FFC629;
        color: #171717;
        font-size: 0.68rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 999px;
        flex-shrink: 0;
    }
    @media (prefers-reduced-motion: reduce) {
        .bk-card, .bk-btn-yellow, .bk-services-table tbody tr.bk-service-row {
            transition: none !important;
        }
        .bk-btn-yellow:hover {
            transform: none !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $address = is_array($booking->address_snapshot) ? $booking->address_snapshot : [];
    $schedule = is_array($booking->schedule_snapshot) ? $booking->schedule_snapshot : [];
    $isTerminal = in_array($booking->booking_status, [\App\Models\Booking::STATUS_CANCELLED, \App\Models\Booking::STATUS_COMPLETED], true);

    $statusStyle = \App\Models\Booking::statusBadgeStyle($booking->booking_status);

    $payBadgeStyle = match($pricingSummary['payment_status_label']) {
        'Paid' => 'background-color: #D1FAE5; color: #047857;',
        'Authorized' => 'background-color: #DBEAFE; color: #1D4ED8;',
        'Failed' => 'background-color: #FEE2E2; color: #B91C1C;',
        'Refunded', 'Partially Refunded' => 'background-color: #EDE9FE; color: #6D28D9;',
        default => 'background-color: #FEF3C7; color: #B45309;',
    };

    $nameParts = preg_split('/\s+/', trim((string) $booking->customer_name)) ?: [];
    $initials = strtoupper(
        substr($nameParts[0] ?? 'C', 0, 1) .
        (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : '')
    );

    $categoryPalette = [
        ['bg' => '#FFF8E5', 'border' => '#FFC629', 'text' => '#92400E'],
        ['bg' => '#EFF6FF', 'border' => '#3B82F6', 'text' => '#1D4ED8'],
        ['bg' => '#F5F3FF', 'border' => '#8B5CF6', 'text' => '#5B21B6'],
        ['bg' => '#ECFDF5', 'border' => '#10B981', 'text' => '#065F46'],
    ];
@endphp

<div class="container-fluid py-3 bk-detail-page">
    <!-- Top Compact Booking Header -->
    <div class="bk-header-card p-3 p-md-4 mb-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <a href="{{ route('admin.bookings.index') }}" class="text-decoration-none small fw-semibold" style="color: #64748B;">
                    &larr; Back to Booking Management
                </a>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                    <h2 class="fw-bold mb-0" style="color: #171717;">{{ $booking->reference }}</h2>
                    <span class="badge rounded-pill px-3 py-1 fw-semibold" style="{{ $statusStyle }}">
                        {{ $statusLabels[$booking->booking_status] ?? ucfirst($booking->booking_status) }}
                    </span>
                </div>
                <div class="small mt-1" style="color: #64748B;">
                    <span class="fw-semibold" style="color: #171717;">{{ $booking->customer_name }}</span>
                    &middot; {{ $booking->customer_email }}
                    &middot; Created {{ $booking->created_at?->format('d M Y, H:i') }}
                    &middot; Version {{ $booking->version }}
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @can('manage-bookings', 'admin')
                    @if(!$isTerminal)
                        <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-sm bk-btn-yellow px-3 py-2">
                            <i class="mdi mdi-pencil-outline me-1"></i> Manage / Edit Booking
                        </a>
                    @endif
                @endcan
                @can('cancel-bookings', 'admin')
                    @if(!$isTerminal)
                        <button type="button" class="btn btn-sm btn-outline-danger bk-btn-outline-danger px-3 py-2" data-bs-toggle="modal" data-bs-target="#cancelShowBookingModal">
                            <i class="mdi mdi-close-circle-outline me-1"></i> Cancel Booking
                        </button>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3 py-2 px-3 small mb-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger rounded-3 py-2 px-3 small mb-3">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rounded-3 py-2 px-3 small mb-3">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($booking->booking_status === \App\Models\Booking::STATUS_CANCELLED)
        <div class="alert alert-danger rounded-4 py-2 px-3 mb-3">
            <div class="fw-bold small">Booking Cancelled</div>
            <div class="small">Cancelled at: {{ $booking->cancelled_at?->format('d M Y H:i') }} &middot; Reason: {{ $booking->cancellation_reason }}</div>
        </div>
    @endif

    <!-- First Row: Services & Items (Full Width) -->
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="bk-card p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0" style="color: #171717;">Services &amp; Items</h5>
                    <span class="badge rounded-pill px-3 py-1 fw-semibold"
                          style="{{ $pricingSummary['is_pricing_complete'] ? 'background-color: #D1FAE5; color: #047857;' : 'background-color: #FEF3C7; color: #B45309;' }}">
                        {{ $pricingSummary['pricing_status_label'] }}
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 bk-services-table">
                        <thead>
                            <tr>
                                <th>Service / Item</th>
                                <th class="text-end" style="width: 140px;">Qty / Weight</th>
                                <th class="text-end" style="width: 170px;">Unit Price</th>
                                <th class="text-end" style="width: 160px;">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categoryGroups as $group)
                                @php
                                    $catTheme = $categoryPalette[$loop->index % count($categoryPalette)];
                                @endphp
                                <tr>
                                    <td colspan="4" class="py-2 px-3 fw-bold text-uppercase small"
                                        style="background-color: {{ $catTheme['bg'] }}; border-left: 4px solid {{ $catTheme['border'] }}; color: {{ $catTheme['text'] }};">
                                        {{ $group['category_name'] }}
                                    </td>
                                </tr>
                                @forelse($group['items'] as $line)
                                    <tr class="bk-service-row">
                                        <td class="py-2">
                                            <div class="fw-semibold" style="color: #171717;">
                                                {{ $line->service_name }}
                                                @if($line->is_price_overridden)
                                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 0.7rem;">Overridden</span>
                                                @endif
                                            </div>
                                            @if($line->item_name)
                                                <div class="small" style="color: #64748B;">Item: {{ $line->item_name }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap py-2">
                                            @if($line->pricing_mode === 'weight')
                                                @if($line->weight_kg !== null && (float) $line->weight_kg > 0)
                                                    <span class="fw-semibold">{{ number_format((float) $line->weight_kg, 2) }} kg</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-dark">Pending</span>
                                                @endif
                                            @else
                                                <span class="fw-semibold">{{ (int) $line->quantity }} {{ strtolower($line->unit_name ?: 'item') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap py-2">
                                            {{ $currencyService->formatMinor((int) $line->actual_unit_price_minor, $booking->currency) }}
                                            <span class="small" style="color: #64748B;">/ {{ $line->pricing_mode === 'weight' ? 'kg' : strtolower($line->unit_name ?: 'unit') }}</span>
                                        </td>
                                        <td class="text-end fw-bold text-nowrap py-2" style="color: #171717;">
                                            {{ $currencyService->formatMinor((int) $line->line_total_minor, $booking->currency) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="small py-2 px-3" style="color: #64748B;">No items added yet (pricing pending).</td>
                                    </tr>
                                @endforelse
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 small" style="color: #64748B;">No active operational services recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bk-subtotal-row">
                                <td colspan="3" class="text-end fw-bold py-2 px-3">Items Subtotal</td>
                                <td class="text-end fw-bold fs-6 py-2 px-3 text-nowrap">
                                    {{ $currencyService->formatMinor((int) $pricingSummary['items_subtotal_minor'], $booking->currency) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @if($booking->operational_notes)
                    <div class="mt-3 p-2 px-3 rounded-3 small" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; color: #334155;">
                        <strong>Operational Notes:</strong> {{ $booking->operational_notes }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Second Row: Three Compact Information Cards -->
    <div class="row g-3 mb-3">
        <!-- Left: Customer & Status -->
        <div class="col-md-6 col-lg-4">
            <div class="bk-card p-3 p-md-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <h6 class="fw-bold mb-3" style="color: #171717;">Customer &amp; Status</h6>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bk-avatar">{{ $initials }}</div>
                        <div class="min-w-0">
                            <div class="fw-bold text-truncate" style="color: #171717;">{{ $booking->customer_name }}</div>
                            <div class="small text-truncate" style="color: #64748B;">{{ $booking->customer_email }}</div>
                        </div>
                    </div>
                    <div class="small mb-2 d-flex justify-content-between">
                        <span style="color: #64748B;"><i class="mdi mdi-phone-outline me-1"></i>Phone</span>
                        <span class="fw-semibold" style="color: #171717;">{{ $booking->customer_phone ?: '-' }}</span>
                    </div>
                    <div class="small mb-2 d-flex justify-content-between">
                        <span style="color: #64748B;"><i class="mdi mdi-account-outline me-1"></i>Type</span>
                        <span class="fw-semibold" style="color: #171717;">{{ ucfirst($booking->customer_type ?? 'individual') }}</span>
                    </div>
                    <div class="small mb-3 d-flex justify-content-between align-items-center">
                        <span style="color: #64748B;"><i class="mdi mdi-tag-outline me-1"></i>Status</span>
                        <span class="badge rounded-pill px-2 py-1 fw-semibold" style="{{ $statusStyle }}">
                            {{ $statusLabels[$booking->booking_status] ?? ucfirst($booking->booking_status) }}
                        </span>
                    </div>
                </div>

                @can('manage-bookings', 'admin')
                    @if($booking->booking_status !== \App\Models\Booking::STATUS_CANCELLED)
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="quickStatusSelect" class="form-label small fw-semibold mb-0" style="color: #64748B;">Quick Status Update</label>
                                <span class="badge rounded-pill px-2 py-1"
                                      style="{{ $stageProgress['is_finished'] ? 'background-color: #DCFCE7; color: #16A34A;' : 'background-color: #DBEAFE; color: #1D4ED8;' }}">
                                    @if($stageProgress['is_finished'])
                                        11/11 Completed
                                    @elseif($stageProgress['current_step_number'] > 0)
                                        Step {{ $stageProgress['current_step_number'] }} of {{ $stageProgress['total_steps'] }}
                                    @else
                                        {{ $stageProgress['completed_count'] }}/{{ $stageProgress['total_steps'] }} Done
                                    @endif
                                </span>
                            </div>

                            @if(!$stageProgress['is_finished'])
                                <form method="POST" action="{{ route('admin.bookings.status.advance', $booking) }}" class="mb-2">
                                    @csrf
                                    <input type="hidden" name="expected_stage" value="{{ $booking->booking_status }}">
                                    <input type="hidden" name="version" value="{{ $booking->version }}">
                                    <button type="submit" class="btn btn-sm btn-dark w-100 fw-semibold py-2" style="border-radius: 10px;">
                                        <i class="mdi mdi-check-circle-outline me-1"></i>
                                        @if(array_key_exists($booking->booking_status, $operationalStatuses))
                                            Mark {{ $booking->getStatusLabel() }} Done
                                        @else
                                            Start Processing (Step 1 of 11)
                                        @endif
                                    </button>
                                </form>
                            @else
                                <div class="small fw-semibold px-2 py-2 rounded-3 mb-2 text-center" style="background-color: #DCFCE7; color: #16A34A;">
                                    <i class="mdi mdi-check-all me-1"></i> All 11 operational stages completed
                                </div>
                            @endif

                            <form method="POST" action="{{ route('admin.bookings.status.update', $booking) }}">
                                @csrf
                                <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 bk-status-select-wrap">
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <select id="quickStatusSelect" name="booking_status" class="form-select form-select-sm">
                                            @if(!array_key_exists($booking->booking_status, $operationalStatuses))
                                                <option value="{{ $booking->booking_status }}"
                                                    data-completed="0"
                                                    data-current="1"
                                                    selected>
                                                    {{ $booking->getStatusLabel() }}
                                                </option>
                                            @endif
                                            @foreach($operationalStatuses as $stKey => $stLabel)
                                                @php
                                                    $isDone = in_array($stKey, $completedOperationalStatuses, true);
                                                    $isCurr = !$stageProgress['is_finished'] && $booking->booking_status === $stKey;
                                                @endphp
                                                <option value="{{ $stKey }}"
                                                    data-completed="{{ $isDone ? '1' : '0' }}"
                                                    data-current="{{ $isCurr ? '1' : '0' }}"
                                                    @selected($booking->booking_status === $stKey)>
                                                    {{ $stLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if(!$stageProgress['is_finished'])
                                        <button type="submit" class="btn btn-sm btn-outline-secondary px-3 text-nowrap" style="min-height: 36px; border-radius: 10px;">Update</button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    @endif
                @endcan
            </div>
        </div>

        <!-- Middle: Collection & Delivery -->
        <div class="col-md-6 col-lg-4">
            <div class="bk-card p-3 p-md-4 h-100">
                <h6 class="fw-bold mb-3" style="color: #171717;">Collection &amp; Delivery</h6>
                @php
                    $fullAddress = $address['full_address'] ?? $address['address_line'] ?? null;
                    $postcode = $address['postcode'] ?? null;
                    $details = $address['address_details'] ?? $address['instructions'] ?? null;
                    $isHotel = ! empty($address['is_hotel']) || ! empty($address['hotel_name']);
                @endphp

                @if($fullAddress || $postcode || $isHotel)
                    <div class="small mb-2">
                        <span style="color: #64748B;"><i class="mdi mdi-map-marker-outline me-1 text-primary"></i>Address:</span>
                        <span class="fw-semibold" style="color: #171717;">{{ $fullAddress ?: 'Not provided' }}</span>
                    </div>
                    @if($postcode)
                        <div class="small mb-2">
                            <span style="color: #64748B;">Postcode:</span>
                            <span class="fw-semibold" style="color: #171717;">{{ $postcode }}</span>
                        </div>
                    @endif
                    @if($isHotel)
                        <div class="small mb-2 p-2 rounded-3" style="background-color: #F8FAFC; border: 1px solid #E2E8F0;">
                            @if(!empty($address['hotel_name']))
                                <div><strong>Hotel:</strong> {{ $address['hotel_name'] }}</div>
                            @endif
                            @if(!empty($address['room_number']))
                                <div><strong>Room:</strong> {{ $address['room_number'] }}</div>
                            @endif
                            @if(!empty($address['hotel_preference']))
                                <div><strong>Preference:</strong> {{ $address['hotel_preference'] }}</div>
                            @endif
                        </div>
                    @endif
                    @if($details)
                        <div class="small mb-3" style="color: #64748B;"><strong>Details:</strong> {{ $details }}</div>
                    @else
                        <div class="mb-3"></div>
                    @endif
                @else
                    <div class="small text-muted p-2 rounded-3 bg-light mb-3">Address details unavailable.</div>
                @endif

                <div class="bk-schedule-box-collect p-2 px-3 mb-2 small">
                    <div class="fw-semibold" style="color: #1D4ED8;">
                        <i class="mdi mdi-calendar-outline me-1"></i>Collection
                    </div>
                    <div style="color: #1E293B;">
                        {{ $schedule['collection_date'] ?? $schedule['pickup_date'] ?? 'Not scheduled' }}
                        <span class="ms-1" style="color: #475569;"><i class="mdi mdi-clock-outline me-1"></i>{{ $schedule['collection_slot'] ?? $schedule['pickup_slot'] ?? '-' }}</span>
                    </div>
                </div>

                <div class="bk-schedule-box-deliver p-2 px-3 small">
                    <div class="fw-semibold" style="color: #047857;">
                        <i class="mdi mdi-calendar-check-outline me-1"></i>Delivery
                    </div>
                    <div style="color: #1E293B;">
                        {{ $schedule['delivery_date'] ?? 'Not scheduled' }}
                        <span class="ms-1" style="color: #475569;"><i class="mdi mdi-clock-outline me-1"></i>{{ $schedule['delivery_slot'] ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Payment Summary -->
        <div class="col-12 col-lg-4">
            <div class="bk-card p-3 p-md-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <h6 class="fw-bold mb-3" style="color: #171717;">Payment Summary</h6>
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span style="color: #64748B;">Gateway</span>
                        <span class="fw-bold text-uppercase" style="color: #171717;">{{ $booking->payment_gateway }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span style="color: #64748B;">Payment Status</span>
                        <span class="badge rounded-pill px-2 py-1 fw-semibold" style="{{ $payBadgeStyle }}">
                            {{ $pricingSummary['payment_status_label'] }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span style="color: #64748B;">Pricing Status</span>
                        <span class="badge rounded-pill px-2 py-1 fw-semibold"
                              style="{{ $pricingSummary['is_pricing_complete'] ? 'background-color: #D1FAE5; color: #047857;' : 'background-color: #FEF3C7; color: #B45309;' }}">
                            {{ $pricingSummary['pricing_status_label'] }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-2" style="color: #64748B;">
                        <span>Original Booking Estimate</span>
                        <span>{{ $currencyService->formatMinor((int) $booking->estimated_amount_minor, $booking->currency) }}</span>
                    </div>

                    <hr class="my-2">

                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span style="color: #64748B;">Items Subtotal</span>
                        <span class="fw-semibold" style="color: #171717;">{{ $currencyService->formatMinor((int) $pricingSummary['items_subtotal_minor'], $booking->currency) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span style="color: #64748B;">Service Fee ({{ $booking->service_fee_override_mode }})</span>
                        <span style="color: #171717;">{{ $currencyService->formatMinor((int) $pricingSummary['service_fee_minor'], $booking->currency) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span style="color: #64748B;">Collection &amp; Delivery Fee ({{ $booking->delivery_fee_override_mode }})</span>
                        <span style="color: #171717;">{{ $currencyService->formatMinor((int) $pricingSummary['delivery_fee_minor'], $booking->currency) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span style="color: #64748B;">Tax</span>
                        <span style="color: #171717;">{{ $currencyService->formatMinor((int) $pricingSummary['tax_minor'], $booking->currency) }}</span>
                    </div>
                    @if((int) $pricingSummary['tip_minor'] > 0)
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <span style="color: #64748B;">Driver Tip</span>
                            <span style="color: #171717;">{{ $currencyService->formatMinor((int) $pricingSummary['tip_minor'], $booking->currency) }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        <span style="color: #64748B;">Discount</span>
                        <span style="color: #EF4444;">-{{ $currencyService->formatMinor((int) $pricingSummary['discount_minor'], $booking->currency) }}</span>
                    </div>
                </div>

                <div class="bk-final-total-box p-2 px-3 mt-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold" style="color: #171717;">Final Total</span>
                        @if($pricingSummary['is_pricing_complete'])
                            <span class="fs-5 fw-bold" style="color: #171717;">{{ $currencyService->formatMinor((int) $pricingSummary['final_total_minor'], $booking->currency) }}</span>
                        @else
                            <span class="fw-bold small" style="color: #B45309;">Final pricing pending</span>
                        @endif
                    </div>
                    @if(!$pricingSummary['is_pricing_complete'] && !empty($pricingSummary['pending_reasons']))
                        <div class="small text-end mt-1" style="color: #B45309;">{{ $pricingSummary['pending_reasons'][0] }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Read-Only Audit History -->
    <div class="bk-card p-3 p-md-4">
        <h6 class="fw-bold mb-3" style="color: #171717;">Read-Only Audit History</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr class="small">
                        <th>Timestamp</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Summary</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($booking->auditLogs->sortByDesc('id') as $log)
                        <tr>
                            <td class="small text-nowrap" style="color: #64748B;">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                            <td class="small">{{ $log->actor_name }} <span style="color: #64748B;">({{ $log->actor_type }})</span></td>
                            <td><span class="badge bg-light text-dark border">{{ $log->action }}</span></td>
                            <td class="small">{{ $log->summary }}</td>
                            <td class="small" style="color: #64748B;">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-3 small" style="color: #64748B;">No audit logs recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@can('cancel-bookings', 'admin')
    @if(!$isTerminal)
        <div class="modal fade" id="cancelShowBookingModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}">
                    @csrf
                    <div class="modal-content rounded-4">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Cancel Booking {{ $booking->reference }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted">The booking and payment records will be preserved. The customer will be notified by email.</p>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Cancellation Reason <span class="text-danger">*</span></label>
                                <textarea name="cancellation_reason" class="form-control" rows="3" required minlength="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endcan
@endsection

@push('scripts')
<script src="{{ asset('admin/dist/libs/select2/dist/js/select2.full.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
    const $ = jQuery;
    const $select = $('#quickStatusSelect');
    if (!$select.length || $select.data('select2')) return;

    $select.select2({
        width: '100%',
        allowClear: false,
        templateResult: function (state) {
            if (!state.id || !state.element) return state.text;
            const isCompleted = state.element.getAttribute('data-completed') === '1';
            const isCurrent = state.element.getAttribute('data-current') === '1';

            let cls = 's2-st-item ';
            if (isCurrent) {
                cls += 's2-st-current';
            } else if (isCompleted) {
                cls += 's2-st-completed';
            } else {
                cls += 's2-st-upcoming';
            }

            const $wrap = $('<div></div>').addClass(cls);
            const $left = $('<span></span>');
            if (isCompleted) {
                $left.append($('<span class="s2-st-check" aria-hidden="true">&#10003;</span>'));
            }
            $left.append(document.createTextNode(state.text));
            $wrap.append($left);

            if (isCurrent) {
                $wrap.append($('<span class="s2-st-current-pill">Current</span>'));
            }

            return $wrap;
        },
        templateSelection: function (state) {
            if (!state.id || !state.element) return state.text;
            const isCompleted = state.element.getAttribute('data-completed') === '1';
            const $sel = $('<span></span>');
            if (isCompleted) {
                $sel.append($('<span class="s2-st-check" aria-hidden="true">&#10003;</span>'));
            }
            $sel.append(document.createTextNode(state.text));
            return $sel;
        }
    });
});
</script>
@endpush
