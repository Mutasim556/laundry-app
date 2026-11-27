<div class="tm-table-scroll">
    <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
            <tr>
                <th>Allocation Ref</th>
                <th>Delivery Person</th>
                <th>Qty</th>
                <th>Tag Range</th>
                <th>Notes</th>
                <th>Allocated By</th>
                <th>Allocation Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($allocations as $alloc)
                @php
                    $firstCode = $alloc->tags->first()?->tag_code;
                    $lastCode = $alloc->tags->last()?->tag_code;
                @endphp
                <tr>
                    <td><code>{{ $alloc->allocation_reference }}</code></td>
                    <td class="fw-semibold">{{ $alloc->deliveryPerson?->full_name ?? '—' }}</td>
                    <td><span class="badge bg-primary-subtle text-primary">{{ $alloc->quantity }} tags</span></td>
                    <td class="text-nowrap">
                        @if($firstCode)
                            <code>{{ $firstCode }}</code> @if($lastCode && $lastCode !== $firstCode) &rarr; <code>{{ $lastCode }}</code> @endif
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $alloc->notes ?: '—' }}</td>
                    <td>{{ $alloc->allocatedByAdmin?->name ?? 'Admin' }}</td>
                    <td class="text-nowrap">{{ $alloc->allocated_at?->format('d M Y, H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="tm-empty-state">
                            <div class="tm-empty-icon"><i data-feather="user-plus" style="width:22px;height:22px;"></i></div>
                            <div class="fw-semibold text-dark">No allocations found.</div>
                            <div class="small">Click "Allocate Tags to Delivery Person" above to assign available tags.</div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($allocations->hasPages())
    <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2 fg-alloc-pagination">
        <div class="small text-muted">Showing {{ $allocations->firstItem() }}–{{ $allocations->lastItem() }} of {{ $allocations->total() }} allocations</div>
        <div>{!! $allocations->links() !!}</div>
    </div>
@endif
