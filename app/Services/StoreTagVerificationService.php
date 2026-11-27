<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\LaundryTag;
use App\Models\LaundryTagBookingAssignment;
use App\Models\LaundryTagEvent;
use App\Models\StoreUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreTagVerificationService
{
    public function __construct(
        private readonly LaundryTagQrService $qrService,
        private readonly StorePickupDeliveryService $pickupDeliveryService,
    ) {}

    /**
     * Look up a tag by manual code or scanned QR payload, verify Store ownership & eligibility,
     * mark physical receipt verification if not yet verified, and return booking + progress.
     *
     * @return array{verified_assignment: LaundryTagBookingAssignment, booking: Booking, progress: array<string, int>}
     */
    public function lookupAndVerifyForStore(StoreUser $storeUser, string $rawCodeOrQr, ?string $method = null): array
    {
        $parsed = $this->qrService->parseAndValidateInput($rawCodeOrQr);
        $tagCode = $parsed['tag_code'];
        $verifyMethod = $method && in_array($method, ['manual', 'qr'], true)
            ? $method
            : $parsed['input_mode'];

        return DB::transaction(function () use ($storeUser, $tagCode, $parsed, $verifyMethod) {
            /** @var LaundryTag|null $tag */
            $tag = LaundryTag::where('tag_code', $tagCode)->lockForUpdate()->first();
            if (! $tag) {
                throw ValidationException::withMessages([
                    'tag_code' => 'Tag code not found.',
                ]);
            }

            if ($parsed['signature'] !== null && ! hash_equals((string) $tag->qr_signature, (string) $parsed['signature'])) {
                throw ValidationException::withMessages([
                    'tag_code' => 'QR signature verification failed. This tag QR appears invalid or tampered.',
                ]);
            }

            /** @var LaundryTagBookingAssignment|null $activeAssignment */
            $activeAssignment = LaundryTagBookingAssignment::where('tag_id', $tag->id)
                ->where('active_lock', 1)
                ->lockForUpdate()
                ->first();

            if (! $activeAssignment) {
                throw ValidationException::withMessages([
                    'tag_code' => 'This tag is not assigned to any active booking.',
                ]);
            }

            /** @var Booking $booking */
            $booking = Booking::whereKey($activeAssignment->booking_id)
                ->with(['currentStoreAssignment.store', 'currentCollectionAssignment.deliveryPerson', 'operationalItems'])
                ->lockForUpdate()
                ->firstOrFail();

            $assignedStoreId = (int) ($booking->currentStoreAssignment?->store_id ?: $booking->assigned_store_id);
            if ($assignedStoreId !== (int) $storeUser->store_id) {
                throw ValidationException::withMessages([
                    'tag_code' => 'This tag is not assigned to a booking at your Store.',
                ]);
            }

            if ($booking->cancelled_at !== null || $booking->booking_status === Booking::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'tag_code' => "Booking {$booking->reference} has been cancelled.",
                ]);
            }

            $completedStages = $booking->getCompletedOperationalStatuses();
            $isStoreReceived = in_array(Booking::STATUS_STORE_RECEIVED, $completedStages, true)
                || in_array($booking->booking_status, [
                    Booking::STATUS_STORE_PROCESSING,
                    Booking::STATUS_STORE_COMPLETE,
                    Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP,
                ], true);

            if (! $isStoreReceived) {
                throw ValidationException::withMessages([
                    'tag_code' => 'This tag is not yet eligible for Store processing. Please accept the Store Handover receipt first.',
                ]);
            }

            // Idempotently record Store physical verification (never auto-completes processing)
            if (! $activeAssignment->isVerifiedByStore()) {
                $now = now();
                $activeAssignment->update([
                    'store_id' => $storeUser->store_id,
                    'status' => LaundryTagBookingAssignment::STATUS_STORE_VERIFIED,
                    'store_verified_at' => $now,
                    'verified_by_store_user_id' => $storeUser->id,
                    'verification_method' => $verifyMethod,
                ]);

                if ($tag->status === LaundryTag::STATUS_ASSIGNED) {
                    $tag->update([
                        'status' => LaundryTag::STATUS_AT_STORE,
                        'at_store_at' => $tag->at_store_at ?? $now,
                    ]);
                }

                LaundryTagEvent::create([
                    'tag_id' => $tag->id,
                    'event_type' => LaundryTagEvent::EVENT_STORE_VERIFIED,
                    'previous_status' => $tag->status,
                    'new_status' => LaundryTag::STATUS_AT_STORE,
                    'booking_id' => $booking->id,
                    'delivery_person_id' => $tag->current_delivery_person_id,
                    'store_id' => $storeUser->store_id,
                    'actor_type' => 'store_user',
                    'actor_id' => $storeUser->id,
                    'actor_name' => $storeUser->name,
                    'reason' => "Verified at Store for booking {$booking->reference} via {$verifyMethod}",
                    'occurred_at' => $now,
                ]);
            }

            $booking->load([
                'activeTagAssignments.tag',
                'activeTagAssignments.verifiedByStoreUser',
                'activeTagAssignments.storeProcessingCompletedBy',
            ]);

            return [
                'verified_assignment' => $activeAssignment->fresh(['tag', 'verifiedByStoreUser', 'storeProcessingCompletedBy']),
                'booking' => $booking,
                'progress' => $this->getBookingTagProgress($booking),
            ];
        });
    }

    /**
     * Mark an individual bag/tag's Store processing as completed, and automatically trigger
     * Store Complete when all required tags for the booking are completed and eligible.
     *
     * @return array{
     *     assignment: LaundryTagBookingAssignment,
     *     booking: Booking,
     *     progress: array<string, int>,
     *     auto_store_complete_status: string,
     *     auto_store_complete_message: ?string
     * }
     */
    public function markTagProcessingCompleted(
        StoreUser $storeUser,
        LaundryTagBookingAssignment $assignment,
        ?string $notes = null,
        ?string $ipAddress = null
    ): array {
        return DB::transaction(function () use ($storeUser, $assignment, $notes, $ipAddress) {
            /** @var LaundryTagBookingAssignment $lockedAssignment */
            $lockedAssignment = LaundryTagBookingAssignment::whereKey($assignment->id)
                ->with('tag')
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedAssignment->active_lock !== 1) {
                throw ValidationException::withMessages([
                    'tag' => 'This tag assignment is no longer active.',
                ]);
            }

            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::whereKey($lockedAssignment->booking_id)
                ->with('currentStoreAssignment')
                ->lockForUpdate()
                ->firstOrFail();

            $assignedStoreId = (int) ($lockedBooking->currentStoreAssignment?->store_id ?: $lockedBooking->assigned_store_id);
            if ($assignedStoreId !== (int) $storeUser->store_id) {
                throw ValidationException::withMessages([
                    'store' => 'This tag is not assigned to a booking at your Store.',
                ]);
            }

            if ($lockedBooking->cancelled_at !== null || $lockedBooking->booking_status === Booking::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'booking_status' => 'Cannot complete processing for a cancelled booking.',
                ]);
            }

            if ($lockedAssignment->isStoreProcessingCompleted()) {
                throw ValidationException::withMessages([
                    'tag' => 'This tag has already been completed.',
                ]);
            }

            $completedStages = $lockedBooking->getCompletedOperationalStatuses();
            if (in_array(Booking::STATUS_STORE_COMPLETE, $completedStages, true)) {
                throw ValidationException::withMessages([
                    'booking_status' => 'This booking is already marked Store Complete.',
                ]);
            }

            if ($lockedBooking->booking_status !== Booking::STATUS_STORE_PROCESSING) {
                throw ValidationException::withMessages([
                    'booking_status' => 'Booking must be in Store Processing stage before completing tags.',
                ]);
            }

            $now = now();
            $lockedAssignment->update([
                'store_verified_at' => $lockedAssignment->store_verified_at ?? $now,
                'verified_by_store_user_id' => $lockedAssignment->verified_by_store_user_id ?? $storeUser->id,
                'status' => LaundryTagBookingAssignment::STATUS_STORE_VERIFIED,
                'is_manifest_finalized' => true,
                'store_processing_completed_at' => $now,
                'store_processing_completed_by_store_user_id' => $storeUser->id,
                'store_processing_notes' => $notes ? trim($notes) : null,
            ]);

            LaundryTagEvent::create([
                'tag_id' => $lockedAssignment->tag_id,
                'event_type' => LaundryTagEvent::EVENT_TAG_PROCESSING_COMPLETED,
                'previous_status' => $lockedAssignment->tag?->status,
                'new_status' => $lockedAssignment->tag?->status,
                'booking_id' => $lockedBooking->id,
                'store_id' => $storeUser->store_id,
                'actor_type' => 'store_user',
                'actor_id' => $storeUser->id,
                'actor_name' => $storeUser->name,
                'reason' => "Store processing completed for {$lockedAssignment->tag?->tag_code} on booking {$lockedBooking->reference}"
                    . ($notes ? " ({$notes})" : ''),
                'occurred_at' => $now,
            ]);

            $progress = $this->getBookingTagProgress($lockedBooking);
            $autoStatus = 'in_progress';
            $autoMessage = null;

            if ($progress['total'] > 0 && $progress['remaining'] === 0) {
                LaundryTagEvent::create([
                    'tag_id' => $lockedAssignment->tag_id,
                    'event_type' => LaundryTagEvent::EVENT_MANIFEST_COMPLETED,
                    'previous_status' => $lockedAssignment->tag?->status,
                    'new_status' => $lockedAssignment->tag?->status,
                    'booking_id' => $lockedBooking->id,
                    'store_id' => $storeUser->store_id,
                    'actor_type' => 'store_user',
                    'actor_id' => $storeUser->id,
                    'actor_name' => $storeUser->name,
                    'reason' => "All {$progress['total']} required tags completed for booking {$lockedBooking->reference}",
                    'occurred_at' => $now,
                ]);

                // Let ValidationException bubble up so the combined final-tag + Store Complete
                // transaction rolls back atomically if any prerequisite is missing.
                $lockedBooking = $this->pickupDeliveryService->markStoreComplete(
                    $lockedBooking,
                    $storeUser,
                    "Auto-completed after all {$progress['total']} laundry tags finished Store processing.",
                    $ipAddress
                );

                LaundryTagEvent::create([
                    'tag_id' => $lockedAssignment->tag_id,
                    'event_type' => LaundryTagEvent::EVENT_AUTO_STORE_COMPLETE,
                    'previous_status' => LaundryTag::STATUS_AT_STORE,
                    'new_status' => LaundryTag::STATUS_AT_STORE,
                    'booking_id' => $lockedBooking->id,
                    'store_id' => $storeUser->store_id,
                    'actor_type' => 'store_user',
                    'actor_id' => $storeUser->id,
                    'actor_name' => $storeUser->name,
                    'reason' => "Booking {$lockedBooking->reference} automatically marked Store Complete.",
                    'occurred_at' => now(),
                ]);

                $autoStatus = 'completed';
                $autoMessage = 'All laundry tags verified successfully. Booking marked Store Complete.';
            }

            $lockedBooking->load([
                'activeTagAssignments.tag',
                'activeTagAssignments.verifiedByStoreUser',
                'activeTagAssignments.storeProcessingCompletedBy',
                'currentStoreAssignment.store',
                'currentCollectionAssignment.deliveryPerson',
                'operationalItems',
            ]);

            return [
                'assignment' => $lockedAssignment->fresh(['tag', 'verifiedByStoreUser', 'storeProcessingCompletedBy']),
                'booking' => $lockedBooking,
                'progress' => $progress,
                'auto_store_complete_status' => $autoStatus,
                'auto_store_complete_message' => $autoMessage,
            ];
        });
    }

    /**
     * Authoritative tag completion progress for a booking.
     *
     * @return array{total: int, verified: int, completed: int, remaining: int, percent: int}
     */
    public function getBookingTagProgress(Booking $booking): array
    {
        $active = LaundryTagBookingAssignment::where('booking_id', $booking->id)
            ->where('active_lock', 1)
            ->get();

        $total = $active->count();
        $verified = $active->filter(fn (LaundryTagBookingAssignment $a) => $a->isVerifiedByStore())->count();
        $completed = $active->filter(fn (LaundryTagBookingAssignment $a) => $a->isStoreProcessingCompleted())->count();
        $remaining = max(0, $total - $completed);
        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'total' => $total,
            'verified' => $verified,
            'completed' => $completed,
            'remaining' => $remaining,
            'percent' => $percent,
        ];
    }
}
