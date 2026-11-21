<!-- Mobile Header: Profile, Greeting, Bell, Hamburger -->
<header class="mobile-header">
    <div class="user-profile-group">
        <div class="avatar-wrapper">
            <img class="avatar-img" src="{{ asset('admin/assets/images/users/3.jpg') }}" alt="Emma Carter">
            <span class="avatar-badge" title="Online / Active"></span>
        </div>
        <div>
            <div class="user-greeting">
                <span>Hello</span>
                <span>👋</span>
            </div>
            <h2 class="user-name">Emma Carter</h2>
        </div>
    </div>

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
