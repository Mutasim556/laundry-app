@extends('delivery-person-portal.layouts.app')
@section('title', 'My Earnings')
@section('page_title', 'My Earnings')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/dist/libs/select2/dist/css/select2.min.css') }}">
<style>
    .dp-stat-card {
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 14px;
        padding: 0.9rem 1rem;
        height: 100%;
        position: relative;
        overflow: hidden;
        animation: dpEarnFadeUp 0.52s cubic-bezier(0.22, 1, 0.36, 1) both;
        transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.24s ease;
    }
    .dp-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }
    .dp-earn-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .dp-stat-card:hover .dp-earn-icon {
        transform: scale(1.12) rotate(-6deg);
    }
    .dp-card {
        animation: dpEarnFadeUp 0.52s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    #dpOpenWithdrawModalBtn {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    #dpOpenWithdrawModalBtn:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(245, 158, 11, 0.28);
    }
    .tab-pane.active {
        animation: dpEarnFadeUp 0.32s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    @keyframes dpEarnFadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .dp-stat-card, .dp-card, .tab-pane.active { animation: none !important; }
        .dp-stat-card, .dp-earn-icon, #dpOpenWithdrawModalBtn { transition: none !important; }
    }
    .dp-stat-card .dp-stat-bar {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
    }
    .dp-stat-label {
        font-size: 0.74rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #475569;
    }
    .dp-stat-value {
        font-size: 1.35rem;
        font-weight: 800;
        color: #171717;
        margin-top: 0.25rem;
    }
    .dp-stat-sub {
        font-size: 0.72rem;
        color: #64748B;
        margin-top: 0.15rem;
    }
    .dp-table-wrap {
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
<div id="dpEarningsAlert" class="alert d-none py-2 small mb-3"></div>

<!-- Page Header & Month Selector -->
<div class="dp-card p-3 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border-color: #FDE68A;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h5 class="fw-bold mb-0 d-inline-flex align-items-center gap-2" style="color: #171717;">
                <span class="dp-earn-icon" style="background:#FEF3C7; color:#B45309;" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                </span>
                <span>My Earnings</span>
            </h5>
            <div class="small text-muted mt-1">Monthly Earnings &amp; Withdrawal Management (<span id="dpMonthLabel">{{ $summary['period']['label'] }}</span>)</div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="btn-group btn-group-sm" role="group" aria-label="Month Navigation">
                <button type="button" class="btn btn-light border" id="dpPrevMonthBtn" data-month="{{ $summary['period']['prev_month'] }}">
                    &lsaquo; Previous
                </button>
                <input type="month" id="dpMonthInput" class="form-control form-control-sm rounded-0 border-start-0 border-end-0" style="width: 145px;" value="{{ $summary['period']['month'] }}">
                <button type="button" class="btn btn-light border" id="dpNextMonthBtn" data-month="{{ $summary['period']['next_month'] }}">
                    Next &rsaquo;
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-dark" id="dpCurrentMonthBtn" data-month="{{ $summary['period']['current_month'] }}">
                Current Month
            </button>
            <button type="button" class="btn btn-sm dp-btn-yellow px-3 d-inline-flex align-items-center gap-1" id="dpOpenWithdrawModalBtn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 22h18"/><path d="M6 18v-7"/><path d="M18 18v-7"/><polygon points="12 2 20 7 4 7"/></svg>
                <span>Request Withdrawal</span>
            </button>
        </div>
    </div>
</div>

<!-- 6 Compact Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="dp-stat-card" style="animation-delay: 20ms;">
            <div class="dp-stat-bar" style="background: #8B5CF6;"></div>
            <div class="dp-stat-label">Earned This Month</div>
            <div class="dp-stat-value" id="cardEarnedMonth">{{ $currencyService->formatMinor($summary['earned_month_minor'], $currency) }}</div>
            <div class="dp-stat-sub">Selected month activity</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="dp-stat-card" style="animation-delay: 60ms;">
            <div class="dp-stat-bar" style="background: #3B82F6;"></div>
            <div class="dp-stat-label">Collection Earnings</div>
            <div class="dp-stat-value" id="cardCollectionMonth" style="color: #1D4ED8;">{{ $currencyService->formatMinor($summary['collection_month_minor'], $currency) }}</div>
            <div class="dp-stat-sub">Selected month jobs</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="dp-stat-card" style="background: #FFFDF5; animation-delay: 100ms;">
            <div class="dp-stat-bar" style="background: #FFC629;"></div>
            <div class="dp-stat-label">Final Delivery</div>
            <div class="dp-stat-value" id="cardFinalMonth">{{ $currencyService->formatMinor($summary['final_delivery_month_minor'], $currency) }}</div>
            <div class="dp-stat-sub">Selected month jobs</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="dp-stat-card" style="background: #F0FDF4; border-color: #BBF7D0; animation-delay: 140ms;">
            <div class="dp-stat-bar" style="background: #16A34A;"></div>
            <div class="dp-stat-label text-success">Available Balance</div>
            <div class="dp-stat-value text-success" id="cardAvailableBalance">{{ $currencyService->formatMinor($summary['available_balance_minor'], $currency) }}</div>
            <div class="dp-stat-sub">Current withdrawable balance</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="dp-stat-card" style="animation-delay: 180ms;">
            <div class="dp-stat-bar" style="background: #F59E0B;"></div>
            <div class="dp-stat-label">Pending Withdrawals</div>
            <div class="dp-stat-value" id="cardPendingWithdrawal" style="color: #B45309;">{{ $currencyService->formatMinor($summary['pending_withdrawal_minor'], $currency) }}</div>
            <div class="dp-stat-sub">Currently reserved</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="dp-stat-card" style="animation-delay: 220ms;">
            <div class="dp-stat-bar" style="background: #171717;"></div>
            <div class="dp-stat-label">Approved (Month)</div>
            <div class="dp-stat-value" id="cardApprovedMonth">{{ $currencyService->formatMinor($summary['approved_month_minor'], $currency) }}</div>
            <div class="dp-stat-sub">Selected month requests</div>
        </div>
    </div>
</div>

<!-- Table 1: Monthly Earnings Transaction History -->
<div class="dp-card mb-3 overflow-hidden">
    <div class="p-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="fw-bold mb-0"><i class="mdi mdi-cash-plus me-1 text-success"></i>Earnings History</h6>
            <div class="small text-muted">Collection &amp; Final Delivery job earnings recognized in the selected month.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <select id="dpJobTypeFilter" class="form-select form-select-sm" style="width: 170px;">
                <option value="">All Job Types</option>
                <option value="customer_collection" {{ $activeJobType === 'customer_collection' ? 'selected' : '' }}>Collection</option>
                <option value="final_delivery" {{ $activeJobType === 'final_delivery' ? 'selected' : '' }}>Final Delivery</option>
            </select>
        </div>
    </div>
    <div class="dp-table-wrap" id="dpEarningsTableContainer">
        @include('delivery-person-portal.partials.earnings-table', ['earningsEntries' => $earningsEntries, 'currencyService' => $currencyService])
    </div>
</div>

<!-- Table 2: Monthly Withdrawal History -->
<div class="dp-card mb-3 overflow-hidden">
    <div class="p-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h6 class="fw-bold mb-0"><i class="mdi mdi-bank-transfer-out me-1 text-primary"></i>Withdrawal History</h6>
            <div class="small text-muted">Withdrawal requests created during the selected month.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <select id="dpWdStatusFilter" class="form-select form-select-sm" style="width: 175px;">
                <option value="">All Statuses</option>
                <option value="pending" {{ $activeWithdrawalStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ $activeWithdrawalStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="manually_settled" {{ $activeWithdrawalStatus === 'manually_settled' ? 'selected' : '' }}>Manually Settled</option>
                <option value="rejected" {{ $activeWithdrawalStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
    </div>
    <div class="dp-table-wrap" id="dpWithdrawalsTableContainer">
        @include('delivery-person-portal.partials.withdrawals-table', ['withdrawals' => $withdrawals, 'currencyService' => $currencyService])
    </div>
</div>

<!-- Table 3: Account Activity Ledger -->
<div class="dp-card overflow-hidden">
    <div class="p-3 border-bottom">
        <h6 class="fw-bold mb-0"><i class="mdi mdi-book-open-page-variant-outline me-1 text-secondary"></i>Account Activity Ledger</h6>
        <div class="small text-muted">Authoritative ledger events affecting your available balance (credits, reservations, releases, and corrections).</div>
    </div>
    <div class="dp-table-wrap" id="dpLedgerTableContainer">
        @include('delivery-person-portal.partials.ledger-table', ['ledgerEntries' => $ledgerEntries, 'currencyService' => $currencyService])
    </div>
</div>

<!-- Request Withdrawal Modal (AJAX) -->
<div class="modal fade" id="dpWithdrawModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom" style="background: #FFF8E5;">
                <div>
                    <h6 class="modal-title fw-bold mb-0">Request Withdrawal</h6>
                    <div class="small text-muted">Withdraw your available Delivery Person earnings to a saved bank account.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="dpWithdrawForm" novalidate>
                <input type="hidden" name="idempotency_key" id="dpWdIdempotencyKey" value="">
                <div class="modal-body p-3 p-md-4">
                    <div class="p-3 rounded-3 mb-3 d-flex align-items-center justify-content-between" style="background: #F0FDF4; border: 1px solid #BBF7D0;">
                        <span class="small fw-semibold text-success">Current Available Balance</span>
                        <span class="fs-5 fw-bold text-success" id="dpModalAvailableBalance">{{ $currencyService->formatMinor($summary['available_balance_minor'], $currency) }}</span>
                    </div>

                    <div id="dpNoBankWarning" class="alert alert-warning py-2 small {{ $bankAccounts->isEmpty() ? '' : 'd-none' }}">
                        <i class="mdi mdi-alert-outline me-1"></i>
                        You must save at least one bank account before requesting a withdrawal.
                        <a href="{{ route('delivery-person.profile') }}" class="fw-bold text-dark text-decoration-underline ms-1">Go to My Profile &amp; Bank Accounts</a>
                    </div>

                    <div id="dpWdModalAlert" class="alert alert-danger py-2 small d-none"></div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1">Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_account_id" id="dpWdBankSelect" class="form-select form-select-sm" style="width:100%;">
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
                        <input type="number" step="0.01" min="0.01" name="amount" id="dpWdAmount" class="form-control form-control-sm" placeholder="0.00" required>
                        <div class="invalid-feedback" data-field="amount"></div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label small fw-semibold mb-1">Note (Optional)</label>
                        <textarea name="notes" id="dpWdNotes" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="Optional note for Admin reviewer"></textarea>
                        <div class="invalid-feedback" data-field="notes"></div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm dp-btn-yellow px-3" id="dpWdSubmitBtn" {{ $bankAccounts->isEmpty() ? 'disabled' : '' }}>
                        Submit Withdrawal Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Withdrawal Detail Modal (AJAX) -->
<div class="modal fade" id="dpWdDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom" style="background: #F8FAFC;">
                <h6 class="modal-title fw-bold mb-0" id="dpWdDetailTitle">Withdrawal Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4 small" id="dpWdDetailBody">
                Loading...
            </div>
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
(function($) {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const earningsUrl = @json(route('delivery-person.earnings'));
    const modalDataUrl = @json(route('delivery-person.withdrawals.modal-data'));
    const submitWdUrl = @json(route('delivery-person.withdrawals.store'));
    const withdrawModal = new bootstrap.Modal(document.getElementById('dpWithdrawModal'));
    const detailModal = new bootstrap.Modal(document.getElementById('dpWdDetailModal'));

    let state = {
        month: @json($summary['period']['month']),
        job_type: @json($activeJobType ?? ''),
        withdrawal_status: @json($activeWithdrawalStatus ?? ''),
        earnings_page: 1,
        withdrawals_page: 1,
        ledger_page: 1,
    };

    if ($.fn.select2) {
        $('#dpWdBankSelect').select2({
            dropdownParent: $('#dpWithdrawModal'),
            width: '100%'
        });
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    }

    function showBanner(msg, type = 'success') {
        const box = $('#dpEarningsAlert');
        box.removeClass('d-none alert-success alert-danger').addClass(type === 'success' ? 'alert-success' : 'alert-danger').text(msg);
    }

    function loadEarningsData(pushHistory = true) {
        const params = {
            month: state.month,
            job_type: state.job_type,
            withdrawal_status: state.withdrawal_status,
            earnings_page: state.earnings_page,
            withdrawals_page: state.withdrawals_page,
            ledger_page: state.ledger_page,
        };

        if (pushHistory && window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('month', state.month);
            window.history.replaceState({}, '', url.toString());
        }

        $.ajax({
            url: earningsUrl,
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            data: params,
            success: function(res) {
                const p = res.summary.period;
                state.month = p.month;
                $('#dpMonthLabel').text(p.label);
                $('#dpMonthInput').val(p.month);
                $('#dpPrevMonthBtn').attr('data-month', p.prev_month).data('month', p.prev_month);
                $('#dpNextMonthBtn').attr('data-month', p.next_month).data('month', p.next_month);
                $('#dpCurrentMonthBtn').attr('data-month', p.current_month).data('month', p.current_month);

                $('#cardEarnedMonth').text(res.summary_formatted.earned_month);
                $('#cardCollectionMonth').text(res.summary_formatted.collection_month);
                $('#cardFinalMonth').text(res.summary_formatted.final_delivery_month);
                $('#cardAvailableBalance').text(res.summary_formatted.available_balance);
                $('#cardPendingWithdrawal').text(res.summary_formatted.pending_withdrawal);
                $('#cardApprovedMonth').text(res.summary_formatted.approved_month);

                $('#dpEarningsTableContainer').html(res.earnings_html);
                $('#dpWithdrawalsTableContainer').html(res.withdrawals_html);
                $('#dpLedgerTableContainer').html(res.ledger_html);
            },
            error: function() {
                showBanner('Failed to refresh monthly earnings data.', 'danger');
            }
        });
    }

    $('#dpPrevMonthBtn, #dpNextMonthBtn, #dpCurrentMonthBtn').on('click', function() {
        state.month = $(this).attr('data-month');
        state.earnings_page = 1;
        state.withdrawals_page = 1;
        state.ledger_page = 1;
        loadEarningsData();
    });

    $('#dpMonthInput').on('change', function() {
        if ($(this).val()) {
            state.month = $(this).val();
            state.earnings_page = 1;
            state.withdrawals_page = 1;
            state.ledger_page = 1;
            loadEarningsData();
        }
    });

    $('#dpJobTypeFilter').on('change', function() {
        state.job_type = $(this).val();
        state.earnings_page = 1;
        loadEarningsData(false);
    });

    $('#dpWdStatusFilter').on('change', function() {
        state.withdrawal_status = $(this).val();
        state.withdrawals_page = 1;
        loadEarningsData(false);
    });

    $(document).on('click', '.dp-ajax-pagination a', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (!href) return;
        const url = new URL(href, window.location.origin);
        const target = $(this).closest('.dp-ajax-pagination').data('target');
        if (target === 'earnings') {
            state.earnings_page = url.searchParams.get('earnings_page') || 1;
        } else if (target === 'withdrawals') {
            state.withdrawals_page = url.searchParams.get('withdrawals_page') || 1;
        } else if (target === 'ledger') {
            state.ledger_page = url.searchParams.get('ledger_page') || 1;
        }
        loadEarningsData(false);
    });

    function generateIdempotencyKey() {
        return 'wdl-' + Date.now() + '-' + Math.random().toString(36).substring(2, 10);
    }

    $('#dpOpenWithdrawModalBtn').on('click', function() {
        $('#dpWithdrawForm .is-invalid').removeClass('is-invalid');
        $('#dpWithdrawForm .invalid-feedback').text('');
        $('#dpWdModalAlert').addClass('d-none').text('');
        $('#dpWdAmount').val('');
        $('#dpWdNotes').val('');
        $('#dpWdIdempotencyKey').val(generateIdempotencyKey());

        $.ajax({
            url: modalDataUrl,
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function(res) {
                $('#dpModalAvailableBalance').text(res.available_balance_formatted);
                const sel = $('#dpWdBankSelect').empty();
                if (res.has_bank_account && res.bank_accounts.length) {
                    $('#dpNoBankWarning').addClass('d-none');
                    $('#dpWdSubmitBtn').prop('disabled', false);
                    res.bank_accounts.forEach(acc => {
                        const opt = new Option(`${acc.bank_name} (${acc.account_number_masked}) — ${acc.currency}`, acc.id, acc.is_default, acc.is_default);
                        sel.append(opt);
                    });
                } else {
                    $('#dpNoBankWarning').removeClass('d-none');
                    $('#dpWdSubmitBtn').prop('disabled', true);
                }
                sel.trigger('change');
                withdrawModal.show();
            },
            error: function() {
                showBanner('Could not load available balance and bank accounts.', 'danger');
            }
        });
    });

    let wdSubmitting = false;
    $('#dpWithdrawForm').on('submit', function(e) {
        e.preventDefault();
        if (wdSubmitting) return;
        wdSubmitting = true;
        $('#dpWithdrawForm .is-invalid').removeClass('is-invalid');
        $('#dpWithdrawForm .invalid-feedback').text('');
        $('#dpWdModalAlert').addClass('d-none').text('');
        const btn = $('#dpWdSubmitBtn').prop('disabled', true).text('Submitting...');

        $.ajax({
            url: submitWdUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: $(this).serialize() + '&month=' + encodeURIComponent(state.month),
            success: function(res) {
                withdrawModal.hide();
                showBanner(res.message || 'Withdrawal request submitted.', 'success');
                loadEarningsData(false);
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errs = xhr.responseJSON.errors;
                    Object.keys(errs).forEach(field => {
                        const input = $(`#dpWithdrawForm [name="${field}"]`);
                        input.addClass('is-invalid');
                        $(`#dpWithdrawForm .invalid-feedback[data-field="${field}"]`).text(errs[field][0]);
                    });
                } else {
                    $('#dpWdModalAlert').removeClass('d-none').text(xhr.responseJSON?.message || 'Unable to submit withdrawal request.');
                }
            },
            complete: function() {
                wdSubmitting = false;
                btn.prop('disabled', false).text('Submit Withdrawal Request');
            }
        });
    });

    $(document).on('click', '.dp-view-wd-btn', function() {
        const url = $(this).data('url');
        $('#dpWdDetailBody').html('Loading...');
        detailModal.show();
        $.ajax({
            url: url,
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function(res) {
                const w = res.withdrawal;
                const snap = w.bank_snapshot || {};
                let historyHtml = '';
                (w.audit_history || []).forEach(h => {
                    historyHtml += `<li class="mb-1"><strong>${escapeHtml(h.event)}</strong> — ${escapeHtml(h.note || '')} <span class="text-muted">(${escapeHtml(h.at || '')})</span></li>`;
                });
                $('#dpWdDetailTitle').text('Withdrawal ' + w.withdrawal_reference);
                $('#dpWdDetailBody').html(`
                    <div class="d-grid gap-2">
                        <div class="d-flex justify-content-between"><span class="text-muted">Amount:</span><strong>${escapeHtml(w.amount_formatted)}</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Status:</span><span class="badge ${escapeHtml(w.status_meta.badge)}">${escapeHtml(w.status_meta.label)}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Bank Account:</span><span>${escapeHtml(snap.bank_name)} (${escapeHtml(snap.account_number_masked)})</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Requested At:</span><span>${escapeHtml(w.requested_at)}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Available Before Request:</span><span>${escapeHtml(w.available_before_formatted)}</span></div>
                        ${w.rejection_reason ? `<div class="alert alert-danger py-2 mb-0"><strong>Rejection Reason:</strong> ${escapeHtml(w.rejection_reason)}</div>` : ''}
                        ${w.payment_date ? `<div class="alert alert-success py-2 mb-0"><strong>Manual Settlement:</strong> ${escapeHtml(w.payment_date)} ${w.external_reference ? '(Ref: ' + escapeHtml(w.external_reference) + ')' : ''}</div>` : ''}
                        ${w.notes ? `<div><strong>Note:</strong> ${escapeHtml(w.notes)}</div>` : ''}
                        <div class="border-top pt-2 mt-1">
                            <div class="fw-semibold mb-1">Audit History</div>
                            <ul class="ps-3 mb-0">${historyHtml || '<li>No events recorded.</li>'}</ul>
                        </div>
                    </div>
                `);
            },
            error: function() {
                $('#dpWdDetailBody').html('<div class="text-danger">Failed to load withdrawal details.</div>');
            }
        });
    });
})(jQuery);
</script>
@endpush
