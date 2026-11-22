<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\Admin;
use Modules\Admin\Models\UkAddress;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach ([
        'view-uk-addresses',
        'create-uk-addresses',
        'edit-uk-addresses',
        'delete-uk-addresses',
        'import-uk-addresses',
        'export-uk-addresses',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin']);
    }
});

test('public api addresses search normalizes lowercase postcode and returns active local addresses', function () {
    $active = UkAddress::create([
        'postcode' => 'e10bj',
        'address_line_1' => 'Flat 10, Demo Tower',
        'street' => 'Crowder Street',
        'city' => 'London',
        'is_active' => true,
        'is_demo' => true,
    ]);

    UkAddress::create([
        'postcode' => 'E1 0BJ',
        'address_line_1' => 'Flat 99, Inactive Tower',
        'street' => 'Crowder Street',
        'city' => 'London',
        'is_active' => false,
        'is_demo' => true,
    ]);

    $response = $this->getJson(route('api.addresses.search', ['postcode' => 'e10bj']));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'postcode' => 'E1 0BJ',
        ])
        ->assertJsonFragment([
            'id' => $active->id,
            'full_address' => $active->full_address,
        ]);

    $returnedIds = collect($response->json('addresses'))->pluck('id')->all();
    expect($returnedIds)->toContain($active->id);
    expect(count($returnedIds))->toBe(1);
});

test('public api addresses search rejects invalid uk postcode', function () {
    $this->getJson(route('api.addresses.search', ['postcode' => 'NOTVALID']))
        ->assertStatus(422)
        ->assertJson(['success' => false]);
});

test('booking step 2 stores uk_address_id and preserves snapshot even if address is deactivated later', function () {
    $address = UkAddress::create([
        'postcode' => 'SW1A 1AA',
        'address_line_1' => '100 Demo Residence',
        'city' => 'London',
        'is_active' => true,
        'is_demo' => true,
    ]);

    $response = $this->withSession([
        'booking' => [
            'services' => [['service_id' => 1, 'name' => 'Wash & Fold', 'quantity' => 1, 'price' => 100]],
        ],
    ])->post(route('booking.address.store'), [
        'collection_mode' => 'home_office',
        'postcode' => 'sw1a1aa',
        'uk_address_id' => $address->id,
        'selected_address' => $address->full_address,
        'is_manual' => '0',
        'address_type' => 'HOME',
    ]);

    $response->assertRedirect();
    expect(session('booking.address.uk_address_id'))->toBe($address->id);
    expect(session('booking.address.postcode'))->toBe('SW1A 1AA');
    expect(session('booking.address.selected_address'))->toBe($address->full_address);

    // Deactivate in admin; returning to Step 2 still preserves customer's snapshot
    $address->update(['is_active' => false]);

    $step2 = $this->get(route('booking.address'));
    $step2->assertOk();
    $step2->assertSee($address->full_address);
});

test('unauthenticated and unauthorized admins cannot access uk address management', function () {
    $this->get(route('admin.uk-addresses.index'))
        ->assertRedirect(route('admin.login'));

    $unauthorizedAdmin = Admin::factory()->create();
    $this->actingAs($unauthorizedAdmin, 'admin')
        ->get(route('admin.uk-addresses.index'))
        ->assertStatus(403);
});

test('authorized admin can create, prevent duplicates, edit, toggle status, and delete uk address', function () {
    $admin = Admin::factory()->create();
    $admin->givePermissionTo([
        'view-uk-addresses',
        'create-uk-addresses',
        'edit-uk-addresses',
        'delete-uk-addresses',
    ]);

    // Create
    $createResp = $this->actingAs($admin, 'admin')->postJson(route('admin.uk-addresses.store'), [
        'postcode' => 'ec1a1bb',
        'address_line_1' => '22 Bishops Demo Gate',
        'street' => 'St Martin-le-Grand',
        'city' => 'London',
        'is_active' => 1,
    ]);

    $createResp->assertCreated()->assertJson(['success' => true]);
    $createdId = $createResp->json('address.id');

    // Duplicate prevention
    $this->actingAs($admin, 'admin')->postJson(route('admin.uk-addresses.store'), [
        'postcode' => 'EC1A 1BB',
        'address_line_1' => '22 Bishops Demo Gate',
        'street' => 'St Martin-le-Grand',
        'city' => 'London',
    ])->assertStatus(422);

    // Public search finds new address
    $this->getJson(route('api.addresses.search', ['postcode' => 'EC1A 1BB']))
        ->assertOk()
        ->assertJsonFragment(['id' => $createdId]);

    // Update
    $this->actingAs($admin, 'admin')->putJson(route('admin.uk-addresses.update', $createdId), [
        'postcode' => 'EC1A 1BB',
        'address_line_1' => '24 Bishops Demo Gate',
        'street' => 'St Martin-le-Grand',
        'city' => 'London',
        'is_active' => 1,
    ])->assertOk()->assertJson(['success' => true]);

    // Toggle inactive
    $this->actingAs($admin, 'admin')->patchJson(route('admin.uk-addresses.toggle-status', $createdId))
        ->assertOk()
        ->assertJson(['is_active' => false]);

    // Public search no longer returns inactive address
    $searchAfterToggle = $this->getJson(route('api.addresses.search', ['postcode' => 'EC1A 1BB']));
    expect(collect($searchAfterToggle->json('addresses'))->pluck('id')->all())->not->toContain($createdId);

    // Delete
    $this->actingAs($admin, 'admin')->deleteJson(route('admin.uk-addresses.destroy', $createdId))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(UkAddress::find($createdId))->toBeNull();
});

test('authorized admin can preview and batch import uk addresses from csv and export csv', function () {
    Storage::fake('local');

    $admin = Admin::factory()->create();
    $admin->givePermissionTo([
        'view-uk-addresses',
        'import-uk-addresses',
        'export-uk-addresses',
    ]);

    $csvContent = implode("\n", [
        'postcode,address_line_1,street,city,county',
        'w1a1aa,Suite 10 Demo House,Portland Place,London,Greater London',
        'INVALID,Missing Postcode Row,Some Street,London,Greater London',
        'm11ae,50 Piccadilly Demo,Piccadilly,Manchester,Greater Manchester',
    ]);

    $file = UploadedFile::fake()->createWithContent('addresses.csv', $csvContent);

    $preview = $this->actingAs($admin, 'admin')->postJson(route('admin.uk-addresses.import.preview'), [
        'csv_file' => $file,
    ]);

    $preview->assertOk()
        ->assertJson(['success' => true])
        ->assertJsonStructure(['import_token', 'headers', 'sample_rows', 'suggested_mapping']);

    $token = $preview->json('import_token');

    $import = $this->actingAs($admin, 'admin')->postJson(route('admin.uk-addresses.import.process'), [
        'import_token' => $token,
        'duplicate_strategy' => 'skip',
        'mark_as_demo' => true,
        'mapping' => [
            'postcode' => 0,
            'address_line_1' => 1,
            'street' => 2,
            'city' => 3,
            'county' => 4,
        ],
    ]);

    $import->assertOk()
        ->assertJson([
            'success' => true,
            'summary' => [
                'imported' => 2,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 1,
            ],
        ]);

    expect(UkAddress::where('postcode', 'W1A 1AA')->where('is_demo', true)->exists())->toBeTrue();
    expect(UkAddress::where('postcode', 'M1 1AE')->where('is_demo', true)->exists())->toBeTrue();

    // Export CSV
    $export = $this->actingAs($admin, 'admin')->get(route('admin.uk-addresses.export'));
    $export->assertOk();
    expect($export->headers->get('content-type'))->toContain('text/csv');
});
