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
            width: 250px;
            background: #FFFFFF;
            border-right: 1px solid #E2E8F0;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }
        .dp-brand {
            padding: 1rem 1.1rem;
            border-bottom: 1px solid #F1F5F9;
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%);
        }
        .dp-brand-badge {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--dp-yellow);
            color: var(--dp-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .dp-nav-link {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.62rem 0.9rem;
            margin: 0.14rem 0.6rem;
            border-radius: 10px;
            color: var(--dp-slate);
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
        }
        .dp-nav-link:hover { background-color: #FFF8E5; color: var(--dp-dark); }
        .dp-nav-link.active { background-color: var(--dp-yellow); color: var(--dp-dark); font-weight: 700; }
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
    <aside class="dp-sidebar">
        <div class="dp-brand d-flex align-items-center gap-2">
            <span class="dp-brand-badge"><i class="mdi mdi-motorbike"></i></span>
            <div class="min-w-0">
                <div class="fw-bold text-truncate" style="font-size: 0.95rem;">Delivery Portal</div>
                <div class="small text-muted text-truncate" style="font-size: 0.72rem;">{{ $dpUser?->full_name }}</div>
            </div>
        </div>
        <nav class="py-2 flex-grow-1">
            <a href="{{ route('delivery-person.dashboard') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.dashboard') ? 'active' : '' }}"><i class="mdi mdi-view-dashboard"></i><span>Dashboard</span></a>
            <a href="{{ route('delivery-person.bookings.index') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.bookings.*') ? 'active' : '' }}"><i class="mdi mdi-basket"></i><span>My Bookings</span></a>
            <a href="{{ route('delivery-person.collections.index') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.collections.*') ? 'active' : '' }}"><i class="mdi mdi-package-up"></i><span>Collections</span></a>
            <a href="{{ route('delivery-person.final-deliveries.index') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.final-deliveries.*') ? 'active' : '' }}"><i class="mdi mdi-truck-delivery"></i><span>Final Deliveries</span></a>
            <a href="{{ route('delivery-person.cash-account') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.cash-account') ? 'active' : '' }}"><i class="mdi mdi-cash-multiple"></i><span>Cash Account</span></a>
            <a href="{{ route('delivery-person.settlements') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.settlements') ? 'active' : '' }}"><i class="mdi mdi-check-circle"></i><span>Settlements</span></a>
            <a href="{{ route('delivery-person.earnings') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.earnings') ? 'active' : '' }}"><i class="mdi mdi-wallet"></i><span>My Earnings</span></a>
            <a href="{{ route('delivery-person.profile') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.profile') ? 'active' : '' }}"><i class="mdi mdi-account"></i><span>My Profile</span></a>
            <a href="{{ route('delivery-person.password.change') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.password.*') ? 'active' : '' }}"><i class="mdi mdi-lock"></i><span>Change Password</span></a>
        </nav>
        <div class="p-2 border-top">
            <form method="POST" action="{{ route('delivery-person.logout') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="mdi mdi-logout me-1"></i>Logout</button>
            </form>
        </div>
    </aside>

    <div class="dp-main">
        <header class="dp-topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#dpMobileMenu" aria-label="Menu">
                    <i class="mdi mdi-menu"></i>
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
<div class="offcanvas offcanvas-start" tabindex="-1" id="dpMobileMenu">
    <div class="offcanvas-header" style="background: #FFF8E5;">
        <h6 class="offcanvas-title fw-bold">Delivery Person Menu</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-2 d-flex flex-column justify-content-between">
        <div>
            <a href="{{ route('delivery-person.dashboard') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.dashboard') ? 'active' : '' }}"><i class="mdi mdi-view-dashboard"></i><span>Dashboard</span></a>
            <a href="{{ route('delivery-person.bookings.index') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.bookings.*') ? 'active' : '' }}"><i class="mdi mdi-basket"></i><span>My Bookings</span></a>
            <a href="{{ route('delivery-person.collections.index') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.collections.*') ? 'active' : '' }}"><i class="mdi mdi-package-up"></i><span>Collections</span></a>
            <a href="{{ route('delivery-person.final-deliveries.index') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.final-deliveries.*') ? 'active' : '' }}"><i class="mdi mdi-truck-delivery"></i><span>Final Deliveries</span></a>
            <a href="{{ route('delivery-person.cash-account') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.cash-account') ? 'active' : '' }}"><i class="mdi mdi-cash-multiple"></i><span>Cash Account</span></a>
            <a href="{{ route('delivery-person.settlements') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.settlements') ? 'active' : '' }}"><i class="mdi mdi-check-circle"></i><span>Settlements</span></a>
            <a href="{{ route('delivery-person.earnings') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.earnings') ? 'active' : '' }}"><i class="mdi mdi-wallet"></i><span>My Earnings</span></a>
            <a href="{{ route('delivery-person.profile') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.profile') ? 'active' : '' }}"><i class="mdi mdi-account"></i><span>My Profile</span></a>
            <a href="{{ route('delivery-person.password.change') }}" class="dp-nav-link {{ request()->routeIs('delivery-person.password.*') ? 'active' : '' }}"><i class="mdi mdi-lock"></i><span>Change Password</span></a>
        </div>
        <form method="POST" action="{{ route('delivery-person.logout') }}" class="p-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-danger w-100">Logout</button>
        </form>
    </div>
</div>

<script src="{{ asset('admin/dist/libs/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
</body>
</html>
