<?php

namespace Modules\Admin\App\Services;

use App\Models\Booking;
use App\Services\BookingFinancialSettlementService;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryAssignmentEvent;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use Modules\Admin\Models\DeliveryPersonPayout;
use Modules\Admin\Models\DeliveryPersonPostcode;
use Modules\Admin\Models\Store;
use Modules\Admin\Models\UkAddress;

class DeliveryLogisticsService
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    /**
     * Validate and sync multiple UK coverage postcodes from uk_addresses.
     *
     * @param  array<int, string>  $rawPostcodes
     * @return array<int, string>
     *
     * @throws ValidationException
     */
    public function syncPostcodes(DeliveryPerson $person, array $rawPostcodes): array
    {
        $normalized = [];
        $invalid = [];

        foreach ($rawPostcodes as $raw) {
            $trimmed = trim((string) $raw);
            if ($trimmed === '') {
                continue;
            }

            $compact = UkAddress::compactPostcode($trimmed);
            $match = UkAddress::query()
                ->where('is_active', true)
                ->where('postcode_compact', $compact)
                ->first();

            if (! $match) {
                $invalid[] = $trimmed;
                continue;
            }

            $canonical = UkAddress::normalizePostcode($match->postcode)
                ?? strtoupper((string) preg_replace('/\s+/', ' ', trim((string) $match->postcode)));

            $normalized[$compact] = $canonical;
        }

        if (! empty($invalid)) {
            throw ValidationException::withMessages([
                'coverage_postcodes' => [
                    'Invalid UK postcode(s) not found in uk_addresses: ' . implode(', ', $invalid),
                ],
            ]);
        }

        DB::transaction(function () use ($person, $normalized) {
            $person->postcodes()->delete();
            foreach ($normalized as $compact => $canonical) {
                DeliveryPersonPostcode::create([
                    'delivery_person_id' => $person->id,
                    'postcode' => $canonical,
                    'postcode_compact' => $compact,
                ]);
            }
        });

        return array_values($normalized);
    }

    /**
     * Extract customer address text and normalized postcode from booking snapshot.
     *
     * @return array{address: string, postcode: ?string, postcode_compact: string}
     */
    public function extractBookingAddressAndPostcode(Booking $booking): array
    {
        $snap = is_array($booking->address_snapshot) ? $booking->address_snapshot : [];
        $parts = array_filter([
            $snap['hotel_name'] ?? null,
            $snap['address_line_1'] ?? ($snap['street'] ?? ($snap['address'] ?? null)),
            $snap['address_line_2'] ?? ($snap['details'] ?? null),
            $snap['city'] ?? null,
            $snap['postcode'] ?? null,
        ], fn ($v) => is_string($v) && trim($v) !== '');

        $rawPostcode = trim((string) ($snap['postcode'] ?? ''));
        $normalized = $rawPostcode !== '' ? (UkAddress::normalizePostcode($rawPostcode) ?? strtoupper($rawPostcode)) : null;
        $compact = $rawPostcode !== '' ? UkAddress::compactPostcode($rawPostcode) : '';

        return [
            'address' => ! empty($parts) ? implode(', ', $parts) : 'Customer Address (Not Specified)',
            'postcode' => $normalized,
            'postcode_compact' => $compact,
        ];
    }

    /**
     * Format store address string for assignment snapshot.
     */
    public function formatStoreAddress(?Store $store): string
    {
        if (! $store) {
            return 'Assigned Store';
        }

        $parts = array_filter([
            $store->name,
            $store->address_line_1 ?: $store->full_address,
            $store->city,
            $store->postcode,
        ], fn ($v) => is_string($v) && trim($v) !== '');

        return implode(', ', $parts);
    }

    /**
     * Find eligible active delivery persons for a booking, ranked by postcode match & availability.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findEligibleDeliveryPersonsForBooking(Booking $booking, string $type, ?Store $store = null): array
    {
        $customerInfo = $this->extractBookingAddressAndPostcode($booking);
        $targetCompact = $customerInfo['postcode_compact'];

        $persons = DeliveryPerson::query()
            ->where('work_status', 'active')
            ->with(['store:id,name', 'postcodes'])
            ->withCount([
                'assignments as active_assignments_count' => fn ($q) => $q->whereIn('status', [
                    DeliveryAssignment::STATUS_PENDING,
                    DeliveryAssignment::STATUS_ASSIGNED,
                    DeliveryAssignment::STATUS_ACCEPTED,
                    DeliveryAssignment::STATUS_PICKED_UP,
                    DeliveryAssignment::STATUS_IN_TRANSIT,
                ]),
            ])
            ->orderBy('full_name')
            ->get();

        return $persons->map(function (DeliveryPerson $person) use ($targetCompact, $store) {
            $matched = null;
            if ($targetCompact !== '') {
                $pc = $person->postcodes->firstWhere('postcode_compact', $targetCompact);
                $matched = $pc?->postcode;
            }

            $storeMatch = $store && $person->store_id === $store->id;

            return [
                'id' => $person->id,
                'code' => $person->code,
                'full_name' => $person->full_name,
                'store_name' => $person->store?->name ?? 'Unassigned',
                'availability_status' => $person->availability_status,
                'matched_postcode' => $matched,
                'postcode_matched' => $matched !== null,
                'store_matched' => $storeMatch,
                'active_assignments_count' => $person->active_assignments_count,
                'per_collection_rate_minor' => $person->per_collection_rate_minor,
                'per_delivery_rate_minor' => $person->per_delivery_rate_minor,
                'currency' => $person->currency,
            ];
        })
            ->sortByDesc(fn ($row) => ($row['postcode_matched'] ? 100 : 0) + ($row['availability_status'] === 'available' ? 10 : 0) + ($row['store_matched'] ? 5 : 0))
            ->values()
            ->all();
    }

    /**
     * Assign a delivery person to a booking leg (Customer Collection or Final Delivery).
     *
     * @throws ValidationException
     */
    public function assignToBooking(
        Booking $booking,
        DeliveryPerson $person,
        string $type,
        ?Store $store,
        int $adminId,
        ?int $overrideRateMinor = null,
        ?string $overrideReason = null,
        ?string $coverageOverrideReason = null
    ): DeliveryAssignment {
        if (! $person->isEligibleForAssignment()) {
            throw ValidationException::withMessages([
                'delivery_person_id' => ["{$person->full_name} is inactive or suspended and cannot receive new assignments."],
            ]);
        }

        if (! in_array($type, [DeliveryAssignment::TYPE_CUSTOMER_COLLECTION, DeliveryAssignment::TYPE_FINAL_DELIVERY], true)) {
            throw ValidationException::withMessages([
                'assignment_type' => ['Invalid assignment type.'],
            ]);
        }

        $customerInfo = $this->extractBookingAddressAndPostcode($booking);
        $matchedPostcode = null;
        if ($customerInfo['postcode_compact'] !== '') {
            $matchedPostcode = $person->postcodes()
                ->where('postcode_compact', $customerInfo['postcode_compact'])
                ->value('postcode');
        }

        if ($matchedPostcode === null && trim((string) $coverageOverrideReason) === '') {
            throw ValidationException::withMessages([
                'coverage_override_reason' => [
                    'No eligible postcode match for ' . ($customerInfo['postcode'] ?: 'this booking') . '. Provide an explicit coverage override reason to assign outside covered postcodes.',
                ],
            ]);
        }

        $storeAddress = $this->formatStoreAddress($store);
        $storePostcode = $store?->postcode;

        $defaultDeliverySplit = BookingFinancialSettlementService::splitMinorHalf((int) ($booking->collection_delivery_fee_minor ?? 0));
        $tipSplit = BookingFinancialSettlementService::splitMinorHalf((int) ($booking->tip_amount_minor ?? 0));

        if ($type === DeliveryAssignment::TYPE_CUSTOMER_COLLECTION) {
            $pickupLocation = $customerInfo['address'];
            $pickupPostcode = $customerInfo['postcode'];
            $destinationLocation = $storeAddress;
            $destinationPostcode = $storePostcode;
            $defaultRateMinor = $defaultDeliverySplit['collection'];
            $tipShareMinor = $tipSplit['collection'];
        } else {
            $pickupLocation = $storeAddress;
            $pickupPostcode = $storePostcode;
            $destinationLocation = $customerInfo['address'];
            $destinationPostcode = $customerInfo['postcode'];
            $defaultRateMinor = $defaultDeliverySplit['final'];
            $tipShareMinor = $tipSplit['final'];
        }

        $rateMinor = $overrideRateMinor !== null ? max(0, $overrideRateMinor) : $defaultRateMinor;
        $isManualRateOverride = $overrideRateMinor !== null && $rateMinor !== $defaultRateMinor;
        $currency = strtoupper((string) ($booking->currency ?: $this->currencyService->code()));

        $codExpectedMinor = ($type === DeliveryAssignment::TYPE_FINAL_DELIVERY && strtolower((string) $booking->payment_gateway) === 'cod' && $booking->payment_status !== 'paid')
            ? (int) ($booking->final_amount_minor ?: $booking->estimated_amount_minor ?: 0)
            : 0;

        return DB::transaction(function () use (
            $booking,
            $person,
            $store,
            $type,
            $pickupLocation,
            $pickupPostcode,
            $destinationLocation,
            $destinationPostcode,
            $matchedPostcode,
            $coverageOverrideReason,
            $rateMinor,
            $tipShareMinor,
            $isManualRateOverride,
            $currency,
            $overrideReason,
            $codExpectedMinor,
            $adminId
        ) {
            $assignment = DeliveryAssignment::create([
                'assignment_reference' => 'ASG-' . date('Y') . '-' . strtoupper(Str::random(6)),
                'booking_id' => $booking->id,
                'delivery_person_id' => $person->id,
                'store_id' => $store?->id,
                'assignment_type' => $type,
                'status' => DeliveryAssignment::STATUS_ASSIGNED,
                'pickup_location' => $pickupLocation,
                'pickup_postcode' => $pickupPostcode,
                'destination_location' => $destinationLocation,
                'destination_postcode' => $destinationPostcode,
                'matched_postcode' => $matchedPostcode,
                'coverage_override_reason' => $coverageOverrideReason ? trim($coverageOverrideReason) : null,
                'assigned_at' => now(),
                'rate_minor' => $rateMinor,
                'tip_share_minor' => $tipShareMinor,
                'is_manual_rate_override' => $isManualRateOverride,
                'currency' => $currency,
                'rate_override_reason' => $overrideReason ? trim($overrideReason) : null,
                'earnings_status' => 'pending',
                'cod_expected_minor' => $codExpectedMinor,
                'cod_handover_status' => $codExpectedMinor > 0 ? 'pending_handover' : 'not_applicable',
                'assigned_by_admin_id' => $adminId,
            ]);

            $assignment->assignment_reference = 'ASG-' . date('Y') . '-' . str_pad((string) $assignment->id, 6, '0', STR_PAD_LEFT);
            $assignment->save();

            DeliveryAssignmentEvent::create([
                'delivery_assignment_id' => $assignment->id,
                'delivery_person_id' => $person->id,
                'booking_id' => $booking->id,
                'event_type' => 'assigned',
                'from_status' => null,
                'to_status' => DeliveryAssignment::STATUS_ASSIGNED,
                'title' => ($type === DeliveryAssignment::TYPE_CUSTOMER_COLLECTION ? 'Customer Collection' : 'Final Delivery') . ' Assigned',
                'description' => "Assigned {$person->full_name} ({$person->code}) for {$booking->reference}.",
                'meta' => [
                    'matched_postcode' => $matchedPostcode,
                    'coverage_override_reason' => $coverageOverrideReason,
                    'rate_minor' => $rateMinor,
                    'currency' => $currency,
                ],
                'actor_admin_id' => $adminId,
                'occurred_at' => now(),
            ]);

            return $assignment;
        });
    }

    /**
     * Transition an assignment's status and idempotently credit earnings upon qualifying completion.
     *
     * @throws ValidationException
     */
    public function transitionAssignment(
        DeliveryAssignment $assignment,
        string $targetStatus,
        int $adminId,
        array $data = []
    ): DeliveryAssignment {
        return DB::transaction(function () use ($assignment, $targetStatus, $adminId, $data) {
            /** @var DeliveryAssignment $locked */
            $locked = DeliveryAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === $targetStatus) {
                return $locked;
            }

            if (! $locked->canTransitionTo($targetStatus)) {
                throw ValidationException::withMessages([
                    'status' => ["Invalid assignment status transition from {$locked->status} to {$targetStatus}."],
                ]);
            }

            $oldStatus = $locked->status;
            $now = now();
            $locked->status = $targetStatus;

            match ($targetStatus) {
                DeliveryAssignment::STATUS_ACCEPTED => $locked->accepted_at = $locked->accepted_at ?? $now,
                DeliveryAssignment::STATUS_PICKED_UP => $locked->picked_up_at = $locked->picked_up_at ?? $now,
                DeliveryAssignment::STATUS_HANDED_OVER => $locked->handed_over_at = $locked->handed_over_at ?? $now,
                DeliveryAssignment::STATUS_COMPLETED => $locked->completed_at = $locked->completed_at ?? $now,
                DeliveryAssignment::STATUS_FAILED => $locked->failed_at = $locked->failed_at ?? $now,
                DeliveryAssignment::STATUS_CANCELLED => $locked->cancelled_at = $locked->cancelled_at ?? $now,
                default => null,
            };

            if ($targetStatus === DeliveryAssignment::STATUS_COMPLETED) {
                $locked->completed_by_admin_id = $adminId;
                if (! empty($data['proof_of_delivery_notes'])) {
                    $locked->proof_of_delivery_notes = trim((string) $data['proof_of_delivery_notes']);
                }
            }

            if ($targetStatus === DeliveryAssignment::STATUS_CANCELLED) {
                $locked->cancellation_reason = ! empty($data['reason']) ? trim((string) $data['reason']) : null;
                if ($locked->earnings_status === 'pending') {
                    $locked->earnings_status = 'not_payable';
                }
            }

            // Qualifying completion for earnings:
            // Customer Collection -> handed_over or completed
            // Final Delivery -> completed
            $qualifiesForEarning = (
                $locked->assignment_type === DeliveryAssignment::TYPE_CUSTOMER_COLLECTION
                && in_array($targetStatus, [DeliveryAssignment::STATUS_HANDED_OVER, DeliveryAssignment::STATUS_COMPLETED], true)
            ) || (
                $locked->assignment_type === DeliveryAssignment::TYPE_FINAL_DELIVERY
                && $targetStatus === DeliveryAssignment::STATUS_COMPLETED
            );

            if ($qualifiesForEarning && $locked->earnings_status === 'pending' && $locked->rate_minor > 0) {
                $alreadyCredited = DeliveryPersonLedgerEntry::query()
                    ->where('delivery_assignment_id', $locked->id)
                    ->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING)
                    ->exists();

                if (! $alreadyCredited) {
                    $bookingRef = $locked->booking?->reference ?? ('#' . $locked->booking_id);
                    DeliveryPersonLedgerEntry::create([
                        'entry_reference' => 'LED-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                        'delivery_person_id' => $locked->delivery_person_id,
                        'delivery_assignment_id' => $locked->id,
                        'booking_id' => $locked->booking_id,
                        'transaction_type' => DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING,
                        'direction' => 'credit',
                        'amount_minor' => $locked->rate_minor,
                        'currency' => $locked->currency,
                        'notes' => "{$locked->type_label} completed for {$bookingRef} ({$locked->assignment_reference})",
                        'created_by_admin_id' => $adminId,
                        'occurred_at' => $now,
                    ]);
                }

                $locked->earnings_status = 'credited';
            }

            $locked->save();

            DeliveryAssignmentEvent::create([
                'delivery_assignment_id' => $locked->id,
                'delivery_person_id' => $locked->delivery_person_id,
                'booking_id' => $locked->booking_id,
                'event_type' => 'status_' . $targetStatus,
                'from_status' => $oldStatus,
                'to_status' => $targetStatus,
                'title' => 'Status updated to ' . (DeliveryAssignment::STATUS_LABELS[$targetStatus] ?? $targetStatus),
                'description' => ! empty($data['notes']) ? trim((string) $data['notes']) : (! empty($data['reason']) ? trim((string) $data['reason']) : null),
                'meta' => [
                    'earnings_status' => $locked->earnings_status,
                ],
                'actor_admin_id' => $adminId,
                'occurred_at' => $now,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Record a payout against a delivery person's ledger balance.
     *
     * @throws ValidationException
     */
    public function recordPayout(
        DeliveryPerson $person,
        int $amountMinor,
        string $currency,
        string $method,
        string $paymentDate,
        ?string $externalRef,
        ?string $notes,
        int $adminId,
        ?string $idempotencyKey = null,
        bool $allowOverdraft = false
    ): DeliveryPersonPayout {
        if ($amountMinor <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Payout amount must be greater than zero.'],
            ]);
        }

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $existing = DeliveryPersonPayout::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use (
            $person,
            $amountMinor,
            $currency,
            $method,
            $paymentDate,
            $externalRef,
            $notes,
            $adminId,
            $idempotencyKey,
            $allowOverdraft
        ) {
            DeliveryPerson::whereKey($person->id)->lockForUpdate()->firstOrFail();

            $balance = $this->getBalanceSummary($person);
            if (! $allowOverdraft && $amountMinor > $balance['outstanding_balance_minor']) {
                throw ValidationException::withMessages([
                    'amount' => ['Payout amount exceeds the outstanding payable balance.'],
                ]);
            }

            $entry = DeliveryPersonLedgerEntry::create([
                'entry_reference' => 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'delivery_person_id' => $person->id,
                'transaction_type' => DeliveryPersonLedgerEntry::TYPE_PAYOUT,
                'direction' => 'debit',
                'amount_minor' => $amountMinor,
                'currency' => strtoupper($currency),
                'notes' => $notes ?: "Payout via {$method}" . ($externalRef ? " (Ref: {$externalRef})" : ''),
                'created_by_admin_id' => $adminId,
                'occurred_at' => now(),
            ]);

            $payout = DeliveryPersonPayout::create([
                'payout_reference' => 'POUT-' . date('Y') . '-' . strtoupper(Str::random(6)),
                'delivery_person_id' => $person->id,
                'ledger_entry_id' => $entry->id,
                'amount_minor' => $amountMinor,
                'currency' => strtoupper($currency),
                'payment_method' => $method,
                'external_reference' => $externalRef,
                'payment_date' => $paymentDate,
                'status' => 'recorded',
                'notes' => $notes,
                'idempotency_key' => $idempotencyKey,
                'recorded_by_admin_id' => $adminId,
            ]);

            $payout->payout_reference = 'POUT-' . date('Y') . '-' . str_pad((string) $payout->id, 6, '0', STR_PAD_LEFT);
            $payout->save();

            return $payout;
        });
    }

    /**
     * Record an append-only ledger adjustment or reversal.
     */
    public function recordLedgerAdjustment(
        DeliveryPerson $person,
        string $transactionType,
        string $direction,
        int $amountMinor,
        string $currency,
        string $notes,
        int $adminId,
        ?DeliveryAssignment $assignment = null
    ): DeliveryPersonLedgerEntry {
        if ($amountMinor <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Adjustment amount must be greater than zero.'],
            ]);
        }

        return DB::transaction(function () use (
            $person,
            $transactionType,
            $direction,
            $amountMinor,
            $currency,
            $notes,
            $adminId,
            $assignment
        ) {
            $entry = DeliveryPersonLedgerEntry::create([
                'entry_reference' => 'ADJ-' . date('Ymd') . '-' . strtoupper(Str::random(6)),
                'delivery_person_id' => $person->id,
                'delivery_assignment_id' => $assignment?->id,
                'booking_id' => $assignment?->booking_id,
                'transaction_type' => $transactionType,
                'direction' => $direction,
                'amount_minor' => $amountMinor,
                'currency' => strtoupper($currency),
                'notes' => $notes,
                'created_by_admin_id' => $adminId,
                'occurred_at' => now(),
            ]);

            if ($assignment && $transactionType === DeliveryPersonLedgerEntry::TYPE_EARNING_REVERSAL) {
                $assignment->update(['earnings_status' => 'reversed']);
            }

            return $entry;
        });
    }

    /**
     * Calculate financial ledger summary for a delivery person.
     *
     * @return array<string, int|string>
     */
    public function getBalanceSummary(DeliveryPerson $person): array
    {
        $entries = $person->ledgerEntries()->get();

        $earningsMinor = (int) $entries
            ->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING)
            ->sum('amount_minor');

        $adjustmentsCreditMinor = (int) $entries
            ->whereIn('transaction_type', [
                DeliveryPersonLedgerEntry::TYPE_EARNING_ADJUSTMENT,
                DeliveryPersonLedgerEntry::TYPE_AUTHORIZED_CORRECTION,
            ])
            ->where('direction', 'credit')
            ->sum('amount_minor');

        $adjustmentsDebitMinor = (int) $entries
            ->whereIn('transaction_type', [
                DeliveryPersonLedgerEntry::TYPE_EARNING_ADJUSTMENT,
                DeliveryPersonLedgerEntry::TYPE_AUTHORIZED_CORRECTION,
                DeliveryPersonLedgerEntry::TYPE_EARNING_REVERSAL,
            ])
            ->where('direction', 'debit')
            ->sum('amount_minor');

        $netAdjustmentsMinor = $adjustmentsCreditMinor - $adjustmentsDebitMinor;

        $totalPaidMinor = (int) $entries
            ->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_PAYOUT)
            ->sum('amount_minor');

        $payoutReversalsMinor = (int) $entries
            ->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_PAYOUT_REVERSAL)
            ->sum('amount_minor');

        $withdrawalReservedMinor = (int) $entries
            ->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_WITHDRAWAL_RESERVATION)
            ->sum('amount_minor');

        $withdrawalReleasedMinor = (int) $entries
            ->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_WITHDRAWAL_RELEASE)
            ->sum('amount_minor');

        $netWithdrawalCommittedMinor = max(0, $withdrawalReservedMinor - $withdrawalReleasedMinor);

        $netPaidMinor = $totalPaidMinor - $payoutReversalsMinor;
        $totalPayableMinor = $earningsMinor + $netAdjustmentsMinor;
        $outstandingMinor = max(0, $totalPayableMinor - $netPaidMinor - $netWithdrawalCommittedMinor);

        $pendingMinor = (int) $person->assignments()
            ->where('earnings_status', 'pending')
            ->whereNotIn('status', [DeliveryAssignment::STATUS_CANCELLED, DeliveryAssignment::STATUS_FAILED])
            ->sum('rate_minor');

        return [
            'currency' => $person->currency ?: $this->currencyService->code(),
            'earnings_minor' => $earningsMinor,
            'net_adjustments_minor' => $netAdjustmentsMinor,
            'total_payable_minor' => $totalPayableMinor,
            'total_paid_minor' => $netPaidMinor,
            'outstanding_balance_minor' => $outstandingMinor,
            'pending_earnings_minor' => $pendingMinor,
        ];
    }

    /**
     * Calculate operational performance statistics for a delivery person.
     *
     * @return array<string, int|float|null>
     */
    public function getPerformanceStats(DeliveryPerson $person): array
    {
        $assignments = $person->assignments()->get();

        $completedLegs = $assignments->whereIn('status', [
            DeliveryAssignment::STATUS_HANDED_OVER,
            DeliveryAssignment::STATUS_COMPLETED,
        ]);

        $qualifyingCompleted = $completedLegs->filter(function (DeliveryAssignment $a) {
            if ($a->assignment_type === DeliveryAssignment::TYPE_CUSTOMER_COLLECTION) {
                return in_array($a->status, [DeliveryAssignment::STATUS_HANDED_OVER, DeliveryAssignment::STATUS_COMPLETED], true);
            }

            return $a->status === DeliveryAssignment::STATUS_COMPLETED;
        });

        $durations = $qualifyingCompleted
            ->filter(fn (DeliveryAssignment $a) => $a->assigned_at && ($a->completed_at || $a->handed_over_at))
            ->map(function (DeliveryAssignment $a) {
                $end = $a->completed_at ?? $a->handed_over_at;

                return $a->assigned_at->diffInMinutes($end);
            });

        return [
            'total_assigned' => $assignments->count(),
            'completed_assignments' => $qualifyingCompleted->count(),
            'unique_orders_handled' => $qualifyingCompleted->pluck('booking_id')->unique()->count(),
            'total_collections' => $qualifyingCompleted->where('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)->count(),
            'total_deliveries' => $qualifyingCompleted->where('assignment_type', DeliveryAssignment::TYPE_FINAL_DELIVERY)->count(),
            'active_assignments' => $assignments->whereIn('status', [
                DeliveryAssignment::STATUS_PENDING,
                DeliveryAssignment::STATUS_ASSIGNED,
                DeliveryAssignment::STATUS_ACCEPTED,
                DeliveryAssignment::STATUS_PICKED_UP,
                DeliveryAssignment::STATUS_IN_TRANSIT,
            ])->count(),
            'failed_assignments' => $assignments->where('status', DeliveryAssignment::STATUS_FAILED)->count(),
            'cancelled_assignments' => $assignments->where('status', DeliveryAssignment::STATUS_CANCELLED)->count(),
            'avg_completion_minutes' => $durations->isNotEmpty() ? round((float) $durations->avg(), 1) : null,
        ];
    }
}
