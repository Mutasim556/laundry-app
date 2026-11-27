@extends('admin::layouts.app')

@section('title', $meta['title'] . ' - Reports')

@section('content')
<div class="container-fluid py-3">
    @include('admin::reports.partials.header', [
        'reportTitle' => $meta['title'],
        'reportSubtitle' => $meta['subtitle'],
    ])

    @if(!empty($meta['note']))
        <div class="alert alert-info small py-2 mb-3">{{ $meta['note'] }}</div>
    @endif

    <div class="row g-3 mb-3">
        @foreach($meta['kpis'] as $kpi)
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm p-3 h-100">
                    <div class="small text-muted">{{ $kpi['label'] }}</div>
                    <div class="fs-4 fw-bold text-{{ $kpi['color'] }}">{{ $kpi['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold small">Period Activity Trend (Current vs Previous)</div>
                <div class="card-body"><div id="repDynTrendChart" style="min-height: 240px;"></div></div>
            </div>
        </div>
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold small">Recent Recorded Activity</div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle small mb-0">
                            <thead class="table-light sticky-top">
                                <tr><th>Module</th><th>Ref</th><th>Description</th><th>When</th></tr>
                            </thead>
                            <tbody>
                                @forelse($activity as $act)
                                    <tr>
                                        <td><span class="badge bg-light text-dark border">{{ $act['module'] }}</span></td>
                                        <td><code>{{ $act['reference'] }}</code></td>
                                        <td>{{ $act['description'] }}</td>
                                        <td class="text-nowrap text-muted">{{ $act['timestamp'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">No recent activity recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
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
    new ApexCharts(document.getElementById('repDynTrendChart'), {
        chart: { type: 'area', height: 240, toolbar: { show: false } },
        series: [
            { name: 'Current Period', data: @json($overview['trend']['current']) },
            { name: 'Previous Period', data: @json($overview['trend']['previous']) }
        ],
        xaxis: { categories: @json($overview['trend']['labels']) },
        colors: ['#3B82F6', '#94A3B8'],
        stroke: { curve: 'smooth', width: 2 },
        dataLabels: { enabled: false }
    }).render();
});
</script>
@endpush
