<div class="results-heading">
    <div><p class="eyebrow">A FRESH TAKE ON EVERYDAY CARE</p><h2 id="results-title" tabindex="-1">{{ $category?->name ?? 'Other services' }}</h2><p>{{ $category?->description ?: 'Explore the services below and find the care that fits.' }}</p></div>
    <span class="result-count">{{ $services->total() }} {{ $services->total() === 1 ? 'service' : 'services' }}</span>
</div>
@if ($items->isNotEmpty())
    <nav class="item-filters" aria-label="Filter by item">
        <a href="{{ route('services.index', array_merge(request()->except(['item', 'page']), ['category' => $category->slug])) }}" data-item="" class="item-chip {{ ! $selectedItem ? 'is-active' : '' }}" @if (! $selectedItem) aria-current="true" @endif>All items</a>
        @foreach ($items as $item)
            <a href="{{ route('services.index', array_merge(request()->except('page'), ['category' => $category->slug, 'item' => $item->id])) }}" data-item="{{ $item->id }}" class="item-chip {{ (string) $selectedItem === (string) $item->id ? 'is-active' : '' }}" @if ((string) $selectedItem === (string) $item->id) aria-current="true" @endif>{{ $item->name }}</a>
        @endforeach
        @if ($hasCategoryServices)
            <a href="{{ route('services.index', array_merge(request()->except('page'), ['category' => $category->slug, 'item' => 'other'])) }}" data-item="other" class="item-chip {{ $selectedItem === 'other' ? 'is-active' : '' }}" @if ($selectedItem === 'other') aria-current="true" @endif>General care</a>
        @endif
    </nav>
@endif
@if ($search !== '' || $selectedItem || $sort !== 'name')
    <div class="active-filters">
        <span>
            @if ($search !== '')
                Results for <strong>“{{ $search }}”</strong>
            @else
                Your filters are applied
            @endif
        </span>
        <a href="{{ route('services.index', ['category' => $category?->slug ?? 'other-services']) }}" data-reset>Clear filters <span aria-hidden="true">×</span></a>
    </div>
@endif
<div class="service-grid">
    @forelse ($services as $service)
        <article class="service-card" style="--card-order: {{ $loop->index }}">
            <div class="card-top"><span class="service-type">{{ $service->item?->name ?? 'General care' }}</span><span class="card-spark" aria-hidden="true">✦</span></div>
            @if ($service->image_url)<img class="service-image" src="{{ $service->image_url }}" alt="" loading="lazy" width="320" height="160">@endif
            <h3>{{ $service->name }}</h3>
            @if ($service->description)<p class="service-description">{{ $service->description }}</p>@endif
            <div class="card-bottom"><div class="service-price"><span class="currency">BDT</span> <strong>{{ number_format((float) $service->price, 2) }}</strong>@if ($service->unit)<span class="billing-unit">/ {{ $service->unit->short_name ?: $service->unit->name }}</span>@endif</div><span class="care-label"><span aria-hidden="true">✓</span> Freshly cared for</span></div>
        </article>
    @empty
        <div class="empty-state"><span class="empty-icon" aria-hidden="true">⌕</span><h3>{{ $search !== '' || $selectedItem ? 'No matching services' : 'A little quiet here, for now.' }}</h3><p>{{ $search !== '' || $selectedItem ? 'Try a different search or clear your filters to see more services.' : 'There are no services in this category yet. Explore another category for more care options.' }}</p>@if ($search !== '' || $selectedItem)<a class="reset-button" href="{{ route('services.index', ['category' => $category?->slug ?? 'other-services']) }}" data-reset>Clear filters ↗</a>@endif</div>
    @endforelse
</div>
@if ($services->hasPages())
    <nav class="pagination" aria-label="Service pages"><span>Showing {{ $services->firstItem() ?? 0 }}–{{ $services->lastItem() ?? 0 }} of {{ $services->total() }}</span><div>@if ($services->onFirstPage())<span class="page-button disabled" aria-disabled="true">← Previous</span>@else<a class="page-button" href="{{ $services->previousPageUrl() }}" data-page>← Previous</a>@endif<span class="page-number">{{ $services->currentPage() }} / {{ $services->lastPage() }}</span>@if ($services->hasMorePages())<a class="page-button" href="{{ $services->nextPageUrl() }}" data-page>Next →</a>@else<span class="page-button disabled" aria-disabled="true">Next →</span>@endif</div></nav>
@endif
