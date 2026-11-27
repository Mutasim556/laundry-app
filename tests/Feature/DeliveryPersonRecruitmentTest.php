<?php

use App\Models\DeliveryPersonApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\DeliveryPerson;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeRecruitmentAdmin(): Admin
{
    $admin = Admin::create([
        'name' => 'Recruitment Admin',
        'email' => 'recruit-admin@example.com',
        'password' => bcrypt('Password123!'),
    ]);

    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
    $perm = Permission::firstOrCreate(['name' => 'view-delivery-persons', 'guard_name' => 'admin']);
    $role->givePermissionTo($perm);
    $admin->assignRole($role);

    return $admin;
}

it('renders public recruitment and application status pages', function () {
    $this->get(route('join-delivery-person'))
        ->assertOk()
        ->assertSee('Deliver Freshness. Grow With Fresh Go.')
        ->assertSee('flatpickr')
        ->assertSee('dpCountryMenu')
        ->assertSee('dpFilePreviewModal')
        ->assertSee('data-toggle-pwd="dpPasswordConfirm"', false);

    $this->get(route('delivery-person.application-status'))
        ->assertOk()
        ->assertSee('Track Your Application');
});

it('submits a public application via AJAX, stores documents privately, and prevents duplicates', function () {
    Storage::fake('local');
    Notification::fake();

    $response = $this->postJson(route('join-delivery-person.apply'), [
        'full_name' => 'Alex Rider',
        'email' => 'alex.rider@example.com',
        'phone_country_code' => '+44',
        'phone' => '7700900111',
        'address_line_1' => '10 Downing Street',
        'city' => 'London',
        'postcode' => 'SW1A 2AA',
        'country_code' => 'GB',
        'delivery_method' => 'bicycle',
        'password' => 'StrongPass123',
        'password_confirmation' => 'StrongPass123',
        'consent_accepted' => '1',
        'identity_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
    ]);

    $response->assertCreated()->assertJson(['success' => true]);

    $app = DeliveryPersonApplication::where('email', 'alex.rider@example.com')->first();
    expect($app)->not->toBeNull()
        ->and($app->status)->toBe(DeliveryPersonApplication::STATUS_SUBMITTED)
        ->and(Hash::check('StrongPass123', $app->password))->toBeTrue()
        ->and(DeliveryPerson::where('email', 'alex.rider@example.com')->exists())->toBeFalse();

    $doc = $app->documents()->first();
    expect($doc)->not->toBeNull()
        ->and($doc->disk)->toBe('local');
    Storage::disk('local')->assertExists($doc->storage_path);

    // Duplicate application blocked without enumeration
    $this->postJson(route('join-delivery-person.apply'), [
        'full_name' => 'Alex Rider',
        'email' => 'alex.rider@example.com',
        'phone_country_code' => '+44',
        'phone' => '7700900111',
        'address_line_1' => '10 Downing Street',
        'city' => 'London',
        'postcode' => 'SW1A 2AA',
        'country_code' => 'GB',
        'delivery_method' => 'bicycle',
        'password' => 'StrongPass123',
        'password_confirmation' => 'StrongPass123',
        'consent_accepted' => '1',
    ])->assertStatus(422)->assertJson(['success' => false]);
});

it('rejects weak passwords and blocks unauthorized admin & document access', function () {
    $this->postJson(route('join-delivery-person.apply'), [
        'full_name' => 'Weak Pass',
        'email' => 'weak@example.com',
        'phone' => '7700900222',
        'address_line_1' => '12 High St',
        'city' => 'London',
        'postcode' => 'E1 1AA',
        'country_code' => 'GB',
        'delivery_method' => 'walking',
        'password' => '123',
        'password_confirmation' => '123',
        'consent_accepted' => '1',
    ])->assertStatus(422);

    $this->getJson(route('admin.delivery-person-applications.index'))->assertStatus(401);
});

