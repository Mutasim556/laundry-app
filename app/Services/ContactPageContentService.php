<?php

namespace App\Services;

use App\Models\HomepageContent;
use App\Models\HomepageContentVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContactPageContentService
{
    public const CACHE_KEY = 'contact_page.published.payload';
    public const PAGE_SLUG = 'contact';

    public function __construct(
        protected HomepageContentService $homepageCms
    ) {}

    public static function defaultPayload(): array
    {
        return [
            'sections' => [
                ['key' => 'hero', 'label' => 'Contact Hero Banner', 'subtitle' => 'Header badge, headline & description', 'visible' => true, 'order' => 1],
                ['key' => 'contact_and_form', 'label' => 'Contact Info & Enquiry Form', 'subtitle' => 'Contact Info (paired with Map) & Enquiry Form (paired with Business Hours)', 'visible' => true, 'order' => 2],
                ['key' => 'map', 'label' => 'Location / Map', 'subtitle' => 'Interactive Google Map & office location (paired with Contact Info)', 'visible' => true, 'order' => 3],
                ['key' => 'business_hours', 'label' => 'Business Hours', 'subtitle' => 'Support & office opening hours (paired with Enquiry Form)', 'visible' => true, 'order' => 4],
                ['key' => 'social', 'label' => 'Social Media Links', 'subtitle' => 'Configured social platform links', 'visible' => true, 'order' => 5],
                ['key' => 'faq', 'label' => 'Quick Help FAQ', 'subtitle' => 'Optional frequently asked support questions', 'visible' => false, 'order' => 6],
            ],
            'hero' => [
                'badge' => 'CONTACT FRESH GO',
                'headline' => "We're Here to Help",
                'description' => 'Have a question about our services or your order? Our team would be happy to hear from you.',
                'bg_preset' => 'gold_soft',
            ],
            'contact_info' => [
                'brand_name' => 'FreshGo Laundry',
                'support_email' => '',
                'phone_country_code' => '',
                'phone_number' => '',
                'whatsapp_number' => '',
                'address' => '',
                'city' => '',
                'country' => '',
                'description' => 'Reach out to our support team for service questions, order assistance, or feedback.',
                'availability_text' => 'Customer support available during configured business hours.',
            ],
            'form_config' => [
                'heading' => 'Send Us a Message',
                'subtitle' => 'Fill out the form below and our team will respond as soon as possible.',
                'submit_label' => 'Send Message →',
                'success_message' => 'Thank you! Your enquiry has been received.',
                'show_phone' => true,
                'show_booking_reference' => true,
            ],
            'business_hours' => [
                ['day' => 'Monday', 'is_open' => true, 'open_time' => '09:00', 'close_time' => '18:00', 'note' => ''],
                ['day' => 'Tuesday', 'is_open' => true, 'open_time' => '09:00', 'close_time' => '18:00', 'note' => ''],
                ['day' => 'Wednesday', 'is_open' => true, 'open_time' => '09:00', 'close_time' => '18:00', 'note' => ''],
                ['day' => 'Thursday', 'is_open' => true, 'open_time' => '09:00', 'close_time' => '18:00', 'note' => ''],
                ['day' => 'Friday', 'is_open' => true, 'open_time' => '09:00', 'close_time' => '18:00', 'note' => ''],
                ['day' => 'Saturday', 'is_open' => true, 'open_time' => '10:00', 'close_time' => '16:00', 'note' => ''],
                ['day' => 'Sunday', 'is_open' => false, 'open_time' => '', 'close_time' => '', 'note' => 'Closed'],
            ],
            'map' => [
                'title' => 'Our Office Location',
                'public_address' => '',
                'map_url' => '',
                'embed_url' => '',
            ],
            'social' => [
                'facebook' => '',
                'instagram' => '',
                'linkedin' => '',
                'youtube' => '',
                'tiktok' => '',
                'whatsapp' => '',
            ],
            'faq' => [
                'title' => 'Frequently Asked Questions',
                'items' => [],
            ],
            'seo' => [
                'meta_title' => 'Contact Us | FreshGo Laundry',
                'meta_description' => 'Contact FreshGo Laundry & Dry Cleaning for order support, service questions, or general enquiries.',
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

    public function sanitizeOptionalHttpsUrl(?string $url, string $field = 'url'): string
    {
        $clean = trim((string) $url);
        if ($clean === '') {
            return '';
        }

        if (preg_match('/^\s*(javascript|data|vbscript):/i', $clean)) {
            throw ValidationException::withMessages([$field => ['Unsafe URL scheme is not permitted.']]);
        }

        if (! str_starts_with($clean, 'https://') || ! filter_var($clean, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([$field => ['External URLs must start with https:// and be valid.']]);
        }

        return $clean;
    }

    public function saveDraftSection(string $sectionKey, array $data, int $adminId): HomepageContent
    {
        $allowed = ['sections', 'hero', 'contact_info', 'form_config', 'business_hours', 'map', 'social', 'faq', 'seo'];
        if (! in_array($sectionKey, $allowed, true)) {
            throw ValidationException::withMessages(['section' => ['Invalid Contact page section key.']]);
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
                'notes' => "Saved draft for contact.{$sectionKey}",
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
                'notes' => $notes ?: 'Published Contact page content',
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
