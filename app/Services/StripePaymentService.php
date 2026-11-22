<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CustomerPaymentMethod;
use Illuminate\Support\Facades\Schema;
use Modules\Admin\App\Models\PaymentGateway;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentService
{
    protected ?StripeClient $client = null;

    protected function gatewayRecord(): ?PaymentGateway
    {
        try {
            if (! Schema::hasTable('payment_gateways')) {
                return null;
            }

            return PaymentGateway::where('gateway_code', 'stripe')->first();
        } catch (\Throwable) {
            return null;
        }
    }

    public function isConfigured(): bool
    {
        $gateway = $this->gatewayRecord();
        if ($gateway) {
            return $gateway->isConfigured();
        }

        return ! empty(config('services.stripe.key')) && ! empty(config('services.stripe.secret'));
    }

    public function isActive(): bool
    {
        $gateway = $this->gatewayRecord();
        if ($gateway) {
            return $gateway->isUsable();
        }

        return $this->isConfigured();
    }

    public function publishableKey(): ?string
    {
        $gateway = $this->gatewayRecord();
        if ($gateway) {
            $pub = (string) $gateway->getPublicValue('publishable_key', '');
            if ($pub !== '') {
                return $pub;
            }
        }

        return config('services.stripe.key');
    }

    public function secretKey(): ?string
    {
        $gateway = $this->gatewayRecord();
        if ($gateway) {
            $creds = $gateway->getCredentials();
            if (! empty($creds['secret_key'])) {
                return (string) $creds['secret_key'];
            }
        }

        return config('services.stripe.secret');
    }

    public function currency(): string
    {
        return strtolower(app(CurrencyService::class)->code());
    }

    public function savedCardsEnabled(): bool
    {
        $gateway = $this->gatewayRecord();
        if ($gateway) {
            return (bool) $gateway->getPublicValue('enable_saved_cards', true);
        }

        return true;
    }

    protected function client(): StripeClient
    {
        if (! $this->client) {
            $secret = (string) ($this->secretKey() ?? '');
            if ($secret === '') {
                throw new \RuntimeException('Stripe secret key is not configured.');
            }
            $this->client = new StripeClient($secret);
        }

        return $this->client;
    }

    public function testConnection(): array
    {
        try {
            $secret = (string) ($this->secretKey() ?? '');
            if ($secret === '') {
                return [
                    'success' => false,
                    'message' => 'Stripe secret key is missing.',
                ];
            }

            $client = new StripeClient($secret);
            $balance = $client->balance->retrieve();

            return [
                'success' => true,
                'message' => 'Stripe API connection verified (livemode: '.($balance->livemode ? 'live' : 'sandbox').').',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Stripe connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Find an existing Stripe Customer ID for this user/email or create one via Stripe API.
     */
    public function getOrCreateCustomer(string $email, ?string $name = null, ?int $userId = null): string
    {
        if ($userId) {
            $existingPm = CustomerPaymentMethod::where('user_id', $userId)->value('stripe_customer_id');
            if ($existingPm) {
                return $existingPm;
            }
        }

        $existingBookingCustomer = Booking::where('customer_email', $email)
            ->whereNotNull('stripe_customer_id')
            ->value('stripe_customer_id');

        if ($existingBookingCustomer) {
            return $existingBookingCustomer;
        }

        $customer = $this->client()->customers->create([
            'email' => $email,
            'name' => $name ?: null,
            'metadata' => array_filter([
                'user_id' => $userId ? (string) $userId : null,
            ]),
        ]);

        return $customer->id;
    }

    /**
     * Create an off-session SetupIntent so the card can be charged after laundry is weighed.
     *
     * @return array{id: string, client_secret: string, customer_id: string}
     */
    public function createSetupIntent(string $customerId, array $metadata = []): array
    {
        $setupIntent = $this->client()->setupIntents->create([
            'customer' => $customerId,
            'automatic_payment_methods' => ['enabled' => true],
            'usage' => 'off_session',
            'metadata' => $metadata,
        ]);

        return [
            'id' => $setupIntent->id,
            'client_secret' => (string) $setupIntent->client_secret,
            'customer_id' => $customerId,
        ];
    }

    /**
     * Retrieve non-sensitive card metadata from Stripe for a confirmed PaymentMethod.
     * Never returns or stores raw card numbers or CVC.
     *
     * @return array{id: string, customer: ?string, brand: string, last4: string, exp_month: int, exp_year: int}
     */
    public function retrievePaymentMethod(string $paymentMethodId): array
    {
        $pm = $this->client()->paymentMethods->retrieve($paymentMethodId);

        return [
            'id' => $pm->id,
            'customer' => is_string($pm->customer) ? $pm->customer : ($pm->customer->id ?? null),
            'brand' => (string) ($pm->card->brand ?? 'card'),
            'last4' => (string) ($pm->card->last4 ?? '0000'),
            'exp_month' => (int) ($pm->card->exp_month ?? 1),
            'exp_year' => (int) ($pm->card->exp_year ?? 2030),
        ];
    }

    /**
     * Detach a saved PaymentMethod from Stripe.
     */
    public function detachPaymentMethod(string $paymentMethodId): void
    {
        $this->client()->paymentMethods->detach($paymentMethodId);
    }

    /**
     * Charge a saved payment method off-session after admin finalizes the verified laundry amount.
     * Uses an idempotency key to prevent duplicate charges.
     *
     * @return array{status: string, payment_intent_id: ?string, client_secret: ?string, error: ?string}
     */
    public function chargeSavedPaymentMethod(Booking $booking, int $amountMinor, string $idempotencyKey): array
    {
        if (! $booking->stripe_customer_id || ! $booking->stripe_payment_method_id) {
            return [
                'status' => 'failed',
                'payment_intent_id' => null,
                'client_secret' => null,
                'error' => 'Booking does not have an authorized Stripe Customer or PaymentMethod.',
            ];
        }

        try {
            $intent = $this->client()->paymentIntents->create([
                'amount' => $amountMinor,
                'currency' => strtolower($booking->currency ?: $this->currency()),
                'customer' => $booking->stripe_customer_id,
                'payment_method' => $booking->stripe_payment_method_id,
                'off_session' => true,
                'confirm' => true,
                'description' => 'FreshGo Laundry Order ' . $booking->reference,
                'metadata' => [
                    'booking_reference' => $booking->reference,
                    'booking_id' => (string) $booking->id,
                ],
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            return [
                'status' => $intent->status, // 'succeeded', 'requires_action', 'processing', etc.
                'payment_intent_id' => $intent->id,
                'client_secret' => $intent->client_secret,
                'error' => null,
            ];
        } catch (CardException $e) {
            $err = $e->getError();
            $pi = $err?->payment_intent;

            if ($pi && ($pi->status ?? null) === 'requires_action') {
                return [
                    'status' => 'requires_action',
                    'payment_intent_id' => $pi->id,
                    'client_secret' => $pi->client_secret ?? null,
                    'error' => 'Customer authentication (3D Secure) is required to complete this payment.',
                ];
            }

            return [
                'status' => 'failed',
                'payment_intent_id' => $pi->id ?? null,
                'client_secret' => null,
                'error' => $e->getMessage(),
            ];
        } catch (ApiErrorException $e) {
            return [
                'status' => 'failed',
                'payment_intent_id' => null,
                'client_secret' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Issue a full or partial refund for a paid Stripe PaymentIntent.
     *
     * @return array{success: bool, refund_id: ?string, status: ?string, error: ?string}
     */
    public function refundBooking(Booking $booking, ?int $amountMinor = null): array
    {
        if (! $booking->stripe_payment_intent_id) {
            return [
                'success' => false,
                'refund_id' => null,
                'status' => null,
                'error' => 'No Stripe PaymentIntent found for this booking.',
            ];
        }

        try {
            $params = ['payment_intent' => $booking->stripe_payment_intent_id];
            if ($amountMinor !== null && $amountMinor > 0) {
                $params['amount'] = $amountMinor;
            }

            $refund = $this->client()->refunds->create($params);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'error' => null,
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'refund_id' => null,
                'status' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify Stripe webhook signature and construct Event.
     *
     * @throws SignatureVerificationException
     */
    public function constructWebhookEvent(string $payload, string $signatureHeader): Event
    {
        $webhookSecret = '';
        $gateway = $this->gatewayRecord();
        if ($gateway) {
            $creds = $gateway->getCredentials();
            $webhookSecret = (string) ($creds['webhook_secret'] ?? '');
        }
        if ($webhookSecret === '') {
            $webhookSecret = (string) config('services.stripe.webhook_secret');
        }
        if ($webhookSecret === '') {
            throw new SignatureVerificationException('Stripe webhook secret is not configured.');
        }

        return Webhook::constructEvent($payload, $signatureHeader, $webhookSecret);
    }
}
