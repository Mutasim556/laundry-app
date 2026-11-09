<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service list | Freshgo Laundry</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; border-top: 15px solid #0756b5; background: #fff; color: #102439; font-family: Arial, sans-serif; }
        main { max-width: 790px; margin: 0 auto; padding: 18px 24px 60px; }
        .back { display: inline-flex; align-items: center; gap: 12px; color: #102439; text-decoration: none; font-size: 17px; }
        .back span { font-size: 25px; line-height: 1; }
        h1 { margin: 0; font-size: 17px; font-weight: 400; }
        .tab-slider { display: flex; align-items: center; gap: 8px; margin: 26px 0 42px; } .tabs { display: grid; grid-auto-flow: column; grid-auto-columns: calc((100% - 32px) / 5); gap: 8px; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; flex: 1; min-width: 0; } .tabs::-webkit-scrollbar { display: none; } .tabs .tab { display: flex; align-items: center; justify-content: center; text-align: center; scroll-snap-align: start; min-height: 46px; padding: 8px 5px; } .slider-arrow { width: 32px; height: 36px; flex: none; border: 0; border-radius: 50%; color: #0756b5; background: #edf5fa; font-size: 24px; cursor: pointer; } .slider-arrow:disabled { opacity: .35; cursor: default; } .slider-arrow:focus-visible { outline: 3px solid #0055ae; outline-offset: 2px; }
        .tab { padding: 13px 17px; border-radius: 999px; background: #f4f7f9; color: #74818d; font-size: 13px; font-weight: 700; text-decoration: none; text-transform: uppercase; }
        .tab.active { background: #058df0; color: white; }
        .tab:focus-visible, .back:focus-visible { outline: 3px solid #0055ae; outline-offset: 3px; }
        .service { display: flex; justify-content: space-between; align-items: end; gap: 24px; margin-bottom: 24px; padding: 16px; border: 1px solid #d7dfe5; border-radius: 8px; box-shadow: 0 3px 12px #1024390d; }
        .service h2 { margin: 0; color: #102439; font-size: 15px; }
        .service p { margin: 16px 0 0; color: #526b7e; font-size: 14px; line-height: 1.5; }
        .price { flex: none; font-size: 14px; white-space: nowrap; }
        .item-slider { display: flex; align-items: center; gap: 6px; margin: 0 0 32px; } .item-tabs { display: flex; gap: 24px; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: thin; border-bottom: 1px solid #e4ebef; flex: 1; min-width: 0; } .item-tabs::-webkit-scrollbar { height: 7px; } .item-tabs::-webkit-scrollbar-thumb { background: #9ba5aa; border-radius: 8px; } .item-tabs a { display: block; flex: none; text-align: center; white-space: nowrap; scroll-snap-align: start; padding: 12px 4px; color: #71818c; text-decoration: none; font-size: 14px; } .item-tabs a.active { color: #058df0; border-bottom: 2px solid #058df0; font-weight: 700; } .item-tabs a:focus-visible { outline: 2px solid #058df0; outline-offset: -2px; } .item-group { scroll-margin-top: 16px; margin-bottom: 26px; } .item-group.highlight { animation: item-highlight .8s ease-out; } @keyframes item-highlight { from { background: #e3f6ff; } to { background: transparent; } } @media (prefers-reduced-motion: reduce) { .item-group.highlight { animation: none; } } .item-group h2 { font-size: 15px; margin: 0 0 12px; } .item-service { display: flex; justify-content: space-between; gap: 20px; padding: 14px 0; font-size: 14px; } .item-service p { margin: 6px 0 0; color: #526b7e; }
        .empty { padding: 24px; border-radius: 8px; background: #e7f7fe; color: #526b7e; line-height: 1.5; }
        @media (max-width: 600px) { main { padding: 16px 16px 40px; } .tab-slider { margin: 24px 0; gap: 4px; } .tabs { grid-auto-columns: calc((100% - 8px) / 2); } .tab { font-size: 12px; padding: 11px 13px; } .service { align-items: flex-start; flex-direction: column; gap: 12px; } .service p { margin-top: 8px; } }
    </style>
</head>
<body>
<main>
    <a class="back" href="{{ url('/') }}"><span aria-hidden="true">&#8249;</span><h1>Service list</h1></a>
    <div class="tab-slider">
    <button class="slider-arrow" type="button" data-prev aria-label="Previous categories" aria-controls="category-tabs">&#8249;</button>
    <nav id="category-tabs" class="tabs" aria-label="Service categories">
        @if ($hasOtherServices)
            <a class="tab {{ ! request('category') || request('category') === 'other-services' ? 'active' : '' }}" href="{{ route('services.index', ['category' => 'other-services']) }}" @if (! request('category') || request('category') === 'other-services') aria-current="page" @endif>Other services</a>
        @endif
        @foreach ($categories as $tab)
            <a class="tab {{ $category?->id === $tab->id ? 'active' : '' }}" href="{{ route('services.index', ['category' => $tab->slug]) }}" @if ($category?->id === $tab->id) aria-current="page" @endif>{{ $tab->name }}</a>
        @endforeach
    </nav>
    <button class="slider-arrow" type="button" data-next aria-label="Next categories" aria-controls="category-tabs">&#8250;</button>
    </div>
    <section aria-label="{{ $category?->name ?? 'Other services' }}">
        @if ($items->isNotEmpty())
            <div class="item-slider">
            <nav id="item-tabs" class="item-tabs" aria-label="Items">
                @foreach ($items as $item)
                    <a href="#item-{{ $item->id }}" class="{{ $loop->first ? 'active' : '' }}">{{ $item->name }}</a>
                @endforeach
                @if ($services->whereNull('item_id')->isNotEmpty()) <a href="#other-category-services">Other services</a> @endif
            </nav>
            </div>
            @foreach ($items as $item)
                <section class="item-group" id="item-{{ $item->id }}">
                    <h2>{{ $item->name }}</h2>
                    @forelse ($services->where('item_id', $item->id) as $service)
                        <div class="item-service"><div>{{ $service->name }}@if ($service->description)<p>{{ $service->description }}</p>@endif</div><span class="price">£{{ number_format((float) $service->price, 2) }}@if ($service->unit) / {{ $service->unit->short_name ?: $service->unit->name }}@endif</span></div>
                    @empty
                        <p>No services available for this item yet.</p>
                    @endforelse
                </section>
            @endforeach
            @if ($services->whereNull('item_id')->isNotEmpty())
                <section class="item-group" id="other-category-services"><h2>Other services</h2>
                    @foreach ($services->whereNull('item_id') as $service)
                        <div class="item-service"><div>{{ $service->name }}@if ($service->description)<p>{{ $service->description }}</p>@endif</div><span class="price">£{{ number_format((float) $service->price, 2) }}@if ($service->unit) / {{ $service->unit->short_name ?: $service->unit->name }}@endif</span></div>
                    @endforeach
                </section>
            @endif
        @else
            @forelse ($services as $service)
                <article class="service">
                    <div><h2>{{ $service->name }}</h2>@if ($service->description)<p>{{ $service->description }}</p>@endif</div>
                    <div class="price">£{{ number_format((float) $service->price, 2) }}@if ($service->unit) / {{ $service->unit->short_name ?: $service->unit->name }}@endif</div>
                </article>
            @empty
                <div class="empty">No services are available in this category yet.</div>
            @endforelse
        @endif
    </section>
</main>
<script>
    (() => {
        const tabs = document.querySelector('#category-tabs');
        const prev = document.querySelector('[data-prev]');
        const next = document.querySelector('[data-next]');
        if (!tabs || !prev || !next) return;
        const update = () => { prev.disabled = tabs.scrollLeft <= 1; next.disabled = tabs.scrollLeft + tabs.clientWidth >= tabs.scrollWidth - 1; };
        const move = direction => { const tab = tabs.querySelector('.tab'); if (tab) tabs.scrollBy({ left: direction * (tab.offsetWidth + 8), behavior: 'smooth' }); };
        prev.addEventListener('click', () => move(-1));
        next.addEventListener('click', () => move(1));
        tabs.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        const active = tabs.querySelector('.tab.active');
        if (active) tabs.scrollLeft = active.offsetLeft - tabs.offsetLeft;
        update();
    })();
</script>
<script>
    (() => {
        const links = document.querySelectorAll('.item-tabs a');
        links.forEach(link => link.addEventListener('click', event => {
            const target = document.getElementById(link.getAttribute('href').slice(1));
            if (!target) return;
            event.preventDefault();
            links.forEach(item => item.classList.remove('active'));
            link.classList.add('active');
            target.classList.remove('highlight');
            void target.offsetWidth;
            target.classList.add('highlight');
            const firstGroup = target.parentElement.querySelector('.item-group');
            if (firstGroup && firstGroup !== target) firstGroup.before(target);

        }));
    })();
</script>
</body>
</html>










