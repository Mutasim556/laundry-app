<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Admin\App\Models\PaymentGateway;

class PayPalPaymentService
{
    protected function gatewayRecord(): ?PaymentGateway
    {
        try {
            if (! Schema::hasTable('payment_gateways')) {
                return null;
            }

            return PaymentGateway::where('gateway_code', 'paypal')->first();
        } catch (\Throwable) {
            return null;
        }
    }

    public function isConfigured(): bool
    {
        $gateway = $this->gatewayRecord();

        return $gateway ? $gateway->isConfigured() : false;
    }

    public function isActive(): bool
    {
        $gateway = $this->gatewayRecord();

        return $gateway ? $gateway->isUsable() : false;
    }

    public function environment(): string
    {
        $gateway = $this->gatewayRecord();

        return ($gateway && $gateway->environment === 'live') ? 'live' : 'sandbox';
    }

    public function baseUrl(): string
    {
        return $this->environment() === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function clientId(): ?string
    {
        $gateway = $this->gatewayRecord();

        return $gateway ? (string) $gateway->getPublicValue('client_id', '') : null;
    }

    public function clientSecret(): ?string
    {
        $gateway = $this->gatewayRecord();
        if (! $gateway) {
            return null;
        }

        $creds = $gateway->getCredentials();

        return ! empty($creds['client_secret']) ? (string) $creds['client_secret'] : null;
    }

    public function currency(): string
    {
        return strtoupper(app(CurrencyService::class)->code());
    }

    public function webhookId(): ?string
    {
        $gateway = $this->gatewayRecord();

        return $gateway ? (string) $gateway->getPublicValue('webhook_id', '') : null;
    }

    public function getAccessToken(): string
    {
        $clientId = (string) ($this->clientId() ?? '');
        $clientSecret = (string) ($this->clientSecret() ?? '');

        if ($clientId === '' || $clientSecret === '') {
            throw new \RuntimeException('PayPal credentials are not configured.');
        }

        $response = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->timeout(15)
            ->post($this->baseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('PayPal OAuth failed: '.$response->status());
        }

        $token = (string) $response->json('access_token', '');
        if ($token === '') {
            throw new \RuntimeException('PayPal OAuth did not return an access token.');
        }

        return $token;
    }

    public function testConnection(): array
    {
        try {
            $this->getAccessToken();

            return [
                'success' => true,
                'message' => 'PayPal OAuth connection verified ('.$this->environment().').',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'PayPal connection failed: '.$e->getMessage(),
            ];
        }
    }

    public function createOrder(Booking $booking, string $returnUrl, string $cancelUrl): array
    {
        $currencyService = app(CurrencyService::class);
        $currency = strtoupper((string) ($booking->currency ?: $this->currency()));
        if (! $currencyService->isPayPalSupported($currency)) {
            throw new \RuntimeException("Currency {$currency} is not supported by PayPal.");
        }

        $minor = (int) ($booking->final_amount_minor ?? $booking->estimated_amount_minor ?? 0);
        $amount = $currencyService->fromMinor($minor, $currency);
        if ($amount <= 0) {
            throw new \RuntimeException('Booking amount must be greater than zero to create a PayPal order.');
        }

        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->timeout(20)
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $booking->booking_reference,
                        'custom_id' => (string) $booking->id,
                        'description' => 'Laundry Order '.$booking->booking_reference,
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', ''),
                        ],
                    ],
                ],
                'application_context' => [
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                    'user_action' => 'PAY_NOW',
                ],
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Unable to create PayPal order: '.$response->body());
        }

        $data = $response->json();
        $approveUrl = collect($data['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        return [
            'order_id' => (string) ($data['id'] ?? ''),
            'status' => (string) ($data['status'] ?? ''),
            'approve_url' => $approveUrl,
        ];
    }

    public function captureOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->timeout(20)
            ->post($this->baseUrl()."/v2/checkout/orders/{$orderId}/capture", (object) []);

        if (! $response->successful()) {
            throw new \RuntimeException('Unable to capture PayPal order: '.$response->body());
        }

        $data = $response->json();
        $captureId = data_get($data, 'purchase_units.0.payments.captures.0.id');

        return [
            'order_id' => (string) ($data['id'] ?? $orderId),
            'capture_id' => $captureId ? (string) $captureId : null,
            'status' => (string) ($data['status'] ?? ''),
        ];
    }

    public function verifyWebhookSignature(array $headers, array $payload): bool
    {
        $webhookId = (string) ($this->webhookId() ?? '');
        if ($webhookId === '' || ! $this->isConfigured()) {
            return false;
        }

        try {
            $token = $this->getAccessToken();
            $response = Http::withToken($token)
                ->timeout(15)
                ->post($this->baseUrl().'/v1/notifications/verify-webhook-signature', [
                    'auth_algo' => $headers['paypal-auth-algo'] ?? '',
                    'cert_url' => $headers['paypal-cert-url'] ?? '',
                    'transmission_id' => $headers['paypal-transmission-id'] ?? '',
                    'transmission_sig' => $headers['paypal-transmission-sig'] ?? '',
                    'transmission_time' => $headers['paypal-transmission-time'] ?? '',
                    'webhook_id' => $webhookId,
                    'webhook_event' => $payload,
                ]);

            return $response->successful()
                && strtoupper((string) $response->json('verification_status')) === 'SUCCESS';
        } catch (\Throwable) {
            return false;
        }
    }
}
