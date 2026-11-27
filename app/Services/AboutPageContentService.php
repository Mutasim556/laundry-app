<?php

namespace App\Services;

use App\Models\HomepageContent;
use App\Models\HomepageContentVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AboutPageContentService
{
    public const CACHE_KEY = 'about_page.published.payload';
    public const PAGE_SLUG = 'about';

    public function __construct(
        protected HomepageContentService $homepageCms
    ) {}

    public static function defaultPayload(): array
    {
        return [
            'sections' => [
                ['key' => 'hero', 'label' => 'About Hero Banner', 'subtitle' => 'Main headline, description, image & CTAs', 'visible' => true, 'order' => 1],
                ['key' => 'story', 'label' => 'Our Story', 'subtitle' => 'Company story and brand introduction', 'visible' => true, 'order' => 2],
                ['key' => 'mission_vision', 'label' => 'Mission & Vision', 'subtitle' => 'Compact mission and vision cards', 'visible' => true, 'order' => 3],
                ['key' => 'benefits', 'label' => 'Why Choose Fresh Go', 'subtitle' => 'Service benefit highlight cards', 'visible' => true, 'order' => 4],
                ['key' => 'how_it_works', 'label' => 'How Fresh Go Works', 'subtitle' => '4-step collection & delivery workflow', 'visible' => true, 'order' => 5],
                ['key' => 'stats', 'label' => 'Company Statistics', 'subtitle' => 'Optional verified milestones (hidden until configured)', 'visible' => false, 'order' => 6],
                ['key' => 'cta', 'label' => 'Final Booking CTA', 'subtitle' => 'Closing call-to-action banner', 'visible' => true, 'order' => 7],
            ],
            'hero' => [
                'badge' => 'ABOUT FRESH GO',
                'headline' => 'Fresh Clothes. More Time for What Matters.',
                'description' => 'Discover a simpler way to care for your clothes, with professional laundry and dry cleaning services and convenient collection and delivery.',
                'image_url' => 'images/freshgo-full-poster.jpg',
                'image_alt' => 'Fresh Go Laundry Care',
                'bg_preset' => 'gold_soft',
                'primary_btn_label' => 'Book a Collection →',
                'primary_btn_url' => '/booking/services',
                'primary_btn_visible' => true,
                'secondary_btn_label' => 'Explore Services',
                'secondary_btn_url' => '/services',
                'secondary_btn_visible' => true,
            ],
            'story' => [
                'eyebrow' => 'OUR STORY',
                'heading' => 'Designed Around Everyday Convenience',
                'body' => 'Fresh Go Laundry & Dry Cleaning brings reliable garment care to your doorstep. From everyday wash and fold to delicate dry cleaning and crisp ironing, our partner stores and dedicated Delivery Persons coordinate every step so your wardrobe stays fresh without disrupting your week.',
                'image_url' => 'images/laundry-icons/07_folded_laundry.png',
                'image_alt' => 'Neatly folded Fresh Go laundry',
            ],
            'mission_vision' => [
                'mission_visible' => true,
                'mission_title' => 'Our Mission',
                'mission_description' => 'To deliver dependable, transparent, and gentle garment care with convenient doorstep collection and return.',
                'vision_visible' => true,
                'vision_title' => 'Our Vision',
                'vision_description' => 'To make professional laundry and dry cleaning effortless for every household through thoughtful service and technology.',
            ],
            'benefits' => [
                'title' => 'Why Choose Fresh Go',
                'subtitle' => 'Built for clarity, garment care, and smooth coordination.',
                'items' => [
                    ['title' => 'Convenient Collection', 'description' => 'Schedule doorstep pickup at a time window that fits your day.', 'icon' => 'truck', 'visible' => true, 'order' => 1],
                    ['title' => 'Professional Garment Care', 'description' => 'Dedicated handling for everyday laundry, delicates, and dry cleaning.', 'icon' => 'shield', 'visible' => true, 'order' => 2],
                    ['title' => 'Reliable Delivery', 'description' => 'Track progress from collection through cleaning to final doorstep return.', 'icon' => 'clock', 'visible' => true, 'order' => 3],
                    ['title' => 'Easy Online Booking', 'description' => 'Transparent service catalog pricing and simple online checkout.', 'icon' => 'smartphone', 'visible' => true, 'order' => 4],
                ],
            ],
            'how_it_works' => [
                'title' => 'How Fresh Go Works',
                'subtitle' => 'Four straightforward steps from booking to fresh delivery.',
                'steps' => [
                    ['step' => '01', 'title' => 'Book Online', 'description' => 'Select your services, pickup address, and preferred time slot.', 'visible' => true, 'order' => 1],
                    ['step' => '02', 'title' => 'We Collect', 'description' => 'A Delivery Person picks up your garments from your doorstep.', 'visible' => true, 'order' => 2],
                    ['step' => '03', 'title' => 'Garments Are Processed', 'description' => 'Our partner store cleans, presses, and inspects each item.', 'visible' => true, 'order' => 3],
                    ['step' => '04', 'title' => 'We Deliver', 'description' => 'Fresh, clean clothes are returned straight back to your door.', 'visible' => true, 'order' => 4],
                ],
            ],
            'stats' => [
                'title' => 'Fresh Go at a Glance',
                'items' => [],
            ],
            'cta' => [
                'headline' => 'Ready to Experience Fresh Go?',
                'description' => 'Book your laundry collection today and enjoy effortless garment care.',
                'button_label' => 'Book a Collection →',
                'button_url' => '/booking/services',
                'button_visible' => true,
                'bg_preset' => 'gold_gradient',
            ],
            'seo' => [
                'meta_title' => 'About Us | FreshGo Laundry',
                'meta_description' => 'Learn about FreshGo Laundry & Dry Cleaning—our story, mission, and simple 4-step collection and delivery service.',
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
        return $this->homepageCms->sanitizeDestinationUrl($url, $field);
    }

    public function sanitizeOptionalUrl(?string $url, string $field = 'url'): string
    {
        return $this->homepageCms->sanitizeOptionalUrl($url, $field);
    }

    public function saveDraftSection(string $sectionKey, array $data, int $adminId): HomepageContent
    {
        $allowed = ['sections', 'hero', 'story', 'mission_vision', 'benefits', 'how_it_works', 'stats', 'cta', 'seo'];
        if (! in_array($sectionKey, $allowed, true)) {
            throw ValidationException::withMessages(['section' => ['Invalid About page section key.']]);
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
                'notes' => "Saved draft for about.{$sectionKey}",
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

            if (! empty($draft['hero']['primary_btn_url'])) {
                $draft['hero']['primary_btn_url'] = $this->sanitizeDestinationUrl($draft['hero']['primary_btn_url'], 'hero.primary_btn_url');
            }
            if (! empty($draft['hero']['secondary_btn_url'])) {
                $draft['hero']['secondary_btn_url'] = $this->sanitizeDestinationUrl($draft['hero']['secondary_btn_url'], 'hero.secondary_btn_url');
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
                'notes' => $notes ?: 'Published About page content',
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
