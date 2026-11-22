@extends('admin::layouts.app')

@section('title', 'Delivery Activity History - Admin Panel')

@section('content')
<div class="saas-page-shell py-4 px-3 px-md-4">
    <div class="saas-header-card">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h1 class="saas-header-title">Delivery Activity History</h1>
                <p class="saas-header-subtitle">Chronological From &rarr; To assignment logs, timestamps, and earnings</p>
            </div>
            <div>
                <a href="{{ route('admin.delivery-persons.index') }}" class="btn btn-outline-secondary rounded-3 fw-semibold">Back to Couriers</a>
            </div>
        </div>
    </div>

    <div class="saas-surface-card">
        <form method="GET" action="{{ route('admin.delivery-activity.index') }}" class="saas-filter-bar">
            <div class="row g-2 align-items-end">
                <div class="col-md-2"><label class="form-label small fw-semibold text-muted mb-1">Booking Ref</label><input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="LND-2026-..."></div>
                <div class="col-md-2"><label class="form-label small fw-semibold text-muted mb-1">Courier</label><select name="delivery_person_id" class="form-select form-select-sm"><option value="">All Couriers</option>@foreach($deliveryPersons as $dp)<option value="{{ $dp->id }}" @selected((string) request('delivery_person_id') === (string) $dp->id)>{{ $dp->full_name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold text-muted mb-1">Type</label><select name="assignment_type" class="form-select form-select-sm"><option value="">All Types</option><option value="customer_collection" @selected(request('assignment_type') === 'customer_collection')>Customer Collection</option><option value="final_delivery" @selected(request('assignment_type') === 'final_delivery')>Final Delivery</option></select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold text-muted mb-1">Store</label><select name="store_id" class="form-select form-select-sm"><option value="">All Stores</option>@foreach($stores as $st)<option value="{{ $st->id }}" @selected((string) request('store_id') === (string) $st->id)>{{ $st->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label small fw-semibold text-muted mb-1">Postcode</label><input type="text" name="postcode" value="{{ request('postcode') }}" class="form-control form-control-sm" placeholder="e.g. M1 2PF"></div>
                <div class="col-md-2 d-flex gap-1"><button type="submit" class="btn btn-sm btn-saas-primary flex-grow-1">Filter</button><a href="{{ route('admin.delivery-activity.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a></div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table saas-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Booking</th>
                        <th>Courier</th>
                        <th>Type</th>
                        <th>From &rarr; To</th>
                        <th>Timestamps</th>
                        <th>Status</th>
                        <th>Payment Earned</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $act)
                        <tr>
                            <td>
                                @if($act->booking)
                                    <a href="{{ route('admin.bookings.show', $act->booking) }}" class="saas-code-pill text-decoration-none">{{ $act->booking->reference }}</a>
                                @endif
                            </td>
                            <td>
                                @if($act->deliveryPerson)
                                    <a href="{{ route('admin.delivery-persons.show', $act->deliveryPerson) }}" class="fw-semibold text-dark text-decoration-none">{{ $act->deliveryPerson->full_name }}</a>
                                @endif
                            </td>
                            <td>{{ $act->type_label }}</td>
                            <td class="small">
                                <div><strong>FROM:</strong> {{ $act->pickup_location }}</div>
                                <div><strong>TO:</strong> {{ $act->destination_location }}</div>
                            </td>
                            <td class="small text-nowrap">
                                <div><strong>Assigned:</strong> {{ $act->assigned_at?->format('d M Y H:i') ?? '—' }}</div>
                                <div><strong>Picked Up:</strong> {{ $act->picked_up_at?->format('d M Y H:i') ?? '—' }}</div>
                                <div><strong>{{ $act->assignment_type === 'customer_collection' ? 'Handover:' : 'Delivered:' }}</strong> {{ ($act->completed_at ?? $act->handed_over_at)?->format('d M Y H:i') ?? '—' }}</div>
                            </td>
                            <td><span class="saas-status-badge {{ in_array($act->status, ['completed','handed_over']) ? 'active' : 'pending' }}">{{ $act->status_label }}</span></td>
                            <td class="fw-bold text-nowrap">
                                {{ $currencyService->formatMinor($act->rate_minor, $act->currency, true) }}
                                <div class="small text-muted fw-normal">{{ ucfirst(str_replace('_', ' ', $act->earnings_status)) }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No delivery activity found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($activities->hasPages())
            <div class="p-3 border-top">{{ $activities->links() }}</div>
        @endif
    </div>
</div>
@endsection
