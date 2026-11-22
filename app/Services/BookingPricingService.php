<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Modules\Admin\App\Models\BookingFeeSetting;

class BookingPricingService
{
    public function getFeeSettings(): array
    {
        $defaults = [
            'service_fee' => [
                'fee_code' => 'service_fee',
                'display_name' => 'Service Fee',
                'fee_type' => 'fixed',
                'fee_value' => 0.00,
                'fee_value_minor' => 0,
                'is_active' => false,
                'is_free' => false,
                'threshold_enabled' => false,
                'threshold_amount_minor' => 0,
                'threshold_after_discount' => true,
                'description' => '',
            ],
            'collection_delivery_fee' => [
                'fee_code' => 'collection_delivery_fee',
                'display_name' => 'Collection & Delivery',
                'fee_type' => 'fixed',
                'fee_value' => 40.00,
                'fee_value_minor' => 4000,
                'is_active' => true,
                'is_free' => false,
                'threshold_enabled' => true,
                'threshold_amount_minor' => 60000,
                'threshold_after_discount' => true,
                'description' => '',
            ],
        ];

        try {
            if (Schema::hasTable('booking_fee_settings') && BookingFeeSetting::allowed()->exists()) {
                $rows = BookingFeeSetting::allowed()->get()->keyBy('fee_code');

                foreach (['service_fee', 'collection_delivery_fee'] as $code) {
                    if ($row = $rows->get($code)) {
                        $defaults[$code] = [
                            'fee_code' => $row->fee_code,
                            'display_name' => $row->display_name,
                            'fee_type' => $row->fee_type,
                            'fee_value' => (float) $row->fee_value,
                            'fee_value_minor' => (int) $row->fee_value_minor,
                            'is_active' => (bool) $row->is_active,
                            'is_free' => (bool) $row->is_free,
                            'threshold_enabled' => (bool) $row->threshold_enabled,
                            'threshold_amount_minor' => (int) $row->threshold_amount_minor,
                            'threshold_after_discount' => (bool) $row->threshold_after_discount,
                            'description' => (string) ($row->description ?? ''),
                        ];
                    }
                }

                return $defaults;
            }
        } catch (\Throwable) {
            // Fall through to Store defaults if table is unavailable
        }

        try {
            if (class_exists(\Modules\Admin\Models\Store::class)) {
                $flagshipStore = \Modules\Admin\Models\Store::query()
                    ->where('status', 'active')
                    ->orderBy('id')
                    ->first();
                if ($flagshipStore) {
                    $deliveryFee = (float) ($flagshipStore->delivery_fee ?? 40.00);
                    $threshold = (float) ($flagshipStore->free_delivery_threshold ?? 600.00);
                    $defaults['collection_delivery_fee']['fee_value'] = $deliveryFee;
                    $defaults['collection_delivery_fee']['fee_value_minor'] = BookingFeeSetting::toMinor($deliveryFee);
                    $defaults['collection_delivery_fee']['is_free'] = $deliveryFee <= 0;
                    $defaults['collection_delivery_fee']['threshold_enabled'] = $threshold > 0;
                    $defaults['collection_delivery_fee']['threshold_amount_minor'] = BookingFeeSetting::toMinor($threshold);
                }
            }
        } catch (\Throwable) {
            // Use built-in defaults
        }

        return $defaults;
    }

    public function getMinOrderAmountMinor(): int
    {
        try {
            if (class_exists(\Modules\Admin\Models\Store::class)) {
                $flagshipStore = \Modules\Admin\Models\Store::query()
                    ->where('status', 'active')
                    ->orderBy('id')
                    ->first();
                if ($flagshipStore && $flagshipStore->min_order_amount !== null) {
                    return BookingFeeSetting::toMinor((float) $flagshipStore->min_order_amount);
                }
            }
        } catch (\Throwable) {
            // Fallback below
        }

        return 15000;
    }

