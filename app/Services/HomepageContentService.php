<?php

namespace App\Services;

use App\Models\HomepageContent;
use App\Models\HomepageContentVersion;
use App\Models\HomepageMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;

class HomepageContentService
{
    public const CACHE_KEY = 'homepage.published.payload';

    public static function defaultPayload(): array
    {
        return [
            'sections' => [
                [
                    'key' => 'hero',
                    'label' => 'Hero Banner & Search',
                    'subtitle' => 'Main hero headline, CTA buttons, highlights, image & search bar',
                    'visible' => true,
                    'order' => 1,
                ],
                [
                    'key' => 'categories',
                    'label' => 'Our Laundry Categories',
                    'subtitle' => 'Interactive category cards carousel',
                    'visible' => true,
                    'order' => 2,
                ],
                [
                    'key' => 'promo_banner',
                    'label' => 'Fresh Go Express Care Banner',
                    'subtitle' => 'Mid-page brand promotional banner with CTA',
                    'visible' => true,
                    'order' => 3,
                ],
                [
                    'key' => 'services',
                    'label' => 'Our Popular Services',
                    'subtitle' => 'Active laundry & dry cleaning services carousel',
                    'visible' => true,
                    'order' => 4,
                ],
            ],
            'hero' => [
                'badge' => 'Premium Laundry Care',
                'headline' => "Fresh Clothes.\nHappier You.",
                'show_heart_icon' => true,
                'subtitle' => 'Professional laundry & dry cleaning at your fingertips. We collect from your doorstep, clean with gentle eco-friendly detergents, and return fresh clothes neatly folded.',
                'image_url' => 'images/freshgo-full-poster.jpg',
                'image_alt' => 'Freshgo Laundry Delivery',
                'primary_btn_label' => 'Book a Collection →',
                'primary_btn_url' => '/booking/services',
                'primary_btn_visible' => true,
                'secondary_btn_label' => 'Explore Services',
                'secondary_btn_url' => '/services',
                'secondary_btn_visible' => true,
                'bg_preset' => 'cream',
                'highlights' => [
                    ['value' => '24h', 'label' => 'Express Delivery'],
                    ['value' => '100%', 'label' => 'Eco-friendly Wash'],
                    ['value' => '4.9 ★', 'label' => 'Customer Rating'],
                ],
            ],
            'search' => [
                'visible' => true,
                'placeholder' => 'Search services, categories, or laundry items...',
                'button_text' => 'Search',
                'show_icon' => true,
                'show_categories' => true,
                'show_services' => true,
                'show_items' => true,
                'max_suggestions' => 5,
            ],
            'categories_config' => [
                'title' => 'Our Laundry Categories',
                'subtitle' => 'Choose specialized garment care suited to your fabric needs.',
                'carousel_enabled' => true,
                'autoplay' => true,
                'interval_ms' => 4000,
                'show_arrows' => true,
                'pause_on_hover' => true,
                'loop' => true,
                'desktop_per_view' => 4,
                'mode' => 'all_active',
                'overrides' => [],
            ],
            'promo_banner' => [
                'badge' => 'Fresh Go Express Care',
                'headline' => 'We Collect. We Clean. We Deliver.',
                'description' => 'Enjoy doorstep laundry and dry cleaning pickup, gentle fabric care, and fast turnaround straight to your door.',
                'button_label' => 'Book Now →',
                'button_url' => '/booking/services',
                'button_visible' => true,
                'image_url' => 'images/laundry-icons/07_folded_laundry.png',
                'image_alt' => 'Folded Laundry Pile',
                'bg_preset' => 'gold_gradient',
            ],
            'services_config' => [
                'title' => 'Our Services',
                'subtitle' => 'Explore active laundry and dry cleaning services.',
                'catalog_link_text' => 'View Full Catalog →',
                'carousel_enabled' => true,
                'autoplay' => true,
                'interval_ms' => 4600,
                'show_arrows' => true,
                'pause_on_hover' => true,
                'loop' => true,
                'desktop_per_view' => 4,
                'mode' => 'all_active',
                'overrides' => [],
            ],
            'seo' => [
                'meta_title' => '',
                'meta_description' => 'Doorstep laundry and dry cleaning pickup, gentle fabric care, and fast turnaround straight to your door.',
                'canonical_url' => '',
                'og_title' => '',
                'og_description' => '',
                'og_image_url' => '',
                'robots' => 'index, follow',
            ],
        ];
    }

