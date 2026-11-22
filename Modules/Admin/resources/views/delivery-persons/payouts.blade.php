@extends('admin::layouts.app')

@section('title', 'Account Balances & Payouts - Admin Panel')

@section('content')
<div class="saas-page-shell py-4 px-3 px-md-4">
    <div class="saas-header-card">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h1 class="saas-header-title">Account Balances &amp; Payouts</h1>
                <p class="saas-header-subtitle">Append-only financial ledger, courier balances, and payout disbursement history</p>
            </div>
            <div>
                <a href="{{ route('admin.delivery-persons.index') }}" class="btn btn-outline-secondary rounded-3 fw-semibold">Back to Couriers</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rounded-3"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @can('manage-delivery-payouts', 'admin')
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="saas-form-section h-100 mb-0">
                <div class="saas-section-heading"><span class="saas-section-badge"><i class="mdi mdi-cash-check"></i></span><span>Record Payout</span></div>
                <form method="POST" action="{{ route('admin.delivery-payouts.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Delivery Person <span class="text-danger">*</span></label>
                        <select name="delivery_person_id" class="form-select form-select-sm" required>
                            <option value="">Select courier...</option>
                            @foreach($persons as $p)
                                @php $s = $summaries[$p->id]; @endphp
                                <option value="{{ $p->id }}">{{ $p->full_name }} (Bal: {{ $currencyService->formatMinor($s['outstanding_balance_minor'], $s['currency'], true) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label class="form-label small fw-semibold">Amount <span class="text-danger">*</span></label><input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required></div>
                    <div class="col-md-3"><label class="form-label small fw-semibold">Method</label><select name="payment_method" class="form-select form-select-sm"><option value="bank_transfer">Bank Transfer</option><option value="cash">Cash</option><option value="other">Other</option></select></div>
                    <div class="col-md-3"><label class="form-label small fw-semibold">Payment Date</label><input type="date" name="payment_date" value="{{ now()->format('Y-m-d') }}" class="form-control form-control-sm" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Reference Number</label><input type="text" name="external_reference" class="form-control form-control-sm" placeholder="Bank / Tx Ref"></div>
                    <div class="col-md-5"><label class="form-label small fw-semibold">Notes</label><input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional payout note"></div>
                    <div class="col-md-3 d-grid"><button type="submit" class="btn btn-sm btn-saas-primary">Record Payout</button></div>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="saas-form-section h-100 mb-0">
                <div class="saas-section-heading"><span class="saas-section-badge"><i class="mdi mdi-scale-balance"></i></span><span>Ledger Adjustment / Reversal</span></div>
                <form method="POST" action="{{ route('admin.delivery-payouts.adjustments.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Delivery Person <span class="text-danger">*</span></label>
                        <select name="delivery_person_id" class="form-select form-select-sm" required>
                            @foreach($persons as $p)
                                <option value="{{ $p->id }}">{{ $p->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Type</label>
                        <select name="transaction_type" class="form-select form-select-sm">
                            <option value="earning_adjustment">Approved Earning Adjustment</option>
                            <option value="earning_reversal">Earning Reversal</option>
                            <option value="payout_reversal">Payout Reversal</option>
                            <option value="authorized_correction">Authorized Correction</option>
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Direction</label><select name="direction" class="form-select form-select-sm"><option value="credit">Credit (+)</option><option value="debit">Debit (-)</option></select></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Amount <span class="text-danger">*</span></label><input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required></div>
                    <div class="col-md-4 d-grid"><button type="submit" class="btn btn-sm btn-outline-dark">Post Entry</button></div>
                    <div class="col-12"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Reason for adjustment (required)" required></div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    {{-- Courier Account Summaries --}}
    <div class="saas-surface-card mb-4">
        <div class="p-3 border-bottom fw-bold">Courier Account Summaries</div>
        <div class="table-responsive">
            <table class="table saas-table align-middle mb-0">
                <thead><tr><th>Courier</th><th>Completed Earnings</th><th>Adjustments</th><th>Total Payable</th><th>Paid Out</th><th>Pending</th><th>Outstanding</th></tr></thead>
                <tbody>
                    @foreach($persons as $p)
                        @php $s = $summaries[$p->id]; @endphp
                        <tr>
                            <td><a href="{{ route('admin.delivery-persons.show', $p) }}" class="fw-bold text-dark text-decoration-none">{{ $p->full_name }}</a> <span class="saas-code-pill ms-1">{{ $p->code }}</span></td>
                            <td>{{ $currencyService->formatMinor($s['earnings_minor'], $s['currency'], true) }}</td>
                            <td>{{ $currencyService->formatMinor($s['net_adjustments_minor'], $s['currency'], true) }}</td>
                            <td class="fw-semibold">{{ $currencyService->formatMinor($s['total_payable_minor'], $s['currency'], true) }}</td>
                            <td>{{ $currencyService->formatMinor($s['total_paid_minor'], $s['currency'], true) }}</td>
                            <td class="text-muted">{{ $currencyService->formatMinor($s['pending_earnings_minor'], $s['currency'], true) }}</td>
                            <td class="fw-bold text-warning-emphasis">{{ $currencyService->formatMinor($s['outstanding_balance_minor'], $s['currency'], true) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Payout History --}}
    <div class="saas-surface-card">
        <div class="p-3 border-bottom fw-bold">Payout History</div>
        <div class="table-responsive">
            <table class="table saas-table align-middle mb-0">
                <thead><tr><th>Payout Ref</th><th>Courier</th><th>Date</th><th>Method</th><th>Amount</th><th>External Ref</th><th>Recorded By</th></tr></thead>
                <tbody>
                    @forelse($payouts as $payout)
                        <tr>
                            <td><span class="saas-code-pill">{{ $payout->payout_reference }}</span></td>
                            <td>{{ $payout->deliveryPerson?->full_name }}</td>
                            <td>{{ $payout->payment_date?->format('d M Y') }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $payout->payment_method)) }}</td>
                            <td class="fw-bold">{{ $currencyService->formatMinor($payout->amount_minor, $payout->currency, true) }}</td>
                            <td>{{ $payout->external_reference ?: '—' }}</td>
                            <td>{{ $payout->recordedByAdmin?->name ?? 'Admin' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No payouts recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payouts->hasPages())
            <div class="p-3 border-top">{{ $payouts->links() }}</div>
        @endif
    </div>
</div>
@endsection
