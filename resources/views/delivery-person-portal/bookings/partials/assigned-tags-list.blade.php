@php
    $canEditTags = $canEditTags ?? false;
    $activeTags = $booking->activeTagAssignments;
@endphp

@if($activeTags->isEmpty())
    <div class="text-muted small py-1">No physical laundry tags attached to this booking yet.</div>
@else
    <div class="d-flex flex-wrap gap-2">
        @foreach($activeTags as $tagAsg)
            <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-light small">
                <div>
                    <code class="fw-bold text-primary">{{ $tagAsg->tag?->tag_code }}</code>
                    @if($tagAsg->package_label)
                        <span class="text-muted">({{ $tagAsg->package_label }})</span>
                    @endif
                    @if($tagAsg->isVerifiedByStore())
                        <span class="badge bg-success-subtle text-success ms-1">&check; Store Verified</span>
                    @endif
                </div>
                @if($canEditTags && ! $tagAsg->isVerifiedByStore())
                    <button type="button"
                            class="btn btn-sm btn-link text-danger p-0 text-decoration-none js-dp-unassign-tag"
                            data-url="{{ route('delivery-person.bookings.tags.unassign', [$booking, $tagAsg->tag]) }}"
                            data-code="{{ $tagAsg->tag?->tag_code }}"
                            title="Remove tag before Store acceptance">
                        &times;
                    </button>
                @endif
            </div>
        @endforeach
    </div>
@endif
