@extends('delivery-person-portal.layouts.app')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="dp-card p-3 h-100" style="background-color: #FFF8E5; border-color: #FDE68A;">
            <div class="small fw-semibold text-warning-emphasis">Collection Jobs</div>
            <div class="fs-4 fw-bold text-dark">{{ $collectionJobsCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card p-3 h-100" style="background-color: #EFF6FF; border-color: #BFDBFE;">
            <div class="small fw-semibold text-primary">Final Delivery Jobs</div>
            <div class="fs-4 fw-bold text-dark">{{ $finalDeliveryJobsCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card p-3 h-100" style="background-color: #FFFBEB; border-color: #FDE68A;">
            <div class="small fw-semibold text-warning-emphasis">Pending Collections</div>
            <div class="fs-4 fw-bold text-dark">{{ $pendingCollectionsCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card p-3 h-100" style="background-color: #ECFDF5; border-color: #A7F3D0;">
            <div class="small fw-semibold text-success">Completed Jobs</div>
            <div class="fs-4 fw-bold text-dark">{{ $completedJobsCount }}</div>
        </div>
    </div>
</div>

<!-- Separate Custody vs Earnings Balances -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="dp-card p-3 h-100" style="background-color: #FFF1F2; border-color: #FECACA;">
            <div class="small fw-semibold text-danger">Cash in Hand (Business Custody)</div>
            <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['cash_in_hand_minor'], $currency) }}</div>
            <div class="small text-muted" style="font-size: 0.72rem;">Customer cash awaiting admin handover</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="dp-card p-3 h-100" style="background-color: #F5F3FF; border-color: #DDD6FE;">
            <div class="small fw-semibold" style="color: #6D28D9;">Online Transfers Awaiting Verification</div>
            <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['online_transfer_awaiting_verification_minor'], $currency) }}</div>
            <div class="small text-muted" style="font-size: 0.72rem;">Pending admin confirmation</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="dp-card p-3 h-100" style="background-color: #FFF8E5; border-color: #FDE68A;">
            <div class="small fw-semibold text-warning-emphasis">Pending Admin Settlement</div>
            <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['cash_in_hand_minor'], $currency) }}</div>
            <div class="small text-muted" style="font-size: 0.72rem;">Total settled: {{ $currencyService->formatMinor($cashSummary['total_settled_minor'], $currency) }}</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="dp-card p-3 h-100" style="background-color: #ECFDF5; border-color: #A7F3D0;">
            <div class="small fw-semibold text-success">Delivery Person Earnings Balance</div>
            <div class="fs-4 fw-bold text-success">{{ $currencyService->formatMinor($earningsBalanceMinor, $currency) }}</div>
            <div class="small text-muted" style="font-size: 0.72rem;">Personal earned delivery pay &amp; tips</div>
        </div>
    </div>
</div>

<div class="dp-card p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0">Recent Assigned Jobs</h6>
        <a href="{{ route('delivery-person.bookings.index') }}" class="btn btn-sm btn-light">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Booking</th>
                    <th>Role</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentAssignments as $asg)
                    @if($asg->booking)
                        <tr>
                            <td class="fw-bold">{{ $asg->booking->reference }}</td>
                            <td>
                                <span class="badge {{ $asg->assignment_type === \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION ? 'bg-warning-subtle text-warning-emphasis' : 'bg-info-subtle text-info-emphasis' }}">
                                    {{ $asg->type_label }}
                                </span>
                            </td>
                            <td>{{ $asg->booking->customer_name }}</td>
                            <td>
                                @php
                                    $isColDone = $asg->assignment_type === \Modules\Admin\Models\DeliveryAssignment::TYPE_CUSTOMER_COLLECTION
                                        && (in_array(\App\Models\Booking::STATUS_STORE_RECEIVED, $asg->booking->getCompletedOperationalStatuses(), true)
                                            || $asg->status === \Modules\Admin\Models\DeliveryAssignment::STATUS_COMPLETED);
                                @endphp
                                @if($isColDone)
                                    <span class="badge rounded-pill px-2 py-1" style="background-color: #DCFCE7; color: #15803D; border: 1px solid #86EFAC;">&check; Completed</span>
                                @else
                                    <span class="badge bg-light text-dark border">{{ \App\Models\Booking::STATUS_LABELS[$asg->booking->booking_status] ?? ucfirst($asg->booking->booking_status) }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('delivery-person.bookings.show', $asg->booking) }}" class="btn btn-xs dp-btn-yellow px-2 py-1">Open</a>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No active assignments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
