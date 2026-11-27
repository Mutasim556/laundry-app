@php
    $categoryIcons = ['wash-fold' => '01_washing_machine.png', 'wash-iron' => '12_clean_shirt.png', 'dry-cleaning' => '06_shirt_hanger.png', 'ironing-only' => '05_iron.png', 'back-wash-iron' => '08_wash_tub.png', 'bulky-item-duvet' => '07_folded_laundry.png', 'repairs' => '13_repairs.svg'];
@endphp
@once
<style>
    @keyframes fgCategoryInfoPulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.45), 0 4px 10px rgba(245, 158, 11, 0.28);
            transform: scale(1);
        }
        50% {
            box-shadow: 0 0 0 6px rgba(245, 158, 11, 0), 0 6px 14px rgba(245, 158, 11, 0.36);
            transform: scale(1.06);
        }
    }
    .fg-cat-info-btn {
        width: 30px;
        height: 30px;
        border: 0;
        border-radius: 50%;
        background: linear-gradient(135deg, #FFC629 0%, #F59E0B 55%, #FB7185 100%);
        color: #FFFFFF;
        font-size: 0.92rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        padding: 0;
        cursor: pointer;
        animation: fgCategoryInfoPulse 2.2s ease-in-out infinite;
        transition: transform 0.2s ease, filter 0.2s ease;
    }
    .fg-cat-info-btn:hover,
    .fg-cat-info-btn:focus-visible {
        animation: none;
        transform: scale(1.14) rotate(8deg);
        filter: brightness(1.06);
        box-shadow: 0 6px 16px rgba(245, 158, 11, 0.42);
    }
    @media (prefers-reduced-motion: reduce) {
        .fg-cat-info-btn { animation: none; transition: none; }
    }
</style>
@endonce
<aside class="category-summary category-summary-box" aria-label="Selected category details">
    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
        <div class="category-summary-heading mb-0">
            <img src="{{ $category?->image_url ?: asset('images/laundry-icons/'.($categoryIcons[$category?->slug] ?? '03_laundry_basket.png')) }}" alt="" width="28" height="28">
            <h2>{{ $category?->name ?? 'Other services' }}</h2>
        </div>
        @if ($category?->description)
            <button class="category-more fg-cat-info-btn"
                    type="button"
                    data-category-more
                    aria-label="Learn more"
                    title="Learn more">
                !
            </button>
            <div class="category-description-source" hidden>{!! \App\Support\CategoryDescription::safeHtml($category->description) !!}</div>
        @endif
    </div>
    <p>{{ $category?->short_description ?: 'Explore our laundry services and find the care that fits.' }}</p>
    <div class="category-summary-actions">
        <a href="{{ route('booking.services', ['category' => $category?->slug ?? 'other-services']) }}"
           class="category-book btn-category-book text-decoration-none"
           data-book-now
           data-booking-url="{{ route('booking.services', ['category' => $category?->slug ?? 'other-services']) }}">Book Now &rarr;</a>
    </div>
</aside>
