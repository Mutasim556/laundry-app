<?php

namespace App\Services;

use App\Mail\BookingNotificationMail;
use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingOperationalItem;
use App\Models\ClaimDeliveryJob;
use App\Models\ClaimEvidence;
use App\Models\ClaimStatusHistory;
use App\Models\CustomerServiceClaim;
use App\Models\CustomerServiceClaimItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;

class ClaimWorkflowService
{
    public function __construct(
        protected PolicyResolutionService $policyService,
        protected CurrencyService $currencyService,
        protected StoreAccountService $storeAccountService
    ) {}

    public function submitClaim(
        Booking $booking,
        array $data,
        array $uploadedFiles = [],
        ?int $userId = null,
        ?string $ipAddress = null
    ): CustomerServiceClaim {
        $eligibility = $this->policyService->getCustomerEligibility($booking);
        $rules = $eligibility['rules'];
        $claimType = (string) ($data['claim_type'] ?? '');

        if (! in_array($claimType, [
            CustomerServiceClaim::TYPE_CANCELLATION_REFUND,
            CustomerServiceClaim::TYPE_REWASH,
            CustomerServiceClaim::TYPE_DAMAGE_MISSING,
        ], true)) {
            throw ValidationException::withMessages(['claim_type' => ['Invalid request type.']]);
        }

        if ($claimType === CustomerServiceClaim::TYPE_CANCELLATION_REFUND && ! $eligibility['can_request_cancellation'] && ! $eligibility['can_request_refund']) {
            throw ValidationException::withMessages(['claim_type' => ['Self-service cancellation/refund is not eligible at this stage. Please contact support.']]);
        }
        if ($claimType === CustomerServiceClaim::TYPE_REWASH && ! $eligibility['can_request_rewash']) {
            throw ValidationException::withMessages(['claim_type' => ['Rewash requests are only available for completed orders.']]);
        }
        if ($claimType === CustomerServiceClaim::TYPE_DAMAGE_MISSING && ! $eligibility['can_report_claim']) {
            throw ValidationException::withMessages(['claim_type' => ['Damage or missing item claims cannot be submitted for this booking status.']]);
        }

        if ($claimType === CustomerServiceClaim::TYPE_DAMAGE_MISSING && ! empty($rules['evidence_required']) && empty($uploadedFiles)) {
            throw ValidationException::withMessages(['evidence' => ['Supporting photo evidence is required for damage or missing item claims.']]);
        }

        $claim = DB::transaction(function () use ($booking, $data, $uploadedFiles, $userId, $ipAddress, $eligibility, $claimType) {
            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $policyVersion = $eligibility['policy_version'];
            if (! $locked->policy_version_id && $policyVersion) {
                $locked->policy_version_id = $policyVersion->id;
                $locked->save();
            }

            $now = now();
            $requestedMinor = max(0, (int) ($data['requested_amount_minor'] ?? 0));

            $claim = CustomerServiceClaim::create([
                'case_reference' => 'CLM-' . $now->format('Y') . '-' . strtoupper(Str::random(6)),
                'booking_id' => $locked->id,
                'user_id' => $userId ?? $locked->user_id,
                'store_id' => $locked->assigned_store_id,
                'policy_version_id' => $locked->policy_version_id ?? $policyVersion?->id,
                'claim_type' => $claimType,
                'reason_code' => trim((string) $data['reason_code']),
                'description' => trim((string) $data['description']),
                'preferred_resolution' => $data['preferred_resolution'] ?? null,
                'status' => CustomerServiceClaim::STATUS_SUBMITTED,
                'requested_amount_minor' => $requestedMinor,
                'approved_amount_minor' => 0,
                'currency' => strtoupper((string) ($locked->currency ?: $this->currencyService->code())),
                'liability_party' => 'none',
            ]);

            $claim->case_reference = 'CLM-' . $now->format('Y') . '-' . str_pad((string) $claim->id, 6, '0', STR_PAD_LEFT);
            $claim->save();

            // Attach affected operational items if selected
            $itemIds = array_filter((array) ($data['operational_item_ids'] ?? []));
            $qty = max(1, (int) ($data['affected_quantity'] ?? 1));
            if (! empty($itemIds)) {
                $opItems = BookingOperationalItem::query()
                    ->where('booking_id', $locked->id)
                    ->whereIn('id', $itemIds)
                    ->get();

                foreach ($opItems as $opItem) {
                    CustomerServiceClaimItem::create([
                        'claim_id' => $claim->id,
                        'booking_operational_item_id' => $opItem->id,
                        'service_name_snapshot' => $opItem->item_name ? "{$opItem->service_name} ({$opItem->item_name})" : $opItem->service_name,
                        'affected_quantity' => min($qty, max(1, (int) $opItem->quantity)),
                        'claimed_amount_minor' => (int) $opItem->computeLineTotalMinor(),
                    ]);
                }
            }

            // Store private evidence files on local disk
            foreach ($uploadedFiles as $file) {
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    continue;
                }
                $path = $file->store("claim-evidence/{$claim->id}", 'local');
                ClaimEvidence::create([
                    'claim_id' => $claim->id,
                    'uploaded_by_type' => 'customer',
                    'uploaded_by_id' => $userId ?? $locked->user_id,
                    'disk' => 'local',
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => (int) $file->getSize(),
                ]);
            }

            ClaimStatusHistory::create([
                'claim_id' => $claim->id,
                'from_status' => null,
                'to_status' => CustomerServiceClaim::STATUS_SUBMITTED,
                'actor_type' => 'customer',
                'actor_id' => $userId ?? $locked->user_id,
                'actor_name' => $locked->customer_name,
                'note' => 'Customer submitted ' . $claim->type_label . ' request.',
            ]);

            BookingAuditLog::create([
                'booking_id' => $locked->id,
                'actor_type' => 'customer',
                'actor_id' => $userId ?? $locked->user_id,
                'actor_name' => $locked->customer_name,
                'action' => 'customer_claim_submitted',
                'summary' => "Customer submitted {$claim->case_reference} ({$claim->type_label}) for {$locked->reference}",
                'changes' => [
                    'claim_id' => $claim->id,
                    'case_reference' => $claim->case_reference,
                    'claim_type' => $claim->claim_type,
                    'reason_code' => $claim->reason_code,
                    'requested_amount_minor' => $requestedMinor,
                ],
                'ip_address' => $ipAddress,
            ]);

            return $claim->fresh(['items', 'evidence', 'policyVersion']);
        });

