<?php

namespace App\Services;

use App\Models\Booking;

class StoreOperationalStatusService
{
    public const STORE_STAGE_KEYS = [
        Booking::STATUS_STORE_RECEIVED,
        Booking::STATUS_STORE_PROCESSING,
        Booking::STATUS_STORE_COMPLETE,
    ];

    /**
     * Derive authoritative last-completed operational status and 3-stage Store Processing
     * workflow state from persisted `completed_operational_stages` (never treating next
     * pending `booking_status` as completed).
     *
     * @return array<string, mixed>
     */
    public function getStatusForBooking(Booking $booking): array
    {
        $completedStages = $booking->getCompletedOperationalStatuses();
        $orderedAll = array_keys(Booking::OPERATIONAL_STATUSES);

        $lastCompletedStage = null;
        foreach ($orderedAll as $stageKey) {
            if (in_array($stageKey, $completedStages, true)) {
                $lastCompletedStage = $stageKey;
            }
        }

        // Store-specific cap: once Store Complete is done, later delivery stages
        // (Store Pickup, On Delivery, Completed) do NOT change the store-specific status.
        $lastCompletedStoreStage = null;
        foreach (self::STORE_STAGE_KEYS as $storeKey) {
            if (in_array($storeKey, $completedStages, true)) {
                $lastCompletedStoreStage = $storeKey;
            }
        }

        $effectiveStageKey = $lastCompletedStoreStage ?? $lastCompletedStage;
        $storeWorkflowFinished = in_array(Booking::STATUS_STORE_COMPLETE, $completedStages, true);

        if ($booking->cancelled_at !== null || $booking->booking_status === Booking::STATUS_CANCELLED) {
            $statusKey = Booking::STATUS_CANCELLED;
            $statusLabel = 'Cancelled';
            $badgeStyle = 'background-color: #FEE2E2; color: #B91C1C; border: 1px solid #FECACA;';
        } elseif ($effectiveStageKey !== null) {
            $statusKey = $effectiveStageKey;
            $statusLabel = $effectiveStageKey === Booking::STATUS_DELIVERY_MAN_PICKED_UP
                ? 'Picked Up'
                : (Booking::STATUS_LABELS[$effectiveStageKey] ?? ucfirst(str_replace('_', ' ', $effectiveStageKey)));

            if ($storeWorkflowFinished) {
                $badgeStyle = 'background-color: #DCFCE7; color: #15803D; border: 1px solid #86EFAC;';
            } elseif (in_array($effectiveStageKey, [Booking::STATUS_STORE_RECEIVED, Booking::STATUS_STORE_PROCESSING], true)) {
                $badgeStyle = 'background-color: #DBEAFE; color: #1D4ED8; border: 1px solid #93C5FD;';
            } else {
                $badgeStyle = 'background-color: #FEF3C7; color: #92400E; border: 1px solid #FDE68A;';
            }
        } else {
            $statusKey = 'pending';
            $statusLabel = 'Pending';
            $badgeStyle = 'background-color: #F1F5F9; color: #475569; border: 1px solid #CBD5E1;';
        }

        $storeStages = [];
        foreach (self::STORE_STAGE_KEYS as $stageKey) {
            $isCompleted = in_array($stageKey, $completedStages, true);
            $isCurrentActive = ! $isCompleted
                && $booking->cancelled_at === null
                && $booking->booking_status === $stageKey;

            $storeStages[$stageKey] = [
                'key' => $stageKey,
                'label' => Booking::STATUS_LABELS[$stageKey] ?? ucfirst(str_replace('_', ' ', $stageKey)),
                'completed' => $isCompleted,
                'active' => $isCurrentActive,
                'state' => $isCompleted ? 'completed' : ($isCurrentActive ? 'active' : 'pending'),
            ];
        }

        $nextEligibleStoreStage = ! $storeWorkflowFinished
            && $booking->cancelled_at === null
            && in_array($booking->booking_status, self::STORE_STAGE_KEYS, true)
            ? $booking->booking_status
            : null;

        return [
            'last_completed_stage' => $lastCompletedStage,
            'last_completed_store_stage' => $lastCompletedStoreStage,
            'operational_status_key' => $statusKey,
            'operational_status_label' => $statusLabel,
            'operational_badge_style' => $badgeStyle,
            'store_workflow_finished' => $storeWorkflowFinished,
            'next_eligible_store_stage' => $nextEligibleStoreStage,
            'store_stages' => $storeStages,
        ];
    }
}
