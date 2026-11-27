<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HomepageContentVersion;
use App\Models\HomepageMedia;
use App\Services\HomepageContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;

class HomepageContentController extends Controller
{
    public function __construct(
        protected HomepageContentService $cms
    ) {}

    public function index(): View
    {
        $record = $this->cms->getOrInitializeRecord()->load(['lastDraftSavedBy:id,name', 'publishedBy:id,name']);
        $defaults = HomepageContentService::defaultPayload();
        $draft = array_replace_recursive($defaults, is_array($record->draft_payload) ? $record->draft_payload : []);

        $categories = ItemCategory::query()->orderBy('id')->get();
        $services = Service::query()->with(['unit:id,name', 'category:id,name'])->orderBy('name')->orderBy('id')->get();
        $mediaItems = HomepageMedia::query()
            ->whereNull('archived_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (HomepageMedia $m) => $m->toSafeArray($this->cms->isMediaInPublishedUse($m)))
            ->values();

        $versions = HomepageContentVersion::query()
            ->with('createdByAdmin:id,name')
            ->where('homepage_content_id', $record->id)
            ->orderByDesc('version_number')
            ->limit(25)
            ->get();

        return view('admin::website-manager.homepage', [
            'record' => $record,
            'draft' => $draft,
            'categories' => $categories,
            'services' => $services,
            'mediaItems' => $mediaItems,
            'versions' => $versions,
            'cms' => $this->cms,
        ]);
    }

    public function saveSections(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.key' => ['required', Rule::in(['hero', 'categories', 'promo_banner', 'services'])],
            'sections.*.label' => ['required', 'string', 'max:80'],
            'sections.*.subtitle' => ['nullable', 'string', 'max:200'],
            'sections.*.visible' => ['required', 'boolean'],
            'sections.*.order' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $sections = collect($validated['sections'])
            ->sortBy('order')
            ->values()
            ->all();

        $record = $this->cms->saveDraftSection('sections', $sections, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Section order & visibility saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveHero(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'badge' => ['nullable', 'string', 'max:80'],
            'headline' => ['required', 'string', 'max:180'],
            'show_heart_icon' => ['nullable', 'boolean'],
            'subtitle' => ['nullable', 'string', 'max:600'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'image_alt' => ['nullable', 'string', 'max:160'],
            'primary_btn_label' => ['nullable', 'string', 'max:60'],
            'primary_btn_url' => ['nullable', 'string', 'max:255'],
            'primary_btn_visible' => ['nullable', 'boolean'],
            'secondary_btn_label' => ['nullable', 'string', 'max:60'],
            'secondary_btn_url' => ['nullable', 'string', 'max:255'],
            'secondary_btn_visible' => ['nullable', 'boolean'],
            'bg_preset' => ['required', Rule::in(['cream', 'white', 'soft_yellow'])],
            'highlights' => ['nullable', 'array', 'max:3'],
            'highlights.*.value' => ['nullable', 'string', 'max:30'],
            'highlights.*.label' => ['nullable', 'string', 'max:50'],
        ]);

        $payload = [
            'badge' => trim((string) ($validated['badge'] ?? '')),
            'headline' => trim((string) $validated['headline']),
            'show_heart_icon' => $request->boolean('show_heart_icon'),
            'subtitle' => trim((string) ($validated['subtitle'] ?? '')),
            'image_url' => trim((string) ($validated['image_url'] ?? '')),
            'image_alt' => trim((string) ($validated['image_alt'] ?? 'FreshGo Laundry')),
            'primary_btn_label' => trim((string) ($validated['primary_btn_label'] ?? '')),
            'primary_btn_url' => $this->cms->sanitizeDestinationUrl($validated['primary_btn_url'] ?? '/booking/services', 'primary_btn_url'),
            'primary_btn_visible' => $request->boolean('primary_btn_visible'),
            'secondary_btn_label' => trim((string) ($validated['secondary_btn_label'] ?? '')),
            'secondary_btn_url' => $this->cms->sanitizeDestinationUrl($validated['secondary_btn_url'] ?? '/services', 'secondary_btn_url'),
            'secondary_btn_visible' => $request->boolean('secondary_btn_visible'),
            'bg_preset' => $validated['bg_preset'],
            'highlights' => collect($validated['highlights'] ?? [])
                ->filter(fn ($h) => trim((string) ($h['value'] ?? '')) !== '' || trim((string) ($h['label'] ?? '')) !== '')
                ->map(fn ($h) => ['value' => trim((string) ($h['value'] ?? '')), 'label' => trim((string) ($h['label'] ?? ''))])
                ->values()
                ->all(),
        ];

        $record = $this->cms->saveDraftSection('hero', $payload, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Hero Banner saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visible' => ['nullable', 'boolean'],
            'placeholder' => ['required', 'string', 'max:140'],
            'button_text' => ['required', 'string', 'max:40'],
            'show_icon' => ['nullable', 'boolean'],
            'show_categories' => ['nullable', 'boolean'],
            'show_services' => ['nullable', 'boolean'],
            'show_items' => ['nullable', 'boolean'],
            'max_suggestions' => ['required', 'integer', 'min:1', 'max:15'],
        ]);

        $payload = [
            'visible' => $request->boolean('visible'),
            'placeholder' => trim($validated['placeholder']),
            'button_text' => trim($validated['button_text']),
            'show_icon' => $request->boolean('show_icon'),
            'show_categories' => $request->boolean('show_categories'),
            'show_services' => $request->boolean('show_services'),
            'show_items' => $request->boolean('show_items'),
            'max_suggestions' => (int) $validated['max_suggestions'],
        ];

        $record = $this->cms->saveDraftSection('search', $payload, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Homepage Search settings saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveCategories(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:250'],
            'carousel_enabled' => ['nullable', 'boolean'],
            'autoplay' => ['nullable', 'boolean'],
            'interval_ms' => ['required', 'integer', 'min:1500', 'max:15000'],
            'show_arrows' => ['nullable', 'boolean'],
            'pause_on_hover' => ['nullable', 'boolean'],
            'loop' => ['nullable', 'boolean'],
            'desktop_per_view' => ['required', 'integer', Rule::in([2, 3, 4])],
            'mode' => ['required', Rule::in(['all_active', 'selected'])],
            'overrides' => ['nullable', 'array'],
            'overrides.*.visible' => ['nullable', 'boolean'],
            'overrides.*.order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'overrides.*.custom_description' => ['nullable', 'string', 'max:220'],
            'overrides.*.custom_image_url' => ['nullable', 'string', 'max:255'],
        ]);

        $overrides = [];
        foreach (($validated['overrides'] ?? []) as $catId => $ov) {
            $id = (int) $catId;
            if ($id <= 0) {
                continue;
            }
            $overrides[$id] = [
                'visible' => ! empty($ov['visible']),
                'order' => (int) ($ov['order'] ?? $id),
                'custom_description' => trim((string) ($ov['custom_description'] ?? '')),
                'custom_image_url' => trim((string) ($ov['custom_image_url'] ?? '')),
            ];
        }

        $payload = [
            'title' => trim($validated['title']),
            'subtitle' => trim((string) ($validated['subtitle'] ?? '')),
            'carousel_enabled' => $request->boolean('carousel_enabled'),
            'autoplay' => $request->boolean('autoplay'),
            'interval_ms' => (int) $validated['interval_ms'],
            'show_arrows' => $request->boolean('show_arrows'),
            'pause_on_hover' => $request->boolean('pause_on_hover'),
            'loop' => $request->boolean('loop'),
            'desktop_per_view' => (int) $validated['desktop_per_view'],
            'mode' => $validated['mode'],
            'overrides' => $overrides,
        ];

        $record = $this->cms->saveDraftSection('categories_config', $payload, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Laundry Categories & Carousel settings saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function savePromoBanner(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'badge' => ['nullable', 'string', 'max:80'],
            'headline' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'button_label' => ['nullable', 'string', 'max:60'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'button_visible' => ['nullable', 'boolean'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'image_alt' => ['nullable', 'string', 'max:160'],
            'bg_preset' => ['required', Rule::in(['gold_gradient', 'cream', 'dark_gold'])],
        ]);

        $payload = [
            'badge' => trim((string) ($validated['badge'] ?? '')),
            'headline' => trim($validated['headline']),
            'description' => trim((string) ($validated['description'] ?? '')),
            'button_label' => trim((string) ($validated['button_label'] ?? '')),
            'button_url' => $this->cms->sanitizeDestinationUrl($validated['button_url'] ?? '/booking/services', 'button_url'),
            'button_visible' => $request->boolean('button_visible'),
            'image_url' => trim((string) ($validated['image_url'] ?? '')),
            'image_alt' => trim((string) ($validated['image_alt'] ?? 'FreshGo Express Care')),
            'bg_preset' => $validated['bg_preset'],
        ];

        $record = $this->cms->saveDraftSection('promo_banner', $payload, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Promotional Banner saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveServices(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:250'],
            'catalog_link_text' => ['nullable', 'string', 'max:60'],
            'carousel_enabled' => ['nullable', 'boolean'],
            'autoplay' => ['nullable', 'boolean'],
            'interval_ms' => ['required', 'integer', 'min:1500', 'max:15000'],
            'show_arrows' => ['nullable', 'boolean'],
            'pause_on_hover' => ['nullable', 'boolean'],
            'loop' => ['nullable', 'boolean'],
            'desktop_per_view' => ['required', 'integer', Rule::in([2, 3, 4])],
            'mode' => ['required', Rule::in(['all_active', 'selected'])],
            'overrides' => ['nullable', 'array'],
            'overrides.*.visible' => ['nullable', 'boolean'],
            'overrides.*.order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'overrides.*.custom_badge' => ['nullable', 'string', 'max:50'],
            'overrides.*.custom_description' => ['nullable', 'string', 'max:220'],
            'overrides.*.custom_image_url' => ['nullable', 'string', 'max:255'],
        ]);

        $overrides = [];
        foreach (($validated['overrides'] ?? []) as $svcId => $ov) {
            $id = (int) $svcId;
            if ($id <= 0) {
                continue;
            }
            $overrides[$id] = [
                'visible' => ! empty($ov['visible']),
                'order' => (int) ($ov['order'] ?? $id),
                'custom_badge' => trim((string) ($ov['custom_badge'] ?? '')),
                'custom_description' => trim((string) ($ov['custom_description'] ?? '')),
                'custom_image_url' => trim((string) ($ov['custom_image_url'] ?? '')),
            ];
        }

        $payload = [
            'title' => trim($validated['title']),
            'subtitle' => trim((string) ($validated['subtitle'] ?? '')),
            'catalog_link_text' => trim((string) ($validated['catalog_link_text'] ?? 'View Full Catalog →')),
            'carousel_enabled' => $request->boolean('carousel_enabled'),
            'autoplay' => $request->boolean('autoplay'),
            'interval_ms' => (int) $validated['interval_ms'],
            'show_arrows' => $request->boolean('show_arrows'),
            'pause_on_hover' => $request->boolean('pause_on_hover'),
            'loop' => $request->boolean('loop'),
            'desktop_per_view' => (int) $validated['desktop_per_view'],
            'mode' => $validated['mode'],
            'overrides' => $overrides,
        ];

        $record = $this->cms->saveDraftSection('services_config', $payload, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Popular Services & Carousel settings saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveSeo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'string', 'max:255'],
            'og_title' => ['nullable', 'string', 'max:160'],
            'og_description' => ['nullable', 'string', 'max:320'],
            'og_image_url' => ['nullable', 'string', 'max:255'],
            'robots' => ['required', Rule::in(['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'])],
        ]);

        $payload = [
            'meta_title' => trim((string) ($validated['meta_title'] ?? '')),
            'meta_description' => trim((string) ($validated['meta_description'] ?? '')),
            'canonical_url' => $this->cms->sanitizeOptionalUrl($validated['canonical_url'] ?? '', 'canonical_url'),
            'og_title' => trim((string) ($validated['og_title'] ?? '')),
            'og_description' => trim((string) ($validated['og_description'] ?? '')),
            'og_image_url' => trim((string) ($validated['og_image_url'] ?? '')),
            'robots' => $validated['robots'],
        ];

        $record = $this->cms->saveDraftSection('seo', $payload, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'SEO settings saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $record = $this->cms->publish((int) Auth::guard('admin')->id(), $request->input('notes'));

        return response()->json([
            'status' => 'ok',
            'message' => 'Homepage changes published live!',
            'published_at' => $record->published_at?->format('d M Y, H:i'),
            'version' => $record->current_version_number,
        ]);
    }

    public function restoreVersion(HomepageContentVersion $version): JsonResponse
    {
        $record = $this->cms->restoreVersionToDraft($version, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => "Version #{$version->version_number} restored to Draft. Preview or Publish to go live.",
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $media = $this->cms->uploadMedia($request->file('image'), $request->input('alt_text'), (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Image uploaded to Homepage Media Library.',
            'media' => $media->toSafeArray(false),
        ], 201);
    }

    public function deleteMedia(HomepageMedia $media): JsonResponse
    {
        $this->cms->deleteMedia($media);

        return response()->json([
            'status' => 'ok',
            'message' => 'Media item deleted.',
        ]);
    }
}
