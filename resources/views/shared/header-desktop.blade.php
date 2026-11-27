<!-- Desktop Header & Navbar -->
<header class="desktop-navbar sticky-top">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <a href="{{ url('/') }}" class="d-flex align-items-center text-decoration-none gap-2">
                <img src="{{ asset('images/freshgo-new-logo.jpeg') }}" alt="Freshgo Logo" class="desktop-brand-logo rounded-2">
            </a>

            <!-- Nav Links -->
            <ul class="nav nav-pills align-items-center gap-1 gap-xl-2 m-0 p-0 desktop-nav-menu">
                <li class="nav-item"><a href="{{ url('/') }}" class="desktop-nav-link {{ request()->is('/') ? 'active' : '' }}">Home</a></li>
                <li class="nav-item"><a href="{{ route('services.index') }}" class="desktop-nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}">Services</a></li>
                <li class="nav-item"><a href="{{ route('orders.index') }}" class="desktop-nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">Orders</a></li>
                {{-- <li class="nav-item"><a href="{{ route('services.index') }}" class="desktop-nav-link">Offers</a></li> --}}
                <li class="nav-item"><a href="{{ route('about') }}" class="desktop-nav-link {{ request()->routeIs('about') ? 'active' : '' }}">About Us</a></li>
                <li class="nav-item"><a href="{{ route('contact') }}" class="desktop-nav-link {{ request()->routeIs('contact') ? 'active' : '' }}">Contact Us</a></li>
            </ul>

            <!-- User and Actions -->
            <div class="d-flex align-items-center gap-2 gap-xl-3">
                <button type="button" class="icon-btn-circle js-notification-bell" data-bs-toggle="offcanvas" data-bs-target="#notificationOffcanvas" aria-label="Notifications">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="notify-indicator"></span>
                </button>

                @auth('web')
                    @php $customer = auth('web')->user(); @endphp
                    <div class="dropdown">
                        <button type="button"
                                class="btn d-flex align-items-center gap-2 border rounded-pill px-2 px-xl-3 py-1 bg-white shadow-sm user-pill-desktop dropdown-toggle"
                                id="desktopProfileDropdownBtn"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-dark"
                                  style="width: 32px; height: 32px; background: #FFF8E5; border: 1.5px solid #FFC629; font-size: 0.78rem;">
                                {{ $customer->initials }}
                            </span>
                            <span class="fw-bold small text-dark">{{ $customer->name }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-4 p-2 mt-2 customer-profile-dropdown"
                            aria-labelledby="desktopProfileDropdownBtn"
                            style="min-width: 225px;">
                            <li>
                                <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2 {{ request()->routeIs('account.profile') ? 'active' : '' }}"
                                   href="{{ route('account.profile') }}">
                                    <span>👤</span><span>Profile</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2 {{ request()->routeIs('orders.*') ? 'active' : '' }}"
                                   href="{{ route('orders.index') }}">
                                    <span>📦</span><span>My Orders</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2 {{ request()->routeIs('account.addresses*') ? 'active' : '' }}"
                                   href="{{ route('account.addresses') }}">
                                    <span>📍</span><span>Address Book</span>
                                </a>
                            </li>
                            {{-- <li>
                                <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2 {{ request()->routeIs('account.payment-methods*') ? 'active' : '' }}"
                                   href="{{ route('account.payment-methods') }}">
                                    <span>💳</span><span>Saved Payment Methods</span>
                                </a>
                            </li> --}}
                            <li><hr class="dropdown-divider my-2"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2 text-danger fw-semibold">
                                        <span>🚪</span><span>Logout</span>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}"
                       class="d-flex align-items-center gap-2 border rounded-pill px-3 py-1 bg-white shadow-sm user-pill-desktop text-decoration-none">
                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center"
                              style="width: 30px; height: 30px; background: #FFF8E5; border: 1.5px solid #FFC629;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#171717" stroke-width="2.2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        <span class="fw-bold small text-dark">Login / Sign Up</span>
                    </a>
                @endauth

                <a href="{{ route('booking.services', ['new' => 1]) }}" class="btn btn-warning rounded-pill px-3 px-xl-4 py-2 fw-bold text-dark shadow-sm btn-book-desktop">
                    Book Service
                </a>
            </div>
        </div>
    </div>
</header>