    public function getOrInitializeRecord(): HomepageContent
    {
        $record = HomepageContent::query()->where('slug', 'homepage')->first();
        if ($record) {
            return $record;
        }

        $defaults = self::defaultPayload();

        return HomepageContent::create([
            'slug' => 'homepage',
            'draft_payload' => $defaults,
            'published_payload' => $defaults,
            'current_version_number' => 1,
            'published_at' => now(),
        ]);
    }

    public function getPublicPayload(bool $previewDraft = false): array
    {
        $defaults = self::defaultPayload();

        if ($previewDraft) {
            $record = $this->getOrInitializeRecord();
            $payload = is_array($record->draft_payload) ? $record->draft_payload : $defaults;

            return array_replace_recursive($defaults, $payload);
        }

        $cached = Cache::remember(self::CACHE_KEY, 3600, function () use ($defaults) {
            $record = $this->getOrInitializeRecord();
            $payload = is_array($record->published_payload) ? $record->published_payload : $defaults;

            return array_replace_recursive($defaults, $payload);
        });

        return is_array($cached) ? $cached : $defaults;
    }

    public function resolveImageUrl(?string $pathOrUrl): string
    {
        $val = trim((string) $pathOrUrl);
        if ($val === '') {
            return '';
        }
        if (str_starts_with($val, 'https://') || str_starts_with($val, 'http://') || str_starts_with($val, '/')) {
            return $val;
        }

        return asset($val);
    }

    public function sanitizeDestinationUrl(?string $url, string $field = 'url'): string
    {
        $clean = trim((string) $url);
        if ($clean === '') {
            return '/services';
        }

        if (preg_match('/^\s*(javascript|data|vbscript):/i', $clean)) {
            throw ValidationException::withMessages([
                $field => ['Unsafe URL scheme is not permitted.'],
            ]);
        }

        // Allow relative internal paths starting with single '/'
        if (str_starts_with($clean, '/') && ! str_starts_with($clean, '//')) {
            return $clean;
        }

        if (str_starts_with($clean, 'https://') && filter_var($clean, FILTER_VALIDATE_URL)) {
            return $clean;
        }

        throw ValidationException::withMessages([
            $field => ['URL must be an internal path (e.g. /booking/services) or a valid https:// URL.'],
        ]);
    }

    public function sanitizeOptionalUrl(?string $url, string $field = 'url'): string
    {
        $clean = trim((string) $url);
        if ($clean === '') {
            return '';
        }

        return $this->sanitizeDestinationUrl($clean, $field);
    }

