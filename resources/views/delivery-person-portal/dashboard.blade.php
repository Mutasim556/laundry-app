@extends('delivery-person-portal.layouts.app')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@push('styles')
<style>
    .dp-dash-anim {
        animation: dpDashFadeUp 0.52s cubic-bezier(0.22, 1, 0.36, 1) both;
        transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.24s ease;
    }
    .dp-dash-anim:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }
    .dp-kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .dp-dash-anim:hover .dp-kpi-icon {
        transform: scale(1.1) rotate(-6deg);
    }
    .dp-pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #D97706;
        display: inline-block;
        animation: dpPulse 1.8s ease-in-out infinite;
    }
    #dpDashboardContentWrap.is-loading {
        opacity: 0.55;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }
    @keyframes dpDashFadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes dpPulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.35); opacity: 0.55; }
    }
    @media (prefers-reduced-motion: reduce) {
        .dp-dash-anim, .dp-pulse-dot { animation: none !important; }
        .dp-dash-anim, .dp-kpi-icon { transition: none !important; }
    }
</style>
@endpush

@section('content')
<!-- AJAX Month-wise Filter Bar -->
<div class="dp-card p-3 mb-3 dp-dash-anim">
    <form id="dpDashMonthForm" method="GET" action="{{ route('delivery-person.dashboard') }}" class="row g-2 align-items-center">
        <div class="col-12 col-md-4 d-flex align-items-center gap-2">
            <span class="dp-kpi-icon" style="background:#FFF8E5; color:#B45309;" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </span>
            <div>
                <div class="fw-bold small text-dark">Filter by Month</div>
                <div class="text-muted" style="font-size:0.75rem;">
                    Showing: <span class="fw-semibold text-dark" id="dpDashPeriodLabel">{{ $selectedMonthLabel ?? 'All Time' }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <input type="month" id="dpDashMonthInput" name="month" value="{{ ($selectedMonth ?? 'all') !== 'all' ? $selectedMonth : '' }}" class="form-control form-control-sm" aria-label="Select month">
        </div>
        <div class="col-12 col-sm-6 col-md-4 d-flex flex-wrap gap-2 justify-content-md-end">
            <button type="submit" class="btn btn-sm dp-btn-yellow px-3 d-inline-flex align-items-center gap-1" id="dpDashApplyBtn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                <span>Apply</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-dark" id="dpDashThisMonthBtn" data-month="{{ now()->format('Y-m') }}">
                This Month
            </button>
            <button type="button" class="btn btn-sm btn-light border" id="dpDashAllTimeBtn">
                All Time
            </button>
        </div>
    </form>
</div>

<div id="dpDashboardContentWrap">
    @include('delivery-person-portal.partials.dashboard-content')
</div>

<!-- Modal: Decline Pickup Request -->
<div class="modal fade" id="dpDeclinePickupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="dpDeclinePickupForm" class="modal-content" style="border-radius: 16px;">
            @csrf
            <div class="modal-header border-0 pb-1">
                <h6 class="modal-title fw-bold">Decline Store Pickup Request (<span id="dpDeclineRefText"></span>)</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <label for="dpDeclineReasonInput" class="form-label small fw-semibold mb-1">Reason for declining <span class="text-danger">*</span></label>
                <textarea id="dpDeclineReasonInput" name="decline_reason" class="form-control form-control-sm" rows="3" required minlength="3" maxlength="500" placeholder="Provide a brief reason..."></textarea>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-danger fw-bold">Submit Decline</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dashboardUrl = @json(route('delivery-person.dashboard'));
    const contentWrap = document.getElementById('dpDashboardContentWrap');
    const monthForm = document.getElementById('dpDashMonthForm');
    const monthInput = document.getElementById('dpDashMonthInput');
    const periodLabel = document.getElementById('dpDashPeriodLabel');
    const thisMonthBtn = document.getElementById('dpDashThisMonthBtn');
    const allTimeBtn = document.getElementById('dpDashAllTimeBtn');

    function bindDeclineButtons() {
        document.querySelectorAll('.dp-open-decline-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('dpDeclinePickupForm').setAttribute('action', btn.getAttribute('data-action'));
                document.getElementById('dpDeclineRefText').textContent = btn.getAttribute('data-ref') || '';
            });
        });
    }

    async function loadDashboardMonth(monthValue) {
        if (!contentWrap) return;
        contentWrap.classList.add('is-loading');
        const param = monthValue || 'all';
        const reqUrl = `${dashboardUrl}?month=${encodeURIComponent(param)}`;

        try {
            const res = await fetch(reqUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (res.ok && data.content_html !== undefined) {
                contentWrap.innerHTML = data.content_html;
                if (periodLabel && data.selected_month_label) {
                    periodLabel.textContent = data.selected_month_label;
                }
                bindDeclineButtons();
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, '', reqUrl);
                }
            }
        } catch (err) {
            window.location.href = reqUrl;
        } finally {
            contentWrap.classList.remove('is-loading');
        }
    }

    if (monthForm) {
        monthForm.addEventListener('submit', function (e) {
            e.preventDefault();
            loadDashboardMonth(monthInput?.value || 'all');
        });
    }
    if (monthInput) {
        monthInput.addEventListener('change', function () {
            loadDashboardMonth(monthInput.value || 'all');
        });
    }
    if (thisMonthBtn) {
        thisMonthBtn.addEventListener('click', function () {
            const m = thisMonthBtn.getAttribute('data-month') || '';
            if (monthInput) monthInput.value = m;
            loadDashboardMonth(m);
        });
    }
    if (allTimeBtn) {
        allTimeBtn.addEventListener('click', function () {
            if (monthInput) monthInput.value = '';
            loadDashboardMonth('all');
        });
    }

    bindDeclineButtons();
});
</script>
@endsection
