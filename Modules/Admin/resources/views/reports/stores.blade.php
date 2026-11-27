@extends('admin::layouts.app')

@section('title', 'Store Performance - Reports')

@section('content')
<div class="container-fluid py-3">
    @include('admin::reports.partials.header', [
        'reportTitle' => 'Store Performance',
        'reportSubtitle' => 'Compare store handovers received, processing completed, rejected handovers, and net store earnings.',
    ])

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-bold small">Store Received vs Completed Comparison</div>
        <div class="card-body"><div id="repStorePerfChart" style="min-height: 260px;"></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold small">Store Breakdown ({{ $currency }})</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0" style="min-width: 720px;">
                    <thead class="table-light">
                        <tr>
                            <th>Store</th>
                            <th>Code</th>
                            <th>Received</th>
                            <th>Completed</th>
                            <th>Rejected Handovers</th>
                            <th>Platform Commission</th>
                            <th>Net Store Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stores as $st)
                            <tr>
                                <td class="fw-semibold">{{ $st['name'] }}</td>
                                <td><code>{{ $st['store_code'] }}</code></td>
                                <td>{{ $st['received'] }}</td>
                                <td><span class="badge bg-success-subtle text-success">{{ $st['completed'] }}</span></td>
                                <td><span class="badge bg-{{ $st['rejected'] > 0 ? 'danger' : 'light text-dark' }}">{{ $st['rejected'] }}</span></td>
                                <td>{{ $st['commission_formatted'] }}</td>
                                <td class="fw-bold">{{ $st['net_earnings_formatted'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No stores found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
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
    new ApexCharts(document.getElementById('repStorePerfChart'), {
        chart: { type: 'bar', height: 260, toolbar: { show: false } },
        series: [
            { name: 'Received', data: @json($stores->pluck('received')->values()) },
            { name: 'Completed', data: @json($stores->pluck('completed')->values()) }
        ],
        xaxis: { categories: @json($stores->pluck('name')->values()) },
        colors: ['#3B82F6', '#16A34A']
    }).render();
});
</script>
@endpush
