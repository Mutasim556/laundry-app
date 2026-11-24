@extends('admin::layouts.app')

@section('title', 'Delivery Person Withdrawals - Admin Panel')

@section('content')
<div class="container-fluid py-3">
    <!-- Header + Month Selector -->
    <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%); border: 1px solid #FDE68A !important;">
        <div class="card-body py-3 px-3 px-md-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="fw-bold mb-0">Delivery Person Withdrawal Requests</h4>
                <div class="small text-muted">
                    Month-wise review, approval, rejection &amp; manual settlement (<span id="admWdMonthLabel">{{ $activeMonth === 'all' ? 'All Months' : $period['label'] }}</span>)
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="btn-group btn-group-sm" role="group" aria-label="Month Navigation">
                    <button type="button" class="btn btn-light border" id="admWdPrevMonthBtn" data-month="{{ $period['prev_month'] }}">&lsaquo; Previous</button>
                    <input type="month" id="admWdMonth" class="form-control form-control-sm rounded-0 border-start-0 border-end-0" style="width: 145px;" value="{{ $activeMonth === 'all' ? $period['month'] : $activeMonth }}">
                    <button type="button" class="btn btn-light border" id="admWdNextMonthBtn" data-month="{{ $period['next_month'] }}">Next &rsaquo;</button>
                </div>
                <button type="button" class="btn btn-sm btn-outline-dark" id="admWdCurrMonthBtn" data-month="{{ $period['current_month'] }}">Current Month</button>
                <button type="button" class="btn btn-sm {{ $activeMonth === 'all' ? 'btn-dark' : 'btn-outline-secondary' }}" id="admWdAllMonthsBtn" data-month="all">All Months</button>
                <a href="{{ route('admin.delivery-payouts.index') }}" class="btn btn-sm btn-outline-secondary">Payouts Ledger</a>
            </div>
        </div>
    </div>

    <div id="admWdAlert" class="alert d-none py-2 small mb-3"></div>

    <!-- Month-wise KPI Cards -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-top: 3px solid #F59E0B !important;">
                <div class="card-body py-2 px-3">
                    <div class="small text-muted fw-semibold">Pending Review</div>
                    <div class="fs-4 fw-bold text-warning" id="kpiPending">{{ $kpis['pending'] }}</div>
                    <div class="small text-muted" id="kpiPendingAmt">{{ $kpis['pending_formatted'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-top: 3px solid #3B82F6 !important;">
                <div class="card-body py-2 px-3">
                    <div class="small text-muted fw-semibold">Approved (Awaiting Transfer)</div>
                    <div class="fs-4 fw-bold text-primary" id="kpiApproved">{{ $kpis['approved'] }}</div>
                    <div class="small text-muted" id="kpiApprovedAmt">{{ $kpis['approved_formatted'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-top: 3px solid #16A34A !important;">
                <div class="card-body py-2 px-3">
                    <div class="small text-muted fw-semibold">Manually Settled</div>
                    <div class="fs-4 fw-bold text-success" id="kpiSettled">{{ $kpis['settled'] }}</div>
                    <div class="small text-muted" id="kpiSettledAmt">{{ $kpis['settled_formatted'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-top: 3px solid #EF4444 !important;">
                <div class="card-body py-2 px-3">
                    <div class="small text-muted fw-semibold">Rejected</div>
                    <div class="fs-4 fw-bold text-danger" id="kpiRejected">{{ $kpis['rejected'] }}</div>
                    <div class="small text-muted" id="kpiRejectedAmt">{{ $kpis['rejected_formatted'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <form id="admWdFilterForm" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold mb-1">Search</label>
                    <input type="text" name="search" id="admWdSearch" class="form-control form-control-sm" value="{{ $search }}" placeholder="Reference, Delivery Person...">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Delivery Person</label>
                    <select name="delivery_person_id" id="admWdDp" class="form-select form-select-sm">
                        <option value="">All Delivery Persons</option>
                        @foreach($persons as $p)
                            <option value="{{ $p->id }}" {{ (string) $activeDpId === (string) $p->id ? 'selected' : '' }}>{{ $p->full_name }} ({{ $p->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Status</label>
                    <select name="status" id="admWdStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ $activeStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $activeStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="manually_settled" {{ $activeStatus === 'manually_settled' ? 'selected' : '' }}>Manually Settled</option>
                        <option value="rejected" {{ $activeStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-warning fw-semibold flex-grow-1">Filter</button>
                    <button type="button" id="admWdResetBtn" class="btn btn-sm btn-light border">Reset</button>
                </div>
            </form>
        </div>
        <div class="card-body p-0" id="admWdTableContainer">
            @include('admin::delivery-persons.partials.withdrawals-table', ['withdrawals' => $withdrawals, 'currencyService' => $currencyService])
        </div>
    </div>
</div>

<!-- View Detail Modal -->
<div class="modal fade" id="admWdViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" id="admWdViewTitle">Withdrawal Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body small" id="admWdViewBody">Loading...</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Confirmation Modal -->
<div class="modal fade" id="admWdApproveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary-subtle">
                <h6 class="modal-title fw-bold text-primary" id="admWdApproveTitle">Approve Withdrawal</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="admWdApproveForm">
                <input type="hidden" id="admWdApproveUrl" value="">
                <div class="modal-body small">
                    <div id="admWdApproveError" class="alert alert-danger py-2 d-none"></div>
                    <div class="p-2 rounded bg-light border mb-3">
                        <div class="d-flex justify-content-between"><span class="text-muted">Delivery Person:</span><strong id="admWdApproveDp">-</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Amount:</span><strong id="admWdApproveAmt">-</strong></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Destination Bank:</span><strong id="admWdApproveBank">-</strong></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <select name="payout_method" id="admWdApproveMethod" class="form-select form-select-sm" required>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Hand Cash</option>
                            <option value="other">Other Manual Method</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold" id="admWdApproveAccountLabel">Paid From Account (Bank / Cash Account)</label>
                        <input type="text" name="paid_from_account" id="admWdApproveAccount" class="form-control form-control-sm" maxlength="160" placeholder="e.g. Company HSBC Business Account or Main Hand Cash">
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Approval Note (Optional)</label>
                        <textarea name="note" id="admWdApproveNote" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="Optional note"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="admWdApproveSubmitBtn">Confirm Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="admWdRejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger-subtle">
                <h6 class="modal-title fw-bold text-danger" id="admWdRejectTitle">Reject Withdrawal</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="admWdRejectForm">
                <input type="hidden" id="admWdRejectUrl" value="">
                <div class="modal-body small">
                    <div id="admWdRejectError" class="alert alert-danger py-2 d-none"></div>
                    <p class="text-muted mb-2">Rejecting this request will release the reserved funds back to the Delivery Person's available balance.</p>
                    <label class="form-label fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" id="admWdRejectReason" class="form-control form-control-sm" rows="3" required minlength="3" maxlength="500" placeholder="Enter reason for rejection"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger" id="admWdRejectSubmitBtn">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Manual Settlement Modal -->
<div class="modal fade" id="admWdSettleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success-subtle">
                <h6 class="modal-title fw-bold text-success" id="admWdSettleTitle">Mark Manually Settled</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="admWdSettleForm">
                <input type="hidden" id="admWdSettleUrl" value="">
                <div class="modal-body small">
                    <div id="admWdSettleError" class="alert alert-danger py-2 d-none"></div>
                    <p class="text-muted mb-3">Confirm that the external manual bank transfer has been completed outside the application.</p>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Actual Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" id="admWdSettleDate" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">External Bank Reference</label>
                        <input type="text" name="external_reference" id="admWdSettleRef" class="form-control form-control-sm" maxlength="120" placeholder="e.g. BACS-992831">
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Payment Notes</label>
                        <textarea name="settlement_notes" id="admWdSettleNotes" class="form-control form-control-sm" rows="2" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success" id="admWdSettleSubmitBtn">Confirm Manual Settlement</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
jQuery(function($) {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const indexUrl = @json(route('admin.delivery-person-withdrawals.index'));
    let activeMonth = @json($activeMonth);
    let currentPage = 1;

    function getModal(id) {
        const el = document.getElementById(id);
        if (window.bootstrap && window.bootstrap.Modal) {
            return window.bootstrap.Modal.getOrCreateInstance(el);
        }
        return {
            show: () => $(el).modal('show'),
            hide: () => $(el).modal('hide')
        };
    }

    function showAlert(msg, type = 'success') {
        $('#admWdAlert').removeClass('d-none alert-success alert-danger').addClass(type === 'success' ? 'alert-success' : 'alert-danger').text(msg);
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    }

    function extractError(xhr, fallback) {
        if (xhr.responseJSON) {
            if (xhr.responseJSON.errors) {
                const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                if (firstKey && xhr.responseJSON.errors[firstKey][0]) {
                    return xhr.responseJSON.errors[firstKey][0];
                }
            }
            if (xhr.responseJSON.message) {
                return xhr.responseJSON.message;
            }
        }
        return fallback;
    }

    function reloadTable(pushUrl = true) {
        const queryData = {
            month: activeMonth,
            search: $('#admWdSearch').val(),
            delivery_person_id: $('#admWdDp').val(),
            status: $('#admWdStatus').val(),
            page: currentPage
        };

        if (pushUrl && window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('month', activeMonth);
            window.history.replaceState({}, '', url.toString());
        }

        $.ajax({
            url: indexUrl,
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            data: queryData,
            success: function(res) {
                if (res.period) {
                    $('#admWdPrevMonthBtn').attr('data-month', res.period.prev_month).data('month', res.period.prev_month);
                    $('#admWdNextMonthBtn').attr('data-month', res.period.next_month).data('month', res.period.next_month);
                    $('#admWdCurrMonthBtn').attr('data-month', res.period.current_month).data('month', res.period.current_month);
                    if (res.active_month === 'all') {
                        $('#admWdMonthLabel').text('All Months');
                        $('#admWdAllMonthsBtn').removeClass('btn-outline-secondary').addClass('btn-dark');
                    } else {
                        $('#admWdMonthLabel').text(res.period.label);
                        $('#admWdMonth').val(res.period.month);
                        $('#admWdAllMonthsBtn').removeClass('btn-dark').addClass('btn-outline-secondary');
                    }
                }
                $('#kpiPending').text(res.counts.pending);
                $('#kpiApproved').text(res.counts.approved);
                $('#kpiSettled').text(res.counts.settled);
                $('#kpiRejected').text(res.counts.rejected);
                $('#kpiPendingAmt').text(res.counts.pending_formatted);
                $('#kpiApprovedAmt').text(res.counts.approved_formatted);
                $('#kpiSettledAmt').text(res.counts.settled_formatted);
                $('#kpiRejectedAmt').text(res.counts.rejected_formatted);
                $('#admWdTableContainer').html(res.table_html);
            },
            error: function(xhr) {
                showAlert(extractError(xhr, 'Failed to load withdrawal requests.'), 'danger');
            }
        });
    }

    $('#admWdPrevMonthBtn, #admWdNextMonthBtn, #admWdCurrMonthBtn, #admWdAllMonthsBtn').on('click', function() {
        activeMonth = $(this).attr('data-month');
        currentPage = 1;
        reloadTable(true);
    });

    $('#admWdMonth').on('change', function() {
        if ($(this).val()) {
            activeMonth = $(this).val();
            currentPage = 1;
            reloadTable(true);
        }
    });

    $('#admWdFilterForm').on('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        reloadTable(false);
    });

    $('#admWdDp, #admWdStatus').on('change', function() {
        currentPage = 1;
        reloadTable(false);
    });

    $('#admWdResetBtn').on('click', function() {
        $('#admWdFilterForm')[0].reset();
        activeMonth = $('#admWdCurrMonthBtn').attr('data-month');
        currentPage = 1;
        reloadTable(true);
    });

    $(document).on('click', '.adm-wd-pagination a', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (!href) return;
        const url = new URL(href, window.location.origin);
        currentPage = url.searchParams.get('page') || 1;
        reloadTable(false);
    });

    $(document).on('click', '.adm-wd-view-btn', function() {
        const url = $(this).data('url');
        $('#admWdViewBody').html('Loading...');
        getModal('admWdViewModal').show();
        $.ajax({
            url: url,
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function(res) {
                const w = res.withdrawal;
                const snap = w.bank_snapshot || {};
                let ledgerRows = (w.recent_ledger || []).map(e => `<tr><td>${escapeHtml(e.occurred_at)}</td><td>${escapeHtml(e.reference)}</td><td>${escapeHtml(e.type)}</td><td class="text-end ${e.direction === 'credit' ? 'text-success' : 'text-danger'}">${e.direction === 'credit' ? '+' : '-'}${escapeHtml(e.amount_formatted)}</td></tr>`).join('');
                $('#admWdViewTitle').text(w.withdrawal_reference + ' — ' + w.delivery_person_name);
                $('#admWdViewBody').html(`
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="fw-bold mb-2">Request &amp; Balance Snapshot</div>
                            <div><strong>Delivery Person:</strong> ${escapeHtml(w.delivery_person_name)} (${escapeHtml(w.delivery_person_code)})</div>
                            <div><strong>Month:</strong> ${escapeHtml(w.request_month)}</div>
                            <div><strong>Requested Amount:</strong> ${escapeHtml(w.amount_formatted)}</div>
                            <div><strong>Available Before Request:</strong> ${escapeHtml(w.available_before_formatted)}</div>
                            <div><strong>Current Available Balance:</strong> ${escapeHtml(w.current_available_formatted)}</div>
                            <div><strong>Status:</strong> <span class="badge ${escapeHtml(w.status_meta.badge)}">${escapeHtml(w.status_meta.label)}</span></div>
                            ${snap.admin_payout_method_label ? `<div><strong>Approved Method:</strong> ${escapeHtml(snap.admin_payout_method_label)}${snap.admin_paid_from_account ? ' (' + escapeHtml(snap.admin_paid_from_account) + ')' : ''}</div>` : ''}
                            ${w.rejection_reason ? `<div class="text-danger mt-1"><strong>Rejection Reason:</strong> ${escapeHtml(w.rejection_reason)}</div>` : ''}
                            ${w.payment_date ? `<div class="text-success mt-1"><strong>Settled Date:</strong> ${escapeHtml(w.payment_date)} ${w.external_reference ? '(Ref: ' + escapeHtml(w.external_reference) + ')' : ''}</div>` : ''}
                        </div>
                        <div class="col-md-6">
                            <div class="fw-bold mb-2">Masked Bank Snapshot</div>
                            <div><strong>Bank:</strong> ${escapeHtml(snap.bank_name)}</div>
                            <div><strong>Holder:</strong> ${escapeHtml(snap.account_holder_name)}</div>
                            <div><strong>Account:</strong> ${escapeHtml(snap.account_number_masked)}</div>
                            <div><strong>Country / Currency:</strong> ${escapeHtml(snap.country_code)} / ${escapeHtml(snap.currency)}</div>
                        </div>
                    </div>
                    <hr>
                    <div class="fw-bold mb-1">Recent Ledger Entries</div>
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light"><tr><th>Date</th><th>Ref</th><th>Type</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>${ledgerRows || '<tr><td colspan="4" class="text-center text-muted">None</td></tr>'}</tbody>
                    </table>
                `);
            },
            error: function(xhr) {
                $('#admWdViewBody').html(`<div class="text-danger">${escapeHtml(extractError(xhr, 'Failed to load withdrawal details.'))}</div>`);
            }
        });
    });

    $(document).on('click', '.adm-wd-approve-btn', function() {
        $('#admWdApproveUrl').val($(this).data('url'));
        $('#admWdApproveTitle').text('Approve ' + $(this).data('ref'));
        $('#admWdApproveDp').text($(this).data('dp') || '-');
        $('#admWdApproveAmt').text($(this).data('amount') || '-');
        $('#admWdApproveBank').text($(this).data('bank') || '-');
        $('#admWdApproveMethod').val('bank_transfer');
        $('#admWdApproveAccount').val('');
        $('#admWdApproveNote').val('');
        $('#admWdApproveError').addClass('d-none').text('');
        getModal('admWdApproveModal').show();
    });

    $('#admWdApproveForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#admWdApproveSubmitBtn').prop('disabled', true).text('Approving...');
        $('#admWdApproveError').addClass('d-none').text('');
        $.ajax({
            url: $('#admWdApproveUrl').val(),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: $(this).serialize(),
            success: function(res) {
                getModal('admWdApproveModal').hide();
                showAlert(res.message, 'success');
                reloadTable(false);
            },
            error: function(xhr) {
                $('#admWdApproveError').removeClass('d-none').text(extractError(xhr, 'Approval failed.'));
            },
            complete: function() {
                btn.prop('disabled', false).text('Confirm Approval');
            }
        });
    });

    $(document).on('click', '.adm-wd-reject-btn', function() {
        $('#admWdRejectUrl').val($(this).data('url'));
        $('#admWdRejectTitle').text('Reject ' + $(this).data('ref'));
        $('#admWdRejectReason').val('');
        $('#admWdRejectError').addClass('d-none').text('');
        getModal('admWdRejectModal').show();
    });

    $('#admWdRejectForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#admWdRejectSubmitBtn').prop('disabled', true).text('Rejecting...');
        $('#admWdRejectError').addClass('d-none').text('');
        $.ajax({
            url: $('#admWdRejectUrl').val(),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: $(this).serialize(),
            success: function(res) {
                getModal('admWdRejectModal').hide();
                showAlert(res.message, 'success');
                reloadTable(false);
            },
            error: function(xhr) {
                $('#admWdRejectError').removeClass('d-none').text(extractError(xhr, 'Rejection failed.'));
            },
            complete: function() {
                btn.prop('disabled', false).text('Confirm Rejection');
            }
        });
    });

    $(document).on('click', '.adm-wd-settle-btn', function() {
        $('#admWdSettleUrl').val($(this).data('url'));
        $('#admWdSettleTitle').text('Mark Manually Settled — ' + $(this).data('ref'));
        $('#admWdSettleRef').val('');
        $('#admWdSettleNotes').val('');
        $('#admWdSettleError').addClass('d-none').text('');
        getModal('admWdSettleModal').show();
    });

    $('#admWdSettleForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#admWdSettleSubmitBtn').prop('disabled', true).text('Saving...');
        $('#admWdSettleError').addClass('d-none').text('');
        $.ajax({
            url: $('#admWdSettleUrl').val(),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: $(this).serialize(),
            success: function(res) {
                getModal('admWdSettleModal').hide();
                showAlert(res.message, 'success');
                reloadTable(false);
            },
            error: function(xhr) {
                $('#admWdSettleError').removeClass('d-none').text(extractError(xhr, 'Settlement recording failed.'));
            },
            complete: function() {
                btn.prop('disabled', false).text('Confirm Manual Settlement');
            }
        });
    });
});
</script>
@endpush
