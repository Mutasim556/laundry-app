<!-- Mobile Fixed Bottom Navigation Bar -->
<nav class="mobile-bottom-nav d-lg-none" aria-label="Mobile Bottom Navigation">
    <!-- Home -->
    <a href="{{ url('/') }}" class="nav-tab-item {{ request()->is('/') ? 'active' : '' }}" aria-label="Home">
        <svg viewBox="0 0 24 24">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        <span>Home</span>
    </a>

    <!-- Orders -->
    <a href="{{ route('orders.index') }}" class="nav-tab-item {{ request()->routeIs('orders.*') ? 'active' : '' }}" aria-label="Orders">
        <svg viewBox="0 0 24 24">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
        </svg>
        <span>Orders</span>
    </a>

    <!-- Central Yellow Floating Action Button (+) -->
    <div class="nav-center-action">
        <a href="{{ route('booking.services') }}" class="btn-center-plus" aria-label="Create Order / Book Service">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
        </a>
    </div>

    <!-- Offers -->
    <a href="{{ route('services.index') }}" class="nav-tab-item" aria-label="Offers">
        <svg viewBox="0 0 24 24">
            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
            <line x1="7" y1="7" x2="7.01" y2="7"></line>
        </svg>
        <span>Offers</span>
    </a>

    <!-- Profile -->
    <a href="{{ route('admin.login') }}" class="nav-tab-item" aria-label="Profile">
        <svg viewBox="0 0 24 24">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
        <span>Profile</span>
    </a>
</nav>
