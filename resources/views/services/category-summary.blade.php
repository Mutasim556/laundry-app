@php
    $categoryIcons = ['wash-fold' => '01_washing_machine.png', 'wash-iron' => '12_clean_shirt.png', 'dry-cleaning' => '06_shirt_hanger.png', 'ironing-only' => '05_iron.png', 'back-wash-iron' => '08_wash_tub.png', 'bulky-item-duvet' => '07_folded_laundry.png', 'repairs' => '13_repairs.svg'];
@endphp
<aside class="category-summary" aria-label="Selected category details">
    <div class="category-summary-heading">
        <img src="{{ $category?->image_url ?: asset('images/laundry-icons/'.($categoryIcons[$category?->slug] ?? '03_laundry_basket.png')) }}" alt="" width="38" height="38">
        <h2>{{ $category?->name ?? 'Other services' }}</h2>
    </div>
    <p>{{ $category?->short_description ?: 'Explore our laundry services and find the care that fits.' }}</p>
    @if ($category?->description)
        <button class="category-more" type="button" data-category-more>Learn more</button><div class="category-description-source" hidden>{!! \App\Support\CategoryDescription::safeHtml($category->description) !!}</div>
    @endif
    <button class="category-book" type="button" data-book-now>Book Now</button>
</aside>