        $this->notifyCustomer($booking, "Request {$claim->case_reference} Received", "We have received your {$claim->type_label} request ({$claim->case_reference}) for booking {$booking->reference}.");

        return $claim;
    }

    public function transitionClaim(
        CustomerServiceClaim $claim,
        string $targetStatus,
        int $adminId,
        ?string $note = null,
        ?int $approvedAmountMinor = null,
        ?string $ipAddress = null
    ): CustomerServiceClaim {
        $cleanNote = trim((string) $note);
        if ($targetStatus === CustomerServiceClaim::STATUS_REJECTED && $cleanNote === '') {
            throw ValidationException::withMessages(['decision_notes' => ['A clear reason is required when rejecting a claim.']]);
        }

        $updated = DB::transaction(function () use ($claim, $targetStatus, $adminId, $cleanNote, $approvedAmountMinor, $ipAddress) {
            /** @var CustomerServiceClaim $locked */
            $locked = CustomerServiceClaim::query()->whereKey($claim->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === $targetStatus) {
                return $locked;
            }

            if (! $locked->canTransitionTo($targetStatus)) {
                throw ValidationException::withMessages([
                    'status' => ["Invalid claim transition from {$locked->status} to {$targetStatus}."],
                ]);
            }

            $oldStatus = $locked->status;
            $locked->status = $targetStatus;
            $locked->reviewed_by_admin_id = $adminId;
            $locked->reviewed_at = now();
            if ($cleanNote !== '') {
                $locked->decision_notes = $cleanNote;
            }
            if ($approvedAmountMinor !== null) {
                $locked->approved_amount_minor = max(0, $approvedAmountMinor);
            }
            $locked->save();

            ClaimStatusHistory::create([
                'claim_id' => $locked->id,
                'from_status' => $oldStatus,
                'to_status' => $targetStatus,
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'actor_name' => 'Admin #' . $adminId,
                'note' => $cleanNote !== '' ? $cleanNote : "Status updated to {$targetStatus}",
            ]);

            BookingAuditLog::create([
                'booking_id' => $locked->booking_id,
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'actor_name' => 'Admin #' . $adminId,
                'action' => 'customer_claim_status_updated',
                'summary' => "Claim {$locked->case_reference} moved from {$oldStatus} to {$targetStatus}",
                'changes' => [
                    'claim_id' => $locked->id,
                    'from_status' => $oldStatus,
                    'to_status' => $targetStatus,
                    'approved_amount_minor' => $locked->approved_amount_minor,
                    'note' => $cleanNote !== '' ? $cleanNote : null,
                ],
                'ip_address' => $ipAddress,
            ]);

            return $locked->fresh();
        });

        if ($updated->booking) {
            $this->notifyCustomer(
                $updated->booking,
                "Update on Request {$updated->case_reference}",
                "Your request {$updated->case_reference} for booking {$updated->booking->reference} is now: {$updated->status_label}."
            );
        }

        return $updated;
    }

    public function scheduleRewashLogistics(
        CustomerServiceClaim $claim,
        int $adminId,
        ?int $collectionDpId = null,
        ?int $deliveryDpId = null,
        int $rateMinor = 0
    ): ClaimDeliveryJob {
        return DB::transaction(function () use ($claim, $adminId, $collectionDpId, $deliveryDpId, $rateMinor) {
            /** @var CustomerServiceClaim $locked */
            $locked = CustomerServiceClaim::query()->with(['booking.assignedStore'])->whereKey($claim->id)->lockForUpdate()->firstOrFail();
            $booking = $locked->booking;
            $store = $booking->assignedStore;
            $customerAddr = is_array($booking->address_snapshot) ? ($booking->address_snapshot['full_address'] ?? $booking->address_snapshot['address_line_1'] ?? 'Customer Address') : 'Customer Address';
            $storeAddr = $store ? ($store->full_address ?: $store->address_line_1 ?: $store->name) : 'Assigned Store';
            $cur = strtoupper((string) ($booking->currency ?: $this->currencyService->code()));

            $colJob = ClaimDeliveryJob::create([
                'job_reference' => 'RWC-' . now()->format('Y') . '-' . strtoupper(Str::random(6)),
                'claim_id' => $locked->id,
                'booking_id' => $booking->id,
                'store_id' => $store?->id,
                'delivery_person_id' => $collectionDpId,
                'job_type' => ClaimDeliveryJob::TYPE_REWASH_COLLECTION,
                'status' => $collectionDpId ? ClaimDeliveryJob::STATUS_ASSIGNED : ClaimDeliveryJob::STATUS_PENDING,
                'pickup_location' => $customerAddr,
                'destination_location' => $storeAddr,
                'rate_minor' => max(0, $rateMinor),
                'currency' => $cur,
                'assigned_at' => $collectionDpId ? now() : null,
                'assigned_by_admin_id' => $adminId,
            ]);

            if ($deliveryDpId) {
                ClaimDeliveryJob::create([
                    'job_reference' => 'RWD-' . now()->format('Y') . '-' . strtoupper(Str::random(6)),
                    'claim_id' => $locked->id,
                    'booking_id' => $booking->id,
                    'store_id' => $store?->id,
                    'delivery_person_id' => $deliveryDpId,
                    'job_type' => ClaimDeliveryJob::TYPE_REWASH_DELIVERY,
                    'status' => ClaimDeliveryJob::STATUS_ASSIGNED,
                    'pickup_location' => $storeAddr,
                    'destination_location' => $customerAddr,
                    'rate_minor' => max(0, $rateMinor),
                    'currency' => $cur,
                    'assigned_at' => now(),
                    'assigned_by_admin_id' => $adminId,
                ]);
            }

            $oldStatus = $locked->status;
            if (! in_array($locked->status, [CustomerServiceClaim::STATUS_REJECTED, CustomerServiceClaim::STATUS_CLOSED], true)) {
                $locked->status = CustomerServiceClaim::STATUS_REWASH_IN_PROGRESS;
            }
            $locked->rewash_status = 'scheduled';
            $locked->save();

            ClaimStatusHistory::create([
                'claim_id' => $locked->id,
                'from_status' => $oldStatus,
                'to_status' => $locked->status,
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'actor_name' => 'Admin #' . $adminId,
                'note' => "Scheduled rewash pickup/return logistics ({$colJob->job_reference}).",
            ]);

            return $colJob;
        });
    }

    public function applyLiabilityAdjustment(
        CustomerServiceClaim $claim,
        string $liabilityParty,
        int $storeLiabilityMinor,
        int $dpLiabilityMinor,
        ?int $dpId,
        string $reason,
        int $adminId
    ): CustomerServiceClaim {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages(['reason' => ['A reason is required for responsibility adjustments.']]);
        }

        return DB::transaction(function () use ($claim, $liabilityParty, $storeLiabilityMinor, $dpLiabilityMinor, $dpId, $cleanReason, $adminId) {
            /** @var CustomerServiceClaim $locked */
            $locked = CustomerServiceClaim::query()->with(['booking', 'store'])->whereKey($claim->id)->lockForUpdate()->firstOrFail();
            $cur = strtoupper((string) ($locked->currency ?: $this->currencyService->code()));

            $locked->liability_party = $liabilityParty;
            $locked->store_liability_minor = max(0, $storeLiabilityMinor);
            $locked->dp_liability_minor = max(0, $dpLiabilityMinor);
            $locked->save();

            if ($storeLiabilityMinor > 0 && $locked->store) {
                $this->storeAccountService->createManualAdjustment(
                    $locked->store,
                    $cur,
                    $storeLiabilityMinor,
                    \App\Models\StoreTransaction::DIRECTION_DEBIT,
                    "Claim {$locked->case_reference} liability: {$cleanReason}",
                    $adminId,
                    $locked->booking,
                    "claim-store-liability:{$locked->id}:" . now()->timestamp
                );
            }

            if ($dpLiabilityMinor > 0 && $dpId) {
                $dp = DeliveryPerson::findOrFail($dpId);
                DeliveryPersonLedgerEntry::create([
                    'entry_reference' => 'DPL-CLM-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4)),
                    'delivery_person_id' => $dp->id,
                    'booking_id' => $locked->booking_id,
                    'transaction_type' => DeliveryPersonLedgerEntry::TYPE_AUTHORIZED_CORRECTION,
                    'direction' => 'debit',
                    'amount_minor' => $dpLiabilityMinor,
                    'currency' => $cur,
                    'notes' => "Claim {$locked->case_reference} authorized adjustment: {$cleanReason}",
                    'created_by_admin_id' => $adminId,
                    'occurred_at' => now(),
                ]);
            }

            ClaimStatusHistory::create([
                'claim_id' => $locked->id,
                'from_status' => $locked->status,
                'to_status' => $locked->status,
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'actor_name' => 'Admin #' . $adminId,
                'note' => "Recorded liability ({$liabilityParty}): {$cleanReason}",
            ]);

            return $locked->fresh();
        });
    }

    protected function notifyCustomer(Booking $booking, string $subject, string $message): void
    {
        try {
            if (! empty($booking->customer_email)) {
                Mail::to($booking->customer_email)->send(new BookingNotificationMail($booking, $subject, $message));
            }
        } catch (\Throwable $e) {
            Log::warning('Claim notification email skipped', ['booking_id' => $booking->id, 'error' => $e->getMessage()]);
        }
    }
}
