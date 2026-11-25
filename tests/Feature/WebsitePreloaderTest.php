<?php

use App\Models\HomepageContentVersion;
use App\Services\PreloaderSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Models\Admin;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function makePreloaderAdmin(bool $withPerm = true): Admin
{
    $admin = Admin::create([
        'name' => 'Preloader Admin',
        'email' => 'pladmin' . random_int(100, 9999) . '@example.com',
        'password' => bcrypt('Admin2026!'),
        'status' => true,
    ]);

    if ($withPerm) {
        Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'admin']);
        $admin->givePermissionTo('manage-settings');
    }

    return $admin;
}

test('admin authorization is enforced on preloader settings routes', function () {
    $unauth = makePreloaderAdmin(false);
    $admin = makePreloaderAdmin(true);

    $this->actingAs($unauth, 'admin')->get(route('admin.website-manager.preloader.index'))->assertForbidden();
    $this->actingAs($unauth, 'admin')->postJson(route('admin.website-manager.preloader.save'), [])->assertForbidden();
    $this->actingAs($unauth, 'admin')->postJson(route('admin.website-manager.preloader.publish'), [])->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.website-manager.preloader.index'))
        ->assertOk()
        ->assertSee('Preloader Settings')
        ->assertSee('Enable Website Preloader')
        ->assertDontSee('id="fgPagePreloader"', false);
});

test('preloader renders on public, booking, and auth pages when enabled and hides when disabled', function () {
    $admin = makePreloaderAdmin(true);

    // Default ON renders on public pages and login
    $this->get(route('home'))->assertOk()->assertSee('id="fgPagePreloader"', false)->assertSee('Preparing your fresh experience...');
    $this->get(route('services.index'))->assertOk()->assertSee('id="fgPagePreloader"', false);
    $this->get(route('about'))->assertOk()->assertSee('id="fgPagePreloader"', false);
    $this->get(route('contact'))->assertOk()->assertSee('id="fgPagePreloader"', false);
    $this->get(route('login'))->assertOk()->assertSee('id="fgPagePreloader"', false);

    // Turn OFF in draft and publish
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.preloader.save'), [
            'enabled' => false,
            'style' => 'brand_spinner',
            'loading_text' => 'Preparing your fresh experience...',
            'show_logo' => true,
            'show_text' => true,
            'background_color' => '#FFF8E5',
            'accent_color' => '#FFC629',
            'text_color' => '#171717',
            'animation_speed' => 'normal',
            'fade_duration_ms' => 250,
            'minimum_duration_ms' => 0,
            'maximum_timeout_ms' => 4000,
            'logo_source' => 'site_logo',
            'logo_size' => 'md',
            'enable_public' => true,
            'enable_booking' => true,
            'enable_orders' => true,
            'enable_auth' => true,
            'respect_reduced_motion' => true,
        ])
        ->assertOk();

    // Before publish, public still sees published ON
    $this->get(route('home'))->assertOk()->assertSee('id="fgPagePreloader"', false);

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.preloader.publish'))
        ->assertOk();

    expect(HomepageContentVersion::where('action', 'published')->exists())->toBeTrue();

    // After publish, preloader is completely omitted
    $this->get(route('home'))->assertOk()->assertDontSee('id="fgPagePreloader"', false);
    $this->get(route('contact'))->assertOk()->assertDontSee('id="fgPagePreloader"', false);
});

test('preloader styles, colors, text, custom logo, and page applicability work after publish', function () {
    $admin = makePreloaderAdmin(true);

    $base = [
        'enabled' => true,
        'loading_text' => 'Sorting your fresh laundry...',
        'show_logo' => true,
        'show_text' => true,
        'background_color' => '#FFFFFF',
        'accent_color' => '#3B82F6',
        'text_color' => '#171717',
        'animation_speed' => 'fast',
        'fade_duration_ms' => 220,
        'minimum_duration_ms' => 0,
        'maximum_timeout_ms' => 3500,
        'logo_source' => 'custom_media',
        'custom_logo_url' => '/storage/homepage-media/test-logo.png',
        'logo_size' => 'lg',
        'enable_public' => true,
        'enable_booking' => false,
        'enable_orders' => true,
        'enable_auth' => false,
        'respect_reduced_motion' => true,
    ];

    // Style 2: laundry_animation
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.preloader.save'), array_merge($base, ['style' => 'laundry_animation']))
        ->assertOk();
    $this->actingAs($admin, 'admin')->postJson(route('admin.website-manager.preloader.publish'))->assertOk();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('fg-pl-garment')
        ->assertSee('Sorting your fresh laundry...')
        ->assertSee('test-logo.png');

    // Auth page excluded when enable_auth = false
    $this->get(route('login'))->assertOk()->assertDontSee('id="fgPagePreloader"', false);

    // Style 3: progress_indicator (indeterminate bar, no numeric percentage)
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.preloader.save'), array_merge($base, ['style' => 'progress_indicator']))
        ->assertOk();
    $this->actingAs($admin, 'admin')->postJson(route('admin.website-manager.preloader.publish'))->assertOk();

    $html = $this->get(route('home'))
        ->assertOk()
        ->assertSee('fg-pl-progress-bar')
        ->getContent();
    preg_match('/<div id="fgPagePreloader"[^>]*>.*?<script>/s', $html, $preloaderMatch);
    expect($preloaderMatch[0] ?? '')->not->toContain('%');
});

test('preloader validates input and safely falls back on malformed stored values', function () {
    $admin = makePreloaderAdmin(true);

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.preloader.save'), [
            'style' => 'invalid_style',
            'background_color' => 'not-a-hex',
            'accent_color' => '#FFC629',
            'text_color' => '#171717',
            'animation_speed' => 'normal',
            'fade_duration_ms' => 250,
            'minimum_duration_ms' => 0,
            'maximum_timeout_ms' => 4000,
            'logo_source' => 'site_logo',
            'logo_size' => 'md',
        ])
        ->assertStatus(422);

    $svc = app(PreloaderSettingService::class);
    $sanitized = $svc->sanitizePayload([
        'style' => 'bad',
        'background_color' => 'javascript:alert(1)',
        'fade_duration_ms' => 99999,
        'maximum_timeout_ms' => 10,
    ]);

    expect($sanitized['style'])->toBe('brand_spinner');
    expect($sanitized['background_color'])->toBe('#FFF8E5');
    expect($sanitized['fade_duration_ms'])->toBe(800);
    expect($sanitized['maximum_timeout_ms'])->toBe(1500);
});
