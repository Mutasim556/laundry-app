<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Admin\Models\Setting;

class SettingController extends Controller
{
    /**
     * Display the branding and logo settings page.
     */
    public function index(): View
    {
        $settings = [
            'site_name' => Setting::getSiteName(),
            'footer_text' => Setting::getFooterText(),
            'site_logo' => [
                'path' => Setting::get('site_logo'),
                'url' => Setting::getLogoUrl(),
                'is_custom' => Setting::hasCustom('site_logo'),
                'label' => 'Primary Brand Logo',
                'description' => 'Main horizontal logo displayed in the top navbar, invoices, and documents. Recommended: PNG or SVG with transparent background (approx 200x50px).',
            ],
            'site_logo_dark' => [
                'path' => Setting::get('site_logo_dark'),
                'url' => Setting::getDarkLogoUrl(),
                'is_custom' => Setting::hasCustom('site_logo_dark'),
                'label' => 'Dark Mode / Inverted Logo',
                'description' => 'Light-colored logo variant used against dark headers, login backdrops, or dark theme modes.',
            ],
            'site_logo_login' => [
                'path' => Setting::get('site_logo_login'),
                'url' => Setting::getLoginLogoUrl(),
                'is_custom' => Setting::hasCustom('site_logo_login'),
                'label' => 'Login Page Logo',
                'description' => 'Dedicated logo displayed on the admin authentication and login portal screen. Recommended: PNG or SVG (approx 220x60px).',
            ],
            'site_icon' => [
                'path' => Setting::get('site_icon'),
                'url' => Setting::getIconUrl(),
                'is_custom' => Setting::hasCustom('site_icon'),
                'label' => 'App Icon / Mini Logo',
                'description' => 'Square icon symbol used when the sidebar collapses and in responsive mobile headers. Recommended: Square PNG or SVG (approx 64x64px).',
            ],
            'site_favicon' => [
                'path' => Setting::get('site_favicon'),
                'url' => Setting::getFaviconUrl(),
                'is_custom' => Setting::hasCustom('site_favicon'),
                'label' => 'Browser Favicon',
                'description' => 'Icon shown in the browser address bar, tab header, and bookmarks. Recommended: ICO, PNG, or SVG (32x32px or 64x64px).',
            ],
            'site_preloader' => [
                'path' => Setting::get('site_preloader'),
                'url' => Setting::getPreloaderUrl() ?? asset('admin/assets/images/logo-icon.png'),
                'is_custom' => Setting::hasCustom('site_preloader'),
                'label' => 'Page Preloader / Reloader Animation',
                'description' => 'Custom loader animation or graphic displayed while pages load. Upload an animated GIF, SVG, APNG, WebP, or PNG icon (e.g. spinning washing machine or logo animation). Recommended: 80x80px to 120x120px.',
            ],
        ];

        return view('admin::settings.index', compact('settings'));
    }

    /**
     * Update branding assets (logo, dark logo, icon, favicon, app name, footer text).
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'site_name' => ['nullable', 'string', 'max:100'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'site_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'site_logo_dark' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'site_logo_login' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'site_icon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp,ico', 'max:2048'],
            'site_favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp,ico', 'max:1024'],
            'site_preloader' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp,gif', 'max:3072'],
        ]);

        if ($request->has('site_name')) {
            Setting::set('site_name', $request->input('site_name'));
        }

        if ($request->has('footer_text')) {
            Setting::set('footer_text', $request->input('footer_text'));
        }

        $assetKeys = ['site_logo', 'site_logo_dark', 'site_logo_login', 'site_icon', 'site_favicon', 'site_preloader'];

        foreach ($assetKeys as $key) {
            if ($request->hasFile($key)) {
                $file = $request->file($key);

                // Remove existing uploaded file if present
                $currentPath = Setting::get($key);
                if ($currentPath && Storage::disk('public')->exists($currentPath)) {
                    Storage::disk('public')->delete($currentPath);
                }

                // Store new file in 'branding' directory
                $newPath = $file->store('branding', 'public');
                Setting::set($key, $newPath);
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Branding assets and settings updated successfully.',
                'assets' => [
                    'site_logo' => Setting::getLogoUrl(),
                    'site_logo_dark' => Setting::getDarkLogoUrl(),
                    'site_logo_login' => Setting::getLoginLogoUrl(),
                    'site_icon' => Setting::getIconUrl(),
                    'site_favicon' => Setting::getFaviconUrl(),
                    'site_preloader' => Setting::getPreloaderUrl() ?? asset('admin/assets/images/logo-icon.png'),
                    'site_name' => Setting::getSiteName(),
                    'footer_text' => Setting::getFooterText(),
                ],
                'custom_status' => [
                    'site_logo' => Setting::hasCustom('site_logo'),
                    'site_logo_dark' => Setting::hasCustom('site_logo_dark'),
                    'site_logo_login' => Setting::hasCustom('site_logo_login'),
                    'site_icon' => Setting::hasCustom('site_icon'),
                    'site_favicon' => Setting::hasCustom('site_favicon'),
                    'site_preloader' => Setting::hasCustom('site_preloader'),
                ],
            ]);
        }

        return redirect()->route('admin.settings.index')->with('status', 'settings-updated');
    }

    /**
     * Reset a branding asset back to default.
     */
    public function destroy(string $key): JsonResponse|RedirectResponse
    {
        $allowed = ['site_logo', 'site_logo_dark', 'site_logo_login', 'site_icon', 'site_favicon', 'site_preloader'];

        if (! in_array($key, $allowed, true)) {
            abort(404, 'Invalid setting key.');
        }

        $currentPath = Setting::get($key);
        if ($currentPath && Storage::disk('public')->exists($currentPath)) {
            Storage::disk('public')->delete($currentPath);
        }

        Setting::forget($key);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Asset reset to default successfully.',
                'key' => $key,
                'default_url' => match ($key) {
                    'site_logo' => Setting::getLogoUrl(),
                    'site_logo_dark' => Setting::getDarkLogoUrl(),
                    'site_logo_login' => Setting::getLoginLogoUrl(),
                    'site_icon' => Setting::getIconUrl(),
                    'site_favicon' => Setting::getFaviconUrl(),
                    'site_preloader' => asset('admin/assets/images/logo-icon.png'),
                    default => null,
                },
                'assets' => [
                    'site_logo' => Setting::getLogoUrl(),
                    'site_logo_dark' => Setting::getDarkLogoUrl(),
                    'site_logo_login' => Setting::getLoginLogoUrl(),
                    'site_icon' => Setting::getIconUrl(),
                    'site_favicon' => Setting::getFaviconUrl(),
                    'site_preloader' => Setting::getPreloaderUrl() ?? asset('admin/assets/images/logo-icon.png'),
                ],
                'is_custom' => false,
            ]);
        }

        return redirect()->route('admin.settings.index')->with('status', 'asset-reset');
    }
}
