@php
    $scope = $navScope ?? 'desktop';
    $opsActive = request()->routeIs('store.bookings.*', 'store.handovers.*');
    $finActive = request()->routeIs('store.account.*', 'store.earnings.*');
    $opsId = 'spNavOps-' . $scope;
    $finId = 'spNavFin-' . $scope;
@endphp

<a href="{{ route('store.dashboard') }}"
   class="sp-nav-link {{ request()->routeIs('store.dashboard') ? 'active' : '' }}"
   data-sp-tooltip="Dashboard"
   title="Dashboard">
    <span class="sp-nav-ico" style="color: #3B82F6;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
    </span>
    <span class="sp-nav-label">Dashboard</span>
</a>

<!-- Operations Group -->
<div class="sp-nav-group">
    <button type="button"
            class="sp-nav-link sp-group-toggle w-100 border-0 bg-transparent text-start {{ $opsActive ? 'is-parent-active' : '' }}"
            data-bs-toggle="collapse"
            data-bs-target="#{{ $opsId }}"
            aria-expanded="{{ $opsActive ? 'true' : 'false' }}"
            aria-controls="{{ $opsId }}"
            data-sp-tooltip="Operations"
            title="Operations">
        <span class="sp-nav-ico" style="color: #16A34A;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>
        </span>
        <span class="sp-nav-label flex-grow-1">Operations</span>
        <span class="sp-chevron sp-nav-label" aria-hidden="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"/></svg>
        </span>
    </button>
    <div class="collapse {{ $opsActive ? 'show' : '' }}" id="{{ $opsId }}">
        <div class="sp-submenu">
            <a href="{{ route('store.bookings.index') }}"
               class="sp-nav-link sp-sub-link {{ request()->routeIs('store.bookings.*') ? 'active' : '' }}"
               data-sp-tooltip="Bookings"
               title="Bookings">
                <span class="sp-nav-ico" style="color: #16A34A;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg>
                </span>
                <span class="sp-nav-label">Bookings</span>
            </a>
            <a href="{{ route('store.handovers.index') }}"
               class="sp-nav-link sp-sub-link {{ request()->routeIs('store.handovers.*') ? 'active' : '' }}"
               data-sp-tooltip="Handovers"
               title="Handovers">
                <span class="sp-nav-ico" style="color: #16A34A;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </span>
                <span class="sp-nav-label">Handovers</span>
            </a>
        </div>
    </div>
</div>

<!-- Finance Group -->
<div class="sp-nav-group">
    <button type="button"
            class="sp-nav-link sp-group-toggle w-100 border-0 bg-transparent text-start {{ $finActive ? 'is-parent-active' : '' }}"
            data-bs-toggle="collapse"
            data-bs-target="#{{ $finId }}"
            aria-expanded="{{ $finActive ? 'true' : 'false' }}"
            aria-controls="{{ $finId }}"
            data-sp-tooltip="Finance"
            title="Finance">
        <span class="sp-nav-ico" style="color: #D97706;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
        </span>
        <span class="sp-nav-label flex-grow-1">Finance</span>
        <span class="sp-chevron sp-nav-label" aria-hidden="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"/></svg>
        </span>
    </button>
    <div class="collapse {{ $finActive ? 'show' : '' }}" id="{{ $finId }}">
        <div class="sp-submenu">
            <a href="{{ route('store.account.index') }}"
               class="sp-nav-link sp-sub-link {{ request()->routeIs('store.account.*') ? 'active' : '' }}"
               data-sp-tooltip="Account"
               title="Account">
                <span class="sp-nav-ico" style="color: #D97706;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                </span>
                <span class="sp-nav-label">Account</span>
            </a>
            <a href="{{ route('store.earnings.index') }}"
               class="sp-nav-link sp-sub-link {{ request()->routeIs('store.earnings.*') ? 'active' : '' }}"
               data-sp-tooltip="Earnings"
               title="Earnings">
                <span class="sp-nav-ico" style="color: #D97706;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                </span>
                <span class="sp-nav-label">Earnings</span>
            </a>
        </div>
    </div>
</div>

<a href="{{ route('store.reports.index') }}"
   class="sp-nav-link {{ request()->routeIs('store.reports.*') ? 'active' : '' }}"
   data-sp-tooltip="Reports & Analytics"
   title="Reports & Analytics">
    <span class="sp-nav-ico" style="color: #8B5CF6;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    </span>
    <span class="sp-nav-label">Reports &amp; Analytics</span>
</a>

<a href="{{ route('store.profile.show') }}"
   class="sp-nav-link {{ request()->routeIs('store.profile.*') ? 'active' : '' }}"
   data-sp-tooltip="My Profile"
   title="My Profile">
    <span class="sp-nav-ico" style="color: #171717;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </span>
    <span class="sp-nav-label">My Profile</span>
</a>
