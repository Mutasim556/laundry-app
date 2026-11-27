<?php

use App\Models\HomepageContent;
use App\Models\HomepageContentVersion;
use App\Models\HomepageMedia;
use App\Services\HomepageContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Unit;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function makeCmsAdmin(bool $withPermission = true): Admin
{
    $admin = Admin::create([
        'name' => 'CMS Admin',
        'email' => 'cmsadmin' . random_int(100, 999) . '@example.com',
        'password' => bcrypt('Admin2026!'),
        'status' => true,
    ]);

    if ($withPermission) {
        Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'admin']);
        $admin->givePermissionTo('manage-settings');
    }

    return $admin;
}

test('admin authorization, draft preview vs publish, and hero management', function () {
    $unauthorizedAdmin = makeCmsAdmin(false);
    $admin = makeCmsAdmin(true);

    $this->actingAs($unauthorizedAdmin, 'admin')
        ->get(route('admin.website-manager.homepage.index'))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.website-manager.homepage.index'))
        ->assertOk()
        ->assertSee('Homepage Contents');

    // Initial public homepage shows default hero headline
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Fresh Clothes.');

    // Save draft hero change
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.homepage.hero.save'), [
            'badge' => 'EXPRESS LAUNDRY',
            'headline' => 'Sparkling Clean Garments.',
            'show_heart_icon' => 1,
            'subtitle' => 'Dynamic hero subtitle from CMS.',
            'image_url' => 'images/freshgo-full-poster.jpg',
            'image_alt' => 'FreshGo Hero',
            'primary_btn_label' => 'Book Now',
            'primary_btn_url' => '/booking/services',
            'primary_btn_visible' => 1,
            'secondary_btn_label' => 'See Prices',
            'secondary_btn_url' => '/services',
            'secondary_btn_visible' => 1,
            'bg_preset' => 'soft_yellow',
        ])->assertOk();

    // Public homepage MUST remain unchanged until published
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Fresh Clothes.')
        ->assertDontSee('Sparkling Clean Garments.');

    // Admin preview shows draft
    $this->actingAs($admin, 'admin')
        ->get(route('home', ['preview' => 'draft']))
        ->assertOk()
        ->assertSee('Draft Preview Mode')
        ->assertSee('Sparkling Clean Garments.');

    // Publish draft -> public homepage updates immediately
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.homepage.publish'))
        ->assertOk();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Sparkling Clean Garments.')
        ->assertSee('EXPRESS LAUNDRY');
});

test('section visibility, reordering, search settings, categories, services live pricing, seo, media protection, and url security', function () {
    Storage::fake('public');
    $admin = makeCmsAdmin(true);

    $unit = Unit::create(['name' => 'Item', 'slug' => 'item', 'is_active' => true]);
    $cat = ItemCategory::create(['name' => 'Dry Cleaning', 'slug' => 'dry-cleaning', 'is_active' => true]);
    $inactiveCat = ItemCategory::create(['name' => 'Archived Cat', 'slug' => 'archived-cat', 'is_active' => false]);
    $item = Item::create(['item_category_id' => $cat->id, 'name' => 'Wool Coat', 'slug' => 'wool-coat', 'is_active' => true]);
    $activeSvc = Service::create([
        'category_id' => $cat->id,
        'item_id' => $item->id,
        'unit_id' => $unit->id,
        'name' => 'Suit Dry Clean',
        'slug' => 'suit-dry-clean',
        'pricing_mode' => 'fixed',
        'price' => 18.50,
        'price_minor' => 1850,
        'is_active' => true,
    ]);
    Service::create([
        'category_id' => $cat->id,
        'unit_id' => $unit->id,
        'name' => 'Hidden Inactive Service',
        'slug' => 'hidden-inactive',
        'pricing_mode' => 'fixed',
        'price' => 99.00,
        'price_minor' => 9900,
        'is_active' => false,
    ]);

    // Unsafe javascript: URL rejected
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.homepage.hero.save'), [
            'headline' => 'Test',
            'primary_btn_url' => 'javascript:alert(1)',
            'bg_preset' => 'cream',
        ])->assertUnprocessable();

    // Upload media & attach to promo banner
    $uploadRes = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.homepage.media.upload'), [
            'image' => UploadedFile::fake()->image('banner.png', 600, 400),
            'alt_text' => 'Custom Banner',
        ])->assertCreated();

    $mediaId = $uploadRes->json('media.id');
    $mediaUrl = $uploadRes->json('media.url');

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.homepage.promo-banner.save'), [
            'badge' => 'EXPRESS',
            'headline' => 'Custom Banner Headline',
            'description' => 'Fast delivery',
            'button_label' => 'Book',
            'button_url' => '/booking/services',
            'button_visible' => 1,
            'image_url' => $mediaUrl,
            'bg_preset' => 'gold_gradient',
        ])->assertOk();

    // Update search settings (disable categories in search)
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.homepage.search.save'), [
            'visible' => 1,
            'placeholder' => 'Find a laundry service...',
            'button_text' => 'Go',
            'show_icon' => 1,
            'show_categories' => 0,
            'show_services' => 1,
            'show_items' => 1,
            'max_suggestions' => 4,
        ])->assertOk();

    // Update SEO
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.homepage.seo.save'), [
            'meta_title' => 'FreshGo Custom SEO Title',
            'meta_description' => 'Custom SEO meta description',
            'robots' => 'index, follow',
        ])->assertOk();

    // Reorder sections: put services before categories, hide promo_banner
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.homepage.sections.save'), [
            'sections' => [
                ['key' => 'hero', 'label' => 'Hero', 'visible' => 1, 'order' => 1],
                ['key' => 'services', 'label' => 'Services', 'visible' => 1, 'order' => 2],
                ['key' => 'categories', 'label' => 'Categories', 'visible' => 1, 'order' => 3],
                ['key' => 'promo_banner', 'label' => 'Promo', 'visible' => 0, 'order' => 4],
            ],
        ])->assertOk();

    // Publish all
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.homepage.publish'))
        ->assertOk();

    // Verify public homepage renders SEO, placeholder, live service price, omits hidden promo_banner & inactive records, and renders services before categories
    $homeHtml = $this->get(route('home'))->assertOk()->getContent();
    expect($homeHtml)->toContain('FreshGo Custom SEO Title');
    expect($homeHtml)->toContain('Find a laundry service...');
    expect($homeHtml)->toContain('Suit Dry Clean');
    expect($homeHtml)->not->toContain('Hidden Inactive Service');
    expect($homeHtml)->not->toContain('Archived Cat');
    expect($homeHtml)->not->toContain('Custom Banner Headline');
    expect(strpos($homeHtml, 'id="fgServiceSection"'))->toBeLessThan(strpos($homeHtml, 'id="fgCategorySection"'));

    // Verify media used in published payload cannot be deleted
    $this->actingAs($admin, 'admin')
        ->deleteJson(route('admin.website-manager.homepage.media.delete', $mediaId))
        ->assertUnprocessable();

    // Verify AJAX search respects show_categories = false and only returns active services
    $this->getJson(route('home.search', ['q' => 'Dry']))
        ->assertOk()
        ->assertJsonCount(0, 'groups.categories')
        ->assertJsonPath('groups.services.0.name', 'Suit Dry Clean');

    // Verify version restore to draft
    $firstVer = HomepageContentVersion::orderBy('version_number')->firstOrFail();
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.homepage.versions.restore', $firstVer->id))
        ->assertOk();
});
