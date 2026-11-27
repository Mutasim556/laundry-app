@extends('store-portal.layouts.app')

@section('title', 'My Bookings')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0" style="color: #171717;">Assigned Bookings</h4>
        <div class="small" style="color: #475569;">Month-wise assigned bookings for {{ $store->name }} (filtered by Store assignment date).</div>
    </div>
    <div class="form-check form-switch m-0">
        <input class="form-check-input" type="checkbox" id="spBkActiveOnlySwitch" @checked(!empty($filters['active_only']))>
        <label class="form-check-label small fw-semibold" for="spBkActiveOnlySwitch">Active / Pending Work (All Months)</label>
    </div>
</div>

@include('store-portal.partials.month-bar', [
    'prefix' => 'spBk',
    'subtitle' => 'Showing bookings assigned in the selected month.',
])

<!-- 5 Monthly Summary Cards -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md"><div class="sp-card sp-anim-card p-3 bg-white"><div class="small text-muted fw-semibold">Total Assigned</div><div class="fs-5 fw-bold text-dark" id="spBkSumTotal">{{ $summary['total'] }}</div></div></div>
    <div class="col-6 col-md"><div class="sp-card sp-anim-card p-3" style="background:#FFF8E5; border-color:#FDE68A;"><div class="small fw-semibold text-warning-emphasis">Waiting Receive</div><div class="fs-5 fw-bold text-dark" id="spBkSumWait">{{ $summary['waiting_receive'] }}</div></div></div>
    <div class="col-6 col-md"><div class="sp-card sp-anim-card p-3" style="background:#EFF6FF; border-color:#BFDBFE;"><div class="small fw-semibold text-primary">Processing</div><div class="fs-5 fw-bold text-dark" id="spBkSumProc">{{ $summary['processing'] }}</div></div></div>
    <div class="col-6 col-md"><div class="sp-card sp-anim-card p-3" style="background:#ECFDF5; border-color:#A7F3D0;"><div class="small fw-semibold text-success">Store Completed</div><div class="fs-5 fw-bold text-dark" id="spBkSumComp">{{ $summary['store_completed'] }}</div></div></div>
    <div class="col-12 col-md"><div class="sp-card sp-anim-card p-3" style="background:#F5F3FF; border-color:#DDD6FE;"><div class="small fw-semibold" style="color:#6D28D9;">Pending Handover</div><div class="fs-5 fw-bold text-dark" id="spBkSumHand">{{ $summary['pending_handover'] }}</div></div></div>
</div>

