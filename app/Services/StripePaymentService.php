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

    /**
     * Create a hosted Stripe Checkout Session for the booking's verified outstanding amount.
     *
     * @return array{id: string, session_id: string, checkout_url: string, approve_url: string, payment_intent_id: ?string, status: string, payment_status: string}
     */
    public function createCheckoutSession(
        Booking $booking,
        int $amountMinor,
        string $currency,
        string $successUrl,
        string $cancelUrl,
        ?string $idempotencyKey = null
    ): array {
        if ($amountMinor <= 0) {
            throw new \RuntimeException('Stripe Checkout amount must be greater than zero.');
        }

        $currencyLower = strtolower($currency);
        $params = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $booking->id,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currencyLower,
                    'unit_amount' => $amountMinor,
                    'product_data' => [
                        'name' => 'Laundry Order ' . ($booking->reference ?? $booking->booking_reference ?? ('#' . $booking->id)),
                    ],
                ],
            ]],
            'metadata' => [
                'booking_id' => (string) $booking->id,
                'booking_reference' => (string) ($booking->reference ?? $booking->booking_reference ?? ''),
                'amount_minor' => (string) $amountMinor,
                'currency' => strtoupper($currency),
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'booking_id' => (string) $booking->id,
                    'booking_reference' => (string) ($booking->reference ?? $booking->booking_reference ?? ''),
                ],
            ],
        ];

        if (! empty($booking->stripe_customer_id)) {
            $params['customer'] = (string) $booking->stripe_customer_id;
        } elseif (! empty($booking->customer_email)) {
            $params['customer_email'] = (string) $booking->customer_email;
        }

        $opts = $idempotencyKey ? ['idempotency_key' => $idempotencyKey] : [];
        $session = $this->client()->checkout->sessions->create($params, $opts);

        return [
            'id' => (string) $session->id,
            'session_id' => (string) $session->id,
            'checkout_url' => (string) ($session->url ?? ''),
            'approve_url' => (string) ($session->url ?? ''),
            'payment_intent_id' => isset($session->payment_intent) ? (string) $session->payment_intent : null,
            'status' => (string) ($session->status ?? 'open'),
            'payment_status' => (string) ($session->payment_status ?? 'unpaid'),
        ];
    }

    /**
     * Retrieve a Stripe Checkout Session from Stripe API to verify its authoritative status.
     */
    public function retrieveCheckoutSession(string $sessionId): object
    {
        return $this->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent'],
        ]);
    }

    /**
     * Create a Stripe PaymentIntent for the booking's verified outstanding amount.
     *
     * @return array{id: string, client_secret: string, status: string, amount_minor: int, currency: string}
     */
    public function createPaymentIntent(
        int $amountMinor,
        string $currency,
        Booking $booking,
        ?string $idempotencyKey = null
    ): array {
        if ($amountMinor <= 0) {
            throw new \RuntimeException('Stripe PaymentIntent amount must be greater than zero.');
        }

        $params = [
            'amount' => $amountMinor,
            'currency' => strtolower($currency),
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
            'description' => 'Laundry Order ' . ($booking->reference ?? $booking->booking_reference ?? ('#' . $booking->id)),
            'metadata' => [
                'booking_id' => (string) $booking->id,
                'booking_reference' => (string) ($booking->reference ?? $booking->booking_reference ?? ''),
            ],
        ];

        if (! empty($booking->stripe_customer_id)) {
            $params['customer'] = (string) $booking->stripe_customer_id;
        }

        $opts = $idempotencyKey ? ['idempotency_key' => $idempotencyKey] : [];
        $intent = $this->client()->paymentIntents->create($params, $opts);

        return [
            'id' => (string) $intent->id,
            'client_secret' => (string) ($intent->client_secret ?? ''),
            'status' => (string) ($intent->status ?? ''),
            'amount_minor' => (int) ($intent->amount ?? $amountMinor),
            'currency' => strtoupper((string) ($intent->currency ?? $currency)),
        ];
    }

    /**
     * Retrieve an existing PaymentIntent from Stripe API.
     */
    public function retrievePaymentIntent(string $paymentIntentId): object
    {
        return $this->client()->paymentIntents->retrieve($paymentIntentId);
    }

    /**
     * Retrieve actual Stripe Charge & BalanceTransaction fee details for a PaymentIntent or Checkout Session.
     *
     * @return array{
     *   payment_intent_id: string,
     *   charge_id: string,
     *   balance_transaction_id: string,
     *   amount_minor: int,
     *   fee_minor: int,
     *   net_minor: int,
     *   currency: string,
     *   fee_details: array<int, array<string, mixed>>
     * }|null
     */
    public function retrieveBalanceTransactionForPaymentReference(string $reference): ?array
    {
        $ref = trim($reference);
        if ($ref === '') {
            return null;
        }

        $client = $this->client();
        $piId = $ref;

        if (str_starts_with($ref, 'cs_')) {
            $session = $client->checkout->sessions->retrieve($ref);
            $piId = is_object($session->payment_intent ?? null)
                ? (string) ($session->payment_intent->id ?? '')
                : (string) ($session->payment_intent ?? '');
            if ($piId === '') {
                return null;
            }
        }

        if (! str_starts_with($piId, 'pi_')) {
            return null;
        }

        $intent = $client->paymentIntents->retrieve($piId, [
            'expand' => ['latest_charge.balance_transaction'],
        ]);

        $charge = $intent->latest_charge ?? null;
        if (! is_object($charge) || empty($charge->id)) {
            return null;
        }

        $bt = $charge->balance_transaction ?? null;
        if (is_string($bt) && $bt !== '') {
            $bt = $client->balanceTransactions->retrieve($bt);
        }

        if (! is_object($bt) || empty($bt->id)) {
            return null;
        }

        $feeDetails = [];
        if (! empty($bt->fee_details) && is_iterable($bt->fee_details)) {
            foreach ($bt->fee_details as $fd) {
                $feeDetails[] = [
                    'type' => (string) ($fd->type ?? ''),
                    'description' => (string) ($fd->description ?? ''),
                    'amount' => (int) ($fd->amount ?? 0),
                    'currency' => strtoupper((string) ($fd->currency ?? $bt->currency ?? '')),
                ];
            }
        }

        return [
            'payment_intent_id' => (string) $intent->id,
            'charge_id' => (string) $charge->id,
            'balance_transaction_id' => (string) $bt->id,
            'amount_minor' => (int) ($bt->amount ?? 0),
            'fee_minor' => (int) ($bt->fee ?? 0),
            'net_minor' => (int) ($bt->net ?? 0),
            'currency' => strtoupper((string) ($bt->currency ?? '')),
            'fee_details' => $feeDetails,
        ];
    }

    /**
     * Create a real Stripe refund for a PaymentIntent or Charge with idempotency protection.
     *
     * @return array{id: string, status: string, amount_minor: int, currency: string}
     */
    public function createRefund(
        string $paymentIntentOrChargeId,
        int $amountMinor,
        string $idempotencyKey,
        string $reason = 'requested_by_customer'
    ): array {
        $params = [
            'amount' => $amountMinor,
            'reason' => in_array($reason, ['duplicate', 'fraudulent', 'requested_by_customer'], true) ? $reason : 'requested_by_customer',
        ];

        if (str_starts_with($paymentIntentOrChargeId, 'ch_')) {
            $params['charge'] = $paymentIntentOrChargeId;
        } else {
            $params['payment_intent'] = $paymentIntentOrChargeId;
        }

        $refund = $this->client()->refunds->create($params, ['idempotency_key' => $idempotencyKey]);

        return [
            'id' => (string) $refund->id,
            'status' => (string) ($refund->status ?? 'pending'),
            'amount_minor' => (int) ($refund->amount ?? $amountMinor),
            'currency' => strtoupper((string) ($refund->currency ?? '')),
        ];
    }
}
