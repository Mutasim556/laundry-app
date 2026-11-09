<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\Service;

Route::get('/', function () {
    return view('welcome', ['categories' => ItemCategory::query()->where('is_active', true)->orderBy('id')->get()]);
});

Route::get('/services', function (Request $request) {
    $categories = ItemCategory::query()->where('is_active', true)->orderBy('id')->get();
    $selected = $request->query('category');
    $category = $categories->firstWhere('slug', $selected);
    abort_if($selected && $selected !== 'other-services' && ! $category, 404);

    $services = Service::query()->with('unit')->where('is_active', true)
        ->when($category, fn ($query) => $query->where(function ($query) use ($category) {
            $query->where('item_category_id', $category->id)
                ->orWhere(fn ($query) => $query->whereNull('item_category_id')->whereHas('item', fn ($query) => $query->where('item_category_id', $category->id)));
        }))
        ->when($selected === 'other-services', fn ($query) => $query->whereNull('item_category_id')->whereNull('item_id'))
        ->when(! $selected, fn ($query) => $query->whereNull('item_category_id')->whereNull('item_id'))
        ->orderBy('name')->get();

    $items = $category ? Item::query()->where('item_category_id', $category->id)->where('is_active', true)->orderBy('name')->get() : collect();
    $hasOtherServices = Service::query()->where('is_active', true)->whereNull('item_category_id')->whereNull('item_id')->exists();

    if (! $selected && ! $hasOtherServices && $categories->isNotEmpty()) {
        return redirect()->route('services.index', ['category' => $categories->first()->slug]);
    }

    return view('services.index', compact('categories', 'category', 'services', 'items', 'hasOtherServices'));
})->name('services.index');

