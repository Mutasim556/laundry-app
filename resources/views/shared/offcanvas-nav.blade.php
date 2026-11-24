<!-- Notifications Offcanvas -->
<div class="offcanvas offcanvas-end custom-offcanvas" tabindex="-1" id="notificationOffcanvas" aria-labelledby="notificationOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="notificationOffcanvasLabel">
            <span class="p-2 rounded-circle me-2" style="background: var(--soft-cream); color: #B58000;">🔔</span> Notifications
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="p-3 mb-2 rounded-4 bg-light border">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="badge bg-success">Status Update</span>
                <span class="small text-muted">2 hours ago</span>
            </div>
            <h6 class="fw-bold mb-1 text-dark">Express Pickup Active</h6>
            <p class="small text-secondary m-0">Our drivers are currently in your neighbourhood. Same-day laundry booking is open!</p>
        </div>
    </div>
</div>

<!-- Mobile Menu Offcanvas -->
<div class="offcanvas offcanvas-end custom-offcanvas" tabindex="-1" id="mobileMenuOffcanvas" aria-labelledby="mobileMenuOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ asset('images/freshgo-new-logo.jpeg') }}" alt="Freshgo Logo" style="height: 36px;" class="rounded-2">
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        @auth('web')
            @php $customer = auth('web')->user(); @endphp
            <div class="d-flex align-items-center gap-3 p-3 rounded-4 mb-4" style="background: var(--soft-cream);">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-dark flex-shrink-0"
                     style="width: 48px; height: 48px; background: #FFFFFF; border: 2px solid #FFC629;">
                    {{ $customer->initials }}
                </div>
                <div class="min-w-0">
                    <h6 class="fw-bold text-dark m-0 text-truncate">{{ $customer->name }}</h6>
                    <small class="text-muted d-block text-truncate">{{ $customer->email }}</small>
                </div>
            </div>
        @else
            <div class="p-3 rounded-4 mb-4 text-center" style="background: var(--soft-cream); border: 1px solid #FFC629;">
                <p class="small fw-semibold text-dark mb-2">Sign in with your email to manage orders, addresses, and cards.</p>
                <a href="{{ route('login') }}" class="btn btn-warning w-100 rounded-pill fw-bold text-dark">Login / Sign Up</a>
            </div>
        @endauth

        <ul class="list-unstyled d-flex flex-column gap-2">
            <li>
                <a href="{{ url('/') }}"
                   class="d-flex align-items-center justify-content-between p-3 rounded-3 text-decoration-none fw-semibold {{ request()->is('/') ? 'text-white' : 'text-dark bg-light' }}"
                   @if(request()->is('/')) style="background-color: #171717;" @endif>
                    <span>Home</span><span>&rarr;</span>
                </a>
            </li>
            <li>
                <a href="{{ route('services.index') }}"
                   class="d-flex align-items-center justify-content-between p-3 rounded-3 text-decoration-none fw-semibold {{ request()->routeIs('services.*') ? 'text-white' : 'text-dark bg-light' }}"
                   @if(request()->routeIs('services.*')) style="background-color: #171717;" @endif>
                    <span>Services</span><span>&rarr;</span>
                </a>
            </li>
            <li>
                <a href="{{ route('orders.index') }}"
                   class="d-flex align-items-center justify-content-between p-3 rounded-3 text-decoration-none fw-semibold {{ request()->routeIs('orders.*') ? 'text-white' : 'text-dark bg-light' }}"
                   @if(request()->routeIs('orders.*')) style="background-color: #171717;" @endif>
                    <span>Orders</span><span>&rarr;</span>
                </a>
            </li>
            <li>
                <a href="{{ url('/#about') }}"
                   class="d-flex align-items-center justify-content-between p-3 rounded-3 text-decoration-none text-dark bg-light fw-semibold">
                    <span>About</span><span>&rarr;</span>
                </a>
            </li>
            <li>
                <a href="{{ url('/#contact') }}"
                   class="d-flex align-items-center justify-content-between p-3 rounded-3 text-decoration-none text-dark bg-light fw-semibold">
                    <span>Contact</span><span>&rarr;</span>
                </a>
            </li>
            @auth('web')
                <li class="pt-2 border-top mt-1">
                    <a href="{{ route('account.profile') }}"
                       class="d-flex align-items-center justify-content-between p-3 rounded-3 text-decoration-none fw-semibold {{ request()->routeIs('account.profile') ? 'text-white' : 'text-dark bg-light' }}"
                       @if(request()->routeIs('account.profile')) style="background-color: #171717;" @endif>
                        <span>My Profile</span><span>&rarr;</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('account.addresses') }}"
                       class="d-flex align-items-center justify-content-between p-3 rounded-3 text-decoration-none fw-semibold {{ request()->routeIs('account.addresses*') ? 'text-white' : 'text-dark bg-light' }}"
                       @if(request()->routeIs('account.addresses*')) style="background-color: #171717;" @endif>
                        <span>Address Book</span><span>&rarr;</span>
                    </a>
                </li>
                {{-- <li>
                    <a href="{{ route('account.payment-methods') }}"
                       class="d-flex align-items-center justify-content-between p-3 rounded-3 text-decoration-none fw-semibold {{ request()->routeIs('account.payment-methods*') ? 'text-white' : 'text-dark bg-light' }}"
                       @if(request()->routeIs('account.payment-methods*')) style="background-color: #171717;" @endif>
                        <span>Saved Payment Methods</span><span>&rarr;</span>
                    </a>
                </li> --}}
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-100 d-flex align-items-center justify-content-between p-3 rounded-3 border-0 text-danger bg-light fw-semibold">
                            <span>Logout</span><span>&rarr;</span>
                        </button>
                    </form>
                </li>
            @endauth
        </ul>
    </div>
</div>
