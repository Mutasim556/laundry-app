@extends('store-portal.layouts.app')

@section('title', 'Store Earnings')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0" style="color: #171717;">Store Earnings</h4>
        <div class="small" style="color: #475569;">Finalized net earnings after platform commission for {{ $store->name }} (excluding platform service fees &amp; delivery charges).</div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="sp-card p-3 h-100" style="background: #F5F3FF; border-color: #C4B5FD;">
            <div class="small fw-bold text-uppercase" style="color: #6D28D9; font-size: 0.72rem;">Total Finalized Net Earnings</div>
            <div class="fs-4 fw-bold mt-1" style="color: #171717;">
                @forelse($totalEarningsByCurrency as $row)
                    <div>{{ $currencyService->formatMinor((int) $row->net_minor, $row->currency) }}</div>
                @empty
                    0.00
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="sp-card p-3 h-100" style="background: #FFF7E2; border-color: #F5BE48;">
            <div class="small fw-bold text-uppercase" style="color: #946200; font-size: 0.72rem;">Current Month Earnings</div>
            <div class="fs-4 fw-bold mt-1" style="color: #171717;">
                @forelse($currentPeriodByCurrency as $row)
                    <div>{{ $currencyService->formatMinor((int) $row->net_minor, $row->currency) }}</div>
                @empty
                    0.00
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="sp-card p-3 h-100" style="background: #ECFDF5; border-color: #86EFAC;">
            <div class="small fw-bold text-uppercase" style="color: #15803D; font-size: 0.72rem;">Completed Booking Earnings</div>
            <div class="fs-4 fw-bold mt-1" style="color: #171717;">
                @forelse($completedEarningsByCurrency as $row)
                    <div>{{ $currencyService->formatMinor((int) $row->net_minor, $row->currency) }}</div>
                @empty
                    0.00
                @endforelse
            </div>
            <div class="small mt-1" style="color: #475569; font-size: 0.74rem;">Payout tracking not configured</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="sp-card p-3 mb-3">
    <form method="GET" action="{{ route('store.earnings.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold mb-1">Booking Reference</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Search booking reference...">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold mb-1">From Date</label>
            <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold mb-1">To Date</label>
            <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-dark flex-grow-1">Filter</button>
            <a href="{{ route('store.earnings.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Earnings Table -->
<div class="sp-card p-3">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Booking Reference</th>
                    <th>Finalization Date</th>
                    <th class="text-end">Eligible Service Amount</th>
                    <th class="text-end">Commission Rate</th>
                    <th class="text-end">Commission Deduction</th>
                    <th class="text-end">Store Net Earnings</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($earnings as $row)
                    <tr class="small">
                        <td class="fw-bold">
                            @if($row->booking)
                                <a href="{{ route('store.bookings.show', $row->booking) }}" class="text-decoration-none">{{ $row->booking->reference }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $row->finalized_at?->format('d M Y, H:i') }}</td>
                        <td class="text-end">{{ $currencyService->formatMinor((int) $row->eligible_service_amount_minor, $row->currency) }}</td>
                        <td class="text-end">{{ number_format((float) $row->commission_rate, 2) }}%</td>
                        <td class="text-end text-danger">-{{ $currencyService->formatMinor((int) $row->commission_amount_minor, $row->currency) }}</td>
                        <td class="text-end fw-bold" style="color: #166534;">{{ $currencyService->formatMinor((int) $row->net_store_earnings_minor, $row->currency) }}</td>
                        <td><span class="badge bg-success-subtle text-success">Finalized</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No finalized store earnings recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($earnings->hasPages())
        <div class="mt-3">{{ $earnings->links() }}</div>
    @endif
</div>
@endsection
