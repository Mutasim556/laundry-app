@extends('admin::layouts.app')

@section('title', 'Overview & Comparisons - Reports')

@section('content')
<div class="container-fluid py-3">
    @include('admin::reports.partials.header', [
        'reportTitle' => 'Overview & Comparisons',
        'reportSubtitle' => 'Compare website booking growth, operational completion, and financial performance against the previous period.',
    ])

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm p-3 h-100"><div class="small text-muted">New Bookings</div><div class="fs-4 fw-bold text-primary">{{ $overview['kpis']['new_bookings'] }}</div><div class="small text-muted">Prev: {{ $overview['kpis']['prev_new_bookings'] }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm p-3 h-100"><div class="small text-muted">Store Completed</div><div class="fs-4 fw-bold text-success">{{ $overview['kpis']['store_completed'] }}</div><div class="small text-muted">Deliveries Done: {{ $overview['kpis']['deliveries_completed'] }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm p-3 h-100"><div class="small text-muted">Verified Receipts ({{ $currency }})</div><div class="fs-4 fw-bold text-dark">{{ $finance['kpis']['verified_receipts_formatted'] }}</div><div class="small text-muted">Commission: {{ $finance['kpis']['platform_commission_formatted'] }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm p-3 h-100"><div class="small text-muted">Cancelled Bookings</div><div class="fs-4 fw-bold text-danger">{{ $overview['kpis']['cancelled_bookings'] }}</div><div class="small text-muted">QR Tags Pending: {{ $tags['pending_store_tags'] }}</div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold small">Booking Progress: Current vs Previous Period</div>
                <div class="card-body"><div id="repOverviewTrendChart" style="min-height: 280px;"></div></div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold small">Collection vs Final Delivery Jobs</div>
                <div class="card-body"><div id="repOverviewJobsChart" style="min-height: 280px;"></div></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin/dist/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;
    new ApexCharts(document.getElementById('repOverviewTrendChart'), {
        chart: { type: 'area', height: 280, toolbar: { show: false } },
        series: [
            { name: 'Current Period', data: @json($overview['trend']['current']) },
            { name: 'Previous Period', data: @json($overview['trend']['previous']) }
        ],
        xaxis: { categories: @json($overview['trend']['labels']) },
        colors: ['#3B82F6', '#94A3B8'],
        stroke: { curve: 'smooth', width: 2 },
        dataLabels: { enabled: false }
    }).render();

    new ApexCharts(document.getElementById('repOverviewJobsChart'), {
        chart: { type: 'bar', height: 280, toolbar: { show: false } },
        series: [
            { name: 'Assigned', data: [{{ $operations['delivery_jobs']['collection_assigned'] }}, {{ $operations['delivery_jobs']['final_assigned'] }}] },
            { name: 'Completed', data: [{{ $operations['delivery_jobs']['collection_completed'] }}, {{ $operations['delivery_jobs']['final_completed'] }}] }
        ],
        xaxis: { categories: ['Collection', 'Final Delivery'] },
        colors: ['#FFC629', '#16A34A']
    }).render();
});
</script>
@endpush
