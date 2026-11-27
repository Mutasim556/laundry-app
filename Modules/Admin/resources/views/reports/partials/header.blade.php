@php
    $selParts = explode('-', (string) ($period['month'] ?? now()->format('Y-m')));
    $selYear = (int) ($selParts[0] ?? now()->year);
    $selMonth = (int) ($selParts[1] ?? now()->month);
    $curYm = now()->format('Y-m');
@endphp
<div class="p-3 p-md-4 mb-3" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 55%, #FEF3C7 100%); border: 1px solid #FDE68A; border-radius: 18px; box-shadow: 0 8px 24px rgba(255, 198, 41, 0.14);">
    <div class="mb-2">
        <span class="badge rounded-pill px-2.5 py-1 fw-bold mb-1" style="background: #FFC629; color: #171717; font-size: 0.72rem;">Reports &amp; Analytics</span>
        <h4 class="fw-bold mb-1" style="color: #171717;">{{ $reportTitle }}</h4>
        <div class="small text-muted">{{ $reportSubtitle }}</div>
    </div>

    <hr class="my-3" style="border-color: #FDE68A; opacity: 0.8;">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <div class="fw-bold fs-6" style="color: #171717;">{{ $period['month_label'] }}</div>
            <div class="small text-muted">Filtered by reporting period (event/created date).</div>
        </div>
        <form method="GET" action="{{ url()->current() }}" id="repMonthFilterForm" class="d-flex flex-wrap align-items-center gap-2">
            <input type="hidden" name="month" id="repMonthHidden" value="{{ $period['month'] }}">
            <button type="button" class="btn btn-sm btn-light border bg-white fw-semibold rep-ajax-trigger" data-month="{{ $period['prev_month'] }}">&lsaquo; Prev</button>
            <select id="repMonthSel" class="form-select form-select-sm bg-white rep-ajax-select" style="width: auto;">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($m === $selMonth)>{{ \Carbon\Carbon::create(2000, $m, 1)->format('F') }}</option>
                @endfor
            </select>
            <select id="repYearSel" class="form-select form-select-sm bg-white rep-ajax-select" style="width: auto;">
                @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" @selected($y === $selYear)>{{ $y }}</option>
                @endfor
            </select>
            <button type="button" class="btn btn-sm btn-light border bg-white fw-semibold rep-ajax-trigger" data-month="{{ $period['next_month'] }}">Next &rsaquo;</button>
            <button type="button" class="btn btn-sm btn-warning fw-semibold rep-ajax-trigger" data-month="{{ $curYm }}">Current Month</button>
            <select name="currency" id="repCurrencySel" class="form-select form-select-sm bg-white rep-ajax-select" style="width: auto;">
                @foreach($currencies as $cur)
                    <option value="{{ $cur }}" @selected($cur === $currency)>{{ $cur }}</option>
                @endforeach
            </select>
            @if(auth('admin')->user()->can('export-reports', 'admin'))
                <a href="{{ route('admin.reports.export', request()->query()) }}" id="repExportCsvLink" class="btn btn-sm btn-dark fw-semibold">Export CSV</a>
            @endif
        </form>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('repMonthFilterForm');
    if (!form || form.dataset.ajaxBound === '1') return;
    form.dataset.ajaxBound = '1';

    async function loadReportAjax(targetUrl) {
        const container = document.querySelector('.page-wrapper .container-fluid');
        if (!container) { window.location.href = targetUrl; return; }
        container.style.opacity = '0.6';
        try {
            const res = await fetch(targetUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nextContainer = doc.querySelector('.page-wrapper .container-fluid');
            if (nextContainer) {
                container.innerHTML = nextContainer.innerHTML;
                window.history?.replaceState({}, '', targetUrl);
                if (window.feather) feather.replace();
                container.querySelectorAll('script').forEach(oldScript => {
                    const s = document.createElement('script');
                    if (oldScript.src) s.src = oldScript.src;
                    else s.textContent = oldScript.textContent;
                    document.body.appendChild(s);
                    s.remove();
                });
                document.dispatchEvent(new Event('DOMContentLoaded'));
            }
        } finally {
            container.style.opacity = '1';
        }
    }

    function submitFilterAjax() {
        const m = String(document.getElementById('repMonthSel')?.value || '{{ $selMonth }}').padStart(2, '0');
        const y = document.getElementById('repYearSel')?.value || '{{ $selYear }}';
        const cur = document.getElementById('repCurrencySel')?.value || '{{ $currency }}';
        const monthVal = document.getElementById('repMonthHidden').value || `${y}-${m}`;
        const url = new URL(form.action, window.location.origin);
        url.searchParams.set('month', monthVal);
        url.searchParams.set('currency', cur);
        loadReportAjax(url.toString());
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.rep-ajax-trigger');
        if (btn) {
            document.getElementById('repMonthHidden').value = btn.dataset.month;
            submitFilterAjax();
        }
    });
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('rep-ajax-select')) {
            if (e.target.id === 'repMonthSel' || e.target.id === 'repYearSel') {
                const m = String(document.getElementById('repMonthSel').value).padStart(2, '0');
                const y = document.getElementById('repYearSel').value;
                document.getElementById('repMonthHidden').value = `${y}-${m}`;
            }
            submitFilterAjax();
        }
    });
})();
</script>

<ul class="nav nav-pills mb-3 flex-wrap gap-1 bg-white p-2 border rounded-3">
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.reports.index') ? 'active bg-warning text-dark fw-bold' : 'text-dark' }}" href="{{ route('admin.reports.index', request()->query()) }}">Overview &amp; Comparisons</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.reports.transactions') ? 'active bg-warning text-dark fw-bold' : 'text-dark' }}" href="{{ route('admin.reports.transactions', request()->query()) }}">Transactions &amp; Finance</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.reports.stores') ? 'active bg-warning text-dark fw-bold' : 'text-dark' }}" href="{{ route('admin.reports.stores', request()->query()) }}">Store Performance</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.reports.delivery-persons') ? 'active bg-warning text-dark fw-bold' : 'text-dark' }}" href="{{ route('admin.reports.delivery-persons', request()->query()) }}">Delivery Person Performance</a></li>
    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.reports.problems') ? 'active bg-warning text-dark fw-bold' : 'text-dark' }}" href="{{ route('admin.reports.problems', request()->query()) }}">Problem &amp; Issue Tracker</a></li>
</ul>