    public function saveDraftSection(string $sectionKey, array $data, int $adminId): HomepageContent
    {
        $allowedKeys = ['sections', 'hero', 'search', 'categories_config', 'promo_banner', 'services_config', 'seo'];
        if (! in_array($sectionKey, $allowedKeys, true)) {
            throw ValidationException::withMessages(['section' => ['Invalid homepage section key.']]);
        }

        return DB::transaction(function () use ($sectionKey, $data, $adminId) {
            /** @var HomepageContent $record */
            $record = $this->getOrInitializeRecord();
            $locked = HomepageContent::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();

            $defaults = self::defaultPayload();
            $draft = array_replace_recursive($defaults, is_array($locked->draft_payload) ? $locked->draft_payload : []);
            $draft[$sectionKey] = $data;

            $nextVer = (int) $locked->current_version_number + 1;

            $locked->update([
                'draft_payload' => $draft,
                'last_draft_saved_by_admin_id' => $adminId,
                'last_draft_saved_at' => now(),
                'current_version_number' => $nextVer,
            ]);

            HomepageContentVersion::create([
                'homepage_content_id' => $locked->id,
                'version_number' => $nextVer,
                'action' => 'draft_saved',
                'section_key' => $sectionKey,
                'payload' => $draft,
                'notes' => "Saved draft for {$sectionKey}",
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

            $defaults = self::defaultPayload();
            $draft = array_replace_recursive($defaults, is_array($locked->draft_payload) ? $locked->draft_payload : []);

            // Validate URLs in draft before publishing
            if (! empty($draft['hero']['primary_btn_url'])) {
                $draft['hero']['primary_btn_url'] = $this->sanitizeDestinationUrl($draft['hero']['primary_btn_url'], 'hero.primary_btn_url');
            }
            if (! empty($draft['hero']['secondary_btn_url'])) {
                $draft['hero']['secondary_btn_url'] = $this->sanitizeDestinationUrl($draft['hero']['secondary_btn_url'], 'hero.secondary_btn_url');
            }
            if (! empty($draft['promo_banner']['button_url'])) {
                $draft['promo_banner']['button_url'] = $this->sanitizeDestinationUrl($draft['promo_banner']['button_url'], 'promo_banner.button_url');
            }

            $nextVer = (int) $locked->current_version_number + 1;
            $now = now();

            $locked->update([
                'draft_payload' => $draft,
                'published_payload' => $draft,
                'published_by_admin_id' => $adminId,
                'published_at' => $now,
                'current_version_number' => $nextVer,
            ]);

            HomepageContentVersion::create([
                'homepage_content_id' => $locked->id,
                'version_number' => $nextVer,
                'action' => 'published',
                'section_key' => null,
                'payload' => $draft,
                'notes' => $notes ?: 'Published homepage content',
                'created_by_admin_id' => $adminId,
                'created_at' => $now,
            ]);

            Cache::forget(self::CACHE_KEY);

            return $locked->fresh();
        });
    }

    public function restoreVersionToDraft(HomepageContentVersion $version, int $adminId): HomepageContent
    {
        return DB::transaction(function () use ($version, $adminId) {
            $locked = HomepageContent::query()->whereKey($version->homepage_content_id)->lockForUpdate()->firstOrFail();
            $nextVer = (int) $locked->current_version_number + 1;
            $payload = array_replace_recursive(self::defaultPayload(), is_array($version->payload) ? $version->payload : []);

            $locked->update([
                'draft_payload' => $payload,
                'last_draft_saved_by_admin_id' => $adminId,
                'last_draft_saved_at' => now(),
                'current_version_number' => $nextVer,
            ]);

            HomepageContentVersion::create([
                'homepage_content_id' => $locked->id,
                'version_number' => $nextVer,
                'action' => 'restored',
                'section_key' => null,
                'payload' => $payload,
                'notes' => "Restored version #{$version->version_number} into draft",
                'created_by_admin_id' => $adminId,
                'created_at' => now(),
            ]);

            return $locked->fresh();
        });
    }

    public function uploadMedia(UploadedFile $file, ?string $altText, int $adminId): HomepageMedia
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw ValidationException::withMessages([
                'image' => ['Only JPG, PNG, and WebP images are permitted.'],
            ]);
        }

        $safeName = Str::uuid()->toString() . '.' . $ext;
        $path = $file->storeAs('homepage-media', $safeName, 'public');

        $width = null;
        $height = null;
        try {
            $dims = @getimagesize($file->getRealPath());
            if (is_array($dims)) {
                $width = (int) ($dims[0] ?? 0) ?: null;
                $height = (int) ($dims[1] ?? 0) ?: null;
            }
        } catch (\Throwable) {
            // Ignore dimension read failure
        }

        return HomepageMedia::create([
            'filename' => $file->getClientOriginalName() ?: $safeName,
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $file->getMimeType() ?: 'image/' . $ext,
            'size_bytes' => (int) $file->getSize(),
            'width' => $width,
            'height' => $height,
            'alt_text' => $altText ? trim($altText) : null,
            'uploaded_by_admin_id' => $adminId,
        ]);
    }

    public function isMediaInPublishedUse(HomepageMedia $media): bool
    {
        $records = HomepageContent::query()->whereNotNull('published_payload')->get(['published_payload']);
        foreach ($records as $record) {
            $pubJson = json_encode($record->published_payload ?? [], JSON_UNESCAPED_SLASHES);
            if (is_string($pubJson) && $pubJson !== '' && str_contains($pubJson, $media->path)) {
                return true;
            }
        }

        return false;
    }

    public function deleteMedia(HomepageMedia $media): void
    {
        if ($this->isMediaInPublishedUse($media)) {
            throw ValidationException::withMessages([
                'media' => ['Cannot delete an image currently used by the published homepage. Replace or unpublish it first.'],
            ]);
        }

        Storage::disk($media->disk ?: 'public')->delete($media->path);
        $media->delete();
    }

