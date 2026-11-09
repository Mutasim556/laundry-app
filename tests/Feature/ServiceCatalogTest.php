<?php

use Modules\Admin\Models\Item;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Service;
use Modules\Admin\Models\Unit;

test('catalog renders category-only services with BDT prices and their billing unit', function () {
    $category = ItemCategory::create(['name' => 'Wash & Fold', 'slug' => 'wash-fold', 'is_active' => true]);
    $unit = Unit::create(['name' => 'Per Kg', 'short_name' => 'kg', 'is_active' => true]);
    Service::create(['name' => 'Separate Wash & Fold', 'item_category_id' => $category->id, 'unit_id' => $unit->id, 'price' => 800]);

    $this->get('/services?category=wash-fold')
        ->assertOk()
        ->assertSee('Separate Wash &amp; Fold', false)
        ->assertSee('BDT')
        ->assertSee('800.00')
        ->assertSee('/ kg')
        ->assertDontSee('£');
});

test('default category redirect retains filters and ajax follows the same catalog contract', function () {
    ItemCategory::create(['name' => 'Wash & Iron', 'slug' => 'wash-iron', 'is_active' => true]);

    $this->get('/services?q=shirt')->assertRedirect('/services?q=shirt&category=wash-iron');
    $this->getJson('/services?category=wash-iron')->assertOk()
        ->assertJsonPath('category', 'wash-iron')
        ->assertJsonPath('total', 0);
});

test('ajax item filtering includes the matching item and category fallback but hides inactive services', function () {
    $category = ItemCategory::create(['name' => 'Wash & Iron', 'slug' => 'wash-iron', 'is_active' => true]);
    $shirt = Item::create(['name' => 'Shirts', 'item_category_id' => $category->id]);
    $tops = Item::create(['name' => 'Tops', 'item_category_id' => $category->id]);
    Service::create(['name' => 'Shirt care', 'item_category_id' => $category->id, 'item_id' => $shirt->id, 'price' => 100]);
    Service::create(['name' => 'Shirt express', 'item_id' => $shirt->id, 'price' => 150]);
    Service::create(['name' => 'Hidden service', 'item_category_id' => $category->id, 'item_id' => $shirt->id, 'price' => 90, 'is_active' => false]);
    Service::create(['name' => 'Top care', 'item_category_id' => $category->id, 'item_id' => $tops->id, 'price' => 120]);

    $response = $this->getJson('/services?category=wash-iron&item='.$shirt->id)->assertOk()->assertJsonPath('total', 2);
    expect($response->json('html'))->toContain('Shirt care', 'Shirt express')->not->toContain('Hidden service', 'Top care');
});

test('catalog rejects items outside the selected category and unavailable categories with 404', function () {
    ItemCategory::create(['name' => 'Wash & Iron', 'slug' => 'wash-iron', 'is_active' => true]);
    $other = ItemCategory::create(['name' => 'Dry cleaning', 'slug' => 'dry-cleaning', 'is_active' => false]);
    $item = Item::create(['name' => 'Coats', 'item_category_id' => $other->id]);

    $this->getJson('/services?category=wash-iron&item='.$item->id)->assertNotFound();
    $this->getJson('/services?category=dry-cleaning')->assertNotFound();
    $this->getJson('/services?category=missing')->assertNotFound();
    $this->getJson('/services?category=wash-iron&item=0')->assertNotFound();
    $this->getJson('/services?category=0')->assertNotFound();
});

test('ajax search matches descriptions and price sorting works in both directions', function () {
    $category = ItemCategory::create(['name' => 'Wash & Fold', 'slug' => 'wash-fold', 'is_active' => true]);
    Service::create(['name' => 'Separate care', 'description' => 'Light and dark clothes washed separately.', 'item_category_id' => $category->id, 'price' => 800]);
    Service::create(['name' => 'Mixed care', 'description' => 'All colours together.', 'item_category_id' => $category->id, 'price' => 500]);

    $response = $this->getJson('/services?category=wash-fold&q=dark')->assertOk()->assertJsonPath('total', 1);
    expect($response->json('html'))->toContain('Separate care')->not->toContain('Mixed care');

    $response = $this->getJson('/services?category=wash-fold&sort=price-low')->assertOk();
    expect(strpos($response->json('html'), 'Mixed care'))->toBeLessThan(strpos($response->json('html'), 'Separate care'));
    $response = $this->getJson('/services?category=wash-fold&sort=price-high')->assertOk();
    expect(strpos($response->json('html'), 'Separate care'))->toBeLessThan(strpos($response->json('html'), 'Mixed care'));
});

