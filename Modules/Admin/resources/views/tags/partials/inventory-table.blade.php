<div class="tm-table-scroll">
    <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
            <tr>
                <th style="width: 32px;"><input type="checkbox" class="form-check-input" id="selectAllTags"></th>
                <th>Tag Code</th>
                <th>Batch</th>
                <th>Status</th>
                <th>Delivery Person</th>
                <th>Booking Reference</th>
                <th>Generated Date</th>
                <th>Printed Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tags as $tag)
                @php
                    $statusBadge = match ($tag->status) {
                        'available' => 'success',
                        'allocated' => 'primary',
                        'assigned' => 'info text-dark',
                        'at_store' => 'warning text-dark',
                        'in_final_delivery' => 'dark',
                        'retired' => 'secondary',
                        default => 'danger',
                    };
                @endphp
                <tr>
                    <td><input type="checkbox" class="form-check-input fg-tag-checkbox" value="{{ $tag->id }}"></td>
                    <td>
                        <code class="fw-bold text-dark">{{ $tag->tag_code }}</code>
                        @if($tag->is_reusable)
                            <span class="badge bg-info-subtle text-info ms-1">Reusable</span>
                        @endif
                    </td>
                    <td><code>{{ $tag->batch?->batch_reference ?? '—' }}</code></td>
                    <td><span class="badge bg-{{ $statusBadge }}">{{ $tag->status_label }}</span></td>
                    <td>{{ $tag->currentDeliveryPerson?->full_name ?? '—' }}</td>
                    <td>
                        @if($tag->currentBooking)
                            <a href="{{ route('admin.bookings.show', $tag->currentBooking) }}" class="fw-semibold text-decoration-none">
                                {{ $tag->currentBooking->reference }}
                            </a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-nowrap">{{ $tag->created_at?->format('d M Y, H:i') }}</td>
                    <td>
                        <span class="text-muted">{{ ucfirst(str_replace('_', ' ', $tag->print_status)) }}</span>
                        @if($tag->print_count > 0)
                            <span class="badge bg-light text-dark border">{{ $tag->print_count }}×</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-dark btn-view-tag-details"
                            data-url="{{ route('admin.tags.show', $tag) }}">Details</button>
                        <a href="{{ route('admin.tags.print-preview', ['tag_ids' => [$tag->id]]) }}" class="btn btn-sm btn-outline-primary">Print</a>
                        @if(!$tag->isExceptionStatus())
                            <button type="button" class="btn btn-sm btn-outline-danger btn-open-exception"
                                data-url="{{ route('admin.tags.exception', $tag) }}"
                                data-code="{{ $tag->tag_code }}"
                                data-active="{{ $tag->isActivelyAttached() ? '1' : '0' }}">
                                Exception
                            </button>
                        @elseif($tag->status === \App\Models\LaundryTag::STATUS_RETIRED)
                            <button type="button" class="btn btn-sm btn-outline-success btn-open-return"
                                data-url="{{ route('admin.tags.return-to-stock', $tag) }}"
                                data-code="{{ $tag->tag_code }}">
                                Return Stock
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="tm-empty-state">
                            <div class="tm-empty-icon"><i data-feather="layers" style="width:22px;height:22px;"></i></div>
                            <div class="fw-semibold text-dark">No laundry tags found.</div>
                            <div class="small">Try adjusting your search or filter criteria, or generate a new tag batch.</div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($tags->hasPages())
    <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2 fg-ajax-pagination">
        <div class="small text-muted">Showing {{ $tags->firstItem() }}–{{ $tags->lastItem() }} of {{ $tags->total() }} tags</div>
        <div>{!! $tags->links() !!}</div>
    </div>
@endif
