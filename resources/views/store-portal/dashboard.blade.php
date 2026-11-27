@extends('store-portal.layouts.app')

@section('title', 'Store Dashboard')

@section('content')
<div class="sp-card sp-anim-card p-3 p-md-4 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border-color: #FDE68A; border-left: 5px solid #FFC629;">
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

<!-- Current Pending Actions (Always visible regardless of selected month) -->
@if(($pendingHandoversCount ?? 0) > 0 || ($activeProcessingCount ?? 0) > 0)
    <div class="sp-card sp-anim-card p-3 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2" style="background: #FEF3C7; border-color: #FDE68A;">
        <div class="small fw-semibold text-dark d-flex flex-wrap align-items-center gap-3">
            <span class="d-inline-flex align-items-center gap-2">
                <span class="sp-pulse-dot"></span>
                <strong>Current Pending Actions:</strong>
            </span>
            @if(($pendingHandoversCount ?? 0) > 0)
                <span><strong>{{ $pendingHandoversCount }}</strong> Pending Handover {{ Str::plural('Request', $pendingHandoversCount) }}</span>
            @endif
            @if(($activeProcessingCount ?? 0) > 0)
                <span><strong>{{ $activeProcessingCount }}</strong> Active Booking(s) in workflow</span>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if(($pendingHandoversCount ?? 0) > 0)
                <a href="{{ route('store.handovers.index', ['pending_only' => 1]) }}" class="btn btn-sm sp-btn-yellow px-3">Review Handovers</a>
            @endif
            <a href="{{ route('store.bookings.index', ['active_only' => 1]) }}" class="btn btn-sm btn-outline-dark px-3">Active Work</a>
        </div>
    </div>
@endif

<!-- Current Live Balance Strip (Never changes with month) -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="sp-card sp-anim-card p-3 h-100" style="background: #ECFDF5; border-color: #86EFAC;">
            <div class="small fw-bold text-uppercase" style="color: #15803D; font-size: 0.7rem;">Current Available Balance</div>
            <div class="fs-5 fw-bold mt-1 text-dark">
                @forelse($accountSummary as $row)
                    <div>{{ $currencyService->formatMinor((int) $row['current_balance_minor'], $row['currency']) }}</div>
                @empty 0.00 @endforelse
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sp-card sp-anim-card p-3 h-100" style="background: #FFF7E2; border-color: #F5BE48;">
            <div class="small fw-bold text-uppercase" style="color: #946200; font-size: 0.7rem;">Pending Earnings</div>
            <div class="fs-5 fw-bold mt-1 text-dark">
                @forelse($accountSummary as $row)
                    <div>{{ $currencyService->formatMinor((int) $row['pending_earnings_minor'], $row['currency']) }}</div>
                @empty 0.00 @endforelse
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sp-card sp-anim-card p-3 h-100" style="background: #EFF6FF; border-color: #93C5FD;">
            <div class="small fw-bold text-uppercase" style="color: #1D4ED8; font-size: 0.7rem;">All-Time Earned</div>
            <div class="fs-5 fw-bold mt-1 text-dark">
                @forelse($accountSummary as $row)
                    <div>{{ $currencyService->formatMinor((int) $row['total_earned_minor'], $row['currency']) }}</div>
                @empty 0.00 @endforelse
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sp-card sp-anim-card p-3 h-100" style="background: #F5F3FF; border-color: #C4B5FD;">
            <div class="small fw-bold text-uppercase" style="color: #6D28D9; font-size: 0.7rem;">Total Paid Out</div>
            <div class="fs-5 fw-bold mt-1 text-dark">
                @forelse($accountSummary as $row)
                    <div>{{ $currencyService->formatMinor((int) $row['total_paid_out_minor'], $row['currency']) }}</div>
                @empty 0.00 @endforelse
            </div>
        </div>
    </div>
</div>

@include('store-portal.partials.month-bar', [
    'prefix' => 'spDash',
    'subtitle' => 'Monthly Store metrics by assignment, handover, and earning date.',
])

<div id="spDashError" class="alert alert-danger d-none small py-2 mb-3"></div>

<div id="spDashResultsWrap" class="sp-ajax-wrap">
    @include('store-portal.partials.dashboard-content')
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.StorePortalMonthAjax.init({
        prefix: 'spDash',
        baseUrl: @json(route('store.dashboard')),
        currentMonth: {{ $currentMonth }},
        currentYear: {{ $currentYear }},
        wrapId: 'spDashResultsWrap',
        errorId: 'spDashError',
        htmlKey: 'results_html'
    });
});
</script>
@endpush
@endsection
