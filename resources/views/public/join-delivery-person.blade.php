@php
    $general = $payload['general'] ?? [];
    $hero = $payload['hero'] ?? [];
    $benefits = $payload['benefits'] ?? [];
    $howItWorks = $payload['how_it_works'] ?? [];
    $requirements = $payload['requirements'] ?? [];
    $formCfg = $payload['form_config'] ?? [];
    $docConfigs = $payload['documents'] ?? [];
    $faqs = $payload['faqs'] ?? [];
    $cta = $payload['cta'] ?? [];
    $demo = $payload['demo'] ?? [];
    $seo = $payload['seo'] ?? [];
    $orderedSections = collect($payload['sections'] ?? [])->where('visible', true)->sortBy('order')->values();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seo['meta_title'] ?? 'Join as a Delivery Person - Fresh Go' }}</title>
    @if(!empty($seo['meta_description']))
        <meta name="description" content="{{ $seo['meta_description'] }}">
    @endif
    <meta name="robots" content="{{ $seo['robots'] ?? 'index, follow' }}">
    @if(!empty($seo['canonical_url']))
        <link rel="canonical" href="{{ $seo['canonical_url'] }}">
    @endif
    <meta property="og:title" content="{{ !empty($seo['og_title']) ? $seo['og_title'] : ($seo['meta_title'] ?? 'Join as a Delivery Person - Fresh Go') }}">
    @if(!empty($seo['og_description']) || !empty($seo['meta_description']))
        <meta property="og:description" content="{{ !empty($seo['og_description']) ? $seo['og_description'] : $seo['meta_description'] }}">
    @endif
    <link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin/dist/libs/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laundry-home.css') }}?v={{ filemtime(public_path('css/laundry-home.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --fg-yellow: #FFC629;
            --fg-cream: #FFF8E5;
            --fg-dark: #171717;
            --fg-amber: #F59E0B;
        }
        html,
        body.fg-home-body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #F7F6F2;
            color: var(--fg-dark);
            min-height: 100vh;
            overflow-x: hidden;
            max-width: 100%;
        }
        @media (max-width: 991.98px) {
            body.fg-home-body {
                padding-bottom: calc(88px + env(safe-area-inset-bottom, 0px));
            }
        }
        .fg-hero {
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 55%, #FEF3C7 100%);
            border-bottom: 1px solid #FDE68A;
            padding: 1.75rem 0;
            position: relative;
            overflow: hidden;
        }
        @media (min-width: 768px) {
            .fg-hero {
                padding: 3rem 0;
            }
        }
        .fg-hero-title {
            font-size: clamp(1.65rem, 5.2vw, 2.75rem);
            line-height: 1.18;
        }
        .fg-step-tabs-scroll {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            gap: 0.5rem;
            padding-bottom: 4px;
            scrollbar-width: none;
        }
        .fg-step-tabs-scroll::-webkit-scrollbar { display: none; }
        @media (min-width: 768px) {
            .fg-step-tabs-scroll {
                flex-wrap: wrap;
                overflow-x: visible;
            }
        }
        .fg-step-tabs-scroll .fg-step-pill {
            flex-shrink: 0;
            white-space: nowrap;
        }
        .fg-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            background: #FFFFFF;
            border: 1px solid #FDE68A;
            border-radius: 999px;
            padding: 0.35rem 0.9rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: #92400E;
        }
        .fg-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 18px;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .fg-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.07);
        }
        .fg-step-pill {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.85rem;
            border-radius: 12px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            font-size: 0.82rem;
            font-weight: 600;
            color: #64748B;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .fg-step-pill.active {
            background: var(--fg-cream);
            border-color: var(--fg-yellow);
            color: var(--fg-dark);
        }
        .fg-step-pill.completed {
            background: #ECFDF5;
            border-color: #A7F3D0;
            color: #065F46;
        }
        /* Input with crisp icon */
        .fg-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .fg-input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748B;
            pointer-events: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            z-index: 3;
        }
        .fg-input-icon.fg-icon-top {
            top: 14px;
            transform: none;
        }
        .fg-input-wrap .form-control,
        .fg-input-wrap .form-select {
            height: 44px;
            padding-left: 40px;
            border-radius: 12px;
            border: 1px solid #CBD5E1;
            font-size: 0.9rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .fg-input-wrap textarea.form-control {
            height: auto;
            padding-top: 10px;
        }
        .fg-input-wrap .form-control:focus,
        .fg-input-wrap .form-select:focus {
            border-color: #F59E0B;
            box-shadow: 0 0 0 3px rgba(255, 198, 41, 0.28);
        }
        .fg-pwd-toggle {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #64748B;
            padding: 6px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            z-index: 4;
        }
        .fg-pwd-toggle:hover {
            color: #171717;
            background: #F1F5F9;
        }
        /* Phone country dropdown */
        .fg-phone-group {
            display: flex;
            align-items: stretch;
            position: relative;
        }
        .fg-country-btn {
            height: 44px;
            min-width: 92px;
            border: 1px solid #CBD5E1;
            border-right: 0;
            border-radius: 12px 0 0 12px;
            background: #F8FAFC;
            padding: 0 8px;
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 5px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #171717;
            flex-shrink: 0;
        }
        @media (min-width: 576px) {
            .fg-country-btn {
                min-width: 112px;
                padding: 0 10px;
                font-size: 0.88rem;
            }
        }
        .fg-country-btn:focus {
            outline: none;
            border-color: #F59E0B;
            box-shadow: 0 0 0 3px rgba(255, 198, 41, 0.28);
            z-index: 4;
        }
        .fg-phone-input-wrap {
            flex: 1 1 auto;
            min-width: 0;
            position: relative;
        }
        .fg-phone-input-wrap .form-control {
            height: 44px;
            padding-left: 38px;
            border-radius: 0 12px 12px 0;
            border: 1px solid #CBD5E1;
            font-size: 0.9rem;
        }
        .fg-phone-input-wrap .form-control:focus {
            border-color: #F59E0B;
            box-shadow: 0 0 0 3px rgba(255, 198, 41, 0.28);
        }
        .fg-country-menu {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            width: min(320px, 90vw);
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.14);
            z-index: 1050;
            padding: 8px;
            animation: fgFadeDrop 0.15s ease-out;
        }
        .fg-country-list {
            max-height: 220px;
            overflow-y: auto;
            margin-top: 6px;
        }
        .fg-country-item {
            width: 100%;
            border: 0;
            background: transparent;
            padding: 7px 10px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            text-align: left;
            color: #171717;
        }
        .fg-country-item:hover,
        .fg-country-item.active {
            background: #FFF8E5;
        }
        /* Flatpickr Fresh Go Theme */
        .flatpickr-calendar {
            border-radius: 16px !important;
            border: 1px solid #E2E8F0 !important;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.12) !important;
            font-family: 'Poppins', sans-serif !important;
        }
        .flatpickr-day.selected,
        .flatpickr-day.selected:hover {
            background: #FFC629 !important;
            border-color: #FFC629 !important;
            color: #171717 !important;
            font-weight: 700 !important;
        }
        .flatpickr-day:hover {
            background: #FFF8E5 !important;
            border-color: #FDE68A !important;
        }
        /* Document Upload Cards */
        .fg-upload-card {
            border: 1.5px dashed #CBD5E1;
            border-radius: 16px;
            background: #F8FAFC;
            padding: 1rem;
            transition: border-color 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
            height: 100%;
        }
        .fg-upload-card.drag-over {
            border-color: #F59E0B;
            background: #FFF8E5;
            box-shadow: 0 6px 18px rgba(255, 198, 41, 0.22);
        }
        .fg-upload-card.has-file {
            border-style: solid;
            border-color: #86EFAC;
            background: #FFFFFF;
        }
        .fg-upload-thumb {
            width: 56px;
            height: 56px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid #E2E8F0;
            background: #FFF8E5;
            flex-shrink: 0;
        }
        .fg-float-icon {
            animation: fgFloat 3.6s ease-in-out infinite;
        }
        @keyframes fgFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        @keyframes fgFadeDrop {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .fg-float-icon, .fg-card, .fg-country-menu { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body class="fg-home-body">
@include('shared.header')

@if(!empty($isPreviewDraft))
    <div class="bg-warning text-dark text-center py-2 px-3 small fw-bold">
        DRAFT PREVIEW MODE — Unpublished changes are visible only to Admins. Form submission is disabled in preview.
    </div>
@endif

@foreach($orderedSections as $sec)
    @if($sec['key'] === 'hero')
        @php
            $heroBg = match($hero['bg_preset'] ?? 'gold_soft') {
                'white_clean' => 'background: #FFFFFF; border-bottom: 1px solid #E2E8F0;',
                'gold_gradient' => 'background: linear-gradient(135deg, #FFF8E5 0%, #FDE68A 100%); border-bottom: 1px solid #F59E0B;',
                default => 'background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 55%, #FEF3C7 100%); border-bottom: 1px solid #FDE68A;',
            };
        @endphp
        <section class="fg-hero" style="{{ $heroBg }}">
            <div class="container">
                <div class="row align-items-center g-4 {{ ($hero['alignment'] ?? 'left') === 'center' ? 'text-center justify-content-center' : '' }}">
                    <div class="{{ ($hero['alignment'] ?? 'left') === 'center' ? 'col-lg-9' : 'col-lg-7' }}">
                        @if(!empty($hero['badge']))
                            <span class="fg-hero-badge mb-3">
                                <span>🚚</span>
                                <span>{{ $hero['badge'] }}</span>
                            </span>
                        @endif
                        <h1 class="fg-hero-title fw-bold mb-3" style="color: #171717;">
                            {{ $hero['headline'] ?? 'Deliver Freshness. Grow With Fresh Go.' }}
                        </h1>
                        @if(!empty($hero['subtitle']))
                            <p class="lead text-secondary mb-3">{{ $hero['subtitle'] }}</p>
                        @endif
                        @if(!empty($hero['description']))
                            <p class="small text-secondary mb-4">{{ $hero['description'] }}</p>
                        @endif
                        <div class="d-flex flex-wrap gap-2 {{ ($hero['alignment'] ?? 'left') === 'center' ? 'justify-content-center' : '' }}">
                            @if($hero['primary_btn_visible'] ?? true)
                                <a href="#applyFormSection" class="btn btn-dark rounded-pill px-4 py-2 fw-bold shadow-sm">
                                    {{ $hero['primary_btn_label'] ?? 'Apply as a Delivery Person' }}
                                </a>
                            @endif
                            @if($demo['enabled'] ?? true)
                                <button type="button" id="openRegistrationDemoBtn"
                                        class="btn rounded-pill px-4 py-2 fw-bold text-dark shadow-sm d-inline-flex align-items-center gap-2"
                                        style="background: #FFC629; border: 1px solid #F59E0B;"
                                        data-bs-toggle="modal" data-bs-target="#dpRegistrationDemoModal">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                                    <span>{{ $demo['button_label'] ?? 'View Registration Demo' }}</span>
                                </button>
                            @endif
                            @if($hero['secondary_btn_visible'] ?? true)
                                <a href="{{ $hero['secondary_btn_url'] ?? route('delivery-person.login') }}" class="btn btn-outline-dark rounded-pill px-4 py-2 fw-semibold">
                                    {{ $hero['secondary_btn_label'] ?? 'Already Registered? Login' }}
                                </a>
                            @endif
                            <a href="{{ route('delivery-person.application-status') }}" class="btn btn-link text-dark fw-semibold text-decoration-none px-3 py-2">
                                Track Application →
                            </a>
                        </div>
                    </div>
                    @if(($hero['alignment'] ?? 'left') !== 'center')
                        <div class="col-lg-5">
                            <div class="fg-card p-4 bg-white">
                                @if(!empty($hero['image_url']))
                                    <img src="{{ $cms->resolveImageUrl($hero['image_url']) }}" alt="{{ $hero['image_alt'] ?? 'Fresh Go Delivery Person' }}" class="img-fluid rounded-4 mb-3 w-100" style="max-height: 220px; object-fit: cover;">
                                @else
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="rounded-4 d-inline-flex align-items-center justify-content-center fg-float-icon"
                                             style="width: 54px; height: 54px; background: #FFF8E5; border: 1.5px solid #FFC629; font-size: 1.6rem;">
                                            📦
                                        </div>
                                        <div>
                                            <h2 class="h6 fw-bold mb-1">Simple Digital Workflow</h2>
                                            <p class="small text-secondary mb-0">Manage collection &amp; final delivery jobs in one portal.</p>
                                        </div>
                                    </div>
                                @endif
                                <div class="row g-2 text-center">
                                    <div class="col-4"><div class="p-2 rounded-3" style="background: #F8FAFC;"><div class="fw-bold small">1. Apply</div><div class="text-muted" style="font-size: 0.72rem;">Online form</div></div></div>
                                    <div class="col-4"><div class="p-2 rounded-3" style="background: #FFF8E5;"><div class="fw-bold small">2. Verify</div><div class="text-muted" style="font-size: 0.72rem;">Admin review</div></div></div>
                                    <div class="col-4"><div class="p-2 rounded-3" style="background: #ECFDF5;"><div class="fw-bold small">3. Deliver</div><div class="text-muted" style="font-size: 0.72rem;">Portal access</div></div></div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @elseif($sec['key'] === 'how_it_works')
        <section class="py-4 py-md-5">
            <div class="container">
                <div class="text-center mb-4">
                    <h2 class="h3 fw-bold">{{ $howItWorks['title'] ?? 'How It Works' }}</h2>
                    @if(!empty($howItWorks['subtitle']))
                        <p class="text-secondary small mb-0">{{ $howItWorks['subtitle'] }}</p>
                    @endif
                </div>
                <div class="row g-3">
                    @php $stepBgs = ['#FFF8E5', '#EFF6FF', '#F5F3FF', '#ECFDF5']; @endphp
                    @foreach(collect($howItWorks['steps'] ?? [])->where('visible', true)->sortBy('order') as $idx => $item)
                        <div class="col-md-6 col-lg-3">
                            <div class="fg-card p-3 h-100">
                                <span class="badge rounded-pill text-dark fw-bold mb-2" style="background: {{ $stepBgs[$idx % 4] }};">Step {{ $item['step'] ?? ('0' . ($idx + 1)) }}</span>
                                <h3 class="h6 fw-bold mb-1">{{ $item['title'] ?? '' }}</h3>
                                <p class="small text-secondary mb-0">{{ $item['description'] ?? '' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @elseif($sec['key'] === 'benefits')
        <section class="py-4">
            <div class="container">
                <div class="text-center mb-4">
                    <h2 class="h3 fw-bold">{{ $benefits['title'] ?? 'Why Deliver With Fresh Go' }}</h2>
                    @if(!empty($benefits['subtitle']))
                        <p class="text-secondary small mb-0">{{ $benefits['subtitle'] }}</p>
                    @endif
                </div>
                <div class="row g-3">
                    @foreach(collect($benefits['items'] ?? [])->where('visible', true)->sortBy('order') as $b)
                        <div class="col-md-6 col-lg-3">
                            <div class="fg-card p-3 h-100">
                                <div class="rounded-3 d-inline-flex align-items-center justify-content-center mb-2" style="width:38px;height:38px;background:#FFF8E5;border:1px solid #FDE68A;">✓</div>
                                <h3 class="h6 fw-bold mb-1">{{ $b['title'] ?? '' }}</h3>
                                <p class="small text-secondary mb-0">{{ $b['description'] ?? '' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @elseif($sec['key'] === 'requirements')
        <section class="py-4">
            <div class="container">
                <div class="fg-card p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h2 class="h4 fw-bold mb-1">{{ $requirements['title'] ?? 'Eligibility Requirements' }}</h2>
                            @if(!empty($requirements['subtitle']))
                                <p class="small text-secondary mb-0">{{ $requirements['subtitle'] }}</p>
                            @endif
                        </div>
                        @if(!empty($requirements['region_note']))
                            <span class="badge bg-light text-dark border px-3 py-2">{{ $requirements['region_note'] }}</span>
                        @endif
                    </div>
                    <div class="row g-3">
                        @foreach(collect($requirements['items'] ?? [])->where('visible', true)->sortBy('order') as $r)
                            <div class="col-md-4">
                                <div class="p-3 rounded-4 border bg-light h-100">
                                    <h3 class="h6 fw-bold mb-1">{{ $r['title'] ?? '' }}</h3>
                                    <p class="small text-secondary mb-0">{{ $r['description'] ?? '' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @elseif($sec['key'] === 'application_form')

<section class="pb-5" id="applyFormSection">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="fg-card p-3 p-md-5">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                        <div>
                            <h2 class="h4 fw-bold mb-1">{{ $formCfg['heading'] ?? 'Delivery Person Registration' }}</h2>
                            <p class="small text-secondary mb-0">{{ $formCfg['subtitle'] ?? 'All applications are reviewed by an Admin before account activation.' }}</p>
                        </div>
                        <span class="badge rounded-pill text-dark px-3 py-2" style="background: #FFF8E5; border: 1px solid #FFC629;">
                            Step <span id="dpCurrentStepNum">1</span> of 5
                        </span>
                    </div>

                    @if(!($general['applications_open'] ?? true))
                        <div class="text-center py-4">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px;background:#FFF8E5;border:1px solid #FFC629;font-size:1.5rem;">⏸</div>
                            <h3 class="h5 fw-bold mb-2">{{ $general['closed_title'] ?? 'Applications Currently Closed' }}</h3>
                            <p class="text-secondary small mb-3">{{ $general['closed_message'] ?? 'We are not accepting new Delivery Person applications at this moment.' }}</p>
                            @if(!empty($general['expected_reopening']))
                                <p class="small fw-semibold mb-3">Expected Reopening: {{ $general['expected_reopening'] }}</p>
                            @endif
                            <a href="{{ route('delivery-person.application-status') }}" class="btn btn-dark rounded-pill px-4 fw-bold">Track Existing Application</a>
                        </div>
                    @else
                    <div class="progress mb-4" style="height: 6px; border-radius: 999px;">
                        <div id="dpProgressBar" class="progress-bar" role="progressbar" style="width: 20%; background-color: #FFC629;"></div>
                    </div>

                    <div class="fg-step-tabs-scroll mb-4" id="dpStepTabs">
                        <button type="button" class="fg-step-pill active" data-step="1">{{ $formCfg['step_1_title'] ?? '1. Personal' }}</button>
                        <button type="button" class="fg-step-pill" data-step="2">{{ $formCfg['step_2_title'] ?? '2. Address' }}</button>
                        <button type="button" class="fg-step-pill" data-step="3">{{ $formCfg['step_3_title'] ?? '3. Delivery' }}</button>
                        <button type="button" class="fg-step-pill" data-step="4">{{ $formCfg['step_4_title'] ?? '4. Documents' }}</button>
                        <button type="button" class="fg-step-pill" data-step="5">{{ $formCfg['step_5_title'] ?? '5. Security' }}</button>
                    </div>

                    <div id="dpFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <form id="dpApplicationForm" enctype="multipart/form-data" novalidate>
                        @csrf
                        <input type="hidden" name="phone_country_code" id="dpPhoneCountryCode" value="+44">
                        <input type="hidden" name="phone_iso_code" id="dpPhoneIsoCode" value="GB">

                        {{-- STEP 1 --}}
                        <div class="dp-step-pane" data-step="1">
                            <h3 class="h6 fw-bold mb-3">Personal Information</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="dpFullName" class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        </span>
                                        <input type="text" id="dpFullName" name="full_name" class="form-control" placeholder="e.g. Alex Johnson" required maxlength="150">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="dpEmail" class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                        </span>
                                        <input type="email" id="dpEmail" name="email" class="form-control" placeholder="you@example.com" required maxlength="150">
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <label for="dpPhone" class="form-label small fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                                    <div class="fg-phone-group" id="dpPhoneGroup">
                                        <button type="button" class="fg-country-btn" id="dpCountryBtn" aria-haspopup="listbox" aria-expanded="false" aria-label="Select country calling code">
                                            <span id="dpCountryFlag">🇬🇧</span>
                                            <span id="dpCountryDial">+44</span>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m6 9 6 6 6-6"/></svg>
                                        </button>
                                        <div class="fg-phone-input-wrap">
                                            <span class="fg-input-icon" aria-hidden="true">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                            </span>
                                            <input type="tel" id="dpPhone" name="phone" class="form-control" placeholder="7700 900111" required maxlength="30" inputmode="tel">
                                        </div>
                                        <div class="fg-country-menu d-none" id="dpCountryMenu" role="listbox" aria-label="Country calling codes">
                                            <input type="text" id="dpCountrySearch" class="form-control form-control-sm" placeholder="Search country, ISO or +code..." autocomplete="off">
                                            <div class="fg-country-list" id="dpCountryList"></div>
                                        </div>
                                    </div>
                                    <div class="form-text small" id="dpPhoneHint">Selected: United Kingdom (+44)</div>
                                </div>
                                <div class="col-md-5">
                                    <label for="dpDob" class="form-label small fw-semibold">{{ $formCfg['dob_label'] ?? 'Date of Birth' }}</label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                        </span>
                                        <input type="text" id="dpDob" name="date_of_birth" class="form-control bg-white" placeholder="{{ $formCfg['dob_placeholder'] ?? 'Select date of birth' }}" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- STEP 2 --}}
                        <div class="dp-step-pane d-none" data-step="2">
                            <h3 class="h6 fw-bold mb-3">Address &amp; Service Area</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="dpAddr1" class="form-label small fw-semibold">Address Line 1 <span class="text-danger">*</span></label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                        </span>
                                        <input type="text" id="dpAddr1" name="address_line_1" class="form-control" placeholder="Street address" required maxlength="255">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="dpAddr2" class="form-label small fw-semibold">Address Line 2</label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                                        </span>
                                        <input type="text" id="dpAddr2" name="address_line_2" class="form-control" placeholder="Apartment, suite (optional)" maxlength="255">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="dpCity" class="form-label small fw-semibold">City <span class="text-danger">*</span></label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/></svg>
                                        </span>
                                        <input type="text" id="dpCity" name="city" class="form-control" placeholder="City" required maxlength="100">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="dpPostcode" class="form-label small fw-semibold">Postcode <span class="text-danger">*</span></label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        </span>
                                        <input type="text" id="dpPostcode" name="postcode" class="form-control" placeholder="e.g. SW1A 2AA" required maxlength="30">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="dpAddressCountry" class="form-label small fw-semibold">Country <span class="text-danger">*</span></label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                                        </span>
                                        <select id="dpAddressCountry" name="country_code" class="form-select" required>
                                            @foreach ($countries as $code => $name)
                                                <option value="{{ $code }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="dpServiceArea" class="form-label small fw-semibold">Preferred Service Area / Postcode Prefix</label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" x2="9" y1="3" y2="18"/><line x1="15" x2="15" y1="6" y2="21"/></svg>
                                        </span>
                                        <input type="text" id="dpServiceArea" name="service_area" class="form-control" placeholder="e.g. Central London, SW1, E1" maxlength="150">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- STEP 3 --}}
                        <div class="dp-step-pane d-none" data-step="3">
                            <h3 class="h6 fw-bold mb-3">Delivery Information</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="dpDeliveryMethod" class="form-label small fw-semibold">Delivery Method <span class="text-danger">*</span></label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>
                                        </span>
                                        <select name="delivery_method" id="dpDeliveryMethod" class="form-select" required>
                                            @foreach ($deliveryMethods as $val => $label)
                                                <option value="{{ $val }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 dp-motor-field d-none">
                                    <label for="dpVehicleType" class="form-label small fw-semibold">Vehicle Type</label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                                        </span>
                                        <input type="text" id="dpVehicleType" name="vehicle_type" class="form-control" placeholder="e.g. Hatchback, Scooter" maxlength="60">
                                    </div>
                                </div>
                                <div class="col-md-6 dp-motor-field d-none">
                                    <label for="dpVehicleReg" class="form-label small fw-semibold">Vehicle Registration</label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="10" x="3" y="7" rx="2"/><path d="M7 12h10"/></svg>
                                        </span>
                                        <input type="text" id="dpVehicleReg" name="vehicle_registration" class="form-control" placeholder="e.g. AB12 CDE" maxlength="40">
                                    </div>
                                </div>
                                <div class="col-md-6 dp-motor-field d-none">
                                    <label for="dpLicenceNo" class="form-label small fw-semibold">Driving Licence Number</label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><circle cx="8" cy="12" r="2"/><path d="M14 10h4"/><path d="M14 14h4"/></svg>
                                        </span>
                                        <input type="text" id="dpLicenceNo" name="driving_licence_number" class="form-control" placeholder="Licence number" maxlength="60">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="dpExperience" class="form-label small fw-semibold">Relevant Experience (Optional)</label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon fg-icon-top" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                                        </span>
                                        <textarea id="dpExperience" name="experience_notes" class="form-control" rows="2" placeholder="Briefly describe any delivery experience..." maxlength="1000"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- STEP 4 --}}
                        <div class="dp-step-pane d-none" data-step="4">
                            <h3 class="h6 fw-bold mb-2">Verification Documents</h3>
                            <p class="small text-secondary mb-3">
                                Preview your images or PDFs before submitting. Files stay in your browser until you submit and are stored privately on the server.
                            </p>
                            <div class="row g-3">
                                @php
                                    $docCards = [
                                        ['name' => 'profile_photo', 'label' => 'Profile Photo', 'desc' => 'Clear headshot for your Delivery Person profile.', 'accept' => '.jpg,.jpeg,.png,.webp', 'types' => 'JPG, PNG, WebP · Max 4 MB', 'maxBytes' => 4 * 1024 * 1024, 'motor' => false],
                                        ['name' => 'identity_document', 'label' => 'Identity Verification', 'desc' => 'Government-issued photo ID for Admin verification.', 'accept' => '.jpg,.jpeg,.png,.pdf', 'types' => 'JPG, PNG, PDF · Max 5 MB', 'maxBytes' => 5 * 1024 * 1024, 'motor' => false],
                                        ['name' => 'driving_licence_document', 'label' => 'Driving Licence Document', 'desc' => 'Required only for motorised vehicles.', 'accept' => '.jpg,.jpeg,.png,.pdf', 'types' => 'JPG, PNG, PDF · Max 5 MB', 'maxBytes' => 5 * 1024 * 1024, 'motor' => true],
                                        ['name' => 'vehicle_document', 'label' => 'Vehicle Insurance / Document', 'desc' => 'Valid vehicle insurance or registration.', 'accept' => '.jpg,.jpeg,.png,.pdf', 'types' => 'JPG, PNG, PDF · Max 5 MB', 'maxBytes' => 5 * 1024 * 1024, 'motor' => true],
                                    ];
                                @endphp
                                @foreach ($docCards as $card)
                                    <div class="col-md-6 {{ $card['motor'] ? 'dp-motor-field d-none' : '' }}">
                                        <div class="fg-upload-card" data-upload-card="{{ $card['name'] }}" data-max-bytes="{{ $card['maxBytes'] }}" data-accept="{{ $card['accept'] }}">
                                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                                <div>
                                                    <div class="fw-bold small">{{ $card['label'] }}</div>
                                                    <div class="text-muted" style="font-size: 0.76rem;">{{ $card['desc'] }}</div>
                                                </div>
                                                <span class="badge bg-white text-secondary border" style="font-size: 0.68rem;">{{ $card['types'] }}</span>
                                            </div>

                                            <input type="file" id="file_{{ $card['name'] }}" name="{{ $card['name'] }}" class="d-none dp-doc-input" accept="{{ $card['accept'] }}">

                                            <div class="dp-upload-empty text-center py-2">
                                                <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-semibold dp-choose-btn" data-target="file_{{ $card['name'] }}">
                                                    Choose File
                                                </button>
                                                <div class="text-muted mt-1 d-none d-md-block" style="font-size: 0.72rem;">or drag &amp; drop here</div>
                                            </div>

                                            <div class="dp-upload-selected d-none">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <img src="" alt="Preview thumbnail" class="fg-upload-thumb d-none dp-thumb-img">
                                                    <div class="fg-upload-thumb d-none dp-thumb-pdf align-items-center justify-content-center fw-bold text-danger small">PDF</div>
                                                    <div class="min-w-0 flex-grow-1">
                                                        <div class="small fw-semibold text-truncate dp-file-name"></div>
                                                        <div class="text-muted dp-file-meta" style="font-size: 0.72rem;"></div>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <button type="button" class="btn btn-xs btn-sm btn-outline-primary rounded-pill px-2 py-0 dp-preview-btn">Preview</button>
                                                    <button type="button" class="btn btn-xs btn-sm btn-outline-secondary rounded-pill px-2 py-0 dp-replace-btn" data-target="file_{{ $card['name'] }}">Replace</button>
                                                    <button type="button" class="btn btn-xs btn-sm btn-outline-danger rounded-pill px-2 py-0 dp-remove-btn">Remove</button>
                                                </div>
                                            </div>
                                            <div class="text-danger small mt-1 d-none dp-upload-error"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- STEP 5 --}}
                        <div class="dp-step-pane d-none" data-step="5">
                            <h3 class="h6 fw-bold mb-3">Account Security &amp; Consent</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="dpPassword" class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        </span>
                                        <input type="password" name="password" id="dpPassword" class="form-control pe-5" required minlength="8">
                                        <button type="button" class="fg-pwd-toggle" data-toggle-pwd="dpPassword" aria-label="Show password">
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                    </div>
                                    <div class="form-text">Minimum 8 characters with letters and numbers.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="dpPasswordConfirm" class="form-label small fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="fg-input-wrap">
                                        <span class="fg-input-icon" aria-hidden="true">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                                        </span>
                                        <input type="password" name="password_confirmation" id="dpPasswordConfirm" class="form-control pe-5" required minlength="8">
                                        <button type="button" class="fg-pwd-toggle" data-toggle-pwd="dpPasswordConfirm" aria-label="Show confirm password">
                                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="consent_accepted" value="1" id="dpConsentCheck" required>
                                        <label class="form-check-label small" for="dpConsentCheck">
                                            I confirm that the information provided is accurate and I consent to Fresh Go processing my application for verification.
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4 d-none" id="dpPrevBtn">Previous</button>
                            <div class="ms-auto d-flex gap-2">
                                <button type="button" class="btn btn-dark rounded-pill px-4 fw-bold" id="dpNextBtn">Next Step</button>
                                <button type="submit" class="btn rounded-pill px-4 fw-bold text-dark d-none" id="dpSubmitBtn" style="background: #FFC629;" @disabled(!empty($isPreviewDraft))>
                                    {{ $formCfg['submit_label'] ?? 'Submit Application' }}
                                </button>
                            </div>
                        </div>
                    </form>

                    <div id="dpSuccessScreen" class="d-none text-center py-4">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 64px; height: 64px; background: #ECFDF5; color: #16A34A; font-size: 1.8rem;">
                            ✓
                        </div>
                        <h3 class="h4 fw-bold mb-2">{{ $formCfg['success_title'] ?? 'Application Submitted Successfully' }}</h3>
                        <p class="text-secondary mb-3">
                            {{ $formCfg['success_message'] ?? 'Your application has been received and is awaiting Admin verification. You will be notified when its status changes.' }}
                        </p>
                        <div class="d-inline-block px-4 py-2 rounded-3 mb-4" style="background: #FFF8E5; border: 1px solid #FFC629;">
                            <span class="small text-secondary">Application Reference:</span>
                            <strong class="ms-1" id="dpSuccessRef"></strong>
                        </div>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="{{ route('delivery-person.application-status') }}" id="dpTrackLink" class="btn btn-dark rounded-pill px-4 fw-bold">
                                Track Application
                            </a>
                            <a href="{{ route('home') }}" class="btn btn-outline-dark rounded-pill px-4">
                                Back to Homepage
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
    @elseif($sec['key'] === 'faqs')
        <section class="pb-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <div class="text-center mb-4">
                            <h2 class="h3 fw-bold">{{ $faqs['title'] ?? 'Frequently Asked Questions' }}</h2>
                            @if(!empty($faqs['subtitle']))
                                <p class="text-secondary small mb-0">{{ $faqs['subtitle'] }}</p>
                            @endif
                        </div>
                        <div class="accordion" id="dpFaqAccordion">
                            @foreach(collect($faqs['items'] ?? [])->where('visible', true)->sortBy('order') as $fIdx => $faq)
                                <div class="accordion-item border rounded-4 mb-2 overflow-hidden shadow-sm">
                                    <h3 class="accordion-header">
                                        <button class="accordion-button {{ $fIdx > 0 ? 'collapsed' : '' }} fw-semibold bg-white text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#dpFaq{{ $fIdx }}">
                                            {{ $faq['question'] ?? '' }}
                                        </button>
                                    </h3>
                                    <div id="dpFaq{{ $fIdx }}" class="accordion-collapse collapse {{ $fIdx === 0 ? 'show' : '' }}" data-bs-parent="#dpFaqAccordion">
                                        <div class="accordion-body small text-secondary bg-white">
                                            {{ $faq['answer'] ?? '' }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @elseif($sec['key'] === 'cta')
        <section class="pb-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        @php
                            $ctaBg = match($cta['bg_preset'] ?? 'gold_gradient') {
                                'dark_clean' => 'background: #171717; color: #FFFFFF;',
                                'cream_soft' => 'background: #FFF8E5; border: 1px solid #FDE68A;',
                                default => 'background: linear-gradient(135deg, #FFF8E5 0%, #FDE68A 100%); border: 1px solid #F59E0B;',
                            };
                        @endphp
                        <div class="rounded-4 p-4 p-md-5 text-center shadow-sm" style="{{ $ctaBg }}">
                            <h2 class="h4 fw-bold mb-2">{{ $cta['headline'] ?? 'Already Applied?' }}</h2>
                            @if(!empty($cta['description']))
                                <p class="small mb-3 opacity-75">{{ $cta['description'] }}</p>
                            @endif
                            @if($cta['button_visible'] ?? true)
                                <a href="{{ $cta['button_url'] ?? route('delivery-person.application-status') }}" class="btn btn-dark rounded-pill px-4 py-2 fw-bold">
                                    {{ $cta['button_label'] ?? 'Track Application' }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endforeach

{{-- Document Preview Modal --}}
<div class="modal fade" id="dpFilePreviewModal" tabindex="-1" aria-labelledby="dpPreviewModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="dpPreviewModalTitle">Document Preview</h5>
                    <div class="small text-secondary" id="dpPreviewModalMeta"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-light p-3" id="dpPreviewModalBody" style="min-height: 280px;"></div>
            <div class="modal-footer border-top justify-content-between">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none" id="dpZoomToggleBtn">Zoom / Fit</button>
                    <a href="#" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark rounded-pill px-3 d-none" id="dpOpenBlobBtn">Open in New Tab</a>
                </div>
                <button type="button" class="btn btn-sm btn-dark rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Interactive Registration Demo Modal (Read-Only, Isolated) --}}
@if($demo['enabled'] ?? true)
<div class="modal fade" id="dpRegistrationDemoModal" tabindex="-1" aria-labelledby="dpDemoModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom" style="background: linear-gradient(135deg, #FFFFFF 0%, #FFF8E5 100%);">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge rounded-pill text-dark fw-bold" style="background: #FFC629;">Demo Mode</span>
                        <span class="small text-muted" id="dpDemoStepBadge">Step 1 of 6</span>
                    </div>
                    <h5 class="modal-title fw-bold mb-0" id="dpDemoModalTitle">{{ $demo['modal_title'] ?? 'How to Register as a Delivery Person' }}</h5>
                    @if(!empty($demo['modal_subtitle']))
                        <div class="small text-secondary">{{ $demo['modal_subtitle'] }}</div>
                    @endif
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close demo"></button>
            </div>

            <div class="modal-body p-3 p-md-4">
                <div class="alert alert-warning border-warning-subtle py-2 px-3 small mb-3 d-flex align-items-center gap-2">
                    <span>ℹ️</span>
                    <span><strong>Sample Information — Demo Only.</strong> {{ $demo['intro_note'] ?? 'Nothing entered here is saved or submitted.' }}</span>
                </div>

                <div class="progress mb-3" style="height: 6px; border-radius: 999px;" role="progressbar" aria-label="Demo walkthrough progress">
                    <div id="dpDemoProgressBar" class="progress-bar" style="width: 16.6%; background-color: #FFC629; transition: width 0.25s ease;"></div>
                </div>

                <div class="fg-step-tabs-scroll mb-3" id="dpDemoStepTabs">
                    <button type="button" class="fg-step-pill active" data-demo-step="1">{{ $formCfg['step_1_title'] ?? '1. Personal' }}</button>
                    <button type="button" class="fg-step-pill" data-demo-step="2">{{ $formCfg['step_2_title'] ?? '2. Address' }}</button>
                    <button type="button" class="fg-step-pill" data-demo-step="3">{{ $formCfg['step_3_title'] ?? '3. Delivery' }}</button>
                    <button type="button" class="fg-step-pill" data-demo-step="4">{{ $formCfg['step_4_title'] ?? '4. Documents' }}</button>
                    <button type="button" class="fg-step-pill" data-demo-step="5">{{ $formCfg['step_5_title'] ?? '5. Security' }}</button>
                    <button type="button" class="fg-step-pill" data-demo-step="6">6. Ready!</button>
                </div>

                {{-- Demo Step 1: Personal + Calendar + Country Flag --}}
                <div class="dp-demo-pane" data-demo-step="1">
                    <p class="small text-secondary mb-3">{{ $demo['step_1_note'] ?? 'Enter your name, email, international mobile number with country flag, and date of birth.' }}</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Sample Full Name</label>
                            <input type="text" class="form-control form-control-sm" value="Jordan Taylor (Sample)" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Sample Email</label>
                            <input type="text" class="form-control form-control-sm" value="jordan.sample@example.com" readonly>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">International Mobile Number Demo</label>
                            <div class="input-group input-group-sm">
                                <select id="demoCountrySelect" class="form-select" style="max-width: 135px;" aria-label="Demo country code">
                                    <option value="7700 900111" selected>🇬🇧 +44 (UK)</option>
                                    <option value="01711 223344">🇧🇩 +880 (BD)</option>
                                    <option value="555 234 5678">🇺🇸 +1 (US)</option>
                                    <option value="98765 43210">🇮🇳 +91 (IN)</option>
                                </select>
                                <input type="text" id="demoPhoneSample" class="form-control" value="7700 900111" aria-label="Demo phone number">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label for="demoDobPicker" class="form-label small fw-semibold">Calendar Demo (Date of Birth)</label>
                            <input type="text" id="demoDobPicker" class="form-control form-control-sm bg-white" placeholder="Select sample date..." readonly>
                            <div class="form-text" style="font-size: 0.72rem;">Select your date of birth using the calendar.</div>
                        </div>
                    </div>
                </div>

                {{-- Demo Step 2: Address & Service Area --}}
                <div class="dp-demo-pane d-none" data-demo-step="2">
                    <p class="small text-secondary mb-3">{{ $demo['step_2_note'] ?? 'Provide your residential address and preferred delivery service area.' }}</p>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small fw-semibold">Address Line 1</label><input type="text" class="form-control form-control-sm" value="221B Sample Street" readonly></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">City</label><input type="text" class="form-control form-control-sm" value="London" readonly></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Postcode</label><input type="text" class="form-control form-control-sm" value="NW1 6XE" readonly></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Country</label><input type="text" class="form-control form-control-sm" value="United Kingdom" readonly></div>
                        <div class="col-md-4"><label class="form-label small fw-semibold">Preferred Service Area</label><input type="text" class="form-control form-control-sm" value="Central London / NW1" readonly></div>
                    </div>
                </div>

                {{-- Demo Step 3: Delivery Info --}}
                <div class="dp-demo-pane d-none" data-demo-step="3">
                    <p class="small text-secondary mb-3">{{ $demo['step_3_note'] ?? 'Choose how you will deliver. Vehicle details are only shown for motorised transport.' }}</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="demoDeliveryMethod" class="form-label small fw-semibold">Try Delivery Method</label>
                            <select id="demoDeliveryMethod" class="form-select form-select-sm">
                                <option value="bicycle">Bicycle (No vehicle registration needed)</option>
                                <option value="car">Car (Shows vehicle &amp; licence fields)</option>
                                <option value="motorbike">Motorbike / Scooter</option>
                            </select>
                        </div>
                        <div class="col-md-6 dp-demo-motor d-none">
                            <label class="form-label small fw-semibold">Sample Vehicle Registration</label>
                            <input type="text" class="form-control form-control-sm" value="FG26 DEMO" readonly>
                        </div>
                        <div class="col-md-6 dp-demo-motor d-none">
                            <label class="form-label small fw-semibold">Sample Driving Licence</label>
                            <input type="text" class="form-control form-control-sm" value="SAMPLE-LIC-9988" readonly>
                        </div>
                    </div>
                </div>

                {{-- Demo Step 4: Document Preview --}}
                <div class="dp-demo-pane d-none" data-demo-step="4">
                    <p class="small text-secondary mb-3">{{ $demo['step_4_note'] ?? 'Preview your image or PDF verification documents privately in your browser before submitting.' }}</p>
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 border bg-light text-center">
                                <img id="demoDocPreviewImg"
                                     src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='320' height='180' viewBox='0 0 320 180'><rect width='320' height='180' rx='14' fill='%23FFF8E5' stroke='%23FFC629' stroke-width='2'/><rect x='22' y='34' width='68' height='82' rx='8' fill='%23FDE68A'/><circle cx='56' cy='64' r='16' fill='%23F59E0B'/><rect x='108' y='40' width='180' height='14' rx='4' fill='%23171717' opacity='0.75'/><rect x='108' y='66' width='130' height='10' rx='4' fill='%2364748B' opacity='0.5'/><rect x='108' y='88' width='155' height='10' rx='4' fill='%2364748B' opacity='0.4'/><text x='22' y='150' font-family='sans-serif' font-size='13' font-weight='bold' fill='%2392400E'>SAMPLE ID PREVIEW (DEMO ONLY)</text></svg>"
                                     alt="Sample verification document preview"
                                     class="img-fluid rounded-3 mb-2" style="max-height: 150px; object-fit: contain;">
                                <div class="d-flex justify-content-center gap-2">
                                    <label class="btn btn-xs btn-sm btn-outline-dark rounded-pill px-3 mb-0" style="cursor:pointer;">
                                        Try Local Preview
                                        <input type="file" id="demoLocalFileInput" class="d-none" accept=".jpg,.jpeg,.png,.webp">
                                    </label>
                                    <button type="button" id="demoResetDocBtn" class="btn btn-xs btn-sm btn-outline-secondary rounded-pill px-2 d-none">Reset</button>
                                </div>
                                <div class="text-muted mt-1" style="font-size: 0.72rem;">Browser-only preview. Nothing is uploaded.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <ol class="small text-secondary ps-3 mb-0">
                                <li class="mb-1">Choose a supported image (JPG, PNG, WebP) or PDF file.</li>
                                <li class="mb-1">Preview the document instantly before submitting.</li>
                                <li class="mb-1">Replace or remove it if needed.</li>
                                <li>Submit securely with your real application.</li>
                            </ol>
                        </div>
                    </div>
                </div>

                {{-- Demo Step 5: Security & Admin Verification --}}
                <div class="dp-demo-pane d-none" data-demo-step="5">
                    <p class="small text-secondary mb-3">{{ $demo['step_5_note'] ?? 'Set a strong password, accept the consent terms, and submit for Admin verification.' }}</p>
                    <div class="row g-2">
                        <div class="col-md-4"><div class="p-3 rounded-4 bg-light border h-100"><div class="fw-bold small mb-1">1. Admin Verification</div><div class="text-secondary" style="font-size:0.78rem;">Our Admin team reviews your details and private documents.</div></div></div>
                        <div class="col-md-4"><div class="p-3 rounded-4 bg-light border h-100"><div class="fw-bold small mb-1">2. Account Activation</div><div class="text-secondary" style="font-size:0.78rem;">Once approved, your Delivery Person Portal login is activated.</div></div></div>
                        <div class="col-md-4"><div class="p-3 rounded-4 bg-light border h-100"><div class="fw-bold small mb-1">3. Job Eligibility</div><div class="text-secondary" style="font-size:0.78rem;">Only approved &amp; active Delivery Persons receive job assignments.</div></div></div>
                    </div>
                </div>

                {{-- Demo Step 6: Ready to Apply --}}
                <div class="dp-demo-pane d-none text-center py-3" data-demo-step="6">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width: 60px; height: 60px; background: #ECFDF5; color: #16A34A; font-size: 1.7rem;">✓</div>
                    <h4 class="h5 fw-bold mb-2">{{ $demo['final_title'] ?? "You're Ready to Apply!" }}</h4>
                    <p class="small text-secondary mx-auto mb-3" style="max-width: 540px;">
                        {{ $demo['final_message'] ?? 'Complete the real registration form and submit your application. Our Admin team will review your information. Once approved and activated, you can access your Delivery Person Portal and become eligible for delivery assignments.' }}
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <button type="button" id="dpDemoStartRealBtn" class="btn rounded-pill px-4 fw-bold text-dark" style="background: #FFC629;">
                            {{ $demo['cta_label'] ?? 'Start My Application' }}
                        </button>
                        <button type="button" id="dpDemoReplayBtn" class="btn btn-outline-dark rounded-pill px-4">
                            Replay Demo
                        </button>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none" id="dpDemoPrevBtn">Previous</button>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-dark rounded-pill px-4 fw-bold" id="dpDemoNextBtn">Next Step</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@include('shared.footer')

<div class="d-lg-none">
    @include('shared.bottom-nav')
</div>

@include('shared.offcanvas-nav')

<script src="{{ asset('admin/dist/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/laundry-home.js') }}?v={{ filemtime(public_path('js/laundry-home.js')) }}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
(function () {
    // 1. Flatpickr Date of Birth Calendar
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#dpDob', {
            altInput: true,
            altFormat: 'd M Y',
            dateFormat: 'Y-m-d',
            maxDate: 'today',
            allowInput: false,
            disableMobile: false,
        });
    } else {
        document.getElementById('dpDob').type = 'date';
    }

    // 2. International Country Calling Codes with Flags & Search
    const COUNTRIES = [
        { iso: 'GB', name: 'United Kingdom', dial: '+44', flag: '🇬🇧' },
        { iso: 'BD', name: 'Bangladesh', dial: '+880', flag: '🇧🇩' },
        { iso: 'US', name: 'United States', dial: '+1', flag: '🇺🇸' },
        { iso: 'CA', name: 'Canada', dial: '+1', flag: '🇨🇦' },
        { iso: 'IN', name: 'India', dial: '+91', flag: '🇮🇳' },
        { iso: 'PK', name: 'Pakistan', dial: '+92', flag: '🇵🇰' },
        { iso: 'AU', name: 'Australia', dial: '+61', flag: '🇦🇺' },
        { iso: 'AE', name: 'United Arab Emirates', dial: '+971', flag: '🇦🇪' },
        { iso: 'SA', name: 'Saudi Arabia', dial: '+966', flag: '🇸🇦' },
        { iso: 'DE', name: 'Germany', dial: '+49', flag: '🇩🇪' },
        { iso: 'FR', name: 'France', dial: '+33', flag: '🇫🇷' },
        { iso: 'IE', name: 'Ireland', dial: '+353', flag: '🇮🇪' },
        { iso: 'MY', name: 'Malaysia', dial: '+60', flag: '🇲🇾' },
        { iso: 'SG', name: 'Singapore', dial: '+65', flag: '🇸🇬' },
        { iso: 'NG', name: 'Nigeria', dial: '+234', flag: '🇳🇬' },
        { iso: 'ZA', name: 'South Africa', dial: '+27', flag: '🇿🇦' },
        { iso: 'ES', name: 'Spain', dial: '+34', flag: '🇪🇸' },
        { iso: 'IT', name: 'Italy', dial: '+39', flag: '🇮🇹' },
        { iso: 'NL', name: 'Netherlands', dial: '+31', flag: '🇳🇱' },
        { iso: 'TR', name: 'Turkey', dial: '+90', flag: '🇹🇷' },
    ];

    let manualPhoneCountrySelected = false;
    const countryBtn = document.getElementById('dpCountryBtn');
    const countryMenu = document.getElementById('dpCountryMenu');
    const countrySearch = document.getElementById('dpCountrySearch');
    const countryList = document.getElementById('dpCountryList');
    const hiddenDial = document.getElementById('dpPhoneCountryCode');
    const hiddenIso = document.getElementById('dpPhoneIsoCode');
    const phoneHint = document.getElementById('dpPhoneHint');

    function setPhoneCountry(c, isManual = false) {
        if (!c) return;
        if (isManual) manualPhoneCountrySelected = true;
        document.getElementById('dpCountryFlag').textContent = c.flag;
        document.getElementById('dpCountryDial').textContent = c.dial;
        hiddenDial.value = c.dial;
        hiddenIso.value = c.iso;
        phoneHint.textContent = `Selected: ${c.name} (${c.dial})`;
    }

    function renderCountryList(query = '') {
        const q = query.trim().toLowerCase();
        const filtered = COUNTRIES.filter(c =>
            c.name.toLowerCase().includes(q) ||
            c.iso.toLowerCase().includes(q) ||
            c.dial.includes(q)
        );
        countryList.innerHTML = filtered.map(c => `
            <button type="button" class="fg-country-item ${c.iso === hiddenIso.value ? 'active' : ''}" data-iso="${c.iso}">
                <span><span class="me-2">${c.flag}</span>${c.name} <span class="text-muted">(${c.iso})</span></span>
                <span class="fw-bold">${c.dial}</span>
            </button>
        `).join('') || '<div class="small text-muted p-2 text-center">No matching country</div>';
    }

    countryBtn.addEventListener('click', () => {
        const isOpen = !countryMenu.classList.contains('d-none');
        countryMenu.classList.toggle('d-none', isOpen);
        countryBtn.setAttribute('aria-expanded', String(!isOpen));
        if (!isOpen) {
            renderCountryList('');
            countrySearch.value = '';
            setTimeout(() => countrySearch.focus(), 30);
        }
    });

    countrySearch.addEventListener('input', () => renderCountryList(countrySearch.value));

    countryList.addEventListener('click', (e) => {
        const btn = e.target.closest('.fg-country-item');
        if (!btn) return;
        const found = COUNTRIES.find(c => c.iso === btn.dataset.iso);
        setPhoneCountry(found, true);
        countryMenu.classList.add('d-none');
        countryBtn.setAttribute('aria-expanded', 'false');
    });

    document.addEventListener('click', (e) => {
        if (!document.getElementById('dpPhoneGroup').contains(e.target)) {
            countryMenu.classList.add('d-none');
            countryBtn.setAttribute('aria-expanded', 'false');
        }
    });

    document.getElementById('dpAddressCountry').addEventListener('change', function () {
        if (manualPhoneCountrySelected) return;
        const found = COUNTRIES.find(c => c.iso === this.value);
        if (found) setPhoneCountry(found, false);
    });

    // 3. Multi-Step Navigation
    let currentStep = 1;
    const totalSteps = 5;
    const form = document.getElementById('dpApplicationForm');
    const panes = document.querySelectorAll('.dp-step-pane');
    const tabs = document.querySelectorAll('#dpStepTabs .fg-step-pill');
    const prevBtn = document.getElementById('dpPrevBtn');
    const nextBtn = document.getElementById('dpNextBtn');
    const submitBtn = document.getElementById('dpSubmitBtn');
    const alertBox = document.getElementById('dpFormAlert');
    const progressBar = document.getElementById('dpProgressBar');
    const stepNumEl = document.getElementById('dpCurrentStepNum');

    function showStep(step) {
        currentStep = step;
        panes.forEach(p => p.classList.toggle('d-none', Number(p.dataset.step) !== step));
        tabs.forEach(t => {
            const s = Number(t.dataset.step);
            t.classList.toggle('active', s === step);
            t.classList.toggle('completed', s < step);
        });
        prevBtn.classList.toggle('d-none', step === 1);
        nextBtn.classList.toggle('d-none', step === totalSteps);
        submitBtn.classList.toggle('d-none', step !== totalSteps);
        progressBar.style.width = `${(step / totalSteps) * 100}%`;
        stepNumEl.textContent = String(step);
    }

    tabs.forEach(t => t.addEventListener('click', () => showStep(Number(t.dataset.step))));
    prevBtn.addEventListener('click', () => { if (currentStep > 1) showStep(currentStep - 1); });
    nextBtn.addEventListener('click', () => { if (currentStep < totalSteps) showStep(currentStep + 1); });

    const methodSelect = document.getElementById('dpDeliveryMethod');
    function toggleMotorFields() {
        const isMotor = ['motorbike', 'car', 'van'].includes(methodSelect.value);
        document.querySelectorAll('.dp-motor-field').forEach(el => el.classList.toggle('d-none', !isMotor));
    }
    methodSelect.addEventListener('change', toggleMotorFields);
    toggleMotorFields();

    // 4. Password Visibility Toggles
    document.querySelectorAll('.fg-pwd-toggle').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = document.getElementById(this.dataset.togglePwd);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    // 5. Document Upload Cards + Pre-Upload Image/PDF Preview + Memory Cleanup
    const objectUrls = new Map();
    const previewModalEl = document.getElementById('dpFilePreviewModal');
    const previewModal = typeof bootstrap !== 'undefined' ? new bootstrap.Modal(previewModalEl) : null;

    function revokeUrl(field) {
        if (objectUrls.has(field)) {
            URL.revokeObjectURL(objectUrls.get(field));
            objectUrls.delete(field);
        }
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
    }

    function handleFileSelection(card, input, file) {
        const errEl = card.querySelector('.dp-upload-error');
        errEl.classList.add('d-none');
        errEl.textContent = '';

        if (!file) return;

        const maxBytes = Number(card.dataset.maxBytes || 5242880);
        const allowedExts = (card.dataset.accept || '').split(',').map(s => s.trim().toLowerCase());
        const ext = '.' + (file.name.split('.').pop() || '').toLowerCase();
        const allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

        if (!allowedExts.includes(ext) || (file.type && !allowedMimes.includes(file.type))) {
            input.value = '';
            errEl.textContent = 'Please select a supported document format.';
            errEl.classList.remove('d-none');
            return;
        }
        if (file.size > maxBytes) {
            input.value = '';
            errEl.textContent = 'This file exceeds the allowed upload size.';
            errEl.classList.remove('d-none');
            return;
        }

        const fieldName = card.dataset.uploadCard;
        revokeUrl(fieldName);
        const blobUrl = URL.createObjectURL(file);
        objectUrls.set(fieldName, blobUrl);

        card.classList.add('has-file');
        card.querySelector('.dp-upload-empty').classList.add('d-none');
        card.querySelector('.dp-upload-selected').classList.remove('d-none');
        card.querySelector('.dp-file-name').textContent = file.name;
        card.querySelector('.dp-file-meta').textContent = `${formatBytes(file.size)} · ${file.type || ext.toUpperCase()}`;

        const thumbImg = card.querySelector('.dp-thumb-img');
        const thumbPdf = card.querySelector('.dp-thumb-pdf');
        if (file.type === 'application/pdf' || ext === '.pdf') {
            thumbImg.classList.add('d-none');
            thumbPdf.classList.remove('d-none');
            thumbPdf.classList.add('d-flex');
        } else {
            thumbPdf.classList.add('d-none');
            thumbPdf.classList.remove('d-flex');
            thumbImg.src = blobUrl;
            thumbImg.classList.remove('d-none');
        }
    }

    document.querySelectorAll('[data-upload-card]').forEach(card => {
        const input = card.querySelector('.dp-doc-input');
        const fieldName = card.dataset.uploadCard;

        card.querySelector('.dp-choose-btn')?.addEventListener('click', () => input.click());
        card.querySelector('.dp-replace-btn')?.addEventListener('click', () => input.click());
        card.querySelector('.dp-remove-btn')?.addEventListener('click', () => {
            input.value = '';
            revokeUrl(fieldName);
            card.classList.remove('has-file');
            card.querySelector('.dp-upload-selected').classList.add('d-none');
            card.querySelector('.dp-upload-empty').classList.remove('d-none');
            card.querySelector('.dp-upload-error').classList.add('d-none');
        });

        card.querySelector('.dp-preview-btn')?.addEventListener('click', () => {
            const file = input.files && input.files[0];
            const url = objectUrls.get(fieldName);
            if (!file || !url || !previewModal) return;

            document.getElementById('dpPreviewModalTitle').textContent = file.name;
            document.getElementById('dpPreviewModalMeta').textContent = `${formatBytes(file.size)} · ${file.type || 'Document'}`;
            const body = document.getElementById('dpPreviewModalBody');
            const zoomBtn = document.getElementById('dpZoomToggleBtn');
            const openBtn = document.getElementById('dpOpenBlobBtn');
            openBtn.href = url;
            openBtn.classList.remove('d-none');

            if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
                zoomBtn.classList.add('d-none');
                body.innerHTML = `<iframe src="${url}" title="PDF Preview" style="width:100%;height:440px;border:0;border-radius:10px;background:#fff;"></iframe>
                    <p class="small text-muted mt-2 mb-0">If your browser does not display inline PDFs, click "Open in New Tab".</p>`;
            } else {
                zoomBtn.classList.remove('d-none');
                body.innerHTML = `<img src="${url}" id="dpPreviewFullImg" alt="Document preview" style="max-width:100%;max-height:70vh;border-radius:10px;transition:transform 0.2s ease;">`;
                let zoomed = false;
                zoomBtn.onclick = () => {
                    zoomed = !zoomed;
                    const img = document.getElementById('dpPreviewFullImg');
                    if (img) img.style.transform = zoomed ? 'scale(1.45)' : 'scale(1)';
                };
            }
            previewModal.show();
        });

        input.addEventListener('change', () => {
            if (input.files && input.files[0]) handleFileSelection(card, input, input.files[0]);
        });

        ['dragenter', 'dragover'].forEach(evt => {
            card.addEventListener(evt, e => { e.preventDefault(); card.classList.add('drag-over'); });
        });
        ['dragleave', 'drop'].forEach(evt => {
            card.addEventListener(evt, e => { e.preventDefault(); card.classList.remove('drag-over'); });
        });
        card.addEventListener('drop', e => {
            const files = e.dataTransfer?.files;
            if (files && files[0]) {
                const dt = new DataTransfer();
                dt.items.add(files[0]);
                input.files = dt.files;
                handleFileSelection(card, input, files[0]);
            }
        });
    });

    window.addEventListener('beforeunload', () => {
        objectUrls.forEach(url => URL.revokeObjectURL(url));
        objectUrls.clear();
    });

    // 6. AJAX Submission with Duplicate-Submit Prevention
    let submitting = false;
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (submitting) return;
        submitting = true;
        submitBtn.disabled = true;
        const origText = submitBtn.textContent;
        submitBtn.textContent = 'Submitting...';
        alertBox.className = 'alert d-none mb-3';

        try {
            const res = await fetch('{{ route('join-delivery-person.apply') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                let msg = data.message || 'Please check the form fields and try again.';
                if (data.errors) {
                    msg = Object.values(data.errors).flat().join('<br>');
                }
                alertBox.className = 'alert alert-danger mb-3';
                alertBox.innerHTML = msg;
                return;
            }

            objectUrls.forEach(url => URL.revokeObjectURL(url));
            objectUrls.clear();
            form.classList.add('d-none');
            document.getElementById('dpStepTabs').classList.add('d-none');
            document.getElementById('dpSuccessRef').textContent = data.reference;
            document.getElementById('dpTrackLink').href = data.status_url;
            document.getElementById('dpSuccessScreen').classList.remove('d-none');
        } catch (err) {
            alertBox.className = 'alert alert-danger mb-3';
            alertBox.textContent = 'Network error while submitting your application. Please try again.';
        } finally {
            submitting = false;
            submitBtn.disabled = false;
            submitBtn.textContent = origText;
        }
    });
    // 7. Interactive Registration Demo Modal Controller (Completely Isolated)
    const demoModalEl = document.getElementById('dpRegistrationDemoModal');
    if (demoModalEl) {
        let demoStep = 1;
        const totalDemoSteps = 6;
        let demoObjectUrl = null;
        const defaultDemoImg = document.getElementById('demoDocPreviewImg')?.src || '';

        if (typeof flatpickr !== 'undefined' && document.getElementById('demoDobPicker')) {
            flatpickr('#demoDobPicker', {
                altInput: true,
                altFormat: 'd M Y',
                dateFormat: 'Y-m-d',
                maxDate: 'today',
                defaultDate: '1996-06-15',
            });
        }

        function showDemoStep(step) {
            demoStep = step;
            demoModalEl.querySelectorAll('.dp-demo-pane').forEach(p => p.classList.toggle('d-none', Number(p.dataset.demoStep) !== step));
            demoModalEl.querySelectorAll('[data-demo-step].fg-step-pill').forEach(t => {
                const s = Number(t.dataset.demoStep);
                t.classList.toggle('active', s === step);
                t.classList.toggle('completed', s < step);
            });
            document.getElementById('dpDemoStepBadge').textContent = `Step ${step} of ${totalDemoSteps}`;
            document.getElementById('dpDemoProgressBar').style.width = `${(step / totalDemoSteps) * 100}%`;
            document.getElementById('dpDemoPrevBtn').classList.toggle('d-none', step === 1);
            document.getElementById('dpDemoNextBtn').classList.toggle('d-none', step === totalDemoSteps);
        }

        demoModalEl.querySelectorAll('[data-demo-step].fg-step-pill').forEach(t => {
            t.addEventListener('click', () => showDemoStep(Number(t.dataset.demoStep)));
        });
        document.getElementById('dpDemoPrevBtn')?.addEventListener('click', () => { if (demoStep > 1) showDemoStep(demoStep - 1); });
        document.getElementById('dpDemoNextBtn')?.addEventListener('click', () => { if (demoStep < totalDemoSteps) showDemoStep(demoStep + 1); });
        document.getElementById('dpDemoReplayBtn')?.addEventListener('click', () => showDemoStep(1));

        document.getElementById('demoCountrySelect')?.addEventListener('change', function () {
            document.getElementById('demoPhoneSample').value = this.value;
        });
        document.getElementById('demoDeliveryMethod')?.addEventListener('change', function () {
            const isMotor = ['car', 'motorbike'].includes(this.value);
            demoModalEl.querySelectorAll('.dp-demo-motor').forEach(el => el.classList.toggle('d-none', !isMotor));
        });

        const demoFileInput = document.getElementById('demoLocalFileInput');
        const demoResetBtn = document.getElementById('demoResetDocBtn');
        const demoPreviewImg = document.getElementById('demoDocPreviewImg');
        function revokeDemoUrl() {
            if (demoObjectUrl) {
                URL.revokeObjectURL(demoObjectUrl);
                demoObjectUrl = null;
            }
        }
        demoFileInput?.addEventListener('change', function () {
            const f = this.files && this.files[0];
            if (!f || !f.type.startsWith('image/')) return;
            revokeDemoUrl();
            demoObjectUrl = URL.createObjectURL(f);
            demoPreviewImg.src = demoObjectUrl;
            demoResetBtn.classList.remove('d-none');
        });
        demoResetBtn?.addEventListener('click', () => {
            if (demoFileInput) demoFileInput.value = '';
            revokeDemoUrl();
            demoPreviewImg.src = defaultDemoImg;
            demoResetBtn.classList.add('d-none');
        });

        document.getElementById('dpDemoStartRealBtn')?.addEventListener('click', () => {
            bootstrap.Modal.getInstance(demoModalEl)?.hide();
            const target = document.getElementById('applyFormSection');
            target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            setTimeout(() => document.getElementById('dpFullName')?.focus(), 350);
        });

        demoModalEl.addEventListener('hidden.bs.modal', () => {
            revokeDemoUrl();
            if (demoPreviewImg && defaultDemoImg) demoPreviewImg.src = defaultDemoImg;
            demoResetBtn?.classList.add('d-none');
        });
    }
})();
</script>
</body>
</html>