<!-- AJAX Filter Bar -->
<div class="sp-card p-3 mb-3">
    <form id="spBkFilterForm" method="GET" action="{{ route('store.bookings.index') }}" class="row g-2 align-items-end small">
        <div class="col-12 col-lg-3">
            <label class="form-label fw-semibold mb-1">Search</label>
            <input type="text" id="spBkSearchInput" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Booking ref, customer, postcode...">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label fw-semibold mb-1">Booking Status</label>
            <select id="spBkStatusSelect" name="status" class="form-select form-select-sm">
                <option value="all" @selected($filters['status'] === 'all')>All</option>
                <option value="new" @selected($filters['status'] === 'new')>New</option>
                <option value="in_progress" @selected($filters['status'] === 'in_progress')>In Progress</option>
                <option value="completed" @selected($filters['status'] === 'completed')>Completed</option>
                <option value="cancelled" @selected($filters['status'] === 'cancelled')>Cancelled</option>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label fw-semibold mb-1">Store Stage</label>
            <select id="spBkStageSelect" name="store_stage" class="form-select form-select-sm">
                <option value="all" @selected($filters['store_stage'] === 'all')>All Stages</option>
                <option value="awaiting_receive" @selected($filters['store_stage'] === 'awaiting_receive')>Waiting Receive</option>
                <option value="store_received" @selected($filters['store_stage'] === 'store_received')>Store Received</option>
                <option value="store_processing" @selected($filters['store_stage'] === 'store_processing')>Processing</option>
                <option value="store_complete" @selected($filters['store_stage'] === 'store_complete')>Store Complete</option>
            </select>
        </div>
        <div class="col-6 col-md-2 col-lg-2">
            <label class="form-label fw-semibold mb-1">Finalization</label>
            <select id="spBkPricingSelect" name="pricing" class="form-select form-select-sm">
                <option value="all" @selected($filters['pricing'] === 'all')>All</option>
                <option value="finalized" @selected($filters['pricing'] === 'finalized')>Finalized</option>
                <option value="not_finalized" @selected($filters['pricing'] === 'not_finalized')>Not Finalized</option>
            </select>
        </div>
        <div class="col-6 col-md-2 col-lg-2">
            <label class="form-label fw-semibold mb-1">Sort</label>
            <select id="spBkSortSelect" name="sort" class="form-select form-select-sm">
                <option value="assigned_desc" @selected($filters['sort'] === 'assigned_desc')>Newest Assigned</option>
                <option value="assigned_asc" @selected($filters['sort'] === 'assigned_asc')>Oldest Assigned</option>
                <option value="updated_desc" @selected($filters['sort'] === 'updated_desc')>Recently Updated</option>
            </select>
        </div>
        <div class="col-12 col-md-2 col-lg-1 d-grid">
            <button type="button" id="spBkResetBtn" class="btn btn-sm btn-outline-secondary">Reset</button>
        </div>
    </form>
</div>

<div id="spBkError" class="alert alert-danger d-none small py-2 mb-3"></div>

<div id="spBkResultsWrap" class="sp-ajax-wrap">
    @include('store-portal.bookings.partials.table')
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.StorePortalMonthAjax.init({
        prefix: 'spBk',
        baseUrl: @json(route('store.bookings.index')),
        currentMonth: {{ $currentMonth }},
        currentYear: {{ $currentYear }},
        wrapId: 'spBkResultsWrap',
        errorId: 'spBkError',
        formId: 'spBkFilterForm',
        resetBtnId: 'spBkResetBtn',
        paginationSelector: '.sp-bk-pagination',
        changeIds: ['spBkStatusSelect', 'spBkStageSelect', 'spBkPricingSelect', 'spBkSortSelect', 'spBkActiveOnlySwitch'],
        searchIds: ['spBkSearchInput'],
        collectParams: function () {
            return {
                q: document.getElementById('spBkSearchInput')?.value.trim() || '',
                status: document.getElementById('spBkStatusSelect')?.value || 'all',
                store_stage: document.getElementById('spBkStageSelect')?.value || 'all',
                pricing: document.getElementById('spBkPricingSelect')?.value || 'all',
                sort: document.getElementById('spBkSortSelect')?.value || 'assigned_desc',
                active_only: document.getElementById('spBkActiveOnlySwitch')?.checked ? '1' : ''
            };
        },
        onSuccess: function (data) {
            if (data.summary) {
                document.getElementById('spBkSumTotal').textContent = data.summary.total ?? 0;
                document.getElementById('spBkSumWait').textContent = data.summary.waiting_receive ?? 0;
                document.getElementById('spBkSumProc').textContent = data.summary.processing ?? 0;
                document.getElementById('spBkSumComp').textContent = data.summary.store_completed ?? 0;
                document.getElementById('spBkSumHand').textContent = data.summary.pending_handover ?? 0;
            }
        },
        onReset: function () {
            document.getElementById('spBkSearchInput').value = '';
            document.getElementById('spBkStatusSelect').value = 'all';
            document.getElementById('spBkStageSelect').value = 'all';
            document.getElementById('spBkPricingSelect').value = 'all';
            document.getElementById('spBkSortSelect').value = 'assigned_desc';
            document.getElementById('spBkActiveOnlySwitch').checked = false;
        }
    });
});
</script>
@endpush
@endsection
