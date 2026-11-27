@php
    $seo = $payload['seo'] ?? [];
    $siteName = class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getSiteName() : 'FreshGo Laundry';
    $metaTitle = ! empty($seo['meta_title']) ? $seo['meta_title'] : ('Contact Us | ' . $siteName);
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
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        .fg-contact-main { padding: 20px 12px calc(92px + env(safe-area-inset-bottom, 0px)); }
        @media (min-width: 768px) { .fg-contact-main { padding: 28px 24px 72px; } }
        .fg-contact-card { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 22px; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04); }
        .fg-contact-hero { border-radius: 26px; border: 1px solid #FDE68A; box-shadow: 0 12px 28px rgba(255, 198, 41, 0.16); }
        .fg-info-pill { background: #FFF8E5; border: 1px solid #FDE68A; border-radius: 14px; padding: 0.85rem 1rem; transition: transform 0.22s ease, box-shadow 0.22s ease; }
        .fg-info-pill:hover { transform: translateX(4px); box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06); }
        .fg-info-pill:hover .fg-hour-icon { transform: scale(1.12) rotate(6deg); }
        .fg-sec-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            color: #FFFFFF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            animation: fgHoursFloat 3.2s ease-in-out infinite;
            flex-shrink: 0;
        }
        .fg-sec-icon--gold { background: linear-gradient(135deg, #F59E0B 0%, #F97316 100%); box-shadow: 0 8px 18px rgba(245, 158, 11, 0.28); }
        .fg-sec-icon--emerald { background: linear-gradient(135deg, #10B981 0%, #06B6D4 100%); box-shadow: 0 8px 18px rgba(16, 185, 129, 0.28); }
        .fg-sec-icon--blue { background: linear-gradient(135deg, #3B82F6 0%, #6366F1 100%); box-shadow: 0 8px 18px rgba(59, 130, 246, 0.28); }
        .fg-sec-icon--pink { background: linear-gradient(135deg, #EC4899 0%, #8B5CF6 100%); box-shadow: 0 8px 18px rgba(236, 72, 153, 0.28); }
        .fg-sec-icon--teal { background: linear-gradient(135deg, #14B8A6 0%, #3B82F6 100%); box-shadow: 0 8px 18px rgba(20, 184, 166, 0.28); }
        .fg-social-pill { transition: transform 0.22s ease, box-shadow 0.22s ease; border: 1px solid transparent; }
        .fg-social-pill:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(15, 23, 42, 0.1); }
        .fg-faq-item { border-radius: 14px; transition: transform 0.22s ease, box-shadow 0.22s ease; }
        .fg-faq-item:hover { transform: translateX(4px); box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06); }
        .fg-hours-header-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, #8B5CF6 0%, #EC4899 55%, #F59E0B 100%);
            color: #FFFFFF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 18px rgba(139, 92, 246, 0.28);
            animation: fgHoursFloat 3.2s ease-in-out infinite;
            flex-shrink: 0;
        }
        .fg-hours-clock-hand {
            transform-origin: 12px 12px;
            animation: fgClockTick 6s linear infinite;
        }
        .fg-hour-row {
            border-radius: 14px;
            padding: 0.62rem 0.9rem;
            border: 1px solid transparent;
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }
        .fg-hour-row:hover {
            transform: translateX(4px);
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06);
        }
        .fg-hour-row.is-today {
            box-shadow: 0 6px 18px rgba(139, 92, 246, 0.14);
            border-width: 1.5px;
        }
        .fg-hour-icon {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: transform 0.25s ease;
        }
        .fg-hour-row:hover .fg-hour-icon {
            transform: scale(1.12) rotate(6deg);
        }
        .fg-live-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            display: inline-block;
            animation: fgPulseDot 1.8s ease-in-out infinite;
        }
        @keyframes fgHoursFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }
        @keyframes fgClockTick {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes fgPulseDot {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.35); opacity: 0.55; }
        }
        @media (prefers-reduced-motion: reduce) {
            .fg-hours-header-icon, .fg-sec-icon, .fg-hours-clock-hand, .fg-live-dot { animation: none !important; }
        }
    </style>
</head>
<body>
    @include('shared.header')

    <main class="container fg-contact-main" style="max-width: 1240px;">
        @if(!empty($isPreviewDraft))
            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 rounded-4 border-warning mb-4 py-2 px-3 small">
                <div><strong>Draft Preview Mode:</strong> Previewing unpublished Contact Us page changes.</div>
                <a href="{{ route('admin.website-manager.contact.index') }}" class="btn btn-sm btn-dark rounded-pill px-3">Back to Editor</a>
            </div>
        @endif

        @php
            $visibleMap = collect($sections)->pluck('visible', 'key')->all();
            $info = $payload['contact_info'] ?? [];
            $form = $payload['form_config'] ?? [];
            $hours = $payload['business_hours'] ?? [];
            $map = $payload['map'] ?? [];

            $fullPhone = trim(($info['phone_country_code'] ?? '') . ' ' . ($info['phone_number'] ?? ''));
            $telHref = preg_replace('/[^0-9+]/', '', $fullPhone);
            $fullAddress = implode(', ', array_filter([
                trim((string) ($info['address'] ?? '')),
                trim((string) ($info['city'] ?? '')),
                trim((string) ($info['country'] ?? '')),
            ]));

            $embedUrl = trim((string) ($map['embed_url'] ?? ''));
            if ($embedUrl === '' && !empty($map['map_url'])) {
                $mu = (string) $map['map_url'];
                if (str_contains($mu, '/maps/embed') || str_contains($mu, 'output=embed')) {
                    $embedUrl = $mu;
                } elseif (preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $mu, $m)) {
                    $embedUrl = 'https://maps.google.com/maps?q=' . $m[1] . ',' . $m[2] . '&z=15&output=embed';
                } elseif (preg_match('#/maps/place/([^/?]+)#', $mu, $m)) {
                    $embedUrl = 'https://maps.google.com/maps?q=' . $m[1] . '&z=15&output=embed';
                }
            }
            if ($embedUrl === '' && !empty($map['public_address'])) {
                $embedUrl = 'https://maps.google.com/maps?q=' . rawurlencode((string) $map['public_address']) . '&z=15&output=embed';
            }

            $showContactSection = !empty($visibleMap['contact_and_form']);
            $hasMapContent = !empty($visibleMap['map']) && (!empty($map['public_address']) || !empty($map['map_url']) || $embedUrl !== '');
            $hasHoursContent = !empty($visibleMap['business_hours']) && !empty($hours);
            $renderedPairs = ['info_map' => false, 'form_hours' => false];
        @endphp

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
                <section class="fg-contact-hero p-4 p-md-5 mb-4 text-center text-md-start" id="fgContactHero" style="{{ $heroBg }}">
                    @if(!empty($hero['badge']))
                        <span class="badge rounded-pill px-3 py-2 mb-3 fw-bold d-inline-flex align-items-center gap-2" style="background: rgba(255,255,255,0.9); color:#171717; font-size:0.75rem; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.16);">
                            <span class="fg-live-dot" style="background:#F59E0B;"></span>
                            {{ $hero['badge'] }}
                        </span>
                    @endif
                    <div class="d-flex flex-column flex-md-row align-items-center gap-3">
                        <span class="fg-sec-icon fg-sec-icon--gold" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </span>
                        <div>
                            <h1 class="display-6 fw-bolder mb-1" style="color:#171717;">{{ $hero['headline'] ?? "We're Here to Help" }}</h1>
                            @if(!empty($hero['description']))
                                <p class="fs-6 mb-0" style="color:#334155; max-width: 620px;">{{ $hero['description'] }}</p>
                            @endif
                        </div>
                    </div>
                </section>

            @elseif(in_array($sec['key'], ['contact_and_form', 'map', 'business_hours'], true))
                {{-- Row 1: Contact Info + Map Load Section in a single row --}}
                @if(!$renderedPairs['info_map'] && ($showContactSection || $hasMapContent))
                    @php $renderedPairs['info_map'] = true; @endphp
                    <section class="mb-4" id="fgContactInfoMapRow">
                        <div class="row g-4 align-items-stretch">
                            @if($showContactSection)
                                <div class="col-12 {{ $hasMapContent ? 'col-lg-5' : '' }}">
                                    <div class="fg-contact-card p-4 h-100 d-flex flex-column justify-content-between" style="border-top: 4px solid #FFC629; background: linear-gradient(180deg, #FFFFFF 0%, #FFFDF5 100%);">
                                        <div>
                                            <div class="d-flex align-items-center gap-3 mb-2">
                                                <span class="fg-sec-icon fg-sec-icon--gold" aria-hidden="true">
                                                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                                                        <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                                                    </svg>
                                                </span>
                                                <h2 class="h4 fw-bold mb-0">{{ $info['brand_name'] ?? 'FreshGo Laundry' }}</h2>
                                            </div>
                                            @if(!empty($info['description']))
                                                <p class="small text-muted mb-4">{{ $info['description'] }}</p>
                                            @endif

                                            <div class="d-grid gap-3">
                                                @if(!empty($info['support_email']))
                                                    <div class="fg-info-pill d-flex align-items-center gap-3">
                                                        <span class="fg-hour-icon" style="background:#FEF3C7; color:#D97706;" aria-hidden="true">
                                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                                        </span>
                                                        <div>
                                                            <div class="small text-muted fw-semibold">Support Email</div>
                                                            <a href="mailto:{{ $info['support_email'] }}" class="fw-bold text-dark text-decoration-none">{{ $info['support_email'] }}</a>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($fullPhone !== '')
                                                    <div class="fg-info-pill d-flex align-items-center gap-3" style="background:#EFF6FF; border-color:#BFDBFE;">
                                                        <span class="fg-hour-icon" style="background:#DBEAFE; color:#2563EB;" aria-hidden="true">
                                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                                        </span>
                                                        <div>
                                                            <div class="small text-muted fw-semibold">Phone</div>
                                                            <a href="tel:{{ $telHref }}" class="fw-bold text-dark text-decoration-none">{{ $fullPhone }}</a>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if(!empty($info['whatsapp_number']))
                                                    <div class="fg-info-pill d-flex align-items-center gap-3" style="background:#ECFDF5; border-color:#A7F3D0;">
                                                        <span class="fg-hour-icon" style="background:#D1FAE5; color:#059669;" aria-hidden="true">
                                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                                        </span>
                                                        <div>
                                                            <div class="small text-muted fw-semibold">WhatsApp</div>
                                                            <span class="fw-bold text-dark">{{ $info['whatsapp_number'] }}</span>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($fullAddress !== '')
                                                    <div class="fg-info-pill d-flex align-items-center gap-3" style="background:#F5F3FF; border-color:#DDD6FE;">
                                                        <span class="fg-hour-icon" style="background:#EDE9FE; color:#7C3AED;" aria-hidden="true">
                                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                                        </span>
                                                        <div>
                                                            <div class="small text-muted fw-semibold">Address</div>
                                                            <div class="fw-semibold text-dark small">{{ $fullAddress }}</div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        @if(!empty($info['availability_text']))
                                            <div class="small text-muted mt-4 pt-3 border-top d-flex align-items-center gap-2">
                                                <span class="fg-live-dot" style="background:#10B981;"></span>
                                                <span>{{ $info['availability_text'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if($hasMapContent)
                                <div class="col-12 {{ $showContactSection ? 'col-lg-7' : '' }}">
                                    <div class="fg-contact-card p-4 h-100 d-flex flex-column" id="fgContactMap" style="border-top: 4px solid #10B981; background: linear-gradient(180deg, #FFFFFF 0%, #F4FDF9 100%);">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="fg-sec-icon fg-sec-icon--emerald" aria-hidden="true">
                                                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                                        <circle cx="12" cy="10" r="3"></circle>
                                                    </svg>
                                                </span>
                                                <div>
                                                    <h2 class="h5 fw-bold mb-1">{{ $map['title'] ?? 'Our Office Location' }}</h2>
                                                    @if(!empty($map['public_address']))
                                                        <p class="small text-muted mb-0">{{ $map['public_address'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                            @if(!empty($map['map_url']))
                                                <a href="{{ $map['map_url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm rounded-pill px-3 py-2 fw-semibold text-white shadow-sm" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                                                    Open in Maps &rarr;
                                                </a>
                                            @endif
                                        </div>

                                        @if($embedUrl !== '')
                                            <div class="mt-3 rounded-4 overflow-hidden border flex-grow-1" style="min-height: 260px; background: #F8FAFC;">
                                                <iframe
                                                    src="{{ $embedUrl }}"
                                                    title="{{ $map['title'] ?? 'Our Office Location' }}"
                                                    width="100%"
                                                    height="100%"
                                                    style="border:0; min-height: 260px;"
                                                    loading="lazy"
                                                    referrerpolicy="no-referrer-when-downgrade"
                                                    allowfullscreen></iframe>
                                            </div>
                                        @elseif(!empty($map['map_url']))
                                            <div class="mt-3 p-4 rounded-4 border text-center flex-grow-1 d-flex flex-column justify-content-center align-items-center" style="background: linear-gradient(135deg, #FFFDF5 0%, #FFF8E5 100%);">
                                                <div class="fw-semibold mb-1">Interactive preview requires a Google Maps Embed URL or Public Address</div>
                                                <p class="small text-muted mb-3">Short share links open directly in Google Maps.</p>
                                                <a href="{{ $map['map_url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-warning rounded-pill px-4 py-2 fw-bold text-dark">
                                                    View Location on Google Maps &rarr;
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif

                {{-- Row 2: Send Us a Message + Business Hours in a single row --}}
                @if(!$renderedPairs['form_hours'] && ($showContactSection || $hasHoursContent))
                    @php $renderedPairs['form_hours'] = true; @endphp
                    <section class="mb-4" id="fgContactMain">
                        <div class="row g-4 align-items-stretch">
                            @if($showContactSection)
                                <div class="col-12 {{ $hasHoursContent ? 'col-lg-7' : '' }}">
                                    <div class="fg-contact-card p-4 h-100" style="border-top: 4px solid #3B82F6; background: linear-gradient(180deg, #FFFFFF 0%, #F5F8FF 100%);">
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <span class="fg-sec-icon fg-sec-icon--blue" aria-hidden="true">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                                </svg>
                                            </span>
                                            <div>
                                                <h2 class="h4 fw-bold mb-0">{{ $form['heading'] ?? 'Send Us a Message' }}</h2>
                                                @if(!empty($form['subtitle']))
                                                    <p class="small text-muted mb-0">{{ $form['subtitle'] }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        <div id="fgContactAlert" class="alert d-none py-2 small mb-3"></div>

                                        <form id="fgContactEnquiryForm" novalidate>
                                            <input type="hidden" name="idempotency_key" id="fgEnqIdempotencyKey" value="">
                                            <!-- Honeypot spam trap -->
                                            <div class="d-none" aria-hidden="true">
                                                <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                                            </div>

                                            <div class="row g-3 small">
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label fw-semibold" for="enqName">Full Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" id="enqName" class="form-control" required maxlength="140">
                                                    <div class="invalid-feedback" data-field="name"></div>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label fw-semibold" for="enqEmail">Email Address <span class="text-danger">*</span></label>
                                                    <input type="email" name="email" id="enqEmail" class="form-control" required maxlength="160">
                                                    <div class="invalid-feedback" data-field="email"></div>
                                                </div>
                                                @if(!empty($form['show_phone']))
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label fw-semibold" for="enqPhone">Phone Number (Optional)</label>
                                                        <input type="text" name="phone" id="enqPhone" class="form-control" maxlength="40">
                                                        <div class="invalid-feedback" data-field="phone"></div>
                                                    </div>
                                                @endif
                                                @if(!empty($form['show_booking_reference']))
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label fw-semibold" for="enqBookingRef">Booking Reference (Optional)</label>
                                                        <input type="text" name="booking_reference" id="enqBookingRef" class="form-control" maxlength="60" placeholder="e.g. LND-2026-000001">
                                                        <div class="invalid-feedback" data-field="booking_reference"></div>
                                                    </div>
                                                @endif
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold" for="enqSubject">Subject <span class="text-danger">*</span></label>
                                                    <input type="text" name="subject" id="enqSubject" class="form-control" required maxlength="180">
                                                    <div class="invalid-feedback" data-field="subject"></div>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label fw-semibold" for="enqMessage">Message <span class="text-danger">*</span></label>
                                                    <textarea name="message" id="enqMessage" class="form-control" rows="4" required maxlength="4000"></textarea>
                                                    <div class="invalid-feedback" data-field="message"></div>
                                                </div>
                                                <div class="col-12 text-end">
                                                    <button type="submit" class="btn rounded-pill px-4 py-2 fw-bold text-dark shadow-sm" style="background:#FFC629;" id="fgEnqSubmitBtn">
                                                        {{ $form['submit_label'] ?? 'Send Message →' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif

                            @if($hasHoursContent)
                                @php
                                    $todayName = strtolower(now()->format('l'));
                                    $dayThemes = [
                                        ['bg' => '#FFFBEB', 'border' => '#FDE68A', 'iconBg' => '#FEF3C7', 'iconColor' => '#D97706', 'timeBg' => '#FEF3C7', 'timeColor' => '#B45309'],
                                        ['bg' => '#EFF6FF', 'border' => '#BFDBFE', 'iconBg' => '#DBEAFE', 'iconColor' => '#2563EB', 'timeBg' => '#DBEAFE', 'timeColor' => '#1D4ED8'],
                                        ['bg' => '#ECFDF5', 'border' => '#A7F3D0', 'iconBg' => '#D1FAE5', 'iconColor' => '#059669', 'timeBg' => '#D1FAE5', 'timeColor' => '#047857'],
                                        ['bg' => '#F5F3FF', 'border' => '#DDD6FE', 'iconBg' => '#EDE9FE', 'iconColor' => '#7C3AED', 'timeBg' => '#EDE9FE', 'timeColor' => '#6D28D9'],
                                        ['bg' => '#ECFEFF', 'border' => '#A5F3FC', 'iconBg' => '#CFFAFE', 'iconColor' => '#0891B2', 'timeBg' => '#CFFAFE', 'timeColor' => '#0E7490'],
                                        ['bg' => '#FDF2F8', 'border' => '#FBCFE8', 'iconBg' => '#FCE7F3', 'iconColor' => '#DB2777', 'timeBg' => '#FCE7F3', 'timeColor' => '#BE185D'],
                                        ['bg' => '#F8FAFC', 'border' => '#E2E8F0', 'iconBg' => '#F1F5F9', 'iconColor' => '#64748B', 'timeBg' => '#F1F5F9', 'timeColor' => '#475569'],
                                    ];
                                @endphp
                                <div class="col-12 {{ $showContactSection ? 'col-lg-5' : '' }}">
                                    <div class="fg-contact-card p-4 h-100" id="fgContactHours" style="border-top: 4px solid #8B5CF6; background: linear-gradient(180deg, #FFFFFF 0%, #FAF8FF 100%);">
                                        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="fg-hours-header-icon" aria-hidden="true">
                                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="12" cy="12" r="9"></circle>
                                                        <line x1="12" y1="12" x2="12" y2="7" class="fg-hours-clock-hand"></line>
                                                        <line x1="12" y1="12" x2="16" y2="12"></line>
                                                    </svg>
                                                </span>
                                                <div>
                                                    <h2 class="h4 fw-bold mb-0">Business Hours</h2>
                                                    <p class="small text-muted mb-0">Support &amp; pickup schedule</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2 small">
                                            @foreach($hours as $idx => $dayRow)
                                                @php
                                                    $isOpen = !empty($dayRow['is_open']);
                                                    $dayName = (string) ($dayRow['day'] ?? '');
                                                    $isToday = strtolower(trim($dayName)) === $todayName;
                                                    $theme = $isOpen
                                                        ? $dayThemes[$idx % count($dayThemes)]
                                                        : ['bg' => '#FFF1F2', 'border' => '#FECDD3', 'iconBg' => '#FFE4E6', 'iconColor' => '#E11D48', 'timeBg' => '#FFE4E6', 'timeColor' => '#BE123C'];
                                                @endphp
                                                <div class="fg-hour-row d-flex justify-content-between align-items-center gap-2 {{ $isToday ? 'is-today' : '' }}"
                                                     style="background: {{ $theme['bg'] }}; border-color: {{ $isToday ? '#8B5CF6' : $theme['border'] }};">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="fg-hour-icon" style="background: {{ $theme['iconBg'] }}; color: {{ $theme['iconColor'] }};" aria-hidden="true">
                                                            @if($isOpen)
                                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <circle cx="12" cy="12" r="4"></circle>
                                                                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path>
                                                                </svg>
                                                            @else
                                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                                                                </svg>
                                                            @endif
                                                        </span>
                                                        <span class="fw-semibold text-dark">{{ $dayName }}</span>
                                                        @if($isToday)
                                                            <span class="badge rounded-pill px-2 py-1" style="background:#8B5CF6; color:#FFFFFF; font-size:0.65rem;">Today</span>
                                                        @endif
                                                    </div>

                                                    @if($isOpen)
                                                        <span class="badge rounded-pill px-2 py-1 fw-semibold d-inline-flex align-items-center gap-1"
                                                              style="background: {{ $theme['timeBg'] }}; color: {{ $theme['timeColor'] }}; font-size:0.75rem;">
                                                            <span class="fg-live-dot" style="background: {{ $theme['iconColor'] }};"></span>
                                                            {{ $dayRow['open_time'] ?? '' }} – {{ $dayRow['close_time'] ?? '' }}
                                                        </span>
                                                    @else
                                                        <span class="badge rounded-pill px-2 py-1 fw-semibold"
                                                              style="background: {{ $theme['timeBg'] }}; color: {{ $theme['timeColor'] }}; font-size:0.75rem;">
                                                            {{ $dayRow['note'] ?: 'Closed' }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif

            @elseif($sec['key'] === 'social')
                @php
                    $social = $payload['social'] ?? [];
                    $activeSocials = collect([
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'linkedin' => 'LinkedIn',
                        'youtube' => 'YouTube',
                        'tiktok' => 'TikTok',
                        'whatsapp' => 'WhatsApp',
                    ])->filter(fn ($label, $key) => ! empty($social[$key]));
                @endphp
                @if($activeSocials->isNotEmpty())
                    @php
                        $socialPalette = [
                            'facebook' => ['bg' => '#EFF6FF', 'color' => '#1D4ED8', 'border' => '#BFDBFE'],
                            'instagram' => ['bg' => '#FDF2F8', 'color' => '#BE185D', 'border' => '#FBCFE8'],
                            'linkedin' => ['bg' => '#ECFEFF', 'color' => '#0E7490', 'border' => '#A5F3FC'],
                            'youtube' => ['bg' => '#FFF1F2', 'color' => '#BE123C', 'border' => '#FECDD3'],
                            'tiktok' => ['bg' => '#F5F3FF', 'color' => '#6D28D9', 'border' => '#DDD6FE'],
                            'whatsapp' => ['bg' => '#ECFDF5', 'color' => '#047857', 'border' => '#A7F3D0'],
                        ];
                    @endphp
                    <section class="fg-contact-card p-4 mb-4" id="fgContactSocial" style="border-top: 4px solid #EC4899; background: linear-gradient(180deg, #FFFFFF 0%, #FFF7FB 100%);">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="fg-sec-icon fg-sec-icon--pink" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="18" cy="5" r="3"></circle>
                                    <circle cx="6" cy="12" r="3"></circle>
                                    <circle cx="18" cy="19" r="3"></circle>
                                    <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                                    <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                                </svg>
                            </span>
                            <div>
                                <h2 class="h5 fw-bold mb-0">Connect With Us</h2>
                                <p class="small text-muted mb-0">Follow us or reach out on social media</p>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($activeSocials as $sKey => $sLabel)
                                @php $sp = $socialPalette[$sKey] ?? ['bg' => '#F8FAFC', 'color' => '#171717', 'border' => '#E2E8F0']; @endphp
                                <a href="{{ $social[$sKey] }}" target="_blank" rel="noopener noreferrer"
                                   class="btn btn-sm rounded-pill px-3 py-2 fw-semibold fg-social-pill d-inline-flex align-items-center gap-2"
                                   style="background: {{ $sp['bg'] }}; color: {{ $sp['color'] }}; border-color: {{ $sp['border'] }};">
                                    <span class="fg-live-dot" style="background: {{ $sp['color'] }};"></span>
                                    {{ $sLabel }}
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

            @elseif($sec['key'] === 'faq')
                @php
                    $faq = $payload['faq'] ?? [];
                    $faqItems = collect($faq['items'] ?? [])->filter(fn ($f) => !empty($f['question']) && !empty($f['answer']))->values();
                @endphp
                @if($faqItems->isNotEmpty())
                    <section class="fg-contact-card p-4 mb-4" id="fgContactFaq" style="border-top: 4px solid #14B8A6; background: linear-gradient(180deg, #FFFFFF 0%, #F2FCFA 100%);">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="fg-sec-icon fg-sec-icon--teal" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                            </span>
                            <div>
                                <h2 class="h5 fw-bold mb-0">{{ $faq['title'] ?? 'Frequently Asked Questions' }}</h2>
                                <p class="small text-muted mb-0">Quick answers to common customer questions</p>
                            </div>
                        </div>
                        <div class="d-grid gap-2 small">
                            @foreach($faqItems as $fIdx => $f)
                                <div class="p-3 border fg-faq-item d-flex align-items-start gap-3" style="background:#FFFFFF; border-color:#CCFBF1 !important;">
                                    <span class="fg-hour-icon mt-1" style="background:#CCFBF1; color:#0F766E; font-weight:700;">{{ $fIdx + 1 }}</span>
                                    <div>
                                        <div class="fw-bold mb-1 text-dark">{{ $f['question'] }}</div>
                                        <div class="text-muted">{{ $f['answer'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endif
        @endforeach
    </main>

    @include('shared.footer')
    <div class="d-lg-none">@include('shared.bottom-nav')</div>
    @include('shared.offcanvas-nav')
    <script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('fgContactEnquiryForm');
            if (!form) return;

            const btn = document.getElementById('fgEnqSubmitBtn');
            const alertBox = document.getElementById('fgContactAlert');
            const idemInput = document.getElementById('fgEnqIdempotencyKey');
            const submitUrl = @json(route('contact.enquiry.store'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const genKey = () => 'enq-' + Date.now() + '-' + Math.random().toString(36).substring(2, 10);
            if (idemInput) idemInput.value = genKey();

            let submitting = false;
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                if (submitting) return;
                submitting = true;

                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
                alertBox.className = 'alert d-none py-2 small mb-3';

                const defaultBtnText = btn.textContent;
                btn.disabled = true;
                btn.textContent = 'Sending...';

                try {
                    const res = await fetch(submitUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: new FormData(form),
                    });
                    const data = await res.json();

                    if (!res.ok) {
                        if (res.status === 422 && data.errors) {
                            Object.keys(data.errors).forEach(field => {
                                const input = form.querySelector(`[name="${field}"]`);
                                const fb = form.querySelector(`.invalid-feedback[data-field="${field}"]`);
                                if (input) input.classList.add('is-invalid');
                                if (fb) fb.textContent = data.errors[field][0];
                            });
                        } else {
                            alertBox.className = 'alert alert-danger py-2 small mb-3';
                            alertBox.textContent = data.message || 'Unable to send your enquiry right now.';
                        }
                        return;
                    }

                    form.reset();
                    if (idemInput) idemInput.value = genKey();
                    alertBox.className = 'alert alert-success py-2 small mb-3';
                    alertBox.textContent = (data.message || 'Enquiry submitted!') + (data.reference ? ` (Reference: ${data.reference})` : '');
                } catch (err) {
                    alertBox.className = 'alert alert-danger py-2 small mb-3';
                    alertBox.textContent = 'Network error. Please try again.';
                } finally {
                    submitting = false;
                    btn.disabled = false;
                    btn.textContent = defaultBtnText;
                }
            });
        });
    </script>
</body>
</html>
