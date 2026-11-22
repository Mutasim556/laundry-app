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
                    <span class="badge rounded-pill px-3 py-2 fw-semibold" style="{{ $statusStyle }}">
                        {{ $bookingStatusLabel }}
                    </span>
                    <span class="badge rounded-pill px-3 py-2 fw-semibold" style="{{ $payBadgeStyle }}">
                        Payment: {{ $pricingSummary['payment_status_label'] }}
                    </span>
                    <span class="badge rounded-pill px-3 py-2 fw-semibold"
                          style="{{ $pricingSummary['is_pricing_complete'] ? 'background-color: #D1FAE5; color: #047857;' : 'background-color: #FEF3C7; color: #B45309;' }}">
                        {{ $pricingSummary['is_pricing_complete'] ? 'Finalized' : 'Pending Pricing' }}
                    </span>
                </div>
            </div>

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

                        <div class="border-top pt-3">
                            <div class="fw-semibold mb-1" style="color: #777777;">Payment Information</div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span style="color: #777777;">Method</span>
                                <span class="fw-bold" style="color: #171717;">{{ $paymentSnap['payment_method_name'] ?? strtoupper((string) $booking->payment_gateway) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span style="color: #777777;">Payment Status</span>
                                <span class="badge rounded-pill px-2 py-1 fw-semibold" style="{{ $payBadgeStyle }}">{{ $pricingSummary['payment_status_label'] }}</span>
                            </div>
                            @if($booking->stripe_payment_intent_id || $booking->paypal_order_id)
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="color: #777777;">Reference</span>
                                    <span class="small text-muted">{{ $booking->stripe_payment_intent_id ?: $booking->paypal_order_id }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
