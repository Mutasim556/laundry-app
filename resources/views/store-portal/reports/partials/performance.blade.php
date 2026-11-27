<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="sp-card p-3 sp-anim-card">
            <div class="small text-muted">Avg Processing Time</div>
            <div class="fs-4 fw-bold mt-1" style="color: #171717;">{{ $stats['avg_processing_hours'] !== null ? $stats['avg_processing_hours'] . ' hrs' : 'N/A' }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sp-card p-3 sp-anim-card">
            <div class="small text-muted">Store Completed</div>
            <div class="fs-4 fw-bold mt-1 text-success">{{ $stats['completed'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sp-card p-3 sp-anim-card">
            <div class="small text-muted">Store Received</div>
            <div class="fs-4 fw-bold mt-1 text-info-emphasis">{{ $stats['received'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sp-card p-3 sp-anim-card">
            <div class="small text-muted">Confirmed Handovers</div>
            <div class="fs-4 fw-bold mt-1 text-primary">{{ $stats['handovers'] }}</div>
        </div>
    </div>
</div>

<div class="sp-card p-3 sp-anim-card">
    <h6 class="fw-bold mb-2" style="color: #171717;">Service Category &amp; Unit Breakdown — {{ $period['label'] }}</h6>
    <div class="text-muted small mb-3">Weight-based (kg) and piece-based (items) services are reported separately without mixing units.</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Category</th>
                    <th>Pricing Mode</th>
                    <th class="text-end">Line Items</th>
                    <th class="text-end">Total Volume</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                    <tr class="small">
                        <td class="fw-semibold">{{ $cat->category_name ?: 'Uncategorized' }}</td>
                        <td><span class="badge bg-light text-dark border text-capitalize">{{ $cat->pricing_mode }}</span></td>
                        <td class="text-end">{{ $cat->line_count }}</td>
                        <td class="text-end fw-bold">
                            @if($cat->pricing_mode === 'weight')
                                {{ number_format((float) $cat->total_kg, 2) }} kg
                            @else
                                {{ (int) $cat->total_qty }} items
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-4 text-muted">No operational service items recorded in {{ $period['label'] }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
