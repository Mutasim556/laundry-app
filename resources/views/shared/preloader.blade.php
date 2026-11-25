@php
    $fgPreloaderSvc = app(\App\Services\PreloaderSettingService::class);
    $fgPreloader = $fgPreloaderSvc->getPublishedPayload();
    $fgShowPreloader = $fgPreloaderSvc->shouldDisplayForCurrentRoute($fgPreloader);
@endphp

@if($fgShowPreloader)
@php
    $fgLogoUrl = !empty($fgPreloader['show_logo']) ? $fgPreloaderSvc->resolveLogoUrl($fgPreloader) : '';
    $fgLogoHeight = match ($fgPreloader['logo_size'] ?? 'md') {
        'sm' => '36px',
        'lg' => '68px',
        default => '52px',
    };
    $fgSpeedDuration = match ($fgPreloader['animation_speed'] ?? 'normal') {
        'slow' => '1.45s',
        'fast' => '0.6s',
        default => '0.95s',
    };
    $fgFadeMs = (int) ($fgPreloader['fade_duration_ms'] ?? 250);
    $fgMinMs = (int) ($fgPreloader['minimum_duration_ms'] ?? 0);
    $fgMaxMs = (int) ($fgPreloader['maximum_timeout_ms'] ?? 4000);
    $fgStyle = $fgPreloader['style'] ?? 'brand_spinner';
    $fgBg = $fgPreloader['background_color'] ?? '#FFF8E5';
    $fgAccent = $fgPreloader['accent_color'] ?? '#FFC629';
    $fgText = $fgPreloader['text_color'] ?? '#171717';
    $fgRespectReduced = !empty($fgPreloader['respect_reduced_motion']);
@endphp
<style>
    #fgPagePreloader {
        position: fixed;
        inset: 0;
        z-index: 99999;
        background-color: {{ $fgBg }};
        color: {{ $fgText }};
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        opacity: 1;
        visibility: visible;
        transition: opacity {{ $fgFadeMs }}ms ease, visibility {{ $fgFadeMs }}ms ease;
    }
    #fgPagePreloader.is-hiding {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    #fgPagePreloader.is-hidden {
        display: none !important;
        pointer-events: none !important;
    }
    .fg-pl-inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        max-width: 320px;
        width: 100%;
        gap: 14px;
    }
    .fg-pl-logo {
        max-height: {{ $fgLogoHeight }};
        width: auto;
        max-width: 200px;
        object-fit: contain;
        display: block;
    }
    .fg-pl-brand-badge {
        font-weight: 800;
        letter-spacing: 0.08em;
        font-size: 1.05rem;
        padding: 8px 16px;
        border-radius: 999px;
        background: {{ $fgAccent }};
        color: #171717;
    }
    .fg-pl-spinner {
        width: 44px;
        height: 44px;
        border-radius: 999px;
        border: 3.5px solid rgba(23, 23, 23, 0.12);
        border-top-color: {{ $fgAccent }};
        animation: fgPlSpin {{ $fgSpeedDuration }} linear infinite;
    }
    .fg-pl-garment {
        width: 54px;
        height: 54px;
        border-radius: 16px;
        background: {{ $fgAccent }};
        color: #171717;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        animation: fgPlFloat {{ $fgSpeedDuration }} ease-in-out infinite;
    }
    .fg-pl-dots {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .fg-pl-dots span {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: {{ $fgAccent }};
        animation: fgPlDot {{ $fgSpeedDuration }} ease-in-out infinite;
    }
    .fg-pl-dots span:nth-child(2) { animation-delay: 0.16s; }
    .fg-pl-dots span:nth-child(3) { animation-delay: 0.32s; }
    .fg-pl-progress-track {
        width: 220px;
        max-width: 100%;
        height: 6px;
        border-radius: 999px;
        background: rgba(23, 23, 23, 0.12);
        overflow: hidden;
        position: relative;
    }
    .fg-pl-progress-bar {
        position: absolute;
        top: 0;
        bottom: 0;
        left: -45%;
        width: 45%;
        border-radius: 999px;
        background: {{ $fgAccent }};
        animation: fgPlSlide {{ $fgSpeedDuration }} ease-in-out infinite;
    }
    .fg-pl-text {
        font-size: 0.92rem;
        font-weight: 600;
        margin: 0;
        color: {{ $fgText }};
        opacity: 0.88;
    }
    @keyframes fgPlSpin {
        to { transform: rotate(360deg); }
    }
    @keyframes fgPlFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-6px); }
    }
    @keyframes fgPlDot {
        0%, 100% { transform: scale(0.75); opacity: 0.45; }
        50% { transform: scale(1.2); opacity: 1; }
    }
    @keyframes fgPlSlide {
        0% { left: -45%; }
        100% { left: 105%; }
    }
    @if($fgRespectReduced)
    @media (prefers-reduced-motion: reduce) {
        #fgPagePreloader { transition: none !important; }
        .fg-pl-spinner, .fg-pl-garment, .fg-pl-dots span, .fg-pl-progress-bar { animation: none !important; }
        .fg-pl-progress-bar { left: 0 !important; width: 100% !important; opacity: 0.7; }
    }
    @endif
