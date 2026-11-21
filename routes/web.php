<?php

use App\Http\Controllers\BookingAddressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;

Route::get('/', function () {
    $categories = ItemCategory::query()->where('is_active', true)->orderBy('id')->get();
    $popularServices = Service::query()->with(['unit', 'category'])->where('is_active', true)->take(8)->get();

    return view('welcome', compact('categories', 'popularServices'));
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
            'summary' => view('services.category-summary', $data)->render(),
            'category' => $category?->slug ?? 'other-services',
            'title' => $category?->name ?? 'Other services',
            'total' => $services->total(),
        ]);
    }

    return view('services.index', $data);
})->name('services.index');

// Booking Flow: 5-Step Connected Progress Workflow
Route::prefix('booking')->name('booking.')->group(function () {
    // Step 1: Select Services
    Route::get('/services', [BookingAddressController::class, 'showServicesStep'])->name('services');
    Route::post('/services', [BookingAddressController::class, 'storeServicesStep'])->name('services.store');

    // Step 2: Collection Address / Hotel
    Route::get('/address', [BookingAddressController::class, 'showAddressStep'])->name('address');
    Route::post('/address', [BookingAddressController::class, 'storeAddress'])->name('address.store');
    Route::get('/postcode-lookup', [BookingAddressController::class, 'lookupPostcode'])->name('postcode.lookup');
    Route::get('/hotel-search', [BookingAddressController::class, 'searchHotels'])->name('hotel.search');

    // Step 3: Schedule Collection & Delivery
    Route::get('/schedule', [BookingAddressController::class, 'showScheduleStep'])->name('schedule');
    Route::post('/schedule', [BookingAddressController::class, 'storeScheduleStep'])->name('schedule.store');

    // Step 4: Customer Details & Notes
    Route::get('/details', [BookingAddressController::class, 'showDetailsStep'])->name('details');
    Route::post('/details', [BookingAddressController::class, 'storeDetailsStep'])->name('details.store');

    // Step 5: Review & Confirm
    Route::get('/confirm', [BookingAddressController::class, 'showConfirmStep'])->name('confirm');
    Route::post('/confirm', [BookingAddressController::class, 'storeConfirmStep'])->name('confirm.store');
    Route::post('/confirm/options', [BookingAddressController::class, 'updateConfirmOptions'])->name('confirm.options');
});


