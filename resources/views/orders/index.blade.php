<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Orders | FreshGo Laundry</title>
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/booking-address.css') }}">
    <style>
        .orders-shell {
            max-width: 1120px;
            margin: 0 auto;
        }
        .orders-stat-card {
            border-radius: 14px;
            padding: 0.9rem 1rem;
            border: 1px solid transparent;
            height: 100%;
            text-decoration: none;
            display: block;
            transition: transform 0.14s ease, box-shadow 0.14s ease;
        }
        .orders-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
        }
        .orders-stat-card--total { background: #EFF6FF; border-color: #BFDBFE; color: #1D4ED8; }
        .orders-stat-card--pending { background: #FFF7E2; border-color: #FDE68A; color: #A16207; }
        .orders-stat-card--progress { background: #F5F3FF; border-color: #DDD6FE; color: #6D28D9; }
        .orders-stat-card--completed { background: #ECFDF5; border-color: #A7F3D0; color: #15803D; }
        .orders-stat-card--cancelled { background: #FFF1F2; border-color: #FECDD3; color: #BE123C; }
        .orders-stat-title {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .orders-stat-count {
            font-size: 1.55rem;
            font-weight: 800;
            color: #171717;
            line-height: 1.15;
            margin-top: 0.2rem;
        }
        .orders-filter-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .orders-preset-pill {
            font-size: 0.76rem;
            font-weight: 600;
            padding: 0.3rem 0.75rem;
            border-radius: 999px;
            border: 1px solid #CBD5E1;
            background: #FFFFFF;
            color: #334155;
            text-decoration: none;
            white-space: nowrap;
        }
        .orders-preset-pill.active, .orders-preset-pill:hover {
            background: #FFF8E5;
            border-color: #FFC629;
            color: #171717;
        }
        .orders-filter-input {
            height: 40px;
            border-radius: 10px;
            border-color: #CBD5E1;
            color: #171717;
            font-size: 0.85rem;
        }
        .orders-filter-input:focus {
            border-color: #FFC629;
            box-shadow: 0 0 0 3px rgba(255, 198, 41, 0.25);
        }
        .order-item-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            transition: box-shadow 0.15s ease;
        }
        .order-item-card:hover {
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.07);
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

    <main class="container booking-main-container py-4">
        <div class="orders-shell">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h1 class="booking-screen-title mb-1">My Orders</h1>
                    <p class="booking-screen-subtitle mb-0" style="color: #475569;">Track your laundry bookings, schedules, and totals in real time.</p>
                </div>
                <a href="{{ route('booking.services', ['new' => 1]) }}" class="btn btn-warning fw-bold text-dark rounded-pill px-4" style="background-color: #FFC629; border-color: #FFC629;">
                    + New Booking
                </a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger rounded-3 py-2 px-3 small mb-3">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Order Statistics (All-Time Customer Totals) -->
            <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-5 g-2 g-md-3 mb-3">
                <div class="col">
                    <a href="{{ route('orders.index', array_merge(request()->except('page', 'status'), ['status' => 'all'])) }}" class="orders-stat-card orders-stat-card--total">
                        <div class="orders-stat-title">Total Orders</div>
                        <div class="orders-stat-count">{{ $stats['total'] }}</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('orders.index', array_merge(request()->except('page', 'status'), ['status' => 'pending'])) }}" class="orders-stat-card orders-stat-card--pending">
                        <div class="orders-stat-title">Pending Orders</div>
                        <div class="orders-stat-count">{{ $stats['pending'] }}</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('orders.index', array_merge(request()->except('page', 'status'), ['status' => 'in_progress'])) }}" class="orders-stat-card orders-stat-card--progress">
                        <div class="orders-stat-title">In Progress</div>
                        <div class="orders-stat-count">{{ $stats['in_progress'] }}</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('orders.index', array_merge(request()->except('page', 'status'), ['status' => 'completed'])) }}" class="orders-stat-card orders-stat-card--completed">
                        <div class="orders-stat-title">Completed</div>
                        <div class="orders-stat-count">{{ $stats['completed'] }}</div>
                    </a>
                </div>
                <div class="col-12 col-sm">
                    <a href="{{ route('orders.index', array_merge(request()->except('page', 'status'), ['status' => 'cancelled'])) }}" class="orders-stat-card orders-stat-card--cancelled">
                        <div class="orders-stat-title">Cancelled</div>
                        <div class="orders-stat-count">{{ $stats['cancelled'] }}</div>
                    </a>
                </div>
            </div>

            <!-- Compact Filter Bar -->
            <div class="orders-filter-card p-3 mb-3">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2 pb-2 border-bottom">
                    <span class="small fw-semibold me-1" style="color: #475569;">Quick Range:</span>
                    @php
                        $presets = [
                            'all' => 'All Time',
                            'today' => 'Today',
                            'last_7_days' => 'Last 7 Days',
                            'last_30_days' => 'Last 30 Days',
                            'this_month' => 'This Month',
                        ];
                    @endphp
                    @foreach ($presets as $pKey => $pLabel)
                        <a href="{{ route('orders.index', array_filter(['preset' => $pKey, 'status' => $filters['status'] !== 'all' ? $filters['status'] : null, 'q' => $filters['q'] !== '' ? $filters['q'] : null])) }}"
                           class="orders-preset-pill {{ $filters['preset'] === $pKey ? 'active' : '' }}">
                            {{ $pLabel }}
                        </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('orders.index') }}" class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label for="filterFromDate" class="form-label small fw-semibold mb-1" style="color: #475569;">From Date</label>
                        <input id="filterFromDate" type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control orders-filter-input">
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="filterToDate" class="form-label small fw-semibold mb-1" style="color: #475569;">To Date</label>
                        <input id="filterToDate" type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control orders-filter-input">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="filterStatus" class="form-label small fw-semibold mb-1" style="color: #475569;">Status</label>
                        <select id="filterStatus" name="status" class="form-select orders-filter-input">
                            <option value="all" @selected($filters['status'] === 'all')>All Orders</option>
                            <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
                            <option value="in_progress" @selected($filters['status'] === 'in_progress')>In Progress</option>
                            <option value="completed" @selected($filters['status'] === 'completed')>Completed</option>
                            <option value="cancelled" @selected($filters['status'] === 'cancelled')>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="filterSearch" class="form-label small fw-semibold mb-1" style="color: #475569;">Search</label>
                        <input id="filterSearch" type="text" name="q" value="{{ $filters['q'] }}" class="form-control orders-filter-input" placeholder="Search by booking reference...">
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-dark btn-sm fw-semibold flex-grow-1" style="height: 40px; border-radius: 10px;">Apply</button>
                        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center justify-content-center" style="height: 40px; border-radius: 10px;">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Orders List / Empty States -->
            @if ($stats['total'] === 0)
                <div class="order-item-card text-center p-5">
                    <h2 class="h5 fw-bold mb-2" style="color: #171717;">No orders yet.</h2>
                    <p class="small mb-3" style="color: #475569;">Your laundry bookings will appear here automatically after you place an order.</p>
                    <a href="{{ route('booking.services', ['new' => 1]) }}" class="btn btn-warning fw-bold text-dark rounded-pill px-4" style="background-color: #FFC629; border-color: #FFC629;">
                        Book Laundry Service
                    </a>
                </div>
            @elseif ($bookings->isEmpty())
                <div class="order-item-card text-center p-5">
                    <h2 class="h5 fw-bold mb-2" style="color: #171717;">No orders match your filters.</h2>
                    <p class="small mb-3" style="color: #475569;">Try adjusting your date range, status, or booking reference search.</p>
                    <a href="{{ route('orders.index') }}" class="btn btn-dark fw-semibold rounded-pill px-4">
                        Clear Filters
                    </a>
                </div>
            @else
                <div class="row g-3">
                    @foreach ($bookings as $booking)
                        @php
                            $schedule = $booking->schedule_snapshot ?? [];
                            $services = collect($booking->services_snapshot ?? [])->pluck('name')->filter();
                            if ($services->isEmpty() && $booking->operationalItems->isNotEmpty()) {
                                $services = $booking->operationalItems->pluck('service_name')->filter();
                            }
                            $serviceCount = $services->count();
                            $servicePreview = $services->take(3)->implode(', ') . ($serviceCount > 3 ? ' +' . ($serviceCount - 3) . ' more' : '');
                            $badgeInfo = $booking->getCustomerStatusBadgeStyle();
                            $pricing = $booking->getOperationalPricingSummary($currencyService);
                            $displayAmountMinor = $pricing['is_pricing_complete'] && $pricing['final_total_minor'] !== null
                                ? (int) $pricing['final_total_minor']
                                : (int) $booking->estimated_amount_minor;
                            $storeName = $booking->currentStoreAssignment?->store_name_snapshot ?? $booking->assignedStore?->name;
                        @endphp
                        <div class="col-12">
                            <div class="order-item-card p-3 p-md-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2 pb-2 border-bottom">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="badge fw-bold rounded-pill px-3 py-1" style="background-color: #FFC629; color: #171717;">{{ $booking->reference }}</span>
                                        @if ($booking->currentInvoice)
                                            <span class="badge bg-light text-dark border">{{ $booking->currentInvoice->invoice_number }}</span>
                                        @endif
                                        <span class="small fw-medium" style="color: #475569;">{{ $booking->created_at?->format('d M Y, H:i') }}</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge rounded-pill px-3 py-1 fw-semibold" style="{{ $badgeInfo['style'] }}">
                                            {{ $badgeInfo['label'] }}
                                        </span>
                                        <span class="badge bg-dark text-white text-uppercase px-2 py-1">
                                            {{ $pricing['payment_status_label'] }}
                                        </span>
                                    </div>
                                </div>

                                <div class="row g-2 small align-items-center">
                                    <div class="col-12 col-md-4">
                                        <div style="color: #475569;">Services ({{ $serviceCount }})</div>
                                        <div class="fw-semibold" style="color: #171717;">{{ $servicePreview ?: 'Laundry Order' }}</div>
                                        @if ($storeName)
                                            <div class="mt-1" style="color: #475569; font-size: 0.78rem;">Store: <strong style="color: #171717;">{{ $storeName }}</strong></div>
                                        @endif
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <div style="color: #475569;">Collection &amp; Delivery</div>
                                        <div style="color: #171717;">
                                            Col: <strong>{{ $schedule['collection_date'] ?? '-' }}</strong> ({{ $schedule['collection_slot'] ?? '-' }})<br>
                                            Del: <strong>{{ $schedule['delivery_date'] ?? '-' }}</strong> ({{ $schedule['delivery_slot'] ?? '-' }})
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-2 text-md-end">
                                        <div style="color: #475569;">{{ $pricing['is_pricing_complete'] ? 'Final Total' : 'Estimated' }}</div>
                                        <div class="fw-bold fs-6" style="color: #171717;">
                                            {{ $currencyService->formatMinor($displayAmountMinor, $booking->currency) }}
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-2 text-end">
                                        <a href="{{ route('orders.show', $booking->reference) }}" class="btn btn-sm fw-bold rounded-pill px-3" style="background-color: #FFC629; color: #171717;">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </main>

    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
