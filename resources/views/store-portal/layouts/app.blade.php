<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Store Portal') | FreshGo Laundry</title>
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/css/icons/material-design-iconic-font/css/materialdesignicons.min.css') }}">
    @stack('styles')
    <style>
        :root {
            --sp-yellow: #FFC629;
            --sp-cream: #FFF8E5;
            --sp-bg: #F8FAFC;
            --sp-dark: #171717;
            --sp-slate: #475569;
        }
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--sp-bg);
            color: var(--sp-dark);
            min-height: 100vh;
            overflow-x: hidden;
        }
        .sp-shell {
            display: flex;
            min-height: 100vh;
        }
        .sp-sidebar {
            width: 232px;
            background: #FFFFFF;
            border-right: 1px solid #E2E8F0;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            transition: width 0.2s ease;
        }
        .sp-sidebar.collapsed {
            width: 68px;
        }
        .sp-sidebar.collapsed .sp-nav-label,
        .sp-sidebar.collapsed .sp-brand-sub {
            display: none;
        }
        .sp-sidebar.collapsed .sp-nav-link {
            justify-content: center;
            padding: 0.55rem 0.5rem;
            margin: 0.12rem 0.45rem;
        }
        .sp-sidebar.collapsed .sp-submenu {
            padding-left: 0;
        }
        .sp-brand {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid #F1F5F9;
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%);
        }
        .sp-brand-badge {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background: var(--sp-yellow);
            color: var(--sp-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .sp-nav-link {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.52rem 0.8rem;
            margin: 0.12rem 0.5rem;
            border-radius: 10px;
            color: var(--sp-slate);
            font-size: 0.845rem;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .sp-nav-ico {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .sp-nav-link:hover {
            background-color: #FFF8E5;
            color: var(--sp-dark);
        }
        .sp-nav-link.active {
            background-color: var(--sp-yellow);
            color: var(--sp-dark);
            font-weight: 700;
        }
        .sp-nav-link.active .sp-nav-ico {
            color: var(--sp-dark) !important;
        }
        .sp-group-toggle.is-parent-active {
            background-color: #FFFDF5;
            color: var(--sp-dark);
            font-weight: 600;
        }
        .sp-chevron {
            transition: transform 0.2s ease;
        }
        .sp-group-toggle[aria-expanded="true"] .sp-chevron {
            transform: rotate(180deg);
        }
        .sp-submenu {
            padding-left: 0.75rem;
        }
        .sp-sub-link {
            font-size: 0.815rem;
            padding: 0.44rem 0.75rem;
        }
        .sp-main {
            flex: 1 1 auto;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }
        .sp-topbar {
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            padding: 0.7rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .sp-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        }
        .sp-btn-yellow {
            background-color: var(--sp-yellow);
            border-color: var(--sp-yellow);
            color: var(--sp-dark);
            font-weight: 600;
            border-radius: 10px;
        }
        .sp-btn-yellow:hover, .sp-btn-yellow:focus {
            background-color: #f0b516;
            border-color: #f0b516;
            color: var(--sp-dark);
        }
        .sp-anim-card {
            animation: spFadeUp 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
            transition: transform 0.22s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.22s ease;
        }
        .sp-anim-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07);
        }
        .sp-icon-box {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .sp-anim-card:hover .sp-icon-box {
            transform: scale(1.1) rotate(-5deg);
        }
        .sp-pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #D97706;
            display: inline-block;
            animation: spPulse 1.8s ease-in-out infinite;
        }
        .sp-ajax-wrap.is-loading {
            opacity: 0.55;
            pointer-events: none;
            transition: opacity 0.18s ease;
        }
        @keyframes spFadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes spPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.35); opacity: 0.55; }
        }
        @media (prefers-reduced-motion: reduce) {
            .sp-anim-card, .sp-pulse-dot { animation: none !important; }
            .sp-anim-card, .sp-icon-box, .sp-ajax-wrap.is-loading { transition: none !important; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="sp-shell">
    <!-- Desktop Sidebar -->
    <aside class="sp-sidebar d-none d-lg-flex" id="spSidebar">
        <div class="sp-brand d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <span class="sp-brand-badge"><i class="mdi mdi-store"></i></span>
                <div class="sp-nav-label min-w-0">
                    <div class="fw-bold text-truncate" style="font-size: 0.92rem; color: #171717;">Store Portal</div>
                    <div class="sp-brand-sub text-truncate" style="font-size: 0.72rem; color: #475569;">{{ auth('store')->user()?->store?->name }}</div>
                </div>
            </div>
        </div>

        <nav class="py-2 flex-grow-1">
            @include('store-portal.layouts._nav_links', ['navScope' => 'desktop'])
        </nav>

        <div class="p-2 border-top">
            <form method="POST" action="{{ route('store.logout') }}">
                @csrf
                <button type="submit" class="sp-nav-link w-100 border-0 bg-transparent text-danger">
                    <i class="mdi mdi-logout font-18"></i>
                    <span class="sp-nav-label">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="sp-main">
        <header class="sp-topbar">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-light d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#spMobileNav" aria-label="Open menu">
                    <i class="mdi mdi-menu"></i>
                </button>
                <button type="button" class="btn btn-sm btn-light d-none d-lg-inline-flex" id="btnToggleSpSidebar" title="Toggle sidebar">
                    <i class="mdi mdi-menu"></i>
                </button>
                <span class="fw-semibold small text-truncate" style="color: #171717;">
                    {{ auth('store')->user()?->store?->name }}
                </span>
            </div>

            <div class="d-flex align-items-center gap-2 small">
                <span class="d-none sm-inline text-muted" style="color: #475569 !important;">{{ auth('store')->user()?->name }}</span>
                <span class="badge rounded-pill px-2 py-1" style="background-color: #FFF8E5; color: #92400E; border: 1px solid #FDE68A;">
                    {{ ucfirst(auth('store')->user()?->role ?? 'owner') }}
                </span>
            </div>
        </header>

        <main class="p-3 p-md-4 flex-grow-1">
            @if(session('success'))
                <div class="alert alert-success rounded-3 py-2 px-3 small mb-3">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger rounded-3 py-2 px-3 small mb-3">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger rounded-3 py-2 px-3 small mb-3">{{ $errors->first() }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<!-- Mobile Offcanvas Drawer -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="spMobileNav" aria-labelledby="spMobileNavLabel" style="max-width: 280px;">
    <div class="offcanvas-header border-bottom" style="background: #FFF8E5;">
        <h6 class="offcanvas-title fw-bold" id="spMobileNavLabel">{{ auth('store')->user()?->store?->name }}</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-2">
        <nav class="flex-grow-1">
            @include('store-portal.layouts._nav_links', ['navScope' => 'mobile'])
        </nav>
        <form method="POST" action="{{ route('store.logout') }}" class="mt-3 pt-2 border-top">
            @csrf
            <button type="submit" class="sp-nav-link w-100 border-0 bg-transparent text-danger">
                <i class="mdi mdi-logout font-18"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</div>

<script src="{{ asset('admin/dist/libs/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script>
    (function () {
        const sidebar = document.getElementById('spSidebar');
        const toggleBtn = document.getElementById('btnToggleSpSidebar');
        const storageKey = 'sp_sidebar_collapsed';
        if (sidebar && localStorage.getItem(storageKey) === '1') {
            sidebar.classList.add('collapsed');
        }
        toggleBtn?.addEventListener('click', function () {
            if (!sidebar) return;
            sidebar.classList.toggle('collapsed');
            localStorage.setItem(storageKey, sidebar.classList.contains('collapsed') ? '1' : '0');
        });
    })();

    window.StorePortalMonthAjax = {
        init: function (cfg) {
            const p = cfg.prefix;
            const monthEl = document.getElementById(p + 'MonthSelect');
            const yearEl = document.getElementById(p + 'YearSelect');
            const prevBtn = document.getElementById(p + 'PrevBtn');
            const nextBtn = document.getElementById(p + 'NextBtn');
            const currBtn = document.getElementById(p + 'CurrentBtn');
            const headEl = document.getElementById(p + 'MonthHeading');
            const spinEl = document.getElementById(p + 'Spinner');
            const wrapEl = document.getElementById(cfg.wrapId);
            const errEl = document.getElementById(cfg.errorId);
            const formEl = cfg.formId ? document.getElementById(cfg.formId) : null;
            const resetBtn = cfg.resetBtnId ? document.getElementById(cfg.resetBtnId) : null;

            let ctrl = null, seq = 0, timer = null;

            function buildParams(page) {
                const params = new URLSearchParams();
                params.set('month', monthEl ? monthEl.value : String(cfg.currentMonth));
                params.set('year', yearEl ? yearEl.value : String(cfg.currentYear));
                if (typeof cfg.collectParams === 'function') {
                    const extra = cfg.collectParams();
                    Object.keys(extra).forEach(function (k) {
                        if (extra[k] !== '' && extra[k] !== null && extra[k] !== undefined) {
                            params.set(k, String(extra[k]));
                        }
                    });
                }
                if (page > 1) params.set('page', String(page));
                return params;
            }

            async function load(page) {
                if (ctrl) ctrl.abort();
                ctrl = new AbortController();
                const mySeq = ++seq;
                const params = buildParams(page || 1);
                const url = cfg.baseUrl + '?' + params.toString();

                wrapEl?.classList.add('is-loading');
                spinEl?.classList.remove('d-none');
                errEl?.classList.add('d-none');

                try {
                    const res = await fetch(url, {
                        signal: ctrl.signal,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (mySeq !== seq) return;
                    if (!res.ok || data.status !== 'ok') throw new Error(data.message || 'Unable to load data.');

                    if (headEl && data.month_label) headEl.textContent = data.month_label;
                    if (monthEl && data.month) monthEl.value = String(data.month);
                    if (yearEl && data.year) yearEl.value = String(data.year);
                    if (prevBtn) { prevBtn.dataset.month = String(data.prev_month); prevBtn.dataset.year = String(data.prev_year); }
                    if (nextBtn) { nextBtn.dataset.month = String(data.next_month); nextBtn.dataset.year = String(data.next_year); }
                    if (wrapEl && data[cfg.htmlKey || 'results_html'] !== undefined) {
                        wrapEl.innerHTML = data[cfg.htmlKey || 'results_html'];
                    }
                    if (typeof cfg.onSuccess === 'function') cfg.onSuccess(data);
                    window.history?.replaceState({}, '', url);
                } catch (err) {
                    if (err.name === 'AbortError' || mySeq !== seq) return;
                    if (errEl) {
                        errEl.textContent = err.message || 'Request failed. Please try again.';
                        errEl.classList.remove('d-none');
                    }
                } finally {
                    if (mySeq === seq) {
                        wrapEl?.classList.remove('is-loading');
                        spinEl?.classList.add('d-none');
                    }
                }
            }

            prevBtn?.addEventListener('click', () => {
                if (monthEl) monthEl.value = prevBtn.dataset.month;
                if (yearEl) yearEl.value = prevBtn.dataset.year;
                load(1);
            });
            nextBtn?.addEventListener('click', () => {
                if (monthEl) monthEl.value = nextBtn.dataset.month;
                if (yearEl) yearEl.value = nextBtn.dataset.year;
                load(1);
            });
            currBtn?.addEventListener('click', () => {
                if (monthEl) monthEl.value = String(cfg.currentMonth);
                if (yearEl) yearEl.value = String(cfg.currentYear);
                load(1);
            });
            [monthEl, yearEl].forEach(el => el?.addEventListener('change', () => load(1)));

            (cfg.changeIds || []).forEach(id => {
                document.getElementById(id)?.addEventListener('change', () => load(1));
            });
            (cfg.searchIds || []).forEach(id => {
                document.getElementById(id)?.addEventListener('input', () => {
                    clearTimeout(timer);
                    timer = setTimeout(() => load(1), 300);
                });
            });
            formEl?.addEventListener('submit', e => { e.preventDefault(); load(1); });
            resetBtn?.addEventListener('click', () => {
                if (monthEl) monthEl.value = String(cfg.currentMonth);
                if (yearEl) yearEl.value = String(cfg.currentYear);
                if (typeof cfg.onReset === 'function') cfg.onReset();
                load(1);
            });
            if (cfg.paginationSelector) {
                document.addEventListener('click', e => {
                    const a = e.target.closest(cfg.paginationSelector + ' a');
                    if (!a) return;
                    e.preventDefault();
                    const p = parseInt(new URL(a.href, window.location.origin).searchParams.get('page') || '1', 10);
                    load(isNaN(p) ? 1 : p);
                });
            }

            return { reload: load };
        }
    };
</script>
@stack('scripts')
</body>
</html>
