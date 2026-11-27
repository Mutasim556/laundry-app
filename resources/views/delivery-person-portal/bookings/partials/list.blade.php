<div class="row g-3">
    @forelse($assignments as $idx => $asg)
        @php
            $booking = $asg->booking;
            $isCol = $asg->isCollectionJob();
            $jobStatus = $asg->getJobStatusDisplay();
            $storeName = $asg->store?->name ?? $booking?->assignedStore?->name ?? 'Assigned Store';
            $addrSnap = is_array($booking?->address_snapshot) ? $booking->address_snapshot : [];
            $customerLocation = trim((string) ($addrSnap['hotel_name'] ?? $addrSnap['full_address'] ?? $addrSnap['address_line_1'] ?? 'Customer / Hotel'));
            $customerPostcode = $isCol
                ? ($asg->pickup_postcode ?: ($addrSnap['postcode'] ?? ''))
                : ($asg->destination_postcode ?: ($addrSnap['postcode'] ?? ''));

            $fromLabel = $isCol ? ($customerLocation !== '' ? $customerLocation : 'Customer / Hotel') : $storeName;
            $toLabel = $isCol ? $storeName : ($customerLocation !== '' ? $customerLocation : 'Customer / Hotel');

            $isCredited = $asg->earnings_status === 'credited';
            $ledgerEarnedMinor = $isCredited && $asg->relationLoaded('ledgerEntries')
                ? (int) $asg->ledgerEntries->where('entry_type', 'credit')->sum('amount_minor')
                : 0;
            $earningMinor = $ledgerEarnedMinor > 0
                ? $ledgerEarnedMinor
                : ((int) $asg->rate_minor + (int) $asg->tip_share_minor);
            $jobCurrency = $asg->currency ?: ($booking?->currency ?: $currencyService->code());

            $scheduledDt = $asg->scheduled_at ?? $asg->assigned_at ?? $asg->created_at;
            $scheduledDateStr = $scheduledDt ? $scheduledDt->timezone(config('app.timezone', 'UTC'))->format('d M Y') : 'Not scheduled';
            $scheduledTimeStr = $scheduledDt ? $scheduledDt->timezone(config('app.timezone', 'UTC'))->format('h:i A') : '';

            $jobUrl = $isCol
                ? route('delivery-person.collection-jobs.show', $asg)
                : route('delivery-person.final-delivery-jobs.show', $asg);
        @endphp
        <div class="col-12 col-md-6">
            <div class="dp-card dp-bk-card p-3 h-100 d-flex flex-column justify-content-between"
                 style="animation-delay: {{ min($idx * 35, 240) }}ms; {{ $isCol
                    ? 'background: linear-gradient(180deg, #FFFDF7 0%, #FFFFFF 100%); border-color: #FDE68A; border-left: 4px solid #FFC629;'
                    : 'background: linear-gradient(180deg, #F8FBFF 0%, #FFFFFF 100%); border-color: #BFDBFE; border-left: 4px solid #3B82F6;' }}">
                <div>
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="min-w-0">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <span class="badge rounded-pill px-2 py-1 fw-bold d-inline-flex align-items-center gap-1"
                                      style="{{ $isCol ? 'background-color: #FEF3C7; color: #92400E;' : 'background-color: #DBEAFE; color: #1D4ED8;' }}">
                                    @if($isCol)
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg>
                                        COLLECTION &middot; Customer &rarr; Store
                                    @else
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5v8h1"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                                        FINAL DELIVERY &middot; Store &rarr; Customer
                                    @endif
                                </span>
                                <span class="small fw-semibold text-muted">{{ $asg->job_code }}</span>
                            </div>
                            <div class="fw-bold text-dark">Booking: {{ $booking->reference }}</div>
                            <div class="small text-muted">{{ $booking->customer_name }} @if($booking->customer_phone)&bull; {{ $booking->customer_phone }}@endif</div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="small text-muted" style="font-size: 0.72rem;">{{ $isCredited ? 'Earned' : 'Expected Pay' }}</div>
                            <div class="fw-bold {{ $isCredited ? 'text-success' : 'text-dark' }}">
                                {{ $currencyService->formatMinor($earningMinor, $jobCurrency) }}
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 small mb-2">
                        <span class="badge bg-light text-dark border fw-normal d-inline-flex align-items-center gap-1">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            {{ $scheduledDateStr }} @if($scheduledTimeStr)&bull; {{ $scheduledTimeStr }}@endif
                        </span>
                        <span class="badge bg-light text-dark border fw-normal d-inline-flex align-items-center gap-1">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                            {{ $storeName }}
                        </span>
                    </div>

                    <div class="p-2 px-3 rounded-3 bg-white border small mb-2">
                        <div class="mb-1"><span class="text-muted">Pickup:</span> <strong class="text-dark">{{ $fromLabel }}</strong></div>
                        <div class="mb-1"><span class="text-muted">Drop-off:</span> <strong class="text-dark">{{ $toLabel }}</strong></div>
                        @if($customerPostcode !== '')
                            <div><span class="text-muted">{{ $isCol ? 'Pickup Postcode:' : 'Destination Postcode:' }}</span> <strong>{{ $customerPostcode }}</strong></div>
                        @endif
                    </div>

                    @if($isCol && $booking)
                        <div class="small mb-2 text-muted">
                            <strong>Collection OTP:</strong>
                            @if($booking->collection_otp_verified_at)
                                <span class="text-success fw-semibold">Verified</span>
                            @else
                                <span>Pending verification</span>
                            @endif
                        </div>
                    @elseif(!$isCol && $jobStatus['pickup_request_label'])
                        <div class="small mb-2 text-muted">
                            <strong>Pickup Request:</strong>
                            <span class="text-dark fw-semibold">{{ $jobStatus['pickup_request_label'] }}</span>
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2 border-top">
                    <span class="badge rounded-pill px-2 py-1" style="{{ $jobStatus['badge_style'] }}">
                        {{ $jobStatus['label'] }}
                    </span>
                    <a href="{{ $jobUrl }}"
                       class="btn btn-sm fw-bold px-3 d-inline-flex align-items-center gap-1 {{ $isCol ? 'dp-btn-yellow' : 'text-white' }}"
                       style="{{ $isCol ? '' : 'background-color: #3B82F6; border-radius: 10px;' }}">
                        <span>{{ $isCol ? 'View Collection Job' : 'View Final Delivery Job' }}</span>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="dp-card p-5 text-center">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2" style="width: 48px; height: 48px; background: #FFF8E5; color: #B45309;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="fw-bold text-dark mb-1">No booking jobs found for the selected month and filters.</div>
                <div class="small text-muted">Try switching months or resetting your filters.</div>
            </div>
        </div>
    @endforelse
</div>

@if($assignments->hasPages())
    <div class="mt-3 d-flex justify-content-end dp-bk-pagination">
        {{ $assignments->links('pagination::bootstrap-5') }}
    </div>
@endif
