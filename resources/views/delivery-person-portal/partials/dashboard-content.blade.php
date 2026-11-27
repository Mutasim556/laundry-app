<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="dp-card dp-dash-anim p-3 h-100 d-flex align-items-center justify-content-between gap-2" style="background-color: #FFF8E5; border-color: #FDE68A; animation-delay: 30ms;">
            <div>
                <div class="small fw-semibold text-warning-emphasis">Collection Jobs</div>
                <div class="fs-4 fw-bold text-dark">{{ $collectionJobsCount }}</div>
            </div>
            <span class="dp-kpi-icon" style="background:#FEF3C7; color:#B45309;" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
            </span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card dp-dash-anim p-3 h-100 d-flex align-items-center justify-content-between gap-2" style="background-color: #EFF6FF; border-color: #BFDBFE; animation-delay: 70ms;">
            <div>
                <div class="small fw-semibold text-primary">Final Delivery Jobs</div>
                <div class="fs-4 fw-bold text-dark">{{ $finalDeliveryJobsCount }}</div>
            </div>
            <span class="dp-kpi-icon" style="background:#DBEAFE; color:#1D4ED8;" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5v8h1"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
            </span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card dp-dash-anim p-3 h-100 d-flex align-items-center justify-content-between gap-2" style="background-color: #FFFBEB; border-color: #FDE68A; animation-delay: 110ms;">
            <div>
                <div class="small fw-semibold text-warning-emphasis">Pending Collections</div>
                <div class="fs-4 fw-bold text-dark">{{ $pendingCollectionsCount }}</div>
            </div>
            <span class="dp-kpi-icon" style="background:#FEF3C7; color:#D97706;" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="dp-card dp-dash-anim p-3 h-100 d-flex align-items-center justify-content-between gap-2" style="background-color: #ECFDF5; border-color: #A7F3D0; animation-delay: 150ms;">
            <div>
                <div class="small fw-semibold text-success">Completed Jobs</div>
                <div class="fs-4 fw-bold text-dark">{{ $completedJobsCount }}</div>
            </div>
            <span class="dp-kpi-icon" style="background:#D1FAE5; color:#15803D;" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </span>
        </div>
    </div>
</div>

<!-- Separate Custody vs Earnings Balances -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="dp-card dp-dash-anim p-3 h-100" style="background-color: #FFF1F2; border-color: #FECACA; animation-delay: 180ms;">
            <div class="d-flex align-items-start justify-content-between gap-2">
                <div>
                    <div class="small fw-semibold text-danger">Cash in Hand (Business Custody)</div>
                    <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['cash_in_hand_minor'], $currency) }}</div>
                </div>
                <span class="dp-kpi-icon" style="background:#FFE4E6; color:#BE123C;" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
                </span>
            </div>
            <div class="small text-muted mt-1" style="font-size: 0.72rem;">Customer cash awaiting admin handover</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="dp-card dp-dash-anim p-3 h-100" style="background-color: #F5F3FF; border-color: #DDD6FE; animation-delay: 220ms;">
            <div class="d-flex align-items-start justify-content-between gap-2">
                <div>
                    <div class="small fw-semibold" style="color: #6D28D9;">Online Transfers Awaiting Verification</div>
                    <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['online_transfer_awaiting_verification_minor'], $currency) }}</div>
                </div>
                <span class="dp-kpi-icon" style="background:#EDE9FE; color:#6D28D9;" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22h18"/><path d="M6 18v-7"/><path d="M10 18v-7"/><path d="M14 18v-7"/><path d="M18 18v-7"/><polygon points="12 2 20 7 4 7"/></svg>
                </span>
            </div>
            <div class="small text-muted mt-1" style="font-size: 0.72rem;">Pending admin confirmation</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="dp-card dp-dash-anim p-3 h-100" style="background-color: #FFF8E5; border-color: #FDE68A; animation-delay: 260ms;">
            <div class="d-flex align-items-start justify-content-between gap-2">
                <div>
                    <div class="small fw-semibold text-warning-emphasis">Pending Admin Settlement</div>
                    <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor($cashSummary['cash_in_hand_minor'], $currency) }}</div>
                </div>
                <span class="dp-kpi-icon" style="background:#FEF3C7; color:#B45309;" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </span>
            </div>
            <div class="small text-muted mt-1" style="font-size: 0.72rem;">Total settled: {{ $currencyService->formatMinor($cashSummary['total_settled_minor'], $currency) }}</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="dp-card dp-dash-anim p-3 h-100" style="background-color: #ECFDF5; border-color: #A7F3D0; animation-delay: 300ms;">
            <div class="d-flex align-items-start justify-content-between gap-2">
                <div>
                    <div class="small fw-semibold text-success">Delivery Person Earnings Balance</div>
                    <div class="fs-4 fw-bold text-success">{{ $currencyService->formatMinor($earningsBalanceMinor, $currency) }}</div>
                </div>
                <span class="dp-kpi-icon" style="background:#D1FAE5; color:#15803D;" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                </span>
            </div>
            <div class="small text-muted mt-1" style="font-size: 0.72rem;">Personal earned delivery pay &amp; tips</div>
        </div>
    </div>
</div>

<!-- Store Pickup Requests -->
<div class="dp-card dp-dash-anim p-3 mb-3" style="background-color: #FFF8E5; border-color: #FDE68A; border-left: 4px solid #FFC629; animation-delay: 330ms;">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0 d-inline-flex align-items-center gap-2" style="color: #171717;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#B45309" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
            <span>Store Pickup Requests</span>
        </h6>
        <span class="badge rounded-pill px-2 py-1 d-inline-flex align-items-center gap-1" style="background-color: #FFC629; color: #171717;">
            @if(($pendingPickupRequests ?? collect())->isNotEmpty())
                <span class="dp-pulse-dot"></span>
            @endif
            {{ ($pendingPickupRequests ?? collect())->count() }} Pending
        </span>
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
                                    <span class="badge rounded-pill mb-1 d-inline-flex align-items-center gap-1" style="background:#EFF6FF;color:#1D4ED8;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5v8h1"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                                        Final Delivery
                                    </span>
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
                                <button type="submit" class="btn btn-sm text-white fw-bold px-3 d-inline-flex align-items-center gap-1" style="background-color:#16A34A;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    Accept Request
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-danger fw-semibold px-3 dp-open-decline-btn d-inline-flex align-items-center gap-1"
                                    data-action="{{ route('delivery-person.pickup-requests.decline', $pr) }}"
                                    data-ref="{{ $prBooking?->reference ?? $pr->request_reference }}"
                                    data-bs-toggle="modal" data-bs-target="#dpDeclinePickupModal">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                Decline Request
                            </button>
                            @if($prBooking)
                                <a href="{{ route('delivery-person.bookings.show', $prBooking) }}" class="btn btn-sm btn-light border ms-auto d-inline-flex align-items-center gap-1">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Details
                                </a>
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

<div class="dp-card dp-dash-anim p-3" style="animation-delay: 360ms;">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0 d-inline-flex align-items-center gap-2">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
            <span>Recent Assigned Jobs</span>
        </h6>
        <a href="{{ route('delivery-person.bookings.index') }}" class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1">
            <span>View All</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
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
                                <a href="{{ $jobUrl }}" class="btn btn-xs dp-btn-yellow px-2 py-1 d-inline-flex align-items-center gap-1">
                                    <span>Open Job</span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                </a>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No active assignments found for {{ $selectedMonthLabel ?? 'this period' }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
