<nav class="sidebar-nav">
    <ul id="sidebarnav">
        <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'selected' : '' }}">
            <a href="{{ route('admin.dashboard') }}"
                class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="mdi mdi-view-dashboard"></i>
                <span class="hide-menu"> Dashboard</span>
            </a>
        </li>
        @if (auth('admin')->user()->can('view-roles') ||
                auth('admin')->user()->can('view-admins') ||
                auth('admin')->user()->can('view-users'))
            <li
                class="sidebar-item {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.users.*') ? 'selected' : '' }}">
                <a class="sidebar-link has-arrow waves-effect waves-dark {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.users.*') ? 'active' : '' }}"
                    href="javascript:void(0)" aria-expanded="false"><i class="mdi mdi-security"></i><span
                        class="hide-menu">Roles & Permissions</span></a>
                <ul aria-expanded="false"
                    class="collapse first-level {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.users.*') ? 'in' : '' }}">
                    @can('view-roles', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.roles.index') }}" class="sidebar-link"><i
                                    class="mdi mdi-account-key"></i><span class="hide-menu">Roles</span></a>
                        </li>
                    @endcan

                    @can('view-admins', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.admins.index') }}" class="sidebar-link"><i
                                    class="mdi mdi-account-star"></i><span class="hide-menu">Admins</span></a>
                        </li>
                    @endcan

                    @can('view-users', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.users.index') }}" class="sidebar-link"><i
                                    class="mdi mdi-account-multiple"></i><span class="hide-menu">Users</span></a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endif

        @can('manage-settings', 'admin')
            <li class="sidebar-item {{ request()->routeIs('admin.settings.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.settings.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i class="mdi mdi-palette"></i>
                    <span class="hide-menu"> Branding & Logo</span>
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
                    <i class="mdi mdi-washing-machine"></i>
                    <span class="hide-menu">Services</span>
                </a>
                <ul aria-expanded="{{ request()->routeIs('admin.services.*') ? 'true' : 'false' }}"
                    class="collapse first-level {{ request()->routeIs('admin.services.*') ? 'in' : '' }}">
                    @can('view-units', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.services.units.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.units.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.units.*') ? 'active' : '' }}">
                                <i class="mdi mdi-scale-balance"></i>
                                <span class="hide-menu">Unit</span>
                            </a>
                        </li>
                    @endcan

                    @can('view-item-categories', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.services.categories.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.categories.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.categories.*') ? 'active' : '' }}">
                                <i class="mdi mdi-tag-text-outline"></i>
                                <span class="hide-menu">Item Category</span>
                            </a>
                        </li>
                    @endcan

                    @can('view-items', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.services.items.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.items.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.items.*') ? 'active' : '' }}">
                                <i class="mdi mdi-tshirt-crew"></i>
                                <span class="hide-menu">Items</span>
                            </a>
                        </li>
                    @endcan

                    @can('view-services', 'admin')
                        <li class="sidebar-item {{ request()->routeIs('admin.services.services.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.services.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.services.*') ? 'active' : '' }}">
                                <i class="mdi mdi-cube-outline"></i>
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
            <a class="sidebar-link has-arrow waves-effect waves-dark {{ request()->routeIs('admin.stores.*') ? 'active' : '' }}" href="javascript:void(0)" aria-expanded="{{ request()->routeIs('admin.stores.*') ? 'true' : 'false' }}">
                <i class="mdi mdi-store"></i>
                <span class="hide-menu">Store Management</span>
            </a>
            <ul aria-expanded="{{ request()->routeIs('admin.stores.*') ? 'true' : 'false' }}" class="collapse first-level {{ request()->routeIs('admin.stores.*') ? 'in' : '' }}">
                @can('view-stores', 'admin')
                <li class="sidebar-item {{ request()->routeIs('admin.stores.index') || request()->routeIs('admin.stores.update') ? 'active' : '' }}">
                    <a href="{{ route('admin.stores.index') }}" class="sidebar-link {{ request()->routeIs('admin.stores.index') ? 'active' : '' }}">
                        <i class="mdi mdi-store"></i>
                        <span class="hide-menu">Stores</span>
                    </a>
                </li>
                @endcan

                @can('manage-vendors', 'admin')
                <li class="sidebar-item {{ request()->routeIs('admin.stores.vendors.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.stores.vendors.index') }}" class="sidebar-link {{ request()->routeIs('admin.stores.vendors.*') ? 'active' : '' }}">
                        <i class="mdi mdi-briefcase"></i>
                        <span class="hide-menu">Vendors</span>
                    </a>
                </li>
                @endcan
            </ul>
        </li>
        @endif
    </ul>
</nav>