it('completes full admin review, additional info resubmission, approval, login, and job eligibility', function () {
    Storage::fake('local');
    Notification::fake();

    $this->postJson(route('join-delivery-person.apply'), [
        'full_name' => 'Sam Courier',
        'email' => 'sam.dp@example.com',
        'phone_country_code' => '+44',
        'phone' => '7700900333',
        'address_line_1' => '22 Baker St',
        'city' => 'London',
        'postcode' => 'NW1 6XE',
        'country_code' => 'GB',
        'delivery_method' => 'bicycle',
        'password' => 'PortalPass123',
        'password_confirmation' => 'PortalPass123',
        'consent_accepted' => '1',
    ])->assertCreated();

    $app = DeliveryPersonApplication::where('email', 'sam.dp@example.com')->firstOrFail();
    $admin = makeRecruitmentAdmin();

    // Admin begins review
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.delivery-person-applications.begin-review', $app))
        ->assertOk();
    expect($app->fresh()->status)->toBe(DeliveryPersonApplication::STATUS_UNDER_REVIEW);

    // Admin requests additional info
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.delivery-person-applications.request-info', $app), [
            'additional_info_requested' => 'Please upload a clearer ID document.',
        ])->assertOk();
    expect($app->fresh()->status)->toBe(DeliveryPersonApplication::STATUS_ADDITIONAL_INFO_REQUIRED);

    // Applicant resubmits additional info
    $this->postJson(route('delivery-person.application-status.resubmit'), [
        'reference' => $app->reference,
        'email' => 'sam.dp@example.com',
        'password' => 'PortalPass123',
        'additional_notes' => 'Uploaded clearer ID.',
        'identity_document' => UploadedFile::fake()->create('id-clear.pdf', 120, 'application/pdf'),
    ])->assertOk();
    expect($app->fresh()->status)->toBe(DeliveryPersonApplication::STATUS_UNDER_REVIEW);

    // Admin approves & activates Delivery Person
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.delivery-person-applications.approve', $app))
        ->assertOk();

    $app->refresh();
    expect($app->status)->toBe(DeliveryPersonApplication::STATUS_APPROVED)
        ->and($app->approved_delivery_person_id)->not->toBeNull();

    // Double approval prevented
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.delivery-person-applications.approve', $app))
        ->assertStatus(422);

    // Approved Delivery Person is eligible for assignment & login
    $dp = DeliveryPerson::findOrFail($app->approved_delivery_person_id);
    expect($dp->isEligibleForAssignment())->toBeTrue()
        ->and($dp->isEligibleToLogin())->toBeTrue();

    // Suspended Delivery Person is NOT eligible for assignment
    $dp->update(['work_status' => 'suspended']);
    expect($dp->fresh()->isEligibleForAssignment())->toBeFalse();

    // Legacy Delivery Person without application remains eligible
    $legacy = DeliveryPerson::create([
        'code' => 'DP-LEGACY1',
        'full_name' => 'Legacy Driver',
        'email' => 'legacy@example.com',
        'phone_country_code' => '+44',
        'phone' => '7700900444',
        'phone_normalized' => '447700900444',
        'date_of_birth' => '1990-01-01',
        'address_line_1' => '1 Legacy Road',
        'city' => 'London',
        'country_code' => 'GB',
        'country_name' => 'United Kingdom',
        'residential_postcode' => 'E1 1AA',
        'work_status' => 'active',
        'portal_access_enabled' => true,
        'password' => 'LegacyPass123',
    ]);
    expect($legacy->isEligibleForAssignment())->toBeTrue();
});

