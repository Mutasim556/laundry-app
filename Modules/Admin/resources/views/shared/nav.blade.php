<nav class="sidebar-nav">
    <ul id="sidebarnav">
        <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'selected' : '' }}">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="mdi mdi-view-dashboard"></i>
                <span class="hide-menu"> Dashboard</span>
            </a>
        </li>
        @if(auth('admin')->user()->can('view-roles') || auth('admin')->user()->can('view-admins') || auth('admin')->user()->can('view-users'))
        <li class="sidebar-item {{ (request()->routeIs('admin.roles.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.users.*')) ? 'selected' : '' }}">
            <a
                class="sidebar-link has-arrow waves-effect waves-dark {{ (request()->routeIs('admin.roles.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.users.*')) ? 'active' : '' }}"
                href="javascript:void(0)"
                aria-expanded="false"><i class="mdi mdi-security"></i><span class="hide-menu">Roles & Permissions</span></a>
            <ul aria-expanded="false" class="collapse first-level {{ (request()->routeIs('admin.roles.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.users.*')) ? 'in' : '' }}">
                @can('view-roles', 'admin')
                <li class="sidebar-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.roles.index') }}" class="sidebar-link"><i class="mdi mdi-account-key"></i><span class="hide-menu">Roles</span></a>
                </li>
                @endcan

                @can('view-admins', 'admin')
                <li class="sidebar-item {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.admins.index') }}" class="sidebar-link"><i class="mdi mdi-account-star"></i><span class="hide-menu">Admins</span></a>
                </li>
                @endcan

                @can('view-users', 'admin')
                <li class="sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.users.index') }}" class="sidebar-link"><i class="mdi mdi-account-multiple"></i><span class="hide-menu">Users</span></a>
                </li>
                @endcan
            </ul>
        </li>
        @endif
    </ul>
</nav>
