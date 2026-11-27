<div class="row g-3 mb-3">
    <div class="col-12 col-lg-8">
        <div class="dp-card p-3 h-100">
            <h6 class="fw-bold mb-2">{{ $jobType === 'customer_collection' ? 'Collection' : 'Final Delivery' }} Jobs Trend — {{ $period['label'] }}</h6>
            <div id="{{ $barId }}" style="min-height: 230px;"></div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="dp-card p-3 h-100">
            <h6 class="fw-bold mb-2">Status Distribution</h6>
            <div id="{{ $donutId }}" style="min-height: 230px;"></div>
        </div>
    </div>
</div>

<div class="dp-card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">{{ $jobType === 'customer_collection' ? 'Collection Jobs' : 'Final Delivery Jobs' }} — {{ $period['label'] }}</h6>
        <span class="badge rounded-pill bg-light text-dark border">{{ $jobs->total() }} {{ Str::plural('job', $jobs->total()) }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase text-muted">
                    <th>Booking Ref</th>
                    <th>Customer</th>
                    <th>Assigned At</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                    <tr class="small">
                        <td class="fw-bold text-primary">{{ $job->booking?->reference ?? '-' }}</td>
                        <td>{{ $job->booking?->customer_name ?? 'Customer' }}</td>
                        <td class="text-nowrap">{{ ($job->assigned_at ?? $job->created_at)?->format('d M Y, H:i') }}</td>
                        <td><span class="badge bg-light text-dark border text-capitalize">{{ str_replace('_', ' ', $job->status) }}</span></td>
                        <td class="text-end">
                            <a href="{{ $jobType === 'customer_collection' ? route('delivery-person.collection-jobs.show', $job) : route('delivery-person.final-delivery-jobs.show', $job) }}" class="btn btn-sm btn-outline-dark py-0 px-2">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">No {{ $jobType === 'customer_collection' ? 'collection' : 'final delivery' }} jobs in {{ $period['label'] }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($jobs->hasPages())
        <div class="mt-3 {{ $pagClass }}">{{ $jobs->links() }}</div>
    @endif
</div>
