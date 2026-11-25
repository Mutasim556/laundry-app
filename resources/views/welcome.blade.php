<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getSiteName() : 'FreshGo Laundry' }} - Professional Laundry & Dry Cleaning</title>
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}?v={{ filemtime(public_path('css/laundry-home.css')) }}">
    <style>
        .fg-home-body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #F7F6F2;
            color: #171717;
            min-height: 100vh;
            overflow-x: hidden;
        }
        .fg-home-main {
            width: 100%;
            padding: 16px 12px calc(92px + env(safe-area-inset-bottom, 0px));
        }
        @media (min-width: 768px) {
            .fg-home-main {
                padding: 20px 24px 84px;
            }
        }
        @media (min-width: 992px) {
            .fg-home-main {
                padding: 24px 32px 48px;
            }
        }
        .fg-home-container {
            width: 100%;
            max-width: 1380px;
            margin: 0 auto;
        }
        /* Hero Banner */
        .fg-hero-section {
            background: radial-gradient(circle at 12% 20%, #FFFDF5 0%, #FAF8F2 92%);
            border-radius: 24px;
            padding: 28px 20px;
            border: 1px solid #F1ECE0;
        }
        @media (min-width: 992px) {
            .fg-hero-section {
                padding: 42px 44px;
            }
        }
        .fg-hero-kicker {
            display: inline-block;
            background: #FFF8E5;
            color: #B58000;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 999px;
            border: 1px solid rgba(255, 198, 41, 0.45);
        }
        .fg-hero-title {
            font-size: clamp(1.95rem, 3.8vw, 3.1rem);
            font-weight: 800;
            line-height: 1.12;
            color: #171717;
        }
        .fg-hero-subtitle {
            font-size: 0.98rem;
            color: #64748B;
            max-width: 560px;
            line-height: 1.6;
        }
        .fg-hero-media {
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
            background: #FFFFFF;
        }
        .fg-hero-media img {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            display: block;
        }
        .fg-hero-delivered-badge {
            background: #FFFFFF;
            border-radius: 16px;
            padding: 12px 16px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }
        /* Dynamic AJAX Search */
        .fg-search-shell {
            position: relative;
            z-index: 30;
        }
        .fg-search-bar {
            background: #FFFFFF;
            border: 1.5px solid #E2E8F0;
            border-radius: 16px;
            padding: 6px 8px 6px 16px;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .fg-search-bar:focus-within {
            border-color: #FFC629;
            box-shadow: 0 8px 24px rgba(255, 198, 41, 0.18);
        }
        .fg-search-input {
            border: 0;
            outline: none;
            width: 100%;
            font-size: 0.95rem;
            color: #171717;
            background: transparent;
            min-width: 0;
        }
        .fg-search-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
            max-height: 420px;
            overflow-y: auto;
            padding: 8px;
        }
        .fg-search-group-label {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            color: #64748B;
            padding: 6px 10px 4px;
            text-transform: uppercase;
        }
        .fg-search-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 10px;
            border-radius: 12px;
            text-decoration: none;
            color: #171717;
            transition: background-color 0.12s ease;
        }
        .fg-search-item:hover,
        .fg-search-item.is-active {
            background-color: #FFF8E5;
            color: #171717;
        }
        .fg-search-thumb {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #FFF8E5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        .fg-search-thumb img {
            width: 26px;
            height: 26px;
            object-fit: contain;
        }
        /* Carousels */
        .fg-carousel-nav-btn {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 1.5px solid #E2E8F0;
            background: #FFFFFF;
            color: #171717;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }
        .fg-carousel-nav-btn:hover:not(:disabled) {
            background: #FFC629;
            border-color: #FFC629;
        }
        .fg-carousel-nav-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .fg-carousel-viewport {
            overflow: hidden;
            width: 100%;
        }
        .fg-carousel-track {
            display: flex;
            gap: 16px;
            transition: transform 0.45s cubic-bezier(0.22, 1, 0.36, 1);
            will-change: transform;
        }
        @media (prefers-reduced-motion: reduce) {
            .fg-carousel-track {
                transition: none;
            }
        }
        .fg-carousel-slide {
            flex: 0 0 100%;
            min-width: 0;
        }
        #fgCategoryCarousel .fg-carousel-slide {
            flex: 0 0 calc((100% - 16px) / 2);
        }
        @media (min-width: 768px) {
            .fg-carousel-slide,
            #fgCategoryCarousel .fg-carousel-slide {
                flex: 0 0 calc((100% - 16px) / 2);
            }
        }
        @media (min-width: 992px) {
            .fg-carousel-slide,
            #fgCategoryCarousel .fg-carousel-slide {
                flex: 0 0 calc((100% - 32px) / 3);
            }
        }
        @media (min-width: 1200px) {
            .fg-carousel-slide,
            #fgCategoryCarousel .fg-carousel-slide {
                flex: 0 0 calc((100% - 48px) / 4);
            }
        }
        /* Category Card */
        .fg-cat-card {
            background: #FFFFFF;
            border: 1.5px solid #F0ECE1;
            border-radius: 22px;
            padding: 18px 14px;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            text-decoration: none;
            color: #171717;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        @media (min-width: 768px) {
            .fg-cat-card {
                padding: 24px 20px;
            }
        }
        .fg-cat-card:hover {
            transform: translateY(-4px);
            border-color: #FFC629;
            box-shadow: 0 14px 30px rgba(255, 198, 41, 0.2);
            color: #171717;
        }
        .fg-cat-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            background-color: #FFF8E5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            overflow: hidden;
            transition: background-color 0.2s ease, transform 0.2s ease;
        }
        @media (min-width: 768px) {
            .fg-cat-icon {
                width: 68px;
                height: 68px;
                margin-bottom: 14px;
            }
        }
        .fg-cat-card:hover .fg-cat-icon {
            background-color: #FFC629;
            transform: scale(1.06);
        }
        .fg-cat-icon img {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }
        /* Service Card */
        .fg-svc-card {
            background: #FFFFFF;
            border: 1.5px solid #E2E8F0;
            border-radius: 20px;
            padding: 18px;
            height: 100%;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: #171717;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .fg-svc-card:hover {
            transform: translateY(-4px);
            border-color: #FFC629;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            color: #171717;
        }
        .fg-svc-media {
            height: 144px;
            border-radius: 14px;
            background: linear-gradient(135deg, #FFF8E5 0%, #F8FAFC 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 14px;
        }
        .fg-svc-media img.fg-svc-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .fg-svc-media img.fg-svc-icon {
            width: 96px;
            height: 96px;
            object-fit: contain;
            mix-blend-mode: multiply;
        }
        @media (min-width: 768px) {
            .fg-svc-media {
                height: 112px;
            }
            .fg-svc-media img.fg-svc-icon {
                width: 68px;
                height: 68px;
            }
        }
        .fg-svc-arrow {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: #FFC629;
            color: #171717;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
    </style>
</head>
<body class="fg-home-body">
    @include('shared.header')

    <main class="fg-home-main">
        <div class="fg-home-container">
            <!-- 1. Fresh Go Hero Section with Integrated AJAX Search (No Coupon Banner) -->
            <section class="fg-hero-section mb-5" id="about" aria-label="Fresh Go Laundry Hero">
                <div class="row align-items-center g-4 g-lg-5">
                    <div class="col-12 col-lg-6">
                        <span class="fg-hero-kicker mb-3">Premium Laundry Care</span>
                        <h1 class="fg-hero-title mb-3">
                            Fresh Clothes.<br>
                            Happier You.
                            <svg class="hero-heart-icon" viewBox="0 0 24 24" style="width: 30px; height: 30px; vertical-align: middle;" aria-hidden="true">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="none" stroke="#FFC629" stroke-width="2.2"></path>
                            </svg>
                        </h1>
                        <p class="fg-hero-subtitle mb-4">
                            Professional laundry &amp; dry cleaning at your fingertips. We collect from your doorstep, clean with gentle eco-friendly detergents, and return fresh clothes neatly folded.
                        </p>

                        <!-- Integrated Dynamic AJAX Search Bar -->
                        <div class="fg-search-shell mb-4" id="fgHomeSearchWrap" style="max-width: 560px;">
                            <div class="fg-search-bar">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9E9E9E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input
                                    type="text"
                                    id="fgHomeSearchInput"
                                    class="fg-search-input"
                                    placeholder="Search services, categories, or laundry items..."
                                    autocomplete="off"
                                    aria-label="Search services, categories, or laundry items"
                                    aria-expanded="false"
                                    aria-controls="fgHomeSearchDropdown"
                                    data-search-api="{{ route('home.search') }}"
                                    data-catalog-url="{{ route('services.index') }}"
                                >
                                <span id="fgHomeSearchSpinner" class="spinner-border spinner-border-sm text-warning d-none" role="status" aria-hidden="true"></span>
                                <button type="button" id="fgHomeSearchClear" class="btn btn-sm btn-link text-muted text-decoration-none d-none px-2" aria-label="Clear search">&times;</button>
                                <a href="{{ route('services.index') }}" id="fgHomeSearchBtn" class="btn rounded-pill px-4 py-2 fw-bold text-dark flex-shrink-0 shadow-sm" style="background-color: #FFC629;">
                                    Search
                                </a>
                            </div>
                            <div id="fgHomeSearchDropdown" class="fg-search-dropdown d-none" role="listbox" aria-label="Search suggestions"></div>
                        </div>

                        <!-- Highlights & CTAs -->
                        <div class="d-flex flex-nowrap align-items-center justify-content-between justify-content-sm-start gap-2 gap-sm-4 mb-3">
                            <div class="min-w-0">
                                <div class="fw-bolder text-dark text-nowrap" style="font-size: clamp(0.95rem, 4vw, 1.25rem);">24h</div>
                                <div class="text-muted text-nowrap" style="font-size: clamp(0.66rem, 2.7vw, 0.85rem);">Express Delivery</div>
                            </div>
                            <div class="vr flex-shrink-0"></div>
                            <div class="min-w-0">
                                <div class="fw-bolder text-dark text-nowrap" style="font-size: clamp(0.95rem, 4vw, 1.25rem);">100%</div>
                                <div class="text-muted text-nowrap" style="font-size: clamp(0.66rem, 2.7vw, 0.85rem);">Eco-friendly Wash</div>
                            </div>
                            <div class="vr flex-shrink-0"></div>
                            <div class="min-w-0">
                                <div class="fw-bolder text-dark text-nowrap" style="font-size: clamp(0.95rem, 4vw, 1.25rem);">4.9 &#9733;</div>
                                <div class="text-muted text-nowrap" style="font-size: clamp(0.66rem, 2.7vw, 0.85rem);">Customer Rating</div>
                            </div>
                        </div>

                        <div class="d-flex flex-nowrap align-items-center gap-2 pt-1">
                            <a href="{{ route('booking.services') }}" class="btn btn-dark rounded-pill px-3 px-sm-4 py-2 fw-bold shadow-sm text-nowrap flex-grow-1 flex-sm-grow-0 text-center" style="font-size: clamp(0.76rem, 3.2vw, 0.92rem);">
                                Book a Collection &rarr;
                            </a>
                            <a href="{{ route('services.index') }}" class="btn btn-outline-dark rounded-pill px-3 px-sm-4 py-2 fw-semibold text-nowrap flex-grow-1 flex-sm-grow-0 text-center" style="font-size: clamp(0.76rem, 3.2vw, 0.92rem);">
                                Explore Services
                            </a>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 text-center">
                        <div class="fg-hero-media">
                            <img src="{{ asset('images/freshgo-full-poster.jpg') }}" alt="Freshgo Laundry Delivery">
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. Our Laundry Categories Carousel -->
            @php
                $catAccents = [
                    ['bg' => '#FFF8E5', 'color' => '#B45309'],
                    ['bg' => '#EFF6FF', 'color' => '#1D4ED8'],
                    ['bg' => '#ECFDF5', 'color' => '#047857'],
                    ['bg' => '#F5F3FF', 'color' => '#6D28D9'],
                ];
            @endphp
            <section class="mb-5" id="fgCategorySection" aria-label="Our Laundry Categories">
                <div class="d-flex justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                    <div class="min-w-0">
                        <h2 class="h4 fw-bold mb-0" style="color: #171717;">Our Laundry Categories</h2>
                        <p class="small mb-0" style="color: #64748B;">Choose specialized garment care suited to your fabric needs.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0" id="fgCategoryControls">
                        <button type="button" class="fg-carousel-nav-btn js-carousel-prev" aria-label="Previous categories" title="Previous">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        </button>
                        <button type="button" class="fg-carousel-nav-btn js-carousel-next" aria-label="Next categories" title="Next">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
                    </div>
                </div>

                @if ($categories->isEmpty())
                    <div class="p-4 bg-white rounded-4 border text-center small text-muted">
                        No active laundry categories are currently available.
                    </div>
                @else
                    <div class="fg-carousel-viewport" id="fgCategoryCarousel" data-interval="4000" data-mobile-per-view="2" tabindex="0">
                        <div class="fg-carousel-track">
                            @foreach ($categories as $cat)
                                @php
                                    $catIconFile = $categoryIcons[$cat->slug] ?? '03_laundry_basket.png';
                                    $catImg = $cat->image_url ?: asset('images/laundry-icons/' . $catIconFile);
                                    $catDesc = $cat->short_description ?: ($cat->description ?: 'Professional garment care tailored to this category.');
                                @endphp
                                <div class="fg-carousel-slide">
                                    <a href="{{ route('services.index', ['category' => $cat->slug]) }}" class="fg-cat-card">
                                        <div class="fg-cat-icon">
                                            <img src="{{ $catImg }}" alt="{{ $cat->name }}" loading="lazy">
                                        </div>
                                        <h3 class="h6 fw-bold mb-0 mb-md-1" style="color: #171717;">{{ $cat->name }}</h3>
                                        <p class="small mb-0 d-none d-md-block" style="color: #64748B;">
                                            {{ \Illuminate\Support\Str::limit($catDesc, 88) }}
                                        </p>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <!-- 3. Fresh Go Brand Promotional Banner (No Coupon System) -->
            <section class="mb-5" id="how-it-works" aria-label="Fresh Go Promotional Banner">
                <div class="p-4 p-md-5" style="background: linear-gradient(135deg, #FFE88D 0%, #FFC83B 55%, #FFBE26 100%); border-radius: 28px; box-shadow: 0 14px 32px rgba(255, 198, 41, 0.24);">
                    <div class="row align-items-center g-4">
                        <div class="col-12 col-md-8 text-center text-md-start">
                            <span class="badge rounded-pill px-3 py-2 mb-3 fw-bold" style="background: rgba(255, 255, 255, 0.72); color: #171717; font-size: 0.78rem;">
                                Fresh Go Express Care
                            </span>
                            <h2 class="display-6 fw-bolder text-dark mb-2">We Collect. We Clean. We Deliver.</h2>
                            <p class="fs-6 text-dark mb-4 opacity-75 mx-auto mx-md-0" style="max-width: 560px;">
                                Enjoy doorstep laundry and dry cleaning pickup, gentle fabric care, and fast turnaround straight to your door.
                            </p>
                            <div class="d-flex justify-content-center justify-content-md-start">
                                <a href="{{ route('booking.services') }}" class="btn btn-dark text-white rounded-pill px-4 py-2 fw-bold shadow-sm">
                                    Book Now &rarr;
                                </a>
                            </div>
                        </div>
                        <div class="d-none d-md-block col-md-4 text-center">
                            <img src="{{ asset('images/laundry-icons/07_folded_laundry.png') }}"
                                 alt="Folded Laundry Pile"
                                 class="img-fluid"
                                 style="max-height: 170px; object-fit: contain; mix-blend-mode: multiply; filter: drop-shadow(0 12px 20px rgba(0,0,0,0.14));">
                        </div>
                    </div>
                </div>
            </section>

            <!-- 4. Our Services Carousel -->
            <section class="mb-4" id="fgServiceSection" aria-label="Our Services">
                <div class="d-flex justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                    <div class="min-w-0">
                        <h2 class="h4 fw-bold mb-0" style="color: #171717;">Our Services</h2>
                        <p class="small mb-0" style="color: #64748B;">Explore active laundry and dry cleaning services.</p>
                        <a href="{{ route('services.index') }}" class="small fw-bold text-decoration-none d-inline-block d-md-none mt-1" style="color: #B45309;">
                            View Full Catalog &rarr;
                        </a>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <a href="{{ route('services.index') }}" class="small fw-bold text-decoration-none me-2 d-none d-md-inline-block" style="color: #B45309;">
                            View Full Catalog &rarr;
                        </a>
                        <div class="d-flex align-items-center gap-2" id="fgServiceControls">
                            <button type="button" class="fg-carousel-nav-btn js-carousel-prev" aria-label="Previous services" title="Previous">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            </button>
                            <button type="button" class="fg-carousel-nav-btn js-carousel-next" aria-label="Next services" title="Next">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </button>
                        </div>
                    </div>
                </div>

                @if ($services->isEmpty())
                    <div class="p-4 bg-white rounded-4 border text-center small text-muted">
                        No active services are currently available.
                    </div>
                @else
                    <div class="fg-carousel-viewport" id="fgServiceCarousel" data-interval="4600" tabindex="0">
                        <div class="fg-carousel-track">
                            @foreach ($services as $svc)
                                @php
                                    $svcCat = $svc->category ?: $svc->item?->category;
                                    $svcCatSlug = $svcCat?->slug;
                                    $svcFallbackIcon = $categoryIcons[$svcCatSlug ?? ''] ?? '03_laundry_basket.png';
                                    $svcImg = $svc->image_url;
                                    $svcUrl = route('services.index', array_filter([
                                        'category' => $svcCatSlug,
                                        'item' => $svc->item_id,
                                        'q' => $svc->name,
                                    ], fn ($v) => $v !== null && $v !== ''));
                                @endphp
                                <div class="fg-carousel-slide">
                                    <a href="{{ $svcUrl }}" class="fg-svc-card">
                                        <div class="fg-svc-media">
                                            @if ($svcImg)
                                                <img src="{{ $svcImg }}" alt="{{ $svc->name }}" class="fg-svc-photo" loading="lazy">
                                            @else
                                                <img src="{{ asset('images/laundry-icons/' . $svcFallbackIcon) }}" alt="{{ $svc->name }}" class="fg-svc-icon" loading="lazy">
                                            @endif
                                        </div>
                                        <div class="flex-grow-1 text-center text-md-start">
                                            @if ($svcCat)
                                                <span class="badge rounded-pill mb-2" style="background-color: #FFF8E5; color: #92400E;">
                                                    {{ $svcCat->name }}
                                                </span>
                                            @endif
                                            <h3 class="h6 fw-bold mb-1" style="color: #171717;">{{ $svc->name }}</h3>
                                            <p class="small mb-0" style="color: #64748B;">
                                                {{ \Illuminate\Support\Str::limit($svc->description ?: 'Professional laundry care with doorstep collection.', 78) }}
                                            </p>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-between gap-3 pt-3 mt-3 border-top">
                                            <div>
                                                @if ($svc->price !== null)
                                                    <span class="fw-bold fs-6" style="color: #171717;">{{ formatMoney($svc->price, $svc->currency_code ?? null) }}</span>
                                                    @if ($svc->unit?->name)
                                                        <span class="small" style="color: #64748B;">/ {{ $svc->unit->name }}</span>
                                                    @endif
                                                @else
                                                    <span class="small fw-semibold" style="color: #64748B;">See catalog</span>
                                                @endif
                                            </div>
                                            <span class="fg-svc-arrow" aria-hidden="true">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </main>

    @include('shared.footer')

    <!-- Mobile Fixed Bottom Navigation Bar -->
    <div class="d-lg-none">
        @include('shared.bottom-nav')
    </div>

    @include('shared.offcanvas-nav')

    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/laundry-home.js') }}?v={{ filemtime(public_path('js/laundry-home.js')) }}"></script>
</body>
</html>
