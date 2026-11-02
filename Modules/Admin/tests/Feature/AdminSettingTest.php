<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Setting;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'admin']);
});

test('unauthenticated guest cannot view branding settings', function () {
    $response = $this->get(route('admin.settings.index'));

    $response->assertRedirect(route('admin.login'));
});

test('admin without manage-settings permission cannot view branding settings', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->get(route('admin.settings.index'));

    $response->assertStatus(403);
});

test('admin with manage-settings permission can view branding settings page', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo('manage-settings');

    $response = $this->actingAs($admin, 'admin')->get(route('admin.settings.index'));

    $response->assertStatus(200);
    $response->assertSee('Brand & Logo Settings', false);
    $response->assertSee('Primary Brand Logo');
    $response->assertSee('Dark Mode / Inverted Logo');
    $response->assertSee('Login Page Logo');
    $response->assertSee('App Icon / Mini Logo');
    $response->assertSee('Browser Favicon');
    $response->assertSee('Page Preloader / Reloader Animation');
    $response->assertSee('Footer Copyright Text');
});

test('admin can upload new brand logo, login logo, icon, favicon, and footer text via ajax', function () {
    Storage::fake('public');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo('manage-settings');

    $logo = UploadedFile::fake()->image('custom-logo.png', 400, 100);
    $darkLogo = UploadedFile::fake()->image('custom-dark-logo.png', 400, 100);
    $loginLogo = UploadedFile::fake()->image('custom-login-logo.png', 440, 120);
    $icon = UploadedFile::fake()->image('custom-icon.png', 128, 128);
    $favicon = UploadedFile::fake()->image('custom-favicon.png', 32, 32);

    $response = $this->actingAs($admin, 'admin')->postJson(route('admin.settings.update'), [
        'site_name' => 'Sparkle Laundry Hub',
        'footer_text' => 'All site reserved by Sparkle Laundry Hub.',
        'site_logo' => $logo,
        'site_logo_dark' => $darkLogo,
        'site_logo_login' => $loginLogo,
        'site_icon' => $icon,
        'site_favicon' => $favicon,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Branding assets and settings updated successfully.',
        'assets' => [
            'site_name' => 'Sparkle Laundry Hub',
            'footer_text' => 'All site reserved by Sparkle Laundry Hub.',
        ],
        'custom_status' => [
            'site_logo' => true,
            'site_logo_dark' => true,
            'site_logo_login' => true,
            'site_icon' => true,
            'site_favicon' => true,
        ],
    ]);

    expect(Setting::getSiteName())->toBe('Sparkle Laundry Hub');
    expect(Setting::getFooterText())->toBe('All site reserved by Sparkle Laundry Hub.');
    expect(Setting::hasCustom('site_logo'))->toBeTrue();
    expect(Setting::hasCustom('site_logo_dark'))->toBeTrue();
    expect(Setting::hasCustom('site_logo_login'))->toBeTrue();
    expect(Setting::hasCustom('site_icon'))->toBeTrue();
    expect(Setting::hasCustom('site_favicon'))->toBeTrue();

    Storage::disk('public')->assertExists(Setting::get('site_logo'));
    Storage::disk('public')->assertExists(Setting::get('site_logo_dark'));
    Storage::disk('public')->assertExists(Setting::get('site_logo_login'));
    Storage::disk('public')->assertExists(Setting::get('site_icon'));
    Storage::disk('public')->assertExists(Setting::get('site_favicon'));

    // Check that guest login page renders the custom login logo
    auth('admin')->logout();
    $loginPage = $this->get(route('admin.login'));
    $loginPage->assertStatus(200);
    $loginPage->assertSee(Setting::getLoginLogoUrl());
});

test('admin can reset custom branding asset back to default via ajax', function () {
    Storage::fake('public');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo('manage-settings');

    $logo = UploadedFile::fake()->image('temporary-logo.png', 200, 50);
    $path = $logo->store('branding', 'public');
    Setting::set('site_logo', $path);

    expect(Setting::hasCustom('site_logo'))->toBeTrue();

    $response = $this->actingAs($admin, 'admin')->deleteJson(route('admin.settings.destroy', 'site_logo'));

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'key' => 'site_logo',
        'is_custom' => false,
    ]);

    expect(Setting::hasCustom('site_logo'))->toBeFalse();
    Storage::disk('public')->assertMissing($path);
});

test('admin can reset custom login page logo back to default via ajax', function () {
    Storage::fake('public');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo('manage-settings');

    $logo = UploadedFile::fake()->image('temporary-login-logo.png', 240, 60);
    $path = $logo->store('branding', 'public');
    Setting::set('site_logo_login', $path);

    expect(Setting::hasCustom('site_logo_login'))->toBeTrue();

    $response = $this->actingAs($admin, 'admin')->deleteJson(route('admin.settings.destroy', 'site_logo_login'));

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'key' => 'site_logo_login',
        'is_custom' => false,
    ]);

    expect(Setting::hasCustom('site_logo_login'))->toBeFalse();
    Storage::disk('public')->assertMissing($path);
});

test('upload rejects oversized files exceeding maximum threshold', function () {
    Storage::fake('public');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo('manage-settings');

    // 4.5MB file exceeds limit
    $oversized = UploadedFile::fake()->create('huge-logo.png', 4500, 'image/png');

    $response = $this->actingAs($admin, 'admin')->postJson(route('admin.settings.update'), [
        'site_logo' => $oversized,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['site_logo']);
});

test('admin can upload and reset custom preloader reloader animation', function () {
    Storage::fake('public');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo('manage-settings');

    $preloaderGif = UploadedFile::fake()->create('washer-spin.gif', 500, 'image/gif');

    $response = $this->actingAs($admin, 'admin')->postJson(route('admin.settings.update'), [
        'site_preloader' => $preloaderGif,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    expect(Setting::hasPreloader())->toBeTrue();
    $path = Setting::get('site_preloader');
    Storage::disk('public')->assertExists($path);

    // Reset preloader
    $resetRes = $this->actingAs($admin, 'admin')->deleteJson(route('admin.settings.destroy', 'site_preloader'));
    $resetRes->assertStatus(200);
    $resetRes->assertJson([
        'success' => true,
        'key' => 'site_preloader',
        'is_custom' => false,
    ]);

    expect(Setting::hasPreloader())->toBeFalse();
    Storage::disk('public')->assertMissing($path);
});
