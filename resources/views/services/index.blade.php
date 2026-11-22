<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="Explore Freshgo laundry services and compare prices in BDT.">
    <title>{{ $category?->name ?? 'Other services' }} | Freshgo Laundry</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">

    <!-- Google Fonts: Poppins (Unified Typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">

    <!-- Shared Laundry Home Theme CSS (For exact Header, Navbar, Offcanvas, and Bottom Nav styles) -->
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">

    <!-- Services Redesign Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/services.css') }}">
    <script src="{{ asset('js/services.js') }}" defer></script>
</head>
<body class="services-page-body">
    <a class="skip-link" href="#catalog">Skip to services</a>

    <!-- Mobile Header (< 992px) - EXACT SAME AS HOMEPAGE -->
    <div class="d-lg-none px-3 pt-3 bg-white border-bottom">
        @include('shared.header-mobile')
    </div>

    <!-- Desktop Navbar (>= 992px) - EXACT SAME AS HOMEPAGE -->
    <div class="d-none d-lg-block">
        @include('shared.header-desktop')
    </div>

    <!-- Main Catalog: Full-Width Search & Categories Slider + Two-Column Content -->
    <main class="container catalog-section-wrapper" id="catalog">
        <!-- TOP SECTION: Search & Sort Filter Bar (Full Width) -->
        <form class="service-filter-form-bar" id="service-filters" action="{{ route('services.index') }}" method="GET" role="search">
            <input type="hidden" name="category" value="{{ $category?->slug ?? 'other-services' }}">
            <input type="hidden" name="item" value="{{ $selectedItem }}">

            <div class="search-field-group">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9E9E9E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <label class="visually-hidden" for="service-search">Search in this category</label>
                <input type="search" class="search-field-input" id="service-search" name="q" placeholder="Search services or clothing items..." value="{{ $search }}" maxlength="100" autocomplete="off">
                <kbd class="search-kbd-hint" aria-hidden="true">&crarr;</kbd>
            </div>

            <div class="sort-field-group">
                <label for="service-sort" class="sort-field-label">Sort by:</label>
                <select id="service-sort" name="sort" class="sort-field-select">
                    <option value="name" @selected($sort === 'name')>Name: A–Z</option>
                    <option value="price-low" @selected($sort === 'price-low')>Price: low to high</option>
                    <option value="price-high" @selected($sort === 'price-high')>Price: high to low</option>
                </select>
            </div>
            <button class="visually-hidden" type="submit">Apply filters</button>
        </form>

        @php
            $categoryIcons = [
                'wash-fold' => '01_washing_machine.png',
                'wash-iron' => '12_clean_shirt.png',
                'dry-cleaning' => '06_shirt_hanger.png',
                'ironing-only' => '05_iron.png',
                'back-wash-iron' => '08_wash_tub.png',
                'bulky-item-duvet' => '07_folded_laundry.png',
                'repairs' => '13_repairs.svg'
            ];
        @endphp

        <!-- HORIZONTAL CATEGORY SLIDER SECTION (Full Width, directly below search) -->
        <section class="category-slider-wrapper mb-3" aria-label="Our Categories">
            <div class="category-slider-header">
                <div>
                    <h3 class="category-slider-heading">Our Categories</h3>
                </div>
                <div class="category-slider-nav-controls">
                    <button type="button" class="category-slider-arrow" id="catSliderPrev" aria-label="Previous categories" disabled>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <button type="button" class="category-slider-arrow" id="catSliderNext" aria-label="Next categories">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="category-slider-track" id="categorySliderTrack" role="region" aria-label="Categories slider">
                @foreach ($categories as $tab)
                    <a class="category-slider-item {{ $category?->id === $tab->id ? 'is-active' : '' }}"
                       href="{{ route('services.index', ['category' => $tab->slug]) }}"
                       data-category="{{ $tab->slug }}"
                       @if ($category?->id === $tab->id) aria-current="page" @endif>
                        <div class="category-slider-icon-box">
                            <img class="category-slider-img"
                                 src="{{ $tab->image_url ?: asset('images/laundry-icons/'.($categoryIcons[$tab->slug] ?? '03_laundry_basket.png')) }}"
                                 alt=""
                                 loading="lazy">
                        </div>
                        <span class="category-slider-title">{{ $tab->name }}</span>
                    </a>
                @endforeach

                @if ($hasOtherServices)
                    <a class="category-slider-item {{ ! $category ? 'is-active' : '' }}"
                       href="{{ route('services.index', ['category' => 'other-services']) }}"
                       data-category="other-services"
                       @if (! $category) aria-current="page" @endif>
                        <div class="category-slider-icon-box">
                            <img class="category-slider-img"
                                 src="{{ asset('images/laundry-icons/03_laundry_basket.png') }}"
                                 alt="">
                        </div>
                        <span class="category-slider-title">Other services</span>
                    </a>
                @endif
            </div>
        </section>

        <!-- MAIN CONTENT: Two-Column Layout (Left: Services 75% | Right: Book Now Promotional Card 25%) -->
        <div class="row g-4 align-items-start">
            <!-- Left Column: Services Results Grid (~75%) -->
            <section class="col-lg-9 order-1" aria-label="Find laundry services">
                <!-- Error Notice Container -->
                <div class="catalog-error-banner" id="request-error" role="alert" hidden>
                    <span>We couldn’t load the services. Please try again.</span>
                    <button type="button" id="retry-request" class="btn btn-sm btn-outline-danger rounded-pill px-3">Try again &#8635;</button>
                </div>

                <p class="visually-hidden" id="catalog-status" role="status" aria-live="polite"></p>

                <!-- Dynamic AJAX Results Container -->
                <div class="results-container" id="results-container" aria-busy="false">
                    <div id="service-results">
                        @include('services.results')
                    </div>
                </div>

                <p class="text-muted small mt-4">
                    <span class="text-warning fw-bold">&#9432;</span> All prices are shown in {{ currencyCode() }} ({{ currencySymbol() }}). Billing unit is clearly indicated with each service.
                </p>
            </section>

            <!-- Right Column: Book Now Promotional Card (~25%) -->
            <aside class="col-lg-3 order-2 order-lg-2" aria-label="Selected category booking">
                <div class="services-right-sidebar">
                    <!-- Dynamic Category Summary & Book Now Box -->
                    <div id="category-summary">
                        @include('services.category-summary')
                    </div>

                    <!-- Care Note -->
                    <div class="sidebar-care-box mt-3 d-none d-lg-block">
                        <h4>Good Care. Longer Wear.</h4>
                        <p>A little professional care keeps your favourite garments in daily rotation longer.</p>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Mobile Fixed Bottom Navigation Bar (< 992px) - EXACT SAME AS HOMEPAGE -->
    @include('shared.bottom-nav')

    <!-- Reusable Offcanvas Modals (Notifications & Mobile Hamburger Menu) - EXACT SAME AS HOMEPAGE -->
    @include('shared.offcanvas-nav')

    <!-- Desktop Footer matching Homepage -->
    <footer class="services-footer">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <img src="{{ asset('images/freshgo-new-logo.jpeg') }}" alt="Freshgo Logo" class="rounded-2 mb-3" style="height: 48px; background: white; padding: 4px;">
                    <p class="text-secondary small">
                        Professional laundry &amp; dry cleaning service crafted for your peace of mind. We collect, clean, and deliver fresh clothes right back to your door.
                    </p>
                    <div class="d-flex align-items-center gap-2 pt-2">
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">Freshgo Express</span>
                        <span class="small text-secondary">Available 7 Days a Week</span>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="text-white fw-bold mb-3">Services</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('services.index', ['category' => 'wash-fold']) }}" class="footer-link">Wash &amp; Fold</a></li>
                        <li><a href="{{ route('services.index', ['category' => 'dry-cleaning']) }}" class="footer-link">Dry Cleaning</a></li>
                        <li><a href="{{ route('services.index', ['category' => 'ironing-only']) }}" class="footer-link">Steam Ironing</a></li>
                        <li><a href="{{ route('services.index', ['category' => 'bulky-item-duvet']) }}" class="footer-link">Bulky Items &amp; Duvet</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="text-white fw-bold mb-3">Company</h6>
                    <ul class="list-unstyled">
                        <li><a href="{{ url('/') }}" class="footer-link">Home</a></li>
                        <li><a href="{{ url('/#about') }}" class="footer-link">About Us</a></li>
                        <li><a href="{{ url('/#how-it-works') }}" class="footer-link">How It Works</a></li>
                        <li><a href="{{ route('admin.login') }}" class="footer-link">Admin Portal</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h6 class="text-white fw-bold mb-3">Get in Touch</h6>
                    <p class="small text-secondary mb-1"><strong>Email:</strong> support@freshgolaundry.co.uk</p>
                    <p class="small text-secondary mb-1"><strong>Phone:</strong> +44 (0) 20 1234 5678</p>
                    <p class="small text-secondary"><strong>Hours:</strong> Mon - Sun: 8:00 AM - 9:00 PM</p>
                </div>
            </div>
            <div class="border-top border-secondary pt-4 mt-5 text-center text-secondary small">
                &copy; {{ date('Y') }} FreshGo Laundry. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Interactive Category Dialog Modal (Preserved Functionality) -->
    <dialog id="category-dialog" class="custom-category-dialog" aria-labelledby="category-dialog-title">
        <div class="dialog-header-inner">
            <h2 id="category-dialog-title"></h2>
            <button type="button" class="btn-close" data-dialog-close aria-label="Close"></button>
        </div>
        <div id="category-dialog-content" class="dialog-body-inner"></div>
        <div class="dialog-footer-inner">
            <button type="button" class="btn btn-warning rounded-pill px-4 py-2 fw-bold text-dark shadow-sm" data-dialog-close>Close</button>
        </div>
    </dialog>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
