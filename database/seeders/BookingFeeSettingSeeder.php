<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\App\Models\BookingFeeSetting;

class BookingFeeSettingSeeder extends Seeder
{
    public function run(): void
    {
        $serviceFee = BookingFeeSetting::firstOrNew(['fee_code' => 'service_fee']);
        if (! $serviceFee->exists) {
            $serviceFee->display_name = 'Service Fee';
            $serviceFee->fee_type = 'fixed';
            $serviceFee->fee_value = 0.00;
            $serviceFee->fee_value_minor = 0;
            $serviceFee->is_active = false;
            $serviceFee->is_free = false;
            $serviceFee->threshold_enabled = false;
            $serviceFee->threshold_amount_minor = 0;
            $serviceFee->threshold_after_discount = true;
            $serviceFee->description = 'Platform and handling service fee applied to bookings.';
            $serviceFee->save();
        }

        $deliveryFee = BookingFeeSetting::firstOrNew(['fee_code' => 'collection_delivery_fee']);
        if (! $deliveryFee->exists) {
            $baseDelivery = 40.00;
            $freeThreshold = 600.00;

            try {
                if (class_exists(\Modules\Admin\Models\Store::class)) {
                    $store = \Modules\Admin\Models\Store::query()->where('status', 'active')->orderBy('id')->first();
                    if ($store) {
                        $baseDelivery = (float) ($store->delivery_fee ?? 40.00);
                        $freeThreshold = (float) ($store->free_delivery_threshold ?? 600.00);
                    }
                }
            } catch (\Throwable) {
                // Use defaults if stores table is unavailable
            }

            $deliveryFee->display_name = 'Collection & Delivery';
            $deliveryFee->fee_type = 'fixed';
            $deliveryFee->fee_value = $baseDelivery;
            $deliveryFee->fee_value_minor = BookingFeeSetting::toMinor($baseDelivery);
            $deliveryFee->is_active = true;
            $deliveryFee->is_free = $baseDelivery <= 0;
            $deliveryFee->threshold_enabled = $freeThreshold > 0;
            $deliveryFee->threshold_amount_minor = BookingFeeSetting::toMinor($freeThreshold);
            $deliveryFee->threshold_after_discount = true;
            $deliveryFee->description = 'Doorstep collection and delivery fee.';
            $deliveryFee->save();
        }
    }
}
