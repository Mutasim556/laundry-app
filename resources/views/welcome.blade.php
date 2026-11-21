<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getSiteName() : 'FreshGo Laundry' }} - Professional Laundry & Dry Cleaning</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">

    <!-- Google Fonts: Poppins (Design System typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    
    <!-- Custom Theme CSS for Laundry Mobile & Desktop -->
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">
</head>
<body class="laundry-home-body">

    <!-- =========================================================================
         1. MOBILE APP HOMEPAGE (< 992px)
         Matches the uploaded design screenshot exactly.
         ========================================================================= -->
    <div class="mobile-app-container">
        
        <!-- Mobile Header: Profile, Greeting, Bell, Hamburger -->
        @include('shared.header-mobile')

        <!-- Mobile Hero Section: Fresh Clothes, Happier You + Heart -->
        <section class="mobile-hero">
            <h1 class="hero-title">
                Fresh Clothes<br>
                <span class="highlight-yellow">Happier You</span>
                <!-- Hand-drawn style decorative heart matching screenshot -->
                <svg class="hero-heart-icon" viewBox="0 0 24 24">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
                </svg>
            </h1>
            <p class="hero-subtitle">Professional laundry &amp; dry cleaning at your fingertips.</p>
        </section>

        <!-- Mobile Search Bar with Filter Icon -->
        <div class="search-bar-wrapper">
            <div class="search-box-inner">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9E9E9E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" class="search-input-field js-laundry-search-input" placeholder="Search services..." data-search-url="{{ route('services.index') }}" aria-label="Search services">
                <button type="button" class="search-filter-btn js-filter-toggle-btn" data-url="{{ route('services.index') }}" title="Open Filters" aria-label="Filter Options">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="6" x2="20" y2="6"></line>
                        <line x1="10" y1="12" x2="20" y2="12"></line>
                        <line x1="14" y1="18" x2="20" y2="18"></line>
                        <circle cx="7" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Promotional Banner Card (Get 20% OFF) -->
        <div class="promo-banner-card">
            <span class="promo-badge-pill">New User Offer</span>
            <h3 class="promo-title">Get 20% OFF</h3>
            <p class="promo-desc">on your first laundry order</p>
            <a href="{{ route('booking.services') }}" class="btn-book-now">
                Book Now <span>&rarr;</span>
            </a>

            <!-- Decorative Flowers & Sparkles -->
            <div class="sparkle-dec">&#10022; &#10022;</div>
            
            <!-- White decorative flower 1 -->
            <svg class="flower-dec flower-dec-1" viewBox="0 0 50 50">
                <circle cx="25" cy="15" r="8" fill="#FFFFFF"/>
                <circle cx="35" cy="25" r="8" fill="#FFFFFF"/>
                <circle cx="25" cy="35" r="8" fill="#FFFFFF"/>
                <circle cx="15" cy="25" r="8" fill="#FFFFFF"/>
                <circle cx="25" cy="25" r="5" fill="#FFE57F"/>
            </svg>

            <!-- White decorative flower 2 -->
            <svg class="flower-dec flower-dec-2" viewBox="0 0 40 40">
                <circle cx="20" cy="12" r="6" fill="#FFFFFF"/>
                <circle cx="28" cy="20" r="6" fill="#FFFFFF"/>
                <circle cx="20" cy="28" r="6" fill="#FFFFFF"/>
                <circle cx="12" cy="20" r="6" fill="#FFFFFF"/>
                <circle cx="20" cy="20" r="4" fill="#FFE57F"/>
            </svg>

            <!-- Folded laundry clothes image on the right -->
            <div class="promo-image-wrapper">
                <img class="promo-laundry-img" src="{{ asset('images/laundry-icons/07_folded_laundry.png') }}" alt="Folded Laundry Pile">
            </div>
        </div>

        <!-- Service Categories (4 items in a single row) -->
        <section class="categories-section">
            <div class="categories-grid-four">
                <!-- 1. Wash & Fold -->
                <a href="{{ route('services.index', ['category' => 'wash-fold']) }}" class="category-item-card">
                    <div class="category-icon-box">
                        <!-- Black outline washing machine icon -->
                        <svg class="category-icon-svg" viewBox="0 0 24 24">
                            <rect x="3" y="2" width="18" height="20" rx="3" ry="3"></rect>
                            <circle cx="12" cy="13" r="5"></circle>
                            <circle cx="8" cy="6" r="1.5" fill="currentColor"></circle>
                            <line x1="12" y1="6" x2="16" y2="6"></line>
                        </svg>
                    </div>
                    <span class="category-label">Wash &amp; Fold</span>
                </a>

                <!-- 2. Dry Cleaning -->
                <a href="{{ route('services.index', ['category' => 'dry-cleaning']) }}" class="category-item-card">
                    <div class="category-icon-box">
                        <!-- Black outline hanger icon -->
                        <svg class="category-icon-svg" viewBox="0 0 24 24">
                            <path d="M12 4a3 3 0 0 1 3 3c0 .8-.4 1.5-1 2L2 17h20L13.5 8.5"></path>
                            <line x1="2" y1="17" x2="22" y2="17"></line>
                        </svg>
                    </div>
                    <span class="category-label">Dry Cleaning</span>
                </a>

                <!-- 3. Ironing -->
                <a href="{{ route('services.index', ['category' => 'ironing-only']) }}" class="category-item-card">
                    <div class="category-icon-box">
                        <!-- Black outline iron icon -->
                        <svg class="category-icon-svg" viewBox="0 0 24 24">
                            <path d="M4 17h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H8l-5 7v1a2 2 0 0 0 2 2z"></path>
                            <line x1="7" y1="10" x2="16" y2="10"></line>
                            <circle cx="12" cy="14" r="1" fill="currentColor"></circle>
                        </svg>
                    </div>
                    <span class="category-label">Ironing</span>
                </a>

                <!-- 4. Express -->
                <a href="{{ route('services.index', ['category' => 'wash-fold']) }}" class="category-item-card">
                    <div class="category-icon-box">
                        <!-- Black outline lightning bolt / express icon -->
                        <svg class="category-icon-svg" viewBox="0 0 24 24">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    </div>
                    <span class="category-label">Express</span>
                </a>
            </div>
        </section>

        <!-- Our Popular Services Section -->
        <section class="popular-section">
            <div class="section-header-row">
                <h3 class="section-heading-title">Our Popular Services</h3>
                <a href="{{ route('services.index') }}" class="see-all-link">
                    See All <span>&rarr;</span>
                </a>
            </div>

            <!-- Dynamic or Seeded Popular Service Cards Matching Design Screenshot -->
            <div id="serviceCardsContainer">
                <!-- Service 1: Wash & Fold -->
                <a href="{{ route('services.index', ['category' => 'wash-fold']) }}" class="service-card-item js-service-card-item" data-name="Wash & Fold" data-desc="Fresh, clean and neatly folded clothes" data-category="wash-fold">
                    <div class="service-card-left">
                        <div class="service-thumb-box">
                            <img class="service-thumb-img" src="{{ asset('images/laundry-icons/03_laundry_basket.png') }}" alt="Wash & Fold Basket">
                        </div>
                        <div class="service-meta">
                            <h4 class="service-meta-title">Wash &amp; Fold</h4>
                            <p class="service-meta-desc">Fresh, clean and neatly folded clothes.</p>
                            <span class="service-meta-price">$12.00</span>
                        </div>
                    </div>
                    <div class="service-arrow-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </div>
                </a>

                <!-- Service 2: Dry Cleaning -->
                <a href="{{ route('services.index', ['category' => 'dry-cleaning']) }}" class="service-card-item js-service-card-item" data-name="Dry Cleaning" data-desc="Delicate care for your suits & premium fabrics" data-category="dry-cleaning">
                    <div class="service-card-left">
                        <div class="service-thumb-box">
                            <img class="service-thumb-img" src="{{ asset('images/laundry-icons/06_shirt_hanger.png') }}" alt="Dry Cleaning">
                        </div>
                        <div class="service-meta">
                            <h4 class="service-meta-title">Dry Cleaning</h4>
                            <p class="service-meta-desc">Delicate care for suits &amp; fine fabrics.</p>
                            <span class="service-meta-price">$18.00</span>
                        </div>
                    </div>
                    <div class="service-arrow-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </div>
                </a>

                <!-- Service 3: Ironing -->
                <a href="{{ route('services.index', ['category' => 'ironing-only']) }}" class="service-card-item js-service-card-item" data-name="Ironing" data-desc="Crisp, wrinkle-free steam ironing" data-category="ironing-only">
                    <div class="service-card-left">
                        <div class="service-thumb-box">
                            <img class="service-thumb-img" src="{{ asset('images/laundry-icons/05_iron.png') }}" alt="Steam Ironing">
                        </div>
                        <div class="service-meta">
                            <h4 class="service-meta-title">Ironing</h4>
                            <p class="service-meta-desc">Crisp, wrinkle-free professional press.</p>
                            <span class="service-meta-price">$8.00</span>
                        </div>
                    </div>
                    <div class="service-arrow-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </div>
                </a>

                <!-- Service 4: Express Laundry -->
                <a href="{{ route('services.index') }}" class="service-card-item js-service-card-item" data-name="Express Laundry" data-desc="Same-day expedited pickup and delivery" data-category="express">
                    <div class="service-card-left">
                        <div class="service-thumb-box">
                            <img class="service-thumb-img" src="{{ asset('images/laundry-icons/10_laundry_delivery.png') }}" alt="Express Delivery">
                        </div>
                        <div class="service-meta">
                            <h4 class="service-meta-title">Express Laundry</h4>
                            <p class="service-meta-desc">Same-day fast turnaround service.</p>
                            <span class="service-meta-price">$25.00</span>
                        </div>
                    </div>
                    <div class="service-arrow-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </div>
                </a>
            </div>

            <div id="searchEmptyNotice" class="text-center py-4 d-none">
                <p class="text-muted small mb-2">No matching services found.</p>
                <a href="{{ route('services.index') }}" class="btn btn-sm btn-outline-warning rounded-pill px-3">View Full Catalog</a>
            </div>
        </section>

        <!-- 7. Mobile Fixed Bottom Navigation Bar -->
        @include('shared.bottom-nav')
    </div>


    <!-- =========================================================================
         2. DESKTOP HOMEPAGE (>= 992px)
         Professional full-width desktop experience using the same design system.
         ========================================================================= -->
    <div class="desktop-app-container">
        <!-- Desktop Header & Navbar -->
        @include('shared.header-desktop')

        <!-- Desktop Hero Section -->
        <section class="desktop-hero-section" id="about">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <div class="badge px-3 py-2 rounded-pill mb-3" style="background-color: var(--soft-cream); color: #B58000; font-weight: 700; border: 1px solid rgba(255, 198, 41, 0.4);">
                            Premium Laundry Care
                        </div>
                        <h1 class="desktop-hero-title">
                            Fresh Clothes.<br>
                            <span class="highlight-yellow">Happier You.</span>
                            <svg class="hero-heart-icon" viewBox="0 0 24 24" style="width: 32px; height: 32px;">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
                            </svg>
                        </h1>
                        <p class="desktop-hero-subtitle">
                            Professional laundry &amp; dry cleaning at your fingertips. We collect from your doorstep, clean with gentle eco-friendly detergents, and return fresh clothes neatly folded.
                        </p>

                        <!-- Desktop Search Bar -->
                        <div class="search-box-inner mb-4" style="max-width: 520px;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#9E9E9E" stroke-width="2.2" class="me-2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" class="search-input-field js-laundry-search-input" placeholder="Search services (e.g. Wash & Fold, Ironing, Suits)..." data-search-url="{{ route('services.index') }}">
                            <a href="{{ route('services.index') }}" class="btn btn-warning rounded-pill px-4 py-2 fw-bold text-dark shadow-sm">
                                Search
                            </a>
                        </div>

                        <!-- Stats or Highlights -->
                        <div class="d-flex align-items-center gap-4 pt-2">
                            <div>
                                <div class="fw-bolder fs-4 text-dark">24h</div>
                                <div class="small text-muted">Express Delivery</div>
                            </div>
                            <div class="vr"></div>
                            <div>
                                <div class="fw-bolder fs-4 text-dark">100%</div>
                                <div class="small text-muted">Eco-friendly Wash</div>
                            </div>
                            <div class="vr"></div>
                            <div>
                                <div class="fw-bolder fs-4 text-dark">4.9 ★</div>
                                <div class="small text-muted">Customer Rating</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 text-center">
                        <div class="position-relative d-inline-block">
                            <img src="{{ asset('images/freshgo-full-poster.jpg') }}" alt="Freshgo Laundry Delivery" class="img-fluid rounded-5 shadow-lg" style="max-height: 480px; object-fit: cover;">
                            <div class="position-absolute bottom-0 start-0 translate-middle-y bg-white p-3 rounded-4 shadow-lg ms-3 d-flex align-items-center gap-3">
                                <div class="p-2 rounded-circle" style="background-color: var(--soft-cream);">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#B58000" stroke-width="2.2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                </div>
                                <div class="text-start">
                                    <div class="fw-bold text-dark small">Order Delivered</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Cleaned &amp; Pressed Neatly</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Desktop Promotional Banner -->
        <section class="container" id="how-it-works">
            <div class="desktop-banner-card">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <span class="promo-badge-pill">New User Special Offer</span>
                        <h2 class="display-5 fw-bolder text-dark mb-2">Get 20% OFF</h2>
                        <p class="fs-5 text-dark mb-4 opacity-75">on your first laundry and dry cleaning order with code <strong>FRESH20</strong></p>
                        <a href="{{ route('booking.services') }}" class="btn btn-dark text-white rounded-pill px-5 py-3 fw-bold fs-6 shadow">
                            Book Now &rarr;
                        </a>
                    </div>
                    <div class="col-md-5 text-center position-relative">
                        <img src="{{ asset('images/laundry-icons/07_folded_laundry.png') }}" alt="Folded Laundry Pile" class="img-fluid" style="max-height: 220px; filter: drop-shadow(0 15px 25px rgba(0,0,0,0.18));">
                    </div>
                </div>
            </div>
        </section>

        <!-- Desktop Service Categories (4 Columns) -->
        <section class="container py-4">
            <div class="text-center mb-5">
                <h2 class="fw-bolder text-dark">Our Laundry Categories</h2>
                <p class="text-muted">Choose the specialized garment care suited to your fabric needs.</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <a href="{{ route('services.index', ['category' => 'wash-fold']) }}" class="desktop-category-card">
                        <div class="desktop-category-icon-box">
                            <svg class="category-icon-svg" viewBox="0 0 24 24" style="width: 36px; height: 36px;">
                                <rect x="3" y="2" width="18" height="20" rx="3" ry="3"></rect>
                                <circle cx="12" cy="13" r="5"></circle>
                                <circle cx="8" cy="6" r="1.5" fill="currentColor"></circle>
                                <line x1="12" y1="6" x2="16" y2="6"></line>
                            </svg>
                        </div>
                        <h3 class="desktop-category-title">Wash &amp; Fold</h3>
                        <p class="text-muted small m-0">Daily t-shirts, towels, gym wear &amp; casual clothing.</p>
                    </a>
                </div>

                <div class="col-lg-3 col-md-6">
                    <a href="{{ route('services.index', ['category' => 'dry-cleaning']) }}" class="desktop-category-card">
                        <div class="desktop-category-icon-box">
                            <svg class="category-icon-svg" viewBox="0 0 24 24" style="width: 36px; height: 36px;">
                                <path d="M12 4a3 3 0 0 1 3 3c0 .8-.4 1.5-1 2L2 17h20L13.5 8.5"></path>
                                <line x1="2" y1="17" x2="22" y2="17"></line>
                            </svg>
                        </div>
                        <h3 class="desktop-category-title">Dry Cleaning</h3>
                        <p class="text-muted small m-0">Suits, silks, wool coats &amp; delicate evening dresses.</p>
                    </a>
                </div>

                <div class="col-lg-3 col-md-6">
                    <a href="{{ route('services.index', ['category' => 'ironing-only']) }}" class="desktop-category-card">
                        <div class="desktop-category-icon-box">
                            <svg class="category-icon-svg" viewBox="0 0 24 24" style="width: 36px; height: 36px;">
                                <path d="M4 17h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2H8l-5 7v1a2 2 0 0 0 2 2z"></path>
                                <line x1="7" y1="10" x2="16" y2="10"></line>
                                <circle cx="12" cy="14" r="1" fill="currentColor"></circle>
                            </svg>
                        </div>
                        <h3 class="desktop-category-title">Ironing</h3>
                        <p class="text-muted small m-0">Crisp wrinkle-free professional steam pressing on hangers.</p>
                    </a>
                </div>

                <div class="col-lg-3 col-md-6">
                    <a href="{{ route('services.index') }}" class="desktop-category-card">
                        <div class="desktop-category-icon-box">
                            <svg class="category-icon-svg" viewBox="0 0 24 24" style="width: 36px; height: 36px;">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                            </svg>
                        </div>
                        <h3 class="desktop-category-title">Express Laundry</h3>
                        <p class="text-muted small m-0">Fast track turnaround with same-day collection &amp; return.</p>
                    </a>
                </div>
            </div>
        </section>

        <!-- Desktop Popular Services Grid (3 or 4 Columns) -->
        <section class="container py-5">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h2 class="fw-bolder text-dark m-0">Our Popular Services</h2>
                    <p class="text-muted small m-0">Most requested everyday laundry solutions.</p>
                </div>
                <a href="{{ route('services.index') }}" class="see-all-link fs-6">
                    View Full Catalog <span>&rarr;</span>
                </a>
            </div>

            <div class="row g-4">
                <!-- 1. Wash & Fold -->
                <div class="col-lg-3 col-md-6">
                    <a href="{{ route('services.index', ['category' => 'wash-fold']) }}" class="desktop-service-card js-service-card-item" data-name="Wash & Fold" data-desc="Fresh, clean and neatly folded clothes" data-category="wash-fold">
                        <div class="text-center p-3 mb-3 rounded-4 bg-light">
                            <img src="{{ asset('images/laundry-icons/03_laundry_basket.png') }}" alt="Wash & Fold" style="height: 110px; object-fit: contain;">
                        </div>
                        <div>
                            <span class="badge rounded-pill mb-2" style="background-color: var(--soft-cream); color: #B58000;">Everyday Care</span>
                            <h4 class="fw-bold text-dark fs-5">Wash &amp; Fold</h4>
                            <p class="text-muted small">Fresh, clean and neatly folded clothes for your daily lifestyle.</p>
                        </div>
                        <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-3">
                            <span class="fw-bolder fs-5 text-dark">$12.00</span>
                            <span class="service-arrow-btn">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </span>
                        </div>
                    </a>
                </div>

                <!-- 2. Dry Cleaning -->
                <div class="col-lg-3 col-md-6">
                    <a href="{{ route('services.index', ['category' => 'dry-cleaning']) }}" class="desktop-service-card js-service-card-item" data-name="Dry Cleaning" data-desc="Delicate care for suits and dresses" data-category="dry-cleaning">
                        <div class="text-center p-3 mb-3 rounded-4 bg-light">
                            <img src="{{ asset('images/laundry-icons/06_shirt_hanger.png') }}" alt="Dry Cleaning" style="height: 110px; object-fit: contain;">
                        </div>
                        <div>
                            <span class="badge rounded-pill mb-2" style="background-color: var(--soft-cream); color: #B58000;">Premium Dry Clean</span>
                            <h4 class="fw-bold text-dark fs-5">Dry Cleaning</h4>
                            <p class="text-muted small">Professional solvent treatment for delicate suits, blazers and gowns.</p>
                        </div>
                        <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-3">
                            <span class="fw-bolder fs-5 text-dark">$18.00</span>
                            <span class="service-arrow-btn">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </span>
                        </div>
                    </a>
                </div>

                <!-- 3. Ironing -->
                <div class="col-lg-3 col-md-6">
                    <a href="{{ route('services.index', ['category' => 'ironing-only']) }}" class="desktop-service-card js-service-card-item" data-name="Ironing" data-desc="Steam press wrinkle free" data-category="ironing-only">
                        <div class="text-center p-3 mb-3 rounded-4 bg-light">
                            <img src="{{ asset('images/laundry-icons/05_iron.png') }}" alt="Ironing Only" style="height: 110px; object-fit: contain;">
                        </div>
                        <div>
                            <span class="badge rounded-pill mb-2" style="background-color: var(--soft-cream); color: #B58000;">Steam Press</span>
                            <h4 class="fw-bold text-dark fs-5">Ironing</h4>
                            <p class="text-muted small">Crisp, crease-free garment pressing on custom hangers.</p>
                        </div>
                        <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-3">
                            <span class="fw-bolder fs-5 text-dark">$8.00</span>
                            <span class="service-arrow-btn">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </span>
                        </div>
                    </a>
                </div>

                <!-- 4. Express Laundry -->
                <div class="col-lg-3 col-md-6">
                    <a href="{{ route('services.index') }}" class="desktop-service-card js-service-card-item" data-name="Express Laundry" data-desc="Same day delivery fast track" data-category="express">
                        <div class="text-center p-3 mb-3 rounded-4 bg-light">
                            <img src="{{ asset('images/laundry-icons/10_laundry_delivery.png') }}" alt="Express Delivery" style="height: 110px; object-fit: contain;">
                        </div>
                        <div>
                            <span class="badge rounded-pill mb-2" style="background-color: var(--soft-cream); color: #B58000;">Fast Track</span>
                            <h4 class="fw-bold text-dark fs-5">Express Laundry</h4>
                            <p class="text-muted small">Emergency service with guaranteed collection and delivery in under 24 hours.</p>
                        </div>
                        <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-3">
                            <span class="fw-bolder fs-5 text-dark">$25.00</span>
                            <span class="service-arrow-btn">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </span>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <!-- Desktop Footer -->
        <footer class="desktop-footer" id="contact">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-4">
                        <img src="{{ asset('images/freshgo-new-logo.jpeg') }}" alt="Freshgo Logo" class="rounded-2 mb-3" style="height: 48px; background: white; padding: 4px;">
                        <p class="text-secondary small">
                            Professional laundry &amp; dry cleaning service crafted for your peace of mind. We collect, we clean, and we deliver fresh clothes right back to your door.
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
                            <li><a href="{{ route('services.index') }}" class="footer-link">Express Laundry</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <h6 class="text-white fw-bold mb-3">Company</h6>
                        <ul class="list-unstyled">
                            <li><a href="#about" class="footer-link">About Us</a></li>
                            <li><a href="{{ route('services.index') }}" class="footer-link">Pricing &amp; Catalog</a></li>
                            <li><a href="#how-it-works" class="footer-link">How It Works</a></li>
                            <li><a href="{{ route('admin.login') }}" class="footer-link">Admin Portal</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-4 col-md-4">
                        <h6 class="text-white fw-bold mb-3">Get in Touch</h6>
                        <p class="small text-secondary mb-2">Have a question or special laundry request? Reach out to our care team.</p>
                        <p class="small text-secondary mb-1"><strong>Email:</strong> support@freshgolaundry.co.uk</p>
                        <p class="small text-secondary mb-1"><strong>Phone:</strong> +44 (0) 20 1234 5678</p>
                        <p class="small text-secondary"><strong>Hours:</strong> Mon - Sun: 8:00 AM - 9:00 PM</p>
                    </div>
                </div>
                <div class="border-top border-secondary pt-4 mt-5 text-center text-secondary small">
                    &copy; {{ date('Y') }} FreshGo Laundry. All rights reserved. Professional Laundry &amp; Dry Cleaning.
                </div>
            </div>
        </footer>
    </div>


    <!-- =========================================================================
         3. REUSABLE MODALS / OFFCANVAS
         ========================================================================= -->
    @include('shared.offcanvas-nav')

    <!-- Bootstrap 5 JS Bundle -->
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    
    <!-- Custom Theme JS for Search, Tabs, and Interactions -->
    <script src="{{ asset('js/laundry-home.js') }}"></script>
</body>
</html>
