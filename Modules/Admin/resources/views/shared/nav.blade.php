@php
    $adminMenuVisible = $adminMenuVisible ?? fn (string $menu, ?string $child = null): bool => true;
@endphp
<style>
    .fg-sidebar-search-wrap {
        padding: 0.6rem 0.75rem 0.35rem;
    }
    .fg-sidebar-search-box {
        position: relative;
        display: flex;
        align-items: center;
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .fg-sidebar-search-box:focus-within {
        border-color: #FFC629;
        box-shadow: 0 0 0 3px rgba(255, 198, 41, 0.22);
    }
    .fg-sidebar-search-ico {
        position: absolute;
        left: 0.6rem;
        color: #64748B;
        pointer-events: none;
        display: inline-flex;
        align-items: center;
    }
    .fg-sidebar-search-input {
        width: 100%;
        border: 0;
        background: transparent;
        padding: 0.42rem 1.85rem 0.42rem 1.95rem;
        font-size: 0.8rem;
        color: #171717;
        outline: none;
        border-radius: 10px;
    }
    .fg-sidebar-search-input::placeholder {
        color: #94A3B8;
    }
    .fg-sidebar-search-clear {
        position: absolute;
        right: 0.35rem;
        border: 0;
        background: transparent;
        color: #64748B;
        width: 22px;
        height: 22px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.95rem;
        line-height: 1;
    }
    .fg-sidebar-search-clear:hover {
        background: #FFF8E5;
        color: #171717;
    }
    .fg-sidebar-search-mini-btn {
        display: none;
        width: 38px;
        height: 36px;
        margin: 0.35rem auto 0.2rem;
        border: 1px solid #E2E8F0;
        background: #FFFFFF;
        color: #475569;
        border-radius: 10px;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .fg-sidebar-search-mini-btn:hover {
        background: #FFF8E5;
        border-color: #FFC629;
        color: #171717;
    }
    .mini-sidebar .fg-sidebar-search-wrap {
        padding: 0.25rem;
    }
    .mini-sidebar .fg-sidebar-search-box {
        display: none;
    }
    .mini-sidebar .fg-sidebar-search-mini-btn {
        display: flex;
    }
    .fg-sidebar-no-results {
        padding: 1.25rem 0.85rem;
        text-align: center;
        color: #64748B;
        font-size: 0.8rem;
    }
    .sidebar-nav ul .sidebar-item .sidebar-link.active,
    .sidebar-nav ul .sidebar-item.selected > .sidebar-link {
        background: #FFF8E5 !important;
        color: #171717 !important;
        font-weight: 600;
        border-left: 3px solid #FFC629;
        border-radius: 0 10px 10px 0;
    }
    .sidebar-nav ul .sidebar-item .sidebar-link {
        padding: 8px 14px;
        font-size: 0.84rem;
    }
    @media (prefers-reduced-motion: reduce) {
        .fg-sidebar-search-box {
            transition: none !important;
        }
    }
</style>
<div class="fg-sidebar-search-wrap">
    <div class="fg-sidebar-search-box">
        <span class="fg-sidebar-search-ico" aria-hidden="true">
            <i data-feather="search" style="width: 14px; height: 14px;"></i>
        </span>
        <input type="text"
               id="fgAdminMenuSearch"
               class="fg-sidebar-search-input"
               placeholder="Search admin menu..."
               aria-label="Search admin menu"
               autocomplete="off">
        <button type="button"
                id="fgAdminMenuSearchClear"
                class="fg-sidebar-search-clear d-none"
                aria-label="Clear menu search"
                title="Clear (Esc)">&times;</button>
    </div>
    <button type="button"
            id="fgAdminMenuSearchMiniBtn"
            class="fg-sidebar-search-mini-btn"
            aria-label="Expand sidebar and search menu"
            title="Search menu (Ctrl+K)">
        <i data-feather="search" style="width: 15px; height: 15px;"></i>
    </button>
</div>
<nav class="sidebar-nav">
    <ul id="sidebarnav">
        {{-- 1. DASHBOARD --}}
        @if ($adminMenuVisible('Dashboard'))
            <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'selected' : '' }}">
                <a href="{{ route('admin.dashboard') }}"
                    class="sidebar-link waves-effect waves-dark {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i data-feather="grid" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Dashboard</span>
                </a>
            </li>
        @endif

        {{-- 2. OPERATIONS --}}
        @php
            $showBookingsAll = auth('admin')->user()->can('view-bookings', 'admin') && $adminMenuVisible('Booking Management', 'All Bookings');
            $showBookingsToday = auth('admin')->user()->can('view-bookings', 'admin') && $adminMenuVisible('Booking Management', "Today's Collection");
            $showBookingsParent = $adminMenuVisible('Booking Management') && ($showBookingsAll || $showBookingsToday);
            $bookingsActive = request()->routeIs('admin.bookings.*') || request()->routeIs('admin.todays-collection.*');

            $showStoresChild = auth('admin')->user()->can('view-stores', 'admin') && $adminMenuVisible('Store Management', 'Stores');
            $showVendorsChild = auth('admin')->user()->can('manage-vendors', 'admin') && $adminMenuVisible('Store Management', 'Vendors');
            $showStoreParent = $adminMenuVisible('Store Management') && ($showStoresChild || $showVendorsChild);
            $storesActive = request()->routeIs('admin.stores.*');

            $hasDpPerm = auth('admin')->user()->can('view-delivery-persons', 'admin')
                || auth('admin')->user()->can('manage-delivery-assignments', 'admin')
                || auth('admin')->user()->can('manage-delivery-payouts', 'admin');
            $showDpAll = auth('admin')->user()->can('view-delivery-persons', 'admin') && $adminMenuVisible('Delivery Persons', 'All Delivery Persons');
            $showDpAdd = auth('admin')->user()->can('create-delivery-persons', 'admin') && $adminMenuVisible('Delivery Persons', 'Add Delivery Person');
            $showDpAssign = $adminMenuVisible('Delivery Persons', 'Delivery Assignments');
            $showDpActivity = $adminMenuVisible('Delivery Persons', 'Activity History');
            $showDpParent = $hasDpPerm && $adminMenuVisible('Delivery Persons') && ($showDpAll || $showDpAdd || $showDpAssign || $showDpActivity);
            $dpActive = request()->routeIs('admin.delivery-persons.*') || request()->routeIs('admin.delivery-assignments.*') || request()->routeIs('admin.delivery-activity.*');

            $canTagView = auth('admin')->user()->can('view-laundry-tags', 'admin') || auth('admin')->user()->can('manage-laundry-tags', 'admin');
            $canTagManage = auth('admin')->user()->can('manage-laundry-tags', 'admin');
            $showTagGen = $canTagManage && $adminMenuVisible('Tag Management', 'Generate & Print');
            $showTagInv = $canTagView && $adminMenuVisible('Tag Management', 'Tag Inventory');
            $showTagAlloc = $canTagManage && $adminMenuVisible('Tag Management', 'Assign to Delivery Person');
            $showTagHistory = $canTagView && $adminMenuVisible('Tag Management', 'Tag History');
            $showTagPrinter = $canTagManage && $adminMenuVisible('Tag Management', 'Printer Settings');
            $showTagParent = $adminMenuVisible('Tag Management') && ($showTagGen || $showTagInv || $showTagAlloc || $showTagHistory || $showTagPrinter);
            $tagActive = request()->routeIs('admin.tags.*');

            $showRefunds = auth('admin')->user()->can('view-refunds', 'admin') && $adminMenuVisible('Refund & Returns');
            $refundsActive = request()->routeIs('admin.refund-returns.*');

            $showUnitsMenu = auth('admin')->user()->can('view-units', 'admin') && $adminMenuVisible('Services', 'Unit');
            $showItemCatMenu = auth('admin')->user()->can('view-item-categories', 'admin') && $adminMenuVisible('Services', 'Item Category');
            $showItemsMenu = auth('admin')->user()->can('view-items', 'admin') && $adminMenuVisible('Services', 'Items');
            $showServicesChildMenu = auth('admin')->user()->can('view-services', 'admin') && $adminMenuVisible('Services', 'Services');
            $showServicesParent = $adminMenuVisible('Services') && ($showUnitsMenu || $showItemCatMenu || $showItemsMenu || $showServicesChildMenu);
            $servicesActive = request()->routeIs('admin.services.*');

            $showOperations = $showBookingsParent || $showStoreParent || $showDpParent || $showTagParent || $showRefunds || $showServicesParent;
            $operationsActive = $bookingsActive || $storesActive || $dpActive || $tagActive || $refundsActive || $servicesActive;
        @endphp
        @if ($showOperations)
            <li class="sidebar-item {{ $operationsActive ? 'selected' : '' }}">
                <a class="sidebar-link has-arrow waves-effect waves-dark {{ $operationsActive ? 'active' : '' }}"
                    href="javascript:void(0)" aria-expanded="{{ $operationsActive ? 'true' : 'false' }}">
                    <i data-feather="clipboard" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Operations</span>
                </a>
                <ul aria-expanded="{{ $operationsActive ? 'true' : 'false' }}"
                    class="collapse first-level {{ $operationsActive ? 'in' : '' }}"
                    data-group="{{ trim(($showBookingsParent ? 'Booking Management ' : '') . ($showStoreParent ? 'Store Management' : '')) }}">
                    @if ($showBookingsAll)
                        <li class="sidebar-item {{ request()->routeIs('admin.bookings.*') && !request()->boolean('today') ? 'active' : '' }}">
                            <a href="{{ route('admin.bookings.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.bookings.*') && !request()->boolean('today') ? 'active' : '' }}">
                                <i data-feather="calendar" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">All Bookings</span>
                            </a>
                        </li>
                    @endif
                    @if ($showBookingsToday)
                        <li class="sidebar-item {{ request()->routeIs('admin.bookings.index') && request()->boolean('today') ? 'active' : '' }}">
                            <a href="{{ route('admin.bookings.index', ['today' => 1]) }}"
                                class="sidebar-link {{ request()->routeIs('admin.bookings.index') && request()->boolean('today') ? 'active' : '' }}">
                                <i data-feather="clock" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Today's Collection</span>
                            </a>
                        </li>
                    @endif
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
                    @if ($showDpAll)
                        <li class="sidebar-item {{ request()->routeIs('admin.delivery-persons.index') || request()->routeIs('admin.delivery-persons.show') || request()->routeIs('admin.delivery-persons.edit') ? 'active' : '' }}">
                            <a href="{{ route('admin.delivery-persons.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.delivery-persons.index') ? 'active' : '' }}">
                                <i data-feather="truck" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Delivery Persons</span>
                            </a>
                        </li>
                        <li class="sidebar-item {{ request()->routeIs('admin.delivery-person-applications.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.delivery-person-applications.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.delivery-person-applications.*') ? 'active' : '' }}">
                                <i data-feather="file-text" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Delivery Person Applications</span>
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
                                <span class="hide-menu">Delivery Activity</span>
                            </a>
                        </li>
                    @endif
                    @if ($showTagInv)
                        <li class="sidebar-item {{ request()->routeIs('admin.tags.index') || request()->routeIs('admin.tags.show') ? 'active' : '' }}"
                            data-section="TAG MANAGEMENT">
                            <a href="{{ route('admin.tags.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.tags.index') || request()->routeIs('admin.tags.show') ? 'active' : '' }}">
                                <i data-feather="tag" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Tag Inventory</span>
                            </a>
                        </li>
                    @endif
                    @if ($showTagGen)
                        <li class="sidebar-item {{ request()->routeIs('admin.tags.generate*') ? 'active' : '' }}">
                            <a href="{{ route('admin.tags.generate') }}"
                                class="sidebar-link {{ request()->routeIs('admin.tags.generate*') ? 'active' : '' }}">
                                <i data-feather="plus-square" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Generate &amp; Print Tags</span>
                            </a>
                        </li>
                    @endif
                    @if ($showTagAlloc)
                        <li class="sidebar-item {{ request()->routeIs('admin.tags.allocations*') ? 'active' : '' }}">
                            <a href="{{ route('admin.tags.allocations.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.tags.allocations*') ? 'active' : '' }}">
                                <i data-feather="user-check" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Assign to Delivery Person</span>
                            </a>
                        </li>
                    @endif
                    @if ($showTagHistory)
                        <li class="sidebar-item {{ request()->routeIs('admin.tags.history*') ? 'active' : '' }}">
                            <a href="{{ route('admin.tags.history') }}"
                                class="sidebar-link {{ request()->routeIs('admin.tags.history*') ? 'active' : '' }}">
                                <i data-feather="list" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Tag History</span>
                            </a>
                        </li>
                    @endif
                    @if ($showTagPrinter)
                        <li class="sidebar-item {{ request()->routeIs('admin.tags.printer-settings*') ? 'active' : '' }}">
                            <a href="{{ route('admin.tags.printer-settings.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.tags.printer-settings*') ? 'active' : '' }}">
                                <i data-feather="printer" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Printer Settings</span>
                            </a>
                        </li>
                    @endif
                    @if ($showRefunds)
                        <li class="sidebar-item {{ $refundsActive ? 'active' : '' }}">
                            <a href="{{ route('admin.refund-returns.cases.index') }}"
                                class="sidebar-link {{ $refundsActive ? 'active' : '' }}">
                                <i data-feather="rotate-ccw" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Refund &amp; Returns</span>
                            </a>
                        </li>
                    @endif
                    @if ($showServicesChildMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.services.services.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.services.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.services.*') ? 'active' : '' }}">
                                <i data-feather="layers" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Services</span>
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
                    @if ($showItemCatMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.services.categories.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.categories.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.categories.*') ? 'active' : '' }}">
                                <i data-feather="folder" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Item Categories</span>
                            </a>
                        </li>
                    @endif
                    @if ($showUnitsMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.services.units.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.services.units.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.services.units.*') ? 'active' : '' }}">
                                <i data-feather="sliders" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Units</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        {{-- 3. FINANCE --}}
        @php
            $showCurrentAccount = auth('admin')->user()->can('view-current-account', 'admin') && $adminMenuVisible('Current Account');
            $showDeliverySettlements = auth('admin')->user()->can('view-delivery-settlements', 'admin') && $adminMenuVisible('Delivery Settlements');
            $showStoreWithdrawalsChild = (auth('admin')->user()->can('view-store-accounts', 'admin') || auth('admin')->user()->can('view-stores', 'admin')) && $adminMenuVisible('Store Management', 'Store Withdrawals');
            $showDpPayouts = $hasDpPerm && $adminMenuVisible('Delivery Persons', 'Account Balances & Payouts');
            $showDpWithdrawals = $hasDpPerm && $adminMenuVisible('Delivery Persons', 'Withdrawal Requests');
            $showCurrencySettings = auth('admin')->user()->can('manage-settings', 'admin') && $adminMenuVisible('Country & Currency');

            $showFinance = $showCurrentAccount || $showDeliverySettlements || $showStoreWithdrawalsChild || $showDpPayouts || $showDpWithdrawals || $showCurrencySettings;
            $financeActive = request()->routeIs('admin.current-account.*')
                || request()->routeIs('admin.delivery-person-settlements.*')
                || request()->routeIs('admin.store-withdrawals.*')
                || request()->routeIs('admin.delivery-payouts.*')
                || request()->routeIs('admin.delivery-person-withdrawals.*')
                || request()->routeIs('admin.currency-settings.*');
        @endphp
        @if ($showFinance)
            <li class="sidebar-item {{ $financeActive ? 'selected' : '' }}">
                <a class="sidebar-link has-arrow waves-effect waves-dark {{ $financeActive ? 'active' : '' }}"
                    href="javascript:void(0)" aria-expanded="{{ $financeActive ? 'true' : 'false' }}">
                    <i data-feather="credit-card" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Finance</span>
                </a>
                <ul aria-expanded="{{ $financeActive ? 'true' : 'false' }}"
                    class="collapse first-level {{ $financeActive ? 'in' : '' }}">
                    @if ($showCurrentAccount)
                        <li class="sidebar-item {{ request()->routeIs('admin.current-account.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.current-account.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.current-account.*') ? 'active' : '' }}">
                                <i data-feather="briefcase" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Current Account</span>
                            </a>
                        </li>
                    @endif
                    @if ($showDeliverySettlements)
                        <li class="sidebar-item {{ request()->routeIs('admin.delivery-person-settlements.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.delivery-person-settlements.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.delivery-person-settlements.*') ? 'active' : '' }}">
                                <i data-feather="dollar-sign" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Delivery Settlements</span>
                            </a>
                        </li>
                    @endif
                    @if ($showStoreWithdrawalsChild)
                        <li class="sidebar-item {{ request()->routeIs('admin.store-withdrawals.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.store-withdrawals.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.store-withdrawals.*') ? 'active' : '' }}">
                                <i data-feather="arrow-up-right" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Store Withdrawals</span>
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
                                <i data-feather="inbox" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Withdrawal Requests</span>
                            </a>
                        </li>
                    @endif
                    @if ($showCurrencySettings)
                        <li class="sidebar-item {{ request()->routeIs('admin.currency-settings.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.currency-settings.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.currency-settings.*') ? 'active' : '' }}">
                                <i data-feather="globe" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Country &amp; Currency</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        {{-- 4. REPORTS & ANALYTICS --}}
        @php
            $canReportsView = auth('admin')->user()->can('view-reports', 'admin')
                || auth('admin')->user()->can('view-bookings', 'admin')
                || auth('admin')->user()->can('view-current-account', 'admin');
            $showReportsParent = $canReportsView && $adminMenuVisible('Reports & Analytics');
            $reportsRouteActive = request()->routeIs('admin.reports.*');
        @endphp
        @if ($showReportsParent)
            <li class="sidebar-item {{ $reportsRouteActive ? 'selected' : '' }}">
                <a class="sidebar-link has-arrow waves-effect waves-dark {{ $reportsRouteActive ? 'active' : '' }}"
                    href="javascript:void(0)" aria-expanded="{{ $reportsRouteActive ? 'true' : 'false' }}">
                    <i data-feather="bar-chart-2" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Reports &amp; Analytics</span>
                </a>
                <ul aria-expanded="{{ $reportsRouteActive ? 'true' : 'false' }}"
                    class="collapse first-level {{ $reportsRouteActive ? 'in' : '' }}">
                    <li class="sidebar-item {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">
                        <a href="{{ route('admin.reports.index') }}"
                            class="sidebar-link {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">
                            <i data-feather="trending-up" class="sidebar-icon" aria-hidden="true"></i>
                            <span class="hide-menu">Overview &amp; Comparisons</span>
                        </a>
                    </li>
                    <li class="sidebar-item {{ request()->routeIs('admin.reports.transactions*') ? 'active' : '' }}">
                        <a href="{{ route('admin.reports.transactions') }}"
                            class="sidebar-link {{ request()->routeIs('admin.reports.transactions*') ? 'active' : '' }}">
                            <i data-feather="dollar-sign" class="sidebar-icon" aria-hidden="true"></i>
                            <span class="hide-menu">Transactions &amp; Finance</span>
                        </a>
                    </li>
                    <li class="sidebar-item {{ request()->routeIs('admin.reports.stores*') ? 'active' : '' }}">
                        <a href="{{ route('admin.reports.stores') }}"
                            class="sidebar-link {{ request()->routeIs('admin.reports.stores*') ? 'active' : '' }}">
                            <i data-feather="shopping-bag" class="sidebar-icon" aria-hidden="true"></i>
                            <span class="hide-menu">Store Performance</span>
                        </a>
                    </li>
                    <li class="sidebar-item {{ request()->routeIs('admin.reports.delivery-persons*') ? 'active' : '' }}">
                        <a href="{{ route('admin.reports.delivery-persons') }}"
                            class="sidebar-link {{ request()->routeIs('admin.reports.delivery-persons*') ? 'active' : '' }}">
                            <i data-feather="truck" class="sidebar-icon" aria-hidden="true"></i>
                            <span class="hide-menu">Delivery Person Performance</span>
                        </a>
                    </li>
                    <li class="sidebar-item {{ request()->routeIs('admin.reports.problems*') ? 'active' : '' }}">
                        <a href="{{ route('admin.reports.problems') }}"
                            class="sidebar-link {{ request()->routeIs('admin.reports.problems*') ? 'active' : '' }}">
                            <i data-feather="alert-triangle" class="sidebar-icon" aria-hidden="true"></i>
                            <span class="hide-menu">Problem &amp; Issue Tracker</span>
                        </a>
                    </li>
                </ul>
            </li>
        @endif

        {{-- 5. WEBSITE MANAGER --}}
        @php
            $showHomepageContentsMenu = auth('admin')->user()->can('manage-settings', 'admin') && $adminMenuVisible('Website Manager', 'Homepage Contents');
            $showServicesContentsMenu = auth('admin')->user()->can('manage-settings', 'admin') && $adminMenuVisible('Website Manager', 'Services Page Contents');
            $showAboutContentsMenu = auth('admin')->user()->can('manage-settings', 'admin') && $adminMenuVisible('Website Manager', 'About Page Contents');
            $showContactContentsMenu = auth('admin')->user()->can('manage-settings', 'admin') && $adminMenuVisible('Website Manager', 'Contact Page Contents');
            $showEnquiriesMenu = auth('admin')->user()->can('manage-settings', 'admin') && $adminMenuVisible('Website Manager', 'Contact Enquiries');
            $showPreloaderSettingsMenu = auth('admin')->user()->can('manage-settings', 'admin') && $adminMenuVisible('Website Manager', 'Preloader Settings');
            $showCustomerLoginOtpMenu = auth('admin')->user()->can('manage-settings', 'admin') && $adminMenuVisible('Website Manager', 'Customer Login OTP Settings');
            $showDpJoinPageMenu = auth('admin')->user()->can('manage-settings', 'admin') && $adminMenuVisible('Website Manager', 'Delivery Person Join Page');
            $showWebsiteManagerParent = $adminMenuVisible('Website Manager') && ($showHomepageContentsMenu || $showServicesContentsMenu || $showAboutContentsMenu || $showContactContentsMenu || $showEnquiriesMenu || $showPreloaderSettingsMenu || $showCustomerLoginOtpMenu || $showDpJoinPageMenu);
            $wmRouteActive = request()->routeIs('admin.website-manager.*');
        @endphp
        @if ($showWebsiteManagerParent)
            <li class="sidebar-item {{ $wmRouteActive ? 'selected' : '' }}">
                <a class="sidebar-link has-arrow waves-effect waves-dark {{ $wmRouteActive ? 'active' : '' }}"
                    href="javascript:void(0)" aria-expanded="{{ $wmRouteActive ? 'true' : 'false' }}">
                    <i data-feather="monitor" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Website Manager</span>
                </a>
                <ul aria-expanded="{{ $wmRouteActive ? 'true' : 'false' }}"
                    class="collapse first-level {{ $wmRouteActive ? 'in' : '' }}">
                    @if ($showHomepageContentsMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.website-manager.homepage.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.website-manager.homepage.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.website-manager.homepage.*') ? 'active' : '' }}">
                                <i data-feather="layout" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Homepage Contents</span>
                            </a>
                        </li>
                    @endif
                    @if ($showServicesContentsMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.website-manager.services.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.website-manager.services.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.website-manager.services.*') ? 'active' : '' }}">
                                <i data-feather="layers" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Services Page Contents</span>
                            </a>
                        </li>
                    @endif
                    @if ($showAboutContentsMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.website-manager.about.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.website-manager.about.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.website-manager.about.*') ? 'active' : '' }}">
                                <i data-feather="info" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">About Page Contents</span>
                            </a>
                        </li>
                    @endif
                    @if ($showContactContentsMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.website-manager.contact.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.website-manager.contact.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.website-manager.contact.*') ? 'active' : '' }}">
                                <i data-feather="phone" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Contact Page Contents</span>
                            </a>
                        </li>
                    @endif
                    @if ($showEnquiriesMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.website-manager.enquiries.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.website-manager.enquiries.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.website-manager.enquiries.*') ? 'active' : '' }}">
                                <i data-feather="mail" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Contact Enquiries</span>
                            </a>
                        </li>
                    @endif
                    @if ($showPreloaderSettingsMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.website-manager.preloader.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.website-manager.preloader.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.website-manager.preloader.*') ? 'active' : '' }}">
                                <i data-feather="loader" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Preloader Settings</span>
                            </a>
                        </li>
                    @endif
                    @if ($showCustomerLoginOtpMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.website-manager.customer-login-otp.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.website-manager.customer-login-otp.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.website-manager.customer-login-otp.*') ? 'active' : '' }}">
                                <i data-feather="shield" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Customer Login OTP Settings</span>
                            </a>
                        </li>
                    @endif
                    @if ($showDpJoinPageMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.website-manager.delivery-person-join.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.website-manager.delivery-person-join.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.website-manager.delivery-person-join.*') ? 'active' : '' }}">
                                <i data-feather="truck" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Delivery Person Join Page</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        {{-- 6. SETTINGS --}}
        {{-- 6. SETTINGS --}}
        @php
            $showRolesMenu = auth('admin')->user()->can('view-roles', 'admin') && $adminMenuVisible('Roles & Permissions', 'Roles');
            $showAdminsMenu = auth('admin')->user()->can('view-admins', 'admin') && $adminMenuVisible('Roles & Permissions', 'Admins');
            $showUsersMenu = auth('admin')->user()->can('view-users', 'admin') && $adminMenuVisible('Roles & Permissions', 'Users');
            $showRolesParent = $adminMenuVisible('Roles & Permissions') && ($showRolesMenu || $showAdminsMenu || $showUsersMenu);
            $rolesActive = request()->routeIs('admin.roles.*') || request()->routeIs('admin.admins.*') || request()->routeIs('admin.users.*');

            $showBranding = auth('admin')->user()->can('manage-settings', 'admin') && $adminMenuVisible('Branding & Logo');
            $showDbBackup = auth('admin')->user()->can('view-database-backups', 'admin') && $adminMenuVisible('Database Backup & Restore');
            $showDbCleanup = auth('admin')->user()->can('manage-database-cleanup', 'admin') && $adminMenuVisible('Database Cleanup');

            $showSettings = $showRolesParent || $showBranding || $showDbBackup || $showDbCleanup;
            $settingsActive = $rolesActive
                || request()->routeIs('admin.settings.*')
                || request()->routeIs('admin.database-backups.*')
                || request()->routeIs('admin.database-cleanup.*');
        @endphp
        @if ($showSettings)
            <li class="sidebar-item {{ $settingsActive ? 'selected' : '' }}">
                <a class="sidebar-link has-arrow waves-effect waves-dark {{ $settingsActive ? 'active' : '' }}"
                    href="javascript:void(0)" aria-expanded="{{ $settingsActive ? 'true' : 'false' }}">
                    <i data-feather="settings" class="sidebar-icon" aria-hidden="true"></i>
                    <span class="hide-menu">Settings</span>
                </a>
                <ul aria-expanded="{{ $settingsActive ? 'true' : 'false' }}"
                    class="collapse first-level {{ $settingsActive ? 'in' : '' }}">
                    @if ($showRolesMenu)
                        <li class="sidebar-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.roles.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                <i data-feather="shield" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Roles &amp; Permissions</span>
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
                    @if ($showBranding)
                        <li class="sidebar-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.settings.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                                <i data-feather="droplet" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Branding &amp; Logo</span>
                            </a>
                        </li>
                    @endif
                    @if ($showDbBackup)
                        <li class="sidebar-item {{ request()->routeIs('admin.database-backups.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.database-backups.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.database-backups.*') ? 'active' : '' }}">
                                <i data-feather="database" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Database Backup &amp; Restore</span>
                            </a>
                        </li>
                    @endif
                    @if ($showDbCleanup)
                        <li class="sidebar-item {{ request()->routeIs('admin.database-cleanup.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.database-cleanup.index') }}"
                                class="sidebar-link {{ request()->routeIs('admin.database-cleanup.*') ? 'active' : '' }}">
                                <i data-feather="trash-2" class="sidebar-icon" aria-hidden="true"></i>
                                <span class="hide-menu">Database Cleanup</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif
    </ul>
<div id="fgAdminMenuNoResults" class="fg-sidebar-no-results d-none">
        <i data-feather="search" style="width: 18px; height: 18px;" class="mb-1 d-block mx-auto"></i>
        No matching menus found.
    </div>
</nav>
<script>
(function () {
    const input = document.getElementById('fgAdminMenuSearch');
    const clearBtn = document.getElementById('fgAdminMenuSearchClear');
    const miniBtn = document.getElementById('fgAdminMenuSearchMiniBtn');
    const navList = document.getElementById('sidebarnav');
    const noResults = document.getElementById('fgAdminMenuNoResults');
    if (!input || !navList || input.dataset.bound === '1') return;
    input.dataset.bound = '1';

    let savedState = false;

    function saveOriginalCollapseState() {
        if (savedState) return;
        navList.querySelectorAll(':scope > li.sidebar-item').forEach(li => {
            const sub = li.querySelector(':scope > ul.collapse');
            const toggle = li.querySelector(':scope > a.has-arrow');
            if (sub) {
                sub.dataset.fgOrigIn = sub.classList.contains('in') ? '1' : '0';
                sub.dataset.fgOrigShow = sub.classList.contains('show') ? '1' : '0';
            }
            if (toggle) {
                toggle.dataset.fgOrigExpanded = toggle.getAttribute('aria-expanded') || 'false';
            }
        });
        savedState = true;
    }

    function restoreOriginalCollapseState() {
        navList.querySelectorAll(':scope > li.sidebar-item').forEach(li => {
            li.style.display = '';
            li.querySelectorAll('li.sidebar-item').forEach(child => {
                child.style.display = '';
            });
            const sub = li.querySelector(':scope > ul.collapse');
            const toggle = li.querySelector(':scope > a.has-arrow');
            if (sub && savedState) {
                sub.classList.toggle('in', sub.dataset.fgOrigIn === '1');
                sub.classList.toggle('show', sub.dataset.fgOrigShow === '1');
            }
            if (toggle && savedState) {
                toggle.setAttribute('aria-expanded', toggle.dataset.fgOrigExpanded || 'false');
            }
        });
        savedState = false;
        noResults?.classList.add('d-none');
    }

    function filterMenus() {
        const query = input.value.trim().toLowerCase().replace(/\s+/g, ' ');
        clearBtn?.classList.toggle('d-none', query.length === 0);

        if (!query) {
            restoreOriginalCollapseState();
            return;
        }

        saveOriginalCollapseState();
        let visibleParents = 0;

        navList.querySelectorAll(':scope > li.sidebar-item').forEach(li => {
            const parentLabel = (li.querySelector(':scope > a .hide-menu')?.textContent || '').trim().toLowerCase().replace(/\s+/g, ' ');
            const sub = li.querySelector(':scope > ul.collapse');
            const toggle = li.querySelector(':scope > a.has-arrow');

            if (!sub) {
                const match = parentLabel.includes(query);
                li.style.display = match ? '' : 'none';
                if (match) visibleParents++;
                return;
            }

            const parentMatches = parentLabel.includes(query);
            let childMatchCount = 0;

            sub.querySelectorAll(':scope > li.sidebar-item').forEach(child => {
                const childLabel = (child.querySelector('.hide-menu')?.textContent || '').trim().toLowerCase().replace(/\s+/g, ' ');
                const showChild = parentMatches || childLabel.includes(query);
                child.style.display = showChild ? '' : 'none';
                if (showChild) childMatchCount++;
            });

            const showParent = parentMatches || childMatchCount > 0;
            li.style.display = showParent ? '' : 'none';
            if (showParent) {
                visibleParents++;
                sub.classList.add('in', 'show');
                toggle?.setAttribute('aria-expanded', 'true');
            }
        });

        noResults?.classList.toggle('d-none', visibleParents > 0);
    }

    input.addEventListener('input', filterMenus);

    clearBtn?.addEventListener('click', () => {
        input.value = '';
        filterMenus();
        input.focus();
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            input.value = '';
            filterMenus();
            input.blur();
        }
    });

    miniBtn?.addEventListener('click', () => {
        document.querySelector('.sidebartoggler')?.click();
        setTimeout(() => input.focus(), 120);
    });

    document.addEventListener('keydown', (e) => {
        const tag = (document.activeElement?.tagName || '').toUpperCase();
        const isTyping = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || document.activeElement?.isContentEditable;
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (document.getElementById('main-wrapper')?.classList.contains('mini-sidebar')) {
                document.querySelector('.sidebartoggler')?.click();
            }
            setTimeout(() => { input.focus(); input.select(); }, 80);
        } else if (!isTyping && e.key === '/') {
            e.preventDefault();
            input.focus();
        }
    });
})();
</script>
