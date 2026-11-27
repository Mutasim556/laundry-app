<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingStoreHandover;
use App\Models\LaundryTag;
use App\Models\LaundryTagBookingAssignment;
use App\Models\LaundryTagEvent;
use App\Models\StoreUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Models\DeliveryPerson;

class BookingTagAssignmentService
{
    public function __construct(
        private readonly LaundryTagQrService $qrService,
    ) {}

    /**
     * Verify tag ownership/eligibility and attach a physical tag to a booking at collection/handover.
     */
    public function verifyAndAssignToCollectionBooking(
        Booking $booking,
        DeliveryPerson $deliveryPerson,
        string $rawCodeOrQr,
        ?string $packageLabel = null,
        ?string $method = null,
        ?string $idempotencyKey = null
    ): LaundryTagBookingAssignment {
        $parsed = $this->qrService->parseAndValidateInput($rawCodeOrQr);
        $tagCode = $parsed['tag_code'];
        $inputMethod = $method && in_array($method, ['manual', 'qr'], true)
            ? $method
            : $parsed['input_mode'];

        return DB::transaction(function () use ($booking, $deliveryPerson, $tagCode, $parsed, $packageLabel, $inputMethod, $idempotencyKey) {
            if ($idempotencyKey) {
                $existingIdempotent = LaundryTagBookingAssignment::where('idempotency_key', $idempotencyKey)
                    ->with(['tag', 'booking'])
                    ->first();
                if ($existingIdempotent) {
                    return $existingIdempotent;
                }
            }

            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($lockedBooking->cancelled_at !== null || $lockedBooking->booking_status === Booking::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'tag_code' => 'Cannot attach laundry tags to a cancelled booking.',
                ]);
            }

            if (! $deliveryPerson->isEligibleForAssignment()) {
                throw ValidationException::withMessages([
                    'tag_code' => 'Your Delivery Person account is currently inactive.',
                ]);
            }

            $collectionAssignment = $lockedBooking->currentCollectionAssignment()->lockForUpdate()->first();
            if (! $collectionAssignment || (int) $collectionAssignment->delivery_person_id !== (int) $deliveryPerson->id) {
                throw ValidationException::withMessages([
                    'tag_code' => 'Only the assigned Collection Delivery Person can attach laundry tags to this booking.',
                ]);
            }

            if ($lockedBooking->collection_otp_verified_at === null) {
                throw ValidationException::withMessages([
                    'tag_code' => 'Customer Collection OTP verification must be completed before tagging items for Store handover.',
                ]);
            }

            if (! in_array(Booking::STATUS_DELIVERY_MAN_PICKED_UP, $lockedBooking->getCompletedOperationalStatuses(), true)) {
                throw ValidationException::withMessages([
                    'tag_code' => 'Mark the booking as Picked Up from Customer before assigning physical tags for Store handover.',
                ]);
            }

            $currentHandover = $lockedBooking->currentStoreHandover()->lockForUpdate()->first();
            if ($currentHandover && $currentHandover->status === BookingStoreHandover::STATUS_ACCEPTED) {
                throw ValidationException::withMessages([
                    'tag_code' => 'Store has already accepted this booking handover. Contact Admin for tag adjustments.',
                ]);
            }

            if (in_array(Booking::STATUS_STORE_COMPLETE, $lockedBooking->getCompletedOperationalStatuses(), true)
                || in_array($lockedBooking->booking_status, [
                    Booking::STATUS_STORE_COMPLETE,
                    Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP,
                    Booking::STATUS_ON_DELIVERY,
                    Booking::STATUS_COMPLETED,
                ], true)) {
                throw ValidationException::withMessages([
                    'tag_code' => 'Cannot add laundry tags to a booking that has already reached Store Complete or later.',
                ]);
            }

            $storeId = $lockedBooking->currentStoreAssignment?->store_id ?: $lockedBooking->assigned_store_id;

            /** @var LaundryTag|null $lockedTag */
            $lockedTag = LaundryTag::where('tag_code', $tagCode)->lockForUpdate()->first();
            if (! $lockedTag) {
                throw ValidationException::withMessages([
                    'tag_code' => "Laundry tag {$tagCode} was not found in the system.",
                ]);
            }

            if ($parsed['signature'] !== null && ! hash_equals((string) $lockedTag->qr_signature, (string) $parsed['signature'])) {
                throw ValidationException::withMessages([
                    'tag_code' => 'Scanned QR signature does not match the registered tag.',
                ]);
            }

            if (in_array($lockedTag->status, [LaundryTag::STATUS_LOST, LaundryTag::STATUS_DAMAGED, LaundryTag::STATUS_VOIDED, LaundryTag::STATUS_RETIRED], true)) {
                throw ValidationException::withMessages([
                    'tag_code' => "Tag {$tagCode} cannot be used because its status is {$lockedTag->status_label}.",
                ]);
            }

            if ((int) $lockedTag->current_delivery_person_id !== (int) $deliveryPerson->id) {
                throw ValidationException::withMessages([
                    'tag_code' => "Tag {$tagCode} is not allocated to your inventory.",
                ]);
            }

            $existingActive = LaundryTagBookingAssignment::where('tag_id', $lockedTag->id)
                ->where('active_lock', 1)
                ->lockForUpdate()
                ->first();

            if ($existingActive) {
                if ((int) $existingActive->booking_id === (int) $lockedBooking->id) {
                    throw ValidationException::withMessages([
                        'tag_code' => "Tag {$tagCode} is already attached to this booking.",
                    ]);
                }

                $otherRef = $existingActive->booking?->reference ?? "#{$existingActive->booking_id}";
                throw ValidationException::withMessages([
                    'tag_code' => "Tag {$tagCode} is already actively assigned to booking {$otherRef}.",
                ]);
            }

            if ($lockedTag->status !== LaundryTag::STATUS_ALLOCATED || $lockedTag->current_booking_id !== null) {
                throw ValidationException::withMessages([
                    'tag_code' => "Tag {$tagCode} is not available for assignment (current status: {$lockedTag->status_label}).",
                ]);
            }

            $now = now();
            $existingCount = LaundryTagBookingAssignment::where('booking_id', $lockedBooking->id)
                ->where('active_lock', 1)
                ->count();
            $resolvedLabel = trim((string) ($packageLabel ?? '')) ?: ('Bag ' . ($existingCount + 1));

            $assignment = LaundryTagBookingAssignment::create([
                'tag_id' => $lockedTag->id,
                'booking_id' => $lockedBooking->id,
                'delivery_assignment_id' => $collectionAssignment->id,
                'handover_id' => ($currentHandover && $currentHandover->status === BookingStoreHandover::STATUS_PENDING) ? $currentHandover->id : null,
                'delivery_person_id' => $deliveryPerson->id,
                'store_id' => $storeId,
                'package_label' => $resolvedLabel,
                'active_lock' => 1,
                'status' => LaundryTagBookingAssignment::STATUS_ACTIVE,
                'assigned_method' => $inputMethod,
                'assigned_at' => $now,
                'assigned_by_dp_id' => $deliveryPerson->id,
                'idempotency_key' => $idempotencyKey ?: null,
            ]);

            $prevStatus = $lockedTag->status;
            $lockedTag->update([
                'status' => LaundryTag::STATUS_ASSIGNED,
                'current_booking_id' => $lockedBooking->id,
                'assigned_at' => $now,
            ]);

            LaundryTagEvent::create([
                'tag_id' => $lockedTag->id,
                'event_type' => LaundryTagEvent::EVENT_ASSIGNED_TO_BOOKING,
                'previous_status' => $prevStatus,
                'new_status' => LaundryTag::STATUS_ASSIGNED,
                'booking_id' => $lockedBooking->id,
                'delivery_person_id' => $deliveryPerson->id,
                'store_id' => $storeId,
                'actor_type' => 'delivery_person',
                'actor_id' => $deliveryPerson->id,
                'actor_name' => $deliveryPerson->full_name,
                'reason' => "Attached to booking {$lockedBooking->reference} ({$resolvedLabel}) via {$inputMethod}",
                'metadata' => [
                    'package_label' => $resolvedLabel,
                    'method' => $inputMethod,
                    'booking_reference' => $lockedBooking->reference,
                ],
                'occurred_at' => $now,
            ]);

            BookingAuditLog::create([
                'booking_id' => $lockedBooking->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $deliveryPerson->id,
                'actor_name' => $deliveryPerson->full_name,
                'action' => 'laundry_tag_assigned',
                'summary' => "Assigned physical laundry tag {$lockedTag->tag_code} ({$resolvedLabel}) via {$inputMethod}.",
                'changes' => [
                    'tag_id' => $lockedTag->id,
                    'tag_code' => $lockedTag->tag_code,
                    'package_label' => $resolvedLabel,
                    'method' => $inputMethod,
                ],
            ]);

            return $assignment->load(['tag', 'booking', 'verifiedByStoreUser']);
        });
    }

    /**
     * Unassign a tag from a booking before Store acceptance (Collection DP only).
     */
    public function unassignFromCollectionBooking(
        Booking $booking,
        LaundryTag $tag,
        DeliveryPerson $deliveryPerson,
        string $reason = 'Removed before store handover confirmation'
    ): LaundryTag {
        return DB::transaction(function () use ($booking, $tag, $deliveryPerson, $reason) {
            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $collectionAssignment = $lockedBooking->currentCollectionAssignment()->lockForUpdate()->first();

            if (! $collectionAssignment || (int) $collectionAssignment->delivery_person_id !== (int) $deliveryPerson->id) {
                throw ValidationException::withMessages([
                    'tag_id' => 'Only the assigned Collection Delivery Person can remove tags from this booking.',
                ]);
            }

            $currentHandover = $lockedBooking->currentStoreHandover()->lockForUpdate()->first();
            if ($currentHandover && $currentHandover->status === BookingStoreHandover::STATUS_ACCEPTED) {
                throw ValidationException::withMessages([
                    'tag_id' => 'Store has already accepted this handover; tags can no longer be removed by the Delivery Person.',
                ]);
            }

            $activeAssignment = LaundryTagBookingAssignment::where('tag_id', $tag->id)
                ->where('booking_id', $lockedBooking->id)
                ->where('active_lock', 1)
                ->lockForUpdate()
                ->first();

            if (! $activeAssignment) {
                throw ValidationException::withMessages([
                    'tag_id' => "Tag {$tag->tag_code} is not actively assigned to this booking.",
                ]);
            }

            if ($activeAssignment->isVerifiedByStore()) {
                throw ValidationException::withMessages([
                    'tag_id' => "Tag {$tag->tag_code} has already been verified by Store staff and cannot be removed.",
                ]);
            }

            /** @var LaundryTag $lockedTag */
            $lockedTag = LaundryTag::whereKey($tag->id)->lockForUpdate()->firstOrFail();
            $now = now();
            $prevStatus = $lockedTag->status;

            $activeAssignment->update([
                'active_lock' => null,
                'status' => LaundryTagBookingAssignment::STATUS_UNASSIGNED,
                'released_at' => $now,
                'release_reason' => trim($reason) ?: 'Removed before store handover confirmation',
            ]);

            $lockedTag->update([
                'status' => LaundryTag::STATUS_ALLOCATED,
                'current_booking_id' => null,
                'assigned_at' => null,
            ]);

            LaundryTagEvent::create([
                'tag_id' => $lockedTag->id,
                'event_type' => LaundryTagEvent::EVENT_UNASSIGNED_FROM_BOOKING,
                'previous_status' => $prevStatus,
                'new_status' => LaundryTag::STATUS_ALLOCATED,
                'booking_id' => $lockedBooking->id,
                'delivery_person_id' => $deliveryPerson->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $deliveryPerson->id,
                'actor_name' => $deliveryPerson->full_name,
                'reason' => trim($reason) ?: 'Removed before store handover confirmation',
                'occurred_at' => $now,
            ]);

            return $lockedTag->fresh();
        });
    }

    /**
     * Store staff verify an expected laundry tag on physical receipt (manual entry or QR scan).
     * Never completes Store Handover automatically; Store must still confirm Accept & Confirm Received.
     */
    public function verifyTagForStoreHandover(
        Booking $booking,
        StoreUser $storeUser,
        string $rawCodeOrQr,
        ?string $method = null,
        ?string $notes = null
    ): LaundryTagBookingAssignment {
        $parsed = $this->qrService->parseAndValidateInput($rawCodeOrQr);
        $tagCode = $parsed['tag_code'];
        $verifyMethod = $method && in_array($method, ['manual', 'qr'], true)
            ? $method
            : $parsed['input_mode'];

        $mismatchAudit = null;

        try {
            return DB::transaction(function () use ($booking, $storeUser, $tagCode, $parsed, $verifyMethod, $notes, &$mismatchAudit) {
                /** @var Booking $lockedBooking */
                $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

                $assignedStoreId = (int) ($lockedBooking->currentStoreAssignment?->store_id ?: $lockedBooking->assigned_store_id);
                if ($assignedStoreId !== (int) $storeUser->store_id) {
                    throw ValidationException::withMessages([
                        'tag_code' => 'This booking does not belong to your store.',
                    ]);
                }

                /** @var LaundryTag|null $lockedTag */
                $lockedTag = LaundryTag::where('tag_code', $tagCode)->lockForUpdate()->first();
                if (! $lockedTag) {
                    throw ValidationException::withMessages([
                        'tag_code' => "Tag {$tagCode} was not found in the system.",
                    ]);
                }

                if ($parsed['signature'] !== null && ! hash_equals((string) $lockedTag->qr_signature, (string) $parsed['signature'])) {
                    throw ValidationException::withMessages([
                        'tag_code' => 'Scanned QR signature does not match the registered tag.',
                    ]);
                }

                $activeAssignment = LaundryTagBookingAssignment::where('tag_id', $lockedTag->id)
                    ->where('active_lock', 1)
                    ->lockForUpdate()
                    ->first();

                if (! $activeAssignment || (int) $activeAssignment->booking_id !== (int) $lockedBooking->id) {
                    $mismatchAudit = [
                        'tag_id' => $lockedTag->id,
                        'event_type' => LaundryTagEvent::EVENT_HANDOVER_VERIFICATION_ISSUE,
                        'previous_status' => $lockedTag->status,
                        'new_status' => $lockedTag->status,
                        'booking_id' => $lockedBooking->id,
                        'store_id' => $storeUser->store_id,
                        'actor_type' => 'store_user',
                        'actor_id' => $storeUser->id,
                        'actor_name' => $storeUser->name,
                        'reason' => "Tag {$tagCode} scanned/entered for booking {$lockedBooking->reference}, but it does not belong to this booking.",
                        'occurred_at' => now(),
                    ];

                    throw ValidationException::withMessages([
                        'tag_code' => "Tag {$tagCode} is NOT assigned to booking {$lockedBooking->reference}.",
                    ]);
                }

                if ($activeAssignment->isVerifiedByStore()) {
                    return $activeAssignment->load(['tag', 'verifiedByStoreUser']);
                }

                $now = now();
                $handover = $lockedBooking->currentStoreHandover()->lockForUpdate()->first();

                $activeAssignment->update([
                    'handover_id' => $activeAssignment->handover_id ?? $handover?->id,
                    'store_id' => $storeUser->store_id,
                    'status' => LaundryTagBookingAssignment::STATUS_STORE_VERIFIED,
                    'store_verified_at' => $now,
                    'verified_by_store_user_id' => $storeUser->id,
                    'verification_method' => $verifyMethod,
                    'verification_notes' => $notes ? trim($notes) : null,
                ]);

                $prevStatus = $lockedTag->status;
                $lockedTag->update([
                    'status' => LaundryTag::STATUS_AT_STORE,
                    'at_store_at' => $lockedTag->at_store_at ?? $now,
                ]);

                LaundryTagEvent::create([
                    'tag_id' => $lockedTag->id,
                    'event_type' => LaundryTagEvent::EVENT_STORE_VERIFIED,
                    'previous_status' => $prevStatus,
                    'new_status' => LaundryTag::STATUS_AT_STORE,
                    'booking_id' => $lockedBooking->id,
                    'delivery_person_id' => $lockedTag->current_delivery_person_id,
                    'store_id' => $storeUser->store_id,
                    'actor_type' => 'store_user',
                    'actor_id' => $storeUser->id,
                    'actor_name' => $storeUser->name,
                    'reason' => "Verified at store for booking {$lockedBooking->reference} via {$verifyMethod}",
                    'metadata' => [
                        'verification_method' => $verifyMethod,
                        'notes' => $notes ? trim($notes) : null,
                    ],
                    'occurred_at' => $now,
                ]);

                return $activeAssignment->fresh(['tag', 'verifiedByStoreUser']);
            });
        } catch (ValidationException $e) {
            if ($mismatchAudit !== null) {
                LaundryTagEvent::create($mismatchAudit);
            }
            throw $e;
        }
    }

    /**
     * Link active booking tags to a newly created StoreHandover request and write event history.
     *
     * @return array<int, string> Tag codes attached to the handover
     */
    public function attachActiveTagsToHandoverRequest(Booking $booking, BookingStoreHandover $handover, DeliveryPerson $deliveryPerson): array
    {
        $activeAssignments = LaundryTagBookingAssignment::where('booking_id', $booking->id)
            ->where('active_lock', 1)
            ->with('tag')
            ->lockForUpdate()
            ->get();

        $codes = [];
        $now = now();

        foreach ($activeAssignments as $asg) {
            $asg->update([
                'handover_id' => $handover->id,
                'store_id' => $handover->store_id,
            ]);

            if ($asg->tag) {
                $codes[] = $asg->tag->tag_code;
                LaundryTagEvent::create([
                    'tag_id' => $asg->tag->id,
                    'event_type' => LaundryTagEvent::EVENT_HANDED_OVER_REQUESTED,
                    'previous_status' => $asg->tag->status,
                    'new_status' => $asg->tag->status,
                    'booking_id' => $booking->id,
                    'delivery_person_id' => $deliveryPerson->id,
                    'store_id' => $handover->store_id,
                    'actor_type' => 'delivery_person',
                    'actor_id' => $deliveryPerson->id,
                    'actor_name' => $deliveryPerson->full_name,
                    'reason' => "Included in Store Handover request {$handover->request_reference}",
                    'occurred_at' => $now,
                ]);
            }
        }

        return $codes;
    }

    /**
     * Transition any remaining assigned tags on a booking to AT_STORE when handover is accepted.
     */
    public function syncTagsOnHandoverAccepted(Booking $booking, BookingStoreHandover $handover, StoreUser $storeUser): void
    {
        $activeAssignments = LaundryTagBookingAssignment::where('booking_id', $booking->id)
            ->where('active_lock', 1)
            ->with('tag')
            ->lockForUpdate()
            ->get();

        $now = now();
        foreach ($activeAssignments as $asg) {
            if (! $asg->handover_id) {
                $asg->update([
                    'handover_id' => $handover->id,
                    'store_id' => $handover->store_id,
                    'is_manifest_finalized' => true,
                ]);
            } else {
                $asg->update(['is_manifest_finalized' => true]);
            }

            $tag = $asg->tag;
            if ($tag && $tag->status === LaundryTag::STATUS_ASSIGNED) {
                $tag->update([
                    'status' => LaundryTag::STATUS_AT_STORE,
                    'at_store_at' => $tag->at_store_at ?? $now,
                ]);

                LaundryTagEvent::create([
                    'tag_id' => $tag->id,
                    'event_type' => LaundryTagEvent::EVENT_MARKED_AT_STORE,
                    'previous_status' => LaundryTag::STATUS_ASSIGNED,
                    'new_status' => LaundryTag::STATUS_AT_STORE,
                    'booking_id' => $booking->id,
                    'delivery_person_id' => $tag->current_delivery_person_id,
                    'store_id' => $handover->store_id,
                    'actor_type' => 'store_user',
                    'actor_id' => $storeUser->id,
                    'actor_name' => $storeUser->name,
                    'reason' => "Handover {$handover->request_reference} accepted by store",
                    'occurred_at' => $now,
                ]);
            }
        }
    }

    /**
     * Mark active booking tags as In Final Delivery when Final Delivery Person collects from Store.
     */
    public function syncTagsOnFinalDeliveryCollected(Booking $booking, DeliveryPerson $person): void
    {
        $activeAssignments = LaundryTagBookingAssignment::where('booking_id', $booking->id)
            ->where('active_lock', 1)
            ->with('tag')
            ->lockForUpdate()
            ->get();

        $now = now();
        foreach ($activeAssignments as $asg) {
            $tag = $asg->tag;
            if ($tag && in_array($tag->status, [LaundryTag::STATUS_ASSIGNED, LaundryTag::STATUS_AT_STORE], true)) {
                $prevStatus = $tag->status;
                $tag->update([
                    'status' => LaundryTag::STATUS_IN_FINAL_DELIVERY,
                ]);

                LaundryTagEvent::create([
                    'tag_id' => $tag->id,
                    'event_type' => LaundryTagEvent::EVENT_IN_FINAL_DELIVERY,
                    'previous_status' => $prevStatus,
                    'new_status' => LaundryTag::STATUS_IN_FINAL_DELIVERY,
                    'booking_id' => $booking->id,
                    'delivery_person_id' => $person->id,
                    'store_id' => $asg->store_id,
                    'actor_type' => 'delivery_person',
                    'actor_id' => $person->id,
                    'actor_name' => $person->full_name,
                    'reason' => "Final Delivery Person collected parcel for booking {$booking->reference}",
                    'occurred_at' => $now,
                ]);
            }
        }
    }

    /**
     * Retire tags on completed booking (never auto-recycled back into available inventory).
     */
    public function retireTagsOnBookingCompleted(Booking $booking, DeliveryPerson $person): void
    {
        $activeAssignments = LaundryTagBookingAssignment::where('booking_id', $booking->id)
            ->where('active_lock', 1)
            ->with('tag')
            ->lockForUpdate()
            ->get();

        $now = now();
        foreach ($activeAssignments as $asg) {
            $asg->update([
                'active_lock' => null,
                'status' => LaundryTagBookingAssignment::STATUS_COMPLETED,
                'released_at' => $now,
                'release_reason' => "Booking {$booking->reference} delivered & completed",
            ]);

            $tag = $asg->tag;
            if ($tag && ! $tag->isExceptionStatus()) {
                $prevStatus = $tag->status;
                $tag->update([
                    'status' => LaundryTag::STATUS_RETIRED,
                    'retired_at' => $now,
                    'current_booking_id' => null,
                    'current_delivery_person_id' => null,
                    'status_reason' => "Completed lifecycle on booking {$booking->reference}",
                ]);

                LaundryTagEvent::create([
                    'tag_id' => $tag->id,
                    'event_type' => LaundryTagEvent::EVENT_RETIRED,
                    'previous_status' => $prevStatus,
                    'new_status' => LaundryTag::STATUS_RETIRED,
                    'booking_id' => $booking->id,
                    'delivery_person_id' => $person->id,
                    'store_id' => $asg->store_id,
                    'actor_type' => 'delivery_person',
                    'actor_id' => $person->id,
                    'actor_name' => $person->full_name,
                    'reason' => "Single-use/completed retirement upon delivery completion of {$booking->reference}",
                    'occurred_at' => $now,
                ]);
            }
        }
    }
}
