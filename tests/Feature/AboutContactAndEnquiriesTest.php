<?php

use App\Mail\ContactEnquiryNotificationMail;
use App\Models\ContactEnquiry;
use App\Models\HomepageContentVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function makeAboutContactCmsAdmin(bool $withPerm = true): Admin
{
    $admin = Admin::create([
        'name' => 'About Contact CMS Admin',
        'email' => 'accms' . random_int(100, 9999) . '@example.com',
        'password' => bcrypt('Admin2026!'),
        'status' => true,
    ]);

    if ($withPerm) {
        Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'admin']);
        $admin->givePermissionTo('manage-settings');
    }

    return $admin;
}

test('public about and contact pages render with nav and footer links', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('About Us')
        ->assertSee('Contact Us')
        ->assertSee(route('about'))
        ->assertSee(route('contact'));

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Send Message')
        ->assertSee(route('about'))
        ->assertSee(route('contact'));
});

test('admin authorization is enforced on about, contact, and enquiries website manager routes', function () {
    $unauth = makeAboutContactCmsAdmin(false);
    $admin = makeAboutContactCmsAdmin(true);

    $this->actingAs($unauth, 'admin')->get(route('admin.website-manager.about.index'))->assertForbidden();
    $this->actingAs($unauth, 'admin')->get(route('admin.website-manager.contact.index'))->assertForbidden();
    $this->actingAs($unauth, 'admin')->get(route('admin.website-manager.enquiries.index'))->assertForbidden();

    $this->actingAs($admin, 'admin')->get(route('admin.website-manager.about.index'))->assertOk()->assertSee('About Page Contents');
    $this->actingAs($admin, 'admin')->get(route('admin.website-manager.contact.index'))->assertOk()->assertSee('Contact Page Contents');
    $this->actingAs($admin, 'admin')->get(route('admin.website-manager.enquiries.index'))->assertOk()->assertSee('Contact Enquiries');
});

test('about page supports draft preview, publish, section visibility, and url sanitization', function () {
    $admin = makeAboutContactCmsAdmin(true);

    // Reject javascript: URL
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.about.hero.save'), [
            'badge' => 'Our Story',
            'headline' => 'Draft About Heading',
            'description' => 'Draft About Subtitle',
            'bg_preset' => 'gold_soft',
            'primary_btn_label' => 'Book Pickup',
            'primary_btn_url' => 'javascript:alert(1)',
            'primary_btn_visible' => true,
        ])
        ->assertStatus(422);

    // Save valid hero draft
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.about.hero.save'), [
            'badge' => 'Our Story',
            'headline' => 'Draft About Heading',
            'description' => 'Draft About Subtitle',
            'bg_preset' => 'gold_soft',
            'primary_btn_label' => 'Book Pickup',
            'primary_btn_url' => '/services',
            'primary_btn_visible' => true,
            'secondary_btn_label' => 'Contact Us',
            'secondary_btn_url' => '/contact',
            'secondary_btn_visible' => true,
        ])
        ->assertOk()
        ->assertJsonPath('status', 'ok');

    // Public /about still shows published content, not draft
    $this->get(route('about'))
        ->assertOk()
        ->assertDontSee('Draft About Heading');

    // Admin draft preview shows draft heading
    $this->actingAs($admin, 'admin')
        ->get(route('about', ['preview' => 'draft']))
        ->assertOk()
        ->assertSee('Draft About Heading');

    // Publish draft
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.about.publish'), [
            'notes' => 'Publish about v1',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'ok');

    expect(HomepageContentVersion::where('notes', 'Publish about v1')->exists())->toBeTrue();

    $this->get(route('about'))
        ->assertOk()
        ->assertSee('Draft About Heading');

    // Save story heading then hide story section via sections.save
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.about.story.save'), [
            'heading' => 'Hidden Story Heading',
            'body' => 'Paragraph one',
        ])
        ->assertOk();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.about.sections.save'), [
            'sections' => [
                ['key' => 'hero', 'label' => 'Hero Banner', 'visible' => true, 'order' => 1],
                ['key' => 'story', 'label' => 'Our Story', 'visible' => false, 'order' => 2],
                ['key' => 'mission_vision', 'label' => 'Mission & Vision', 'visible' => true, 'order' => 3],
                ['key' => 'benefits', 'label' => 'Why Choose Us', 'visible' => true, 'order' => 4],
                ['key' => 'how_it_works', 'label' => 'How It Works', 'visible' => true, 'order' => 5],
                ['key' => 'stats', 'label' => 'Key Highlights', 'visible' => false, 'order' => 6],
                ['key' => 'cta', 'label' => 'Call To Action', 'visible' => true, 'order' => 7],
            ],
        ])
        ->assertOk();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.about.publish'), ['note' => 'Hide story'])
        ->assertOk();

    $this->get(route('about'))
        ->assertOk()
        ->assertDontSee('Hidden Story Heading');
});