    /**
     * Calculate authoritative booking pricing using integer minor units.
     * Preserves fee snapshots on already-confirmed bookings.
     */
    public function calculate(array $booking, Collection $catalog, ?int $confirmedItemsSubtotalMinor = null): array
    {
        $currencyService = app(CurrencyService::class);
        $activeCurrency = $currencyService->code();
        $preservedSnapshot = $this->findConfirmedFeeSnapshot($booking['reference'] ?? null);
        if ($preservedSnapshot !== null && ! empty($preservedSnapshot['currency'])) {
            $activeCurrency = strtoupper((string) $preservedSnapshot['currency']);
        }

        $savedServices = $booking['services'] ?? [];
        $verifiedServices = [];
        $estimatedServicesMinor = 0;

        foreach ($savedServices as $svc) {
            $catId = (int) ($svc['category_id'] ?? $svc['service_id'] ?? 0);
            $catObj = $catalog->get($catId);
            if (! $catObj) {
                continue;
            }

            $isWashFold = (bool) $catObj->is_wash_fold;
            $unitPrice = null;
            $unitName = 'item';
            $displayName = $catObj->name;
            $optionName = null;
            $optionDesc = $catObj->subtitle;
            $pricingTypeLabel = 'ITEM BASED';
            $dbServiceId = (int) $catObj->id;
            $optionServiceId = null;

            if ($isWashFold) {
                $optId = (int) ($svc['option_service_id'] ?? $svc['service_id'] ?? 0);
                $matchedOpt = $catObj->wash_options->firstWhere('id', $optId) ?: $catObj->wash_options->first();
                if ($matchedOpt) {
                    $dbServiceId = (int) $matchedOpt->id;
                    $optionServiceId = (int) $matchedOpt->id;
                    $optionName = $matchedOpt->name;
                    $optionDesc = $matchedOpt->description;
                    $unitPrice = (float) $matchedOpt->price;
                    $unitName = $matchedOpt->unit_name ?: 'kg';
                    $pricingTypeLabel = $currencyService->formatMoney($unitPrice, $activeCurrency).' / '.$unitName;
                    $estimatedServicesMinor += $currencyService->toMinor($unitPrice, $activeCurrency);
                }
            } else {
                $pricingTypeLabel = 'Item-Based';
            }

            $verifiedServices[] = [
                'category_id' => (int) $catObj->id,
                'service_id' => $dbServiceId,
                'option_service_id' => $optionServiceId,
                'name' => $displayName,
                'option_name' => $optionName,
                'option_description' => $optionDesc,
                'slug' => $catObj->slug,
                'icon_url' => $catObj->icon_url,
                'is_wash_fold' => $isWashFold,
                'price' => $unitPrice,
                'unit' => $unitName,
                'pricing_type_label' => $pricingTypeLabel,
                'special_request' => trim((string) ($svc['special_request'] ?? '')),
            ];
        }

        $minOrderMinor = $this->getMinOrderAmountMinor();
        $tipOption = (string) ($booking['confirm_options']['tip_option'] ?? '0');
        $tipAmountFloat = max(0.0, (float) ($booking['confirm_options']['tip_amount'] ?? 0.0));
        $tipMinor = BookingFeeSetting::toMinor($tipAmountFloat);
        $discountMinor = 0;

        // Preserve fee snapshot for already-confirmed bookings so admin fee changes do not alter history
        $preservedSnapshot = $this->findConfirmedFeeSnapshot($booking['reference'] ?? null);

        if ($preservedSnapshot !== null) {
            $serviceCfg = $preservedSnapshot['service_fee'];
            $deliveryCfg = $preservedSnapshot['collection_delivery_fee'];
            $serviceFeeMinor = (int) ($preservedSnapshot['applied_service_fee_minor'] ?? 0);
            $collectionDeliveryFeeMinor = (int) ($preservedSnapshot['applied_collection_delivery_fee_minor'] ?? 0);
            $baseDeliveryMinor = (int) ($deliveryCfg['fee_value_minor'] ?? $collectionDeliveryFeeMinor);
            $thresholdEnabled = (bool) ($deliveryCfg['threshold_enabled'] ?? false);
            $thresholdMinor = (int) ($deliveryCfg['threshold_amount_minor'] ?? 0);
            $thresholdAfterDiscount = (bool) ($deliveryCfg['threshold_after_discount'] ?? true);
            $isFreeDelivery = $collectionDeliveryFeeMinor === 0;
            $thresholdStatus = $isFreeDelivery && $thresholdEnabled ? 'qualified' : ($thresholdEnabled ? 'pending' : 'disabled');
        } else {
            $settings = $this->getFeeSettings();
            $serviceCfg = $settings['service_fee'];
            $deliveryCfg = $settings['collection_delivery_fee'];

            $hasUnknownFinalAmount = $confirmedItemsSubtotalMinor === null;
            $knownSubtotalMinor = $confirmedItemsSubtotalMinor ?? $estimatedServicesMinor;

            // Service Fee calculation in integer minor units
            if (! $serviceCfg['is_active']) {
                $serviceFeeMinor = 0;
            } elseif ($serviceCfg['fee_type'] === 'percentage') {
                // fee_value_minor stores percentage * 100 (basis points: 5.00% = 500 bps)
                $bps = (int) $serviceCfg['fee_value_minor'];
                $serviceFeeMinor = $hasUnknownFinalAmount
                    ? 0
                    : (int) round(($knownSubtotalMinor * $bps) / 10000);
            } else {
                $serviceFeeMinor = (int) $serviceCfg['fee_value_minor'];
            }

            // Collection & Delivery Fee calculation in integer minor units
            $baseDeliveryMinor = (int) $deliveryCfg['fee_value_minor'];
            $thresholdEnabled = (bool) $deliveryCfg['threshold_enabled'];
            $thresholdMinor = (int) $deliveryCfg['threshold_amount_minor'];
            $thresholdAfterDiscount = (bool) $deliveryCfg['threshold_after_discount'];

            if (! $deliveryCfg['is_active'] || $deliveryCfg['is_free'] || $baseDeliveryMinor <= 0) {
                $collectionDeliveryFeeMinor = 0;
                $isFreeDelivery = true;
                $thresholdStatus = 'free';
            } elseif ($thresholdEnabled && $thresholdMinor > 0) {
                if ($hasUnknownFinalAmount) {
                    // Never promise threshold-based free delivery on unknown item/weight estimates
                    $collectionDeliveryFeeMinor = $baseDeliveryMinor;
                    $isFreeDelivery = false;
                    $thresholdStatus = 'pending';
                } else {
                    $eligibleMinor = $thresholdAfterDiscount
                        ? max(0, $confirmedItemsSubtotalMinor - $discountMinor)
                        : $confirmedItemsSubtotalMinor;

                    if ($eligibleMinor >= $thresholdMinor) {
                        $collectionDeliveryFeeMinor = 0;
                        $isFreeDelivery = true;
                        $thresholdStatus = 'qualified';
                    } else {
                        $collectionDeliveryFeeMinor = $baseDeliveryMinor;
                        $isFreeDelivery = false;
                        $thresholdStatus = 'not_met';
                    }
                }
            } else {
                $collectionDeliveryFeeMinor = $baseDeliveryMinor;
                $isFreeDelivery = false;
                $thresholdStatus = 'disabled';
            }
        }

        $estimatedOrderTotalMinor = max($minOrderMinor, $estimatedServicesMinor)
            + $serviceFeeMinor
            + $collectionDeliveryFeeMinor
            + $tipMinor
            - $discountMinor;

        $feeSnapshot = [
            'currency' => $activeCurrency,
            'service_fee' => $serviceCfg,
            'collection_delivery_fee' => $deliveryCfg,
            'applied_service_fee_minor' => $serviceFeeMinor,
            'applied_collection_delivery_fee_minor' => $collectionDeliveryFeeMinor,
            'is_free_delivery' => $isFreeDelivery,
            'threshold_status' => $thresholdStatus,
            'captured_at' => now()->toIso8601String(),
        ];

        return [
            'currency' => $activeCurrency,
            'verified_services' => $verifiedServices,
            'estimated_services_total' => BookingFeeSetting::fromMinor($estimatedServicesMinor),
            'estimated_services_minor' => $estimatedServicesMinor,
            'service_fee_display_name' => $serviceCfg['display_name'] ?? 'Service Fee',
            'service_fee_type' => $serviceCfg['fee_type'] ?? 'fixed',
            'service_fee_value' => (float) ($serviceCfg['fee_value'] ?? 0),
            'service_fee_active' => (bool) ($serviceCfg['is_active'] ?? false),
            'service_fee' => BookingFeeSetting::fromMinor($serviceFeeMinor),
            'service_fee_minor' => $serviceFeeMinor,
            'delivery_fee_display_name' => $deliveryCfg['display_name'] ?? 'Collection & Delivery',
            'delivery_fee_active' => (bool) ($deliveryCfg['is_active'] ?? true),
            'base_delivery_fee' => BookingFeeSetting::fromMinor($baseDeliveryMinor),
            'base_delivery_fee_minor' => $baseDeliveryMinor,
            'threshold_enabled' => $thresholdEnabled,
            'free_delivery_threshold' => BookingFeeSetting::fromMinor($thresholdMinor),
            'free_delivery_threshold_minor' => $thresholdMinor,
            'threshold_after_discount' => $thresholdAfterDiscount,
            'threshold_status' => $thresholdStatus,
            'is_free_delivery' => $isFreeDelivery,
            'collection_delivery_fee' => BookingFeeSetting::fromMinor($collectionDeliveryFeeMinor),
            'collection_delivery_fee_minor' => $collectionDeliveryFeeMinor,
            'min_order_amount' => BookingFeeSetting::fromMinor($minOrderMinor),
            'min_order_amount_minor' => $minOrderMinor,
            'tip_option' => $tipOption,
            'tip_amount' => BookingFeeSetting::fromMinor($tipMinor),
            'tip_minor' => $tipMinor,
            'discount_amount' => BookingFeeSetting::fromMinor($discountMinor),
            'discount_minor' => $discountMinor,
            'estimated_total' => BookingFeeSetting::fromMinor($estimatedOrderTotalMinor),
            'estimated_total_minor' => $estimatedOrderTotalMinor,
            'due_today' => 0.00,
            'fee_snapshot' => $feeSnapshot,
        ];
    }

    protected function findConfirmedFeeSnapshot(?string $reference): ?array
    {
        if (empty($reference)) {
            return null;
        }

        try {
            if (! Schema::hasTable('bookings')) {
                return null;
            }

            $existing = Booking::where('reference', $reference)
                ->whereNotNull('confirmed_at')
                ->first();

            if ($existing && is_array($existing->fee_snapshot) && isset($existing->fee_snapshot['service_fee'])) {
                return $existing->fee_snapshot;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
