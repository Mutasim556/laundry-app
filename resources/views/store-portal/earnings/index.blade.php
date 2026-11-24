@extends('store-portal.layouts.app')

@section('title', 'Store Earnings & Withdrawals')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/dist/libs/select2/dist/css/select2.min.css') }}">
<style>
    .sp-stat-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 0.85rem 0.95rem;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .sp-stat-bar {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
    }
    .sp-stat-label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #475569;
    }
    .sp-stat-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: #171717;
        margin-top: 0.22rem;
    }
    .sp-stat-sub {
        font-size: 0.7rem;
        color: #64748B;
        margin-top: 0.12rem;
    }
    .sp-table-wrap {
        width: 100%;
        overflow-x: auto;
    }
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 4px 8px;
        font-size: 0.875rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
@endpush

@section('content')
<div id="spEarningsAlert" class="alert d-none py-2 small mb-3"></div>

<!-- Header & Month Selector -->
<div class="sp-card p-3 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border-color: #FDE68A;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h5 class="fw-bold mb-0" style="color: #171717;">
                <i class="mdi mdi-cash-multiple me-1 text-warning"></i>Store Earnings
            </h5>
            <div class="small text-muted">Monthly Earnings &amp; Withdrawal Management (<span id="spMonthLabel">{{ $summary['period']['label'] }}</span>)</div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="btn-group btn-group-sm" role="group" aria-label="Month Navigation">
                <button type="button" class="btn btn-light border" id="spPrevMonthBtn" data-month="{{ $summary['period']['prev_month'] }}">&lsaquo; Previous</button>
                <input type="month" id="spMonthInput" class="form-control form-control-sm rounded-0 border-start-0 border-end-0" style="width: 145px;" value="{{ $summary['period']['month'] }}">
                <button type="button" class="btn btn-light border" id="spNextMonthBtn" data-month="{{ $summary['period']['next_month'] }}">Next &rsaquo;</button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-dark" id="spCurrentMonthBtn" data-month="{{ $summary['period']['current_month'] }}">Current Month</button>
            <button type="button" class="btn btn-sm sp-btn-yellow px-3" id="spOpenWithdrawModalBtn">
                <i class="mdi mdi-bank-transfer-out me-1"></i>Request Withdrawal
            </button>
        </div>
    </div>
</div>

<!-- 7 Summary Cards -->
<div class="row g-2 g-md-3 mb-3">
    <div class="col-6 col-md-4 col-xl">
        <div class="sp-stat-card">
            <div class="sp-stat-bar" style="background: #8B5CF6;"></div>
            <div class="sp-stat-label">Store Revenue</div>
            <div class="sp-stat-value" id="cardRevenueMonth">{{ $currencyService->formatMinor($summary['revenue_month_minor'], $currency) }}</div>
            <div class="sp-stat-sub">Eligible month revenue</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="sp-stat-card">
            <div class="sp-stat-bar" style="background: #EF4444;"></div>
            <div class="sp-stat-label">Platform Commission</div>
            <div class="sp-stat-value text-danger" id="cardCommissionMonth">{{ $currencyService->formatMinor($summary['commission_month_minor'], $currency) }}</div>
            <div class="sp-stat-sub">Month commission</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="sp-stat-card">
            <div class="sp-stat-bar" style="background: #3B82F6;"></div>
            <div class="sp-stat-label">Store Earnings</div>
            <div class="sp-stat-value text-primary" id="cardStoreShareMonth">{{ $currencyService->formatMinor($summary['store_share_month_minor'], $currency) }}</div>
            <div class="sp-stat-sub">Net store share (month)</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="sp-stat-card" style="background: #F0FDF4; border-color: #BBF7D0;">
            <div class="sp-stat-bar" style="background: #16A34A;"></div>
            <div class="sp-stat-label text-success">Available Balance</div>
            <div class="sp-stat-value text-success" id="cardAvailableBalance">{{ $currencyService->formatMinor($summary['available_balance_minor'], $currency) }}</div>
            <div class="sp-stat-sub">Current withdrawable</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="sp-stat-card">
            <div class="sp-stat-bar" style="background: #F59E0B;"></div>
            <div class="sp-stat-label">Pending Withdrawals</div>
            <div class="sp-stat-value" id="cardPendingWithdrawal" style="color: #B45309;">{{ $currencyService->formatMinor($summary['pending_withdrawal_minor'], $currency) }}</div>
            <div class="sp-stat-sub">Currently reserved</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="sp-stat-card" style="background: #FFFDF5;">
            <div class="sp-stat-bar" style="background: #FFC629;"></div>
            <div class="sp-stat-label">Approved (Month)</div>
            <div class="sp-stat-value" id="cardApprovedMonth">{{ $currencyService->formatMinor($summary['approved_month_minor'], $currency) }}</div>
            <div class="sp-stat-sub">Selected month</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl">
        <div class="sp-stat-card">
            <div class="sp-stat-bar" style="background: #171717;"></div>
            <div class="sp-stat-label">Settled (Month)</div>
            <div class="sp-stat-value" id="cardSettledMonth">{{ $currencyService->formatMinor($summary['settled_month_minor'], $currency) }}</div>
            <div class="sp-stat-sub">Manually paid</div>
        </div>
    </div>
