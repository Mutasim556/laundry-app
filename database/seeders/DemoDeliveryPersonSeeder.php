<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Database\Seeders\UkDemoDeliveryPersonsSeeder;
use Modules\Admin\Models\DeliveryPerson;

class DemoDeliveryPersonSeeder extends Seeder
{
    /**
     * Seed demo delivery persons and enable Delivery Person Portal login credentials.
     */
    public function run(): void
    {
        $this->call([
            UkDemoDeliveryPersonsSeeder::class,
        ]);

        DeliveryPerson::query()
            ->where('code', 'like', 'DP-UK-%')
            ->get()
            ->each(function (DeliveryPerson $person) {
                $person->update([
                    'work_status' => 'active',
                    'portal_access_enabled' => true,
                    'phone_normalized' => DeliveryPerson::normalizePhone($person->phone, $person->phone_country_code),
                    'password' => 'Driver@12345',
                ]);
            });
    }
}
