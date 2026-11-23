<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingStoreHandover;
use App\Models\StoreUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use RuntimeException;

class StoreHandoverService
{
    public function requestHandover(
        Booking $booking,
        DeliveryPerson $person,
        ?string $note = null,
        ?string $idempotencyKey = null
    ): BookingStoreHandover {
        $key = $idempotencyKey ?: ('handover-req:' . $booking->id . ':' . $person->id . ':' . Str::uuid());

        return DB::transaction(function () use ($booking, $person, $note, $key) {
            $existingByKey = BookingStoreHandover::query()->where('idempotency_key', $key)->first();
            if ($existingByKey) {
                return $existingByKey;
            }

            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->cancelled_at !== null || $locked->booking_status === Booking::STATUS_CANCELLED) {
                throw new RuntimeException('Cannot request store handover for a cancelled booking.');
            }

            $collectionAssignment = $locked->currentCollectionAssignment()->lockForUpdate()->first();
            if (! $collectionAssignment || (int) $collectionAssignment->delivery_person_id !== (int) $person->id) {
                throw new RuntimeException('Only the assigned Collection Delivery Person can request store acceptance.');
            }

            $completedStages = $locked->getCompletedOperationalStatuses();
            if (! in_array(Booking::STATUS_DELIVERY_MAN_PICKED_UP, $completedStages, true)) {
                throw new RuntimeException('Delivery Person Picked Up must be marked Done before requesting store acceptance.');
            }

            if (in_array(Booking::STATUS_STORE_RECEIVED, $completedStages, true)) {
                throw new RuntimeException('Store Received has already been completed for this booking.');
            }

            $storeAssignment = $locked->currentStoreAssignment()->with('store')->first();
            $store = $storeAssignment?->store;
            if (! $store || $store->trashed() || $store->status !== 'active') {
                throw new RuntimeException('No active store is assigned to this booking. Contact an administrator.');
            }

            $activePendingOrAccepted = BookingStoreHandover::query()
                ->where('booking_id', $locked->id)
                ->whereIn('status', [BookingStoreHandover::STATUS_PENDING, BookingStoreHandover::STATUS_ACCEPTED])
                ->lockForUpdate()
                ->first();

            if ($activePendingOrAccepted) {
                throw new RuntimeException(
                    $activePendingOrAccepted->status === BookingStoreHandover::STATUS_ACCEPTED
                        ? 'Store handover has already been accepted.'
                        : 'A store handover request is already pending acceptance.'
                );
            }

            $handover = BookingStoreHandover::query()->create([
                'request_reference' => 'HND-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5)),
                'booking_id' => $locked->id,
                'store_id' => $store->id,
                'collection_delivery_person_id' => $person->id,
                'status' => BookingStoreHandover::STATUS_PENDING,
                'requested_at' => now(),
                'request_note' => $note ? trim($note) : null,
                'booking_version' => (int) ($locked->version ?? 1),
                'idempotency_key' => $key,
            ]);

            BookingAuditLog::create([
                'booking_id' => $locked->id,
                'actor_type' => 'delivery_person',
                'actor_id' => $person->id,
                'actor_name' => $person->full_name,
                'action' => 'store_handover_requested',
                'summary' => "Store handover requested ({$handover->request_reference}) to {$store->name} by Delivery Person {$person->full_name}.",
                'changes' => [
                    'handover_id' => $handover->id,
                    'request_reference' => $handover->request_reference,
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'delivery_person_id' => $person->id,
                ],
            ]);

            return $handover;
        });
    }

    public function acceptHandover(BookingStoreHandover $handover, StoreUser $storeUser): BookingStoreHandover
    {
        return DB::transaction(function () use ($handover, $storeUser) {
            /** @var BookingStoreHandover $lockedHandover */
            $lockedHandover = BookingStoreHandover::query()->whereKey($handover->id)->lockForUpdate()->firstOrFail();

            if ((int) $lockedHandover->store_id !== (int) $storeUser->store_id) {
                throw new RuntimeException('This handover request does not belong to your store.');
            }

            if ($lockedHandover->status !== BookingStoreHandover::STATUS_PENDING) {
                throw new RuntimeException("This handover request is already {$lockedHandover->status}.");
            }

            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()->whereKey($lockedHandover->booking_id)->lockForUpdate()->firstOrFail();

            $currentStoreId = (int) ($lockedBooking->currentStoreAssignment?->store_id ?: $lockedBooking->assigned_store_id);
            if ($currentStoreId !== (int) $storeUser->store_id) {
                throw new RuntimeException('Store assignment has changed; your store is no longer assigned to this booking.');
            }

            if ($lockedBooking->cancelled_at !== null || $lockedBooking->booking_status === Booking::STATUS_CANCELLED) {
                throw new RuntimeException('Cannot accept handover for a cancelled booking.');
            }

            $completedStages = $lockedBooking->getCompletedOperationalStatuses();
            if (! in_array(Booking::STATUS_DELIVERY_MAN_PICKED_UP, $completedStages, true)) {
                throw new RuntimeException('Collection Delivery Person must complete Picked Up before Store Received can be confirmed.');
            }

            if (in_array(Booking::STATUS_STORE_RECEIVED, $completedStages, true)) {
                throw new RuntimeException('Store Received is already completed for this booking.');
            }

            if ($lockedBooking->booking_status !== Booking::STATUS_STORE_RECEIVED) {
                throw new RuntimeException('Booking is not currently at the Store Received workflow stage.');
            }

            $now = now();
            $nowIso = $now->toIso8601String();

            $lockedHandover->update([
                'status' => BookingStoreHandover::STATUS_ACCEPTED,
                'accepted_at' => $now,
                'accepted_by_store_user_id' => $storeUser->id,
            ]);

            $stages = is_array($lockedBooking->completed_operational_stages) ? $lockedBooking->completed_operational_stages : [];
            $stages[Booking::STATUS_STORE_RECEIVED] = [
                'completed_at' => $nowIso,
                'store_user_id' => $storeUser->id,
                'handover_id' => $lockedHandover->id,
            ];

            $lockedBooking->forceFill([
                'completed_operational_stages' => $stages,
                'booking_status' => Booking::STATUS_STORE_PROCESSING,
                'version' => ((int) $lockedBooking->version) + 1,
            ])->save();

            $collectionAssignment = $lockedBooking->currentCollectionAssignment()->lockForUpdate()->first();
            $creditedCollectionMinor = 0;
            if ($collectionAssignment) {
                $tipSplit = BookingFinancialSettlementService::splitMinorHalf((int) ($lockedBooking->tip_amount_minor ?? 0));
                $collectionTipShareMinor = $tipSplit['collection'];

                $collectionAssignment->update([
                    'status' => DeliveryAssignment::STATUS_COMPLETED,
                    'handed_over_at' => $collectionAssignment->handed_over_at ?: $now,
                    'completed_at' => $collectionAssignment->completed_at ?: $now,
                ]);

                app(BookingFinancialSettlementService::class)->creditDeliveryAssignment(
                    $lockedBooking,
                    $collectionAssignment,
                    $collectionTipShareMinor,
                    "booking:{$lockedBooking->id}:collection-earning:{$collectionAssignment->id}",
                    'Collection delivery payment and tip share credited upon store handover acceptance for booking ' . $lockedBooking->reference,
                    null
                );

                $creditedCollectionMinor = max(0, (int) $collectionAssignment->rate_minor) + max(0, $collectionTipShareMinor);
            }

            BookingAuditLog::create([
                'booking_id' => $lockedBooking->id,
                'actor_type' => 'store_user',
                'actor_id' => $storeUser->id,
                'actor_name' => $storeUser->name,
                'action' => 'store_handover_accepted',
                'summary' => "Store handover ({$lockedHandover->request_reference}) accepted by {$storeUser->name}; Store Received marked Done and collection job completed.",
                'changes' => [
                    'handover_id' => $lockedHandover->id,
                    'request_reference' => $lockedHandover->request_reference,
                    'store_id' => $storeUser->store_id,
                    'store_user_id' => $storeUser->id,
                    'completed_stage' => Booking::STATUS_STORE_RECEIVED,
                    'new_status' => Booking::STATUS_STORE_PROCESSING,
                    'collection_earnings_credited_minor' => $creditedCollectionMinor,
                ],
            ]);

            return $lockedHandover->fresh(['store', 'collectionDeliveryPerson', 'acceptedByStoreUser']);
        });
    }

    public function rejectHandover(BookingStoreHandover $handover, StoreUser $storeUser, string $reason): BookingStoreHandover
    {
        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw new RuntimeException('Please provide a reason when reporting an issue with this handover.');
        }

        return DB::transaction(function () use ($handover, $storeUser, $trimmedReason) {
            /** @var BookingStoreHandover $lockedHandover */
            $lockedHandover = BookingStoreHandover::query()->whereKey($handover->id)->lockForUpdate()->firstOrFail();

            if ((int) $lockedHandover->store_id !== (int) $storeUser->store_id) {
                throw new RuntimeException('This handover request does not belong to your store.');
            }

            if ($lockedHandover->status !== BookingStoreHandover::STATUS_PENDING) {
                throw new RuntimeException("This handover request is already {$lockedHandover->status}.");
            }

            $now = now();
            $lockedHandover->update([
                'status' => BookingStoreHandover::STATUS_REJECTED,
                'rejected_at' => $now,
                'rejected_by_store_user_id' => $storeUser->id,
                'rejection_reason' => $trimmedReason,
            ]);

            BookingAuditLog::create([
                'booking_id' => $lockedHandover->booking_id,
                'actor_type' => 'store_user',
                'actor_id' => $storeUser->id,
                'actor_name' => $storeUser->name,
                'action' => 'store_handover_rejected',
                'summary' => "Store handover ({$lockedHandover->request_reference}) issue reported by {$storeUser->name}: {$trimmedReason}",
                'changes' => [
                    'handover_id' => $lockedHandover->id,
                    'request_reference' => $lockedHandover->request_reference,
                    'store_id' => $storeUser->store_id,
                    'store_user_id' => $storeUser->id,
                    'reason' => $trimmedReason,
                ],
            ]);

            return $lockedHandover->fresh(['store', 'rejectedByStoreUser']);
        });
    }

    public function cancelPendingForStoreChange(Booking $booking, int $newStoreId, ?int $adminId = null): int
    {
        $pending = BookingStoreHandover::query()
            ->where('booking_id', $booking->id)
            ->where('status', BookingStoreHandover::STATUS_PENDING)
            ->where('store_id', '!=', $newStoreId)
            ->get();

        foreach ($pending as $h) {
            $h->update([
                'status' => BookingStoreHandover::STATUS_CANCELLED,
                'rejection_reason' => 'Cancelled automatically because assigned store was changed by admin.',
            ]);

            BookingAuditLog::create([
                'booking_id' => $booking->id,
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'actor_name' => 'Admin #' . (int) $adminId,
                'action' => 'store_handover_cancelled_reassignment',
                'summary' => "Cancelled pending store handover {$h->request_reference} due to store reassignment.",
                'changes' => ['handover_id' => $h->id, 'old_store_id' => $h->store_id, 'new_store_id' => $newStoreId],
            ]);
        }

        return $pending->count();
    }
}
