<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Unit;

uses(RefreshDatabase::class);

function seedHomepageCatalog(): array
{
    $unit = Unit::create(['name' => 'Per Kg', 'slug' => 'per-kg', 'is_active' => true]);

    $cat1 = ItemCategory::create(['name' => 'Wash & Fold', 'slug' => 'wash-fold', 'short_description' => 'Everyday laundry', 'is_active' => true]);
    $cat2 = ItemCategory::create(['name' => 'Dry Cleaning', 'slug' => 'dry-cleaning', 'short_description' => 'Delicate suits', 'is_active' => true]);
    $cat3 = ItemCategory::create(['name' => 'Ironing Only', 'slug' => 'ironing-only', 'short_description' => 'Crisp steam press', 'is_active' => true]);
    $cat4 = ItemCategory::create(['name' => 'Wash & Iron', 'slug' => 'wash-iron', 'short_description' => 'Shirts and trousers', 'is_active' => true]);
    $cat5 = ItemCategory::create(['name' => 'Bedding & Duvets', 'slug' => 'bedding-duvets', 'short_description' => 'Bulky care', 'is_active' => true]);
    $inactiveCat = ItemCategory::create(['name' => 'Hidden Category', 'slug' => 'hidden-cat', 'is_active' => false]);

    $shirtItem = Item::create([
        'item_category_id' => $cat4->id,
        'name' => 'Formal Shirt',
        'slug' => 'formal-shirt',
        'is_active' => true,
    ]);
    $inactiveItem = Item::create([
        'item_category_id' => $cat4->id,
        'name' => 'Hidden Shirt',
        'slug' => 'hidden-shirt',
        'is_active' => false,
    ]);

    $svc1 = Service::create([
        'unit_id' => $unit->id,
        'item_category_id' => $cat1->id,
        'name' => 'Everyday Wash & Fold',
        'slug' => 'everyday-wash-fold',
        'price' => 14.50,
        'is_active' => true,
    ]);
    $svc2 = Service::create([
        'unit_id' => $unit->id,
        'item_category_id' => $cat4->id,
        'item_id' => $shirtItem->id,
        'name' => 'Shirt Wash & Iron',
        'slug' => 'shirt-wash-iron',
        'price' => 9.00,
        'is_active' => true,
    ]);
    $inactiveSvc = Service::create([
        'unit_id' => $unit->id,
        'item_category_id' => $cat1->id,
        'name' => 'Hidden Service',
        'slug' => 'hidden-service',
        'price' => 99.00,
        'is_active' => false,
    ]);

    return compact('cat1', 'cat2', 'cat3', 'cat4', 'cat5', 'inactiveCat', 'shirtItem', 'inactiveItem', 'svc1', 'svc2', 'inactiveSvc');
}

it('renders the redesigned Fresh Go homepage without coupon ads and with dynamic database categories and services', function () {
    seedHomepageCatalog();

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Premium Laundry Care')
        ->assertSee('Fresh Clothes.')
        ->assertSee('Happier You.')
        ->assertDontSee('Order Delivered')
        ->assertSee('We Collect. We Clean. We Deliver.')
        ->assertSee('Book Now')
        ->assertSee('Book a Collection')
        ->assertSee('Explore Services')
        ->assertSee('Our Laundry Categories')
        ->assertSee('Our Services')
        ->assertSee('Wash &amp; Fold', false)
        ->assertSee('Bedding &amp; Duvets', false)
        ->assertSee('Everyday Wash &amp; Fold', false)
        ->assertSee('Shirt Wash &amp; Iron', false)
        ->assertDontSee('FRESH20')
        ->assertDontSee('Get 20% OFF')
        ->assertDontSee('Hidden Category')
        ->assertDontSee('Hidden Service')
        ->assertDontSee('$12.00');
});

it('searches active categories, services, and laundry items via AJAX endpoint and excludes inactive records', function () {
    seedHomepageCatalog();

    // Validation requires min 2 chars
    $this->getJson(route('home.search', ['q' => 'a']))
        ->assertStatus(422);

    // Search "Shirt" matches Formal Shirt item & Shirt Wash & Iron service, excludes Hidden Shirt
    $res = $this->getJson(route('home.search', ['q' => 'Shirt']))
        ->assertOk()
        ->assertJsonPath('success', true);

    $data = $res->json();
    expect($data['total'])->toBeGreaterThanOrEqual(2)
        ->and(collect($data['groups']['items'])->pluck('name')->all())->toContain('Formal Shirt')
        ->and(collect($data['groups']['items'])->pluck('name')->all())->not->toContain('Hidden Shirt')
        ->and(collect($data['groups']['services'])->pluck('name')->all())->toContain('Shirt Wash & Iron');

    // Empty search result returns total 0
    $this->getJson(route('home.search', ['q' => 'NonexistentZebraItem']))
        ->assertOk()
        ->assertJsonPath('total', 0);
});
