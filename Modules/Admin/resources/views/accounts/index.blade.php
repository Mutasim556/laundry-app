@extends('admin::layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="mdi mdi-wallet-outline text-primary me-1"></i>Admin Current Account
            </h4>
            <div class="small text-muted">Immutable platform delivery margin, shortfall, advance settlement, and adjustment ledger.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if(count($availableCurrencies) > 1)
                <form method="GET" action="{{ route('admin.current-account.index') }}" class="d-flex align-items-center gap-2">
                    <select name="currency" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($availableCurrencies as $cur)
                            <option value="{{ $cur }}" {{ $cur === $selectedCurrency ? 'selected' : '' }}>{{ $cur }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
            @can('adjust-admin-accounts', 'admin')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#adminAdjustmentModal">
                    <i class="mdi mdi-plus-circle-outline me-1"></i>Manual Adjustment
                </button>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 4 Statistic Cards -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="background-color: #ECFDF5; border-radius: 14px;">
                <div class="card-body p-3">
                    <div class="small fw-semibold text-success mb-1">Current Balance ({{ $selectedCurrency }})</div>
                    <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor((int) $activeSummary['current_balance_minor'], $selectedCurrency) }}</div>
                    <div class="small text-muted mt-1">Net retained platform ledger</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="background-color: #EFF6FF; border-radius: 14px;">
                <div class="card-body p-3">
                    <div class="small fw-semibold text-primary mb-1">Total Credits</div>
                    <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor((int) $activeSummary['total_credited_minor'], $selectedCurrency) }}</div>
                    <div class="small text-muted mt-1">Delivery margins &amp; credit adjustments</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="background-color: #FFF1F2; border-radius: 14px;">
                <div class="card-body p-3">
                    <div class="small fw-semibold text-danger mb-1">Total Debits</div>
                    <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor((int) $activeSummary['total_debited_minor'], $selectedCurrency) }}</div>
                    <div class="small text-muted mt-1">Delivery shortfalls &amp; debit adjustments</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="background-color: #FFFBEB; border-radius: 14px;">
                <div class="card-body p-3">
                    <div class="small fw-semibold text-warning-emphasis mb-1">Awaiting Advance Settlement</div>
                    <div class="fs-4 fw-bold text-dark">{{ $currencyService->formatMinor((int) $activeSummary['awaiting_advance_minor'], $selectedCurrency) }}</div>
                    <div class="small text-muted mt-1">Prepaid bookings pending completion</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm mb-3" style="border-radius: 14px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.current-account.index') }}" class="row g-2 align-items-end">
                <input type="hidden" name="currency" value="{{ $selectedCurrency }}">
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Transaction Type</label>
                    <select name="transaction_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach($transactionTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('transaction_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Booking Reference</label>
                    <input type="text" name="booking_reference" value="{{ request('booking_reference') }}" class="form-control form-control-sm" placeholder="e.g. LND-...">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
                    <a href="{{ route('admin.current-account.index', ['currency' => $selectedCurrency]) }}" class="btn btn-sm btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="card border-0 shadow-sm" style="border-radius: 14px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Transaction Reference</th>
                            <th>Booking Reference</th>
                            <th>Type</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Running Balance</th>
                            <th>Currency</th>
                            <th>Description</th>
                            @can('adjust-admin-accounts', 'admin')
                                <th class="text-end">Action</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            @php
                                $isCredit = $tx->direction === \App\Models\AdminAccountTransaction::DIRECTION_CREDIT;
                                $amtMinor = \App\Services\StoreAccountService::decimalToMinor($tx->amount);
                                $balMinor = \App\Services\StoreAccountService::decimalToMinor($tx->balance_after);
                                $canReverse = $tx->transaction_type !== \App\Models\AdminAccountTransaction::TYPE_REVERSAL
                                    && ! in_array($tx->id, $reversedTransactionIds, true);
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ $tx->created_at?->format('d M Y, H:i') }}</td>
                                <td class="fw-semibold text-dark text-nowrap">{{ $tx->reference }}</td>
                                <td>
                                    @if($tx->booking)
                                        <a href="{{ route('admin.bookings.show', $tx->booking) }}" class="fw-semibold text-decoration-none">{{ $tx->booking->reference }}</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $transactionTypes[$tx->transaction_type] ?? ucfirst(str_replace('_', ' ', $tx->transaction_type)) }}</span>
                                </td>
                                <td class="text-end fw-semibold text-success">
                                    {{ $isCredit ? $currencyService->formatMinor($amtMinor, $tx->currency) : '-' }}
                                </td>
                                <td class="text-end fw-semibold text-danger">
                                    {{ !$isCredit ? $currencyService->formatMinor($amtMinor, $tx->currency) : '-' }}
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    {{ $currencyService->formatMinor($balMinor, $tx->currency) }}
                                </td>
                                <td>{{ $tx->currency }}</td>
                                <td style="max-width: 280px;">{{ $tx->description }}</td>
                                @can('adjust-admin-accounts', 'admin')
                                    <td class="text-end text-nowrap">
                                        @if($canReverse)
                                            <button type="button"
                                                    class="btn btn-xs btn-outline-danger js-reverse-tx-btn"
                                                    data-action="{{ route('admin.current-account.reverse', $tx) }}"
                                                    data-ref="{{ $tx->reference }}">
                                                Reverse
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No admin account transactions recorded yet for {{ $selectedCurrency }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
            <div class="card-footer bg-white border-top py-2 px-3">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>

@can('adjust-admin-accounts', 'admin')
<!-- Manual Adjustment Modal -->
<div class="modal fade" id="adminAdjustmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('admin.current-account.adjust') }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Manual Admin Account Adjustment</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body small">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-semibold mb-1">Currency</label>
                            <select name="currency" class="form-select form-select-sm" required>
                                @foreach($availableCurrencies as $cur)
                                    <option value="{{ $cur }}" {{ $cur === $selectedCurrency ? 'selected' : '' }}>{{ $cur }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold mb-1">Direction</label>
                            <select name="direction" class="form-select form-select-sm" required>
                                <option value="credit">Credit (+)</option>
                                <option value="debit">Debit (-)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold mb-1">Amount</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required placeholder="0.00">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold mb-1">Booking Reference (Optional)</label>
                        <input type="text" name="booking_reference" class="form-control form-control-sm" placeholder="e.g. LND-2026-0001">
                    </div>
                    <div>
                        <label class="form-label fw-semibold mb-1">Description / Reason <span class="text-danger">*</span></label>
                        <textarea name="description" rows="2" class="form-control form-control-sm" required placeholder="Reason for manual adjustment"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Record Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reversal Modal -->
<div class="modal fade" id="adminReversalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="adminReversalForm" action="#">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Compensating Reversal (<span id="reverseTxRefLabel"></span>)</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body small">
                    <p class="text-muted mb-2">Ledger entries are immutable. This creates an equal and opposite reversal entry.</p>
                    <label class="form-label fw-semibold mb-1">Reversal Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" rows="2" class="form-control form-control-sm" required placeholder="Explain why this entry is being reversed"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Confirm Reversal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
    $(document).on('click', '.js-reverse-tx-btn', function () {
        $('#adminReversalForm').attr('action', $(this).data('action'));
        $('#reverseTxRefLabel').text($(this).data('ref'));
        const el = document.getElementById('adminReversalModal');
        if (el && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(el).show();
        }
    });
</script>
@endpush
