@extends('store-portal.layouts.app')

@section('title', 'Store Dashboard')

@section('content')
<div class="sp-card p-3 p-md-4 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border-color: #FDE68A; border-left: 5px solid #FFC629;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <div class="small fw-semibold" style="color: #92400E;">Welcome Back, {{ $storeUser->name }}</div>
            <h4 class="fw-bold mb-1" style="color: #171717;">{{ $store->name }}</h4>
            <div class="small" style="color: #475569;">
                {{ $store->full_address ?: implode(', ', array_filter([$store->address_line_1, $store->city])) }}
                @if($store->postcode) &middot; <strong>{{ $store->postcode }}</strong> @endif
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background-color: #DCFCE7; color: #166534;">
                {{ ucfirst($store->status) }} Store
            </span>
        </div>
    </div>
</div>

<!-- 5 Colorful Dashboard Statistic Cards -->
@if(isset($pendingHandoversCount) && $pendingHandoversCount > 0)
    <div class="sp-card p-3 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2" style="background: #FEF3C7; border-color: #FDE68A;">
        <div class="small fw-semibold text-dark">
            <i class="mdi mdi-truck-delivery text-warning-emphasis me-1"></i>
            <strong>{{ $pendingHandoversCount }}</strong> Pending Handover Request(s) awaiting physical receipt confirmation.
        </div>
        <a href="{{ route('store.handovers.index') }}" class="btn btn-sm sp-btn-yellow px-3">Review Incoming Handovers</a>
    </div>
@endif
<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-3 mb-3">
    <div class="col">
        <div class="sp-card p-3 h-100" style="background: #ECFDF5; border-color: #86EFAC;">
            <div class="small fw-bold text-uppercase" style="color: #15803D; font-size: 0.72rem;">Current Balance</div>
            <div class="fs-5 fw-bold mt-1" style="color: #171717;">
                @forelse($accountSummary as $row)
                    <div>{{ $currencyService->formatMinor((int) $row['current_balance_minor'], $row['currency']) }}</div>
                @empty
                    0.00
                @endforelse
            </div>
            <div class="small" style="color: #475569; font-size: 0.72rem;">Posted credits less debits</div>
        </div>
    </div>
    <div class="col">
        <div class="sp-card p-3 h-100" style="background: #FFF7E2; border-color: #F5BE48;">
            <div class="small fw-bold text-uppercase" style="color: #946200; font-size: 0.72rem;">Pending Earnings</div>
            <div class="fs-5 fw-bold mt-1" style="color: #171717;">
                @forelse($accountSummary as $row)
                    <div>{{ $currencyService->formatMinor((int) $row['pending_earnings_minor'], $row['currency']) }}</div>
                @empty
                    0.00
                @endforelse
            </div>
            <div class="small" style="color: #475569; font-size: 0.72rem;">Awaiting final delivery</div>
        </div>
    </div>
    <div class="col">
        <div class="sp-card p-3 h-100" style="background: #EFF6FF; border-color: #93C5FD;">
            <div class="small fw-bold text-uppercase" style="color: #1D4ED8; font-size: 0.72rem;">Total Earned</div>
            <div class="fs-5 fw-bold mt-1" style="color: #171717;">
                @forelse($accountSummary as $row)
                    <div>{{ $currencyService->formatMinor((int) $row['total_earned_minor'], $row['currency']) }}</div>
                @empty
                    0.00
                @endforelse
            </div>
            <div class="small" style="color: #475569; font-size: 0.72rem;">Completed bookings</div>
        </div>
    </div>
    <div class="col">
        <div class="sp-card p-3 h-100" style="background: #F5F3FF; border-color: #C4B5FD;">
            <div class="small fw-bold text-uppercase" style="color: #6D28D9; font-size: 0.72rem;">Total Paid Out</div>
            <div class="fs-5 fw-bold mt-1" style="color: #171717;">
                @forelse($accountSummary as $row)
                    <div>{{ $currencyService->formatMinor((int) $row['total_paid_out_minor'], $row['currency']) }}</div>
                @empty
                    0.00
                @endforelse
            </div>
            <div class="small" style="color: #475569; font-size: 0.72rem;">Confirmed payout debits</div>
        </div>
    </div>
    <div class="col">
        <div class="sp-card p-3 h-100" style="background: #ECFEFF; border-color: #67E8F9;">
            <div class="small fw-bold text-uppercase" style="color: #0E7490; font-size: 0.72rem;">Assigned Bookings</div>
            <div class="fs-3 fw-bold mt-1" style="color: #171717;">{{ $stats['total'] }}</div>
            <div class="small" style="color: #475569; font-size: 0.72rem;">{{ $stats['in_progress'] }} in progress &middot; {{ $stats['completed'] }} done</div>
        </div>
    </div>
</div>

<!-- Recent Assigned Bookings -->
<div class="sp-card p-3 p-md-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0" style="color: #171717;">Recent Assigned Bookings</h6>
        <a href="{{ route('store.bookings.index') }}" class="btn btn-sm sp-btn-yellow px-3">View All Bookings</a>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Booking Ref</th>
                    <th>Collection Date</th>
                    <th>Workflow Status</th>
                    <th>Net Earnings</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentBookings as $bk)
                    @php
                        $sched = $bk->schedule_snapshot ?? [];
                        $earning = $bk->currentStoreEarning;
                    @endphp
                    <tr class="small">
                        <td class="fw-bold">{{ $bk->reference }}</td>
                        <td>{{ $sched['collection_date'] ?? '-' }} ({{ $sched['collection_slot'] ?? '-' }})</td>
                        <td>
                            <span class="badge rounded-pill px-2 py-1" style="{{ \App\Models\Booking::statusBadgeStyle($bk->booking_status) }}">
                                {{ $bk->getStatusLabel() }}
                            </span>
                        </td>
                        <td class="fw-semibold">
                            @if($earning)
                                {{ $currencyService->formatMinor((int) $earning->net_store_earnings_minor, $earning->currency) }}
                            @else
                                <span class="text-muted">Not finalized</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('store.bookings.show', $bk) }}" class="btn btn-sm btn-outline-dark rounded-pill px-3">View Details</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No bookings assigned to your store yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
