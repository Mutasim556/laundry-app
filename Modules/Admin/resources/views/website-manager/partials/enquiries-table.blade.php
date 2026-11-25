@if ($enquiries->isEmpty())
    <div class="text-center py-5">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 56px; height: 56px; background: rgba(99, 102, 241, 0.1); color: #6366f1;">
            <i data-feather="inbox"></i>
        </div>
        <h6 class="fw-bold mb-1">No enquiries found</h6>
        <p class="text-muted small mb-0">Try clearing your search or status filter, or check back when customers submit enquiries.</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Reference</th>
                    <th>Received</th>
                    <th>Customer</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Email Alert</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($enquiries as $enquiry)
                    @php
                        $meta = $enquiry->statusMeta();
                    @endphp
                    <tr data-enquiry-id="{{ $enquiry->id }}">
                        <td>
                            <span class="badge bg-light text-dark border fw-semibold">{{ $enquiry->reference }}</span>
                            @if ($enquiry->booking_reference)
                                <div class="small text-muted mt-1">Booking: {{ $enquiry->booking_reference }}</div>
                            @endif
                        </td>
                        <td class="small text-muted text-nowrap">
                            {{ $enquiry->submitted_at?->format('d M Y, H:i') ?? $enquiry->created_at?->format('d M Y, H:i') }}
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $enquiry->name }}</div>
                            <div class="small text-muted">{{ $enquiry->email }}</div>
                            @if ($enquiry->phone)
                                <div class="small text-muted">{{ $enquiry->phone }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-medium">{{ \Illuminate\Support\Str::limit($enquiry->subject, 60) }}</div>
                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($enquiry->message, 80) }}</div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $meta['badge'] }} adm-enq-status-badge">{{ $meta['label'] }}</span>
                        </td>
                        <td>
                            @if ($enquiry->email_notification_status === 'sent')
                                <span class="badge bg-success-subtle text-success">Sent</span>
                            @elseif ($enquiry->email_notification_status === 'failed')
                                <span class="badge bg-danger-subtle text-danger">Failed</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($enquiry->email_notification_status ?? 'pending') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary rounded-pill px-3 adm-enq-view-btn"
                                data-show-url="{{ route('admin.website-manager.enquiries.show', $enquiry) }}"
                                data-update-url="{{ route('admin.website-manager.enquiries.status', $enquiry) }}"
                            >
                                <i data-feather="eye" class="me-1" style="width: 14px; height: 14px;"></i> View
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($enquiries->hasPages())
        <div class="mt-3 d-flex justify-content-end adm-enq-pagination">
            {{ $enquiries->links('pagination::bootstrap-5') }}
        </div>
    @endif
@endif
