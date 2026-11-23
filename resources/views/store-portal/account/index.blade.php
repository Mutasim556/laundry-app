@extends('store-portal.layouts.app')

@section('title', 'My Store Account')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0" style="color: #171717;">My Store Account</h4>
        <div class="small" style="color: #475569;">Permanent ledger balance, pending finalized earnings, and immutable transaction history for {{ $store->name }}.</div>
    </div>
</div>

<!-- 4 Financial Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="sp-card p-3 h-100" style="background: #ECFDF5; border-color: #86EFAC;">
            <div class="small fw-bold text-uppercase" style="color: #15803D; font-size: 0.72rem;">Current Balance</div>
            <div class="fs-4 fw-bold mt-1" style="color: #171717;">
                @forelse($accountSummary as $row)
                    <div>{{ $currencyService->formatMinor((int) $row['current_balance_minor'], $row['currency']) }}</div>
                @empty
                    0.00
                @endforelse
            </div>
            <div class="small" style="color: #475569; font-size: 0.74rem;">Available posted balance</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="sp-card p-3 h-100" style="background: #FFF7E2; border-color: #F5BE48;">
            <div class="small fw-bold text-uppercase" style="color: #946200; font-size: 0.72rem;">Pending Earnings</div>
            <div class="fs-4 fw-bold mt-1" style="color: #171717;">
                @forelse($accountSummary as $row)
                    <div>{{ $currencyService->formatMinor((int) $row['pending_earnings_minor'], $row['currency']) }}</div>
                @empty
                    0.00
                @endforelse
            </div>
            <div class="small" style="color: #475569; font-size: 0.74rem;">Credited upon final Completed delivery</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="sp-card p-3 h-100" style="background: #EFF6FF; border-color: #93C5FD;">
            <div class="small fw-bold text-uppercase" style="color: #1D4ED8; font-size: 0.72rem;">Total Earned</div>
            <div class="fs-4 fw-bold mt-1" style="color: #171717;">
                @forelse($accountSummary as $row)
                    <div>{{ $currencyService->formatMinor((int) $row['total_earned_minor'], $row['currency']) }}</div>
                @empty
                    0.00
                @endforelse
            </div>
            <div class="small" style="color: #475569; font-size: 0.74rem;">Posted booking earnings</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="sp-card p-3 h-100" style="background: #F5F3FF; border-color: #C4B5FD;">
            <div class="small fw-bold text-uppercase" style="color: #6D28D9; font-size: 0.72rem;">Total Paid Out</div>
            <div class="fs-4 fw-bold mt-1" style="color: #171717;">
                @forelse($accountSummary as $row)
                    <div>{{ $currencyService->formatMinor((int) $row['total_paid_out_minor'], $row['currency']) }}</div>
                @empty
                    0.00
                @endforelse
            </div>
            <div class="small" style="color: #475569; font-size: 0.74rem;">Confirmed payout debits</div>
        </div>
    </div>
</div>

@if($pendingItems->isNotEmpty())
<div class="sp-card p-3 mb-3" style="background: #FFF8E5; border-color: #FDE68A;">
    <h6 class="fw-bold mb-2" style="color: #92400E;">Pending Finalized Earnings (Not Yet Credited)</h6>
    <div class="table-responsive bg-white rounded-3 border">
        <table class="table table-sm align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Booking Ref</th>
                    <th>Workflow Stage</th>
                    <th class="text-end">Eligible Base</th>
                    <th class="text-end">Commission</th>
                    <th class="text-end">Pending Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingItems as $pe)
                    <tr>
                        <td class="fw-bold">
                            @if($pe->booking)
                                <a href="{{ route('store.bookings.show', $pe->booking) }}" class="text-decoration-none">{{ $pe->booking->reference }}</a>
                            @endif
                        </td>
                        <td>{{ $pe->booking?->getStatusLabel() }}</td>
                        <td class="text-end">{{ $currencyService->formatMinor((int) $pe->eligible_service_amount_minor, $pe->currency) }}</td>
                        <td class="text-end">{{ number_format((float) $pe->commission_rate, 2) }}%</td>
                        <td class="text-end fw-bold" style="color: #92400E;">{{ $currencyService->formatMinor((int) $pe->net_store_earnings_minor, $pe->currency) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Transaction Filters -->
<div class="sp-card p-3 mb-3">
    <form method="GET" action="{{ route('store.account.index') }}" class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">From Date</label>
            <input type="date" name="from_date" value="{{ $filters['from_date'] }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">To Date</label>
            <input type="date" name="to_date" value="{{ $filters['to_date'] }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Type</label>
            <select name="type" class="form-select form-select-sm">
                <option value="all" @selected($filters['type'] === 'all')>All Types</option>
                <option value="booking_earning" @selected($filters['type'] === 'booking_earning')>Booking Earning</option>
                <option value="payout" @selected($filters['type'] === 'payout')>Payout</option>
                <option value="positive_adjustment" @selected($filters['type'] === 'positive_adjustment')>Positive Adjustment</option>
                <option value="negative_adjustment" @selected($filters['type'] === 'negative_adjustment')>Negative Adjustment</option>
                <option value="reversal" @selected($filters['type'] === 'reversal')>Reversal</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Direction</label>
            <select name="direction" class="form-select form-select-sm">
                <option value="all" @selected($filters['direction'] === 'all')>All Transactions</option>
                <option value="credit" @selected($filters['direction'] === 'credit')>Credits Only</option>
                <option value="debit" @selected($filters['direction'] === 'debit')>Debits Only</option>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label small fw-semibold mb-1">Search</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Booking or Tx Ref...">
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-dark flex-grow-1">Apply</button>
            <a href="{{ route('store.account.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Transaction History Table -->
<div class="sp-card p-3">
    <h6 class="fw-bold mb-3" style="color: #171717;">Transaction History</h6>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Date &amp; Time</th>
                    <th>Transaction Reference</th>
                    <th>Booking Reference</th>
                    <th>Transaction Type</th>
                    <th class="text-end">Credit</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Running Balance</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                    <tr class="small">
                        <td class="text-nowrap">{{ $tx->created_at?->format('d M Y, H:i:s') }}</td>
                        <td class="fw-semibold text-primary text-nowrap">{{ $tx->reference }}</td>
                        <td class="text-nowrap">
                            @if($tx->booking)
                                <a href="{{ route('store.bookings.show', $tx->booking) }}" class="fw-semibold text-decoration-none">{{ $tx->booking->reference }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $tx->type_label }}</span></td>
                        <td class="text-end fw-bold" style="color: #16A34A;">
                            {{ $tx->direction === 'credit' ? $tx->currency . ' ' . $tx->amount : '-' }}
                        </td>
                        <td class="text-end fw-bold" style="color: #EF4444;">
                            {{ $tx->direction === 'debit' ? $tx->currency . ' ' . $tx->amount : '-' }}
                        </td>
                        <td class="text-end fw-bold" style="color: #171717;">
                            {{ $tx->balance_after !== null ? $tx->currency . ' ' . $tx->balance_after : '-' }}
                        </td>
                        <td>{{ $tx->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No posted account transactions yet. Credits appear automatically when final delivery is Completed.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
        <div class="mt-3">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
