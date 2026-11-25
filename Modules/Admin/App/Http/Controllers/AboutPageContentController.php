<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HomepageContentVersion;
use App\Models\HomepageMedia;
use App\Services\AboutPageContentService;
use App\Services\HomepageContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AboutPageContentController extends Controller
{
    public function __construct(
        protected AboutPageContentService $cms,
        protected HomepageContentService $homepageCms
    ) {}

    public function index(): View
    {
        $record = $this->cms->getOrInitializeRecord()->load(['lastDraftSavedBy:id,name', 'publishedBy:id,name']);
        $defaults = AboutPageContentService::defaultPayload();
        $draft = array_replace_recursive($defaults, is_array($record->draft_payload) ? $record->draft_payload : []);

        $mediaItems = HomepageMedia::query()
            ->whereNull('archived_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (HomepageMedia $m) => $m->toSafeArray($this->homepageCms->isMediaInPublishedUse($m)))
            ->values();

        $versions = HomepageContentVersion::query()
            ->with('createdByAdmin:id,name')
            ->where('homepage_content_id', $record->id)
            ->orderByDesc('version_number')
            ->limit(25)
            ->get();

        return view('admin::website-manager.about', [
            'record' => $record,
            'draft' => $draft,
            'mediaItems' => $mediaItems,
            'versions' => $versions,
            'cms' => $this->cms,
        ]);
    }

    public function saveSections(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.key' => ['required', Rule::in(['hero', 'story', 'mission_vision', 'benefits', 'how_it_works', 'stats', 'cta'])],
            'sections.*.label' => ['required', 'string', 'max:80'],
            'sections.*.subtitle' => ['nullable', 'string', 'max:200'],
            'sections.*.visible' => ['required', 'boolean'],
            'sections.*.order' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $sections = collect($validated['sections'])->sortBy('order')->values()->all();
        $record = $this->cms->saveDraftSection('sections', $sections, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'About page section order & visibility saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveHero(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'badge' => ['nullable', 'string', 'max:80'],
            'headline' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'image_alt' => ['nullable', 'string', 'max:160'],
            'bg_preset' => ['required', Rule::in(['gold_soft', 'cream', 'white'])],
            'primary_btn_label' => ['nullable', 'string', 'max:60'],
            'primary_btn_url' => ['nullable', 'string', 'max:255'],
            'primary_btn_visible' => ['nullable', 'boolean'],
            'secondary_btn_label' => ['nullable', 'string', 'max:60'],
            'secondary_btn_url' => ['nullable', 'string', 'max:255'],
            'secondary_btn_visible' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'badge' => trim((string) ($validated['badge'] ?? '')),
            'headline' => trim($validated['headline']),
            'description' => trim((string) ($validated['description'] ?? '')),
            'image_url' => trim((string) ($validated['image_url'] ?? '')),
            'image_alt' => trim((string) ($validated['image_alt'] ?? 'Fresh Go Laundry Care')),
            'bg_preset' => $validated['bg_preset'],
            'primary_btn_label' => trim((string) ($validated['primary_btn_label'] ?? '')),
            'primary_btn_url' => $this->cms->sanitizeDestinationUrl($validated['primary_btn_url'] ?? '/booking/services', 'primary_btn_url'),
            'primary_btn_visible' => $request->boolean('primary_btn_visible'),
            'secondary_btn_label' => trim((string) ($validated['secondary_btn_label'] ?? '')),
            'secondary_btn_url' => $this->cms->sanitizeDestinationUrl($validated['secondary_btn_url'] ?? '/services', 'secondary_btn_url'),
            'secondary_btn_visible' => $request->boolean('secondary_btn_visible'),
        ];

        $record = $this->cms->saveDraftSection('hero', $payload, (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'About Hero saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
    }

    public function saveStory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'eyebrow' => ['nullable', 'string', 'max:60'],
            'heading' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:3000'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'image_alt' => ['nullable', 'string', 'max:160'],
        ]);

        $record = $this->cms->saveDraftSection('story', [
            'eyebrow' => trim((string) ($validated['eyebrow'] ?? '')),
            'heading' => trim($validated['heading']),
            'body' => trim($validated['body']),
            'image_url' => trim((string) ($validated['image_url'] ?? '')),
            'image_alt' => trim((string) ($validated['image_alt'] ?? 'Fresh Go Story')),
        ], (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'Our Story saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
    }

    public function saveMissionVision(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mission_visible' => ['nullable', 'boolean'],
            'mission_title' => ['required', 'string', 'max:120'],
            'mission_description' => ['required', 'string', 'max:600'],
            'vision_visible' => ['nullable', 'boolean'],
            'vision_title' => ['nullable', 'string', 'max:120'],
            'vision_description' => ['nullable', 'string', 'max:600'],
        ]);

        $record = $this->cms->saveDraftSection('mission_vision', [
            'mission_visible' => $request->boolean('mission_visible'),
            'mission_title' => trim($validated['mission_title']),
            'mission_description' => trim($validated['mission_description']),
            'vision_visible' => $request->boolean('vision_visible'),
            'vision_title' => trim((string) ($validated['vision_title'] ?? 'Our Vision')),
            'vision_description' => trim((string) ($validated['vision_description'] ?? '')),
        ], (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'Mission & Vision saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
    }

    public function saveBenefits(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:240'],
            'items' => ['nullable', 'array', 'max:12'],
            'items.*.title' => ['required', 'string', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:300'],
            'items.*.visible' => ['nullable', 'boolean'],
            'items.*.order' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $items = collect($validated['items'] ?? [])
            ->map(fn ($it, $idx) => [
                'title' => trim($it['title']),
                'description' => trim($it['description']),
                'icon' => 'check',
                'visible' => ! empty($it['visible']),
                'order' => (int) ($it['order'] ?? ($idx + 1)),
            ])
            ->sortBy('order')
            ->values()
            ->all();

        $record = $this->cms->saveDraftSection('benefits', [
            'title' => trim($validated['title']),
            'subtitle' => trim((string) ($validated['subtitle'] ?? '')),
            'items' => $items,
        ], (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'Benefits saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
    }

    public function saveHowItWorks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:240'],
            'steps' => ['required', 'array', 'min:1', 'max:8'],
            'steps.*.step' => ['required', 'string', 'max:10'],
            'steps.*.title' => ['required', 'string', 'max:100'],
            'steps.*.description' => ['required', 'string', 'max:300'],
            'steps.*.visible' => ['nullable', 'boolean'],
            'steps.*.order' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $steps = collect($validated['steps'])
            ->map(fn ($st, $idx) => [
                'step' => trim($st['step']),
                'title' => trim($st['title']),
                'description' => trim($st['description']),
                'visible' => ! empty($st['visible']),
                'order' => (int) ($st['order'] ?? ($idx + 1)),
            ])
            ->sortBy('order')
            ->values()
            ->all();

        $record = $this->cms->saveDraftSection('how_it_works', [
            'title' => trim($validated['title']),
            'subtitle' => trim((string) ($validated['subtitle'] ?? '')),
            'steps' => $steps,
        ], (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'How Fresh Go Works saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
    }

    public function saveStats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'items' => ['nullable', 'array', 'max:8'],
            'items.*.value' => ['nullable', 'string', 'max:40'],
            'items.*.label' => ['nullable', 'string', 'max:80'],
        ]);

        $items = collect($validated['items'] ?? [])
            ->filter(fn ($it) => trim((string) ($it['value'] ?? '')) !== '' && trim((string) ($it['label'] ?? '')) !== '')
            ->map(fn ($it) => ['value' => trim($it['value']), 'label' => trim($it['label'])])
            ->values()
            ->all();

        $record = $this->cms->saveDraftSection('stats', [
            'title' => trim((string) ($validated['title'] ?? '')),
            'items' => $items,
        ], (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'Statistics saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
    }

    public function saveCta(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'headline' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:400'],
            'button_label' => ['nullable', 'string', 'max:60'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'button_visible' => ['nullable', 'boolean'],
            'bg_preset' => ['required', Rule::in(['gold_gradient', 'cream', 'dark_gold'])],
        ]);

        $record = $this->cms->saveDraftSection('cta', [
            'headline' => trim($validated['headline']),
            'description' => trim((string) ($validated['description'] ?? '')),
            'button_label' => trim((string) ($validated['button_label'] ?? '')),
            'button_url' => $this->cms->sanitizeDestinationUrl($validated['button_url'] ?? '/booking/services', 'button_url'),
            'button_visible' => $request->boolean('button_visible'),
            'bg_preset' => $validated['bg_preset'],
        ], (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'About CTA saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
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

        $record = $this->cms->saveDraftSection('seo', [
            'meta_title' => trim((string) ($validated['meta_title'] ?? '')),
            'meta_description' => trim((string) ($validated['meta_description'] ?? '')),
            'canonical_url' => $this->cms->sanitizeOptionalUrl($validated['canonical_url'] ?? '', 'canonical_url'),
            'og_title' => trim((string) ($validated['og_title'] ?? '')),
            'og_description' => trim((string) ($validated['og_description'] ?? '')),
            'og_image_url' => trim((string) ($validated['og_image_url'] ?? '')),
            'robots' => $validated['robots'],
        ], (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'About SEO saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
    }

    public function publish(Request $request): JsonResponse
    {
        $record = $this->cms->publish((int) Auth::guard('admin')->id(), $request->input('notes'));

        return response()->json([
            'status' => 'ok',
            'message' => 'About Us page published live!',
            'published_at' => $record->published_at?->format('d M Y, H:i'),
        ]);
    }

    public function restoreVersion(HomepageContentVersion $version): JsonResponse
    {
        $record = $this->cms->restoreVersionToDraft($version, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => "Version #{$version->version_number} restored to Draft.",
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }
}
