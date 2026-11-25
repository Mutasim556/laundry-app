@php
    $seo = $servicesPayload['seo'] ?? [];
    $siteName = class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getSiteName() : 'FreshGo Laundry';
    $defaultTitle = ($category?->name ?? 'Other services') . ' | ' . $siteName;
    $metaTitle = ! empty($seo['meta_title']) ? $seo['meta_title'] : $defaultTitle;
    $metaDesc = ! empty($seo['meta_description']) ? $seo['meta_description'] : 'Explore FreshGo laundry services and compare prices.';
    $ogTitle = ! empty($seo['og_title']) ? $seo['og_title'] : $metaTitle;
    $ogDesc = ! empty($seo['og_description']) ? $seo['og_description'] : $metaDesc;
    $ogImage = ! empty($seo['og_image_url']) ? $svcCms->resolveImageUrl($seo['og_image_url']) : '';
    $robots = $seo['robots'] ?? 'index, follow';
    $canonical = $seo['canonical_url'] ?? '';
    $categoryIcons = \App\Http\Controllers\HomeController::categoryIconMap();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="{{ $metaDesc }}">
    <meta name="robots" content="{{ $robots }}">
    @if($canonical !== '')
        <link rel="canonical" href="{{ $canonical }}">
    @endif
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDesc }}">
    @if($ogImage !== '')
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    <title>{{ $metaTitle }}</title>

    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/services.css') }}?v={{ filemtime(public_path('css/services.css')) }}">
    <script src="{{ asset('js/services.js') }}" defer></script>
    <style>
        .fg-svc-banner {
            border-radius: 24px;
            padding: 1.5rem 1.4rem;
            box-shadow: 0 12px 28px rgba(255, 198, 41, 0.16);
            border: 1px solid #FDE68A;
        }
        @media (min-width: 768px) {
            .fg-svc-banner { padding: 2rem 2.5rem; }
        }
        .fg-svc-banner-img-wrap {
            border-radius: 18px;
            overflow: hidden;
            max-height: 220px;
            display: inline-block;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        }
        .fg-svc-banner-img-wrap img {
            width: 100%;
            max-height: 220px;
            object-fit: cover;
            display: block;
            transition: transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .fg-svc-banner {
            animation: fgSvcFadeUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .fg-svc-banner-img-wrap:hover img {
            transform: scale(1.04);
        }
        .search-field-group {
            transition: border-color 0.38s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.38s cubic-bezier(0.22, 1, 0.36, 1), transform 0.38s cubic-bezier(0.22, 1, 0.36, 1);
            animation: fgSvcSearchPulse 4.8s ease-in-out infinite;
        }
        .search-field-group:hover {
            border-color: #FCD34D;
            transform: translateY(-1px);
        }
        .search-field-group:focus-within {
            border-color: #FFC629;
            box-shadow: 0 12px 28px rgba(255, 198, 41, 0.24), 0 0 0 4px rgba(255, 198, 41, 0.12);
            transform: translateY(-2px);
            animation: none;
        }
        .fg-svc-search-icon {
            flex-shrink: 0;
            transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1), stroke 0.25s ease;
        }
        .search-field-group:focus-within .fg-svc-search-icon {
            stroke: #D97706;
            transform: scale(1.15) rotate(-8deg);
        }
        .search-field-input::placeholder {
            color: #94A3B8;
            opacity: 1;
            transition: opacity 0.28s ease;
        }
        .search-field-input.is-ph-fading::placeholder {
            opacity: 0;
        }
        .category-slider-item {
            transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.24s ease, border-color 0.24s ease;
        }
        .category-slider-item:hover {
            transform: translateY(-3px);
        }
        .category-slider-icon-box {
            transition: transform 0.26s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .category-slider-item:hover .category-slider-icon-box {
            transform: scale(1.08) rotate(-4deg);
        }
        .service-card,
        .catalog-card,
        #fgServicesCta {
            transition: transform 0.26s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.26s ease;
        }
        .service-card:hover,
        .catalog-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
        }
        .fg-svc-reveal {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity 0.58s cubic-bezier(0.22, 1, 0.36, 1), transform 0.58s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .fg-svc-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        @keyframes fgSvcFadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fgSvcSearchPulse {
            0%, 100% { box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04); }
            50% { box-shadow: 0 8px 22px rgba(255, 198, 41, 0.18); }
        }
        @media (prefers-reduced-motion: reduce) {
            .fg-svc-banner, .search-field-group { animation: none !important; }
            .fg-svc-reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
            .fg-svc-banner-img-wrap img, .search-field-group, .fg-svc-search-icon, .category-slider-item, .category-slider-icon-box, .service-card, .catalog-card { transition: none !important; }
        }
    </style>
