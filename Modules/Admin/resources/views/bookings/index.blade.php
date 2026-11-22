@extends('admin::layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="p-4 rounded-4 mb-4 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="mb-1 fw-bold text-white">Booking Management</h2>
                <p class="mb-0 text-white-50 small">Search, inspect, operationally adjust services/weights/fees, update statuses, or cancel bookings without altering customer snapshots.</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <div class="small text-muted text-uppercase fw-semibold">Total Bookings</div>
                <div class="fs-3 fw-bold">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <div class="small text-muted text-uppercase fw-semibold">Active Operational</div>
                <div class="fs-3 fw-bold text-primary">{{ $stats['active'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <div class="small text-muted text-uppercase fw-semibold">Completed</div>
                <div class="fs-3 fw-bold text-success">{{ $stats['completed'] }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <div class="small text-muted text-uppercase fw-semibold">Cancelled</div>
                <div class="fs-3 fw-bold text-danger">{{ $stats['cancelled'] }}</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.bookings.index') }}" class="row g-2 mb-4">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Search</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Reference, name, email, phone...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Booking Date</label>
                    <input type="date" name="booking_date" class="form-control" value="{{ request('booking_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Collection Date</label>
                    <input type="date" name="collection_date" class="form-control" value="{{ request('collection_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Delivery Date</label>
                    <input type="date" name="delivery_date" class="form-control" value="{{ request('delivery_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Booking Status</label>
                    <select name="booking_status" class="form-select">
                        <option value="">All Booking Statuses</option>
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}" @selected(request('booking_status') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Payment Status</label>
                    <select name="payment_status" class="form-select">
                        <option value="">All Payment Statuses</option>
                        @foreach(['pending', 'authorized', 'paid', 'failed', 'refunded', 'partial_refund'] as $pStatus)
                            <option value="{{ $pStatus }}" @selected(request('payment_status') === $pStatus)>{{ ucfirst(str_replace('_', ' ', $pStatus)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Payment Gateway</label>
                    <select name="payment_gateway" class="form-select">
                        <option value="">All Gateways</option>
                        @foreach(['stripe' => 'Stripe', 'paypal' => 'PayPal', 'cod' => 'Cash on Delivery'] as $gwKey => $gwLabel)
                            <option value="{{ $gwKey }}" @selected(request('payment_gateway') === $gwKey)>{{ $gwLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary px-4">Filter</button>
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Schedule</th>
                            <th>Booking Status</th>
                            <th>Payment</th>
                            <th>Total</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            @php
                                $schedule = is_array($booking->schedule_snapshot) ? $booking->schedule_snapshot : [];
                                $collectionDate = $schedule['collection_date'] ?? $schedule['pickup_date'] ?? '-';
                                $deliveryDate = $schedule['delivery_date'] ?? '-';
                                $displayMinor = $booking->final_amount_minor ?? $booking->estimated_amount_minor ?? 0;
                                $isTerminal = in_array($booking->booking_status, [\App\Models\Booking::STATUS_CANCELLED, \App\Models\Booking::STATUS_COMPLETED], true);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $booking->reference }}</div>
                                    <div class="small text-muted">{{ $booking->created_at?->format('d M Y H:i') }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $booking->customer_name }}</div>
                                    <div class="small text-muted">{{ $booking->customer_email }}</div>
                                    @if($booking->customer_phone)
                                        <div class="small text-muted">{{ $booking->customer_phone }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    <div><strong>Collect:</strong> {{ $collectionDate }}</div>
                                    <div><strong>Deliver:</strong> {{ $deliveryDate }}</div>
                                </td>
                                <td>
                                    @php
                                        $statusBadge = match($booking->booking_status) {
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            'confirmed' => 'bg-primary',
                                            default => 'bg-info text-dark',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusBadge }}">{{ $statusLabels[$booking->booking_status] ?? ucfirst($booking->booking_status) }}</span>
                                </td>
                                <td class="small">
                                    <div class="text-uppercase fw-semibold">{{ $booking->payment_gateway }}</div>
                                    <span class="badge {{ $booking->payment_status === 'paid' ? 'bg-success' : ($booking->payment_status === 'failed' ? 'bg-danger' : 'bg-secondary') }}">
                                        {{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $currencyService->formatMinor((int) $displayMinor, $booking->currency) }}</div>
                                    @if($booking->final_amount_minor === null)
                                        <div class="small text-warning">Pending Weight/Qty</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                        @can('manage-bookings', 'admin')
                                            @if(!$isTerminal)
                                                <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-sm btn-primary">Manage</a>
                                            @endif
                                        @endcan
                                        @can('cancel-bookings', 'admin')
                                            @if(!$isTerminal)
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#cancelBookingModal"
                                                    data-booking-id="{{ $booking->id }}"
                                                    data-booking-ref="{{ $booking->reference }}"
                                                    data-cancel-url="{{ route('admin.bookings.cancel', $booking) }}">
                                                    Cancel
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No bookings matched your filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $bookings->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Cancel Booking Modal -->
<div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="cancelBookingForm" action="">
            @csrf
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Cancel Booking <span id="cancelBookingRef"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">The booking record and payment history will be preserved. The customer will receive an email notification.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" required minlength="3" placeholder="Enter reason for cancellation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('cancelBookingModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        document.getElementById('cancelBookingRef').textContent = button.getAttribute('data-booking-ref') || '';
        document.getElementById('cancelBookingForm').setAttribute('action', button.getAttribute('data-cancel-url') || '');
    });
});
</script>
@endpush
