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
        .order-detail-main {
            width: 100%;
            padding: 16px 12px calc(92px + env(safe-area-inset-bottom, 0px));
        }
        @media (min-width: 768px) {
            .order-detail-main {
                padding: 20px 24px 88px;
            }
        }
        @media (min-width: 1200px) {
            .order-detail-main {
                padding: 22px 32px 64px;
            }
        }
        .order-detail-shell {
            width: 100%;
            max-width: 1380px;
        }
        .order-header-card {
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%);
            border: 1px solid #FDE68A;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        }
        .order-detail-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            box-shadow: 0 3px 14px rgba(15, 23, 42, 0.04);
            height: 100%;
            min-width: 0;
        }
        .order-card-head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            padding-bottom: 10px;
            margin-bottom: 12px;
            border-bottom: 1px solid #F1F5F9;
        }
        .order-card-title {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            color: #171717;
            margin: 0;
        }
        .order-card-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .order-cat-header {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 6px 10px;
            border-radius: 8px;
        }
        .order-final-box {
            background: linear-gradient(135deg, #FFF8E5 0%, #FEF3C7 100%);
            border: 1px solid #FFC629;
            border-radius: 12px;
            padding: 10px 14px;
        }
        .order-pending-box {
            background-color: #FFF8E5;
            border: 1px solid #FCD34D;
            border-radius: 12px;
            padding: 10px 12px;
        }
        .order-progress-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 6px;
        }
        @media (min-width: 576px) {
            .order-progress-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (min-width: 768px) {
            .order-progress-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (min-width: 1200px) {
            .order-progress-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        .order-kv-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 8px 11px;
            min-width: 0;
        }
        .order-kv-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #64748B;
            margin-bottom: 2px;
        }
        .order-kv-value {
            font-size: 0.84rem;
            font-weight: 600;
            color: #171717;
            overflow-wrap: anywhere;
        }
        .order-wrap-anywhere {
            overflow-wrap: anywhere;
            word-break: break-word;
        }
    </style>
</head>
<body class="booking-page-body">
    
    @include('shared.header')

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

    <main class="order-detail-main">
        <div class="order-detail-shell mx-auto">
            <!-- Top Utility Row -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <a href="{{ route('orders.index') }}" class="booking-back-link">&lsaquo; Back to My Orders</a>
                <span class="badge fw-bold rounded-pill px-3 py-1" style="background-color: #FFC629; color: #171717; font-size: 0.75rem;">
                    &#128274; Confirmed &amp; Locked (Read-Only)
                </span>
            </div>

            <!-- Compact Booking Header -->
            <div class="order-header-card p-3 p-md-3 px-md-4 mb-3">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
                    <div class="min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <h1 class="h4 fw-bold mb-0" style="color: #171717;">Order {{ $booking->reference }}</h1>
                            @if($stageProgress['is_cancelled'])
                                <span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #FEE2E2; color: #B91C1C; font-size: 0.72rem;">Cancelled</span>
                            @elseif($stageProgress['is_finished'])
                                <span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #DCFCE7; color: #16A34A; font-size: 0.72rem;">11/11 Completed</span>
                            @elseif($stageProgress['current_step_number'] > 0)
                                <span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #FFFFFF; color: #92400E; border: 1px solid #FFC629; font-size: 0.72rem;">
                                    Step {{ $stageProgress['current_step_number'] }} of {{ $stageProgress['total_steps'] }}
                                </span>
                            @endif
                        </div>
                        <div class="small mt-1" style="color: #64748B;">
                            @if($booking->confirmed_at)
                                Confirmed on {{ $booking->confirmed_at->format('d M Y, H:i') }}
                            @elseif($booking->created_at)
                                Created on {{ $booking->created_at->format('d M Y, H:i') }}
                            @endif
                            &bull; {{ $bookingStatusLabel }}
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
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
                        @if(empty($paymentState['is_payment_satisfied']) && in_array(strtolower((string) ($booking->payment_gateway ?? 'cod')), ['stripe', 'paypal'], true) && $booking->cancelled_at === null && $booking->collection_otp_verified_at !== null && !empty($pricingSummary['is_pricing_complete']) && (int) ($paymentState['outstanding_minor'] ?? 0) > 0)
                            <a href="#custPaymentCard" class="btn btn-sm fw-bold rounded-pill px-3 py-1" style="background-color: #FFC629; color: #171717;">
                                Pay Now
                            </a>
                        @endif
                    </div>
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

            <!-- Compact Order Progress -->
            <div class="order-detail-card p-3 mb-3" style="height: auto;">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <h2 class="order-card-title">
                        <span class="order-card-icon" style="background-color: #FFF8E5; color: #B45309;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
                        </span>
                        Order Progress
                    </h2>
                    @if($stageProgress['is_cancelled'])
                        <span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #FEE2E2; color: #B91C1C; font-size: 0.72rem;">Cancelled</span>
                    @elseif($stageProgress['is_finished'])
                        <span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #DCFCE7; color: #16A34A; font-size: 0.72rem;">11/11 Completed</span>
                    @elseif($stageProgress['current_step_number'] > 0)
                        <span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #FFF8E5; color: #92400E; border: 1px solid #FFC629; font-size: 0.72rem;">
                            Step {{ $stageProgress['current_step_number'] }} of {{ $stageProgress['total_steps'] }}
                        </span>
                    @endif
                </div>
                @if($stageProgress['is_cancelled'])
                    <div class="small text-danger fw-semibold">
                        This order was cancelled{{ $booking->cancellation_reason ? ': ' . $booking->cancellation_reason : '.' }}
                    </div>
                @else
                    <div class="order-progress-grid">
                        @foreach($stageProgress['stages'] as $st)
                            @php
                                $boxStyle = match($st['state']) {
                                    'completed' => 'background-color: #DCFCE7; border: 1px solid #86EFAC; color: #166534;',
                                    'current' => 'background-color: #FFF8E5; border: 1px solid #FFC629; color: #171717; font-weight: 700;',
                                    default => 'background-color: #F8FAFC; border: 1px solid #E2E8F0; color: #64748B;',
                                };
                            @endphp
                            <div class="px-2 py-1 rounded-3 small d-flex align-items-center justify-content-between gap-2 min-w-0" style="{{ $boxStyle }}">
                                <span class="text-truncate">
                                    @if($st['is_completed'])
                                        <span style="color: #16A34A; font-weight: 700;">&#10003;</span>
                                    @else
                                        <span class="me-1">{{ $st['step'] }}.</span>
                                    @endif
                                    {{ $st['label'] }}
                                </span>
                                @if($st['is_current'])
                                    <span class="badge rounded-pill flex-shrink-0" style="background-color: #FFC629; color: #171717; font-size: 0.65rem;">Current</span>
                                @elseif($st['is_completed'])
                                    <span class="flex-shrink-0" style="color: #16A34A; font-size: 0.68rem; font-weight: 600;">Done</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- ROW 1: Services & Items (67%) + Payment Summary (33%) -->
            <div class="row g-3 align-items-stretch">
                <div class="col-12 col-lg-8">
                    <div class="order-detail-card p-3 p-md-4 d-flex flex-column">
                        <div class="order-card-head">
                            <h2 class="order-card-title">
                                <span class="order-card-icon" style="background-color: #FFF8E5; color: #B45309;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                                </span>
                                Services &amp; Items
                            </h2>
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

                        <div class="flex-grow-1">
                            @forelse($categoryGroups as $group)
                                @php
                                    $catTheme = $categoryPalette[$loop->index % count($categoryPalette)];
                                @endphp
                                <div class="mb-2">
                                    <div class="order-cat-header mb-1"
                                         style="background-color: {{ $catTheme['bg'] }}; border-left: 4px solid {{ $catTheme['border'] }}; color: {{ $catTheme['text'] }};">
                                        {{ $group['category_name'] }}
                                    </div>

                                    @if($group['items']->isEmpty())
                                        <div class="small px-2 py-1" style="color: #64748B;">
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
                                                                    <div class="small" style="color: #64748B; font-size: 0.76rem;">Item: {{ $line->item_name }}</div>
                                                                @endif
                                                            </td>
                                                            <td class="small text-end text-nowrap" style="color: #475569;">
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
                                                            <td class="text-end fw-bold small text-nowrap pe-1" style="color: #171717; width: 110px;">
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
                                <ul class="list-group list-group-flush mb-2">
                                    @foreach ($origServices as $svc)
                                        <li class="list-group-item px-0 d-flex justify-content-between small">
                                            <span class="fw-semibold">{{ $svc['name'] ?? 'Service' }}</span>
                                            <span class="text-secondary">{{ $svc['pricing_type_label'] ?? 'Item-Based' }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endforelse
                        </div>

                        <div class="border-top pt-2 mt-2 small">
                            <div class="row g-1">
                                <div class="col-12 col-sm-6 d-flex justify-content-between pe-sm-3">
                                    <span style="color: #64748B;">Items Subtotal</span>
                                    <span class="fw-semibold text-nowrap" style="color: #171717;">
                                        {{ $currencyService->formatMinor((int) $pricingSummary['items_subtotal_minor'], $booking->currency) }}
                                    </span>
                                </div>
                                <div class="col-12 col-sm-6 d-flex justify-content-between ps-sm-3">
                                    <span style="color: #64748B;">Service Fee</span>
                                    <span class="fw-semibold text-nowrap" style="color: #171717;">
                                        {{ $currencyService->formatMinor((int) $pricingSummary['service_fee_minor'], $booking->currency) }}
                                    </span>
                                </div>
                                <div class="col-12 col-sm-6 d-flex justify-content-between pe-sm-3">
                                    <span style="color: #64748B;">Collection &amp; Delivery Fee</span>
                                    <span class="fw-semibold text-nowrap" style="color: #171717;">
                                        {{ (int) $pricingSummary['delivery_fee_minor'] <= 0 ? 'FREE' : $currencyService->formatMinor((int) $pricingSummary['delivery_fee_minor'], $booking->currency) }}
                                    </span>
                                </div>
                                @if((int) $pricingSummary['tax_minor'] > 0)
                                    <div class="col-12 col-sm-6 d-flex justify-content-between ps-sm-3">
                                        <span style="color: #64748B;">Tax</span>
                                        <span class="fw-semibold text-nowrap" style="color: #171717;">
                                            {{ $currencyService->formatMinor((int) $pricingSummary['tax_minor'], $booking->currency) }}
                                        </span>
                                    </div>
                                @endif
                                @if((int) $pricingSummary['tip_minor'] > 0)
                                    <div class="col-12 col-sm-6 d-flex justify-content-between pe-sm-3">
                                        <span style="color: #64748B;">Driver Tip</span>
                                        <span class="fw-semibold text-nowrap" style="color: #171717;">
                                            {{ $currencyService->formatMinor((int) $pricingSummary['tip_minor'], $booking->currency) }}
                                        </span>
                                    </div>
                                @endif
                                @if((int) $pricingSummary['discount_minor'] > 0)
                                    <div class="col-12 col-sm-6 d-flex justify-content-between ps-sm-3">
                                        <span style="color: #64748B;">Discount</span>
                                        <span class="fw-semibold text-danger text-nowrap">
                                            -{{ $currencyService->formatMinor((int) $pricingSummary['discount_minor'], $booking->currency) }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <div class="order-final-box mt-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold" style="color: #171717;">Final Total</span>
                                    @if($pricingSummary['is_pricing_complete'])
                                        <span class="fs-6 fw-bold text-nowrap" style="color: #171717;">
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

                <!-- Payment Summary (33%) -->
                <div class="col-12 col-lg-4">
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
                    <div class="order-detail-card p-3 p-md-4 d-flex flex-column small" id="custPaymentCard">
                        <div class="order-card-head">
                            <h2 class="order-card-title">
                                <span class="order-card-icon" style="background-color: #EFF6FF; color: #3B82F6;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                </span>
                                Payment Summary
                            </h2>
                            <span id="custPaymentStatusBadge" class="badge rounded-pill px-2 py-1 fw-semibold" style="{{ $payBadgeStyle }}">
                                {{ $pricingSummary['payment_status_label'] }}
                            </span>
                        </div>

                        <div class="d-flex flex-column gap-2 flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                <span style="color: #64748B;">Payment Method</span>
                                <span class="fw-bold" style="color: #171717;">{{ $custGatewayLabel }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                <span style="color: #64748B;">Pricing Status</span>
                                <span class="fw-semibold" style="color: {{ $pricingSummary['is_pricing_complete'] ? '#16A34A' : '#B45309' }};">
                                    {{ $pricingSummary['is_pricing_complete'] ? 'Finalized' : 'Pending Pricing' }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                <span style="color: #64748B;">Final Booking Total</span>
                                <span class="fw-bold text-nowrap" style="color: #171717;">
                                    @if($pricingSummary['is_pricing_complete'])
                                        {{ $currencyService->formatMinor((int) $pricingSummary['final_total_minor'], $booking->currency) }}
                                    @else
                                        Pending
                                    @endif
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                <span style="color: #64748B;">Confirmed Paid</span>
                                <span id="custConfirmedPaid" class="fw-semibold text-success text-nowrap">{{ $currencyService->formatMinor((int) ($custPaymentState['confirmed_paid_minor'] ?? 0), $booking->currency) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1">
                                <span style="color: #64748B;">Outstanding Balance</span>
                                <span id="custOutstandingAmount" class="fw-bold text-nowrap" style="color: #171717;">{{ $currencyService->formatMinor((int) ($custPaymentState['outstanding_minor'] ?? 0), $booking->currency) }}</span>
                            </div>
                            @if($booking->stripe_payment_intent_id || $booking->paypal_order_id)
                                <div class="p-2 rounded-3" style="background-color: #F8FAFC; border: 1px solid #E2E8F0;">
                                    <div class="order-kv-label">Gateway Reference</div>
                                    <div class="small text-muted order-wrap-anywhere">{{ $booking->stripe_payment_intent_id ?: $booking->paypal_order_id }}</div>
                                </div>
                            @endif
                        </div>

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

            <!-- ROW 2: Collection & Delivery (50%) + Assigned Store (50%) -->
            <div class="row g-3 align-items-stretch mt-1">
                <div class="col-12 col-lg-6">
                    <div class="order-detail-card p-3 p-md-4 small">
                        <div class="order-card-head">
                            <h2 class="order-card-title">
                                <span class="order-card-icon" style="background-color: #EFF6FF; color: #3B82F6;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                </span>
                                Collection &amp; Delivery
                            </h2>
                        </div>

                        <div class="row g-2">
                            <div class="col-12 col-sm-6">
                                <div class="p-3 rounded-3 h-100" style="background-color: #EFF6FF; border: 1px solid #BFDBFE;">
                                    <div class="fw-semibold mb-1" style="color: #1D4ED8; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em;">Collection</div>
                                    <div class="fw-bold" style="color: #171717;">
                                        {{ $schedule['collection_date'] ?? $schedule['pickup_date'] ?? '-' }}
                                    </div>
                                    <div class="fw-semibold" style="color: #1E40AF;">
                                        {{ $schedule['collection_slot'] ?? $schedule['pickup_slot'] ?? '-' }}
                                    </div>
                                    @if (!empty($schedule['collection_instruction']))
                                        <div class="mt-2 pt-2 border-top border-primary-subtle" style="color: #475569; font-size: 0.78rem;">
                                            {{ $schedule['collection_instruction'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-12 col-sm-6">
                                <div class="p-3 rounded-3 h-100" style="background-color: #ECFDF5; border: 1px solid #A7F3D0;">
                                    <div class="fw-semibold mb-1" style="color: #047857; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em;">Delivery</div>
                                    <div class="fw-bold" style="color: #171717;">
                                        {{ $schedule['delivery_date'] ?? '-' }}
                                    </div>
                                    <div class="fw-semibold" style="color: #065F46;">
                                        {{ $schedule['delivery_slot'] ?? '-' }}
                                    </div>
                                    @if (!empty($schedule['delivery_instruction']))
                                        <div class="mt-2 pt-2 border-top border-success-subtle" style="color: #475569; font-size: 0.78rem;">
                                            {{ $schedule['delivery_instruction'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="order-detail-card p-3 p-md-4 small">
                        <div class="order-card-head">
                            <h2 class="order-card-title">
                                <span class="order-card-icon" style="background-color: #FFF8E5; color: #F59E0B;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                </span>
                                Assigned Store
                            </h2>
                            @if ($booking->currentStoreAssignment)
                                <span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #FFF8E5; color: #92400E; border: 1px solid #FDE68A;">
                                    Assigned
                                </span>
                            @endif
                        </div>

                        @if ($booking->currentStoreAssignment)
                            <div class="p-3 rounded-3" style="background-color: #FFF8E5; border: 1px solid #FDE68A;">
                                <div class="fw-bold fs-6 mb-1" style="color: #171717;">{{ $booking->currentStoreAssignment->store_name_snapshot }}</div>
                                <div class="order-wrap-anywhere" style="color: #475569; line-height: 1.45;">
                                    {{ $booking->currentStoreAssignment->store_address_snapshot }}
                                    @if ($booking->currentStoreAssignment->store_city_snapshot)
                                        <br>{{ $booking->currentStoreAssignment->store_city_snapshot }}
                                    @endif
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-3 mt-2 pt-2 border-top border-warning-subtle">
                                    @if ($booking->currentStoreAssignment->store_postcode_snapshot)
                                        <div>
                                            <span style="color: #64748B;">Postcode:</span>
                                            <strong style="color: #171717;">{{ $booking->currentStoreAssignment->store_postcode_snapshot }}</strong>
                                        </div>
                                    @endif
                                    @if ($booking->currentStoreAssignment->store_phone_snapshot)
                                        <div>
                                            <span style="color: #64748B;">Phone:</span>
                                            <a href="tel:{{ preg_replace('/\s+/', '', $booking->currentStoreAssignment->store_phone_snapshot) }}"
                                               class="text-decoration-none fw-bold" style="color: #171717;">
                                                {{ $booking->currentStoreAssignment->store_phone_snapshot }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="p-3 rounded-3 bg-light border text-muted">
                                Store assignment pending
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- ROW 3: Delivery Persons (50%) + Customer & Address (50%) -->
            <div class="row g-3 align-items-stretch mt-1">
                <div class="col-12 col-lg-6">
                    <div class="order-detail-card p-3 p-md-4 small">
                        <div class="order-card-head">
                            <h2 class="order-card-title">
                                <span class="order-card-icon" style="background-color: #F5F3FF; color: #8B5CF6;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                                </span>
                                Delivery Persons
                            </h2>
                        </div>

                        <div class="row g-2">
                            <div class="col-12 col-sm-6">
                                <div class="p-3 rounded-3 h-100" style="background-color: #F8FAFC; border: 1px solid #E2E8F0;">
                                    <div class="order-kv-label mb-1">Collection Delivery Person</div>
                                    @if ($booking->currentCollectionAssignment && $booking->currentCollectionAssignment->deliveryPerson)
                                        @php
                                            $collectionAsg = $booking->currentCollectionAssignment;
                                            $collectionPerson = $collectionAsg->deliveryPerson;
                                        @endphp
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                            <span class="fw-bold" style="color: #171717;">{{ $collectionPerson->full_name }}</span>
                                            <span class="badge rounded-pill px-2 py-1 flex-shrink-0" style="background-color: #DBEAFE; color: #1D4ED8;">
                                                {{ $collectionAsg->status_label }}
                                            </span>
                                        </div>
                                        <div style="color: #475569;">
                                            Phone:
                                            @if ($collectionPerson->phone_display !== '')
                                                <a href="tel:{{ preg_replace('/\s+/', '', $collectionPerson->phone_display) }}" class="text-decoration-none fw-semibold" style="color: #171717;">{{ $collectionPerson->phone_display }}</a>
                                            @else
                                                <span>Not provided</span>
                                            @endif
                                        </div>
                                        @if ($collectionAsg->completed_at)
                                            <div class="mt-1 fw-semibold" style="color: #16A34A; font-size: 0.75rem;">
                                                &#10003; Completed {{ $collectionAsg->completed_at->format('d M Y, H:i') }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-muted">Collection delivery person not assigned yet.</div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-12 col-sm-6">
                                <div class="p-3 rounded-3 h-100" style="background-color: #F8FAFC; border: 1px solid #E2E8F0;">
                                    <div class="order-kv-label mb-1">Final Delivery Person</div>
                                    @if ($booking->currentFinalDeliveryAssignment && $booking->currentFinalDeliveryAssignment->deliveryPerson)
                                        @php
                                            $finalAsg = $booking->currentFinalDeliveryAssignment;
                                            $finalPerson = $finalAsg->deliveryPerson;
                                        @endphp
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                            <span class="fw-bold" style="color: #171717;">{{ $finalPerson->full_name }}</span>
                                            <span class="badge rounded-pill px-2 py-1 flex-shrink-0" style="background-color: #D1FAE5; color: #047857;">
                                                {{ $finalAsg->status_label }}
                                            </span>
                                        </div>
                                        <div style="color: #475569;">
                                            Phone:
                                            @if ($finalPerson->phone_display !== '')
                                                <a href="tel:{{ preg_replace('/\s+/', '', $finalPerson->phone_display) }}" class="text-decoration-none fw-semibold" style="color: #171717;">{{ $finalPerson->phone_display }}</a>
                                            @else
                                                <span>Not provided</span>
                                            @endif
                                        </div>
                                        @if ($finalAsg->completed_at)
                                            <div class="mt-1 fw-semibold" style="color: #16A34A; font-size: 0.75rem;">
                                                &#10003; Completed {{ $finalAsg->completed_at->format('d M Y, H:i') }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-muted">Final delivery person not assigned yet.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="order-detail-card p-3 p-md-4 small">
                        <div class="order-card-head">
                            <h2 class="order-card-title">
                                <span class="order-card-icon" style="background-color: #ECFDF5; color: #16A34A;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                </span>
                                Customer &amp; Address
                            </h2>
                            <span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #F1F5F9; color: #475569;">
                                {{ !empty($address['hotel_name']) ? 'Hotel' : ucfirst((string) ($address['address_type'] ?? $booking->customer_type ?? 'Individual')) }}
                            </span>
                        </div>

                        <div class="row g-2">
                            <div class="col-12 col-sm-6">
                                <div class="order-kv-box h-100">
                                    <div class="order-kv-label">Customer</div>
                                    <div class="order-kv-value">{{ $booking->customer_name }} ({{ ucfirst($booking->customer_type ?? 'individual') }})</div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="order-kv-box h-100">
                                    <div class="order-kv-label">Phone</div>
                                    <div class="order-kv-value">{{ $booking->customer_phone }}</div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="order-kv-box h-100">
                                    <div class="order-kv-label">Email</div>
                                    <div class="order-kv-value">{{ $booking->customer_email }}</div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="order-kv-box h-100">
                                    <div class="order-kv-label">Postcode</div>
                                    <div class="order-kv-value">{{ $address['postcode'] ?? '-' }}</div>
                                </div>
                            </div>
                            @if (!empty($address['hotel_name']) || !empty($address['room_number']) || !empty($address['hotel_preference']))
                                <div class="col-12">
                                    <div class="order-kv-box" style="background-color: #FFF8E5; border-color: #FDE68A;">
                                        <div class="order-kv-label" style="color: #92400E;">Hotel &amp; Room</div>
                                        <div class="order-kv-value">
                                            {{ $address['hotel_name'] ?? 'Hotel' }}
                                            @if (!empty($address['room_number']))
                                                &bull; Room: {{ $address['room_number'] }}
                                            @endif
                                        </div>
                                        @if (!empty($address['hotel_preference']))
                                            <div class="mt-1" style="color: #78350F; font-size: 0.78rem;">{{ $address['hotel_preference'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="col-12">
                                <div class="order-kv-box">
                                    <div class="order-kv-label">Collection / Delivery Address</div>
                                    <div class="order-kv-value" style="font-weight: 500;">
                                        {{ $address['selected_address'] ?? $address['full_address'] ?? $address['address_line'] ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('orders.partials.need-help-claims')
        </div>
    </main>

    @include('shared.footer')
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
