<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Delivery Person Portal') | FreshGo Laundry</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/dist/css/icons/material-design-iconic-font/css/materialdesignicons.min.css') }}">
    <style>
        :root {
            --dp-yellow: #FFC629;
            --dp-cream: #FFF8E5;
            --dp-bg: #F8FAFC;
            --dp-dark: #171717;
            --dp-slate: #475569;
        }
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--dp-bg);
            color: var(--dp-dark);
            min-height: 100vh;
            overflow-x: hidden;
        }
        .dp-shell { display: flex; min-height: 100vh; }
        .dp-sidebar {
            width: 232px;
            background: #FFFFFF;
            border-right: 1px solid #E2E8F0;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            transition: width 0.2s ease;
        }
        .dp-sidebar.collapsed {
            width: 68px;
        }
        .dp-sidebar.collapsed .dp-nav-label,
        .dp-sidebar.collapsed .dp-brand-sub {
            display: none;
        }
        .dp-sidebar.collapsed .dp-nav-link {
            justify-content: center;
            padding: 0.55rem 0.5rem;
            margin: 0.12rem 0.45rem;
        }
        .dp-sidebar.collapsed .dp-submenu {
            padding-left: 0;
        }
        .dp-brand {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid #F1F5F9;
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%);
        }
        .dp-brand-badge {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background: var(--dp-yellow);
            color: var(--dp-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .dp-nav-link {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.52rem 0.8rem;
            margin: 0.12rem 0.5rem;
            border-radius: 10px;
            color: var(--dp-slate);
            font-size: 0.845rem;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .dp-nav-ico {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .dp-nav-link:hover { background-color: #FFF8E5; color: var(--dp-dark); }
        .dp-nav-link.active { background-color: var(--dp-yellow); color: var(--dp-dark); font-weight: 700; }
        .dp-nav-link.active .dp-nav-ico { color: var(--dp-dark) !important; }
        .dp-group-toggle.is-parent-active {
            background-color: #FFFDF5;
            color: var(--dp-dark);
            font-weight: 600;
        }
        .dp-chevron { transition: transform 0.2s ease; }
        .dp-group-toggle[aria-expanded="true"] .dp-chevron { transform: rotate(180deg); }
        .dp-submenu { padding-left: 0.75rem; }
        .dp-sub-link { font-size: 0.815rem; padding: 0.44rem 0.75rem; }
        @media (prefers-reduced-motion: reduce) {
            .dp-sidebar, .dp-nav-link, .dp-chevron { transition: none !important; }
        }
        .dp-main { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; }
        .dp-topbar {
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            padding: 0.65rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }
        .dp-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        }
        .dp-btn-yellow {
            background-color: var(--dp-yellow);
            color: var(--dp-dark);
            border: 1px solid #EAB308;
            font-weight: 600;
        }
        .dp-btn-yellow:hover { background-color: #F5B800; color: var(--dp-dark); }
        @media (max-width: 991.98px) {
            .dp-sidebar { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body>
@php $dpUser = Auth::guard('delivery_person')->user(); @endphp
<div class="dp-shell">
    <aside class="dp-sidebar" id="dpSidebar">
        <div class="dp-brand d-flex align-items-center gap-2">
            <span class="dp-brand-badge">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 17h4V5H2v12h3"/><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5v8h1"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
            </span>
            <div class="dp-nav-label min-w-0">
                <div class="fw-bold text-truncate" style="font-size: 0.92rem;">Delivery Portal</div>
                <div class="dp-brand-sub small text-muted text-truncate" style="font-size: 0.72rem;">{{ $dpUser?->full_name }}</div>
            </div>
        </div>
        <nav class="py-2 flex-grow-1">
            @include('delivery-person-portal.layouts._nav_links', ['navScope' => 'desktop'])
        </nav>
        <div class="p-2 border-top">
            <form method="POST" action="{{ route('delivery-person.logout') }}">
                @csrf
                <button type="submit" class="dp-nav-link w-100 border-0 bg-transparent text-danger" title="Logout">
                    <span class="dp-nav-ico">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </span>
                    <span class="dp-nav-label">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="dp-main">
        <header class="dp-topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#dpMobileMenu" aria-label="Open menu">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <button class="btn btn-sm btn-light d-none d-lg-inline-flex" type="button" id="btnToggleDpSidebar" title="Toggle sidebar" aria-label="Toggle sidebar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <span class="fw-bold" style="font-size: 0.95rem;">@yield('page_title', 'Delivery Person Portal')</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill" style="background-color: #FFF8E5; color: #171717; border: 1px solid #FDE68A;">
                    {{ $dpUser?->code }}
                </span>
            </div>
        </header>

        <main class="p-3 p-md-4 flex-grow-1">
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
            @yield('content')
        </main>
    </div>
</div>

<!-- Mobile Offcanvas Navigation -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="dpMobileMenu" aria-labelledby="dpMobileMenuLabel" style="max-width: 280px;">
    <div class="offcanvas-header border-bottom" style="background: #FFF8E5;">
        <h6 class="offcanvas-title fw-bold" id="dpMobileMenuLabel">Delivery Person Menu</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-2 d-flex flex-column justify-content-between">
        <nav class="flex-grow-1">
            @include('delivery-person-portal.layouts._nav_links', ['navScope' => 'mobile'])
        </nav>
        <form method="POST" action="{{ route('delivery-person.logout') }}" class="p-2 border-top mt-2">
            @csrf
            <button type="submit" class="dp-nav-link w-100 border-0 bg-transparent text-danger">Logout</button>
        </form>
    </div>
</div>

<script src="{{ asset('admin/dist/libs/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script>
    (function () {
        const sidebar = document.getElementById('dpSidebar');
        const toggleBtn = document.getElementById('btnToggleDpSidebar');
        const storageKey = 'dp_sidebar_collapsed';
        if (sidebar && localStorage.getItem(storageKey) === '1') {
            sidebar.classList.add('collapsed');
        }
        toggleBtn?.addEventListener('click', function () {
            if (!sidebar) return;
            sidebar.classList.toggle('collapsed');
            localStorage.setItem(storageKey, sidebar.classList.contains('collapsed') ? '1' : '0');
        });
    })();
</script>
@stack('scripts')
</body>
</html>
