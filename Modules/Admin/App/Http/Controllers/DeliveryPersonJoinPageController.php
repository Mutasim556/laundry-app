<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HomepageContentVersion;
use App\Models\HomepageMedia;
use App\Services\DeliveryPersonJoinPageService;
use App\Services\HomepageContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryPersonJoinPageController extends Controller
{
    public function __construct(
        protected DeliveryPersonJoinPageService $cms,
        protected HomepageContentService $homepageCms
    ) {}

    public function index(): View
    {
        $record = $this->cms->getOrInitializeRecord()->load(['lastDraftSavedBy:id,name', 'publishedBy:id,name']);
        $defaults = DeliveryPersonJoinPageService::defaultPayload();
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

        return view('admin::website-manager.delivery-person-join', [
            'record' => $record,
            'draft' => $draft,
            'mediaItems' => $mediaItems,
            'versions' => $versions,
            'cms' => $this->cms,
            'allowedIcons' => DeliveryPersonJoinPageService::ALLOWED_ICONS,
        ]);
    }

    public function saveGeneral(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_enabled' => ['required', 'boolean'],
            'applications_open' => ['required', 'boolean'],
            'nav_label' => ['required', 'string', 'max:60'],
            'footer_label' => ['required', 'string', 'max:60'],
            'status_badge' => ['nullable', 'string', 'max:80'],
            'closed_title' => ['required', 'string', 'max:150'],
            'closed_message' => ['required', 'string', 'max:500'],
            'expected_reopening' => ['nullable', 'string', 'max:120'],
        ]);

        $record = $this->cms->saveDraftSection('general', $validated, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'General & availability settings saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveSections(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.key' => ['required', Rule::in(DeliveryPersonJoinPageService::ALLOWED_SECTION_KEYS)],
            'sections.*.label' => ['required', 'string', 'max:80'],
            'sections.*.subtitle' => ['nullable', 'string', 'max:200'],
            'sections.*.visible' => ['required', 'boolean'],
            'sections.*.order' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $sections = collect($validated['sections'])->sortBy('order')->values()->all();
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
            'subtitle' => ['nullable', 'string', 'max:400'],
            'description' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'mobile_image_url' => ['nullable', 'string', 'max:255'],
            'image_alt' => ['nullable', 'string', 'max:160'],
            'bg_preset' => ['required', Rule::in(['gold_soft', 'white_clean', 'gold_gradient'])],
            'alignment' => ['required', Rule::in(['left', 'center'])],
            'primary_btn_label' => ['required', 'string', 'max:60'],
            'primary_btn_visible' => ['required', 'boolean'],
            'secondary_btn_label' => ['nullable', 'string', 'max:60'],
            'secondary_btn_url' => ['nullable', 'string', 'max:255'],
            'secondary_btn_visible' => ['required', 'boolean'],
        ]);

        if (! empty($validated['secondary_btn_url'])) {
            $validated['secondary_btn_url'] = $this->cms->sanitizeDestinationUrl($validated['secondary_btn_url'], 'secondary_btn_url');
        }

        $record = $this->cms->saveDraftSection('hero', $validated, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Hero section saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveBenefits(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:250'],
            'items' => ['required', 'array', 'max:12'],
            'items.*.title' => ['required', 'string', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:300'],
            'items.*.icon' => ['required', Rule::in(DeliveryPersonJoinPageService::ALLOWED_ICONS)],
            'items.*.visible' => ['required', 'boolean'],
            'items.*.order' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $validated['items'] = collect($validated['items'])->sortBy('order')->values()->all();
        $record = $this->cms->saveDraftSection('benefits', $validated, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Benefits section saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveHowItWorks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:250'],
            'steps' => ['required', 'array', 'min:1', 'max:8'],
            'steps.*.step' => ['required', 'string', 'max:10'],
            'steps.*.title' => ['required', 'string', 'max:100'],
            'steps.*.description' => ['required', 'string', 'max:300'],
            'steps.*.icon' => ['required', Rule::in(DeliveryPersonJoinPageService::ALLOWED_ICONS)],
            'steps.*.visible' => ['required', 'boolean'],
            'steps.*.order' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $validated['steps'] = collect($validated['steps'])->sortBy('order')->values()->all();
        $record = $this->cms->saveDraftSection('how_it_works', $validated, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'How It Works steps saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveRequirements(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:250'],
            'region_note' => ['nullable', 'string', 'max:300'],
            'items' => ['required', 'array', 'max:12'],
            'items.*.title' => ['required', 'string', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:300'],
            'items.*.icon' => ['required', Rule::in(DeliveryPersonJoinPageService::ALLOWED_ICONS)],
            'items.*.visible' => ['required', 'boolean'],
            'items.*.order' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $validated['items'] = collect($validated['items'])->sortBy('order')->values()->all();
        $record = $this->cms->saveDraftSection('requirements', $validated, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Requirements section saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveFormConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'heading' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:250'],
            'intro_text' => ['nullable', 'string', 'max:300'],
            'step_1_title' => ['required', 'string', 'max:40'],
            'step_2_title' => ['required', 'string', 'max:40'],
            'step_3_title' => ['required', 'string', 'max:40'],
            'step_4_title' => ['required', 'string', 'max:40'],
            'step_5_title' => ['required', 'string', 'max:40'],
            'dob_label' => ['required', 'string', 'max:60'],
            'dob_placeholder' => ['nullable', 'string', 'max:80'],
            'dob_helper' => ['nullable', 'string', 'max:150'],
            'dob_format' => ['required', Rule::in(['d M Y', 'Y-m-d', 'm/d/Y', 'd/m/Y'])],
            'default_country_code' => ['required', 'string', 'size:2'],
            'phone_label' => ['required', 'string', 'max:60'],
            'phone_placeholder' => ['nullable', 'string', 'max:40'],
            'phone_helper' => ['nullable', 'string', 'max:150'],
            'show_experience' => ['required', 'boolean'],
            'show_profile_photo' => ['required', 'boolean'],
            'upload_instructions' => ['nullable', 'string', 'max:300'],
            'submit_label' => ['required', 'string', 'max:60'],
            'success_title' => ['required', 'string', 'max:120'],
            'success_message' => ['required', 'string', 'max:400'],
            'consent_text' => ['required', 'string', 'max:400'],
        ]);

        $record = $this->cms->saveDraftSection('form_config', $validated, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Application form settings saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveDocuments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'documents' => ['required', 'array', 'min:1', 'max:6'],
            'documents.*.key' => ['required', Rule::in(['profile_photo', 'identity_document', 'driving_licence_document', 'vehicle_document'])],
            'documents.*.label' => ['required', 'string', 'max:80'],
            'documents.*.description' => ['nullable', 'string', 'max:200'],
            'documents.*.required' => ['required', 'boolean'],
            'documents.*.motor_only' => ['required', 'boolean'],
            'documents.*.max_mb' => ['required', 'integer', 'min:1', 'max:5'],
            'documents.*.visible' => ['required', 'boolean'],
            'documents.*.order' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $docs = collect($validated['documents'])->sortBy('order')->values()->all();
        $record = $this->cms->saveDraftSection('documents', $docs, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Verification document settings saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveFaqs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:250'],
            'items' => ['required', 'array', 'max:20'],
            'items.*.question' => ['required', 'string', 'max:200'],
            'items.*.answer' => ['required', 'string', 'max:1000'],
            'items.*.visible' => ['required', 'boolean'],
            'items.*.order' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $validated['items'] = collect($validated['items'])->sortBy('order')->values()->all();
        $record = $this->cms->saveDraftSection('faqs', $validated, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'FAQs saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveCta(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'headline' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:400'],
            'button_label' => ['required', 'string', 'max:60'],
            'button_url' => ['required', 'string', 'max:255'],
            'button_visible' => ['required', 'boolean'],
            'bg_preset' => ['required', Rule::in(['gold_gradient', 'dark_clean', 'cream_soft'])],
            'image_url' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['button_url'] = $this->cms->sanitizeDestinationUrl($validated['button_url'], 'button_url');
        $record = $this->cms->saveDraftSection('cta', $validated, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Bottom CTA saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveSeo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'meta_title' => ['required', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'canonical_url' => ['nullable', 'string', 'max:255'],
            'og_title' => ['nullable', 'string', 'max:120'],
            'og_description' => ['nullable', 'string', 'max:300'],
            'og_image_url' => ['nullable', 'string', 'max:255'],
            'robots' => ['required', Rule::in(['index, follow', 'noindex, follow', 'noindex, nofollow'])],
        ]);

        if (! empty($validated['canonical_url'])) {
            $validated['canonical_url'] = $this->cms->sanitizeOptionalUrl($validated['canonical_url'], 'canonical_url');
        }

        $record = $this->cms->saveDraftSection('seo', $validated, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'SEO settings saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function saveDemo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'button_label' => ['required', 'string', 'max:60'],
            'modal_title' => ['required', 'string', 'max:120'],
            'modal_subtitle' => ['nullable', 'string', 'max:200'],
            'intro_note' => ['nullable', 'string', 'max:250'],
            'step_1_note' => ['nullable', 'string', 'max:250'],
            'step_2_note' => ['nullable', 'string', 'max:250'],
            'step_3_note' => ['nullable', 'string', 'max:250'],
            'step_4_note' => ['nullable', 'string', 'max:250'],
            'step_5_note' => ['nullable', 'string', 'max:250'],
            'final_title' => ['required', 'string', 'max:120'],
            'final_message' => ['required', 'string', 'max:500'],
            'cta_label' => ['required', 'string', 'max:60'],
        ]);

        $record = $this->cms->saveDraftSection('demo', $validated, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Registration Demo settings saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $record = $this->cms->publish((int) Auth::guard('admin')->id(), $request->input('notes'));

        return response()->json([
            'status' => 'ok',
            'message' => 'Delivery Person Join Page published live.',
            'published_at' => $record->published_at?->format('d M Y, H:i'),
        ]);
    }

    public function restoreVersion(HomepageContentVersion $version): JsonResponse
    {
        $record = $this->cms->restoreVersionToDraft($version, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Version #' . $version->version_number . ' restored to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }
}
