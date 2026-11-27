<?php

namespace App\Http\Controllers\DeliveryPersonPortal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\DeliveryPersonBankAccount;
use App\Models\DeliveryPersonCashTransaction;
use App\Models\DeliveryPersonSettlement;
use App\Services\CurrencyService;
use App\Services\DeliveryPersonBankAccountService;
use App\Services\DeliveryPersonCashService;
use App\Services\DeliveryPersonWithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Admin\Models\DeliveryAssignment;
use Modules\Admin\Models\DeliveryPerson;

class DeliveryPersonDashboardController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService,
        protected DeliveryPersonCashService $cashService,
        protected DeliveryPersonWithdrawalService $withdrawalService,
        protected DeliveryPersonBankAccountService $bankService
    ) {}

    protected function person(): DeliveryPerson
    {
        return Auth::guard('delivery_person')->user();
    }

    public function dashboard(Request $request): View|JsonResponse
    {
        $person = $this->person();
        $currency = strtoupper((string) ($person->currency ?: $this->currencyService->code()));

        $rawMonth = trim((string) $request->query('month', now()->format('Y-m')));
        $selectedMonth = $rawMonth === 'all'
            ? 'all'
            : (preg_match('/^\d{4}-\d{2}$/', $rawMonth) ? $rawMonth : now()->format('Y-m'));

        $monthStart = null;
        $monthEnd = null;
        $selectedMonthLabel = 'All Time';

        if ($selectedMonth !== 'all') {
            try {
                $parsed = \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth);
                $monthStart = $parsed->copy()->startOfMonth();
                $monthEnd = $parsed->copy()->endOfMonth();
                $selectedMonthLabel = $parsed->format('F Y');
            } catch (\Throwable) {
                $selectedMonth = 'all';
            }
        }

        $activeBase = DeliveryAssignment::query()
            ->where('delivery_person_id', $person->id)
            ->whereNotIn('status', [DeliveryAssignment::STATUS_CANCELLED, DeliveryAssignment::STATUS_FAILED]);

        if ($monthStart && $monthEnd) {
            $activeBase->whereBetween('created_at', [$monthStart, $monthEnd]);
        }

        $collectionJobsCount = (clone $activeBase)
            ->where('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)
            ->count();

        $finalDeliveryJobsCount = (clone $activeBase)
            ->where('assignment_type', DeliveryAssignment::TYPE_FINAL_DELIVERY)
            ->count();

        $pendingCollectionsCount = (clone $activeBase)
            ->where('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)
            ->whereNotIn('status', [DeliveryAssignment::STATUS_HANDED_OVER, DeliveryAssignment::STATUS_COMPLETED])
            ->count();

        $completedQuery = DeliveryAssignment::query()
            ->where('delivery_person_id', $person->id)
            ->whereIn('status', [DeliveryAssignment::STATUS_HANDED_OVER, DeliveryAssignment::STATUS_COMPLETED]);

        if ($monthStart && $monthEnd) {
            $completedQuery->whereBetween('created_at', [$monthStart, $monthEnd]);
        }

        $completedJobsCount = $completedQuery->count();

        $cashSummary = $this->cashService->getSummary($person, $currency);
        $earningsBalanceMinor = $this->cashService->getEarningsBalanceMinor($person, $currency);

        $recentQuery = DeliveryAssignment::query()
            ->with(['booking', 'store', 'latestPickupRequest'])
            ->where('delivery_person_id', $person->id)
            ->whereNotIn('status', [DeliveryAssignment::STATUS_CANCELLED, DeliveryAssignment::STATUS_FAILED]);

        if ($monthStart && $monthEnd) {
            $recentQuery->whereBetween('created_at', [$monthStart, $monthEnd]);
        }

        $recentAssignments = $recentQuery
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $pendingPickupRequests = \App\Models\BookingStorePickupRequest::query()
            ->with(['booking', 'store', 'deliveryAssignment'])
            ->where('delivery_person_id', $person->id)
            ->where('status', \App\Models\BookingStorePickupRequest::STATUS_PENDING)
            ->orderByDesc('requested_at')
            ->get();

        $viewData = [
            'person' => $person,
            'currency' => $currency,
            'currencyService' => $this->currencyService,
            'collectionJobsCount' => $collectionJobsCount,
            'finalDeliveryJobsCount' => $finalDeliveryJobsCount,
            'pendingCollectionsCount' => $pendingCollectionsCount,
            'completedJobsCount' => $completedJobsCount,
            'cashSummary' => $cashSummary,
            'earningsBalanceMinor' => $earningsBalanceMinor,
            'recentAssignments' => $recentAssignments,
            'pendingPickupRequests' => $pendingPickupRequests,
            'selectedMonth' => $selectedMonth,
            'selectedMonthLabel' => $selectedMonthLabel,
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'selected_month' => $selectedMonth,
                'selected_month_label' => $selectedMonthLabel,
                'content_html' => view('delivery-person-portal.partials.dashboard-content', $viewData)->render(),
            ]);
        }

        return view('delivery-person-portal.dashboard', $viewData);
    }

    public function bookings(Request $request): View|JsonResponse
    {
        return $this->listAssignments($request, null, 'My Assigned Bookings');
    }

    public function collections(Request $request): View|JsonResponse
    {
        return $this->listAssignments($request, DeliveryAssignment::TYPE_CUSTOMER_COLLECTION, 'Collection Bookings');
    }

    public function finalDeliveries(Request $request): View|JsonResponse
    {
        return $this->listAssignments($request, DeliveryAssignment::TYPE_FINAL_DELIVERY, 'Final Delivery Bookings');
    }

    protected function listAssignments(Request $request, ?string $type, string $heading): View|JsonResponse
    {
        $person = $this->person();
        $tz = (string) config('app.timezone', 'UTC');
        $nowTz = \Carbon\Carbon::now($tz);

        $reqMonth = $request->input('month');
        $reqYear = $request->input('year');
        $selectedMonth = is_numeric($reqMonth) && (int) $reqMonth >= 1 && (int) $reqMonth <= 12
            ? (int) $reqMonth
            : $nowTz->month;
        $selectedYear = is_numeric($reqYear) && (int) $reqYear >= 2020 && (int) $reqYear <= 2099
            ? (int) $reqYear
            : $nowTz->year;

        $monthStart = \Carbon\Carbon::create($selectedYear, $selectedMonth, 1, 0, 0, 0, $tz);
        $nextMonthStart = $monthStart->copy()->addMonthNoOverflow()->startOfMonth();
        $prevMonthDate = $monthStart->copy()->subMonthNoOverflow();
        $nextMonthDate = $monthStart->copy()->addMonthNoOverflow();
        $monthLabel = $monthStart->format('F Y');

        $jobTypeFilter = $type ?? ($request->filled('job_type') ? (string) $request->input('job_type') : '');
        $statusFilter = $request->filled('status_filter') ? (string) $request->input('status_filter') : '';
        $search = trim((string) $request->input('search', ''));
        $sort = (string) $request->input('sort', 'scheduled_desc');
        if (! in_array($sort, ['scheduled_desc', 'scheduled_asc', 'assigned_desc', 'updated_desc'], true)) {
            $sort = 'scheduled_desc';
        }

        $query = DeliveryAssignment::query()
            ->with(['booking.assignedStore', 'store', 'latestPickupRequest', 'ledgerEntries'])
            ->where('delivery_person_id', $person->id)
            ->whereNotIn('status', [DeliveryAssignment::STATUS_CANCELLED, DeliveryAssignment::STATUS_FAILED])
            ->whereHas('booking')
            ->whereRaw('COALESCE(scheduled_at, assigned_at, created_at) >= ? AND COALESCE(scheduled_at, assigned_at, created_at) < ?', [
                $monthStart->toDateTimeString(),
                $nextMonthStart->toDateTimeString(),
            ]);

        if ($jobTypeFilter !== '' && in_array($jobTypeFilter, [
            DeliveryAssignment::TYPE_CUSTOMER_COLLECTION,
            DeliveryAssignment::TYPE_FINAL_DELIVERY,
        ], true)) {
            $query->where('assignment_type', $jobTypeFilter);
        }

        if ($search !== '') {
            $numericId = null;
            if (preg_match('/^(?:COL|DEL)-0*(\d+)$/i', $search, $m)) {
                $numericId = (int) $m[1];
            }

            $query->where(function ($q) use ($search, $numericId) {
                $q->where('assignment_reference', 'like', "%{$search}%")
                    ->orWhere('pickup_postcode', 'like', "%{$search}%")
                    ->orWhere('destination_postcode', 'like', "%{$search}%")
                    ->orWhereHas('booking', function ($bq) use ($search) {
                        $bq->where('reference', 'like', "%{$search}%")
                            ->orWhere('customer_name', 'like', "%{$search}%")
                            ->orWhere('customer_phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('store', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));

                if ($numericId !== null) {
                    $q->orWhere('id', $numericId);
                }
            });
        }

        match ($sort) {
            'scheduled_asc' => $query->orderByRaw('COALESCE(scheduled_at, assigned_at, created_at) ASC, id ASC'),
            'assigned_desc' => $query->orderByRaw('COALESCE(assigned_at, created_at) DESC, id DESC'),
            'updated_desc' => $query->orderByDesc('updated_at')->orderByDesc('id'),
            default => $query->orderByRaw('COALESCE(scheduled_at, assigned_at, created_at) DESC, id DESC'),
        };

        $matchedCollection = $query->get();
        if ($statusFilter !== '') {
            $matchedCollection = $matchedCollection->filter(function (DeliveryAssignment $asg) use ($statusFilter) {
                return $asg->getJobStatusDisplay()['key'] === $statusFilter;
            })->values();
        }

        $summary = [
            'total' => $matchedCollection->count(),
            'collection' => $matchedCollection->where('assignment_type', DeliveryAssignment::TYPE_CUSTOMER_COLLECTION)->count(),
            'final_delivery' => $matchedCollection->where('assignment_type', DeliveryAssignment::TYPE_FINAL_DELIVERY)->count(),
            'completed' => $matchedCollection->filter(fn (DeliveryAssignment $a) => $a->getJobStatusDisplay()['key'] === 'completed')->count(),
            'pending' => $matchedCollection->filter(fn (DeliveryAssignment $a) => $a->getJobStatusDisplay()['key'] !== 'completed')->count(),
        ];

        $perPage = 12;
        $page = max(1, (int) \Illuminate\Pagination\Paginator::resolveCurrentPage());
        $assignments = new \Illuminate\Pagination\LengthAwarePaginator(
            $matchedCollection->forPage($page, $perPage)->values(),
            $matchedCollection->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => array_merge($request->query(), [
                    'month' => $selectedMonth,
                    'year' => $selectedYear,
                ]),
            ]
        );

        $viewData = [
            'person' => $person,
            'heading' => $heading,
            'filterType' => $type,
            'jobTypeFilter' => $jobTypeFilter,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'sort' => $sort,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthLabel' => $monthLabel,
            'prevMonth' => $prevMonthDate->month,
            'prevYear' => $prevMonthDate->year,
            'nextMonth' => $nextMonthDate->month,
            'nextYear' => $nextMonthDate->year,
            'currentMonth' => $nowTz->month,
            'currentYear' => $nowTz->year,
            'summary' => $summary,
            'assignments' => $assignments,
            'currencyService' => $this->currencyService,
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'month' => $selectedMonth,
                'year' => $selectedYear,
                'month_label' => $monthLabel,
                'prev_month' => $prevMonthDate->month,
                'prev_year' => $prevMonthDate->year,
                'next_month' => $nextMonthDate->month,
                'next_year' => $nextMonthDate->year,
                'summary' => $summary,
                'results_html' => view('delivery-person-portal.bookings.partials.list', $viewData)->render(),
            ]);
        }

        return view('delivery-person-portal.bookings.index', $viewData);
    }

    public function cashAccount(Request $request): View|JsonResponse
    {
        $person = $this->person();
        $currency = strtoupper((string) ($person->currency ?: $this->currencyService->code()));
        $cashSummary = $this->cashService->getSummary($person, $currency);

        $tz = (string) config('app.timezone', 'UTC');
        $nowTz = \Carbon\Carbon::now($tz);
        $reqMonth = $request->input('month');
        $reqYear = $request->input('year');
        $selectedMonth = is_numeric($reqMonth) && (int) $reqMonth >= 1 && (int) $reqMonth <= 12
            ? (int) $reqMonth
            : $nowTz->month;
        $selectedYear = is_numeric($reqYear) && (int) $reqYear >= 2020 && (int) $reqYear <= 2099
            ? (int) $reqYear
            : $nowTz->year;

        $monthStart = \Carbon\Carbon::create($selectedYear, $selectedMonth, 1, 0, 0, 0, $tz);
        $nextMonthStart = $monthStart->copy()->addMonthNoOverflow()->startOfMonth();
        $prevMonthDate = $monthStart->copy()->subMonthNoOverflow();
        $nextMonthDate = $monthStart->copy()->addMonthNoOverflow();
        $monthLabel = $monthStart->format('F Y');

        $paymentChannel = trim((string) $request->input('payment_channel', ''));
        $transactionType = trim((string) $request->input('transaction_type', ''));
        $bookingReference = trim((string) $request->input('booking_reference', ''));
        $sort = (string) $request->input('sort', 'newest');
        if (! in_array($sort, ['newest', 'oldest'], true)) {
            $sort = 'newest';
        }

        $txQuery = DeliveryPersonCashTransaction::query()
            ->with(['booking', 'settlement'])
            ->where('delivery_person_id', $person->id)
            ->where('created_at', '>=', $monthStart->toDateTimeString())
            ->where('created_at', '<', $nextMonthStart->toDateTimeString());

        if ($request->filled('from_date')) {
            $txQuery->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $txQuery->whereDate('created_at', '<=', $request->input('to_date'));
        }
        if ($paymentChannel !== '') {
            $txQuery->where('payment_channel', $paymentChannel);
        }
        if ($transactionType !== '') {
            $txQuery->where('transaction_type', $transactionType);
        }
        if ($bookingReference !== '') {
            $txQuery->where(function ($q) use ($bookingReference) {
                $q->where('description', 'like', "%{$bookingReference}%")
                    ->orWhereHas('booking', fn ($bq) => $bq->where('reference', 'like', "%{$bookingReference}%"));
            });
        }

        $sort === 'oldest' ? $txQuery->orderBy('id') : $txQuery->orderByDesc('id');

        $transactions = $txQuery->paginate(15)->withQueryString();

        $viewData = [
            'person' => $person,
            'currency' => $currency,
            'currencyService' => $this->currencyService,
            'cashSummary' => $cashSummary,
            'transactions' => $transactions,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthLabel' => $monthLabel,
            'prevMonth' => $prevMonthDate->month,
            'prevYear' => $prevMonthDate->year,
            'nextMonth' => $nextMonthDate->month,
            'nextYear' => $nextMonthDate->year,
            'currentMonth' => $nowTz->month,
            'currentYear' => $nowTz->year,
            'paymentChannel' => $paymentChannel,
            'transactionType' => $transactionType,
            'bookingReference' => $bookingReference,
            'sort' => $sort,
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'month' => $selectedMonth,
                'year' => $selectedYear,
                'month_label' => $monthLabel,
                'prev_month' => $prevMonthDate->month,
                'prev_year' => $prevMonthDate->year,
                'next_month' => $nextMonthDate->month,
                'next_year' => $nextMonthDate->year,
                'table_html' => view('delivery-person-portal.partials.cash-account-table', $viewData)->render(),
            ]);
        }

        return view('delivery-person-portal.cash-account', $viewData);
    }

    public function settlements(Request $request): View|JsonResponse
    {
        $person = $this->person();
        $currency = strtoupper((string) ($person->currency ?: $this->currencyService->code()));
        $cashSummary = $this->cashService->getSummary($person, $currency);

        $tz = (string) config('app.timezone', 'UTC');
        $nowTz = \Carbon\Carbon::now($tz);
        $reqMonth = $request->input('month');
        $reqYear = $request->input('year');
        $selectedMonth = is_numeric($reqMonth) && (int) $reqMonth >= 1 && (int) $reqMonth <= 12
            ? (int) $reqMonth
            : $nowTz->month;
        $selectedYear = is_numeric($reqYear) && (int) $reqYear >= 2020 && (int) $reqYear <= 2099
            ? (int) $reqYear
            : $nowTz->year;

        $monthStart = \Carbon\Carbon::create($selectedYear, $selectedMonth, 1, 0, 0, 0, $tz);
        $nextMonthStart = $monthStart->copy()->addMonthNoOverflow()->startOfMonth();
        $prevMonthDate = $monthStart->copy()->subMonthNoOverflow();
        $nextMonthDate = $monthStart->copy()->addMonthNoOverflow();
        $monthLabel = $monthStart->format('F Y');

        $search = trim((string) $request->input('search', ''));
        $channel = trim((string) $request->input('settlement_channel', ''));
        $sort = (string) $request->input('sort', 'newest');
        if (! in_array($sort, ['newest', 'oldest', 'amount_desc'], true)) {
            $sort = 'newest';
        }

        $query = DeliveryPersonSettlement::query()
            ->with('admin')
            ->where('delivery_person_id', $person->id)
            ->whereRaw('COALESCE(settled_at, created_at) >= ? AND COALESCE(settled_at, created_at) < ?', [
                $monthStart->toDateTimeString(),
                $nextMonthStart->toDateTimeString(),
            ]);

        if ($channel !== '') {
            $query->where('settlement_channel', $channel);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('settlement_reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $monthTotalMinor = (clone $query)->get()->sum(fn ($s) => \App\Services\StoreAccountService::decimalToMinor($s->amount));
        $monthCount = (clone $query)->count();

        match ($sort) {
            'oldest' => $query->orderByRaw('COALESCE(settled_at, created_at) ASC, id ASC'),
            'amount_desc' => $query->orderByDesc('amount')->orderByDesc('id'),
            default => $query->orderByRaw('COALESCE(settled_at, created_at) DESC, id DESC'),
        };

        $settlements = $query->paginate(15)->withQueryString();

        $viewData = [
            'person' => $person,
            'currency' => $currency,
            'currencyService' => $this->currencyService,
            'cashSummary' => $cashSummary,
            'settlements' => $settlements,
            'monthTotalMinor' => $monthTotalMinor,
            'monthTotalFormatted' => $this->currencyService->formatMinor($monthTotalMinor, $currency),
            'monthCount' => $monthCount,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthLabel' => $monthLabel,
            'prevMonth' => $prevMonthDate->month,
            'prevYear' => $prevMonthDate->year,
            'nextMonth' => $nextMonthDate->month,
            'nextYear' => $nextMonthDate->year,
            'currentMonth' => $nowTz->month,
            'currentYear' => $nowTz->year,
            'search' => $search,
            'channel' => $channel,
            'sort' => $sort,
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'month' => $selectedMonth,
                'year' => $selectedYear,
                'month_label' => $monthLabel,
                'prev_month' => $prevMonthDate->month,
                'prev_year' => $prevMonthDate->year,
                'next_month' => $nextMonthDate->month,
                'next_year' => $nextMonthDate->year,
                'month_total_formatted' => $viewData['monthTotalFormatted'],
                'month_count' => $monthCount,
                'table_html' => view('delivery-person-portal.partials.settlements-table', $viewData)->render(),
            ]);
        }

        return view('delivery-person-portal.settlements', $viewData);
    }

    public function earnings(Request $request): View|JsonResponse
    {
        $person = $this->person();
        $currency = strtoupper((string) ($person->currency ?: $this->currencyService->code()));
        $month = $request->input('month');
        $jobType = $request->input('job_type');
        $wdStatus = $request->input('withdrawal_status');

        $summary = $this->withdrawalService->getMonthlySummary($person, (string) $month, $currency);
        $activeMonth = $summary['period']['month'];

        $earningsEntries = $this->withdrawalService->getMonthlyEarningsEntries($person, $activeMonth, $jobType ?: null);
        $withdrawals = $this->withdrawalService->getMonthlyWithdrawals($person, $activeMonth, $wdStatus ?: null);
        $ledgerEntries = $this->withdrawalService->getMonthlyLedgerActivity($person, $activeMonth);

        $bankAccounts = DeliveryPersonBankAccount::query()
            ->where('delivery_person_id', $person->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toSafeArray()
            ->values();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'currency' => $currency,
                'summary' => $summary,
                'summary_formatted' => [
                    'earned_month' => $this->currencyService->formatMinor($summary['earned_month_minor'], $currency),
                    'collection_month' => $this->currencyService->formatMinor($summary['collection_month_minor'], $currency),
                    'final_delivery_month' => $this->currencyService->formatMinor($summary['final_delivery_month_minor'], $currency),
                    'available_balance' => $this->currencyService->formatMinor($summary['available_balance_minor'], $currency),
                    'pending_withdrawal' => $this->currencyService->formatMinor($summary['pending_withdrawal_minor'], $currency),
                    'approved_month' => $this->currencyService->formatMinor($summary['approved_month_minor'], $currency),
                ],
                'bank_accounts' => $bankAccounts,
                'earnings_html' => view('delivery-person-portal.partials.earnings-table', [
                    'earningsEntries' => $earningsEntries,
                    'currencyService' => $this->currencyService,
                ])->render(),
                'withdrawals_html' => view('delivery-person-portal.partials.withdrawals-table', [
                    'withdrawals' => $withdrawals,
                    'currencyService' => $this->currencyService,
                ])->render(),
                'ledger_html' => view('delivery-person-portal.partials.ledger-table', [
                    'ledgerEntries' => $ledgerEntries,
                    'currencyService' => $this->currencyService,
                ])->render(),
            ]);
        }

        return view('delivery-person-portal.earnings', [
            'person' => $person,
            'currency' => $currency,
            'currencyService' => $this->currencyService,
            'summary' => $summary,
            'earningsEntries' => $earningsEntries,
            'withdrawals' => $withdrawals,
            'ledgerEntries' => $ledgerEntries,
            'bankAccounts' => $bankAccounts,
            'activeJobType' => $jobType,
            'activeWithdrawalStatus' => $wdStatus,
            'countriesMeta' => $this->bankService->getCountriesMeta(),
        ]);
    }

    public function profile(): View
    {
        $person = $this->person()->load(['store', 'postcodes']);
        $bankAccounts = DeliveryPersonBankAccount::query()
            ->where('delivery_person_id', $person->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->map->toSafeArray()
            ->values();

        return view('delivery-person-portal.profile', [
            'person' => $person,
            'bankAccounts' => $bankAccounts,
            'countriesMeta' => $this->bankService->getCountriesMeta(),
            'currencies' => \App\Support\CurrencyCatalog::CURRENCIES,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $person = $this->person();
        $validated = $request->validate([
            'availability_status' => ['required', 'in:available,busy,off_duty'],
        ]);

        $person->update($validated);

        return back()->with('success', 'Availability status updated.');
    }
}