</div>

<!-- Table 1: Store Earnings History -->
<div class="sp-card mb-3 overflow-hidden">
    <div class="p-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="fw-bold mb-0"><i class="mdi mdi-cash-plus me-1 text-success"></i>Store Earnings History</h6>
            <div class="small text-muted">Commission snapshots &amp; Store Share for bookings finalized in the selected month.</div>
        </div>
        <form id="spEarningsSearchForm" class="d-flex align-items-center gap-2">
            <input type="text" id="spEarningsSearchInput" class="form-control form-control-sm" style="width: 190px;" value="{{ $filters['q'] }}" placeholder="Search booking ref...">
            <button type="submit" class="btn btn-sm btn-light border">Search</button>
        </form>
    </div>
    <div class="sp-table-wrap" id="spEarningsTableContainer">
        @include('store-portal.earnings.partials.earnings-table', ['earnings' => $earnings, 'currencyService' => $currencyService])
    </div>
</div>

<!-- Table 2: Withdrawal History -->
<div class="sp-card mb-3 overflow-hidden">
    <div class="p-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="fw-bold mb-0"><i class="mdi mdi-bank-transfer-out me-1 text-primary"></i>Withdrawal History</h6>
            <div class="small text-muted">Store withdrawal requests created during the selected month.</div>
        </div>
        <select id="spWdStatusFilter" class="form-select form-select-sm" style="width: 175px;">
            <option value="">All Statuses</option>
            <option value="pending" {{ $activeWithdrawalStatus === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ $activeWithdrawalStatus === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="manually_settled" {{ $activeWithdrawalStatus === 'manually_settled' ? 'selected' : '' }}>Manually Settled</option>
            <option value="rejected" {{ $activeWithdrawalStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
    </div>
    <div class="sp-table-wrap" id="spWithdrawalsTableContainer">
        @include('store-portal.earnings.partials.withdrawals-table', ['withdrawals' => $withdrawals, 'currencyService' => $currencyService])
    </div>
</div>

<!-- Table 3: Store Account Activity -->
<div class="sp-card overflow-hidden">
    <div class="p-3 border-bottom">
        <h6 class="fw-bold mb-0"><i class="mdi mdi-book-open-page-variant-outline me-1 text-secondary"></i>Store Account Activity</h6>
        <div class="small text-muted">Authoritative ledger events for the selected month (credits, withdrawal reservations, releases, and adjustments).</div>
    </div>
    <div class="sp-table-wrap" id="spLedgerTableContainer">
        @include('store-portal.earnings.partials.ledger-table', ['ledgerEntries' => $ledgerEntries, 'currencyService' => $currencyService])
    </div>
</div>

<!-- Request Withdrawal Modal -->
<div class="modal fade" id="spWithdrawModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom" style="background: #FFF8E5;">
                <div>
                    <h6 class="modal-title fw-bold mb-0">Request Withdrawal</h6>
                    <div class="small text-muted">Withdraw available store balance to a saved bank account.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="spWithdrawForm" novalidate>
                <input type="hidden" name="idempotency_key" id="spWdIdempotencyKey" value="">
                <div class="modal-body p-3 p-md-4">
                    <div class="p-3 rounded-3 mb-3 d-flex align-items-center justify-content-between" style="background: #F0FDF4; border: 1px solid #BBF7D0;">
                        <span class="small fw-semibold text-success">Available Store Balance</span>
                        <span class="fs-5 fw-bold text-success" id="spModalAvailableBalance">{{ $currencyService->formatMinor($summary['available_balance_minor'], $currency) }}</span>
                    </div>

                    <div id="spNoBankWarning" class="alert alert-warning py-2 small {{ $bankAccounts->isEmpty() ? '' : 'd-none' }}">
                        <i class="mdi mdi-alert-outline me-1"></i>
                        Please save at least one store bank account before requesting a withdrawal.
                        <a href="{{ route('store.profile.show') }}" class="fw-bold text-dark text-decoration-underline ms-1">Go to Store Profile &amp; Bank Accounts</a>
                    </div>

                    <div id="spWdModalAlert" class="alert alert-danger py-2 small d-none"></div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1">Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_account_id" id="spWdBankSelect" class="form-select form-select-sm" style="width:100%;">
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc['id'] }}" {{ $acc['is_default'] ? 'selected' : '' }}>
                                    {{ $acc['bank_name'] }} ({{ $acc['account_number_masked'] }}) — {{ $acc['currency'] }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" data-field="bank_account_id"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1">Withdrawal Amount ({{ $currency }}) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="spWdAmount" class="form-control form-control-sm" placeholder="0.00" required>
                        <div class="invalid-feedback" data-field="amount"></div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label small fw-semibold mb-1">Note (Optional)</label>
                        <textarea name="notes" id="spWdNotes" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="Optional note for Admin reviewer"></textarea>
                        <div class="invalid-feedback" data-field="notes"></div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm sp-btn-yellow px-3" id="spWdSubmitBtn" {{ $bankAccounts->isEmpty() ? 'disabled' : '' }}>
                        Submit Withdrawal Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdrawal Detail Modal -->
<div class="modal fade" id="spWdDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom bg-light">
                <h6 class="modal-title fw-bold mb-0" id="spWdDetailTitle">Withdrawal Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4 small" id="spWdDetailBody">Loading...</div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin/dist/libs/select2/dist/js/select2.full.min.js') }}"></script>
<script>
jQuery(function($) {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const earningsUrl = @json(route('store.earnings.index'));
    const modalDataUrl = @json(route('store.withdrawals.modal-data'));
    const submitWdUrl = @json(route('store.withdrawals.store'));
    const withdrawModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('spWithdrawModal'));
    const detailModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('spWdDetailModal'));

    let state = {
        month: @json($summary['period']['month']),
        q: @json($filters['q'] ?? ''),
        withdrawal_status: @json($activeWithdrawalStatus ?? ''),
        earnings_page: 1,
        withdrawals_page: 1,
        ledger_page: 1,
    };

    if ($.fn.select2) {
        $('#spWdBankSelect').select2({ dropdownParent: $('#spWithdrawModal'), width: '100%' });
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    }

    function showBanner(msg, type = 'success') {
        $('#spEarningsAlert').removeClass('d-none alert-success alert-danger').addClass(type === 'success' ? 'alert-success' : 'alert-danger').text(msg);
    }

    function loadEarningsData(pushHistory = true) {
        if (pushHistory && window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('month', state.month);
            window.history.replaceState({}, '', url.toString());
        }

        $.ajax({
            url: earningsUrl,
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            data: state,
            success: function(res) {
                const p = res.summary.period;
                state.month = p.month;
                $('#spMonthLabel').text(p.label);
                $('#spMonthInput').val(p.month);
                $('#spPrevMonthBtn').attr('data-month', p.prev_month).data('month', p.prev_month);
                $('#spNextMonthBtn').attr('data-month', p.next_month).data('month', p.next_month);
                $('#spCurrentMonthBtn').attr('data-month', p.current_month).data('month', p.current_month);

                $('#cardRevenueMonth').text(res.summary_formatted.revenue_month);
                $('#cardCommissionMonth').text(res.summary_formatted.commission_month);
                $('#cardStoreShareMonth').text(res.summary_formatted.store_share_month);
                $('#cardAvailableBalance').text(res.summary_formatted.available_balance);
                $('#cardPendingWithdrawal').text(res.summary_formatted.pending_withdrawal);
                $('#cardApprovedMonth').text(res.summary_formatted.approved_month);
                $('#cardSettledMonth').text(res.summary_formatted.settled_month);

                $('#spEarningsTableContainer').html(res.earnings_html);
                $('#spWithdrawalsTableContainer').html(res.withdrawals_html);
                $('#spLedgerTableContainer').html(res.ledger_html);
            },
            error: function() {
                showBanner('Failed to refresh monthly store earnings.', 'danger');
            }
        });
    }

    $('#spPrevMonthBtn, #spNextMonthBtn, #spCurrentMonthBtn').on('click', function() {
        state.month = $(this).attr('data-month');
        state.earnings_page = 1;
        state.withdrawals_page = 1;
        state.ledger_page = 1;
        loadEarningsData();
    });

    $('#spMonthInput').on('change', function() {
        if ($(this).val()) {
            state.month = $(this).val();
            state.earnings_page = 1;
            state.withdrawals_page = 1;
            state.ledger_page = 1;
            loadEarningsData();
        }
    });

    $('#spEarningsSearchForm').on('submit', function(e) {
        e.preventDefault();
        state.q = $('#spEarningsSearchInput').val();
        state.earnings_page = 1;
        loadEarningsData(false);
    });

    $('#spWdStatusFilter').on('change', function() {
        state.withdrawal_status = $(this).val();
        state.withdrawals_page = 1;
        loadEarningsData(false);
    });

    $(document).on('click', '.sp-ajax-pagination a', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (!href) return;
        const url = new URL(href, window.location.origin);
        const target = $(this).closest('.sp-ajax-pagination').data('target');
        if (target === 'earnings') state.earnings_page = url.searchParams.get('earnings_page') || 1;
        else if (target === 'withdrawals') state.withdrawals_page = url.searchParams.get('withdrawals_page') || 1;
        else if (target === 'ledger') state.ledger_page = url.searchParams.get('ledger_page') || 1;
        loadEarningsData(false);
    });

    $('#spOpenWithdrawModalBtn').on('click', function() {
        $('#spWithdrawForm .is-invalid').removeClass('is-invalid');
        $('#spWithdrawForm .invalid-feedback').text('');
        $('#spWdModalAlert').addClass('d-none').text('');
        $('#spWdAmount').val('');
        $('#spWdNotes').val('');
        $('#spWdIdempotencyKey').val('swd-' + Date.now() + '-' + Math.random().toString(36).substring(2, 10));

        $.ajax({
            url: modalDataUrl,
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function(res) {
                $('#spModalAvailableBalance').text(res.available_balance_formatted);
                const sel = $('#spWdBankSelect').empty();
                if (res.has_bank_account && res.bank_accounts.length) {
                    $('#spNoBankWarning').addClass('d-none');
                    $('#spWdSubmitBtn').prop('disabled', false);
                    res.bank_accounts.forEach(acc => {
                        sel.append(new Option(`${acc.bank_name} (${acc.account_number_masked}) — ${acc.currency}`, acc.id, acc.is_default, acc.is_default));
                    });
                } else {
                    $('#spNoBankWarning').removeClass('d-none');
                    $('#spWdSubmitBtn').prop('disabled', true);
                }
                sel.trigger('change');
                withdrawModal.show();
            }
        });
    });

    let wdSubmitting = false;
    $('#spWithdrawForm').on('submit', function(e) {
        e.preventDefault();
        if (wdSubmitting) return;
        wdSubmitting = true;
        $('#spWithdrawForm .is-invalid').removeClass('is-invalid');
        $('#spWithdrawForm .invalid-feedback').text('');
        $('#spWdModalAlert').addClass('d-none').text('');
        const btn = $('#spWdSubmitBtn').prop('disabled', true).text('Submitting...');

        $.ajax({
            url: submitWdUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: $(this).serialize() + '&month=' + encodeURIComponent(state.month),
            success: function(res) {
                withdrawModal.hide();
                showBanner(res.message || 'Store withdrawal request submitted.', 'success');
                loadEarningsData(false);
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errs = xhr.responseJSON.errors;
                    Object.keys(errs).forEach(field => {
                        $(`#spWithdrawForm [name="${field}"]`).addClass('is-invalid');
                        $(`#spWithdrawForm .invalid-feedback[data-field="${field}"]`).text(errs[field][0]);
                    });
                } else {
                    $('#spWdModalAlert').removeClass('d-none').text(xhr.responseJSON?.message || 'Unable to submit withdrawal request.');
                }
            },
            complete: function() {
                wdSubmitting = false;
                btn.prop('disabled', false).text('Submit Withdrawal Request');
            }
        });
    });

    $(document).on('click', '.sp-view-wd-btn', function() {
        const url = $(this).data('url');
        $('#spWdDetailBody').html('Loading...');
        detailModal.show();
        $.ajax({
            url: url,
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function(res) {
                const w = res.withdrawal;
                const snap = w.bank_snapshot || {};
                let historyHtml = (w.audit_history || []).map(h => `<li class="mb-1"><strong>${escapeHtml(h.event)}</strong> — ${escapeHtml(h.note || '')} <span class="text-muted">(${escapeHtml(h.at || '')})</span></li>`).join('');
                $('#spWdDetailTitle').text('Withdrawal ' + w.withdrawal_reference);
                $('#spWdDetailBody').html(`
                    <div class="d-grid gap-2">
                        <div class="d-flex justify-content-between"><span class="text-muted">Amount:</span><strong>${escapeHtml(w.amount_formatted)}</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Status:</span><span class="badge ${escapeHtml(w.status_meta.badge)}">${escapeHtml(w.status_meta.label)}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Bank Account:</span><span>${escapeHtml(snap.bank_name)} (${escapeHtml(snap.account_number_masked)})</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Requested At:</span><span>${escapeHtml(w.requested_at)}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Available Before Request:</span><span>${escapeHtml(w.available_before_formatted)}</span></div>
                        ${w.rejection_reason ? `<div class="alert alert-danger py-2 mb-0"><strong>Rejection Reason:</strong> ${escapeHtml(w.rejection_reason)}</div>` : ''}
                        ${w.payment_date ? `<div class="alert alert-success py-2 mb-0"><strong>Manual Settlement:</strong> ${escapeHtml(w.payment_date)} ${w.external_reference ? '(Ref: ' + escapeHtml(w.external_reference) + ')' : ''}</div>` : ''}
                        <div class="border-top pt-2 mt-1">
                            <div class="fw-semibold mb-1">Audit History</div>
                            <ul class="ps-3 mb-0">${historyHtml || '<li>No events recorded.</li>'}</ul>
                        </div>
                    </div>
                `);
            }
        });
    });
});
</script>
@endpush
