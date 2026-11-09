<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;

Route::get('/', function () {
    return view('welcome', ['categories' => ItemCategory::query()->where('is_active', true)->orderBy('id')->get()]);
});

Route::get('/services', function (Request $request) {
    $filters = $request->validate([
        'category' => ['nullable', 'string', 'max:255'],
        'item' => ['nullable', 'string', 'max:20'],
        'q' => ['nullable', 'string', 'max:100'],
        'sort' => ['nullable', Rule::in(['name', 'price-low', 'price-high'])],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);

    $categories = ItemCategory::query()->where('is_active', true)->orderBy('id')->get();
    $hasOtherServices = Service::query()->where('is_active', true)->whereNull('item_category_id')->whereNull('item_id')->exists();
    $selected = $filters['category'] ?? null;

    if ($selected === null && ! $hasOtherServices && $categories->isNotEmpty()) {
        return redirect()->route('services.index', array_merge($request->query(), ['category' => $categories->first()->slug]));
    }

    $category = $categories->firstWhere('slug', $selected);
    abort_if($selected !== null && $selected !== 'other-services' && ! $category, 404);

    $items = $category ? Item::query()->where('item_category_id', $category->id)->where('is_active', true)->orderBy('name')->get() : collect();
    $selectedItem = $filters['item'] ?? null;
    abort_if($selectedItem !== null && $selectedItem !== 'other' && ! $items->contains(fn ($item) => (string) $item->id === $selectedItem), 404);
    $search = trim($filters['q'] ?? '');
    $sort = $filters['sort'] ?? 'name';

    $query = Service::query()->with(['unit', 'item'])->where('is_active', true)
        ->where(fn ($query) => $query->whereNull('item_id')->orWhereHas('item', fn ($query) => $query->where('is_active', true)))
        ->when($category, fn ($query) => $query->where(function ($query) use ($category) {
            $query->where('item_category_id', $category->id)
                ->orWhere(fn ($query) => $query->whereNull('item_category_id')->whereHas('item', fn ($query) => $query->where('item_category_id', $category->id)));
        }))
        ->when($selected === 'other-services', fn ($query) => $query->whereNull('item_category_id')->whereNull('item_id'))
        ->when($selected === null, fn ($query) => $query->whereNull('item_category_id')->whereNull('item_id'));

    $hasCategoryServices = (clone $query)->whereNull('item_id')->exists();

    $query->when($selectedItem === 'other', fn ($query) => $query->whereNull('item_id'))
        ->when($selectedItem !== null && $selectedItem !== 'other', fn ($query) => $query->where('item_id', $selectedItem))
        ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
            $query->where('name', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%')
                ->orWhereHas('item', fn ($query) => $query->where('name', 'like', '%'.$search.'%'));
        }));

    if ($sort === 'price-low' || $sort === 'price-high') {
        $query->orderBy('price', $sort === 'price-low' ? 'asc' : 'desc');
    }

    $services = $query->orderBy('name')->orderBy('id')->paginate(12)->withQueryString();
    $data = compact('categories', 'category', 'selected', 'services', 'items', 'hasOtherServices', 'selectedItem', 'search', 'sort', 'hasCategoryServices');

    if ($request->expectsJson()) {
        return response()->json([
            'html' => view('services.results', $data)->render(),
            'category' => $category?->slug ?? 'other-services',
            'title' => $category?->name ?? 'Other services',
            'total' => $services->total(),
        ]);
    }

    return view('services.index', $data);
})->name('services.index');
