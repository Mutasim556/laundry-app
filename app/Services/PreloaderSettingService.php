<?php

namespace App\Services;

use App\Models\HomepageContent;
use App\Models\HomepageContentVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\Admin\Models\Setting;

class PreloaderSettingService
{
    public const CACHE_KEY = 'website.preloader.published.payload';
    public const PAGE_SLUG = 'preloader';

    public function __construct(
        protected HomepageContentService $homepageCms
    ) {}

    public static function defaultPayload(): array
    {
        return [
            'enabled' => true,
            'style' => 'brand_spinner',
            'loading_text' => 'Preparing your fresh experience...',
            'show_logo' => true,
            'show_text' => true,
            'background_color' => '#FFF8E5',
            'accent_color' => '#FFC629',
            'text_color' => '#171717',
            'animation_speed' => 'normal',
            'fade_duration_ms' => 250,
            'minimum_duration_ms' => 450,
            'maximum_timeout_ms' => 4000,
            'logo_source' => 'site_logo',
            'custom_logo_url' => '',
            'logo_size' => 'md',
            'enable_public' => true,
            'enable_booking' => true,
            'enable_orders' => true,
            'enable_auth' => true,
            'respect_reduced_motion' => true,
        ];
    }

    public function getOrInitializeRecord(): HomepageContent
    {
        $record = HomepageContent::query()->where('slug', self::PAGE_SLUG)->first();
        if ($record) {
            return $record;
        }

        $defaults = self::defaultPayload();

        return HomepageContent::create([
            'slug' => self::PAGE_SLUG,
            'draft_payload' => $defaults,
            'published_payload' => $defaults,
            'current_version_number' => 1,
            'published_at' => now(),
            'last_draft_saved_at' => now(),
        ]);
    }

    public function getDraftPayload(): array
    {
        $record = $this->getOrInitializeRecord();

        return $this->sanitizePayload(
            array_replace(self::defaultPayload(), is_array($record->draft_payload) ? $record->draft_payload : [])
        );
    }

    public function getPublishedPayload(): array
    {
        try {
            $payload = Cache::remember(self::CACHE_KEY, now()->addMinutes(30), function () {
                $record = HomepageContent::query()->where('slug', self::PAGE_SLUG)->first();
                if (! $record) {
                    $record = $this->getOrInitializeRecord();
                }

                $raw = is_array($record->published_payload) ? $record->published_payload : self::defaultPayload();

                return array_replace(self::defaultPayload(), $raw);
            });

            return $this->sanitizePayload(is_array($payload) ? $payload : self::defaultPayload());
        } catch (\Throwable) {
            return self::defaultPayload();
        }
    }

    public function sanitizePayload(array $payload): array
    {
        $defaults = self::defaultPayload();

        $style = in_array($payload['style'] ?? '', ['brand_spinner', 'laundry_animation', 'progress_indicator'], true)
            ? $payload['style']
            : $defaults['style'];

        $speed = in_array($payload['animation_speed'] ?? '', ['slow', 'normal', 'fast'], true)
            ? $payload['animation_speed']
            : $defaults['animation_speed'];

        $logoSource = in_array($payload['logo_source'] ?? '', ['site_logo', 'custom_media'], true)
            ? $payload['logo_source']
            : $defaults['logo_source'];

        $logoSize = in_array($payload['logo_size'] ?? '', ['sm', 'md', 'lg'], true)
            ? $payload['logo_size']
            : $defaults['logo_size'];

        return [
            'enabled' => (bool) ($payload['enabled'] ?? $defaults['enabled']),
            'style' => $style,
            'loading_text' => mb_substr(trim((string) ($payload['loading_text'] ?? $defaults['loading_text'])), 0, 120),
            'show_logo' => (bool) ($payload['show_logo'] ?? $defaults['show_logo']),
            'show_text' => (bool) ($payload['show_text'] ?? $defaults['show_text']),
            'background_color' => $this->sanitizeHexColor((string) ($payload['background_color'] ?? ''), $defaults['background_color']),
            'accent_color' => $this->sanitizeHexColor((string) ($payload['accent_color'] ?? ''), $defaults['accent_color']),
            'text_color' => $this->sanitizeHexColor((string) ($payload['text_color'] ?? ''), $defaults['text_color']),
            'animation_speed' => $speed,
            'fade_duration_ms' => max(100, min(800, (int) ($payload['fade_duration_ms'] ?? $defaults['fade_duration_ms']))),
            'minimum_duration_ms' => max(0, min(1500, (int) ($payload['minimum_duration_ms'] ?? $defaults['minimum_duration_ms']))),
            'maximum_timeout_ms' => max(1500, min(8000, (int) ($payload['maximum_timeout_ms'] ?? $defaults['maximum_timeout_ms']))),
            'logo_source' => $logoSource,
            'custom_logo_url' => trim((string) ($payload['custom_logo_url'] ?? '')),
            'logo_size' => $logoSize,
            'enable_public' => (bool) ($payload['enable_public'] ?? $defaults['enable_public']),
            'enable_booking' => (bool) ($payload['enable_booking'] ?? $defaults['enable_booking']),
            'enable_orders' => (bool) ($payload['enable_orders'] ?? $defaults['enable_orders']),
            'enable_auth' => (bool) ($payload['enable_auth'] ?? $defaults['enable_auth']),
            'respect_reduced_motion' => (bool) ($payload['respect_reduced_motion'] ?? $defaults['respect_reduced_motion']),
        ];
    }

