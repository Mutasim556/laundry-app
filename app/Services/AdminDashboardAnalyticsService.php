<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingAuditLog;
use App\Models\BookingCustomerPayment;
use App\Models\BookingStoreEarning;
use App\Models\BookingStoreHandover;
use App\Models\BookingStorePickupRequest;
use App\Models\DeliveryPersonCashTransaction;
use App\Models\DeliveryPersonWithdrawalRequest;
use App\Models\LaundryTag;
use App\Models\LaundryTagBookingAssignment;
use App\Models\LaundryTagEvent;
use App\Models\StoreWithdrawalRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use Modules\Admin\Models\Store;

class AdminDashboardAnalyticsService
{
    public function __construct(
        private readonly CurrentAccountReportService $currentAccountReport,
        private readonly CurrencyService $currencyService,
    ) {}

    /**
     * Resolve reporting period supporting presets (this_month, last_month, last_3_months, last_6_months, this_year, custom).
     *
     * @return array<string, mixed>
     */
    public function resolveReportingPeriod(?string $month = null, ?string $preset = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $tz = (string) config('app.timezone', 'UTC');
        $nowLocal = Carbon::now($tz);

        if ($preset === 'last_month') {
            $month = $nowLocal->copy()->subMonth()->format('Y-m');
            $fromDate = null;
            $toDate = null;
        } elseif ($preset === 'last_3_months') {
            $fromDate = $nowLocal->copy()->subMonths(2)->startOfMonth()->toDateString();
            $toDate = $nowLocal->copy()->endOfMonth()->toDateString();
        } elseif ($preset === 'last_6_months') {
            $fromDate = $nowLocal->copy()->subMonths(5)->startOfMonth()->toDateString();
            $toDate = $nowLocal->copy()->endOfMonth()->toDateString();
        } elseif ($preset === 'this_year') {
            $fromDate = $nowLocal->copy()->startOfYear()->toDateString();
            $toDate = $nowLocal->copy()->endOfMonth()->toDateString();
        } elseif ($preset === 'this_month') {
            $month = $nowLocal->format('Y-m');
            $fromDate = null;
            $toDate = null;
        }

        $period = $this->currentAccountReport->resolvePeriod($month, $fromDate, $toDate);
        $fromUtc = $period['from_utc'];
        $toUtc = $period['to_exclusive_utc'];
        $durationSeconds = max(86400, (int) $fromUtc->diffInSeconds($toUtc));

        $period['prev_from_utc'] = $fromUtc->copy()->subSeconds($durationSeconds);
        $period['prev_to_exclusive_utc'] = $fromUtc->copy();
        $period['preset'] = $preset ?: ($period['is_custom_range'] ? 'custom' : 'month');

        return $period;
    }

    public function getAvailableCurrencies(): array
    {
        return $this->currentAccountReport->getAvailableCurrencies();
    }

    /**
     * Selected-period KPIs + Booking Workflow + Trend & Status Charts.
     *
     * @return array<string, mixed>
     */
    public function getOverviewData(array $period, string $groupBy = 'daily'): array
    {
        $from = $period['from_utc'];
        $to = $period['to_exclusive_utc'];
        $prevFrom = $period['prev_from_utc'];
        $prevTo = $period['prev_to_exclusive_utc'];

        $periodBookings = Booking::query()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->get(['id', 'booking_status', 'completed_operational_stages', 'created_at']);

        $prevBookingCount = Booking::query()
            ->where('created_at', '>=', $prevFrom)
            ->where('created_at', '<', $prevTo)
            ->count();

        $newBookings = $periodBookings->count();
        $cancelledBookings = $periodBookings->where('booking_status', Booking::STATUS_CANCELLED)->count();
        $deliveriesCompleted = Booking::query()
            ->where('booking_status', Booking::STATUS_COMPLETED)
            ->where('updated_at', '>=', $from)
            ->where('updated_at', '<', $to)
            ->count();

        $storeCompleted = Booking::query()
            ->where('updated_at', '>=', $from)
            ->where('updated_at', '<', $to)
            ->whereIn('booking_status', [
                Booking::STATUS_STORE_COMPLETE,
                Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP,
                Booking::STATUS_ON_DELIVERY,
                Booking::STATUS_COMPLETED,
            ])
            ->count();

        // Workflow distribution for bookings created in the selected period
        $statusCounts = $periodBookings->groupBy('booking_status')->map->count();
        $workflowStages = [];
        foreach (Booking::OPERATIONAL_STATUSES as $key => $label) {
            $cnt = (int) ($statusCounts[$key] ?? 0);
            $workflowStages[] = [
                'key' => $key,
                'label' => $label,
                'count' => $cnt,
                'percent' => $newBookings > 0 ? round(($cnt / $newBookings) * 100, 1) : 0,
                'url' => route('admin.bookings.index', ['booking_status' => $key]),
            ];
        }
        $workflowStages[] = [
            'key' => Booking::STATUS_CANCELLED,
            'label' => 'Cancelled',
            'count' => $cancelledBookings,
            'percent' => $newBookings > 0 ? round(($cancelledBookings / $newBookings) * 100, 1) : 0,
            'url' => route('admin.bookings.index', ['booking_status' => Booking::STATUS_CANCELLED]),
        ];

        // Booking Trend Series (current vs previous period)
        $trend = $this->buildBookingTrend($from, $to, $prevFrom, $prevTo, $groupBy, $period['tz']);

        return [
            'kpis' => [
                'new_bookings' => $newBookings,
                'prev_new_bookings' => $prevBookingCount,
                'store_completed' => $storeCompleted,
                'deliveries_completed' => $deliveriesCompleted,
                'cancelled_bookings' => $cancelledBookings,
            ],
            'workflow_stages' => $workflowStages,
            'trend' => $trend,
        ];
    }

