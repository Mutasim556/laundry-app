<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\App\Models\PaymentGateway;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $stripeKey = (string) config('services.stripe.key', '');
        $stripeSecret = (string) config('services.stripe.secret', '');
        $stripeWebhookSecret = (string) config('services.stripe.webhook_secret', '');
        $defaultCurrency = strtoupper((string) config('services.stripe.currency', 'GBP')) ?: 'GBP';

        $stripe = PaymentGateway::firstOrNew(['gateway_code' => 'stripe']);
        if (! $stripe->exists) {
            $hasEnvStripe = $stripeKey !== '' && $stripeSecret !== '';
            $stripe->display_name = 'Credit / Debit Card — Stripe';
            $stripe->description = 'Enter your card securely via Stripe. You will not be charged until your laundry is weighed and priced.';
            $stripe->environment = str_starts_with($stripeKey, 'pk_live_') ? 'live' : 'sandbox';
            $stripe->public_configuration = [
                'publishable_key' => $stripeKey,
                'currency' => $defaultCurrency,
                'capture_mode' => 'delayed_setup_intent',
                'enable_saved_cards' => true,
            ];
            if ($hasEnvStripe) {
                $stripe->setCredentials([
                    'secret_key' => $stripeSecret,
                    'webhook_secret' => $stripeWebhookSecret,
                ]);
            }
            $stripe->is_active = $hasEnvStripe;
            $stripe->sort_order = 1;
            $stripe->syncConfigurationStatus();
            $stripe->save();
        }

        $paypal = PaymentGateway::firstOrNew(['gateway_code' => 'paypal']);
        if (! $paypal->exists) {
            $paypal->display_name = 'PayPal';
            $paypal->description = 'Select PayPal as your payment method. You can complete PayPal payment once your laundry is weighed and finalized.';
            $paypal->environment = 'sandbox';
            $paypal->public_configuration = [
                'client_id' => '',
                'currency' => $defaultCurrency,
                'webhook_id' => '',
            ];
            $paypal->is_active = false;
            $paypal->sort_order = 2;
            $paypal->syncConfigurationStatus();
            $paypal->save();
        }

        $cod = PaymentGateway::firstOrNew(['gateway_code' => 'cod']);
        if (! $cod->exists) {
            $cod->display_name = 'Cash on Delivery';
            $cod->description = 'Pay by cash or POS card terminal upon collection or delivery after your items are counted and cleaned.';
            $cod->environment = 'none';
            $cod->public_configuration = [
                'instructions' => 'Pay our driver by cash or mobile POS terminal at delivery.',
            ];
            $cod->is_active = true;
            $cod->sort_order = 3;
            $cod->syncConfigurationStatus();
            $cod->save();
        }
    }
}
