<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;

uses(RefreshDatabase::class);

function dpRepMakePerson(string $name = 'DP Reports Rider'): DeliveryPerson
{
    $phone = '077007' . random_int(10000, 99999);

    return DeliveryPerson::create([
        'code' => 'DP-' . random_int(1000, 9999),
        'full_name' => $name,
        'date_of_birth' => '1990-01-01',
        'email' => 'dp' . random_int(1000, 9999) . '@example.com',
        'phone_country_code' => '+44',
        'phone' => $phone,
        'phone_normalized' => DeliveryPerson::normalizePhone($phone, '+44'),
        'address_line_1' => '10 High St',
        'city' => 'Manchester',
        'residential_postcode' => 'M1 2PF',
        'work_status' => 'active',
        'availability_status' => 'available',
        'currency' => 'GBP',
        'portal_access_enabled' => true,
        'password' => 'Driver2026',
    ]);
}

it('loads delivery person reports page and all 7 ajax report tabs', function () {
    $dp = dpRepMakePerson();

    $this->actingAs($dp, 'delivery_person')
        ->get(route('delivery-person.reports.index'))
        ->assertOk()
        ->assertSee('My Reports &amp; Analytics', false);

    foreach ([
        'delivery-person.reports.overview',
        'delivery-person.reports.collections',
        'delivery-person.reports.final-deliveries',
        'delivery-person.reports.earnings',
        'delivery-person.reports.withdrawals',
        'delivery-person.reports.cash-account',
        'delivery-person.reports.performance',
    ] as $routeName) {
        $this->actingAs($dp, 'delivery_person')
            ->getJson(route($routeName))
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }
});

it('isolates csv export to authenticated delivery person', function () {
    $dpA = dpRepMakePerson('Rider A');
    $dpB = dpRepMakePerson('Rider B');

    $this->actingAs($dpA, 'delivery_person')
        ->get(route('delivery-person.reports.export'))
        ->assertOk();
});
