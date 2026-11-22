<nav class="sidebar-nav">
    <ul id="sidebarnav">
        <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'selected' : '' }}">
            <a href="{{ route('admin.dashboard') }}"
                class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i data-feather="grid" class="sidebar-icon" aria-hidden="true"></i>
                <span class="hide-menu">Dashboard</span>
            </a>
        </li>

        @if (auth('admin')->user()->can('view-roles', 'admin') ||
                auth('admin')->user()->can('view-admins', 'admin') ||
                auth('admin')->user()->can('view-users', 'admin'))
            <li
                class="sidebar-item {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.users.*') ? 'selected' : '' }}">
                <a class="sidebar-link has-arrow waves-effect waves-dark {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.users.*') ? 'active' : '' }}"
                    href="javascript:void(0)"
                    aria-expanded="{{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.users.*') ? 'true' : 'false' }}">
                    <i data-feather="shield" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Roles &amp; Permissions</span>
                </a>
                <ul aria-expanded="{{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.users.*') ? 'true' : 'false' }}"
                    class="collapse first-level {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.users.*') ? 'in' : '' }}">
                    @can('view-roles', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.roles.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                <i data-feather="key" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Roles</span>
                            </a>
                        </li>
                    @endcan

                    @can('view-admins', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.admins.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                                <i data-feather="user-check" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Admins</span>
                            </a>
                        </li>
                    @endcan

                    @can('view-users', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.users.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <i data-feather="users" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Users</span>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endif

        @can('manage-settings', 'admin')
            <li class="sidebar-item {{ request()->routeIs('admin.settings.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.settings.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i data-feather="droplet" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Branding &amp; Logo</span>
                </a>
            </li>
        @endcan

        @if (auth('admin')->user()->can('view-units', 'admin') ||
                auth('admin')->user()->can('view-item-categories', 'admin') ||
                auth('admin')->user()->can('view-items', 'admin') ||
                auth('admin')->user()->can('view-services', 'admin'))
            <li class="sidebar-item {{ request()->routeIs('admin.services.*') ? 'selected' : '' }}">
                <a class="sidebar-link has-arrow waves-effect waves-dark {{ request()->routeIs('admin.services.*') ? 'active' : '' }}"
                    href="javascript:void(0)"
                    aria-expanded="{{ request()->routeIs('admin.services.*') ? 'true' : 'false' }}">
                    <i data-feather="layers" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Services</span>
                </a>
                <ul aria-expanded="{{ request()->routeIs('admin.services.*') ? 'true' : 'false' }}"
                    class="collapse first-level {{ request()->routeIs('admin.services.*') ? 'in' : '' }}">
                    @can('view-units', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.services.units.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.units.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.units.*') ? 'active' : '' }}">
                                <i data-feather="sliders" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Unit</span>
                            </a>
                        </li>
                    @endcan

                    @can('view-item-categories', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.services.categories.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.categories.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.categories.*') ? 'active' : '' }}">
                                <i data-feather="tag" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Item Category</span>
                            </a>
                        </li>
                    @endcan

                    @can('view-items', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.services.items.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.items.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.items.*') ? 'active' : '' }}">
                                <i data-feather="shopping-bag" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Items</span>
                            </a>
                        </li>
                    @endcan

                    @can('view-services', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.services.services.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.services.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.services.*') ? 'active' : '' }}">
                                <i data-feather="box" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Services</span>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endif

        @if (auth('admin')->user()->can('view-stores', 'admin') ||
                auth('admin')->user()->can('manage-vendors', 'admin'))
            <li class="sidebar-item {{ request()->routeIs('admin.stores.*') ? 'selected' : '' }}">
                <a class="sidebar-link has-arrow waves-effect waves-dark {{ request()->routeIs('admin.stores.*') ? 'active' : '' }}"
                    href="javascript:void(0)"
                    aria-expanded="{{ request()->routeIs('admin.stores.*') ? 'true' : 'false' }}">
                    <i data-feather="shopping-cart" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Store Management</span>
                </a>
                <ul aria-expanded="{{ request()->routeIs('admin.stores.*') ? 'true' : 'false' }}"
                    class="collapse first-level {{ request()->routeIs('admin.stores.*') ? 'in' : '' }}">
                    @can('view-stores', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.stores.index') || request()->routeIs('admin.stores.update') ? 'active' : '' }}">
                            <a href="{{ route('admin.stores.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.stores.index') ? 'active' : '' }}">
                                <i data-feather="home" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Stores</span>
                            </a>
                        </li>
                    @endcan

                    @can('manage-vendors', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.stores.vendors.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.stores.vendors.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.stores.vendors.*') ? 'active' : '' }}">
                                <i data-feather="briefcase" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Vendors</span>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endif

        @if (auth('admin')->user()->can('view-delivery-persons', 'admin') ||
                auth('admin')->user()->can('manage-delivery-assignments', 'admin') ||
                auth('admin')->user()->can('manage-delivery-payouts', 'admin'))
            @php
                $dpRouteActive = request()->routeIs('admin.delivery-persons.*')
                    || request()->routeIs('admin.delivery-assignments.*')
                    || request()->routeIs('admin.delivery-activity.*')
                    || request()->routeIs('admin.delivery-payouts.*');
            @endphp
            <li class="sidebar-item {{ $dpRouteActive ? 'selected' : '' }}">
                <a class="sidebar-link has-arrow waves-effect waves-dark {{ $dpRouteActive ? 'active' : '' }}"
                    href="javascript:void(0)"
                    aria-expanded="{{ $dpRouteActive ? 'true' : 'false' }}">
                    <i data-feather="truck" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Delivery Persons</span>
                </a>
                <ul aria-expanded="{{ $dpRouteActive ? 'true' : 'false' }}"
                    class="collapse first-level {{ $dpRouteActive ? 'in' : '' }}">
                    @can('view-delivery-persons', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.delivery-persons.index') || request()->routeIs('admin.delivery-persons.show') || request()->routeIs('admin.delivery-persons.edit') ? 'active' : '' }}">
                            <a href="{{ route('admin.delivery-persons.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.delivery-persons.index') ? 'active' : '' }}">
                                <i data-feather="users" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">All Delivery Persons</span>
                            </a>
                        </li>
                    @endcan
                    @can('create-delivery-persons', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.delivery-persons.create') ? 'active' : '' }}">
                            <a href="{{ route('admin.delivery-persons.create') }}"
                                class="sidebar-link {{ request()->routeIs('admin.delivery-persons.create') ? 'active' : '' }}">
                                <i data-feather="user-plus" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Add Delivery Person</span>
                            </a>
                        </li>
                    @endcan
                    <li class="sidebar-item {{ request()->routeIs('admin.delivery-assignments.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.delivery-assignments.index') }}"
                            class="sidebar-link {{ request()->routeIs('admin.delivery-assignments.*') ? 'active' : '' }}">
                            <i data-feather="navigation" class="sidebar-icon" aria-hidden="true"></i>
                            <span class="hide-menu">Delivery Assignments</span>
                        </a>
                    </li>
                    <li class="sidebar-item {{ request()->routeIs('admin.delivery-activity.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.delivery-activity.index') }}"
                            class="sidebar-link {{ request()->routeIs('admin.delivery-activity.*') ? 'active' : '' }}">
                            <i data-feather="activity" class="sidebar-icon" aria-hidden="true"></i>
                            <span class="hide-menu">Activity History</span>
                        </a>
                    </li>
                    <li class="sidebar-item {{ request()->routeIs('admin.delivery-payouts.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.delivery-payouts.index') }}"
                            class="sidebar-link {{ request()->routeIs('admin.delivery-payouts.*') ? 'active' : '' }}">
                            <i data-feather="credit-card" class="sidebar-icon" aria-hidden="true"></i>
                            <span class="hide-menu">Account Balances &amp; Payouts</span>
                        </a>
                    </li>
                </ul>
            </li>
        @endif

        @can('view-uk-addresses', 'admin')
            <li class="sidebar-item {{ request()->routeIs('admin.uk-addresses.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.uk-addresses.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.uk-addresses.*') ? 'active' : '' }}">
                    <i data-feather="map-pin" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">UK Address Management</span>
                </a>
            </li>
        @endcan

        @can('view-hotels', 'admin')
            <li class="sidebar-item {{ request()->routeIs('admin.hotels.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.hotels.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.hotels.*') ? 'active' : '' }}">
                    <i data-feather="compass" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Hotel Management</span>
                </a>
            </li>
        @endcan

        @can('view-booking-schedule', 'admin')
            <li class="sidebar-item {{ request()->routeIs('admin.booking-schedule.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.booking-schedule.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.booking-schedule.*') ? 'active' : '' }}">
                    <i data-feather="clock" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Booking Schedule</span>
                </a>
            </li>
        @endcan

        @can('view-payment-gateways', 'admin')
            <li class="sidebar-item {{ request()->routeIs('admin.payment-gateways.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.payment-gateways.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.payment-gateways.*') ? 'active' : '' }}">
                    <i data-feather="credit-card" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Payment Gateways</span>
                </a>
            </li>
        @endcan

        @can('view-booking-fees', 'admin')
            <li class="sidebar-item {{ request()->routeIs('admin.booking-fees.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.booking-fees.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.booking-fees.*') ? 'active' : '' }}">
                    <i data-feather="dollar-sign" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Booking Fee Settings</span>
                </a>
            </li>
        @endcan

        @can('view-bookings', 'admin')
            <li class="sidebar-item {{ request()->routeIs('admin.bookings.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.bookings.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                    <i data-feather="check-square" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Booking Management</span>
                </a>
            </li>
        @endcan

        @can('manage-settings', 'admin')
            <li class="sidebar-item {{ request()->routeIs('admin.currency-settings.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.currency-settings.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.currency-settings.*') ? 'active' : '' }}">
                    <i data-feather="globe" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Country &amp; Currency</span>
                </a>
            </li>
        @endcan

        @can('view-database-backups', 'admin')
            <li class="sidebar-item {{ request()->routeIs('admin.database-backups.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.database-backups.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.database-backups.*') ? 'active' : '' }}">
                    <i data-feather="database" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Database Backup &amp; Restore</span>
                </a>
            </li>
        @endcan
    </ul>
</nav>