test('contact page supports draft preview, publish, business hours, and url sanitization', function () {
    $admin = makeAboutContactCmsAdmin(true);

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.contact.hours.save'), [
            'hours' => [
                ['day' => 'Monday', 'is_open' => true, 'open_time' => '08:00 AM', 'close_time' => '08:00 PM', 'note' => ''],
                ['day' => 'Tuesday', 'is_open' => true, 'open_time' => '08:00 AM', 'close_time' => '08:00 PM', 'note' => ''],
                ['day' => 'Wednesday', 'is_open' => true, 'open_time' => '08:00 AM', 'close_time' => '08:00 PM', 'note' => ''],
                ['day' => 'Thursday', 'is_open' => true, 'open_time' => '08:00 AM', 'close_time' => '08:00 PM', 'note' => ''],
                ['day' => 'Friday', 'is_open' => true, 'open_time' => '08:00 AM', 'close_time' => '08:00 PM', 'note' => ''],
                ['day' => 'Saturday', 'is_open' => true, 'open_time' => '09:00 AM', 'close_time' => '05:00 PM', 'note' => ''],
                ['day' => 'Sunday', 'is_open' => false, 'open_time' => '', 'close_time' => '', 'note' => 'Closed'],
            ],
        ])
        ->assertOk();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.contact.info.save'), [
            'brand_name' => 'FreshGo Laundry',
            'support_email' => 'care@freshgo.test',
            'phone_country_code' => '+44',
            'phone_number' => '20 7946 0000',
            'availability_text' => 'Within 2 hours',
        ])
        ->assertOk();

    // Reject javascript: map_url
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.contact.map-social.save'), [
            'map' => [
                'title' => 'Our Office',
                'public_address' => '221B Baker St',
                'map_url' => 'javascript:evil()',
            ],
            'social' => [],
        ])
        ->assertStatus(422);

    $this->get(route('contact'))
        ->assertOk()
        ->assertDontSee('care@freshgo.test');

    $this->actingAs($admin, 'admin')
        ->get(route('contact', ['preview' => 'draft']))
        ->assertOk()
        ->assertSee('care@freshgo.test')
        ->assertSee('08:00 AM');

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.contact.publish'), ['note' => 'Publish contact v1'])
        ->assertOk();

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('care@freshgo.test')
        ->assertSee('08:00 AM');
});

test('contact enquiry ajax submission validates, respects honeypot and idempotency, mails, and admin updates status', function () {
    Mail::fake();
    $admin = makeAboutContactCmsAdmin(true);

    // Invalid payload returns 422
    $this->postJson(route('contact.enquiry.store'), [
        'name' => '',
        'email' => 'not-an-email',
        'subject' => '',
        'message' => '',
    ])->assertStatus(422);

    // Honeypot filled returns ok without saving
    $this->postJson(route('contact.enquiry.store'), [
        'name' => 'Bot User',
        'email' => 'bot@example.com',
        'subject' => 'Spam',
        'message' => 'Spam message body here',
        'website_url' => 'http://spam.example.com',
    ])->assertOk();

    expect(ContactEnquiry::count())->toBe(0);

    // Valid submission saves and sends email
    $payload = [
        'name' => 'Jane Customer',
        'email' => 'jane@example.com',
        'phone' => '+44 7700 900123',
        'subject' => 'Pickup schedule question',
        'message' => 'Can I reschedule my laundry pickup to tomorrow morning?',
        'booking_reference' => 'FG-10042',
        'idempotency_key' => 'idem-key-12345',
    ];

    $res = $this->postJson(route('contact.enquiry.store'), $payload)
        ->assertStatus(201)
        ->assertJsonPath('status', 'ok');

    $reference = $res->json('reference');
    expect($reference)->toStartWith('ENQ-');
    expect(ContactEnquiry::count())->toBe(1);

    Mail::assertSent(ContactEnquiryNotificationMail::class, 1);

    // Duplicate idempotency key does not create a second enquiry
    $this->postJson(route('contact.enquiry.store'), $payload)
        ->assertOk()
        ->assertJsonPath('reference', $reference);

    expect(ContactEnquiry::count())->toBe(1);

    $enquiry = ContactEnquiry::first();
    expect($enquiry->status)->toBe(ContactEnquiry::STATUS_NEW);

    // Admin views enquiry -> transitions from new to read
    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.website-manager.enquiries.show', $enquiry))
        ->assertOk()
        ->assertJsonPath('enquiry.status', ContactEnquiry::STATUS_READ)
        ->assertJsonPath('enquiry.reference', $reference);

    // Admin updates status to resolved with internal notes
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.enquiries.status', $enquiry), [
            'status' => ContactEnquiry::STATUS_RESOLVED,
            'internal_notes' => 'Rescheduled pickup with customer via phone.',
        ])
        ->assertOk()
        ->assertJsonPath('enquiry_status', ContactEnquiry::STATUS_RESOLVED);

    $enquiry->refresh();
    expect($enquiry->status)->toBe(ContactEnquiry::STATUS_RESOLVED);
    expect($enquiry->internal_notes)->toBe('Rescheduled pickup with customer via phone.');
    expect($enquiry->resolved_at)->not->toBeNull();
});
