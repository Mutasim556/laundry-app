<?php

namespace Modules\Admin\App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class PaymentGateway extends Model
{
    public const ALLOWED_GATEWAYS = ['stripe', 'paypal', 'cod'];

    protected $table = 'payment_gateways';

    protected $fillable = [
        'gateway_code',
        'display_name',
        'description',
        'is_active',
        'environment',
        'encrypted_credentials',
        'public_configuration',
        'sort_order',
        'configuration_status',
        'last_tested_at',
        'last_test_status',
        'last_test_message',
    ];

    protected $hidden = [
        'encrypted_credentials',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'public_configuration' => 'array',
        'sort_order' => 'integer',
        'last_tested_at' => 'datetime',
    ];

    public function scopeAllowed(Builder $query): Builder
    {
        return $query->whereIn('gateway_code', self::ALLOWED_GATEWAYS);
    }

    public function scopeActiveOrdered(Builder $query): Builder
    {
        return $query->allowed()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function getCredentials(): array
    {
        if (empty($this->encrypted_credentials)) {
            return [];
        }

        try {
            $decoded = json_decode(Crypt::decryptString($this->encrypted_credentials), true);

            return is_array($decoded) ? $decoded : [];
        } catch (Throwable) {
            return [];
        }
    }

    public function setCredentials(array $credentials): void
    {
        $filtered = array_filter($credentials, static fn ($value) => $value !== null && $value !== '');

        $this->encrypted_credentials = $filtered !== []
            ? Crypt::encryptString(json_encode($filtered, JSON_THROW_ON_ERROR))
            : null;
    }

    public function hasSecret(string $key): bool
    {
        $credentials = $this->getCredentials();

        return ! empty($credentials[$key]);
    }

    public function getPublicValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->public_configuration ?? [], $key, $default);
    }

    public function isConfigured(): bool
    {
        $credentials = $this->getCredentials();

        return match ($this->gateway_code) {
            'stripe' => ! empty($this->getPublicValue('publishable_key')) && ! empty($credentials['secret_key']),
            'paypal' => ! empty($this->getPublicValue('client_id')) && ! empty($credentials['client_secret']),
            'cod' => true,
            default => false,
        };
    }

    public function isUsable(): bool
    {
        return in_array($this->gateway_code, self::ALLOWED_GATEWAYS, true)
            && (bool) $this->is_active
            && $this->isConfigured();
    }

    public function syncConfigurationStatus(): void
    {
        $this->configuration_status = $this->isConfigured()
            ? ($this->is_active ? 'active' : 'configured')
            : 'unconfigured';

        if (! $this->isConfigured() && $this->gateway_code !== 'cod') {
            $this->is_active = false;
        }
    }

    public function getMaskedCredentials(): array
    {
        return match ($this->gateway_code) {
            'stripe' => [
                'secret_key' => $this->hasSecret('secret_key') ? '********' : '',
                'webhook_secret' => $this->hasSecret('webhook_secret') ? '********' : '',
                'has_secret_key' => $this->hasSecret('secret_key'),
                'has_webhook_secret' => $this->hasSecret('webhook_secret'),
            ],
            'paypal' => [
                'client_secret' => $this->hasSecret('client_secret') ? '********' : '',
                'has_client_secret' => $this->hasSecret('client_secret'),
            ],
            default => [],
        };
    }
}
