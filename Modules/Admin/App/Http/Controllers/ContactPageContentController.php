<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HomepageContentVersion;
use App\Models\HomepageMedia;
use App\Services\ContactPageContentService;
use App\Services\HomepageContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactPageContentController extends Controller
{
    public function __construct(
        protected ContactPageContentService $cms,
        protected HomepageContentService $homepageCms
    ) {}

    public function index(): View
    {
        $record = $this->cms->getOrInitializeRecord()->load(['lastDraftSavedBy:id,name', 'publishedBy:id,name']);
        $defaults = ContactPageContentService::defaultPayload();
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

        return view('admin::website-manager.contact', [
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
            'sections.*.key' => ['required', Rule::in(['hero', 'contact_and_form', 'business_hours', 'map', 'social', 'faq'])],
            'sections.*.label' => ['required', 'string', 'max:80'],
            'sections.*.subtitle' => ['nullable', 'string', 'max:200'],
            'sections.*.visible' => ['required', 'boolean'],
            'sections.*.order' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $sections = collect($validated['sections'])->sortBy('order')->values()->all();
        $record = $this->cms->saveDraftSection('sections', $sections, (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'Contact page sections saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
    }

    public function saveHero(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'badge' => ['nullable', 'string', 'max:80'],
            'headline' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:500'],
            'bg_preset' => ['required', Rule::in(['gold_soft', 'cream', 'white'])],
        ]);

        $record = $this->cms->saveDraftSection('hero', [
            'badge' => trim((string) ($validated['badge'] ?? '')),
            'headline' => trim($validated['headline']),
            'description' => trim((string) ($validated['description'] ?? '')),
            'bg_preset' => $validated['bg_preset'],
        ], (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'Contact Hero saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
    }

    public function saveContactInfo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:120'],
            'support_email' => ['nullable', 'email:rfc', 'max:160'],
            'phone_country_code' => ['nullable', 'string', 'max:10'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'whatsapp_number' => ['nullable', 'string', 'max:35'],
            'address' => ['nullable', 'string', 'max:220'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:400'],
            'availability_text' => ['nullable', 'string', 'max:220'],
        ]);

        $record = $this->cms->saveDraftSection('contact_info', [
            'brand_name' => trim($validated['brand_name']),
            'support_email' => trim((string) ($validated['support_email'] ?? '')),
            'phone_country_code' => trim((string) ($validated['phone_country_code'] ?? '')),
            'phone_number' => trim((string) ($validated['phone_number'] ?? '')),
            'whatsapp_number' => trim((string) ($validated['whatsapp_number'] ?? '')),
            'address' => trim((string) ($validated['address'] ?? '')),
            'city' => trim((string) ($validated['city'] ?? '')),
            'country' => trim((string) ($validated['country'] ?? '')),
            'description' => trim((string) ($validated['description'] ?? '')),
            'availability_text' => trim((string) ($validated['availability_text'] ?? '')),
        ], (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'Contact Information saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
    }

    public function saveFormConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'heading' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:240'],
            'submit_label' => ['required', 'string', 'max:60'],
            'success_message' => ['required', 'string', 'max:240'],
            'show_phone' => ['nullable', 'boolean'],
            'show_booking_reference' => ['nullable', 'boolean'],
        ]);

        $record = $this->cms->saveDraftSection('form_config', [
            'heading' => trim($validated['heading']),
            'subtitle' => trim((string) ($validated['subtitle'] ?? '')),
            'submit_label' => trim($validated['submit_label']),
            'success_message' => trim($validated['success_message']),
            'show_phone' => $request->boolean('show_phone'),
            'show_booking_reference' => $request->boolean('show_booking_reference'),
        ], (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'Enquiry Form settings saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
    }

    public function saveBusinessHours(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day' => ['required', 'string', 'max:20'],
            'hours.*.is_open' => ['nullable', 'boolean'],
            'hours.*.open_time' => ['nullable', 'string', 'max:20'],
            'hours.*.close_time' => ['nullable', 'string', 'max:20'],
            'hours.*.note' => ['nullable', 'string', 'max:80'],
        ]);

        $hours = collect($validated['hours'])->map(fn ($row) => [
            'day' => trim($row['day']),
            'is_open' => ! empty($row['is_open']),
            'open_time' => trim((string) ($row['open_time'] ?? '')),
            'close_time' => trim((string) ($row['close_time'] ?? '')),
            'note' => trim((string) ($row['note'] ?? '')),
        ])->values()->all();

        $record = $this->cms->saveDraftSection('business_hours', $hours, (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'Business Hours saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
    }

    public function saveMapSocial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'map.title' => ['nullable', 'string', 'max:120'],
            'map.public_address' => ['nullable', 'string', 'max:240'],
            'map.map_url' => ['nullable', 'string', 'max:1000'],
            'map.embed_url' => ['nullable', 'string', 'max:2000'],
            'social.facebook' => ['nullable', 'string', 'max:255'],
            'social.instagram' => ['nullable', 'string', 'max:255'],
            'social.linkedin' => ['nullable', 'string', 'max:255'],
            'social.youtube' => ['nullable', 'string', 'max:255'],
            'social.tiktok' => ['nullable', 'string', 'max:255'],
            'social.whatsapp' => ['nullable', 'string', 'max:255'],
        ]);

        $rawMapUrl = trim((string) ($validated['map']['map_url'] ?? ''));
        $rawEmbedUrl = trim((string) ($validated['map']['embed_url'] ?? ''));

        if ($rawMapUrl !== '' && preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $rawMapUrl, $m)) {
            $rawMapUrl = trim($m[1]);
        }
        if ($rawEmbedUrl !== '' && preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $rawEmbedUrl, $m)) {
            $rawEmbedUrl = trim($m[1]);
        }

        $cleanMapUrl = $this->cms->sanitizeOptionalHttpsUrl($rawMapUrl, 'map.map_url');
        $cleanEmbedUrl = $this->cms->sanitizeOptionalHttpsUrl($rawEmbedUrl, 'map.embed_url');
        $publicAddress = trim((string) ($validated['map']['public_address'] ?? ''));

        if ($cleanEmbedUrl === '') {
            $cleanEmbedUrl = $this->deriveGoogleMapsEmbedUrl($cleanMapUrl, $publicAddress);
        }

        $mapData = [
            'title' => trim((string) ($validated['map']['title'] ?? 'Our Office Location')),
            'public_address' => $publicAddress,
            'map_url' => $cleanMapUrl,
            'embed_url' => $cleanEmbedUrl,
        ];

        $socialData = [];
        foreach (['facebook', 'instagram', 'linkedin', 'youtube', 'tiktok', 'whatsapp'] as $net) {
            $socialData[$net] = $this->cms->sanitizeOptionalHttpsUrl($validated['social'][$net] ?? '', "social.{$net}");
        }

        $record = $this->cms->saveDraftSection('map', $mapData, (int) Auth::guard('admin')->id());
        if ($mapData['map_url'] !== '' || $mapData['public_address'] !== '') {
            $sections = $record->draft_payload['sections'] ?? \App\Services\ContactPageContentService::defaultPayload()['sections'];
            $sections = collect($sections)->map(function ($sec) {
                if (($sec['key'] ?? '') === 'map') {
                    $sec['visible'] = true;
                }
                return $sec;
            })->values()->all();
            $this->cms->saveDraftSection('sections', $sections, (int) Auth::guard('admin')->id());
        }
        $record = $this->cms->saveDraftSection('social', $socialData, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Map & Social Links saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
            'embed_url' => $mapData['embed_url'],
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

        $record = $this->cms->saveDraftSection('seo', [
            'meta_title' => trim((string) ($validated['meta_title'] ?? '')),
            'meta_description' => trim((string) ($validated['meta_description'] ?? '')),
            'canonical_url' => $this->homepageCms->sanitizeOptionalUrl($validated['canonical_url'] ?? '', 'canonical_url'),
            'og_title' => trim((string) ($validated['og_title'] ?? '')),
            'og_description' => trim((string) ($validated['og_description'] ?? '')),
            'og_image_url' => trim((string) ($validated['og_image_url'] ?? '')),
            'robots' => $validated['robots'],
        ], (int) Auth::guard('admin')->id());

        return response()->json(['status' => 'ok', 'message' => 'Contact SEO saved to Draft.', 'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i')]);
    }

    public function publish(Request $request): JsonResponse
    {
        $record = $this->cms->publish((int) Auth::guard('admin')->id(), $request->input('notes'));

        return response()->json([
            'status' => 'ok',
            'message' => 'Contact Us page published live!',
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

    protected function deriveGoogleMapsEmbedUrl(string $mapUrl, string $publicAddress): string
    {
        if ($mapUrl !== '') {
            $resolved = $this->resolveGoogleMapsShortUrl($mapUrl);

            if (str_contains($resolved, '/maps/embed') || str_contains($resolved, 'output=embed')) {
                return $resolved;
            }
            if (preg_match('#/maps/(?:place|dir)//?([^/@]+)#', $resolved, $m) && $m[1] !== '') {
                return 'https://maps.google.com/maps?q=' . $m[1] . '&z=16&output=embed';
            }
            if (preg_match('/!1d(-?\d+(?:\.\d+)?)!2d(-?\d+(?:\.\d+)?)/', $resolved, $m)) {
                return 'https://maps.google.com/maps?q=' . $m[2] . ',' . $m[1] . '&z=16&output=embed';
            }
            if (preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $resolved, $m)) {
                return 'https://maps.google.com/maps?q=' . $m[1] . ',' . $m[2] . '&z=16&output=embed';
            }
        }

        if ($publicAddress !== '') {
            return 'https://maps.google.com/maps?q=' . rawurlencode($publicAddress) . '&z=16&output=embed';
        }

        return '';
    }

    protected function resolveGoogleMapsShortUrl(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (! in_array($host, ['maps.app.goo.gl', 'goo.gl'], true)) {
            return $url;
        }

        try {
            $ch = curl_init($url);
            if ($ch === false) {
                return $url;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HEADER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 4,
            ]);
            $response = curl_exec($ch);
            if (is_string($response) && preg_match('/^location:\s*(.+)$/im', $response, $m)) {
                $target = trim($m[1]);
                if (str_starts_with(strtolower($target), 'https://')) {
                    return $target;
                }
            }
        } catch (\Throwable) {
            // Fall back to original URL if shortener lookup fails
        }

        return $url;
    }
}
