<?php

namespace App\Services;

use App\Mail\BookingNotificationMail;
use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingStorePickupRequest;
use App\Models\StoreUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryAssignmentEvent;
use Modules\Admin\Models\DeliveryPerson;

class StorePickupDeliveryService
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected BookingFinancialSettlementService $settlementService
    ) {}

    public function markStoreComplete(
        Booking $booking,
        StoreUser $storeUser,
        ?string $note = null,
        ?string $ipAddress = null
    ): Booking {
        return DB::transaction(function () use ($booking, $storeUser, $note, $ipAddress) {
            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing(['currentStoreAssignment.store', 'operationalItems']);

            if (! $locked->currentStoreAssignment || (int) $locked->currentStoreAssignment->store_id !== (int) $storeUser->store_id) {
                throw ValidationException::withMessages([
                    'store' => ['You are not authorized to complete store processing for this booking.'],
                ]);
            }

            if ($locked->cancelled_at !== null || $locked->booking_status === Booking::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'booking_status' => ['Cancelled bookings cannot be marked Store Complete.'],
                ]);
            }

            $completedStages = $locked->getCompletedOperationalStatuses();
            if (in_array(Booking::STATUS_STORE_COMPLETE, $completedStages, true)) {
                throw ValidationException::withMessages([
                    'booking_status' => ['Store Complete has already been confirmed for this booking.'],
                ]);
            }

            if (! in_array($locked->booking_status, [Booking::STATUS_STORE_PROCESSING, Booking::STATUS_STORE_COMPLETE], true)) {
                throw ValidationException::withMessages([
                    'booking_status' => ['Booking must be in Store Processing before marking Store Complete.'],
                ]);
            }

            if (! in_array(Booking::STATUS_FINALIZED, $completedStages, true) || ! $locked->isInvoiceEligible($this->currencyService)) {
                throw ValidationException::withMessages([
                    'pricing' => ['Operational pricing must be finalized before marking Store Complete.'],
                ]);
            }

            $nowIso = now()->toIso8601String();
            $stages = is_array($locked->completed_operational_stages) ? $locked->completed_operational_stages : [];

            if (! array_key_exists(Booking::STATUS_STORE_PROCESSING, $stages)) {
                $stages[Booking::STATUS_STORE_PROCESSING] = [
                    'completed_at' => $nowIso,
                    'store_user_id' => $storeUser->id,
                    'store_id' => $storeUser->store_id,
                ];
            }

            $stages[Booking::STATUS_STORE_COMPLETE] = [
                'completed_at' => $nowIso,
                'store_user_id' => $storeUser->id,
                'store_id' => $storeUser->store_id,
                'note' => $note ? trim($note) : null,
            ];

            $oldStatus = $locked->booking_status;
            $locked->completed_operational_stages = $stages;
            $locked->booking_status = Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP;
            $locked->version = ((int) $locked->version) + 1;
            $locked->save();

            BookingAuditLog::create([
                'booking_id' => $locked->id,
                'actor_type' => 'store_user',
                'actor_id' => $storeUser->id,
                'actor_name' => $storeUser->name ?: $storeUser->email,
                'action' => 'store_complete_confirmed',
                'summary' => "Store {$storeUser->store?->name} confirmed Store Complete for {$locked->reference}",
                'changes' => [
                    'previous_status' => $oldStatus,
                    'new_status' => Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP,
                    'store_id' => $storeUser->store_id,
                    'store_user_id' => $storeUser->id,
                    'completed_at' => $nowIso,
                    'note' => $note ? trim($note) : null,
                ],
                'ip_address' => $ipAddress,
            ]);

            return $locked->fresh();
        });
    }

    public function requestStorePickup(
        Booking $booking,
        StoreUser $storeUser,
        ?string $note = null,
        ?string $ipAddress = null
    ): BookingStorePickupRequest {
        $pickupRequest = DB::transaction(function () use ($booking, $storeUser, $note, $ipAddress) {
            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing([
                'currentStoreAssignment.store',
                'currentFinalDeliveryAssignment.deliveryPerson',
            ]);

            if (! $locked->currentStoreAssignment || (int) $locked->currentStoreAssignment->store_id !== (int) $storeUser->store_id) {
                throw ValidationException::withMessages([
                    'store' => ['You are not authorized to request pickup for this booking.'],
                ]);
            }

            if ($locked->cancelled_at !== null || $locked->booking_status === Booking::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'booking_status' => ['Cannot request pickup for a cancelled booking.'],
                ]);
            }

            $completedStages = $locked->getCompletedOperationalStatuses();
            if (! in_array(Booking::STATUS_STORE_COMPLETE, $completedStages, true)) {
                throw ValidationException::withMessages([
                    'booking_status' => ['Mark Store Complete before requesting a Store Pickup.'],
                ]);
            }

            $finalAssignment = $locked->currentFinalDeliveryAssignment;
            if (! $finalAssignment) {
                throw ValidationException::withMessages([
                    'delivery_assignment' => ['Awaiting Admin assignment of a Final Delivery Person.'],
                ]);
            }

            $person = $finalAssignment->deliveryPerson;
            if (! $person || $person->trashed() || ! $person->isEligibleForAssignment()) {
                throw ValidationException::withMessages([
                    'delivery_person' => ['Assigned Final Delivery Person is no longer active or eligible.'],
                ]);
            }

            $duplicateActive = BookingStorePickupRequest::query()
                ->where('booking_id', $locked->id)
                ->where('delivery_assignment_id', $finalAssignment->id)
                ->whereIn('status', [
                    BookingStorePickupRequest::STATUS_PENDING,
                    BookingStorePickupRequest::STATUS_ACCEPTED,
                    BookingStorePickupRequest::STATUS_COLLECTED,
                    BookingStorePickupRequest::STATUS_COMPLETED,
                ])
                ->lockForUpdate()
                ->exists();

            if ($duplicateActive) {
                throw ValidationException::withMessages([
                    'pickup_request' => ['An active or completed store pickup request already exists for this assignment.'],
                ]);
            }

            $now = now();
            $req = BookingStorePickupRequest::create([
                'request_reference' => 'SPR-' . $now->format('Y') . '-' . strtoupper(Str::random(6)),
                'booking_id' => $locked->id,
                'store_id' => $storeUser->store_id,
                'delivery_assignment_id' => $finalAssignment->id,
                'delivery_person_id' => $person->id,
                'status' => BookingStorePickupRequest::STATUS_PENDING,
                'requested_by_store_user_id' => $storeUser->id,
                'requested_at' => $now,
                'request_note' => $note ? trim($note) : null,
                'booking_version' => (int) $locked->version,
                'idempotency_key' => "spr:{$locked->id}:asg:{$finalAssignment->id}:" . Str::uuid(),
            ]);

            $req->request_reference = 'SPR-' . $now->format('Y') . '-' . str_pad((string) $req->id, 6, '0', STR_PAD_LEFT);
            $req->save();

            BookingAuditLog::create([
                'booking_id' => $locked->id,
                'actor_type' => 'store_user',
                'actor_id' => $storeUser->id,
                'actor_name' => $storeUser->name ?: $storeUser->email,
                'action' => 'store_pickup_requested',
                'summary' => "Store requested pickup ({$req->request_reference}) from {$person->full_name} for {$locked->reference}",
                'changes' => [
                    'pickup_request_id' => $req->id,
                    'request_reference' => $req->request_reference,
                    'delivery_assignment_id' => $finalAssignment->id,
                    'delivery_person_id' => $person->id,
                    'status' => BookingStorePickupRequest::STATUS_PENDING,
                    'requested_at' => $now->toIso8601String(),
                ],
                'ip_address' => $ipAddress,
            ]);

            return $req->fresh(['booking', 'store', 'deliveryPerson', 'deliveryAssignment']);
        });

        $this->notifyPersonIfConfigured($pickupRequest);

        return $pickupRequest;
    }

    public function acceptPickupRequest(
        BookingStorePickupRequest $pickupRequest,
        DeliveryPerson $person,
        ?string $ipAddress = null
    ): BookingStorePickupRequest {
        return DB::transaction(function () use ($pickupRequest, $person, $ipAddress) {
            /** @var BookingStorePickupRequest $lockedReq */
            $lockedReq = BookingStorePickupRequest::query()->whereKey($pickupRequest->id)->lockForUpdate()->firstOrFail();

            if ((int) $lockedReq->delivery_person_id !== (int) $person->id) {
                throw ValidationException::withMessages([
                    'delivery_person' => ['You are not authorized to respond to this pickup request.'],
                ]);
            }

            if ($lockedReq->status !== BookingStorePickupRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => ['This pickup request is no longer pending (current status: ' . $lockedReq->getStatusLabel() . ').'],
                ]);
            }

            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()->whereKey($lockedReq->booking_id)->lockForUpdate()->firstOrFail();
            if ($lockedBooking->cancelled_at !== null || $lockedBooking->booking_status === Booking::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'booking_status' => ['Cancelled bookings cannot be accepted for pickup.'],
                ]);
            }

            if (! in_array(Booking::STATUS_STORE_COMPLETE, $lockedBooking->getCompletedOperationalStatuses(), true)) {
                throw ValidationException::withMessages([
                    'booking_status' => ['Store processing is not marked complete yet.'],
                ]);
            }

            /** @var DeliveryAssignment $lockedAsg */
            $lockedAsg = DeliveryAssignment::query()->whereKey($lockedReq->delivery_assignment_id)->lockForUpdate()->firstOrFail();
            $currentFinal = $lockedBooking->currentFinalDeliveryAssignment()->first();
            if (
                in_array($lockedAsg->status, [DeliveryAssignment::STATUS_CANCELLED, DeliveryAssignment::STATUS_FAILED], true)
                || ! $currentFinal
                || (int) $currentFinal->id !== (int) $lockedAsg->id
            ) {
                throw ValidationException::withMessages([
                    'delivery_assignment' => ['This pickup request is stale because the Final Delivery Person assignment has changed.'],
                ]);
            }

            $now = now();
            $lockedReq->update([
                'status' => BookingStorePickupRequest::STATUS_ACCEPTED,
                'accepted_at' => $now,
                'responded_at' => $now,
            ]);

            if ($lockedAsg->status === DeliveryAssignment::STATUS_ASSIGNED) {
                $oldAsgStatus = $lockedAsg->status;
                $lockedAsg->update([
                    'status' => DeliveryAssignment::STATUS_ACCEPTED,
                    'accepted_at' => $lockedAsg->accepted_at ?? $now,
                ]);

                DeliveryAssignmentEvent::create([
                    'delivery_assignment_id' => $lockedAsg->id,
                    'delivery_person_id' => $person->id,
                    'booking_id' => $lockedBooking->id,
                    'event_type' => 'status_accepted',
                    'from_status' => $oldAsgStatus,
                    'to_status' => DeliveryAssignment::STATUS_ACCEPTED,
                    'title' => 'Store pickup request accepted by Delivery Person',
                    'description' => "{$person->full_name} accepted store pickup request {$lockedReq->request_reference}.",
                    'occurred_at' => $now,
                ]);
            }

            BookingAuditLog::create([
                'booking_id' => $lockedBooking->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $person->id,
                'actor_name' => $person->full_name,
                'action' => 'store_pickup_request_accepted',
                'summary' => "{$person->full_name} accepted store pickup request {$lockedReq->request_reference} for {$lockedBooking->reference}",
                'changes' => [
                    'pickup_request_id' => $lockedReq->id,
                    'request_reference' => $lockedReq->request_reference,
                    'previous_status' => BookingStorePickupRequest::STATUS_PENDING,
                    'new_status' => BookingStorePickupRequest::STATUS_ACCEPTED,
                    'accepted_at' => $now->toIso8601String(),
                ],
                'ip_address' => $ipAddress,
            ]);

            return $lockedReq->fresh(['booking', 'store', 'deliveryPerson', 'deliveryAssignment']);
        });
    }

    public function declinePickupRequest(
        BookingStorePickupRequest $pickupRequest,
        DeliveryPerson $person,
        string $reason,
        ?string $ipAddress = null
    ): BookingStorePickupRequest {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages([
                'decline_reason' => ['Please provide a reason for declining this store pickup request.'],
            ]);
        }

        return DB::transaction(function () use ($pickupRequest, $person, $cleanReason, $ipAddress) {
            /** @var BookingStorePickupRequest $lockedReq */
            $lockedReq = BookingStorePickupRequest::query()->whereKey($pickupRequest->id)->lockForUpdate()->firstOrFail();

            if ((int) $lockedReq->delivery_person_id !== (int) $person->id) {
                throw ValidationException::withMessages([
                    'delivery_person' => ['You are not authorized to respond to this pickup request.'],
                ]);
            }

            if ($lockedReq->status !== BookingStorePickupRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => ['Only pending pickup requests can be declined.'],
                ]);
            }

            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()->whereKey($lockedReq->booking_id)->lockForUpdate()->firstOrFail();
            /** @var DeliveryAssignment $lockedAsg */
            $lockedAsg = DeliveryAssignment::query()->whereKey($lockedReq->delivery_assignment_id)->lockForUpdate()->firstOrFail();

            $now = now();
            $lockedReq->update([
                'status' => BookingStorePickupRequest::STATUS_DECLINED,
                'declined_at' => $now,
                'responded_at' => $now,
                'decline_reason' => $cleanReason,
            ]);

            if (! in_array($lockedAsg->status, [
                DeliveryAssignment::STATUS_PICKED_UP,
                DeliveryAssignment::STATUS_IN_TRANSIT,
                DeliveryAssignment::STATUS_HANDED_OVER,
                DeliveryAssignment::STATUS_COMPLETED,
                DeliveryAssignment::STATUS_CANCELLED,
            ], true)) {
                $oldAsgStatus = $lockedAsg->status;
                $lockedAsg->update([
                    'status' => DeliveryAssignment::STATUS_FAILED,
                    'failed_at' => $now,
                    'earnings_status' => 'not_payable',
                    'cancellation_reason' => "Declined by Delivery Person: {$cleanReason}",
                ]);

                DeliveryAssignmentEvent::create([
                    'delivery_assignment_id' => $lockedAsg->id,
                    'delivery_person_id' => $person->id,
                    'booking_id' => $lockedBooking->id,
                    'event_type' => 'store_pickup_declined',
                    'from_status' => $oldAsgStatus,
                    'to_status' => DeliveryAssignment::STATUS_FAILED,
                    'title' => 'Store pickup request declined by Delivery Person',
                    'description' => "{$person->full_name} declined {$lockedReq->request_reference}: {$cleanReason}",
                    'occurred_at' => $now,
                ]);
            }

            BookingAuditLog::create([
                'booking_id' => $lockedBooking->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $person->id,
                'actor_name' => $person->full_name,
                'action' => 'store_pickup_request_declined',
                'summary' => "{$person->full_name} declined store pickup request {$lockedReq->request_reference} for {$lockedBooking->reference}",
                'changes' => [
                    'pickup_request_id' => $lockedReq->id,
                    'request_reference' => $lockedReq->request_reference,
                    'previous_status' => BookingStorePickupRequest::STATUS_PENDING,
                    'new_status' => BookingStorePickupRequest::STATUS_DECLINED,
                    'decline_reason' => $cleanReason,
                    'declined_at' => $now->toIso8601String(),
                ],
                'ip_address' => $ipAddress,
            ]);

            return $lockedReq->fresh(['booking', 'store', 'deliveryPerson', 'deliveryAssignment']);
        });
    }

    public function confirmCollectedFromStore(
        Booking $booking,
        DeliveryPerson $person,
        ?string $notes = null,
        ?string $ipAddress = null
    ): Booking {
        return DB::transaction(function () use ($booking, $person, $notes, $ipAddress) {
            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($lockedBooking->cancelled_at !== null || $lockedBooking->booking_status === Booking::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'booking_status' => ['Cancelled bookings cannot be collected from store.'],
                ]);
            }

            if (! in_array(Booking::STATUS_STORE_COMPLETE, $lockedBooking->getCompletedOperationalStatuses(), true)) {
                throw ValidationException::withMessages([
                    'booking_status' => ['Store Complete must be confirmed before collecting from store.'],
                ]);
            }

            $finalAssignment = $lockedBooking->currentFinalDeliveryAssignment()->lockForUpdate()->first();
            if (! $finalAssignment || (int) $finalAssignment->delivery_person_id !== (int) $person->id) {
                throw ValidationException::withMessages([
                    'delivery_person' => ['You are not the active Final Delivery Person for this booking.'],
                ]);
            }

            $pickupRequest = BookingStorePickupRequest::query()
                ->where('booking_id', $lockedBooking->id)
                ->where('delivery_assignment_id', $finalAssignment->id)
                ->where('status', BookingStorePickupRequest::STATUS_ACCEPTED)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $pickupRequest) {
                throw ValidationException::withMessages([
                    'pickup_request' => ['An accepted Store Pickup Request is required before confirming collection from store.'],
                ]);
            }

            if (! in_array($lockedBooking->booking_status, [
                Booking::STATUS_STORE_COMPLETE,
                Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP,
            ], true)) {
                throw ValidationException::withMessages([
                    'booking_status' => ['Booking is not in an eligible stage for physical store pickup.'],
                ]);
            }

            $now = now();
            $nowIso = $now->toIso8601String();

            $pickupRequest->update([
                'status' => BookingStorePickupRequest::STATUS_COLLECTED,
                'collected_at' => $now,
            ]);

            $oldAsgStatus = $finalAssignment->status;
            $finalAssignment->update([
                'status' => DeliveryAssignment::STATUS_PICKED_UP,
                'picked_up_at' => $finalAssignment->picked_up_at ?? $now,
            ]);

            DeliveryAssignmentEvent::create([
                'delivery_assignment_id' => $finalAssignment->id,
                'delivery_person_id' => $person->id,
                'booking_id' => $lockedBooking->id,
                'event_type' => 'status_picked_up',
                'from_status' => $oldAsgStatus,
                'to_status' => DeliveryAssignment::STATUS_PICKED_UP,
                'title' => 'Collected laundry from store',
                'description' => $notes ? trim($notes) : "{$person->full_name} collected parcel from store.",
                'occurred_at' => $now,
            ]);

            $stages = is_array($lockedBooking->completed_operational_stages) ? $lockedBooking->completed_operational_stages : [];
            $stages[Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP] = [
                'completed_at' => $nowIso,
                'delivery_person_id' => $person->id,
            ];

            $oldBookingStatus = $lockedBooking->booking_status;
            $lockedBooking->completed_operational_stages = $stages;
            $lockedBooking->booking_status = Booking::STATUS_ON_DELIVERY;
            $lockedBooking->version = ((int) $lockedBooking->version) + 1;
            $lockedBooking->save();

            BookingAuditLog::create([
                'booking_id' => $lockedBooking->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $person->id,
                'actor_name' => $person->full_name,
                'action' => 'store_pickup_collected',
                'summary' => "{$person->full_name} collected {$lockedBooking->reference} from store; status moved to On Delivery",
                'changes' => [
                    'pickup_request_id' => $pickupRequest->id,
                    'previous_status' => $oldBookingStatus,
                    'new_status' => Booking::STATUS_ON_DELIVERY,
                    'collected_at' => $nowIso,
                    'notes' => $notes ? trim($notes) : null,
                ],
                'ip_address' => $ipAddress,
            ]);

            return $lockedBooking->fresh();
        });
    }

    public function confirmDeliveredToCustomer(
        Booking $booking,
        DeliveryPerson $person,
        ?string $proofNotes = null,
        ?string $ipAddress = null
    ): Booking {
        $completedBooking = DB::transaction(function () use ($booking, $person, $proofNotes, $ipAddress) {
            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($lockedBooking->cancelled_at !== null || $lockedBooking->booking_status === Booking::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'booking_status' => ['Cancelled bookings cannot be marked delivered.'],
                ]);
            }

            if ($lockedBooking->booking_status === Booking::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'booking_status' => ['Delivery completion has already been recorded for this booking.'],
                ]);
            }

            if ($lockedBooking->booking_status !== Booking::STATUS_ON_DELIVERY) {
                throw ValidationException::withMessages([
                    'booking_status' => ['Booking must be On Delivery before confirming customer delivery.'],
                ]);
            }

            $finalAssignment = $lockedBooking->currentFinalDeliveryAssignment()->lockForUpdate()->first();
            if (! $finalAssignment || (int) $finalAssignment->delivery_person_id !== (int) $person->id) {
                throw ValidationException::withMessages([
                    'delivery_person' => ['You are not the active Final Delivery Person for this booking.'],
                ]);
            }

            $pickupRequest = BookingStorePickupRequest::query()
                ->where('booking_id', $lockedBooking->id)
                ->where('delivery_assignment_id', $finalAssignment->id)
                ->where('status', BookingStorePickupRequest::STATUS_COLLECTED)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $pickupRequest) {
                throw ValidationException::withMessages([
                    'pickup_request' => ['Physical store pickup must be confirmed before completing customer delivery.'],
                ]);
            }

            $now = now();
            $nowIso = $now->toIso8601String();
            $cleanProof = $proofNotes ? trim($proofNotes) : null;

            $pickupRequest->update([
                'status' => BookingStorePickupRequest::STATUS_COMPLETED,
                'delivered_at' => $now,
                'proof_of_delivery_notes' => $cleanProof,
            ]);

            $oldAsgStatus = $finalAssignment->status;
            $finalAssignment->update([
                'status' => DeliveryAssignment::STATUS_COMPLETED,
                'handed_over_at' => $finalAssignment->handed_over_at ?? $now,
                'completed_at' => $finalAssignment->completed_at ?? $now,
                'proof_of_delivery_notes' => $cleanProof,
            ]);

            DeliveryAssignmentEvent::create([
                'delivery_assignment_id' => $finalAssignment->id,
                'delivery_person_id' => $person->id,
                'booking_id' => $lockedBooking->id,
                'event_type' => 'status_completed',
                'from_status' => $oldAsgStatus,
                'to_status' => DeliveryAssignment::STATUS_COMPLETED,
                'title' => 'Final delivery completed to customer',
                'description' => $cleanProof ?: "Delivered {$lockedBooking->reference} to customer.",
                'occurred_at' => $now,
            ]);

            $stages = is_array($lockedBooking->completed_operational_stages) ? $lockedBooking->completed_operational_stages : [];
            $stages[Booking::STATUS_ON_DELIVERY] = [
                'completed_at' => $nowIso,
                'delivery_person_id' => $person->id,
            ];
            $stages[Booking::STATUS_COMPLETED] = [
                'completed_at' => $nowIso,
                'delivery_person_id' => $person->id,
            ];

            $lockedBooking->completed_operational_stages = $stages;
            $lockedBooking->booking_status = Booking::STATUS_COMPLETED;
            $lockedBooking->version = ((int) $lockedBooking->version) + 1;
            $lockedBooking->save();

            // Idempotently settle booking & credit Final Delivery Person earnings ledger once
            $this->settlementService->settleCompletedBooking($lockedBooking, null);

            BookingAuditLog::create([
                'booking_id' => $lockedBooking->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $person->id,
                'actor_name' => $person->full_name,
                'action' => 'final_delivery_completed',
                'summary' => "{$person->full_name} delivered {$lockedBooking->reference} to customer; booking Completed and earnings settled",
                'changes' => [
                    'pickup_request_id' => $pickupRequest->id,
                    'previous_status' => Booking::STATUS_ON_DELIVERY,
                    'new_status' => Booking::STATUS_COMPLETED,
                    'delivered_at' => $nowIso,
                    'proof_of_delivery_notes' => $cleanProof,
                ],
                'ip_address' => $ipAddress,
            ]);

            return $lockedBooking->fresh();
        });

        try {
            if (! empty($completedBooking->customer_email)) {
                Mail::to($completedBooking->customer_email)->send(new BookingNotificationMail(
                    $completedBooking,
                    'Your Laundry Order Has Been Delivered',
                    "Your booking {$completedBooking->reference} has been delivered and marked Completed."
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send final delivery completion email', [
                'booking_id' => $completedBooking->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $completedBooking;
    }

    public function cancelActiveRequestsForAssignment(DeliveryAssignment $assignment, string $reason): void
    {
        $now = now();
        BookingStorePickupRequest::query()
            ->where('delivery_assignment_id', $assignment->id)
            ->whereIn('status', [
                BookingStorePickupRequest::STATUS_PENDING,
                BookingStorePickupRequest::STATUS_ACCEPTED,
            ])
            ->update([
                'status' => BookingStorePickupRequest::STATUS_CANCELLED,
                'cancelled_at' => $now,
                'cancellation_reason' => trim($reason),
                'updated_at' => $now,
            ]);
    }

    protected function notifyPersonIfConfigured(BookingStorePickupRequest $pickupRequest): void
    {
        try {
            $email = $pickupRequest->deliveryPerson?->email;
            if (! empty($email) && $pickupRequest->booking) {
                Mail::to($email)->send(new BookingNotificationMail(
                    $pickupRequest->booking,
                    'New Store Pickup Request: ' . $pickupRequest->booking->reference,
                    "Store {$pickupRequest->store?->name} has requested pickup ({$pickupRequest->request_reference}) for booking {$pickupRequest->booking->reference}."
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send store pickup request email', [
                'pickup_request_id' => $pickupRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
