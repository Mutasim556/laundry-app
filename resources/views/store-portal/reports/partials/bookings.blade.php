<div class="row g-3 mb-3">
    <div class="col-12 col-lg-8">
        <div class="sp-card p-3 h-100 sp-anim-card">
            <h6 class="fw-bold mb-2" style="color: #171717;">Daily Booking Activity — {{ $period['label'] }}</h6>
            <div id="spRepBookBarChart" style="min-height: 240px;"></div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="sp-card p-3 h-100 sp-anim-card">
            <h6 class="fw-bold mb-2" style="color: #171717;">Stage Distribution</h6>
            <div id="spRepBookDonutChart" style="min-height: 240px;"></div>
        </div>
    </div>
</div>

<div class="sp-card p-3 sp-anim-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h6 class="fw-bold mb-0" style="color: #171717;">Assigned Bookings — {{ $period['label'] }}</h6>
        <span class="badge rounded-pill bg-light text-dark border">{{ $bookings->total() }} {{ Str::plural('booking', $bookings->total()) }}</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Booking Ref</th>
                    <th>Customer</th>
                    <th>Assigned At</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $bk)
                    <tr class="small">
                        <td class="fw-bold text-primary">{{ $bk->reference }}</td>
                        <td>{{ $bk->customer_name ?? 'Customer' }}</td>
                        <td class="text-nowrap">{{ ($bk->currentStoreAssignment?->assigned_at ?? $bk->currentStoreAssignment?->created_at)?->format('d M Y, H:i') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $bk->getStatusLabel() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('store.bookings.show', $bk) }}" class="btn btn-sm btn-outline-dark py-0 px-2">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">No bookings assigned in {{ $period['label'] }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($bookings->hasPages())
        <div class="mt-3 sp-rep-bk-pagination">{{ $bookings->links() }}</div>
    @endif
</div>
