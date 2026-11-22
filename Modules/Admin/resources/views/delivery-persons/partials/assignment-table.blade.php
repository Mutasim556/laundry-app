<div class="table-responsive">
    <table class="table saas-table align-middle mb-0">
        <thead>
            <tr>
                <th>Ref</th>
                <th>Booking</th>
                <th>Type</th>
                <th>From &rarr; To</th>
                <th>Rate</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr>
                    <td><span class="saas-code-pill">{{ $item->assignment_reference }}</span></td>
                    <td>
                        @if($item->booking)
                            <a href="{{ route('admin.bookings.show', $item->booking) }}" class="fw-bold text-decoration-none">{{ $item->booking->reference }}</a>
                        @else
                            #{{ $item->booking_id }}
                        @endif
                    </td>
                    <td>{{ $item->type_label }}</td>
                    <td class="small">
                        <div><strong>From:</strong> {{ $item->pickup_location }}</div>
                        <div><strong>To:</strong> {{ $item->destination_location }}</div>
                    </td>
                    <td class="fw-bold text-nowrap">{{ $currencyService->formatMinor($item->rate_minor, $item->currency, true) }}</td>
                    <td><span class="saas-status-badge {{ in_array($item->status, ['completed','handed_over']) ? 'active' : 'pending' }}">{{ $item->status_label }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No assignments in this category.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
