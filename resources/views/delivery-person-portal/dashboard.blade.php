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

<!-- Store Pickup Requests -->
<div class="dp-card p-3 mb-3" style="background-color: #FFF8E5; border-color: #FDE68A; border-left: 4px solid #FFC629;">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0" style="color: #171717;"><i class="mdi mdi-package-variant-closed text-warning-emphasis me-1"></i>Store Pickup Requests</h6>
        <span class="badge rounded-pill px-2 py-1" style="background-color: #FFC629; color: #171717;">{{ ($pendingPickupRequests ?? collect())->count() }} Pending</span>
    </div>
    @if(($pendingPickupRequests ?? collect())->isNotEmpty())
        <div class="row g-2">
            @foreach($pendingPickupRequests as $pr)
                @php
                    $prBooking = $pr->booking;
                    $prStore = $pr->store;
                    $prAsg = $pr->deliveryAssignment;
                    $prAddr = is_array($prBooking?->address_snapshot) ? $prBooking->address_snapshot : [];
                    $prExpectedMinor = (int) ($prAsg?->rate_minor ?? 0) + (int) ($prAsg?->tip_share_minor ?? 0);
                @endphp
                <div class="col-12 col-md-6">
                    <div class="p-3 rounded-3 bg-white border h-100 d-flex flex-column justify-content-between" style="border-color: #E2E8F0;">
                        <div>
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <span class="badge rounded-pill mb-1" style="background:#EFF6FF;color:#1D4ED8;">Final Delivery</span>
                                    <div class="fw-bold" style="color:#171717;">{{ $prBooking?->reference ?? 'Booking' }}</div>
                                </div>
                                <span class="badge rounded-pill" style="background:#FEF3C7;color:#92400E;">{{ $pr->getStatusLabel() }}</span>
                            </div>
                            <div class="small mb-1"><strong>Store:</strong> {{ $prStore?->name ?? 'Assigned Store' }}</div>
                            <div class="small text-muted mb-1"><strong>Store Address:</strong> {{ $prStore?->full_address ?: ($prStore?->address_line_1 ?: 'Not provided') }}</div>
                            <div class="small text-muted mb-1"><strong>Store Phone:</strong> {{ $prStore?->phone ?: 'Not provided' }}</div>
                            <div class="small mb-1"><strong>Customer Area / Postcode:</strong> {{ $prAddr['city'] ?? ($prAddr['area'] ?? 'Customer Address') }} {{ !empty($prAddr['postcode']) ? '(' . $prAddr['postcode'] . ')' : '' }}</div>
                            <div class="small text-muted mb-2">
                                <strong>Requested:</strong> {{ $pr->requested_at?->format('d M Y, H:i') }}
                                @if($prExpectedMinor > 0)
                                    &bull; <strong class="text-success">Expected Earning: {{ $currencyService->formatMinor($prExpectedMinor, $prAsg?->currency ?? $currency) }}</strong>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                            <form method="POST" action="{{ route('delivery-person.pickup-requests.accept', $pr) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm text-white fw-bold px-3" style="background-color:#16A34A;">Accept Request</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-danger fw-semibold px-3 dp-open-decline-btn"
                                    data-action="{{ route('delivery-person.pickup-requests.decline', $pr) }}"
                                    data-ref="{{ $prBooking?->reference ?? $pr->request_reference }}"
                                    data-bs-toggle="modal" data-bs-target="#dpDeclinePickupModal">
                                Decline Request
                            </button>
                            @if($prBooking)
                                <a href="{{ route('delivery-person.bookings.show', $prBooking) }}" class="btn btn-sm btn-light border ms-auto">Details</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="small text-muted bg-white rounded-3 p-3 border">No pending Store Pickup Requests at the moment.</div>
    @endif
</div>

<!-- Modal: Decline Pickup Request -->
<div class="modal fade" id="dpDeclinePickupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="dpDeclinePickupForm" class="modal-content" style="border-radius: 16px;">
            @csrf
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-bold">Decline Store Pickup Request (<span id="dpDeclineRefText"></span>)</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <label for="dpDeclineReasonInput" class="form-label small fw-semibold mb-1">Reason for declining <span class="text-danger">*</span></label>
                <textarea id="dpDeclineReasonInput" name="decline_reason" class="form-control form-control-sm" rows="3" required minlength="3" maxlength="500" placeholder="Provide a brief reason..."></textarea>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-danger fw-bold">Submit Decline</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.dp-open-decline-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('dpDeclinePickupForm').setAttribute('action', btn.getAttribute('data-action'));
            document.getElementById('dpDeclineRefText').textContent = btn.getAttribute('data-ref') || '';
        });
    });
});
</script>

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
                        @php
                            $jobStatus = $asg->getJobStatusDisplay();
                            $jobUrl = $asg->isCollectionJob()
                                ? route('delivery-person.collection-jobs.show', $asg)
                                : route('delivery-person.final-delivery-jobs.show', $asg);
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $asg->booking->reference }}</div>
                                <div class="small text-muted" style="font-size: 0.72rem;">{{ $asg->job_code }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $asg->isCollectionJob() ? 'bg-warning-subtle text-warning-emphasis' : 'bg-info-subtle text-info-emphasis' }}">
                                    {{ $asg->type_label }}
                                </span>
                            </td>
                            <td>{{ $asg->booking->customer_name }}</td>
                            <td>
                                <span class="badge rounded-pill px-2 py-1" style="{{ $jobStatus['badge_style'] }}">{{ $jobStatus['label'] }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ $jobUrl }}" class="btn btn-xs dp-btn-yellow px-2 py-1">Open Job</a>
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
