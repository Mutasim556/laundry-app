@php
    $seo = $payload['seo'] ?? [];
    $siteName = class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getSiteName() : 'FreshGo Laundry';
    $metaTitle = ! empty($seo['meta_title']) ? $seo['meta_title'] : ('About Us | ' . $siteName);
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
    @if($metaDesc !== '')<meta name="description" content="{{ $metaDesc }}">@endif
    <meta name="robots" content="{{ $robots }}">
    @if($canonical !== '')<link rel="canonical" href="{{ $canonical }}">@endif
    <meta property="og:title" content="{{ $ogTitle }}">
    @if($ogDesc !== '')<meta property="og:description" content="{{ $ogDesc }}">@endif
    @if($ogImage !== '')<meta property="og:image" content="{{ $ogImage }}">@endif

    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}?v={{ filemtime(public_path('css/laundry-home.css')) }}">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #F7F6F2; color: #171717; overflow-x: hidden; }
        .fg-about-main { padding: 20px 12px calc(92px + env(safe-area-inset-bottom, 0px)); }
        @media (min-width: 768px) { .fg-about-main { padding: 28px 24px 72px; } }
        .fg-about-card { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 22px; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04); }
        .fg-about-hero { border-radius: 26px; border: 1px solid #FDE68A; box-shadow: 0 14px 32px rgba(255, 198, 41, 0.16); }
        .fg-about-img-wrap { border-radius: 20px; overflow: hidden; box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12); max-height: 290px; }
        .fg-about-img-wrap img { width: 100%; max-height: 290px; object-fit: cover; display: block; }
        .fg-step-badge { width: 42px; height: 42px; border-radius: 12px; background: #FFC629; color: #171717; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    @include('shared.header')

    <main class="container fg-about-main" style="max-width: 1240px;">
        @if(!empty($isPreviewDraft))
            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 rounded-4 border-warning mb-4 py-2 px-3 small">
                <div><strong>Draft Preview Mode:</strong> Previewing unpublished About Us page changes.</div>
                <a href="{{ route('admin.website-manager.about.index') }}" class="btn btn-sm btn-dark rounded-pill px-3">Back to Editor</a>
            </div>
        @endif

        @foreach($sections as $sec)
            @if(empty($sec['visible'])) @continue @endif

            @if($sec['key'] === 'hero')
                @php
                    $hero = $payload['hero'] ?? [];
                    $heroBg = match ($hero['bg_preset'] ?? 'gold_soft') {
                        'cream' => 'background: #FFF8E5;',
                        'white' => 'background: #FFFFFF; border-color: #E2E8F0;',
                        default => 'background: linear-gradient(135deg, #FFFDF7 0%, #FFF3C4 55%, #FFE082 100%);',
                    };
                @endphp
                <section class="fg-about-hero p-4 p-md-5 mb-5" id="fgAboutHero" style="{{ $heroBg }}">
                    <div class="row align-items-center g-4">
                        <div class="col-12 col-lg-7 text-center text-lg-start">
                            @if(!empty($hero['badge']))
                                <span class="badge rounded-pill px-3 py-2 mb-3 fw-bold" style="background: rgba(255,255,255,0.85); color:#171717; font-size:0.75rem;">
                                    {{ $hero['badge'] }}
                                </span>
                            @endif
                            <h1 class="display-6 fw-bolder mb-3" style="color:#171717;">{{ $hero['headline'] ?? 'Fresh Clothes. More Time for What Matters.' }}</h1>
                            @if(!empty($hero['description']))
                                <p class="fs-6 mb-4 mx-auto mx-lg-0" style="color:#334155; max-width: 580px;">{{ $hero['description'] }}</p>
                            @endif
                            <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-2">
                                @if(!empty($hero['primary_btn_visible']) && !empty($hero['primary_btn_label']))
                                    <a href="{{ $hero['primary_btn_url'] ?? route('booking.services') }}" class="btn btn-dark rounded-pill px-4 py-2 fw-bold shadow-sm">{{ $hero['primary_btn_label'] }}</a>
                                @endif
                                @if(!empty($hero['secondary_btn_visible']) && !empty($hero['secondary_btn_label']))
                                    <a href="{{ $hero['secondary_btn_url'] ?? route('services.index') }}" class="btn btn-outline-dark rounded-pill px-4 py-2 fw-semibold">{{ $hero['secondary_btn_label'] }}</a>
                                @endif
                            </div>
                        </div>
                        @if(!empty($hero['image_url']))
                            <div class="col-12 col-lg-5 text-center">
                                <div class="fg-about-img-wrap d-inline-block">
                                    <img src="{{ $cms->resolveImageUrl($hero['image_url']) }}" alt="{{ $hero['image_alt'] ?? 'Fresh Go Laundry Care' }}">
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

            @elseif($sec['key'] === 'story')
                @php $story = $payload['story'] ?? []; @endphp
                <section class="fg-about-card p-4 p-md-5 mb-5" id="fgAboutStory">
                    <div class="row align-items-center g-4">
                        <div class="col-12 {{ !empty($story['image_url']) ? 'col-lg-8' : '' }}">
                            @if(!empty($story['eyebrow']))
                                <span class="badge rounded-pill px-3 py-1 mb-2 fw-bold" style="background:#FFF8E5; color:#B45309;">{{ $story['eyebrow'] }}</span>
                            @endif
                            <h2 class="h3 fw-bold mb-3" style="color:#171717;">{{ $story['heading'] ?? 'Our Story' }}</h2>
                            <p class="mb-0" style="color:#475569; line-height:1.75; white-space: pre-line;">{{ $story['body'] ?? '' }}</p>
                        </div>
                        @if(!empty($story['image_url']))
                            <div class="col-12 col-lg-4 text-center">
                                <div class="d-inline-flex align-items-center justify-content-center p-4 rounded-4" style="background:#FFF8E5;">
                                    <img src="{{ $cms->resolveImageUrl($story['image_url']) }}" alt="{{ $story['image_alt'] ?? 'Our Story' }}" class="img-fluid rounded-3" style="max-height: 170px; object-fit: contain;">
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

            @elseif($sec['key'] === 'mission_vision')
                @php $mv = $payload['mission_vision'] ?? []; @endphp
                @if(!empty($mv['mission_visible']) || !empty($mv['vision_visible']))
                    <section class="mb-5" id="fgAboutMissionVision">
                        <div class="row g-3 g-md-4">
                            @if(!empty($mv['mission_visible']))
                                <div class="col-12 {{ !empty($mv['vision_visible']) ? 'col-md-6' : '' }}">
                                    <div class="fg-about-card p-4 h-100" style="border-top: 4px solid #3B82F6;">
                                        <span class="badge rounded-pill mb-2 bg-primary-subtle text-primary">Mission</span>
                                        <h3 class="h5 fw-bold mb-2">{{ $mv['mission_title'] ?? 'Our Mission' }}</h3>
                                        <p class="small mb-0 text-muted" style="line-height: 1.7;">{{ $mv['mission_description'] ?? '' }}</p>
                                    </div>
                                </div>
                            @endif
                            @if(!empty($mv['vision_visible']))
                                <div class="col-12 {{ !empty($mv['mission_visible']) ? 'col-md-6' : '' }}">
                                    <div class="fg-about-card p-4 h-100" style="border-top: 4px solid #8B5CF6;">
                                        <span class="badge rounded-pill mb-2" style="background:#F3E8FF; color:#6D28D9;">Vision</span>
                                        <h3 class="h5 fw-bold mb-2">{{ $mv['vision_title'] ?? 'Our Vision' }}</h3>
                                        <p class="small mb-0 text-muted" style="line-height: 1.7;">{{ $mv['vision_description'] ?? '' }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif

            @elseif($sec['key'] === 'benefits')
                @php
                    $benefits = $payload['benefits'] ?? [];
                    $benefitItems = collect($benefits['items'] ?? [])->where('visible', true)->sortBy('order')->values();
                    $accents = ['#FFC629', '#3B82F6', '#16A34A', '#8B5CF6'];
                @endphp
                @if($benefitItems->isNotEmpty())
                    <section class="mb-5" id="fgAboutBenefits">
                        <div class="text-center mb-4">
                            <h2 class="h3 fw-bold mb-1">{{ $benefits['title'] ?? 'Why Choose Fresh Go' }}</h2>
                            @if(!empty($benefits['subtitle']))
                                <p class="small text-muted mb-0">{{ $benefits['subtitle'] }}</p>
                            @endif
                        </div>
                        <div class="row g-3">
                            @foreach($benefitItems as $bIdx => $item)
                                @php $accent = $accents[$bIdx % count($accents)]; @endphp
                                <div class="col-12 col-sm-6 col-lg-3">
                                    <div class="fg-about-card p-4 h-100" style="border-top: 4px solid {{ $accent }};">
                                        <div class="fw-bold mb-2" style="color:#171717;">{{ $item['title'] ?? '' }}</div>
                                        <p class="small text-muted mb-0" style="line-height: 1.65;">{{ $item['description'] ?? '' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

            @elseif($sec['key'] === 'how_it_works')
                @php
                    $hiw = $payload['how_it_works'] ?? [];
                    $steps = collect($hiw['steps'] ?? [])->where('visible', true)->sortBy('order')->values();
                @endphp
                @if($steps->isNotEmpty())
                    <section class="mb-5" id="fgAboutHowItWorks">
                        <div class="text-center mb-4">
                            <h2 class="h3 fw-bold mb-1">{{ $hiw['title'] ?? 'How Fresh Go Works' }}</h2>
                            @if(!empty($hiw['subtitle']))
                                <p class="small text-muted mb-0">{{ $hiw['subtitle'] }}</p>
                            @endif
                        </div>
                        <div class="row g-3">
                            @foreach($steps as $sIdx => $step)
                                <div class="col-12 col-sm-6 col-lg-3">
                                    <div class="fg-about-card p-4 h-100 text-center text-md-start">
                                        <span class="fg-step-badge mb-3">{{ $step['step'] ?? str_pad((string) ($sIdx + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        <h3 class="h6 fw-bold mb-2">{{ $step['title'] ?? '' }}</h3>
                                        <p class="small text-muted mb-0">{{ $step['description'] ?? '' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

            @elseif($sec['key'] === 'stats')
                @php
                    $stats = $payload['stats'] ?? [];
                    $statItems = collect($stats['items'] ?? [])->filter(fn ($st) => !empty($st['value']) && !empty($st['label']))->values();
                @endphp
                @if($statItems->isNotEmpty())
                    <section class="fg-about-card p-4 mb-5" id="fgAboutStats" style="background: #FFFDF7; border-color: #FDE68A;">
                        @if(!empty($stats['title']))
                            <h2 class="h5 fw-bold text-center mb-3">{{ $stats['title'] }}</h2>
                        @endif
                        <div class="row g-3 text-center">
                            @foreach($statItems as $st)
                                <div class="col-6 col-md-3">
                                    <div class="fs-3 fw-bolder text-dark">{{ $st['value'] }}</div>
                                    <div class="small text-muted">{{ $st['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

            @elseif($sec['key'] === 'cta')
                @php
                    $cta = $payload['cta'] ?? [];
                    $ctaPreset = $cta['bg_preset'] ?? 'gold_gradient';
                    $ctaStyle = match ($ctaPreset) {
                        'cream' => 'background: #FFF8E5; border: 1px solid #FDE68A; border-radius: 26px;',
                        'dark_gold' => 'background: #171717; color: #FFFFFF; border-radius: 26px;',
                        default => 'background: linear-gradient(135deg, #FFE88D 0%, #FFC83B 55%, #FFBE26 100%); border-radius: 26px; box-shadow: 0 12px 28px rgba(255, 198, 41, 0.22);',
                    };
                    $isDarkCta = $ctaPreset === 'dark_gold';
                @endphp
                <section class="p-4 p-md-5 d-flex flex-wrap align-items-center justify-content-between gap-3" id="fgAboutCta" style="{{ $ctaStyle }}">
                    <div>
                        <h2 class="h4 fw-bolder mb-1 {{ $isDarkCta ? 'text-white' : 'text-dark' }}">{{ $cta['headline'] ?? 'Ready to Experience Fresh Go?' }}</h2>
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
    <div class="d-lg-none">@include('shared.bottom-nav')</div>
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