</head>
<body class="services-page-body">
    <a class="skip-link" href="#catalog">Skip to services</a>
    @include('shared.header')

    <main class="container catalog-section-wrapper" id="catalog">
        @if(!empty($isPreviewDraft))
            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 rounded-4 border-warning mb-4 py-2 px-3 small">
                <div><strong>Draft Preview Mode:</strong> Previewing unpublished Services page changes.</div>
                <a href="{{ route('admin.website-manager.services.index') }}" class="btn btn-sm btn-dark rounded-pill px-3">Back to Editor</a>
            </div>
        @endif

        @foreach($servicesSections as $sec)
            @if(empty($sec['visible']))
                @continue
            @endif

            @if($sec['key'] === 'banner')
                @php
                    $banner = $servicesPayload['banner'] ?? [];
                    $bannerBg = match ($banner['bg_preset'] ?? 'gold_soft') {
                        'cream' => 'background: #FFF8E5;',
                        'white' => 'background: #FFFFFF; border-color: #E2E8F0;',
                        default => 'background: linear-gradient(135deg, #FFFDF7 0%, #FFF3C4 55%, #FFE082 100%);',
                    };
                @endphp
                <section class="fg-svc-banner mb-4" id="fgServicesBanner" style="{{ $bannerBg }}" aria-label="Services Banner">
                    <div class="row align-items-center g-4">
                        <div class="col-12 col-lg-7 text-center text-lg-start">
                            @if(!empty($banner['badge']))
                                <span class="badge rounded-pill px-3 py-2 mb-3 fw-bold" style="background: rgba(255, 255, 255, 0.85); color: #171717; font-size: 0.74rem; letter-spacing: 0.04em;">
                                    {{ $banner['badge'] }}
                                </span>
                            @endif
                            <h1 class="h2 fw-bolder mb-2" style="color: #171717;">{{ $banner['headline'] ?? 'Professional Care for Every Garment' }}</h1>
                            @if(!empty($banner['description']))
                                <p class="small mb-3 mx-auto mx-lg-0" style="color: #334155; max-width: 560px; font-size: 0.95rem;">
                                    {{ $banner['description'] }}
                                </p>
                            @endif
                            <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-2">
                                @if(!empty($banner['primary_btn_visible']) && !empty($banner['primary_btn_label']))
                                    <a href="{{ $banner['primary_btn_url'] ?? route('booking.services') }}" class="btn btn-dark rounded-pill px-4 py-2 fw-bold shadow-sm">
                                        {{ $banner['primary_btn_label'] }}
                                    </a>
                                @endif
                                @if(!empty($banner['secondary_btn_visible']) && !empty($banner['secondary_btn_label']))
                                    <a href="{{ $banner['secondary_btn_url'] ?? '#catalog-results' }}" class="btn btn-outline-dark rounded-pill px-4 py-2 fw-semibold">
                                        {{ $banner['secondary_btn_label'] }}
                                    </a>
                                @endif
                            </div>
                        </div>
                        @if(!empty($banner['image_url']))
                            <div class="col-12 col-lg-5 text-center">
                                <div class="fg-svc-banner-img-wrap">
                                    <img src="{{ $svcCms->resolveImageUrl($banner['image_url']) }}" alt="{{ $banner['image_alt'] ?? 'FreshGo Services' }}">
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

            @elseif($sec['key'] === 'search')
                @php $searchCfg = $servicesPayload['search'] ?? []; @endphp
                @if(!empty($searchCfg['visible']))
                    <form class="service-filter-form-bar" id="service-filters" action="{{ route('services.index') }}" method="GET" role="search">
                        <input type="hidden" name="category" value="{{ $category?->slug ?? 'other-services' }}">
                        <input type="hidden" name="item" value="{{ $selectedItem }}">

                        <div class="search-field-group">
                            <svg class="fg-svc-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9E9E9E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <label class="visually-hidden" for="service-search">Search in this category</label>
                            <input type="search" class="search-field-input" id="service-search" name="q" placeholder="{{ $searchCfg['placeholder'] ?? 'Search services, categories, or laundry items...' }}" value="{{ $search }}" maxlength="100" autocomplete="off">
                            <kbd class="search-kbd-hint" aria-hidden="true">&crarr;</kbd>
                        </div>

                        @if(!empty($searchCfg['show_sort']))
                            <div class="sort-field-group">
                                <label for="service-sort" class="sort-field-label">Sort by:</label>
                                <select id="service-sort" name="sort" class="sort-field-select">
                                    <option value="name" @selected($sort === 'name')>Name: A–Z</option>
                                    <option value="price-low" @selected($sort === 'price-low')>Price: low to high</option>
                                    <option value="price-high" @selected($sort === 'price-high')>Price: high to low</option>
                                </select>
                            </div>
                        @endif
                        <button class="visually-hidden" type="submit">Apply filters</button>
                    </form>
                @endif

            @elseif($sec['key'] === 'categories')
                @php
                    $catCfg = $servicesPayload['categories_config'] ?? [];
                    $catOverrides = $catCfg['overrides'] ?? [];
                @endphp
                <section class="category-slider-wrapper mb-3" id="fgServicesCategories" aria-label="{{ $catCfg['title'] ?? 'Our Categories' }}">
                    <div class="category-slider-header">
                        <div>
                            <h3 class="category-slider-heading">{{ $catCfg['title'] ?? 'Our Categories' }}</h3>
                            @if(!empty($catCfg['subtitle']))
                                <p class="small text-muted mb-0">{{ $catCfg['subtitle'] }}</p>
                            @endif
                        </div>
                        <div class="category-slider-nav-controls">
                            <button type="button" class="category-slider-arrow" id="catSliderPrev" aria-label="Previous categories" disabled>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            </button>
                            <button type="button" class="category-slider-arrow" id="catSliderNext" aria-label="Next categories">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </button>
                        </div>
                    </div>

                    <div class="category-slider-track" id="categorySliderTrack" role="region" aria-label="Categories slider">
                        @foreach ($categories as $tab)
                            @php
                                $catOv = $catOverrides[$tab->id] ?? ($catOverrides[(string) $tab->id] ?? []);
                                $catImg = ! empty($catOv['custom_image_url'])
                                    ? $svcCms->resolveImageUrl($catOv['custom_image_url'])
                                    : ($tab->image_url ?: asset('images/laundry-icons/'.($categoryIcons[$tab->slug] ?? '03_laundry_basket.png')));
                            @endphp
                            <a class="category-slider-item {{ $category?->id === $tab->id ? 'is-active' : '' }}"
                               href="{{ route('services.index', ['category' => $tab->slug]) }}"
                               data-category="{{ $tab->slug }}"
                               @if ($category?->id === $tab->id) aria-current="page" @endif>
                                <div class="category-slider-icon-box">
                                    <img class="category-slider-img" src="{{ $catImg }}" alt="" loading="lazy">
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
                                    <img class="category-slider-img" src="{{ asset('images/laundry-icons/03_laundry_basket.png') }}" alt="">
                                </div>
                                <span class="category-slider-title">Other services</span>
                            </a>
                        @endif
                    </div>
                </section>

            @elseif($sec['key'] === 'featured' && $featuredServices->isNotEmpty())
                @php $featCfg = $servicesPayload['featured_config'] ?? []; @endphp
                <section class="mb-4" id="fgServicesFeatured" aria-label="{{ $featCfg['title'] ?? 'Featured Services' }}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h3 class="h5 fw-bold mb-0" style="color:#171717;">{{ $featCfg['title'] ?? 'Featured Services' }}</h3>
                            @if(!empty($featCfg['subtitle']))
                                <p class="small text-muted mb-0">{{ $featCfg['subtitle'] }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="row g-3">
                        @foreach($featuredServices as $fSvc)
                            <div class="col-12 col-sm-6 col-lg-{{ 12 / max(2, min(4, (int) ($featCfg['desktop_per_view'] ?? 4))) }}">
                                <div class="service-card h-100">
                                    <div>
                                        <div class="card-top">
                                            <span class="service-type">{{ $fSvc->category?->name ?? 'Featured' }}</span>
                                            <span class="card-spark text-warning">&#9733;</span>
                                        </div>
                                        <h4 class="service-card-name">{{ $fSvc->name }}</h4>
                                        @if($fSvc->description)
                                            <p class="service-description">{{ \Illuminate\Support\Str::limit($fSvc->description, 80) }}</p>
                                        @endif
                                    </div>
                                    <div class="card-bottom">
                                        <div class="service-price">
                                            <span class="curr-amount">{{ formatMoney($fSvc->price, $fSvc->currency_code ?? null) }}</span>
                                            @if($fSvc->unit)
                                                <span class="curr-label">/ {{ $fSvc->unit->short_name ?: $fSvc->unit->name }}</span>
                                            @endif
                                        </div>
                                        <a href="{{ route('booking.services', ['service_id' => $fSvc->id]) }}" class="btn-service-card-book text-decoration-none">Book &rarr;</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

            @elseif($sec['key'] === 'catalog')
                <div class="row g-4 align-items-start" id="catalog-results">
                    <section class="col-lg-9 order-1" aria-label="Find laundry services">
                        <div class="catalog-error-banner" id="request-error" role="alert" hidden>
                            <span>We couldn’t load the services. Please try again.</span>
                            <button type="button" id="retry-request" class="btn btn-sm btn-outline-danger rounded-pill px-3">Try again &#8635;</button>
                        </div>

                        <p class="visually-hidden" id="catalog-status" role="status" aria-live="polite"></p>

                        <div class="results-container" id="results-container" aria-busy="false">
                            <div id="service-results">
                                @include('services.results')
                            </div>
                        </div>

                        <p class="text-muted small mt-4">
                            <span class="text-warning fw-bold">&#9432;</span> All prices are shown in {{ currencyCode() }} ({{ currencySymbol() }}). Billing unit is clearly indicated with each service.
                        </p>
                    </section>

                    <aside class="col-lg-3 order-2 order-lg-2" aria-label="Selected category booking">
                        <div class="services-right-sidebar">
                            <div id="category-summary">
                                @include('services.category-summary')
                            </div>
                            <div class="sidebar-care-box mt-3 d-none d-lg-block">
                                <h4>Good Care. Longer Wear.</h4>
                                <p>A little professional care keeps your favourite garments in daily rotation longer.</p>
                            </div>
                        </div>
                    </aside>
                </div>

            @elseif($sec['key'] === 'cta')
                @php
                    $cta = $servicesPayload['cta'] ?? [];
                    $ctaPreset = $cta['bg_preset'] ?? 'gold_gradient';
                    $ctaStyle = match ($ctaPreset) {
                        'cream' => 'background: #FFF8E5; border: 1px solid #FDE68A; border-radius: 24px;',
                        'dark_gold' => 'background: #171717; color: #FFFFFF; border-radius: 24px;',
                        default => 'background: linear-gradient(135deg, #FFE88D 0%, #FFC83B 55%, #FFBE26 100%); border-radius: 24px; box-shadow: 0 12px 28px rgba(255, 198, 41, 0.22);',
                    };
                    $isDarkCta = $ctaPreset === 'dark_gold';
                @endphp
                <section class="mt-5 mb-2 p-4 p-md-5 d-flex flex-wrap align-items-center justify-content-between gap-3" id="fgServicesCta" style="{{ $ctaStyle }}" aria-label="Book Collection">
                    <div>
                        <h2 class="h4 fw-bolder mb-1 {{ $isDarkCta ? 'text-white' : 'text-dark' }}">{{ $cta['headline'] ?? 'Ready for Fresh, Clean Clothes?' }}</h2>
                        @if(!empty($cta['description']))
                            <p class="small mb-0 {{ $isDarkCta ? 'text-light' : 'text-dark' }} opacity-75">{{ $cta['description'] }}</p>
                        @endif
                    </div>
                    @if(!empty($cta['button_visible']) && !empty($cta['button_label']))
                        <a href="{{ $cta['button_url'] ?? route('booking.services') }}" class="btn {{ $isDarkCta ? 'btn-warning text-dark' : 'btn-dark text-white' }} rounded-pill px-4 py-2 fw-bold shadow-sm">
                            {{ $cta['button_label'] }}
                        </a>
                    @endif
                </section>
            @endif
        @endforeach
    </main>

    @include('shared.footer')
    @include('shared.bottom-nav')
    @include('shared.offcanvas-nav')

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
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sections = document.querySelectorAll('#catalog > section:not(#fgServicesBanner)');
            if (sections.length) {
                if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    sections.forEach(sec => sec.classList.add('is-visible'));
                } else {
                    sections.forEach(sec => sec.classList.add('fg-svc-reveal'));
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('is-visible');
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.06, rootMargin: '0px 0px -24px 0px' });
                    sections.forEach(sec => observer.observe(sec));
                }
            }

            const input = document.getElementById('service-search');
            if (!input || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            const fullText = (input.getAttribute('placeholder') || '').trim();
            const chars = Array.from(fullText);
            if (chars.length <= 1) return;

            let charIdx = 0;
            let timer = null;
            let isPaused = false;

            function tick() {
                if (isPaused || document.activeElement === input || input.value.trim() !== '') {
                    return;
                }
                charIdx += 1;
                if (charIdx <= chars.length) {
                    input.classList.remove('is-ph-fading');
                    input.setAttribute('placeholder', chars.slice(0, charIdx).join(''));
                    const delay = charIdx === chars.length ? 1800 : 48;
                    timer = setTimeout(tick, delay);
                } else {
                    input.classList.add('is-ph-fading');
                    timer = setTimeout(() => {
                        if (isPaused || document.activeElement === input || input.value.trim() !== '') return;
                        charIdx = 1;
                        input.setAttribute('placeholder', chars[0]);
                        input.classList.remove('is-ph-fading');
                        timer = setTimeout(tick, 48);
                    }, 280);
                }
            }

            input.addEventListener('focus', () => {
                isPaused = true;
                clearTimeout(timer);
                input.classList.remove('is-ph-fading');
                input.setAttribute('placeholder', fullText);
            });

            input.addEventListener('blur', () => {
                if (input.value.trim() === '') {
                    isPaused = false;
                    charIdx = 0;
                    clearTimeout(timer);
                    timer = setTimeout(tick, 350);
                }
            });

            if (input.value.trim() === '') {
                input.setAttribute('placeholder', chars[0]);
                charIdx = 1;
                timer = setTimeout(tick, 120);
            }
        });
    </script>
</body>
</html>
