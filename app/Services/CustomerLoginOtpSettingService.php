<?php

namespace App\Services;

use App\Models\CustomerEmailOtp;
use App\Models\HomepageContent;
use App\Models\HomepageContentVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerLoginOtpSettingService
{
    public const CACHE_KEY = 'website.customer_login_otp.setting';
    public const PAGE_SLUG = 'customer_login_otp';
    public const MODE_EMAIL = 'email';
    public const MODE_TESTING = 'testing';

    public static function defaultPayload(): array
    {
        return [
            'mode' => self::MODE_EMAIL,
        ];
    }

    public function isEnvironmentApproved(): bool
    {
        $approved = config('customer_otp.approved_environments', ['local', 'testing']);

        return app()->environment(is_array($approved) ? $approved : ['local', 'testing']);
    }

    public function isTestingConfigEnabled(): bool
    {
        return (bool) config('customer_otp.testing_allowed', false);
    }

    /**
     * Testing OTP may operate ONLY when:
     *  1. Application environment is an approved local/testing environment
     *  2. Explicit server-side configuration (CUSTOMER_TEST_OTP_ALLOWED=true) is enabled
     */
    public function isEnvironmentAuthorizedForTesting(): bool
    {
        return $this->isEnvironmentApproved() && $this->isTestingConfigEnabled();
    }

    public function getOrInitializeRecord(): HomepageContent
    {
        $record = HomepageContent::query()->where('slug', self::PAGE_SLUG)->first();
        if ($record) {
            return $record;
        }

        $defaults = self::defaultPayload();

        return HomepageContent::create([
            'slug' => self::PAGE_SLUG,
            'draft_payload' => $defaults,
            'published_payload' => $defaults,
            'current_version_number' => 1,
            'published_at' => now(),
            'last_draft_saved_at' => now(),
        ]);
    }

    public function getSelectedMode(): string
    {
        try {
            $mode = Cache::remember(self::CACHE_KEY, now()->addMinutes(30), function () {
                $record = HomepageContent::query()->where('slug', self::PAGE_SLUG)->first();
                if (! $record) {
                    $record = $this->getOrInitializeRecord();
                }

                $raw = is_array($record->published_payload) ? $record->published_payload : [];

                return ($raw['mode'] ?? self::MODE_EMAIL) === self::MODE_TESTING
                    ? self::MODE_TESTING
                    : self::MODE_EMAIL;
            });

            return $mode === self::MODE_TESTING ? self::MODE_TESTING : self::MODE_EMAIL;
        } catch (\Throwable) {
            return self::MODE_EMAIL;
        }
    }

    /**
     * Effective OTP mode enforces the mandatory server-side testing gate.
     * In production or unapproved environments, this ALWAYS returns MODE_EMAIL.
     */
    public function getEffectiveMode(): string
    {
        if ($this->getSelectedMode() === self::MODE_TESTING && $this->isEnvironmentAuthorizedForTesting()) {
            return self::MODE_TESTING;
        }

        return self::MODE_EMAIL;
    }

    public function isTestingModeActive(): bool
    {
        return $this->getEffectiveMode() === self::MODE_TESTING;
    }

    public function getTestOtpCode(): string
    {
        $code = preg_replace('/\D+/', '', (string) config('customer_otp.test_otp_code', '123456')) ?? '';

        return strlen($code) === 6 ? $code : '123456';
    }

    public function updateMode(string $mode, int $adminId): HomepageContent
    {
        if (! in_array($mode, [self::MODE_EMAIL, self::MODE_TESTING], true)) {
            throw ValidationException::withMessages([
                'mode' => ['Invalid OTP mode selected.'],
            ]);
        }

        if ($mode === self::MODE_TESTING && ! $this->isEnvironmentAuthorizedForTesting()) {
            throw ValidationException::withMessages([
                'mode' => ['Testing OTP Mode cannot be enabled in this environment. Both an approved local/testing environment and CUSTOMER_TEST_OTP_ALLOWED=true are required.'],
            ]);
        }

        return DB::transaction(function () use ($mode, $adminId) {
            $record = $this->getOrInitializeRecord();
            $locked = HomepageContent::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();

            $payload = ['mode' => $mode];
            $nextVer = (int) $locked->current_version_number + 1;
            $now = now();

            $locked->update([
                'draft_payload' => $payload,
                'published_payload' => $payload,
                'published_by_admin_id' => $adminId,
                'published_at' => $now,
                'last_draft_saved_at' => $now,
                'current_version_number' => $nextVer,
            ]);

            HomepageContentVersion::create([
                'homepage_content_id' => $locked->id,
                'version_number' => $nextVer,
                'action' => 'published',
                'section_key' => 'customer_login_otp',
                'payload' => $payload,
                'notes' => 'Customer login OTP mode set to ' . $mode,
                'created_by_admin_id' => $adminId,
                'created_at' => $now,
            ]);

            // Invalidate any pending customer login OTP challenges issued under another mode
            CustomerEmailOtp::whereNull('consumed_at')
                ->where('mode', '!=', $mode)
                ->update(['consumed_at' => $now]);

            Cache::forget(self::CACHE_KEY);

            Log::info('Customer login OTP mode updated', [
                'admin_id' => $adminId,
                'mode' => $mode,
                'environment' => app()->environment(),
            ]);

            return $locked->fresh();
        });
    }
}
