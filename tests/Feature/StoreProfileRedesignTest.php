<?php

use App\Models\StoreBankAccount;
use App\Models\StoreUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Models\Store;

uses(RefreshDatabase::class);

function spProfileMakeStore(string $name = 'Profile Test Store'): Store
{
    return Store::create([
        'name' => $name,
        'slug' => strtolower(str_replace(' ', '-', $name)) . '-' . random_int(1000, 9999),
        'store_code' => 'STR-' . random_int(1000, 9999),
        'email' => 'store' . random_int(1000, 9999) . '@example.com',
        'phone_country_code' => '+44',
        'phone' => '161496' . random_int(1000, 9999),
        'address_line_1' => '10 Deansgate',
        'city' => 'Manchester',
        'postcode' => 'M1 2PF',
        'country_code' => 'GB',
        'status' => 'active',
        'commission_type' => 'percentage',
        'commission_rate' => 20.00,
    ]);
}

function spProfileMakeUser(Store $store, string $email): StoreUser
{
    $phone = '77009' . random_int(10000, 99999);

    return StoreUser::create([
        'store_id' => $store->id,
        'name' => $store->name . ' Manager',
        'email' => $email,
        'phone_country_code' => '+44',
        'phone' => $phone,
        'phone_normalized' => StoreUser::normalizePhone($phone, '+44'),
        'password' => 'StorePass123!',
        'role' => 'owner',
        'is_active' => true,
    ]);
}

it('renders redesigned store profile with read-only store info and masked bank accounts', function () {
    $store = spProfileMakeStore('FreshWash Manchester');
    $user = spProfileMakeUser($store, 'profile@example.com');

    StoreBankAccount::create([
        'store_id' => $store->id,
        'country_code' => 'GB',
        'currency' => 'GBP',
        'account_holder_name' => 'FreshWash Manchester Ltd',
        'bank_name' => 'Barclays UK',
        'account_number_encrypted' => encrypt('12345678'),
        'account_number_last4' => '5678',
        'account_number_masked' => '****5678',
        'routing_code_encrypted' => encrypt('200000'),
        'is_default' => true,
        'is_active' => true,
        'verification_status' => 'verified',
    ]);

    $this->actingAs($user, 'store')
        ->get(route('store.profile.show'))
        ->assertOk()
        ->assertSee('Store Profile')
        ->assertSee('FreshWash Manchester')
        ->assertSee('Admin Managed (Read-Only)')
        ->assertSee('Barclays UK')
        ->assertSee('****5678')
        ->assertDontSee('12345678');
});

it('updates store user profile via ajax json and validates duplicate phone', function () {
    $store = spProfileMakeStore('Ajax Profile Store');
    $user = spProfileMakeUser($store, 'ajax1@example.com');
    $otherUser = spProfileMakeUser($store, 'ajax2@example.com');

    $this->actingAs($user, 'store')
        ->putJson(route('store.profile.update'), [
            'name' => 'Updated Manager',
            'phone_country_code' => '+44',
            'phone' => '7700112233',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('store_user.name', 'Updated Manager');

    expect($user->fresh()->name)->toBe('Updated Manager');

    $this->actingAs($user, 'store')
        ->putJson(route('store.profile.update'), [
            'name' => 'Updated Manager',
            'phone_country_code' => $otherUser->phone_country_code,
            'phone' => $otherUser->phone,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['phone']);
});

it('changes store user password via ajax with validation and hashing', function () {
    $store = spProfileMakeStore('Password Store');
    $user = spProfileMakeUser($store, 'pw@example.com');

    // Wrong current password
    $this->actingAs($user, 'store')
        ->putJson(route('store.password.change.update'), [
            'current_password' => 'WrongPass123!',
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['current_password']);

    // Confirmation mismatch
    $this->actingAs($user, 'store')
        ->putJson(route('store.password.change.update'), [
            'current_password' => 'StorePass123!',
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'Mismatch123!',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);

    // Reusing current password
    $this->actingAs($user, 'store')
        ->putJson(route('store.password.change.update'), [
            'current_password' => 'StorePass123!',
            'password' => 'StorePass123!',
            'password_confirmation' => 'StorePass123!',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);

    // Valid password change
    $this->actingAs($user, 'store')
        ->putJson(route('store.password.change.update'), [
            'current_password' => 'StorePass123!',
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'ok');

    $user->refresh();
    expect(Hash::check('NewSecurePass123!', $user->password))->toBeTrue();
    expect(Hash::check('StorePass123!', $user->password))->toBeFalse();
});
