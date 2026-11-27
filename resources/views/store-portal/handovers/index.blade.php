@extends('store-portal.layouts.app')

@section('title', 'Delivery Person Handovers — Store Portal')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0" style="color: #171717;">Delivery Person Handovers</h4>
        <div class="small text-muted">Verify physical laundry handovers from Collection Delivery Persons</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('store.bookings.index') }}" class="btn btn-sm btn-outline-dark">My Bookings</a>
    </div>
</div>

<!-- Live Pending Handovers Banner (Cross-Month Operational Visibility) -->
<div class="sp-card p-3 mb-3 sp-anim-card" style="background: {{ $allTimePendingCount > 0 ? '#FFFBEB' : '#F8FAFC' }}; border-color: {{ $allTimePendingCount > 0 ? '#FDE68A' : '#E2E8F0' }};">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="sp-icon-box" style="background: {{ $allTimePendingCount > 0 ? '#FEF3C7' : '#E2E8F0' }}; color: {{ $allTimePendingCount > 0 ? '#92400E' : '#475569' }};">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            </span>
            <div>
                <div class="fw-bold small" style="color: #171717;">
                    @if($allTimePendingCount > 0)
                        <span class="sp-pulse-dot me-1"></span>{{ $allTimePendingCount }} Pending {{ Str::plural('Handover', $allTimePendingCount) }} Awaiting Store Verification
                    @else
                        All Handovers Verified
                    @endif
                </div>
                <div class="text-muted" style="font-size: 0.78rem;">Toggle "Show All Pending" to review unverified handovers from any month.</div>
            </div>
        </div>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch" id="spHoPendingOnly" @checked(!empty($filters['pending_only']))>
            <label class="form-check-label small fw-semibold" for="spHoPendingOnly">Show All Pending (All Months)</label>
        </div>
    </div>
</div>

@include('store-portal.partials.month-bar', [
    'prefix' => 'spHo',
    'monthLabel' => $monthLabel,
    'selectedMonth' => $selectedMonth,
    'selectedYear' => $selectedYear,
    'prevMonth' => $prevMonth,
    'prevYear' => $prevYear,
    'nextMonth' => $nextMonth,
    'nextYear' => $nextYear,
    'currentMonth' => $currentMonth,
    'currentYear' => $currentYear,
    'subtitle' => 'Filtered by Handover Request / Acceptance Date',
])

