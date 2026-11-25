<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HomepageContentVersion;
use App\Models\HomepageMedia;
use App\Services\PreloaderSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Models\Setting;

class PreloaderSettingController extends Controller
{
    public function __construct(
        protected PreloaderSettingService $preloader
    ) {}

    public function index(): View
    {
        $record = $this->preloader->getOrInitializeRecord();
        $draft = $this->preloader->getDraftPayload();
        $mediaItems = HomepageMedia::query()->orderByDesc('id')->limit(30)->get();

        $versions = HomepageContentVersion::query()
            ->with('createdByAdmin:id,name')
            ->where('homepage_content_id', $record->id)
            ->orderByDesc('version_number')
            ->limit(20)
            ->get();

        $siteLogoUrl = $this->preloader->resolveLogoUrl(['logo_source' => 'site_logo']);

        return view('admin::website-manager.preloader', [
            'record' => $record,
            'draft' => $draft,
            'mediaItems' => $mediaItems,
            'versions' => $versions,
            'siteLogoUrl' => $siteLogoUrl,
        ]);
    }

    public function saveDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'style' => ['required', Rule::in(['brand_spinner', 'laundry_animation', 'progress_indicator'])],
            'loading_text' => ['nullable', 'string', 'max:120'],
            'show_logo' => ['nullable', 'boolean'],
            'show_text' => ['nullable', 'boolean'],
            'background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'animation_speed' => ['required', Rule::in(['slow', 'normal', 'fast'])],
            'fade_duration_ms' => ['required', 'integer', 'min:100', 'max:800'],
            'minimum_duration_ms' => ['required', 'integer', 'min:0', 'max:1500'],
            'maximum_timeout_ms' => ['required', 'integer', 'min:1500', 'max:8000'],
            'logo_source' => ['required', Rule::in(['site_logo', 'custom_media'])],
            'custom_logo_url' => ['nullable', 'string', 'max:255'],
            'logo_size' => ['required', Rule::in(['sm', 'md', 'lg'])],
            'enable_public' => ['nullable', 'boolean'],
            'enable_booking' => ['nullable', 'boolean'],
            'enable_orders' => ['nullable', 'boolean'],
            'enable_auth' => ['nullable', 'boolean'],
            'respect_reduced_motion' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'enabled' => $request->boolean('enabled'),
            'style' => $validated['style'],
            'loading_text' => trim((string) ($validated['loading_text'] ?? '')),
            'show_logo' => $request->boolean('show_logo'),
            'show_text' => $request->boolean('show_text'),
            'background_color' => strtoupper($validated['background_color']),
            'accent_color' => strtoupper($validated['accent_color']),
            'text_color' => strtoupper($validated['text_color']),
            'animation_speed' => $validated['animation_speed'],
            'fade_duration_ms' => (int) $validated['fade_duration_ms'],
            'minimum_duration_ms' => (int) $validated['minimum_duration_ms'],
            'maximum_timeout_ms' => (int) $validated['maximum_timeout_ms'],
            'logo_source' => $validated['logo_source'],
            'custom_logo_url' => trim((string) ($validated['custom_logo_url'] ?? '')),
            'logo_size' => $validated['logo_size'],
            'enable_public' => $request->boolean('enable_public'),
            'enable_booking' => $request->boolean('enable_booking'),
            'enable_orders' => $request->boolean('enable_orders'),
            'enable_auth' => $request->boolean('enable_auth'),
            'respect_reduced_motion' => $request->boolean('respect_reduced_motion'),
        ];

        $record = $this->preloader->saveDraft($payload, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => 'Preloader settings saved to Draft.',
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $record = $this->preloader->publish((int) Auth::guard('admin')->id(), $request->input('notes'));

        return response()->json([
            'status' => 'ok',
            'message' => 'Website Preloader settings published live!',
            'published_at' => $record->published_at?->format('d M Y, H:i'),
        ]);
    }

    public function restoreVersion(HomepageContentVersion $version): JsonResponse
    {
        $record = $this->preloader->restoreVersionToDraft($version, (int) Auth::guard('admin')->id());

        return response()->json([
            'status' => 'ok',
            'message' => "Version #{$version->version_number} restored to Draft.",
            'draft_saved_at' => $record->last_draft_saved_at?->format('d M Y, H:i'),
        ]);
    }
}