test('general care filter excludes item services and uncategorized services remain separate', function () {
    $category = ItemCategory::create(['name' => 'Wash & Iron', 'slug' => 'wash-iron', 'is_active' => true]);
    $item = Item::create(['name' => 'Shirts', 'item_category_id' => $category->id]);
    Service::create(['name' => 'General care service', 'item_category_id' => $category->id, 'price' => 90]);
    Service::create(['name' => 'Shirt care service', 'item_category_id' => $category->id, 'item_id' => $item->id, 'price' => 100]);
    Service::create(['name' => 'Other service', 'price' => 60]);

    $response = $this->getJson('/services?category=wash-iron&item=other')->assertOk()->assertJsonPath('total', 1);
    expect($response->json('html'))->toContain('General care service')->not->toContain('Shirt care service', 'Other service');
    $response = $this->getJson('/services')->assertOk()->assertJsonPath('category', 'other-services')->assertJsonPath('total', 1);
    expect($response->json('html'))->toContain('Other service')->not->toContain('General care service');
});

test('ajax paginates services while retaining filters in navigation links', function () {
    $category = ItemCategory::create(['name' => 'Wash & Fold', 'slug' => 'wash-fold', 'is_active' => true]);
    foreach (range(1, 13) as $number) {
        Service::create(['name' => sprintf('Care %02d', $number), 'item_category_id' => $category->id, 'price' => $number * 10]);
    }

    $response = $this->getJson('/services?category=wash-fold&q=Care&sort=price-low')->assertOk()->assertJsonPath('total', 13);
    expect($response->json('html'))->toContain('Care 01', 'Care 12', 'page=2', 'sort=price-low')->not->toContain('Care 13');
    $response = $this->getJson('/services?category=wash-fold&q=Care&sort=price-low&page=2')->assertOk();
    expect($response->json('html'))->toContain('Care 13')->not->toContain('Care 01');
});

test('catalog escapes stored text and search input in ajax html', function () {
    $category = ItemCategory::create(['name' => 'Wash & Fold', 'slug' => 'wash-fold', 'is_active' => true]);
    Service::create(['name' => '<script>care</script>', 'description' => '<img src=x onerror=alert(1)>', 'item_category_id' => $category->id, 'price' => 80]);

    $response = $this->getJson('/services?category=wash-fold&q='.urlencode('<script>'))->assertOk()->assertJsonPath('total', 1);
    expect($response->json('html'))->toContain('&lt;script&gt;', '&lt;img')->not->toContain('<script>', '<img src=x');
});

test('catalog validates ajax filters instead of accepting arbitrary sorting or array input', function (array $query, string $field) {
    $this->getJson('/services?'.http_build_query($query))->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'sort injection' => [['sort' => 'price desc; drop table services'], 'sort'],
    'array category' => [['category' => ['wash-fold']], 'category'],
    'array item' => [['item' => ['1']], 'item'],
    'long search' => [['q' => str_repeat('a', 101)], 'q'],
    'invalid page' => [['page' => -1], 'page'],
]);

test('catalog hides services whose linked item is inactive', function () {
    $category = ItemCategory::create(['name' => 'Wash & Iron', 'slug' => 'wash-iron', 'is_active' => true]);
    $item = Item::create(['name' => 'Shirts', 'item_category_id' => $category->id, 'is_active' => false]);
    Service::create(['name' => 'Hidden item care', 'item_category_id' => $category->id, 'item_id' => $item->id, 'price' => 100]);

    $response = $this->getJson('/services?category=wash-iron')->assertOk()->assertJsonPath('total', 0);
    expect($response->json('html'))->not->toContain('Hidden item care');
});
