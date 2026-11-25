@php
    $adminMenuVisible = $adminMenuVisible ?? fn (string $menu, ?string $child = null): bool => true;
@endphp
<nav class="sidebar-nav">
    <ul id="sidebarnav">
        @if ($adminMenuVisible('Dashboard'))
            <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'selected' : '' }}">
                <a href="{{ route('admin.dashboard') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i data-feather="grid" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Dashboard</span>
                </a>
            </li>
        @endif

        @php
            $showRolesMenu = auth('admin')->user()->can('view-roles', 'admin') && $adminMenuVisible('Roles & Permissions', 'Roles');
            $showAdminsMenu = auth('admin')->user()->can('view-admins', 'admin') && $adminMenuVisible('Roles & Permissions', 'Admins');
            $showUsersMenu = auth('admin')->user()->can('view-users', 'admin') && $adminMenuVisible('Roles & Permissions', 'Users');
            $showRolesParent = $adminMenuVisible('Roles & Permissions') && ($showRolesMenu || $showAdminsMenu || $showUsersMenu);
        @endphp
        @if ($showRolesParent)
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
                    @if ($showRolesMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.roles.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                <i data-feather="key" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Roles</span>
                            </a>
                        </li>
                    @endif

                    @if ($showAdminsMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.admins.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                                <i data-feather="user-check" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Admins</span>
                            </a>
                        </li>
                    @endif

                    @if ($showUsersMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.users.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <i data-feather="users" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Users</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @if (auth('admin')->user()->can('manage-settings', 'admin') && $adminMenuVisible('Branding & Logo'))
            <li class="sidebar-item {{ request()->routeIs('admin.settings.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.settings.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i data-feather="droplet" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Branding &amp; Logo</span>
                </a>
            </li>
        @endif

        @php
            $showUnitsMenu = auth('admin')->user()->can('view-units', 'admin') && $adminMenuVisible('Services', 'Unit');
            $showItemCatMenu = auth('admin')->user()->can('view-item-categories', 'admin') && $adminMenuVisible('Services', 'Item Category');
            $showItemsMenu = auth('admin')->user()->can('view-items', 'admin') && $adminMenuVisible('Services', 'Items');
            $showServicesChildMenu = auth('admin')->user()->can('view-services', 'admin') && $adminMenuVisible('Services', 'Services');
            $showServicesParent = $adminMenuVisible('Services') && ($showUnitsMenu || $showItemCatMenu || $showItemsMenu || $showServicesChildMenu);
        @endphp
        @if ($showServicesParent)
            <li class="sidebar-item {{ request()->routeIs('admin.services.*') ? 'selected' : '' }}">
                <a class="sidebar-link has-arrow waves-effect waves-dark {{ request()->routeIs('admin.services.*') ? 'active' : '' }}"
                    href="javascript:void(0)"
                    aria-expanded="{{ request()->routeIs('admin.services.*') ? 'true' : 'false' }}">
                    <i data-feather="layers" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Services</span>
                </a>
                <ul aria-expanded="{{ request()->routeIs('admin.services.*') ? 'true' : 'false' }}"
                    class="collapse first-level {{ request()->routeIs('admin.services.*') ? 'in' : '' }}">
                    @if ($showUnitsMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.services.units.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.units.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.units.*') ? 'active' : '' }}">
                                <i data-feather="sliders" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Unit</span>
                            </a>
                        </li>
                    @endif

                    @if ($showItemCatMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.services.categories.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.categories.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.categories.*') ? 'active' : '' }}">
                                <i data-feather="tag" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Item Category</span>
                            </a>
                        </li>
                    @endif

                    @if ($showItemsMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.services.items.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.items.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.items.*') ? 'active' : '' }}">
                                <i data-feather="shopping-bag" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Items</span>
                            </a>
                        </li>
                    @endif

                    @if ($showServicesChildMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.services.services.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.services.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.services.*') ? 'active' : '' }}">
                                <i data-feather="box" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Services</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @php
            $showStoresChild = auth('admin')->user()->can('view-stores', 'admin') && $adminMenuVisible('Store Management', 'Stores');
            $showVendorsChild = auth('admin')->user()->can('manage-vendors', 'admin') && $adminMenuVisible('Store Management', 'Vendors');
            $showStoreWithdrawalsChild = (auth('admin')->user()->can('view-store-accounts', 'admin') || auth('admin')->user()->can('view-stores', 'admin')) && $adminMenuVisible('Store Management', 'Store Withdrawals');
            $showStoreParent = $adminMenuVisible('Store Management') && ($showStoresChild || $showVendorsChild || $showStoreWithdrawalsChild);
        @endphp
        @if ($showStoreParent)
            <li class="sidebar-item {{ request()->routeIs('admin.stores.*') || request()->routeIs('admin.store-withdrawals.*') ? 'selected' : '' }}">
                <a class="sidebar-link has-arrow waves-effect waves-dark {{ request()->routeIs('admin.stores.*') || request()->routeIs('admin.store-withdrawals.*') ? 'active' : '' }}"
                    href="javascript:void(0)"
                    aria-expanded="{{ request()->routeIs('admin.stores.*') || request()->routeIs('admin.store-withdrawals.*') ? 'true' : 'false' }}">
                    <i data-feather="shopping-cart" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Store Management</span>
                </a>
                <ul aria-expanded="{{ request()->routeIs('admin.stores.*') || request()->routeIs('admin.store-withdrawals.*') ? 'true' : 'false' }}"
                    class="collapse first-level {{ request()->routeIs('admin.stores.*') || request()->routeIs('admin.store-withdrawals.*') ? 'in' : '' }}">
                    @if ($showStoresChild)
                        <li class="sidebar-item {{ request()->routeIs('admin.stores.index') || request()->routeIs('admin.stores.update') ? 'active' : '' }}">
                            <a href="{{ route('admin.stores.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.stores.index') ? 'active' : '' }}">
                                <i data-feather="home" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Stores</span>
                            </a>
                        </li>
                    @endif

                    @if ($showVendorsChild)
                        <li class="sidebar-item {{ request()->routeIs('admin.stores.vendors.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.stores.vendors.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.stores.vendors.*') ? 'active' : '' }}">
                                <i data-feather="briefcase" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Vendors</span>
                            </a>
                        </li>
                    @endif
                    @if ($showStoreWithdrawalsChild)
                        <li class="sidebar-item {{ request()->routeIs('admin.store-withdrawals.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.store-withdrawals.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.store-withdrawals.*') ? 'active' : '' }}">
                                <i data-feather="dollar-sign" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Store Withdrawals</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @php
            $hasDpPerm = auth('admin')->user()->can('view-delivery-persons', 'admin')
                || auth('admin')->user()->can('manage-delivery-assignments', 'admin')
                || auth('admin')->user()->can('manage-delivery-payouts', 'admin');
            $showDpAll = auth('admin')->user()->can('view-delivery-persons', 'admin') && $adminMenuVisible('Delivery Persons', 'All Delivery Persons');
            $showDpAdd = auth('admin')->user()->can('create-delivery-persons', 'admin') && $adminMenuVisible('Delivery Persons', 'Add Delivery Person');
            $showDpAssign = $adminMenuVisible('Delivery Persons', 'Delivery Assignments');
            $showDpActivity = $adminMenuVisible('Delivery Persons', 'Activity History');
            $showDpPayouts = $adminMenuVisible('Delivery Persons', 'Account Balances & Payouts');
            $showDpWithdrawals = $adminMenuVisible('Delivery Persons', 'Withdrawal Requests');
            $showDpParent = $hasDpPerm && $adminMenuVisible('Delivery Persons') && ($showDpAll || $showDpAdd || $showDpAssign || $showDpActivity || $showDpPayouts || $showDpWithdrawals);
        @endphp
        @if ($showDpParent)
            @php
                $dpRouteActive = request()->routeIs('admin.delivery-persons.*')
                    || request()->routeIs('admin.delivery-assignments.*')
                    || request()->routeIs('admin.delivery-activity.*')
                    || request()->routeIs('admin.delivery-payouts.*')
                    || request()->routeIs('admin.delivery-person-withdrawals.*');
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
                    @if ($showDpAll)
                        <li class="sidebar-item {{ request()->routeIs('admin.delivery-persons.index') || request()->routeIs('admin.delivery-persons.show') || request()->routeIs('admin.delivery-persons.edit') ? 'active' : '' }}">
                            <a href="{{ route('admin.delivery-persons.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.delivery-persons.index') ? 'active' : '' }}">
                                <i data-feather="users" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">All Delivery Persons</span>
                            </a>
                        </li>
                    @endif
                    @if ($showDpAdd)
                        <li class="sidebar-item {{ request()->routeIs('admin.delivery-persons.create') ? 'active' : '' }}">
                            <a href="{{ route('admin.delivery-persons.create') }}"
                                class="sidebar-link {{ request()->routeIs('admin.delivery-persons.create') ? 'active' : '' }}">
                                <i data-feather="user-plus" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Add Delivery Person</span>
                            </a>
                        </li>
                    @endif
                    @if ($showDpAssign)
                        <li class="sidebar-item {{ request()->routeIs('admin.delivery-assignments.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.delivery-assignments.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.delivery-assignments.*') ? 'active' : '' }}">
                                <i data-feather="navigation" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Delivery Assignments</span>
                            </a>
                        </li>
                    @endif
                    @if ($showDpActivity)
                        <li class="sidebar-item {{ request()->routeIs('admin.delivery-activity.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.delivery-activity.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.delivery-activity.*') ? 'active' : '' }}">
                                <i data-feather="activity" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Activity History</span>
                            </a>
                        </li>
                    @endif
                    @if ($showDpPayouts)
                        <li class="sidebar-item {{ request()->routeIs('admin.delivery-payouts.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.delivery-payouts.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.delivery-payouts.*') ? 'active' : '' }}">
                                <i data-feather="credit-card" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Account Balances &amp; Payouts</span>
                            </a>
                        </li>
                    @endif
                    @if ($showDpWithdrawals)
                        <li class="sidebar-item {{ request()->routeIs('admin.delivery-person-withdrawals.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.delivery-person-withdrawals.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.delivery-person-withdrawals.*') ? 'active' : '' }}">
                                <i data-feather="dollar-sign" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Withdrawal Requests</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @if (auth('admin')->user()->can('view-uk-addresses', 'admin') && $adminMenuVisible('UK Address Management'))
            <li class="sidebar-item {{ request()->routeIs('admin.uk-addresses.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.uk-addresses.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.uk-addresses.*') ? 'active' : '' }}">
                    <i data-feather="map-pin" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">UK Address Management</span>
                </a>
            </li>
        @endif

        @if (auth('admin')->user()->can('view-hotels', 'admin') && $adminMenuVisible('Hotel Management'))
            <li class="sidebar-item {{ request()->routeIs('admin.hotels.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.hotels.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.hotels.*') ? 'active' : '' }}">
                    <i data-feather="compass" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Hotel Management</span>
                </a>
            </li>
        @endif

        @if (auth('admin')->user()->can('view-booking-schedule', 'admin') && $adminMenuVisible('Booking Schedule'))
            <li class="sidebar-item {{ request()->routeIs('admin.booking-schedule.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.booking-schedule.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.booking-schedule.*') ? 'active' : '' }}">
                    <i data-feather="clock" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Booking Schedule</span>
                </a>
            </li>
        @endif

        @if (auth('admin')->user()->can('view-payment-gateways', 'admin') && $adminMenuVisible('Payment Gateways'))
            <li class="sidebar-item {{ request()->routeIs('admin.payment-gateways.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.payment-gateways.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.payment-gateways.*') ? 'active' : '' }}">
                    <i data-feather="credit-card" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Payment Gateways</span>
                </a>
            </li>
        @endif

        @if (auth('admin')->user()->can('view-booking-fees', 'admin') && $adminMenuVisible('Booking Fee Settings'))
            <li class="sidebar-item {{ request()->routeIs('admin.booking-fees.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.booking-fees.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.booking-fees.*') ? 'active' : '' }}">
                    <i data-feather="dollar-sign" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Booking Fee Settings</span>
                </a>
            </li>
        @endif

        @can('view-bookings', 'admin')
            @if ($adminMenuVisible('Booking Management'))
                <li class="sidebar-item {{ request()->routeIs('admin.bookings.*') ? 'selected' : '' }}">
                    <a href="{{ route('admin.bookings.index') }}"
                        class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                        <i data-feather="check-square" class="sidebar-icon" aria-hidden="true"></i>
                        <span class="hide-menu">Booking Management</span>
                    </a>
                </li>
            @endif
            @php
                $showRrAll = $adminMenuVisible('Refund & Returns', 'All Requests');
                $showRrRefund = $adminMenuVisible('Refund & Returns', 'Refund Requests');
                $showRrRewash = $adminMenuVisible('Refund & Returns', 'Rewash Requests');
                $showRrDamage = $adminMenuVisible('Refund & Returns', 'Damage & Missing Claims');
                $showRrPolicy = $adminMenuVisible('Refund & Returns', 'Policy Settings');
                $showRrHistory = $adminMenuVisible('Refund & Returns', 'Policy History');
                $showRrTx = $adminMenuVisible('Refund & Returns', 'Refund Transactions');
                $showRrParent = $adminMenuVisible('Refund & Returns') && ($showRrAll || $showRrRefund || $showRrRewash || $showRrDamage || $showRrPolicy || $showRrHistory || $showRrTx);
            @endphp
            @if ($showRrParent)
                @php $rrActive = request()->routeIs('admin.refund-returns.*'); @endphp
                <li class="sidebar-item {{ $rrActive ? 'selected' : '' }}">
                    <a class="sidebar-link has-arrow waves-effect waves-dark {{ $rrActive ? 'active' : '' }}"
                        href="javascript:void(0)" aria-expanded="{{ $rrActive ? 'true' : 'false' }}">
                        <i data-feather="rotate-ccw" class="sidebar-icon" aria-hidden="true"></i>
                        <span class="hide-menu">Refund &amp; Returns</span>
                    </a>
                    <ul aria-expanded="{{ $rrActive ? 'true' : 'false' }}" class="collapse first-level {{ $rrActive ? 'in' : '' }}">
                        @if ($showRrAll)
                            <li class="sidebar-item">
                                <a href="{{ route('admin.refund-returns.cases.index') }}" class="sidebar-link {{ request()->routeIs('admin.refund-returns.cases.index') && !request('type') ? 'active' : '' }}">
                                    <span class="hide-menu">All Requests</span>
                                </a>
                            </li>
                        @endif
                        @if ($showRrRefund)
                            <li class="sidebar-item">
                                <a href="{{ route('admin.refund-returns.cases.index', ['type' => 'cancellation_refund']) }}" class="sidebar-link {{ request('type') === 'cancellation_refund' ? 'active' : '' }}">
                                    <span class="hide-menu">Refund Requests</span>
                                </a>
                            </li>
                        @endif
                        @if ($showRrRewash)
                            <li class="sidebar-item">
                                <a href="{{ route('admin.refund-returns.cases.index', ['type' => 'rewash']) }}" class="sidebar-link {{ request('type') === 'rewash' ? 'active' : '' }}">
                                    <span class="hide-menu">Rewash Requests</span>
                                </a>
                            </li>
                        @endif
                        @if ($showRrDamage)
                            <li class="sidebar-item">
                                <a href="{{ route('admin.refund-returns.cases.index', ['type' => 'damage_missing']) }}" class="sidebar-link {{ request('type') === 'damage_missing' ? 'active' : '' }}">
                                    <span class="hide-menu">Damage &amp; Missing Claims</span>
                                </a>
                            </li>
                        @endif
                        @if ($showRrPolicy)
                            <li class="sidebar-item">
                                <a href="{{ route('admin.refund-returns.policies.index') }}" class="sidebar-link {{ request()->routeIs('admin.refund-returns.policies.index') ? 'active' : '' }}">
                                    <span class="hide-menu">Policy Settings</span>
                                </a>
                            </li>
                        @endif
                        @if ($showRrHistory)
                            <li class="sidebar-item">
                                <a href="{{ route('admin.refund-returns.policies.history') }}" class="sidebar-link {{ request()->routeIs('admin.refund-returns.policies.history') ? 'active' : '' }}">
                                    <span class="hide-menu">Policy History</span>
                                </a>
                            </li>
                        @endif
                        @if ($showRrTx)
                            <li class="sidebar-item">
                                <a href="{{ route('admin.refund-returns.refunds.index') }}" class="sidebar-link {{ request()->routeIs('admin.refund-returns.refunds.*') ? 'active' : '' }}">
                                    <span class="hide-menu">Refund Transactions</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
        @endcan

        @if (auth('admin')->user()->can('view-admin-accounts', 'admin') && $adminMenuVisible('Current Account'))
            <li class="sidebar-item {{ request()->routeIs('admin.current-account.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.current-account.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.current-account.*') ? 'active' : '' }}">
                    <i data-feather="credit-card" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Current Account</span>
                </a>
            </li>
        @endif

        @if (auth('admin')->user()->can('view-delivery-settlements', 'admin') && $adminMenuVisible('Delivery Settlements'))
            <li class="sidebar-item {{ request()->routeIs('admin.delivery-person-settlements.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.delivery-person-settlements.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.delivery-person-settlements.*') ? 'active' : '' }}">
                    <i data-feather="dollar-sign" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Delivery Settlements</span>
                </a>
            </li>
        @endif

        @if (auth('admin')->user()->can('manage-settings', 'admin') && $adminMenuVisible('Country & Currency'))
            <li class="sidebar-item {{ request()->routeIs('admin.currency-settings.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.currency-settings.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.currency-settings.*') ? 'active' : '' }}">
                    <i data-feather="globe" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Country &amp; Currency</span>
                </a>
            </li>
        @endif

        @if (auth('admin')->user()->can('view-database-backups', 'admin') && $adminMenuVisible('Database Backup & Restore'))
            <li class="sidebar-item {{ request()->routeIs('admin.database-backups.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.database-backups.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.database-backups.*') ? 'active' : '' }}">
                    <i data-feather="database" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Database Backup &amp; Restore</span>
                </a>
            </li>
        @endif

        @if (auth('admin')->user()->can('manage-database-cleanup', 'admin') && $adminMenuVisible('Database Cleanup'))
            <li class="sidebar-item {{ request()->routeIs('admin.database-cleanup.*') ? 'selected' : '' }}">
                <a href="{{ route('admin.database-cleanup.index') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.database-cleanup.*') ? 'active' : '' }}">
                    <i data-feather="trash-2" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Database Cleanup</span>
                </a>
            </li>
        @endif
    </ul>
</nav>
