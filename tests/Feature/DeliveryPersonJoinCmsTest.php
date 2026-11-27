<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeWebsiteManagerAdmin(): Admin
{
    $admin = Admin::create([
        'name' => 'Website Manager Admin',
        'email' => 'wm-admin@example.com',
        'password' => bcrypt('Password123!'),
    ]);

    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
    $perm = Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'admin']);
    $role->givePermissionTo($perm);
    $admin->assignRole($role);

    return $admin;
}

it('opens the Admin Delivery Person Join Page editor and blocks unauthorized access', function () {
    $this->get(route('admin.website-manager.delivery-person-join.index'))->assertRedirect();

    $admin = makeWebsiteManagerAdmin();
    $this->actingAs($admin, 'admin')
        ->get(route('admin.website-manager.delivery-person-join.index'))
        ->assertOk()
        ->assertSee('Delivery Person Join Page')
        ->assertSee('Website Manager');
});

it('saves draft sections via AJAX, keeps drafts private until published, and updates public page on publish', function () {
    $admin = makeWebsiteManagerAdmin();

    // Save Hero draft
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.delivery-person-join.hero.save'), [
            'badge' => 'CUSTOM BADGE',
            'headline' => 'Custom Draft Hero Headline',
            'subtitle' => 'Custom draft subtitle',
            'bg_preset' => 'gold_soft',
            'alignment' => 'left',
            'primary_btn_label' => 'Apply Now',
            'primary_btn_visible' => true,
            'secondary_btn_label' => 'Driver Login',
            'secondary_btn_url' => '/delivery-person/login',
            'secondary_btn_visible' => true,
        ])->assertOk()->assertJson(['status' => 'ok']);

    // Save FAQs draft
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.delivery-person-join.faqs.save'), [
            'title' => 'Recruitment FAQs',
            'subtitle' => 'Updated subtitle',
            'items' => [
                ['question' => 'Is this dynamic FAQ?', 'answer' => 'Yes, managed from Website Manager.', 'visible' => true, 'order' => 1],
            ],
        ])->assertOk();

    // Public page must NOT show unpublished draft
    $this->get(route('join-delivery-person'))
        ->assertOk()
        ->assertDontSee('Custom Draft Hero Headline')
        ->assertDontSee('Is this dynamic FAQ?');

    // Admin preview DOES show draft
    $this->actingAs($admin, 'admin')
        ->get(route('join-delivery-person', ['preview' => 'draft']))
        ->assertOk()
        ->assertSee('Custom Draft Hero Headline')
        ->assertSee('Is this dynamic FAQ?');

    // Publish live
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.delivery-person-join.publish'))
        ->assertOk();

    // Public page now shows published content
    $this->get(route('join-delivery-person'))
        ->assertOk()
        ->assertSee('Custom Draft Hero Headline')
        ->assertSee('Is this dynamic FAQ?');
});

it('enforces applications_open = false on both public UI and server-side apply endpoint', function () {
    $admin = makeWebsiteManagerAdmin();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.delivery-person-join.general.save'), [
            'page_enabled' => true,
            'applications_open' => false,
            'nav_label' => 'Join as a Delivery Person',
            'footer_label' => 'Join as a Delivery Person',
            'closed_title' => 'Recruitment Paused',
            'closed_message' => 'Applications are paused this week.',
        ])->assertOk();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.delivery-person-join.publish'))
        ->assertOk();

    $this->get(route('join-delivery-person'))
        ->assertOk()
        ->assertSee('Recruitment Paused')
        ->assertSee('Applications are paused this week.');

    // Server-side enforcement blocks new submissions
    $this->postJson(route('join-delivery-person.apply'), [
        'full_name' => 'Blocked Applicant',
        'email' => 'blocked@example.com',
        'phone_country_code' => '+44',
        'phone' => '7700900555',
        'address_line_1' => '10 High St',
        'city' => 'London',
        'postcode' => 'SW1A 1AA',
        'country_code' => 'GB',
        'delivery_method' => 'bicycle',
        'password' => 'StrongPass123',
        'password_confirmation' => 'StrongPass123',
        'consent_accepted' => '1',
    ])->assertStatus(422);

    // Existing applicant tracking remains accessible
    $this->get(route('delivery-person.application-status'))->assertOk();
});

it('renders interactive registration demo modal and allows Admin CMS toggling', function () {
    $this->get(route('join-delivery-person'))
        ->assertOk()
        ->assertSee('View Registration Demo')
        ->assertSee('dpRegistrationDemoModal')
        ->assertSee('Sample Information — Demo Only')
        ->assertSee('Start My Application');

    $admin = makeWebsiteManagerAdmin();
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.delivery-person-join.demo.save'), [
            'enabled' => false,
            'button_label' => 'View Demo',
            'modal_title' => 'How to Apply',
            'final_title' => 'Ready!',
            'final_message' => 'Proceed to apply.',
            'cta_label' => 'Start Now',
        ])->assertOk();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.delivery-person-join.publish'))
        ->assertOk();

    $this->get(route('join-delivery-person'))
        ->assertOk()
        ->assertDontSee('id="openRegistrationDemoBtn"', false)
        ->assertDontSee('id="dpRegistrationDemoModal"', false);
});
