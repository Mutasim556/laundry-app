<div class="results-header-box results-heading">
    <div>
        @php
            $catOvTitle = trim((string) ($servicesPayload['catalog_config']['title'] ?? ''));
            $catOvSub = trim((string) ($servicesPayload['catalog_config']['subtitle'] ?? ''));
        @endphp
        <h2 id="results-title" class="results-title-text" tabindex="-1">{{ $catOvTitle !== '' ? $catOvTitle : ($category?->name ?? 'Other services') }}</h2>
        <p class="results-short-desc">{{ $catOvSub !== '' ? $catOvSub : ($category?->short_description ?: 'Explore the services below and find the care that fits.') }}</p>
    </div>
    <span class="results-count-pill result-count">{{ $services->total() }} {{ $services->total() === 1 ? 'service' : 'services' }}</span>
</div>

@if ($items->isNotEmpty())
    <nav class="item-filters item-filters-bar" aria-label="Filter by item">
        <a href="{{ route('services.index', array_merge(request()->except(['item', 'page']), ['category' => $category->slug])) }}" data-item="" class="item-chip item-chip-pill {{ ! $selectedItem ? 'is-active' : '' }}" @if (! $selectedItem) aria-current="true" @endif>All items</a>
        <button class="item-scroll-button item-scroll-btn" type="button" data-item-scroll="-1" aria-label="Previous items">&#8249;</button>
        <div class="item-scroll-track">
        @foreach ($items as $item)
            <a href="{{ route('services.index', array_merge(request()->except('page'), ['category' => $category->slug, 'item' => $item->id])) }}" data-item="{{ $item->id }}" class="item-chip item-chip-pill {{ (string) $selectedItem === (string) $item->id ? 'is-active' : '' }}" @if ((string) $selectedItem === (string) $item->id) aria-current="true" @endif>{{ $item->name }}</a>
        @endforeach
        @if ($hasCategoryServices)
            <a href="{{ route('services.index', array_merge(request()->except('page'), ['category' => $category->slug, 'item' => 'other'])) }}" data-item="other" class="item-chip item-chip-pill {{ $selectedItem === 'other' ? 'is-active' : '' }}" @if ($selectedItem === 'other') aria-current="true" @endif>General care</a>
        @endif
        </div>
        <button class="item-scroll-button item-scroll-btn" type="button" data-item-scroll="1" aria-label="Next items">&#8250;</button>
    </nav>
@endif

@if ($search !== '' || $selectedItem || $sort !== 'name')
    <div class="active-filters active-filters-box">
        <span>
            @if ($search !== '')
                Results for <strong>“{{ $search }}”</strong>
            @else
                Your filters are applied
            @endif
        </span>
        <a href="{{ route('services.index', ['category' => $category?->slug ?? 'other-services']) }}" data-reset>Clear filters <span aria-hidden="true">&times;</span></a>
    </div>
@endif

<div class="service-grid service-cards-grid">
    @forelse ($services as $service)
        @php
            $svcOv = ($catalogOverrides ?? [])[$service->id] ?? (($catalogOverrides ?? [])[(string) $service->id] ?? []);
            $svcBadge = ! empty($svcOv['custom_badge']) ? $svcOv['custom_badge'] : ($service->item?->name ?? 'General care');
            $svcDesc = ! empty($svcOv['custom_description']) ? $svcOv['custom_description'] : $service->description;
        @endphp
        <article class="service-card service-item-card" style="--card-order: {{ $loop->index }}">
            <div>
                <div class="card-top card-top-badge">
                    <span class="service-type service-item-tag">{{ $svcBadge }}</span>
                    <span class="card-spark text-warning" aria-hidden="true">✦</span>
                </div>
                @if ($service->image_url)
                    <img class="service-image img-fluid rounded-4 mb-3" src="{{ $service->image_url }}" alt="" loading="lazy" style="max-height: 140px; width: 100%; object-fit: cover;">
                @endif
                <h3 class="service-card-name">{{ $service->name }}</h3>
                @if ($svcDesc)
                    <p class="service-description service-card-desc">{{ $svcDesc }}</p>
                @endif
            </div>

            <div class="card-bottom service-card-footer">
                <div class="service-price price-unit-block">
                    <span class="curr-amount">{{ formatMoney($service->price, $service->currency_code ?? null) }}</span>
                    @if ($service->unit)
                        <span class="curr-label">/ {{ $service->unit->short_name ?: $service->unit->name }}</span>
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="care-label card-care-pill">
                        <span aria-hidden="true">✓</span> Freshly cared
                    </span>
                    <a href="{{ route('booking.services', ['service_id' => $service->id]) }}"
                       class="btn-service-card-book text-decoration-none"
                       title="Book {{ $service->name }}">
                        Book &rarr;
                    </a>
                </div>
            </div>
        </article>
    @empty
        <div class="empty-state catalog-empty-card">
            <span class="empty-icon" aria-hidden="true">🔍</span>
            <h3>{{ $search !== '' || $selectedItem ? 'No matching services' : 'A little quiet here, for now.' }}</h3>
            <p>{{ $search !== '' || $selectedItem ? 'Try a different search or clear your filters to see more services.' : 'There are no services in this category yet. Explore another category for more care options.' }}</p>
            @if ($search !== '' || $selectedItem)
                <a class="reset-button btn btn-sm btn-warning rounded-pill px-3 py-1 fw-bold text-dark" href="{{ route('services.index', ['category' => $category?->slug ?? 'other-services']) }}" data-reset>Clear filters &rarr;</a>
            @endif
        </div>
    @endforelse
</div>

@if ($services->hasPages())
    <nav class="pagination catalog-pagination-bar" aria-label="Service pages">
        <span class="pagination-info-text">Showing {{ $services->firstItem() ?? 0 }}–{{ $services->lastItem() ?? 0 }} of {{ $services->total() }}</span>
        <div class="pagination-btn-group">
            @if ($services->onFirstPage())
                <span class="page-button page-nav-pill disabled" aria-disabled="true">&larr; Previous</span>
            @else
                <a class="page-button page-nav-pill" href="{{ $services->previousPageUrl() }}" data-page>&larr; Previous</a>
            @endif
            <span class="page-number page-num-pill">{{ $services->currentPage() }} / {{ $services->lastPage() }}</span>
            @if ($services->hasMorePages())
                <a class="page-button page-nav-pill" href="{{ $services->nextPageUrl() }}" data-page>Next &rarr;</a>
            @else
                <span class="page-button page-nav-pill disabled" aria-disabled="true">Next &rarr;</span>
            @endif
        </div>
    </nav>
@endif
