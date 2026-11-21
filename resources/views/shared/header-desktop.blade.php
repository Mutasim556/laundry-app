<!-- Desktop Header & Navbar -->
<header class="desktop-navbar sticky-top">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <a href="{{ url('/') }}" class="d-flex align-items-center text-decoration-none gap-2">
                <img src="{{ asset('images/freshgo-new-logo.jpeg') }}" alt="Freshgo Logo" class="desktop-brand-logo rounded-2">
            </a>

            <!-- Nav Links -->
            <ul class="nav nav-pills align-items-center gap-1 gap-xl-2 m-0 p-0 desktop-nav-menu">
                <li class="nav-item"><a href="{{ url('/') }}" class="desktop-nav-link {{ request()->is('/') ? 'active' : '' }}">Home</a></li>
                <li class="nav-item"><a href="{{ route('services.index') }}" class="desktop-nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}">Services</a></li>
                <li class="nav-item"><a href="{{ route('services.index') }}" class="desktop-nav-link">Orders</a></li>
                <li class="nav-item"><a href="{{ route('services.index') }}" class="desktop-nav-link">Offers</a></li>
                <li class="nav-item"><a href="{{ url('/#about') }}" class="desktop-nav-link">About</a></li>
                <li class="nav-item"><a href="{{ url('/#contact') }}" class="desktop-nav-link">Contact</a></li>
            </ul>

            <!-- User and Actions -->
            <div class="d-flex align-items-center gap-2 gap-xl-3">
                <button type="button" class="icon-btn-circle js-notification-bell" data-bs-toggle="offcanvas" data-bs-target="#notificationOffcanvas" aria-label="Notifications">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="notify-indicator"></span>
                </button>

                <div class="d-flex align-items-center gap-2 border rounded-pill px-2 px-xl-3 py-1 bg-white shadow-sm user-pill-desktop">
                    <img src="{{ asset('admin/assets/images/users/3.jpg') }}" alt="Emma Carter" class="rounded-circle" width="32" height="32">
                    <span class="fw-bold small text-dark">Emma Carter</span>
                </div>

                <a href="{{ route('booking.services') }}" class="btn btn-warning rounded-pill px-3 px-xl-4 py-2 fw-bold text-dark shadow-sm btn-book-desktop">
                    Book Service
                </a>
            </div>
        </div>
    </div>
</header>
