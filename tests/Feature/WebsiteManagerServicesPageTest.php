<?php

use App\Models\HomepageContentVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Unit;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function makeSvcCmsAdmin(bool $withPerm = true): Admin
{
    $admin = Admin::create([
        'name' => 'Services CMS Admin',
        'email' => 'svccms' . random_int(100, 999) . '@example.com',
        'password' => bcrypt('Admin2026!'),
        'status' => true,
    ]);

    if ($withPerm) {
        Permission::firstOrCreate(['name' => 'manage-settings', 'guard_name' => 'admin']);
        $admin->givePermissionTo('manage-settings');
    }

    return $admin;
}

test('admin authorization, services page banner, draft preview vs publish, and live catalog pricing', function () {
    $unauth = makeSvcCmsAdmin(false);
    $admin = makeSvcCmsAdmin(true);

    $unit = Unit::create(['name' => 'Item', 'slug' => 'item', 'is_active' => true]);
    $cat = ItemCategory::create(['name' => 'Wash & Fold', 'slug' => 'wash-fold', 'is_active' => true]);
    $item = Item::create(['item_category_id' => $cat->id, 'name' => 'Shirt', 'slug' => 'shirt', 'is_active' => true]);
    $svc = Service::create([
        'category_id' => $cat->id,
        'item_category_id' => $cat->id,
        'item_id' => $item->id,
        'unit_id' => $unit->id,
        'name' => 'Crisp Shirt Wash',
        'slug' => 'crisp-shirt-wash',
        'pricing_mode' => 'fixed',
        'price' => 4.50,
        'price_minor' => 450,
        'is_active' => true,
    ]);

    $this->actingAs($unauth, 'admin')
        ->get(route('admin.website-manager.services.index'))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.website-manager.services.index'))
        ->assertOk()
        ->assertSee('Services Page Contents');

    // Public /services renders default banner & live service
    $this->get(route('services.index', ['category' => $cat->slug]))
        ->assertOk()
        ->assertSee('Professional Care for Every Garment')
        ->assertSee('Crisp Shirt Wash');

    // Save draft banner change
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.services.banner.save'), [
            'badge' => 'CUSTOM SERVICES',
            'headline' => 'Expert Garment Care At Your Door',
            'description' => 'Tailored cleaning for every fabric.',
            'image_url' => 'images/freshgo-full-poster.jpg',
            'bg_preset' => 'cream',
            'primary_btn_label' => 'Book Pickup',
            'primary_btn_url' => '/booking/services',
            'primary_btn_visible' => 1,
            'secondary_btn_label' => 'Browse',
            'secondary_btn_url' => '#catalog-results',
            'secondary_btn_visible' => 1,
        ])->assertOk();

    // Public /services remains unchanged until published
    $this->get(route('services.index', ['category' => $cat->slug]))
        ->assertOk()
        ->assertSee('Professional Care for Every Garment')
        ->assertDontSee('Expert Garment Care At Your Door');

    // Admin preview shows draft
    $this->actingAs($admin, 'admin')
        ->get(route('services.index', ['category' => $cat->slug, 'preview' => 'draft']))
        ->assertOk()
        ->assertSee('Draft Preview Mode')
        ->assertSee('Expert Garment Care At Your Door');

    // Publish -> public updates
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.services.publish'))
        ->assertOk();

    $this->get(route('services.index', ['category' => $cat->slug]))
        ->assertOk()
        ->assertSee('Expert Garment Care At Your Door')
        ->assertSee('CUSTOM SERVICES');
});

test('services page section visibility, reordering, featured services, inactive hiding, seo, and url security', function () {
    $admin = makeSvcCmsAdmin(true);

    $unit = Unit::create(['name' => 'Kg', 'slug' => 'kg', 'is_active' => true]);
    $cat = ItemCategory::create(['name' => 'Dry Cleaning', 'slug' => 'dry-cleaning', 'is_active' => true]);
    $activeSvc = Service::create([
        'category_id' => $cat->id,
        'item_category_id' => $cat->id,
        'unit_id' => $unit->id,
        'name' => 'Luxury Coat Clean',
        'slug' => 'luxury-coat-clean',
        'pricing_mode' => 'fixed',
        'price' => 25.00,
        'price_minor' => 2500,
        'is_active' => true,
    ]);
    $inactiveSvc = Service::create([
        'category_id' => $cat->id,
        'item_category_id' => $cat->id,
        'unit_id' => $unit->id,
        'name' => 'Disabled Service',
        'slug' => 'disabled-service',
        'pricing_mode' => 'fixed',
        'price' => 50.00,
        'price_minor' => 5000,
        'is_active' => false,
    ]);

    // Unsafe URL rejected
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.services.banner.save'), [
            'headline' => 'Banner',
            'primary_btn_url' => 'javascript:alert(1)',
            'bg_preset' => 'cream',
        ])->assertUnprocessable();

    // Configure featured services (include active + attempt inactive, inactive must never appear publicly)
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.services.featured.save'), [
            'title' => 'Top Picks',
            'subtitle' => 'Customer favorites',
            'interval_ms' => 4000,
            'desktop_per_view' => 4,
            'autoplay' => 1,
            'show_arrows' => 1,
            'pause_on_hover' => 1,
            'loop' => 1,
            'service_ids' => [$activeSvc->id, $inactiveSvc->id],
        ])->assertOk();

    // Update search & SEO
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.services.search.save'), [
            'visible' => 1,
            'placeholder' => 'Search FreshGo catalog...',
            'show_sort' => 1,
            'max_suggestions' => 5,
        ])->assertOk();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.services.seo.save'), [
            'meta_title' => 'Services SEO Title',
            'meta_description' => 'Services SEO Description',
            'robots' => 'index, follow',
        ])->assertOk();

    // Enable featured before categories, hide CTA
    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.services.sections.save'), [
            'sections' => [
                ['key' => 'banner', 'label' => 'Banner', 'visible' => 1, 'order' => 1],
                ['key' => 'search', 'label' => 'Search', 'visible' => 1, 'order' => 2],
                ['key' => 'featured', 'label' => 'Featured', 'visible' => 1, 'order' => 3],
                ['key' => 'categories', 'label' => 'Categories', 'visible' => 1, 'order' => 4],
                ['key' => 'catalog', 'label' => 'Catalog', 'visible' => 1, 'order' => 5],
                ['key' => 'cta', 'label' => 'CTA', 'visible' => 0, 'order' => 6],
            ],
        ])->assertOk();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.website-manager.services.publish'))
        ->assertOk();

    $html = $this->get(route('services.index', ['category' => $cat->slug]))->assertOk()->getContent();
    expect($html)->toContain('Services SEO Title');
    expect($html)->toContain('Search FreshGo catalog...');
    expect($html)->toContain('Top Picks');
    expect($html)->toContain('Luxury Coat Clean');
    expect($html)->not->toContain('Disabled Service');
    expect($html)->not->toContain('id="fgServicesCta"');
    expect(strpos($html, 'id="fgServicesFeatured"'))->toBeLessThan(strpos($html, 'id="fgServicesCategories"'));

    // Homepage remains unaffected
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Fresh Clothes.');
});