    /**
     * @return array{labels: array<int, string>, current: array<int, int>, previous: array<int, int>}
     */
    private function buildBookingTrend(Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo, string $groupBy, string $tz): array
    {
        $currentRows = Booking::query()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->get(['created_at']);

        $prevRows = Booking::query()
            ->where('created_at', '>=', $prevFrom)
            ->where('created_at', '<', $prevTo)
            ->get(['created_at']);

        $format = match ($groupBy) {
            'monthly' => 'Y-m',
            'weekly' => 'o-W',
            default => 'Y-m-d',
        };
        $labelFormat = match ($groupBy) {
            'monthly' => 'M Y',
            'weekly' => '\W\k W',
            default => 'd M',
        };

        $curBuckets = [];
        foreach ($currentRows as $row) {
            $k = $row->created_at->copy()->timezone($tz)->format($format);
            $curBuckets[$k] = ($curBuckets[$k] ?? 0) + 1;
        }

        $prevBuckets = [];
        foreach ($prevRows as $row) {
            $k = $row->created_at->copy()->timezone($tz)->format($format);
            $prevBuckets[$k] = ($prevBuckets[$k] ?? 0) + 1;
        }

        $labels = [];
        $currentSeries = [];
        $prevSeries = [];

        $cursor = $from->copy()->timezone($tz);
        $end = $to->copy()->timezone($tz);
        $prevCursor = $prevFrom->copy()->timezone($tz);

        $stepMethod = match ($groupBy) {
            'monthly' => 'addMonth',
            'weekly' => 'addWeek',
            default => 'addDay',
        };

        $guard = 0;
        while ($cursor->lt($end) && $guard < 90) {
            $guard++;
            $curKey = $cursor->format($format);
            $prevKey = $prevCursor->format($format);
            $labels[] = $cursor->format($labelFormat);
            $currentSeries[] = (int) ($curBuckets[$curKey] ?? 0);
            $prevSeries[] = (int) ($prevBuckets[$prevKey] ?? 0);
            $cursor->$stepMethod();
            $prevCursor->$stepMethod();
        }

        return [
            'labels' => $labels,
            'current' => $currentSeries,
            'previous' => $prevSeries,
        ];
    }

