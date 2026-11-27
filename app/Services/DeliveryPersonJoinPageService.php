<?php

namespace App\Services;

use App\Models\HomepageContent;
use App\Models\HomepageContentVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryPersonJoinPageService
{
    public const CACHE_KEY = 'delivery_person_join.published.payload';
    public const PAGE_SLUG = 'delivery-person-join';

    public const ALLOWED_SECTION_KEYS = [
        'hero',
        'benefits',
        'how_it_works',
        'requirements',
        'application_form',
        'faqs',
        'cta',
    ];

    public const ALLOWED_ICONS = [
        'truck',
        'shield',
        'clock',
        'smartphone',
        'check-circle',
        'calendar',
        'map-pin',
        'file-text',
        'award',
        'users',
    ];

    public function __construct(
        protected HomepageContentService $homepageCms
    ) {}

    public static function defaultPayload(): array
    {
        return [
            'general' => [
                'page_enabled' => true,
                'applications_open' => true,
                'nav_label' => 'Join as a Delivery Person',
                'footer_label' => 'Join as a Delivery Person',
                'status_badge' => 'Fresh Go Delivery Network',
                'closed_title' => 'Applications Currently Closed',
                'closed_message' => 'We are not accepting new Delivery Person applications at this moment. Existing applicants can still track their application status below.',
                'expected_reopening' => '',
            ],
            'sections' => [
                ['key' => 'hero', 'label' => 'Hero Banner', 'subtitle' => 'Main headline, image & CTAs', 'visible' => true, 'order' => 1],
                ['key' => 'how_it_works', 'label' => 'How It Works', 'subtitle' => '4-step onboarding workflow', 'visible' => true, 'order' => 2],
                ['key' => 'benefits', 'label' => 'Why Join Fresh Go', 'subtitle' => 'Delivery Person benefit cards', 'visible' => true, 'order' => 3],
                ['key' => 'requirements', 'label' => 'Eligibility Requirements', 'subtitle' => 'Informational requirements & region notes', 'visible' => true, 'order' => 4],
                ['key' => 'application_form', 'label' => 'Application Form', 'subtitle' => '5-step AJAX registration form', 'visible' => true, 'order' => 5],
                ['key' => 'faqs', 'label' => 'Frequently Asked Questions', 'subtitle' => 'Applicant FAQ accordion', 'visible' => true, 'order' => 6],
                ['key' => 'cta', 'label' => 'Bottom CTA', 'subtitle' => 'Closing call-to-action banner', 'visible' => true, 'order' => 7],
            ],
            'hero' => [
                'badge' => 'Fresh Go Delivery Network',
                'headline' => 'Deliver Freshness. Grow With Fresh Go.',
                'subtitle' => 'Join our delivery network and help customers enjoy convenient laundry collection and delivery.',
                'description' => 'Manage collection and final delivery jobs in one streamlined Delivery Person Portal after Admin verification.',
                'image_url' => '',
                'mobile_image_url' => '',
                'image_alt' => 'Fresh Go Delivery Person',
                'bg_preset' => 'gold_soft',
                'alignment' => 'left',
                'primary_btn_label' => 'Apply as a Delivery Person',
                'primary_btn_visible' => true,
                'secondary_btn_label' => 'Already Registered? Login',
                'secondary_btn_url' => '/delivery-person/login',
                'secondary_btn_visible' => true,
            ],
            'benefits' => [
                'title' => 'Why Deliver With Fresh Go',
                'subtitle' => 'Built around clear workflows and transparent portal tools.',
                'items' => [
                    ['title' => 'Simple Online Registration', 'description' => 'Apply in minutes with guided steps and instant document preview.', 'icon' => 'smartphone', 'visible' => true, 'order' => 1],
                    ['title' => 'Digital Job Management', 'description' => 'View assigned collection and final delivery jobs from one portal.', 'icon' => 'truck', 'visible' => true, 'order' => 2],
                    ['title' => 'Clear Earnings Tracking', 'description' => 'Review completed job earnings, cash custody, and settlement history.', 'icon' => 'check-circle', 'visible' => true, 'order' => 3],
                    ['title' => 'Secure Profile & Verification', 'description' => 'Private document handling and Admin-verified account activation.', 'icon' => 'shield', 'visible' => true, 'order' => 4],
                ],
            ],
            'how_it_works' => [
                'title' => 'How It Works',
                'subtitle' => 'Four clear steps from application to active Delivery Person.',
                'steps' => [
                    ['step' => '01', 'title' => 'Apply Online', 'description' => 'Complete the 5-step registration form with your contact and service area details.', 'icon' => 'file-text', 'visible' => true, 'order' => 1],
                    ['step' => '02', 'title' => 'Submit Your Details', 'description' => 'Upload any required verification documents securely to our private review vault.', 'icon' => 'shield', 'visible' => true, 'order' => 2],
                    ['step' => '03', 'title' => 'Get Verified by Admin', 'description' => 'Our Admin team reviews your application and notifies you of approval or next steps.', 'icon' => 'check-circle', 'visible' => true, 'order' => 3],
                    ['step' => '04', 'title' => 'Start Accepting Assigned Jobs', 'description' => 'Once approved, sign in to the Delivery Person Portal to manage assigned jobs.', 'icon' => 'truck', 'visible' => true, 'order' => 4],
                ],
            ],
            'requirements' => [
                'title' => 'Eligibility Requirements',
                'subtitle' => 'Review these standard requirements before submitting your application.',
                'region_note' => 'Requirements may vary based on your operating country and selected delivery method.',
                'items' => [
                    ['title' => 'Valid Contact & Identity', 'description' => 'Active email address, mobile number, and valid photo identification.', 'icon' => 'shield', 'visible' => true, 'order' => 1],
                    ['title' => 'Local Area Familiarity', 'description' => 'Ability to collect and deliver within your preferred service area.', 'icon' => 'map-pin', 'visible' => true, 'order' => 2],
                    ['title' => 'Vehicle & Licence (If Motorised)', 'description' => 'Valid driving licence and vehicle documentation for motorbike, car, or van.', 'icon' => 'truck', 'visible' => true, 'order' => 3],
                ],
            ],
            'form_config' => [
                'heading' => 'Delivery Person Registration',
                'subtitle' => 'All applications are reviewed by an Admin before account activation.',
                'intro_text' => 'Complete all 5 steps accurately. Your uploaded documents remain private.',
                'step_1_title' => '1. Personal',
                'step_2_title' => '2. Address',
                'step_3_title' => '3. Delivery',
                'step_4_title' => '4. Documents',
                'step_5_title' => '5. Security',
                'dob_label' => 'Date of Birth',
                'dob_placeholder' => 'Select date of birth',
                'dob_helper' => 'Cannot be a future date.',
                'dob_format' => 'd M Y',
                'default_country_code' => 'GB',
                'phone_label' => 'Mobile Number',
                'phone_placeholder' => '7700 900111',
                'phone_helper' => 'Select your country flag and enter your mobile number.',
                'show_experience' => true,
                'show_profile_photo' => true,
                'upload_instructions' => 'Preview your images or PDFs before submitting. Files stay in your browser until you submit and are stored privately on the server.',
                'submit_label' => 'Submit Application',
                'success_title' => 'Application Submitted Successfully',
                'success_message' => 'Your application has been received and is awaiting Admin verification. You will be notified when its status changes.',
                'consent_text' => 'I confirm that the information provided is accurate and I consent to Fresh Go processing my application for verification.',
            ],
            'documents' => [
                ['key' => 'profile_photo', 'label' => 'Profile Photo', 'description' => 'Clear headshot for your Delivery Person profile.', 'required' => false, 'motor_only' => false, 'max_mb' => 4, 'visible' => true, 'order' => 1],
                ['key' => 'identity_document', 'label' => 'Identity Verification', 'description' => 'Government-issued photo ID for Admin verification.', 'required' => false, 'motor_only' => false, 'max_mb' => 5, 'visible' => true, 'order' => 2],
                ['key' => 'driving_licence_document', 'label' => 'Driving Licence Document', 'description' => 'Required only for motorised vehicles.', 'required' => false, 'motor_only' => true, 'max_mb' => 5, 'visible' => true, 'order' => 3],
                ['key' => 'vehicle_document', 'label' => 'Vehicle Insurance / Document', 'description' => 'Valid vehicle insurance or registration.', 'required' => false, 'motor_only' => true, 'max_mb' => 5, 'visible' => true, 'order' => 4],
            ],
            'faqs' => [
                'title' => 'Frequently Asked Questions',
                'subtitle' => 'Common questions about applying to become a Fresh Go Delivery Person.',
                'items' => [
                    ['question' => 'How long does Admin verification take?', 'answer' => 'Our team reviews applications in order of receipt. You will receive an email update when your status changes.', 'visible' => true, 'order' => 1],
                    ['question' => 'Can I log in immediately after applying?', 'answer' => 'No. Your Delivery Person Portal account is activated only after an Admin reviews and approves your application.', 'visible' => true, 'order' => 2],
                    ['question' => 'Are my verification documents public?', 'answer' => 'No. All uploaded verification documents are stored privately and are only accessible to authorized Fresh Go Admins.', 'visible' => true, 'order' => 3],
                ],
            ],
            'cta' => [
                'headline' => 'Already Applied?',
                'description' => 'Check your current application review status or submit requested additional information.',
                'button_label' => 'Track Application',
                'button_url' => '/delivery-person/application-status',
                'button_visible' => true,
                'bg_preset' => 'gold_gradient',
                'image_url' => '',
            ],
            'demo' => [
                'enabled' => true,
                'button_label' => 'View Registration Demo',
                'modal_title' => 'How to Register as a Delivery Person',
                'modal_subtitle' => 'Explore the application process before getting started.',
                'intro_note' => 'Sample Information — Demo Only. Nothing entered here is saved or submitted.',
                'step_1_note' => 'Enter your name, email, international mobile number with country flag, and date of birth.',
                'step_2_note' => 'Provide your residential address and preferred delivery service area.',
                'step_3_note' => 'Choose how you will deliver. Vehicle details are only shown for motorised transport.',
                'step_4_note' => 'Preview your image or PDF verification documents privately in your browser before submitting.',
                'step_5_note' => 'Set a strong password, accept the consent terms, and submit for Admin verification.',
                'final_title' => "You're Ready to Apply!",
                'final_message' => 'Complete the real registration form and submit your application. Our Admin team will review your information. Once approved and activated, you can access your Delivery Person Portal and become eligible for delivery assignments.',
                'cta_label' => 'Start My Application',
            ],
            'seo' => [
                'meta_title' => 'Join as a Delivery Person | Fresh Go',
                'meta_description' => 'Apply online to join the Fresh Go delivery network for laundry collection and delivery.',
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
        return HomepageContent::firstOrCreate(
            ['slug' => self::PAGE_SLUG],
            [
                'draft_payload' => self::defaultPayload(),
                'published_payload' => self::defaultPayload(),
                'current_version_number' => 1,
                'published_at' => now(),
            ]
        );
    }

    public function getPublicPayload(bool $previewDraft = false): array
    {
        $defaults = self::defaultPayload();

        if ($previewDraft) {
            $record = $this->getOrInitializeRecord();
            $raw = is_array($record->draft_payload) ? $record->draft_payload : [];

            return $this->mergeWithDefaults($defaults, $raw);
        }

        $cached = Cache::rememberForever(self::CACHE_KEY, function () use ($defaults) {
            $record = $this->getOrInitializeRecord();
            $raw = is_array($record->published_payload) ? $record->published_payload : [];

            return $this->mergeWithDefaults($defaults, $raw);
        });

        return is_array($cached) ? $cached : $defaults;
    }

    protected function mergeWithDefaults(array $defaults, array $raw): array
    {
        $merged = array_replace_recursive($defaults, $raw);

        // Preserve exact Admin-edited list arrays instead of merging stale default indices
        foreach (['sections', 'documents'] as $listKey) {
            if (isset($raw[$listKey]) && is_array($raw[$listKey])) {
                $merged[$listKey] = $raw[$listKey];
            }
        }
        if (isset($raw['benefits']['items']) && is_array($raw['benefits']['items'])) {
            $merged['benefits']['items'] = $raw['benefits']['items'];
        }
        if (isset($raw['how_it_works']['steps']) && is_array($raw['how_it_works']['steps'])) {
            $merged['how_it_works']['steps'] = $raw['how_it_works']['steps'];
        }
        if (isset($raw['requirements']['items']) && is_array($raw['requirements']['items'])) {
            $merged['requirements']['items'] = $raw['requirements']['items'];
        }
        if (isset($raw['faqs']['items']) && is_array($raw['faqs']['items'])) {
            $merged['faqs']['items'] = $raw['faqs']['items'];
        }

        return $merged;
    }

    public function resolveImageUrl(?string $path): string
    {
        return $this->homepageCms->resolveImageUrl($path);
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
        $allowed = ['general', 'sections', 'hero', 'benefits', 'how_it_works', 'requirements', 'form_config', 'documents', 'faqs', 'cta', 'demo', 'seo'];
        if (! in_array($sectionKey, $allowed, true)) {
            throw ValidationException::withMessages(['section' => ['Invalid Delivery Person Join section key.']]);
        }

        return DB::transaction(function () use ($sectionKey, $data, $adminId) {
            $record = $this->getOrInitializeRecord();
            $locked = HomepageContent::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();

            $defaults = self::defaultPayload();
            $draft = $this->mergeWithDefaults($defaults, is_array($locked->draft_payload) ? $locked->draft_payload : []);
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
                'notes' => "Saved draft for delivery_person_join.{$sectionKey}",
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
            $draft = $this->mergeWithDefaults($defaults, is_array($locked->draft_payload) ? $locked->draft_payload : []);

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
                'notes' => $notes ?: 'Published Delivery Person Join page content',
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
