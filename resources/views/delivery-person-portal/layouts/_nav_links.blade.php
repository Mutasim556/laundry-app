@php
    $scope = $navScope ?? 'desktop';
    $jobsActive = request()->routeIs(
        'delivery-person.bookings.*',
        'delivery-person.collections.*',
        'delivery-person.collection-jobs.*',
        'delivery-person.final-deliveries.*',
        'delivery-person.final-delivery-jobs.*',
        'delivery-person.tags.*'
    );
    $finActive = request()->routeIs(
        'delivery-person.earnings*',
        'delivery-person.cash-account*',
        'delivery-person.settlements*'
    );
    $jobsId = 'dpNavJobs-' . $scope;
    $finId = 'dpNavFin-' . $scope;
@endphp

<a href="{{ route('delivery-person.dashboard') }}"
   class="dp-nav-link {{ request()->routeIs('delivery-person.dashboard') ? 'active' : '' }}"
   title="Dashboard">
    <span class="dp-nav-ico" style="color: #3B82F6;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
    </span>
    <span class="dp-nav-label">Dashboard</span>
</a>

<!-- My Jobs Group -->
<div class="dp-nav-group">
    <button type="button"
            class="dp-nav-link dp-group-toggle w-100 border-0 bg-transparent text-start {{ $jobsActive ? 'is-parent-active' : '' }}"
            data-bs-toggle="collapse"
            data-bs-target="#{{ $jobsId }}"
            aria-expanded="{{ $jobsActive ? 'true' : 'false' }}"
            aria-controls="{{ $jobsId }}"
            title="My Jobs">
        <span class="dp-nav-ico" style="color: #16A34A;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>
        </span>
        <span class="dp-nav-label flex-grow-1">My Jobs</span>
        <span class="dp-chevron dp-nav-label" aria-hidden="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"/></svg>
        </span>
    </button>
    <div class="collapse {{ $jobsActive ? 'show' : '' }}" id="{{ $jobsId }}">
        <div class="dp-submenu">
            <a href="{{ route('delivery-person.bookings.index') }}"
               class="dp-nav-link dp-sub-link {{ request()->routeIs('delivery-person.bookings.*') ? 'active' : '' }}"
               title="All Bookings">
                <span class="dp-nav-ico" style="color: #16A34A;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg>
                </span>
                <span class="dp-nav-label">All Bookings</span>
            </a>
            <a href="{{ route('delivery-person.collections.index') }}"
               class="dp-nav-link dp-sub-link {{ request()->routeIs('delivery-person.collections.*', 'delivery-person.collection-jobs.*') ? 'active' : '' }}"
               title="Collections">
                <span class="dp-nav-ico" style="color: #16A34A;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </span>
                <span class="dp-nav-label">Collections</span>
            </a>
            <a href="{{ route('delivery-person.final-deliveries.index') }}"
               class="dp-nav-link dp-sub-link {{ request()->routeIs('delivery-person.final-deliveries.*', 'delivery-person.final-delivery-jobs.*') ? 'active' : '' }}"
               title="Final Deliveries">
                <span class="dp-nav-ico" style="color: #16A34A;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10 17h4V5H2v12h3"/><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5v8h1"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                </span>
                <span class="dp-nav-label">Final Deliveries</span>
            </a>
            <a href="{{ route('delivery-person.tags.index') }}"
               class="dp-nav-link dp-sub-link {{ request()->routeIs('delivery-person.tags.*') ? 'active' : '' }}"
               title="My Tags">
                <span class="dp-nav-ico" style="color: #16A34A;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3z"/><path d="M18 18h3v3h-3z"/></svg>
                </span>
                <span class="dp-nav-label">My Tags</span>
            </a>
        </div>
    </div>
</div>

<!-- Finance Group -->
<div class="dp-nav-group">
    <button type="button"
            class="dp-nav-link dp-group-toggle w-100 border-0 bg-transparent text-start {{ $finActive ? 'is-parent-active' : '' }}"
            data-bs-toggle="collapse"
            data-bs-target="#{{ $finId }}"
            aria-expanded="{{ $finActive ? 'true' : 'false' }}"
            aria-controls="{{ $finId }}"
            title="Finance">
        <span class="dp-nav-ico" style="color: #D97706;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
        </span>
        <span class="dp-nav-label flex-grow-1">Finance</span>
        <span class="dp-chevron dp-nav-label" aria-hidden="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"/></svg>
        </span>
    </button>
    <div class="collapse {{ $finActive ? 'show' : '' }}" id="{{ $finId }}">
        <div class="dp-submenu">
            <a href="{{ route('delivery-person.earnings') }}"
               class="dp-nav-link dp-sub-link {{ request()->routeIs('delivery-person.earnings*') ? 'active' : '' }}"
               title="My Earnings">
                <span class="dp-nav-ico" style="color: #D97706;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                </span>
                <span class="dp-nav-label">My Earnings</span>
            </a>
            <a href="{{ route('delivery-person.cash-account') }}"
               class="dp-nav-link dp-sub-link {{ request()->routeIs('delivery-person.cash-account*') ? 'active' : '' }}"
               title="Cash Account">
                <span class="dp-nav-ico" style="color: #D97706;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                </span>
                <span class="dp-nav-label">Cash Account</span>
            </a>
            <a href="{{ route('delivery-person.settlements') }}"
               class="dp-nav-link dp-sub-link {{ request()->routeIs('delivery-person.settlements*') ? 'active' : '' }}"
               title="Settlements">
                <span class="dp-nav-ico" style="color: #D97706;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M14 8H8"/><path d="M16 12H8"/><path d="M13 16H8"/></svg>
                </span>
                <span class="dp-nav-label">Settlements</span>
            </a>
        </div>
    </div>
</div>

<a href="{{ route('delivery-person.reports.index') }}"
   class="dp-nav-link {{ request()->routeIs('delivery-person.reports.*') ? 'active' : '' }}"
   title="Reports & Analytics">
    <span class="dp-nav-ico" style="color: #8B5CF6;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
    </span>
    <span class="dp-nav-label">Reports &amp; Analytics</span>
</a>

<a href="{{ route('delivery-person.profile') }}"
   class="dp-nav-link {{ request()->routeIs('delivery-person.profile*') ? 'active' : '' }}"
   title="My Profile">
    <span class="dp-nav-ico" style="color: #8B5CF6;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </span>
    <span class="dp-nav-label">My Profile</span>
</a>