    /**
     * Store Operations, Delivery Person Jobs & Current Action Required.
     *
     * @return array<string, mixed>
     */
    public function getOperationsData(array $period): array
    {
        $from = $period['from_utc'];
        $to = $period['to_exclusive_utc'];

        // Current Operational Snapshot (always live, independent of selected month)
        $activeCollectionJobs = DeliveryAssignment::query()
            ->where('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)
            ->whereNotIn('status', [DeliveryAssignment::STATUS_COMPLETED, DeliveryAssignment::STATUS_CANCELLED, DeliveryAssignment::STATUS_FAILED])
            ->count();

        $activeFinalJobs = DeliveryAssignment::query()
            ->where('assignment_type', DeliveryAssignment::TYPE_FINAL_DELIVERY)
            ->whereNotIn('status', [DeliveryAssignment::STATUS_COMPLETED, DeliveryAssignment::STATUS_CANCELLED, DeliveryAssignment::STATUS_FAILED])
            ->count();

        $storeProcessingPending = Booking::query()
            ->whereIn('booking_status', [Booking::STATUS_STORE_RECEIVED, Booking::STATUS_STORE_PROCESSING])
            ->whereNull('cancelled_at')
            ->count();

        $pendingTagVerification = LaundryTagBookingAssignment::query()
            ->where('active_lock', 1)
            ->whereNull('store_processing_completed_at')
            ->count();

        $actionRequired = [
            [
                'label' => 'Awaiting Store Assignment',
                'count' => Booking::where('booking_status', Booking::STATUS_PROCESSING)->whereNull('cancelled_at')->count(),
                'urgency' => 'attention',
                'url' => route('admin.bookings.index', ['booking_status' => Booking::STATUS_PROCESSING]),
            ],
            [
                'label' => 'Awaiting Collection Delivery Person',
                'count' => Booking::where('booking_status', Booking::STATUS_STORE_ASSIGNED)->whereNull('cancelled_at')->count(),
                'urgency' => 'attention',
                'url' => route('admin.bookings.index', ['booking_status' => Booking::STATUS_STORE_ASSIGNED]),
            ],
            [
                'label' => 'Store Processing Pending',
                'count' => $storeProcessingPending,
                'urgency' => 'pending',
                'url' => route('admin.bookings.index', ['booking_status' => Booking::STATUS_STORE_PROCESSING]),
            ],
            [
                'label' => 'Pending QR Tag Completions',
                'count' => $pendingTagVerification,
                'urgency' => 'pending',
                'url' => route('admin.tags.index', ['status' => 'at_store']),
            ],
            [
                'label' => 'Store Pickup Requests Pending',
                'count' => BookingStorePickupRequest::where('status', BookingStorePickupRequest::STATUS_PENDING)->count(),
                'urgency' => 'attention',
                'url' => route('admin.bookings.index', ['booking_status' => Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP]),
            ],
            [
                'label' => 'Store Withdrawals Pending Review',
                'count' => StoreWithdrawalRequest::where('status', 'pending')->count(),
                'urgency' => 'attention',
                'url' => route('admin.current-account.index'),
            ],
            [
                'label' => 'DP Withdrawals Pending Review',
                'count' => DeliveryPersonWithdrawalRequest::where('status', 'pending')->count(),
                'urgency' => 'attention',
                'url' => route('admin.delivery-person-settlements.index'),
            ],
        ];

        // Selected-Period Delivery Jobs (Collection vs Final Delivery)
        $collectionAssigned = DeliveryAssignment::where('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)
            ->where('created_at', '>=', $from)->where('created_at', '<', $to)->count();
        $collectionCompleted = DeliveryAssignment::where('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)
            ->where('status', DeliveryAssignment::STATUS_COMPLETED)
            ->where('completed_at', '>=', $from)->where('completed_at', '<', $to)->count();

        $finalAssigned = DeliveryAssignment::where('assignment_type', DeliveryAssignment::TYPE_FINAL_DELIVERY)
            ->where('created_at', '>=', $from)->where('created_at', '<', $to)->count();
        $finalCompleted = DeliveryAssignment::where('assignment_type', DeliveryAssignment::TYPE_FINAL_DELIVERY)
            ->where('status', DeliveryAssignment::STATUS_COMPLETED)
            ->where('completed_at', '>=', $from)->where('completed_at', '<', $to)->count();

        // Store Workload Table (top active stores)
        $stores = Store::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name', 'store_code']);

        $storeWorkload = $stores->map(function (Store $st) use ($from, $to) {
            $activeWork = Booking::where('assigned_store_id', $st->id)
                ->whereIn('booking_status', [Booking::STATUS_STORE_RECEIVED, Booking::STATUS_STORE_PROCESSING])
                ->whereNull('cancelled_at')
                ->count();
            $receivedPeriod = BookingStoreHandover::where('store_id', $st->id)
                ->where('status', BookingStoreHandover::STATUS_ACCEPTED)
                ->where('accepted_at', '>=', $from)->where('accepted_at', '<', $to)
                ->count();
            $completedPeriod = Booking::where('assigned_store_id', $st->id)
                ->whereIn('booking_status', [Booking::STATUS_STORE_COMPLETE, Booking::STATUS_DELIVERY_MAN_ASSIGNED_FOR_STORE_PICKUP, Booking::STATUS_ON_DELIVERY, Booking::STATUS_COMPLETED])
                ->where('updated_at', '>=', $from)->where('updated_at', '<', $to)
                ->count();
            $pendingHandover = BookingStoreHandover::where('store_id', $st->id)
                ->where('status', BookingStoreHandover::STATUS_PENDING)
                ->count();

            return [
                'id' => $st->id,
                'name' => $st->name,
                'store_code' => $st->store_code,
                'active_work' => $activeWork,
                'received_period' => $receivedPeriod,
                'completed_period' => $completedPeriod,
                'pending_handover' => $pendingHandover,
                'url' => route('admin.stores.index'),
            ];
        })->values();

        return [
            'operational_kpis' => [
                'active_collection_jobs' => $activeCollectionJobs,
                'active_final_jobs' => $activeFinalJobs,
                'store_processing_pending' => $storeProcessingPending,
                'pending_tag_verification' => $pendingTagVerification,
                'active_stores' => $stores->count(),
            ],
            'delivery_jobs' => [
                'collection_assigned' => $collectionAssigned,
                'collection_completed' => $collectionCompleted,
                'final_assigned' => $finalAssigned,
                'final_completed' => $finalCompleted,
            ],
            'store_workload' => $storeWorkload,
            'action_required' => $actionRequired,
        ];
    }

    /**
     * Authoritative Currency-Safe Financial Overview.
     *
     * @return array<string, mixed>
     */
    public function getFinanceData(array $period, ?string $currency = null): array
    {
        $from = $period['from_utc'];
        $to = $period['to_exclusive_utc'];
        $currency = strtoupper(trim((string) ($currency ?: $this->currencyService->code())));

        $payments = BookingCustomerPayment::query()
            ->where('currency', $currency)
            ->whereIn('status', [BookingCustomerPayment::STATUS_CONFIRMED, 'verified', 'paid', 'succeeded', 'completed'])
            ->whereRaw('COALESCE(verified_at, created_at) >= ? AND COALESCE(verified_at, created_at) < ?', [$from, $to])
            ->get(['payment_method', 'amount_minor', 'gateway_fee_minor', 'gateway_net_minor']);

        $stripeMinor = 0;
        $stripeFeeMinor = 0;
        $hasUnreconciledStripeFee = false;
        $paypalMinor = 0;
        $codMinor = 0;

        foreach ($payments as $p) {
            $gw = strtolower((string) $p->payment_method);
            $amt = (int) $p->amount_minor;
            if ($gw === BookingCustomerPayment::METHOD_STRIPE) {
                $stripeMinor += $amt;
                if ($p->gateway_fee_minor !== null) {
                    $stripeFeeMinor += (int) $p->gateway_fee_minor;
                } else {
                    $hasUnreconciledStripeFee = true;
                }
            } elseif ($gw === BookingCustomerPayment::METHOD_PAYPAL) {
                $paypalMinor += $amt;
            } else {
                $codMinor += $amt;
            }
        }

        $totalReceiptsMinor = $stripeMinor + $paypalMinor + $codMinor;

        $storeEarnings = BookingStoreEarning::query()
            ->where('currency', $currency)
            ->where('is_current', true)
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->whereHas('booking', fn ($q) => $q->where('booking_status', '!=', Booking::STATUS_CANCELLED))
            ->get(['commission_amount_minor', 'net_store_earnings_minor']);

        $platformCommissionMinor = (int) $storeEarnings->sum('commission_amount_minor');
        $storeShareMinor = (int) $storeEarnings->sum('net_store_earnings_minor');

        $dpEarningsMinor = (int) DeliveryPersonLedgerEntry::query()
            ->where('currency', $currency)
            ->where('direction', 'credit')
            ->where('occurred_at', '>=', $from)
            ->where('occurred_at', '<', $to)
            ->sum('amount_minor');

        // Current COD Cash in Delivery Person Custody (independent of selected month)
        $codCredits = (float) DeliveryPersonCashTransaction::query()
            ->where('currency', $currency)
            ->where('direction', DeliveryPersonCashTransaction::DIRECTION_CREDIT)
            ->sum('amount');
        $codDebits = (float) DeliveryPersonCashTransaction::query()
            ->where('currency', $currency)
            ->where('direction', DeliveryPersonCashTransaction::DIRECTION_DEBIT)
            ->sum('amount');
        $codCustodyMinor = (int) round(max(0.0, $codCredits - $codDebits) * 100);

        return [
            'currency' => $currency,
            'kpis' => [
                'verified_receipts_minor' => $totalReceiptsMinor,
                'verified_receipts_formatted' => $this->currencyService->formatMinor($totalReceiptsMinor, $currency),
                'platform_commission_minor' => $platformCommissionMinor,
                'platform_commission_formatted' => $this->currencyService->formatMinor($platformCommissionMinor, $currency),
                'store_share_minor' => $storeShareMinor,
                'store_share_formatted' => $this->currencyService->formatMinor($storeShareMinor, $currency),
                'dp_earnings_minor' => $dpEarningsMinor,
                'dp_earnings_formatted' => $this->currencyService->formatMinor($dpEarningsMinor, $currency),
                'stripe_fee_minor' => $stripeFeeMinor,
                'stripe_fee_formatted' => $hasUnreconciledStripeFee && $stripeFeeMinor === 0
                    ? 'Pending reconciliation'
                    : $this->currencyService->formatMinor($stripeFeeMinor, $currency),
                'cod_custody_minor' => $codCustodyMinor,
                'cod_custody_formatted' => $this->currencyService->formatMinor($codCustodyMinor, $currency),
            ],
            'by_method' => [
                'stripe_minor' => $stripeMinor,
                'paypal_minor' => $paypalMinor,
                'cod_minor' => $codMinor,
            ],
            'withdrawals' => [
                'store_pending' => StoreWithdrawalRequest::where('currency', $currency)->where('status', 'pending')->count(),
                'store_approved' => StoreWithdrawalRequest::where('currency', $currency)->where('status', 'approved')->count(),
                'store_settled' => StoreWithdrawalRequest::where('currency', $currency)->where('status', 'paid')->count(),
                'dp_pending' => DeliveryPersonWithdrawalRequest::where('currency', $currency)->where('status', 'pending')->count(),
                'dp_approved' => DeliveryPersonWithdrawalRequest::where('currency', $currency)->where('status', 'approved')->count(),
                'dp_settled' => DeliveryPersonWithdrawalRequest::where('currency', $currency)->where('status', 'paid')->count(),
            ],
        ];
    }

    /**
     * QR Laundry Tag Management Overview.
     *
     * @return array<string, int>
     */
    public function getTagsData(): array
    {
        $counts = LaundryTag::query()
            ->selectRaw('status, COUNT(*) as agg')
            ->groupBy('status')
            ->pluck('agg', 'status');

        $pendingStoreTags = LaundryTagBookingAssignment::where('active_lock', 1)
            ->whereNull('store_processing_completed_at')
            ->count();
        $completedStoreTags = LaundryTagBookingAssignment::whereNotNull('store_processing_completed_at')
            ->count();

        return [
            'total_generated' => (int) $counts->sum(),
            'available' => (int) ($counts[LaundryTag::STATUS_AVAILABLE] ?? 0),
            'allocated' => (int) ($counts[LaundryTag::STATUS_ALLOCATED] ?? 0),
            'assigned_to_bookings' => (int) ($counts[LaundryTag::STATUS_ASSIGNED] ?? 0) + (int) ($counts[LaundryTag::STATUS_AT_STORE] ?? 0) + (int) ($counts[LaundryTag::STATUS_IN_FINAL_DELIVERY] ?? 0),
            'pending_store_tags' => $pendingStoreTags,
            'completed_store_tags' => $completedStoreTags,
        ];
    }

    /**
     * Recent recorded activity from BookingAuditLog & LaundryTagEvent.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecentActivity(int $limit = 15): array
    {
        $bookingLogs = BookingAuditLog::query()
            ->with('booking:id,reference')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (BookingAuditLog $log) => [
                'module' => 'Booking',
                'action' => $log->action,
                'description' => $log->summary,
                'reference' => $log->booking?->reference ?? ('#' . $log->booking_id),
                'actor' => $log->actor_name ?: ucfirst((string) $log->actor_type),
                'timestamp' => $log->created_at?->format('d M Y, H:i'),
                'sort_ts' => $log->created_at?->timestamp ?? 0,
                'url' => $log->booking ? route('admin.bookings.show', $log->booking) : null,
            ]);

        $tagEvents = LaundryTagEvent::query()
            ->with(['tag:id,tag_code', 'booking:id,reference'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (LaundryTagEvent $ev) => [
                'module' => 'QR Tag',
                'action' => $ev->event_type,
                'description' => $ev->reason ?: $ev->event_type,
                'reference' => $ev->tag?->tag_code ?? ($ev->booking?->reference ?? '—'),
                'actor' => $ev->actor_name ?: ucfirst((string) $ev->actor_type),
                'timestamp' => $ev->occurred_at?->format('d M Y, H:i'),
                'sort_ts' => $ev->occurred_at?->timestamp ?? 0,
                'url' => route('admin.tags.history', ['tag_code' => $ev->tag?->tag_code]),
            ]);

        return $bookingLogs
            ->concat($tagEvents)
            ->sortByDesc('sort_ts')
            ->take($limit)
            ->values()
            ->all();
    }
}
