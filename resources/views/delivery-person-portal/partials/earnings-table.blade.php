<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small" style="min-width: 720px;">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Booking Reference</th>
                <th>Job Type</th>
                <th>Description</th>
                <th class="text-end">Earned Amount</th>
                <th>Earning Status</th>
                <th>Credited Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($earningsEntries as $entry)
                @php
                    $isFinal = $entry->assignment?->assignment_type === \Modules\Admin\Models\DeliveryAssignment::TYPE_FINAL_DELIVERY;
                    $jobBadge = $isFinal
                        ? 'bg-purple-subtle text-purple border border-purple-subtle'
                        : 'bg-primary-subtle text-primary border border-primary-subtle';
                    $jobLabel = $isFinal ? 'Final Delivery' : 'Collection';
                    $dt = $entry->occurred_at ?? $entry->created_at;
                @endphp
                <tr>
                    <td class="text-nowrap">{{ $dt?->format('d M Y, H:i') }}</td>
                    <td class="fw-semibold text-nowrap">{{ $entry->booking?->reference ?? '-' }}</td>
                    <td class="text-nowrap">
                        <span class="badge rounded-pill {{ $jobBadge }}" style="{{ $isFinal ? 'background-color:#F3E8FF;color:#6D28D9;border-color:#DDD6FE;' : '' }}">
                            <i class="mdi {{ $isFinal ? 'mdi-truck-delivery' : 'mdi-package-up' }} me-1"></i>{{ $jobLabel }}
                        </span>
                    </td>
                    <td>{{ $entry->notes ?: ($entry->assignment?->assignment_reference ? "Job {$entry->assignment->assignment_reference}" : 'Completed delivery job') }}</td>
                    <td class="text-end fw-bold text-success text-nowrap">
                        +{{ $currencyService->formatMinor((int) $entry->amount_minor, $entry->currency) }}
                    </td>
                    <td class="text-nowrap">
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Credited</span>
                    </td>
                    <td class="text-nowrap text-muted">{{ $dt?->format('d M Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="mdi mdi-cash-remove fs-4 d-block mb-1"></i>
                        No job earnings recorded for this month.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($earningsEntries->hasPages())
    <div class="p-3 border-top dp-ajax-pagination" data-target="earnings">
        {{ $earningsEntries->links() }}
    </div>
@endif
