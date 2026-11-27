@php
    $seo = $homepagePayload['seo'] ?? [];
    $siteName = class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getSiteName() : 'FreshGo Laundry';
    $metaTitle = ! empty($seo['meta_title']) ? $seo['meta_title'] : ($siteName . ' - Professional Laundry & Dry Cleaning');
    $metaDesc = $seo['meta_description'] ?? '';
    $ogTitle = ! empty($seo['og_title']) ? $seo['og_title'] : $metaTitle;
    $ogDesc = ! empty($seo['og_description']) ? $seo['og_description'] : $metaDesc;
    $ogImage = ! empty($seo['og_image_url']) ? $cms->resolveImageUrl($seo['og_image_url']) : '';
    $robots = $seo['robots'] ?? 'index, follow';
    $canonical = $seo['canonical_url'] ?? '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $metaTitle }}</title>
    @if($metaDesc !== '')
        <meta name="description" content="{{ $metaDesc }}">
    @endif
    <meta name="robots" content="{{ $robots }}">
    @if($canonical !== '')
        <link rel="canonical" href="{{ $canonical }}">
    @endif
    <meta property="og:title" content="{{ $ogTitle }}">
    @if($ogDesc !== '')
        <meta property="og:description" content="{{ $ogDesc }}">
    @endif
    @if($ogImage !== '')
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
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
            position: relative;
            z-index: 40;
            background: radial-gradient(circle at 12% 20%, #FFFDF5 0%, #FAF8F2 92%);
            border-radius: 24px;
            padding: 28px 20px;
            border: 1px solid #F1ECE0;
        }
        .fg-home-container > section:not(.fg-hero-section) {
            position: relative;
            z-index: 1;
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
            z-index: 50;
        }
        .fg-search-bar {
            position: relative;
            z-index: 2;
            background: #FFFFFF;
            border: 1.5px solid #E2E8F0;
            border-radius: 16px;
            min-height: 48px;
            padding: 5px 7px 5px 16px;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: border-color 0.38s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.38s cubic-bezier(0.22, 1, 0.36, 1), transform 0.38s cubic-bezier(0.22, 1, 0.36, 1);
            animation: fgSearchPulse 4.8s ease-in-out infinite;
        }
        .fg-search-bar:hover {
            border-color: #FCD34D;
            transform: translateY(-1px);
        }
        .fg-search-bar:focus-within {
            border-color: #FFC629;
            box-shadow: 0 12px 28px rgba(255, 198, 41, 0.24), 0 0 0 4px rgba(255, 198, 41, 0.12);
            transform: translateY(-2px);
            animation: none;
        }
        .fg-search-input {
            border: 0;
            outline: none;
            width: 100%;
            min-height: 36px;
            padding: 4px 0;
            font-size: 0.95rem;
            color: #171717;
            background: transparent;
            min-width: 0;
        }
        .fg-search-input::placeholder {
            color: #94A3B8;
            opacity: 1;
            transition: opacity 0.28s ease;
        }
        .fg-search-input.is-ph-fading::placeholder {
            opacity: 0;
        }
        .fg-search-icon {
            flex-shrink: 0;
            transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1), stroke 0.25s ease;
        }
        .fg-search-bar:focus-within .fg-search-icon {
            stroke: #D97706;
            transform: scale(1.15) rotate(-8deg);
        }
        #fgHomeSearchBtn {
            position: relative;
            overflow: hidden;
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem 1.25rem !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        #fgHomeSearchBtn::after {
            content: "";
            position: absolute;
            top: 0;
            left: -120%;
            width: 60%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.45), transparent);
            transform: skewX(-20deg);
            animation: fgBtnShine 4.5s ease-in-out infinite;
        }
        #fgHomeSearchBtn:hover {
            transform: translateY(-1px) scale(1.03);
            box-shadow: 0 8px 18px rgba(255, 198, 41, 0.36) !important;
        }
        @media (max-width: 575.98px) {
            #fgHomeSearchBtn {
                min-height: 34px;
                padding: 0.34rem 0.78rem !important;
                font-size: 0.75rem !important;
                line-height: 1.2;
            }
        }
        .fg-search-dropdown:not(.d-none) {
            animation: fgSearchDropIn 0.22s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes fgSearchPulse {
            0%, 100% { box-shadow: 0 6px 20px rgba(15, 23, 42, 0.05); }
            50% { box-shadow: 0 8px 24px rgba(255, 198, 41, 0.18); }
        }
        @keyframes fgBtnShine {
            0%, 65% { left: -120%; }
            100% { left: 160%; }
        }
        @keyframes fgSearchDropIn {
            from { opacity: 0; transform: translateY(-6px) scale(0.99); }
            to { opacity: 1; transform: translateY(0) scale(1); }
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
            top: calc(100% + 10px);
            left: 0;
            right: 0;
            z-index: 60;
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
            transition: transform 0.22s ease, background-color 0.22s ease;
        }
        /* Homepage Animations */
        .fg-hero-section {
            animation: fgHomeFadeUp 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .fg-hero-media img {
            transition: transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .fg-hero-media:hover img {
            transform: scale(1.03);
        }
        .fg-hero-delivered-badge {
            animation: fgHeroFloat 3.6s ease-in-out infinite;
        }
        .fg-search-bar:focus-within {
            transform: translateY(-2px);
        }
        .fg-cat-card:hover .fg-cat-icon {
            transform: scale(1.08) rotate(-4deg);
            box-shadow: 0 8px 18px rgba(255, 198, 41, 0.35);
        }
        .fg-svc-media img {
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .fg-svc-card:hover .fg-svc-media img {
            transform: scale(1.07);
        }
        .fg-svc-card:hover .fg-svc-arrow {
            transform: translateX(3px) scale(1.08);
            background: #F5B800;
        }
        .fg-home-reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s cubic-bezier(0.22, 1, 0.36, 1), transform 0.6s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .fg-home-reveal.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        @keyframes fgHomeFadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fgHeroFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        @media (prefers-reduced-motion: reduce) {
            .fg-hero-section, .fg-hero-delivered-badge, .fg-search-bar, #fgHomeSearchBtn::after, .fg-search-dropdown:not(.d-none) { animation: none !important; }
            .fg-home-reveal { opacity: 1 !important; transform: none !important; transition: none !important; }
            .fg-hero-media img, .fg-svc-media img, .fg-svc-arrow, .fg-search-bar, .fg-search-icon, #fgHomeSearchBtn { transition: none !important; }
        }
    </style>
</head>
<body class="fg-home-body">
    @include('shared.header')

    <main class="fg-home-main">
        <div class="fg-home-container">
            @if(!empty($isPreviewDraft))
                <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 rounded-4 border-warning mb-4 py-2 px-3 small">
                    <div>
                        <strong>Draft Preview Mode:</strong> You are previewing unpublished homepage changes. Public visitors still see the published version.
                    </div>
                    <a href="{{ route('admin.website-manager.homepage.index') }}" class="btn btn-sm btn-dark rounded-pill px-3">Back to Editor</a>
                </div>
            @endif

            @foreach($homepageSections as $sec)
                @if(empty($sec['visible']))
                    @continue
                @endif

                @if($sec['key'] === 'hero')
                    @php
                        $hero = $homepagePayload['hero'] ?? [];
                        $searchCfg = $homepagePayload['search'] ?? [];
                        $heroBg = match ($hero['bg_preset'] ?? 'cream') {
                            'white' => '#FFFFFF',
                            'soft_yellow' => 'linear-gradient(135deg, #FFFDF7 0%, #FFF3C4 100%)',
                            default => '',
                        };
                    @endphp
                    <section class="fg-hero-section mb-5" id="about" aria-label="Fresh Go Laundry Hero" @if($heroBg !== '') style="background: {{ $heroBg }};" @endif>
                        <div class="row align-items-center g-4 g-lg-5">
                            <div class="col-12 col-lg-6">
                                @if(!empty($hero['badge']))
                                    <span class="fg-hero-kicker mb-3">{{ $hero['badge'] }}</span>
                                @endif
                                <h1 class="fg-hero-title mb-3">
                                    {!! nl2br(e($hero['headline'] ?? "Fresh Clothes.\nHappier You.")) !!}
                                    @if(!empty($hero['show_heart_icon']))
                                        <svg class="hero-heart-icon" viewBox="0 0 24 24" style="width: 30px; height: 30px; vertical-align: middle;" aria-hidden="true">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="none" stroke="#FFC629" stroke-width="2.2"></path>
                                        </svg>
                                    @endif
                                </h1>
                                @if(!empty($hero['subtitle']))
                                    <p class="fg-hero-subtitle mb-4">{{ $hero['subtitle'] }}</p>
                                @endif

                                @if(!empty($searchCfg['visible']))
                                    <div class="fg-search-shell mb-4" id="fgHomeSearchWrap" style="max-width: 560px;">
                                        <div class="fg-search-bar">
                                            @if(!empty($searchCfg['show_icon']))
                                                <svg class="fg-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9E9E9E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <circle cx="11" cy="11" r="8"></circle>
                                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                                </svg>
                                            @endif
                                            <input
                                                type="text"
                                                id="fgHomeSearchInput"
                                                class="fg-search-input"
                                                placeholder="{{ $searchCfg['placeholder'] ?? 'Search services, categories, or laundry items...' }}"
                                                autocomplete="off"
                                                aria-label="{{ $searchCfg['placeholder'] ?? 'Search services, categories, or laundry items' }}"
                                                aria-expanded="false"
                                                aria-controls="fgHomeSearchDropdown"
                                                data-search-api="{{ route('home.search') }}"
                                                data-catalog-url="{{ route('services.index') }}"
                                            >
                                            <span id="fgHomeSearchSpinner" class="spinner-border spinner-border-sm text-warning d-none" role="status" aria-hidden="true"></span>
                                            <button type="button" id="fgHomeSearchClear" class="btn btn-sm btn-link text-muted text-decoration-none d-none px-2" aria-label="Clear search">&times;</button>
                                            <a href="{{ route('services.index') }}" id="fgHomeSearchBtn" class="btn rounded-pill px-4 py-2 fw-bold text-dark flex-shrink-0 shadow-sm" style="background-color: #FFC629;">
                                                {{ $searchCfg['button_text'] ?? 'Search' }}
                                            </a>
                                        </div>
                                        <div id="fgHomeSearchDropdown" class="fg-search-dropdown d-none" role="listbox" aria-label="Search suggestions"></div>
                                    </div>
                                @endif

                                @if(!empty($hero['highlights']))
                                    <div class="d-flex flex-nowrap align-items-center justify-content-between justify-content-sm-start gap-2 gap-sm-4 mb-3">
                                        @foreach($hero['highlights'] as $hlIdx => $hl)
                                            @if($hlIdx > 0)<div class="vr flex-shrink-0"></div>@endif
                                            <div class="min-w-0">
                                                <div class="fw-bolder text-dark text-nowrap" style="font-size: clamp(0.95rem, 4vw, 1.25rem);">{{ $hl['value'] ?? '' }}</div>
                                                <div class="text-muted text-nowrap" style="font-size: clamp(0.66rem, 2.7vw, 0.85rem);">{{ $hl['label'] ?? '' }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="d-flex flex-nowrap align-items-center gap-2 pt-1">
                                    @if(!empty($hero['primary_btn_visible']) && !empty($hero['primary_btn_label']))
                                        <a href="{{ $hero['primary_btn_url'] ?? route('booking.services') }}" class="btn btn-dark rounded-pill px-3 px-sm-4 py-2 fw-bold shadow-sm text-nowrap flex-grow-1 flex-sm-grow-0 text-center" style="font-size: clamp(0.76rem, 3.2vw, 0.92rem);">
                                            {{ $hero['primary_btn_label'] }}
                                        </a>
                                    @endif
                                    @if(!empty($hero['secondary_btn_visible']) && !empty($hero['secondary_btn_label']))
                                        <a href="{{ $hero['secondary_btn_url'] ?? route('services.index') }}" class="btn btn-outline-dark rounded-pill px-3 px-sm-4 py-2 fw-semibold text-nowrap flex-grow-1 flex-sm-grow-0 text-center" style="font-size: clamp(0.76rem, 3.2vw, 0.92rem);">
                                            {{ $hero['secondary_btn_label'] }}
                                        </a>
                                    @endif
                                </div>
                            </div>

                            @if(!empty($hero['image_url']))
                                <div class="col-12 col-lg-6 text-center">
                                    <div class="fg-hero-media">
                                        <img src="{{ $cms->resolveImageUrl($hero['image_url']) }}" alt="{{ $hero['image_alt'] ?? 'Freshgo Laundry Delivery' }}">
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>

                @elseif($sec['key'] === 'categories')
                    @php
                        $catCfg = $homepagePayload['categories_config'] ?? [];
                        $catCarouselEnabled = !empty($catCfg['carousel_enabled']);
                    @endphp
                    <section class="mb-5" id="fgCategorySection" aria-label="{{ $catCfg['title'] ?? 'Our Laundry Categories' }}">
                        <div class="d-flex justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                            <div class="min-w-0">
                                <h2 class="h4 fw-bold mb-0" style="color: #171717;">{{ $catCfg['title'] ?? 'Our Laundry Categories' }}</h2>
                                @if(!empty($catCfg['subtitle']))
                                    <p class="small mb-0" style="color: #64748B;">{{ $catCfg['subtitle'] }}</p>
                                @endif
                            </div>
                            @if($catCarouselEnabled && !empty($catCfg['show_arrows']))
                                <div class="d-flex align-items-center gap-2 flex-shrink-0" id="fgCategoryControls">
                                    <button type="button" class="fg-carousel-nav-btn js-carousel-prev" aria-label="Previous categories" title="Previous">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                    </button>
                                    <button type="button" class="fg-carousel-nav-btn js-carousel-next" aria-label="Next categories" title="Next">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if ($categories->isEmpty())
                            <div class="p-4 bg-white rounded-4 border text-center small text-muted">
                                No active laundry categories are currently available.
                            </div>
                        @elseif($catCarouselEnabled)
                            <div class="fg-carousel-viewport"
                                 id="fgCategoryCarousel"
                                 data-interval="{{ (int) ($catCfg['interval_ms'] ?? 4000) }}"
                                 data-autoplay="{{ !empty($catCfg['autoplay']) ? '1' : '0' }}"
                                 data-pause-hover="{{ !empty($catCfg['pause_on_hover']) ? '1' : '0' }}"
                                 data-loop="{{ !empty($catCfg['loop']) ? '1' : '0' }}"
                                 data-mobile-per-view="2"
                                 tabindex="0">
                                <div class="fg-carousel-track">
                                    @foreach ($categories as $cat)
                                        <div class="fg-carousel-slide">
                                            <a href="{{ route('services.index', ['category' => $cat['slug']]) }}" class="fg-cat-card">
                                                <div class="fg-cat-icon">
                                                    <img src="{{ $cat['image_url'] }}" alt="{{ $cat['name'] }}" loading="lazy">
                                                </div>
                                                <h3 class="h6 fw-bold mb-0 mb-md-1" style="color: #171717;">{{ $cat['name'] }}</h3>
                                                <p class="small mb-0 d-none d-md-block" style="color: #64748B;">
                                                    {{ \Illuminate\Support\Str::limit($cat['description'], 88) }}
                                                </p>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="row g-3">
                                @foreach ($categories as $cat)
                                    <div class="col-6 col-md-4 col-xl-{{ 12 / max(2, min(4, (int) ($catCfg['desktop_per_view'] ?? 4))) }}">
                                        <a href="{{ route('services.index', ['category' => $cat['slug']]) }}" class="fg-cat-card">
                                            <div class="fg-cat-icon">
                                                <img src="{{ $cat['image_url'] }}" alt="{{ $cat['name'] }}" loading="lazy">
                                            </div>
                                            <h3 class="h6 fw-bold mb-0 mb-md-1" style="color: #171717;">{{ $cat['name'] }}</h3>
                                            <p class="small mb-0 d-none d-md-block" style="color: #64748B;">
                                                {{ \Illuminate\Support\Str::limit($cat['description'], 88) }}
                                            </p>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                @elseif($sec['key'] === 'promo_banner')
                    @php
                        $promo = $homepagePayload['promo_banner'] ?? [];
                        $promoPreset = $promo['bg_preset'] ?? 'gold_gradient';
                        $promoStyle = match ($promoPreset) {
                            'cream' => 'background: #FFF8E5; border: 1px solid #FDE68A; border-radius: 28px;',
                            'dark_gold' => 'background: #171717; color: #FFFFFF; border-radius: 28px;',
                            default => 'background: linear-gradient(135deg, #FFE88D 0%, #FFC83B 55%, #FFBE26 100%); border-radius: 28px; box-shadow: 0 14px 32px rgba(255, 198, 41, 0.24);',
                        };
                        $isDarkPromo = $promoPreset === 'dark_gold';
                    @endphp
                    <section class="mb-5" id="how-it-works" aria-label="Fresh Go Promotional Banner">
                        <div class="p-4 p-md-5" style="{{ $promoStyle }}">
                            <div class="row align-items-center g-4">
                                <div class="col-12 col-md-8 text-center text-md-start">
                                    @if(!empty($promo['badge']))
                                        <span class="badge rounded-pill px-3 py-2 mb-3 fw-bold" style="background: {{ $isDarkPromo ? '#FFC629' : 'rgba(255, 255, 255, 0.72)' }}; color: #171717; font-size: 0.78rem;">
                                            {{ $promo['badge'] }}
                                        </span>
                                    @endif
                                    <h2 class="display-6 fw-bolder {{ $isDarkPromo ? 'text-white' : 'text-dark' }} mb-2">{{ $promo['headline'] ?? 'We Collect. We Clean. We Deliver.' }}</h2>
                                    @if(!empty($promo['description']))
                                        <p class="fs-6 {{ $isDarkPromo ? 'text-light' : 'text-dark' }} mb-4 opacity-75 mx-auto mx-md-0" style="max-width: 560px;">
                                            {{ $promo['description'] }}
                                        </p>
                                    @endif
                                    @if(!empty($promo['button_visible']) && !empty($promo['button_label']))
                                        <div class="d-flex justify-content-center justify-content-md-start">
                                            <a href="{{ $promo['button_url'] ?? route('booking.services') }}" class="btn {{ $isDarkPromo ? 'btn-warning text-dark' : 'btn-dark text-white' }} rounded-pill px-4 py-2 fw-bold shadow-sm">
                                                {{ $promo['button_label'] }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                                @if(!empty($promo['image_url']))
                                    <div class="d-none d-md-block col-md-4 text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-4 p-3 shadow-sm">
                                            <img src="{{ $cms->resolveImageUrl($promo['image_url']) }}"
                                                 alt="{{ $promo['image_alt'] ?? 'FreshGo Express Care' }}"
                                                 class="img-fluid rounded-3"
                                                 style="max-height: 150px; object-fit: contain;">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>

                @elseif($sec['key'] === 'services')
                    @php
                        $svcCfg = $homepagePayload['services_config'] ?? [];
                        $svcCarouselEnabled = !empty($svcCfg['carousel_enabled']);
                    @endphp
                    <section class="mb-4" id="fgServiceSection" aria-label="{{ $svcCfg['title'] ?? 'Our Services' }}">
                        <div class="d-flex justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                            <div class="min-w-0">
                                <h2 class="h4 fw-bold mb-0" style="color: #171717;">{{ $svcCfg['title'] ?? 'Our Services' }}</h2>
                                @if(!empty($svcCfg['subtitle']))
                                    <p class="small mb-0" style="color: #64748B;">{{ $svcCfg['subtitle'] }}</p>
                                @endif
                                @if(!empty($svcCfg['catalog_link_text']))
                                    <a href="{{ route('services.index') }}" class="small fw-bold text-decoration-none d-inline-block d-md-none mt-1" style="color: #B45309;">
                                        {{ $svcCfg['catalog_link_text'] }}
                                    </a>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                @if(!empty($svcCfg['catalog_link_text']))
                                    <a href="{{ route('services.index') }}" class="small fw-bold text-decoration-none me-2 d-none d-md-inline-block" style="color: #B45309;">
                                        {{ $svcCfg['catalog_link_text'] }}
                                    </a>
                                @endif
                                @if($svcCarouselEnabled && !empty($svcCfg['show_arrows']))
                                    <div class="d-flex align-items-center gap-2" id="fgServiceControls">
                                        <button type="button" class="fg-carousel-nav-btn js-carousel-prev" aria-label="Previous services" title="Previous">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                        </button>
                                        <button type="button" class="fg-carousel-nav-btn js-carousel-next" aria-label="Next services" title="Next">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if ($services->isEmpty())
                            <div class="p-4 bg-white rounded-4 border text-center small text-muted">
                                No active services are currently available.
                            </div>
                        @elseif($svcCarouselEnabled)
                            <div class="fg-carousel-viewport"
                                 id="fgServiceCarousel"
                                 data-interval="{{ (int) ($svcCfg['interval_ms'] ?? 4600) }}"
                                 data-autoplay="{{ !empty($svcCfg['autoplay']) ? '1' : '0' }}"
                                 data-pause-hover="{{ !empty($svcCfg['pause_on_hover']) ? '1' : '0' }}"
                                 data-loop="{{ !empty($svcCfg['loop']) ? '1' : '0' }}"
                                 tabindex="0">
                                <div class="fg-carousel-track">
                                    @foreach ($services as $item)
                                        @php $svc = $item['model']; @endphp
                                        <div class="fg-carousel-slide">
                                            <a href="{{ $item['url'] }}" class="fg-svc-card">
                                                <div class="fg-svc-media">
                                                    <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="{{ $item['is_photo'] ? 'fg-svc-photo' : 'fg-svc-icon' }}" loading="lazy">
                                                </div>
                                                <div class="flex-grow-1 text-center text-md-start">
                                                    @if ($item['badge'])
                                                        <span class="badge rounded-pill mb-2" style="background-color: #FFF8E5; color: #92400E;">
                                                            {{ $item['badge'] }}
                                                        </span>
                                                    @endif
                                                    <h3 class="h6 fw-bold mb-1" style="color: #171717;">{{ $item['name'] }}</h3>
                                                    <p class="small mb-0" style="color: #64748B;">
                                                        {{ \Illuminate\Support\Str::limit($item['description'], 78) }}
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
                        @else
                            <div class="row g-3">
                                @foreach ($services as $item)
                                    @php $svc = $item['model']; @endphp
                                    <div class="col-12 col-sm-6 col-xl-{{ 12 / max(2, min(4, (int) ($svcCfg['desktop_per_view'] ?? 4))) }}">
                                        <a href="{{ $item['url'] }}" class="fg-svc-card">
                                            <div class="fg-svc-media">
                                                <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="{{ $item['is_photo'] ? 'fg-svc-photo' : 'fg-svc-icon' }}" loading="lazy">
                                            </div>
                                            <div class="flex-grow-1 text-center text-md-start">
                                                @if ($item['badge'])
                                                    <span class="badge rounded-pill mb-2" style="background-color: #FFF8E5; color: #92400E;">
                                                        {{ $item['badge'] }}
                                                    </span>
                                                @endif
                                                <h3 class="h6 fw-bold mb-1" style="color: #171717;">{{ $item['name'] }}</h3>
                                                <p class="small mb-0" style="color: #64748B;">
                                                    {{ \Illuminate\Support\Str::limit($item['description'], 78) }}
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
                        @endif
                    </section>
                @endif
            @endforeach
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sections = document.querySelectorAll('.fg-home-container > section:not(.fg-hero-section)');
            if (!sections.length) return;

            if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                sections.forEach(sec => sec.classList.add('is-visible'));
                return;
            }

            sections.forEach(sec => sec.classList.add('fg-home-reveal'));
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });

            sections.forEach(sec => observer.observe(sec));
        });

        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('fgHomeSearchInput');
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

            input.setAttribute('placeholder', chars[0]);
            charIdx = 1;
            timer = setTimeout(tick, 120);
        });
    </script>
</body>
</html>
