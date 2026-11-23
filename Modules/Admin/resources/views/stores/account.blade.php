@extends('admin::layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <a href="{{ route('admin.stores.index') }}" class="text-decoration-none small fw-semibold text-muted">&larr; Back to Store Management</a>
            <h4 class="fw-bold mb-0 mt-1">{{ $store->name }} — Store Account &amp; Ledger</h4>
            <div class="small text-muted">Immutable transaction ledger (Commission Rate: {{ $store->commission_rate !== null ? number_format((float) $store->commission_rate, 2) . '%' : 'Not configured' }})</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success small py-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger small py-2">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger small py-2">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3 mb-3">
        @forelse($summary as $row)
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3" style="background: #ECFDF5;">
                    <div class="small fw-bold text-success text-uppercase">Current Balance ({{ $row['currency'] }})</div>
                    <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor((int) $row['current_balance_minor'], $row['currency']) }}</div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3" style="background: #FFF7E2;">
                    <div class="small fw-bold text-warning-emphasis text-uppercase">Pending Earnings ({{ $row['currency'] }})</div>
                    <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor((int) $row['pending_earnings_minor'], $row['currency']) }}</div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3" style="background: #EFF6FF;">
                    <div class="small fw-bold text-primary text-uppercase">Total Earned ({{ $row['currency'] }})</div>
                    <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor((int) $row['total_earned_minor'], $row['currency']) }}</div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3" style="background: #F5F3FF;">
                    <div class="small fw-bold text-uppercase" style="color: #6D28D9;">Total Paid Out ({{ $row['currency'] }})</div>
                    <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor((int) $row['total_paid_out_minor'], $row['currency']) }}</div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 p-3 small text-muted">No financial activity recorded for this store yet.</div>
            </div>
        @endforelse
    </div>

    @can('adjust-store-accounts', 'admin')
        <div class="card border-0 shadow-sm rounded-3 p-3 mb-3">
            <h6 class="fw-bold mb-2">Authorized Manual Adjustment (Immutable Ledger Entry)</h6>
            <form method="POST" action="{{ route('admin.stores.account.adjust', $store) }}" class="row g-2 align-items-end small">
                @csrf
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold mb-1">Currency</label>
                    <input type="text" name="currency" value="GBP" class="form-control form-control-sm text-uppercase" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold mb-1">Direction</label>
                    <select name="direction" class="form-select form-select-sm" required>
                        <option value="credit">Credit (+)</option>
                        <option value="debit">Debit (-)</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold mb-1">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold mb-1">Audit Reason *</label>
                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason for adjustment..." required>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="confirm_adjustment" value="1" id="confirmAdj" required>
                        <label class="form-check-label" for="confirmAdj" style="font-size: 0.72rem;">Confirm</label>
                    </div>
                    <button type="submit" class="btn btn-sm btn-warning fw-bold">Post Entry</button>
                </div>
            </form>
        </div>
    @endcan

    <div class="card border-0 shadow-sm rounded-3 p-3">
        <h6 class="fw-bold mb-3">Full Transaction History</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Tx Ref</th>
                        <th>Booking</th>
                        <th>Type</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Running Balance</th>
                        <th>Description</th>
                        <th>Admin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        <tr>
                            <td class="text-nowrap">{{ $tx->created_at?->format('d M Y, H:i:s') }}</td>
                            <td class="fw-semibold text-primary text-nowrap">{{ $tx->reference }}</td>
                            <td class="text-nowrap">{{ $tx->booking?->reference ?? '-' }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $tx->type_label }}</span></td>
                            <td class="text-end fw-bold text-success">{{ $tx->direction === 'credit' ? $tx->currency . ' ' . $tx->amount : '-' }}</td>
                            <td class="text-end fw-bold text-danger">{{ $tx->direction === 'debit' ? $tx->currency . ' ' . $tx->amount : '-' }}</td>
                            <td class="text-end fw-bold">{{ $tx->balance_after !== null ? $tx->currency . ' ' . $tx->balance_after : '-' }}</td>
                            <td>{{ $tx->description }}</td>
                            <td>{{ $tx->createdByAdmin?->name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No transactions posted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="mt-3">{{ $transactions->links() }}</div>
        @endif
    </div>
</div>
@endsection
