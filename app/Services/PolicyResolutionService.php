<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingCustomerPayment;
use App\Models\RefundReturnPolicy;
use App\Models\RefundReturnPolicyVersion;

class PolicyResolutionService
{
    public function resolveForBooking(Booking $booking): ?RefundReturnPolicyVersion
    {
        if ($booking->policy_version_id) {
            return $booking->policyVersion;
        }

        $now = now();
        $storeId = $booking->assigned_store_id;
        $countryCode = strtoupper((string) ($booking->address_snapshot['country_code'] ?? ''));

        $candidates = RefundReturnPolicyVersion::query()
            ->with('policy')
            ->where('status', RefundReturnPolicyVersion::STATUS_PUBLISHED)
            ->where(fn ($q) => $q->whereNull('effective_at')->orWhere('effective_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $now))
            ->whereHas('policy', fn ($q) => $q->where('is_active', true))
            ->orderByDesc('effective_at')
            ->orderByDesc('id')
            ->get();

        // Priority: store-specific > country-specific > global default
        if ($storeId) {
            $storeMatch = $candidates->first(fn ($v) => (int) ($v->policy?->store_id) === (int) $storeId);
            if ($storeMatch) {
                return $storeMatch;
            }
        }

        if ($countryCode !== '') {
            $countryMatch = $candidates->first(fn ($v) => strtoupper((string) ($v->policy?->country_code)) === $countryCode && ! $v->policy?->store_id);
            if ($countryMatch) {
                return $countryMatch;
            }
        }

        return $candidates->first(fn ($v) => ! $v->policy?->store_id && empty($v->policy?->country_code))
            ?? $candidates->first();
    }

    public function getEffectiveRules(Booking $booking): array
    {
        $version = $this->resolveForBooking($booking);
        $defaults = RefundReturnPolicyVersion::defaultRules();

        if (! $version || ! is_array($version->rules_json)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $version->rules_json);
    }

    /**
     * Determine eligible self-service actions on /my-orders/{reference}.
     *
     * @return array<string, mixed>
     */
    public function getCustomerEligibility(Booking $booking): array
    {
        $version = $this->resolveForBooking($booking);
        $rules = $this->getEffectiveRules($booking);
        $enabled = (bool) ($rules['policy_enabled'] ?? true);

        $isCancelled = $booking->booking_status === Booking::STATUS_CANCELLED || $booking->cancelled_at !== null;
        $isCompleted = $booking->booking_status === Booking::STATUS_COMPLETED;
        $confirmedPaidMinor = (int) $booking->customerPayments()
            ->where('status', BookingCustomerPayment::STATUS_CONFIRMED)
            ->sum('amount_minor');

        $allowedCancelStages = (array) ($rules['cancel_allowed_before_stages'] ?? []);
        $canCancel = $enabled
            && (bool) ($rules['customer_cancel_enabled'] ?? true)
            && ! $isCancelled
            && ! $isCompleted
            && in_array($booking->booking_status, $allowedCancelStages, true);

        $canRequestRefund = $enabled
            && $confirmedPaidMinor > 0
            && ($isCancelled || in_array($booking->booking_status, $allowedCancelStages, true));

        $canRequestRewash = $enabled
            && (bool) ($rules['rewash_enabled'] ?? true)
            && $isCompleted
            && ! $isCancelled;

        $canReportClaim = $enabled
            && (bool) ($rules['claims_enabled'] ?? true)
            && ! $isCancelled
            && in_array($booking->booking_status, [
                Booking::STATUS_DELIVERY_MAN_PICKED_UP,
                Booking::STATUS_STORE_RECEIVED,
                Booking::STATUS_STORE_PROCESSING,
                Booking::STATUS_STORE_COMPLETE,
                Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP,
                Booking::STATUS_ON_DELIVERY,
                Booking::STATUS_COMPLETED,
            ], true);

        return [
            'policy_version' => $version,
            'rules' => $rules,
            'confirmed_paid_minor' => $confirmedPaidMinor,
            'can_request_cancellation' => $canCancel,
            'can_request_refund' => $canRequestRefund,
            'can_request_rewash' => $canRequestRewash,
            'can_report_claim' => $canReportClaim,
            'has_any_self_service' => $canCancel || $canRequestRefund || $canRequestRewash || $canReportClaim,
            'public_text' => $version?->public_text ?: 'Standard customer protection, rewash, and refund review rules apply. Contact support if self-service options are unavailable.',
        ];
    }

    public function ensureDefaultDraftPolicy(?int $adminId = null): RefundReturnPolicy
    {
        $existing = RefundReturnPolicy::query()->where('code', 'DEFAULT-GLOBAL')->first();
        if ($existing) {
            return $existing;
        }

        $policy = RefundReturnPolicy::create([
            'code' => 'DEFAULT-GLOBAL',
            'name' => 'Default Global Refund, Rewash & Claim Policy',
            'country_code' => null,
            'is_active' => true,
            'created_by_admin_id' => $adminId,
        ]);

        RefundReturnPolicyVersion::create([
            'policy_id' => $policy->id,
            'version_number' => 1,
            'status' => RefundReturnPolicyVersion::STATUS_DRAFT,
            'rules_json' => RefundReturnPolicyVersion::defaultRules(),
            'public_text' => 'Draft policy awaiting legal and business review before publication.',
            'legal_disclaimer_note' => 'DRAFT FOR REVIEW: Do not rely on unreviewed legal text. Statutory consumer protections always apply.',
            'effective_at' => now(),
        ]);

        return $policy;
    }
}
