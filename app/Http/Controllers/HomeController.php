<?php

namespace App\Http\Controllers;

use App\Services\HomepageContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;

class HomeController extends Controller
{
    public function __construct(
        protected HomepageContentService $cms
    ) {}
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

    public function index(Request $request): View
    {
        $isPreview = $request->query('preview') === 'draft'
            && Auth::guard('admin')->check()
            && Auth::guard('admin')->user()?->can('manage-settings', 'admin');

        $payload = $this->cms->getPublicPayload($isPreview);
        $categoryIcons = self::categoryIconMap();
        $rendered = $this->cms->buildPublicRenderData($payload, $categoryIcons);

        return view('welcome', [
            'homepagePayload' => $payload,
            'homepageSections' => $rendered['sections'],
            'categories' => $rendered['categories'],
            'services' => $rendered['services'],
            'categoryIcons' => $categoryIcons,
            'isPreviewDraft' => $isPreview,
            'cms' => $this->cms,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $payload = $this->cms->getPublicPayload(false);
        $searchCfg = $payload['search'] ?? [];
        $maxLimit = max(1, min(15, (int) ($searchCfg['max_suggestions'] ?? 5)));
        $showCategories = array_key_exists('show_categories', $searchCfg) ? (bool) $searchCfg['show_categories'] : true;
        $showServices = array_key_exists('show_services', $searchCfg) ? (bool) $searchCfg['show_services'] : true;
        $showItems = array_key_exists('show_items', $searchCfg) ? (bool) $searchCfg['show_items'] : true;

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

        $categories = ! $showCategories ? collect() : ItemCategory::query()
            ->where('is_active', true)
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->orderBy('name')
            ->limit($maxLimit)
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

        $items = ! $showItems ? collect() : Item::query()
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
            ->limit($maxLimit)
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

        $services = ! $showServices ? collect() : Service::query()
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
            ->limit($maxLimit)
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
