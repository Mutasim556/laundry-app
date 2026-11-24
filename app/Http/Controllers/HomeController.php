<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;

class HomeController extends Controller
{
    /**
     * Default icon mapping for category slugs when no uploaded image is present.
     *
     * @return array<string, string>
     */
    public static function categoryIconMap(): array
    {
        return [
            'wash-fold' => '01_washing_machine.png',
            'wash-iron' => '12_clean_shirt.png',
            'dry-cleaning' => '06_shirt_hanger.png',
            'ironing-only' => '05_iron.png',
            'back-wash-iron' => '08_wash_tub.png',
            'bulky-item-duvet' => '07_folded_laundry.png',
            'repairs' => '13_repairs.svg',
        ];
    }

    public function index(): View
    {
        $categories = ItemCategory::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $services = Service::query()
            ->with(['unit', 'category', 'item.category'])
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('item_id')
                    ->orWhereHas('item', fn ($iq) => $iq->where('is_active', true));
            })
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $categoryIcons = self::categoryIconMap();

        return view('welcome', [
            'categories' => $categories,
            'services' => $services,
            'categoryIcons' => $categoryIcons,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $term = trim((string) $validated['q']);
        if (mb_strlen($term) < 2) {
            return response()->json([
                'success' => true,
                'query' => $term,
                'total' => 0,
                'groups' => [
                    'categories' => [],
                    'services' => [],
                    'items' => [],
                ],
            ]);
        }

        $like = '%' . addcslashes($term, '%_\\') . '%';
        $iconMap = self::categoryIconMap();

        $categories = ItemCategory::query()
            ->where('is_active', true)
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(function (ItemCategory $cat) use ($iconMap) {
                $fallback = $iconMap[$cat->slug] ?? '03_laundry_basket.png';

                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'type' => 'Category',
                    'description' => $cat->short_description ?: $cat->description,
                    'price_label' => null,
                    'image_url' => $cat->image_url ?: asset('images/laundry-icons/' . $fallback),
                    'url' => route('services.index', ['category' => $cat->slug]),
                ];
            })
            ->values();

        $items = Item::query()
            ->with('category:id,name,slug,is_active')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('item_category_id')
                    ->orWhereHas('category', fn ($cq) => $cq->where('is_active', true));
            })
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(function (Item $item) use ($iconMap) {
                $cat = $item->category;
                $fallback = $iconMap[$cat?->slug ?? ''] ?? '12_clean_shirt.png';
                $url = $cat
                    ? route('services.index', ['category' => $cat->slug, 'item' => $item->id])
                    : route('services.index', ['q' => $item->name]);

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => 'Laundry Item',
                    'description' => $cat ? $cat->name : $item->description,
                    'price_label' => null,
                    'image_url' => $item->image_url ?: asset('images/laundry-icons/' . $fallback),
                    'url' => $url,
                ];
            })
            ->values();

        $services = Service::query()
            ->with(['unit:id,name', 'category:id,name,slug', 'item:id,name,item_category_id', 'item.category:id,name,slug'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('item_id')
                    ->orWhereHas('item', fn ($iq) => $iq->where('is_active', true));
            })
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', $like))
                    ->orWhereHas('item', fn ($iq) => $iq->where('name', 'like', $like));
            })
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(function (Service $svc) use ($iconMap) {
                $catSlug = $svc->category?->slug ?? $svc->item?->category?->slug;
                $fallback = $iconMap[$catSlug ?? ''] ?? '03_laundry_basket.png';
                $params = array_filter([
                    'category' => $catSlug,
                    'item' => $svc->item_id,
                    'q' => $svc->name,
                ], fn ($v) => $v !== null && $v !== '');

                $priceLabel = $svc->price !== null
                    ? formatMoney($svc->price, $svc->currency_code ?? null) . ($svc->unit?->name ? ' / ' . $svc->unit->name : '')
                    : null;

                return [
                    'id' => $svc->id,
                    'name' => $svc->name,
                    'type' => 'Service',
                    'description' => $svc->category?->name ?? $svc->description,
                    'price_label' => $priceLabel,
                    'image_url' => $svc->image_url ?: asset('images/laundry-icons/' . $fallback),
                    'url' => route('services.index', $params),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'query' => $term,
            'total' => $categories->count() + $services->count() + $items->count(),
            'groups' => [
                'categories' => $categories,
                'services' => $services,
                'items' => $items,
            ],
        ]);
    }
}
