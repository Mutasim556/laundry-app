@extends('admin::layouts.app')

@section('title', 'Delivery Person Performance - Reports')

@section('content')
<div class="container-fluid py-3">
    @include('admin::reports.partials.header', [
        'reportTitle' => 'Delivery Person Performance',
        'reportSubtitle' => 'Track Collection Jobs, Final Delivery Jobs, credited earnings, and COD cash custody per Delivery Person.',
    ])

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold small">Delivery Person Breakdown ({{ $currency }})</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0" style="min-width: 720px;">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Delivery Person</th>
                            <th>Status</th>
                            <th>Collections Completed</th>
                            <th>Final Deliveries Completed</th>
                            <th>Credited Earnings</th>
                            <th>Current COD Custody</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deliveryPersons as $dp)
                            <tr>
                                <td><code>{{ $dp['code'] }}</code></td>
                                <td class="fw-semibold">{{ $dp['name'] }}</td>
                                <td><span class="badge bg-{{ $dp['work_status'] === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($dp['work_status']) }}</span></td>
                                <td>{{ $dp['collection_completed'] }}</td>
                                <td>{{ $dp['final_completed'] }}</td>
                                <td class="fw-bold text-primary">{{ $dp['earnings_formatted'] }}</td>
                                <td class="fw-semibold">{{ $dp['cod_custody_formatted'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No Delivery Persons found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
