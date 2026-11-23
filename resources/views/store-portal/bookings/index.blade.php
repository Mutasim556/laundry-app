@extends('store-portal.layouts.app')

@section('title', 'My Bookings')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0" style="color: #171717;">Assigned Bookings</h4>
        <div class="small" style="color: #475569;">All bookings currently assigned to {{ $store->name }}.</div>
    </div>
</div>

<!-- Filters -->
<div class="sp-card p-3 mb-3">
    <div class="d-flex flex-wrap gap-2 mb-2 pb-2 border-bottom">
        @foreach(['all' => 'All', 'new' => 'New', 'in_progress' => 'In Progress', 'completed' => 'Completed'] as $k => $lbl)
            <a href="{{ route('store.bookings.index', array_filter(['status' => $k, 'q' => $filters['q'], 'from_date' => $filters['from_date'], 'to_date' => $filters['to_date'], 'pricing' => $filters['pricing']])) }}"
               class="btn btn-sm {{ $filters['status'] === $k ? 'sp-btn-yellow' : 'btn-outline-secondary' }}"
               style="border-radius: 999px;">
                {{ $lbl }}
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('store.bookings.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold mb-1">Booking Reference</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Search reference...">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">From Date</label>
            <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">To Date</label>
            <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="all" @selected($filters['status'] === 'all')>All</option>
                <option value="new" @selected($filters['status'] === 'new')>New</option>
                <option value="in_progress" @selected($filters['status'] === 'in_progress')>In Progress</option>
                <option value="completed" @selected($filters['status'] === 'completed')>Completed</option>
                <option value="cancelled" @selected($filters['status'] === 'cancelled')>Cancelled</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Finalization</label>
            <select name="pricing" class="form-select form-select-sm">
                <option value="all" @selected($filters['pricing'] === 'all')>All</option>
                <option value="finalized" @selected($filters['pricing'] === 'finalized')>Finalized</option>
                <option value="not_finalized" @selected($filters['pricing'] === 'not_finalized')>Not Finalized</option>
            </select>
        </div>
        <div class="col-12 col-md-1 d-grid">
            <button type="submit" class="btn btn-sm btn-dark">Filter</button>
        </div>
    </form>
</div>

<!-- Bookings Table -->
<div class="sp-card p-3">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Booking Reference</th>
                    <th>Booking Date</th>
                    <th>Collection Schedule</th>
                    <th>Delivery Schedule</th>
                    <th>Customer / Hotel</th>
                    <th>Operational Status</th>
                    <th>Pricing</th>
                    <th>Store Net Amount</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $bk)
                    @php
                        $sched = $bk->schedule_snapshot ?? [];
                        $addr = $bk->address_snapshot ?? [];
                        $earning = $bk->currentStoreEarning;
                    @endphp
                    <tr class="small">
                        <td class="fw-bold text-nowrap">{{ $bk->reference }}</td>
                        <td class="text-nowrap">{{ $bk->created_at?->format('d M Y') }}</td>
                        <td class="text-nowrap">{{ $sched['collection_date'] ?? '-' }}<br><span class="text-muted">{{ $sched['collection_slot'] ?? '' }}</span></td>
                        <td class="text-nowrap">{{ $sched['delivery_date'] ?? '-' }}<br><span class="text-muted">{{ $sched['delivery_slot'] ?? '' }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ $bk->customer_name }}</div>
                            @if(!empty($addr['hotel_name']))
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $addr['hotel_name'] }} @if(!empty($addr['room_number'])) (Rm {{ $addr['room_number'] }}) @endif</div>
                            @elseif(!empty($addr['postcode']))
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $addr['postcode'] }}</div>
                            @endif
                        </td>
                        <td>
                            @php
                                $rowStatus = $storeStatusMap[$bk->id] ?? app(\App\Services\StoreOperationalStatusService::class)->getStatusForBooking($bk);
                            @endphp
                            <span class="badge rounded-pill px-2 py-1" style="{{ $rowStatus['operational_badge_style'] }}">
                                {{ $rowStatus['operational_status_label'] }}
                            </span>
                            @if($rowStatus['store_workflow_finished'])
                                <div class="small text-success fw-semibold mt-1" style="font-size: 0.7rem;">Store Workflow Finished</div>
                            @endif
                        </td>
                        <td>
                            @if($earning)
                                <span class="badge bg-success-subtle text-success">Finalized</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis">Not finalized</span>
                            @endif
                        </td>
                        <td class="fw-bold text-nowrap">
                            @if($earning)
                                {{ $currencyService->formatMinor((int) $earning->net_store_earnings_minor, $earning->currency) }}
                            @else
                                <span class="text-muted fw-normal">Not finalized</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('store.bookings.show', $bk) }}" class="btn btn-sm sp-btn-yellow px-3 text-nowrap">View Details</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No bookings matched your criteria.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($bookings->hasPages())
        <div class="mt-3">{{ $bookings->links() }}</div>
    @endif
</div>
@endsection
