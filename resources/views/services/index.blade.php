<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Explore Freshgo laundry services and compare prices in BDT.">
    <title>{{ $category?->name ?? 'Other services' }} | Freshgo Laundry</title>
    <link rel="stylesheet" href="{{ asset('css/services.css') }}">
    <script src="{{ asset('js/services.js') }}" defer></script>
</head>
<body>
    <a class="skip-link" href="#catalog">Skip to services</a>
    <header class="site-header"><div class="header-inner">
        <a class="brand" href="{{ url('/') }}" aria-label="Freshgo Laundry home"><img src="{{ asset('images/freshgo-new-logo.jpeg') }}" alt="Freshgo Laundry"></a>
        <nav aria-label="Main navigation"><a href="{{ url('/') }}">Home</a><a class="current" href="{{ route('services.index') }}" aria-current="page">Services & prices</a><a class="how-link" href="{{ url('/#how-it-works') }}">How it works <span aria-hidden="true">↗</span></a></nav>
    </div></header>
    <main class="page-shell">
        <section class="hero" aria-labelledby="page-title">
            <div class="hero-copy"><p class="eyebrow"><span class="status-dot"></span> A LITTLE CARE GOES A LONG WAY</p><h1 id="page-title">Fresh clothes.<br><span>One less thing to do.</span></h1><p class="hero-description">From everyday favourites to the pieces you love most.<br class="desktop-break"> Find the right care, at a clear price.</p><div class="hero-notes"><span><span aria-hidden="true">✓</span> Prices in BDT</span><span><span aria-hidden="true">✓</span> Care for every fabric</span></div></div>
            <div class="hero-art" aria-hidden="true"><div class="art-ring"></div><span class="spark spark-one">✦</span><span class="spark spark-two">✧</span><img src="{{ asset('images/laundry-icons/12_clean_shirt.png') }}" alt="" width="220" height="220"><div class="care-tag"><span>✦</span><div>A fresh start<small>for your wardrobe</small></div></div></div>
        </section>
        <div class="catalog-layout" id="catalog">
            <aside class="category-panel" aria-label="Service categories">
                <p class="eyebrow">FIND YOUR CARE</p><h2>Our services<span aria-hidden="true">↙</span></h2>
                @php
                    $categoryIcons = ['wash-fold' => '01_washing_machine.png', 'wash-iron' => '12_clean_shirt.png', 'dry-cleaning' => '06_shirt_hanger.png', 'ironing-only' => '05_iron.png', 'back-wash-iron' => '08_wash_tub.png', 'bulky-item-duvet' => '07_folded_laundry.png', 'repairs' => '13_repairs.svg'];
                @endphp
                <nav class="category-list" aria-label="Choose a service category">
                    @foreach ($categories as $tab)
                        <a class="category-link {{ $category?->id === $tab->id ? 'is-active' : '' }}" href="{{ route('services.index', ['category' => $tab->slug]) }}" data-category="{{ $tab->slug }}" @if ($category?->id === $tab->id) aria-current="page" @endif><img src="{{ $tab->image_url ?: asset('images/laundry-icons/'.($categoryIcons[$tab->slug] ?? '03_laundry_basket.png')) }}" alt="" width="34" height="34" loading="lazy"><span>{{ $tab->name }}</span><span class="category-arrow" aria-hidden="true">↗</span></a>
                    @endforeach
                    @if ($hasOtherServices)
                        <a class="category-link {{ ! $category ? 'is-active' : '' }}" href="{{ route('services.index', ['category' => 'other-services']) }}" data-category="other-services" @if (! $category) aria-current="page" @endif><img src="{{ asset('images/laundry-icons/03_laundry_basket.png') }}" alt="" width="34" height="34"><span>Other services</span><span class="category-arrow" aria-hidden="true">↗</span></a>
                    @endif
                </nav>
                <div class="care-note"><span aria-hidden="true">✳</span><h3>Good care. Longer wear.</h3><p>A little professional care helps keep your favourites in rotation.</p></div>
            </aside>
            <section class="catalog-main" aria-label="Find laundry services">
                <form class="filter-bar" id="service-filters" action="{{ route('services.index') }}" method="GET" role="search">
                    <input type="hidden" name="category" value="{{ $category?->slug ?? 'other-services' }}"><input type="hidden" name="item" value="{{ $selectedItem }}">
                    <div class="search-field"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg><label class="sr-only" for="service-search">Search in this category</label><input type="search" id="service-search" name="q" placeholder="Search services or items…" value="{{ $search }}" maxlength="100" autocomplete="off"><kbd aria-hidden="true">↵</kbd></div>
                    <div class="sort-field"><label for="service-sort">Sort by</label><select id="service-sort" name="sort"><option value="name" @selected($sort === 'name')>Name: A–Z</option><option value="price-low" @selected($sort === 'price-low')>Price: low to high</option><option value="price-high" @selected($sort === 'price-high')>Price: high to low</option></select></div>
                    <button class="sr-only focus-visible-button" type="submit">Apply filters</button>
                </form>
                <div class="request-error" id="request-error" role="alert" hidden><span>We couldn’t load the services. Please try again.</span><button type="button" id="retry-request">Try again ↻</button></div>
                <p class="sr-only" id="catalog-status" role="status" aria-live="polite"></p>
                <div class="results-container" id="results-container" aria-busy="false"><div id="service-results">@include('services.results')</div><div class="loading-panel" aria-hidden="true"><div class="skeleton skeleton-heading"></div><div class="skeleton skeleton-chips"></div><div class="skeleton-grid">@for ($i = 0; $i < 6; $i++)<div class="skeleton skeleton-card"></div>@endfor</div></div></div>
                <p class="pricing-note"><span aria-hidden="true">ⓘ</span> All prices are in Bangladeshi Taka (BDT). The billing unit is shown with each service.</p>
            </section>
        </div>
        <footer class="site-footer"><span>Freshgo Laundry</span><p>A fresh start for the things you wear.</p><a href="{{ url('/') }}">Back to home ↗</a></footer>
    </main>
</body>
</html>