    public function buildPublicRenderData(array $payload, array $categoryIconMap): array
    {
        $sections = collect($payload['sections'] ?? [])
            ->sortBy(fn ($s) => (int) ($s['order'] ?? 99))
            ->values();

        // Categories
        $catConfig = $payload['categories_config'] ?? [];
        $catOverrides = $catConfig['overrides'] ?? [];
        $catMode = $catConfig['mode'] ?? 'all_active';

        $activeCategories = ItemCategory::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $categories = $activeCategories
            ->filter(function (ItemCategory $cat) use ($catOverrides, $catMode) {
                $ov = $catOverrides[$cat->id] ?? ($catOverrides[(string) $cat->id] ?? null);
                if ($catMode === 'selected') {
                    return $ov !== null && ! empty($ov['visible']);
                }

                return $ov === null || ! array_key_exists('visible', $ov) || ! empty($ov['visible']);
            })
            ->map(function (ItemCategory $cat) use ($catOverrides, $categoryIconMap) {
                $ov = $catOverrides[$cat->id] ?? ($catOverrides[(string) $cat->id] ?? []);
                $fallbackFile = $categoryIconMap[$cat->slug] ?? '03_laundry_basket.png';
                $img = ! empty($ov['custom_image_url'])
                    ? $this->resolveImageUrl($ov['custom_image_url'])
                    : ($cat->image_url ?: asset('images/laundry-icons/' . $fallbackFile));
                $desc = ! empty($ov['custom_description'])
                    ? $ov['custom_description']
                    : ($cat->short_description ?: ($cat->description ?: 'Professional garment care tailored to this category.'));

                return [
                    'model' => $cat,
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'description' => $desc,
                    'image_url' => $img,
                    'order' => (int) ($ov['order'] ?? (1000 + $cat->id)),
                ];
            })
            ->sortBy('order')
            ->values();

        // Services (always live pricing & active check)
        $svcConfig = $payload['services_config'] ?? [];
        $svcOverrides = $svcConfig['overrides'] ?? [];
        $svcMode = $svcConfig['mode'] ?? 'all_active';

        $activeServices = Service::query()
            ->with(['unit', 'category', 'item.category'])
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('item_id')
                    ->orWhereHas('item', fn ($iq) => $iq->where('is_active', true));
            })
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $services = $activeServices
            ->filter(function (Service $svc) use ($svcOverrides, $svcMode) {
                $ov = $svcOverrides[$svc->id] ?? ($svcOverrides[(string) $svc->id] ?? null);
                if ($svcMode === 'selected') {
                    return $ov !== null && ! empty($ov['visible']);
                }

                return $ov === null || ! array_key_exists('visible', $ov) || ! empty($ov['visible']);
            })
            ->map(function (Service $svc) use ($svcOverrides, $categoryIconMap) {
                $ov = $svcOverrides[$svc->id] ?? ($svcOverrides[(string) $svc->id] ?? []);
                $svcCat = $svc->category ?: $svc->item?->category;
                $svcCatSlug = $svcCat?->slug;
                $fallbackIcon = $categoryIconMap[$svcCatSlug ?? ''] ?? '03_laundry_basket.png';

                $customImg = ! empty($ov['custom_image_url']) ? $this->resolveImageUrl($ov['custom_image_url']) : null;
                $img = $customImg ?: $svc->image_url;
                $isPhoto = (bool) $img;
                if (! $img) {
                    $img = asset('images/laundry-icons/' . $fallbackIcon);
                }

                return [
                    'model' => $svc,
                    'id' => $svc->id,
                    'name' => $svc->name,
                    'category_name' => $svcCat?->name,
                    'badge' => ! empty($ov['custom_badge']) ? $ov['custom_badge'] : $svcCat?->name,
                    'description' => ! empty($ov['custom_description'])
                        ? $ov['custom_description']
                        : ($svc->description ?: 'Professional laundry care with doorstep collection.'),
                    'image_url' => $img,
                    'is_photo' => $isPhoto,
                    'url' => route('services.index', array_filter([
                        'category' => $svcCatSlug,
                        'item' => $svc->item_id,
                        'q' => $svc->name,
                    ], fn ($v) => $v !== null && $v !== '')),
                    'order' => (int) ($ov['order'] ?? (1000 + $svc->id)),
                ];
            })
            ->sortBy('order')
            ->values();

        return [
            'sections' => $sections,
            'categories' => $categories,
            'services' => $services,
        ];
    }
}
