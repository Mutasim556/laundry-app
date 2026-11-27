<div class="dp-card p-3 mb-3">
    <h6 class="fw-bold mb-2">Daily Earnings Trend — {{ $period['label'] }}</h6>
    <div id="dpRepEarnBarChart" style="min-height: 220px;"></div>
</div>

<div class="dp-card p-3 mb-3">
    <h6 class="fw-bold mb-2">Credited Earnings by Job Type — {{ $period['label'] }}</h6>
    @if($byJobType->isEmpty())
        <div class="text-muted small">No credited earnings in {{ $period['label'] }}.</div>
    @else
        <div class="row g-2">
            @foreach($byJobType as $row)
                <div class="col-6 col-md-4">
                    <div class="p-2 rounded-3 border bg-light small">
                        <div class="text-muted text-capitalize">{{ str_replace('_', ' ', $row->job_type) }} ({{ $row->currency }})</div>
                        <div class="fs-5 fw-bold text-success">{{ $currencyService->formatMinor((int) $row->total_minor, $row->currency) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<div class="dp-card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Earnings Ledger — {{ $period['label'] }}</h6>
        <span class="badge rounded-pill bg-light text-dark border">{{ $earnings->total() }} {{ Str::plural('record', $earnings->total()) }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase text-muted">
                    <th>Date</th>
                    <th>Job Type</th>
                    <th>Booking</th>
                    <th>Status</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($earnings as $er)
                    <tr class="small">
                        <td class="text-nowrap">{{ $er->created_at?->format('d M Y, H:i') }}</td>
                        <td class="text-capitalize">{{ str_replace('_', ' ', $er->transaction_type) }}</td>
                        <td class="fw-semibold">{{ $er->booking?->reference ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border text-capitalize">{{ $er->direction }}</span></td>
                        <td class="text-end fw-bold text-success">{{ $currencyService->formatMinor((int) ($er->amount_minor ?? 0), $er->currency ?: 'GBP') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">No earnings records in {{ $period['label'] }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($earnings->hasPages())
        <div class="mt-3 dp-rep-er-pagination">{{ $earnings->links() }}</div>
    @endif
</div>