<!-- Monthly Summary Cards -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="sp-card p-3 h-100 sp-anim-card">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">Total Handovers</div>
                <span class="sp-icon-box" style="background: #F1F5F9; color: #334155;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18"/></svg>
                </span>
            </div>
            <div class="fs-4 fw-bold mt-1" id="spHoSumTotal" style="color: #171717;">{{ $summary['total'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sp-card p-3 h-100 sp-anim-card">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">Pending</div>
                <span class="sp-icon-box" style="background: #FEF3C7; color: #92400E;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </span>
            </div>
            <div class="fs-4 fw-bold mt-1" id="spHoSumPending" style="color: #92400E;">{{ $summary['pending'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sp-card p-3 h-100 sp-anim-card">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">Accepted</div>
                <span class="sp-icon-box" style="background: #DCFCE7; color: #166534;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                </span>
            </div>
            <div class="fs-4 fw-bold mt-1" id="spHoSumAccepted" style="color: #166534;">{{ $summary['accepted'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="sp-card p-3 h-100 sp-anim-card">
            <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">Issues Reported</div>
                <span class="sp-icon-box" style="background: #FEE2E2; color: #991B1B;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4m0 4h.01"/></svg>
                </span>
            </div>
            <div class="fs-4 fw-bold mt-1" id="spHoSumRejected" style="color: #991B1B;">{{ $summary['rejected'] }}</div>
        </div>
    </div>
</div>

<!-- AJAX Filter Bar -->
<div class="sp-card p-3 mb-3">
    <form id="spHoFilterForm" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold mb-1">Search</label>
            <input type="text" id="spHoSearch" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Request ref, booking ref, customer, DP...">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold mb-1">Handover Status</label>
            <select id="spHoStatus" name="status" class="form-select form-select-sm">
                <option value="all" @selected($filters['status'] === 'all')>All Statuses</option>
                <option value="pending" @selected($filters['status'] === 'pending')>Pending Verification</option>
                <option value="accepted" @selected($filters['status'] === 'accepted')>Accepted</option>
                <option value="rejected" @selected($filters['status'] === 'rejected')>Issue Reported</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold mb-1">Sort By</label>
            <select id="spHoSort" name="sort" class="form-select form-select-sm">
                <option value="pending_first" @selected($filters['sort'] === 'pending_first')>Pending First</option>
                <option value="newest" @selected($filters['sort'] === 'newest')>Newest First</option>
                <option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest First</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex">
            <button type="button" id="spHoResetBtn" class="btn btn-sm btn-outline-secondary w-100">Reset Filters</button>
        </div>
    </form>
</div>

<div id="spHoErrorAlert" class="alert alert-danger d-none py-2 small" role="alert"></div>

<div id="spHoResultsWrap" class="sp-ajax-wrap">
    @include('store-portal.handovers.partials.list')
</div>

<!-- Accept Confirmation Modal -->
<div class="modal fade" id="storeAcceptHandoverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="storeAcceptHandoverForm" action="#">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Accept Laundry Handover</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small">
                    <p class="mb-2 fw-semibold text-dark">Confirm that your store has physically received the laundry for this booking.</p>
                    <div class="p-2 rounded-2 bg-light border">
                        <div><strong>Booking:</strong> <span id="acceptModalRef"></span></div>
                        <div><strong>Collection Delivery Person:</strong> <span id="acceptModalDp"></span></div>
                        <div><strong>Items Summary:</strong> <span id="acceptModalSummary"></span></div>
                        <div><strong>Store:</strong> <span id="acceptModalStore"></span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success fw-bold">Accept &amp; Confirm Received</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Report Issue Modal -->
<div class="modal fade" id="storeRejectHandoverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="storeRejectHandoverForm" action="#">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Report Handover Issue (<span id="rejectModalRef"></span>)</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small">
                    <label class="form-label fw-semibold mb-1">Reason <span class="text-danger">*</span></label>
                    <textarea name="reason" rows="3" class="form-control form-control-sm" required placeholder="e.g. Laundry has not arrived physically or items are missing"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Submit Issue</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.StorePortalMonthAjax) {
            window.StorePortalMonthAjax.init({
                prefix: 'spHo',
                baseUrl: '{{ route('store.handovers.index') }}',
                currentMonth: {{ (int) $currentMonth }},
                currentYear: {{ (int) $currentYear }},
                wrapId: 'spHoResultsWrap',
                errorId: 'spHoErrorAlert',
                formId: 'spHoFilterForm',
                resetBtnId: 'spHoResetBtn',
                paginationSelector: '.sp-ho-pagination a',
                changeIds: ['spHoStatus', 'spHoSort', 'spHoPendingOnly'],
                searchIds: ['spHoSearch'],
                collectParams: function () {
                    return {
                        q: (document.getElementById('spHoSearch')?.value || '').trim(),
                        status: document.getElementById('spHoStatus')?.value || 'all',
                        sort: document.getElementById('spHoSort')?.value || 'pending_first',
                        pending_only: document.getElementById('spHoPendingOnly')?.checked ? '1' : '0',
                    };
                },
                onSuccess: function (data) {
                    if (data.summary) {
                        const map = {
                            spHoSumTotal: data.summary.total,
                            spHoSumPending: data.summary.pending,
                            spHoSumAccepted: data.summary.accepted,
                            spHoSumRejected: data.summary.rejected,
                        };
                        Object.entries(map).forEach(([id, val]) => {
                            const el = document.getElementById(id);
                            if (el && val !== undefined) el.textContent = val;
                        });
                    }
                },
                onReset: function () {
                    const q = document.getElementById('spHoSearch');
                    const status = document.getElementById('spHoStatus');
                    const sort = document.getElementById('spHoSort');
                    const pendingOnly = document.getElementById('spHoPendingOnly');
                    if (q) q.value = '';
                    if (status) status.value = 'all';
                    if (sort) sort.value = 'pending_first';
                    if (pendingOnly) pendingOnly.checked = false;
                }
            });
        }
    });

    document.addEventListener('click', function (e) {
        const acceptBtn = e.target.closest('.js-store-open-accept');
        if (acceptBtn) {
            document.getElementById('storeAcceptHandoverForm').setAttribute('action', acceptBtn.dataset.action);
            document.getElementById('acceptModalRef').textContent = acceptBtn.dataset.ref || '';
            document.getElementById('acceptModalDp').textContent = acceptBtn.dataset.dp || '';
            document.getElementById('acceptModalSummary').textContent = acceptBtn.dataset.summary || '';
            document.getElementById('acceptModalStore').textContent = acceptBtn.dataset.store || '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('storeAcceptHandoverModal')).show();
            return;
        }

        const rejectBtn = e.target.closest('.js-store-open-reject');
        if (rejectBtn) {
            document.getElementById('storeRejectHandoverForm').setAttribute('action', rejectBtn.dataset.action);
            document.getElementById('rejectModalRef').textContent = rejectBtn.dataset.ref || '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('storeRejectHandoverModal')).show();
        }
    });
</script>
@endpush
