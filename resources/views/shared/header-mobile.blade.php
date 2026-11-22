<!-- Mobile Header: Profile, Greeting, Bell, Hamburger -->
<header class="mobile-header">
    @auth('web')
        @php $customer = auth('web')->user(); @endphp
        <div class="dropdown">
            <button type="button"
                    class="btn p-0 border-0 bg-transparent text-start user-profile-group"
                    id="mobileProfileDropdownBtn"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">
                <div class="avatar-wrapper d-flex align-items-center justify-content-center fw-bold text-dark"
                     style="background: #FFF8E5; border: 1.8px solid #FFC629;">
                    <span>{{ $customer->initials }}</span>
                    <span class="avatar-badge" title="Online / Active"></span>
                </div>
                <div>
                    <div class="user-greeting">
                        <span>Hello</span>
                        <span>👋</span>
                    </div>
                    <h2 class="user-name mb-0">{{ $customer->name }} ▾</h2>
                </div>
            </button>
            <ul class="dropdown-menu border-0 shadow rounded-4 p-2 mt-2 customer-profile-dropdown"
                aria-labelledby="mobileProfileDropdownBtn"
                style="min-width: 220px;">
                <li><a class="dropdown-item rounded-3 py-2" href="{{ route('account.profile') }}">👤 Profile</a></li>
                <li><a class="dropdown-item rounded-3 py-2" href="{{ route('orders.index') }}">📦 My Orders</a></li>
                <li><a class="dropdown-item rounded-3 py-2" href="{{ route('account.addresses') }}">📍 Address Book</a></li>
                <li><a class="dropdown-item rounded-3 py-2" href="{{ route('account.payment-methods') }}">💳 Saved Payment Methods</a></li>
                <li><hr class="dropdown-divider my-2"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item rounded-3 py-2 text-danger fw-semibold">🚪 Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    @else
        <a href="{{ route('login') }}" class="user-profile-group text-decoration-none">
            <div class="avatar-wrapper d-flex align-items-center justify-content-center"
                 style="background: #FFF8E5; border: 1.8px solid #FFC629;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2.2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <div>
                <div class="user-greeting">
                    <span>Welcome</span>
                    <span>👋</span>
                </div>
                <h2 class="user-name mb-0">Login / Sign Up</h2>
            </div>
        </a>
    @endauth

    <div class="header-action-group">
        <!-- Notification Bell with Yellow Indicator -->
        <button type="button" class="icon-btn-circle js-notification-bell" data-bs-toggle="offcanvas" data-bs-target="#notificationOffcanvas" aria-label="Notifications">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span class="notify-indicator"></span>
        </button>

        <!-- Hamburger Menu -->
        <button type="button" class="icon-btn-circle" data-bs-toggle="offcanvas" data-bs-target="#mobileMenuOffcanvas" aria-label="Open Navigation Menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="4" y1="7" x2="20" y2="7"></line>
                <line x1="4" y1="12" x2="20" y2="12"></line>
                <line x1="4" y1="17" x2="20" y2="17"></line>
            </svg>
        </button>
    </div>
</header>
