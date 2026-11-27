<?php

namespace App\Http\Controllers\DeliveryPersonPortal;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPersonCashTransaction;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;
use Modules\Admin\Models\DeliveryPersonLedgerEntry;
use App\Models\DeliveryPersonSettlement;
use App\Models\DeliveryPersonWithdrawalRequest;
use App\Services\CurrencyService;
use App\Services\DeliveryPersonCashService;
use App\Services\DeliveryPersonWithdrawalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeliveryPersonReportsController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected DeliveryPersonCashService $cashService,
        protected DeliveryPersonWithdrawalService $withdrawalService
    ) {}

    protected function person(): DeliveryPerson
    {
        return Auth::guard('delivery_person')->user();
    }

    /**
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

        return ['current' => $current, 'previous' => $previous, 'diff' => $diff, 'pct' => $pct, 'direction' => $direction];
    }

    public function index(Request $request): View
    {
        $person = $this->person();
        $p = $this->resolvePeriod($request);

        return view('delivery-person-portal.reports.index', [
            'person' => $person,
            'period' => $p,
        ]);
    }

    public function overview(Request $request): JsonResponse
    {
        $person = $this->person();
        $p = $this->resolvePeriod($request);

        $curr = $this->computeOverviewStats($person->id, $p['start'], $p['end_exclusive']);
        $prev = $p['comp_start'] ? $this->computeOverviewStats($person->id, $p['comp_start'], $p['comp_end_exclusive']) : null;

        // Current Snapshot Metrics (independent of selected month)
        $activeJobsCount = DeliveryAssignment::query()
            ->where('delivery_person_id', $person->id)
            ->whereNotIn('status', ['completed', 'cancelled', 'declined'])
            ->count();

        $cashSummary = $this->cashService->getSummary($person, $person->currency ?: 'GBP');
        $cashBalanceMinor = (int) ($cashSummary['cash_in_custody_minor'] ?? $cashSummary['cash_balance_minor'] ?? 0);

        $earningsBalanceMinor = $this->cashService->getEarningsBalanceMinor($person, $person->currency ?: 'GBP');

        $deltas = [
            'total_jobs' => $this->makeDelta($curr['total_jobs'], $prev['total_jobs'] ?? null),
            'collections' => $this->makeDelta($curr['collections'], $prev['collections'] ?? null),
            'final_deliveries' => $this->makeDelta($curr['final_deliveries'], $prev['final_deliveries'] ?? null),
            'completed' => $this->makeDelta($curr['completed'], $prev['completed'] ?? null),
        ];

        $html = view('delivery-person-portal.reports.partials.overview', [
            'period' => $p,
            'stats' => $curr,
            'deltas' => $deltas,
            'activeJobsCount' => $activeJobsCount,
            'cashBalanceMinor' => max(0, $cashBalanceMinor),
            'earningsBalanceMinor' => max(0, $earningsBalanceMinor),
            'currency' => $person->currency ?: 'GBP',
            'currencyService' => $this->currencyService,
        ])->render();

        return response()->json([
            'status' => 'ok',
            'period_label' => $p['label'],
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
    protected function computeOverviewStats(int $dpId, Carbon $start, Carbon $endExclusive): array
    {
        $base = DeliveryAssignment::query()
            ->where('delivery_person_id', $dpId)
            ->whereRaw('COALESCE(assigned_at, created_at) >= ?', [$start])
            ->whereRaw('COALESCE(assigned_at, created_at) < ?', [$endExclusive]);

        $collections = (clone $base)->where('assignment_type', 'customer_collection')->count();
        $finalDeliveries = (clone $base)->where('assignment_type', DeliveryAssignment::TYPE_FINAL_DELIVERY)->count();
        $completed = DeliveryAssignment::query()
            ->where('delivery_person_id', $dpId)
            ->where('status', 'completed')
            ->whereRaw('COALESCE(completed_at, updated_at) >= ?', [$start])
            ->whereRaw('COALESCE(completed_at, updated_at) < ?', [$endExclusive])
            ->count();

        $earningsByCurrency = DeliveryPersonLedgerEntry::query()
            ->where('delivery_person_id', $dpId)
            ->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $endExclusive)
            ->select('currency', DB::raw('SUM(amount_minor) as total_minor'))
            ->groupBy('currency')
            ->get()
            ->map(fn ($r) => ['currency' => strtoupper((string) ($r->currency ?: 'GBP')), 'total_minor' => (int) $r->total_minor])
            ->values()
            ->all();

        return [
            'total_jobs' => $collections + $finalDeliveries,
            'collections' => $collections,
            'final_deliveries' => $finalDeliveries,
            'completed' => $completed,
            'earnings_by_currency' => $earningsByCurrency,
        ];
    }

    public function collections(Request $request): JsonResponse
    {
        return $this->jobTypeReport($request, 'customer_collection', 'dpRepColBarChart', 'dpRepColDonutChart', 'sp-rep-col-pagination');
    }

    public function finalDeliveries(Request $request): JsonResponse
    {
        return $this->jobTypeReport($request, DeliveryAssignment::TYPE_FINAL_DELIVERY, 'dpRepFdBarChart', 'dpRepFdDonutChart', 'sp-rep-fd-pagination');
    }

    protected function jobTypeReport(Request $request, string $jobType, string $barId, string $donutId, string $pagClass): JsonResponse
    {
        $person = $this->person();
        $p = $this->resolvePeriod($request);
        $status = (string) $request->input('status', 'all');
        $search = trim((string) $request->input('q', ''));

        $base = DeliveryAssignment::query()
            ->with(['booking:id,reference,customer_name'])
            ->where('delivery_person_id', $person->id)
            ->where('assignment_type', $jobType)
            ->whereRaw('COALESCE(assigned_at, created_at) >= ?', [$p['start']])
            ->whereRaw('COALESCE(assigned_at, created_at) < ?', [$p['end_exclusive']]);

        $statusCounts = (clone $base)
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->all();

        $dailyRows = (clone $base)
            ->select(
                DB::raw('DATE(COALESCE(assigned_at, created_at)) as dt'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_cnt"),
                DB::raw("SUM(CASE WHEN status NOT IN ('completed', 'cancelled', 'declined') THEN 1 ELSE 0 END) as pending_cnt")
            )
            ->groupBy('dt')
            ->get()
            ->keyBy('dt');

        $dailyLabels = [];
        $dailyPending = [];
        $dailyCompleted = [];
        $cursor = $p['start']->copy();
        while ($cursor->lt($p['end_exclusive'])) {
            $dtKey = $cursor->toDateString();
            $row = $dailyRows->get($dtKey);
            $dailyLabels[] = $cursor->format('d M');
            $dailyPending[] = (int) ($row->pending_cnt ?? 0);
            $dailyCompleted[] = (int) ($row->completed_cnt ?? 0);
            $cursor->addDay();
        }

        $query = clone $base;
        if ($status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $query->whereHas('booking', fn ($bq) => $bq
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%"));
        }

        $jobs = $query->orderByDesc('assigned_at')->orderByDesc('id')->paginate(12)->withQueryString();

        $html = view('delivery-person-portal.reports.partials.jobs', [
            'period' => $p,
            'jobType' => $jobType,
            'jobs' => $jobs,
            'statusCounts' => $statusCounts,
            'barId' => $barId,
            'donutId' => $donutId,
            'pagClass' => $pagClass,
        ])->render();

        return response()->json([
            'status' => 'ok',
            'html' => $html,
            'charts' => [
                'bar_id' => $barId,
                'donut_id' => $donutId,
                'daily' => [
                    'labels' => $dailyLabels,
                    'pending' => $dailyPending,
                    'completed' => $dailyCompleted,
                ],
                'distribution' => [
                    'labels' => array_map(fn ($k) => ucwords(str_replace('_', ' ', $k)), array_keys($statusCounts)),
                    'series' => array_map('intval', array_values($statusCounts)),
                ],
            ],
        ]);
    }

    public function earnings(Request $request): JsonResponse
    {
        $person = $this->person();
        $p = $this->resolvePeriod($request);

        $byJobType = DeliveryPersonLedgerEntry::query()
            ->where('delivery_person_id', $person->id)
            ->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING)
            ->where('created_at', '>=', $p['start'])
            ->where('created_at', '<', $p['end_exclusive'])
            ->select('transaction_type as job_type', 'currency', DB::raw('SUM(amount_minor) as total_minor'))
            ->groupBy('transaction_type', 'currency')
            ->get();

        $earnings = DeliveryPersonLedgerEntry::query()
            ->with(['booking:id,reference'])
            ->where('delivery_person_id', $person->id)
            ->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING)
            ->where('created_at', '>=', $p['start'])
            ->where('created_at', '<', $p['end_exclusive'])
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $dailyEarn = DeliveryPersonLedgerEntry::query()
            ->where('delivery_person_id', $person->id)
            ->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING)
            ->where('created_at', '>=', $p['start'])
            ->where('created_at', '<', $p['end_exclusive'])
            ->select(DB::raw('DATE(created_at) as dt'), DB::raw('SUM(amount_minor) as total_minor'))
            ->groupBy('dt')
            ->pluck('total_minor', 'dt')
            ->all();

        $earnLabels = [];
        $earnSeries = [];
        $cursor = $p['start']->copy();
        while ($cursor->lt($p['end_exclusive'])) {
            $dtKey = $cursor->toDateString();
            $earnLabels[] = $cursor->format('d M');
            $earnSeries[] = round(((int) ($dailyEarn[$dtKey] ?? 0)) / 100, 2);
            $cursor->addDay();
        }

        $html = view('delivery-person-portal.reports.partials.earnings', [
            'period' => $p,
            'byJobType' => $byJobType,
            'earnings' => $earnings,
            'currencyService' => $this->currencyService,
        ])->render();

        return response()->json([
            'status' => 'ok',
            'html' => $html,
            'charts' => [
                'bar_id' => 'dpRepEarnBarChart',
                'series_name' => 'Earnings (' . ($person->currency ?: 'GBP') . ')',
                'daily' => [
                    'labels' => $earnLabels,
                    'series' => $earnSeries,
                ],
            ],
        ]);
    }

    public function withdrawals(Request $request): JsonResponse
    {
        $person = $this->person();
        $p = $this->resolvePeriod($request);

        $withdrawals = DeliveryPersonWithdrawalRequest::query()
            ->with(['bankAccount:id,bank_name,account_number_masked'])
            ->where('delivery_person_id', $person->id)
            ->where('created_at', '>=', $p['start'])
            ->where('created_at', '<', $p['end_exclusive'])
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $reservedMinor = (int) DeliveryPersonWithdrawalRequest::query()
            ->where('delivery_person_id', $person->id)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount_minor');

        $settledMinor = (int) DeliveryPersonWithdrawalRequest::query()
            ->where('delivery_person_id', $person->id)
            ->where('status', 'manually_settled')
            ->where('settled_at', '>=', $p['start'])
            ->where('settled_at', '<', $p['end_exclusive'])
            ->sum('amount_minor');

        $dailyWd = DeliveryPersonWithdrawalRequest::query()
            ->where('delivery_person_id', $person->id)
            ->where('created_at', '>=', $p['start'])
            ->where('created_at', '<', $p['end_exclusive'])
            ->select(DB::raw('DATE(created_at) as dt'), DB::raw('SUM(amount_minor) as total_minor'))
            ->groupBy('dt')
            ->pluck('total_minor', 'dt')
            ->all();

        $wdLabels = [];
        $wdSeries = [];
        $cursor = $p['start']->copy();
        while ($cursor->lt($p['end_exclusive'])) {
            $dtKey = $cursor->toDateString();
            $wdLabels[] = $cursor->format('d M');
            $wdSeries[] = round(((int) ($dailyWd[$dtKey] ?? 0)) / 100, 2);
            $cursor->addDay();
        }

        $html = view('delivery-person-portal.reports.partials.withdrawals', [
            'period' => $p,
            'withdrawals' => $withdrawals,
            'reservedMinor' => $reservedMinor,
            'settledMinor' => $settledMinor,
            'currency' => $person->currency ?: 'GBP',
            'currencyService' => $this->currencyService,
        ])->render();

        return response()->json([
            'status' => 'ok',
            'html' => $html,
            'charts' => [
                'bar_id' => 'dpRepWdBarChart',
                'series_name' => 'Withdrawals (' . ($person->currency ?: 'GBP') . ')',
                'daily' => [
                    'labels' => $wdLabels,
                    'series' => $wdSeries,
                ],
            ],
        ]);
    }

    public function cashAccount(Request $request): JsonResponse
    {
        $person = $this->person();
        $p = $this->resolvePeriod($request);

        $transactions = DeliveryPersonCashTransaction::query()
            ->with(['booking:id,reference'])
            ->where('delivery_person_id', $person->id)
            ->where('created_at', '>=', $p['start'])
            ->where('created_at', '<', $p['end_exclusive'])
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $settlements = DeliveryPersonSettlement::query()
            ->where('delivery_person_id', $person->id)
            ->where('created_at', '>=', $p['start'])
            ->where('created_at', '<', $p['end_exclusive'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $cashSummary = $this->cashService->getSummary($person, $person->currency ?: 'GBP');
        $custodyMinor = (int) ($cashSummary['cash_in_custody_minor'] ?? $cashSummary['cash_balance_minor'] ?? 0);

        $html = view('delivery-person-portal.reports.partials.cash-account', [
            'period' => $p,
            'transactions' => $transactions,
            'settlements' => $settlements,
            'custodyMinor' => max(0, $custodyMinor),
            'currency' => $person->currency ?: 'GBP',
            'currencyService' => $this->currencyService,
        ])->render();

        return response()->json([
            'status' => 'ok',
            'html' => $html,
        ]);
    }

    public function performance(Request $request): JsonResponse
    {
        $person = $this->person();
        $p = $this->resolvePeriod($request);

        $jobs = DeliveryAssignment::query()
            ->where('delivery_person_id', $person->id)
            ->where('status', 'completed')
            ->whereRaw('COALESCE(completed_at, updated_at) >= ?', [$p['start']])
            ->whereRaw('COALESCE(completed_at, updated_at) < ?', [$p['end_exclusive']])
            ->get(['assignment_type', 'assigned_at', 'picked_up_at', 'completed_at']);

        $colDurations = [];
        $fdDurations = [];
        foreach ($jobs as $j) {
            if ($j->assigned_at && $j->completed_at && $j->completed_at->gte($j->assigned_at)) {
                $mins = $j->assigned_at->diffInMinutes($j->completed_at);
                if ($j->assignment_type === 'customer_collection') {
                    $colDurations[] = $mins;
                } else {
                    $fdDurations[] = $mins;
                }
            }
        }

        $html = view('delivery-person-portal.reports.partials.performance', [
            'period' => $p,
            'completedCount' => $jobs->count(),
            'avgColMins' => count($colDurations) ? round(array_sum($colDurations) / count($colDurations), 1) : null,
            'avgFdMins' => count($fdDurations) ? round(array_sum($fdDurations) / count($fdDurations), 1) : null,
        ])->render();

        return response()->json([
            'status' => 'ok',
            'html' => $html,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $person = $this->person();
        $p = $this->resolvePeriod($request);
        $filename = 'delivery-person-earnings-' . $p['date_from'] . '-to-' . $p['date_to'] . '.csv';

        $rows = DeliveryPersonLedgerEntry::query()
            ->with(['booking:id,reference'])
            ->where('delivery_person_id', $person->id)
            ->where('transaction_type', DeliveryPersonLedgerEntry::TYPE_ASSIGNMENT_EARNING)
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
            fputcsv($out, ['Date', 'Type', 'Booking Ref', 'Currency', 'Amount', 'Direction']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->created_at?->format('Y-m-d H:i:s'),
                    $sanitize($row->transaction_type),
                    $sanitize($row->booking?->reference ?? '-'),
                    $sanitize($row->currency ?: 'GBP'),
                    number_format(((int) ($row->amount_minor ?? 0)) / 100, 2, '.', ''),
                    $sanitize($row->direction),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