it('validates future DOB, country-aware E.164 phone normalization, and rejects SVG or oversized uploads', function () {
    Storage::fake('local');
    Notification::fake();

    // Reject future DOB and invalid BD phone
    $this->postJson(route('join-delivery-person.apply'), [
        'full_name' => 'Test Applicant',
        'email' => 'future.dob@example.com',
        'phone_country_code' => '+880',
        'phone_iso_code' => 'BD',
        'phone' => '12345',
        'date_of_birth' => now()->addDays(5)->toDateString(),
        'address_line_1' => '12 Gulshan Ave',
        'city' => 'Dhaka',
        'postcode' => '1212',
        'country_code' => 'BD',
        'delivery_method' => 'bicycle',
        'password' => 'StrongPass123',
        'password_confirmation' => 'StrongPass123',
        'consent_accepted' => '1',
    ])->assertStatus(422);

    // Reject SVG upload
    $this->postJson(route('join-delivery-person.apply'), [
        'full_name' => 'Svg Applicant',
        'email' => 'svg@example.com',
        'phone_country_code' => '+880',
        'phone_iso_code' => 'BD',
        'phone' => '01711223344',
        'date_of_birth' => '1998-05-10',
        'address_line_1' => '12 Gulshan Ave',
        'city' => 'Dhaka',
        'postcode' => '1212',
        'country_code' => 'BD',
        'delivery_method' => 'bicycle',
        'password' => 'StrongPass123',
        'password_confirmation' => 'StrongPass123',
        'consent_accepted' => '1',
        'identity_document' => UploadedFile::fake()->create('bad.svg', 50, 'image/svg+xml'),
    ])->assertStatus(422);

    // Valid BD phone with leading 0 normalizes cleanly
    $this->postJson(route('join-delivery-person.apply'), [
        'full_name' => 'BD Applicant',
        'email' => 'bd.applicant@example.com',
        'phone_country_code' => '+880',
        'phone_iso_code' => 'BD',
        'phone' => '01711223344',
        'date_of_birth' => '1998-05-10',
        'address_line_1' => '12 Gulshan Ave',
        'city' => 'Dhaka',
        'postcode' => '1212',
        'country_code' => 'BD',
        'delivery_method' => 'bicycle',
        'password' => 'StrongPass123',
        'password_confirmation' => 'StrongPass123',
        'consent_accepted' => '1',
    ])->assertCreated();

    $bdApp = DeliveryPersonApplication::where('email', 'bd.applicant@example.com')->firstOrFail();
    expect($bdApp->phone_normalized)->toBe('8801711223344');
});

it('renders redesigned admin applications dashboard and streams secure inline document previews', function () {
    Storage::fake('local');
    Notification::fake();

    $this->postJson(route('join-delivery-person.apply'), [
        'full_name' => 'Preview Applicant',
        'email' => 'preview.doc@example.com',
        'phone_country_code' => '+44',
        'phone' => '7700900777',
        'address_line_1' => '15 Oxford St',
        'city' => 'London',
        'postcode' => 'W1D 2DZ',
        'country_code' => 'GB',
        'delivery_method' => 'bicycle',
        'password' => 'StrongPass123',
        'password_confirmation' => 'StrongPass123',
        'consent_accepted' => '1',
        'identity_document' => UploadedFile::fake()->image('id-card.png', 200, 200),
    ])->assertCreated();

    $app = DeliveryPersonApplication::where('email', 'preview.doc@example.com')->firstOrFail();
    $doc = $app->documents()->firstOrFail();

    // Unauthenticated preview blocked
    $this->get(route('admin.delivery-person-applications.documents.preview', [$app, $doc]))->assertRedirect();

    $admin = makeRecruitmentAdmin();

    // Admin dashboard & JSON details
    $this->actingAs($admin, 'admin')
        ->get(route('admin.delivery-person-applications.index'))
        ->assertOk()
        ->assertSee('Delivery Person Applications')
        ->assertSee('docPreviewModal');

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.delivery-person-applications.show', $app))
        ->assertOk()
        ->assertJsonPath('application.documents.0.is_image', true);

    // Authorized inline preview streams image with no-store cache headers
    $previewRes = $this->actingAs($admin, 'admin')
        ->get(route('admin.delivery-person-applications.documents.preview', [$app, $doc]));
    $previewRes->assertOk();
    expect($previewRes->headers->get('Content-Disposition'))->toContain('inline')
        ->and($previewRes->headers->get('Cache-Control'))->toContain('no-store');
});
