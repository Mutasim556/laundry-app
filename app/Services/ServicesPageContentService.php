<?php

namespace App\Services;

use App\Models\HomepageContent;
use App\Models\HomepageContentVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;

class ServicesPageContentService
{
    public const CACHE_KEY = 'services_page.published.payload';
    public const PAGE_SLUG = 'services';

    public function __construct(
        protected HomepageContentService $homepageCms
    ) {}

    public static function defaultPayload(): array
    {
        return [
            'sections' => [
                [
                    'key' => 'banner',
                    'label' => 'Services Hero Banner',
                    'subtitle' => 'Top Fresh Go Services hero banner with image and CTAs',
                    'visible' => true,
                    'order' => 1,
                ],
                [
                    'key' => 'search',
                    'label' => 'Service Search & Sort Bar',
                    'subtitle' => 'Search services, categories, or items + sort dropdown',
                    'visible' => true,
                    'order' => 2,
                ],
                [
                    'key' => 'categories',
                    'label' => 'Service Categories',
                    'subtitle' => 'Category slider / filter navigation',
                    'visible' => true,
                    'order' => 3,
                ],
                [
                    'key' => 'featured',
                    'label' => 'Featured Services',
                    'subtitle' => 'Optional featured services carousel above catalog',
                    'visible' => false,
                    'order' => 4,
                ],
                [
                    'key' => 'catalog',
                    'label' => 'All Services Catalog',
                    'subtitle' => 'Main service cards grid, item chips, and booking sidebar',
                    'visible' => true,
                    'order' => 5,
                ],
                [
                    'key' => 'cta',
                    'label' => 'Bottom Call to Action',
                    'subtitle' => 'Closing booking prompt banner',
                    'visible' => true,
                    'order' => 6,
                ],
            ],
            'banner' => [
                'badge' => 'FRESH GO SERVICES',
                'headline' => 'Professional Care for Every Garment',
                'description' => 'Explore our laundry, dry cleaning, ironing, and garment care services—all designed to make your life easier.',
                'image_url' => 'images/freshgo-full-poster.jpg',
                'image_alt' => 'FreshGo Laundry Services',
                'bg_preset' => 'gold_soft',
                'primary_btn_label' => 'Book a Collection →',
                'primary_btn_url' => '/booking/services',
                'primary_btn_visible' => true,
                'secondary_btn_label' => 'Explore Services',
                'secondary_btn_url' => '#catalog-results',
                'secondary_btn_visible' => true,
            ],
            'search' => [
                'visible' => true,
                'placeholder' => 'Search services, categories, or laundry items...',
                'show_sort' => true,
                'show_categories' => true,
                'show_services' => true,
                'show_items' => true,
                'max_suggestions' => 5,
            ],
            'categories_config' => [
                'title' => 'Our Categories',
                'subtitle' => '',
                'mode' => 'all_active',
                'overrides' => [],
            ],
            'featured_config' => [
                'title' => 'Featured Services',
                'subtitle' => 'Hand-picked garment care options ready for collection.',
                'autoplay' => true,
                'interval_ms' => 4200,
                'show_arrows' => true,
                'pause_on_hover' => true,
                'loop' => true,
                'desktop_per_view' => 4,
                'service_ids' => [],
            ],
            'catalog_config' => [
                'title' => '',
                'subtitle' => '',
                'per_page' => 12,
                'desktop_columns' => 3,
                'overrides' => [],
            ],
            'cta' => [
                'headline' => 'Ready for Fresh, Clean Clothes?',
                'description' => 'Schedule your collection and let Fresh Go take care of the rest.',
                'button_label' => 'Book Now →',
                'button_url' => '/booking/services',
                'button_visible' => true,
                'bg_preset' => 'gold_gradient',
            ],
            'seo' => [
                'meta_title' => '',
                'meta_description' => 'Explore FreshGo laundry, dry cleaning, and ironing services and compare transparent prices.',
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
        return $this->homepageCms->resolveImageUrl($pathOrUrl);
    }

    public function sanitizeDestinationUrl(?string $url, string $field = 'url'): string
    {
        $clean = trim((string) $url);
        if (str_starts_with($clean, '#') && preg_match('/^#[A-Za-z0-9\-_]+$/', $clean)) {
            return $clean;
        }

        return $this->homepageCms->sanitizeDestinationUrl($clean, $field);
    }

    public function sanitizeOptionalUrl(?string $url, string $field = 'url'): string
    {
        return $this->homepageCms->sanitizeOptionalUrl($url, $field);
    }

    public function saveDraftSection(string $sectionKey, array $data, int $adminId): HomepageContent
    {
        $allowedKeys = ['sections', 'banner', 'search', 'categories_config', 'featured_config', 'catalog_config', 'cta', 'seo'];
        if (! in_array($sectionKey, $allowedKeys, true)) {
            throw ValidationException::withMessages(['section' => ['Invalid Services page section key.']]);
        }

        return DB::transaction(function () use ($sectionKey, $data, $adminId) {
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
                'notes' => "Saved draft for services.{$sectionKey}",
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

            if (! empty($draft['banner']['primary_btn_url'])) {
                $draft['banner']['primary_btn_url'] = $this->sanitizeDestinationUrl($draft['banner']['primary_btn_url'], 'banner.primary_btn_url');
            }
            if (! empty($draft['banner']['secondary_btn_url'])) {
                $draft['banner']['secondary_btn_url'] = $this->sanitizeDestinationUrl($draft['banner']['secondary_btn_url'], 'banner.secondary_btn_url');
            }
            if (! empty($draft['cta']['button_url'])) {
                $draft['cta']['button_url'] = $this->sanitizeDestinationUrl($draft['cta']['button_url'], 'cta.button_url');
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
                'notes' => $notes ?: 'Published Services page content',
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

    public function getVisibleActiveCategories(array $payload)
    {
        $catCfg = $payload['categories_config'] ?? [];
        $overrides = $catCfg['overrides'] ?? [];
        $mode = $catCfg['mode'] ?? 'all_active';

        return ItemCategory::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->filter(function (ItemCategory $cat) use ($overrides, $mode) {
                $ov = $overrides[$cat->id] ?? ($overrides[(string) $cat->id] ?? null);
                if ($mode === 'selected') {
                    return $ov !== null && ! empty($ov['visible']);
                }

                return $ov === null || ! array_key_exists('visible', $ov) || ! empty($ov['visible']);
            })
            ->sortBy(function (ItemCategory $cat) use ($overrides) {
                $ov = $overrides[$cat->id] ?? ($overrides[(string) $cat->id] ?? []);

                return (int) ($ov['order'] ?? (1000 + $cat->id));
            })
            ->values();
    }

    public function getFeaturedServices(array $payload)
    {
        $featCfg = $payload['featured_config'] ?? [];
        $ids = array_values(array_filter(array_map('intval', (array) ($featCfg['service_ids'] ?? []))));
        if (empty($ids)) {
            return collect();
        }

        $services = Service::query()
            ->with(['unit', 'category', 'item.category'])
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('item_id')->orWhereHas('item', fn ($iq) => $iq->where('is_active', true));
            })
            ->get()
            ->keyBy('id');

        $ordered = collect();
        foreach ($ids as $id) {
            if ($services->has($id)) {
                $ordered->push($services->get($id));
            }
        }

        return $ordered;
    }
}
