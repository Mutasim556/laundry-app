<?php

namespace App\Services;

use App\Models\Booking;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;

class DeliveryPersonAuthorizationService
{
    /**
     * Resolve active assignment roles for this Delivery Person on a Booking.
     *
     * @return array{
     *   collection_assignment: ?DeliveryAssignment,
     *   final_assignment: ?DeliveryAssignment,
     *   historical_assignments: \Illuminate\Support\Collection,
     *   can_view: bool,
     *   is_collection_assignee: bool,
     *   is_final_assignee: bool,
     *   can_edit_collection: bool,
     *   can_manage_otp: bool,
     *   can_collect_payment: bool,
     *   can_finalize_collection: bool
     * }
     */
    public function resolveContext(DeliveryPerson $person, Booking $booking): array
    {
        $activeAssignments = DeliveryAssignment::query()
            ->where('booking_id', $booking->id)
            ->where('delivery_person_id', $person->id)
            ->whereNotIn('status', [
                DeliveryAssignment::STATUS_CANCELLED,
                DeliveryAssignment::STATUS_FAILED,
            ])
            ->orderByDesc('id')
            ->get();

        $collectionAssignment = $activeAssignments
            ->firstWhere('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION);
        $finalAssignment = $activeAssignments
            ->firstWhere('assignment_type', DeliveryAssignment::TYPE_FINAL_DELIVERY);

        $historicalAssignments = DeliveryAssignment::query()
            ->where('booking_id', $booking->id)
            ->where('delivery_person_id', $person->id)
            ->whereIn('status', [
                DeliveryAssignment::STATUS_COMPLETED,
                DeliveryAssignment::STATUS_HANDED_OVER,
            ])
            ->orderByDesc('id')
            ->get();

        $isCollectionAssignee = $collectionAssignment !== null;
        $isFinalAssignee = $finalAssignment !== null;
        $canView = $isCollectionAssignee || $isFinalAssignee || $historicalAssignments->isNotEmpty();

        $completedStages = $booking->getCompletedOperationalStatuses();
        $isFinalized = in_array(Booking::STATUS_FINALIZED, $completedStages, true);
        $isTerminal = in_array($booking->booking_status, [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED], true);

        $canEditCollection = $isCollectionAssignee && ! $isFinalized && ! $isTerminal;
        $canManageOtp = $isCollectionAssignee && ! $isFinalized && ! $isTerminal;
        $canCollectPayment = $isCollectionAssignee && ! $isTerminal;
        $canFinalizeCollection = $isCollectionAssignee && ! $isFinalized && ! $isTerminal;

        return [
            'collection_assignment' => $collectionAssignment,
            'final_assignment' => $finalAssignment,
            'historical_assignments' => $historicalAssignments,
            'can_view' => $canView,
            'is_collection_assignee' => $isCollectionAssignee,
            'is_final_assignee' => $isFinalAssignee,
            'can_edit_collection' => $canEditCollection,
            'can_manage_otp' => $canManageOtp,
            'can_collect_payment' => $canCollectPayment,
            'can_finalize_collection' => $canFinalizeCollection,
        ];
    }

    public function canViewBooking(DeliveryPerson $person, Booking $booking): bool
    {
        return $this->resolveContext($person, $booking)['can_view'];
    }
}
