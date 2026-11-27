@extends('store-portal.layouts.app')

@section('title', 'Store Account & Ledger — Store Portal')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0" style="color: #171717;">Store Account &amp; Financial Ledger</h4>
        <div class="small text-muted">All-time balances, monthly earnings activity, and posted ledger transactions</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('store.dashboard') }}" class="btn btn-sm btn-outline-dark">Dashboard</a>
        <a href="{{ route('store.bookings.index') }}" class="btn btn-sm btn-outline-dark">My Bookings</a>
    </div>
</div>

<!-- Permanent Overall Account Balances (Never Filtered by Month) -->
@if(empty($accountSummary))
    <div class="sp-card p-3 mb-3 text-muted small sp-anim-card">
        No account balances yet. Store Share credits are posted automatically once a booking's final delivery is Completed.
    </div>
@else
    @foreach($accountSummary as $curr => $row)
        <div class="sp-card p-3 mb-3 sp-anim-card" style="background: linear-gradient(135deg, #FFFBEB 0%, #FFFFFF 100%); border-color: #FDE68A;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-2 py-1" style="background: #171717; color: #FFC629;">{{ $curr }}</span>
                    <span class="small fw-bold text-uppercase" style="color: #92400E;">All-Time Account Summary</span>
                </div>
                <span class="text-muted" style="font-size: 0.75rem;">Unchanged when switching months</span>
            </div>
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded-2 bg-white border h-100">
                        <div class="text-muted" style="font-size: 0.75rem;">Current Available Balance</div>
                        <div class="fs-5 fw-bold mt-1" style="color: #166534;">
                            {{ $currencyService->formatMinor((int) $row['current_balance_minor'], $curr) }}
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded-2 bg-white border h-100">
                        <div class="text-muted" style="font-size: 0.75rem;">Pending Earnings</div>
                        <div class="fs-5 fw-bold mt-1" style="color: #92400E;">
                            {{ $currencyService->formatMinor((int) $row['pending_earnings_minor'], $curr) }}
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded-2 bg-white border h-100">
                        <div class="text-muted" style="font-size: 0.75rem;">All-Time Earned</div>
                        <div class="fs-5 fw-bold mt-1" style="color: #171717;">
                            {{ $currencyService->formatMinor((int) $row['total_earned_minor'], $curr) }}
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 rounded-2 bg-white border h-100">
                        <div class="text-muted" style="font-size: 0.75rem;">All-Time Paid Out</div>
                        <div class="fs-5 fw-bold mt-1" style="color: #1D4ED8;">
                            {{ $currencyService->formatMinor((int) $row['total_paid_out_minor'], $curr) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif

@if($pendingItems->isNotEmpty())
<div class="sp-card p-3 mb-3 sp-anim-card">
    <h6 class="fw-bold mb-2" style="color: #171717;">Pending Earnings (Awaiting Final Delivery Completion)</h6>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-uppercase" style="color: #475569;">
                    <th>Booking</th>
                    <th>Status</th>
                    <th class="text-end">Eligible Amount</th>
                    <th class="text-end">Commission</th>
                    <th class="text-end">Est. Store Share</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingItems as $pe)
                    <tr class="small">
                        <td>
                            @if($pe->booking)
                                <a href="{{ route('store.bookings.show', $pe->booking) }}" class="fw-semibold text-decoration-none">{{ $pe->booking->reference }}</a>
                            @else
                                -
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

@include('store-portal.partials.month-bar', [
    'prefix' => 'spAcc',
    'monthLabel' => $monthLabel,
    'selectedMonth' => $selectedMonth,
    'selectedYear' => $selectedYear,
    'prevMonth' => $prevMonth,
    'prevYear' => $prevYear,
    'nextMonth' => $nextMonth,
    'nextYear' => $nextYear,
    'currentMonth' => $currentMonth,
    'currentYear' => $currentYear,
    'subtitle' => 'Monthly Ledger Activity & Posted Transactions',
])

<!-- Selected Month Activity -->
<div id="spAccMonthlyWrap">
    @include('store-portal.account.partials.monthly-activity')
</div>

<!-- AJAX Transaction Filters -->
<div class="sp-card p-3 mb-3">
    <form id="spAccFilterForm" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold mb-1">Search</label>
            <input type="text" id="spAccSearch" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Tx ref, booking ref, notes...">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Type</label>
            <select id="spAccType" name="type" class="form-select form-select-sm">
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
            <select id="spAccDirection" name="direction" class="form-select form-select-sm">
                <option value="all" @selected($filters['direction'] === 'all')>All Directions</option>
                <option value="credit" @selected($filters['direction'] === 'credit')>Credits Only</option>
                <option value="debit" @selected($filters['direction'] === 'debit')>Debits Only</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Currency</label>
            <select id="spAccCurrency" name="currency" class="form-select form-select-sm">
                <option value="all" @selected(($filters['currency'] ?? 'all') === 'all')>All Currencies</option>
                @foreach($currencies as $currOpt)
                    <option value="{{ $currOpt }}" @selected(($filters['currency'] ?? 'all') === $currOpt)>{{ $currOpt }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold mb-1">Sort By</label>
            <select id="spAccSort" name="sort" class="form-select form-select-sm">
                <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest First</option>
                <option value="oldest" @selected(($filters['sort'] ?? 'newest') === 'oldest')>Oldest First</option>
                <option value="amount_desc" @selected(($filters['sort'] ?? 'newest') === 'amount_desc')>Highest Amount</option>
            </select>
        </div>
        <div class="col-12 col-md-1 d-flex">
            <button type="button" id="spAccResetBtn" class="btn btn-sm btn-outline-secondary w-100">Reset</button>
        </div>
    </form>
</div>

<div id="spAccErrorAlert" class="alert alert-danger d-none py-2 small" role="alert"></div>

<div id="spAccResultsWrap" class="sp-ajax-wrap">
    @include('store-portal.account.partials.transactions-table')
</div>

<!-- Read-Only Transaction Detail Modal -->
<div class="modal fade" id="spAccTxDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Transaction Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body small">
                <div class="p-3 rounded-2 bg-light border d-flex flex-column gap-2">
                    <div><strong>Reference:</strong> <span id="txModalRef" class="text-primary fw-semibold"></span></div>
                    <div><strong>Date &amp; Time:</strong> <span id="txModalDate"></span></div>
                    <div><strong>Type:</strong> <span id="txModalType"></span></div>
                    <div><strong>Direction:</strong> <span id="txModalDirection"></span></div>
                    <div><strong>Amount:</strong> <span id="txModalAmount" class="fw-bold"></span></div>
                    <div><strong>Balance After:</strong> <span id="txModalBalance" class="fw-bold"></span></div>
                    <div><strong>Booking Ref:</strong> <span id="txModalBooking"></span></div>
                    <div><strong>Description:</strong> <span id="txModalDesc"></span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-dark" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.StorePortalMonthAjax) {
            window.StorePortalMonthAjax.init({
                prefix: 'spAcc',
                baseUrl: '{{ route('store.account.index') }}',
                currentMonth: {{ (int) $currentMonth }},
                currentYear: {{ (int) $currentYear }},
                wrapId: 'spAccResultsWrap',
                errorId: 'spAccErrorAlert',
                formId: 'spAccFilterForm',
                resetBtnId: 'spAccResetBtn',
                paginationSelector: '.sp-acc-pagination a',
                changeIds: ['spAccType', 'spAccDirection', 'spAccCurrency', 'spAccSort'],
                searchIds: ['spAccSearch'],
                collectParams: function () {
                    return {
                        q: (document.getElementById('spAccSearch')?.value || '').trim(),
                        type: document.getElementById('spAccType')?.value || 'all',
                        direction: document.getElementById('spAccDirection')?.value || 'all',
                        currency: document.getElementById('spAccCurrency')?.value || 'all',
                        sort: document.getElementById('spAccSort')?.value || 'newest',
                    };
                },
                onSuccess: function (data) {
                    if (data.monthly_html) {
                        const monthlyWrap = document.getElementById('spAccMonthlyWrap');
                        if (monthlyWrap) monthlyWrap.innerHTML = data.monthly_html;
                    }
                },
                onReset: function () {
                    const q = document.getElementById('spAccSearch');
                    const type = document.getElementById('spAccType');
                    const dir = document.getElementById('spAccDirection');
                    const curr = document.getElementById('spAccCurrency');
                    const sort = document.getElementById('spAccSort');
                    if (q) q.value = '';
                    if (type) type.value = 'all';
                    if (dir) dir.value = 'all';
                    if (curr) curr.value = 'all';
                    if (sort) sort.value = 'newest';
                }
            });
        }
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-sp-tx-detail');
        if (!btn) return;
        document.getElementById('txModalRef').textContent = btn.dataset.ref || '';
        document.getElementById('txModalDate').textContent = btn.dataset.date || '';
        document.getElementById('txModalType').textContent = btn.dataset.type || '';
        document.getElementById('txModalDirection').textContent = btn.dataset.direction || '';
        document.getElementById('txModalAmount').textContent = btn.dataset.amount || '';
        document.getElementById('txModalBalance').textContent = btn.dataset.balance || '';
        document.getElementById('txModalBooking').textContent = btn.dataset.booking || '-';
        document.getElementById('txModalDesc').textContent = btn.dataset.desc || '-';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('spAccTxDetailModal')).show();
    });
</script>
@endpush
