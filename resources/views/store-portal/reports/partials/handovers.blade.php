<div class="row g-2 mb-3">
    <div class="col-6 col-md-3"><div class="sp-card p-3 sp-anim-card"><div class="small text-muted">Total Handovers</div><div class="fs-4 fw-bold">{{ $summary['total'] }}</div></div></div>
    <div class="col-6 col-md-3"><div class="sp-card p-3 sp-anim-card"><div class="small text-muted">Pending</div><div class="fs-4 fw-bold text-warning-emphasis">{{ $summary['pending'] }}</div></div></div>
    <div class="col-6 col-md-3"><div class="sp-card p-3 sp-anim-card"><div class="small text-muted">Accepted</div><div class="fs-4 fw-bold text-success">{{ $summary['accepted'] }}</div></div></div>
    <div class="col-6 col-md-3"><div class="sp-card p-3 sp-anim-card"><div class="small text-muted">Rejected</div><div class="fs-4 fw-bold text-danger">{{ $summary['rejected'] }}</div></div></div>
</div>

<div class="sp-card p-3 mb-3 sp-anim-card">
    <h6 class="fw-bold mb-2" style="color: #171717;">Handover Status Breakdown</h6>
    <div id="spRepHoBarChart" style="min-height: 220px;"></div>
</div>

<div class="sp-card p-3 sp-anim-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h6 class="fw-bold mb-0" style="color: #171717;">Handovers — {{ $period['label'] }}</h6>
        <span class="badge rounded-pill bg-light text-dark border">{{ $handovers->total() }} {{ Str::plural('record', $handovers->total()) }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Request Ref</th>
                    <th>Booking</th>
                    <th>Delivery Person</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Accepted / Rejected</th>
                </tr>
            </thead>
            <tbody>
                @forelse($handovers as $ho)
                    <tr class="small">
                        <td class="fw-bold">{{ $ho->request_reference }}</td>
                        <td>{{ $ho->booking?->reference ?? '-' }}</td>
                        <td>{{ $ho->collectionDeliveryPerson?->full_name ?? 'Delivery Person' }}</td>
                        <td><span class="badge bg-light text-dark border text-capitalize">{{ $ho->status }}</span></td>
                        <td class="text-nowrap">{{ $ho->requested_at?->format('d M Y, H:i') ?? '-' }}</td>
                        <td class="text-nowrap">{{ ($ho->accepted_at ?? $ho->rejected_at)?->format('d M Y, H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No handovers in {{ $period['label'] }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($handovers->hasPages())
        <div class="mt-3 sp-rep-ho-pagination">{{ $handovers->links() }}</div>
    @endif
</div>
