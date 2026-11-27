<!-- 6 Monthly Summary Cards -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="sp-card sp-anim-card p-3 h-100" style="background:#EFF6FF; border-color:#BFDBFE; animation-delay:20ms;">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="small fw-bold text-uppercase" style="color:#1D4ED8; font-size:0.7rem;">Assigned (Month)</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ $monthlyStats['assigned'] }}</div>
                </div>
                <span class="sp-icon-box" style="background:#DBEAFE; color:#1D4ED8;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg></span>
            </div>
            <div class="text-muted mt-1" style="font-size:0.7rem;">By assignment date</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="sp-card sp-anim-card p-3 h-100" style="background:#ECFEFF; border-color:#A5F3FC; animation-delay:55ms;">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="small fw-bold text-uppercase" style="color:#0E7490; font-size:0.7rem;">Store Received</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ $monthlyStats['received'] }}</div>
                </div>
                <span class="sp-icon-box" style="background:#CFFAFE; color:#0E7490;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg></span>
            </div>
            <div class="text-muted mt-1" style="font-size:0.7rem;">Selected month</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="sp-card sp-anim-card p-3 h-100" style="background:#FFF8E5; border-color:#FDE68A; animation-delay:90ms;">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="small fw-bold text-uppercase" style="color:#B45309; font-size:0.7rem;">Processing</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ $monthlyStats['processing'] }}</div>
                </div>
                <span class="sp-icon-box" style="background:#FEF3C7; color:#B45309;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
            </div>
            <div class="text-muted mt-1" style="font-size:0.7rem;">In store washing/care</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="sp-card sp-anim-card p-3 h-100" style="background:#ECFDF5; border-color:#A7F3D0; animation-delay:125ms;">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="small fw-bold text-uppercase" style="color:#15803D; font-size:0.7rem;">Store Completed</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ $monthlyStats['completed'] }}</div>
                </div>
                <span class="sp-icon-box" style="background:#D1FAE5; color:#15803D;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
            </div>
            <div class="text-muted mt-1" style="font-size:0.7rem;">Ready / delivered</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="sp-card sp-anim-card p-3 h-100" style="background:#F5F3FF; border-color:#DDD6FE; animation-delay:160ms;">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="small fw-bold text-uppercase" style="color:#6D28D9; font-size:0.7rem;">Handovers</div>
                    <div class="fs-4 fw-bold text-dark mt-1">{{ $monthlyHandoversCount }}</div>
                </div>
                <span class="sp-icon-box" style="background:#EDE9FE; color:#6D28D9;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17h4V5H2v12h3"/><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5v8h1"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg></span>
            </div>
            <div class="text-muted mt-1" style="font-size:0.7rem;">Confirmed receipts</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="sp-card sp-anim-card p-3 h-100" style="background:#F0FDF4; border-color:#86EFAC; animation-delay:195ms;">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="small fw-bold text-uppercase" style="color:#15803D; font-size:0.7rem;">Month Store Share</div>
                    <div class="fs-5 fw-bold text-success mt-1">
                        @forelse($monthlyEarningsByCurrency as $er)
                            <div>{{ $currencyService->formatMinor($er['net_minor'], $er['currency']) }}</div>
                        @empty
                            0.00
                        @endforelse
                    </div>
                </div>
                <span class="sp-icon-box" style="background:#DCFCE7; color:#15803D;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg></span>
            </div>
            <div class="text-muted mt-1" style="font-size:0.7rem;">By earning date</div>
        </div>
    </div>
</div>

<!-- Daily Assignment Activity Bar Chart & Monthly Activity -->
<div class="row g-3 mb-3">
    <div class="col-12 col-lg-5">
        <div class="sp-card sp-anim-card p-3 h-100">
            <h6 class="fw-bold mb-2">Daily Assigned Activity ({{ $monthLabel }})</h6>
            @if(empty($dailyChart))
                <div class="text-center text-muted small py-4">No assigned bookings recorded for {{ $monthLabel }}.</div>
            @else
                @php $maxDay = max(1, max($dailyChart)); @endphp
                <div class="d-grid gap-2 small">
                    @foreach(array_slice($dailyChart, -8, 8, true) as $dayLabel => $cnt)
                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold text-dark">{{ $dayLabel }}</span>
                                <span class="text-muted">{{ $cnt }} booking(s)</span>
                            </div>
                            <div class="progress" style="height: 8px; background:#F1F5F9; border-radius:999px;">
                                <div class="progress-bar" role="progressbar" style="width: {{ round(($cnt / $maxDay) * 100) }}%; background: linear-gradient(90deg, #FFC629, #F59E0B); border-radius:999px;" aria-valuenow="{{ $cnt }}" aria-valuemin="0" aria-valuemax="{{ $maxDay }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="sp-card sp-anim-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0">Recent Handover Activity ({{ $monthLabel }})</h6>
                <a href="{{ route('store.handovers.index') }}" class="btn btn-xs btn-light border">All Handovers</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 small">
                    <thead class="table-light">
                        <tr><th>Booking</th><th>Delivery Person</th><th>Status</th><th class="text-end">Date</th></tr>
                    </thead>
                    <tbody>
                        @forelse($recentHandovers as $h)
                            <tr>
                                <td class="fw-bold">{{ $h->booking?->reference ?? $h->request_reference }}</td>
                                <td>{{ $h->collectionDeliveryPerson?->full_name ?? '-' }}</td>
                                <td><span class="badge rounded-pill {{ $h->status === 'accepted' ? 'bg-success-subtle text-success' : ($h->status === 'pending' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-danger-subtle text-danger') }}">{{ ucfirst($h->status) }}</span></td>
                                <td class="text-end text-muted text-nowrap">{{ ($h->accepted_at ?? $h->requested_at ?? $h->created_at)?->format('d M, H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No handover activity for {{ $monthLabel }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Selected Month Assigned Bookings -->
<div class="sp-card sp-anim-card p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0">Assigned Bookings in {{ $monthLabel }}</h6>
        <a href="{{ route('store.bookings.index', ['month' => $selectedMonth, 'year' => $selectedYear]) }}" class="btn btn-sm sp-btn-yellow px-3">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Booking Ref</th>
                    <th>Assigned Date</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Store Net</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentBookings as $bk)
                    @php $earning = $bk->currentStoreEarning; @endphp
                    <tr>
                        <td class="fw-bold">{{ $bk->reference }}</td>
                        <td class="text-nowrap text-muted">{{ ($bk->currentStoreAssignment?->assigned_at ?? $bk->created_at)?->format('d M Y, H:i') }}</td>
                        <td>{{ $bk->customer_name }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $bk->getStatusLabel() }}</span></td>
                        <td class="fw-semibold">{{ $earning ? $currencyService->formatMinor((int) $earning->net_store_earnings_minor, $earning->currency) : 'Pending' }}</td>
                        <td class="text-end"><a href="{{ route('store.bookings.show', $bk) }}" class="btn btn-xs btn-outline-dark">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No bookings assigned for {{ $monthLabel }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