    protected function sanitizeHexColor(string $value, string $fallback): string
    {
        $value = trim($value);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? strtoupper($value) : $fallback;
    }

    public function resolveLogoUrl(array $payload): string
    {
        if (($payload['logo_source'] ?? 'site_logo') === 'custom_media' && ! empty($payload['custom_logo_url'])) {
            return $this->homepageCms->resolveImageUrl((string) $payload['custom_logo_url']);
        }

        if (class_exists(Setting::class)) {
            if (method_exists(Setting::class, 'hasCustom') && Setting::hasCustom('site_logo')) {
                return (string) Setting::getLogoUrl();
            }
            if (file_exists(public_path('images/freshgo-new-logo.jpeg'))) {
                return asset('images/freshgo-new-logo.jpeg');
            }
            $siteLogo = (string) Setting::getLogoUrl();
            if ($siteLogo !== '') {
                return $siteLogo;
            }
        }

        return '';
    }

    public function shouldDisplayForCurrentRoute(array $payload): bool
    {
        if (empty($payload['enabled'])) {
            return false;
        }

        $request = request();
        if ($request->ajax() || $request->wantsJson()) {
            return false;
        }

        $routeName = (string) (Route::currentRouteName() ?? '');
        $path = ltrim($request->path(), '/');

        // Never display on Admin, Store, Delivery Person, or API routes
        if (
            str_starts_with($routeName, 'admin.')
            || str_starts_with($routeName, 'store.')
            || str_starts_with($routeName, 'delivery.')
            || str_starts_with($routeName, 'api.')
            || str_starts_with($path, 'admin')
            || str_starts_with($path, 'store')
            || str_starts_with($path, 'delivery')
            || str_starts_with($path, 'api')
        ) {
            return false;
        }

        // Booking workflow pages
        if (str_starts_with($routeName, 'booking.') || str_starts_with($path, 'book')) {
            return ! empty($payload['enable_booking']);
        }

        // Customer My Orders & Account pages
        if (
            str_starts_with($routeName, 'orders.')
            || str_starts_with($routeName, 'account.')
            || str_starts_with($routeName, 'customer.')
            || str_starts_with($path, 'my-orders')
            || str_starts_with($path, 'orders')
            || str_starts_with($path, 'account')
        ) {
            return ! empty($payload['enable_orders']);
        }

        // Customer Auth / OTP pages
        if (
            in_array($routeName, ['login', 'register', 'otp.verify', 'otp.send', 'complete-profile'], true)
            || str_starts_with($routeName, 'auth.')
            || str_starts_with($path, 'login')
            || str_starts_with($path, 'register')
            || str_starts_with($path, 'verify-otp')
            || str_starts_with($path, 'complete-profile')
        ) {
            return ! empty($payload['enable_auth']);
        }

        // Public website pages (home, services, about, contact, etc.)
        return ! empty($payload['enable_public']);
    }

    public function saveDraft(array $data, int $adminId): HomepageContent
    {
        return DB::transaction(function () use ($data, $adminId) {
            $record = $this->getOrInitializeRecord();
            $locked = HomepageContent::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();

            $merged = array_replace(
                self::defaultPayload(),
                is_array($locked->draft_payload) ? $locked->draft_payload : [],
                $data
            );
            $clean = $this->sanitizePayload($merged);

            $locked->update([
                'draft_payload' => $clean,
                'last_draft_saved_at' => now(),
            ]);

            $nextVer = (int) $locked->current_version_number + 1;
            $locked->update(['current_version_number' => $nextVer]);

            HomepageContentVersion::create([
                'homepage_content_id' => $locked->id,
                'version_number' => $nextVer,
                'action' => 'draft',
                'section_key' => 'preloader',
                'payload' => $clean,
                'notes' => 'Saved preloader settings draft',
                'created_by_admin_id' => $adminId,
                'created_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    public function publish(int $adminId, ?string $notes = null): HomepageContent
    {
        return DB::transaction(function () use ($adminId, $notes) {
            $record = $this->getOrInitializeRecord();
            $locked = HomepageContent::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();

            $clean = $this->sanitizePayload(
                array_replace(self::defaultPayload(), is_array($locked->draft_payload) ? $locked->draft_payload : [])
            );

            $nextVer = (int) $locked->current_version_number + 1;
            $now = now();

            $locked->update([
                'draft_payload' => $clean,
                'published_payload' => $clean,
                'published_by_admin_id' => $adminId,
                'published_at' => $now,
                'current_version_number' => $nextVer,
            ]);

            HomepageContentVersion::create([
                'homepage_content_id' => $locked->id,
                'version_number' => $nextVer,
                'action' => 'published',
                'section_key' => null,
                'payload' => $clean,
                'notes' => $notes ?: 'Published Preloader Settings',
                'created_by_admin_id' => $adminId,
                'created_at' => $now,
            ]);

            Cache::forget(self::CACHE_KEY);

            return $locked->fresh();
        });
    }

    public function restoreVersionToDraft(HomepageContentVersion $version, int $adminId): HomepageContent
    {
        return $this->homepageCms->restoreVersionToDraft($version, $adminId);
    }
}