</style>

<div id="fgPagePreloader" role="status" aria-live="polite" aria-label="{{ $fgPreloader['loading_text'] ?: 'Loading page' }}">
    <div class="fg-pl-inner">
        @if(!empty($fgPreloader['show_logo']))
            @if($fgLogoUrl !== '')
                <img src="{{ $fgLogoUrl }}" alt="Fresh Go" class="fg-pl-logo">
            @else
                <span class="fg-pl-brand-badge">FRESH GO</span>
            @endif
        @endif

        @if($fgStyle === 'laundry_animation')
            <div class="fg-pl-garment" aria-hidden="true">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>
                </svg>
            </div>
            <div class="fg-pl-dots" aria-hidden="true"><span></span><span></span><span></span></div>
        @elseif($fgStyle === 'progress_indicator')
            <div class="fg-pl-progress-track" aria-hidden="true">
                <div class="fg-pl-progress-bar"></div>
            </div>
        @else
            <div class="fg-pl-spinner" aria-hidden="true"></div>
        @endif

        @if(!empty($fgPreloader['show_text']) && !empty($fgPreloader['loading_text']))
            <p class="fg-pl-text">{{ $fgPreloader['loading_text'] }}</p>
        @endif
    </div>
</div>

<script>
(function () {
    var el = document.getElementById('fgPagePreloader');
    if (!el) return;

    var startTime = Date.now();
    var minMs = {{ $fgMinMs }};
    var fadeMs = {{ $fgFadeMs }};
    var maxMs = {{ $fgMaxMs }};
    var hidden = false;

    function hidePreloader(immediate) {
        if (hidden) return;
        hidden = true;
        if (immediate) {
            el.classList.add('is-hidden');
            return;
        }
        el.classList.add('is-hiding');
        setTimeout(function () {
            el.classList.add('is-hidden');
        }, fadeMs + 30);
    }

    function scheduleHide() {
        var elapsed = Date.now() - startTime;
        var wait = Math.max(0, minMs - elapsed);
        if (wait === 0) {
            hidePreloader(false);
        } else {
            setTimeout(function () { hidePreloader(false); }, wait);
        }
    }

    // Hide as soon as core DOM is ready (never wait for external trackers, Stripe, PayPal, or AJAX)
    if (document.readyState === 'interactive' || document.readyState === 'complete') {
        scheduleHide();
    } else {
        document.addEventListener('DOMContentLoaded', scheduleHide, { once: true });
    }

    // Handle Back/Forward cache (bfcache) restoration on Mobile Safari & Chrome
    window.addEventListener('pageshow', function (event) {
        if (event.persisted || document.readyState === 'complete') {
            hidePreloader(true);
        }
    });

    // Maximum safety timeout so overlay can never block the page permanently
    setTimeout(function () {
        hidePreloader(false);
    }, maxMs);
})();
</script>
@endif
