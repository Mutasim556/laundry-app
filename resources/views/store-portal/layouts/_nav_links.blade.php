<a href="{{ route('store.dashboard') }}" class="sp-nav-link {{ request()->routeIs('store.dashboard') ? 'active' : '' }}">
    <i class="mdi mdi-view-dashboard font-18"></i>
    <span class="sp-nav-label">Dashboard</span>
</a>
<a href="{{ route('store.bookings.index') }}" class="sp-nav-link {{ request()->routeIs('store.bookings.*') ? 'active' : '' }}">
    <i class="mdi mdi-basket font-18"></i>
    <span class="sp-nav-label">My Bookings</span>
</a>
<a href="{{ route('store.handovers.index') }}" class="sp-nav-link {{ request()->routeIs('store.handovers.*') ? 'active' : '' }}">
    <i class="mdi mdi-truck-delivery font-18"></i>
    <span class="sp-nav-label">Incoming Handovers</span>
</a>
<a href="{{ route('store.earnings.index') }}" class="sp-nav-link {{ request()->routeIs('store.earnings.*') ? 'active' : '' }}">
    <i class="mdi mdi-cash-multiple font-18"></i>
    <span class="sp-nav-label">Earnings</span>
</a>
<a href="{{ route('store.account.index') }}" class="sp-nav-link {{ request()->routeIs('store.account.*') ? 'active' : '' }}">
    <i class="mdi mdi-wallet font-18"></i>
    <span class="sp-nav-label">My Store Account</span>
</a>
<a href="{{ route('store.profile.show') }}" class="sp-nav-link {{ request()->routeIs('store.profile.*') ? 'active' : '' }}">
    <i class="mdi mdi-store font-18"></i>
    <span class="sp-nav-label">Store Profile</span>
</a>
<a href="{{ route('store.password.change') }}" class="sp-nav-link {{ request()->routeIs('store.password.change*') ? 'active' : '' }}">
    <i class="mdi mdi-lock font-18"></i>
    <span class="sp-nav-label">Change Password</span>
</a>
