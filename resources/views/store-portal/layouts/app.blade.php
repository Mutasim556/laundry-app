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
            width: 250px;
            background: #FFFFFF;
            border-right: 1px solid #E2E8F0;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            transition: width 0.2s ease;
        }
        .sp-sidebar.collapsed {
            width: 74px;
        }
        .sp-sidebar.collapsed .sp-nav-label,
        .sp-sidebar.collapsed .sp-brand-sub {
            display: none;
        }
        .sp-brand {
            padding: 1rem 1.15rem;
            border-bottom: 1px solid #F1F5F9;
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%);
        }
        .sp-brand-badge {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--sp-yellow);
            color: var(--sp-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .sp-nav-link {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.65rem 0.9rem;
            margin: 0.15rem 0.6rem;
            border-radius: 10px;
            color: var(--sp-slate);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.15s ease, color 0.15s ease;
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

        <nav class="py-3 flex-grow-1">
            @include('store-portal.layouts._nav_links')
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
            @include('store-portal.layouts._nav_links')
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
    document.getElementById('btnToggleSpSidebar')?.addEventListener('click', function () {
        document.getElementById('spSidebar')?.classList.toggle('collapsed');
    });
</script>
@stack('scripts')
</body>
</html>
