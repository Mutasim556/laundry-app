<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Order {{ $booking->reference }} | FreshGo Laundry</title>
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/booking-address.css') }}">
    <style>
        .order-detail-card {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        }
        .order-cat-header {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 7px 12px;
            border-radius: 8px;
        }
        .order-final-box {
            background: linear-gradient(135deg, #FFF8E5 0%, #FEF3C7 100%);
            border: 1px solid #FFC629;
            border-radius: 12px;
            padding: 12px 14px;
        }
        .order-pending-box {
            background-color: #FFF8E5;
            border: 1px solid #FCD34D;
            border-radius: 12px;
            padding: 10px 14px;
        }
    </style>
</head>
<body class="booking-page-body">
    <div class="d-lg-none px-3 pt-3 bg-white border-bottom">
        @include('shared.header-mobile')
    </div>
    <div class="d-none d-lg-block">
        @include('shared.header-desktop')
    </div>

    @php
        $address = $booking->address_snapshot ?? [];
        $schedule = $booking->schedule_snapshot ?? [];
        $origServices = $booking->services_snapshot ?? [];
        $paymentSnap = $booking->payment_snapshot ?? [];

        $bookingStatusLabel = $booking->getStatusLabel();

        $statusStyle = \App\Models\Booking::statusBadgeStyle($booking->booking_status);

        $payBadgeStyle = match($pricingSummary['payment_status_label']) {
            'Paid' => 'background-color: #D1FAE5; color: #047857;',
            'Partially Paid' => 'background-color: #DBEAFE; color: #1D4ED8;',
            'Authorized' => 'background-color: #DBEAFE; color: #1D4ED8;',
            'Failed' => 'background-color: #FEE2E2; color: #B91C1C;',
            'Refunded', 'Partially Refunded' => 'background-color: #EDE9FE; color: #6D28D9;',
            default => 'background-color: #FEF3C7; color: #B45309;',
        };

        $categoryPalette = [
            ['bg' => '#FFF8E5', 'border' => '#FFC629', 'text' => '#92400E'],
            ['bg' => '#EFF6FF', 'border' => '#3B82F6', 'text' => '#1D4ED8'],
            ['bg' => '#F5F3FF', 'border' => '#8B5CF6', 'text' => '#5B21B6'],
            ['bg' => '#ECFDF5', 'border' => '#10B981', 'text' => '#065F46'],
        ];
    @endphp

    <main class="container booking-main-container py-4">
        <div class="booking-confirm-shell mx-auto">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <a href="{{ route('orders.index') }}" class="booking-back-link">&lsaquo; Back to My Orders</a>
                <span class="badge fw-bold rounded-pill px-3 py-2" style="background-color: #FFC629; color: #171717;">
                    &#128274; Confirmed &amp; Locked (Read-Only)
                </span>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
                <div>
                    <h1 class="booking-screen-title mb-1">Order {{ $booking->reference }}</h1>
                    <p class="booking-screen-subtitle mb-0">
                        Confirmed on {{ $booking->confirmed_at?->format('d M Y, H:i') }}
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span id="orderTopStatusBadge" class="badge rounded-pill px-3 py-2 fw-semibold" style="{{ $statusStyle }}">
                        {{ $bookingStatusLabel }}
                    </span>
                    <span id="orderTopPayBadge" class="badge rounded-pill px-3 py-2 fw-semibold" style="{{ $payBadgeStyle }}">
                        Payment: {{ $pricingSummary['payment_status_label'] }}
                    </span>
                    <span class="badge rounded-pill px-3 py-2 fw-semibold"
                          style="{{ $pricingSummary['is_pricing_complete'] ? 'background-color: #D1FAE5; color: #047857;' : 'background-color: #FEF3C7; color: #B45309;' }}">
                        {{ $pricingSummary['is_pricing_complete'] ? 'Finalized' : 'Pending Pricing' }}
                    </span>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success rounded-4 border-0 shadow-sm mb-3 py-2 px-3 small fw-semibold" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-3 py-2 px-3 small fw-semibold" role="alert">
                    {{ session('error') }}
                </div>
            @endif
            @if (session('info'))
                <div class="alert alert-info rounded-4 border-0 shadow-sm mb-3 py-2 px-3 small fw-semibold" role="alert">
                    {{ session('info') }}
                </div>
            @endif

            <div class="row g-3 align-items-start">
                <div class="col-12">
                    <div class="order-detail-card p-3 p-md-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h2 class="h6 fw-bold mb-0" style="color: #171717;">Order Progress</h2>
                            @if($stageProgress['is_cancelled'])
                                <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background-color: #FEE2E2; color: #B91C1C;">Cancelled</span>
                            @elseif($stageProgress['is_finished'])
                                <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background-color: #DCFCE7; color: #16A34A;">11/11 Completed</span>
                            @elseif($stageProgress['current_step_number'] > 0)
                                <span class="badge rounded-pill px-3 py-1 fw-semibold" style="background-color: #FFF8E5; color: #92400E; border: 1px solid #FFC629;">
                                    Step {{ $stageProgress['current_step_number'] }} of {{ $stageProgress['total_steps'] }}
                                </span>
                            @endif
                        </div>
                        @if($stageProgress['is_cancelled'])
                            <div class="small text-danger fw-semibold">
                                This order was cancelled{{ $booking->cancellation_reason ? ': ' . $booking->cancellation_reason : '.' }}
                            </div>
                        @else
                            <div class="row g-2">
                                @foreach($stageProgress['stages'] as $st)
                                    @php
                                        $boxStyle = match($st['state']) {
                                            'completed' => 'background-color: #DCFCE7; border: 1px solid #86EFAC; color: #166534;',
                                            'current' => 'background-color: #FFF8E5; border: 1px solid #FFC629; color: #171717; font-weight: 700;',
                                            default => 'background-color: #F8FAFC; border: 1px solid #E2E8F0; color: #777777;',
                                        };
                                    @endphp
                                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                        <div class="px-2 py-2 rounded-3 small d-flex align-items-center justify-content-between gap-2" style="{{ $boxStyle }}">
                                            <span class="text-truncate">
                                                @if($st['is_completed'])
                                                    <span style="color: #16A34A; font-weight: 700;">&#10003;</span>
                                                @else
                                                    <span class="me-1">{{ $st['step'] }}.</span>
                                                @endif
                                                {{ $st['label'] }}
                                            </span>
                                            @if($st['is_current'])
                                                <span class="badge rounded-pill" style="background-color: #FFC629; color: #171717; font-size: 0.65rem;">Current</span>
                                            @elseif($st['is_completed'])
                                                <span style="color: #16A34A; font-size: 0.7rem; font-weight: 600;">Done</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row g-3 align-items-start mt-1">
                <!-- Left Column: Services & Items + Price Breakdown -->
                <div class="col-12 col-lg-7">
                    <div class="order-detail-card p-3 p-md-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h2 class="h6 fw-bold mb-0" style="color: #171717;">Services &amp; Items</h2>
                            <span class="badge rounded-pill px-2 py-1 fw-semibold small"
                                  style="{{ $pricingSummary['is_pricing_complete'] ? 'background-color: #D1FAE5; color: #047857;' : 'background-color: #FEF3C7; color: #B45309;' }}">
                                {{ $pricingSummary['is_pricing_complete'] ? 'Finalized' : 'Pending Pricing' }}
                            </span>
                        </div>

                        @if(!$pricingSummary['is_pricing_complete'])
                            <div class="order-pending-box mb-3 small">
                                <div class="fw-bold" style="color: #92400E;">Final pricing pending</div>
                                @if(!empty($pricingSummary['pending_reasons']))
                                    <div style="color: #78350F;">{{ $pricingSummary['pending_reasons'][0] }}</div>
                                @endif
                            </div>
                        @endif

                        @forelse($categoryGroups as $group)
                            @php
                                $catTheme = $categoryPalette[$loop->index % count($categoryPalette)];
                            @endphp
                            <div class="mb-3">
                                <div class="order-cat-header mb-2"
                                     style="background-color: {{ $catTheme['bg'] }}; border-left: 4px solid {{ $catTheme['border'] }}; color: {{ $catTheme['text'] }};">
                                    {{ $group['category_name'] }}
                                </div>

                                @if($group['items']->isEmpty())
                                    <div class="small px-2 py-1" style="color: #777777;">
                                        Awaiting operational item/weight confirmation.
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <tbody>
                                                @foreach($group['items'] as $line)
                                                    <tr>
                                                        <td class="ps-1">
                                                            <div class="fw-semibold small" style="color: #171717;">{{ $line->service_name }}</div>
                                                            @if($line->item_name)
                                                                <div class="small" style="color: #777777;">Item: {{ $line->item_name }}</div>
                                                            @endif
                                                        </td>
                                                        <td class="small text-end text-nowrap" style="color: #555555;">
                                                            @if($line->pricing_mode === 'weight')
                                                                @if($line->weight_kg !== null && (float) $line->weight_kg > 0)
                                                                    {{ number_format((float) $line->weight_kg, 2) }} kg &times; {{ $currencyService->formatMinor((int) $line->actual_unit_price_minor, $booking->currency) }}/kg
                                                                @else
                                                                    <span class="text-warning fw-semibold">Weight pending</span>
                                                                @endif
                                                            @else
                                                                {{ (int) $line->quantity }} {{ strtolower($line->unit_name ?: 'unit') }} &times; {{ $currencyService->formatMinor((int) $line->actual_unit_price_minor, $booking->currency) }}
                                                            @endif
                                                        </td>
                                                        <td class="text-end fw-bold small text-nowrap pe-1" style="color: #171717;">
                                                            {{ $currencyService->formatMinor((int) $line->line_total_minor, $booking->currency) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <ul class="list-group list-group-flush mb-3">
                                @foreach ($origServices as $svc)
                                    <li class="list-group-item px-0 d-flex justify-content-between small">
                                        <span class="fw-semibold">{{ $svc['name'] ?? 'Service' }}</span>
                                        <span class="text-secondary">{{ $svc['pricing_type_label'] ?? 'Item-Based' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endforelse

                        <div class="border-top pt-3 mt-2 small">
                            <div class="d-flex justify-content-between mb-1">
                                <span style="color: #777777;">Items Subtotal</span>
                                <span class="fw-semibold" style="color: #171717;">
                                    {{ $currencyService->formatMinor((int) $pricingSummary['items_subtotal_minor'], $booking->currency) }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span style="color: #777777;">Service Fee</span>
                                <span class="fw-semibold" style="color: #171717;">
                                    {{ $currencyService->formatMinor((int) $pricingSummary['service_fee_minor'], $booking->currency) }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span style="color: #777777;">Collection &amp; Delivery Fee</span>
                                <span class="fw-semibold" style="color: #171717;">
                                    {{ (int) $pricingSummary['delivery_fee_minor'] <= 0 ? 'FREE' : $currencyService->formatMinor((int) $pricingSummary['delivery_fee_minor'], $booking->currency) }}
                                </span>
                            </div>
                            @if((int) $pricingSummary['tax_minor'] > 0)
                                <div class="d-flex justify-content-between mb-1">
                                    <span style="color: #777777;">Tax</span>
                                    <span class="fw-semibold" style="color: #171717;">
                                        {{ $currencyService->formatMinor((int) $pricingSummary['tax_minor'], $booking->currency) }}
                                    </span>
                                </div>
                            @endif
                            @if((int) $pricingSummary['tip_minor'] > 0)
                                <div class="d-flex justify-content-between mb-1">
                                    <span style="color: #777777;">Driver Tip</span>
                                    <span class="fw-semibold" style="color: #171717;">
                                        {{ $currencyService->formatMinor((int) $pricingSummary['tip_minor'], $booking->currency) }}
                                    </span>
                                </div>
                            @endif
                            @if((int) $pricingSummary['discount_minor'] > 0)
                                <div class="d-flex justify-content-between mb-2">
                                    <span style="color: #777777;">Discount</span>
                                    <span class="fw-semibold text-danger">
                                        -{{ $currencyService->formatMinor((int) $pricingSummary['discount_minor'], $booking->currency) }}
                                    </span>
                                </div>
                            @endif

                            <div class="order-final-box mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold" style="color: #171717;">Final Total</span>
                                    @if($pricingSummary['is_pricing_complete'])
                                        <span class="fs-6 fw-bold" style="color: #171717;">
                                            {{ $currencyService->formatMinor((int) $pricingSummary['final_total_minor'], $booking->currency) }}
                                        </span>
                                    @else
                                        <span class="fw-bold" style="color: #B45309;">Final pricing pending</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Collection, Delivery & Contact -->
                <div class="col-12 col-lg-5">
                    <div class="order-detail-card p-3 p-md-4 small">
                        <h2 class="h6 fw-bold mb-3" style="color: #171717;">Collection, Delivery &amp; Contact</h2>

                        <div class="mb-3 p-2 px-3 rounded-3" style="background-color: #EFF6FF; border: 1px solid #BFDBFE;">
                            <div class="fw-semibold" style="color: #1D4ED8;">Collection</div>
                            <div class="fw-bold" style="color: #171717;">
                                {{ $schedule['collection_date'] ?? $schedule['pickup_date'] ?? '-' }} &bull; {{ $schedule['collection_slot'] ?? $schedule['pickup_slot'] ?? '-' }}
                            </div>
                            @if (!empty($schedule['collection_instruction']))
                                <div style="color: #475569;">{{ $schedule['collection_instruction'] }}</div>
                            @endif
                        </div>

                        <div class="mb-3 p-2 px-3 rounded-3" style="background-color: #ECFDF5; border: 1px solid #A7F3D0;">
                            <div class="fw-semibold" style="color: #047857;">Delivery</div>
                            <div class="fw-bold" style="color: #171717;">
                                {{ $schedule['delivery_date'] ?? '-' }} &bull; {{ $schedule['delivery_slot'] ?? '-' }}
                            </div>
                            @if (!empty($schedule['delivery_instruction']))
                                <div style="color: #475569;">{{ $schedule['delivery_instruction'] }}</div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <div class="fw-semibold mb-1" style="color: #777777;">Assigned Laundry Store</div>
                            @if ($booking->currentStoreAssignment)
                                <div class="p-2 px-3 rounded-3" style="background-color: #FFF8E5; border: 1px solid #FDE68A;">
                                    <div class="fw-bold" style="color: #171717;">{{ $booking->currentStoreAssignment->store_name_snapshot }}</div>
                                    <div style="color: #475569;">{{ $booking->currentStoreAssignment->store_address_snapshot }}</div>
                                    @if ($booking->currentStoreAssignment->store_city_snapshot)
                                        <div style="color: #475569;">{{ $booking->currentStoreAssignment->store_city_snapshot }}</div>
                                    @endif
                                    @if ($booking->currentStoreAssignment->store_postcode_snapshot)
                                        <div class="fw-semibold mt-1" style="color: #171717;">{{ $booking->currentStoreAssignment->store_postcode_snapshot }}</div>
                                    @endif
                                    @if ($booking->currentStoreAssignment->store_phone_snapshot)
                                        <div class="mt-1" style="color: #475569;">Phone: {{ $booking->currentStoreAssignment->store_phone_snapshot }}</div>
                                    @endif
                                </div>
                            @else
                                <div class="p-2 px-3 rounded-3 bg-light border text-muted">
                                    Store assignment pending
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <div class="fw-semibold mb-1" style="color: #777777;">Collection Delivery Person</div>
                            @if ($booking->currentCollectionAssignment && $booking->currentCollectionAssignment->deliveryPerson)
                                @php $collectionPerson = $booking->currentCollectionAssignment->deliveryPerson; @endphp
                                <div class="p-2 px-3 rounded-3" style="background-color: #F8FAFC; border: 1px solid #E2E8F0;">
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <span class="fw-bold" style="color: #171717;">{{ $collectionPerson->full_name }}</span>
                                        <span class="badge rounded-pill px-2 py-1" style="background-color: #DBEAFE; color: #1D4ED8;">
                                            {{ $booking->currentCollectionAssignment->status_label }}
                                        </span>
                                    </div>
                                    <div class="mt-1" style="color: #475569;">
                                        Phone:
                                        @if ($collectionPerson->phone_display !== '')
                                            <a href="tel:{{ preg_replace('/\s+/', '', $collectionPerson->phone_display) }}" class="text-decoration-none fw-semibold" style="color: #171717;">{{ $collectionPerson->phone_display }}</a>
                                        @else
                                            <span>Not provided</span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="p-2 px-3 rounded-3 bg-light border text-muted">
                                    Collection delivery person not assigned yet.
                                </div>
                            @endif
                        </div>

                        @if ($booking->currentFinalDeliveryAssignment && $booking->currentFinalDeliveryAssignment->deliveryPerson)
                            @php $finalPerson = $booking->currentFinalDeliveryAssignment->deliveryPerson; @endphp
                            <div class="mb-3">
                                <div class="fw-semibold mb-1" style="color: #777777;">Final Delivery Person</div>
                                <div class="p-2 px-3 rounded-3" style="background-color: #F8FAFC; border: 1px solid #E2E8F0;">
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <span class="fw-bold" style="color: #171717;">{{ $finalPerson->full_name }}</span>
                                        <span class="badge rounded-pill px-2 py-1" style="background-color: #D1FAE5; color: #047857;">
                                            {{ $booking->currentFinalDeliveryAssignment->status_label }}
                                        </span>
                                    </div>
                                    <div class="mt-1" style="color: #475569;">
                                        Phone:
                                        @if ($finalPerson->phone_display !== '')
                                            <a href="tel:{{ preg_replace('/\s+/', '', $finalPerson->phone_display) }}" class="text-decoration-none fw-semibold" style="color: #171717;">{{ $finalPerson->phone_display }}</a>
                                        @else
                                            <span>Not provided</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <div class="fw-semibold mb-1" style="color: #777777;">Address</div>
                            @if (!empty($address['hotel_name']))
                                <div class="fw-bold" style="color: #171717;">{{ $address['hotel_name'] }}</div>
                            @endif
                            <div style="color: #171717;">{{ $address['selected_address'] ?? $address['full_address'] ?? $address['address_line'] ?? '-' }}</div>
                            @if (!empty($address['postcode']))
                                <div style="color: #777777;">Postcode: {{ $address['postcode'] }}</div>
                            @endif
                            @if (!empty($address['room_number']))
                                <div style="color: #777777;">Room: {{ $address['room_number'] }}</div>
                            @endif
                            @if (!empty($address['hotel_preference']))
                                <div style="color: #777777;">{{ $address['hotel_preference'] }}</div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <div class="fw-semibold mb-1" style="color: #777777;">Contact</div>
                            <div class="fw-bold" style="color: #171717;">{{ $booking->customer_name }} ({{ ucfirst($booking->customer_type ?? 'individual') }})</div>
                            <div style="color: #555555;">{{ $booking->customer_phone }} &bull; {{ $booking->customer_email }}</div>
                        </div>

                        <div class="border-top pt-3" id="custPaymentCard">
                            @php
                                $custGateway = strtolower((string) ($booking->payment_gateway ?? 'cod'));
                                $custGatewayLabel = match ($custGateway) {
                                    'stripe' => 'Stripe',
                                    'paypal' => 'PayPal',
                                    'cod' => 'Cash on Delivery',
                                    default => $paymentSnap['payment_method_name'] ?? strtoupper($custGateway),
                                };
                                $isOnlineGateway = in_array($custGateway, ['stripe', 'paypal'], true);
                                $isCancelledOrder = $booking->cancelled_at !== null || $booking->booking_status === \App\Models\Booking::STATUS_CANCELLED;
                                $isOtpVerifiedOrder = $booking->collection_otp_verified_at !== null;
                                $custPaymentState = $paymentState ?? ['confirmed_paid_minor' => 0, 'outstanding_minor' => 0, 'is_payment_satisfied' => false, 'is_pricing_complete' => $pricingSummary['is_pricing_complete'], 'currency' => $booking->currency];
                            @endphp
                            <div class="fw-semibold mb-2" style="color: #777777;">Payment Information</div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span style="color: #777777;">Payment Method</span>
                                <span class="fw-bold" style="color: #171717;">{{ $custGatewayLabel }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span style="color: #777777;">Payment Status</span>
                                <span id="custPaymentStatusBadge" class="badge rounded-pill px-2 py-1 fw-semibold" style="{{ $payBadgeStyle }}">{{ $pricingSummary['payment_status_label'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span style="color: #777777;">Confirmed Paid</span>
                                <span id="custConfirmedPaid" class="fw-semibold text-success">{{ $currencyService->formatMinor((int) ($custPaymentState['confirmed_paid_minor'] ?? 0), $booking->currency) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span style="color: #777777;">Outstanding</span>
                                <span id="custOutstandingAmount" class="fw-bold" style="color: #171717;">{{ $currencyService->formatMinor((int) ($custPaymentState['outstanding_minor'] ?? 0), $booking->currency) }}</span>
                            </div>
                            @if($booking->stripe_payment_intent_id || $booking->paypal_order_id)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span style="color: #777777;">Reference</span>
                                    <span class="small text-muted">{{ $booking->stripe_payment_intent_id ?: $booking->paypal_order_id }}</span>
                                </div>
                            @endif

                            <div id="custPaymentActionArea" class="mt-3 pt-2 border-top">
                                @if($custPaymentState['is_payment_satisfied'])
                                    <div class="p-2 px-3 rounded-3 small fw-bold text-center" style="background-color: #D1FAE5; border: 1px solid #86EFAC; color: #047857;">
                                        Payment Completed &#10003;
                                    </div>
                                @elseif($isOnlineGateway && !$isCancelledOrder)
                                    @if(!$isOtpVerifiedOrder)
                                        <div class="p-2 px-3 rounded-3 small fw-semibold" style="background-color: #FFF8E5; border: 1px solid #FDE68A; color: #92400E;">
                                            Online payment will be available after OTP verification.
                                        </div>
                                    @elseif(!$custPaymentState['is_pricing_complete'])
                                        <div class="p-2 px-3 rounded-3 small fw-semibold" style="background-color: #FFF8E5; border: 1px solid #FDE68A; color: #92400E;">
                                            Awaiting Final Amount.
                                        </div>
                                    @elseif((int) ($custPaymentState['outstanding_minor'] ?? 0) > 0)
                                        @php
                                            $formattedOutstanding = $currencyService->formatMinor((int) ($custPaymentState['outstanding_minor'] ?? 0), $booking->currency);
                                        @endphp
                                        @if($custGateway === 'stripe')
                                            <button type="button"
                                                    id="btnOpenStripePayment"
                                                    class="btn btn-warning w-100 fw-bold text-dark rounded-3 py-2"
                                                    data-intent-url="{{ route('orders.stripe.payment-intent', $booking->reference) }}"
                                                    data-status-url="{{ route('orders.payment-status', $booking->reference) }}"
                                                    data-return-url="{{ route('orders.show', $booking->reference) }}"
                                                    data-amount-label="{{ $formattedOutstanding }}">
                                                Pay with Stripe
                                            </button>
                                        @elseif($custGateway === 'paypal')
                                            <form method="POST" action="{{ route('orders.pay.paypal', $booking->reference) }}" class="js-cust-pay-form">
                                                @csrf
                                                <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3 py-2">
                                                    Pay Securely with PayPal
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')

    @if(strtolower((string) ($booking->payment_gateway ?? '')) === 'stripe')
        @php
            $modalOutstandingLabel = $currencyService->formatMinor((int) ($paymentState['outstanding_minor'] ?? 0), $booking->currency);
        @endphp
        <!-- Centered Stripe Payment Popup Modal -->
        <div class="modal fade" id="stripePaymentModal" tabindex="-1" aria-labelledby="stripePaymentModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 500px;">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; background: #FFFFFF;">
                    <div class="modal-header border-bottom-0 pb-2 px-4 pt-4">
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-3"
                                  style="width: 38px; height: 38px; background: #FFF8E5; border: 1.5px solid #FFC629; color: #171717;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                            </span>
                            <div>
                                <h5 class="modal-title fw-bold mb-0 fs-6" id="stripePaymentModalLabel" style="color: #171717;">Secure Stripe Payment</h5>
                                <div class="small" style="color: #64748B;">Complete your booking payment securely.</div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" id="btnCustStripeCloseX" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body px-4 pt-2 pb-3">
                        <!-- Form State -->
                        <div id="custStripeFormView">
                            <div class="p-3 rounded-3 mb-3 small" style="background-color: #FFF8E5; border: 1.5px solid #FFC629;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span style="color: #64748B;">Booking:</span>
                                    <strong style="color: #171717;">{{ $booking->reference }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span style="color: #64748B;">Payment Method:</span>
                                    <strong style="color: #171717;">Stripe</strong>
                                </div>
                                <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                    <span style="color: #64748B;">Amount to Pay:</span>
                                    <strong class="fs-6" style="color: #171717;">{{ $modalOutstandingLabel }}</strong>
                                </div>
                            </div>

                            <div id="custStripeLoading" class="d-flex align-items-center gap-2 small text-muted py-3 justify-content-center">
                                <span class="spinner-border spinner-border-sm text-warning" role="status" aria-hidden="true"></span>
                                <span>Loading secure card form...</span>
                            </div>
                            <div id="custStripePaymentElement" class="mb-2"></div>
                            <div id="custStripeError" class="alert alert-danger py-2 px-3 small fw-semibold mt-2 mb-0 d-none" role="alert"></div>
                            <div id="custStripeProcessingMsg" class="alert alert-info py-2 px-3 small fw-semibold mt-2 mb-0 d-none"></div>
                        </div>

                        <!-- Success State Inside Modal -->
                        <div id="custStripeSuccessView" class="text-center py-3 d-none">
                            <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle"
                                 style="width: 60px; height: 60px; background: #DCFCE7; color: #16A34A;">
                                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                            <h5 class="fw-bold mb-1" style="color: #171717;">Payment Successful</h5>
                            <p class="small mb-3" style="color: #64748B;">Your payment has been received.</p>

                            <div class="p-3 rounded-3 text-start small mb-3" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span style="color: #64748B;">Booking Reference:</span>
                                    <strong style="color: #171717;">{{ $booking->reference }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span style="color: #64748B;">Amount Paid:</span>
                                    <strong id="custStripeSuccessPaid" style="color: #16A34A;">{{ $modalOutstandingLabel }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span style="color: #64748B;">Payment Status:</span>
                                    <span class="badge rounded-pill px-2 py-1" style="background: #D1FAE5; color: #047857;">Paid</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span style="color: #64748B;">Booking Status:</span>
                                    <strong id="custStripeSuccessBookingStatus" style="color: #171717;">Finalized</strong>
                                </div>
                            </div>

                            <button type="button" id="btnCustStripeDone" class="btn btn-warning w-100 fw-bold text-dark rounded-3 py-2">
                                Done
                            </button>
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 px-4 pb-4 pt-1" id="custStripeModalFooter">
                        <button type="button" id="btnCustStripeCancel" class="btn btn-outline-secondary rounded-3 fw-semibold px-3" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="button" id="btnCustStripePayNow" class="btn btn-warning fw-bold text-dark rounded-3 px-4 flex-grow-1" disabled>
                            <span id="btnCustStripePayNowText">Pay {{ $modalOutstandingLabel }}</span>
                            <span id="btnCustStripePayNowSpinner" class="spinner-border spinner-border-sm ms-1 d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @include('orders.partials.need-help-claims')

    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    @if(strtolower((string) ($booking->payment_gateway ?? '')) === 'stripe')
        <script src="https://js.stripe.com/v3/"></script>
    @endif
    <script>
    (() => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        document.querySelectorAll('.js-cust-pay-form').forEach((form) => {
            form.addEventListener('submit', () => {
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'Redirecting to secure payment...';
                }
            });
        });

        const applyVerifiedPaymentUi = (data) => {
            const topPay = document.querySelector('#orderTopPayBadge');
            const cardPay = document.querySelector('#custPaymentStatusBadge');
            const paidEl = document.querySelector('#custConfirmedPaid');
            const outEl = document.querySelector('#custOutstandingAmount');
            const topStatus = document.querySelector('#orderTopStatusBadge');
            const actionArea = document.querySelector('#custPaymentActionArea');

            if (topPay) {
                topPay.textContent = 'Payment: ' + (data.payment_status_label || 'Paid');
                topPay.style.backgroundColor = '#D1FAE5';
                topPay.style.color = '#047857';
            }
            if (cardPay) {
                cardPay.textContent = data.payment_status_label || 'Paid';
                cardPay.style.backgroundColor = '#D1FAE5';
                cardPay.style.color = '#047857';
            }
            if (paidEl && data.formatted_paid) paidEl.textContent = data.formatted_paid;
            if (outEl && data.formatted_outstanding) outEl.textContent = data.formatted_outstanding;
            if (topStatus && data.booking_status_label) topStatus.textContent = data.booking_status_label;
            if (actionArea) {
                actionArea.innerHTML = '<div class="p-2 px-3 rounded-3 small fw-bold text-center" style="background-color: #D1FAE5; border: 1px solid #86EFAC; color: #047857;">Payment Completed &#10003;</div>';
            }
        };

        const btnOpenStripe = document.querySelector('#btnOpenStripePayment');
        const stripeModalEl = document.querySelector('#stripePaymentModal');
        const stripeFormView = document.querySelector('#custStripeFormView');
        const stripeSuccessView = document.querySelector('#custStripeSuccessView');
        const stripeModalFooter = document.querySelector('#custStripeModalFooter');
        const stripeLoading = document.querySelector('#custStripeLoading');
        const stripeError = document.querySelector('#custStripeError');
        const stripeProcessingMsg = document.querySelector('#custStripeProcessingMsg');
        const btnPayNow = document.querySelector('#btnCustStripePayNow');
        const btnPayNowText = document.querySelector('#btnCustStripePayNowText');
        const btnPayNowSpinner = document.querySelector('#btnCustStripePayNowSpinner');
        const btnCancel = document.querySelector('#btnCustStripeCancel');
        const btnCloseX = document.querySelector('#btnCustStripeCloseX');
        const btnDone = document.querySelector('#btnCustStripeDone');
        const successPaidEl = document.querySelector('#custStripeSuccessPaid');
        const successStatusEl = document.querySelector('#custStripeSuccessBookingStatus');

        let stripeModalInstance = null;
        let stripeInstance = null;
        let stripeElements = null;
        let isInitializingIntent = false;
        let isConfirmingPayment = false;
        let statusPollTimer = null;
        let verifiedPaymentData = null;

        const showModalSuccess = (data) => {
            verifiedPaymentData = data;
            if (successPaidEl && data.formatted_paid) successPaidEl.textContent = data.formatted_paid;
            if (successStatusEl) {
                successStatusEl.textContent = data.is_finalized ? 'Finalized' : (data.booking_status_label || 'Updated');
            }
            stripeFormView?.classList.add('d-none');
            stripeModalFooter?.classList.add('d-none');
            stripeSuccessView?.classList.remove('d-none');
            if (btnCloseX) btnCloseX.disabled = false;
        };

        const setConfirmingState = (busy) => {
            isConfirmingPayment = busy;
            if (btnPayNow) btnPayNow.disabled = busy;
            if (btnCancel) btnCancel.disabled = busy;
            if (btnCloseX) btnCloseX.disabled = busy;
            if (btnPayNowText) {
                btnPayNowText.textContent = busy
                    ? 'Processing payment...'
                    : ('Pay ' + (btnOpenStripe?.dataset.amountLabel || ''));
            }
            btnPayNowSpinner?.classList.toggle('d-none', !busy);
        };

        const pollUntilVerified = (statusUrl) => {
            if (statusPollTimer) clearInterval(statusPollTimer);
            let attempts = 0;
            statusPollTimer = setInterval(async () => {
                attempts++;
                try {
                    const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (data.is_payment_satisfied) {
                        clearInterval(statusPollTimer);
                        statusPollTimer = null;
                        setConfirmingState(false);
                        showModalSuccess(data);
                        return;
                    }
                    if (data.payment_status === 'failed' || attempts >= 15) {
                        clearInterval(statusPollTimer);
                        statusPollTimer = null;
                        setConfirmingState(false);
                        stripeProcessingMsg?.classList.add('d-none');
                        if (stripeError) {
                            stripeError.textContent = data.payment_status === 'failed'
                                ? 'Payment failed. Please try again.'
                                : 'Payment verification is taking longer than expected. Please refresh shortly.';
                            stripeError.classList.remove('d-none');
                        }
                    }
                } catch {}
            }, 2000);
        };

        const initStripeModalElement = async () => {
            if (stripeElements || isInitializingIntent || !btnOpenStripe) return;
            isInitializingIntent = true;
            stripeLoading?.classList.remove('d-none');
            stripeError?.classList.add('d-none');
            if (btnPayNow) btnPayNow.disabled = true;

            try {
                const res = await fetch(btnOpenStripe.dataset.intentUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({}),
                });
                const data = await res.json();
                if (!res.ok || !data.success || !data.client_secret || !data.publishable_key) {
                    throw new Error(data.message || 'Unable to initialize Stripe payment.');
                }
                if (!window.Stripe) {
                    throw new Error('Stripe.js failed to load. Please refresh and try again.');
                }

                stripeInstance = window.Stripe(data.publishable_key);
                stripeElements = stripeInstance.elements({
                    clientSecret: data.client_secret,
                    appearance: {
                        theme: 'stripe',
                        variables: {
                            colorPrimary: '#FFC629',
                            colorBackground: '#FFFFFF',
                            colorText: '#171717',
                            borderRadius: '10px',
                        },
                    },
                });
                const paymentEl = stripeElements.create('payment');
                paymentEl.mount('#custStripePaymentElement');
                stripeLoading?.classList.add('d-none');
                if (btnPayNow) btnPayNow.disabled = false;
            } catch (err) {
                stripeLoading?.classList.add('d-none');
                if (stripeError) {
                    stripeError.textContent = err.message || 'Unable to load card form.';
                    stripeError.classList.remove('d-none');
                }
            } finally {
                isInitializingIntent = false;
            }
        };

        btnOpenStripe?.addEventListener('click', () => {
            if (!stripeModalEl || !window.bootstrap) return;
            stripeModalInstance = window.bootstrap.Modal.getOrCreateInstance(stripeModalEl);
            stripeError?.classList.add('d-none');
            stripeProcessingMsg?.classList.add('d-none');
            stripeModalInstance.show();
            initStripeModalElement();
        });

        btnDone?.addEventListener('click', () => {
            if (verifiedPaymentData) {
                applyVerifiedPaymentUi(verifiedPaymentData);
            }
            stripeModalInstance?.hide();
        });

        stripeModalEl?.addEventListener('hidden.bs.modal', () => {
            if (verifiedPaymentData) {
                applyVerifiedPaymentUi(verifiedPaymentData);
            }
        });

        btnPayNow?.addEventListener('click', async () => {
            if (isConfirmingPayment || !stripeInstance || !stripeElements) return;

            setConfirmingState(true);
            stripeError?.classList.add('d-none');
            stripeProcessingMsg?.classList.add('d-none');

            try {
                const { error, paymentIntent } = await stripeInstance.confirmPayment({
                    elements: stripeElements,
                    confirmParams: {
                        return_url: btnOpenStripe?.dataset.returnUrl || window.location.href,
                    },
                    redirect: 'if_required',
                });

                if (error) {
                    throw new Error(error.message || 'Card payment failed. Please check your details.');
                }

                if (paymentIntent && (paymentIntent.status === 'succeeded' || paymentIntent.status === 'processing')) {
                    if (stripeProcessingMsg) {
                        stripeProcessingMsg.textContent = 'Payment is processing. Verifying with Stripe...';
                        stripeProcessingMsg.classList.remove('d-none');
                    }
                    const statusUrl = btnOpenStripe?.dataset.statusUrl || '';
                    if (statusUrl) {
                        const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                        if (res.ok) {
                            const statusData = await res.json();
                            if (statusData.is_payment_satisfied) {
                                setConfirmingState(false);
                                showModalSuccess(statusData);
                                return;
                            }
                        }
                        pollUntilVerified(statusUrl);
                    }
                } else {
                    setConfirmingState(false);
                }
            } catch (err) {
                setConfirmingState(false);
                if (stripeError) {
                    stripeError.textContent = err.message || 'Payment failed. Please try again.';
                    stripeError.classList.remove('d-none');
                }
            }
        });

        @if(in_array(strtolower((string) ($booking->payment_gateway ?? '')), ['stripe', 'paypal'], true) && $booking->cancelled_at === null && empty($paymentState['is_payment_satisfied']))
        const initialOtpVerified = {{ $booking->collection_otp_verified_at !== null ? 'true' : 'false' }};
        const initialPricingComplete = {{ !empty($paymentState['is_pricing_complete']) ? 'true' : 'false' }};
        const statusUrl = "{{ route('orders.payment-status', $booking->reference) }}";
        setInterval(async () => {
            if (isConfirmingPayment || verifiedPaymentData) return;
            try {
                const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                if (data.is_payment_satisfied) {
                    applyVerifiedPaymentUi(data);
                } else if (data.otp_verified !== initialOtpVerified || data.is_pricing_complete !== initialPricingComplete) {
                    window.location.reload();
                }
            } catch {}
        }, 5000);
        @endif
    })();
    </script>
</body>
</html>
