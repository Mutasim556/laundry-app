<?php

namespace App\Http\Controllers\StorePortal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingOperationalItem;
use App\Models\BookingStoreEarning;
use App\Models\BookingStoreHandover;
use App\Models\StoreTransaction;
use App\Models\StoreUser;
use App\Models\StoreWithdrawalRequest;
use App\Services\CurrencyService;
use App\Services\StoreAccountService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoreReportsController extends Controller
{
    public function __construct(
        protected StoreAccountService $accountService,
        protected CurrencyService $currencyService
    ) {}

    protected function storeUser(): StoreUser
    {
        return Auth::guard('store')->user()->load('store');
    }

    /**
     * Resolve selected date range & comparison period using app timezone and half-open boundaries.
     *
     * @return array<string, mixed>
     */
    protected function resolvePeriod(Request $request): array
    {
        $tz = config('app.timezone', 'UTC');
        $now = Carbon::now($tz);

        $period = (string) $request->input('period', 'this_month');
        if (! in_array($period, ['this_month', 'last_month', 'last_3_months', 'last_6_months', 'this_year', 'custom'], true)) {
            $period = 'this_month';
        }

        $month = (int) $request->input('month', $now->month);
        $year = (int) $request->input('year', $now->year);
        if ($month < 1 || $month > 12) {
            $month = $now->month;
        }
        if ($year < 2020 || $year > 2100) {
            $year = $now->year;
        }

        if ($period === 'custom' && $request->filled('date_from') && $request->filled('date_to')) {
            try {
                $start = Carbon::parse($request->input('date_from'), $tz)->startOfDay();
                $endInclusive = Carbon::parse($request->input('date_to'), $tz)->startOfDay();
                if ($endInclusive->lt($start)) {
                    [$start, $endInclusive] = [$endInclusive, $start];
                }
                $endExclusive = $endInclusive->copy()->addDay();
                $label = $start->format('d M Y') . ' – ' . $endInclusive->format('d M Y');
            } catch (\Throwable) {
                $period = 'this_month';
            }
        }

        if ($period !== 'custom') {
            if ($period === 'last_month') {
                $start = $now->copy()->startOfMonth()->subMonth();
                $endExclusive = $start->copy()->addMonth();
                $month = $start->month;
                $year = $start->year;
                $label = $start->format('F Y');
            } elseif ($period === 'last_3_months') {
                $endExclusive = $now->copy()->startOfMonth()->addMonth();
                $start = $endExclusive->copy()->subMonths(3);
                $label = $start->format('M Y') . ' – ' . $endExclusive->copy()->subDay()->format('M Y');
            } elseif ($period === 'last_6_months') {
                $endExclusive = $now->copy()->startOfMonth()->addMonth();
                $start = $endExclusive->copy()->subMonths(6);
                $label = $start->format('M Y') . ' – ' . $endExclusive->copy()->subDay()->format('M Y');
            } elseif ($period === 'this_year') {
                $start = Carbon::create($year, 1, 1, 0, 0, 0, $tz);
                $endExclusive = $start->copy()->addYear();
                $label = 'Year ' . $year;
            } else {
                // this_month or explicit month/year selection
                $start = Carbon::create($year, $month, 1, 0, 0, 0, $tz)->startOfMonth();
                $endExclusive = $start->copy()->addMonth();
                $label = $start->format('F Y');
            }
        }

        $compare = (string) $request->input('compare', 'prev_month');
        if (! in_array($compare, ['prev_month', 'prev_year', 'prev_equivalent', 'none'], true)) {
            $compare = 'prev_month';
        }

        $compStart = null;
        $compEndExclusive = null;
        $compLabel = 'No Comparison';

        if ($compare === 'prev_month') {
            $compStart = $start->copy()->subMonth();
            $compEndExclusive = $endExclusive->copy()->subMonth();
            $compLabel = $compStart->format('F Y');
        } elseif ($compare === 'prev_year') {
            $compStart = $start->copy()->subYear();
            $compEndExclusive = $endExclusive->copy()->subYear();
            $compLabel = $compStart->format('F Y');
        } elseif ($compare === 'prev_equivalent') {
            $days = max(1, (int) $start->diffInDays($endExclusive));
            $compEndExclusive = $start->copy();
            $compStart = $compEndExclusive->copy()->subDays($days);
            $compLabel = $compStart->format('d M Y') . ' – ' . $compEndExclusive->copy()->subDay()->format('d M Y');
        }

        $prevMonthDate = $start->copy()->subMonth();
        $nextMonthDate = $start->copy()->addMonth();

        return [
            'period' => $period,
            'month' => $month,
            'year' => $year,
            'current_month' => $now->month,
            'current_year' => $now->year,
            'date_from' => $start->toDateString(),
            'date_to' => $endExclusive->copy()->subDay()->toDateString(),
            'start' => $start,
            'end_exclusive' => $endExclusive,
            'label' => $label,
            'prev_month' => $prevMonthDate->month,
            'prev_year' => $prevMonthDate->year,
            'next_month' => $nextMonthDate->month,
            'next_year' => $nextMonthDate->year,
            'compare' => $compare,
            'comp_start' => $compStart,
            'comp_end_exclusive' => $compEndExclusive,
            'comp_label' => $compLabel,
        ];
    }

    /**
     * @return array{current: float|int, previous: float|int|null, diff: float|int|null, pct: float|null, direction: string}
     */
    protected function makeDelta(float|int $current, float|int|null $previous): array
    {
        if ($previous === null) {
            return ['current' => $current, 'previous' => null, 'diff' => null, 'pct' => null, 'direction' => 'flat'];
        }

        $diff = $current - $previous;
        $pct = ($previous == 0) ? null : round(($diff / abs($previous)) * 100, 1);
        $direction = $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'flat');

        return [
            'current' => $current,
            'previous' => $previous,
            'diff' => $diff,
            'pct' => $pct,
            'direction' => $direction,
        ];
    }

    public function index(Request $request): View
    {
        $storeUser = $this->storeUser();
        $store = $storeUser->store;
        $p = $this->resolvePeriod($request);

        return view('store-portal.reports.index', [
            'storeUser' => $storeUser,
            'store' => $store,
            'period' => $p,
            'currencies' => StoreTransaction::query()
                ->where('store_id', $store->id)
                ->distinct()
                ->pluck('currency')
                ->filter()
                ->values(),
        ]);
    }

    public function overview(Request $request): JsonResponse
    {
        $store = $this->storeUser()->store;
        $p = $this->resolvePeriod($request);

        $currentStats = $this->computeOverviewStats($store->id, $p['start'], $p['end_exclusive']);
        $prevStats = $p['comp_start']
            ? $this->computeOverviewStats($store->id, $p['comp_start'], $p['comp_end_exclusive'])
            : null;

        // Live Current Outstanding Work (never filtered by month)
        $outstanding = [
            'processing' => Booking::query()
                ->where('assigned_store_id', $store->id)
                ->where('booking_status', Booking::STATUS_STORE_PROCESSING)
                ->count(),
            'pending_handovers' => BookingStoreHandover::query()
                ->where('store_id', $store->id)
                ->where('status', BookingStoreHandover::STATUS_PENDING)
                ->count(),
            'awaiting_collection' => Booking::query()
                ->where('assigned_store_id', $store->id)
                ->where('booking_status', Booking::STATUS_STORE_COMPLETE)
                ->count(),
        ];
        $outstanding['total'] = $outstanding['processing'] + $outstanding['pending_handovers'] + $outstanding['awaiting_collection'];

        $deltas = [
            'assigned' => $this->makeDelta($currentStats['assigned'], $prevStats['assigned'] ?? null),
            'received' => $this->makeDelta($currentStats['received'], $prevStats['received'] ?? null),
            'completed' => $this->makeDelta($currentStats['completed'], $prevStats['completed'] ?? null),
            'handovers' => $this->makeDelta($currentStats['handovers'], $prevStats['handovers'] ?? null),
            'avg_processing_hours' => $this->makeDelta($currentStats['avg_processing_hours'] ?? 0, $prevStats['avg_processing_hours'] ?? null),
        ];

        $html = view('store-portal.reports.partials.overview', [
            'period' => $p,
            'stats' => $currentStats,
            'prevStats' => $prevStats,
            'deltas' => $deltas,
            'outstanding' => $outstanding,
            'currencyService' => $this->currencyService,
        ])->render();

        return response()->json([
            'status' => 'ok',
            'period_label' => $p['label'],
            'comp_label' => $p['comp_label'],
            'date_from' => $p['date_from'],
            'date_to' => $p['date_to'],
            'prev_month' => $p['prev_month'],
            'prev_year' => $p['prev_year'],
            'next_month' => $p['next_month'],
            'next_year' => $p['next_year'],
            'html' => $html,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function computeOverviewStats(int $storeId, Carbon $start, Carbon $endExclusive): array
    {
        $assigned = Booking::query()
            ->where('assigned_store_id', $storeId)
            ->whereHas('currentStoreAssignment', fn ($q) => $q
                ->where('store_id', $storeId)
                ->whereRaw('COALESCE(assigned_at, created_at) >= ?', [$start])
                ->whereRaw('COALESCE(assigned_at, created_at) < ?', [$endExclusive]))
            ->count();

        $handovers = BookingStoreHandover::query()
            ->where('store_id', $storeId)
            ->where('status', BookingStoreHandover::STATUS_ACCEPTED)
            ->where('accepted_at', '>=', $start)
            ->where('accepted_at', '<', $endExclusive)
            ->count();

        // Received & Completed from Booking completed_operational_stages timestamps
        $bookings = Booking::query()
            ->where('assigned_store_id', $storeId)
            ->where('updated_at', '>=', $start->copy()->subDays(7))
            ->get(['id', 'booking_status', 'completed_operational_stages']);

        $received = 0;
        $completed = 0;
        $durationsHours = [];

        foreach ($bookings as $b) {
            $stages = is_array($b->completed_operational_stages) ? $b->completed_operational_stages : [];
            $recAt = isset($stages[Booking::STATUS_STORE_RECEIVED]['completed_at'])
                ? Carbon::parse($stages[Booking::STATUS_STORE_RECEIVED]['completed_at'], config('app.timezone', 'UTC'))
                : null;
            $compAt = isset($stages[Booking::STATUS_STORE_COMPLETE]['completed_at'])
                ? Carbon::parse($stages[Booking::STATUS_STORE_COMPLETE]['completed_at'], config('app.timezone', 'UTC'))
                : null;

            if ($recAt && $recAt->gte($start) && $recAt->lt($endExclusive)) {
                $received++;
            }
            if ($compAt && $compAt->gte($start) && $compAt->lt($endExclusive)) {
                $completed++;
                if ($recAt && $compAt->gte($recAt)) {
                    $durationsHours[] = round($recAt->diffInMinutes($compAt) / 60, 2);
                }
            }
        }

        // Fallback: if received was recorded via handover acceptance without stage timestamp
        if ($received === 0 && $handovers > 0) {
            $received = $handovers;
        }

        $earnings = BookingStoreEarning::query()
            ->where('store_id', $storeId)
            ->where('is_current', true)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $endExclusive)
            ->select(
                'currency',
                DB::raw('SUM(net_store_earnings_minor) as store_share_minor'),
                DB::raw('SUM(commission_amount_minor) as commission_minor')
            )
            ->groupBy('currency')
            ->get()
            ->map(fn ($r) => [
                'currency' => strtoupper((string) $r->currency),
                'store_share_minor' => (int) $r->store_share_minor,
                'commission_minor' => (int) $r->commission_minor,
            ])
            ->values()
            ->all();

        $avgHours = count($durationsHours) > 0
            ? round(array_sum($durationsHours) / count($durationsHours), 1)
            : null;

        return [
            'assigned' => $assigned,
            'received' => $received,
            'completed' => $completed,
            'handovers' => $handovers,
            'earnings_by_currency' => $earnings,
            'avg_processing_hours' => $avgHours,
        ];
    }

    public function financial(Request $request): JsonResponse
    {
        $store = $this->storeUser()->store;
        $p = $this->resolvePeriod($request);

        $accountSummary = $this->accountService->getStoreAccountSummary($store);

        // Reserved & Settled Withdrawals
        $reservedByCurrency = StoreWithdrawalRequest::query()
            ->where('store_id', $store->id)
            ->whereIn('status', [StoreWithdrawalRequest::STATUS_PENDING, StoreWithdrawalRequest::STATUS_APPROVED])
            ->select('currency', DB::raw('SUM(amount_minor) as total_minor'))
            ->groupBy('currency')
            ->pluck('total_minor', 'currency')
            ->all();

        $settledPeriodByCurrency = StoreWithdrawalRequest::query()
            ->where('store_id', $store->id)
            ->where('status', StoreWithdrawalRequest::STATUS_MANUALLY_SETTLED)
            ->where('settled_at', '>=', $p['start'])
            ->where('settled_at', '<', $p['end_exclusive'])
            ->select('currency', DB::raw('SUM(amount_minor) as total_minor'))
            ->groupBy('currency')
            ->pluck('total_minor', 'currency')
            ->all();

        $earningsPeriod = BookingStoreEarning::query()
            ->where('store_id', $store->id)
            ->where('is_current', true)
            ->where('created_at', '>=', $p['start'])
            ->where('created_at', '<', $p['end_exclusive'])
            ->select(
                'currency',
                DB::raw('SUM(eligible_service_amount_minor) as revenue_minor'),
                DB::raw('SUM(commission_amount_minor) as commission_minor'),
                DB::raw('SUM(net_store_earnings_minor) as store_share_minor')
            )
            ->groupBy('currency')
            ->get()
            ->keyBy('currency');

        // Daily series for Chart A (Store Share vs Commission) & Chart B (Trend)
        $dailyRows = BookingStoreEarning::query()
            ->where('store_id', $store->id)
            ->where('is_current', true)
            ->where('created_at', '>=', $p['start'])
            ->where('created_at', '<', $p['end_exclusive'])
            ->select(
                DB::raw('DATE(created_at) as dt'),
                DB::raw('SUM(net_store_earnings_minor) as store_share_minor'),
                DB::raw('SUM(commission_amount_minor) as commission_minor')
            )
            ->groupBy('dt')
            ->orderBy('dt')
            ->get();

        $labels = [];
        $shareSeries = [];
        $commSeries = [];
        foreach ($dailyRows as $row) {
            $labels[] = Carbon::parse($row->dt)->format('d M');
            $shareSeries[] = round(((int) $row->store_share_minor) / 100, 2);
            $commSeries[] = round(((int) $row->commission_minor) / 100, 2);
        }

        $totalShareMinor = (int) $earningsPeriod->sum('store_share_minor');
        $totalCommMinor = (int) $earningsPeriod->sum('commission_minor');

        $html = view('store-portal.reports.partials.financial', [
            'period' => $p,
            'accountSummary' => $accountSummary,
            'reservedByCurrency' => $reservedByCurrency,
            'settledPeriodByCurrency' => $settledPeriodByCurrency,
            'earningsPeriod' => $earningsPeriod,
            'currencyService' => $this->currencyService,
        ])->render();

        return response()->json([
            'status' => 'ok',
            'html' => $html,
            'charts' => [
                'share_vs_commission' => [
                    'labels' => $labels,
                    'store_share' => $shareSeries,
                    'commission' => $commSeries,
                ],
                'breakdown' => [
                    'labels' => ['Net Store Share', 'Platform Commission'],
                    'series' => [round($totalShareMinor / 100, 2), round($totalCommMinor / 100, 2)],
                ],
            ],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $store = $this->storeUser()->store;
        $p = $this->resolvePeriod($request);

        $type = (string) $request->input('type', 'all');
        $direction = (string) $request->input('direction', 'all');
        $currency = strtoupper((string) $request->input('currency', 'ALL'));
        $search = trim((string) $request->input('q', ''));
        $sort = (string) $request->input('sort', 'newest');

        $query = StoreTransaction::query()
            ->with(['booking:id,reference'])
            ->where('store_id', $store->id)
            ->where('created_at', '>=', $p['start'])
            ->where('created_at', '<', $p['end_exclusive']);

        if ($type !== 'all' && $type !== '') {
            $query->where('transaction_type', $type);
        }
        if (in_array($direction, ['credit', 'debit'], true)) {
            $query->where('direction', $direction);
        }
        if ($currency !== 'ALL' && $currency !== '') {
            $query->where('currency', $currency);
        }
        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('booking', fn ($bq) => $bq->where('reference', 'like', "%{$search}%"));
            });
        }

        match ($sort) {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'amount_desc' => $query->orderByRaw('CAST(amount AS DECIMAL(12,2)) DESC')->orderByDesc('id'),
            'amount_asc' => $query->orderByRaw('CAST(amount AS DECIMAL(12,2)) ASC')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        $transactions = $query->paginate(12)->withQueryString();

        $html = view('store-portal.reports.partials.transactions', [
            'period' => $p,
            'transactions' => $transactions,
        ])->render();

        return response()->json([
            'status' => 'ok',
            'html' => $html,
        ]);
    }

    public function transactionDetail(StoreTransaction $transaction): JsonResponse
    {
        $store = $this->storeUser()->store;
        if ((int) $transaction->store_id !== (int) $store->id) {
            abort(403, 'Unauthorized transaction access.');
        }

        $transaction->load(['booking:id,reference,booking_status']);

        return response()->json([
            'status' => 'ok',
            'transaction' => [
                'id' => $transaction->id,
                'reference' => $transaction->reference,
                'created_at' => $transaction->created_at?->format('d M Y, H:i:s'),
                'type_label' => $transaction->type_label,
                'direction' => ucfirst($transaction->direction),
                'amount' => $transaction->currency . ' ' . $transaction->amount,
                'balance_after' => $transaction->balance_after !== null ? ($transaction->currency . ' ' . $transaction->balance_after) : '-',
                'booking_reference' => $transaction->booking?->reference ?? '-',
                'description' => $transaction->description ?? '-',
            ],
        ]);
    }

    public function bookings(Request $request): JsonResponse
    {
        $store = $this->storeUser()->store;
        $p = $this->resolvePeriod($request);

        $status = (string) $request->input('status', 'all');
        $search = trim((string) $request->input('q', ''));
        $sort = (string) $request->input('sort', 'assigned_desc');

        $query = Booking::query()
            ->with(['currentStoreAssignment'])
            ->where('assigned_store_id', $store->id)
            ->whereHas('currentStoreAssignment', fn ($q) => $q
                ->where('store_id', $store->id)
                ->whereRaw('COALESCE(assigned_at, created_at) >= ?', [$p['start']])
                ->whereRaw('COALESCE(assigned_at, created_at) < ?', [$p['end_exclusive']]));

        if ($status !== 'all' && $status !== '') {
            $query->where('booking_status', $status);
        }
        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('reference', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        match ($sort) {
            'assigned_asc' => $query->orderBy('confirmed_at')->orderBy('id'),
            'updated_desc' => $query->orderByDesc('updated_at')->orderByDesc('id'),
            default => $query->orderByDesc('confirmed_at')->orderByDesc('id'),
        };

        $bookings = $query->paginate(12)->withQueryString();

        // Stage distribution for Donut Chart
        $stageCounts = Booking::query()
            ->where('assigned_store_id', $store->id)
            ->whereHas('currentStoreAssignment', fn ($q) => $q
                ->where('store_id', $store->id)
                ->whereRaw('COALESCE(assigned_at, created_at) >= ?', [$p['start']])
                ->whereRaw('COALESCE(assigned_at, created_at) < ?', [$p['end_exclusive']]))
            ->select('booking_status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('booking_status')
            ->pluck('cnt', 'booking_status')
            ->all();

        // Daily assignment counts for Bar Chart
        $dailyAssigned = Booking::query()
            ->join('booking_store_assignments as bsa', 'bsa.booking_id', '=', 'bookings.id')
            ->where('bookings.assigned_store_id', $store->id)
            ->where('bsa.store_id', $store->id)
            ->where('bsa.is_current', true)
            ->whereRaw('COALESCE(bsa.assigned_at, bsa.created_at) >= ?', [$p['start']])
            ->whereRaw('COALESCE(bsa.assigned_at, bsa.created_at) < ?', [$p['end_exclusive']])
            ->select(DB::raw('DATE(COALESCE(bsa.assigned_at, bsa.created_at)) as dt'), DB::raw('COUNT(DISTINCT bookings.id) as cnt'))
            ->groupBy('dt')
            ->orderBy('dt')
            ->get();

        $html = view('store-portal.reports.partials.bookings', [
            'period' => $p,
            'bookings' => $bookings,
            'stageCounts' => $stageCounts,
        ])->render();

        return response()->json([
            'status' => 'ok',
            'html' => $html,
            'charts' => [
                'daily_activity' => [
                    'labels' => $dailyAssigned->map(fn ($r) => Carbon::parse($r->dt)->format('d M'))->values(),
                    'series' => $dailyAssigned->map(fn ($r) => (int) $r->cnt)->values(),
                ],
                'stage_distribution' => [
                    'labels' => array_map(fn ($k) => ucwords(str_replace('_', ' ', $k)), array_keys($stageCounts)),
                    'series' => array_map('intval', array_values($stageCounts)),
                ],
            ],
        ]);
    }

    public function handovers(Request $request): JsonResponse
    {
        $store = $this->storeUser()->store;
        $p = $this->resolvePeriod($request);

        $status = (string) $request->input('status', 'all');
        $search = trim((string) $request->input('q', ''));
        $sort = (string) $request->input('sort', 'newest');

        $base = BookingStoreHandover::query()
            ->where('store_id', $store->id)
            ->whereRaw('COALESCE(accepted_at, requested_at, created_at) >= ?', [$p['start']])
            ->whereRaw('COALESCE(accepted_at, requested_at, created_at) < ?', [$p['end_exclusive']]);

        $summary = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', BookingStoreHandover::STATUS_PENDING)->count(),
            'accepted' => (clone $base)->where('status', BookingStoreHandover::STATUS_ACCEPTED)->count(),
            'rejected' => (clone $base)->where('status', BookingStoreHandover::STATUS_REJECTED)->count(),
        ];

        $query = (clone $base)->with(['booking:id,reference,customer_name', 'collectionDeliveryPerson:id,full_name']);
        if (in_array($status, ['pending', 'accepted', 'rejected'], true)) {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('request_reference', 'like', "%{$search}%")
                    ->orWhereHas('booking', fn ($bq) => $bq->where('reference', 'like', "%{$search}%"))
                    ->orWhereHas('collectionDeliveryPerson', fn ($dq) => $dq->where('full_name', 'like', "%{$search}%"));
            });
        }

        match ($sort) {
            'oldest' => $query->orderBy('requested_at')->orderBy('id'),
            default => $query->orderByDesc('requested_at')->orderByDesc('id'),
        };

        $handovers = $query->paginate(12)->withQueryString();

        $html = view('store-portal.reports.partials.handovers', [
            'period' => $p,
            'summary' => $summary,
            'handovers' => $handovers,
        ])->render();

        return response()->json([
            'status' => 'ok',
            'html' => $html,
            'charts' => [
                'status_bar' => [
                    'labels' => ['Pending', 'Accepted', 'Rejected'],
                    'series' => [$summary['pending'], $summary['accepted'], $summary['rejected']],
                ],
            ],
        ]);
    }

    public function performance(Request $request): JsonResponse
    {
        $store = $this->storeUser()->store;
        $p = $this->resolvePeriod($request);

        $stats = $this->computeOverviewStats($store->id, $p['start'], $p['end_exclusive']);

        // Service Category Analysis (separating weight kg vs unit/piece counts!)
        $categories = BookingOperationalItem::query()
            ->join('bookings', 'bookings.id', '=', 'booking_operational_items.booking_id')
            ->join('booking_store_assignments as bsa', 'bsa.booking_id', '=', 'bookings.id')
            ->where('bookings.assigned_store_id', $store->id)
            ->where('bsa.store_id', $store->id)
            ->where('bsa.is_current', true)
            ->whereRaw('COALESCE(bsa.assigned_at, bsa.created_at) >= ?', [$p['start']])
            ->whereRaw('COALESCE(bsa.assigned_at, bsa.created_at) < ?', [$p['end_exclusive']])
            ->select(
                'booking_operational_items.category_name',
                'booking_operational_items.pricing_mode',
                DB::raw('COUNT(*) as line_count'),
                DB::raw('SUM(COALESCE(booking_operational_items.weight_kg, 0)) as total_kg'),
                DB::raw('SUM(COALESCE(booking_operational_items.quantity, 0)) as total_qty')
            )
            ->groupBy('booking_operational_items.category_name', 'booking_operational_items.pricing_mode')
            ->orderByDesc('line_count')
            ->limit(10)
            ->get();

        $html = view('store-portal.reports.partials.performance', [
            'period' => $p,
            'stats' => $stats,
            'categories' => $categories,
        ])->render();

        return response()->json([
            'status' => 'ok',
            'html' => $html,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $store = $this->storeUser()->store;
        $p = $this->resolvePeriod($request);
        $filename = 'store-transactions-' . $p['date_from'] . '-to-' . $p['date_to'] . '.csv';

        $rows = StoreTransaction::query()
            ->with(['booking:id,reference'])
            ->where('store_id', $store->id)
            ->where('created_at', '>=', $p['start'])
            ->where('created_at', '<', $p['end_exclusive'])
            ->orderByDesc('created_at')
            ->get();

        $sanitize = static function (?string $val): string {
            $str = (string) ($val ?? '');
            if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'" . $str;
            }
            return $str;
        };

        return response()->streamDownload(function () use ($rows, $sanitize) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Transaction Ref', 'Booking Ref', 'Type', 'Direction', 'Currency', 'Amount', 'Balance After', 'Description']);
            foreach ($rows as $tx) {
                fputcsv($out, [
                    $tx->created_at?->format('Y-m-d H:i:s'),
                    $sanitize($tx->reference),
                    $sanitize($tx->booking?->reference ?? '-'),
                    $sanitize($tx->type_label),
                    $sanitize($tx->direction),
                    $sanitize($tx->currency),
                    $tx->amount,
                    $tx->balance_after ?? '',
                    $sanitize($tx->description),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
