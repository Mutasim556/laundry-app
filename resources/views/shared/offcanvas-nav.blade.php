<!-- Notifications Offcanvas -->
<div class="offcanvas offcanvas-end custom-offcanvas" tabindex="-1" id="notificationOffcanvas" aria-labelledby="notificationOffcanvasLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="notificationOffcanvasLabel">
            <span class="p-2 rounded-circle me-2" style="background: var(--soft-cream); color: #B58000;">🔔</span> Notifications
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="p-3 mb-3 rounded-4" style="background-color: var(--soft-cream); border: 1px solid rgba(255, 198, 41, 0.4);">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="badge bg-warning text-dark fw-bold">20% Discount</span>
                <span class="small text-muted">Just now</span>
            </div>
            <h6 class="fw-bold mb-1 text-dark">Welcome Emma!</h6>
            <p class="small text-secondary m-0">Use promo code <strong>FRESH20</strong> to receive 20% off your first laundry or dry cleaning collection.</p>
        </div>

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
        <div class="d-flex align-items-center gap-3 p-3 rounded-4 mb-4" style="background: var(--soft-cream);">
            <img src="{{ asset('admin/assets/images/users/3.jpg') }}" alt="Emma Carter" class="rounded-circle" width="48" height="48">
            <div>
                <h6 class="fw-bold text-dark m-0">Emma Carter</h6>
                <small class="text-muted">emma.carter@example.com</small>
            </div>
        </div>

        <ul class="list-unstyled d-flex flex-column gap-2">
            <li>
                <a href="{{ url('/') }}" class="d-flex align-items-center justify-content-between p-3 rounded-4 text-decoration-none text-dark bg-light fw-semibold">
                    <span>Home</span>
                    <span>&rarr;</span>
                </a>
            </li>
            <li>
                <a href="{{ route('services.index') }}" class="d-flex align-items-center justify-content-between p-3 rounded-4 text-decoration-none text-dark bg-light fw-semibold">
                    <span>All Services &amp; Prices</span>
                    <span>&rarr;</span>
                </a>
            </li>
            <li>
                <a href="{{ route('services.index', ['category' => 'wash-fold']) }}" class="d-flex align-items-center justify-content-between p-3 rounded-4 text-decoration-none text-dark bg-light fw-semibold">
                    <span>Wash &amp; Fold</span>
                    <span>&rarr;</span>
                </a>
            </li>
            <li>
                <a href="{{ route('services.index', ['category' => 'dry-cleaning']) }}" class="d-flex align-items-center justify-content-between p-3 rounded-4 text-decoration-none text-dark bg-light fw-semibold">
                    <span>Dry Cleaning</span>
                    <span>&rarr;</span>
                </a>
            </li>
            <li>
                <a href="{{ route('services.index', ['category' => 'ironing-only']) }}" class="d-flex align-items-center justify-content-between p-3 rounded-4 text-decoration-none text-dark bg-light fw-semibold">
                    <span>Ironing</span>
                    <span>&rarr;</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.login') }}" class="d-flex align-items-center justify-content-between p-3 rounded-4 text-decoration-none text-dark bg-light fw-semibold">
                    <span>Admin Login</span>
                    <span>&rarr;</span>
                </a>
            </li>
        </ul>

        <div class="mt-4 pt-4 border-top text-center">
            <a href="{{ route('booking.services') }}" class="btn btn-warning w-100 rounded-pill py-3 fw-bold text-dark shadow-sm">
                Book Service Now
            </a>
        </div>
    </div>
</div>
